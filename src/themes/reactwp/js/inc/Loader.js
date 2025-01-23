'use strict';
//import React, { useEffect, useState, useRef } from 'react'
import { useLocation } from 'react-router-dom';
import { gsap } from 'gsap';

window.loader = {
	isLoaded: {
		gscroll: false,
		medias: false,
		fonts: false
	}
}

const Loader = {
	el: document.getElementById('loader'),
	perPage: true,
	init: function() {

		return new Promise(done => {

			let tl = gsap.timeline({
				delay: 1,
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

					||

					!window.loader.isLoaded.fonts
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

				/*
				* Fonts
				*/
				if(!window.loader.download){
					document.fonts.ready.then(() => {

						const fonts = Array.from(document.fonts);

						if(!fonts.length){

	                        window.loader.isLoaded.fonts = true;

	                        if(window.loader.isLoaded.medias) done();
	                        
	                        return;
	                    }

	                    let totalFontsLoaded = 0;
	                    const totalFontsToDownloads = fonts.length;


	                    for(let i = 0; i < totalFontsToDownloads; i++){

	                        const font = fonts[i];

	                        if (font.status === 'error'){

	                            throw new Error(`${font.family} can\'t be loaded.`);

	                        } else if(font.status === 'unloaded'){

	                            font
	                            .load()
	                            .then(() => isLoaded())
	                            .catch(() => {

	                                throw new Error(`${font.family} weight ${font.weight} can\'t be loaded.`);

	                            });

	                        } else
	                            isLoaded();


	                        function isLoaded(){

								totalFontsLoaded += 1;

								if(totalFontsLoaded !== totalFontsToDownloads || window.loader.isLoaded.fonts) return;

								ScrollTrigger.refresh();

								window.loader.isLoaded.fonts = true;

								if(window.loader.isLoaded.medias) done();

							}

	                    }

					});
				}


				window.loader.download = null;

				if(!Object.keys(MEDIAS).length){

					window.loader.isLoaded.medias = true;

					if(window.loader.isLoaded.fonts) done();

					return;
				}

				/*
				* Images, videos and audios
				*/
				const mediaGroups = ROUTES.find(({main}) => main).mediaGroups;
				const loaders = mediaGroups && mediaGroups.length ? mediaGroups.split(',') : [];

				const mediasToDownload = !this.perPage ? MEDIAS : (loaders.length ? loaders.reduce((obj, key) => {

					if(!MEDIAS[key]) return obj;

					obj[key] = MEDIAS[key];

					return obj;

				}, {}) : {});

				
				if(!Object.keys(mediasToDownload).length){

					window.loader.isLoaded.medias = true;

					if(window.loader.isLoaded.fonts) done();

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

						if(!media.type || !media.src || media.el){

							loadedTotal += 1;

							if(loadedTotal !== totalToDownloads) return;

							window.loader.isLoaded.medias = true;

							if(window.loader.isLoaded.fonts) done();

							return;

						}


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

							if(window.loader.isLoaded.fonts) done();

						}


					})

				});

			} catch(error){

				reject(error);

			}


		});

	},
	display: function(){

		return new Promise(done => {

			window.loader.display = null;

			window.loader.download?.then(() => {

				const mediaGroups = ROUTES.find(({main}) => main).mediaGroups;
				const loaders = mediaGroups && mediaGroups.length ? mediaGroups.split(',') : [];


				const mediasToDisplay = loaders.length ? Array.from(loaders).reduce((obj, key) => {

					if(!MEDIAS[key]) return obj;

					obj[key] = MEDIAS[key];

					return obj;

				}, {}) : {};

				if(!Object.keys(mediasToDisplay).length){

					done();

					return;
				}


				const inlineDisplays = Object.values(mediasToDisplay).flat(Infinity).filter(({target}) => target);
				const countInlineDisplays = inlineDisplays.length;

				let totalDisplayed = 0;

				inlineDisplays.forEach((media, i) => {

					totalDisplayed += 1;

					let target = null;

                    if(media.target && Array.isArray(media.target)){

                        media.target.forEach(tar => {

                            if(!document.querySelector(tar)) return;

                            target = document.querySelector(tar);

                        });

                    } else if(media.target && !Array.isArray(media.target)) {
                        target = document.querySelector(media.target);
                    }

					if(target){
						target.replaceWith(media.el);
					}

					if(totalDisplayed !== countInlineDisplays) return;

					done();

				});

			});

		});

	}
};


window.loader.instance = Loader;

window.loader.init = window.loader.instance.init();
window.loader.download = window.loader.instance.download();
window.loader.display = window.loader.instance.display();