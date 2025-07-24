'use strict';

const RWPCache = {
	memory: new Map(),
	async use(url) {

		if (this.memory.has(url)) {

			return this.memory.get(url);

		}

		try {

			const cache = await caches.open('rwp-cache');
			const cached = await cache.match(url);

			let blob;

			if (cached) {

				blob = await cached.blob();

			} else {
				const response = await fetch(url);

				if (response.ok) {

					cache.put(url, response.clone());
					blob = await response.blob();

				} else {

					console.warn(`Fetch failed for ${url}`);

					return url;

				}
			}

			const blobUrl = URL.createObjectURL(blob);
			this.memory.set(url, blobUrl);

			return blobUrl;

		} catch (e) {

			console.warn('Error cache:', e);
			return url;
			
		}
	}
};

export default RWPCache;