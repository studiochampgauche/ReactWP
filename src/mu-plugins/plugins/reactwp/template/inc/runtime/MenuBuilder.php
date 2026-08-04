<?php

namespace ReactWP\Runtime;

class MenuBuilder {

    public static function all() {

        $locations = get_nav_menu_locations();
        $registered_locations = get_registered_nav_menus();
        $menus = [];

        foreach(array_slice((array)$locations, 0, 100, true) as $location => $menu_id){
            if(
                !is_string($location)
                || $location === ''
                || !array_key_exists($location, $registered_locations)
            ){
                continue;
            }

            $menus[$location] = self::from_location($location, $menu_id);
        }

        return $menus;

    }

    public static function from_location($location, $menu_id = null) {

        $locations = get_nav_menu_locations();
        $resolved_menu_id = $menu_id ?: ($locations[$location] ?? 0);

        if(!$resolved_menu_id){
            return [];
        }

        $items = wp_get_nav_menu_items($resolved_menu_id, [
            'update_post_term_cache' => false
        ]);

        if(!$items){
            return [];
        }

        $normalized_items = [];

        foreach(array_slice((array)$items, 0, 5000) as $item){
            $normalized_items[$item->ID] = [
                'id' => (int)$item->ID,
                'parentId' => $item->menu_item_parent ? (int)$item->menu_item_parent : null,
                'label' => $item->title,
                'title' => $item->title,
                'url' => $item->url,
                'path' => RouteResolver::normalize_path(wp_parse_url($item->url, PHP_URL_PATH) ?: '/'),
                'target' => in_array($item->target, ['_blank', '_self', '_parent', '_top'], true)
                    ? $item->target
                    : null,
                'classes' => array_values(array_filter(array_map(
                    'sanitize_html_class',
                    array_slice((array)$item->classes, 0, 100)
                ))),
                'children' => []
            ];
        }

        $tree = [];

        foreach($normalized_items as $item_id => &$normalized_item){
            $parent_id = $normalized_item['parentId'];

            if($parent_id && isset($normalized_items[$parent_id])){
                $normalized_items[$parent_id]['children'][] = &$normalized_item;
                continue;
            }

            $tree[] = &$normalized_item;
        }

        unset($normalized_item);

        $visited = [];

        return array_values(array_filter(array_map(function($item) use (&$visited){
            return self::cleanup_item($item, 0, $visited);
        }, $tree)));

    }

    private static function cleanup_item($item, $depth = 0, &$visited = []) {

        $item_id = (int)($item['id'] ?? 0);

        if($depth > 20 || !$item_id || isset($visited[$item_id])){
            return null;
        }

        $visited[$item_id] = true;

        $children = [];

        foreach(array_slice((array)$item['children'], 0, 500) as $child){
            $normalized_child = self::cleanup_item($child, $depth + 1, $visited);

            if($normalized_child){
                $children[] = $normalized_child;
            }
        }

        unset($item['parentId']);

        $item['children'] = $children;

        return $item;

    }

}
