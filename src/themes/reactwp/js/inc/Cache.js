'use strict';

const RWPCache = {
    mediaMemory: new Map(),
    mediaPending: new Map(),
    jsonMemory: new Map(),
    jsonPending: new Map(),

    async media(url, {
        asBlob = true,
        useCache = true
    } = {}){

        if(!url) return null;

        if(!asBlob){
            return url;
        }

        if(this.mediaMemory.has(url)){
            return this.mediaMemory.get(url);
        }

        if(this.mediaPending.has(url)){
            return this.mediaPending.get(url);
        }

        const promise = async () => {

            try{

                let response = null;

                if(useCache){
                    const cache = await caches.open('rwp-cache-media');
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(url);

                        if(!response.ok){
                            console.warn(`Fetch failed for ${url}`);
                            return url;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(url);

                    if(!response.ok){
                        console.warn(`Fetch failed for ${url}`);
                        return url;
                    }
                }

                const blobUrl = URL.createObjectURL(await response.blob());

                this.mediaMemory.set(url, blobUrl);

                return blobUrl;

            } catch(error){

                console.warn('Error media cache:', error);

                return url;

            } finally {

                this.mediaPending.delete(url);

            }

        };

        this.mediaPending.set(url, promise);

        return promise ();

    },

    async json(url, {
        useCache = true
    } = {}){

        if(!url) return null;

        if(this.jsonMemory.has(url)){
            return this.jsonMemory.get(url);
        }

        if(this.jsonPending.has(url)){
            return this.jsonPending.get(url);
        }

        const promise = async () => {

            try{

                let response = null;

                if(useCache){
                    const cache = await caches.open('rwp-cache-json');
                    response = await cache.match(url);

                    if(!response){
                        response = await fetch(url);

                        if(!response.ok){
                            console.warn(`Fetch failed for ${url}`);
                            return null;
                        }

                        await cache.put(url, response.clone());
                    }
                } else {
                    response = await fetch(url);

                    if(!response.ok){
                        console.warn(`Fetch failed for ${url}`);
                        return null;
                    }
                }

                const data = await response.json();

                this.jsonMemory.set(url, data);

                return data;

            } catch(error){

                console.warn('Error json cache:', error);

                return null;

            } finally {

                this.jsonPending.delete(url);

            }

        };

        this.jsonPending.set(url, promise);

        return promise();

    },

    revoke(url){

        if(!this.mediaMemory.has(url)) return;

        URL.revokeObjectURL(this.mediaMemory.get(url));
        this.mediaMemory.delete(url);

    },

    async delete(url){

        this.revoke(url);
        this.jsonMemory.delete(url);
        this.mediaPending.delete(url);
        this.jsonPending.delete(url);

        try{

            const mediaCache = await caches.open(MEDIA_CACHE_NAME);
            const jsonCache = await caches.open(JSON_CACHE_NAME);

            await Promise.allSettled([
                mediaCache.delete(url),
                jsonCache.delete(url)
            ]);

        } catch(error){

            console.warn('Error deleting cache:', error);

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

        try{

            const mediaCache = await caches.open(MEDIA_CACHE_NAME);
            const jsonCache = await caches.open(JSON_CACHE_NAME);

            const [mediaRequests, jsonRequests] = await Promise.all([
                mediaCache.keys(),
                jsonCache.keys()
            ]);

            await Promise.allSettled([
                ...mediaRequests.map((request) => mediaCache.delete(request)),
                ...jsonRequests.map((request) => jsonCache.delete(request))
            ]);

        } catch(error){

            console.warn('Error clearing cache:', error);

        }

    }
};

export default RWPCache;