<?php

add_action('after_setup_theme', function(){
    
    //add_theme_support('post-thumbnails');

});

add_action('wp_head', function(){

	echo '
	<style type="text/css">
        :root{
            color-scheme: light;
            --white: #ffffff;
            --black: #000000;
        }

        *{
            box-sizing: border-box;
        }

        html,
        body{
            margin: 0;
            padding: 0;
        }

        html{
            font-size: 16px;
            scroll-behavior: smooth;
            -webkit-font-smoothing: antialiased;
        }

        body{
            background: var(--black);
            min-height: 100vh;
            color: var(--black);
        }

        #loader{
            background: var(--black);
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            color: var(--white);
        }

        #loader .loader-inner{
            display: grid;
            gap: .55rem;
            text-align: center;
        }

        #loader .loader-kicker{
            letter-spacing: .2em;
            text-transform: uppercase;
            font-size: .72rem;
            opacity: .55;
        }

        #loader .loader-label{
            font-size: clamp(1rem, 2vw, 1.35rem);
            font-weight: 600;
        }

        #viewport{
            min-height: 100vh;
        }

        #pageWrapper,
        #pageContent,
        #app{
            min-height: inherit;
        }
	</style>
	';

}, 3);

add_action('acf/init', function(){

    ReactWP\Utils\Field::replace([
        '{Y}',
        '{SITE_NAME}',
        '{SITE_DOMAIN}'
    ], [
        date('Y'),
        get_bloginfo('name'),
        wp_parse_url(site_url(), PHP_URL_HOST)
    ]);

    ReactWP\Utils\CustomPostType::default('posts_per_page', -1);
    ReactWP\Utils\CustomPostType::default('paged', 1);

    ReactWP\Utils\Menu::default('container', null);
    ReactWP\Utils\Menu::default('items_wrap', '<ul>%3$s</ul>');

});

add_filter('rwp_bootstrap', function($payload){
    
    return $payload;

}, 10, 1);

add_filter('rwp_system', function($system){

    return $system;

});

add_filter('rwp_critical_fonts', function($fonts){

    return $fonts;

});

add_filter('rwp_critical_medias', function($medias){

    return $medias;

});

add_filter('rwp_no_critical_medias', function($medias){

    return $medias;

});

add_filter('acf/load_value/name=react_template', function($value){

    return $value;

}, 10, 3);


add_filter('acf/load_value/name=media_groups', function($value){

    return $value;

}, 10, 3);

add_filter('rwp_wp_head', function($wp_heads, $context = []){

    /*$is_front = false;

    $contextSource = ($context['source'] ?? null);
    $contextPath = ($context['route']['path'] ?? null);

    if($contextSource === 'wp_head'){
        $is_front = is_front_page();
    } elseif($contextSource === 'route'){
        $is_front = ($contextPath === '/');
    }

    if($is_front){
        $wp_heads['potato'] = '<meta name="potato" content="He is a vegetable.">';
    }*/

    return $wp_heads;

}, 10, 2);
