// CAT Enterprise Service Worker
const CACHE_NAME = 'cat-enterprise-v3';
const APP_BASE_PATH = new URL('.', self.registration.scope).pathname.replace(/\/$/, '');
const ASSET_BASE_PATH = `${APP_BASE_PATH}/assets/`;

// Hanya cache aset statis saja
const STATIC_ASSETS = [
    `${ASSET_BASE_PATH}cat.png`,
];

// Install
self.addEventListener('install', function(e) {
    e.waitUntil(
        caches.open(CACHE_NAME).then(function(cache) {
            return cache.addAll(STATIC_ASSETS).catch(() => {});
        })
    );
    self.skipWaiting();
});

// Activate — hapus cache lama
self.addEventListener('activate', function(e) {
    e.waitUntil(
        caches.keys().then(function(keys) {
            return Promise.all(
                keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
            );
        })
    );
    self.clients.claim();
});

// Fetch — hanya cache aset statis (gambar, css, js dari CDN)
// Semua request ke server PHP dibiarkan lewat tanpa cache
self.addEventListener('fetch', function(e) {
    const url = new URL(e.request.url);

    // Hanya handle GET
    if (e.request.method !== 'GET') return;

    // Aset statis lokal: cache-first
    if (url.hostname === self.location.hostname) {
        if (url.pathname.startsWith(ASSET_BASE_PATH)) {
            e.respondWith(
                caches.match(e.request).then(function(cached) {
                    if (cached) return cached;
                    return fetch(e.request).then(function(response) {
                        if (response && response.status === 200) {
                            const clone = response.clone();
                            caches.open(CACHE_NAME).then(function(cache) {
                                cache.put(e.request, clone);
                            });
                        }
                        return response;
                    }).catch(function() {
                        // Tidak ada cache dan fetch gagal — kembalikan 404 kosong
                        return new Response('', { status: 404, statusText: 'Not Found' });
                    });
                })
            );
        }
        // Semua URL PHP lainnya: JANGAN intercept, biarkan browser handle langsung
        return;
    }

    // CDN external (bootstrap, fontawesome, google fonts): cache-first
    const isCDN = url.hostname.includes('cdn.jsdelivr.net') ||
                  url.hostname.includes('cdnjs.cloudflare.com') ||
                  url.hostname.includes('fonts.googleapis.com') ||
                  url.hostname.includes('fonts.gstatic.com');

    if (isCDN) {
        e.respondWith(
            caches.match(e.request).then(function(cached) {
                if (cached) return cached;
                return fetch(e.request).then(function(response) {
                    if (response && response.status === 200) {
                        const clone = response.clone();
                        caches.open(CACHE_NAME).then(function(cache) {
                            cache.put(e.request, clone);
                        });
                    }
                    return response;
                }).catch(function() {
                    // Fetch CDN gagal dan tidak ada cache — kembalikan response kosong
                    return new Response('', { status: 503, statusText: 'Service Unavailable' });
                });
            })
        );
    }
    // Request lain yang tidak dikenali: biarkan browser handle
});
