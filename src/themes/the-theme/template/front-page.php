<?php
/*
* Used if you set WordPress to use a static front page.
*/

get_header();
while(have_posts()) : the_post();



endwhile; get_footer(); ?>