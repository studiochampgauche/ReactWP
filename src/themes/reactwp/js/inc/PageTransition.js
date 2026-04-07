'use strict';
import React, { useContext, useEffect, useLayoutEffect, useState, useRef } from 'react'
import { useBlocker, useLocation, useNavigate } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { RouteContext } from '../App';
import Loader from './Loader';
import RWPCache from './Cache';
import PageTransitionAnimation from './PageTransitionAnimation';


const PageTransition = () => {
	
	const location = useLocation();
	const navigate = useNavigate();
	const blocker = useBlocker(true);

	const [ firstLoad, firstLoadSet] = useState(true);

	const { currentRoute, setCurrentRoute } = useContext(RouteContext);


	useLayoutEffect(() => {
		if(!currentRoute) return;

		window.loader.display = Loader.display();
		window.loader.noCriticalDisplay = Loader.noCriticalDisplay();
	}, [currentRoute?.path]);

	useEffect(() => {

		if(blocker.state === 'blocked'){

			if([blocker.location.pathname, blocker.location.pathname + '/'].includes(location.pathname)){

				if(blocker.location.hash){

					if(window.gscroll){

						window.gscroll.scrollTo(blocker.location.hash, true, 'top top');

					} else{

						window.scrollTo({top: (blocker.location.hash ? (document.querySelector(blocker.location.hash).getBoundingClientRect().top + window.scrollY) : 0), behavior: 'smooth'});

					}

				}


				return;

			}



			const blockerAction = async () => {

				const newRouteData = await RWPCache.json(`${SYSTEM.restUrl}reactwp/v1/route?view=${encodeURIComponent(blocker.location.pathname)}`);

				Loader.setRoute(newRouteData);
				window.loader.download = Loader.download();


				let animation = PageTransitionAnimation.leave({
					blocker,
					location
				});

				const previousOnComplete = animation?.eventCallback
					? animation.eventCallback('onComplete')
					: null;

				animation?.eventCallback?.('onComplete', () => {
					previousOnComplete?.();

					animation.kill();
					animation = null;

					if(window.gscroll){
						window.gscroll.paused(true);
						window.gscroll.scrollTop(0);
					} else {
						window.scrollTo({ top: 0, behavior: 'instant' });
					}

					document.querySelectorAll('.inner-img')?.forEach((img) => {
						if(img.querySelector('img')){
							img.innerHTML = '<div class="img"></div>';
						}
					});

					document.querySelectorAll('.inner-video')?.forEach((video) => {
						const videoEl = video.querySelector('video');

						if(videoEl){
							videoEl.pause();
							videoEl.currentTime = 0;
							video.innerHTML = '<div class="video"></div>';
						}
					});

					document.querySelectorAll('.inner-audio')?.forEach((audio) => {
						const audioEl = audio.querySelector('audio');

						if(audioEl){
							audioEl.pause();
							audioEl.currentTime = 0;
							audio.innerHTML = '<div class="audio"></div>';
						}
					});


					
					setCurrentRoute(newRouteData);

					window.loader.download.then(() => blocker.proceed());
				});

			}
			blockerAction();

		}

	}, [blocker]);

	useEffect(() => {

		let killEvents = [];


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


		if(firstLoad){

			firstLoadSet(false);

			return;

		}

		window.loader.display.then(() => {

			let animation = PageTransitionAnimation.enter({
				location
			});

			const previousOnComplete = animation?.eventCallback
				? animation.eventCallback('onComplete')
				: null;

			animation?.eventCallback?.('onComplete', () => {
				previousOnComplete?.();

				animation.kill();
				animation = null;

				ScrollTrigger?.refresh();

				requestAnimationFrame(() => {
					window.gscroll?.paused(false);
				});
			});

		});


		return () => {

			killEvents?.forEach(killEvent => killEvent());
			killEvents = null;

		}


	}, [location.pathname]);
	
}

export default PageTransition;