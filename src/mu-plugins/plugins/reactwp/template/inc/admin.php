<?php

function rwp_admin_locale_code() {

    static $locale = null;

    if($locale !== null){
        return $locale;
    }

    $locale = defined('CL') ? CL : substr(get_locale(), 0, 2);

    return $locale;

}

function rwp_admin_option_checkbox_values($name) {

    static $cache = [];

    $cache_key = (string)$name;

    if(array_key_exists($cache_key, $cache)){
        return $cache[$cache_key];
    }

    $value = get_option('options_' . $name, []);

    if(is_array($value)){
        $cache[$cache_key] = array_values(array_filter($value));
        return $cache[$cache_key];
    }

    if($value === null || $value === ''){
        $cache[$cache_key] = [];
        return $cache[$cache_key];
    }

    $cache[$cache_key] = [(string)$value];

    return $cache[$cache_key];

}

function rwp_admin_option_repeater_rows($name, $sub_fields = []) {

    static $cache = [];

    $sub_fields = array_values(array_map('strval', (array)$sub_fields));
    $cache_key = (string)$name . '|' . implode('|', $sub_fields);

    if(array_key_exists($cache_key, $cache)){
        return $cache[$cache_key];
    }

    $rows_count = (int)get_option('options_' . $name, 0);
    $rows = [];

    if($rows_count < 1){
        $cache[$cache_key] = [];
        return $cache[$cache_key];
    }

    for($index = 0; $index < $rows_count; $index++){
        $row = [];

        foreach($sub_fields as $sub_field){
            $row[$sub_field] = get_option("options_{$name}_{$index}_{$sub_field}");
        }

        $rows[] = $row;
    }

    $cache[$cache_key] = $rows;

    return $cache[$cache_key];

}

function rwp_admin_langs() {

    static $langs = null;

    if($langs !== null){
        return $langs;
    }

    $langs = rwp_admin_option_repeater_rows('langs', [
        'name',
        'code',
    ]);

    $langs = array_values(array_filter($langs, function($lang){
        return !empty($lang['code']);
    }));

    return $langs;

}

function rwp_admin_theme_locations() {

    static $locations = null;

    if($locations !== null){
        return $locations;
    }

    $langs = rwp_admin_langs();
    $sub_fields = ['slug'];

    foreach($langs as $lang){
        $sub_fields[] = 'name_' . $lang['code'];
    }

    $locations = rwp_admin_option_repeater_rows('theme_locations', $sub_fields);

    $locations = array_values(array_filter($locations, function($location){
        return !empty($location['slug']);
    }));

    return $locations;

}

function rwp_admin_post_type_choices() {

    static $choices = null;

    if($choices !== null){
        return $choices;
    }

    $post_types = get_post_types();
    $excluded = [
        'post',
        'page',
        'attachment',
        'revision',
        'nav_menu_item',
        'custom_css',
        'customize_changeset',
        'oembed_cache',
        'user_request',
        'wp_block',
        'wp_template',
        'wp_template_part',
        'wp_global_styles',
        'wp_navigation',
        'acf-field',
        'acf-ui-options-page',
        'acf-field-group',
        'acf-post-type',
        'acf-taxonomy',
        'wp_font_family',
        'wp_font_face'
    ];

    foreach($excluded as $post_type){
        unset($post_types[$post_type]);
    }

    $choices = $post_types;

    return $choices;

}

function rwp_admin_request_value($key) {

    if(!isset($_REQUEST[$key]) || !is_scalar($_REQUEST[$key])){
        return '';
    }

    return sanitize_text_field(wp_unslash((string)$_REQUEST[$key]));

}

function rwp_admin_is_site_settings_context() {

    if(!is_admin()){
        return false;
    }

    $page = sanitize_key(rwp_admin_request_value('page'));

    if($page === 'site-settings'){
        return true;
    }

    if(!wp_doing_ajax()){
        return false;
    }

    $screen = rwp_admin_request_value('screen');
    $post_id = rwp_admin_request_value('post_id');

    return strpos($screen, 'site-settings') !== false || $post_id === 'options';

}

function rwp_admin_register_options_pages() {

    acf_add_options_page([
        'page_title' => 'Site settings',
        'menu_slug' => 'site-settings',
        'position' => '',
        'redirect' => false,
    ]);

    acf_add_options_page([
        'page_title' => 'Theme settings',
        'menu_slug' => 'theme-settings',
        'position' => '',
        'redirect' => false,
    ]);

}

function rwp_admin_register_settings_field_groups() {

    $locale = rwp_admin_locale_code();
    $langs = rwp_admin_langs();
    $theme_location_name_fields = [];
    $post_type_choices = rwp_admin_post_type_choices();

    foreach($langs as $lang){
        $code = $lang['code'];
        $name = $lang['name'] ?: strtoupper($code);

        $theme_location_name_fields[] = [
            'key' => 'field_rwp_theme_location_tab_' . $code,
            'label' => $name,
            'name' => '',
            'aria-label' => '',
            'type' => 'tab',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'placement' => 'top',
            'endpoint' => 0,
            'selected' => 0
        ];

        $theme_location_name_fields[] = [
            'key' => 'field_rwp_theme_location_name_' . $code,
            'label' => ($locale === 'fr' ? 'Nom (' . $code . ')' : 'Name (' . $code . ')'),
            'name' => 'name_' . $code,
            'aria-label' => '',
            'type' => 'text',
            'instructions' => '',
            'required' => 0,
            'conditional_logic' => 0,
            'wrapper' => [
                'width' => '',
                'class' => '',
                'id' => '',
            ],
            'default_value' => '',
            'maxlength' => '',
            'allow_in_bindings' => 0,
            'placeholder' => '',
            'prepend' => '',
            'append' => '',
            'parent_repeater' => 'field_678bca18008f5',
        ];
    }

    acf_add_local_field_group([
        'key' => 'group_67a8554341c42',
        'title' => 'Langues',
        'fields' => [
            [
                'key' => 'field_67a8554331600',
                'label' => 'Langs',
                'name' => 'langs',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 5,
                'collapsed' => '',
                'button_label' => 'Add lang',
                'rows_per_page' => 20,
                'sub_fields' => [
                    [
                        'key' => 'field_67a8556a31601',
                        'label' => 'Name',
                        'name' => 'name',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_67a8554331600',
                    ],
                    [
                        'key' => 'field_67a8558631603',
                        'label' => 'Code',
                        'name' => 'code',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_67a8554331600',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 10,
        'position' => 'acf_after_title',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ]);

    acf_add_local_field_group([
        'key' => 'group_678bca1894511',
        'title' => 'Theme Locations',
        'fields' => [
            [
                'key' => 'field_678bca18008f5',
                'label' => 'Theme Locations',
                'name' => 'theme_locations',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'layout' => 'block',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => 'field_678bca4e008f7',
                'button_label' => 'Ajouter un emplacement',
                'rows_per_page' => 20,
                'sub_fields' => [
                    [
                        'key' => 'field_678bca4e008f7',
                        'label' => 'Slug',
                        'name' => 'slug',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '33.3333333333',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_678bca18008f5',
                    ],
                    ...$theme_location_name_fields
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 11,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ]);

    acf_add_local_field_group([
        'key' => 'acf-group_60983478kjhsad54323',
        'title' => 'Media Groups Global Settings',
        'fields' => [
            [
                'key' => 'field_60983478kjhsad54324',
                'label' => 'Media Groups Post Types',
                'name' => 'mediaGroups_post_types',
                'aria-label' => '',
                'type' => 'checkbox',
                'instructions' => ($locale === 'fr'
                    ? 'Les articles, les pages, les utilisateurs et les taxonomies ont le module. Si vous ne voyez pas de sélection, créez un nouveau type d\'article.'
                    : 'Posts, pages, users and taxonomies have the module. If you don\'t see a selection, create a new post type.'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '33.3333333333',
                    'class' => '',
                    'id' => '',
                ],
                'choices' => $post_type_choices,
                'default_value' => [],
                'return_format' => 'value',
                'allow_custom' => 0,
                'allow_in_bindings' => 0,
                'layout' => 'vertical',
                'toggle' => 0,
                'save_custom' => 0,
                'custom_choice_button_text' => 'Add new choice',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 12,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ]);

    acf_add_local_field_group([
        'key' => 'acf-group_609asdas7d6uj12gh854323',
        'title' => 'React Template Global Settings',
        'fields' => [
            [
                'key' => 'field_609asdas7d6uj12gh854324',
                'label' => 'React Template Post Types',
                'name' => 'react_template_post_types',
                'aria-label' => '',
                'type' => 'checkbox',
                'instructions' => ($locale === 'fr'
                    ? 'Les articles, les pages, les utilisateurs et les taxonomies ont le module. Si vous ne voyez pas de sélection, créez un nouveau type d\'article.'
                    : 'Posts, pages, users and taxonomies have the module. If you don\'t see a selection, create a new post type.'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '33.3333333333',
                    'class' => '',
                    'id' => '',
                ],
                'choices' => $post_type_choices,
                'default_value' => [],
                'return_format' => 'value',
                'allow_custom' => 0,
                'allow_in_bindings' => 0,
                'layout' => 'vertical',
                'toggle' => 0,
                'save_custom' => 0,
                'custom_choice_button_text' => 'Add new choice',
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 13,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ]);

    acf_add_local_field_group([
        'key' => 'group_rwp_headless_api_settings',
        'title' => 'Headless API',
        'fields' => [
            [
                'key' => 'field_rwp_headless_allowed_origins',
                'label' => 'Allowed Headless Origins',
                'name' => 'headless_allowed_origins',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => ($locale === 'fr'
                    ? 'Ajoutez uniquement les origines frontend autorisees a utiliser les requetes authentifiees. Exemple: https://app.example.com'
                    : 'Only add frontend origins that may use authenticated requests. Example: https://app.example.com'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => [
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ],
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 20,
                'collapsed' => '',
                'button_label' => 'Add origin',
                'rows_per_page' => 20,
                'sub_fields' => [
                    [
                        'key' => 'field_rwp_headless_allowed_origin',
                        'label' => 'Origin',
                        'name' => 'origin',
                        'aria-label' => '',
                        'type' => 'url',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => [
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ],
                        'default_value' => '',
                        'placeholder' => 'https://app.example.com',
                        'parent_repeater' => 'field_rwp_headless_allowed_origins',
                    ],
                ],
            ],
        ],
        'location' => [
            [
                [
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ],
            ],
        ],
        'menu_order' => 14,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ]);

}

function rwp_register_nav_menus_from_options() {

    if(!function_exists('register_nav_menus')){
        return;
    }

    $locale = rwp_admin_locale_code();
    $locations = rwp_admin_theme_locations();
    $menu_locations = [];

    foreach($locations as $location){
        if(empty($location['slug'])){
            continue;
        }

        $label = $location['name_' . $locale] ?? $location['slug'];
        $menu_locations[$location['slug']] = $label;
    }

    if($menu_locations){
        register_nav_menus($menu_locations);
    }

}

function rwp_should_register_nav_menus_from_options() {

    if(!is_admin()){
        return true;
    }

    if((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()){
        return true;
    }

    $script = isset($_SERVER['PHP_SELF'])
        ? strtolower(basename((string)$_SERVER['PHP_SELF']))
        : '';

    return in_array($script, [
        'nav-menus.php',
        'customize.php',
    ], true);

}

add_action('acf/init', function(){

    if(
        !is_admin()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || !function_exists('acf_add_options_page')
        || !function_exists('acf_add_local_field_group')
    ){
        return;
    }

    rwp_admin_register_options_pages();

    if(rwp_admin_is_site_settings_context()){
        rwp_admin_register_settings_field_groups();
    }

}, 20);

add_action('init', function(){

    if(rwp_should_register_nav_menus_from_options()){
        rwp_register_nav_menus_from_options();
    }

}, 12);
