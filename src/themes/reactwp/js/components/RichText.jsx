import { createElement } from 'react';
import parse, { domToReact, Element } from 'html-react-parser';

const ALLOWED_TAGS = new Set([
    'a', 'abbr', 'b', 'blockquote', 'br', 'cite', 'code', 'del', 'details',
    'div', 'em', 'figcaption', 'figure', 'h1', 'h2', 'h3', 'h4', 'h5',
    'h6', 'hr', 'i', 'img', 'li', 'ol', 'p', 'pre', 'q', 's', 'small',
    'span', 'strong', 'sub', 'summary', 'sup', 'table', 'tbody', 'td',
    'tfoot', 'th', 'thead', 'time', 'tr', 'u', 'ul'
]);
const VOID_TAGS = new Set(['br', 'hr', 'img']);
const GLOBAL_ATTRIBUTES = new Set(['class', 'id', 'role', 'title']);
const TAG_ATTRIBUTES = {
    a: new Set(['href', 'rel', 'target']),
    blockquote: new Set(['cite']),
    details: new Set(['open']),
    img: new Set(['alt', 'decoding', 'height', 'loading', 'sizes', 'src', 'srcset', 'width']),
    li: new Set(['value']),
    ol: new Set(['reversed', 'start', 'type']),
    q: new Set(['cite']),
    td: new Set(['colspan', 'rowspan']),
    th: new Set(['colspan', 'rowspan', 'scope']),
    time: new Set(['datetime'])
};
const URL_ATTRIBUTES = new Set(['cite', 'href', 'src']);
const CONTROL_CHARACTERS = /[\u0000-\u001f\u007f]/;
const MAX_HTML_BYTES = 2 * 1024 * 1024;
const MAX_ATTRIBUTE_BYTES = 65536;

const isSafeUrl = (value, attribute) => {
    const url = String(value || '').trim();

    if(!url || url.length > 4096 || CONTROL_CHARACTERS.test(url) || url.includes('\\')){
        return false;
    }

    if(/^(?:#|\/|\.\/|\.\.\/|\?|\/\/)/.test(url)){
        return true;
    }

    return attribute === 'href'
        ? /^(?:https?:|mailto:|tel:)/i.test(url)
        : /^https?:/i.test(url);
};

const isSafeSrcset = (value) => {
    const srcset = String(value || '').trim();

    if(srcset === '' || CONTROL_CHARACTERS.test(srcset) || srcset.includes('\\')){
        return false;
    }

    return srcset.split(',').every((candidate) => {
        const parts = candidate.trim().split(/\s+/);
        const source = parts.shift();
        const descriptor = parts.join(' ');

        return isSafeUrl(source, 'src')
            && (descriptor === '' || /^(?:\d+(?:\.\d+)?x|\d+w)$/.test(descriptor));
    });
};

const safeAttributes = (tag, attributes = {}) => {
    const props = {};

    Object.entries(attributes).forEach(([rawName, rawValue]) => {
        const name = rawName.toLowerCase();
        const normalizedValue = String(rawValue ?? '');
        const allowed = GLOBAL_ATTRIBUTES.has(name)
            || TAG_ATTRIBUTES[tag]?.has(name)
            || /^aria-[a-z0-9_.-]+$/.test(name)
            || /^data-[a-z0-9_.-]+$/.test(name);

        if(!allowed || /^on/i.test(name) || normalizedValue.length > MAX_ATTRIBUTE_BYTES){
            return;
        }

        if(URL_ATTRIBUTES.has(name) && !isSafeUrl(rawValue, name)){
            return;
        }

        if(name === 'srcset' && !isSafeSrcset(rawValue)){
            return;
        }

        const propName = name === 'class'
            ? 'className'
            : name === 'colspan'
                ? 'colSpan'
                : name === 'rowspan'
                    ? 'rowSpan'
                    : name;
        props[propName] = rawValue === '' ? true : rawValue;
    });

    if(tag === 'a' && props.target === '_blank'){
        const rel = new Set(String(props.rel || '').split(/\s+/).filter(Boolean));
        rel.add('noopener');
        rel.add('noreferrer');
        props.rel = [...rel].join(' ');
    }

    if(tag === 'a' && props.target && !/^_(?:blank|self|parent|top)$/.test(props.target)){
        delete props.target;
    }

    return props;
};

const options = {
    replace(domNode){
        if(!(domNode instanceof Element)){
            return undefined;
        }

        const tag = String(domNode.name || '').toLowerCase();

        if(!ALLOWED_TAGS.has(tag)){
            return <></>;
        }

        return createElement(
            tag,
            safeAttributes(tag, domNode.attribs),
            VOID_TAGS.has(tag) ? undefined : domToReact(domNode.children, options)
        );
    }
};

const RichText = ({ value, className = '' }) => {
    if(!value){
        return null;
    }

    if(typeof value !== 'string'){
        return <div className={className}>{value}</div>;
    }

    if(value.length > MAX_HTML_BYTES){
        return null;
    }

    return <div className={className}>{parse(value, options)}</div>;
};

export default RichText;
