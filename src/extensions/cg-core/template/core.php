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
Text Domain: cg-core-plugin
Domain Path: /langs
*/


if(!defined('ABSPATH') || !class_exists('ACF')) return;



class StudioChampGauche{
    
    function __construct(){
        
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        
        /*
        * Shot Events on init
        */
        add_action('init', function(){
            
            /*
			* Load Languages
			*/
			load_plugin_textdomain('cg-core-plugin', false, __DIR__ . '/langs/');
            
            
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

                    if (!current_user_can('manage_options')){
                        return new WP_Error(
                            'cannot_access_rest',
                            __('JSON REST API IS PROTECTED'),
                            ['status' => 403]
                        );
                    }

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
            
        });
        
    }
    
    
    static function field($field = null, $id = null){
		
        if($field && $id)
			return get_field($field, $id);

		elseif($field)
			return !empty(get_field($field, 'option')) ? get_field($field, 'option') : get_field($field);


		return;
	}
    
}
class_alias('StudioChampGauche', 'scg');


new scg();

?>