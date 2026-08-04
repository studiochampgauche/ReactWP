<?php

if(!defined('ABSPATH')){
    exit;
}

function rwp_firstload_target_version() {

    return max(1, (int)apply_filters('rwp_firstload_version', 1));

}

function rwp_firstload_repeater_is_ready($field_key, $name) {

    $option_name = 'options_' . $name;

    return (int)get_option($option_name, 0) > 0
        && get_option('_' . $option_name, '') === $field_key;

}

function rwp_seed_option_repeater_rows($field_key, $name, $sub_fields = [], $rows = []) {

    $option_name = 'options_' . $name;
    $rows_count = (int)get_option($option_name, 0);
    $should_seed = $rows_count < 1;

    if($should_seed){
        $rows_count = count($rows);
        update_option($option_name, $rows_count);
    }

    update_option('_' . $option_name, $field_key);

    for($index = 0; $index < $rows_count; $index++){
        $row = $rows[$index] ?? [];

        foreach($sub_fields as $sub_field => $sub_field_key){
            $value_option = "{$option_name}_{$index}_{$sub_field}";

            if($should_seed && array_key_exists($sub_field, $row)){
                update_option($value_option, $row[$sub_field]);
            }

            if(get_option($value_option, null) !== null){
                update_option('_' . $value_option, $sub_field_key);
            }
        }
    }

    return rwp_firstload_repeater_is_ready($field_key, $name);

}

function rwp_firstload_needs_run($target_version = null) {

    $target_version = $target_version === null
        ? rwp_firstload_target_version()
        : max(1, (int)$target_version);

    return (int)get_option('rwp_firstload', 0) < $target_version
        || !rwp_firstload_repeater_is_ready('field_67a8554331600', 'langs')
        || !rwp_firstload_repeater_is_ready('field_678bca18008f5', 'theme_locations');

}

function rwp_firstload_enabled() {

    $enabled = defined('RWP_FIRSTLOAD') && RWP_FIRSTLOAD;

    return (bool)apply_filters('rwp_firstload_enabled', $enabled);

}

function rwp_firstload_insert_post($args = []) {

    $post_id = wp_insert_post($args, true);

    if(is_wp_error($post_id) || !$post_id){
        return 0;
    }

    return (int)$post_id;

}

function rwp_firstload_author_id() {

    $users = get_users([
        'role' => 'administrator',
        'number' => 1,
        'fields' => 'ID',
        'orderby' => 'ID',
        'order' => 'ASC',
    ]);

    return !empty($users[0]) ? (int)$users[0] : 0;

}

function rwp_firstload_home_page() {

    $front_page_id = (int)get_option('page_on_front');

    if($front_page_id && get_post($front_page_id)){
        return $front_page_id;
    }

    $existing = get_page_by_path('reactwp-3', OBJECT, 'page');

    if($existing instanceof WP_Post){
        return (int)$existing->ID;
    }

    return rwp_firstload_insert_post([
        'post_title' => 'ReactWP 3',
        'post_name' => 'reactwp-3',
        'post_type' => 'page',
        'post_content' => '',
        'post_status' => 'publish',
        'post_author' => rwp_firstload_author_id(),
    ]);

}

function rwp_firstload_navigation() {

    $locations = array_filter((array)get_theme_mod('nav_menu_locations'), function($value, $key){
        return is_string($key) && $key !== '';
    }, ARRAY_FILTER_USE_BOTH);

    if(!empty($locations['primary'])){
        return;
    }

    $menu = wp_get_nav_menu_object('Primary Navigation');
    $menu_id = $menu instanceof WP_Term
        ? (int)$menu->term_id
        : wp_create_nav_menu('Primary Navigation');

    if(is_wp_error($menu_id) || !$menu_id){
        return;
    }

    $locations['primary'] = (int)$menu_id;
    set_theme_mod('nav_menu_locations', $locations);

}

add_action('init', function(){

    $target_version = rwp_firstload_target_version();

    if(
        !is_admin()
        || !current_user_can('manage_options')
        || !rwp_firstload_enabled()
        || !rwp_firstload_needs_run($target_version)
    ){
        return;
    }

    $existing_lock = (int)get_option('rwp_firstload_lock');

    if($existing_lock && $existing_lock < time() - 300){
        delete_option('rwp_firstload_lock');
    }

    if(!add_option('rwp_firstload_lock', time(), '', false)){
        return;
    }

    try{
        $home_page_id = rwp_firstload_home_page();

        if($home_page_id && !(int)get_option('page_on_front')){
            update_option('show_on_front', 'page');
            update_option('page_on_front', $home_page_id);
        }

        if((string)get_option('permalink_structure') === ''){
            update_option('permalink_structure', '/%postname%/');
            flush_rewrite_rules();
        }

        $langs_ready = rwp_seed_option_repeater_rows(
            'field_67a8554331600',
            'langs',
            [
                'name' => 'field_67a8556a31601',
                'code' => 'field_67a8558631603',
            ],
            [
                [
                    'name' => 'Francais',
                    'code' => 'fr',
                ],
                [
                    'name' => 'English',
                    'code' => 'en',
                ],
            ]
        );

        $theme_locations_ready = rwp_seed_option_repeater_rows(
            'field_678bca18008f5',
            'theme_locations',
            [
                'name_fr' => 'field_rwp_theme_location_name_fr',
                'name_en' => 'field_rwp_theme_location_name_en',
                'slug' => 'field_678bca4e008f7',
            ],
            [
                [
                    'name_fr' => 'Navigation principale',
                    'name_en' => 'Primary navigation',
                    'slug' => 'primary',
                ],
            ]
        );

        rwp_firstload_navigation();

        if($home_page_id && $langs_ready && $theme_locations_ready){
            update_option('rwp_firstload', $target_version);
        }
    } finally {
        delete_option('rwp_firstload_lock');
    }

}, 11);
