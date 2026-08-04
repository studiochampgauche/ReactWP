<?php

namespace ReactWP\Runtime;

class PublicPayload {

    const API_VERSION = '1.4';
    const MAX_VALUE_DEPTH = 20;
    const MAX_ARRAY_ITEMS = 10000;
    const MAX_STRING_BYTES = 2097152;
    const MAX_HEAD_ITEMS = 100;
    const MAX_HEAD_ENTRY_BYTES = 65536;
    const MAX_NAVIGATION_LOCATIONS = 100;
    const MAX_NAVIGATION_ITEMS = 500;

    public static function bootstrap($payload = null) {

        $resolved_payload = is_array($payload) ? $payload : Bootstrap::payload();

        return self::response([
            'site' => self::site($resolved_payload['site'] ?? []),
            'theme' => self::theme($resolved_payload['theme'] ?? []),
            'system' => self::system($resolved_payload['system'] ?? Bootstrap::system()),
            'assets' => self::value($resolved_payload['assets'] ?? []),
            'navigation' => self::navigation($resolved_payload['navigation'] ?? []),
            'route' => self::route_object($resolved_payload['route'] ?? []),
            'seoDefaults' => self::value($resolved_payload['seoDefaults'] ?? []),
        ]);

    }

    public static function route($route, $context = []) {

        $route_object = self::route_object($route);

        return self::response([
            'status' => $route_object['status'],
            'route' => $route_object,
        ], $context);

    }

    public static function navigation($navigation) {

        if(!is_array($navigation)){
            return [];
        }

        $normalized = [];

        $location_count = 0;

        foreach($navigation as $location => $items){
            $location_count++;

            if($location_count > self::MAX_NAVIGATION_LOCATIONS){
                break;
            }

            if(!is_string($location) || !preg_match('/^[a-z0-9_-]{1,100}$/i', $location)){
                continue;
            }

            $normalized[$location] = array_values(array_map(
                [self::class, 'navigation_item'],
                array_slice(is_array($items) ? $items : [], 0, self::MAX_NAVIGATION_ITEMS)
            ));
        }

        return $normalized;

    }

    public static function settings() {

        $settings = apply_filters('rwp_headless_public_settings', []);

        return self::response([
            'settings' => is_array($settings) ? self::value($settings) : [],
        ]);

    }

    public static function sanitize_value($value) {

        return self::value($value);

    }

    public static function sitemap($items) {

        return self::response([
            'items' => array_values(array_filter(array_map(function($item){
                return is_array($item) ? self::value($item) : null;
            }, array_slice(is_array($items) ? $items : [], 0, 5000)))),
        ]);

    }

    public static function current_user() {

        if(!is_user_logged_in()){
            return [
                'authenticated' => false,
            ];
        }

        $user = wp_get_current_user();

        if(!$user || !$user->exists()){
            return [
                'authenticated' => false,
            ];
        }

        $payload = [
            'authenticated' => true,
            'id' => (int)$user->ID,
            'slug' => $user->user_nicename,
            'displayName' => $user->display_name,
            'email' => $user->user_email,
            'roles' => array_values((array)$user->roles),
            'capabilities' => apply_filters('rwp_headless_user_capabilities', [], $user),
            'restNonce' => wp_create_nonce('wp_rest'),
        ];

        $filtered = apply_filters('rwp_headless_current_user_payload', $payload, $user);

        return is_array($filtered) ? self::value($filtered) : $payload;

    }

    public static function response($payload = [], $context = []) {

        $metadata = [
            'apiVersion' => self::API_VERSION,
            'generatedAt' => gmdate('c'),
        ];
        $response = [];

        if(is_array($context)){
            foreach($context as $key => $value){
                if(
                    is_string($key)
                    && $key !== ''
                    && !array_key_exists($key, $metadata)
                ){
                    $response[$key] = self::value($value);
                }
            }
        }

        return [
            ...(is_array($payload) ? $payload : []),
            ...$response,
            ...$metadata,
        ];

    }

    private static function site($site) {

        $site = is_array($site) ? $site : [];

        return [
            'name' => self::scalar_string($site['name'] ?? get_bloginfo('name'), 512),
            'description' => self::scalar_string($site['description'] ?? get_bloginfo('description'), 4096),
            'language' => self::scalar_string($site['language'] ?? self::current_language(), 32),
            'locale' => self::scalar_string($site['locale'] ?? get_locale(), 64),
            'homeUrl' => esc_url_raw($site['homeUrl'] ?? home_url('/')),
            'adminUrl' => esc_url_raw($site['adminUrl'] ?? admin_url()),
        ];

    }

    private static function system($system) {

        $system = is_array($system) ? $system : [];
        $rest_url = esc_url_raw($system['restUrl'] ?? rest_url());
        $base_url = esc_url_raw($system['baseUrl'] ?? site_url('/'));

        return [
            'public' => (int)($system['public'] ?? get_option('blog_public')),
            'baseUrl' => $base_url,
            'homeUrl' => esc_url_raw($system['homeUrl'] ?? home_url('/')),
            'adminUrl' => esc_url_raw($system['adminUrl'] ?? admin_url()),
            'ajaxUrl' => esc_url_raw($system['ajaxUrl'] ?? admin_url('admin-ajax.php')),
            'restUrl' => $rest_url,
            'cacheVersion' => self::scalar_string($system['cacheVersion'] ?? ClientCache::version(), 128),
            'themeUrl' => esc_url_raw($system['themeUrl'] ?? get_stylesheet_directory_uri()),
            'routeEndpoint' => esc_url_raw($system['routeEndpoint'] ?? rest_url('reactwp/v1/route')),
            'headless' => [
                'bootstrapEndpoint' => esc_url_raw(rest_url('reactwp/v1/bootstrap')),
                'routeEndpoint' => esc_url_raw(rest_url('reactwp/v1/route')),
                'navigationEndpoint' => esc_url_raw(rest_url('reactwp/v1/navigation')),
                'settingsEndpoint' => esc_url_raw(rest_url('reactwp/v1/settings')),
                'sitemapEndpoint' => esc_url_raw(rest_url('reactwp/v1/sitemap')),
                'previewEndpoint' => esc_url_raw(rest_url('reactwp/v1/preview')),
                'currentUserEndpoint' => esc_url_raw(rest_url('reactwp/v1/auth/me')),
                'loginEndpoint' => esc_url_raw(rest_url('reactwp/v1/auth/login')),
                'logoutEndpoint' => esc_url_raw(rest_url('reactwp/v1/auth/logout')),
            ],
            'endpoints' => [
                'bootstrap' => esc_url_raw(rest_url('reactwp/v1/bootstrap')),
                'route' => esc_url_raw(rest_url('reactwp/v1/route')),
                'navigation' => esc_url_raw(rest_url('reactwp/v1/navigation')),
                'settings' => esc_url_raw(rest_url('reactwp/v1/settings')),
                'sitemap' => esc_url_raw(rest_url('reactwp/v1/sitemap')),
                'preview' => esc_url_raw(rest_url('reactwp/v1/preview')),
                'currentUser' => esc_url_raw(rest_url('reactwp/v1/auth/me')),
                'login' => esc_url_raw(rest_url('reactwp/v1/auth/login')),
                'logout' => esc_url_raw(rest_url('reactwp/v1/auth/logout')),
            ],
        ];

    }

    private static function theme($theme) {

        $theme = is_array($theme) ? $theme : [];
        $wp_theme = wp_get_theme();

        return [
            'name' => self::scalar_string($theme['name'] ?? $wp_theme->get('Name'), 512),
            'slug' => self::scalar_string($theme['slug'] ?? $wp_theme->get_stylesheet(), 128),
            'version' => self::scalar_string($theme['version'] ?? $wp_theme->get('Version'), 128),
        ];

    }

    private static function route_object($route) {

        $route = is_array($route) ? $route : [];
        $is_404 = !empty($route['is404']);
        $path = isset($route['path']) ? RouteResolver::normalize_path($route['path']) : '/';
        $search = isset($route['search']) ? RouteResolver::normalize_search($route['search']) : '';
        $query = isset($route['query']) && is_array($route['query']) ? $route['query'] : [];
        $url = isset($route['url']) ? esc_url_raw($route['url']) : home_url($path);
        $template = self::scalar_string($route['template'] ?? ($is_404 ? 'NotFound' : 'Default'), 128);

        if(!preg_match('/^[A-Za-z][A-Za-z0-9_.-]{0,127}$/', $template)){
            $template = $is_404 ? 'NotFound' : 'Default';
        }

        $head = array_values(array_filter(array_map(function($entry){
            $entry = is_string($entry) ? trim($entry) : '';
            return $entry !== '' && strlen($entry) <= self::MAX_HEAD_ENTRY_BYTES ? $entry : '';
        }, array_slice(is_array($route['head'] ?? null) ? $route['head'] : [], 0, self::MAX_HEAD_ITEMS))));

        $id = $route['id'] ?? null;

        if(is_numeric($id)){
            $id = (int)$id;
        } elseif(!is_string($id) || !preg_match('/^(?:user|term)_\d+$/', $id)){
            $id = null;
        }

        return [
            'id' => $id,
            'type' => self::scalar_string($route['type'] ?? ($is_404 ? '404' : ''), 128),
            'template' => $template,
            'status' => $is_404 ? 404 : 200,
            'lang' => self::scalar_string($route['lang'] ?? self::current_language(), 32),
            'title' => self::scalar_string($route['pageName'] ?? get_bloginfo('name'), 4096),
            'pageName' => self::scalar_string($route['pageName'] ?? get_bloginfo('name'), 4096),
            'path' => $path,
            'search' => $search,
            'query' => self::value($query),
            'url' => $url,
            'seo' => self::value($route['seo'] ?? []),
            'mediaGroups' => self::scalar_string($route['mediaGroups'] ?? '', 512),
            'data' => self::value($route['data'] ?? []),
            'head' => $head,
            'render' => self::render_config($route['render'] ?? []),
            'is404' => $is_404,
            'links' => [
                'self' => esc_url_raw(add_query_arg([
                    'view' => $path . $search,
                ], rest_url('reactwp/v1/route'))),
            ],
        ];

    }

    private static function render_config($config) {

        $config = RenderStrategy::normalize($config);

        return [
            'mode' => $config['mode'],
            'cache' => [
                'html' => (bool)$config['cache']['html'],
                'scope' => $config['cache']['scope'],
                'ttl' => (int)$config['cache']['ttl'],
                'payload' => (bool)$config['cache']['payload'],
                'media' => (bool)$config['cache']['media'],
                'tags' => array_values($config['cache']['tags']),
            ],
        ];

    }

    private static function current_language() {

        if(function_exists('pll_current_language')){
            $language = pll_current_language('slug');

            if(is_string($language) && $language !== ''){
                return $language;
            }
        }

        return defined('CL') ? CL : substr(get_locale(), 0, 2);

    }

    private static function navigation_item($item, $depth = 0) {

        $item = is_array($item) ? $item : [];
        $children = $depth < 10 && isset($item['children']) && is_array($item['children'])
            ? $item['children']
            : [];

        return [
            'id' => isset($item['id']) ? (int)$item['id'] : null,
            'label' => (string)($item['label'] ?? $item['title'] ?? ''),
            'title' => (string)($item['title'] ?? $item['label'] ?? ''),
            'url' => esc_url_raw($item['url'] ?? ''),
            'path' => RouteResolver::normalize_path($item['path'] ?? '/'),
            'target' => isset($item['target']) && in_array($item['target'], ['_blank', '_self', '_parent', '_top'], true)
                ? (string)$item['target']
                : null,
            'classes' => array_values(array_filter(array_map(
                'sanitize_html_class',
                array_slice((array)($item['classes'] ?? []), 0, 100)
            ))),
            'children' => array_values(array_map(function($child) use ($depth){
                return self::navigation_item($child, $depth + 1);
            }, array_slice($children, 0, self::MAX_NAVIGATION_ITEMS))),
        ];

    }

    private static function languages() {

        if(!function_exists('get_field')){
            return [];
        }

        $langs = get_field('langs', 'option');

        if(!is_array($langs)){
            return [];
        }

        return array_values(array_filter(array_map(function($lang){
            if(!is_array($lang) || empty($lang['code'])){
                return null;
            }

            return [
                'name' => (string)($lang['name'] ?? strtoupper((string)$lang['code'])),
                'code' => sanitize_key($lang['code']),
            ];
        }, $langs)));

    }

    private static function value($value, $depth = 0) {

        if($depth > self::MAX_VALUE_DEPTH){
            return null;
        }

        if($value instanceof \WP_Post && $value->post_type === 'attachment'){
            return self::attachment($value->ID);
        }

        if($value instanceof \WP_Post){
            if(!RouteResolver::is_public_object($value)){
                return null;
            }

            return self::post_reference($value);
        }

        if($value instanceof \WP_Term){
            $taxonomy = get_taxonomy($value->taxonomy);

            if(!$taxonomy || empty($taxonomy->public)){
                return null;
            }

            return self::term_reference($value);
        }

        if($value instanceof \WP_User){
            if(!RouteResolver::is_public_author($value)){
                return null;
            }

            return self::user_reference($value);
        }

        if(is_array($value)){
            $possible_attachment_id = isset($value['ID'])
                ? (int)$value['ID']
                : (isset($value['id']) ? (int)$value['id'] : 0);
            $looks_like_attachment = isset($value['url'])
                || isset($value['mime_type'])
                || isset($value['filename'])
                || isset($value['sizes']);

            if(
                $possible_attachment_id
                && $looks_like_attachment
                && get_post_type($possible_attachment_id) === 'attachment'
            ){
                return self::attachment_from_array($value);
            }

            $normalized = [];

            $count = 0;

            foreach($value as $key => $item){
                $count++;

                if($count > self::MAX_ARRAY_ITEMS){
                    break;
                }

                $normalized[$key] = self::value($item, $depth + 1);
            }

            return $normalized;
        }

        if(is_object($value)){
            return null;
        }

        if(is_string($value) && strlen($value) > self::MAX_STRING_BYTES){
            return null;
        }

        if(is_float($value) && !is_finite($value)){
            return null;
        }

        return $value;

    }

    private static function scalar_string($value, $max_bytes) {

        if(!is_scalar($value) || is_bool($value)){
            return '';
        }

        $value = (string)$value;
        $max_bytes = max(1, (int)$max_bytes);

        return strlen($value) <= $max_bytes ? $value : '';

    }

    private static function post_reference($post) {

        if(!RouteResolver::is_public_object($post)){
            return null;
        }

        $url = get_permalink($post);

        return [
            'id' => (int)$post->ID,
            'type' => $post->post_type,
            'title' => get_the_title($post),
            'url' => $url && !is_wp_error($url) ? esc_url_raw($url) : '',
            'path' => $url && !is_wp_error($url)
                ? RouteResolver::normalize_path(wp_parse_url($url, PHP_URL_PATH) ?: '/')
                : '/',
        ];

    }

    private static function term_reference($term) {

        $url = get_term_link($term);

        return [
            'id' => (int)$term->term_id,
            'type' => 'term',
            'taxonomy' => $term->taxonomy,
            'slug' => $term->slug,
            'title' => $term->name,
            'url' => $url && !is_wp_error($url) ? esc_url_raw($url) : '',
            'path' => $url && !is_wp_error($url)
                ? RouteResolver::normalize_path(wp_parse_url($url, PHP_URL_PATH) ?: '/')
                : '/',
        ];

    }

    private static function user_reference($user) {

        $url = get_author_posts_url($user->ID);

        return [
            'id' => (int)$user->ID,
            'type' => 'user',
            'slug' => $user->user_nicename,
            'title' => $user->display_name,
            'url' => esc_url_raw($url),
            'path' => RouteResolver::normalize_path(wp_parse_url($url, PHP_URL_PATH) ?: '/'),
        ];

    }

    private static function attachment_from_array($value) {

        $id = isset($value['ID'])
            ? (int)$value['ID']
            : (isset($value['id']) ? (int)$value['id'] : 0);

        if(!$id || get_post_type($id) !== 'attachment'){
            return null;
        }

        return self::attachment($id, $value);

    }

    private static function attachment($id, $source = []) {

        $id = (int)$id;
        $source = is_array($source) ? $source : [];
        $attachment = get_post($id);

        if(
            !$attachment instanceof \WP_Post
            || $attachment->post_type !== 'attachment'
            || in_array($attachment->post_status, ['trash', 'private'], true)
            || $attachment->post_password !== ''
        ){
            return null;
        }

        if($attachment->post_parent){
            $parent = get_post($attachment->post_parent);

            if($parent instanceof \WP_Post && !RouteResolver::is_public_object($parent)){
                return null;
            }
        }

        if(empty($source['url']) && !wp_get_attachment_url($id)){
            return null;
        }

        $meta = wp_get_attachment_metadata($id);
        $sizes = [];

        foreach((array)($source['sizes'] ?? []) as $name => $size_url){
            if(is_string($size_url) && filter_var($size_url, FILTER_VALIDATE_URL)){
                $sizes[$name] = esc_url_raw($size_url);
            }
        }

        return [
            'id' => $id,
            'url' => esc_url_raw($source['url'] ?? wp_get_attachment_url($id)),
            'alt' => (string)($source['alt'] ?? get_post_meta($id, '_wp_attachment_image_alt', true)),
            'width' => isset($source['width'])
                ? (int)$source['width']
                : (int)($meta['width'] ?? 0),
            'height' => isset($source['height'])
                ? (int)$source['height']
                : (int)($meta['height'] ?? 0),
            'mimeType' => (string)get_post_mime_type($id),
            'sizes' => $sizes,
        ];

    }

}
