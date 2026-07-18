import { runtime } from './Runtime';

const MEDIA_CACHE_PREFIX = 'rwp-cache-media';
const JSON_CACHE_PREFIX = 'rwp-cache-json';

const normalizeCacheVersion = (value) => {
    const version = String(value || '1').replace(/[^a-zA-Z0-9._-]/g, '-');

    return version || '1';
};

const CACHE_VERSION = normalizeCacheVersion(runtime.system.cacheVersion);
const MEDIA_CACHE_NAME = `${MEDIA_CACHE_PREFIX}-${CACHE_VERSION}`;
const JSON_CACHE_NAME = `${JSON_CACHE_PREFIX}-${CACHE_VERSION}`;

const supportsCacheStorage = () => {
    return typeof window !== 'undefined' && 'caches' in window;
};

const isManagedCache = (name = '') => {
    return name === MEDIA_CACHE_PREFIX
        || name === JSON_CACHE_PREFIX
        || name.startsWith(`${MEDIA_CACHE_PREFIX}-`)
        || name.startsWith(`${JSON_CACHE_PREFIX}-`);
};

const getVersionedMediaUrl = (url) => {
    try{
        const target = new URL(url, window.location.origin);

        if(target.origin !== window.location.origin || !['http:', 'https:'].includes(target.protocol)){
            return url;
        }

        target.searchParams.set('rwp-cache-version', CACHE_VERSION);

        return target.href;
    } catch(_error){
        return url;
    }
};

const ReactWPCache = {
    mediaMemory: new Map(),
    mediaPending: new Map(),
    jsonMemory: new Map(),
    jsonPending: new Map(),
    initializePromise: null,

    initialize(){
        if(!supportsCacheStorage()){
            return Promise.resolve([]);
        }

        if(!this.initializePromise){
            this.initializePromise = caches.keys()
                .then((names) => {
                    const staleCaches = names.filter((name) => {
                        return isManagedCache(name)
                            && name !== MEDIA_CACHE_NAME
                            && name !== JSON_CACHE_NAME;
                    });

                    return Promise.allSettled(
                        staleCaches.map((name) => caches.delete(name))
                    );
                })
                .catch((error) => {
                    console.warn('ReactWP stale cache cleanup failed.', error);
                    return [];
                });
        }

        return this.initializePromise;
    },

    async media(url, {
        asBlob = true,
        useCache = true,
        requestInit = {
            credentials: 'same-origin'
        }
    } = {}){
        if(!url){
            return null;
        }

        if(!asBlob){
            return url;
        }

        if(this.mediaMemory.has(url)){
            return this.mediaMemory.get(url);
        }

        if(this.mediaPending.has(url)){
            return this.mediaPending.get(url);
        }

        const promise = (async () => {
            try{
                let response = null;

                if(useCache && supportsCacheStorage()){
                    await this.initialize();
                    const cache = await caches.open(MEDIA_CACHE_NAME);
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(getVersionedMediaUrl(url), {
                            cache: 'reload',
                            ...requestInit
                        });

                        if(!response.ok){
                            return url;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(getVersionedMediaUrl(url), {
                        cache: 'reload',
                        ...requestInit
                    });

                    if(!response.ok){
                        return url;
                    }
                }

                const blobUrl = URL.createObjectURL(await response.blob());
                this.mediaMemory.set(url, blobUrl);

                return blobUrl;
            } catch(error){
                console.warn('ReactWP media cache failed.', error);
                return url;
            } finally {
                this.mediaPending.delete(url);
            }
        })();

        this.mediaPending.set(url, promise);

        return promise;
    },

    async json(url, {
        useCache = true,
        requestInit = {}
    } = {}){
        if(!url){
            return null;
        }

        if(this.jsonMemory.has(url)){
            return this.jsonMemory.get(url);
        }

        if(this.jsonPending.has(url)){
            return this.jsonPending.get(url);
        }

        const promise = (async () => {
            try{
                let response = null;

                if(useCache && supportsCacheStorage()){
                    await this.initialize();
                    const cache = await caches.open(JSON_CACHE_NAME);
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(url, {
                            cache: 'reload',
                            ...requestInit
                        });

                        if(!response.ok){
                            return null;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(url, {
                        cache: 'reload',
                        ...requestInit
                    });

                    if(!response.ok){
                        return null;
                    }
                }

                const data = await response.json();
                this.jsonMemory.set(url, data);

                return data;
            } catch(error){
                console.warn('ReactWP JSON cache failed.', error);
                return null;
            } finally {
                this.jsonPending.delete(url);
            }
        })();

        this.jsonPending.set(url, promise);

        return promise;
    },

    revoke(url){
        if(!this.mediaMemory.has(url)){
            return;
        }

        URL.revokeObjectURL(this.mediaMemory.get(url));
        this.mediaMemory.delete(url);
    },

    async delete(url){
        this.revoke(url);
        this.jsonMemory.delete(url);
        this.mediaPending.delete(url);
        this.jsonPending.delete(url);

        if(!supportsCacheStorage()){
            return;
        }

        try{
            await this.initialize();
            const mediaCache = await caches.open(MEDIA_CACHE_NAME);
            const jsonCache = await caches.open(JSON_CACHE_NAME);

            await Promise.allSettled([
                mediaCache.delete(url),
                jsonCache.delete(url)
            ]);
        } catch(error){
            console.warn('ReactWP cache delete failed.', error);
        }
    },

    async clear(){
        this.mediaMemory.forEach((blobUrl) => {
            URL.revokeObjectURL(blobUrl);
        });

        this.mediaMemory.clear();
        this.mediaPending.clear();
        this.jsonMemory.clear();
        this.jsonPending.clear();

        if(!supportsCacheStorage()){
            return;
        }

        try{
            const names = await caches.keys();

            await Promise.allSettled(
                names.filter(isManagedCache).map((name) => caches.delete(name))
            );

            this.initializePromise = null;
        } catch(error){
            console.warn('ReactWP cache clear failed.', error);
        }
    }
};

ReactWPCache.initialize();

export default ReactWPCache;
