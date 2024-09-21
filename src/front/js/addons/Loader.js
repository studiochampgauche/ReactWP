'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const panelElement = document.getElementById('preloader');

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
                if(
                    
                    !window.gscroll

                    || !window.SYSTEM.loaded.css

                    || !window.SYSTEM.loaded.fonts

                    || !window.SYSTEM.loaded.images

                    || !window.SYSTEM.loaded.videos

                    || !window.SYSTEM.loaded.audios

                ){

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
	downloader: () => {

        return {

            init: (fetchImagesVideos = true) => {

                return new Promise((resolved, rejected) => {

                    let fontDatas = [],
                        mediaDatas = [];

                    const css = () => {

                        const cssLinkElement = document.getElementById('mainStyle');

                        if (cssLinkElement.sheet){

                            if(cssLinkElement.sheet.cssRules.length)
                                loaded();
                            else
                                throw new Error('CSS can\'t be load or no cssRules can\'t be found.');

                        } else {

                            cssLinkElement.onload = () => loaded();

                            cssLinkElement.onerror = () => {

                                throw new Error('CSS can\'t be load');

                            };

                        }


                        function loaded(){

                            window.SYSTEM.loaded.css = true;

                            done();

                        }

                    }


                    const fonts = () => {

                        document.fonts.ready.then(() => {

                            const fonts = Array.from(document.fonts);

                            if(!fonts.length){

                                window.SYSTEM.loaded.fonts = true;

                                done();
                                
                                return;
                            }


                            let ok = true;
                            let countLoaded = 0;

                            for(let i = 0; i < fonts.length; i++){

                                const font = fonts[i];

                                if (font.status === 'error'){

                                    throw new Error(`${font.family} can\'t be loaded.`);

                                } else if(font.status === 'unloaded'){

                                    font
                                    .load()
                                    .then(() => loaded())
                                    .catch(() => {

                                        throw new Error(`${font.family} weight ${font.weight} can\'t be loaded.`);

                                    });

                                } else
                                    loaded();

                            }


                            function loaded(){

                                countLoaded += 1;

                                if(countLoaded !== fonts.length || window.SYSTEM.loaded.fonts) return;

                                ScrollTrigger?.refresh();

                                window.SYSTEM.loaded.fonts = true;

                                fontDatas = fonts;

                                done();

                            }



                        });

                    }



                    const otherMedias = async () => {

                        if(!fetchImagesVideos){

                            window.SYSTEM.loaded.images = true;
                            window.SYSTEM.loaded.videos = true;
                            window.SYSTEM.loaded.audios = true;

                            done();
                            
                            return;
                        }



                        const callMediaGroups = await fetch(window.SYSTEM.restPath + 'scg/v1/medias');

                        if(!callMediaGroups.ok) throw new Error('Medias groups can\'t be loaded');


                        let mediaGroups = await callMediaGroups.json();
                        

                        if(Array.isArray(mediaGroups)){

                            window.SYSTEM.loaded.images = true;
                            window.SYSTEM.loaded.videos = true;
                            window.SYSTEM.loaded.audios = true;

                            done();

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

                                const mediaTypes = {
                                    video: () => document.createElement('video'),
                                    audio: () => new Audio(),
                                    image: () => new Image()
                                };

                                let srcElement = mediaTypes[media.type];

                                if(!srcElement)
                                    throw new Error(`${media.type} isn't supported.`);


                                srcElement = srcElement();

                                srcElement.src = media.src;

                                if(['video', 'audio'].includes(media.type)){

                                    srcElement.preload = 'auto';
                                    srcElement.controls = true;

                                    srcElement.onloadeddata = () => loaded(srcElement, group, i)
                                    
                                } else {

                                    srcElement.onload  = () => loaded(srcElement, group, i)

                                }

                            });

                        }


                        function loaded(srcElement, group, i){

                            loadedCount += 1;

                            mediaGroups[group][i].el = srcElement;

                            if(loadedCount !== totalToCount) return;

                            window.SYSTEM.loaded.images = true;
                            window.SYSTEM.loaded.videos = true;
                            window.SYSTEM.loaded.audios = true;

                            mediaDatas = mediaGroups;

                            done();

                        }

                    }



                    try{

                        css();
                        fonts();
                        otherMedias();

                    } catch(error){

                        rejected(error);

                    }



                    function done(){

                        if(
                            !window.SYSTEM.loaded.css

                            || !window.SYSTEM.loaded.fonts

                            || !window.SYSTEM.loaded.images

                            || !window.SYSTEM.loaded.videos

                            || !window.SYSTEM.loaded.audios
                        ) return;


                        resolved({mediaGroups: mediaDatas, fonts: fontDatas});

                    }

                });

            },
            display: () => {

                return new Promise(done => {
        
                    const loadElements = document.querySelectorAll('scg-load');
                    if(!loadElements.length){

                        done();

                        return;
                    }


                    let ok = false;
                    loadElements.forEach(loadElement => {

                        if(!loadElement.hasAttribute('data-value')) return;

                        if(!ok) ok = true;
                        
                        window.loader.medias
                        .then(({ mediaGroups }) => {

                            mediaGroups?.[loadElement.getAttribute('data-value')]?.forEach((data, i) => {

                                const target = document.querySelector(data.target);

                                if(!target) return;

                                target.replaceWith(data.el);

                                if(i !== mediaGroups[loadElement.getAttribute('data-value')].length -1) return;

                                done();

                            });

                        });

                    });



                    if(!ok){

                        done();

                        return;
                    }

                });

            }
        }
    }
}

export default Loader;