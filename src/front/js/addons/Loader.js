'use strict';
import { gsap } from 'gsap';

const panelElement = document.getElementById('preloader');

let isLoaded = false;

const Loader = {
	init: () => {

		/*
        * Create loading animation
        */
        let tl = gsap.timeline({
            onComplete: () => {

                tl.kill();
                tl = null;

                if(window.gscroll)
                	window.gscroll.paused(false);

            }
        });


        tl
        .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
            scaleY: 1,
            stagger: .065
        })
        .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
            scaleY: .1,
            stagger: .065
        })
        .add(() => {

            /*
            * If medias not loaded, restart
            */
            if(!isLoaded || !window.gscroll){

                tl.restart();

                return;

            }

        })
        .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
            scaleY: 1,
            stagger: .065
        })
        .to(panelElement.querySelectorAll('.contents'), .6, {
            scale: 1.5
        }, '-=.4')
        .to(panelElement, .6, {
            opacity: 0
        }, '-=.6')
        .to(panelElement.querySelectorAll('.contents .bars'), .6, {
            y: 25
        }, '-=.6')
        .add(() => {

            panelElement.remove();

        });

	},
	download: (what = null) => {

		if(!what){

			isLoaded = true;

			return;
		}

		isLoaded = true;


	}
}

export default Loader;