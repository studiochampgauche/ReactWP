<?php

	class helloChamp{

		function __construct(){
            
			if(!class_exists('scg')) return;
			
            /*
            * str_replace your return when you use scg::field() or StudioChampGauche\Utils\Field::get();
            *
            * StudioChampGauche\Utils\Field::replace(['{MAIN_EMAIL}'], [scg::field('contact_email_main')]);
			*
			* You need to use ::replace Method in acf/init hook if you play with acf Field
            */
            
            
            /*
            * Set defaults when you call scg::cpt() or StudioChampGauche\Utils\CustomPostType::get();
            */
            StudioChampGauche\Utils\CustomPostType::default('posts_per_page', -1);
            StudioChampGauche\Utils\CustomPostType::default('paged', 1);
			


            /*
            * Shot events on template_redirect
            */
            add_action('template_redirect', function(){

            	if(is_admin()) return;

            	wp_redirect(admin_url());

            	exit;

            });
			
			
			/*
			* Enqueue Scripts
			*/
			add_action('wp_enqueue_scripts', function(){

				//wp_localize_script('scg-main', 'SYSTEM', []);
				
			}, 11);

		}

	}

	new helloChamp();
?>