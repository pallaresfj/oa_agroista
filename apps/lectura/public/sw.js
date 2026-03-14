/**
 * Teaching Assistance Service Worker
 * Provides offline caching and PWA functionality
 */

const CACHE_NAME = 'teaching-assistance-v1';
const STATIC_CACHE = 'static-v1';
const DYNAMIC_CACHE = 'dynamic-v1';

// Static assets to cache on install
const urlsToCache = [
    '/',
    '/css/app.css',
    '/js/app.js',
    '/manifest.json',
    '/offline.html'
];

// Install event - cache static assets
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Install');

    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => {
                console.log('[ServiceWorker] Pre-caching static assets');
                return cache.addAll(urlsToCache);
            })
            .then(() => self.skipWaiting())
            .catch((err) => console.log('[ServiceWorker] Cache error:', err))
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate');

    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames
                    .filter((name) => name !== STATIC_CACHE && name !== DYNAMIC_CACHE)
                    .map((name) => {
                        console.log('[ServiceWorker] Removing old cache:', name);
                        return caches.delete(name);
                    })
            );
        }).then(() => self.clients.claim())
    );
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
    // Skip non-GET requests
    if (event.request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Skip admin panel requests (always fetch fresh)
    if (event.request.url.includes('/admin')) {
        return;
    }

    event.respondWith(
        caches.match(event.request)
            .then((cachedResponse) => {
                // Return cached version if available
                if (cachedResponse) {
                    // Fetch fresh version in background
                    fetchAndCache(event.request);
                    return cachedResponse;
                }

                // Not in cache, fetch from network
                return fetchAndCache(event.request);
            })
            .catch(() => {
                // Offline fallback for navigation requests
                if (event.request.mode === 'navigate') {
                    return caches.match('/offline.html');
                }
                return new Response('Offline', { status: 503 });
            })
    );
});

// Helper: Fetch and cache response
function fetchAndCache(request) {
    return fetch(request)
        .then((response) => {
            // Don't cache non-successful responses
            if (!response || response.status !== 200 || response.type !== 'basic') {
                return response;
            }

            // Clone the response
            const responseToCache = response.clone();

            // Cache the fetched response
            caches.open(DYNAMIC_CACHE)
                .then((cache) => {
                    cache.put(request, responseToCache);
                });

            return response;
        });
}

// Background sync for attendance registration
self.addEventListener('sync', (event) => {
    if (event.tag === 'sync-attendance') {
        event.waitUntil(syncAttendance());
    }
});

async function syncAttendance() {
    try {
        const db = await openDB();
        const pendingAttendances = await getPendingAttendances(db);

        for (const attendance of pendingAttendances) {
            await fetch('/attendance/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': attendance.csrf
                },
                body: JSON.stringify(attendance.data)
            });

            await deletePendingAttendance(db, attendance.id);
        }
    } catch (error) {
        console.error('[ServiceWorker] Sync failed:', error);
    }
}

// Push notifications handler
self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Push received');

    let data = { title: 'Teaching Assistance', body: 'Nueva notificación' };

    if (event.data) {
        data = event.data.json();
    }

    event.waitUntil(
        self.registration.showNotification(data.title, {
            body: data.body,
            icon: '/images/icon-192x192.png',
            badge: '/images/icon-72x72.png',
            vibrate: [100, 50, 100],
            data: data.data || {}
        })
    );
});

// Notification click handler
self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Notification clicked');

    event.notification.close();

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                if (clientList.length > 0) {
                    return clientList[0].focus();
                }
                return clients.openWindow('/dashboard');
            })
    );
});

console.log('[ServiceWorker] Loaded');
