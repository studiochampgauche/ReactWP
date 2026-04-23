import { runtime, normalizePath } from './Runtime';

const routeMemory = new Map([
    [runtime.route.path, runtime.route]
]);

const requestJson = async (url) => {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'X-WP-Nonce': runtime.system.restNonce || ''
        },
        credentials: 'include'
    });
    const contentType = response.headers.get('content-type') || '';
    const data = contentType.includes('application/json')
        ? await response.json()
        : null;

    return {
        ok: response.ok,
        status: response.status,
        data
    };
};

const normalizeRoute = (route, fallbackPath) => {
    if(route){
        return {
            ...route,
            template: route.template || (route.is404 ? 'NotFound' : 'Default'),
            path: normalizePath(route.path || fallbackPath || '/'),
            seo: route.seo || {},
            data: route.data || {},
            mediaGroups: route.mediaGroups || '',
            is404: Boolean(route.is404),
        };
    }

    return {
        id: null,
        type: '404',
        template: 'NotFound',
        pageName: 'Page not found',
        path: normalizePath(fallbackPath || '/'),
        seo: {},
        data: {},
        mediaGroups: '',
        is404: true
    };
};

export const fetchRoute = async (path) => {
    const normalizedPath = normalizePath(path);

    if(routeMemory.has(normalizedPath)){
        return routeMemory.get(normalizedPath);
    }

    const endpoint = runtime.system.routeEndpoint || `${runtime.system.restUrl}reactwp/v1/route`;
    const { data } = await requestJson(`${endpoint}?view=${encodeURIComponent(normalizedPath)}`);
    const route = normalizeRoute(data, normalizedPath);

    routeMemory.set(route.path, route);

    return route;
};
