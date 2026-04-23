import { useEffect } from 'react';

const isModifiedEvent = (event) => {
    return event.metaKey || event.ctrlKey || event.shiftKey || event.altKey;
};

export const useInternalNavigation = (navigate) => {
    useEffect(() => {
        const handleClick = (event) => {
            const anchor = event.target.closest('a');

            if(event.target.closest('#app')){
                return;
            }

            if(
                !anchor
                || anchor.dataset.router === 'false'
                || anchor.hasAttribute('download')
                || anchor.target
                || isModifiedEvent(event)
                || event.defaultPrevented
            ){
                return;
            }

            try{
                const destination = new URL(anchor.href, window.location.origin);

                if(destination.origin !== window.location.origin){
                    return;
                }

                event.preventDefault();
                navigate(`${destination.pathname}${destination.search}${destination.hash}`);
            } catch(_error){
                // Ignore malformed href values.
            }
        };

        document.addEventListener('click', handleClick);

        return () => {
            document.removeEventListener('click', handleClick);
        };
    }, [navigate]);
};
