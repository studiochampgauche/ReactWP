const normalizePath = (value = '/') => {
    const path = `/${String(value || '').replace(/^\/+|\/+$/g, '')}/`;

    return path === '//' ? '/' : path;
};

const normalizeSearch = (value = '') => {
    const rawSearch = typeof value === 'string'
        ? value.trim()
        : '';
    const rawQuery = rawSearch.startsWith('?')
        ? rawSearch.slice(1)
        : rawSearch;

    if(!rawQuery){
        return '';
    }

    const params = new URLSearchParams(rawQuery);
    const normalizedQuery = params.toString();

    return normalizedQuery ? `?${normalizedQuery}` : '';
};

const searchToQuery = (search = '') => {
    const normalizedSearch = normalizeSearch(search);
    const params = new URLSearchParams(normalizedSearch.replace(/^\?/, ''));
    const query = {};

    params.forEach((value, key) => {
        if(Object.prototype.hasOwnProperty.call(query, key)){
            const current = query[key];

            query[key] = Array.isArray(current)
                ? [...current, value]
                : [current, value];
            return;
        }

        query[key] = value;
    });

    return query;
};

const queryToSearch = (query = {}) => {
    if(!query || typeof query !== 'object' || Array.isArray(query)){
        return '';
    }

    const params = new URLSearchParams();

    Object.entries(query).forEach(([key, value]) => {
        if(value == null || value === ''){
            return;
        }

        if(Array.isArray(value)){
            value.forEach((item) => {
                if(item != null && item !== ''){
                    params.append(key, String(item));
                }
            });
            return;
        }

        params.append(key, String(value));
    });

    const normalizedQuery = params.toString();

    return normalizedQuery ? `?${normalizedQuery}` : '';
};

const createRouteKey = (path = '/', search = '') => {
    return `${normalizePath(path)}${normalizeSearch(search)}`;
};

const normalizeRoute = (route = {}, fallbackPath = '/', fallbackSearch = '') => {
    const path = normalizePath(route.path || fallbackPath || '/');
    const derivedSearch = route.search ?? queryToSearch(route.query);
    const search = normalizeSearch(derivedSearch || fallbackSearch || '');
    const query = route.query && typeof route.query === 'object' && !Array.isArray(route.query)
        ? route.query
        : searchToQuery(search);
    const cacheScope = route.render?.cache?.scope === 'public' ? 'public' : 'private';
    const persistentCacheDefault = cacheScope === 'public';

    return {
        ...route,
        template: route.template || 'Default',
        path,
        search,
        query,
        key: createRouteKey(path, search),
        seo: route.seo || {},
        data: route.data || {},
        mediaGroups: route.mediaGroups || '',
        render: {
            mode: ['client', 'static', 'server'].includes(route.render?.mode)
                ? route.render.mode
                : 'client',
            cache: {
                html: Boolean(route.render?.cache?.html),
                scope: cacheScope,
                ttl: Math.max(0, Number.parseInt(route.render?.cache?.ttl || 0, 10)),
                payload: route.render?.cache?.payload == null
                    ? persistentCacheDefault
                    : route.render.cache.payload !== false,
                media: route.render?.cache?.media == null
                    ? persistentCacheDefault
                    : route.render.cache.media !== false,
                tags: Array.isArray(route.render?.cache?.tags)
                    ? [...route.render.cache.tags]
                    : []
            }
        },
        is404: Boolean(route.is404),
    };
};

const parseBootstrap = () => {
    if(typeof document === 'undefined'){
        return {};
    }

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
const currentLocation = typeof window !== 'undefined'
    ? window.location
    : { pathname: route.path || '/', search: route.search || '' };

export const runtime = {
    bootstrap,
    site: bootstrap.site || {},
    theme: bootstrap.theme || {},
    system: bootstrap.system || {},
    assets: bootstrap.assets || {},
    navigation: bootstrap.navigation || {},
    currentUser: bootstrap.currentUser || { authenticated: false },
    seoDefaults: bootstrap.seoDefaults || {},
    route: normalizeRoute(
        route,
        route.path || currentLocation.pathname || '/',
        route.search || currentLocation.search || ''
    )
};

export {
    normalizePath,
    normalizeSearch,
    searchToQuery,
    queryToSearch,
    createRouteKey,
    normalizeRoute
};
