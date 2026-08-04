import { runtime, normalizePath, normalizeSearch, createRouteKey, normalizeRoute } from './Runtime';

const routeMemory = new Map([
    [runtime.route.key, runtime.route]
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

const normalizeRouteRequest = (input) => {
    if(input && typeof input === 'object'){
        const path = normalizePath(input.pathname || input.path || '/');
        const search = normalizeSearch(input.search || '');

        return {
            path,
            search,
            key: createRouteKey(path, search),
            view: `${path}${search}`
        };
    }

    const url = new URL(String(input || '/'), runtime.system.baseUrl || window.location.origin);
    const path = normalizePath(url.pathname || '/');
    const search = normalizeSearch(url.search || '');

    return {
        path,
        search,
        key: createRouteKey(path, search),
        view: `${path}${search}`
    };
};

export const fetchRoute = async (input) => {
    const request = normalizeRouteRequest(input);

    if(routeMemory.has(request.key)){
        const cachedRoute = routeMemory.get(request.key);

        if(cachedRoute?.render?.cache?.payload !== false){
            return cachedRoute;
        }
    }

    const endpoint = runtime.system.routeEndpoint || `${runtime.system.restUrl}reactwp/v1/route`;
    const { data } = await requestJson(`${endpoint}?view=${encodeURIComponent(request.view)}`);
    const routePayload = data && typeof data === 'object' && data.route
        ? data.route
        : data;
    const route = normalizeRoute(routePayload, request.path, request.search);

    if(route.render?.cache?.payload !== false){
        routeMemory.set(route.key, route);
    }

    return route;
};
