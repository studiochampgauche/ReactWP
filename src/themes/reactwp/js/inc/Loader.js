'use strict';
import { gsap } from 'gsap';

window.loader = {
	el: document.getElementById('loader'),
	isLoaded: {
		gscroll: false,
		fonts: true,
		images: true,
		videos: true,
		audios: true
	},
	init: function() {

		return new Promise(done => {

			let tl = gsap.timeline({
				onComplete: () => {

					tl.kill();
					tl = null;

					window.gscroll.paused(false);

				}
			});


			tl
			.to({}, .4, {})
			.add(() => {

				if(
					!this.isLoaded.gscroll

					||

					!this.isLoaded.fonts

					||

					!this.isLoaded.images

					||

					!this.isLoaded.videos

					||

					!this.isLoaded.audios
				){

					tl.restart();

					return;
				}

			})
			.to(this.el, .4, {
				opacity: 0,
				pointerEvents: 'none',
				onComplete: () => done()
			});

		});

	}
};


window.loader.init();