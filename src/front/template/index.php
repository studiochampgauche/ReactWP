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


		<?php if(scg::field('consent_module')){ ?>
			<div id="consent-panel">
				<div class="inner">
					<div id="consent-box">
						<div class="contents">
							<?= (scg::field('consent_box_header_title_display') ? '<h2>'. scg::field('consent_box_header_title_text') .'</h2>' : null); ?>
							<div class="text">
								<?= scg::field('consent_box_main_text'); ?>
							</div>
							<div class="buttons">
								<button class="btn accept">
									<span><?= scg::field('consent_box_accept_button_text'); ?></span>
								</button>
								<?php if(scg::field('consent_box_reject_button_display')){ ?>
									<button class="btn reject">
										<span><?= scg::field('consent_box_reject_button_text'); ?></span>
									</button>
								<?php } ?>
							</div>
							<?php

								$links = scg::field('consent_box_links');

								if($links){

									echo '<ul class="links">';

									foreach($links as $link){

										echo '
										<li>
											<a href="'. $link['url'] .'"'. ($link['new_tab'] ? ' target="_blank"' : null) . ($link['page_transition'] ? ' data-transition="true"' : null) .'>'. $link['text'] .'</a>
										</li>
										';

									}

									echo '</ul>';
								}

							?>
						</div>
					</div>
					<div id="consent-button">
						<div class="int">
							<span><?= scg::field('consent_box_tab_name'); ?></span>
						</div>
					</div>
				</div>
			</div>
		<?php } ?>

	</div>
	<?php wp_footer(); ?>
</body>
</html>