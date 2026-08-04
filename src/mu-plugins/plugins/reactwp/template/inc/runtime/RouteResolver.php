<?php

namespace ReactWP\Runtime;

class RouteResolver {

    private const MAX_PATH_BYTES = 2048;
    private const MAX_QUERY_BYTES = 8192;
    private const MAX_QUERY_ITEMS = 100;
    private const MAX_QUERY_DEPTH = 4;

    public static function current() {

        $request_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $request_query = self::parse_query($request_uri);
        $object = get_queried_object();

        if($object && self::can_render_current_object($object)){
            return self::payload_from_object($object, $request_uri, $request_query, true);
        }

        if(is_front_page()){
            $front_page_id = (int)get_option('page_on_front');

            if($front_page_id){
                $front_page = get_post($front_page_id);

                if($front_page instanceof \WP_Post && self::can_render_current_object($front_page)){
                    return self::payload_from_object($front_page, $request_uri, $request_query, true);
                }
            }
        }

        $resolved_object = self::resolve_object_from_path($request_uri, true);

        if($resolved_object){
            return self::payload_from_object($resolved_object, $request_uri, $request_query, true);
        }

        return self::not_found($request_uri, $request_query);

    }

    public static function from_path($path) {

        if(!RestAccess::is_safe_view($path)){
            return self::not_found('/', []);
        }

        $normalized_path = self::normalize_path($path);
        $query = self::parse_query($path);
        $object = self::resolve_object_from_path($path);

        if(!$object){
            return self::not_found($normalized_path, $query);
        }

        return self::payload_from_object($object, $normalized_path, $query);

    }

    public static function from_post_id($post_id, $request = null, $query = null, $allow_non_public = false) {

        $post = get_post((int)$post_id);

        if(
            !$post instanceof \WP_Post
            || $post->post_status === 'trash'
            || (!$allow_non_public && !self::is_public_object($post))
        ){
            return self::not_found('/', []);
        }

        $resolved_request = $request;

        if($resolved_request === null){
            $permalink = get_permalink($post);
            $resolved_request = !is_wp_error($permalink) && $permalink
                ? $permalink
                : '/?p=' . (int)$post->ID;
        }

        return self::payload_from_object($post, $resolved_request, $query, $allow_non_public);

    }

    public static function from_object($object, $request = '/', $query = null, $allow_non_public = false) {

        return self::payload_from_object($object, $request, $query, $allow_non_public);

    }

    public static function is_public_object($object) {

        if(!$object instanceof \WP_Post){
            return true;
        }

        return $object->post_password === '' && is_post_publicly_viewable($object);

    }

    public static function is_public_author($user) {

        if(!$user instanceof \WP_User || !$user->exists()){
            return false;
        }

        static $cache = [];
        $user_id = (int)$user->ID;

        if(array_key_exists($user_id, $cache)){
            return $cache[$user_id];
        }

        $post_types = array_values(array_diff(
            get_post_types(['public' => true], 'names'),
            ['attachment']
        ));
        $query = new \WP_Query([
            'author' => $user_id,
            'post_type' => $post_types,
            'post_status' => 'publish',
            'has_password' => false,
            'posts_per_page' => 1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ]);
        $is_public = !empty($query->posts);

        $cache[$user_id] = (bool)apply_filters('rwp_public_author', $is_public, $user);

        return $cache[$user_id];

    }

    public static function is_public_term($term) {

        if(!$term instanceof \WP_Term){
            return false;
        }

        $taxonomy = get_taxonomy($term->taxonomy);

        return $taxonomy && !empty($taxonomy->public);

    }

    public static function normalize_path($path = '/') {

        $path = (string)$path;

        if(
            $path === ''
            || strlen($path) > self::MAX_PATH_BYTES
            || strpos($path, '\\') !== false
            || preg_match('/[\x00-\x1F\x7F]/', rawurldecode($path))
        ){
            return '/';
        }

        $parsed_path = wp_parse_url($path, PHP_URL_PATH);
        $normalized_path = '/' . trim((string)($parsed_path ?: $path), '/') . '/';

        return $normalized_path === '//' ? '/' : $normalized_path;

    }

    public static function normalize_search($value = '') {

        return self::build_search(self::parse_query($value));

    }

    public static function parse_query($value = '/') {

        if(is_array($value)){
            return self::normalize_query($value);
        }

        $string_value = trim((string)$value);
        $parsed_query = wp_parse_url($string_value, PHP_URL_QUERY);

        if($parsed_query === null || $parsed_query === false){
            if($string_value === '' || $string_value[0] !== '?'){
                return [];
            }

            $raw_query = substr($string_value, 1);
        } else {
            $raw_query = (string)$parsed_query;
        }

        if($raw_query === ''){
            return [];
        }

        if(strlen($raw_query) > self::MAX_QUERY_BYTES){
            return [];
        }

        $query = [];
        wp_parse_str($raw_query, $query);

        return is_array($query) ? self::normalize_query($query) : [];

    }

    public static function not_found($path = '/', $query = null) {

        $normalized_path = self::normalize_path($path);
        $resolved_query = is_array($query) ? $query : self::parse_query($path);
        $site_name = get_bloginfo('name');
        $page_name = defined('CL') && CL === 'fr' ? 'Page introuvable' : 'Page not found';

        $payload = [
            'id' => null,
            'type' => '404',
            'template' => 'NotFound',
            'pageName' => $page_name,
            'path' => $normalized_path,
            'search' => self::build_search($resolved_query),
            'query' => $resolved_query,
            'url' => self::build_url($normalized_path, $resolved_query),
            'seo' => [
                'pageName' => $page_name,
                'title' => $page_name . ' - ' . $site_name,
                'description' => $site_name
            ],
            'mediaGroups' => '',
            'data' => [],
            'is404' => true
        ];

        $filtered_payload = apply_filters('rwp_route_payload', $payload, null);
        $payload = is_array($filtered_payload) ? $filtered_payload : $payload;
        $payload['lang'] = self::current_language();
        $payload['render'] = RenderStrategy::resolve($payload, null);
        $payload['head'] = self::resolve_head_payload($payload, null);

        return $payload;

    }

    private static function resolve_object_from_path($path, $allow_non_public = false) {

        $normalized_path = self::normalize_path($path);
        $target_url = site_url($normalized_path);
        $post_id = url_to_postid($target_url);

        if(!$post_id){
            $segments = array_values(array_filter(explode('/', trim($normalized_path, '/'))));
            $front_page_id = (int)get_option('page_on_front');

            if($front_page_id && $normalized_path === '/'){
                $post_id = $front_page_id;
            } elseif(
                $front_page_id
                && count($segments) === 1
                && function_exists('pll_languages_list')
                && function_exists('pll_get_post')
                && in_array($segments[0], pll_languages_list(['fields' => 'slug']), true)
            ){
                $translated_front_page_id = (int)pll_get_post($front_page_id, $segments[0]);
                $post_id = $translated_front_page_id ?: $front_page_id;
            }
        }

        if($post_id){
            $post = get_post($post_id);

            if(
                $post instanceof \WP_Post
                && (
                    self::is_public_object($post)
                    || ($allow_non_public && self::can_render_current_object($post))
                )
            ){
                return $post;
            }
        }

        $segments = array_values(array_filter(explode('/', trim($normalized_path, '/'))));

        if(($segments[0] ?? null) === 'author' && !empty($segments[1])){
            $user = get_user_by('slug', $segments[1]);

            if($user instanceof \WP_User && self::is_public_author($user)){
                return $user;
            }
        }

        foreach(get_taxonomies(['public' => true], 'names') as $taxonomy){
            $term_slug = end($segments);

            if(!$term_slug){
                continue;
            }

            $term = get_term_by('slug', $term_slug, $taxonomy);

            if(!$term instanceof \WP_Term){
                continue;
            }

            $term_link = get_term_link($term);

            if(is_wp_error($term_link)){
                continue;
            }

            $term_path = self::normalize_path(wp_parse_url($term_link, PHP_URL_PATH) ?: '/');

            if(untrailingslashit($term_path) === untrailingslashit($normalized_path)){
                return $term;
            }
        }

        return null;

    }

    public static function payload_from_object($object, $request = '/', $query = null, $allow_non_public = false) {

        if(
            $object instanceof \WP_Post
            && !$allow_non_public
            && !self::is_public_object($object)
        ){
            return self::not_found($request, $query);
        }

        if(
            !$allow_non_public
            && (
                ($object instanceof \WP_User && !self::is_public_author($object))
                || ($object instanceof \WP_Term && !self::is_public_term($object))
            )
        ){
            return self::not_found($request, $query);
        }

        $id = null;
        $type = null;
        $url = home_url('/');
        $page_name = get_bloginfo('name');
        $acf_context = [];
        $resolved_query = is_array($query) ? $query : self::parse_query($request);

        if($object instanceof \WP_Post){
            $id = $object->ID;
            $type = $object->post_type;
            $url = get_permalink($object->ID);
            $page_name = \rwp::field('page_title', $id, true, false) ?: get_the_title($object->ID);
            $acf_context = ['post_id' => $id];
        } elseif($object instanceof \WP_User){
            $id = 'user_' . $object->ID;
            $type = 'user';
            $url = get_author_posts_url($object->ID);
            $page_name = \rwp::field('page_title', $id, true, false) ?: $object->display_name;
            $acf_context = ['user_id' => $object->ID, 'rest' => true];
        } elseif($object instanceof \WP_Term){
            $id = 'term_' . $object->term_id;
            $type = 'term';
            $url = get_term_link($object);
            $url = !is_wp_error($url) ? $url : home_url('/');
            $page_name = \rwp::field('page_title', $id, true, false) ?: $object->name;
            $acf_context = ['term_id' => $object->term_id, 'rest' => true];
        } else {
            return self::not_found($request, $resolved_query);
        }

        $acf = self::resolve_acf_payload($id, $acf_context);
        $seo = isset($acf['seo']) && is_array($acf['seo']) ? $acf['seo'] : [];
        $media_groups = isset($acf['media_groups']) ? (string)$acf['media_groups'] : '';
        $page_template = isset($acf['react_template']) && $acf['react_template'] ? $acf['react_template'] : 'Default';
        $normalized_path = self::normalize_path(wp_parse_url($url, PHP_URL_PATH) ?: '/');
        $search = self::build_search($resolved_query);

        if($resolved_query){
            $url = add_query_arg($resolved_query, $url);
        }

        unset($acf['seo'], $acf['media_groups'], $acf['react_template']);

        $payload = [
            'id' => $id,
            'type' => $type,
            'template' => $page_template,
            'pageName' => $page_name,
            'path' => $normalized_path,
            'search' => $search,
            'query' => $resolved_query,
            'url' => $url,
            'seo' => [
                ...$seo,
                'pageName' => $page_name
            ],
            'mediaGroups' => $media_groups,
            'data' => $acf,
            'is404' => false
        ];

        $filtered_payload = apply_filters('rwp_route_payload', $payload, $object);
        $payload = is_array($filtered_payload) ? $filtered_payload : $payload;
        $payload['lang'] = self::current_language();
        $payload['render'] = RenderStrategy::resolve($payload, $object);
        $payload['head'] = self::resolve_head_payload($payload, $object);

        return $payload;

    }

    private static function can_render_current_object($object) {

        if($object instanceof \WP_User){
            return self::is_public_author($object)
                || current_user_can('edit_user', $object->ID);
        }

        if($object instanceof \WP_Term){
            return self::is_public_term($object)
                || current_user_can('manage_categories');
        }

        if(!$object instanceof \WP_Post){
            return false;
        }

        if(self::is_public_object($object)){
            return true;
        }

        if($object->post_status === 'trash' || !current_user_can('read_post', $object->ID)){
            return false;
        }

        return $object->post_password === '' || current_user_can('edit_post', $object->ID);

    }

    private static function resolve_head_payload($payload, $object = null) {

        $head = apply_filters('rwp_wp_head', [], [
            'source' => 'route',
            'route' => $payload,
            'object' => $object,
        ]);

        if(!is_array($head)){
            return [];
        }

        return array_values(array_filter(array_map(function($entry){
            return is_string($entry) ? trim($entry) : '';
        }, array_slice($head, 0, 100))));

    }

    private static function resolve_acf_payload($id, $context) {

        if(!$id || !function_exists('acf_get_field_groups') || !function_exists('acf_get_fields')){
            return [];
        }

        $acf_groups = acf_get_field_groups($context);

        if(!$acf_groups){
            return [];
        }

        $payload = [];

        foreach($acf_groups as $group){
            if(empty($group['active']) || empty($group['show_in_rest'])){
                continue;
            }

            $fields = acf_get_fields($group['key']);

            if(!$fields){
                continue;
            }

            foreach($fields as $field){
                if(empty($field['name'])){
                    continue;
                }

                $payload[$field['name']] = \rwp::field($field['name'], $id, true, false);
            }
        }

        return $payload;

    }

    private static function build_search($query = []) {

        if(!$query || !is_array($query)){
            return '';
        }

        $query = self::normalize_query($query);
        $query_string = http_build_query($query);

        return $query_string !== '' ? '?' . $query_string : '';

    }

    private static function normalize_query($value, $depth = 0, &$items = 0) {

        if(!is_array($value) || $depth > self::MAX_QUERY_DEPTH){
            return [];
        }

        $normalized = [];

        foreach($value as $key => $item){
            $items++;

            if($items > self::MAX_QUERY_ITEMS){
                break;
            }

            $key = is_int($key) ? $key : preg_replace('/[^a-zA-Z0-9_.-]/', '', (string)$key);

            if($key === ''){
                continue;
            }

            if(is_array($item)){
                $normalized[$key] = self::normalize_query($item, $depth + 1, $items);
                continue;
            }

            if(!is_scalar($item)){
                continue;
            }

            $item = (string)$item;

            if(strlen($item) <= 2048 && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $item)){
                $normalized[$key] = $item;
            }
        }

        return $normalized;

    }

    private static function build_url($path = '/', $query = []) {

        $url = home_url(self::normalize_path($path));

        if($query && is_array($query)){
            return add_query_arg($query, $url);
        }

        return $url;

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

}
