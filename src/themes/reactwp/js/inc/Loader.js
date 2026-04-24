import { runtime, createRouteKey } from './Runtime';
import { fetchRoute } from './RouteService';
import { resolveTemplateEntry } from './TemplateRegistry';
import { gsap, ScrollTrigger, prefersReducedMotion, runAnimation } from './motion';

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
    criticalMedias: resolvedPromise([]),
    noCriticalMedias: resolvedPromise([]),
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

const mediaRequests = new Map();
const fontRequests = new Map();
const criticalRouteRequests = new Map();
const deferredRouteRequests = new Map();

const syncLoaderState = (route = null) => {
    const state = ensureLoaderState();
    const criticalRequest = route?.path ? criticalRouteRequests.get(route.path) : null;
    const deferredRequest = route?.path ? deferredRouteRequests.get(route.path) : null;

    state.route = route || null;
    state.template = criticalRequest?.template || resolvedPromise(route || null);
    state.criticalFonts = criticalRequest?.fonts || resolvedPromise([]);
    state.criticalMedias = criticalRequest?.medias || resolvedPromise([]);
    state.noCriticalMedias = deferredRequest?.medias || resolvedPromise([]);
    state.criticalDownload = criticalRequest?.download || resolvedPromise(route || null);
    state.noCriticalDownload = deferredRequest?.download || resolvedPromise(route || null);

    return state;
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

const collectAssets = (map = {}, route) => {
    return getGroups(route)
        .flatMap((group) => map[group] || [])
        .filter(Boolean);
};

const preloadFonts = async (route) => {
    if(!document.fonts){
        return;
    }

    const fonts = collectAssets(runtime.assets.criticalFonts || {}, route);

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
};

const preloadMediaEntries = async (entries = []) => {
    await Promise.allSettled(
        entries
            .filter((item) => item?.src)
            .map((media) => {
                if(mediaRequests.has(media.src)){
                    return mediaRequests.get(media.src);
                }

                const request = fetch(media.src, {
                    credentials: 'same-origin'
                }).catch(() => null);

                mediaRequests.set(media.src, request);

                return request;
            })
    );
};

const preloadCriticalMedia = async (route) => {
    await preloadMediaEntries(
        collectAssets(runtime.assets.criticalMedias || {}, route)
    );
};

const preloadDeferredMedia = async (route) => {
    await preloadMediaEntries(
        collectAssets(runtime.assets.noCriticalMedias || {}, route)
    );
};

const preloadTemplate = async (route) => {
    const templateEntry = resolveTemplateEntry(route.template);
    await templateEntry.preload();
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
    if(!route?.path){
        return {
            route,
            template: resolvedPromise(route),
            fonts: resolvedPromise([]),
            medias: resolvedPromise([]),
            download: resolvedPromise(route)
        };
    }

    if(criticalRouteRequests.has(route.path)){
        return criticalRouteRequests.get(route.path);
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

    criticalRouteRequests.set(route.path, request);

    return request;
};

const createDeferredRequest = (route) => {
    const key = route?.path || '__global__';

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

        if(route?.path && route.path === this.currentRoute?.path){
            this.syncState(route);
        }

        await criticalRequest.download;

        return route;
    },
    preloadDeferred(route){
        createDeferredRequest(route);

        if(route?.path && route.path === this.currentRoute?.path){
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
        ]).then(() => resolvedRoute);

        state.noCriticalDisplay = promise;

        return promise;
    }
};
