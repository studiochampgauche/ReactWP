'use strict';

const RWPCache = {
	async use(url){

		try{

			const cache = await caches.open('rwp-cache');
			const cached = await cache.match(url);

			if(cached){

				return URL.createObjectURL(await cached.blob());

			}

			const response = await fetch(url);

			if (response.ok) {

				cache.put(url, response.clone());
				return URL.createObjectURL(await response.blob());

			}

		} catch (e) {

			console.warn('Error cache:', e);

		}


		return url;

	}
};


export default RWPCache;