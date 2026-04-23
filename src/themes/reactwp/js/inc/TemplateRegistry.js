import { lazy } from 'react';

const createTemplateEntry = (loader) => {
    let pending = null;

    return {
        Component: lazy(loader),
        preload(){
            if(!pending){
                pending = loader().catch((error) => {
                    pending = null;
                    throw error;
                });
            }

            return pending;
        }
    };
};

const defaultTemplateLoaders = {
    Default: () => import('../templates/Default'),
    NotFound: () => import('../templates/NotFound'),
};

export const templateRegistry = {};

const isTemplateEntry = (value) => {
    return Boolean(value?.Component && value?.preload);
};

const normalizeTemplateEntry = (value) => {
    if(isTemplateEntry(value)){
        return value;
    }

    if(typeof value === 'function'){
        return createTemplateEntry(value);
    }

    throw new Error('ReactWP template entries must be a loader function or a normalized template entry.');
};

export const registerTemplate = (name, value) => {
    if(!name){
        return templateRegistry;
    }

    templateRegistry[name] = normalizeTemplateEntry(value);

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
    return templateRegistry[templateName] || templateRegistry.Default;
};

resetTemplateRegistry();

export { createTemplateEntry };
