<?php

namespace ReactWP\Runtime;

class RenderStrategy {

    const MODES = ['client', 'static', 'server'];

    public static function resolve($route, $object = null) {

        $route = is_array($route) ? $route : [];
        $template = (string)($route['template'] ?? 'Default');
        $manifest_config = self::template_manifest_config($template);
        $templates = apply_filters('rwp_render_templates', []);
        $template_config = is_array($templates) ? ($templates[$template] ?? []) : [];
        $config = self::merge($manifest_config, $template_config);

        if(isset($route['render']) && is_array($route['render'])){
            $config = self::merge($config, $route['render']);
        }

        $field_config = self::field_config($object, $route);

        if($field_config){
            $config = self::merge($config, $field_config);
        }

        $config = apply_filters('rwp_render_config', $config, $route, $object);
        $config = self::normalize($config);
        $config['mode'] = apply_filters('rwp_render_mode', $config['mode'], $route, $object);

        return self::normalize($config);

    }

    public static function normalize($config = []) {

        if(is_string($config)){
            $config = ['mode' => $config];
        }

        $config = is_array($config) ? $config : [];
        $mode = sanitize_key((string)($config['mode'] ?? 'client'));
        $mode = in_array($mode, self::MODES, true) ? $mode : 'client';
        $cache = isset($config['cache']) && is_array($config['cache'])
            ? $config['cache']
            : [];
        $default_html_cache = $mode === 'static';
        $html_cache = array_key_exists('html', $cache)
            ? (bool)$cache['html']
            : $default_html_cache;
        $scope = sanitize_key((string)($cache['scope'] ?? ($mode === 'server' ? 'private' : 'public')));

        if(!in_array($scope, ['public', 'private'], true)){
            $scope = $mode === 'server' ? 'private' : 'public';
        }

        $persistent_cache_default = $scope === 'public';

        return [
            'mode' => $mode,
            'cache' => [
                'html' => $html_cache,
                'scope' => $scope,
                'ttl' => min(31536000, max(0, (int)($cache['ttl'] ?? 0))),
                'payload' => array_key_exists('payload', $cache)
                    ? (bool)$cache['payload']
                    : $persistent_cache_default,
                'media' => array_key_exists('media', $cache)
                    ? (bool)$cache['media']
                    : $persistent_cache_default,
                'tags' => self::normalize_tags($cache['tags'] ?? []),
            ],
        ];

    }

    public static function route_key($route) {

        $route = is_array($route) ? $route : [];
        $language = sanitize_key((string)($route['lang'] ?? self::current_language()));
        $path = RouteResolver::normalize_path($route['path'] ?? '/');
        $search = RouteResolver::normalize_search($route['search'] ?? '');

        return strtolower($language ?: 'en') . ':' . $path . $search;

    }

    private static function merge($base, $override) {

        $base = self::normalize($base);
        $override = is_array($override) ? $override : [];
        $cache = isset($override['cache']) && is_array($override['cache'])
            ? [...$base['cache'], ...$override['cache']]
            : $base['cache'];

        return [
            ...$base,
            ...$override,
            'cache' => $cache,
        ];

    }

    private static function template_manifest_config($template) {

        static $cache = [];
        $manifest_path = get_stylesheet_directory() . '/assets/render/templates.json';

        if(
            !is_file($manifest_path)
            || !is_readable($manifest_path)
            || filesize($manifest_path) > 1024 * 1024
        ){
            return [];
        }

        $modified = filemtime($manifest_path) ?: 0;
        $cache_key = $manifest_path . '|' . $modified;

        if(!isset($cache[$cache_key])){
            $manifest = json_decode((string)file_get_contents($manifest_path), true, 32);
            $cache = [
                $cache_key => is_array($manifest['templates'] ?? null)
                    ? $manifest['templates']
                    : [],
            ];
        }

        $config = $cache[$cache_key][$template] ?? [];

        return is_array($config) ? $config : [];

    }

    private static function field_config($object, $route) {

        if(!function_exists('get_field')){
            return [];
        }

        $field_id = self::field_id($object, $route);

        if(!$field_id){
            return [];
        }

        $mode = sanitize_key((string)get_field('react_render_mode', $field_id));

        if(!in_array($mode, self::MODES, true)){
            return [];
        }

        $ttl = get_field('react_render_cache_ttl', $field_id);
        $scope = sanitize_key((string)get_field('react_render_cache_scope', $field_id));

        return [
            'mode' => $mode,
            'cache' => [
                'ttl' => max(0, (int)$ttl),
                'scope' => in_array($scope, ['public', 'private'], true)
                    ? $scope
                    : ($mode === 'server' ? 'private' : 'public'),
                'html' => $mode === 'static' || ($mode === 'server' && (int)$ttl > 0),
            ],
        ];

    }

    private static function field_id($object, $route) {

        if($object instanceof \WP_Post){
            return $object->ID;
        }

        if($object instanceof \WP_User){
            return 'user_' . $object->ID;
        }

        if($object instanceof \WP_Term){
            return 'term_' . $object->term_id;
        }

        return $route['id'] ?? null;

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

    private static function normalize_tags($tags) {

        $tags = array_slice(is_array($tags) ? $tags : [$tags], 0, 200);

        return array_values(array_unique(array_filter(array_map(function($tag){
            $tag = strtolower(trim((string)$tag));
            return preg_match('/^[a-z0-9_-]+:[a-z0-9_.-]+$/', $tag) ? $tag : '';
        }, $tags))));

    }

}
