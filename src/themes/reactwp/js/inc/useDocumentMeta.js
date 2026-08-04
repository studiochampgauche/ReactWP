import { useEffect } from 'react';
import { runtime } from './Runtime';

const managedHeadAttribute = 'data-rwp-head';
const managedHeadValue = 'route';

const upsertMeta = (selector, attributes) => {
    let node = document.head.querySelector(selector);

    if(!node){
        node = document.createElement('meta');
        document.head.appendChild(node);
    }

    Object.entries(attributes).forEach(([key, value]) => {
        if(value == null || value === ''){
            node.removeAttribute(key);
            return;
        }

        node.setAttribute(key, value);
    });

    node.setAttribute(managedHeadAttribute, managedHeadValue);
};

const clearManagedHead = () => {
    document.head
        .querySelectorAll(`[${managedHeadAttribute}="${managedHeadValue}"]`)
        .forEach((node) => node.remove());
};

const parseHeadEntries = (entries = []) => {
    return entries.slice(0, 100).flatMap((entry) => {
        if(typeof entry !== 'string' || !entry.trim() || entry.length > 65536){
            return [];
        }

        const template = document.createElement('template');
        template.innerHTML = entry.trim();

        return Array.from(template.content.childNodes)
            .map(sanitizeHeadNode)
            .filter(Boolean);
    });
};

const safeHeadUrl = (value) => {
    try{
        const url = new URL(String(value || ''), window.location.origin);
        return String(value || '').length <= 4096
            && !url.username
            && !url.password
            && ['http:', 'https:'].includes(url.protocol)
            ? url.href
            : '';
    } catch(_error){
        return '';
    }
};

const sanitizeHeadNode = (node) => {
    if(!(node instanceof Element)){
        return null;
    }

    const tag = node.tagName.toLowerCase();

    if(tag === 'title'){
        const title = document.createElement('title');
        title.textContent = String(node.textContent || '').slice(0, 4096);
        return title;
    }

    if(tag === 'meta'){
        const meta = document.createElement('meta');
        const charset = String(node.getAttribute('charset') || '').trim();
        const name = String(node.getAttribute('name') || '').trim();
        const property = String(node.getAttribute('property') || '').trim();
        const httpEquiv = String(node.getAttribute('http-equiv') || '').trim().toLowerCase();
        const content = String(node.getAttribute('content') || '');

        if(content.length > 65536){
            return null;
        }

        if(charset && /^[a-z0-9._-]{1,40}$/i.test(charset)){
            meta.setAttribute('charset', charset);
            return meta;
        }

        if(name && /^[a-z0-9:._-]{1,100}$/i.test(name)){
            meta.setAttribute('name', name);
        } else if(property && /^[a-z0-9:._-]{1,100}$/i.test(property)){
            meta.setAttribute('property', property);
        } else if(httpEquiv === 'x-ua-compatible'){
            meta.setAttribute('http-equiv', httpEquiv);
        } else {
            return null;
        }

        meta.setAttribute('content', content);
        return meta;
    }

    if(tag === 'link'){
        const rel = String(node.getAttribute('rel') || '').trim().toLowerCase();
        const allowedRelations = new Set(['alternate', 'apple-touch-icon', 'canonical', 'icon', 'manifest']);
        const href = safeHeadUrl(node.getAttribute('href'));

        if(!allowedRelations.has(rel) || !href){
            return null;
        }

        const link = document.createElement('link');
        link.setAttribute('rel', rel);
        link.setAttribute('href', href);

        ['hreflang', 'media', 'sizes', 'type'].forEach((attribute) => {
            const value = node.getAttribute(attribute);

            if(value && value.length <= 1024){
                link.setAttribute(attribute, value);
            }
        });

        return link;
    }

    return null;
};

const normalizedHeadNode = (node) => {
    if(!(node instanceof Element)){
        return '';
    }

    const clone = node.cloneNode(true);
    clone.removeAttribute(managedHeadAttribute);

    return clone.outerHTML.trim();
};

const findMatchingHeadNode = (targetNode) => {
    const targetMarkup = normalizedHeadNode(targetNode);

    return Array.from(document.head.children).find((node) => {
        return normalizedHeadNode(node) === targetMarkup;
    });
};

const syncHeadEntries = (entries = []) => {
    clearManagedHead();

    parseHeadEntries(entries).forEach((node) => {
        const existing = findMatchingHeadNode(node);
        const headNode = existing || node;

        headNode.setAttribute(managedHeadAttribute, managedHeadValue);

        if(!existing){
            document.head.appendChild(headNode);
        }

        if(headNode.tagName === 'TITLE'){
            document.title = headNode.textContent || '';
        }
    });
};

export const useDocumentMeta = (route) => {
    useEffect(() => {
        if(!route){
            return;
        }

        if(Array.isArray(route.head) && route.head.length){
            syncHeadEntries(route.head);
            return;
        }

        clearManagedHead();

        const language = runtime.site.language || 'en';
        const defaults = runtime.seoDefaults || {};
        const siteName = runtime.site.name || defaults.title || 'ReactWP';
        const seo = route.seo || {};

        const title = seo[`title_${language}`]
            || seo.title
            || `${route.pageName || siteName} - ${siteName}`;
        const description = seo[`description_${language}`]
            || seo.description
            || defaults.description
            || runtime.site.description
            || '';
        const ogTitle = seo[`og_title_${language}`] || seo.og_title || title;
        const ogDescription = seo[`og_description_${language}`] || seo.og_description || description;
        const ogImage = seo.og_image || defaults.ogImage || '';
        const canonicalUrl = route.url
            || new URL(
                `${route.path || '/'}${route.search || ''}`,
                runtime.system.baseUrl || window.location.origin
            ).toString();
        const canonical = canonicalUrl.replace(/\/$/, '');

        document.title = title;

        upsertMeta('meta[name="description"]', {
            name: 'description',
            content: description
        });
        upsertMeta('meta[property="og:type"]', {
            property: 'og:type',
            content: seo.og_type || 'website'
        });
        upsertMeta('meta[property="og:url"]', {
            property: 'og:url',
            content: canonical
        });
        upsertMeta('meta[property="og:title"]', {
            property: 'og:title',
            content: ogTitle
        });
        upsertMeta('meta[property="og:description"]', {
            property: 'og:description',
            content: ogDescription
        });

        if(ogImage){
            upsertMeta('meta[property="og:image"]', {
                property: 'og:image',
                content: ogImage
            });
        }
    }, [route]);
};
