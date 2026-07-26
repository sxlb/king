/**
 * KingJoe Service Worker — 离线缓存
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
            return Promise.all(keys.filter(function (key) {
                return key !== CACHE_NAME;
            }).map(function (key) {
                return caches.delete(key);
            }));
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
                caches.open(CACHE_NAME).then(function (cache) {
                    cache.put(e.request, clone);
                });
            }
            return response;
        }).catch(function () {
            return caches.match(e.request).then(function (cached) {
                return cached || new Response('Offline', { status: 503 });
            });
        })
    );
});
