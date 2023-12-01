<?php ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php wp_head(); ?>
</head>

<body data-base-url="<?= home_url(); ?>" data-barba="wrapper">

	<div id="viewport">

		<header></header>
		
		<div id="pageWrapper">
			<div id="pageContent">

				<main data-barba="container" data-barba-namespace="<?= get_post_field('post_name'); ?>">