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


            /*
            * System management
            */
            wp_localize_script('rwp-main', 'SYSTEM', [
                'public' => get_option('blog_public'),
                'baseUrl' => site_url(),
                'adminUrl' => admin_url(),
                'ajaxPath' => '/wp-admin/admin-ajax.php',
                'restPath' => '/wp-json'
            ]);

            
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
                        'name_fr' => 'Menu principal',
                        'name_en' => 'Main Menu',
                        'slug' => 'main_menu',
                    ]
                ], 'option');

            }


            /*
            * Register some acf fields
            */
            $postTypes = get_post_types();

            $unsets = [
                'post',
                'page',
                'attachment',
                'revision',
                'nav_menu_item',
                'custom_css',
                'customize_changeset',
                'oembed_cache',
                'user_request',
                'wp_block',
                'wp_template',
                'wp_template_part',
                'wp_global_styles',
                'wp_navigation',
                'acf-field',
                'acf-ui-options-page',
                'acf-field-group',
                'acf-post-type',
                'acf-taxonomy',
                'acf-field',
                'wp_font_family',
                'wp_font_face'
            ];

            foreach ($unsets as $unset) {
                unset($postTypes[$unset]);
            }


            $mediaGroupsPostTypes =  rwp::field('mediaGroups_post_types') ? rwp::field('mediaGroups_post_types') : [];


            $mediaGroupsPts = [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'post',
                    ]
                ],
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => 'page',
                    ]
                ],
                [
                    [
                        'param' => 'user_form',
                        'operator' => '==',
                        'value' => 'all',
                    ]
                ],
                [
                    [
                        'param' => 'taxonomy',
                        'operator' => '==',
                        'value' => 'all',
                    ]
                ]
            ];


            if($mediaGroupsPostTypes){
                foreach ($mediaGroupsPostTypes as $pt) {
                    $mediaGroupsPts[] = [
                        [
                            'param' => 'post_type',
                            'operator' => '==',
                            'value' => $pt,
                        ]
                    ];
                }
            }



            acf_add_local_field_group( array(
                'key' => 'group_67kjhb39087sdh233',
                'title' => 'Media Groups',
                'fields' => array(
                    array(
                        'key' => 'field_67kjhb39087sdh234',
                        'label' => (CL === 'fr' ? 'Groupe de médias' : 'Media Groups'),
                        'name' => 'media_groups',
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
                        'append' => ''
                    ),
                ),
                'location' => $mediaGroupsPts,
                'menu_order' => 8,
                'position' => 'side',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 1,
            ) );

            acf_add_local_field_group( array(
                'key' => 'acf-group_60983478kjhsad54323',
                'title' => 'Media Groups Global Settings',
                'fields' => array(
                    array(
                        'key' => 'field_60983478kjhsad54324',
                        'label' => 'Media Groups Post Types',
                        'name' => 'mediaGroups_post_types',
                        'aria-label' => '',
                        'type' => 'checkbox',
                        'instructions' => (CL === 'fr' ? 'Les articles, les pages, les utilisateurs et les taxonomies ont le module. Si vous ne voyez pas de sélection, créez un nouveau type d\'article.' : 'Posts, pages, users and taxonomies have the module. If you don\'t see a selection, create a new post type.'),
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '33.3333333333',
                            'class' => '',
                            'id' => '',
                        ),
                        'choices' => $postTypes,
                        'default_value' => array(
                        ),
                        'return_format' => 'value',
                        'allow_custom' => 0,
                        'allow_in_bindings' => 0,
                        'layout' => 'vertical',
                        'toggle' => 0,
                        'save_custom' => 0,
                        'custom_choice_button_text' => 'Add new choice',
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
                'menu_order' => 9,
                'position' => 'normal',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ) );


        }, 11);


        /*
        * Register theme locations
        */
        add_action('after_setup_theme', function(){

            $locations = self::field('theme_locations');
        
            if($locations){
                
                foreach ($locations as $l) {
                    $__locations[$l['slug']] = $l['name_' . CL];
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
        * Make sure get_fields() use only what is in the REST
        add_filter('acf/get_fields', function($fields, $post_id) {

            $allowed_fields = [];
            
            foreach ($fields as $key => $field) {

                if ($field['show_in_rest']) {

                    $allowed_fields[$key] = $field;

                }

            }
            
            return $allowed_fields;

        }, 10, 2);
        */


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
                                'key' => 'field_678bca390083240hgvbnvh32',
                                'label' => (CL === 'fr' ? 'Nom français' : 'French Name'),
                                'name' => 'name_fr',
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
                                'key' => 'field_678bca39008f6',
                                'label' => (CL === 'fr' ? 'Nom anglais' : 'English Name'),
                                'name' => 'name_en',
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
                'menu_order' => 9,
                'position' => 'normal',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 0,
            ) );
            
            
        });


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