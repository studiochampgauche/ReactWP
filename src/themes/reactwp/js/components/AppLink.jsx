import { Link } from 'react-router-dom';
import { Loader } from '../inc/Loader';

const resolveHref = (to = '/') => {
    if(typeof to === 'string'){
        return to || '/';
    }

    if(to && typeof to === 'object'){
        return `${to.pathname || '/'}${to.search || ''}${to.hash || ''}`;
    }

    return '/';
};

const AppLink = ({ to = '/', onMouseEnter, onFocus, ...props }) => {
    const href = resolveHref(to);
    const routerEnabled = !(props['data-router'] === false || props['data-router'] === 'false');

    const prefetch = () => {
        Loader.prepareRoute(href).catch(() => null);
    };

    if(!routerEnabled){
        return (
            <a
                href={href}
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
