<?php

namespace ReactWP\Consent\Render;

class Render{
    
    function __construct(){


    	/*
		* Display the consent box
		*/
		add_action('wp_footer', [$this, 'wp_footer']);


		/*
		* Display ACF Fields in admin
		*/
		add_action('init', [$this, 'acf']);

        
    }


    public function wp_footer(){

    	$consentData = \rwp::field('consent_box');

    	$buttons = [
    		($consentData['accept_button'] ?? [
				'fr' => null,
				'en' => null
    		]),
    		($consentData['reject_button'] ?? [
				'fr' => null,
				'en' => null
    		])
    	];

    	echo '
    		<div id="consent-panel">
    			<div id="consent-box">
    				<div class="inner">
    					<div class="contents">
    						'. ($consentData['header_title'] && $consentData['header_title'][CL] ? '<h2>'. $consentData['header_title'][CL] .'</h2>' : null) .'
    						<div class="text">
    							'. ($consentData['text'] ? $consentData['text'][CL] : null) .'
    						</div>
    						<div class="buttons">
		';
							foreach($buttons as $k => $v){

								if(!$v[CL]) continue;

								echo \rwp::button($v[CL], [
									'class' => ($k === 0 ? 'accept' : 'reject')
								]);

							}
		echo '
							</div>
		';

							if($consentData['links']){
								echo '<ul class="links">';

								foreach($consentData['links'] as $k => $v){

									echo '<li>';
										echo '<a href="'. $v['url'][CL] .'"'. ($v['new_tab'] ? ' target="_blank"' : null) .'><span>'. $v['text'][CL] .'</span></a>';
									echo '</li>';

								}

								echo '</ul>';
							}

		echo '
    					</div>
    				</div>
    			</div>
    			<div id="consent-btn">
    				<div class="inner">
    					<span>'. ($consentData['tab_text'] ? $consentData['tab_text'][CL] : null) .'</span>
    				</div>
    			</div>
    		</div>
    	';

    }

    public function acf(){

    	acf_add_local_field_group( array(
			'key' => 'group_678cbb0414873',
			'title' => 'Consent Box',
			'fields' => array(
				array(
					'key' => 'field_678cbb045f894',
					'label' => (CL === 'fr' ? 'Boite de consentement' : 'Consent Box'),
					'name' => 'consent_box',
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
							'key' => 'field_678cbb475f895',
							'label' => 'Version',
							'name' => 'version',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '33.3333333333',
								'class' => '',
								'id' => '',
							),
							'default_value' => 1,
							'min' => 1,
							'max' => '',
							'allow_in_bindings' => 0,
							'placeholder' => '',
							'step' => '0.1',
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_678cbb7c5f896',
							'label' => 'Expiration',
							'name' => 'expiration',
							'aria-label' => '',
							'type' => 'number',
							'instructions' => '',
							'required' => 0,
							'conditional_logic' => 0,
							'wrapper' => array(
								'width' => '33.3333333333',
								'class' => '',
								'id' => '',
							),
							'default_value' => 300,
							'min' => 0,
							'max' => '',
							'allow_in_bindings' => 0,
							'placeholder' => '',
							'step' => 300,
							'prepend' => '',
							'append' => '',
						),
						array(
							'key' => 'field_678cbc085f897',
							'label' => (CL === 'fr' ? 'Texte de l\'onglet' : 'Tab Text'),
							'name' => 'tab_text',
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
									'key' => 'field_678cbc225f898',
									'label' => (CL === 'fr' ? 'Français' : 'French'),
									'name' => 'fr',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
									'key' => 'field_678cbc275f899',
									'label' => (CL === 'fr' ? 'Anglais' : 'English'),
									'name' => 'en',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
							),
						),
						array(
							'key' => 'field_678cbc8a5f89a',
							'label' => (CL === 'fr' ? 'Titre de l\'en-tête' : 'Header Title'),
							'name' => 'header_title',
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
									'key' => 'field_678cbc8a5f89b',
									'label' => (CL === 'fr' ? 'Français' : 'French'),
									'name' => 'fr',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
									'key' => 'field_678cbc8a5f89c',
									'label' => (CL === 'fr' ? 'Anglais' : 'English'),
									'name' => 'en',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
							),
						),
						array(
							'key' => 'field_678cbcd45f89d',
							'label' => (CL === 'fr' ? 'Texte' : 'Text'),
							'name' => 'text',
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
									'key' => 'field_678cbcd45f89e',
									'label' => (CL === 'fr' ? 'Français' : 'French'),
									'name' => 'fr',
									'aria-label' => '',
									'type' => 'wysiwyg',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
										'class' => '',
										'id' => '',
									),
									'default_value' => '',
									'allow_in_bindings' => 0,
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 0,
									'delay' => 0,
								),
								array(
									'key' => 'field_678cbcf95f8a0',
									'label' => (CL === 'fr' ? 'Anglais' : 'English'),
									'name' => 'en',
									'aria-label' => '',
									'type' => 'wysiwyg',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
										'class' => '',
										'id' => '',
									),
									'default_value' => '',
									'allow_in_bindings' => 0,
									'tabs' => 'all',
									'toolbar' => 'basic',
									'media_upload' => 0,
									'delay' => 0,
								),
							),
						),
						array(
							'key' => 'field_678cbd0d5f8a1',
							'label' => (CL === 'fr' ? 'Button accepter' : 'Accept button'),
							'name' => 'accept_button',
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
									'key' => 'field_678cbd0d5f8a2',
									'label' => (CL === 'fr' ? 'Français' : 'French'),
									'name' => 'fr',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
									'key' => 'field_678cbd0d5f8a3',
									'label' => (CL === 'fr' ? 'Anglais' : 'English'),
									'name' => 'en',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
							),
						),
						array(
							'key' => 'field_678cbd185f8a4',
							'label' => (CL === 'fr' ? 'Button rejet' : 'Reject button'),
							'name' => 'reject_button',
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
									'key' => 'field_678cbd185f8a5',
									'label' => (CL === 'fr' ? 'Français' : 'French'),
									'name' => 'fr',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
									'key' => 'field_678cbd185f8a6',
									'label' => (CL === 'fr' ? 'Anglais' : 'English'),
									'name' => 'en',
									'aria-label' => '',
									'type' => 'text',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '50',
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
							),
						),
						array(
							'key' => 'field_678cbde5341f3',
							'label' => (CL === 'fr' ? 'Lien' : 'Links'),
							'name' => 'links',
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
							'layout' => 'block',
							'pagination' => 0,
							'min' => 0,
							'max' => 0,
							'collapsed' => '',
							'button_label' => (CL === 'fr' ? 'Ajouter un lien' : 'Add a link'),
							'rows_per_page' => 20,
							'sub_fields' => array(
								array(
									'key' => 'field_678cbe03341f4',
									'label' => (CL === 'fr' ? 'Texte' : 'Text'),
									'name' => 'text',
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
											'key' => 'field_678cbe03341f5',
											'label' => (CL === 'fr' ? 'Français' : 'French'),
											'name' => 'fr',
											'aria-label' => '',
											'type' => 'text',
											'instructions' => '',
											'required' => 0,
											'conditional_logic' => 0,
											'wrapper' => array(
												'width' => '50',
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
											'key' => 'field_678cbe03341f6',
											'label' => (CL === 'fr' ? 'Anglais' : 'English'),
											'name' => 'en',
											'aria-label' => '',
											'type' => 'text',
											'instructions' => '',
											'required' => 0,
											'conditional_logic' => 0,
											'wrapper' => array(
												'width' => '50',
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
									),
									'parent_repeater' => 'field_678cbde5341f3',
								),
								array(
									'key' => 'field_678cbe13341f7',
									'label' => 'URL',
									'name' => 'url',
									'aria-label' => '',
									'type' => 'group',
									'instructions' => '',
									'required' => 0,
									'conditional_logic' => 0,
									'wrapper' => array(
										'width' => '66.6666666667',
										'class' => '',
										'id' => '',
									),
									'layout' => 'block',
									'sub_fields' => array(
										array(
											'key' => 'field_678cbe13341f8',
											'label' => (CL === 'fr' ? 'Français' : 'French'),
											'name' => 'fr',
											'aria-label' => '',
											'type' => 'text',
											'instructions' => '',
											'required' => 0,
											'conditional_logic' => 0,
											'wrapper' => array(
												'width' => '50',
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
											'key' => 'field_678cbe13341f9',
											'label' => (CL === 'fr' ? 'Anglais' : 'English'),
											'name' => 'en',
											'aria-label' => '',
											'type' => 'text',
											'instructions' => '',
											'required' => 0,
											'conditional_logic' => 0,
											'wrapper' => array(
												'width' => '50',
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
									),
									'parent_repeater' => 'field_678cbde5341f3',
								),
								array(
									'key' => 'field_678cbe1e341fa',
									'label' => (CL === 'fr' ? 'Nouvelle tab' : 'New tab'),
									'name' => 'new_tab',
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
									'parent_repeater' => 'field_678cbde5341f3',
								),
							),
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
			'position' => 'acf_after_title',
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