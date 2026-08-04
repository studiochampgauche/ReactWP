<?php

namespace ReactWP\Runtime;

class TemplateAssets {

    public static function enqueue($route, $source = 'client') {

        if($source === 'client' || !is_array($route)){
            return;
        }

        $template = (string)($route['template'] ?? 'Default');
        $render_manifest = self::manifest('templates.json');
        $asset_manifest = self::manifest('template-assets.json');
        $asset_key = (string)($render_manifest['templates'][$template]['assetKey'] ?? $template);
        $styles = $asset_manifest['templates'][$asset_key]['styles'] ?? [];

        foreach(array_values(array_unique((array)$styles)) as $index => $style){
            $style = str_replace('\\', '/', (string)$style);

            if($style === '' || strpos($style, '..') !== false || substr($style, -4) !== '.css'){
                continue;
            }

            $path = get_stylesheet_directory() . '/' . ltrim($style, '/');

            if(!is_file($path)){
                continue;
            }

            wp_enqueue_style(
                'rwp-initial-template-' . sanitize_key($asset_key) . '-' . ($index + 1),
                get_stylesheet_directory_uri() . '/' . ltrim($style, '/'),
                [],
                ClientCache::version() . '-' . filemtime($path)
            );
        }

    }

    private static function manifest($filename) {

        static $cache = [];
        $path = get_stylesheet_directory() . '/assets/render/' . $filename;

        if(!is_file($path) || !is_readable($path)){
            return [];
        }

        $modified = filemtime($path) ?: 0;
        $key = $path . '|' . $modified;

        if(!isset($cache[$key])){
            $value = json_decode((string)file_get_contents($path), true);
            $cache = [$key => is_array($value) ? $value : []];
        }

        return $cache[$key];

    }

}
