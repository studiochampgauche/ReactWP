<?php

define('ABSPATH', __DIR__);
define('CL', 'en');

function add_action() {}
function add_filter() {}

function get_bloginfo($field) {
    return $field === 'name' ? 'Example' : '';
}

function get_locale() {
    return 'en_CA';
}

require_once __DIR__ . '/../../src/plugins/reactwp-seo/template/init.php';

use ReactWP\Seo\Seo;

$title = Seo::title([
    'route' => [
        'lang' => 'fr',
        'pageName' => 'À propos',
        'seo' => [
            'title_en' => 'About',
            'title_fr' => 'À propos de nous',
        ],
    ],
]);

if($title !== 'À propos de nous'){
    fwrite(STDERR, "Route SEO must use the canonical route.lang value.\n");
    exit(1);
}

$legacy_title = Seo::title([
    'route' => [
        'language' => 'fr',
        'pageName' => 'À propos',
        'seo' => [
            'title_fr' => 'Titre hérité',
        ],
    ],
]);

if($legacy_title !== 'Titre hérité'){
    fwrite(STDERR, "Legacy route.language SEO contexts must remain compatible.\n");
    exit(1);
}

fwrite(STDOUT, "SEO route language tests passed.\n");
