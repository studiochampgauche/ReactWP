import { forwardRef } from 'react';
import { Link } from 'react-router';
import { normalizePath, normalizeSearch } from '../inc/Runtime';
import { sanitizeDomProps } from '../inc/domProps';

const resolveHref = (to = '/') => {
    if(typeof to === 'string'){
        return to || '/';
    }

    if(to && typeof to === 'object'){
        return `${to.pathname || '/'}${to.search || ''}${to.hash || ''}`;
    }

    return '/';
};

const isExternalHref = (href = '') => /^(?:(?:https?:)?\/\/|mailto:|tel:)/i.test(href);

const isSafeHref = (href = '') => {
    const value = String(href || '').trim();

    return value !== ''
        && value.length <= 4096
        && !/[\u0000-\u001f\u007f\\]/.test(value)
        && (!/^[a-z][a-z0-9+.-]*:/i.test(value) || isExternalHref(value));
};

const isModifiedEvent = (event) => {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
};

const getHashElement = (hash) => {
    if(typeof document === 'undefined'){
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

const getLocalHash = (href = '') => {
    if(
        typeof window === 'undefined'
        || typeof href !== 'string'
        || !href.includes('#')
    ){
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
    return getHashElement(hash) ? hash : null;
};

const waitForFrame = () => new Promise((resolve) => requestAnimationFrame(resolve));

const waitForHashTarget = async (hash, attempts = 8) => {
    for(let attempt = 0; attempt <= attempts; attempt += 1){
        const target = resolveHashTarget(hash);

        if(target !== null){
            return target;
        }

        if(attempt < attempts){
            await waitForFrame();
        }
    }

    return null;
};

const scrollToHash = (hash) => {
    requestAnimationFrame(() => {
        import('../inc/Scroller').then(async ({ scroller }) => {
            const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)')?.matches === true;
            const target = await waitForHashTarget(hash);

            if(target === null){
                return;
            }

            window.gscroll?.paused?.(false);
            scroller.refresh();
            scroller.scrollTo(target, !reduceMotion);
        });
    });
};

const AppLink = forwardRef(function AppLink({
    to = '/',
    updateHash = true,
    onMouseEnter,
    onFocus,
    onClick,
    children,
    ...props
}, ref){
    const requestedHref = resolveHref(to);
    const href = isSafeHref(requestedHref) ? requestedHref : '/';
    const destination = href === requestedHref ? to : '/';
    const routerEnabled = !(props['data-router'] === false || props['data-router'] === 'false');
    const localHash = getLocalHash(href);
    const external = isExternalHref(href);
    const domProps = sanitizeDomProps(props);
    const target = /^_(?:blank|self|parent|top)$/.test(String(domProps.target || ''))
        ? domProps.target
        : undefined;

    delete domProps.target;

    const prefetch = () => {
        import('../inc/Loader').then(({ Loader }) => {
            return Loader.prepareRoute(href);
        }).catch(() => null);
    };

    if(!routerEnabled || localHash || external){
        const rel = target === '_blank'
            ? ['noopener', 'noreferrer', domProps.rel].filter(Boolean).join(' ')
            : domProps.rel;

        return (
            <a
                ref={ref}
                {...domProps}
                href={href}
                onClick={(event) => {
                    onClick?.(event);

                    if(localHash === '#'){
                        event.preventDefault();
                        return;
                    }

                    if(
                        !localHash
                        || isModifiedEvent(event)
                        || event.defaultPrevented
                    ){
                        return;
                    }

                    event.preventDefault();

                    if(updateHash && window.location.hash !== localHash){
                        window.history.pushState(null, '', localHash);
                    }

                    scrollToHash(localHash);
                }}
                onMouseEnter={onMouseEnter}
                onFocus={onFocus}
                target={target}
                rel={rel}
            >
                {children}
            </a>
        );
    }

    return (
        <Link
            ref={ref}
            {...domProps}
            to={destination}
            onMouseEnter={(event) => {
                prefetch();
                onMouseEnter?.(event);
            }}
            onFocus={(event) => {
                prefetch();
                onFocus?.(event);
            }}
            target={target}
        >
            {children}
        </Link>
    );
});

export default AppLink;
