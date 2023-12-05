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
            * Set defaults when you call scg::button() or StudioChampGauche\Utils\Button::get();
            *
            * StudioChampGauche\Utils\Button::default('text', 'x');
            * StudioChampGauche\Utils\Button::default('href', 'x');
            * StudioChampGauche\Utils\Button::default('class', 'x');
            * StudioChampGauche\Utils\Button::default('attr', 'x');
            * StudioChampGauche\Utils\Button::default('before', 'x');
            * StudioChampGauche\Utils\Button::default('after', 'x');
            */
            
            
            /*
            * Set defaults when you call scg::source() or StudioChampGauche\Utils\Source::get();
            *
            * StudioChampGauche\Utils\Source::default('base', '/');
            * StudioChampGauche\Utils\Source::default('url', true);
            */
			
			
			/*
			* Modify SCG Part in wp_head
			*
			* add_filter('scg_wp_head', function($wp_heads){
			*
			* 	//$wp_heads = [];
			*
			* 	return $wp_heads;
			*
			* });
            
            
            
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