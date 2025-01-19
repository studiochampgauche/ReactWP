<?php

/*
* Enqueue styles & scripts
*/
add_action('wp_enqueue_scripts', function(){
	
	/*
	* CSS
	*/
	wp_enqueue_style('rwp-main', get_stylesheet_directory_uri() . '/assets/css/main.min.css', null, null, null);


	/*
	* JS
	*/
	wp_enqueue_script('rwp-main', get_stylesheet_directory_uri() . '/assets/js/main.min.js', null, null, true);



	/*
    * Remove rwp-main script type attribute
    */
    add_filter('script_loader_tag', function($tag, $handle, $src){
        if($handle !== 'rwp-main')
            return $tag;

        $tag = '<script src="' . esc_url( $src ) . '"></script>';

        return $tag;

    } , 10, 3);

});