'use strict';
import { gsap } from 'gsap';

const Loader = {
	el: document.getElementById('loader'),
	perPage: false,
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
					!window.loader.isLoaded.gscroll

					||

					!window.loader.isLoaded.medias
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

	},
	download: function(){

		return new Promise((done, reject) => {

			try{
				const loaders = document.querySelectorAll('rwp-loader');

				const mediasToDownload = !this.perPage ? MEDIAS : (loaders.length ? Array.from(loaders).reduce((obj, element) => {

					const keys = element.getAttribute('data-value').replace(', ', ',').split(',');

					keys.forEach(key => {

						obj[key] = MEDIAS[key];

					});

					return obj;

				}, {}) : null);
				
				if(!mediasToDownload){

					window.loader.isLoaded.medias = true;

					done();

					return;

				}

				const inlineDownloads = Object.values(mediasToDownload).flat(Infinity);

				const totalToDownloads = inlineDownloads.length;


				const mediaTypes = {
	                video: () => document.createElement('video'),
	                audio: () => new Audio(),
	                image: () => new Image()
	            };

	            let loadedTotal = 0;

				Object.keys(mediasToDownload).forEach((group, i) => {

					const medias = mediasToDownload[group];


					medias.forEach((media, j) => {

						if(!media.type || !media.src || media.el) return;


						let mediaElement = mediaTypes[media.type];

						if(!mediaElement) throw new Error(`${media.type} isn't supported.`);

						mediaElement = mediaElement();

						mediaElement.src = media.src;


						if(media.type === 'image'){

							mediaElement.onload = () => isLoaded();

						} else {

							mediaElement.onloadeddata = () => isLoaded();

						}


						function isLoaded(){

							loadedTotal += 1;

							MEDIAS[group][j].el = mediaElement;

							if(loadedTotal !== totalToDownloads) return;

							window.loader.isLoaded.medias = true;

							done();

						}


					})

				});

			} catch(error){

				reject(error);

			}


		});

	},
	display: function(){



	}
};


window.loader = {
	init: Loader.init(),
	download: Loader.download(),
	isLoaded: {
		gscroll: false,
		medias: false
	}
}