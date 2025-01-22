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
	wp_enqueue_script('rwp-main', get_stylesheet_directory_uri() . '/assets/js/reactwp.min.js', null, null, true);



	/*
    * Remove rwp-main script type attribute
    */
    add_filter('script_loader_tag', function($tag, $handle, $src){
        if($handle !== 'rwp-main')
            return $tag;

        $tag = '<script src="' . esc_url( $src ) . '"></script>';

        return $tag;

    } , 10, 3);


    /*
    * Medias to Download
    */
    $mediasToDownload = [
    	'group1' => [
    		[
	    		'type' => 'image',
	    		'src' => 'https://siterapide.ca/wp-content/uploads/sites/11/2025/01/sharing.jpg'
	    	]
    	],
    	'group2' => [
    		[
	    		'type' => 'image',
	    		'src' => 'https://siterapide.ca/wp-content/uploads/sites/11/2025/01/sharing.jpg'
	    	],
	    	[
	    		'type' => 'image',
	    		'src' => 'https://siterapide.ca/wp-content/uploads/sites/11/2025/01/sharing.jpg'
	    	]
    	]
    ];
    wp_localize_script('rwp-main', 'MEDIAS', $mediasToDownload);


    /*
    * Routes
    */
    $routes = [];
    $data = rwp::cpt(['page', 'post'])->posts;




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
            	'path' => (get_option('page_on_front') == $v->ID ? '/' : '/' . get_page_uri($v->ID)),
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
		#loader{
			background: #000;
			position: fixed;
			top: 0;
			left: 0;
			width: 100%;
			height: 100svh;
			z-index: 999;
		}
	</style>
	';

});