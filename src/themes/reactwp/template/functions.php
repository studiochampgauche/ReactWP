<?php

/*
* Enqueue styles & scripts
*/
add_action('wp_enqueue_scripts', function(){

    global $template;
    $acfGroups = [];

    /*
    * Routes
    */
    $obj = get_queried_object();

    if(is_singular() && $obj instanceof \WP_Post){
        $id = $obj->ID;
        $type = $obj->post_type;
        $url = get_the_permalink($id);
        $pageName = rwp::field('page_title', $id) ?? get_the_title($id);
        $acfGroups = acf_get_field_groups(['post_id' => $id]);
    } elseif(is_author() && $obj instanceof \WP_User){
        $id = 'user_' . $obj->ID;
        $type = 'user';
        $url = get_author_posts_url($obj->ID);
        $pageName = rwp::field('page_title', $id) ?? 'Post(s) of ' . $obj->user_firstname;
        $acfGroups = acf_get_field_groups(['user_id' => $obj->ID, 'rest' => true]);
    } elseif((is_category() || is_tag() || is_tax()) && $obj instanceof \WP_Term){
        $id = 'term_' . $obj->term_id;
        $type = 'term';
        $url = get_term_link($obj->term_id);
        $pageName = rwp::field('page_title', $id) ?? $obj->name;
        $acfGroups = acf_get_field_groups(['term_id' => $obj->term_id, 'rest' => true]);
    } else {
        $id = null;
        $type = null;
        $url = null;
        $pageName = null;
    }

    $acf = [];
    $seo = [];
    $mediaGroups = '';
    $pageTemplate = null;

    if($acfGroups){

        foreach($acfGroups as $l => $group){

            if(!$group['active'] || !$group['show_in_rest']) continue;


            $fields = acf_get_fields($group['key']);

            if(!$fields) continue;

            foreach($fields as $m => $field){

                $acf[$field['name']] = rwp::field($field['name'], $id);

            }

        }

    }

    if(isset($acf['seo'])){
        $seo = $acf['seo'];
        unset($acf['seo']);
    }

    if(isset($acf['media_groups'])){
        $mediaGroups = $acf['media_groups'];
        unset($acf['media_groups']);
    }

    if(isset($acf['react_template'])){
        $pageTemplate = $acf['react_template'];
        unset($acf['react_template']);
    }

    
    wp_localize_script('rwp-main', 'CURRENT_ROUTE', [
        'id' => $id,
        'type' => $type,
        'template' => $pageTemplate,
        'pageName' => $pageName,
        'path' => wp_parse_url($url, PHP_URL_PATH),
        'seo' => [
            ...$seo,
            'pageName' => $pageName
        ],
        'mediaGroups' => $mediaGroups ?? '',
        'data' => $acf
    ]);

});



/*
* Add inline styles for preloader
*/
add_action('wp_head', function(){

	echo '
	<style type="text/css">
        :root{
            --white-color: #fff;
            --black-color: #000;
        }

        *{
            outline: 0;
            scrollbar-width: none;
            box-sizing: border-box;
            -ms-overflow-style: none;
            -webkit-font-smoothing: antialiased;
        }
        *::-webkit-scrollbar {
            display: none;
        }

        html,
        body{
            margin: 0;
            padding: 0;
        }

        html{
            font-size: 16px;
        }

        body{
            background: var(--black-color);
            max-height: 100lvh;
            overflow: hidden;
        }

		#loader{
			background: var(--black-color);
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100vh;
			z-index: 999;
		}

        @media screen and (pointer: coarse), (pointer: none){
            #loader{
                height: 100svh;
            }
        }
	</style>
	';

}, 3);



/*
* Shot events on acf/init
*/
add_action('acf/init', function(){

    /*
    * Create tags
    */
    ReactWP\Utils\Field::replace([
        '{Y}',
        '{SITE_NAME}',
        '{SITE_DOMAIN}',
    ], [
        date('Y'),
        (class_exists('ReactWP\SEO\SEO') ? ReactWP\SEO\SEO::site_name() : 'Site name'),
        wp_parse_url(site_url())['host'],
    ]);
    

    /*
    * Set defaults when you call rwp::cpt() or ReactWP\Utils\CustomPostType::get();
    */
    ReactWP\Utils\CustomPostType::default('posts_per_page', -1);
    ReactWP\Utils\CustomPostType::default('paged', 1);
    
    
    /*
    * Set defaults when you call rwp::menu() or ReactWP\Utils\Menu::get();
    */
    ReactWP\Utils\Menu::default('container', null);
    ReactWP\Utils\Menu::default('items_wrap', '<ul>%3$s</ul>');

});


/*
* Add critical fonts
*/
add_filter('rwp_critical_fonts', function($fonts){

    //$fonts['all'][] = 'normal 400 1rem "font-family"';

    return $fonts;

});

add_filter('rwp_critical_medias', function($medias){

    //$medias['all'][] = [];

    return $medias;

});
