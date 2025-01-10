'use strict';
import React, { useEffect, useState, useRef } from 'react'
import { useNavigate, useLocation } from 'react-router-dom';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Cache from './Cache';


const PageTransition = ({ children }) => {


	const ref = useRef(null);
	const hrefRef = useRef(true);
	const pathRef = useRef(true);
	const anchorRef = useRef(true);
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

				navigate(pathRef.current);

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


		if(anchorRef.current){

			window.gscroll ? window.gscroll.scrollTo(document.getElementById(anchorRef.current), false, 'top top') : document.getElementById(anchorRef.current).scrollIntoView({behavior: 'instant'});

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

	        		pathRef.current = path;
	        		anchorRef.current = anchor;


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


		const isLoaded = {
			images: false,
			videos: false,
			audios: false
		};


		const loaders = document.querySelectorAll('rwp-load');
        const requiredLoaders = (loaders.length ? Object.keys(MEDIAS).filter((media, i) => media === loaders[i].getAttribute('data-value')) : []);

        
        let mediaDatas = [],
        	mediaGroups = [],
        	loadedCount = 0,
        	totalToCount = 0;

        requiredLoaders?.forEach((requiredLoader, i) => {

            mediaGroups[requiredLoader] = MEDIAS[requiredLoader];

        });

        if(!Object.keys(mediaGroups).length){

            isLoaded.images = true;
            isLoaded.videos = true;
            isLoaded.audios = true;

            done();
            
            return;

        }

        for(let group in mediaGroups){

            const medias = mediaGroups[group];

            totalToCount += medias.length;
        }


        for(let group in mediaGroups){

            const medias = mediaGroups[group];

            medias.forEach(async (media, i) => {

            	if(media.el){

            		loaded(null, group, i);

            		return;
            	}

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

            if(srcElement)
            	mediaGroups[group][i].el = srcElement;

            if(loadedCount !== totalToCount) return;

            isLoaded.images = true;
            isLoaded.videos = true;
            isLoaded.audios = true;

            mediaDatas = mediaGroups;

            done();

        }


        function done(){

            if(
                !isLoaded.images

                || !isLoaded.videos

                || !isLoaded.audios
            ) return;


            window.loader.medias = new Promise(resolved => {
            	resolved({mediaGroups: MEDIAS});
            });


            window.loader.medias.then(() => {

            	setMiddle(false);
				setEntering(true);

            });
            

        }
		

	}, [isMiddle]);
	
	
	return(<main ref={ref}>{!isMiddle && children}</main>)
	
}
export default PageTransition;