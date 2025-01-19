<?php

namespace ReactWP\Seo\Render;

class Render{

	public static $wp_heads = [];
    
    function __construct(){
		
		/*
		* Display on wp_head
		*/
		add_action('wp_head', [$this, 'wp_head'], 2);


		/*
		* Display ACF Fields in admin
		*/
		add_action('init', [$this, 'acf']);
        
    }
    
    
    public function wp_head(){

        self::$wp_heads = apply_filters('rwp_wp_head', self::$wp_heads);
        echo implode('', self::$wp_heads);

    }

    public function acf(){

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


		$seoPostTypes = \rwp::field('seo_post_types') ? \rwp::field('seo_post_types') : [];


		$seoPts = [
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


		if($seoPostTypes){
			foreach ($seoPostTypes as $pt) {
				$seoPts[] = [
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => $pt,
					]
				];
			}
		}


    	acf_add_local_field_group( array(
			'key' => 'group_678c4a3132179',
			'title' => 'SEO',
			'fields' => array(
				array(
					'key' => 'field_678c4b58ad753',
					'label' => 'SEO',
					'name' => 'seo',
					'aria-label' => '',
					'type' => 'group',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'layout' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_678c4a31abcb0',
							'label' => 'Don\'t index',
							'name' => 'do_not_index',
							'aria-label' => '',
							'type' => 'true_false',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '',
								'class' => '',
								'id' => '',
							),
							'message' => '',
							'default_value' => 0,
							'allow_in_bindings' => 0,
							'ui_on_text' => '',
							'ui_off_text' => '',
							'ui' => 1,
						),
						array(
							'key' => 'field_678c4a75abcb1',
							'label' => 'Title',
							'name' => 'title',
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
						),
						array(
							'key' => 'field_678c4a83abcb2',
							'label' => 'Description',
							'name' => 'description',
							'aria-label' => '',
							'type' => 'textarea',
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
							'rows' => 6,
							'placeholder' => '',
							'new_lines' => '',
						),
						array(
							'key' => 'field_678c4a9aabcb4',
							'label' => 'OG Title',
							'name' => 'og_title',
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
						),
						array(
							'key' => 'field_678c4a8fabcb3',
							'label' => 'OG Description',
							'name' => 'og_description',
							'aria-label' => '',
							'type' => 'textarea',
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
							'rows' => 6,
							'placeholder' => '',
							'new_lines' => '',
						),
						array(
							'key' => 'field_678c4ab4abcb5',
							'label' => 'OG Image',
							'name' => 'og_image',
							'aria-label' => '',
							'type' => 'image',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'url',
							'library' => 'all',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'allow_in_bindings' => 0,
							'preview_size' => 'medium',
						),
					),
				),
			),
			'location' => $seoPts,
			'menu_order' => 1,
			'position' => 'side',
			'style' => 'seamless',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 0,
		) );

		acf_add_local_field_group( array(
			'key' => 'acf-group_678bca1894522',
			'title' => 'SEO Global Settings',
			'fields' => array(
				array(
					'key' => 'field_678bcff6f2484',
					'label' => 'SEO',
					'name' => 'seo',
					'aria-label' => '',
					'type' => 'group',
					'instructions' => '',
					'required' => 0,
					'conditional_logic' => 0,
					'wrapper' => array(
						'width' => '',
						'class' => '',
						'id' => '',
					),
					'layout' => 'block',
					'sub_fields' => array(
						array(
							'key' => 'field_678c651684c05',
							'label' => 'Post types',
							'name' => 'post_types',
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
						array(
							'key' => 'field_678bd0cce3947',
							'label' => 'Favicon',
							'name' => 'favicon',
							'aria-label' => '',
							'type' => 'image',
							'instructions' => '192 x 192',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '66.6666666667',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'url',
							'library' => 'all',
							'min_width' => 192,
							'min_height' => 192,
							'min_size' => '',
							'max_width' => 192,
							'max_height' => 192,
							'max_size' => '',
							'mime_types' => '',
							'allow_in_bindings' => 0,
							'preview_size' => 'medium',
						),
						array(
							'key' => 'field_678bd007f2485',
							'label' => 'Description',
							'name' => 'description',
							'aria-label' => '',
							'type' => 'textarea',
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
							'rows' => 6,
							'placeholder' => '',
							'new_lines' => '',
						),
						array(
							'key' => 'field_678bd055f2487',
							'label' => 'OG Title',
							'name' => 'og_title',
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
						),
						array(
							'key' => 'field_678bd03ef2486',
							'label' => 'OG Description',
							'name' => 'og_description',
							'aria-label' => '',
							'type' => 'textarea',
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
							'rows' => 6,
							'placeholder' => '',
							'new_lines' => '',
						),
						array(
							'key' => 'field_678bd060f2488',
							'label' => 'OG Image',
							'name' => 'og_image',
							'aria-label' => '',
							'type' => 'image',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '',
								'class' => '',
								'id' => '',
							),
							'return_format' => 'url',
							'library' => 'all',
							'min_width' => '',
							'min_height' => '',
							'min_size' => '',
							'max_width' => '',
							'max_height' => '',
							'max_size' => '',
							'mime_types' => '',
							'allow_in_bindings' => 0,
							'preview_size' => 'medium',
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
			'menu_order' => 2,
			'position' => 'normal',
			'style' => 'seamless',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 0,
		) );

    }

}

new Render();