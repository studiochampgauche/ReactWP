<?php

require_once 'inc/utils.php';
require_once 'inc/admin.php';
require_once 'inc/firstload.php';
require_once 'inc/routes/rest.php';


class ReactWP{
    
    function __construct(){

        add_action('init', function(){

            /*
            * Define Constante Language
            */
            if(!defined('CL')){
                define('CL', substr(get_locale(), 0, 2));
            }

        }, 3);


        /*
        * Enqueue scripts
        */
        add_action('wp_enqueue_scripts', function(){

            $theme = wp_get_theme();
            $slug = $theme->get_stylesheet();

            /*
            * JS
            */
            wp_enqueue_script('rwp-main', self::source(['path' => '/assets/js/'. $slug .'.min.js', 'url' => true]), null, null, false);


            /*
            * Remove rwp-main script type attribute and add defer
            */
            add_filter('script_loader_tag', function($tag, $handle, $src){
                if($handle !== 'rwp-main')
                    return $tag;

                $tag = '<script src="' . rwp::escape('url', $src) . '" defer></script>';

                return $tag;

            } , 10, 3);
            

            /*
            * Lang Reference
            */
            wp_localize_script('rwp-main', 'CL', ['value' => CL]);


            /*
            * System management
            */
            wp_localize_script('rwp-main', 'SYSTEM', apply_filters('rwp_system', [
                'public' => get_option('blog_public'),
                'baseUrl' => site_url('/'),
                'homeUrl' => home_url('/'),
                'adminUrl' => admin_url(),
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'restUrl' => rest_url(),
                'restNonce' => wp_create_nonce('wp_rest')
            ]));


            /*
            * Assets Management
            */
            wp_localize_script('rwp-main', 'ASSETS', [
                'no_critical_medias' => apply_filters('rwp_no_critical_medias', []),
                'critical_medias' => apply_filters('rwp_critical_medias', []),
                'critical_fonts' => apply_filters('rwp_critical_fonts', [])
            ]);

            
        });


        /*
        * ACF Replace values from php functions
        */
        add_filter('acf/format_value', function ($value, $post_id, $field){

            return \ReactWP\Utils\Field::apply_replacement($value);
            
        }, 10, 3);


        /*
        * ACF Replace values from REST API
        */
        add_filter('acf/settings/rest_api_format', function () {
            return 'standard';
        });

        add_filter('acf/rest/format_value_for_rest', function ($value_formatted, $post_id, $field, $value, $format){

            return \ReactWP\Utils\Field::apply_replacement($value_formatted);
            
        }, 10, 5);


        /*
        * Manage REST API
        */
        add_filter('rest_authentication_errors', function ($result) {
    
            if (!empty($result)) {
                return $result;
            }

            $allowed_routes = apply_filters('rwp_allowed_rest_routes', []);

            $requested_route = $_SERVER['REQUEST_URI'] ?? '';

            if($allowed_routes){

                foreach ($allowed_routes as $route) {

                    if (strpos($requested_route, $route) !== false) {
                        return null;
                    }

                }
                
            }

            return new WP_Error(
                'rest_api_disabled',
                __('The REST API is disable.'),
                ['status' => 403]
            );
            
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

    static function sanitize($type, $args = []){
        
        if(!is_array($args)) return null;

        switch ($type) {
            case 'email':
                return ReactWP\Utils\Sanitize::email($args);
                break;
                
            case 'file_name':
                return ReactWP\Utils\Sanitize::file_name($args);
                break;
                
            case 'hex_color':
                return ReactWP\Utils\Sanitize::hex_color($args);
                break;
                
            case 'hex_color_no_hash':
                return ReactWP\Utils\Sanitize::hex_color_no_hash($args);
                break;
                
            case 'html_class':
                return ReactWP\Utils\Sanitize::html_class($args);
                break;
                
            case 'key':
                return ReactWP\Utils\Sanitize::key($args);
                break;
                
            case 'meta':
                return ReactWP\Utils\Sanitize::meta($args);
                break;
                
            case 'mime_type':
                return ReactWP\Utils\Sanitize::mime_type($args);
                break;

            case 'option':
                return ReactWP\Utils\Sanitize::option($args);
                break;
                
            case 'sql_orderby':
                return ReactWP\Utils\Sanitize::sql_orderby($args);
                break;
                
            case 'term':
                return ReactWP\Utils\Sanitize::term($args);
                break;
                
            case 'term_field':
                return ReactWP\Utils\Sanitize::term_field($args);
                break;

            case 'text_field':
                return ReactWP\Utils\Sanitize::text_field($args);
                break;
                
            case 'textarea_field':
                return ReactWP\Utils\Sanitize::textarea_field($args);
                break;
                
            case 'slug':
            case 'title':
                return ReactWP\Utils\Sanitize::title($args);
                break;
                
            case 'title_for_query':
                return ReactWP\Utils\Sanitize::title_for_query($args);
                break;
                
            case 'title_with_dashes':
                return ReactWP\Utils\Sanitize::title_with_dashes($args);
                break;
                
            case 'user':
                return ReactWP\Utils\Sanitize::user($args);
                break;
                
            case 'url':
                return ReactWP\Utils\Sanitize::url($args);
                break;
                
            case 'html':
                return ReactWP\Utils\Sanitize::html($args);
                break;
                
            case 'post_content':
                return ReactWP\Utils\Sanitize::post_content($args);
                break;
                
            default:
                return null;
                break;
        }
        
    }

    static function escape($type, $value, $args = []){
        
        if(!is_array($args)) return null;

        switch ($type) {
            case 'html':
                return ReactWP\Utils\Escape::html($value);
                break;
                
            case 'js':
                return ReactWP\Utils\Escape::js($value);
                break;
                
            case 'url':
                return ReactWP\Utils\Escape::url($value);
                break;
                
            case 'url_raw':
                return ReactWP\Utils\Escape::url_raw($value);
                break;
                
            case 'xml':
                return ReactWP\Utils\Escape::xml($value);
                break;
                
            case 'attr':
                return ReactWP\Utils\Escape::attr($value);
                break;
                
            case 'textarea':
                return ReactWP\Utils\Escape::textarea($value);
                break;
                
            case 'html__':
                return ReactWP\Utils\Escape::html__($value, $args);
                break;
                
            case 'html_x':
                return ReactWP\Utils\Escape::html_x($value, $args);
                break;
                
            case 'attr__':
                return ReactWP\Utils\Escape::attr__($value, $args);
                break;

            case 'attr_x':
                return ReactWP\Utils\Escape::attr_x($value, $args);
                break;
                
            default:
                return null;
                break;
        }
        
    }

}

class_alias('ReactWP', 'rwp');

new rwp();