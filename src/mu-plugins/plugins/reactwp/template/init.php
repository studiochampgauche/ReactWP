<?php

require_once 'inc/utils.php';
require_once 'inc/render.php';
require_once 'inc/firstload.php';


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