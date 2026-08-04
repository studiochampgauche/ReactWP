<?php

class WP_REST_Request {}

function wp_parse_url($url, $component = -1) {
    return parse_url($url, $component);
}

function home_url($path = '/') {
    return 'https://example.test' . $path;
}

function site_url($path = '/') {
    return 'https://example.test' . $path;
}

function get_http_origin() {
    return $GLOBALS['rwp_test_origin'] ?? '';
}

function apply_filters($hook, $value) {
    return $value;
}

function get_option($name, $default = false) {
    return $default;
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RestAccess.php';
require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/HeadlessApi.php';

use ReactWP\Runtime\HeadlessApi;

$assert_same = static function($expected, $actual, $message){
    if($expected === $actual){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
    exit(1);
};

$normalize = new ReflectionMethod(HeadlessApi::class, 'normalize_origin');
$normalize->setAccessible(true);

$assert_same(
    'https://frontend.example:8443',
    $normalize->invoke(null, 'https://frontend.example:8443/path'),
    'Configured frontend URLs must normalize to an exact origin.'
);
$assert_same('', $normalize->invoke(null, '*'), 'Wildcard credentialed origins must be rejected.');
$assert_same('', $normalize->invoke(null, 'https://user:pass@frontend.example'), 'Origins containing credentials must be rejected.');
$assert_same('', $normalize->invoke(null, 'javascript:alert(1)'), 'Non-HTTP origins must be rejected.');

$GLOBALS['rwp_test_origin'] = '';
unset($_SERVER['HTTP_SEC_FETCH_SITE']);
$assert_same(true, HeadlessApi::is_same_origin_request(), 'Origin-less server and legacy same-origin requests must remain supported.');

$_SERVER['HTTP_SEC_FETCH_SITE'] = 'cross-site';
$assert_same(false, HeadlessApi::is_same_origin_request(), 'A browser-reported cross-site request must not bypass the origin check by omitting Origin.');

$GLOBALS['rwp_test_origin'] = 'https://example.test';
$assert_same(true, HeadlessApi::is_same_origin_request(), 'The WordPress origin must remain accepted.');

$GLOBALS['rwp_test_origin'] = 'https://frontend.example';
$assert_same(false, HeadlessApi::is_same_origin_request(), 'An external headless origin must not receive same-origin WordPress REST privileges.');

fwrite(STDOUT, "Headless API security tests passed.\n");
