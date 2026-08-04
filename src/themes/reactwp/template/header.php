<?php
if(!defined('ABSPATH')){
	exit;
}

$reactwp_bootstrap = \ReactWP\Runtime\Bootstrap::payload();
$reactwp_initial_render = \ReactWP\Runtime\InitialRender::resolve($reactwp_bootstrap);
\ReactWP\Runtime\TemplateAssets::enqueue($reactwp_bootstrap['route'], $reactwp_initial_render['source']);
$reactwp_skip_loader = $reactwp_initial_render['source'] !== 'client'
	&& apply_filters('rwp_prerender_skip_loader', true, $reactwp_bootstrap['route'], $reactwp_initial_render);
$reactwp_bootstrap['system']['initialRender'] = [
	'requestedMode' => $reactwp_bootstrap['route']['render']['mode'] ?? 'client',
	'source' => $reactwp_initial_render['source'],
	'key' => $reactwp_initial_render['key'],
];
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
	<?php wp_body_open(); ?>
	<noscript>You need to enable JavaScript to run this app.</noscript>

	<script id="reactwp-bootstrap" type="application/json"><?php echo \ReactWP\Runtime\Bootstrap::json($reactwp_bootstrap); ?></script>

	<div id="loader" aria-hidden="true"<?php echo $reactwp_skip_loader ? ' hidden style="display:none"' : ''; ?>>
		<div class="loader-inner">
			<span class="loader-kicker">ReactWP 3</span>
			<span class="loader-label">Bootstrapping the route</span>
		</div>
	</div>

	<div id="app-header"></div>
	
	<div id="viewport">
		<div id="pageWrapper">
			<div id="pageContent">
				<main
					id="app"
					data-rwp-render="<?php echo esc_attr($reactwp_initial_render['source']); ?>"
					data-rwp-render-key="<?php echo esc_attr($reactwp_initial_render['key']); ?>"
				><?php echo $reactwp_initial_render['html']; ?>
