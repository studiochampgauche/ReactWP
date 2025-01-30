'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';


const PageTransition = () => {
	
	const navigate = useNavigate();
	const location = useLocation();

	const pathRef = useRef(null);
	const hashRef = useRef(null);

	const [isEntering, setEntering] = useState(false);
	const [isLeaving, setLeaving] = useState(false);

	useEffect(() => {

		const killEvents = [];

		const elementsToRedirect = document.querySelectorAll('a');

		elementsToRedirect?.forEach((elementToRedirect) => {

			const handleClick = (e) => {

				if(
					e.ctrlKey

					|| e.shiftKey

					|| e.altKey

					|| e.metaKey

					|| !elementToRedirect.hasAttribute('href')

					|| (elementToRedirect.hasAttribute('target') && elementToRedirect.getAttribute('target') === '_self')
				) return;

				pathRef.current = elementToRedirect.getAttribute('href');


				try{

					const url = new URL(pathRef.current);

					if(window.location.host !== url.host) return;

					pathRef.current = url.pathname;
					hashRef.current = url.hash;

				} catch(_){

					const a = pathRef.current.split('#');

					pathRef.current = a[0];
					hashRef.current = a[1] || null;

				}

				e.preventDefault();

				if(![pathRef.current, pathRef.current + '/'].includes(location.pathname))
					setLeaving(true);
				else if(hashRef.current)
					window.gscroll.scrollTo(document.getElementById(hashRef.current), true, 'top top');

			}

			elementToRedirect.addEventListener('click', handleClick);

			killEvents.push(() => elementToRedirect.removeEventListener('click', handleClick));

		});

		if(pathRef.current){

			window.loader.display = window.loader.instance.display();
			window.loader.display.then(() => setEntering(true));

		} else {

			window.loader.display = window.loader.instance.display();

			window.loader.display.then(() => window.loader.isLoaded.app = true);
			
		}


		return () => killEvents?.forEach(killEvent => killEvent());


	}, [location.pathname]);


	/*
	* When Leaving
	*/
	useEffect(() => {

		if(!isLeaving) return;

		const currentRouteIndex = ROUTES.findIndex(({main}) => main);
		const newRouteIndex = ROUTES.findIndex(({path}) => path === pathRef.current);

		if(currentRouteIndex >= 0)
			ROUTES[currentRouteIndex].main = false;

		if(newRouteIndex >= 0)
			ROUTES[newRouteIndex].main = true;


		window.loader.download = null;
		window.loader.display = null;

		window.loader.download = window.loader.instance.download();



		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;


				window.gscroll.paused(true);
				window.gscroll.scrollTop(0);

				window.loader.download.then(() => {

					navigate(pathRef.current);

					setLeaving(false);

				});

			}
		});


		tl
		.to('#viewport', .2, {
			opacity: 0,
			pointerEvents: 'none'
		});


	}, [isLeaving]);


	/*
	* When Entering
	*/
	useEffect(() => {

		if(!isEntering) return;

		ScrollTrigger.refresh();

		if(hashRef.current){

			window.gscroll.scrollTo(document.getElementById(hashRef.current), false, 'top top');

		}


		pathRef.current = null
		hashRef.current = null;


		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				window.gscroll.paused(false);
				setEntering(false);

			}
		});


		tl
		.to('#viewport', .2, {
			opacity: 1,
			pointerEvents: 'initial'
		});


	}, [isEntering]);

	
}

export default PageTransition;