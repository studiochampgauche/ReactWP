'use strict';
import { gsap } from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import RWPCache from './Cache';

const Loader = {
    el: document.getElementById('loader'),
    fontsPerPage: true,
    mediasPerPage: true,
    currentRoute: null,
    routeReadyPath: null,
    routeReadyResolve: null,
    routeReady: Promise.resolve(),
	animation: null,
	setAnimation(factory){
        this.animation = factory;
        return this;
    },
	defaultAnimation(done){
        let tl = gsap.timeline({
            onComplete: () => {
                tl.kill();
                tl = null;

                window.gscroll?.paused(false);
                done();
            }
        });

        tl
        .to({}, .1, {})
        .add(() => {
            if(!window.loader?.isLoaded){
                tl.restart();
                return;
            }
        })
        .to(this.el, .4, {
            opacity: 0,
            pointerEvents: 'none',
            onStart: () => {
                ScrollTrigger?.refresh();
            }
        });

        return tl;
    },
    setRoute(route){
        const previousPath = this.currentRoute?.path || null;
        const nextPath = route?.path || null;

        this.currentRoute = route || null;

        if(nextPath !== previousPath || this.routeReadyPath !== nextPath){
            this.resetRouteReady(nextPath);
        }

        return this;
    },
    resetRouteReady(path = null){
        this.routeReadyPath = path || null;
        this.routeReady = new Promise((resolve) => {
            this.routeReadyResolve = resolve;
        });
        return this.routeReady;
    },
    markRouteReady(path = null){
        if(this.routeReadyPath && path && this.routeReadyPath !== path){
            return false;
        }

        const resolve = this.routeReadyResolve;

        this.routeReadyResolve = null;
        resolve?.(path || this.routeReadyPath || null);

        return true;
    },
    waitForRouteReady(timeout = 10000){
        if(!this.routeReadyResolve){
            return this.waitForPaint();
        }

        return Promise.race([
            this.routeReady,
            new Promise((resolve) => {
                window.setTimeout(resolve, timeout);
            })
        ]).then(() => this.waitForPaint());
    },
    waitForPaint(frames = 2){
        return new Promise((resolve) => {
            const nextFrame = () => {
                if(frames <= 0){
                    resolve();
                    return;
                }

                frames -= 1;
                window.requestAnimationFrame(nextFrame);
            };

            window.requestAnimationFrame(nextFrame);
        });
    },
    route(){
        return this.currentRoute || CURRENT_ROUTE || null;
    },
    getMediaGroups(){
        const route = this.route();

        return route?.mediaGroups
            ? route.mediaGroups.split(',').map((value) => value.trim()).filter(Boolean)
            : [];
    },
    getFontsMap(){
        const fontGroups = this.getMediaGroups();

        if(!ASSETS?.critical_fonts){
            return {};
        }

        if(!this.fontsPerPage){
            return ASSETS.critical_fonts;
        }

        const groups = [...new Set(['all', ...fontGroups])];

        return Object.fromEntries(
            groups.map((key) => [key, ASSETS.critical_fonts[key] || []])
        );
    },
    getMediasMap(){
        const mediaGroups = [...new Set(['all', ...this.getMediaGroups()])];

        return ASSETS?.critical_medias
            ? (
                this.mediasPerPage
                    ? Object.fromEntries(
                        mediaGroups.map((key) => [key, ASSETS.critical_medias[key] || []])
                    )
                    : ASSETS.critical_medias
            )
            : {};
    },

    getNoCriticalMediasMap(){
        const mediaGroups = [...new Set(['all', ...this.getMediaGroups()])];

        return ASSETS?.no_critical_medias
            ? (
                this.mediasPerPage
                    ? Object.fromEntries(
                        mediaGroups.map((key) => [key, ASSETS.no_critical_medias[key] || []])
                    )
                    : ASSETS.no_critical_medias
            )
            : {};
    },
    resolveTarget(target){
        if(!target) return null;

        if(Array.isArray(target)){
            for(const item of target){
                const targetElement = document.querySelector(item);

                if(targetElement){
                    return targetElement;
                }
            }

            return null;
        }

        return typeof target === 'string'
            ? document.querySelector(target)
            : target;
    },
    createMediaElement(type){
        switch(type){
            case 'video':
                return document.createElement('video');

            case 'audio':
                return new Audio();

            case 'image':
                return new Image();

            default:
                return null;
        }
    },
    applyProps(element, props = {}){
        Object.entries(props).forEach(([key, value]) => {
            if(value == null) return;

            if(key === 'className'){
                element.className = value;
                return;
            }

            if(key === 'style' && typeof value === 'object'){
                Object.assign(element.style, value);
                return;
            }

            if(key === 'dataset' && typeof value === 'object'){
                Object.entries(value).forEach(([datasetKey, datasetValue]) => {
                    if(datasetValue == null) return;
                    element.dataset[datasetKey] = datasetValue;
                });
                return;
            }

            if(typeof value === 'boolean'){
                if(key in element){
                    element[key] = value;
                }

                if(value){
                    element.setAttribute(key, '');
                } else {
                    element.removeAttribute(key);
                }

                return;
            }

            if(key in element){
                try{
                    element[key] = value;
                    return;
                } catch(_){}
            }

            element.setAttribute(key, value);
        });

        return element;
    },
    createSourceElement(mediaType, source){
        if(!source?.src && !source?.cachedSrc) return null;

        const sourceElement = document.createElement('source');
        const {
            src,
            cachedSrc,
            ...sourceProps
        } = source;

        this.applyProps(sourceElement, sourceProps);

        if(mediaType === 'image'){
            sourceElement.srcset = cachedSrc || src;
        } else {
            sourceElement.src = cachedSrc || src;
        }

        return sourceElement;
    },
    waitForMediaDisplay(type, mediaElement){
        if(!mediaElement){
            return Promise.resolve();
        }

        if(type === 'image'){
            if(typeof mediaElement.decode === 'function'){
                return mediaElement.decode()
                    .catch(() => {})
                    .then(() => this.waitForPaint(1));
            }

            if(mediaElement.complete){
                return this.waitForPaint(1);
            }

            return new Promise((resolve) => {
                const finalize = () => {
                    mediaElement.onload = null;
                    mediaElement.onerror = null;
                    this.waitForPaint(1).then(resolve);
                };

                mediaElement.onload = finalize;
                mediaElement.onerror = finalize;
            });
        }

        if(type === 'video'){
            if(mediaElement.readyState >= 2){
                return this.waitForPaint(1);
            }

            return new Promise((resolve) => {
                const finalize = () => {
                    mediaElement.removeEventListener('loadeddata', finalize);
                    mediaElement.removeEventListener('error', finalize);
                    this.waitForPaint(1).then(resolve);
                };

                mediaElement.addEventListener('loadeddata', finalize, { once: true });
                mediaElement.addEventListener('error', finalize, { once: true });
            });
        }

        return Promise.resolve();
    },
    renderMedias(mediaPromise){
        return Promise.resolve(mediaPromise)
            .then((medias) => {
                if(!medias || !Object.keys(medias).length){
                    return medias || {};
                }

                const values = Object.values(medias).flat();

                if(!values.length){
                    return medias;
                }

                return Promise.allSettled(values.map((media) => {
                    const target = this.resolveTarget(media.target);

                    if(!target){
                        return Promise.resolve();
                    }

                    const {
                        target: _target,
                        src,
                        cachedSrc,
                        sources,
                        type,
                        ...props
                    } = media;

                    let mediaElement = this.createMediaElement(type);

                    if(!mediaElement){
                        return Promise.resolve();
                    }

                    this.applyProps(mediaElement, props);

                    if(type === 'video'){
                        mediaElement.playsInline = true;
                    }

                    if(type === 'image'){
                        mediaElement.src = cachedSrc || src;

                        if(Array.isArray(sources) && sources.length){
                            const pictureElement = document.createElement('picture');

                            sources.forEach((source) => {
                                const sourceElement = this.createSourceElement('image', source);

                                if(sourceElement){
                                    pictureElement.appendChild(sourceElement);
                                }
                            });

                            pictureElement.appendChild(mediaElement);
                            target.replaceWith(pictureElement);

                            return this.waitForMediaDisplay(type, mediaElement);
                        }

                        target.replaceWith(mediaElement);
                        return this.waitForMediaDisplay(type, mediaElement);
                    }

                    if(['video', 'audio'].includes(type)){
                        let hasSourceChildren = false;

                        if(Array.isArray(sources) && sources.length){
                            sources.forEach((source) => {
                                const sourceElement = this.createSourceElement(type, source);

                                if(sourceElement){
                                    hasSourceChildren = true;
                                    mediaElement.appendChild(sourceElement);
                                }
                            });
                        }

                        if(!hasSourceChildren){
                            mediaElement.src = cachedSrc || src;
                        }

                        target.replaceWith(mediaElement);
                        mediaElement.load();

                        const displayPromise = this.waitForMediaDisplay(type, mediaElement);

                        if(mediaElement.autoplay){
                            const tryPlay = () => {
                                mediaElement.play()?.catch(() => {});
                            };

                            if(mediaElement.readyState >= 2){
                                tryPlay();
                            } else {
                                mediaElement.addEventListener('loadeddata', tryPlay, { once: true });
                            }
                        }

                        return displayPromise;
                    }

                    target.replaceWith(mediaElement);
                    return Promise.resolve();
                })).then(() => medias);
            });
    },
    preloadMedia(type, item){
        if(!item?.src){
            return Promise.resolve(item);
        }

        if(item.cachedSrc){
            return Promise.resolve(item);
        }

        let mediaElement = this.createMediaElement(type);

        if(!mediaElement){
            return Promise.resolve(item);
        }

        return RWPCache.media(item.src).then((cachedSrc) => {
            item.cachedSrc = cachedSrc || item.src;

            return new Promise((resolve) => {

                const cleanup = () => {
                    if(!mediaElement){
                        resolve(item);
                        return;
                    }

                    if(type === 'image'){
                        mediaElement.onload = null;
                        mediaElement.onerror = null;
                    } else {
                        mediaElement.onloadeddata = null;
                        mediaElement.onerror = null;
                    }

                    mediaElement = null;
                    resolve(item);
                };

                if(type === 'image'){
                    mediaElement.onload = cleanup;
                    mediaElement.onerror = cleanup;
                    mediaElement.src = item.cachedSrc;

                    if(mediaElement.complete){
                        cleanup();
                    }

                    return;
                }

                if(['video', 'audio'].includes(type)){
                    if(type === 'video'){
                        mediaElement.playsInline = true;
                    }

                    mediaElement.preload = 'auto';
                    mediaElement.onloadeddata = cleanup;
                    mediaElement.onerror = cleanup;
                    mediaElement.src = item.cachedSrc;
                    mediaElement.load();

                    if(mediaElement.readyState >= 2){
                        cleanup();
                    }

                    return;
                }

                cleanup();

            });
        }).catch(() => item);
    },
    init(){
        return new Promise((done) => {

            const animation = this.animation
                ? this.animation({
                    Loader: this,
                    gsap,
                    ScrollTrigger,
                    done
                })
                : this.defaultAnimation(done);

            if(!animation){
                done();
            }

        });
    },
    fonts(){
        const fonts = this.getFontsMap();

        if(!Object.keys(fonts).length || !document.fonts){
            return Promise.resolve(fonts);
        }

        return Promise.allSettled(
            Object.values(fonts)
                .flat()
                .filter(Boolean)
                .map((font) => document.fonts.load(font))
        ).then(() => fonts);
    },
    medias(){
        const medias = this.getMediasMap();

        if(!Object.keys(medias).length){
            return Promise.resolve(medias);
        }

        const tasks = [];

        Object.values(medias).flat().forEach((media) => {
            if(!media?.type || !media?.src) return;

            tasks.push(this.preloadMedia(media.type, media));

            if(Array.isArray(media.sources)){
                media.sources.forEach((subMedia) => {
                    if(!subMedia?.src) return;
                    tasks.push(this.preloadMedia(media.type, subMedia));
                });
            }
        });

        if(!tasks.length){
            return Promise.resolve(medias);
        }

        return Promise.allSettled(tasks).then(() => medias);
    },
    noCriticalMedias(){
        const medias = this.getNoCriticalMediasMap();

        if(!Object.keys(medias).length){
            return Promise.resolve(medias);
        }

        const tasks = [];

        Object.values(medias).flat().forEach((media) => {
            if(!media?.type || !media?.src) return;

            tasks.push(this.preloadMedia(media.type, media));

            if(Array.isArray(media.sources)){
                media.sources.forEach((subMedia) => {
                    if(!subMedia?.src) return;
                    tasks.push(this.preloadMedia(media.type, subMedia));
                });
            }
        });

        if(!tasks.length){
            return Promise.resolve(medias);
        }

        return Promise.allSettled(tasks).then(() => medias);
    },
    download(){

		window.loader = window.loader || {};
		window.loader.isLoaded = false;

		window.loader.fonts = this.fonts();
		window.loader.medias = this.medias();
        window.loader.noCriticalMedias = this.noCriticalMedias();

		window.loader.download = Promise.allSettled([
			window.loader.fonts,
			window.loader.medias
		]);

		return window.loader.download;
	},
    display(){
		return new Promise((done) => {

			const finalize = (medias = {}) => {
                Promise.resolve(window.loader?.fonts)
                .finally(() => {
                    window.loader.isLoaded = true;
                    done(medias);
                });
			};

            this.waitForRouteReady()
                .then(() => this.renderMedias(window.loader?.medias))
                .then((medias) => {
                    finalize(medias);
                });

		});
	},
    noCriticalDisplay(){
		return new Promise((done) => {

            const finalize = (medias = {}) => {
                done(medias);
            };

            this.waitForRouteReady()
                .then(() => this.renderMedias(window.loader?.noCriticalMedias))
                .then((medias) => {
                    finalize(medias);
                });

		});
	}
};

export default Loader;