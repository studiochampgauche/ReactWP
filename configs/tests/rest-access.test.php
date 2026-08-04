<?php

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RestAccess.php';

use ReactWP\Runtime\RestAccess;

$assert_same = static function($expected, $actual, $message){
    if($expected === $actual){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
    exit(1);
};

$assert_same(
    '/wp/v2/users',
    RestAccess::requested_route('/wp-json/wp/v2/users?probe=/reactwp/v1/route'),
    'An allowed route in an unrelated query parameter must not replace the requested route.'
);

$assert_same(
    '/reactwp/v1/route',
    RestAccess::requested_route('/index.php?rest_route=%2Freactwp%2Fv1%2Froute', null, '/reactwp/v1/route'),
    'The rest_route query parameter must remain supported.'
);

$assert_same(
    '/wp/v2/posts',
    RestAccess::requested_route(
        '/wp-json/wp/v2/posts?probe=/reactwp/v1/route',
        '/wp/v2/posts',
        null
    ),
    'The route resolved by WordPress must take precedence over unrelated URL content.'
);

$assert_same(
    true,
    RestAccess::is_allowed('/reactwp/v1/route/', ['/reactwp/v1/route']),
    'Equivalent trailing slashes must be normalized.'
);

$assert_same(
    false,
    RestAccess::is_allowed('/wp/v2/users', ['/reactwp/v1/route']),
    'A different REST route must remain blocked.'
);

$assert_same(
    false,
    RestAccess::is_allowed('/reactwp/v1/route/another', ['/reactwp/v1/route']),
    'Allowlist entries must match exact routes.'
);

$assert_same(
    true,
    RestAccess::is_namespace('/reactwp/v1/route', '/reactwp/v1'),
    'ReactWP routes must be recognized within their exact namespace.'
);

$assert_same(
    false,
    RestAccess::is_namespace('/reactwp/v10/route', '/reactwp/v1'),
    'Lookalike namespaces must not match.'
);

$assert_same(
    true,
    RestAccess::is_safe_view('/projects/?page=2'),
    'Local route views with bounded queries must remain valid.'
);

foreach([
    'https://evil.example/path',
    '//evil.example/path',
    '%2F%2Fevil.example/path',
    '/path#fragment',
    '/path%ZZ',
    '/path%5Chidden',
    "/path\0hidden",
] as $unsafe_view){
    $assert_same(
        false,
        RestAccess::is_safe_view($unsafe_view),
        'External, fragmented, or control-bearing route views must be rejected.'
    );
}

fwrite(STDOUT, "REST access tests passed.\n");
