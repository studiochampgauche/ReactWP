<?php
	
	class helloChamp{

		function __construct(){


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
					'ajaxurl' => admin_url('admin-ajax.php')
				]);
				
			}, 11);

		}

	}

	new helloChamp();
?>