<?php

require_once 'inc/utils.php';
require_once 'inc/runtime/RouteResolver.php';
require_once 'inc/runtime/MenuBuilder.php';
require_once 'inc/runtime/Bootstrap.php';
require_once 'inc/runtime/FieldGroups.php';
require_once 'inc/admin.php';
require_once 'inc/firstload.php';
require_once 'inc/routes/rest.php';

function rwp_require_headless_api_runtime() {

    static $loaded = false;

    if($loaded){
        return;
    }

    $loaded = true;

    require_once __DIR__ . '/inc/runtime/PublicPayload.php';
    require_once __DIR__ . '/inc/runtime/PreviewToken.php';
    require_once __DIR__ . '/inc/runtime/HeadlessApi.php';

}

function rwp_is_headless_rest_request() {

    $request_uri = $_SERVER['REQUEST_URI'] ?? '';

    return strpos($request_uri, '/wp-json/reactwp/v1/') !== false
        || strpos($request_uri, 'rest_route=/reactwp/v1/') !== false
        || strpos($request_uri, 'rest_route=%2Freactwp%2Fv1%2F') !== false;

}

add_filter('rwp_allowed_rest_routes', function($routes){

    $routes = is_array($routes) ? $routes : [];

    return array_values(array_unique(array_merge($routes, [
        '/reactwp/v1/bootstrap',
        '/reactwp/v1/route',
        '/reactwp/v1/navigation',
        '/reactwp/v1/settings',
        '/reactwp/v1/sitemap',
        '/reactwp/v1/preview',
        '/reactwp/v1/auth/me',
        '/reactwp/v1/auth/login',
        '/reactwp/v1/auth/logout',
    ])));

});

add_filter('allowed_http_origins', function($origins){

    if(!rwp_is_headless_rest_request()){
        return $origins;
    }

    rwp_require_headless_api_runtime();

    return \ReactWP\Runtime\HeadlessApi::allowed_http_origins($origins);

});

add_action('rest_api_init', function(){

    rwp_require_headless_api_runtime();

    \ReactWP\Runtime\HeadlessApi::register_routes();

});

class ReactWP{
    
    function __construct(){

        add_action('init', function(){

            if(!defined('CL')){
                define('CL', substr(get_locale(), 0, 2));
            }

        }, 3);

        add_action('wp_enqueue_scripts', function(){

            $theme = wp_get_theme();
            $slug = $theme->get_stylesheet();

            $script_path = self::source([
                'path' => '/assets/js/' . $slug . '.min.js',
                'url' => false
            ]);
            $script_url = self::source([
                'path' => '/assets/js/' . $slug . '.min.js',
                'url' => true
            ]);
            $style_path = self::source([
                'path' => '/assets/css/' . $slug . '.min.css',
                'url' => false
            ]);
            $style_url = self::source([
                'path' => '/assets/css/' . $slug . '.min.css',
                'url' => true
            ]);

            $asset_version = $theme->get('Version') ?: null;
            $script_version = file_exists($script_path) ? filemtime($script_path) : $asset_version;
            $style_version = file_exists($style_path) ? filemtime($style_path) : $asset_version;

            if(file_exists($style_path)){
                wp_enqueue_style('rwp-theme', $style_url, [], $style_version);
            }

            wp_enqueue_script('rwp-main', $script_url, [], $script_version, false);

            add_filter('script_loader_tag', function($tag, $handle, $src){
                if($handle !== 'rwp-main'){
                    return $tag;
                }

                return '<script src="' . rwp::escape('url', $src) . '" defer></script>';

            }, 10, 3);

        });

        add_filter('acf/format_value', function($value){

            if(is_admin() && !wp_doing_ajax()){
                return $value;
            }

            return \ReactWP\Utils\Field::apply_replacement($value);

        }, 10, 3);

        add_filter('acf/settings/rest_api_format', function(){
            return 'standard';
        });

        add_filter('acf/rest/format_value_for_rest', function($value_formatted){

            return \ReactWP\Utils\Field::apply_replacement($value_formatted);

        }, 10, 5);

        add_filter('rest_authentication_errors', function($result){
    
            if(!empty($result)){
                return $result;
            }

            $allowed_routes = apply_filters('rwp_allowed_rest_routes', []);
            $requested_route = $_SERVER['REQUEST_URI'] ?? '';

            foreach($allowed_routes as $route){
                if(strpos($requested_route, $route) !== false){
                    return null;
                }
            }

            if(current_user_can('manage_options')){
                return $result;
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

    static function bootstrap(){

        return \ReactWP\Runtime\Bootstrap::payload();

    }

    static function preview_token($post_id, $ttl = null){

        rwp_require_headless_api_runtime();

        return \ReactWP\Runtime\PreviewToken::create($post_id, $ttl);

    }

    static function sanitize($type, $args = []){
        
        if(!is_array($args)) return null;

        switch($type){
            case 'email':
                return ReactWP\Utils\Sanitize::email($args);
            case 'file_name':
                return ReactWP\Utils\Sanitize::file_name($args);
            case 'hex_color':
                return ReactWP\Utils\Sanitize::hex_color($args);
            case 'hex_color_no_hash':
                return ReactWP\Utils\Sanitize::hex_color_no_hash($args);
            case 'html_class':
                return ReactWP\Utils\Sanitize::html_class($args);
            case 'key':
                return ReactWP\Utils\Sanitize::key($args);
            case 'meta':
                return ReactWP\Utils\Sanitize::meta($args);
            case 'mime_type':
                return ReactWP\Utils\Sanitize::mime_type($args);
            case 'option':
                return ReactWP\Utils\Sanitize::option($args);
            case 'sql_orderby':
                return ReactWP\Utils\Sanitize::sql_orderby($args);
            case 'term':
                return ReactWP\Utils\Sanitize::term($args);
            case 'term_field':
                return ReactWP\Utils\Sanitize::term_field($args);
            case 'text_field':
                return ReactWP\Utils\Sanitize::text_field($args);
            case 'textarea_field':
                return ReactWP\Utils\Sanitize::textarea_field($args);
            case 'slug':
            case 'title':
                return ReactWP\Utils\Sanitize::title($args);
            case 'title_for_query':
                return ReactWP\Utils\Sanitize::title_for_query($args);
            case 'title_with_dashes':
                return ReactWP\Utils\Sanitize::title_with_dashes($args);
            case 'user':
                return ReactWP\Utils\Sanitize::user($args);
            case 'url':
                return ReactWP\Utils\Sanitize::url($args);
            case 'html':
                return ReactWP\Utils\Sanitize::html($args);
            case 'post_content':
                return ReactWP\Utils\Sanitize::post_content($args);
            default:
                return null;
        }
        
    }

    static function escape($type, $value, $args = []){
        
        if(!is_array($args)) return null;

        switch($type){
            case 'html':
                return ReactWP\Utils\Escape::html($value);
            case 'js':
                return ReactWP\Utils\Escape::js($value);
            case 'url':
                return ReactWP\Utils\Escape::url($value);
            case 'url_raw':
                return ReactWP\Utils\Escape::url_raw($value);
            case 'xml':
                return ReactWP\Utils\Escape::xml($value);
            case 'attr':
                return ReactWP\Utils\Escape::attr($value);
            case 'textarea':
                return ReactWP\Utils\Escape::textarea($value);
            case 'html__':
                return ReactWP\Utils\Escape::html__($value, $args);
            case 'html_x':
                return ReactWP\Utils\Escape::html_x($value, $args);
            case 'attr__':
                return ReactWP\Utils\Escape::attr__($value, $args);
            case 'attr_x':
                return ReactWP\Utils\Escape::attr_x($value, $args);
            default:
                return null;
        }
        
    }

}

class_alias('ReactWP', 'rwp');

new rwp();
