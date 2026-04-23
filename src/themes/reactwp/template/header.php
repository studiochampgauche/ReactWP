<?php ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<noscript>You need to enable JavaScript to run this app.</noscript>

	<script id="reactwp-bootstrap" type="application/json"><?php echo \ReactWP\Runtime\Bootstrap::json(); ?></script>

	<div id="loader" aria-hidden="true">
		<div class="loader-inner">
			<span class="loader-kicker">ReactWP 3</span>
			<span class="loader-label">Bootstrapping the route</span>
		</div>
	</div>

	<div id="app-header"></div>
	
	<div id="viewport">
		<div id="pageWrapper">
			<div id="pageContent">
				<main id="app">
