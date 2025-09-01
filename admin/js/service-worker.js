/**
 * Orlando International Resorts - Service Worker
 * PWA functionality with offline support and background sync
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

const CACHE_NAME = 'orlando-resorts-v1.0.0';
const CACHE_VERSION = '1.0.0';

// Resources to cache for offline functionality
const STATIC_CACHE_URLS = [
    '/admin/',
    '/admin/css/bootstrap.css',
    '/admin/css/mobile-responsive.css',
    '/admin/css/custom-styles.css',
    '/admin/js/jquery-2.1.4.min.js',
    '/admin/js/bootstrap-3.1.1.min.js',
    '/admin/js/notification-manager.js',
    '/admin/js/operations-dashboard.js',
    '/admin/js/analytics-dashboard.js',
    '/Hotel/images/logo-full.png',
    '/admin/assets/font-awesome/css/font-awesome.min.css'
];

// API endpoints to cache
const API_CACHE_URLS = [
    '/admin/api/get_dashboard_stats.php',
    '/admin/api/get_operations_stats.php',
    '/admin/api/get_notifications.php'
];

// Dynamic cache patterns
const CACHE_PATTERNS = {
    images: /\.(png|jpg|jpeg|svg|gif|webp)$/i,
    api: /\/admin\/api\//,
    pages: /\/admin\/.*\.php$/
};

/**
 * Service Worker Installation
 */
self.addEventListener('install', (event) => {
    console.log('[ServiceWorker] Installing...');
    
    event.waitUntil(
        Promise.all([
            // Cache static resources
            caches.open(CACHE_NAME + '-static').then((cache) => {
                console.log('[ServiceWorker] Caching static resources');
                return cache.addAll(STATIC_CACHE_URLS);
            }),
            
            // Cache API resources
            caches.open(CACHE_NAME + '-api').then((cache) => {
                console.log('[ServiceWorker] Caching API resources');
                return cache.addAll(API_CACHE_URLS);
            })
        ]).then(() => {
            console.log('[ServiceWorker] Installation successful');
            // Skip waiting to activate immediately
            return self.skipWaiting();
        }).catch((error) => {
            console.error('[ServiceWorker] Installation failed:', error);
        })
    );
});

/**
 * Service Worker Activation
 */
self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activating...');
    
    event.waitUntil(
        Promise.all([
            // Clean up old caches
            cleanupOldCaches(),
            
            // Claim all clients
            self.clients.claim()
        ]).then(() => {
            console.log('[ServiceWorker] Activation successful');
        })
    );
});

/**
 * Fetch Event Handler - Main caching strategy
 */
self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);
    
    // Skip non-GET requests and chrome-extension requests
    if (request.method !== 'GET' || url.protocol === 'chrome-extension:') {
        return;
    }
    
    event.respondWith(
        handleFetchRequest(request)
    );
});

/**
 * Background Sync for offline actions
 */
self.addEventListener('sync', (event) => {
    console.log('[ServiceWorker] Background sync:', event.tag);
    
    switch (event.tag) {
        case 'background-sync-notifications':
            event.waitUntil(syncNotifications());
            break;
            
        case 'background-sync-operations':
            event.waitUntil(syncOperations());
            break;
            
        case 'background-sync-analytics':
            event.waitUntil(syncAnalytics());
            break;
            
        default:
            console.log('[ServiceWorker] Unknown sync tag:', event.tag);
    }
});

/**
 * Push Notification Handler
 */
self.addEventListener('push', (event) => {
    console.log('[ServiceWorker] Push received');
    
    let notificationData = {
        title: 'Orlando International Resorts',
        body: 'You have a new notification',
        icon: '/Hotel/images/logo-full.png',
        badge: '/admin/assets/img/badge.png',
        tag: 'general'
    };
    
    if (event.data) {
        try {
            const data = event.data.json();
            notificationData = {
                ...notificationData,
                ...data
            };
        } catch (error) {
            console.error('[ServiceWorker] Error parsing push data:', error);
        }
    }
    
    event.waitUntil(
        self.registration.showNotification(notificationData.title, {
            body: notificationData.body,
            icon: notificationData.icon,
            badge: notificationData.badge,
            tag: notificationData.tag,
            data: notificationData.data,
            actions: [
                {
                    action: 'view',
                    title: 'View',
                    icon: '/admin/assets/img/view-icon.png'
                },
                {
                    action: 'dismiss',
                    title: 'Dismiss',
                    icon: '/admin/assets/img/dismiss-icon.png'
                }
            ],
            requireInteraction: notificationData.priority === 'high'
        })
    );
});

/**
 * Notification Click Handler
 */
self.addEventListener('notificationclick', (event) => {
    console.log('[ServiceWorker] Notification clicked:', event.action);
    
    event.notification.close();
    
    if (event.action === 'view') {
        // Open the app or navigate to specific page
        event.waitUntil(
            clients.matchAll({ type: 'window' }).then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if (client.url.includes('/admin/') && 'focus' in client) {
                        return client.focus();
                    }
                }
                
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow('/admin/');
                }
            })
        );
    }
});

/**
 * Handle fetch requests with caching strategies
 */
async function handleFetchRequest(request) {
    const url = new URL(request.url);
    
    try {
        // Strategy 1: Network First for API calls
        if (CACHE_PATTERNS.api.test(url.pathname)) {
            return await networkFirstStrategy(request, CACHE_NAME + '-api');
        }
        
        // Strategy 2: Cache First for images
        if (CACHE_PATTERNS.images.test(url.pathname)) {
            return await cacheFirstStrategy(request, CACHE_NAME + '-images');
        }
        
        // Strategy 3: Stale While Revalidate for pages
        if (CACHE_PATTERNS.pages.test(url.pathname)) {
            return await staleWhileRevalidateStrategy(request, CACHE_NAME + '-pages');
        }
        
        // Strategy 4: Cache First for static resources
        if (STATIC_CACHE_URLS.includes(url.pathname)) {
            return await cacheFirstStrategy(request, CACHE_NAME + '-static');
        }
        
        // Default: Network only
        return await fetch(request);
        
    } catch (error) {
        console.error('[ServiceWorker] Fetch error:', error);
        
        // Return offline fallback if available
        return getOfflineFallback(request);
    }
}

/**
 * Network First Strategy - Try network, fallback to cache
 */
async function networkFirstStrategy(request, cacheName) {
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.log('[ServiceWorker] Network failed, trying cache:', request.url);
        const cachedResponse = await caches.match(request);
        
        if (cachedResponse) {
            return cachedResponse;
        }
        
        throw error;
    }
}

/**
 * Cache First Strategy - Try cache, fallback to network
 */
async function cacheFirstStrategy(request, cacheName) {
    const cachedResponse = await caches.match(request);
    
    if (cachedResponse) {
        return cachedResponse;
    }
    
    try {
        const networkResponse = await fetch(request);
        
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        
        return networkResponse;
    } catch (error) {
        console.error('[ServiceWorker] Cache first strategy failed:', error);
        throw error;
    }
}

/**
 * Stale While Revalidate Strategy - Return cache, update in background
 */
async function staleWhileRevalidateStrategy(request, cacheName) {
    const cachedResponse = await caches.match(request);
    
    // Background update
    const networkResponsePromise = fetch(request).then(async (networkResponse) => {
        if (networkResponse.ok) {
            const cache = await caches.open(cacheName);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    }).catch((error) => {
        console.log('[ServiceWorker] Background update failed:', error);
    });
    
    // Return cached version immediately if available
    if (cachedResponse) {
        return cachedResponse;
    }
    
    // If no cache, wait for network
    return await networkResponsePromise;
}

/**
 * Clean up old cache versions
 */
async function cleanupOldCaches() {
    const cacheNames = await caches.keys();
    const oldCaches = cacheNames.filter(name => 
        name.startsWith('orlando-resorts-') && !name.includes(CACHE_VERSION)
    );
    
    return Promise.all(
        oldCaches.map(name => {
            console.log('[ServiceWorker] Deleting old cache:', name);
            return caches.delete(name);
        })
    );
}

/**
 * Get offline fallback response
 */
async function getOfflineFallback(request) {
    const url = new URL(request.url);
    
    // Return offline page for navigation requests
    if (request.mode === 'navigate') {
        const offlinePage = await caches.match('/admin/offline.html');
        if (offlinePage) {
            return offlinePage;
        }
    }
    
    // Return placeholder for images
    if (CACHE_PATTERNS.images.test(url.pathname)) {
        const placeholder = await caches.match('/admin/assets/img/offline-placeholder.svg');
        if (placeholder) {
            return placeholder;
        }
    }
    
    // Return offline API response
    if (CACHE_PATTERNS.api.test(url.pathname)) {
        return new Response(
            JSON.stringify({
                success: false,
                error: 'Offline - cached data not available',
                offline: true,
                timestamp: Date.now()
            }),
            {
                status: 503,
                statusText: 'Service Unavailable',
                headers: {
                    'Content-Type': 'application/json'
                }
            }
        );
    }
    
    throw new Error('No offline fallback available');
}

/**
 * Sync notifications when back online
 */
async function syncNotifications() {
    try {
        console.log('[ServiceWorker] Syncing notifications...');
        
        // Get pending notifications from IndexedDB
        const pendingNotifications = await getPendingNotifications();
        
        for (const notification of pendingNotifications) {
            try {
                const response = await fetch('/admin/api/sync-notification.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(notification)
                });
                
                if (response.ok) {
                    await removePendingNotification(notification.id);
                }
            } catch (error) {
                console.error('[ServiceWorker] Failed to sync notification:', error);
            }
        }
        
        console.log('[ServiceWorker] Notification sync completed');
    } catch (error) {
        console.error('[ServiceWorker] Notification sync failed:', error);
    }
}

/**
 * Sync operations data when back online
 */
async function syncOperations() {
    try {
        console.log('[ServiceWorker] Syncing operations data...');
        
        // Sync pending operations updates
        const pendingOperations = await getPendingOperations();
        
        for (const operation of pendingOperations) {
            try {
                const response = await fetch('/admin/api/sync-operation.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(operation)
                });
                
                if (response.ok) {
                    await removePendingOperation(operation.id);
                }
            } catch (error) {
                console.error('[ServiceWorker] Failed to sync operation:', error);
            }
        }
        
        console.log('[ServiceWorker] Operations sync completed');
    } catch (error) {
        console.error('[ServiceWorker] Operations sync failed:', error);
    }
}

/**
 * Sync analytics data when back online
 */
async function syncAnalytics() {
    try {
        console.log('[ServiceWorker] Syncing analytics data...');
        
        // Refresh cached analytics data
        const analyticsCache = await caches.open(CACHE_NAME + '-api');
        const analyticsRequests = [
            '/admin/api/analytics-api.php?action=dashboard',
            '/admin/api/analytics-api.php?action=realtime'
        ];
        
        for (const url of analyticsRequests) {
            try {
                const response = await fetch(url);
                if (response.ok) {
                    await analyticsCache.put(url, response.clone());
                }
            } catch (error) {
                console.error('[ServiceWorker] Failed to sync analytics:', url, error);
            }
        }
        
        console.log('[ServiceWorker] Analytics sync completed');
    } catch (error) {
        console.error('[ServiceWorker] Analytics sync failed:', error);
    }
}

/**
 * IndexedDB operations for offline storage
 */
async function getPendingNotifications() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('OrlandoResortsDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingNotifications'], 'readonly');
            const store = transaction.objectStore('pendingNotifications');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingNotifications')) {
                db.createObjectStore('pendingNotifications', { keyPath: 'id' });
            }
        };
    });
}

async function removePendingNotification(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('OrlandoResortsDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingNotifications'], 'readwrite');
            const store = transaction.objectStore('pendingNotifications');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

async function getPendingOperations() {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('OrlandoResortsDB', 1);
        
        request.onerror = () => reject(request.error);
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingOperations'], 'readonly');
            const store = transaction.objectStore('pendingOperations');
            const getAllRequest = store.getAll();
            
            getAllRequest.onsuccess = () => resolve(getAllRequest.result);
            getAllRequest.onerror = () => reject(getAllRequest.error);
        };
        
        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            if (!db.objectStoreNames.contains('pendingOperations')) {
                db.createObjectStore('pendingOperations', { keyPath: 'id' });
            }
        };
    });
}

async function removePendingOperation(id) {
    return new Promise((resolve, reject) => {
        const request = indexedDB.open('OrlandoResortsDB', 1);
        
        request.onsuccess = () => {
            const db = request.result;
            const transaction = db.transaction(['pendingOperations'], 'readwrite');
            const store = transaction.objectStore('pendingOperations');
            const deleteRequest = store.delete(id);
            
            deleteRequest.onsuccess = () => resolve();
            deleteRequest.onerror = () => reject(deleteRequest.error);
        };
    });
}

/**
 * Periodic background sync for data freshness
 */
self.addEventListener('periodicsync', (event) => {
    console.log('[ServiceWorker] Periodic sync:', event.tag);
    
    if (event.tag === 'content-sync') {
        event.waitUntil(
            Promise.all([
                syncNotifications(),
                syncOperations(),
                syncAnalytics()
            ])
        );
    }
});

/**
 * Handle app update
 */
self.addEventListener('message', (event) => {
    if (event.data && event.data.type === 'SKIP_WAITING') {
        console.log('[ServiceWorker] Skipping waiting...');
        self.skipWaiting();
    }
});

console.log('[ServiceWorker] Service Worker loaded successfully');

