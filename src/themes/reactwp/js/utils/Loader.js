'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Cache from './Cache';

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
            .to(panelElement.querySelector('.gbar .line'), .2, {
                scaleX: 1
            })
            .to(panelElement.querySelector('.gbar .line'), .2, {
                scaleX: 0,
                transformOrigin: 'right center'
            })
            .add(() => {

                /*
                * If medias not loaded, restart
                */
                if(
                    
                    !window.gscroll

                    || !window.loader.isLoaded.fonts

                    || !window.loader.isLoaded.images

                    || !window.loader.isLoaded.videos

                    || !window.loader.isLoaded.audios

                ){

                    tl.restart();

                    return;

                }

            })
            .to(panelElement.querySelector('.gbar .line'), .2, {
                scaleX: 1,
                transformOrigin: 'left center'
            })
            .to(panelElement.querySelector('.gbar .line'), .2, {
                scaleX: 0,
                transformOrigin: 'right center'
            })
            .to(panelElement, .6, {
                opacity: 0,
                onStart: () => done()
            }, '-=.4')
            .add(() => {

                panelElement.remove();

            });

        });

    },
    downloader: () => {

        return {

            init: (downloadAllMedias = true) => {

                return new Promise(async (resolved, rejected) => {

                    let fontDatas = [],
                        mediaDatas = [];


                    const fonts = () => {

                        document.fonts.ready.then(() => {

                            const fonts = Array.from(document.fonts);

                            if(!fonts.length){

                                window.loader.isLoaded.fonts = true;

                                done();
                                
                                return;
                            }

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

                        if(Array.isArray(MEDIAS)){

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

                            done();
                            
                            return;
                        }



                        const loaders = document.querySelectorAll('rwp-load');
                        const requiredLoaders = (!downloadAllMedias && loaders.length ? Object.keys(MEDIAS).filter((media, i) => media === loaders[i].getAttribute('data-value')) : []);

                        let mediaGroups = downloadAllMedias ? MEDIAS : [],
                            loadedCount = 0,
                            totalToCount = 0;


                        requiredLoaders?.forEach((requiredLoader, i) => {

                            mediaGroups[requiredLoader] = MEDIAS[requiredLoader];

                        });


                        if(!Object.keys(mediaGroups).length){

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

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

                            window.loader.isLoaded.images = true;
                            window.loader.isLoaded.videos = true;
                            window.loader.isLoaded.audios = true;

                            mediaDatas = mediaGroups;

                            done();

                        }

                    }



                    try{

                        fonts();
                        otherMedias();

                    } catch(error){

                        rejected(error);

                    }



                    function done(){

                        if(
                            !window.loader.isLoaded.fonts

                            || !window.loader.isLoaded.images

                            || !window.loader.isLoaded.videos

                            || !window.loader.isLoaded.audios
                        ) return;


                        resolved({mediaGroups: mediaDatas, fonts: fontDatas});

                    }

                });

            },
            display: (target = 'rwp-load') => {

                return new Promise(done => {
        
                    const loadElements = document.querySelectorAll((target === 'rwp-load' ? target : 'rwp-load[data-value='+ target +']'));

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

                                let targ = null;

                                if(data.target && Array.isArray(data.target)){

                                    data.target.forEach(tar => {

                                        if(!document.querySelector(tar)) return;

                                        targ = document.querySelector(tar);

                                    });

                                } else if(data.target && !Array.isArray(data.target)) {
                                    targ = document.querySelector(data.target);
                                }

                                if(data.type === 'image' && data.el){
                                    data.el.width = 5;
                                    data.el.height = 5;
                                }

                                if(targ)
                                    targ.replaceWith(data.el);

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