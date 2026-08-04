<?php

$GLOBALS['rwp_test_theme_directory'] = '';
$GLOBALS['rwp_test_upload_directory'] = '';
$GLOBALS['rwp_test_options'] = ['rwp_client_cache_version' => 'test'];

function get_stylesheet_directory() {
    return $GLOBALS['rwp_test_theme_directory'];
}

function wp_upload_dir() {
    return [
        'basedir' => $GLOBALS['rwp_test_upload_directory'],
        'error' => false,
    ];
}

function trailingslashit($path) {
    return rtrim((string)$path, '/\\') . '/';
}

function get_option($name, $default = false) {
    return $GLOBALS['rwp_test_options'][$name] ?? $default;
}

function wp_json_encode($value, $flags = 0) {
    return json_encode($value, $flags);
}

function wp_generate_password() {
    return bin2hex(random_bytes(8));
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/ClientCache.php';
require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/StaticRegenerator.php';

use ReactWP\Runtime\StaticRegenerator;

$root = sys_get_temp_dir() . '/reactwp-static-test-' . bin2hex(random_bytes(6));
$theme = $root . '/theme';
$uploads = $root . '/uploads';
$GLOBALS['rwp_test_theme_directory'] = $theme;
$GLOBALS['rwp_test_upload_directory'] = $uploads;

$remove_tree = static function($path) use (&$remove_tree){
    if(!is_dir($path)){
        @unlink($path);
        return;
    }

    foreach(array_diff(scandir($path), ['.', '..']) as $name){
        $remove_tree($path . '/' . $name);
    }

    @rmdir($path);
};

$assert = static function($condition, $message) use ($remove_tree, $root){
    if($condition){
        return;
    }

    $remove_tree($root);
    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

foreach([
    $theme . '/assets/render/static',
    $uploads . '/reactwp/render/static',
] as $directory){
    mkdir($directory . '/fragments', 0777, true);
    file_put_contents($directory . '/fragments/home.html', '<h1>Stale</h1>');
    file_put_contents($directory . '/manifest.json', json_encode([
        'cacheVersion' => 'old',
        'entries' => [
            'en:/' => [
                'file' => 'fragments/home.html',
                'path' => '/',
                'tags' => ['post:1'],
            ],
        ],
    ]));
}

$method = new ReflectionMethod(StaticRegenerator::class, 'remove');
$method->setAccessible(true);
$assert($method->invoke(null, ['key' => 'en:/']) === true, 'Static removal should complete successfully.');

foreach([
    $theme . '/assets/render/static',
    $uploads . '/reactwp/render/static',
] as $directory){
    $manifest = json_decode(file_get_contents($directory . '/manifest.json'), true);
    $assert(!isset($manifest['entries']['en:/']), 'A route that became private or non-static must be removed from every manifest.');
    $assert(!file_exists($directory . '/fragments/home.html'), 'The stale static fragment must be deleted from disk.');
}

$remove_tree($root);
fwrite(STDOUT, "Static regenerator tests passed.\n");
