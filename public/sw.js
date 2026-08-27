// Service Worker SGIN PWA - Version 11
// v11: DELETE all old cache on activate so stale Vite JS bundles are force-refreshed
const CACHE_NAME = 'sgin-pwa-v11';

const STATIC_ASSETS = [
  './icons/icon-192x192.png',
  './icons/icon-512x512.png',
];

// Install: skip waiting so new SW takes over immediately
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS).catch(() => {}))
  );
});

// Activate: DELETE ALL old caches (including v10, v9, etc.) then claim clients
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.map((key) => caches.delete(key))))
      .then(() => caches.open(CACHE_NAME))
      .then((cache) => cache.addAll(STATIC_ASSETS).catch(() => {}))
      .then(() => self.clients.claim())
  );
});

// Fetch: DO NOT cache Vite JS/CSS build assets - always fetch from network
self.addEventListener('fetch', (event) => {
  if (event.request.method !== 'GET') return;

  const url = new URL(event.request.url);

  // Vite hashed JS/CSS assets: Network-only (never cache, file hashes handle versioning)
  if (url.pathname.includes('/build/assets/')) {
    return; // browser handles natively
  }

  // Google Fonts: Cache-first
  if (url.hostname.includes('fonts.googleapis.com') || url.hostname.includes('fonts.gstatic.com')) {
    event.respondWith(
      caches.match(event.request).then((cached) => {
        if (cached) return cached;
        return fetch(event.request).then((res) => {
          if (res && res.status === 200) {
            caches.open(CACHE_NAME).then((c) => c.put(event.request, res.clone()));
          }
          return res;
        });
      })
    );
    return;
  }

  // Icons & PWA manifest: Network-first
  if (url.pathname.includes('/icons/') || url.pathname.includes('manifest')) {
    event.respondWith(
      fetch(event.request)
        .then((res) => {
          if (res && res.status === 200) {
            caches.open(CACHE_NAME).then((c) => c.put(event.request, res.clone()));
          }
          return res;
        })
        .catch(() => caches.match(event.request))
    );
    return;
  }

  // All other requests (page navigation, API, etc.): Let browser handle natively
});
