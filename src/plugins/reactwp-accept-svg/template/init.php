<?php
/*
* Plugin Name: ReactWP Accept SVG
* Description: Upload your SVGs
* Update URI: false
* Version: 1.0.0
*/


add_filter('upload_mimes', function($mimes){
                    
    $mimes['svg'] = 'image/svg+xml';
    return $mimes;
    
});

add_filter('wp_check_filetype_and_ext', function( $data, $file, $filename, $mimes) {

    $filetype = wp_check_filetype($filename, $mimes);

    return [
        'ext' => $filetype['ext'],
        'type' => $filetype['type'],
        'proper_filename' => $data['proper_filename']
    ];

}, 10, 4);