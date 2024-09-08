<?php
    
namespace StudioChampGauche\Render;

class Render{
    
    public static $wp_heads = [];
    
    function __construct(){
		
		/*
		* Display ACF Admin Elements
		*/
		add_action('acf/init', [$this, 'acf']);
        
    }
    
	
	public function acf(){
		
		if(!function_exists('acf_add_local_field_group')) return;

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

		acf_add_local_field_group( array(
			'key' => 'group_6569608968ef4',
			'title' => __('Configurations du site', 'cg-core'),
			'fields' => array(
			array(
				'key' => 'field_65697d8022de5',
				'label' => __('Backend', 'cg-core'),
				'name' => '',
				'aria-label' => '',
				'type' => 'tab',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'placement' => 'left',
				'endpoint' => 0,
			),
			array(
				'key' => 'field_6569a23ae02cc',
				'label' => __('Changer l\'apparence', 'cg-core'),
				'name' => 'change_appearance',
				'aria-label' => '',
				'type' => 'true_false',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '33.3333333333',
					'class' => '',
					'id' => '',
				),
				'message' => '',
				'default_value' => 0,
				'ui_on_text' => '',
				'ui_off_text' => '',
				'ui' => 1,
			),
			array(
				'key' => 'field_65697d9722de6',
				'label' => __('Tableau de bord', 'cg-core'),
				'name' => 'dashboard',
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
						'key' => 'field_65697d9722de7',
						'label' => 'welcome_panel',
						'name' => 'welcome_panel',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697df022dfa',
						'label' => 'dashboard_incoming_links',
						'name' => 'dashboard_incoming_links',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697df822dfb',
						'label' => 'dashboard_plugins',
						'name' => 'dashboard_plugins',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697dfd22dfc',
						'label' => 'dashboard_primary',
						'name' => 'dashboard_primary',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e1722dfd',
						'label' => 'dashboard_secondary',
						'name' => 'dashboard_secondary',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e2222dfe',
						'label' => 'dashboard_quick_press',
						'name' => 'dashboard_quick_press',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e3a22dff',
						'label' => 'dashboard_recent_drafts',
						'name' => 'dashboard_recent_drafts',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e4022e00',
						'label' => 'dashboard_recent_comments',
						'name' => 'dashboard_recent_comments',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e4622e01',
						'label' => 'dashboard_right_now',
						'name' => 'dashboard_right_now',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e4e22e02',
						'label' => 'dashboard_activity',
						'name' => 'dashboard_activity',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65697e7d22e05',
						'label' => 'dashboard_site_health',
						'name' => 'dashboard_site_health',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
				),
			),
			array(
				'key' => 'field_65698585f5582',
				'label' => __('Éditeur et Gutenberg', 'cg-core'),
				'name' => 'editor_and_gutenberg',
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
						'key' => 'field_656985c2f5583',
						'label' => __('Gutenberg', 'cg-core'),
						'name' => 'gutenberg',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65698608f5584',
						'label' => __('Éditeur de page', 'cg-core'),
						'name' => 'page_editor',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_65698611f5585',
						'label' => __('Éditeur de blogue', 'cg-core'),
						'name' => 'post_editor',
						'aria-label' => '',
						'type' => 'true_false',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '33.3333333333',
							'class' => '',
							'id' => '',
						),
						'message' => '',
						'default_value' => 0,
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
				),
			),
			array(
				'key' => 'field_6569c7eac0820',
				'label' => __('Theme Locations', 'cg-core'),
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
				'button_label' => __('Ajouter un Theme Location', 'cg-core'),
				'rows_per_page' => 20,
				'sub_fields' => array(
					array(
						'key' => 'field_6569c7ffc0821',
						'label' => __('Nom', 'cg-core'),
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
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'parent_repeater' => 'field_6569c7eac0820',
					),
					array(
						'key' => 'field_6569c805c0822',
						'label' => __('Slug', 'cg-core'),
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
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
						'parent_repeater' => 'field_6569c7eac0820',
					),
				),
			),
			array(
				'key' => 'field_6569b42e163b6',
				'label' => __('Extra', 'cg-core'),
				'name' => '',
				'aria-label' => '',
				'type' => 'tab',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'placement' => 'left',
				'endpoint' => 0,
			),
			array(
				'key' => 'field_656982bae9569',
				'label' => __('Accepter les SVG', 'cg-core'),
				'name' => 'accept_svg',
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
				'ui_on_text' => '',
				'ui_off_text' => '',
				'ui' => 1,
			),
			array(
				'key' => 'field_6569b442163b7',
				'label' => __('Redimensionner les images téléchargées', 'cg-core'),
				'name' => 'resize_images',
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
				'ui_on_text' => '',
				'ui_off_text' => '',
				'ui' => 1,
			),
			array(
				'key' => 'field_656f2438585c4',
				'label' => __('Modules', 'cg-core'),
				'name' => '',
				'aria-label' => '',
				'type' => 'tab',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'placement' => 'left',
				'endpoint' => 0,
			),
			array(
				'key' => 'field_6578ad26fghyrf',
				'label' => __('SEO', 'cg-core'),
				'name' => 'seo_module',
				'aria-label' => '',
				'type' => 'checkbox',
				'instructions' => __('Où afficher le module SEO? post et page ont déjà le module.', 'cg-core'),
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'choices' => $postTypes,
				'default_value' => array(
				),
				'return_format' => 'value',
				'allow_custom' => 0,
				'layout' => 'horizontal',
				'toggle' => 0,
				'save_custom' => 0,
				'custom_choice_button_text' => 'Ajouter un choix',
			),
			array(
				'key' => 'field_6578asdtgftgss',
				'label' => __('Composant', 'cg-core'),
				'name' => 'component_module',
				'aria-label' => '',
				'type' => 'checkbox',
				'instructions' => __('Où afficher le module Composant? post et page ont déjà le module.', 'cg-core'),
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => '',
				),
				'choices' => $postTypes,
				'default_value' => array(
				),
				'return_format' => 'value',
				'allow_custom' => 0,
				'layout' => 'horizontal',
				'toggle' => 0,
				'save_custom' => 0,
				'custom_choice_button_text' => 'Ajouter un choix',
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
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'seamless',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => true,
		'description' => '',
		'show_in_rest' => 0,
		) );


		$seoPostTypes = \StudioChampGauche\Utils\Field::get('seo_module') ? \StudioChampGauche\Utils\Field::get('seo_module') : [];
		$componentPostTypes = \StudioChampGauche\Utils\Field::get('component_module') ? \StudioChampGauche\Utils\Field::get('component_module') : [];


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



		$componentPts = [
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
			]
		];


		if($componentPostTypes){
			foreach ($componentPostTypes as $pt) {
				$componentPts[] = [
					[
						'param' => 'post_type',
						'operator' => '==',
						'value' => $pt,
					]
				];
			}
		}


		acf_add_local_field_group( array(
		'key' => 'group_6574cb08273jnhbds',
		'title' => __('Module Component', 'cg-core'),
		'fields' => array(
				array(
					'key' => 'field_6576114beasdk4983jhb',
					'label' => __('Nom du composant', 'cg-core'),
					'name' => 'component_name',
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
					'placeholder' => '',
					'prepend' => '',
					'append' => '',
				),
			),
			'location' => $componentPts,
			'menu_order' => 99,
			'position' => 'side',
			'style' => 'seamless',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 1,
		));



		acf_add_local_field_group( array(
		'key' => 'group_6574cb60329ec',
		'title' => __('Module SEO', 'cg-core'),
		'fields' => array(
			array(
				'key' => 'field_lkjshbn2988',
				'label' => __('SEO', 'cg-core'),
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
						'key' => 'field_6574cb9751639',
						'label' => __('Ne pas indexer', 'cg-core'),
						'name' => 'stop_indexing',
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
						'ui_on_text' => '',
						'ui_off_text' => '',
						'ui' => 1,
					),
					array(
						'key' => 'field_6574cb619f729',
						'label' => __('Titre', 'cg-core'),
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
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_6574cb619fe8d',
						'label' => __('Description', 'cg-core'),
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
						'rows' => 6,
						'placeholder' => '',
						'new_lines' => '',
					),
					array(
						'key' => 'field_6574casdbgeaxvv',
						'label' => __('OG Titre', 'cg-core'),
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
						'placeholder' => '',
						'prepend' => '',
						'append' => '',
					),
					array(
						'key' => 'field_6574cb09821hnbn',
						'label' => __('OG Description', 'cg-core'),
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
						'rows' => 6,
						'placeholder' => '',
						'new_lines' => '',
					),
					array(
						'key' => 'field_65762c8f5a048',
						'label' => __('Image', 'cg-core'),
						'name' => 'image',
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
						'preview_size' => 'full',
					),
				)),
			),
			'location' => $seoPts,
			'menu_order' => 99,
			'position' => 'side',
			'style' => 'seamless',
			'label_placement' => 'top',
			'instruction_placement' => 'label',
			'hide_on_screen' => '',
			'active' => true,
			'description' => '',
			'show_in_rest' => 1,
		) );


		acf_add_options_page( array(
			'page_title' => __('Configurations du site', 'cg-core'),
			'menu_slug' => 'site-settings',
			'menu_title' => __('Configurations', 'cg-core'),
			'position' => '',
			'redirect' => false,
		) );
		
	}
    
}

new Render();

?>