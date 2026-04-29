<?php

namespace ReactWP\Runtime;

class HeadlessApi {

    const REST_NAMESPACE = 'reactwp/v1';
    const LOGIN_LIMIT = 5;
    const LOGIN_LOCK_SECONDS = 600;

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
            'methods' => 'GET',
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

    public static function public_permission() {

        return true;

    }

    public static function auth_read_permission() {

        return self::origin_permission();

    }

    public static function auth_write_permission() {

        $origin_permission = self::origin_permission();

        if(is_wp_error($origin_permission)){
            return $origin_permission;
        }

        if(!self::is_secure_auth_origin()){
            return new \WP_Error(
                'reactwp_headless_insecure_auth',
                __('Headless authentication requires HTTPS, except for local development origins.', 'reactwp'),
                ['status' => 403]
            );
        }

        return true;

    }

    public static function bootstrap(\WP_REST_Request $request) {

        self::switch_language($request);

        return self::response(PublicPayload::bootstrap());

    }

    public static function navigation(\WP_REST_Request $request) {

        self::switch_language($request);

        $location = sanitize_key((string)$request->get_param('location'));
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
            'posts_per_page' => $limit,
            'fields' => 'ids',
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
        ]);
        $items = [];

        foreach($query->posts as $post_id){
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
        $token = (string)$request->get_param('token');
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

        $route = RouteResolver::from_post_id($validated_post_id);

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

        if($username === '' || $password === ''){
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

        foreach((array)$origins as $origin){
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

        $lang = sanitize_key((string)$request->get_param('lang'));

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

        if($origin === '' || in_array($origin, self::allowed_origins(), true)){
            return true;
        }

        return new \WP_Error(
            'reactwp_headless_origin_denied',
            __('This origin is not allowed to use headless authentication.', 'reactwp'),
            ['status' => 403]
        );

    }

    private static function is_secure_auth_origin() {

        $origin = self::request_origin();

        if($origin === ''){
            return true;
        }

        $scheme = wp_parse_url($origin, PHP_URL_SCHEME);

        if($scheme === 'https' || self::is_local_origin($origin)){
            return true;
        }

        return (bool)apply_filters('rwp_headless_allow_insecure_auth', false, $origin);

    }

    private static function request_origin() {

        return self::normalize_origin(get_http_origin());

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

        $rows_count = (int)get_option('options_headless_allowed_origins', 0);

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

        if(!in_array($scheme, ['http', 'https'], true) || $host === ''){
            return '';
        }

        return $scheme . '://' . $host . ($port ? ':' . (int)$port : '');

    }

    private static function is_local_origin($origin) {

        $host = strtolower((string)wp_parse_url($origin, PHP_URL_HOST));

        return in_array($host, ['localhost', '127.0.0.1', '::1'], true);

    }

    private static function login_limit_error(\WP_REST_Request $request, $username) {

        $key = self::login_limit_key($request, $username);
        $attempts = (int)get_transient($key);

        if($attempts < self::LOGIN_LIMIT){
            return null;
        }

        return new \WP_Error(
            'reactwp_headless_login_limited',
            __('Too many login attempts. Try again later.', 'reactwp'),
            ['status' => 429]
        );

    }

    private static function record_failed_login(\WP_REST_Request $request, $username) {

        $key = self::login_limit_key($request, $username);
        $attempts = (int)get_transient($key);

        set_transient($key, $attempts + 1, self::LOGIN_LOCK_SECONDS);

    }

    private static function clear_failed_login(\WP_REST_Request $request, $username) {

        delete_transient(self::login_limit_key($request, $username));

    }

    private static function login_limit_key(\WP_REST_Request $request, $username) {

        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $username = strtolower(sanitize_user((string)$username));

        return 'rwp_headless_login_' . md5($ip . '|' . $username);

    }

    private static function login_failed_error() {

        return new \WP_Error(
            'reactwp_headless_login_failed',
            __('Invalid login credentials.', 'reactwp'),
            ['status' => 403]
        );

    }

}
