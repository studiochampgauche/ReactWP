<?php
	
	class helloChamp{

		function __construct(){

			/*
			* Protect REST API
			*/
			add_filter('rest_authentication_errors', function($result) {

				if (!current_user_can('manage_options')){
					return new WP_Error(
						'cannot_access_rest',
						__('JSON REST API IS PROTECTED'),
						['status' => 403]
					);
				}

				return $result;

			});


			/*
			* Preload
			
			add_action('wp_head', function(){

				echo '<link rel="preload" as="font" href="" type="font/woff2" crossorigin />';

				echo '<link rel="preload" as="image" href="">';
				
			}, 3);
			*/

			/*
			* Enqueue Scripts
			*/
			add_action('wp_enqueue_scripts', function(){

				wp_localize_script('scg-main', 'SYSTEM', [
					'ajaxurl' => admin_url('admin-ajax.php'),
					'lang' => 'fr'
				]);
				
			}, 11);


			/*
			* admin_init
			*/
			add_action('admin_init', function(){

				/*
				* Remove Editor on page
				*/
				remove_post_type_support('page', 'editor');

			});

		}

	}

	new helloChamp();
?>