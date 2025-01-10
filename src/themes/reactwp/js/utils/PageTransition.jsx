'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Cache from './Cache';


const PageTransition = ({ children }) => {


	const ref = useRef(null);
	const hrefRef = useRef(true);
	const firstLoadRef = useRef(true);
	const currentPathRef = useRef(true);


	const [isLeaving, setLeaving] = useState(false);
	const [isEntering, setEntering] = useState(false);
	const [isMiddle, setMiddle] = useState(false);


	const navigate = useNavigate();
	const location = useLocation();

	currentPathRef.current = location.pathname;



	/*
	* When you leave the page
	*/
	useEffect(() => {

		if(!isLeaving) return;


		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				navigate(hrefRef.current);

				window.gscroll?.paused(true);
				window.gscroll?.scrollTop(0) || window.scrollTo(0, 0);


				gsap.delayedCall(.01, () => {

					setLeaving(false);
					setMiddle(true);

				});

			}
		});


		tl
		.to(ref.current, .2, {
			opacity: 0,
			pointerEvents: 'none'
		});
		

	}, [isLeaving]);


	/*
	* When you enter in a page
	*/
	useEffect(() => {

		if(!isEntering) return;

		ScrollTrigger?.refresh();


		if(location.hash){

			window.gscroll ? window.gscroll.scrollTo(document.querySelector(location.hash), false, 'top top') : document.querySelector(location.hash).scrollIntoView({behavior: 'instant'});

			ScrollTrigger?.refresh();

		}
		
		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				window.gscroll?.paused(false);

				setEntering(false);

			}
		});


		tl
		.to(ref.current, .2, {
			opacity: 1,
			pointerEvents: 'initial'
		});


	}, [isEntering]);



	/*
	* Between isLeaving and isEntering
	*/
	useEffect(() => {

		if(!isMiddle) {


			const elementsToRedirect = document.querySelectorAll('a');

			elementsToRedirect?.forEach(elementToRedirect => {

				const handleClick = (e) => {

					hrefRef.current = elementToRedirect.hasAttribute('href') ? elementToRedirect.getAttribute('href') : false;


					if(e.ctrlKey || e.shiftKey || e.altKey || e.metaKey || elementToRedirect.hasAttribute('target')) return;


					let path = null,
        			anchor = null;

	        		try{

	        			const url = new URL(href);

	        			path = url.pathname;

	        			if(url.hash)
	        				anchor = url.hash;


	        			if(window.location.host !== url.host) return;

	        		} catch(_){


	        			if(hrefRef.current.includes('#'))
	        				[path, anchor] = hrefRef.current.split('#');
	        			else
	        				path = hrefRef.current;

	        		}


					e.preventDefault();


					if(!hrefRef.current) return;

					
					if(path !== location.pathname){

	        			setLeaving(true);

	        		} else if(currentPathRef.current === path && anchor){

	        			anchor = anchor.replace('#', '');

	        			window.gscroll ? window.gscroll.scrollTo(document.getElementById(anchor), true, 'top top') : document.getElementById(anchor).scrollIntoView({behavior: 'smooth'});

	        		}

				}

				elementToRedirect.addEventListener('click', handleClick);

			});


			return;
		}

		currentPathRef.current = location.pathname;
		
		setMiddle(false);
		setEntering(true);
		

	}, [isMiddle]);
	
	
	return(<main ref={ref}>{!isMiddle && children}</main>)
	
}
export default PageTransition;