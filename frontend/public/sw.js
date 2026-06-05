const CACHE_VERSION = 'v1';
const STATIC_ASSETS = [
    '/manifest-icons/favicon.svg',
    '/logos/hi-events-text-dark.svg',
    '/logos/hi-events-text-light.svg',
    '/logos/hi-events-icon-dark.svg',
    '/logos/hi-events-icon-light.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION).then((cache) => cache.addAll(STATIC_ASSETS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_VERSION).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const {request} = event;
    const url = new URL(request.url);

    if (request.method !== 'GET') {
        return;
    }

    const isStaticAsset = url.pathname.startsWith('/assets/')
        || url.pathname.match(/\.(js|css|svg|png|jpg|jpeg|webp|woff2?)$/);

    if (!isStaticAsset) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => {
            if (cached) {
                return cached;
            }

            return fetch(request).then((response) => {
                if (!response.ok) {
                    return response;
                }

                const clone = response.clone();
                caches.open(CACHE_VERSION).then((cache) => cache.put(request, clone));

                return response;
            });
        })
    );
});
