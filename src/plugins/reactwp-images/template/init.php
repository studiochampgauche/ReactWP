<?php
/*
* Plugin Name: ReactWP Images
* Description: Handle images
* Update URI: false
* Version: 1.0.0
*/


/*
* Stop resizing images
*/
add_filter('intermediate_image_sizes_advanced', function($size, $metadata){
    return [];
}, 10, 2);


/*
* Image quality
*/
add_filter('jpeg_quality', function(){

    return 100;

});


add_filter('wp_editor_set_quality', function(){

    return 100;

});