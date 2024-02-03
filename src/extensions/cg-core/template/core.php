<?php
/*
Plugin Name: Champ Gauche Core
Author: Studio Champ Gauche
Author URI: https://champgauche.studio
Plugin URI: https://wpboilerplate.champgauche.studio
Description: A plugin that handles repetitive needs in each project and add-ons.
Requires at least: 6.4.1
Requires PHP: 8.2
Version: 3.0.0
Text Domain: cg-core
Domain Path: /langs
*/


if(!defined('ABSPATH') || !class_exists('ACF')) return;

/*
* Make sure you have all you need for the plugin
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';


/*
* Load Languages
*/
load_plugin_textdomain('cg-core', false, basename(__DIR__) . '/langs/');


/*
* Classes
*/
require_once 'class/render.php';
require_once 'class/utils.php';
require_once 'class/seo.php';


class StudioChampGauche{
    
    private $acf_path;
    
    function __construct(){
        
        
        
        /*
        * ACF Path
        */
        $this->acf_path = self::source(['path' => 'datas/acf', 'url' => false]);
        
        
        /*
        * Shot Events on init
        */
        add_action('init', function(){
			
            /*
            * Top Bar
            */
            if(!self::field('top_bar'))
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
            * Protect REST API
            */
            if(self::field('protect_rest_api')){
                add_filter('rest_authentication_errors', function($result) {

                    if (!current_user_can('manage_options'))
                        return wp_send_json_error(['message' => 'JSON REST API IS PROTECTED.'], (is_user_logged_in ? 403 : 401));

                    return $result;

                });
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
            
        });
        
        
        /*
        * Manage Styles & Scripts
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
			* Main Style
			*/
			wp_enqueue_style('scg-main', get_bloginfo('stylesheet_directory').'/assets/css/main.min.css', null, null, null);


			/*
			* Main Javascript
			*/
			wp_enqueue_script('scg-main', get_bloginfo('stylesheet_directory') .'/assets/js/App.js', null, null, true);
            
            
            add_filter('script_loader_tag', function($tag, $handle, $src){
                if($handle !== 'scg-main')
                    return $tag;

                $tag = '<script type="module" src="' . esc_url( $src ) . '"></script>';

                return $tag;

            } , 10, 3);
            
            
        }, 10);
        
        
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
            
            
            /*
            * Create ACF JSON Area
            */
            if(!file_exists($this->acf_path)){
				mkdir($this->acf_path, 0777, true);
				fopen($this->acf_path . '/index.php', 'w');
			}
            
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
					'href' => home_url(),
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
					'title' => __('Menus', 'cg-core'),
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
					'title' => __('Images et fichiers', 'cg-core'),
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
                        'title' => __('Utilisateurs', 'cg-core'),
                        'href' => $admin_url . 'users.php',
                        'meta' => array(
                            'class' => 'is-users-list'
                        )
                    );
                    
                    $wp_admin_bar->add_node($args);
                    
                    $args = array(
                        'id' => 'is-users-profile',
                        'title' => __('Profil', 'cg-core'),
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
                        'title' => __('Profil', 'cg-core'),
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
						'title' => __('Configurations', 'cg-core'),
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
						'title' => __('Générales', 'cg-core'),
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
                            'title' => __('Thèmes', 'cg-core'),
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
                            'title' => __('Éditeur', 'cg-core'),
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
                            'title' => __('Extensions', 'cg-core'),
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
                            'title' => __('Éditeur', 'cg-core'),
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
                            'title' => __('ACF', 'cg-core'),
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
                            'title' => __('Importer', 'cg-core'),
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
                            'title' => __('Exporter', 'cg-core'),
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
        * Shot Events on template_redirect
        */
        add_action('template_redirect', function(){
            
            if(!self::field('maintenance_mode')) return;
            
            $user = wp_get_current_user();
            $roles = $user->ID ? $user->roles : null;
            
            if(
                !is_home()
                
                &&
                
                !is_front_page()
                
                &&
                
                (
                    !$roles
                    
                    ||
                    
                    !in_array('administrator', $roles)
                )
            ){
                
                wp_redirect(home_url());
                
                exit;
                
            }
            
        });
        
        
        /*
		* Save ACF in JSON
		*/
		add_filter('acf/settings/save_json', function($path){

			return $this->acf_path;

		});
        
        
        /*
		* Load ACF in JSON
		*/
		add_filter('acf/settings/load_json', function($paths){

			// Remove original path
			unset( $paths[0] );

			// Append our new path
			$paths[] = $this->acf_path;

			return $paths;
		});
        
    }
    
    
    static function field($field = null, $id = null){
		
        return StudioChampGauche\Utils\Field::get($field, $id);
        
	}
    
    static function cpt($post_type = 'post', $args = []){
        
		return StudioChampGauche\Utils\CustomPostType::get($post_type, $args);
        
	}

	static function menu($theme_location = null, $args = []){

		return StudioChampGauche\Utils\Menu::get($theme_location, $args);
        
	}

	static function button($args = []){
        
        return StudioChampGauche\Utils\Button::get($args);
        
	}

	static function source($args = []){
        
        return StudioChampGauche\Utils\Source::get($args);
        
	}
    
    
}

class_alias('StudioChampGauche', 'scg');

new scg();

?>