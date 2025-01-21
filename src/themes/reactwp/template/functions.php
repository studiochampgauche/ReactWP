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