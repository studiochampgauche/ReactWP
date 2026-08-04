import { lazy } from 'react';

const createTemplateEntry = (loader) => {
    let pending = null;
    let resolvedModule = null;

    const load = () => {
        if(resolvedModule){
            return Promise.resolve(resolvedModule);
        }

        if(!pending){
            pending = loader()
                .then((templateModule) => {
                    resolvedModule = templateModule;
                    return templateModule;
                })
                .catch((error) => {
                    pending = null;
                    throw error;
                });
        }

        return pending;
    };

    return {
        Component: lazy(load),
        load,
        preload(){
            return load();
        },
        getResolvedComponent(){
            return resolvedModule?.default || null;
        },
        render: 'client',
        cache: {},
        assetKey: null
    };
};

const defaultTemplateLoaders = {
    Default: () => import('../templates/Default'),
    NotFound: () => import('../templates/NotFound'),
};

export const templateRegistry = Object.create(null);

const isTemplateEntry = (value) => {
    return Boolean(value?.Component && value?.preload);
};

const normalizeTemplateEntry = (value) => {
    if(isTemplateEntry(value)){
        return {
            render: 'client',
            cache: {},
            ...value
        };
    }

    if(typeof value === 'function'){
        return createTemplateEntry(value);
    }

    if(value && typeof value === 'object' && typeof value.loader === 'function'){
        const entry = createTemplateEntry(value.loader);

        return {
            ...entry,
            render: ['client', 'static', 'server'].includes(value.render)
                ? value.render
                : 'client',
            cache: value.cache && typeof value.cache === 'object'
                ? { ...value.cache }
                : {},
            assetKey: typeof value.assetKey === 'string' && value.assetKey
                ? value.assetKey
                : null
        };
    }

    throw new Error('ReactWP template entries must be a loader function, a loader configuration, or a normalized template entry.');
};

export const registerTemplate = (name, value) => {
    if(typeof name !== 'string' || !/^[A-Za-z][A-Za-z0-9_.-]{0,127}$/.test(name)){
        return templateRegistry;
    }

    templateRegistry[name] = normalizeTemplateEntry(value);
    templateRegistry[name].assetKey = templateRegistry[name].assetKey || name;

    return templateRegistry;
};

export const registerTemplates = (entries = {}) => {
    Object.entries(entries).forEach(([name, value]) => {
        registerTemplate(name, value);
    });

    return templateRegistry;
};

export const resetTemplateRegistry = () => {
    Object.keys(templateRegistry).forEach((name) => {
        delete templateRegistry[name];
    });

    registerTemplates(defaultTemplateLoaders);

    return templateRegistry;
};

export const resolveTemplateEntry = (templateName) => {
    return Object.prototype.hasOwnProperty.call(templateRegistry, templateName)
        ? templateRegistry[templateName]
        : templateRegistry.Default;
};

resetTemplateRegistry();

export { createTemplateEntry };
