<?php
/*
Plugin Name: Champ Gauche Admin Styles & Scripts
Author: Studio Champ Gauche
Author URI: https://champgauche.studio
Description: A plugin that enqueue style and script files in the admin.
Requires at least: 6.4.1
Requires PHP: 8.2
Version: 1.0.0
Text Domain: scg-admin-appearance
Domain Path: /langs
*/


/*
* Make sure you have all you need for the plugin
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';


if(!defined('ABSPATH') || !is_plugin_active('advanced-custom-fields-pro/acf.php') || !is_plugin_active('scg-core/core.php')) return;


/*
* Load Languages
*/
load_plugin_textdomain('scg-admin-appearance', false, basename(__DIR__) . '/langs/');


class StudioChampGaucheAdminStyleScript{
    
    function __construct(){
        
        add_action('admin_enqueue_scripts', function(){

            /*
            * Add Style
            */
            wp_enqueue_style('scg-admin-style-script', plugin_dir_url('scg-admin-style-script/core.php') . 'assets/css/main.min.css', null, null, false);


            /*
            * Add Javascript
            */
            wp_enqueue_script('scg-admin-style-script', plugin_dir_url('scg-admin-style-script/core.php') . 'assets/js/main.min.js', null, null, true);

        });
        
    }
    
}

new StudioChampGaucheAdminStyleScript();

?>