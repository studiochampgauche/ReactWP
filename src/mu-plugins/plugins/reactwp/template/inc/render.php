<?php

/*
* Shot events on init action
*/
add_action('init', function(){


    /*
    * Add site settings page
    */
    acf_add_options_page(array(
        'page_title' => 'Site settings',
        'menu_slug' => 'site-settings',
        'position' => '',
        'redirect' => false,
    ));


    /*
    * Add theme settings page
    */
    acf_add_options_page(array(
        'page_title' => 'Theme settings',
        'menu_slug' => 'theme-settings',
        'position' => '',
        'redirect' => false,
    ));


    /*
    * Add languages to site-settings
    */
    acf_add_local_field_group( array(
        'key' => 'group_67a8554341c42',
        'title' => 'Langues',
        'fields' => array(
            array(
                'key' => 'field_67a8554331600',
                'label' => 'Langs',
                'name' => 'langs',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 5,
                'collapsed' => '',
                'button_label' => 'Add lang',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    array(
                        'key' => 'field_67a8556a31601',
                        'label' => 'Name',
                        'name' => 'name',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_67a8554331600',
                    ),
                    array(
                        'key' => 'field_67a8558631603',
                        'label' => 'Code',
                        'name' => 'code',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_67a8554331600',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ),
            ),
        ),
        'menu_order' => 10,
        'position' => 'acf_after_title',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );
    $langs = rwp::field('langs');


    /*
    * Add theme locations to site-settings
    */
    acf_add_local_field_group( array(
        'key' => 'group_678bca1894511',
        'title' => 'Theme Locations',
        'fields' => array(
            array(
                'key' => 'field_678bca18008f5',
                'label' => 'Theme Locations',
                'name' => 'theme_locations',
                'aria-label' => '',
                'type' => 'repeater',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'layout' => 'table',
                'pagination' => 0,
                'min' => 0,
                'max' => 0,
                'collapsed' => '',
                'button_label' => 'Ajouter un emplacement',
                'rows_per_page' => 20,
                'sub_fields' => array(
                    ...($langs ? array_map(function($item){

                        return [
                            'key' => 'field_678bca390083240hgvbnvh32' . $item['code'],
                            'label' => 'Name (' . $item['code'] . ')',
                            'name' => 'name_' . $item['code'],
                            'aria-label' => '',
                            'type' => 'text',
                            'instructions' => '',
                            'required' => 0,
                            'conditional_logic' => 0,
                            'wrapper' => array(
                                'width' => '',
                                'class' => '',
                                'id' => '',
                            ),
                            'default_value' => '',
                            'maxlength' => '',
                            'allow_in_bindings' => 0,
                            'placeholder' => '',
                            'prepend' => '',
                            'append' => '',
                            'parent_repeater' => 'field_678bca18008f5',
                        ];

                    }, $langs) : []),
                    array(
                        'key' => 'field_678bca4e008f7',
                        'label' => 'Slug',
                        'name' => 'slug',
                        'aria-label' => '',
                        'type' => 'text',
                        'instructions' => '',
                        'required' => 0,
                        'conditional_logic' => 0,
                        'wrapper' => array(
                            'width' => '',
                            'class' => '',
                            'id' => '',
                        ),
                        'default_value' => '',
                        'maxlength' => '',
                        'allow_in_bindings' => 0,
                        'placeholder' => '',
                        'prepend' => '',
                        'append' => '',
                        'parent_repeater' => 'field_678bca18008f5',
                    ),
                ),
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ),
            ),
        ),
        'menu_order' => 11,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );


    /*
    * Register theme locations
    */
    $locations = rwp::field('theme_locations');
    $__locations = [];

    if($locations){
        
        foreach ($locations as $l) {
            $__locations[$l['slug']] = isset($l['name_' . CL]) ? $l['name_' . CL] : $l['slug'];
        }
        
        register_nav_menus($__locations);
        
    }


    /*
    * Medias groups field
    */
    $postTypes = get_post_types();

    $unsets = [
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
        'acf-field',
        'wp_font_family',
        'wp_font_face'
    ];

    foreach ($unsets as $unset) {
        unset($postTypes[$unset]);
    }


    $mediaGroupsPostTypes =  rwp::field('mediaGroups_post_types') ? rwp::field('mediaGroups_post_types') : [];


    $mediaGroupsPts = [
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'post',
            ]
        ],
        [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => 'page',
            ]
        ],
        [
            [
                'param' => 'user_form',
                'operator' => '==',
                'value' => 'all',
            ]
        ],
        [
            [
                'param' => 'taxonomy',
                'operator' => '==',
                'value' => 'all',
            ]
        ]
    ];


    if($mediaGroupsPostTypes){
        foreach ($mediaGroupsPostTypes as $pt) {
            $mediaGroupsPts[] = [
                [
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => $pt,
                ]
            ];
        }
    }


    acf_add_local_field_group( array(
        'key' => 'acf-group_60983478kjhsad54323',
        'title' => 'Media Groups Global Settings',
        'fields' => array(
            array(
                'key' => 'field_60983478kjhsad54324',
                'label' => 'Media Groups Post Types',
                'name' => 'mediaGroups_post_types',
                'aria-label' => '',
                'type' => 'checkbox',
                'instructions' => (CL === 'fr' ? 'Les articles, les pages, les utilisateurs et les taxonomies ont le module. Si vous ne voyez pas de sélection, créez un nouveau type d\'article.' : 'Posts, pages, users and taxonomies have the module. If you don\'t see a selection, create a new post type.'),
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '33.3333333333',
                    'class' => '',
                    'id' => '',
                ),
                'choices' => $postTypes,
                'default_value' => array(
                ),
                'return_format' => 'value',
                'allow_custom' => 0,
                'allow_in_bindings' => 0,
                'layout' => 'vertical',
                'toggle' => 0,
                'save_custom' => 0,
                'custom_choice_button_text' => 'Add new choice',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'options_page',
                    'operator' => '==',
                    'value' => 'site-settings',
                ),
            ),
        ),
        'menu_order' => 12,
        'position' => 'normal',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 0,
    ) );


    /*
    * Add medias Groups field
    */
    acf_add_local_field_group( array(
        'key' => 'group_67kjhb39087sdh233',
        'title' => 'Media Groups',
        'fields' => array(
            array(
                'key' => 'field_67kjhb39087sdh234',
                'label' => (CL === 'fr' ? 'Groupe de médias' : 'Media Groups'),
                'name' => 'media_groups',
                'aria-label' => '',
                'type' => 'text',
                'instructions' => '',
                'required' => 0,
                'conditional_logic' => 0,
                'wrapper' => array(
                    'width' => '',
                    'class' => '',
                    'id' => '',
                ),
                'default_value' => '',
                'maxlength' => '',
                'allow_in_bindings' => 0,
                'placeholder' => '',
                'prepend' => '',
                'append' => ''
            ),
        ),
        'location' => $mediaGroupsPts,
        'menu_order' => 10,
        'position' => 'side',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 1,
    ) );

});