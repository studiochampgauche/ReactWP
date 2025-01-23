<?php

get_header();
while(have_posts()) : the_post();

echo '
<section style="background: #00ff00; height: 100lvh;">
	<span>Hello World!</span>
</section>
';

endwhile; get_footer();