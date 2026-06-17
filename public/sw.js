/**
 * CardVault Service Worker
 * Caches static assets for fast loading and offline app shell.
 *
 * Bump CACHE_VERSION on every deploy that changes CSS/JS/images.
 */
const CACHE_VERSION = 'cardvault-v21';

const STATIC_ASSETS = [
    '/css/style.css',
    '/js/app.js',
    '/img/logo.png',
    '/img/icon-192-v3.png',
    '/img/icon-512-v3.png',
    '/manifest-v3.json'
];

// ── Install: pre-cache static assets ──────────────────────────────────────────
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_VERSION)
            .then(cache => cache.addAll(STATIC_ASSETS))
            .then(() => self.skipWaiting())
    );
});

// ── Activate: purge old caches ────────────────────────────────────────────────
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then(keys =>
            Promise.all(
                keys.filter(k => k !== CACHE_VERSION)
                    .map(k => caches.delete(k))
            )
        ).then(() => self.clients.claim())
    );
});

// ── Fetch strategy ────────────────────────────────────────────────────────────
// Static assets  → cache-first  (fast; updated on next SW install)
// PHP pages/API  → network-first (always fresh; falls back to cache)
self.addEventListener('fetch', (event) => {
    const url = new URL(event.request.url);

    // Only handle same-origin requests
    if (url.origin !== location.origin) return;

    // Skip POST requests (form submissions, API calls)
    if (event.request.method !== 'GET') return;

    // Static assets → cache-first
    const isStatic = STATIC_ASSETS.some(asset => url.pathname === asset || url.pathname.endsWith(asset));
    if (isStatic) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                return cached || fetch(event.request).then(response => {
                    // Cache the new asset for next time
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then(cache => cache.put(event.request, clone));
                    return response;
                });
            })
        );
        return;
    }

    // Card images (uploads/) → cache-first (they never change)
    if (url.pathname.startsWith('/uploads/')) {
        event.respondWith(
            caches.match(event.request).then(cached => {
                return cached || fetch(event.request).then(response => {
                    if (response.ok) {
                        const clone = response.clone();
                        caches.open(CACHE_VERSION).then(cache => cache.put(event.request, clone));
                    }
                    return response;
                });
            })
        );
        return;
    }

    // Everything else (PHP pages, search, etc.) → network-first
    event.respondWith(
        fetch(event.request)
            .then(response => {
                // Cache successful page responses for offline fallback
                if (response.ok && response.headers.get('content-type')?.includes('text/html')) {
                    const clone = response.clone();
                    caches.open(CACHE_VERSION).then(cache => cache.put(event.request, clone));
                }
                return response;
            })
            .catch(() => {
                // Offline: try to serve from cache
                return caches.match(event.request).then(cached => {
                    if (cached) return cached;
                    // Final fallback: return a simple offline message
                    return new Response(
                        '<html><body style="font-family:system-ui;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;background:#0f172a;color:#e2e8f0;text-align:center;"><div><h1>📡 You\'re Offline</h1><p style="color:#94a3b8;">CardVault needs an internet connection to load new pages.<br>Please check your network and try again.</p><button onclick="location.reload()" style="margin-top:1rem;padding:0.75rem 1.5rem;background:#38b27a;color:white;border:none;border-radius:8px;font-size:1rem;cursor:pointer;">Retry</button></div></body></html>',
                        { headers: { 'Content-Type': 'text/html' } }
                    );
                });
            })
    );
});
