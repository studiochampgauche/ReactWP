const MEDIA_CACHE_NAME = 'rwp-cache-media';
const JSON_CACHE_NAME = 'rwp-cache-json';

const supportsCacheStorage = () => {
    return typeof window !== 'undefined' && 'caches' in window;
};

const ReactWPCache = {
    mediaMemory: new Map(),
    mediaPending: new Map(),
    jsonMemory: new Map(),
    jsonPending: new Map(),

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
                    const cache = await caches.open(MEDIA_CACHE_NAME);
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(url, requestInit);

                        if(!response.ok){
                            return url;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(url, requestInit);

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
                    const cache = await caches.open(JSON_CACHE_NAME);
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(url, requestInit);

                        if(!response.ok){
                            return null;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(url, requestInit);

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
            await Promise.allSettled([
                caches.delete(MEDIA_CACHE_NAME),
                caches.delete(JSON_CACHE_NAME)
            ]);
        } catch(error){
            console.warn('ReactWP cache clear failed.', error);
        }
    }
};

export default ReactWPCache;
