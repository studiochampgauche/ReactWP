<?php
/*
* Plugin Name: ReactWP Consent
* Description: Add a consent box
* Update URI: false
* Version: 1.0.0
*/


namespace ReactWP\Consent;

require_once 'inc/render.php';

class Consent{
    
    function __construct(){
        
        /*
        * Enqueue styles & scripts
        */
        add_action('wp_enqueue_scripts', function(){
            
            /*
            * CSS
            */
            wp_enqueue_style('rwp-consent', plugin_dir_url('reactwp-consent/init.php') . 'assets/css/reactwp-consent.min.css', null, null, null);


            /*
            * JS
            */
            wp_enqueue_script('rwp-consent', plugin_dir_url('reactwp-consent/init.php') . 'assets/js/reactwp-consent.min.js', null, null, false);


            /*
            * Data
            */
            $consentData = \rwp::field('consent_box');
            wp_localize_script('rwp-consent', 'RWP_CONSENT_BOX', $consentData);


            /*
            * Remove rwp-main script type attribute
            */
            add_filter('script_loader_tag', function($tag, $handle, $src){
                if($handle !== 'rwp-consent')
                    return $tag;

                $tag = '<script src="' . esc_url( $src ) . '" defer></script>';

                return $tag;

            } , 10, 3);

        }, 11);
        
    }
    
}
new Consent();