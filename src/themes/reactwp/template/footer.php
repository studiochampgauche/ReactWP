<?php ?>
</div>
<?php if(rwp::field('consent_module')){ ?>
	<div id="consent-panel">
		<div class="inner">
			<div id="consent-box">
				<div class="contents">
					<?= (rwp::field('consent_box_header_title_display') ? '<h2>'. rwp::field('consent_box_header_title_text') .'</h2>' : null); ?>
					<div class="text">
						<?= rwp::field('consent_box_main_text'); ?>
					</div>
					<div class="buttons">
						<button class="btn accept">
							<span><?= rwp::field('consent_box_accept_button_text'); ?></span>
						</button>
						<?php if(rwp::field('consent_box_reject_button_display')){ ?>
							<button class="btn reject">
								<span><?= rwp::field('consent_box_reject_button_text'); ?></span>
							</button>
						<?php } ?>
					</div>
					<?php

						$links = rwp::field('consent_box_links');

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
					<span><?= rwp::field('consent_box_tab_name'); ?></span>
				</div>
			</div>
		</div>
	</div>
<?php } ?>

</div>
<?php wp_footer(); ?>
</body>
</html>