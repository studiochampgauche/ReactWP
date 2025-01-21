<?php

get_header();
while(have_posts()) : the_post();

echo '
<section id="test" style="background: #00ff00; height: 100svh;"></section>
<section style="background: #ff0000; height: 100svh;"></section>
';

endwhile; get_footer();