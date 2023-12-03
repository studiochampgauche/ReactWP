<?php
/*
* Used if home.php is not set or if you don't set WordPress to use a static front page
*/

get_header();
while(have_posts()) : the_post();

if(scg::field('maintenance_mode')){
    
    $user = wp_get_current_user();
    $roles = $user->ID ? $user->roles : null;
    
    if(!$roles || !in_array('administrator', $roles))
        include scg::source(['path' => 'inc/template-parts/maintenance.php']);
    else
        include scg::source(['path' => 'inc/template-parts/front-page.php']);
    
} else
    include scg::source(['path' => 'inc/template-parts/front-page.php']);

endwhile; get_footer(); ?>