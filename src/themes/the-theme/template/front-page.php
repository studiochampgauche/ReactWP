<?php
/*
* Used if you set WordPress to use a static front page.
*/

get_header();
while(have_posts()) : the_post();

if(scg::field('maintenance_mode')){
    
    $user = wp_get_current_user();
    $roles = $user->ID ? $user->roles : null;
    
    if(!$roles || !in_array('administrator', $roles))
        include scg::source(['path' => 'inc/template-parts/maintenance.php', 'url' => false]);
    else
        include scg::source(['path' => 'inc/template-parts/front-page.php', 'url' => false]);
    
} else
    include scg::source(['path' => 'inc/template-parts/front-page.php', 'url' => false]);

endwhile; get_footer(); ?>