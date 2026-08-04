<?php

if(!defined('ABSPATH')){
    exit;
}

add_action('wp_head', function(){

	echo '
	<style type="text/css">
        :root{
            color-scheme: light;
            --white: #ffffff;
            --black: #000000;
        }

        *{
            box-sizing: border-box;
        }

        html,
        body{
            margin: 0;
            padding: 0;
        }

        html{
            font-size: 16px;
            scroll-behavior: auto;
            -webkit-font-smoothing: antialiased;
        }

        body{
            background: var(--black);
            min-height: 100vh;
            color: var(--black);
        }

        #loader{
            background: var(--black);
            position: fixed;
            inset: 0;
            z-index: 9999;
            display: grid;
            place-items: center;
            color: var(--white);
        }

        #loader .loader-inner{
            display: grid;
            gap: .55rem;
            text-align: center;
        }

        #loader .loader-kicker{
            letter-spacing: .2em;
            text-transform: uppercase;
            font-size: .72rem;
            opacity: .55;
        }

        #loader .loader-label{
            font-size: clamp(1rem, 2vw, 1.35rem);
            font-weight: 600;
        }

        #viewport{
            min-height: 100vh;
        }

        #pageWrapper,
        #pageContent,
        #app{
            min-height: inherit;
        }
	</style>
	';

}, 3);

/*
* Force the preloader when is static.
* Instead, the site will be displayed directly
*/
//add_filter('rwp_prerender_skip_loader', '__return_false');
