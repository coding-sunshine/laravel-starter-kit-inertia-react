/**
 * Service worker for PWA (Phase 8).
 * - Registers so the app is installable (manifest + SW).
 * - Caches same-origin navigate responses and static assets (JS/CSS, /build/*) on first successful fetch.
 * - When offline: serves cached responses or returns 503 "Offline" for failed GETs.
 */
const CACHE_NAME = 'fleet-v2';

function isSameOrigin(url) {
    return url.origin === self.location.origin;
}

function shouldCacheStatic(request, url) {
    if (!isSameOrigin(url) || request.method !== 'GET') return false;
    const path = url.pathname;
    return path.startsWith('/build/') || request.destination === 'script' || request.destination === 'style';
}

self.addEventListener('install', (event) => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(
                keys.filter((k) => k !== CACHE_NAME).map((k) => caches.delete(k))
            )
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    const url = new URL(event.request.url);

    event.respondWith(
        fetch(event.request)
            .then((res) => {
                if (!res.ok) return res;
                const clone = res.clone();
                if (isSameOrigin(url) && (event.request.mode === 'navigate' || shouldCacheStatic(event.request, url))) {
                    caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
                }
                return res;
            })
            .catch(() =>
                caches.match(event.request).then((cached) =>
                    cached || new Response('Offline', { status: 503, statusText: 'Service Unavailable' })
                )
            )
    );
});
