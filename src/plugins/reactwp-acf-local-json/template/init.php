<?php
/*
* Plugin Name: ReactWP ACF Local JSON
* Description: Save ACF field groups, post types, taxonomies, and option pages as JSON files within your theme
* Update URI: false
* Version: 1.0.0
*/


$acf_path = rwp::source(['path' => 'datas/acf', 'url' => false]);

/*
* Create ACF JSON Area
*/
add_action('admin_init', function(){

    global $acf_path;

    if(!file_exists($acf_path)){
        mkdir($acf_path, 0777, true);
        fopen($acf_path . '/index.php', 'w');
    }

});


/*
* Save
*/
add_filter('acf/settings/save_json', function($path){

    global $acf_path;

    return $acf_path;

});


/*
* Load
*/
add_filter('acf/settings/load_json', function($paths){

    global $acf_path;

    // Remove original path
    unset( $paths[0] );

    // Append our new path
    $paths[] = $acf_path;

    return $paths;
});