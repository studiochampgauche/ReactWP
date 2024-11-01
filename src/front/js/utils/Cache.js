'use strict';

const Cache = {
    cache: null,
    init: async function(cacheName){

        return new Promise(async (done) => {

            try{

                this.cache = await caches.open(cacheName);

            } catch (_){

                console.warn('Cache API can\'t work on non-secure url');

            }

            done();

        });

    },
    get: async function(url){

        if(!this.cache) return url;

        const cacheResponse = await this.cache.match(url);

        if(cacheResponse) {

            const blob = await cacheResponse.blob();

            return URL.createObjectURL(blob);

        }

        return url;

    },
    put: function(url, response){

        if(!this.cache) return;
        
        const newUrl = new URL(url);

        if(newUrl.protocol === 'blob:') return;

        this.cache.put(url, response);

    },
    add: function(url){

        if(!this.cache) return;
        
        const newUrl = new URL(url);

        if(newUrl.protocol === 'blob:') return;

        this.cache.add(url);

    },
    delete: function(cacheName){


        return new Promise(async (done) => {

            await caches.delete(cacheName);

            done();

        });

    }
}

export default Cache;