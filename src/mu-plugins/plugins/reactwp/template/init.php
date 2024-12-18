<?php

if(!defined('ABSPATH')) return;

/*
* Make sure you have all requirements for plugins
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';
            

/*
* Classes
*/
require_once 'class/render.php';
require_once 'class/utils.php';
require_once 'class/seo.php';


class ReactWP{
    
    function __construct(){
        
        /*
        * Shot Events on init
        */
        add_action('init', function(){


            /*
            * Top Bar
            */
            add_filter('show_admin_bar', '__return_false');
            
            
            /*
            * Manage Front End Source Code
            */
            $sourceCodeElements = self::field('source_code');
            
            if($sourceCodeElements){
                $excludedElements = ['feed_links', 'feed_links_extra', 'wp_resource_hints', 'print_emoji_detection_script', 'print_emoji_styles', 'wp_shortlink_wp_head', 'wp_shortlink_header', 'wp_block_library', 'classic_theme_styles', 'global_styles'];

                $elements = array_diff_key($sourceCodeElements, array_flip($excludedElements));

                foreach($elements as $k => $v){

                    if($v) continue;

                    remove_action('wp_head', $k);

                }

                if(!$sourceCodeElements['wp_resource_hints'])
                    remove_action('wp_head', 'wp_resource_hints', 2);

                if(!$sourceCodeElements['feed_links'])
                    remove_action('wp_head', 'feed_links', 2);

                if(!$sourceCodeElements['feed_links_extra'])
                    remove_action('wp_head', 'feed_links_extra', 3);

                if(!$sourceCodeElements['print_emoji_detection_script'])
                    remove_action('wp_head', 'print_emoji_detection_script', 7);

                if(!$sourceCodeElements['print_emoji_styles'])
                    remove_action('wp_print_styles', 'print_emoji_styles');

                if(!$sourceCodeElements['wp_shortlink_wp_head'])
                    remove_action('wp_head', 'wp_shortlink_wp_head', 10);

                if(!$sourceCodeElements['wp_shortlink_header'])
                    remove_action('template_redirect', 'wp_shortlink_header', 11);
            }

            
            /*
            * Accept SVG
            */
            if(self::field('accept_svg')){
                
                add_filter('upload_mimes', function($mimes){
                    
                    $mimes['svg'] = 'image/svg+xml';
                    return $mimes;
                    
                });

                add_filter('wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes) {

                    $filetype = wp_check_filetype($filename, $mimes);

                    return [
                        'ext' => $filetype['ext'],
                        'type' => $filetype['type'],
                        'proper_filename' => $data['proper_filename']
                    ];

                }, 10, 4);
                
            }
            
            
            /*
            * Remove Upload Resizes
            */
            if(!self::field('resize_images')){
                add_filter('intermediate_image_sizes_advanced', function($size, $metadata){
                    return [];
                }, 10, 2);
            }
            
            
            /*
            * Register Theme Locations
            */
			$locations = self::field('theme_locations');
			
			if($locations){
                
				foreach ($locations as $l) {
					$__locations[$l['slug']] = $l['name'];
				}
                
				register_nav_menus($__locations);
                
			}


            /*
            * On firstload
            */
            if (get_option('rwp_firstload') < 1) {

                update_option('rwp_firstload', 1);


                /*
                * Delete default posts/pages
                */
                foreach(self::cpt(['post', 'page'], ['post_status' => ['publish', 'draft']])->posts as $item){

                    wp_delete_post($item->ID, true);

                }

                /*
                * Change Admin Appearance
                */
                update_field('field_6569a23ae02cc', true, 'option');


                /*
                * Change Dashboard
                */
                update_field('field_65697d9722de6', [
                    'welcome_panel' => false,
                    'dashboard_incoming_links' => false,
                    'dashboard_plugins' => false,
                    'dashboard_primary' => false,
                    'dashboard_secondary' => false,
                    'dashboard_quick_press' => false,
                    'dashboard_recent_drafts' => false,
                    'dashboard_recent_comments' => false,
                    'dashboard_right_now' => false,
                    'dashboard_activity' => false,
                    'dashboard_site_health' => false
                ], 'option');


                /*
                * Classic editor and Gutenberg
                */
                update_field('field_65698585f5582', [
                    'gutenberg' => false,
                    'page_editor' => false,
                    'post_editor' => false
                ], 'option');


                /*
                * Theme Customize
                */
                update_field('field_65698585f5582', [
                    'themes' => false,
                    'nav_menus' => false,
                    'title_tagline' => false,
                    'static_front_page' => false,
                    'custom_css' => false,
                    'colors' => false
                ], 'option');


                /*
                * Theme locations
                */
                update_field('field_6569c7eac0820', [
                    [
                        'name' => 'Main Menu',
                        'slug' => 'main_menu',
                    ]
                ], 'option');


                /*
                * Source code
                */
                update_field('field_6569647a500a8', [
                    'rsd_link' => false,
                    'wlwmanifest_link' => false,
                    'wp_generator' => false,
                    'start_post_rel_link' => false,
                    'index_rel_link' => false,
                    'feed_links' => false,
                    'feed_links_extra' => false,
                    'adjacent_posts_rel_link' => false,
                    'rest_output_link_wp_head' => false,
                    'wp_oembed_add_discovery_links' => false,
                    'wp_resource_hints' => false,
                    'print_emoji_detection_script' => false,
                    'print_emoji_styles' => false,
                    'rel_canonical' => false,
                    'wp_shortlink_wp_head' => false,
                    'wp_shortlink_header' => false,
                    'wp_block_library' => false,
                    'classic_theme_styles' => false,
                    'global_styles' => false
                ], 'option');


                /*
                * Allow SVG
                */
                update_field('field_656982bae9569', true, 'option');


                /*
                * Stop resizing uploaded images
                */
                update_field('field_6569b442163b7', false, 'option');


                /*
                * Create Home Page
                */
                $homePageArgs = array(
                    'post_title' => 'Home',
                    'post_type' => 'page',
                    'post_content' => '',
                    'post_status' => 'publish',
                    'post_author' => 1,
                );
                $homePage_id = wp_insert_post($homePageArgs);


                /*
                * Setup home page as a Static Page
                */
                update_option('show_on_front', 'page'); 
                update_option('page_on_front', $homePage_id);


                /*
                * Update permalink Structure
                */
                update_option('permalink_structure', '/%postname%/');
                flush_rewrite_rules();

            }
            
        }, 11);


        /*
        * wp_enqueue_scripts
        */
        add_action('wp_enqueue_scripts', function(){

            /*
            * Remove Basics Styles
            */
            if(!self::field('source_code_global_styles'))
                wp_dequeue_style('global-styles');
            
            if(!self::field('source_code_wp_block_library'))
                wp_dequeue_style('wp-block-library');
            
            if(!self::field('source_code_classic_theme_styles'))
                wp_dequeue_style('classic-theme-styles');


            /*
            * Add Main Style
            */
            //wp_enqueue_style('rwp-main', get_stylesheet_directory_uri() . '/assets/css/main.min.css', null, null, null);


            /*
            * Add Main Javascript
            */
            wp_enqueue_script('rwp-main', get_stylesheet_directory_uri() . '/assets/js/main.min.js', null, null, true);



            /*
            * Add SYSTEM global variable
            */
            $siteUrl = site_url();
            wp_localize_script('rwp-main', 'SYSTEM', [
                'public' => get_option('blog_public'),
                'cacheActive' => (self::field('cache_module') ? true : false),
                'cacheVersion' => (self::field('cache_version') ? self::field('cache_version') : 0),
                'cacheExpiration' => (self::field('cache_expiration') ? self::field('cache_expiration') : 0),
                'consentActive' => (self::field('consent_module') ? true : false),
                'consentVersion' => (self::field('consent_configs_version') ? self::field('consent_configs_version') : 0),
                'consentExpiration' => (self::field('consent_configs_expiration') ? self::field('consent_configs_expiration') : 0),
                'baseUrl' => $siteUrl,
                'adminUrl' => $siteUrl . '/wp-admin',
                'ajaxPath' => '/wp-admin/admin-ajax.php',
                'restPath' => '/wp-json'
            ]);


            /*
            * Add defaultSEO global variable
            */
            $defaultSEO = self::field('seo', 'option');
            $defaultSEO['blogName'] = \ReactWP\SEO\SEO::site_name();

            wp_localize_script('rwp-main', 'defaultSEO', $defaultSEO);

            
            
            /*
            * Remove rwp-main script type attribute
            */
            add_filter('script_loader_tag', function($tag, $handle, $src){
                if($handle !== 'rwp-main')
                    return $tag;

                $tag = '<script src="' . esc_url( $src ) . '"></script>';

                return $tag;

            } , 10, 3);



        });
        
        
        /*
        * Shot Events on admin_init
        */
        add_action('admin_init', function(){
            
            /*
            * Manage Dashboard Meta Boxes
            */
            $dashElements = self::field('dashboard');
            
            if($dashElements){
                $excludedElements = ['welcome_panel', 'dashboard_primary', 'dashboard_quick_press', 'dashboard_recent_drafts'];

                $elements = array_diff_key($dashElements, array_flip($excludedElements));

                foreach($elements as $k => $v){

                    if($v) continue;

                    remove_meta_box($k, 'dashboard', 'normal');

                }
                
                if(!$dashElements['welcome_panel'])
                    remove_action('welcome_panel', 'wp_welcome_panel');
                
                if(!$dashElements['dashboard_primary'])
                    remove_meta_box('dashboard_primary', 'dashboard', 'side');
                
                if(!$dashElements['dashboard_quick_press'])
                    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
                
                if(!$dashElements['dashboard_recent_drafts'])
                    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');
                
            }
            
            
            /*
            * Manage Gutenberg Presence
            */
            if(!self::field('editor_and_gutenberg_gutenberg'))
                add_filter('use_block_editor_for_post_type', '__return_false', 10);
            
            /*
            * Manage Page Editor Presence
            */
            if(!self::field('editor_and_gutenberg_page_editor'))
                remove_post_type_support('page', 'editor');
            
            /*
            * Manage Post Editor Presence
            */
            if(!self::field('editor_and_gutenberg_post_editor'))
                remove_post_type_support('post', 'editor');
            
            
        });
        
        
        /*
        * Shot Events on admin_menu
        */
        add_action('admin_menu', function(){
            
            /*
            * Remove Menu Items if Change Appearance is true
            */
            if(self::field('change_appearance')){
                
                remove_menu_page('tools.php');
				remove_menu_page('upload.php');
				remove_menu_page('themes.php');
				remove_menu_page('plugins.php');
				remove_menu_page('edit-comments.php');
				remove_menu_page('users.php');
				remove_menu_page('edit.php?post_type=acf-field-group');

				remove_submenu_page('options-general.php', 'options-privacy.php');
				remove_submenu_page('options-general.php', 'options-media.php');
				remove_submenu_page('options-general.php', 'options-writing.php');
				remove_submenu_page('options-general.php', 'options-discussion.php');
                
            }
            
        });
        
        
        /*
        * Shot Events on admin_bar_menu
        */
        add_action('admin_bar_menu', function(){
            
            global $wp_admin_bar;
            
            /*
            * Remove Menu Items if Change Appearance is true
            */
            if(self::field('change_appearance')){
                
                $admin_url = admin_url();
                
                $wp_admin_bar->remove_node('wp-logo');
				$wp_admin_bar->remove_node('site-name');
				$wp_admin_bar->remove_node('comments');
				$wp_admin_bar->remove_node('new-content');
                
                if(
                    !current_user_can('update_core')
                    
                    ||
                    
                    !current_user_can('update_plugins')
                    
                    ||
                    
                    !current_user_can('update_themes')
                    
                ) $wp_admin_bar->remove_node( 'updates' );
                
                
                /*
                * Add Home URL
                */
                $args = array(
					'id' => 'is-website',
					'title' => get_bloginfo('name'),
					'href' => '/',
					'target' => '_blank',
					'meta' => array(
						'class' => 'is-website'
					)
				);
				$wp_admin_bar->add_node($args);
                
                
                /*
                * Navigations Management
                */
                $args = array(
					'id' => 'is-menus',
					'title' => __('Menus', 'rwp-core'),
					'href' => $admin_url . 'nav-menus.php',
					'meta' => array(
						'class' => 'is-menus'
					)
				);
				if(current_user_can('edit_theme_options') && self::field('theme_locations'))
					$wp_admin_bar->add_node($args);
                
                
                /*
				* Files Management
				*/
				$args = array(
					'id' => 'is-files',
					'title' => __('Images & files', 'rwp-core'),
					'href' => $admin_url . 'upload.php',
					'meta' => array(
						'class' => 'is-files'
					)
				);
				if(current_user_can('upload_files'))
				    $wp_admin_bar->add_node($args);
                
                
                
                /*
                * User List and Personal Profile
                */
                if(current_user_can('list_users')){
                    
                    $args = array(
                        'id' => 'is-users-list',
                        'title' => __('Users', 'rwp-core'),
                        'href' => $admin_url . 'users.php',
                        'meta' => array(
                            'class' => 'is-users-list'
                        )
                    );
                    
                    $wp_admin_bar->add_node($args);
                    
                    $args = array(
                        'id' => 'is-users-profile',
                        'title' => __('Profile', 'rwp-core'),
                        'href' => $admin_url . 'profile.php',
                        'parent' => 'is-users-list',
                        'meta' => array(
                            'class' => 'is-users-profile'
                        )
                    );
                    $wp_admin_bar->add_node($args);
                    
                } else {
                    
                    $args = array(
                        'id' => 'is-users-profile',
                        'title' => __('Profile', 'rwp-core'),
                        'href' => $admin_url . 'profile.php',
                        'meta' => array(
                            'class' => 'is-users-profile'
                        )
                    );
                    $wp_admin_bar->add_node($args);
                    
                }
                
                
                /*
                * Configurations Tab
                */
                if(current_user_can('edit_theme_options')){
                    
                    /*
                    * The Tab
                    */
                    $args = array(
						'id' => 'is-site',
						'title' => __('ReactWP', 'rwp-core'),
						'meta' => array(
							'class' => 'is-site'
						)
					);
					$wp_admin_bar->add_node($args);
                    
                    
                    /*
                    * Configurations
                    */
                    $args = array(
						'id' => 'is-site-settings',
						'title' => __('Site settings', 'rwp-core'),
						'href' => $admin_url . 'admin.php?page=site-settings',
						'parent' => 'is-site',
						'meta' => array(
							'class' => 'is-site-settings'
						)
					);
					$wp_admin_bar->add_node($args);
                    
                    
                    /*
					* Themes Management
					*/
                    if(current_user_can('switch_themes')){
                        $args = array(
                            'id' => 'is-site-themes',
                            'title' => __('Themes', 'rwp-core'),
                            'href' => $admin_url . 'themes.php',
                            'parent' => 'is-site',
                            'meta' => array(
                                'class' => 'is-site-themes'
                            )
                        );
                        $wp_admin_bar->add_node($args);



                        /*
                        * Add Theme Editor Management
                        */
                        $args = array(
                            'id' => 'is-site-themes-editor',
                            'title' => __('Editor', 'rwp-core'),
                            'href' => $admin_url . 'theme-editor.php',
                            'parent' => 'is-site-themes',
                            'meta' => array(
                                'class' => 'is-site-themes-editor'
                            )
                        );
                        if(current_user_can('edit_themes'))
                            $wp_admin_bar->add_node($args);
                        
                    }
                    
                    
                    /*
                    * Plugins Management
                    */
                    if(current_user_can('activate_plugins')){
                        
                        $args = array(
                            'id' => 'is-site-plugins',
                            'title' => __('Plugins', 'rwp-core'),
                            'href' => $admin_url . 'plugins.php',
                            'parent' => 'is-site',
                            'meta' => array(
                                'class' => 'is-site-plugins'
                            )
                        );
                        $wp_admin_bar->add_node($args);
                        
                        
                        /*
                        * Add Plugin Editor Management
                        */
                        $args = array(
                            'id' => 'is-site-plugin-editor',
                            'title' => __('Editor', 'rwp-core'),
                            'href' => $admin_url . 'plugin-editor.php',
                            'parent' => 'is-site-plugins',
                            'meta' => array(
                                'class' => 'is-site-plugins-editor'
                            )
                        );
                        if(current_user_can('edit_plugins'))
                            $wp_admin_bar->add_node($args);
                        
                        
                        /*
                        * ACF PRO Management
                        */
                        $args = array(
                            'id' => 'is-acf',
                            'title' => __('ACF', 'rwp-core'),
                            'href' => $admin_url . 'edit.php?post_type=acf-field-group',
                            'parent' => 'is-site',
                            'meta' => array(
                                'class' => 'is-acf'
                            )
                        );
                        $wp_admin_bar->add_node($args);
                        
                        
                        /*
                        * Add Import Management
                        */
                        $args = array(
                            'id' => 'is-site-import',
                            'title' => __('Import', 'rwp-core'),
                            'href' => $admin_url . 'import.php',
                            'parent' => 'is-site',
                            'meta' => array(
                                'class' => 'is-site-import'
                            )
                        );
                        if(current_user_can('import'))
                            $wp_admin_bar->add_node($args);
                        
                        
                        /*
                        * Add Export Management
                        */
                        $args = array(
                            'id' => 'is-site-export',
                            'title' => __('Export', 'rwp-core'),
                            'href' => $admin_url . 'export.php',
                            'parent' => 'is-site',
                            'meta' => array(
                                'class' => 'is-site-export'
                            )
                        );
                        if(current_user_can('export'))
                            $wp_admin_bar->add_node($args);

                    }
                    
                }
                
            }
            
        }, 99);
        
        
        /*
        * Shot Events on admin_head
        */
        add_action('admin_head', function(){
            
            /*
            * Add some Admin Styles when Change Appearance is true
            */
            if(self::field('change_appearance')){
                
                echo '<style type="text/css">#toplevel_page_site-settings{display: none !important;}</style>';
                
            }
            
        });



        /*
        * Shot Events on wp_head
        */
        add_action('wp_head', function(){
            
            echo '
                <style type="text/css">
                    '. file_get_contents(get_stylesheet_directory_uri() . '/assets/css/main.min.css') .'
                </style>
            ';

        }, 3);


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
        * Shot events on customize_register
        */
        add_action('customize_register', function($wp_customize){

            if(!self::field('customize_themes'))
                $wp_customize->remove_panel('themes');

            if(!self::field('customize_nav_menus'))
                $wp_customize->remove_panel('nav_menus');

            if(!self::field('customize_title_tagline'))
                $wp_customize->remove_section('title_tagline');

            if(!self::field('customize_static_front_page'))
                $wp_customize->remove_section('static_front_page');

            if(!self::field('customize_custom_css'))
                $wp_customize->remove_section('custom_css');

            if(!self::field('customize_colors'))
                $wp_customize->remove_section('colors');

        }, 20);
        
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
class_alias('ReactWP', 'scg');

new rwp();

?>