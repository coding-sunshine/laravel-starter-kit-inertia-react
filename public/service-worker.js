/**
 * Railway Rake Management Service Worker
 * Offline-first PWA with background sync support
 */

const CACHE_VERSION = 'v1';
const CACHE_NAMES = {
  STATIC: `rrmcs-static-${CACHE_VERSION}`,
  DYNAMIC: `rrmcs-dynamic-${CACHE_VERSION}`,
  API: `rrmcs-api-${CACHE_VERSION}`,
};

const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/css/app.css',
  '/js/app.js',
  '/fonts/',
  '/images/',
];

/**
 * Install event - cache static assets
 */
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAMES.STATIC).then((cache) => {
      console.log('ServiceWorker: Caching static assets');
      return cache.addAll(STATIC_ASSETS).catch(() => {
        // Some assets might not exist, that's okay
        console.log('ServiceWorker: Some static assets not cached');
      });
    })
  );
  self.skipWaiting();
});

/**
 * Activate event - clean up old caches
 */
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cacheName) => {
          if (!Object.values(CACHE_NAMES).includes(cacheName)) {
            console.log(`ServiceWorker: Deleting old cache ${cacheName}`);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
  self.clients.claim();
});

/**
 * Fetch event - offline-first cache strategy
 */
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Skip non-GET requests
  if (request.method !== 'GET') {
    return;
  }

  // Skip external URLs
  if (url.origin !== self.location.origin) {
    return;
  }

  // API calls: Network-first with cache fallback
  if (url.pathname.startsWith('/api/')) {
    event.respondWith(networkFirstStrategy(request, CACHE_NAMES.API));
    return;
  }

  // HTML: Network-first (for SPA routing)
  if (request.headers.get('accept')?.includes('text/html')) {
    event.respondWith(networkFirstStrategy(request, CACHE_NAMES.DYNAMIC));
    return;
  }

  // CSS, JS, images: Cache-first
  if (
    request.url.endsWith('.css') ||
    request.url.endsWith('.js') ||
    request.url.endsWith('.png') ||
    request.url.endsWith('.jpg') ||
    request.url.endsWith('.jpeg') ||
    request.url.endsWith('.gif') ||
    request.url.endsWith('.svg') ||
    request.url.endsWith('.woff') ||
    request.url.endsWith('.woff2')
  ) {
    event.respondWith(cacheFirstStrategy(request, CACHE_NAMES.STATIC));
    return;
  }

  // Default: Network-first
  event.respondWith(networkFirstStrategy(request, CACHE_NAMES.DYNAMIC));
});

/**
 * Background sync for offline form submissions
 */
self.addEventListener('sync', (event) => {
  if (event.tag === 'sync-forms') {
    event.waitUntil(syncPendingForms());
  }
});

/**
 * Push notifications
 */
self.addEventListener('push', (event) => {
  if (!event.data) return;

  const data = event.data.json();
  const options = {
    body: data.body || '',
    icon: '/images/icon-192x192.png',
    badge: '/images/badge-72x72.png',
    tag: data.tag || 'notification',
    data: data.data || {},
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'RRMCS Alert', options)
  );
});

/**
 * Notification click handler
 */
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.matchAll({ type: 'window' }).then((clientList) => {
      for (let client of clientList) {
        if (client.url === '/' && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow('/');
      }
    })
  );
});

/**
 * Cache-first strategy: Return from cache, fallback to network
 */
async function cacheFirstStrategy(request, cacheName) {
  try {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }

    const response = await fetch(request);
    const cache = await caches.open(cacheName);
    cache.put(request, response.clone());
    return response;
  } catch (error) {
    console.error('Fetch failed:', error);
    return caches.match(request) || new Response('Offline', { status: 503 });
  }
}

/**
 * Network-first strategy: Try network, fallback to cache
 */
async function networkFirstStrategy(request, cacheName) {
  try {
    const response = await fetch(request);
    const cache = await caches.open(cacheName);
    cache.put(request, response.clone());
    return response;
  } catch (error) {
    console.error('Network failed, using cache:', error);
    return caches.match(request) || new Response('Offline', { status: 503 });
  }
}

/**
 * Sync pending form submissions from IndexedDB
 */
async function syncPendingForms() {
  try {
    const db = await openDatabase();
    const tx = db.transaction('sync_queue', 'readonly');
    const store = tx.objectStore('sync_queue');
    const items = await store.getAll();

    for (const item of items) {
      try {
        const response = await fetch(item.endpoint, {
          method: item.method || 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(item.payload),
        });

        if (response.ok) {
          const deleteTx = db.transaction('sync_queue', 'readwrite');
          deleteTx.objectStore('sync_queue').delete(item.id);
          await deleteTx.complete;
        }
      } catch (error) {
        console.error('Sync failed for item:', item.id, error);
      }
    }
  } catch (error) {
    console.error('Sync queue processing failed:', error);
  }
}

/**
 * Open IndexedDB connection
 */
function openDatabase() {
  return new Promise((resolve, reject) => {
    const request = indexedDB.open('rrmcs_db', 1);

    request.onerror = () => reject(request.error);
    request.onsuccess = () => resolve(request.result);

    request.onupgradeneeded = (event) => {
      const db = event.target.result;

      if (!db.objectStoreNames.contains('sync_queue')) {
        db.createObjectStore('sync_queue', { keyPath: 'id', autoIncrement: true });
      }
    };
  });
}

console.log('ServiceWorker: Installed');
