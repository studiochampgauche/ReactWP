const normalizePath = (value = '/') => {
    const path = `/${String(value || '').replace(/^\/+|\/+$/g, '')}/`;

    return path === '//' ? '/' : path;
};

const parseBootstrap = () => {
    const node = document.getElementById('reactwp-bootstrap');

    if(!node){
        return {};
    }

    try{
        return JSON.parse(node.textContent || '{}');
    } catch(error){
        console.warn('ReactWP bootstrap could not be parsed.', error);
        return {};
    }
};

const bootstrap = parseBootstrap();
const route = bootstrap.route || {};

export const runtime = {
    bootstrap,
    site: bootstrap.site || {},
    theme: bootstrap.theme || {},
    system: bootstrap.system || {},
    assets: bootstrap.assets || {},
    navigation: bootstrap.navigation || {},
    seoDefaults: bootstrap.seoDefaults || {},
    route: {
        ...route,
        template: route.template || 'Default',
        path: normalizePath(route.path || window.location.pathname || '/'),
        seo: route.seo || {},
        data: route.data || {},
        mediaGroups: route.mediaGroups || '',
        is404: Boolean(route.is404),
    }
};

export { normalizePath };
