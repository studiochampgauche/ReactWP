<?php
/*
* Plugin Name: Core plugins
* Description: ACF + ReactWP
* Author: Studio Champ Gauche
* Author URI: https://champgauche.studio
Version: 1.0.0
*/

if(!defined('ABSPATH')){
    exit;
}

if(!is_blog_installed()) return;

$rwp_acf_files = [
    __DIR__ . '/advanced-custom-fields-pro/acf.php',
    __DIR__ . '/advanced-custom-fields/acf.php',
];

foreach($rwp_acf_files as $rwp_acf_file){
    if(is_readable($rwp_acf_file)){
        require_once $rwp_acf_file;
        break;
    }
}

require_once __DIR__ . '/reactwp/init.php';

add_action('admin_notices', function(){

    if(!current_user_can('manage_options')){
        return;
    }

    if(!function_exists('get_field')){
        echo '<div class="notice notice-error"><p>'
            . esc_html__('ReactWP requires Advanced Custom Fields. Run npm run get:core and choose ACF Free or ACF PRO before editing project fields.', 'reactwp')
            . '</p></div>';
        return;
    }

    if(!function_exists('acf_add_options_page')){
        echo '<div class="notice notice-warning"><p>'
            . esc_html__('ACF Free is active. ReactWP Site settings, Theme settings, repeaters, and other PRO fields require ACF PRO.', 'reactwp')
            . '</p></div>';
    }

});
