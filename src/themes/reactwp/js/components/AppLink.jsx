import { Link } from 'react-router-dom';
import { Loader } from '../inc/Loader';
import { scroller } from '../inc/Scroller';
import { normalizePath, normalizeSearch } from '../inc/Runtime';

const resolveHref = (to = '/') => {
    if(typeof to === 'string'){
        return to || '/';
    }

    if(to && typeof to === 'object'){
        return `${to.pathname || '/'}${to.search || ''}${to.hash || ''}`;
    }

    return '/';
};

const isModifiedEvent = (event) => {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
};

const getHashElement = (hash) => {
    const id = decodeURIComponent(hash.slice(1));

    return document.getElementById(id) || document.querySelector(hash);
};

const getLocalHash = (href = '') => {
    if(typeof href !== 'string' || !href.includes('#')){
        return '';
    }

    if(href.startsWith('#')){
        return href;
    }

    try{
        const destination = new URL(href, window.location.origin);

        if(
            destination.origin !== window.location.origin
            || !destination.hash
            || normalizePath(destination.pathname) !== normalizePath(window.location.pathname)
            || normalizeSearch(destination.search) !== normalizeSearch(window.location.search)
        ){
            return '';
        }

        return destination.hash;
    } catch(_error){
        return '';
    }
};

const resolveHashTarget = (hash) => {
    if(hash === '#'){
        return 0;
    }

    const element = getHashElement(hash);

    if(!element){
        return hash;
    }

    return Math.max(0, element.getBoundingClientRect().top + scroller.getScrollTop());
};

const scrollToHash = (hash, target = resolveHashTarget(hash)) => {
    requestAnimationFrame(() => {
        window.gscroll?.paused?.(false);
        scroller.scrollTo(target, true);
    });
};

const AppLink = ({ to = '/', onMouseEnter, onFocus, onClick, ...props }) => {
    const href = resolveHref(to);
    const routerEnabled = !(props['data-router'] === false || props['data-router'] === 'false');
    const localHash = getLocalHash(href);

    const prefetch = () => {
        Loader.prepareRoute(href).catch(() => null);
    };

    if(!routerEnabled || localHash){
        return (
            <a
                href={href}
                onClick={(event) => {
                    onClick?.(event);

                    if(
                        !localHash
                        || isModifiedEvent(event)
                        || event.defaultPrevented
                    ){
                        return;
                    }

                    event.preventDefault();

                    const target = resolveHashTarget(localHash);

                    if(window.location.hash !== localHash){
                        window.history.pushState(null, '', localHash);
                    }

                    scrollToHash(localHash, target);
                }}
                onMouseEnter={onMouseEnter}
                onFocus={onFocus}
                {...props}
            />
        );
    }

    return (
        <Link
            to={to}
            onMouseEnter={(event) => {
                prefetch();
                onMouseEnter?.(event);
            }}
            onFocus={(event) => {
                prefetch();
                onFocus?.(event);
            }}
            {...props}
        />
    );
};

export default AppLink;
