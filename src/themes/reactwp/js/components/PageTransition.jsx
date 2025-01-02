'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Cache from '../utils/Cache';


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

        		if(
        			item.hasAttribute('target')

        			||

        			(!item.hasAttribute('href') && !item.hasAttribute('data-href'))

        			||

        			e.ctrlKey
        		) return;


        		const href = item.hasAttribute('href') ? item.getAttribute('href') : item.getAttribute('data-href');

        		if(['tel', 'mailto'].some(prefix => href.startsWith(prefix))) return;


        		e.preventDefault();


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

		const isLoaded = {
			images: false,
			videos: false,
			audios: false
		};

		const loaders = document.querySelectorAll('scg-load');
        const requiredLoaders = (loaders.length ? Object.keys(MEDIAS).filter((media, i) => media === loaders[i].getAttribute('data-value')) : []);

        let mediaDatas = [],
        	mediaGroups = [],
        	loadedCount = 0,
        	totalToCount = 0;

        requiredLoaders?.forEach((requiredLoader, i) => {

            mediaGroups[requiredLoader] = MEDIAS[requiredLoader];

        });

        for(let group in mediaGroups){

            const medias = mediaGroups[group];

            totalToCount += medias.length;
        }


        for(let group in mediaGroups){

            const medias = mediaGroups[group];

            medias.forEach(async (media, i) => {

                const mediaTypes = {
                    video: () => document.createElement('video'),
                    audio: () => new Audio(),
                    image: () => new Image()
                };

                let srcElement = mediaTypes[media.type];

                if(!srcElement)
                    throw new Error(`${media.type} isn't supported.`);

                srcElement = srcElement();

                const cacheGet = await Cache.get(media.src);

                if(!cacheGet.includes('blob:')){

                    const resp = await fetch(cacheGet);

                    if(!resp.ok) throw new Error(`${media.src} can\'t be loaded`);

                    Cache.put(resp.url, resp.clone());


                    const srcElementBlob = await resp.blob();
                    const srcElementURL = URL.createObjectURL(srcElementBlob);

                    srcElement.src = srcElementURL;
                } else {

                    srcElement.src = cacheGet;

                }


                loaded(srcElement, group, i);



                if(['video', 'audio'].includes(media.type)){

                    srcElement.preload = 'auto';
                    srcElement.controls = true;
                    
                }

            });

        }


        function loaded(srcElement, group, i){

            loadedCount += 1;

            mediaGroups[group][i].el = srcElement;

            if(loadedCount !== totalToCount) return;

            isLoaded.images = true;
            isLoaded.videos = true;
            isLoaded.audios = true;

            mediaDatas = mediaGroups;

            done();

        }

		function init(){

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

		}


		function done(){

            if(
                !isLoaded.images

                || !isLoaded.videos

                || !isLoaded.audios
            ) return;


            init();

        }
		

	}, [isEntering]);
	
	
	return(<main ref={ref}>{children}</main>)
	
}
export default PageTransition;