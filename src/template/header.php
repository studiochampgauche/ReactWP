<?php ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php
		if(scg::field('html_tags_after_open_head'))
			echo scg::field('html_tags_after_open_head');
		

		wp_head();


		if(scg::field('html_tags_before_close_head'))
			echo scg::field('html_tags_before_close_head');
	?>
</head>

<body data-base-url="<?= home_url(); ?>" data-barba="wrapper">

	<?php
		if(scg::field('html_tags_after_open_body'))
			echo scg::field('html_tags_after_open_body');
	?>

	<div id="viewport">

		<header></header>
		
		<div id="pageWrapper">
			<div id="pageContent">

				<main data-barba="container" data-barba-namespace="<?= get_post_field('post_name'); ?>">