<?php ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<?php
        
        echo scg::field('quick_html_after_head');
    
        wp_head();
    
        echo scg::field('quick_html_before_head');
    
    ?>
</head>

<body data-base-url="<?= home_url(); ?>" data-barba="wrapper">
    
    <?= scg::field('quick_html_after_body'); ?>
    
	<div id="viewport">

		<header></header>
		
		<div id="pageWrapper">
			<div id="pageContent">

				<main data-barba="container" data-barba-namespace="<?= get_post_field('post_name'); ?>">