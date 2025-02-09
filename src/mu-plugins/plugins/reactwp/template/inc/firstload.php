<?php

add_action('init', function(){

    if (get_option('rwp_firstload') < 1) {

        update_option('rwp_firstload', 1);

        /*
        * Delete default posts/pages
        */
        foreach(rwp::cpt(['post', 'page'], ['post_status' => ['publish', 'draft']])->posts as $item){

            wp_delete_post($item->ID, true);

        }


        /*
        * Create Home Page
        */
        $homePageArgs = array(
            'post_title' => CL === 'fr' ? 'Accueil' : 'Home',
            'post_type' => 'page',
            'post_content' => '',
            'post_status' => 'publish',
            'post_author' => 1,
        );
        $homePage_id = wp_insert_post($homePageArgs);


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
        update_field('field_67a8554331600', [
            [
                'name' => 'Français',
                'locale' => 'fr_CA',
                'code' => 'fr',
            ],
            [
                'name' => 'English',
                'locale' => 'en_CA',
                'code' => 'en',
            ]
        ], 'option');


        /*
        * Theme locations
        */
        update_field('field_678bca18008f5', [
            [
                'name_fr' => 'Menu principal',
                'name_en' => 'Main Menu',
                'slug' => 'main_menu',
            ]
        ], 'option');

    }

}, 11);