const CACHE_NAME = 'hi-events-check-in-v1';
const SHELL_URLS = [
    '/check-in-manifest.webmanifest',
    '/manifest-icons/favicon.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.addAll(SHELL_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const {request} = event;
    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);
    if (!url.pathname.startsWith('/check-in')) {
        return;
    }

    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok && url.origin === self.location.origin) {
                    const clone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(request, clone));
                }
                return response;
            })
            .catch(() => caches.match(request).then((cached) => cached || caches.match('/')))
    );
});
