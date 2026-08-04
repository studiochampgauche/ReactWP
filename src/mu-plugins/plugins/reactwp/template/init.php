<?php

if(!defined('ABSPATH')){
    exit;
}

require_once __DIR__ . '/inc/utils.php';
require_once __DIR__ . '/inc/runtime/RenderStrategy.php';
require_once __DIR__ . '/inc/runtime/RestAccess.php';
require_once __DIR__ . '/inc/runtime/RouteResolver.php';
require_once __DIR__ . '/inc/runtime/MenuBuilder.php';
require_once __DIR__ . '/inc/runtime/ClientCache.php';
require_once __DIR__ . '/inc/runtime/RenderCache.php';
require_once __DIR__ . '/inc/runtime/ServerRenderer.php';
require_once __DIR__ . '/inc/runtime/InitialRender.php';
require_once __DIR__ . '/inc/runtime/StaticRegenerator.php';
require_once __DIR__ . '/inc/runtime/TemplateAssets.php';
require_once __DIR__ . '/inc/runtime/Bootstrap.php';
require_once __DIR__ . '/inc/runtime/PublicPayload.php';
require_once __DIR__ . '/inc/runtime/FieldGroups.php';
require_once __DIR__ . '/inc/admin.php';
require_once __DIR__ . '/inc/firstload.php';
require_once __DIR__ . '/inc/routes/rest.php';

\ReactWP\Runtime\RenderCache::boot();
\ReactWP\Runtime\StaticRegenerator::boot();

function rwp_require_headless_api_runtime() {

    static $loaded = false;

    if($loaded){
        return;
    }

    $loaded = true;

    require_once __DIR__ . '/inc/runtime/PreviewToken.php';
    require_once __DIR__ . '/inc/runtime/HeadlessApi.php';

}

function rwp_requested_rest_route() {

    global $wp;

    $rewrite_route = isset($wp->query_vars['rest_route']) && is_string($wp->query_vars['rest_route'])
        ? wp_unslash($wp->query_vars['rest_route'])
        : null;
    $query_route = isset($_GET['rest_route']) && is_string($_GET['rest_route'])
        ? wp_unslash($_GET['rest_route'])
        : null;
    $request_uri = isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
        ? wp_unslash($_SERVER['REQUEST_URI'])
        : '';
    $rest_prefix = function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : 'wp-json';

    return \ReactWP\Runtime\RestAccess::requested_route(
        $request_uri,
        $rewrite_route,
        $query_route,
        $rest_prefix
    );

}

function rwp_is_headless_rest_request() {

    $requested_route = rwp_requested_rest_route();

    return \ReactWP\Runtime\RestAccess::is_namespace($requested_route, '/reactwp/v1');

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

    remove_filter('rest_pre_serve_request', 'rest_send_cors_headers');
    add_filter(
        'rest_pre_serve_request',
        [\ReactWP\Runtime\HeadlessApi::class, 'send_cors_headers'],
        10,
        4
    );

    \ReactWP\Runtime\HeadlessApi::register_routes();

});

add_action('send_headers', function(){

    if(is_admin()){
        return;
    }

    if(function_exists('header_remove')){
        header_remove('X-Powered-By');
    }

    if(is_user_logged_in()){
        nocache_headers();
        header('Vary: Cookie', false);
    }

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: ' . apply_filters(
        'rwp_permissions_policy',
        'camera=(), geolocation=(), microphone=()'
    ));

    $content_security_policy = trim((string)apply_filters(
        'rwp_content_security_policy',
        "base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'"
    ));

    if($content_security_policy !== ''){
        header('Content-Security-Policy: ' . $content_security_policy);
    }

    if(is_ssl() && wp_get_environment_type() === 'production'){
        $hsts = trim((string)apply_filters('rwp_hsts_header', 'max-age=31536000'));

        if($hsts !== ''){
            header('Strict-Transport-Security: ' . $hsts);
        }
    }

});

add_filter('xmlrpc_enabled', function($enabled){

    return (bool)apply_filters('rwp_allow_xmlrpc', false, $enabled);

});

add_filter('xmlrpc_methods', function($methods){

    return apply_filters('rwp_allow_xmlrpc', false, true)
        ? $methods
        : [];

});

add_filter('wp_headers', function($headers){

    if(!apply_filters('rwp_allow_xmlrpc', false, true)){
        unset($headers['X-Pingback']);
    }

    return $headers;

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
            $asset_version = $theme->get('Version') ?: null;
            $client_cache_version = \ReactWP\Runtime\ClientCache::version();
            $version_asset = static function($version) use ($client_cache_version){
                $parts = array_filter([
                    is_scalar($version) ? (string)$version : '',
                    $client_cache_version,
                ], static function($part){
                    return $part !== '';
                });

                return implode('-', $parts);
            };
            $manifest_path = self::source([
                'path' => '/assets/js/entrypoints.json',
                'url' => false
            ]);
            $manifest = [];

            if(file_exists($manifest_path)){
                $manifest = json_decode((string)file_get_contents($manifest_path), true);
                $manifest = is_array($manifest) ? $manifest : [];
            }

            $normalize_assets = static function($assets, $extension){
                if(!is_array($assets)){
                    return [];
                }

                return array_values(array_filter($assets, static function($asset) use ($extension){
                    if(!is_string($asset) || strpos($asset, '..') !== false){
                        return false;
                    }

                    return substr($asset, -strlen($extension)) === $extension;
                }));
            };

            $script_assets = $normalize_assets($manifest['scripts'] ?? [], '.js');
            $style_assets = $normalize_assets($manifest['styles'] ?? [], '.css');

            if(empty($script_assets)){
                foreach([
                    'assets/js/' . $slug . '.min.js',
                    'assets/js/' . $slug . '.js'
                ] as $fallback_asset){
                    $fallback_path = self::source([
                        'path' => '/' . $fallback_asset,
                        'url' => false
                    ]);

                    if(file_exists($fallback_path)){
                        $script_assets[] = $fallback_asset;
                        break;
                    }
                }
            }

            $style_path = self::source([
                'path' => '/assets/css/' . $slug . '.min.css',
                'url' => false
            ]);
            $style_url = self::source([
                'path' => '/assets/css/' . $slug . '.min.css',
                'url' => true
            ]);
            $style_version = $version_asset(file_exists($style_path) ? filemtime($style_path) : $asset_version);

            if(file_exists($style_path)){
                wp_enqueue_style('rwp-theme', $style_url, [], $style_version);
            }

            foreach($style_assets as $index => $style_asset){
                $asset_path = self::source([
                    'path' => '/' . ltrim($style_asset, '/'),
                    'url' => false
                ]);

                if(!file_exists($asset_path)){
                    continue;
                }

                wp_enqueue_style(
                    'rwp-theme-chunk-' . ($index + 1),
                    self::source([
                        'path' => '/' . ltrim($style_asset, '/'),
                        'url' => true
                    ]),
                    file_exists($style_path) ? ['rwp-theme'] : [],
                    $version_asset(filemtime($asset_path))
                );
            }

            $script_entries = [];

            foreach($script_assets as $script_asset){
                $asset_path = self::source([
                    'path' => '/' . ltrim($script_asset, '/'),
                    'url' => false
                ]);

                if(file_exists($asset_path)){
                    $script_entries[] = [
                        'asset' => $script_asset,
                        'path' => $asset_path
                    ];
                }
            }

            $script_handles = [];
            $previous_handle = null;
            $last_script_index = count($script_entries) - 1;

            foreach($script_entries as $index => $script_entry){
                $script_asset = $script_entry['asset'];

                $handle = $index === $last_script_index
                    ? 'rwp-main'
                    : 'rwp-main-chunk-' . ($index + 1);

                wp_enqueue_script(
                    $handle,
                    self::source([
                        'path' => '/' . ltrim($script_asset, '/'),
                        'url' => true
                    ]),
                    $previous_handle ? [$previous_handle] : [],
                    $version_asset(filemtime($script_entry['path'])),
                    false
                );

                $script_handles[] = $handle;
                $previous_handle = $handle;
            }

            add_filter('script_loader_tag', function($tag, $handle, $src) use ($script_handles){
                if(!in_array($handle, $script_handles, true)){
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
            $requested_route = rwp_requested_rest_route();

            if(\ReactWP\Runtime\RestAccess::is_allowed($requested_route, $allowed_routes)){
                return null;
            }

            if(current_user_can('manage_options')){
                rwp_require_headless_api_runtime();

                if(\ReactWP\Runtime\HeadlessApi::is_same_origin_request()){
                    return $result;
                }

                $cross_origin_routes = apply_filters(
                    'rwp_authenticated_cross_origin_rest_routes',
                    []
                );

                if(\ReactWP\Runtime\RestAccess::is_allowed($requested_route, $cross_origin_routes)){
                    return $result;
                }

                return new WP_Error(
                    'reactwp_cross_origin_rest_denied',
                    __('This REST route is not available to an authenticated cross-origin frontend.', 'reactwp'),
                    ['status' => 403]
                );
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

    static function client_cache_version(){

        return \ReactWP\Runtime\ClientCache::version();

    }

    static function bust_client_cache(){

        return \ReactWP\Runtime\ClientCache::bust();

    }

    static function invalidate_render_cache($tags = 'render:all'){

        \ReactWP\Runtime\RenderCache::invalidate($tags);

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
