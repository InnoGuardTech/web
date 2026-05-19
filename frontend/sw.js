/* ============================================================================
   حراج اليمن الفاخر — Service Worker v4.0
   Cache-first for assets, network-first for HTML/API
   ============================================================================ */

const CACHE_VERSION = 'haraj-yemen-v4.0.0';
const STATIC_CACHE = CACHE_VERSION + '-static';
const RUNTIME_CACHE = CACHE_VERSION + '-runtime';

const STATIC_ASSETS = [
    'assets/css/style.css',
    'assets/js/app.js',
    'offline.php',
    'manifest.json'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then(cache => cache.addAll(STATIC_ASSETS.map(p => new Request(p, { credentials: 'same-origin' }))))
            .then(() => self.skipWaiting())
            .catch(() => {})
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys.filter(k => k.startsWith('haraj-yemen-') && k !== STATIC_CACHE && k !== RUNTIME_CACHE)
                .map(k => caches.delete(k))
        )).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;
    const url = new URL(req.url);
    if (url.origin !== location.origin) return;

    // API calls - network only
    if (url.pathname.includes('/backend/') || url.pathname.includes('router.php')) {
        return;
    }

    // Static assets - cache-first
    if (/\.(css|js|svg|png|jpg|jpeg|gif|webp|woff2?|ttf|ico)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then(cached => {
                if (cached) return cached;
                return fetch(req).then(res => {
                    if (res && res.status === 200) {
                        const clone = res.clone();
                        caches.open(RUNTIME_CACHE).then(c => c.put(req, clone));
                    }
                    return res;
                });
            }).catch(() => caches.match(req))
        );
        return;
    }

    // HTML - network-first with offline fallback
    if (req.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(req).then(res => {
                if (res && res.status === 200) {
                    const clone = res.clone();
                    caches.open(RUNTIME_CACHE).then(c => c.put(req, clone));
                }
                return res;
            }).catch(() => {
                return caches.match(req).then(c => c || caches.match('offline.php'));
            })
        );
    }
});
