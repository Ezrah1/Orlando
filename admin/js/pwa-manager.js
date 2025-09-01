/**
 * Orlando International Resorts - PWA Manager
 * Progressive Web App functionality with offline support and installation prompts
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isOnline = navigator.onLine;
        this.installButton = null;
        this.offlineIndicator = null;
        
        this.init();
    }

    /**
     * Initialize PWA Manager
     */
    async init() {
        console.log('[PWAManager] Initializing...');
        
        // Register service worker
        await this.registerServiceWorker();
        
        // Setup installation prompt
        this.setupInstallPrompt();
        
        // Setup offline detection
        this.setupOfflineDetection();
        
        // Setup update detection
        this.setupUpdateDetection();
        
        // Setup cross-device sync
        this.setupCrossDeviceSync();
        
        // Create UI elements
        this.createPWAUI();
        
        console.log('[PWAManager] Initialization complete');
    }

    /**
     * Register Service Worker
     */
    async registerServiceWorker() {
        if ('serviceWorker' in navigator) {
            try {
                const registration = await navigator.serviceWorker.register('/admin/js/service-worker.js', {
                    scope: '/admin/'
                });
                
                console.log('[PWAManager] Service Worker registered:', registration.scope);
                
                // Check for updates
                registration.addEventListener('updatefound', () => {
                    console.log('[PWAManager] Service Worker update found');
                    this.handleServiceWorkerUpdate(registration);
                });
                
                return registration;
            } catch (error) {
                console.error('[PWAManager] Service Worker registration failed:', error);
            }
        } else {
            console.warn('[PWAManager] Service Worker not supported');
        }
    }

    /**
     * Setup installation prompt handling
     */
    setupInstallPrompt() {
        // Listen for beforeinstallprompt event
        window.addEventListener('beforeinstallprompt', (event) => {
            console.log('[PWAManager] Install prompt available');
            
            // Prevent the mini-infobar from appearing
            event.preventDefault();
            
            // Save the event for later use
            this.deferredPrompt = event;
            
            // Show custom install banner
            this.showInstallBanner();
        });

        // Listen for app installation
        window.addEventListener('appinstalled', (event) => {
            console.log('[PWAManager] App installed successfully');
            this.isInstalled = true;
            this.hideInstallBanner();
            this.showNotification('App installed successfully!', 'success');
        });

        // Check if already running as installed app
        if (window.matchMedia('(display-mode: standalone)').matches || 
            window.navigator.standalone === true) {
            this.isInstalled = true;
            console.log('[PWAManager] Running as installed app');
        }
    }

    /**
     * Setup offline/online detection
     */
    setupOfflineDetection() {
        // Online/offline event listeners
        window.addEventListener('online', () => {
            console.log('[PWAManager] Back online');
            this.isOnline = true;
            this.handleOnlineStatus(true);
        });

        window.addEventListener('offline', () => {
            console.log('[PWAManager] Gone offline');
            this.isOnline = false;
            this.handleOnlineStatus(false);
        });

        // Initial status
        this.handleOnlineStatus(this.isOnline);
    }

    /**
     * Setup service worker update detection
     */
    setupUpdateDetection() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[PWAManager] Service Worker controller changed');
                this.showUpdateNotification();
            });
        }
    }

    /**
     * Setup cross-device synchronization
     */
    setupCrossDeviceSync() {
        // Setup IndexedDB for cross-device data storage
        this.initIndexedDB();
        
        // Setup periodic sync registration
        this.registerPeriodicSync();
        
        // Setup broadcast channel for tab communication
        if ('BroadcastChannel' in window) {
            this.broadcastChannel = new BroadcastChannel('orlando-resorts-sync');
            this.broadcastChannel.addEventListener('message', (event) => {
                this.handleCrossDeviceMessage(event.data);
            });
        }
    }

    /**
     * Create PWA UI elements
     */
    createPWAUI() {
        // Create offline indicator
        this.createOfflineIndicator();
        
        // Create update notification
        this.createUpdateNotification();
        
        // Create install banner
        this.createInstallBanner();
        
        // Add PWA-specific CSS classes
        document.body.classList.add('pwa-enabled');
        
        if (this.isInstalled) {
            document.body.classList.add('pwa-installed');
        }
    }

    /**
     * Create offline indicator
     */
    createOfflineIndicator() {
        this.offlineIndicator = document.createElement('div');
        this.offlineIndicator.className = 'offline-indicator';
        this.offlineIndicator.innerHTML = `
            <div class="offline-content">
                <i class="fas fa-wifi-slash"></i>
                <span>You're offline. Some features may be limited.</span>
                <button class="btn-reconnect" onclick="location.reload()">
                    <i class="fas fa-sync"></i> Try Again
                </button>
            </div>
        `;
        document.body.appendChild(this.offlineIndicator);
    }

    /**
     * Create update notification
     */
    createUpdateNotification() {
        this.updateNotification = document.createElement('div');
        this.updateNotification.className = 'update-notification';
        this.updateNotification.innerHTML = `
            <div class="update-content">
                <div class="update-text">
                    <i class="fas fa-download"></i>
                    <span>A new version is available!</span>
                </div>
                <div class="update-actions">
                    <button class="btn-update-later">Later</button>
                    <button class="btn-update-now">Update Now</button>
                </div>
            </div>
        `;
        
        // Add event listeners
        this.updateNotification.querySelector('.btn-update-now').addEventListener('click', () => {
            this.applyUpdate();
        });
        
        this.updateNotification.querySelector('.btn-update-later').addEventListener('click', () => {
            this.hideUpdateNotification();
        });
        
        document.body.appendChild(this.updateNotification);
    }

    /**
     * Create install banner
     */
    createInstallBanner() {
        this.installBanner = document.createElement('div');
        this.installBanner.className = 'pwa-install-banner';
        this.installBanner.innerHTML = `
            <div class="pwa-banner-content">
                <div class="pwa-banner-text">
                    <div class="pwa-banner-title">Install Orlando Resorts</div>
                    <div class="pwa-banner-subtitle">Get faster access and work offline</div>
                </div>
                <div class="pwa-banner-actions">
                    <button class="pwa-banner-btn" id="pwa-dismiss">Not Now</button>
                    <button class="pwa-banner-btn primary" id="pwa-install">Install</button>
                </div>
            </div>
        `;
        
        // Add event listeners
        this.installBanner.querySelector('#pwa-install').addEventListener('click', () => {
            this.installApp();
        });
        
        this.installBanner.querySelector('#pwa-dismiss').addEventListener('click', () => {
            this.hideInstallBanner();
            localStorage.setItem('pwa-install-dismissed', Date.now().toString());
        });
        
        document.body.appendChild(this.installBanner);
    }

    /**
     * Show install banner
     */
    showInstallBanner() {
        // Don't show if recently dismissed
        const dismissed = localStorage.getItem('pwa-install-dismissed');
        if (dismissed && (Date.now() - parseInt(dismissed)) < 7 * 24 * 60 * 60 * 1000) { // 7 days
            return;
        }
        
        // Don't show if already installed
        if (this.isInstalled) {
            return;
        }
        
        setTimeout(() => {
            this.installBanner.classList.add('show');
        }, 2000); // Show after 2 seconds
    }

    /**
     * Hide install banner
     */
    hideInstallBanner() {
        this.installBanner.classList.remove('show');
    }

    /**
     * Install the app
     */
    async installApp() {
        if (!this.deferredPrompt) {
            console.warn('[PWAManager] No install prompt available');
            return;
        }

        try {
            // Show the install prompt
            this.deferredPrompt.prompt();
            
            // Wait for user response
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log(`[PWAManager] Install prompt outcome: ${outcome}`);
            
            if (outcome === 'accepted') {
                this.showNotification('Installing app...', 'info');
            }
            
            // Clear the deferred prompt
            this.deferredPrompt = null;
            this.hideInstallBanner();
            
        } catch (error) {
            console.error('[PWAManager] Install failed:', error);
            this.showNotification('Installation failed. Please try again.', 'error');
        }
    }

    /**
     * Handle online/offline status changes
     */
    handleOnlineStatus(isOnline) {
        if (isOnline) {
            this.offlineIndicator.classList.remove('show');
            document.body.classList.remove('offline');
            this.showNotification('Back online!', 'success', 3000);
            this.syncPendingData();
        } else {
            this.offlineIndicator.classList.add('show');
            document.body.classList.add('offline');
            this.showNotification('You are now offline. Limited functionality available.', 'warning', 5000);
        }
    }

    /**
     * Handle service worker updates
     */
    handleServiceWorkerUpdate(registration) {
        const newWorker = registration.installing;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                // New service worker is available
                this.showUpdateNotification();
            }
        });
    }

    /**
     * Show update notification
     */
    showUpdateNotification() {
        this.updateNotification.classList.add('show');
    }

    /**
     * Hide update notification
     */
    hideUpdateNotification() {
        this.updateNotification.classList.remove('show');
    }

    /**
     * Apply app update
     */
    applyUpdate() {
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.getRegistration().then((registration) => {
                if (registration && registration.waiting) {
                    // Tell the waiting service worker to skip waiting
                    registration.waiting.postMessage({ type: 'SKIP_WAITING' });
                }
            });
        }
        
        // Reload the page to get the new version
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
        this.showNotification('Updating app...', 'info');
    }

    /**
     * Initialize IndexedDB for offline storage
     */
    async initIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('OrlandoResortsDB', 1);
            
            request.onerror = () => {
                console.error('[PWAManager] IndexedDB error:', request.error);
                reject(request.error);
            };
            
            request.onsuccess = () => {
                this.db = request.result;
                console.log('[PWAManager] IndexedDB initialized');
                resolve(this.db);
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create object stores
                if (!db.objectStoreNames.contains('pendingNotifications')) {
                    db.createObjectStore('pendingNotifications', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('pendingOperations')) {
                    db.createObjectStore('pendingOperations', { keyPath: 'id' });
                }
                
                if (!db.objectStoreNames.contains('syncData')) {
                    db.createObjectStore('syncData', { keyPath: 'key' });
                }
                
                console.log('[PWAManager] IndexedDB upgraded');
            };
        });
    }

    /**
     * Register periodic background sync
     */
    async registerPeriodicSync() {
        if ('serviceWorker' in navigator && 'periodicSync' in window.ServiceWorkerRegistration.prototype) {
            try {
                const registration = await navigator.serviceWorker.ready;
                await registration.periodicSync.register('content-sync', {
                    minInterval: 24 * 60 * 60 * 1000 // 24 hours
                });
                console.log('[PWAManager] Periodic sync registered');
            } catch (error) {
                console.log('[PWAManager] Periodic sync not supported or registration failed:', error);
            }
        }
    }

    /**
     * Sync pending data when back online
     */
    async syncPendingData() {
        if (!this.isOnline) return;

        try {
            // Trigger background sync
            if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
                const registration = await navigator.serviceWorker.ready;
                
                await Promise.all([
                    registration.sync.register('background-sync-notifications'),
                    registration.sync.register('background-sync-operations'),
                    registration.sync.register('background-sync-analytics')
                ]);
                
                console.log('[PWAManager] Background sync triggered');
            }
        } catch (error) {
            console.error('[PWAManager] Background sync failed:', error);
        }
    }

    /**
     * Handle cross-device messages
     */
    handleCrossDeviceMessage(data) {
        console.log('[PWAManager] Cross-device message received:', data);
        
        switch (data.type) {
            case 'NOTIFICATION_UPDATE':
                this.handleNotificationUpdate(data.payload);
                break;
                
            case 'OPERATIONS_UPDATE':
                this.handleOperationsUpdate(data.payload);
                break;
                
            case 'SYNC_REQUEST':
                this.handleSyncRequest(data.payload);
                break;
                
            default:
                console.log('[PWAManager] Unknown message type:', data.type);
        }
    }

    /**
     * Store data for offline use
     */
    async storeOfflineData(store, data) {
        if (!this.db) return;

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([store], 'readwrite');
            const objectStore = transaction.objectStore(store);
            const request = objectStore.put(data);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Retrieve offline data
     */
    async getOfflineData(store, key) {
        if (!this.db) return null;

        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([store], 'readonly');
            const objectStore = transaction.objectStore(store);
            const request = objectStore.get(key);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Show notification
     */
    showNotification(message, type = 'info', duration = 5000) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `pwa-notification pwa-notification-${type}`;
        notification.innerHTML = `
            <div class="pwa-notification-content">
                <i class="fas fa-${this.getNotificationIcon(type)}"></i>
                <span>${message}</span>
                <button class="pwa-notification-close">&times;</button>
            </div>
        `;
        
        // Add to DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => notification.classList.add('show'), 100);
        
        // Auto hide
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, duration);
        
        // Manual close
        notification.querySelector('.pwa-notification-close').addEventListener('click', () => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        });
    }

    /**
     * Get notification icon based on type
     */
    getNotificationIcon(type) {
        switch (type) {
            case 'success': return 'check-circle';
            case 'error': return 'exclamation-circle';
            case 'warning': return 'exclamation-triangle';
            case 'info': default: return 'info-circle';
        }
    }

    /**
     * Handle notification updates
     */
    handleNotificationUpdate(payload) {
        // Update notification UI if visible
        if (window.notificationManager) {
            window.notificationManager.handleUpdate(payload);
        }
    }

    /**
     * Handle operations updates
     */
    handleOperationsUpdate(payload) {
        // Update operations UI if visible
        if (window.operationsDashboard) {
            window.operationsDashboard.handleUpdate(payload);
        }
    }

    /**
     * Handle sync requests
     */
    handleSyncRequest(payload) {
        // Trigger appropriate sync
        this.syncPendingData();
    }

    /**
     * Broadcast message to other tabs/devices
     */
    broadcastMessage(type, payload) {
        if (this.broadcastChannel) {
            this.broadcastChannel.postMessage({ type, payload });
        }
    }

    /**
     * Check if app is installed
     */
    isAppInstalled() {
        return this.isInstalled;
    }

    /**
     * Check if online
     */
    isAppOnline() {
        return this.isOnline;
    }

    /**
     * Get app version
     */
    getAppVersion() {
        return '1.0.0';
    }

    /**
     * Clear app cache
     */
    async clearCache() {
        if ('caches' in window) {
            const cacheNames = await caches.keys();
            await Promise.all(
                cacheNames.map(name => caches.delete(name))
            );
            console.log('[PWAManager] Cache cleared');
        }
    }

    /**
     * Reset app data
     */
    async resetAppData() {
        // Clear caches
        await this.clearCache();
        
        // Clear IndexedDB
        if (this.db) {
            this.db.close();
            await new Promise((resolve, reject) => {
                const deleteRequest = indexedDB.deleteDatabase('OrlandoResortsDB');
                deleteRequest.onsuccess = () => resolve();
                deleteRequest.onerror = () => reject(deleteRequest.error);
            });
        }
        
        // Clear localStorage
        localStorage.clear();
        
        // Clear sessionStorage
        sessionStorage.clear();
        
        console.log('[PWAManager] App data reset');
        
        // Reload app
        window.location.reload();
    }
}

// Global PWA Manager instance
window.pwaManager = null;

// Initialize PWA Manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.pwaManager = new PWAManager();
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = PWAManager;
}
