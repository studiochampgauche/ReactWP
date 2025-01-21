<?php

get_header();
while(have_posts()) : the_post();

echo '<rwp-loader data-value="group1" />';
echo '<section style="background: #00ff00; height: 100svh;"></section>';
echo '<section style="background: #ff0000; height: 100svh;"></section>';

endwhile; get_footer();