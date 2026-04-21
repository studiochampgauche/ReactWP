'use strict';
import React, { useContext, useEffect, useLayoutEffect, useState, useRef } from 'react'
import { useBlocker, useLocation, useNavigate } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { RouteContext } from '../App';
import Loader from './Loader';
import RWPCache from './Cache';
import PageTransitionAnimation from './PageTransitionAnimation';

const normalizePath = (path = '/') => {
	const normalized = `/${String(path || '').replace(/^\/+|\/+$/g, '')}/`;

	return normalized === '//' ? '/' : normalized;
};


const PageTransition = () => {
	
	const location = useLocation();
	const navigate = useNavigate();
	const blocker = useBlocker(true);

	const pendingRouteRef = useRef(null);

	const [ firstLoad, firstLoadSet] = useState(true);

	const { currentRoute, setCurrentRoute } = useContext(RouteContext);

	const currentPath = normalizePath(location.pathname);


	useLayoutEffect(() => {
		if(!currentRoute) return;

		window.loader.display = Loader.display();
		window.loader.noCriticalDisplay = Loader.noCriticalDisplay();
		
	}, [currentRoute?.path]);

	useLayoutEffect(() => {
		const pendingRoute = pendingRouteRef.current;

		if(!pendingRoute || normalizePath(pendingRoute.path) !== currentPath){
			return;
		}

		setCurrentRoute({
			...pendingRoute,
			path: normalizePath(pendingRoute.path)
		});

		pendingRouteRef.current = null;
	}, [currentPath, setCurrentRoute]);

	useEffect(() => {

		if(blocker.state === 'blocked'){

			const blockerPath = normalizePath(blocker.location.pathname);

			if(blockerPath === currentPath){

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

				const newRouteData = await RWPCache.json(`${SYSTEM.restUrl}reactwp/v1/route?view=${encodeURIComponent(blockerPath)}`);

				if(!newRouteData){
					blocker.proceed();
					return;
				}

				const normalizedRouteData = {
					...newRouteData,
					path: normalizePath(newRouteData.path || blockerPath)
				};

				pendingRouteRef.current = normalizedRouteData;
				Loader.setRoute(normalizedRouteData);
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


					
					//setCurrentRoute(newRouteData);

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


		return () => {

			killEvents?.forEach(killEvent => killEvent());
			killEvents = null;

		}


	}, [location.pathname]);

	useEffect(() => {

		if(!currentRoute?.path || normalizePath(currentRoute.path) !== currentPath){
			return;
		}

		if(firstLoad){

			firstLoadSet(false);

			return;

		}

		const displayPromise = window.loader.display;
		let cancelled = false;

		Promise.resolve(displayPromise).then(() => {

			if(cancelled){
				return;
			}

			requestAnimationFrame(() => {

				if(cancelled){
					return;
				}

				if(window.gscroll){
					window.gscroll.scrollTop(0);
				} else {
					window.scrollTo({ top: 0, behavior: 'instant' });
				}

				ScrollTrigger?.refresh();

				requestAnimationFrame(() => {

					if(cancelled){
						return;
					}

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

							requestAnimationFrame(() => {
								if(cancelled){
									return;
								}

								window.gscroll?.paused(false);
							});
						});

				});

			});

		});


		return () => {
			cancelled = true;

		}


	}, [currentRoute?.path, currentPath]);
	
}

export default PageTransition;