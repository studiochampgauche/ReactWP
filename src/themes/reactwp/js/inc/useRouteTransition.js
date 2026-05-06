import { useEffect, useEffectEvent, useLayoutEffect, useRef, useState } from 'react';
import { useBlocker, useLocation, useNavigate } from 'react-router-dom';
import { runtime, normalizePath, normalizeSearch, createRouteKey, normalizeRoute } from './Runtime';
import { fetchRoute } from './RouteService';
import { Loader } from './Loader';
import { PageTransitionAnimation, pageTransition } from './PageTransition';
import { scroller } from './Scroller';
import { useInternalNavigation } from './useInternalNavigation';
import { configureLoader } from './config/configureLoader';
import { configurePageTransition } from './config/configurePageTransition';

const getHashElement = (hash) => {
    if(!hash || hash === '#'){
        return null;
    }

    const id = decodeURIComponent(hash.slice(1));

    return document.getElementById(id) || document.querySelector(hash);
};

const resolveHashTarget = (hash) => {
    if(hash === '#'){
        return 0;
    }

    const element = getHashElement(hash);

    if(!element){
        return null;
    }

    return Math.max(0, element.getBoundingClientRect().top + scroller.getScrollTop());
};

const waitForFrame = () => new Promise((resolve) => requestAnimationFrame(resolve));

const resolveRouteScrollTarget = async (hash, attempts = 8) => {
    if(!hash){
        return 0;
    }

    for(let attempt = 0; attempt <= attempts; attempt += 1){
        scroller.refresh();

        const target = resolveHashTarget(hash);

        if(target != null){
            return target;
        }

        await waitForFrame();
    }

    return 0;
};

const setRouteScrollPosition = async (hash, attempts = 8) => {
    const target = await resolveRouteScrollTarget(hash, attempts);

    scroller.scrollTo(target, false);
    scroller.setLockScrollTop(target);

    return target;
};

const scrollToHash = async (hash, attempts = 8) => {
    const target = await resolveRouteScrollTarget(hash, attempts);

    requestAnimationFrame(() => {
        window.gscroll?.paused?.(false);
        scroller.refresh();
        scroller.scrollTo(target, true);
    });
};

const onAnimationComplete = (animation, callback) => {
    let called = false;
    const done = () => {
        if(called){
            return;
        }

        called = true;
        callback();
    };

    if(!animation){
        done();
        return;
    }

    if(typeof animation.eventCallback === 'function'){
        const previousOnComplete = animation.eventCallback('onComplete');

        animation.eventCallback('onComplete', (...args) => {
            previousOnComplete?.apply(animation, args);
            done();
        });

        const totalDuration = typeof animation.totalDuration === 'function'
            ? animation.totalDuration()
            : null;

        if(totalDuration === 0){
            requestAnimationFrame(done);
        }

        return;
    }

    if(typeof animation.then === 'function'){
        Promise.resolve(animation).finally(done);
        return;
    }

    done();
};

const resolveRouteKey = (route) => {
    return route?.key || createRouteKey(route?.path || route?.pathname || '/', route?.search || '');
};

export const useRouteTransition = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const blocker = useBlocker(true);
    const [currentRoute, setCurrentRoute] = useState(runtime.route);
    const [headerKey, setHeaderKey] = useState(resolveRouteKey(runtime.route));
    const pendingRouteRef = useRef(null);
    const firstLoadRef = useRef(true);

    const currentPath = normalizePath(location.pathname);
    const currentSearch = normalizeSearch(location.search || '');
    const currentRouteKey = createRouteKey(currentPath, currentSearch);

    useInternalNavigation(navigate);

    const handleRouteReady = useEffectEvent((route) => {
        const routeKey = resolveRouteKey(route);

        if(routeKey !== currentRoute.key){
            return;
        }

        Loader.markRouteReady(routeKey);
    });

    useLayoutEffect(() => {
        Loader.configure();
        configureLoader();
        Loader.setRoute(runtime.route);
        Loader.prepareInitialLoad(runtime.route);
        PageTransitionAnimation.configure();
        configurePageTransition();
        pageTransition.setup();

        scroller.init();
        scroller.lock();

        return () => {
            scroller.kill();
        };
    }, []);

    useEffect(() => {
        Loader.setRoute(currentRoute);
    }, [currentRoute]);

    useLayoutEffect(() => {
        const pendingRoute = pendingRouteRef.current;

        if(!pendingRoute || resolveRouteKey(pendingRoute) !== currentRouteKey){
            return;
        }

        setCurrentRoute(normalizeRoute(pendingRoute, pendingRoute.path, pendingRoute.search));
        pendingRouteRef.current = null;
    }, [currentRouteKey]);

    useLayoutEffect(() => {
        if(!currentRoute){
            return;
        }

        const displayPromise = Loader.display();
        const noCriticalDisplayPromise = Loader.noCriticalDisplay();
        const loaderState = Loader.state();

        loaderState.criticalDisplay = displayPromise;
        loaderState.noCriticalDisplay = noCriticalDisplayPromise;
    }, [currentRoute?.key]);

    useEffect(() => {
        if(!currentRoute?.key || currentRoute.key !== currentRouteKey){
            return;
        }

        let cancelled = false;

        const run = async () => {
            if(firstLoadRef.current){
                firstLoadRef.current = false;
                scroller.refresh();
                const initialLoadPromise = Loader.finishInitialLoad(currentRoute);
                const loaderState = Loader.state();

                loaderState.init = initialLoadPromise;

                await initialLoadPromise;

                if(cancelled){
                    return;
                }

                await setRouteScrollPosition(location.hash);

                if(cancelled){
                    return;
                }

                scroller.unlock();
                setHeaderKey(resolveRouteKey(currentRoute));
                return;
            }

            await Promise.resolve(window.loader.criticalDisplay);

            if(cancelled){
                return;
            }

            requestAnimationFrame(() => {
                const prepare = async () => {
                    if(cancelled){
                        return;
                    }

                    await setRouteScrollPosition(location.hash);

                    if(cancelled){
                        return;
                    }

                    scroller.refresh();

                    let animation = PageTransitionAnimation.enter({
                        location
                    });

                    onAnimationComplete(animation, () => {
                        animation?.kill?.();
                        animation = null;

                        requestAnimationFrame(() => {
                            const complete = async () => {
                                if(cancelled){
                                    return;
                                }

                                window.gscroll?.paused(false);
                                await setRouteScrollPosition(location.hash, 2);

                                if(cancelled){
                                    return;
                                }

                                scroller.unlock();
                                setHeaderKey(resolveRouteKey(currentRoute));
                                Loader.preloadDeferred(currentRoute);
                            };

                            complete().catch((error) => {
                                console.warn('ReactWP route scroll failed.', error);
                                if(cancelled){
                                    return;
                                }

                                window.gscroll?.paused(false);
                                scroller.unlock();
                                setHeaderKey(resolveRouteKey(currentRoute));
                                Loader.preloadDeferred(currentRoute);
                            });
                        });
                    });
                };

                prepare().catch((error) => {
                    console.warn('ReactWP route scroll failed.', error);

                    if(cancelled){
                        return;
                    }

                    scroller.refresh();
                    scroller.unlock();
                });
            });
        };

        run();

        return () => {
            cancelled = true;
        };
    }, [currentRoute?.key, currentRouteKey]);

    useEffect(() => {
        if(blocker.state !== 'blocked'){
            return;
        }

        const nextPath = normalizePath(blocker.location.pathname);
        const nextSearch = normalizeSearch(blocker.location.search || '');
        const nextHash = blocker.location.hash || '';
        const nextRouteKey = createRouteKey(nextPath, nextSearch);

        if(nextRouteKey === currentRouteKey){
            blocker.proceed();
            requestAnimationFrame(() => scrollToHash(nextHash));
            return;
        }

        let cancelled = false;

        const transition = async () => {
            Loader.setLabel(`Preparing ${nextPath}${nextSearch}`);
            scroller.lock();

            const route = await fetchRoute(`${nextPath}${nextSearch}`);
            const normalizedRoute = normalizeRoute(route, nextPath, nextSearch);

            pendingRouteRef.current = normalizedRoute;
            Loader.setRoute(normalizedRoute);
            const criticalRequest = Loader.prepareRoute(normalizedRoute);

            if(cancelled){
                return;
            }

            let animation = PageTransitionAnimation.leave({
                blocker,
                location
            });

            onAnimationComplete(animation, () => {
                animation?.kill?.();
                animation = null;

                if(cancelled){
                    return;
                }

                window.gscroll?.paused(true);

                if(window.gscroll && !nextHash){
                    window.gscroll.scrollTop(0);
                    scroller.setLockScrollTop(0);
                } else if(!nextHash){
                    window.scrollTo({
                        top: 0,
                        behavior: 'auto'
                    });
                    scroller.setLockScrollTop(0);
                }

                Promise.resolve(criticalRequest).then(() => {
                    if(cancelled){
                        return;
                    }

                    return Loader.waitForPaint(1).then(() => {
                        if(cancelled){
                            return;
                        }

                        blocker.proceed();
                    });
                });
            });
        };

        transition().catch((error) => {
            console.warn('ReactWP route transition failed.', error);
            window.location.assign(`${blocker.location.pathname}${nextSearch}${nextHash}`);
        });

        return () => {
            cancelled = true;
        };
    }, [blocker, currentRouteKey]);

    return {
        currentRoute,
        headerKey,
        footerKey: headerKey,
        handleRouteReady
    };
};
