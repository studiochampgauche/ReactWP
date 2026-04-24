import { runtime, createRouteKey } from './Runtime';
import { fetchRoute } from './RouteService';
import { resolveTemplateEntry } from './TemplateRegistry';
import { gsap, ScrollTrigger, prefersReducedMotion, runAnimation } from './motion';
import ReactWPCache from './Cache';

const IMAGE_EXTENSIONS = new Set(['.avif', '.gif', '.jpeg', '.jpg', '.png', '.svg', '.webp']);
const VIDEO_EXTENSIONS = new Set(['.m4v', '.mov', '.mp4', '.ogv', '.webm']);
const AUDIO_EXTENSIONS = new Set(['.aac', '.flac', '.m4a', '.mp3', '.oga', '.ogg', '.wav']);

const getLoader = () => document.getElementById('loader');
const getLoaderLabel = () => document.querySelector('#loader .loader-label');
const resolvedPromise = (value = null) => Promise.resolve(value);

const createLoaderState = () => ({
    __reactwpLoaderState: true,
    isLoaded: false,
    init: resolvedPromise(null),
    criticalDisplay: resolvedPromise(null),
    noCriticalDisplay: resolvedPromise(null),
    template: resolvedPromise(null),
    criticalFonts: resolvedPromise([]),
    criticalMedias: resolvedPromise({}),
    noCriticalMedias: resolvedPromise({}),
    criticalDownload: resolvedPromise(null),
    noCriticalDownload: resolvedPromise(null),
    route: null
});

let fallbackLoaderState = createLoaderState();

const ensureLoaderState = (reset = false) => {
    if(typeof window === 'undefined'){
        if(reset){
            fallbackLoaderState = createLoaderState();
        }

        return fallbackLoaderState;
    }

    if(reset || !window.loader?.__reactwpLoaderState){
        window.loader = createLoaderState();
    }

    return window.loader;
};

const fontRequests = new Map();
const criticalRouteRequests = new Map();
const deferredRouteRequests = new Map();

const inferMediaType = (src = '') => {
    const cleanSource = String(src || '').split('?')[0].toLowerCase();
    const extension = cleanSource.includes('.')
        ? `.${cleanSource.split('.').pop()}`
        : '';

    if(IMAGE_EXTENSIONS.has(extension)){
        return 'image';
    }

    if(VIDEO_EXTENSIONS.has(extension)){
        return 'video';
    }

    if(AUDIO_EXTENSIONS.has(extension)){
        return 'audio';
    }

    return null;
};

const cloneMediaSource = (source = {}, fallbackType = null) => {
    if(typeof source === 'string'){
        return {
            src: source,
            type: fallbackType || inferMediaType(source)
        };
    }

    return {
        ...source,
        type: source.type || fallbackType || inferMediaType(source.src)
    };
};

const cloneMediaEntry = (entry = {}) => {
    if(typeof entry === 'string'){
        return {
            src: entry,
            type: inferMediaType(entry),
            sources: []
        };
    }

    const type = entry.type || inferMediaType(entry.src);

    return {
        ...entry,
        type,
        sources: Array.isArray(entry.sources)
            ? entry.sources.map((source) => cloneMediaSource(source, type))
            : []
    };
};

const getRouteKey = (route = null) => {
    return createRouteKey(route?.path || '/', route?.search || '');
};

const getGroups = (route) => {
    const groups = new Set(['all']);

    String(route?.mediaGroups || '')
        .split(',')
        .map((group) => group.trim())
        .filter(Boolean)
        .forEach((group) => groups.add(group));

    return [...groups];
};

const collectFonts = (route) => {
    return getGroups(route)
        .flatMap((group) => runtime.assets.criticalFonts?.[group] || [])
        .filter(Boolean);
};

const collectMediaMap = (map = {}, route) => {
    return Object.fromEntries(
        getGroups(route).map((group) => [
            group,
            (map[group] || [])
                .filter(Boolean)
                .map((entry) => cloneMediaEntry(entry))
        ])
    );
};

const syncLoaderState = (route = null) => {
    const state = ensureLoaderState();
    const routeKey = getRouteKey(route);
    const criticalRequest = routeKey ? criticalRouteRequests.get(routeKey) : null;
    const deferredRequest = routeKey ? deferredRouteRequests.get(routeKey) : null;

    state.route = route || null;
    state.template = criticalRequest?.template || resolvedPromise(route || null);
    state.criticalFonts = criticalRequest?.fonts || resolvedPromise([]);
    state.criticalMedias = criticalRequest?.medias || resolvedPromise({});
    state.noCriticalMedias = deferredRequest?.medias || resolvedPromise({});
    state.criticalDownload = criticalRequest?.download || resolvedPromise(route || null);
    state.noCriticalDownload = deferredRequest?.download || resolvedPromise(route || null);

    return state;
};

const preloadFonts = async (route) => {
    if(!document.fonts){
        return [];
    }

    const fonts = collectFonts(route);

    await Promise.allSettled(
        fonts.map((font) => {
            if(fontRequests.has(font)){
                return fontRequests.get(font);
            }

            const request = document.fonts.load(font).catch(() => null);
            fontRequests.set(font, request);

            return request;
        })
    );

    return fonts;
};

const preloadMediaEntry = async (entry = {}) => {
    const media = cloneMediaEntry(entry);

    if(media.src){
        media.cachedSrc = await ReactWPCache.media(media.src).catch(() => media.src);
    }

    if(Array.isArray(media.sources) && media.sources.length){
        media.sources = await Promise.all(
            media.sources.map(async (source) => {
                const nextSource = cloneMediaSource(source, media.type);

                if(nextSource.src){
                    nextSource.cachedSrc = await ReactWPCache.media(nextSource.src).catch(() => nextSource.src);
                }

                return nextSource;
            })
        );
    }

    return media;
};

const preloadMediaMap = async (map = {}) => {
    const groups = await Promise.all(
        Object.entries(map).map(async ([group, items]) => {
            const medias = await Promise.all(items.map((item) => preloadMediaEntry(item)));
            return [group, medias];
        })
    );

    return Object.fromEntries(groups);
};

const preloadCriticalMedia = async (route) => {
    const mediaMap = collectMediaMap(runtime.assets.criticalMedias || {}, route);
    return preloadMediaMap(mediaMap);
};

const preloadDeferredMedia = async (route) => {
    const mediaMap = collectMediaMap(runtime.assets.noCriticalMedias || {}, route);
    return preloadMediaMap(mediaMap);
};

const preloadTemplate = async (route) => {
    const templateEntry = resolveTemplateEntry(route.template);
    await templateEntry.preload();
    return route;
};

const createContext = (done = () => null) => {
    return {
        Loader,
        gsap,
        ScrollTrigger,
        loader: getLoader(),
        labelNode: getLoaderLabel(),
        loaderState: ensureLoaderState(),
        reducedMotion: prefersReducedMotion,
        done
    };
};

const defaultLoaderAnimation = ({ gsap, ScrollTrigger, loader, loaderState, done }) => {
    if(!loader){
        done();
        return null;
    }

    let timeline = gsap.timeline({
        onComplete: () => {
            timeline.kill();
            timeline = null;
            done();
        }
    });

    timeline
        .to({}, {
            duration: 0.1
        })
        .add(() => {
            if(!loaderState.isLoaded){
                timeline.restart();
                return;
            }
        })
        .to(loader, {
            autoAlpha: 0,
            pointerEvents: 'none',
            duration: 0.24,
            ease: 'power2.out',
            overwrite: 'auto',
            onStart: () => {
                ScrollTrigger?.refresh();
            }
        });

    return timeline;
};

const defaultLoaderImmediate = ({ gsap, loader, loaderState }) => {
    if(!loader){
        return null;
    }

    return new Promise((resolve) => {
        const check = () => {
            if(loaderState.isLoaded){
                gsap.set(loader, {
                    autoAlpha: 0,
                    pointerEvents: 'none'
                });
                resolve();
                return;
            }

            window.requestAnimationFrame(check);
        };

        check();
    });
};

const createCriticalRequest = (route) => {
    const key = getRouteKey(route);

    if(!route?.path){
        return {
            route,
            template: resolvedPromise(route),
            fonts: resolvedPromise([]),
            medias: resolvedPromise({}),
            download: resolvedPromise(route)
        };
    }

    if(criticalRouteRequests.has(key)){
        return criticalRouteRequests.get(key);
    }

    const template = preloadTemplate(route);
    const fonts = preloadFonts(route);
    const medias = preloadCriticalMedia(route);
    const download = Promise.allSettled([
        template,
        fonts,
        medias
    ]).then(() => route);

    const request = {
        route,
        template,
        fonts,
        medias,
        download
    };

    criticalRouteRequests.set(key, request);

    return request;
};

const createDeferredRequest = (route) => {
    const key = getRouteKey(route) || '__global__';

    if(deferredRouteRequests.has(key)){
        return deferredRouteRequests.get(key);
    }

    const medias = preloadDeferredMedia(route);
    const download = medias.then(() => route);
    const request = {
        route,
        medias,
        download
    };

    deferredRouteRequests.set(key, request);

    return request;
};

const toUniqueArray = (values = []) => {
    return [...new Set(values.filter(Boolean))];
};

const resolveTargets = (target) => {
    if(!target || typeof document === 'undefined'){
        return [];
    }

    if(Array.isArray(target)){
        return toUniqueArray(
            target.flatMap((item) => resolveTargets(item))
        );
    }

    if(target instanceof Element){
        return [target];
    }

    if(target instanceof NodeList || target instanceof HTMLCollection){
        return toUniqueArray(Array.from(target).filter((node) => node instanceof Element));
    }

    if(typeof target === 'string'){
        return Array.from(document.querySelectorAll(target));
    }

    return [];
};

const applyProps = (element, props = {}) => {
    Object.entries(props).forEach(([key, value]) => {
        if(value == null){
            return;
        }

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
                if(datasetValue == null){
                    return;
                }

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
            } catch(_error){}
        }

        element.setAttribute(key, value);
    });

    return element;
};

const createSourceElement = (mediaType, source = {}) => {
    if(!source?.src && !source?.cachedSrc){
        return null;
    }

    const sourceElement = document.createElement('source');
    const {
        src,
        cachedSrc,
        ...sourceProps
    } = source;

    applyProps(sourceElement, sourceProps);

    if(mediaType === 'image'){
        sourceElement.srcset = cachedSrc || src;
    } else {
        sourceElement.src = cachedSrc || src;
    }

    return sourceElement;
};

const waitForMediaDisplay = (type, mediaElement) => {
    if(!mediaElement){
        return Promise.resolve();
    }

    if(type === 'image'){
        if(typeof mediaElement.decode === 'function'){
            return mediaElement.decode()
                .catch(() => null)
                .then(() => Loader.waitForPaint(1));
        }

        if(mediaElement.complete){
            return Loader.waitForPaint(1);
        }

        return new Promise((resolve) => {
            const finalize = () => {
                mediaElement.onload = null;
                mediaElement.onerror = null;
                Loader.waitForPaint(1).then(resolve);
            };

            mediaElement.onload = finalize;
            mediaElement.onerror = finalize;
        });
    }

    if(type === 'video'){
        if(mediaElement.readyState >= 2){
            return Loader.waitForPaint(1);
        }

        return new Promise((resolve) => {
            const finalize = () => {
                mediaElement.removeEventListener('loadeddata', finalize);
                mediaElement.removeEventListener('error', finalize);
                Loader.waitForPaint(1).then(resolve);
            };

            mediaElement.addEventListener('loadeddata', finalize, { once: true });
            mediaElement.addEventListener('error', finalize, { once: true });
        });
    }

    return Promise.resolve();
};

const createMediaElement = (type) => {
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
};

const buildMediaNode = (media = {}) => {
    const {
        target: _target,
        targets: _targets,
        src,
        cachedSrc,
        sources,
        type,
        ...props
    } = media;

    const resolvedType = type || inferMediaType(src);
    const mediaElement = createMediaElement(resolvedType);

    if(!mediaElement){
        return null;
    }

    applyProps(mediaElement, props);

    if(resolvedType === 'video'){
        mediaElement.playsInline = true;
    }

    if(resolvedType === 'image'){
        mediaElement.src = cachedSrc || src;

        if(Array.isArray(sources) && sources.length){
            const pictureElement = document.createElement('picture');

            sources.forEach((source) => {
                const sourceElement = createSourceElement('image', source);

                if(sourceElement){
                    pictureElement.appendChild(sourceElement);
                }
            });

            pictureElement.appendChild(mediaElement);

            return {
                node: pictureElement,
                mediaElement,
                type: resolvedType
            };
        }

        return {
            node: mediaElement,
            mediaElement,
            type: resolvedType
        };
    }

    let hasSourceChildren = false;

    if(Array.isArray(sources) && sources.length){
        sources.forEach((source) => {
            const sourceElement = createSourceElement(resolvedType, source);

            if(sourceElement){
                hasSourceChildren = true;
                mediaElement.appendChild(sourceElement);
            }
        });
    }

    if(!hasSourceChildren && (cachedSrc || src)){
        mediaElement.src = cachedSrc || src;
    }

    return {
        node: mediaElement,
        mediaElement,
        type: resolvedType
    };
};

const renderMediaItem = async (media = {}) => {
    const targets = resolveTargets(media.targets || media.target);

    if(!targets.length){
        return media;
    }

    await Promise.allSettled(
        targets.map(async (target) => {
            const builtMedia = buildMediaNode(media);

            if(!builtMedia){
                return;
            }

            target.replaceWith(builtMedia.node);

            if(['video', 'audio'].includes(builtMedia.type)){
                builtMedia.mediaElement.load();
            }

            await waitForMediaDisplay(builtMedia.type, builtMedia.mediaElement);

            if(builtMedia.type === 'video' && builtMedia.mediaElement.autoplay){
                builtMedia.mediaElement.play?.().catch(() => null);
            }
        })
    );

    return media;
};

const renderMedias = (mediaPromise) => {
    return Promise.resolve(mediaPromise).then(async (mediaMap) => {
        if(!mediaMap || typeof mediaMap !== 'object'){
            return mediaMap || {};
        }

        const medias = Object.values(mediaMap)
            .flat()
            .filter(Boolean);

        if(!medias.length){
            return mediaMap;
        }

        await Promise.allSettled(
            medias.map((media) => renderMediaItem(media))
        );

        return mediaMap;
    });
};

export const Loader = {
    animation: defaultLoaderAnimation,
    immediateAnimation: defaultLoaderImmediate,
    isLoaded: false,
    currentRoute: null,
    routeReadyPath: null,
    routeReadyResolve: null,
    routeReady: Promise.resolve(),
    initialRouteKey: null,
    initialCriticalRequest: null,
    initialDeferredRequest: null,
    initialAnimationPromise: null,
    configure(){
        this.reset();
        ensureLoaderState(true);
        syncLoaderState(null);
        return this;
    },
    reset(){
        this.animation = defaultLoaderAnimation;
        this.immediateAnimation = defaultLoaderImmediate;
        this.isLoaded = false;
        this.currentRoute = null;
        this.routeReadyPath = null;
        this.routeReadyResolve = null;
        this.routeReady = Promise.resolve();
        this.initialRouteKey = null;
        this.initialCriticalRequest = null;
        this.initialDeferredRequest = null;
        this.initialAnimationPromise = null;

        return this;
    },
    state(reset = false){
        const state = ensureLoaderState(reset);

        if(reset){
            syncLoaderState(this.currentRoute);
        }

        return state;
    },
    syncState(route = this.currentRoute){
        return syncLoaderState(route || null);
    },
    setAnimation(animationFactory, immediateFactory = null){
        this.animation = typeof animationFactory === 'function'
            ? animationFactory
            : defaultLoaderAnimation;

        this.immediateAnimation = typeof immediateFactory === 'function'
            ? immediateFactory
            : animationFactory
                ? null
                : defaultLoaderImmediate;

        return this;
    },
    setRoute(route){
        const previousKey = getRouteKey(this.currentRoute);
        const nextKey = getRouteKey(route);

        this.currentRoute = route || null;
        this.syncState(this.currentRoute);

        if(nextKey !== previousKey || this.routeReadyPath !== nextKey){
            this.resetRouteReady(nextKey);
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
        return this.currentRoute || runtime.route || null;
    },
    setLabel(label){
        const labelNode = getLoaderLabel();

        if(labelNode && label){
            labelNode.textContent = label;
        }

        return this;
    },
    play(){
        return runAnimation({
            animationFactory: this.animation,
            immediateFactory: this.immediateAnimation,
            createContext
        });
    },
    init(){
        return this.play();
    },
    async download(routeOrPath = this.route()){
        const route = typeof routeOrPath === 'string'
            ? await fetchRoute(routeOrPath)
            : routeOrPath;

        if(!route){
            return null;
        }

        this.isLoaded = false;
        this.state().isLoaded = false;
        this.setRoute(route);
        createCriticalRequest(route);
        createDeferredRequest(route);
        this.syncState(route);

        return this.state().criticalDownload;
    },
    async prepareRoute(routeOrPath){
        const route = typeof routeOrPath === 'string'
            ? await fetchRoute(routeOrPath)
            : routeOrPath;

        const criticalRequest = createCriticalRequest(route);
        createDeferredRequest(route);

        if(getRouteKey(route) === getRouteKey(this.currentRoute)){
            this.syncState(route);
        }

        await criticalRequest.download;

        return route;
    },
    preloadDeferred(route){
        createDeferredRequest(route);

        if(getRouteKey(route) === getRouteKey(this.currentRoute)){
            this.syncState(route);
        }

        return this;
    },
    prepareInitialLoad(route = runtime.route){
        this.isLoaded = false;
        this.state().isLoaded = false;
        this.setRoute(route);
        this.initialRouteKey = getRouteKey(route || runtime.route);
        const criticalRequest = createCriticalRequest(route);
        const deferredRequest = createDeferredRequest(route);

        this.initialCriticalRequest = criticalRequest.download;
        this.initialDeferredRequest = deferredRequest.download;
        this.syncState(route);

        return this;
    },
    async finishInitialLoad(route = runtime.route){
        const resolvedRoute = route || runtime.route;

        if(!this.initialCriticalRequest || this.initialRouteKey !== getRouteKey(resolvedRoute)){
            this.prepareInitialLoad(resolvedRoute);
        }

        if(!this.initialAnimationPromise){
            this.initialAnimationPromise = this.init();
        }

        await this.display(resolvedRoute);
        await this.initialAnimationPromise;

        this.initialDeferredRequest?.catch(() => null);

        return resolvedRoute;
    },
    display(route = this.route()){
        const resolvedRoute = route || this.route();
        const state = this.syncState(resolvedRoute);

        if(!resolvedRoute){
            const promise = Promise.resolve(null);
            state.criticalDisplay = promise;
            return promise;
        }

        const promise = Promise.allSettled([
            state.criticalDownload || resolvedPromise(resolvedRoute),
            this.waitForRouteReady()
        ]).then(() => {
            return renderMedias(state.criticalMedias || resolvedPromise({}));
        }).then(() => {
            this.isLoaded = true;
            state.isLoaded = true;
            return resolvedRoute;
        });

        state.criticalDisplay = promise;

        return promise;
    },
    noCriticalDisplay(route = this.route()){
        const resolvedRoute = route || this.route();

        if(!resolvedRoute){
            const promise = Promise.resolve(null);
            this.state().noCriticalDisplay = promise;
            return promise;
        }

        const deferredRequest = createDeferredRequest(resolvedRoute);
        const state = this.syncState(resolvedRoute);
        const promise = Promise.allSettled([
            state.criticalDisplay || this.display(resolvedRoute),
            deferredRequest.download.catch(() => resolvedRoute)
        ]).then(() => {
            return renderMedias(state.noCriticalMedias || resolvedPromise({}));
        }).then(() => resolvedRoute);

        state.noCriticalDisplay = promise;

        return promise;
    }
};
