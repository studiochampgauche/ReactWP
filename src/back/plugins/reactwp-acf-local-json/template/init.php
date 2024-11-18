<?php
/*
Plugin Name: ACF Local JSON
Author: Studio Champ Gauche
Author URI: https://champgauche.studio
Description: Save ACF field groups, post types, taxonomies, and option pages as JSON files within your theme
Requires at least: 6.4.1
Requires PHP: 8.2
Version: 1.0.0
Text Domain: reactwp-acf-local-json
*/



/*
* Check ABSPATH
*/
if(!defined('ABSPATH')) return;


/*
* Make sure you have all you need for the plugin
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';


class ReactWPACFJSON{
    
    private $acf_path;

    function __construct(){
        
        $this->acf_path = rwp::source(['path' => 'datas/acf', 'url' => false]);


        /*
        * Create ACF JSON Area
        */
        add_action('admin_init', function(){

            if(!file_exists($this->acf_path)){
                mkdir($this->acf_path, 0777, true);
                fopen($this->acf_path . '/index.php', 'w');
            }

        });


        /*
        * Save
        */
        add_filter('acf/settings/save_json', function($path){

            return $this->acf_path;

        });
        
        
        /*
        * Load
        */
        add_filter('acf/settings/load_json', function($paths){

            // Remove original path
            unset( $paths[0] );

            // Append our new path
            $paths[] = $this->acf_path;

            return $paths;
        });
        
    }
    
}

new ReactWPACFJSON();

?>