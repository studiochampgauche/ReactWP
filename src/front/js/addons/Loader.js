'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

const Loader = {
    init: () => {

        return new Promise(done => {


            const panelElement = document.getElementById('preloader');

            
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

                    || !window.loader.isLoaded.css

                    || !window.loader.isLoaded.fonts

                    || !window.loader.isLoaded.images

                    || !window.loader.isLoaded.videos

                    || !window.loader.isLoaded.audios

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

                return new Promise(async (resolved, rejected) => {

                    let fontDatas = [],
                        mediaDatas = [];


                    let cache;
                    const urlsToCache = [];

                    try{
                        cache = await caches.open('medias');
                    } catch (_){

                        console.warn('Medias caching can\'t work on non-secure url');

                    }

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

                            window.loader.isLoaded.css = true;

                            done();

                        }

                    }


                    const fonts = () => {

                        document.fonts.ready.then(() => {

                            const fonts = Array.from(document.fonts);

                            if(!fonts.length){

                                window.loader.isLoaded.fonts = true;

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

                                if(countLoaded !== fonts.length || window.loader.isLoaded.fonts) return;

                                ScrollTrigger?.refresh();

                                window.loader.isLoaded.fonts = true;

                                fontDatas = fonts;

                                done();

                            }



                        });

                    }



                    const otherMedias = async () => {

                        if(!fetchImagesVideos){

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

                            done();
                            
                            return;
                        }



                        const callMediaGroups = await fetch(window.SYSTEM.restPath + 'scg/v1/medias');

                        if(!callMediaGroups.ok) throw new Error('Medias groups can\'t be loaded');


                        let mediaGroups = await callMediaGroups.json();
                        

                        if(Array.isArray(mediaGroups)){

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

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

                                const cacheResponse = media?.cache === false || !cache ? false : await cache.match(media.src);

                                if (cacheResponse) {

                                    const blob = await cacheResponse.blob();
                                    srcElement.src = URL.createObjectURL(blob);

                                } else {
                
                                    if(media?.cache !== false && cache)
                                        urlsToCache.push(media.src);

                                    srcElement.src = media.src;
                                }


                                if(['video', 'audio'].includes(media.type)){

                                    srcElement.preload = 'auto';
                                    srcElement.controls = true;

                                    srcElement.onloadeddata = () => loaded(srcElement, group, i)
                                    
                                } else {

                                    srcElement.onload  = () => loaded(srcElement, group, i)

                                }

                            });

                        }


                        async function loaded(srcElement, group, i){

                            loadedCount += 1;

                            mediaGroups[group][i].el = srcElement;

                            if(loadedCount !== totalToCount) return;

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

                            mediaDatas = mediaGroups;

                            if(urlsToCache && cache) await cache.addAll([...new Set(urlsToCache)]);

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
                            !window.loader.isLoaded.css

                            || !window.loader.isLoaded.fonts

                            || !window.loader.isLoaded.images

                            || !window.loader.isLoaded.videos

                            || !window.loader.isLoaded.audios
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


                    const allLoaded = Array.from(loadElements).every(el => el.hasAttribute('data-value'));

                    if(!allLoaded) {

                        done();

                        return;

                    }


                    loadElements.forEach((loadElement, i) => {
                        
                        window.loader.medias
                        .then(({ mediaGroups }) => {

                            mediaGroups?.[loadElement.getAttribute('data-value')]?.forEach((data, j) => {

                                const target = document.querySelector(data.target);

                                if(target)
                                    target.replaceWith(data.el);

                                if(i !== loadElements.length - 1 || j !== mediaGroups[loadElement.getAttribute('data-value')].length - 1) return;

                                done();

                            });

                        });

                    });


                });

            }
        }
    }
}

export default Loader;