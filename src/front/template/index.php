<?php

	require_once 'admin/wp-load.php';

	$url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/admin' . $_SERVER['REQUEST_URI'];

	global $wp;
	$wp->parse_request($url);

	$wp->main();

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
</head>
<body>
	<noscript>You need to enable JavaScript to run this app.</noscript>
	<div id="viewport">
		<div id="preloader">
			<div class="contents">
				<span>Hello world</span>
				<div class="gbar">
					<div class="line"></div>
				</div>
			</div>
		</div>
		<div id="app"></div>
	</div>
	<?php wp_footer(); ?>
</body>
</html>