<?php

namespace ReactWP\Runtime;

class Bootstrap {

    public static function system() {

        return apply_filters('rwp_system', [
            'public' => get_option('blog_public'),
            'baseUrl' => site_url('/'),
            'homeUrl' => home_url('/'),
            'adminUrl' => admin_url(),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'restUrl' => rest_url(),
            'restNonce' => wp_create_nonce('wp_rest'),
            'themeUrl' => get_stylesheet_directory_uri(),
            'themeDirectory' => get_stylesheet_directory(),
            'routeEndpoint' => rest_url('reactwp/v1/route')
        ]);

    }

    public static function payload() {

        $theme = wp_get_theme();
        $route = RouteResolver::current();
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
            'seoDefaults' => [
                'title' => get_bloginfo('name'),
                'description' => get_bloginfo('description'),
                'ogImage' => null
            ]
        ];

        return apply_filters('rwp_bootstrap', $payload, $route);

    }

    public static function json() {

        return wp_json_encode(
            self::payload(),
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );

    }

}
