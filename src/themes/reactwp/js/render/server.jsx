import React, { Suspense } from 'react';
import { renderToString } from 'react-dom/server';
import { StaticRouter } from 'react-router';
import AppShell from '../inc/AppShell';
import { initializeTemplateRegistry } from '../inc/initializeTemplateRegistry';
import { normalizeRoute } from '../inc/Runtime';
import { resolveTemplateEntry, templateRegistry } from '../inc/TemplateRegistry';

let registryInitialized = false;

const initializeRegistry = () => {
    if(registryInitialized){
        return;
    }

    initializeTemplateRegistry();
    registryInitialized = true;
};

export const getTemplateManifest = () => {
    initializeRegistry();

    return Object.fromEntries(
        Object.entries(templateRegistry).map(([name, entry]) => [
            name,
            {
                mode: ['client', 'static', 'server'].includes(entry.render)
                    ? entry.render
                    : 'client',
                cache: entry.cache && typeof entry.cache === 'object'
                    ? { ...entry.cache }
                    : {},
                assetKey: entry.assetKey || name
            }
        ])
    );
};

const renderTags = (route, templateName, templateEntry) => {
    const tags = new Set([
        'render:all',
        'menu:all',
        'settings:all',
        `template:${templateName}`
    ]);

    if(route.id !== null && route.id !== undefined && route.id !== ''){
        tags.add(`post:${route.id}`);
    }

    [
        ...(Array.isArray(templateEntry?.cache?.tags) ? templateEntry.cache.tags : []),
        ...(Array.isArray(route.render?.cache?.tags) ? route.render.cache.tags : [])
    ].forEach((tag) => {
        if(typeof tag === 'string' && /^[a-z0-9_-]+:[a-z0-9_.-]+$/i.test(tag)){
            tags.add(tag.toLowerCase());
        }
    });

    return [...tags];
};

export const render = async (bootstrapPayload = {}, options = {}) => {
    initializeRegistry();

    const route = normalizeRoute(
        bootstrapPayload.route || {},
        options.path || '/',
        options.search || ''
    );
    const templateEntry = resolveTemplateEntry(route.template);
    const templateModule = await templateEntry.load();
    const Template = templateModule?.default;

    if(typeof Template !== 'function' && typeof Template !== 'object'){
        throw new Error(`ReactWP template "${route.template}" does not have a default React export.`);
    }

    const site = bootstrapPayload.site || {};
    const theme = bootstrapPayload.theme || {};
    const system = bootstrapPayload.system || {};
    const navigation = bootstrapPayload.navigation || {};
    const currentUser = bootstrapPayload.currentUser || { authenticated: false };
    const location = `${route.path}${route.search}`;
    const html = renderToString(
        <StaticRouter location={location}>
            <AppShell showHeader={false} showFooter={false}>
                <Suspense fallback={null}>
                    <Template
                        route={route}
                        site={site}
                        theme={theme}
                        system={system}
                        navigation={navigation}
                        currentUser={currentUser}
                    />
                </Suspense>
            </AppShell>
        </StaticRouter>
    );

    return {
        html,
        route,
        template: route.template,
        tags: renderTags(route, route.template, templateEntry)
    };
};

export default render;
