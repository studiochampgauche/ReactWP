<?php
/*
Plugin Name: Admin Styles & Scripts
Author: Studio Champ Gauche
Author URI: https://champgauche.studio
Description: A plugin that enqueue style and script files for admin.
Requires at least: 6.4.1
Requires PHP: 8.2
Version: 1.0.0
Text Domain: reactwp-admin-style-script
*/



/*
* Check ABSPATH
*/
if(!defined('ABSPATH')) return;


/*
* Make sure you have all you need for the plugin
*/
require_once ABSPATH . 'wp-admin/includes/plugin.php';


class ReactWPAdminStyleScript{
    
    function __construct(){
        
        add_action('admin_enqueue_scripts', function(){

            /*
            * Add Style
            */
            wp_enqueue_style('reactwp-admin-style-script', plugin_dir_url('reactwp-admin-style-script/core.php') . 'assets/css/main.min.css', null, null, false);


            /*
            * Add Javascript
            */
            wp_enqueue_script('reactwp-admin-style-script', plugin_dir_url('reactwp-admin-style-script/core.php') . 'assets/js/main.min.js', null, null, true);

        });
        
    }
    
}

new ReactWPAdminStyleScript();

?>