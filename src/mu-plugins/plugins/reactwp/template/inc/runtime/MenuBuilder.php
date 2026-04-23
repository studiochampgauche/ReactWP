<?php

namespace ReactWP\Runtime;

class MenuBuilder {

    public static function all() {

        $locations = get_nav_menu_locations();
        $registered_locations = get_registered_nav_menus();
        $menus = [];

        foreach($locations as $location => $menu_id){
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

        foreach($items as $item){
            $normalized_items[$item->ID] = [
                'id' => (int)$item->ID,
                'parentId' => $item->menu_item_parent ? (int)$item->menu_item_parent : null,
                'label' => $item->title,
                'title' => $item->title,
                'url' => $item->url,
                'path' => RouteResolver::normalize_path(wp_parse_url($item->url, PHP_URL_PATH) ?: '/'),
                'target' => $item->target ?: null,
                'classes' => array_values(array_filter(array_map('sanitize_html_class', (array)$item->classes))),
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

        return array_map([self::class, 'cleanup_item'], $tree);

    }

    private static function cleanup_item($item) {

        $children = [];

        foreach($item['children'] as $child){
            $children[] = self::cleanup_item($child);
        }

        unset($item['parentId']);

        $item['children'] = $children;

        return $item;

    }

}
