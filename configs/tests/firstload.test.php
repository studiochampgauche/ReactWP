<?php

define('ABSPATH', __DIR__);

$rwp_test_options = [
    'rwp_firstload' => 1,
    'options_langs' => 0,
    'options_theme_locations' => 1,
    'options_theme_locations_0_name_fr' => 'Navigation personnalisee',
    'options_theme_locations_0_name_en' => 'Custom navigation',
    'options_theme_locations_0_slug' => 'custom-primary',
];

function get_option($name, $default = false) {

    global $rwp_test_options;

    return array_key_exists($name, $rwp_test_options)
        ? $rwp_test_options[$name]
        : $default;

}

function update_option($name, $value) {

    global $rwp_test_options;

    $rwp_test_options[$name] = $value;

    return true;

}

function apply_filters($hook, $value) {

    return $value;

}

function add_action($hook, $callback, $priority = 10) {

    return true;

}

require_once __DIR__ . '/../../src/mu-plugins/plugins/reactwp/template/inc/firstload.php';

$assert_same = static function($expected, $actual, $message){
    if($expected === $actual){
        return;
    }

    fwrite(STDERR, $message . PHP_EOL);
    fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
    fwrite(STDERR, 'Actual: ' . var_export($actual, true) . PHP_EOL);
    exit(1);
};

$langs = [
    [
        'name' => 'Francais',
        'code' => 'fr',
    ],
    [
        'name' => 'English',
        'code' => 'en',
    ],
];
$lang_fields = [
    'name' => 'field_67a8556a31601',
    'code' => 'field_67a8558631603',
];
$theme_locations = [
    [
        'name_fr' => 'Navigation principale',
        'name_en' => 'Primary navigation',
        'slug' => 'primary',
    ],
];
$theme_location_fields = [
    'name_fr' => 'field_rwp_theme_location_name_fr',
    'name_en' => 'field_rwp_theme_location_name_en',
    'slug' => 'field_678bca4e008f7',
];

$assert_same(1, rwp_firstload_target_version(), 'ReactWP must retain first-load schema version 1 by default.');
$assert_same(true, rwp_firstload_needs_run(1), 'An empty or unreferenced repeater must make first-load repairable.');

$assert_same(
    true,
    rwp_seed_option_repeater_rows('field_67a8554331600', 'langs', $lang_fields, $langs),
    'The language repeater must be seeded when its existing option is zero.'
);
$assert_same(2, get_option('options_langs'), 'Both starter languages must be stored.');
$assert_same('field_67a8554331600', get_option('_options_langs'), 'The language repeater needs its ACF field reference.');
$assert_same('field_67a8556a31601', get_option('_options_langs_0_name'), 'Language subfields need ACF field references.');
$assert_same('field_67a8558631603', get_option('_options_langs_1_code'), 'Every seeded language row needs ACF field references.');

$assert_same(
    true,
    rwp_seed_option_repeater_rows(
        'field_678bca18008f5',
        'theme_locations',
        $theme_location_fields,
        $theme_locations
    ),
    'An existing theme location must have its missing ACF references repaired.'
);
$assert_same(
    'custom-primary',
    get_option('options_theme_locations_0_slug'),
    'A populated theme location must not be replaced by the starter value.'
);
$assert_same(
    'field_rwp_theme_location_name_fr',
    get_option('_options_theme_locations_0_name_fr'),
    'Existing theme location subfields need their ACF references.'
);
$assert_same(false, rwp_firstload_needs_run(1), 'A completed and fully referenced scaffold must not run again.');

rwp_seed_option_repeater_rows('field_67a8554331600', 'langs', $lang_fields, [
    [
        'name' => 'Replacement',
        'code' => 'xx',
    ],
]);

$assert_same('Francais', get_option('options_langs_0_name'), 'A subsequent repair must preserve populated rows.');
$assert_same(2, get_option('options_langs'), 'A subsequent repair must preserve the existing row count.');

fwrite(STDOUT, "First-load scaffold tests passed.\n");
