import { useEffect, useEffectEvent, useLayoutEffect, useRef, useState } from 'react';
import { useBlocker, useLocation, useNavigate } from 'react-router-dom';
import { runtime, normalizePath } from './Runtime';
import { fetchRoute } from './RouteService';
import { Loader } from './Loader';
import { PageTransitionAnimation, pageTransition } from './PageTransition';
import { scroller } from './Scroller';
import { useInternalNavigation } from './useInternalNavigation';
import { configureLoader } from './config/configureLoader';
import { configurePageTransition } from './config/configurePageTransition';

const scrollToHash = (hash) => {
    if(!hash){
        scroller.scrollTo(0, true);
        return;
    }

    scroller.scrollTo(hash, true);
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

export const useRouteTransition = () => {
    const navigate = useNavigate();
    const location = useLocation();
    const blocker = useBlocker(true);
    const [currentRoute, setCurrentRoute] = useState(runtime.route);
    const pendingRouteRef = useRef(null);
    const firstLoadRef = useRef(true);

    const currentPath = normalizePath(location.pathname);

    useInternalNavigation(navigate);

    const handleRouteReady = useEffectEvent((path) => {
        if(normalizePath(path) !== normalizePath(currentRoute.path)){
            return;
        }

        Loader.markRouteReady(path);
    });

    useEffect(() => {
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

        if(!pendingRoute || normalizePath(pendingRoute.path) !== currentPath){
            return;
        }

        setCurrentRoute({
            ...pendingRoute,
            path: normalizePath(pendingRoute.path)
        });

        pendingRouteRef.current = null;
    }, [currentPath]);

    useLayoutEffect(() => {
        if(!currentRoute){
            return;
        }

        const displayPromise = Loader.display();
        const noCriticalDisplayPromise = Loader.noCriticalDisplay();
        const loaderState = Loader.state();

        loaderState.criticalDisplay = displayPromise;
        loaderState.noCriticalDisplay = noCriticalDisplayPromise;
    }, [currentRoute?.path]);

    useEffect(() => {
        if(!currentRoute?.path || normalizePath(currentRoute.path) !== currentPath){
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

                scroller.unlock();
                scrollToHash(location.hash);
                return;
            }

            await Promise.resolve(window.loader.criticalDisplay);

            if(cancelled){
                return;
            }

            requestAnimationFrame(() => {
                if(cancelled){
                    return;
                }

                if(window.gscroll){
                    window.gscroll.scrollTop(0);
                } else if(!location.hash){
                    window.scrollTo({
                        top: 0,
                        behavior: 'auto'
                    });
                }

                scroller.refresh();

                requestAnimationFrame(() => {
                    if(cancelled){
                        return;
                    }

                    let animation = PageTransitionAnimation.enter({
                        location
                    });

                    onAnimationComplete(animation, () => {
                        animation?.kill?.();
                        animation = null;

                        requestAnimationFrame(() => {
                            if(cancelled){
                                return;
                            }

                            window.gscroll?.paused(false);
                            scroller.unlock();
                            scrollToHash(location.hash);
                            Loader.preloadDeferred(currentRoute);
                        });
                    });
                });
            });
        };

        run();

        return () => {
            cancelled = true;
        };
    }, [currentRoute?.path, currentPath, location.hash]);

    useEffect(() => {
        if(blocker.state !== 'blocked'){
            return;
        }

        const nextPath = normalizePath(blocker.location.pathname);
        const nextSearch = blocker.location.search || '';
        const nextHash = blocker.location.hash || '';

        if(nextPath === currentPath){
            blocker.proceed();
            requestAnimationFrame(() => scrollToHash(nextHash));
            return;
        }

        let cancelled = false;

        const transition = async () => {
            Loader.setLabel(`Preparing ${nextPath}`);
            scroller.lock();

            const route = await fetchRoute(`${nextPath}${nextSearch}`);
            const normalizedRoute = {
                ...route,
                path: normalizePath(route.path || nextPath)
            };

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

                if(window.gscroll){
                    window.gscroll.scrollTop(0);
                } else if(!nextHash){
                    window.scrollTo({
                        top: 0,
                        behavior: 'auto'
                    });
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
    }, [blocker, currentPath]);

    return {
        currentRoute,
        handleRouteReady
    };
};
