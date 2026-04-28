import { Link } from 'react-router-dom';
import { Loader } from '../inc/Loader';
import { scroller } from '../inc/Scroller';

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

const scrollToHash = (hash, done = () => null) => {
    const scroll = () => {
        window.gscroll?.paused?.(false);
        scroller.refresh();
        scroller.scrollTo(resolveHashTarget(hash), true);
    };

    requestAnimationFrame(() => {
        scroll();
        requestAnimationFrame(scroll);
        window.setTimeout(() => {
            scroll();
            done();
        }, 80);
    });
};

const AppLink = ({ to = '/', onMouseEnter, onFocus, onClick, ...props }) => {
    const href = resolveHref(to);
    const routerEnabled = !(props['data-router'] === false || props['data-router'] === 'false');
    const isHashOnly = typeof href === 'string' && href.startsWith('#');

    const prefetch = () => {
        Loader.prepareRoute(href).catch(() => null);
    };

    if(!routerEnabled || isHashOnly){
        return (
            <a
                href={href}
                onClick={(event) => {
                    onClick?.(event);

                    if(
                        !isHashOnly
                        || isModifiedEvent(event)
                        || event.defaultPrevented
                    ){
                        return;
                    }

                    event.preventDefault();

                    scrollToHash(href, () => {
                        if(window.location.hash !== href){
                            window.history.pushState(null, '', href);
                        }

                        requestAnimationFrame(() => {
                            scroller.refresh();
                            scroller.scrollTo(resolveHashTarget(href), true);
                        });
                    });
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
