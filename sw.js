/**
 * KingJoe Service Worker
 */
const CACHE_NAME = 'kingjoe-v1.0.7';

// 安装：预缓存关键页面
self.addEventListener('install', function (e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function (cache) {
            return cache.addAll(['/', '/?format=json']).catch(function () {
                // 预缓存失败不阻塞安装
            });
        })
    );
    self.skipWaiting();
});

// 激活：清理旧缓存
self.addEventListener('activate', function (e) {
    e.waitUntil(
        caches.keys().then(function (keys) {
<<<<<<< HEAD
            return Promise.all(keys.filter(function (k) { return k !== CACHE_NAME; }).map(function (k) { return caches.delete(k); }));
=======
            return Promise.all(keys.filter(function (key) {
                return key !== CACHE_NAME;
            }).map(function (key) {
                return caches.delete(key);
            }));
>>>>>>> a9f5c2d (fix: XSS in microblog, archive HTML structure, dashboard SQL fields, session safety, manifest PWA, mermaid dark mode, PHP 8.x null safety)
        })
    );
    self.clients.claim();
});

// 请求拦截：Network First，回退 Cache
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
                return cached || new Response('网络不可用', { status: 503 });
            });
        })
    );
});