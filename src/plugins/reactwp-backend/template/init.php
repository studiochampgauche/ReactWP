<?php
/*
* Plugin Name: ReactWP Backend
* Description: Clean your admin panel
* Update URI: false
* Version: 1.0.0
*/


/*
* Remove Gutenberg
*/
add_filter('use_block_editor_for_post_type', '__return_false', 10);


/*
* Shot events on admin_init action
*/
add_action('admin_init', function(){

    /*
    * Remove editor
    */
    remove_post_type_support('post', 'editor');
    remove_post_type_support('page', 'editor');


    /*
    * Clean Dashboard
    */
    remove_action('welcome_panel', 'wp_welcome_panel');
    remove_meta_box('dashboard_incoming_links', 'dashboard', 'normal');
    remove_meta_box('dashboard_plugins', 'dashboard', 'normal');
    remove_meta_box('dashboard_secondary', 'dashboard', 'normal');
    remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
    remove_meta_box('dashboard_right_now', 'dashboard', 'normal');
    remove_meta_box('dashboard_activity', 'dashboard', 'normal');
    remove_meta_box('dashboard_site_health', 'dashboard', 'normal');
    remove_meta_box('dashboard_primary', 'dashboard', 'side');
    remove_meta_box('dashboard_quick_press', 'dashboard', 'side');
    remove_meta_box('dashboard_recent_drafts', 'dashboard', 'side');

});


/*
* Shot events on admin_menu action
*/
add_action('admin_menu', function(){

    /*
    * Remove admin side menu items
    */
    remove_menu_page('tools.php');
    remove_menu_page('upload.php');
    remove_menu_page('themes.php');
    remove_menu_page('plugins.php');
    remove_menu_page('edit-comments.php');
    remove_menu_page('users.php');
    remove_menu_page('edit.php?post_type=acf-field-group');

    remove_submenu_page('options-general.php', 'options-privacy.php');
    remove_submenu_page('options-general.php', 'options-media.php');
    remove_submenu_page('options-general.php', 'options-writing.php');
    remove_submenu_page('options-general.php', 'options-discussion.php');


});


/*
* Shot events on admin_bar_menu action
*/
add_action('admin_bar_menu', function(){

    global $wp_admin_bar;


    /*
    * Remove some items
    */
    $wp_admin_bar->remove_node('wp-logo');
    $wp_admin_bar->remove_node('site-name');
    $wp_admin_bar->remove_node('comments');
    $wp_admin_bar->remove_node('new-content');


    if(
        !current_user_can('update_core')
        
        ||
        
        !current_user_can('update_plugins')
        
        ||
        
        !current_user_can('update_themes')
        
    ) {

        $wp_admin_bar->remove_node( 'updates' );

    }


    /*
    * Add items
    */
    $admin_url = admin_url();

    // Home URL
    $args = [
        'id' => 'is-website',
        'title' => get_bloginfo('name'),
        'href' => '/',
        'target' => '_blank',
        'meta' => [
            'class' => 'is-website'
        ]
    ];
    $wp_admin_bar->add_node($args);


    // Menus
    $args = array(
        'id' => 'is-menus',
        'title' => __('Menus', 'rwp-core'),
        'href' => $admin_url . 'nav-menus.php',
        'meta' => array(
            'class' => 'is-menus'
        )
    );
    if(current_user_can('edit_theme_options') && rwp::field('theme_locations'))
        $wp_admin_bar->add_node($args);


    // Files
    $args = array(
        'id' => 'is-files',
        'title' => __('Images & files', 'rwp-core'),
        'href' => $admin_url . 'upload.php',
        'meta' => array(
            'class' => 'is-files'
        )
    );
    if(current_user_can('upload_files'))
        $wp_admin_bar->add_node($args);


    // Users list and personal profile
    if(current_user_can('list_users')){
                    
        $args = array(
            'id' => 'is-users-list',
            'title' => __('Users', 'rwp-core'),
            'href' => $admin_url . 'users.php',
            'meta' => array(
                'class' => 'is-users-list'
            )
        );
        $wp_admin_bar->add_node($args);

        
        $args = array(
            'id' => 'is-users-profile',
            'title' => __('Profile', 'rwp-core'),
            'href' => $admin_url . 'profile.php',
            'parent' => 'is-users-list',
            'meta' => array(
                'class' => 'is-users-profile'
            )
        );
        $wp_admin_bar->add_node($args);
        
    } else {
        
        $args = array(
            'id' => 'is-users-profile',
            'title' => __('Profile', 'rwp-core'),
            'href' => $admin_url . 'profile.php',
            'meta' => array(
                'class' => 'is-users-profile'
            )
        );
        $wp_admin_bar->add_node($args);
        
    }


    // ReactWP Tab
    if(current_user_can('edit_theme_options')){

        $args = array(
            'id' => 'is-site',
            'title' => __('ReactWP', 'rwp-core'),
            'meta' => array(
                'class' => 'is-site'
            )
        );
        $wp_admin_bar->add_node($args);
        
        
        // Site settings
        $args = array(
            'id' => 'is-site-settings',
            'title' => __('Site settings', 'rwp-core'),
            'href' => $admin_url . 'admin.php?page=site-settings',
            'parent' => 'is-site',
            'meta' => array(
                'class' => 'is-site-settings'
            )
        );
        $wp_admin_bar->add_node($args);
        
        
        // Themes Management
        if(current_user_can('switch_themes')){
            $args = array(
                'id' => 'is-site-themes',
                'title' => __('Themes', 'rwp-core'),
                'href' => $admin_url . 'themes.php',
                'parent' => 'is-site',
                'meta' => array(
                    'class' => 'is-site-themes'
                )
            );
            $wp_admin_bar->add_node($args);



            // Edit Themes
            $args = array(
                'id' => 'is-site-themes-editor',
                'title' => __('Editor', 'rwp-core'),
                'href' => $admin_url . 'theme-editor.php',
                'parent' => 'is-site-themes',
                'meta' => array(
                    'class' => 'is-site-themes-editor'
                )
            );
            if(current_user_can('edit_themes'))
                $wp_admin_bar->add_node($args);
            
        }
        
        
        // Plugins Management
        if(current_user_can('activate_plugins')){
            
            $args = array(
                'id' => 'is-site-plugins',
                'title' => __('Plugins', 'rwp-core'),
                'href' => $admin_url . 'plugins.php',
                'parent' => 'is-site',
                'meta' => array(
                    'class' => 'is-site-plugins'
                )
            );
            $wp_admin_bar->add_node($args);
            
            
            // Edit Plugins
            $args = array(
                'id' => 'is-site-plugin-editor',
                'title' => __('Editor', 'rwp-core'),
                'href' => $admin_url . 'plugin-editor.php',
                'parent' => 'is-site-plugins',
                'meta' => array(
                    'class' => 'is-site-plugins-editor'
                )
            );
            if(current_user_can('edit_plugins'))
                $wp_admin_bar->add_node($args);
            
            
            // ACF PRO Management
            $args = array(
                'id' => 'is-acf',
                'title' => __('ACF', 'rwp-core'),
                'href' => $admin_url . 'edit.php?post_type=acf-field-group',
                'parent' => 'is-site',
                'meta' => array(
                    'class' => 'is-acf'
                )
            );
            $wp_admin_bar->add_node($args);
            
            
            // Import Management
            $args = array(
                'id' => 'is-site-import',
                'title' => __('Import', 'rwp-core'),
                'href' => $admin_url . 'import.php',
                'parent' => 'is-site',
                'meta' => array(
                    'class' => 'is-site-import'
                )
            );
            if(current_user_can('import'))
                $wp_admin_bar->add_node($args);
            
            
            // Export Management
            $args = array(
                'id' => 'is-site-export',
                'title' => __('Export', 'rwp-core'),
                'href' => $admin_url . 'export.php',
                'parent' => 'is-site',
                'meta' => array(
                    'class' => 'is-site-export'
                )
            );
            if(current_user_can('export'))
                $wp_admin_bar->add_node($args);

        }
        
    }

}, 99);


/*
* Remove Customize Elements
*/
add_action('customize_register', function($wp_customize){

    $wp_customize->remove_panel('themes');
    $wp_customize->remove_panel('nav_menus');
    $wp_customize->remove_section('title_tagline');
    $wp_customize->remove_section('static_front_page');
    $wp_customize->remove_section('custom_css');
    $wp_customize->remove_section('colors');

}, 20);


/*
* Add some styles
*/
add_action('admin_head', function(){
        
    echo '<style type="text/css">#toplevel_page_site-settings,.single-theme .theme-actions{display: none !important;}</style>';
    
});