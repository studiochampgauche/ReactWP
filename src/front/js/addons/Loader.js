'use strict';
import { gsap } from 'gsap';

const panelElement = document.getElementById('preloader');

let isLoaded = false;

const Loader = {
	init: () => {

        return new Promise(done => {

            /*
            * Create loading animation
            */
            let tl = gsap.timeline({
                onComplete: () => {

                    tl.kill();
                    tl = null;

                    window.gscroll?.paused(false);

                }
            });


            tl
            .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
                scaleY: 1,
                stagger: .065
            })
            .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
                scaleY: .1,
                stagger: .065
            })
            .add(() => {

                /*
                * If medias not loaded, restart
                */
                if(!isLoaded || !window.gscroll || !window.SYSTEM.loaded.css){

                    tl.restart();

                    return;

                }

            })
            .to(panelElement.querySelectorAll('.contents .bars .bar'), .4, {
                scaleY: 1,
                stagger: .065
            })
            .to(panelElement.querySelectorAll('.contents'), .6, {
                scale: 1.5
            }, '-=.4')
            .to(panelElement, .6, {
                opacity: 0,
                onStart: () => done()
            }, '-=.6')
            .to(panelElement.querySelectorAll('.contents .bars'), .6, {
                y: 25
            }, '-=.6')
            .add(() => {

                panelElement.remove();

            });

        });

	},
	download: (use = true) => {

		return new Promise(async (done, reject) => {


            if(!use){

                isLoaded = true;

                done([]);

                return;
            }


            try{

                const callMediaGroups = await fetch(window.SYSTEM.restPath + 'scg/v1/medias');

                if(!callMediaGroups.ok) throw new Error('Image groups can\'t be loaded');


                let mediaGroups = await callMediaGroups.json();
                

                if(Array.isArray(mediaGroups)){

                    isLoaded = true;

                    done([]);

                    return;
                }



                let loadedCount = 0,
                    totalToCount = 0;

                for(let group in mediaGroups){

                    const medias = mediaGroups[group];

                    totalToCount += medias.length;

                }

                for(let group in mediaGroups){

                    const medias = mediaGroups[group];

                    medias.forEach((media, i) => {

                        const srcElement = media.type === 'video' ? document.createElement('video') : new Image();
                        srcElement.src = media.src;

                        if(media.type === 'video'){
                            
                            srcElement.onloadedmetadata  = () => loaded(srcElement, group, i)
                            
                        } else {

                            srcElement.onload  = () => loaded(srcElement, group, i)

                        }

                    });

                }


                const loaded = (srcElement, group, i) => {

                    loadedCount += 1;

                    mediaGroups[group][i].el = srcElement;

                    if(loadedCount !== totalToCount) return;

                    isLoaded = true;


                    done(mediaGroups);

                }

            } catch(error){

                reject(error);

            }

        });

	}
}

export default Loader;