<?php

namespace ReactWP\Runtime;

class RestAccess {

    private const MAX_ROUTE_BYTES = 2048;
    private const MAX_VIEW_BYTES = 4096;

    public static function requested_route($request_uri, $rewrite_route = null, $query_route = null, $rest_prefix = 'wp-json') {

        $rewrite_route = self::normalize_route($rewrite_route);

        if($rewrite_route !== ''){
            return $rewrite_route;
        }

        $path_route = self::route_from_path($request_uri, $rest_prefix);

        if($path_route !== ''){
            return $path_route;
        }

        return self::normalize_route($query_route);

    }

    public static function is_allowed($requested_route, $allowed_routes) {

        $requested_route = self::normalize_route($requested_route);

        if($requested_route === '' || !is_array($allowed_routes)){
            return false;
        }

        foreach($allowed_routes as $allowed_route){
            if(self::normalize_route($allowed_route) === $requested_route){
                return true;
            }
        }

        return false;

    }

    public static function is_namespace($requested_route, $namespace) {

        $requested_route = self::normalize_route($requested_route);
        $namespace = self::normalize_route($namespace);

        if($requested_route === '' || $namespace === ''){
            return false;
        }

        return $requested_route === $namespace
            || strpos($requested_route, $namespace . '/') === 0;

    }

    public static function is_safe_view($view) {

        if(!is_string($view)){
            return false;
        }

        $view = trim($view);

        if(
            $view === ''
            || strlen($view) > self::MAX_VIEW_BYTES
            || $view[0] !== '/'
            || strpos($view, '//') === 0
            || strpos($view, '\\') !== false
        ){
            return false;
        }

        $decoded = rawurldecode($view);

        if(
            $decoded === ''
            || $decoded[0] !== '/'
            || strpos($decoded, '//') === 0
            || strpos($decoded, '\\') !== false
            || preg_match('/[\x00-\x1F\x7F]/', $decoded)
            || preg_match('/%(?![A-Fa-f0-9]{2})/', $view)
        ){
            return false;
        }

        $parts = parse_url($decoded);

        return is_array($parts)
            && empty($parts['scheme'])
            && empty($parts['host'])
            && empty($parts['user'])
            && empty($parts['pass'])
            && empty($parts['fragment']);

    }

    public static function normalize_route($route) {

        if(!is_string($route)){
            return '';
        }

        $route = trim($route);

        if(strlen($route) > self::MAX_ROUTE_BYTES){
            return '';
        }

        $route = rawurldecode($route);

        if($route === '' || preg_match('/[\x00-\x1F\x7F]/', $route)){
            return '';
        }

        $route = explode('?', $route, 2)[0];
        $route = explode('#', $route, 2)[0];
        $route = preg_replace('#/+#', '/', '/' . ltrim($route, '/'));

        if(!is_string($route) || $route === ''){
            return '';
        }

        return $route === '/' ? '/' : rtrim($route, '/');

    }

    private static function route_from_path($request_uri, $rest_prefix) {

        if(!is_string($request_uri) || $request_uri === ''){
            return '';
        }

        $path = parse_url($request_uri, PHP_URL_PATH);
        $rest_prefix = trim((string)$rest_prefix, '/');

        if(!is_string($path) || $path === '' || $rest_prefix === ''){
            return '';
        }

        $path = rawurldecode($path);
        $pattern = '#(?:^|/)' . preg_quote($rest_prefix, '#') . '(?<route>/.*)?$#';

        if(!preg_match($pattern, $path, $matches)){
            return '';
        }

        return self::normalize_route($matches['route'] ?? '/');

    }

}
