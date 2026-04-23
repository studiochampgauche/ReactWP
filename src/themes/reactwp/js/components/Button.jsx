import AppLink from './AppLink';

const isExternal = (url = '') => {
    return /^https?:\/\//i.test(url) || url.startsWith('mailto:') || url.startsWith('tel:');
};

const renderInlineSlot = (value, className) => {
    if(value == null || value === false || value === ''){
        return null;
    }

    if(typeof value === 'string'){
        return (
            <span
                className={className}
                dangerouslySetInnerHTML={{ __html: value }}
            />
        );
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
    const destination = to || href;
    const classes = ['button', `button--${variant}`, className].filter(Boolean).join(' ');
    const label = text ?? children;

    const content = (
        <>
            {renderInlineSlot(before, 'button__before')}
            {renderInlineSlot(label, 'button__text')}
            {renderInlineSlot(after, 'button__after')}
        </>
    );

    if(!destination){
        return (
            <button className={classes} {...props}>
                {content}
            </button>
        );
    }

    if(isExternal(destination)){
        return (
            <a className={classes} href={destination} {...props}>
                {content}
            </a>
        );
    }

    return (
        <AppLink className={classes} to={destination} {...props}>
            {content}
        </AppLink>
    );
};

export default Button;