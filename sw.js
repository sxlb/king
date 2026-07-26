/**
 * KingJoe Service Worker
 */
const CACHE_NAME = 'kingjoe-v1.0.7';

self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(['/', '/?format=json']).catch(function () {});
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.filter(function (k) { return k !== CACHE_NAME; }).map(function (k) { return caches.delete(k); }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (e) {
    if (e.request.method !== 'GET') return;
    if (e.request.url.includes('/admin/')) return;
    e.respondWith(
        fetch(e.request).then(function (response) {
            if (response && response.status === 200) {
                var clone = response.clone();
                caches.open(CACHE_NAME).then(function (c) { c.put(e.request, clone); });
            }
            return response;
        }).catch(function () {
            return caches.match(e.request).then(function (c) { return c || new Response('Offline', { status: 503 }); });
        })
    );
});