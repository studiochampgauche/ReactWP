	<footer></footer>
</main>

</div>
</div>
</div>
<?php
wp_footer();

if(scg::field('html_tags_before_close_body'))
	echo scg::field('html_tags_before_close_body');

?>
</body>
</html>