'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import Loader from '../addons/Loader';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';


let firstLoad = true;


const PageTransition = ({ children }) => {


	const to = useRef(null);

	const anchorRef = useRef(null);
	const behaviorRef = useRef(null);
	const navigateRef = useRef(useNavigate());

	const { pathname } = useLocation();


	const [isLeaving, setIsLeaving] = useState(false);
	const [isEntering, setIsEntering] = useState(false);



	/*
	* Manage click
	*/
	useEffect(() => {


		if(!firstLoad){



			/*
			* When you change Page, refresh Scroller
			*/
			ScrollTrigger?.refresh();



			/*
	        * Start position
	        */
	        if(anchorRef.current){

				window.gscroll?.scrollTo(document.getElementById(anchorRef.current), false, 'top top') || document.getElementById(anchorRef.current).scrollIntoView({ behavior: 'instant' })

	        }
			else
	        	window.gscroll?.scrollTop(0) || window.scrollTo(0, 0);



		}



		if(firstLoad)
			firstLoad = false;






        /*
        * Prevent default behavior, create your own behavior
        */
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




        		if(path === pathname && anchor){

        			window.gscroll ? window.gscroll.scrollTo(document.getElementById(anchor), (item.hasAttribute('data-behavior') && item.getAttribute('data-behavior') === 'instant' ? false : true), 'top top') : document.getElementById(anchor).scrollIntoView({behavior: (item.hasAttribute('data-behavior') ? item.getAttribute('data-behavior') : 'auto')});

        			if(window.gscroll && item.hasAttribute('data-behavior') && item.getAttribute('data-behavior') === 'instant')
        				ScrollTrigger.refresh();


        		} else if(path !== pathname){

        			item.hasAttribute('data-transition') && item.getAttribute('data-transition') === 'true' ? setIsLeaving(true) : navigateRef.current(path);

        		}



        		to.current = path;
        		anchorRef.current = anchor;
        		behaviorRef.current = item.hasAttribute('data-behavior') ? item.getAttribute('data-behavior') : 'instant';


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
	* Load medias
	*/
	useEffect(() => {

		const loadElement = document.querySelector('scg-load');
		if(!loadElement || !loadElement.hasAttribute('data-value')) return;

		window.medias.then(mediaGroups => {

			mediaGroups?.[loadElement.getAttribute('data-value')]?.forEach(data => {

				const target = document.querySelector(data.target);

				if(!target) return;

            	target.replaceWith(data.el);

			});

		});



		return () => {



		}


	}, [pathname]);



	/*
    * isLeaving transition
    */
	useEffect(() => {
		
		if(!isLeaving) return;
        
		
		const tl = gsap.timeline({
			onComplete: () => {
				
				navigateRef.current(to.current);
				
				setIsLeaving(false);
				setIsEntering(true);
				
			}
		});
		
		
		tl
		.to('main', .2, {
			opacity: 0
		});
		
		
		return () => {
			
			tl.kill();
			
		}
		
		
	}, [isLeaving]);




	/*
    * isEntering transition
    */
	useEffect(() => {
		
		if(!isEntering) return;
		
		
		const tl = gsap.timeline({
			onComplete: () => {
				
				setIsEntering(false);


			}
		});
		
		tl
		.to('main', .2, {
			opacity: 1
		});
		
		
		return () => {
			
			tl.kill();
			
		}
		
		
	}, [isEntering]);
	
	
	return(<main>{children}</main>)
	
}
export default PageTransition;