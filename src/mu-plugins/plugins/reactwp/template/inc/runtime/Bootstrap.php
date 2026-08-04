<?php

namespace ReactWP\Runtime;

class Bootstrap {

    public static function system() {

        return apply_filters('rwp_system', [
            'public' => (int)get_option('blog_public'),
            'baseUrl' => site_url('/'),
            'homeUrl' => home_url('/'),
            'adminUrl' => admin_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(),
            'restNonce' => is_user_logged_in() ? wp_create_nonce('wp_rest') : '',
            'cacheVersion' => ClientCache::version(),
            'themeUrl' => get_stylesheet_directory_uri(),
            'routeEndpoint' => rest_url('reactwp/v1/route'),
            'headless' => [
                'bootstrapEndpoint' => rest_url('reactwp/v1/bootstrap'),
                'routeEndpoint' => rest_url('reactwp/v1/route'),
                'navigationEndpoint' => rest_url('reactwp/v1/navigation'),
                'settingsEndpoint' => rest_url('reactwp/v1/settings'),
                'sitemapEndpoint' => rest_url('reactwp/v1/sitemap'),
                'previewEndpoint' => rest_url('reactwp/v1/preview'),
                'currentUserEndpoint' => rest_url('reactwp/v1/auth/me'),
                'loginEndpoint' => rest_url('reactwp/v1/auth/login'),
                'logoutEndpoint' => rest_url('reactwp/v1/auth/logout')
            ]
        ]);

    }

    public static function payload($resolved_route = null) {

        $theme = wp_get_theme();
        $route = is_array($resolved_route) ? $resolved_route : RouteResolver::current();

        if(!is_user_logged_in()){
            foreach(['data', 'seo'] as $key){
                $route[$key] = PublicPayload::sanitize_value($route[$key] ?? []);
            }
        }

        $payload = [
            'site' => [
                'name' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'language' => defined('CL') ? CL : substr(get_locale(), 0, 2),
                'locale' => get_locale(),
                'homeUrl' => home_url('/'),
                'adminUrl' => admin_url()
            ],
            'theme' => [
                'name' => $theme->get('Name'),
                'slug' => $theme->get_stylesheet(),
                'version' => $theme->get('Version')
            ],
            'system' => self::system(),
            'assets' => [
                'criticalFonts' => apply_filters('rwp_critical_fonts', []),
                'criticalMedias' => apply_filters('rwp_critical_medias', []),
                'noCriticalMedias' => apply_filters('rwp_no_critical_medias', [])
            ],
            'navigation' => MenuBuilder::all(),
            'route' => $route,
            'currentUser' => self::current_user(),
            'seoDefaults' => [
                'title' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'ogImage' => null
            ]
        ];

        return apply_filters('rwp_bootstrap', $payload, $route);

    }

    private static function current_user() {

        if(!is_user_logged_in()){
            return [
                'authenticated' => false,
            ];
        }

        $user = wp_get_current_user();

        if(!$user || !$user->exists()){
            return [
                'authenticated' => false,
            ];
        }

        return apply_filters('rwp_current_user_payload', [
            'authenticated' => true,
            'id' => (int)$user->ID,
            'slug' => $user->user_nicename,
            'displayName' => $user->display_name,
            'email' => $user->user_email,
            'roles' => array_values((array)$user->roles),
        ], $user);

    }

    public static function json($payload = null) {

        $json = wp_json_encode(
            is_array($payload) ? $payload : self::payload(),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_INVALID_UTF8_SUBSTITUTE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

        return is_string($json) ? $json : '{}';

    }

}
