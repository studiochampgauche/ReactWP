const BLOCKED_PROPS = new Set([
    'children',
    'dangerouslySetInnerHTML',
    'formAction',
    'innerHTML',
    'key',
    'outerHTML',
    'ref',
    'srcDoc'
]);

const sanitizeStyle = (style) => {
    if(!style || typeof style !== 'object' || Array.isArray(style)){
        return null;
    }

    return Object.fromEntries(
        Object.entries(style).slice(0, 100).filter(([property, value]) => {
            if(
                !/^(?:--[a-z0-9_-]+|[a-z][a-z0-9]*)$/i.test(property)
                || !['string', 'number'].includes(typeof value)
            ){
                return false;
            }

            const normalizedValue = String(value);

            return normalizedValue.length <= 2048
                && !/(?:expression\s*\(|@import|javascript:|-moz-binding)/i.test(normalizedValue);
        })
    );
};

export const sanitizeDomProps = (props = {}) => {
    if(!props || typeof props !== 'object' || Array.isArray(props)){
        return {};
    }

    return Object.fromEntries(
        Object.entries(props).filter(([name, value]) => {
            if(BLOCKED_PROPS.has(name)){
                return false;
            }

            if(/^on[A-Z]/.test(name) || /^on[a-z]/.test(name)){
                return typeof value === 'function';
            }

            if(name === 'style'){
                return Boolean(sanitizeStyle(value));
            }

            return value == null
                || ['boolean', 'number'].includes(typeof value)
                || (typeof value === 'string' && value.length <= 65536);
        }).map(([name, value]) => [
            name,
            name === 'style' ? sanitizeStyle(value) : value
        ])
    );
};

export const normalizeHeadingTag = (value, fallback = 'h2') => {
    const tag = String(value || '').toLowerCase();

    return /^h[1-6]$/.test(tag) ? tag : fallback;
};
