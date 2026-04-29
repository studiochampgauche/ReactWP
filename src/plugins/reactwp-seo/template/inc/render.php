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
		add_action('acf/init', [$this, 'acf'], 20);


		/*
		* Enqueue
		*/
		add_action('wp_enqueue_scripts', function(){

			$defaultSEO = \rwp::field('seo', 'option');
            $defaultSEO['blogName'] = \ReactWP\SEO\SEO::site_name();

            wp_localize_script('rwp-main', 'RWP_SEO', $defaultSEO);

		}, 11);
        
    }
    
    
    public function wp_head(){

        self::$wp_heads = apply_filters('rwp_wp_head', self::$wp_heads, [
            'source' => 'wp_head',
            'object' => get_queried_object(),
        ]);
        echo implode('', array_filter(self::$wp_heads, 'is_string'));

    }

    public function acf(){

        if(!function_exists('acf_add_local_field_group')){
            return;
        }

        $register_content_fields = self::should_register_content_fields();
        $register_global_fields = self::should_register_global_fields();

        if(!$register_content_fields && !$register_global_fields){
            return;
        }

    	$postTypes = get_post_types([
            'public' => true
        ], 'objects');

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

        $postTypeChoices = [];

        foreach($postTypes as $slug => $postType){
            $postTypeChoices[$slug] = $postType->labels->singular_name ?: $slug;
        }

		$seoPostTypes = self::option_checkbox_values('seo_post_types');


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

		$langs = self::langs();
		$fields = [];
		$global_fields = [];

		if($langs){

			foreach ($langs as $k => $v) {
				
				$fields[] = [
					array(
						'key' => 'field_67a8981f520aa' . $v['code'],
						'label' => $v['name'],
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
						'placement' => 'top',
						'endpoint' => 0,
						'selected' => 0,
					),
					array(
						'key' => 'field_6hgwsei290' . $v['code'],
						'label' => 'Title ('. $v['code'] .')',
						'name' => 'title_' . $v['code'],
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
						'key' => 'field_sadkljf9923jsd1' . $v['code'],
						'label' => 'Description ('. $v['code'] .')',
						'name' => 'description_' . $v['code'],
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
						'key' => 'field_sadkljasdaad312e23213' . $v['code'],
						'label' => 'OG Title ('. $v['code'] .')',
						'name' => 'og_title_' . $v['code'],
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
						'key' => 'field_sadkljasdaf9923213' . $v['code'],
						'label' => 'OG Description ('. $v['code'] .')',
						'name' => 'og_description_' . $v['code'],
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
				];


				$global_fields[] = [
					array(
						'key' => 'field_67a8981f520aa1' . $v['code'],
						'label' => $v['name'],
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
						'placement' => 'top',
						'endpoint' => 0,
						'selected' => 0,
					),
					array(
						'key' => 'field_sadkljf9923jsd11' . $v['code'],
						'label' => 'Description ('. $v['code'] .')',
						'name' => 'description_' . $v['code'],
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
						'key' => 'field_sadkljasdaad312e232131' . $v['code'],
						'label' => 'OG Title ('. $v['code'] .')',
						'name' => 'og_title_' . $v['code'],
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
						'key' => 'field_sadkljasdaf99232131' . $v['code'],
						'label' => 'OG Description ('. $v['code'] .')',
						'name' => 'og_description_' . $v['code'],
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
				];

			}

			$fields = call_user_func_array('array_merge', $fields);
			$global_fields = call_user_func_array('array_merge', $global_fields);

		}


        if($register_content_fields){
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
                                    'width' => '33.3333333333',
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
                                'key' => 'field_678c4ab4abcb5',
                                'label' => 'OG Image',
                                'name' => 'og_image',
                                'aria-label' => '',
                                'type' => 'image',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '33.3333333333',
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
                            ...$fields
                        ),
                    ),
                ),
                'location' => $seoPts,
                'menu_order' => 99,
                'position' => 'normal',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 1,
            ) );
        }

        if($register_global_fields){
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
                                'choices' => $postTypeChoices,
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
                                    'width' => '33.3333333333',
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
                                'key' => 'field_lkjeqweoihjjws54324314',
                                'label' => 'OG Image',
                                'name' => 'og_image',
                                'aria-label' => '',
                                'type' => 'image',
                                'instructions' => '',
                                'required' => 0,
                                'conditional_logic' => 0,
                                'wrapper' => array(
                                    'width' => '33.3333333333',
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
                            ...$global_fields
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
                'menu_order' => 99,
                'position' => 'normal',
                'style' => 'seamless',
                'label_placement' => 'top',
                'instruction_placement' => 'label',
                'hide_on_screen' => '',
                'active' => true,
                'description' => '',
                'show_in_rest' => 1,
            ) );
        }

    }

    private static function option_checkbox_values($name){

        static $cache = [];

        $cacheKey = (string)$name;

        if(array_key_exists($cacheKey, $cache)){
            return $cache[$cacheKey];
        }

        $value = get_option('options_' . $name, []);

        if(is_array($value)){
            $cache[$cacheKey] = array_values(array_filter($value));
            return $cache[$cacheKey];
        }

        if($value === null || $value === ''){
            $cache[$cacheKey] = [];
            return $cache[$cacheKey];
        }

        $cache[$cacheKey] = [(string)$value];

        return $cache[$cacheKey];

    }

    private static function option_repeater_rows($name, $subFields = []){

        static $cache = [];

        $subFields = array_values(array_map('strval', (array)$subFields));
        $cacheKey = (string)$name . '|' . implode('|', $subFields);

        if(array_key_exists($cacheKey, $cache)){
            return $cache[$cacheKey];
        }

        $rowsCount = (int)get_option('options_' . $name, 0);
        $rows = [];

        if($rowsCount < 1){
            $cache[$cacheKey] = [];
            return $cache[$cacheKey];
        }

        for($index = 0; $index < $rowsCount; $index++){
            $row = [];

            foreach($subFields as $subField){
                $row[$subField] = get_option("options_{$name}_{$index}_{$subField}");
            }

            $rows[] = $row;
        }

        $cache[$cacheKey] = $rows;

        return $cache[$cacheKey];

    }

    private static function langs(){

        static $langs = null;

        if($langs !== null){
            return $langs;
        }

        $langs = array_values(array_filter(
            self::option_repeater_rows('langs', ['name', 'code']),
            function($lang){
                return !empty($lang['code']);
            }
        ));

        return $langs;

    }

    private static function should_register_content_fields(){

        if(!is_admin()){
            return true;
        }

        if((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()){
            return true;
        }

        return in_array(self::admin_script(), [
            'post.php',
            'post-new.php',
            'profile.php',
            'user-edit.php',
            'user-new.php',
            'term.php',
            'edit-tags.php',
        ], true);

    }

    private static function should_register_global_fields(){

        if(!is_admin()){
            return true;
        }

        if((defined('REST_REQUEST') && REST_REQUEST) || wp_doing_ajax()){
            return true;
        }

        return self::request_value('page') === 'site-settings';

    }

    private static function admin_script(){

        return isset($_SERVER['PHP_SELF'])
            ? strtolower(basename((string)$_SERVER['PHP_SELF']))
            : '';

    }

    private static function request_value($key){

        if(!isset($_REQUEST[$key]) || !is_scalar($_REQUEST[$key])){
            return '';
        }

        return sanitize_key(wp_unslash((string)$_REQUEST[$key]));

    }

}

new Render();
