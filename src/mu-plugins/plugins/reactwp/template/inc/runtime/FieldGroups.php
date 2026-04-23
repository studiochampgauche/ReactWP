<?php

namespace ReactWP\Runtime;

function option_checkbox_values($name) {

    $value = get_option('options_' . $name, []);

    if(is_array($value)){
        return array_values(array_filter($value));
    }

    if($value === null || $value === ''){
        return [];
    }

    return [(string)$value];

}

function content_field_locations($option_name) {

    $locations = [
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

    foreach(option_checkbox_values($option_name) as $post_type){
        $locations[] = [
            [
                'param' => 'post_type',
                'operator' => '==',
                'value' => $post_type,
            ]
        ];
    }

    return $locations;

}

function register_runtime_field_groups() {

    if(!function_exists('acf_add_local_field_group')){
        return;
    }

    acf_add_local_field_group([
        'key' => 'group_67kjhb39087sdh233',
        'title' => 'Media Groups',
        'fields' => [
            [
                'key' => 'field_67kjhb39087sdh234',
                'label' => (defined('CL') && CL === 'fr' ? 'Groupe de médias' : 'Media Groups'),
                'name' => 'media_groups',
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
                'append' => ''
            ],
        ],
        'location' => content_field_locations('mediaGroups_post_types'),
        'menu_order' => 10,
        'position' => 'side',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 1,
    ]);

    acf_add_local_field_group([
        'key' => 'group_67kjasd7ayu12g31j8skj33',
        'title' => 'React Template',
        'fields' => [
            [
                'key' => 'field_67kjasd7ayu12g31j8skj34',
                'label' => 'React Template',
                'name' => 'react_template',
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
                'append' => ''
            ],
        ],
        'location' => content_field_locations('react_template_post_types'),
        'menu_order' => 10,
        'position' => 'side',
        'style' => 'seamless',
        'label_placement' => 'top',
        'instruction_placement' => 'label',
        'hide_on_screen' => '',
        'active' => true,
        'description' => '',
        'show_in_rest' => 1,
    ]);

}

\add_action('acf/init', __NAMESPACE__ . '\\register_runtime_field_groups', 20);
