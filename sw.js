/**
 * KingJoe Service Worker — 离线缓存
 */
const CACHE_NAME = 'kingjoe-v1.0.7';
const ASSETS = [
    '/',
    '/?format=json',
];

self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
            return Promise.all(keys.map(function (key) {
                if (key !== CACHE_NAME) return caches.delete(key);
            }));
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', function (e) {
    // 只缓存 GET 请求
    if (e.request.method !== 'GET') return;
    // 跳过管理后台
    if (e.request.url.includes('/admin/')) return;
    
    e.respondWith(
        caches.match(e.request).then(function (cached) {
            var fetched = fetch(e.request).then(function (response) {
                if (response && response.status === 200) {
                    var clone = response.clone();
                    caches.open(CACHE_NAME).then(function (cache) {
                        cache.put(e.request, clone);
                    });
                }
                return response;
            }).catch(function () {
                return cached || new Response('Offline', { status: 503 });
            });
            return cached || fetched;
        })
    );
});
