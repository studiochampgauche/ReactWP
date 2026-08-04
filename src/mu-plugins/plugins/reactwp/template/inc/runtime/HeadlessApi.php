<?php

namespace ReactWP\Runtime;

class HeadlessApi {

    const REST_NAMESPACE = 'reactwp/v1';
    const LOGIN_LIMIT = 5;
    const LOGIN_IP_LIMIT = 25;
    const LOGIN_LOCK_SECONDS = 600;
    const MAX_USERNAME_BYTES = 254;
    const MAX_PASSWORD_BYTES = 4096;

    private static $booted = false;

    public static function boot() {

        if(self::$booted){
            return;
        }

        self::$booted = true;

        add_filter('rwp_allowed_rest_routes', [self::class, 'allowed_rest_routes']);
        add_filter('allowed_http_origins', [self::class, 'allowed_http_origins']);
        add_action('rest_api_init', [self::class, 'register_routes']);

    }

    public static function register_routes() {

        register_rest_route(self::REST_NAMESPACE, '/bootstrap', [
            'methods' => 'GET',
            'callback' => [self::class, 'bootstrap'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/navigation', [
            'methods' => 'GET',
            'callback' => [self::class, 'navigation'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/settings', [
            'methods' => 'GET',
            'callback' => [self::class, 'settings'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/sitemap', [
            'methods' => 'GET',
            'callback' => [self::class, 'sitemap'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/preview', [
            'methods' => ['GET', 'POST'],
            'callback' => [self::class, 'preview'],
            'permission_callback' => [self::class, 'public_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/auth/me', [
            'methods' => 'GET',
            'callback' => [self::class, 'current_user'],
            'permission_callback' => [self::class, 'auth_read_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/auth/login', [
            'methods' => 'POST',
            'callback' => [self::class, 'login'],
            'permission_callback' => [self::class, 'auth_write_permission'],
        ]);

        register_rest_route(self::REST_NAMESPACE, '/auth/logout', [
            'methods' => 'POST',
            'callback' => [self::class, 'logout'],
            'permission_callback' => [self::class, 'auth_read_permission'],
        ]);

    }

    public static function allowed_rest_routes($routes) {

        $routes = is_array($routes) ? $routes : [];

        return array_values(array_unique(array_merge($routes, [
            '/reactwp/v1/bootstrap',
            '/reactwp/v1/route',
            '/reactwp/v1/navigation',
            '/reactwp/v1/settings',
            '/reactwp/v1/sitemap',
            '/reactwp/v1/preview',
            '/reactwp/v1/auth/me',
            '/reactwp/v1/auth/login',
            '/reactwp/v1/auth/logout',
        ])));

    }

    public static function allowed_http_origins($origins) {

        return array_values(array_unique(array_merge(
            is_array($origins) ? $origins : [],
            self::allowed_origins()
        )));

    }

    public static function send_cors_headers($served, $result = null, $request = null, $server = null) {

        $route = $request instanceof \WP_REST_Request
            ? $request->get_route()
            : (function_exists('rwp_requested_rest_route') ? rwp_requested_rest_route() : '');

        if(!RestAccess::is_namespace($route, '/' . self::REST_NAMESPACE)){
            return function_exists('rest_send_cors_headers')
                ? rest_send_cors_headers($served)
                : $served;
        }

        $origin = self::request_origin();

        if($origin !== '' && in_array($origin, self::allowed_origins(), true)){
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Access-Control-Allow-Methods: OPTIONS, GET, POST, PUT, PATCH, DELETE');
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce, X-ReactWP-Preview-Token');
            header('Access-Control-Allow-Credentials: true');
            header('Vary: Origin', false);
        } elseif(!headers_sent()){
            header('Vary: Origin', false);
        }

        return $served;

    }

    public static function public_permission($request = null) {

        if(is_user_logged_in() && current_user_can('manage_options')){
            return true;
        }

        $limit = min(100000, max(0, (int)apply_filters('rwp_headless_public_rate_limit', 240, $request)));
        $window = min(3600, max(10, (int)apply_filters('rwp_headless_public_rate_window', 60, $request)));

        if($limit === 0){
            return true;
        }

        $ip = self::client_ip($request);

        $ip = $ip !== '' ? $ip : 'unknown';

        $bucket = (int)floor(time() / $window);
        $key = 'public_' . hash('sha256', $ip . '|' . $bucket);
        $requests = self::increment_rate_limit($key, $window + 5);

        if($requests <= $limit){
            return true;
        }

        return new \WP_Error(
            'reactwp_headless_rate_limited',
            __('Too many public API requests. Try again shortly.', 'reactwp'),
            [
                'status' => 429,
                'retry_after' => $window,
            ]
        );

    }

    public static function auth_read_permission($request = null) {

        $origin_permission = self::origin_permission();

        if(is_wp_error($origin_permission)){
            return $origin_permission;
        }

        return self::secure_auth_permission();

    }

    public static function auth_write_permission($request = null) {

        $origin_permission = self::origin_permission();

        if(is_wp_error($origin_permission)){
            return $origin_permission;
        }

        $secure_permission = self::secure_auth_permission();

        if(is_wp_error($secure_permission)){
            return $secure_permission;
        }

        if(
            $request instanceof \WP_REST_Request
            && apply_filters('rwp_headless_require_json_auth', true, $request)
        ){
            $content_type = strtolower(trim((string)$request->get_header('Content-Type')));

            if(strpos($content_type, 'application/json') !== 0){
                return new \WP_Error(
                    'reactwp_headless_invalid_content_type',
                    __('Headless login requests must use application/json.', 'reactwp'),
                    ['status' => 415]
                );
            }
        }

        return true;

    }

    public static function bootstrap(\WP_REST_Request $request) {

        self::switch_language($request);

        $view = $request->get_param('view');

        if($view !== null && $view !== '' && !RestAccess::is_safe_view($view)){
            return new \WP_Error(
                'reactwp_bootstrap_invalid_view',
                __('The bootstrap view must be a valid local path.', 'reactwp'),
                ['status' => 400]
            );
        }

        $resolved_view = is_string($view) && trim($view) !== ''
            ? wp_unslash($view)
            : (wp_parse_url(home_url('/'), PHP_URL_PATH) ?: '/');
        $route = RouteResolver::from_path($resolved_view);
        $payload = Bootstrap::payload($route);

        return self::response(PublicPayload::bootstrap($payload));

    }

    public static function navigation(\WP_REST_Request $request) {

        self::switch_language($request);

        $location = substr(sanitize_key((string)$request->get_param('location')), 0, 100);
        $payload = $location !== ''
            ? [$location => MenuBuilder::from_location($location)]
            : MenuBuilder::all();

        return self::response(PublicPayload::response([
            'navigation' => PublicPayload::navigation($payload),
        ]));

    }

    public static function settings(\WP_REST_Request $request) {

        self::switch_language($request);

        return self::response(PublicPayload::settings());

    }

    public static function sitemap(\WP_REST_Request $request) {

        self::switch_language($request);

        $post_types = apply_filters(
            'rwp_headless_sitemap_post_types',
            get_post_types(['public' => true], 'names')
        );
        $post_types = array_values(array_diff((array)$post_types, ['attachment']));
        $limit = min(1000, max(1, (int)apply_filters('rwp_headless_sitemap_limit', 500)));
        $query = new \WP_Query([
            'post_type' => $post_types,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);
        $items = [];

        foreach($query->posts as $post_id){
            $post = get_post($post_id);

            if(!$post instanceof \WP_Post || !RouteResolver::is_public_object($post)){
                continue;
            }

            $url = get_permalink($post_id);

            if(!$url || is_wp_error($url)){
                continue;
            }

            $items[] = [
                'id' => (int)$post_id,
                'type' => get_post_type($post_id),
                'title' => get_the_title($post_id),
                'url' => $url,
                'path' => RouteResolver::normalize_path(wp_parse_url($url, PHP_URL_PATH) ?: '/'),
                'modifiedAt' => get_post_modified_time('c', true, $post_id),
            ];
        }

        return self::response(PublicPayload::sitemap(apply_filters('rwp_headless_sitemap_items', $items)));

    }

    public static function preview(\WP_REST_Request $request) {

        self::switch_language($request);

        $post_id = absint($request->get_param('postId') ?: $request->get_param('id'));
        $token = self::preview_token($request);

        if($token === ''){
            return new \WP_Error(
                'reactwp_preview_token_missing',
                __('A preview token is required.', 'reactwp'),
                ['status' => 403]
            );
        }

        $validated_post_id = PreviewToken::validate($token, $post_id);

        if(is_wp_error($validated_post_id)){
            return $validated_post_id;
        }

        $post = get_post($validated_post_id);

        if(!$post instanceof \WP_Post || $post->post_status === 'trash'){
            return new \WP_Error(
                'reactwp_preview_not_found',
                __('Preview post not found.', 'reactwp'),
                ['status' => 404]
            );
        }

        $route = RouteResolver::from_post_id($validated_post_id, null, null, true);

        return self::no_store_response(PublicPayload::route($route, [
            'preview' => true,
        ]));

    }

    public static function current_user(\WP_REST_Request $request) {

        $permission = self::origin_permission();

        if(is_wp_error($permission)){
            return $permission;
        }

        return self::no_store_response(PublicPayload::response([
            'currentUser' => PublicPayload::current_user(),
        ]));

    }

    public static function login(\WP_REST_Request $request) {

        $params = $request->get_json_params();
        $params = is_array($params) ? $params : $request->get_body_params();
        $username = isset($params['username']) && is_scalar($params['username'])
            ? sanitize_user(wp_unslash((string)$params['username']))
            : '';
        $password = isset($params['password']) && is_scalar($params['password'])
            ? (string)wp_unslash((string)$params['password'])
            : '';
        $remember = !empty($params['remember']);
        $limit_error = self::login_limit_error($request, $username);

        if(is_wp_error($limit_error)){
            return $limit_error;
        }

        if(
            $username === ''
            || $password === ''
            || strlen($username) > self::MAX_USERNAME_BYTES
            || strlen($password) > self::MAX_PASSWORD_BYTES
        ){
            self::record_failed_login($request, $username);

            return self::login_failed_error();
        }

        $user = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember,
        ], is_ssl());

        if(is_wp_error($user)){
            self::record_failed_login($request, $username);

            return self::login_failed_error();
        }

        self::clear_failed_login($request, $username);
        wp_set_current_user($user->ID);

        return self::no_store_response(PublicPayload::response([
            'currentUser' => PublicPayload::current_user(),
        ]));

    }

    public static function logout(\WP_REST_Request $request) {

        $nonce = (string)$request->get_header('X-WP-Nonce');

        if(is_user_logged_in() && !wp_verify_nonce($nonce, 'wp_rest')){
            return new \WP_Error(
                'reactwp_headless_invalid_nonce',
                __('Invalid REST nonce.', 'reactwp'),
                ['status' => 403]
            );
        }

        wp_logout();

        return self::no_store_response(PublicPayload::response([
            'currentUser' => [
                'authenticated' => false,
            ],
        ]));

    }

    public static function allowed_origins() {

        $origins = array_merge(
            self::default_origins(),
            self::option_origins()
        );

        $origins = apply_filters('rwp_headless_allowed_origins', $origins);
        $normalized = [];

        foreach(array_slice((array)$origins, 0, 100) as $origin){
            $origin = self::normalize_origin($origin);

            if($origin !== ''){
                $normalized[] = $origin;
            }
        }

        return array_values(array_unique($normalized));

    }

    private static function response($payload) {

        return rest_ensure_response($payload);

    }

    private static function no_store_response($payload) {

        $response = rest_ensure_response($payload);
        $response->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->header('Pragma', 'no-cache');

        return $response;

    }

    private static function switch_language(\WP_REST_Request $request) {

        $lang = substr(sanitize_key((string)$request->get_param('lang')), 0, 32);

        if($lang === ''){
            return;
        }

        if(function_exists('pll_languages_list')){
            $languages = pll_languages_list(['fields' => 'slug']);

            if(!in_array($lang, (array)$languages, true)){
                return;
            }
        }

        if(function_exists('pll_switch_language')){
            pll_switch_language($lang);
        }

        do_action('wpml_switch_language', $lang);

    }

    private static function origin_permission() {

        $origin = self::request_origin();
        $fetch_site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) && is_string($_SERVER['HTTP_SEC_FETCH_SITE'])
            ? strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE']))
            : '';

        if($origin === '' && in_array($fetch_site, ['cross-site', 'same-site'], true)){
            return new \WP_Error(
                'reactwp_headless_origin_missing',
                __('An explicit allowed origin is required for cross-site authentication.', 'reactwp'),
                ['status' => 403]
            );
        }

        if($origin === '' || in_array($origin, self::allowed_origins(), true)){
            return true;
        }

        return new \WP_Error(
            'reactwp_headless_origin_denied',
            __('This origin is not allowed to use headless authentication.', 'reactwp'),
            ['status' => 403]
        );

    }

    public static function is_same_origin_request() {

        $origin = self::request_origin();

        if($origin === ''){
            $fetch_site = isset($_SERVER['HTTP_SEC_FETCH_SITE']) && is_string($_SERVER['HTTP_SEC_FETCH_SITE'])
                ? strtolower(trim($_SERVER['HTTP_SEC_FETCH_SITE']))
                : '';

            return !in_array($fetch_site, ['cross-site', 'same-site'], true);
        }

        $same_origins = array_filter(array_map([self::class, 'normalize_origin'], self::default_origins()));

        return in_array($origin, $same_origins, true);

    }

    private static function secure_auth_permission() {

        if(self::is_secure_auth_request()){
            return true;
        }

        return new \WP_Error(
            'reactwp_headless_insecure_auth',
            __('Headless authentication requires HTTPS, except for local development requests.', 'reactwp'),
            ['status' => 403]
        );

    }

    private static function is_secure_auth_request() {

        $origin = self::request_origin();

        if(is_ssl()){
            return true;
        }

        if(self::is_local_request() && ($origin === '' || self::is_local_origin($origin))){
            return true;
        }

        return (bool)apply_filters('rwp_headless_allow_insecure_auth', false, $origin);

    }

    private static function request_origin() {

        return self::normalize_origin(get_http_origin());

    }

    private static function preview_token(\WP_REST_Request $request) {

        $token = trim((string)$request->get_header('X-ReactWP-Preview-Token'));
        $authorization = trim((string)$request->get_header('Authorization'));

        if($token === '' && preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)){
            $token = trim($matches[1]);
        }

        if(
            $token === ''
            && apply_filters('rwp_headless_allow_preview_query_token', false)
        ){
            $token = trim((string)$request->get_param('token'));
        }

        return $token;

    }

    private static function default_origins() {

        return [
            home_url('/'),
            site_url('/'),
        ];

    }

    private static function option_origins() {

        $origins = [];
        $option = get_option('rwp_headless_allowed_origins');

        if(is_string($option)){
            $origins = preg_split('/[\r\n,]+/', $option);
        } elseif(is_array($option)){
            $origins = $option;
        }

        $rows_count = min(100, max(0, (int)get_option('options_headless_allowed_origins', 0)));

        for($index = 0; $index < $rows_count; $index++){
            $origin = get_option("options_headless_allowed_origins_{$index}_origin");

            if(is_string($origin) && $origin !== ''){
                $origins[] = $origin;
            }
        }

        return $origins;

    }

    private static function normalize_origin($origin) {

        $origin = is_string($origin) ? trim($origin) : '';

        if($origin === '' || $origin === '*'){
            return '';
        }

        $scheme = strtolower((string)wp_parse_url($origin, PHP_URL_SCHEME));
        $host = strtolower((string)wp_parse_url($origin, PHP_URL_HOST));
        $port = wp_parse_url($origin, PHP_URL_PORT);
        $user = wp_parse_url($origin, PHP_URL_USER);
        $pass = wp_parse_url($origin, PHP_URL_PASS);

        if(
            !in_array($scheme, ['http', 'https'], true)
            || $host === ''
            || $user !== null
            || $pass !== null
            || ($port !== null && ((int)$port < 1 || (int)$port > 65535))
        ){
            return '';
        }

        return $scheme . '://' . $host . ($port ? ':' . (int)$port : '');

    }

    private static function is_local_origin($origin) {

        $host = strtolower((string)wp_parse_url($origin, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);

    }

    private static function is_local_request() {

        $host = isset($_SERVER['HTTP_HOST']) && is_string($_SERVER['HTTP_HOST'])
            ? strtolower(trim($_SERVER['HTTP_HOST']))
            : '';
        $host = preg_replace('/:\d+$/', '', $host);
        $remote = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : '';

        return in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1'], true)
            && filter_var($remote, FILTER_VALIDATE_IP)
            && in_array($remote, ['127.0.0.1', '::1'], true);

    }

    private static function login_limit_error(\WP_REST_Request $request, $username) {

        $user_attempts = self::rate_limit_value(self::login_limit_key($request, $username));
        $ip_attempts = self::rate_limit_value(self::login_ip_limit_key($request));

        if($user_attempts < self::LOGIN_LIMIT && $ip_attempts < self::LOGIN_IP_LIMIT){
            return null;
        }

        return new \WP_Error(
            'reactwp_headless_login_limited',
            __('Too many login attempts. Try again later.', 'reactwp'),
            ['status' => 429]
        );

    }

    private static function record_failed_login(\WP_REST_Request $request, $username) {

        self::increment_rate_limit(self::login_limit_key($request, $username), self::LOGIN_LOCK_SECONDS);
        self::increment_rate_limit(self::login_ip_limit_key($request), self::LOGIN_LOCK_SECONDS);

    }

    private static function clear_failed_login(\WP_REST_Request $request, $username) {

        self::delete_rate_limit(self::login_limit_key($request, $username));

    }

    private static function login_limit_key(\WP_REST_Request $request, $username) {

        $ip = self::client_ip($request) ?: 'unknown';
        $username = strtolower(sanitize_user((string)$username));

        return 'login_user_' . hash('sha256', $ip . '|' . $username);

    }

    private static function login_ip_limit_key(\WP_REST_Request $request) {

        $ip = self::client_ip($request) ?: 'unknown';

        return 'login_ip_' . hash('sha256', $ip);

    }

    private static function increment_rate_limit($key, $ttl) {

        $key = sanitize_key((string)$key);
        $ttl = max(1, (int)$ttl);
        $cache_key = 'rwp_' . $key;

        if(wp_using_ext_object_cache()){
            if(wp_cache_add($cache_key, 1, 'reactwp_rate_limits', $ttl)){
                return 1;
            }

            $incremented = wp_cache_incr($cache_key, 1, 'reactwp_rate_limits');

            if($incremented !== false){
                return (int)$incremented;
            }
        }

        $lock = self::acquire_rate_limit_lock($key);

        if($lock === ''){
            return PHP_INT_MAX;
        }

        $transient_key = 'rwp_' . substr(hash('sha256', $key), 0, 40);

        try {
            $attempts = (int)get_transient($transient_key) + 1;
            set_transient($transient_key, $attempts, $ttl);
        } finally {
            delete_option($lock);
        }

        return $attempts;

    }

    private static function rate_limit_value($key) {

        $key = sanitize_key((string)$key);
        $cache_key = 'rwp_' . $key;

        if(wp_using_ext_object_cache()){
            $cached = wp_cache_get($cache_key, 'reactwp_rate_limits');

            if($cached !== false){
                return (int)$cached;
            }
        }

        return (int)get_transient('rwp_' . substr(hash('sha256', $key), 0, 40));

    }

    private static function delete_rate_limit($key) {

        $key = sanitize_key((string)$key);
        wp_cache_delete('rwp_' . $key, 'reactwp_rate_limits');
        delete_transient('rwp_' . substr(hash('sha256', $key), 0, 40));

    }

    private static function acquire_rate_limit_lock($key) {

        $lock = '_rwp_rate_lock_' . substr(hash('sha256', (string)$key), 0, 32);

        for($attempt = 0; $attempt < 20; $attempt++){
            $expires = time() + 5;

            if(add_option($lock, $expires, '', 'no')){
                return $lock;
            }

            $current_expiry = (int)get_option($lock, 0);

            if($current_expiry > 0 && $current_expiry < time()){
                delete_option($lock);
                continue;
            }

            usleep(5000);
        }

        return '';

    }

    private static function client_ip($request = null) {

        $remote_address = isset($_SERVER['REMOTE_ADDR']) && is_string($_SERVER['REMOTE_ADDR'])
            ? $_SERVER['REMOTE_ADDR']
            : '';
        $ip = (string)apply_filters('rwp_headless_client_ip', $remote_address, $request);

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';

    }

    private static function login_failed_error() {

        return new \WP_Error(
            'reactwp_headless_login_failed',
            __('Invalid login credentials.', 'reactwp'),
            ['status' => 403]
        );

    }

}
