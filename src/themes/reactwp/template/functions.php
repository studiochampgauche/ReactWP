<?php

/*
* Enqueue styles & scripts
*/
add_action('wp_enqueue_scripts', function(){
	
	/*
	* CSS
	*/
	wp_enqueue_style('rwp-main', get_stylesheet_directory_uri() . '/assets/css/reactwp.min.css', null, null, null);


	/*
	* JS
	*/
	wp_enqueue_script('rwp-main', get_stylesheet_directory_uri() . '/assets/js/reactwp.min.js', null, null, false);



	/*
    * Remove rwp-main script type attribute
    */
    add_filter('script_loader_tag', function($tag, $handle, $src){
        if($handle !== 'rwp-main')
            return $tag;

        $tag = '<script src="' . esc_url( $src ) . '" defer></script>';

        return $tag;

    } , 10, 3);


    /*
    * Medias to Download
    */
    $mediasToDownload = [];
    wp_localize_script('rwp-main', 'MEDIAS', $mediasToDownload);


    /*
    * Routes
    */
    $routes = [];
    $data = rwp::cpt(['page', 'post'], [
        'posts_per_page' => -1
    ])->posts;




    if($data){

	    foreach($data as $k => $v){


	    	$pageTemplate = implode('', array_map('ucfirst', explode('-', str_replace(['.php', ' '], ['', '-'], get_page_template_slug($v->ID)))));


	    	$acfGroups = acf_get_field_groups(['post_id' => $v->ID]);
	    	$acf = [];

	    	if($acfGroups){

	    		foreach($acfGroups as $l => $group){

	    			if(!$group['active'] || !$group['show_in_rest']) continue;


	    			$fields = acf_get_fields($group['key']);

	    			if(!$fields) continue;

	    			foreach($fields as $m => $field){

	    				$acf[$field['name']] = rwp::field($field['name'], $v->ID);

	    			}

	    		}

	    	}

	    	$pageName = rwp::field('name', $v->ID);

            $routes[] = [
            	'id' => $v->ID,
            	'template' => $pageTemplate,
            	'routeName' => $v->post_name,
            	'pageName' => ($pageName ? $pageName : $v->post_title),
            	'path' => str_replace(site_url(), '', get_the_permalink($v->ID)),
            	'type' => $v->post_type,
            	'seo' => (isset($acf['seo']) ? $acf['seo'] : []),
            	'mediaGroups' => (isset($acf['media_groups']) ? str_replace(', ', ',', $acf['media_groups']) : null),
            	'main' => ($v->ID === get_the_ID() ? true : false)
            ];


            if(isset($acf['name']))
            	unset($acf['name']);

            if(isset($acf['seo']))
            	unset($acf['seo']);

            if(isset($acf['media_groups']))
            	unset($acf['media_groups']);


            $routes[$k]['acf'] = $acf;


            if($routes[$k]['type'] === 'post'){

                //$routes[$k]['template'] = 'SinglePost';
                $routes[$k]['seo']['og_type'] = 'article';

                $routes[$k]['extraDatas'] = [
                    'date' => $v->post_date,
                    'modified' => $v->post_modified,
                    'author' => get_author_posts_url($v->post_author)
                ];

            } elseif($routes[$k]['type'] === 'author'){

                $routes[$k]['seo']['og_type'] = 'profile';

                $routes[$k]['extraDatas'] = [
                    'username' => '',
                    'name' => [
                        'firstname' => '',
                        'lastname' => ''
                    ]
                ];

            } else {

            	$routes[$k]['seo']['og_type'] = 'website';

            }


	    }
	}

    wp_localize_script('rwp-main', 'ROUTES', $routes);

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
            &::-webkit-scrollbar {
                display: none;
            }
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
            max-height: 100lvh;
            overflow: hidden;
        }

		#loader{
			background: var(--black-color);
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100svh;
			z-index: 999;
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