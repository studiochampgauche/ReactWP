<?php

namespace ReactWP\Runtime;

class ServerRenderer {

    private const CACHE_GROUP = 'reactwp_render';
    private const FAILURE_TRANSIENT = 'rwp_ssr_circuit_open';

    public static function render($payload, $config) {

        $result = self::render_result($payload, $config);

        return is_array($result) && is_string($result['html'] ?? null)
            ? $result['html']
            : null;

    }

    public static function render_result($payload, $config) {

        $endpoint = self::endpoint();

        if($endpoint === '' || !self::endpoint_allowed($endpoint)){
            return null;
        }

        if(get_transient(self::FAILURE_TRANSIENT)){
            return null;
        }

        $route = is_array($payload['route'] ?? null) ? $payload['route'] : [];
        $cached = self::cached($route, $config);

        if($cached !== null){
            return $cached;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ];
        $secret = self::secret();

        if($secret !== ''){
            $headers['X-ReactWP-Render-Secret'] = $secret;
        }

        $request_payload = self::render_payload($payload, $route, $config);
        $request_payload = apply_filters('rwp_ssr_payload', $request_payload, $route, $config);
        $max_bytes = max(1024, (int)apply_filters('rwp_ssr_max_html_bytes', 5 * 1024 * 1024));
        $request_method = self::is_loopback_endpoint($endpoint)
            ? 'wp_remote_post'
            : 'wp_safe_remote_post';
        $response = $request_method($endpoint, [
            'headers' => $headers,
            'body' => wp_json_encode([
                'payload' => $request_payload,
                'options' => [
                    'path' => $route['path'] ?? '/',
                    'search' => $route['search'] ?? '',
                ],
            ]),
            'timeout' => min(30, max(0.5, (float)apply_filters('rwp_ssr_timeout', 2.5))),
            'redirection' => 0,
            'data_format' => 'body',
            'limit_response_size' => $max_bytes + 65536,
            'reject_unsafe_urls' => !self::is_loopback_endpoint($endpoint),
        ]);

        if(is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200){
            set_transient(self::FAILURE_TRANSIENT, 1, max(5, (int)apply_filters('rwp_ssr_circuit_seconds', 20)));
            do_action('rwp_ssr_error', $response, $route);
            return null;
        }

        $content_type = strtolower((string)wp_remote_retrieve_header($response, 'content-type'));

        if(strpos($content_type, 'application/json') === false){
            do_action('rwp_ssr_error', new \WP_Error('reactwp_ssr_invalid_content_type'), $route);
            return null;
        }

        $raw_body = (string)wp_remote_retrieve_body($response);

        if(strlen($raw_body) > $max_bytes + 65536){
            do_action('rwp_ssr_error', new \WP_Error('reactwp_ssr_response_too_large'), $route);
            return null;
        }

        $body = json_decode($raw_body, true, 64);
        $html = is_array($body) && !empty($body['ok']) && is_string($body['html'] ?? null)
            ? $body['html']
            : null;

        if($html === null || strlen($html) > $max_bytes){
            do_action('rwp_ssr_error', new \WP_Error('reactwp_ssr_invalid_response'), $route);
            return null;
        }

        $result = [
            'html' => $html,
            'generatedAtUnix' => microtime(true),
            'tags' => self::normalize_tags($body['tags'] ?? []),
        ];
        self::store($route, $config, $result);

        return $result;

    }

    public static function available() {

        $endpoint = self::endpoint();
        return $endpoint !== '' && self::endpoint_allowed($endpoint);

    }

    private static function endpoint() {

        $endpoint = defined('RWP_SSR_ENDPOINT') ? RWP_SSR_ENDPOINT : '';
        $endpoint = apply_filters('rwp_ssr_endpoint', $endpoint);

        return is_string($endpoint) ? esc_url_raw($endpoint) : '';

    }

    private static function secret() {

        $secret = defined('RWP_SSR_SECRET') ? RWP_SSR_SECRET : getenv('RWP_SSR_SECRET');
        return is_scalar($secret) ? (string)$secret : '';

    }

    private static function endpoint_allowed($endpoint) {

        $scheme = strtolower((string)wp_parse_url($endpoint, PHP_URL_SCHEME));
        $host = strtolower((string)wp_parse_url($endpoint, PHP_URL_HOST));
        $user = (string)wp_parse_url($endpoint, PHP_URL_USER);
        $password = (string)wp_parse_url($endpoint, PHP_URL_PASS);
        $query = (string)wp_parse_url($endpoint, PHP_URL_QUERY);
        $fragment = (string)wp_parse_url($endpoint, PHP_URL_FRAGMENT);

        if(
            !in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || $user !== ''
            || $password !== ''
            || $query !== ''
            || $fragment !== ''
        ){
            return false;
        }

        if(self::is_loopback_endpoint($endpoint)){
            if(strlen(self::secret()) >= 32){
                return true;
            }

            $environment = function_exists('wp_get_environment_type')
                ? wp_get_environment_type()
                : 'production';

            return in_array($environment, ['local', 'development'], true)
                && (bool)apply_filters('rwp_ssr_allow_insecure_loopback', false, $endpoint);
        }

        if($scheme !== 'https' || strlen(self::secret()) < 32){
            return false;
        }

        return (bool)apply_filters('rwp_ssr_allow_remote_endpoint', false, $endpoint);

    }

    private static function is_loopback_endpoint($endpoint) {

        $host = strtolower((string)wp_parse_url($endpoint, PHP_URL_HOST));

        return in_array($host, ['127.0.0.1', '::1', 'localhost'], true);

    }

    private static function cache_context($route, $config) {

        $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];
        $enabled = !empty($cache['html']) && (int)($cache['ttl'] ?? 0) > 0;
        $scope = ($cache['scope'] ?? 'private') === 'public' ? 'public' : 'private';

        if(!$enabled || ($scope === 'public' && is_user_logged_in())){
            return null;
        }

        $default_identity = $scope === 'private' && is_user_logged_in()
            ? 'user:' . get_current_user_id()
            : ($scope === 'public' ? 'public' : '');
        $identity = (string)apply_filters('rwp_ssr_cache_identity', $default_identity, $route, $config);

        if($identity === ''){
            return null;
        }

        $route_key = self::cache_route_key($route, $config);

        if($route_key === null){
            return null;
        }

        $key = hash('sha256', implode('|', [
            ClientCache::version(),
            $route_key,
            $identity,
        ]));

        return [
            'key' => $key,
            'ttl' => max(1, (int)$cache['ttl']),
        ];

    }

    private static function cached($route, $config) {

        $context = self::cache_context($route, $config);

        if(!$context){
            return null;
        }

        $found = false;
        $value = wp_cache_get($context['key'], self::CACHE_GROUP, false, $found);

        if($found && is_array($value) && is_string($value['html'] ?? null) && RenderCache::is_fresh($value)){
            return $value;
        }

        $value = get_transient('rwp_ssr_' . $context['key']);

        return is_array($value) && is_string($value['html'] ?? null) && RenderCache::is_fresh($value)
            ? $value
            : null;

    }

    private static function store($route, $config, $entry) {

        $context = self::cache_context($route, $config);

        if(!$context){
            return;
        }

        $entry['tags'] = array_values(array_filter(array_map('strval', (array)($entry['tags'] ?? []))));

        wp_cache_set($context['key'], $entry, self::CACHE_GROUP, $context['ttl']);
        set_transient('rwp_ssr_' . $context['key'], $entry, $context['ttl']);

    }

    private static function render_payload($payload, $route, $config) {

        $payload = is_array($payload) ? $payload : [];
        $payload = array_intersect_key($payload, array_flip([
            'site',
            'theme',
            'system',
            'assets',
            'navigation',
            'route',
            'currentUser',
            'seoDefaults',
        ]));

        if(is_array($payload['system'] ?? null)){
            unset(
                $payload['system']['restNonce'],
                $payload['system']['themeDirectory']
            );
        }

        $cache = is_array($config['cache'] ?? null) ? $config['cache'] : [];

        if(($cache['scope'] ?? 'private') === 'public'){
            $payload['currentUser'] = [
                'authenticated' => false,
            ];
        }

        $payload['route'] = $route;

        return $payload;

    }

    private static function cache_route_key($route, $config) {

        $route = is_array($route) ? $route : [];
        $search = RouteResolver::normalize_search($route['search'] ?? '');

        if($search === ''){
            return RenderStrategy::route_key($route);
        }

        if(strlen($search) > max(128, (int)apply_filters('rwp_ssr_cache_max_query_bytes', 2048, $route, $config))){
            return null;
        }

        $allowed_keys = apply_filters('rwp_ssr_cache_query_keys', [], $route, $config);
        $allowed_keys = array_values(array_unique(array_filter(array_map(function($key){
            $key = trim((string)$key);
            return preg_match('/^[a-zA-Z0-9_.-]+$/', $key) ? $key : '';
        }, (array)$allowed_keys))));
        $query = [];
        wp_parse_str(ltrim($search, '?'), $query);

        if(count($query) > 50){
            return null;
        }

        foreach(array_keys($query) as $key){
            if(!in_array((string)$key, $allowed_keys, true)){
                return null;
            }
        }

        $query = self::canonical_query($query);

        if($query === null){
            return null;
        }

        $route['search'] = $query
            ? '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986)
            : '';

        return RenderStrategy::route_key($route);

    }

    private static function canonical_query($value, $depth = 0) {

        if($depth > 2 || !is_array($value)){
            return null;
        }

        ksort($value, SORT_STRING);

        foreach($value as $key => $item){
            if(is_array($item)){
                if(count($item) > 20){
                    return null;
                }

                $item = self::canonical_query($item, $depth + 1);

                if($item === null){
                    return null;
                }

                $value[$key] = $item;
                continue;
            }

            if(!is_scalar($item) || strlen((string)$item) > 512){
                return null;
            }

            $value[$key] = (string)$item;
        }

        return $value;

    }

    private static function normalize_tags($tags) {

        return array_values(array_unique(array_filter(array_map(function($tag){
            $tag = strtolower(trim((string)$tag));
            return preg_match('/^[a-z0-9_-]+:[a-z0-9_.-]+$/', $tag) ? $tag : '';
        }, array_slice(is_array($tags) ? $tags : [], 0, 200)))));

    }

}
