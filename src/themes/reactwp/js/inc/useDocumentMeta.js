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
            return;
        }

        node.setAttribute(key, value);
    });
};

const clearManagedHead = () => {
    document.head
        .querySelectorAll(`[${managedHeadAttribute}="${managedHeadValue}"]`)
        .forEach((node) => node.remove());
};

const parseHeadEntries = (entries = []) => {
    return entries.flatMap((entry) => {
        if(typeof entry !== 'string' || !entry.trim()){
            return [];
        }

        const template = document.createElement('template');
        template.innerHTML = entry.trim();

        return Array.from(template.content.childNodes).filter((node) => {
            return node.nodeType === Node.ELEMENT_NODE;
        });
    });
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
        const canonical = new URL(route.path || '/', runtime.system.baseUrl || window.location.origin).toString().replace(/\/$/, '');

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
