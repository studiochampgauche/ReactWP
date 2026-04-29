<?php

namespace ReactWP\Runtime;

class PublicPayload {

    const API_VERSION = '1.0';

    public static function bootstrap($payload = null) {

        $resolved_payload = is_array($payload) ? $payload : Bootstrap::payload();

        return self::response([
            'site' => self::site($resolved_payload['site'] ?? []),
            'system' => self::system($resolved_payload['system'] ?? Bootstrap::system()),
            'assets' => self::value($resolved_payload['assets'] ?? []),
            'navigation' => self::navigation($resolved_payload['navigation'] ?? []),
            'route' => self::route_object($resolved_payload['route'] ?? []),
            'seoDefaults' => self::value($resolved_payload['seoDefaults'] ?? []),
            'currentUser' => self::current_user(),
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

        foreach($navigation as $location => $items){
            if(!is_string($location) || $location === ''){
                continue;
            }

            $normalized[$location] = array_values(array_map(
                [self::class, 'navigation_item'],
                is_array($items) ? $items : []
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

    public static function sitemap($items) {

        return self::response([
            'items' => array_values(array_filter(array_map(function($item){
                return is_array($item) ? self::value($item) : null;
            }, is_array($items) ? $items : []))),
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

        return apply_filters('rwp_headless_current_user_payload', $payload, $user);

    }

    public static function response($payload = [], $context = []) {

        $response = [
            'apiVersion' => self::API_VERSION,
            'generatedAt' => gmdate('c'),
        ];

        if(is_array($context)){
            foreach($context as $key => $value){
                if(is_string($key) && $key !== ''){
                    $response[$key] = self::value($value);
                }
            }
        }

        return [
            ...$response,
            ...(is_array($payload) ? $payload : []),
        ];

    }

    private static function site($site) {

        $site = is_array($site) ? $site : [];

        return [
            'name' => (string)($site['name'] ?? get_bloginfo('name')),
            'description' => (string)($site['description'] ?? get_bloginfo('description')),
            'language' => (string)($site['language'] ?? self::current_language()),
            'locale' => (string)($site['locale'] ?? get_locale()),
            'homeUrl' => esc_url_raw($site['homeUrl'] ?? home_url('/')),
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
            'restUrl' => $rest_url,
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

    private static function route_object($route) {

        $route = is_array($route) ? $route : [];
        $is_404 = !empty($route['is404']);
        $path = isset($route['path']) ? RouteResolver::normalize_path($route['path']) : '/';
        $search = isset($route['search']) ? RouteResolver::normalize_search($route['search']) : '';
        $query = isset($route['query']) && is_array($route['query']) ? $route['query'] : [];
        $url = isset($route['url']) ? esc_url_raw($route['url']) : home_url($path);

        return [
            'id' => $route['id'] ?? null,
            'type' => (string)($route['type'] ?? ($is_404 ? '404' : '')),
            'template' => (string)($route['template'] ?? ($is_404 ? 'NotFound' : 'Default')),
            'status' => $is_404 ? 404 : 200,
            'lang' => self::current_language(),
            'title' => (string)($route['pageName'] ?? get_bloginfo('name')),
            'pageName' => (string)($route['pageName'] ?? get_bloginfo('name')),
            'path' => $path,
            'search' => $search,
            'query' => self::value($query),
            'url' => $url,
            'seo' => self::value($route['seo'] ?? []),
            'mediaGroups' => (string)($route['mediaGroups'] ?? ''),
            'data' => self::value($route['data'] ?? []),
            'head' => array_values(array_filter(array_map('strval', is_array($route['head'] ?? null) ? $route['head'] : []))),
            'is404' => $is_404,
            'links' => [
                'self' => esc_url_raw(add_query_arg([
                    'view' => $path . $search,
                ], rest_url('reactwp/v1/route'))),
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

    private static function navigation_item($item) {

        $item = is_array($item) ? $item : [];
        $children = isset($item['children']) && is_array($item['children'])
            ? $item['children']
            : [];

        return [
            'id' => isset($item['id']) ? (int)$item['id'] : null,
            'label' => (string)($item['label'] ?? $item['title'] ?? ''),
            'title' => (string)($item['title'] ?? $item['label'] ?? ''),
            'url' => esc_url_raw($item['url'] ?? ''),
            'path' => RouteResolver::normalize_path($item['path'] ?? '/'),
            'target' => isset($item['target']) && $item['target'] !== '' ? (string)$item['target'] : null,
            'classes' => array_values(array_filter(array_map('sanitize_html_class', (array)($item['classes'] ?? [])))),
            'children' => array_values(array_map([self::class, 'navigation_item'], $children)),
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

    private static function value($value) {

        if($value instanceof \WP_Post && $value->post_type === 'attachment'){
            return self::attachment($value->ID);
        }

        if($value instanceof \WP_Post){
            return self::post_reference($value);
        }

        if($value instanceof \WP_Term){
            return self::term_reference($value);
        }

        if($value instanceof \WP_User){
            return self::user_reference($value);
        }

        if(is_array($value)){
            $attachment = self::attachment_from_array($value);

            if($attachment){
                return $attachment;
            }

            $normalized = [];

            foreach($value as $key => $item){
                $normalized[$key] = self::value($item);
            }

            return $normalized;
        }

        if(is_object($value)){
            return null;
        }

        return $value;

    }

    private static function post_reference($post) {

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

        if(empty($value['url']) && !wp_get_attachment_url($id)){
            return null;
        }

        return self::attachment($id, $value);

    }

    private static function attachment($id, $source = []) {

        $id = (int)$id;
        $source = is_array($source) ? $source : [];
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
