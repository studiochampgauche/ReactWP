'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';


const PageTransition = ({ children }) => {


	const ref = useRef(null);

	const to = useRef(null);

	const anchorRef = useRef(null);
	const firstLoadRef = useRef(true);
	const canTransitRef = useRef(false);
	const navigateRef = useRef(useNavigate());


	const [isLeaving, setIsLeaving] = useState(false);
	const [isEntering, setIsEntering] = useState(false);


	const { pathname } = useLocation();


	/*
	* On new page
	*/
	useEffect(() => {
		

		if(!firstLoadRef.current){

			setIsLeaving(false);
			setIsEntering(true);

		}
		firstLoadRef.current = false;



		const elements = document.querySelectorAll('a, .goto');
        if(!elements.length) return;

        const events = [];

        elements.forEach(item => {

        	const handleClick = (e) => {

        		if(item.hasAttribute('target')) return;

        		e.preventDefault();

        		if(!item.hasAttribute('href') && !item.hasAttribute('data-href')) return;


        		const href = item.hasAttribute('href') ? item.getAttribute('href') : item.getAttribute('data-href');


        		let path = null,
        			anchor = null;

        		try{

        			const url = new URL(href);

        			path = url.pathname;

        			if(url.hash)
        				anchor = url.hash;

        		} catch(_){


        			if(href.includes('#'))
        				[path, anchor] = href.split('#');
        			else
        				path = href;

        		}



        		to.current = path;
        		anchorRef.current = anchor;
        		canTransitRef.current = item.hasAttribute('data-transition') && item.getAttribute('data-transition') === 'true';


        		if(path === pathname && anchor){

        			window.gscroll ? window.gscroll.scrollTo(document.getElementById(anchor), (item.hasAttribute('data-behavior') && item.getAttribute('data-behavior') === 'instant' ? false : true), 'top top') : document.getElementById(anchor).scrollIntoView({behavior: (item.hasAttribute('data-behavior') ? item.getAttribute('data-behavior') : 'auto')});

        			if(window.gscroll && item.hasAttribute('data-behavior') && item.getAttribute('data-behavior') === 'instant')
        				ScrollTrigger.refresh();


        		} else if(path !== pathname){

        			setIsLeaving(true);

        		}

        	}


        	item.addEventListener('click', handleClick);
			events.push({element: item, event: handleClick});

        });


        return () => {

        	if(!events.length) return;
			
			events.forEach(({ element, event }) => {
				
				element.removeEventListener('click', event);
				
			});

        }

	}, [pathname]);



	/*
	* When you leave the page
	*/
	useEffect(() => {

		if(!isLeaving) return;

		if(!canTransitRef.current){

			if(window.gscroll?.scrollTop() > 0)
				ref.current.style.opacity = 0;


			window.gscroll?.paused(true);

			if(!anchorRef.current)
				window.gscroll?.scrollTop(0) || window.scrollTo(0, 0);


			gsap.delayedCall(.01, () => navigateRef.current(to.current));

			return;

		}


		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				window.gscroll?.paused(true);

				if(!anchorRef.current)
					window.gscroll?.scrollTop(0) || window.scrollTo(0, 0);


				gsap.delayedCall(.01, () => navigateRef.current(to.current));

			}
		});

		tl
		.to(ref.current, .2, {
			opacity: 0
		});
		

	}, [isLeaving]);


	/*
	* When you enter in a page
	*/
	useEffect(() => {

		if(!isEntering) return;


		ScrollTrigger?.refresh();

		if(anchorRef.current){
			window.gscroll?.scrollTo(document.getElementById(anchorRef.current), false, 'top top') || document.getElementById(anchorRef.current).scrollIntoView({ behavior: 'instant' });
			ScrollTrigger?.refresh();
		}


		if(!canTransitRef.current){

			ref.current.style.opacity = 1;

			setIsEntering(false);

			window.gscroll?.paused(false);

			return;

		}



		let tl = gsap.timeline({
			onComplete: () => {

				tl.kill();
				tl = null;

				setIsEntering(false);

				window.gscroll?.paused(false);

			}
		});

		tl
		.to(ref.current, .2, {
			opacity: 1
		});
		

	}, [isEntering]);
	
	
	return(<main ref={ref}>{children}</main>)
	
}
export default PageTransition;