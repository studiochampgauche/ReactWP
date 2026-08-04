<?php
/*
* Plugin Name: ReactWP ACF Local JSON
* Description: Save ACF field groups, post types, taxonomies, and option pages as JSON files within your theme
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
* Update URI: false
* Version: 1.1.0
*/

if(!defined('ABSPATH')){
    exit;
}


$acf_path = rwp::source(['path' => 'datas/acf', 'url' => false]);

function rwp_prepare_acf_json_directory($path) {

    if(!is_string($path) || $path === ''){
        return false;
    }

    if(!is_dir($path) && !wp_mkdir_p($path)){
        return false;
    }

    $protective_files = [
        'index.php' => "<?php\nhttp_response_code(404);\nexit;\n",
        '.htaccess' => "Require all denied\n",
        'web.config' => "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<configuration><system.webServer><security><authorization><remove users=\"*\" roles=\"\" verbs=\"\" /><add accessType=\"Deny\" users=\"*\" /></authorization></security></system.webServer></configuration>\n",
    ];

    foreach($protective_files as $filename => $contents){
        $target = trailingslashit($path) . $filename;

        if(!file_exists($target) && file_put_contents($target, $contents, LOCK_EX) === false){
            return false;
        }
    }

    return is_writable($path);

}

/*
* Create ACF JSON Area
*/
add_action('admin_init', function(){

    global $acf_path;

    rwp_prepare_acf_json_directory($acf_path);

});


/*
* Save
*/
add_filter('acf/settings/save_json', function($path){

    global $acf_path;

    rwp_prepare_acf_json_directory($acf_path);

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
    if(is_dir($acf_path)){
        $paths[] = $acf_path;
    }

    return $paths;
});
