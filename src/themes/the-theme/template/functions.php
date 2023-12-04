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
            * Add to wp_head() just after SEO Returns
			*
			* StudioChampGauche\Render\Render::addToHeadTag('<link rel="preload" as="font" href="" type="font/woff2" crossorigin />');
            * StudioChampGauche\Render\Render::addToHeadTag('<link rel="preload" as="image" href="" />');
			*
            *
			* NOTE #1
			* You can play/modify the datas
			* StudioChampGauche\Render\Render::$wp_heads;
			*
			* e.g. you can clear the returns and redo it
			* StudioChampGauche\Render\Render::$wp_heads = [];
			* StudioChampGauche\Render\Render::addToHeadTag('<title></title>');
			*
			* NOTE #2
			* You can go to yoursite.com/wp-admin/admin.php?page=site-settings
			* for add quick HTML tags right after <head> & <body> and before </head> & </body>
			*
			* Note #3
			* you can use wp_head hook too in priority 1 or 3 for place your codes before or after SEO Returns.
			* Seo Return is on priority 2.
			*
			* Use priority 8 for place it after scg-main-css.
			* 
			* add_action('wp_head', function(){
			* 	echo '<link rel="preload" as="font" href="" type="font/woff2" crossorigin />';
			* }, 3);
			* 
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