<?php ?>
<!DOCTYPE html>
<?php

	$url = (isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . '/admin' . $_SERVER['REQUEST_URI'];

	
	$ch = curl_init();

	
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	
	$response = curl_exec($ch);

	
	if(curl_errno($ch)){

		http_response_code(500);

	    echo '
			<html lang="fr-CA">
			<head></head>
		';

	} else {
	    
	    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    http_response_code($httpCode);

	    $dom = new DOMDocument;

		libxml_use_internal_errors(true);
		$dom->loadHTML($response);
		libxml_clear_errors();


		$html = $dom->getElementsByTagName('html')->item(0);
		$head = $dom->getElementsByTagName('head')->item(0);

		if($html && $head){

			$newHtml = $dom->createElement('html');

		    foreach ($html->attributes as $attr) {
		        $newHtml->setAttribute($attr->nodeName, $attr->nodeValue);
		    }

			echo '<head>';

				echo $dom->saveXML($newHtml);

				echo $dom->saveHTML($head) . PHP_EOL;

				echo '
				<style type="text/css">
					:root{
					    --white-color: #fff;
					    --black-color: #000;
					}

					*{
						outline: 0;
						scrollbar-width: none;
						box-sizing: border-box;
						-ms-overflow-style: none;
						-webkit-font-smoothing: antialiased;
						&::-webkit-scrollbar {
					        display: none;
						}
					}

					html,
					body{
					    margin: 0;
					    padding: 0;
					}

					html{
						font-size: 16px;
					}

					body{
						max-height: 100lvh;
						overflow: hidden;
					}

					#preloader{
						background: var(--black-color);
						position: fixed;
						display: flex;
						top: 0;
						left: 0;
						width: 100%;
						height: 100lvh;
						align-items: center;
						justify-content: center;
						will-change: opacity;
						z-index: 101;
						.contents{
							span{
								display: table;
								margin: 0 auto;
								font-size: 24px;
								white-space: nowrap;
								text-transform: uppercase;
								color: var(--white-color);
							}
							.bars{
								display: flex;
								width: 30px;
								height: 30px;
								margin: 25px auto 0;
								align-items: flex-start;
								justify-content: space-between;
								will-change: transform;
								.bar{
									background: var(--white-color);
									width: 5px;
									height: 100%;
									transform: scaleY(.1);
									transform-origin: left top;
									will-change: transform;
									//border-radius: 10px;
								}
							}
						}
					}

					#viewport{
						height: 100%;
			    		min-height: 100lvh;
					}

				</style>
				<link id="mainStyle" rel="stylesheet" href="/assets/css/main.min.css" type="text/css" media="screen">
				';
			echo '</head>';

		} else {

			echo '
				<html lang="fr-CA">
				<head></head>
			';

		}

	}

	// Fermeture de la session cURL
	curl_close($ch);
?>
<body>
	<noscript>You need to enable JavaScript to run this app.</noscript>
	<div id="viewport">
		<div id="preloader">
			<div class="contents">
				<span>One moment</span>
				<div class="bars">
					<div class="bar"></div>
					<div class="bar"></div>
					<div class="bar"></div>
				</div>
			</div>
		</div>
		<div id="app"></div>
	</div>
	<script src="/assets/js/main.min.js"></script>
</body>
</html>