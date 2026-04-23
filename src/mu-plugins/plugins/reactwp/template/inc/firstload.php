<?php

function rwp_seed_option_repeater_rows($name, $rows = []) {

    update_option('options_' . $name, count($rows));

    foreach($rows as $index => $row){
        foreach($row as $sub_field => $value){
            update_option("options_{$name}_{$index}_{$sub_field}", $value);
        }
    }

}

function rwp_firstload_insert_post($args = []) {

    $post_id = wp_insert_post($args, true);

    if(is_wp_error($post_id) || !$post_id){
        return 0;
    }

    return (int)$post_id;

}

add_action('init', function(){

    if (get_option('rwp_firstload') < 1) {

        /*
        * Delete default posts/pages
        */
        foreach(rwp::cpt(['post', 'page'], ['post_status' => ['publish', 'draft']])->posts as $item){

            wp_delete_post($item->ID, true);

        }


        /*
        * Create starter pages
        */
        $homePageArgs = array(
            'post_title' => 'ReactWP 3',
            'post_type' => 'page',
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
        );
        $homePage_id = rwp_firstload_insert_post($homePageArgs);

        /*
        * Set home page as a Static Page
        */
        update_option('show_on_front', 'page');
        update_option('page_on_front', $homePage_id);


        /*
        * Update permalink Structure
        */
        update_option('permalink_structure', '/%postname%/');
        flush_rewrite_rules();


        /*
        * Languages
        */
        rwp_seed_option_repeater_rows('langs', [
            [
                'name' => 'Français',
                'code' => 'fr',
            ],
            [
                'name' => 'English',
                'code' => 'en',
            ]
        ]);


        /*
        * Theme locations
        */
        rwp_seed_option_repeater_rows('theme_locations', [
            [
                'name_fr' => 'Navigation principale',
                'name_en' => 'Primary navigation',
                'slug' => 'primary',
            ]
        ]);


        /*
        * Create starter navigation
        */
        $menu_id = wp_create_nav_menu('Primary Navigation');

        if(!is_wp_error($menu_id)){
            $locations = array_filter((array)get_theme_mod('nav_menu_locations'), function($value, $key){
                return is_string($key) && $key !== '';
            }, ARRAY_FILTER_USE_BOTH);

            $locations['primary'] = $menu_id;
            set_theme_mod('nav_menu_locations', $locations);
        }

        update_option('rwp_firstload', 1);

    }

}, 11);
