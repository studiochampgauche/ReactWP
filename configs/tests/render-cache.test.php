<?php

$GLOBALS['rwp_test_options'] = [];
$GLOBALS['rwp_test_lock_fail'] = false;

function add_action() {}
function do_action() {}

function get_option($name, $default = false) {
    return $GLOBALS['rwp_test_options'][$name] ?? $default;
}

function update_option($name, $value) {
    $GLOBALS['rwp_test_options'][$name] = $value;
    return true;
}

function add_option($name, $value) {
    if(!empty($GLOBALS['rwp_test_lock_fail']) || array_key_exists($name, $GLOBALS['rwp_test_options'])){
        return false;
    }

    $GLOBALS['rwp_test_options'][$name] = $value;
    return true;
}

function delete_option($name) {
    unset($GLOBALS['rwp_test_options'][$name]);
    return true;
}

function wp_generate_uuid4() {
    return uniqid('test-', true);
}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/runtime/RenderCache.php';

use ReactWP\Runtime\RenderCache;

$assert = static function($condition, $message){
    if($condition){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    exit(1);
};

$old_entry = [
    'generatedAtUnix' => microtime(true) - 60,
    'tags' => ['post:original'],
];

RenderCache::invalidate('post:original');
$assert(!RenderCache::is_fresh($old_entry), 'A directly invalidated entry must be stale.');

for($index = 0; $index < 510; $index++){
    RenderCache::invalidate('post:overflow-' . $index);
}

$state = $GLOBALS['rwp_test_options']['rwp_render_invalidations'];
$assert(count($state['tags']) === 500, 'The invalidation map must stay bounded.');
$assert(!isset($state['tags']['post:original']), 'The oldest invalidation should be pruned from the bounded map.');
$assert((float)$state['prunedBefore'] > 0, 'Pruning must retain a stale-before watermark.');
$assert(!RenderCache::is_fresh($old_entry), 'Pruning must never resurrect an older cached fragment.');

$new_entry = [
    'generatedAtUnix' => microtime(true) + 1,
    'tags' => ['post:original'],
];
$assert(RenderCache::is_fresh($new_entry), 'An entry generated after all invalidations should remain fresh.');

RenderCache::invalidate('render:all');
$assert(!RenderCache::is_fresh($old_entry), 'A global invalidation must invalidate every older entry.');

$GLOBALS['rwp_test_lock_fail'] = true;
$before_failsafe = microtime(true);
RenderCache::invalidate('post:lock-contention');
$assert(
    (float)($GLOBALS['rwp_test_options']['rwp_render_invalidation_failsafe'] ?? 0) > $before_failsafe,
    'Lock contention must establish a fail-safe invalidation watermark.'
);
$assert(!RenderCache::is_fresh($new_entry), 'The fail-safe watermark must prevent stale cache reuse.');

fwrite(STDOUT, "Render cache tests passed.\n");
