'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useBlocker, useLocation, useNavigate } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';


const PageTransition = () => {
	

	const firstload = useRef(true);


	const location = useLocation();
	const navigate = useNavigate();
	const blocker = useBlocker(true);

	useEffect(() => {

		if(blocker.state === 'blocked'){

			if([blocker.location.pathname, blocker.location.pathname + '/'].includes(location.pathname)){

				if(blocker.location.hash){

					if(window.gscroll){

						window.gscroll.scrollTo(blocker.location.hash, true, 'top top');

					} else{

						window.scrollTo({top: (blocker.location.hash ? document.querySelector(blocker.location.hash).getBoundingClientRect().top : 0), behavior: 'smooth'});

					}

				}


				return;

			}


			const currentRouteIndex = ROUTES.findIndex(({main}) => main);
			const newRouteIndex = ROUTES.findIndex(({path}) => [blocker.location.pathname, blocker.location.pathname + '/'].includes(path));

			if(currentRouteIndex >= 0)
				ROUTES[currentRouteIndex].main = false;

			if(newRouteIndex >= 0)
				ROUTES[newRouteIndex].main = true;


			window.loader.display = null;

			window.loader.download = window.loader.instance.download();


			let tl = gsap.timeline({
				onComplete: () => {

					tl.kill();
					tl = null;


					/*
					* Return to top
					*/
					if(window.gscroll){

						window.gscroll.paused(true);
						window.gscroll.scrollTop(0);

					} else {

						window.scrollTo({top: 0, behavior: 'instant'});

					}


					/*
					* Kill images
					*/
					document.querySelectorAll('.inner-img')?.forEach(img => {

						if(img.querySelector('img'))
							img.innerHTML = '<div class="img"></div>';

					});


					/*
					* Kill videos
					*/
					document.querySelectorAll('.inner-video')?.forEach(video => {

						if(video.querySelector('video')){

							video.querySelector('video').pause();
							video.querySelector('video').currentTime = 0;


							video.innerHTML = '<div class="video"></div>';
							
						}

					});


					window.loader.download.then(() => blocker.proceed());

				}
			});


			tl
			.to('#viewport', .2, {
				opacity: 0,
				pointerEvents: 'none'
			});

		}

	}, [blocker]);


	useEffect(() => {

		const killEvents = [];


		window.loader.display = window.loader.instance.display();


		window.loader.display.then(() => {

			ScrollTrigger.refresh();

			if(location.hash && document.querySelector(location.hash)){

				if(window.gscroll){

					window.gscroll?.scrollTo(location.hash, false, 'top top');

				} else {

					window.scrollTo({top: (location.hash ? document.querySelector(location.hash).getBoundingClientRect().top : 0), behavior: 'instant'});

				}

			}

		});


		document.querySelectorAll('a')?.forEach(linkElement => {

			const handleClick = (e) => {

				if (!e.target.closest("#app")) {
					
					if(
						e.ctrlKey

						|| e.shiftKey

						|| e.altKey

						|| e.metaKey

						|| !linkElement.hasAttribute('href')

						|| linkElement.hasAttribute('target')
					) return;


					let path = null,
						hash = null;


					try{

						const url = new URL(linkElement.getAttribute('href'));

						if(window.location.host !== url.host) return;

						path = url.pathname;
						hash = url.hash;


					} catch(_){

						const a = linkElement.getAttribute('href').split('#');

						path = a[0];
						hash = (a[1] ? '#' + a[1] : '');

					}

					e.preventDefault();

					navigate(path + hash);

				}

			}

			linkElement.addEventListener('click', handleClick);

			killEvents.push(() => linkElement.removeEventListener('click', handleClick));

		});


		if(firstload.current){

			window.loader.display.then(() => window.loader.isLoaded.app = true);

			firstload.current = false;

			return;

		}


		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				window.gscroll?.paused(false);

			}
		});


		tl
		.to('#viewport', .2, {
			opacity: 1,
			pointerEvents: 'initial'
		});


		return () => killEvents?.forEach(killEvent => killEvent());


	}, [location.pathname]);

	
}

export default PageTransition;