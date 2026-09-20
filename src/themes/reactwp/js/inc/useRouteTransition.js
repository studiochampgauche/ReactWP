import { useEffect, useEffectEvent, useLayoutEffect, useRef, useState } from 'react';
import { useBlocker, useLocation, useNavigate } from 'react-router';
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

    let id = hash.slice(1);

    try {
        id = decodeURIComponent(id);
    } catch(error){
        id = hash.slice(1);
    }

    const element = document.getElementById(id);

    if(element){
        return element;
    }

    try {
        return document.querySelector(hash);
    } catch(error){
        return null;
    }
};

const waitForFrame = () => new Promise((resolve) => requestAnimationFrame(resolve));

const waitForHashTarget = async (hash, attempts = 8) => {
    if(!hash || hash === '#'){
        return 0;
    }

    for(let attempt = 0; attempt <= attempts; attempt += 1){
        scroller.refresh();

        if(getHashElement(hash)){
            return hash;
        }

        await waitForFrame();
    }

    return null;
};

const scrollToRouteTarget = async (hash, smooth = false, attempts = 8) => {
    const target = await waitForHashTarget(hash, attempts);

    if(target === null){
        return;
    }

    window.gscroll?.paused?.(false);
    scroller.refresh();
    scroller.scrollTo(target, smooth);

    if(!smooth){
        await waitForFrame();
        scroller.refresh();
        scroller.scrollTo(target, false);
    }
};

const onAnimationComplete = (animation, callback, onError = callback) => {
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
        Promise.resolve(animation).then(done, onError);
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
    const interruptedLeaveRef = useRef(false);
    const pageAnimationRef = useRef(null);

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

        if('scrollRestoration' in window.history){
            window.history.scrollRestoration = 'manual';
        }

        scroller.init();
        scroller.lock();

        return () => {
            pageAnimationRef.current?.kill?.();
            pageAnimationRef.current = null;
            scroller.kill();
        };
    }, []);

    useEffect(() => {
        Loader.setRoute(currentRoute);
    }, [currentRoute]);

    useEffect(() => {
        const language = currentRoute?.lang;

        if(typeof language === 'string' && language){
            window.UniversalLegalConsent?.setLanguage?.(language);
        }
    }, [currentRoute?.lang]);

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

        const finishEnter = () => {
            let animation = PageTransitionAnimation.enter({
                location
            });
            pageAnimationRef.current = animation;

            onAnimationComplete(animation, () => {
                animation?.kill?.();

                if(pageAnimationRef.current === animation){
                    pageAnimationRef.current = null;
                }

                animation = null;

                requestAnimationFrame(() => {
                    if(cancelled){
                        return;
                    }

                    window.gscroll?.paused?.(false);
                    setHeaderKey(resolveRouteKey(currentRoute));
                    Loader.preloadDeferred(currentRoute);
                });
            });
        };

        const run = async () => {
            if(firstLoadRef.current){
                firstLoadRef.current = false;
                scroller.refresh();
                const initialLoadPromise = Loader.finishInitialLoad(currentRoute);

                await initialLoadPromise;

                if(cancelled){
                    return;
                }

                await scrollToRouteTarget(location.hash, false);
                scroller.setLockScrollTop(scroller.getScrollTop());
                scroller.unlock();

                if(cancelled){
                    return;
                }

                setHeaderKey(resolveRouteKey(currentRoute));
                return;
            }

            await Promise.resolve(window.loader.criticalDisplay);

            if(cancelled){
                return;
            }

            requestAnimationFrame(() => {
                const prepare = async () => {
                    await scrollToRouteTarget(location.hash, false);
                    scroller.setLockScrollTop(scroller.getScrollTop());
                    scroller.unlock();

                    if(cancelled){
                        return;
                    }

                    scroller.refresh();
                    finishEnter();
                };

                prepare().catch((error) => {
                    console.warn('ReactWP route scroll failed.', error);

                    if(cancelled){
                        return;
                    }

                    scroller.unlock();
                    scroller.refresh();
                    finishEnter();
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
            if(interruptedLeaveRef.current){
                interruptedLeaveRef.current = false;
                Loader.setRoute(currentRoute);
                Loader.markRouteReady(resolveRouteKey(currentRoute));
                let animation = PageTransitionAnimation.enter({ location: blocker.location });
                pageAnimationRef.current = animation;

                onAnimationComplete(animation, () => {
                    animation?.kill?.();

                    if(pageAnimationRef.current === animation){
                        pageAnimationRef.current = null;
                    }

                    animation = null;
                });
            }

            blocker.proceed();
            requestAnimationFrame(() => {
                scrollToRouteTarget(nextHash, true);
            });
            return;
        }

        let cancelled = false;
        let proceeded = false;
        let animation = null;
        let preparedRoute = null;

        const transition = async () => {
            interruptedLeaveRef.current = false;
            pageAnimationRef.current?.kill?.();
            pageAnimationRef.current = null;
            Loader.setLabel(`Preparing ${nextPath}${nextSearch}`);
            scroller.lock();

            // Leave immediately; payload and critical assets load alongside it.
            const leaveRequest = new Promise((resolve, reject) => {
                animation = PageTransitionAnimation.leave({ blocker, location });
                pageAnimationRef.current = animation;

                onAnimationComplete(animation, () => {
                    animation?.kill?.();

                    if(pageAnimationRef.current === animation){
                        pageAnimationRef.current = null;
                    }

                    animation = null;

                    if(cancelled){
                        resolve();
                        return;
                    }

                    window.gscroll?.paused?.(true);

                    if(window.gscroll && !nextHash){
                        window.gscroll.scrollTop(0);
                        scroller.setLockScrollTop(0);
                    } else if(!nextHash){
                        window.scrollTo({ top: 0, behavior: 'auto' });
                        scroller.setLockScrollTop(0);
                    }

                    resolve();
                }, reject);
            });

            const prepare = async () => {
                const route = await fetchRoute(`${nextPath}${nextSearch}`);

                if(cancelled){
                    return;
                }

                preparedRoute = normalizeRoute(route, nextPath, nextSearch);
                pendingRouteRef.current = preparedRoute;
                Loader.setRoute(preparedRoute);
                await Loader.prepareRoute(preparedRoute);
            };

            await Promise.all([leaveRequest, prepare()]);

            if(cancelled){
                return;
            }

            await Loader.waitForPaint(1);

            if(cancelled){
                return;
            }

            proceeded = true;
            blocker.proceed();
        };

        transition().catch((error) => {
            if(cancelled){
                return;
            }

            cancelled = true;
            animation?.kill?.();

            if(pageAnimationRef.current === animation){
                pageAnimationRef.current = null;
            }

            animation = null;
            console.warn('ReactWP route transition failed.', error);
            window.location.assign(`${blocker.location.pathname}${nextSearch}${nextHash}`);
        });

        return () => {
            cancelled = true;
            animation?.kill?.();

            if(pageAnimationRef.current === animation){
                pageAnimationRef.current = null;
            }

            animation = null;

            if(!proceeded){
                interruptedLeaveRef.current = true;
                if(pendingRouteRef.current === preparedRoute){
                    pendingRouteRef.current = null;
                }

                scroller.unlock();
            }
        };
    }, [blocker, currentRouteKey]);

    return {
        currentRoute,
        headerKey,
        footerKey: headerKey,
        handleRouteReady
    };
};
