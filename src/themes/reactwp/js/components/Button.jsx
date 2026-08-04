import AppLink from './AppLink';
import { sanitizeDomProps } from '../inc/domProps';

const isExternal = (url = '') => {
    return typeof url === 'string'
        && (/^(?:https?:)?\/\//i.test(url) || /^mailto:/i.test(url) || /^tel:/i.test(url));
};

const normalizeStringDestination = (destination) => {
    if(typeof destination !== 'string'){
        return null;
    }

    const value = destination.trim();

    if(!value || value.length > 4096 || /[\u0000-\u001f\u007f\\]/.test(value)){
        return null;
    }

    return !/^[a-z][a-z0-9+.-]*:/i.test(value) || isExternal(value)
        ? value
        : null;
};

const normalizeDestination = (destination) => {
    if(typeof destination === 'string'){
        return normalizeStringDestination(destination);
    }

    if(!destination || typeof destination !== 'object' || Array.isArray(destination)){
        return null;
    }

    const pathname = normalizeStringDestination(destination.pathname || '/');
    const search = destination.search == null ? '' : String(destination.search);
    const hash = destination.hash == null ? '' : String(destination.hash);

    if(
        !pathname
        || isExternal(pathname)
        || (search && (search.length > 2048 || !search.startsWith('?') || /[\u0000-\u001f\u007f\\]/.test(search)))
        || (hash && (hash.length > 2048 || !hash.startsWith('#') || /[\u0000-\u001f\u007f\\]/.test(hash)))
    ){
        return null;
    }

    return { pathname, search, hash };
};

const renderInlineSlot = (value, className) => {
    if(value == null || value === false || value === ''){
        return null;
    }

    return <span className={className}>{value}</span>;
};

const Button = ({
    to = null,
    href = null,
    text = null,
    before = null,
    after = null,
    children,
    className = '',
    variant = 'primary',
    ...props
}) => {
    const requestedDestination = to || href;
    const destination = normalizeDestination(requestedDestination);
    const classes = ['button', `button--${variant}`, className].filter(Boolean).join(' ');
    const label = text ?? children;
    const domProps = sanitizeDomProps(props);
    const target = /^_(?:blank|self|parent|top)$/.test(String(domProps.target || ''))
        ? domProps.target
        : undefined;

    delete domProps.target;

    const content = (
        <>
            {renderInlineSlot(before, 'button__before')}
            {renderInlineSlot(label, 'button__text')}
            {renderInlineSlot(after, 'button__after')}
        </>
    );

    if(!destination){
        return (
            <button {...domProps} className={classes}>
                {content}
            </button>
        );
    }

    if(typeof destination === 'string' && isExternal(destination)){
        const rel = target === '_blank'
            ? ['noopener', 'noreferrer', domProps.rel].filter(Boolean).join(' ')
            : domProps.rel;

        return (
            <a {...domProps} className={classes} href={destination} target={target} rel={rel}>
                {content}
            </a>
        );
    }

    return (
        <AppLink {...domProps} className={classes} to={destination} target={target}>
            {content}
        </AppLink>
    );
};

export default Button;
