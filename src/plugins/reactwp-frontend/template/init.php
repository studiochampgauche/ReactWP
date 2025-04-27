<?php
/*
* Plugin Name: ReactWP Frontend
* Description: Clean your frontend
* Update URI: false
* Version: 1.0.0
*/


/*
* Remove top bar
*/
add_filter( 'show_admin_bar', '__return_false' );


/*
* Remove weird inline-style added with WordPress 6.7.1
*/
add_filter('wp_img_tag_add_auto_sizes', '__return_false');


/*
* Remove weird speculation script added in WordPress 6.8
*/
add_filter('wp_speculation_rules_configuration', function($configs){

    return null;

});


/*
* Remove superfluous codes in <head></head>
*/
add_action('wp_enqueue_scripts', function(){

    wp_dequeue_style('global-styles');

    wp_dequeue_style('wp-block-library');

    wp_dequeue_style('classic-theme-styles');

});


remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'index_rel_link');
remove_action('wp_head', 'wlwmanifest_link');
remove_action('wp_head', 'start_post_rel_link');
remove_action('wp_head', 'adjacent_posts_rel_link');
remove_action('wp_head', 'rest_output_link_wp_head');
remove_action('wp_head', 'wp_oembed_add_discovery_links');
remove_action('wp_head', 'rel_canonical');
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'wp_resource_hints', 2);
remove_action('wp_head', 'wp_shortlink_wp_head', 10);
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_print_styles', 'print_emoji_styles');
remove_action('template_redirect', 'wp_shortlink_header', 11);