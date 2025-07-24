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
	},

	revoke(url) {
		if (this.memory.has(url)) {
			URL.revokeObjectURL(this.memory.get(url));
			this.memory.delete(url);
		}
	},

	clear() {
		for (const blobUrl of this.memory.values()) {
			URL.revokeObjectURL(blobUrl);
		}
		this.memory.clear();
	}
};

export default RWPCache;