<?php

namespace ReactWP\Runtime;

class InitialRender {

    private const MAX_MANIFEST_BYTES = 2 * 1024 * 1024;
    private const MAX_MANIFEST_ENTRIES = 50000;

    public static function resolve($payload) {

        $route = is_array($payload['route'] ?? null) ? $payload['route'] : [];
        $config = RenderStrategy::normalize($route['render'] ?? []);
        $mode = $config['mode'];
        $fallback = self::result('client', '', $route);

        if(!self::can_render() || $mode === 'client'){
            return $fallback;
        }

        if($mode === 'static'){
            $html = self::static_fragment($route);

            return $html !== null
                ? self::result('static', $html, $route)
                : $fallback;
        }

        if($mode === 'server'){
            $html = ServerRenderer::render($payload, $config);

            return $html !== null
                ? self::result('server', $html, $route)
                : $fallback;
        }

        return $fallback;

    }

    private static function result($source, $html, $route) {

        return [
            'source' => $source,
            'html' => $html,
            'key' => RenderStrategy::route_key($route),
        ];

    }

    private static function can_render() {

        $method = strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET'));

        if($method !== 'GET' || is_admin() || wp_doing_ajax()){
            return false;
        }

        if((defined('REST_REQUEST') && REST_REQUEST) || is_feed() || is_trackback()){
            return false;
        }

        return (bool)apply_filters('rwp_initial_render_enabled', true);

    }

    private static function static_fragment($route) {

        foreach(self::manifest_paths() as $manifest_path){
            $manifest = self::read_manifest($manifest_path);

            if(!$manifest){
                continue;
            }

            if((string)($manifest['cacheVersion'] ?? '') !== ClientCache::version()){
                continue;
            }

            $key = RenderStrategy::route_key($route);
            $entry = $manifest['entries'][$key] ?? null;

            if(
                !is_array($entry)
                || (string)($entry['cacheVersion'] ?? '') !== ClientCache::version()
                || !RenderCache::is_fresh($entry)
            ){
                continue;
            }

            $html = self::read_fragment($manifest_path, $entry);

            if($html !== null){
                return $html;
            }
        }

        return null;

    }

    public static function manifest_entries() {

        $entries = [];
        $paths = array_reverse(self::manifest_paths());

        foreach($paths as $manifest_path){
            $manifest = self::read_manifest($manifest_path);

            if(!is_array($manifest['entries'] ?? null)){
                continue;
            }

            $entries = [...$entries, ...array_slice($manifest['entries'], 0, self::MAX_MANIFEST_ENTRIES, true)];

            if(count($entries) > self::MAX_MANIFEST_ENTRIES){
                $entries = array_slice($entries, -self::MAX_MANIFEST_ENTRIES, null, true);
            }
        }

        return $entries;

    }

    private static function manifest_paths() {

        $paths = [
            get_stylesheet_directory() . '/assets/render/static/manifest.json',
        ];
        $uploads = wp_upload_dir(null, false);

        if(empty($uploads['error']) && !empty($uploads['basedir'])){
            array_unshift($paths, trailingslashit($uploads['basedir']) . 'reactwp/render/static/manifest.json');
        }

        return array_values(array_unique(array_filter((array)apply_filters('rwp_static_render_manifest_paths', $paths))));

    }

    private static function read_manifest($path) {

        static $cache = [];

        if(
            !is_string($path)
            || !is_file($path)
            || !is_readable($path)
            || filesize($path) > self::MAX_MANIFEST_BYTES
        ){
            return null;
        }

        $modified = filemtime($path) ?: 0;
        $cache_key = $path . '|' . $modified;

        if(array_key_exists($cache_key, $cache)){
            return $cache[$cache_key];
        }

        $manifest = json_decode((string)file_get_contents($path), true, 64);

        if(
            !is_array($manifest)
            || !is_array($manifest['entries'] ?? null)
            || count($manifest['entries']) > self::MAX_MANIFEST_ENTRIES
        ){
            $manifest = null;
        }

        $cache = [$cache_key => is_array($manifest) ? $manifest : null];

        return $cache[$cache_key];

    }

    private static function read_fragment($manifest_path, $entry) {

        $relative_file = str_replace('\\', '/', (string)($entry['file'] ?? ''));

        if($relative_file === '' || strpos($relative_file, '..') !== false || $relative_file[0] === '/'){
            return null;
        }

        $base_directory = realpath(dirname($manifest_path));
        $fragment_path = realpath(dirname($manifest_path) . '/' . $relative_file);

        if(
            !$base_directory
            || !$fragment_path
            || strpos($fragment_path, $base_directory . DIRECTORY_SEPARATOR) !== 0
            || !is_file($fragment_path)
            || !is_readable($fragment_path)
        ){
            return null;
        }

        $max_bytes = max(1024, (int)apply_filters('rwp_static_render_max_html_bytes', 5 * 1024 * 1024));

        if(filesize($fragment_path) > $max_bytes){
            return null;
        }

        $html = file_get_contents($fragment_path);

        return is_string($html) ? $html : null;

    }

}
