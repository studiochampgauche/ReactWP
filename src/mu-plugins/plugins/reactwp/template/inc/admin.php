<?php

function rwp_admin_locale_code() {

    return defined('CL') ? CL : substr(get_locale(), 0, 2);

}

function rwp_admin_option_checkbox_values($name) {

    $value = get_option('options_' . $name, []);

    if(is_array($value)){
        return array_values(array_filter($value));
    }

    if($value === null || $value === ''){
        return [];
    }

    return [(string)$value];

}

function rwp_admin_option_repeater_rows($name, $sub_fields = []) {

    $rows_count = (int)get_option('options_' . $name, 0);
    $rows = [];

    if($rows_count < 1){
        return [];
    }

    for($index = 0; $index < $rows_count; $index++){
        $row = [];

        foreach($sub_fields as $sub_field){
            $row[$sub_field] = get_option("options_{$name}_{$index}_{$sub_field}");
        }

        $rows[] = $row;
    }

    return $rows;

}

function rwp_admin_langs() {

    $langs = rwp_admin_option_repeater_rows('langs', [
        'name',
        'code',
    ]);

    return array_values(array_filter($langs, function($lang){
        return !empty($lang['code']);
    }));

}

function rwp_admin_theme_locations() {

    $langs = rwp_admin_langs();
    $sub_fields = ['slug'];

    foreach($langs as $lang){
        $sub_fields[] = 'name_' . $lang['code'];
    }

    $locations = rwp_admin_option_repeater_rows('theme_locations', $sub_fields);

    return array_values(array_filter($locations, function($location){
        return !empty($location['slug']);
    }));

}

function rwp_admin_post_type_choices() {

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

    return $post_types;

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

add_action('init', function(){

    if(
        !is_admin()
        || wp_doing_ajax()
        || (defined('REST_REQUEST') && REST_REQUEST)
        || !function_exists('acf_add_options_page')
        || !function_exists('acf_add_local_field_group')
    ){
        return;
    }

    rwp_admin_register_options_pages();
    rwp_admin_register_settings_field_groups();

}, 20);

add_action('init', function(){

    rwp_register_nav_menus_from_options();

}, 12);
