<?php

require_once 'inc/utils.php';


class ReactWP{
    
    function __construct(){

        /*
        * Define Constante Language
        */
        if(!defined('CL')){
            define('CL', (function_exists('pll_current_language') ? pll_current_language() : substr(get_locale(), 0, 2)));
        }


        /*
        * Spread Constante Language in JavaScript
        */
        add_action('wp_enqueue_scripts', function(){

            /*
            * Lang Reference
            */
            wp_localize_script('rwp-main', 'CL', ['value' => CL]);

            
        }, 11);


        /*
        * Shot events on init action
        */
        add_action('init', function(){

            /*
            * On First Load Only
            */
            if (get_option('rwp_firstload') < 5) {

                update_option('rwp_firstload', 5);

                /*
                * Delete default posts/pages
                */
                foreach(self::cpt(['post', 'page'], ['post_status' => ['publish', 'draft']])->posts as $item){

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
                * Theme locations
                */
                update_field('field_678bca18008f5', [
                    [
                        'name' => CL === 'fr' ? 'Menu principal' : 'Main Menu',
                        'slug' => 'main_menu',
                    ]
                ], 'option');

            }


        }, 11);


        /*
        * Register theme locations
        */
        add_action('after_setup_theme', function(){

            $locations = self::field('theme_locations');
        
            if($locations){
                
                foreach ($locations as $l) {
                    $__locations[$l['slug']] = $l['name'];
                }
                
                register_nav_menus($__locations);
                
            }

        });


        /*
        * ACF Replace values from php functions
        */
        add_filter('acf/format_value', function ($value, $post_id, $field){

            $return = $value;

            if($return && is_array($return) && \ReactWP\Utils\Field::$elementsToReplace)
                \ReactWP\Utils\Field::recursive(\ReactWP\Utils\Field::$elementsToReplace[0], \ReactWP\Utils\Field::$elementsToReplace[1], $return);
            elseif($return && is_string($return) && \ReactWP\Utils\Field::$elementsToReplace)
                $return = str_replace(\ReactWP\Utils\Field::$elementsToReplace[0], \ReactWP\Utils\Field::$elementsToReplace[1], $return);


            return $return;
            
        }, 10, 3);



        /*
        * ACF Replace values from REST API
        */
        add_filter('acf/settings/rest_api_format', function () {
            return 'standard';
        });

        add_filter('acf/rest/format_value_for_rest', function ($value_formatted, $post_id, $field, $value, $format){

            $return = $value_formatted;

            if($return && is_array($return) && \ReactWP\Utils\Field::$elementsToReplace)
                \ReactWP\Utils\Field::recursive(\ReactWP\Utils\Field::$elementsToReplace[0], \ReactWP\Utils\Field::$elementsToReplace[1], $return);
            elseif($return && is_string($return) && \ReactWP\Utils\Field::$elementsToReplace)
                $return = str_replace(\ReactWP\Utils\Field::$elementsToReplace[0], \ReactWP\Utils\Field::$elementsToReplace[1], $return);


            return $return;
            
        }, 10, 5);


        /*
        * Shot events on acf/init action
        */
        add_action( 'acf/init', function() {

            /*
            * Initialize site settings page
            */
            acf_add_options_page( array(
                'page_title' => 'Site settings',
                'menu_slug' => 'site-settings',
                'position' => '',
                'redirect' => false,
                'menu_icon' => array(
                    'type' => 'dashicons',
                    'value' => 'dashicons-admin-generic',
                ),
                'icon_url' => 'dashicons-admin-generic',
            ) );


            /*
            * Render ACF Fields
            */
            acf_add_local_field_group( array(
                'key' => 'group_678bca1894511',
                'title' => 'Theme Locations',
                'fields' => array(
                    array(
                        'key' => 'field_678bca18008f5',
                        'label' => 'Theme Locations',
                        'name' => 'theme_locations',
                        'aria-label' => '',
                        'type' => 'repeater',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'layout' => 'table',
                        'pagination' => 0,
                        'min' => 0,
                        'max' => 0,
                        'collapsed' => '',
                        'button_label' => 'Ajouter un emplacement',
                        'rows_per_page' => 20,
                        'sub_fields' => array(
                            array(
                                'key' => 'field_678bca39008f6',
                                'label' => 'Nom',
                                'name' => 'name',
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'allow_in_bindings' => 0,
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_678bca18008f5',
                            ),
                            array(
                                'key' => 'field_678bca4e008f7',
                                'label' => 'Slug',
                                'name' => 'slug',
                                'aria-label' => '',
                                'type' => 'text',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '',
                                    'class' => '',
                                    'id' => '',
                                ),
                                'default_value' => '',
                                'maxlength' => '',
                                'allow_in_bindings' => 0,
                                'placeholder' => '',
                                'prepend' => '',
                                'append' => '',
                                'parent_repeater' => 'field_678bca18008f5',
                            ),
                        ),
                    ),
                ),
                'location' => array(
                    array(
                        array(
                            'param' => 'options_page',
                            'operator' => '==',
                            'value' => 'site-settings',
                        ),
                    ),
                ),
                'menu_order' => 1,
                'position' => 'normal',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ) );
        } );


    }

    static function field($field, $id = false, $format = true, $escape = false){
        
        return ReactWP\Utils\Field::get($field, $id, $format, $escape);
        
    }
    
    static function cpt($post_type = 'post', $args = []){
        
        return ReactWP\Utils\CustomPostType::get($post_type, $args);
        
    }

    static function menu($theme_location = null, $args = []){

        return ReactWP\Utils\Menu::get($theme_location, $args);
        
    }

    static function button($text = null, $args = []){
        
        return ReactWP\Utils\Button::get($text, $args);
        
    }

    static function source($args = []){
        
        return ReactWP\Utils\Source::get($args);
        
    }

}

class_alias('ReactWP', 'rwp');

new rwp();