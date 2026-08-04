<?php

$GLOBALS['rwp_ssr_allowed_query_keys'] = [];

function apply_filters($hook, $value, ...$args) {
    if($hook === 'rwp_ssr_allow_remote_endpoint'){
        return true;
    }

    if($hook === 'rwp_ssr_cache_query_keys'){
        return $GLOBALS['rwp_ssr_allowed_query_keys'];
    }

    return $value;
}

function wp_parse_url($url, $component = -1) {
    return parse_url($url, $component);
}

function esc_url_raw($url) {
    return (string)$url;
}

function sanitize_key($value) {
    return strtolower(preg_replace('/[^a-z0-9_-]/i', '', (string)$value));
}

function wp_parse_str($value, &$result) {
    parse_str($value, $result);
}

function get_locale() {
    return 'en_US';
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RouteResolver.php';
require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RenderStrategy.php';
require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/ServerRenderer.php';

use ReactWP\Runtime\ServerRenderer;

$assert_same = static function($expected, $actual, $message){
    if($expected === $actual){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
    exit(1);
};

$endpoint_method = new ReflectionMethod(ServerRenderer::class, 'endpoint_allowed');
$endpoint_method->setAccessible(true);

putenv('RWP_SSR_SECRET=short');
$assert_same(false, $endpoint_method->invoke(null, 'https://renderer.example.com/render'), 'Remote SSR must reject weak secrets.');
$assert_same(false, $endpoint_method->invoke(null, 'http://127.0.0.1:3100/render'), 'Loopback SSR must reject weak secrets by default.');

putenv('RWP_SSR_SECRET=0123456789abcdef0123456789abcdef');
$assert_same(true, $endpoint_method->invoke(null, 'http://127.0.0.1:3100/render'), 'Authenticated loopback SSR must remain available.');
$assert_same(false, $endpoint_method->invoke(null, 'http://renderer.example.com/render'), 'Remote SSR must reject plaintext HTTP.');
$assert_same(false, $endpoint_method->invoke(null, 'https://user:password@renderer.example.com/render'), 'SSR endpoints must reject embedded credentials.');
$assert_same(false, $endpoint_method->invoke(null, 'https://renderer.example.com/render?debug=1'), 'SSR endpoints must reject query parameters.');
$assert_same(true, $endpoint_method->invoke(null, 'https://renderer.example.com/render'), 'Explicitly allowed HTTPS SSR with a strong secret should work.');

$cache_key_method = new ReflectionMethod(ServerRenderer::class, 'cache_route_key');
$cache_key_method->setAccessible(true);
$route = [
    'lang' => 'en',
    'path' => '/projects/',
    'search' => '?page=2',
];

$assert_same(null, $cache_key_method->invoke(null, $route, []), 'Unapproved query parameters must disable SSR HTML caching.');

$GLOBALS['rwp_ssr_allowed_query_keys'] = ['page'];
$first_key = $cache_key_method->invoke(null, $route, []);
$second_key = $cache_key_method->invoke(null, [
    ...$route,
    'search' => '?page=2',
], []);
$assert_same($first_key, $second_key, 'Approved query parameters must produce deterministic cache keys.');

putenv('RWP_SSR_SECRET');
fwrite(STDOUT, "Server renderer security tests passed.\n");
