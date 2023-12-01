<?php
/*
* Used if home.php is not set or if you don't set WordPress to use a static front page
*/

get_header();
while(have_posts()) : the_post();



endwhile; get_footer(); ?>