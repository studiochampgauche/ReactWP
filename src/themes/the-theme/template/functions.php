<?php

	class helloChamp{

		function __construct(){
            
            /*
            * str_replace your return when you use scg::field() or StudioChampGauche\Utils\Field::get();
            *
            * StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email_main')]);
            */
            
            
            /*
            * Set defaults when you call scg::cpt() or StudioChampGauche\Utils\CustomPostType::get();
            */
            StudioChampGauche\Utils\CustomPostType::default('post_per_page', -1);
            StudioChampGauche\Utils\CustomPostType::default('paged', 1);
            
            
            /*
            * Set defaults when you call scg::menu() or StudioChampGauche\Utils\Menu::get();
            */
            StudioChampGauche\Utils\Menu::default('container', null);
            StudioChampGauche\Utils\Menu::default('items_wrap', '<ul>%3$s</ul>');
            
            
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