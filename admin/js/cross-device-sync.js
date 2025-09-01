/**
 * Orlando International Resorts - Cross-Device Sync Manager
 * Seamless data synchronization across multiple devices and browser tabs
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class CrossDeviceSync {
    constructor() {
        this.deviceId = this.generateDeviceId();
        this.sessionId = this.generateSessionId();
        this.broadcastChannel = null;
        this.webSocket = null;
        this.syncQueue = [];
        this.lastSyncTime = 0;
        this.syncInterval = null;
        this.isOnline = navigator.onLine;
        this.storage = this.initStorage();
        
        this.init();
    }

    /**
     * Initialize cross-device sync
     */
    async init() {
        console.log('[CrossDeviceSync] Initializing...');
        
        // Setup storage
        await this.setupStorage();
        
        // Setup broadcast channel for tab communication
        this.setupBroadcastChannel();
        
        // Setup WebSocket for real-time sync
        this.setupWebSocket();
        
        // Setup periodic sync
        this.setupPeriodicSync();
        
        // Setup online/offline handlers
        this.setupNetworkHandlers();
        
        // Setup page visibility handlers
        this.setupVisibilityHandlers();
        
        // Initial sync
        this.performSync();
        
        console.log('[CrossDeviceSync] Initialization complete');
    }

    /**
     * Generate unique device ID
     */
    generateDeviceId() {
        let deviceId = localStorage.getItem('orlando_device_id');
        if (!deviceId) {
            deviceId = 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('orlando_device_id', deviceId);
        }
        return deviceId;
    }

    /**
     * Generate session ID
     */
    generateSessionId() {
        return 'session_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    }

    /**
     * Initialize storage mechanism
     */
    initStorage() {
        // Try IndexedDB first, fallback to localStorage
        if ('indexedDB' in window) {
            return 'indexeddb';
        } else if ('localStorage' in window) {
            return 'localstorage';
        } else {
            return 'memory';
        }
    }

    /**
     * Setup storage databases
     */
    async setupStorage() {
        if (this.storage === 'indexeddb') {
            await this.setupIndexedDB();
        }
    }

    /**
     * Setup IndexedDB
     */
    async setupIndexedDB() {
        return new Promise((resolve, reject) => {
            const request = indexedDB.open('OrlandoResortsSyncDB', 1);
            
            request.onerror = () => {
                console.error('[CrossDeviceSync] IndexedDB error:', request.error);
                this.storage = 'localstorage';
                resolve();
            };
            
            request.onsuccess = () => {
                this.db = request.result;
                console.log('[CrossDeviceSync] IndexedDB ready');
                resolve();
            };
            
            request.onupgradeneeded = (event) => {
                const db = event.target.result;
                
                // Create sync data store
                if (!db.objectStoreNames.contains('syncData')) {
                    const syncStore = db.createObjectStore('syncData', { keyPath: 'id' });
                    syncStore.createIndex('timestamp', 'timestamp', { unique: false });
                    syncStore.createIndex('deviceId', 'deviceId', { unique: false });
                    syncStore.createIndex('type', 'type', { unique: false });
                }
                
                // Create conflict resolution store
                if (!db.objectStoreNames.contains('conflicts')) {
                    const conflictStore = db.createObjectStore('conflicts', { keyPath: 'id' });
                    conflictStore.createIndex('timestamp', 'timestamp', { unique: false });
                }
                
                // Create device registry
                if (!db.objectStoreNames.contains('devices')) {
                    const deviceStore = db.createObjectStore('devices', { keyPath: 'deviceId' });
                    deviceStore.createIndex('lastSeen', 'lastSeen', { unique: false });
                }
                
                console.log('[CrossDeviceSync] IndexedDB schema created');
            };
        });
    }

    /**
     * Setup broadcast channel for tab communication
     */
    setupBroadcastChannel() {
        if ('BroadcastChannel' in window) {
            this.broadcastChannel = new BroadcastChannel('orlando-resorts-sync');
            
            this.broadcastChannel.addEventListener('message', (event) => {
                this.handleBroadcastMessage(event.data);
            });
            
            console.log('[CrossDeviceSync] Broadcast channel ready');
        }
    }

    /**
     * Setup WebSocket for real-time sync
     */
    setupWebSocket() {
        const protocol = window.location.protocol === 'https:' ? 'wss:' : 'ws:';
        const wsUrl = `${protocol}//${window.location.host}/admin/api/websocket-sync.php`;
        
        try {
            this.webSocket = new WebSocket(wsUrl);
            
            this.webSocket.onopen = () => {
                console.log('[CrossDeviceSync] WebSocket connected');
                this.sendWebSocketMessage({
                    type: 'register',
                    deviceId: this.deviceId,
                    sessionId: this.sessionId,
                    userAgent: navigator.userAgent,
                    timestamp: Date.now()
                });
            };
            
            this.webSocket.onmessage = (event) => {
                const data = JSON.parse(event.data);
                this.handleWebSocketMessage(data);
            };
            
            this.webSocket.onclose = () => {
                console.log('[CrossDeviceSync] WebSocket disconnected');
                // Attempt reconnection after 5 seconds
                setTimeout(() => this.setupWebSocket(), 5000);
            };
            
            this.webSocket.onerror = (error) => {
                console.error('[CrossDeviceSync] WebSocket error:', error);
            };
            
        } catch (error) {
            console.warn('[CrossDeviceSync] WebSocket not available:', error);
        }
    }

    /**
     * Setup periodic sync
     */
    setupPeriodicSync() {
        // Sync every 30 seconds when active
        this.syncInterval = setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.performSync();
            }
        }, 30000);
        
        // Sync on page focus
        window.addEventListener('focus', () => {
            this.performSync();
        });
    }

    /**
     * Setup network status handlers
     */
    setupNetworkHandlers() {
        window.addEventListener('online', () => {
            console.log('[CrossDeviceSync] Back online, syncing...');
            this.isOnline = true;
            this.performSync();
        });
        
        window.addEventListener('offline', () => {
            console.log('[CrossDeviceSync] Gone offline');
            this.isOnline = false;
        });
    }

    /**
     * Setup page visibility handlers
     */
    setupVisibilityHandlers() {
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible') {
                // Page became visible, sync data
                this.performSync();
            } else {
                // Page hidden, save current state
                this.saveCurrentState();
            }
        });
        
        // Save state before page unload
        window.addEventListener('beforeunload', () => {
            this.saveCurrentState();
        });
    }

    /**
     * Handle broadcast messages from other tabs
     */
    handleBroadcastMessage(data) {
        console.log('[CrossDeviceSync] Broadcast message received:', data.type);
        
        switch (data.type) {
            case 'DATA_UPDATED':
                this.handleDataUpdate(data.payload);
                break;
                
            case 'SYNC_REQUEST':
                this.handleSyncRequest(data.payload);
                break;
                
            case 'CONFLICT_DETECTED':
                this.handleConflict(data.payload);
                break;
                
            case 'DEVICE_REGISTERED':
                this.handleDeviceRegistration(data.payload);
                break;
                
            default:
                console.log('[CrossDeviceSync] Unknown broadcast message:', data.type);
        }
    }

    /**
     * Handle WebSocket messages
     */
    handleWebSocketMessage(data) {
        console.log('[CrossDeviceSync] WebSocket message received:', data.type);
        
        switch (data.type) {
            case 'sync_data':
                this.processSyncData(data.payload);
                break;
                
            case 'conflict_resolution':
                this.handleConflictResolution(data.payload);
                break;
                
            case 'device_update':
                this.updateDeviceRegistry(data.payload);
                break;
                
            case 'force_sync':
                this.performSync(true);
                break;
                
            default:
                console.log('[CrossDeviceSync] Unknown WebSocket message:', data.type);
        }
    }

    /**
     * Perform data synchronization
     */
    async performSync(force = false) {
        if (!this.isOnline && !force) {
            console.log('[CrossDeviceSync] Offline, skipping sync');
            return;
        }
        
        const now = Date.now();
        if (now - this.lastSyncTime < 5000 && !force) {
            console.log('[CrossDeviceSync] Sync too recent, skipping');
            return;
        }
        
        try {
            console.log('[CrossDeviceSync] Starting sync...');
            
            // Get local changes
            const localChanges = await this.getLocalChanges();
            
            // Send changes to server
            const serverResponse = await this.sendChangesToServer(localChanges);
            
            // Process server changes
            await this.processServerChanges(serverResponse.changes);
            
            // Handle conflicts
            if (serverResponse.conflicts) {
                await this.handleConflicts(serverResponse.conflicts);
            }
            
            // Update last sync time
            this.lastSyncTime = now;
            await this.updateSyncTimestamp(now);
            
            // Broadcast sync completion
            this.broadcastMessage({
                type: 'SYNC_COMPLETED',
                payload: {
                    timestamp: now,
                    deviceId: this.deviceId,
                    changesCount: localChanges.length
                }
            });
            
            console.log('[CrossDeviceSync] Sync completed successfully');
            
        } catch (error) {
            console.error('[CrossDeviceSync] Sync failed:', error);
            
            // Add to retry queue
            this.addToRetryQueue({
                type: 'sync',
                timestamp: now,
                error: error.message
            });
        }
    }

    /**
     * Sync specific data type
     */
    async syncData(dataType, data, operation = 'update') {
        const syncItem = {
            id: this.generateSyncId(),
            type: dataType,
            operation: operation,
            data: data,
            timestamp: Date.now(),
            deviceId: this.deviceId,
            sessionId: this.sessionId
        };
        
        // Store locally first
        await this.storeLocalChange(syncItem);
        
        // Add to sync queue
        this.syncQueue.push(syncItem);
        
        // Broadcast to other tabs
        this.broadcastMessage({
            type: 'DATA_UPDATED',
            payload: syncItem
        });
        
        // Trigger sync if online
        if (this.isOnline) {
            this.performSync();
        }
        
        return syncItem.id;
    }

    /**
     * Get local changes since last sync
     */
    async getLocalChanges() {
        const lastSyncTime = await this.getLastSyncTimestamp();
        
        if (this.storage === 'indexeddb') {
            return this.getLocalChangesFromIndexedDB(lastSyncTime);
        } else {
            return this.getLocalChangesFromLocalStorage(lastSyncTime);
        }
    }

    /**
     * Get local changes from IndexedDB
     */
    async getLocalChangesFromIndexedDB(since) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction(['syncData'], 'readonly');
            const store = transaction.objectStore('syncData');
            const index = store.index('timestamp');
            const range = IDBKeyRange.lowerBound(since);
            const request = index.getAll(range);
            
            request.onsuccess = () => {
                const changes = request.result.filter(item => 
                    item.deviceId === this.deviceId && !item.synced
                );
                resolve(changes);
            };
            
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Send changes to server
     */
    async sendChangesToServer(changes) {
        const response = await fetch('/admin/api/sync-data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Device-ID': this.deviceId,
                'X-Session-ID': this.sessionId
            },
            body: JSON.stringify({
                changes: changes,
                lastSyncTime: this.lastSyncTime,
                deviceInfo: {
                    userAgent: navigator.userAgent,
                    platform: navigator.platform,
                    language: navigator.language
                }
            })
        });
        
        if (!response.ok) {
            throw new Error(`Sync failed: ${response.status} ${response.statusText}`);
        }
        
        return await response.json();
    }

    /**
     * Process changes from server
     */
    async processServerChanges(changes) {
        for (const change of changes) {
            // Check for conflicts
            const localChange = await this.getLocalChange(change.id);
            if (localChange && localChange.timestamp > change.timestamp) {
                // Local change is newer, create conflict
                await this.createConflict(localChange, change);
                continue;
            }
            
            // Apply server change
            await this.applyChange(change);
            
            // Broadcast to other tabs
            this.broadcastMessage({
                type: 'DATA_UPDATED',
                payload: change
            });
        }
    }

    /**
     * Apply a change to local data
     */
    async applyChange(change) {
        console.log('[CrossDeviceSync] Applying change:', change.type, change.operation);
        
        // Store the change
        await this.storeLocalChange(change);
        
        // Update UI based on change type
        switch (change.type) {
            case 'dashboard_stats':
                this.updateDashboardStats(change.data);
                break;
                
            case 'notifications':
                this.updateNotifications(change.data);
                break;
                
            case 'room_status':
                this.updateRoomStatus(change.data);
                break;
                
            case 'booking':
                this.updateBooking(change.data);
                break;
                
            case 'user_preferences':
                this.updateUserPreferences(change.data);
                break;
                
            default:
                console.log('[CrossDeviceSync] Unknown change type:', change.type);
        }
    }

    /**
     * Handle data conflicts
     */
    async handleConflicts(conflicts) {
        for (const conflict of conflicts) {
            console.log('[CrossDeviceSync] Handling conflict:', conflict.id);
            
            // Store conflict for resolution
            await this.storeConflict(conflict);
            
            // Determine resolution strategy
            const resolution = await this.resolveConflict(conflict);
            
            if (resolution) {
                await this.applyConflictResolution(conflict.id, resolution);
            } else {
                // Requires manual resolution
                this.showConflictDialog(conflict);
            }
        }
    }

    /**
     * Resolve conflict automatically if possible
     */
    async resolveConflict(conflict) {
        const { local, remote } = conflict;
        
        // Simple resolution strategies
        switch (conflict.type) {
            case 'timestamp':
                // Use most recent
                return local.timestamp > remote.timestamp ? local : remote;
                
            case 'user_preference':
                // Use local preference
                return local;
                
            case 'system_data':
                // Use remote (server is authoritative)
                return remote;
                
            default:
                // Requires manual resolution
                return null;
        }
    }

    /**
     * Store local change
     */
    async storeLocalChange(change) {
        if (this.storage === 'indexeddb') {
            return this.storeInIndexedDB('syncData', change);
        } else {
            return this.storeInLocalStorage('syncData', change);
        }
    }

    /**
     * Store data in IndexedDB
     */
    async storeInIndexedDB(storeName, data) {
        return new Promise((resolve, reject) => {
            const transaction = this.db.transaction([storeName], 'readwrite');
            const store = transaction.objectStore(storeName);
            const request = store.put(data);
            
            request.onsuccess = () => resolve(request.result);
            request.onerror = () => reject(request.error);
        });
    }

    /**
     * Store data in localStorage
     */
    storeInLocalStorage(key, data) {
        try {
            const existing = JSON.parse(localStorage.getItem(key) || '[]');
            existing.push(data);
            
            // Keep only last 1000 items
            if (existing.length > 1000) {
                existing.splice(0, existing.length - 1000);
            }
            
            localStorage.setItem(key, JSON.stringify(existing));
            return Promise.resolve();
        } catch (error) {
            return Promise.reject(error);
        }
    }

    /**
     * Generate sync ID
     */
    generateSyncId() {
        return `${this.deviceId}_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }

    /**
     * Broadcast message to other tabs
     */
    broadcastMessage(message) {
        if (this.broadcastChannel) {
            this.broadcastChannel.postMessage(message);
        }
    }

    /**
     * Send WebSocket message
     */
    sendWebSocketMessage(message) {
        if (this.webSocket && this.webSocket.readyState === WebSocket.OPEN) {
            this.webSocket.send(JSON.stringify(message));
        }
    }

    /**
     * Update dashboard stats
     */
    updateDashboardStats(data) {
        // Update dashboard widgets if visible
        if (window.dashboardManager) {
            window.dashboardManager.updateStats(data);
        }
        
        // Update specific stat elements
        Object.keys(data).forEach(key => {
            const element = document.querySelector(`[data-stat="${key}"]`);
            if (element) {
                element.textContent = data[key];
                element.classList.add('updated');
                setTimeout(() => element.classList.remove('updated'), 1000);
            }
        });
    }

    /**
     * Update notifications
     */
    updateNotifications(data) {
        if (window.notificationManager) {
            window.notificationManager.handleSyncUpdate(data);
        }
    }

    /**
     * Update room status
     */
    updateRoomStatus(data) {
        // Update room status displays
        const roomElements = document.querySelectorAll(`[data-room-id="${data.room_id}"]`);
        roomElements.forEach(element => {
            element.setAttribute('data-status', data.status);
            element.classList.add('status-updated');
            setTimeout(() => element.classList.remove('status-updated'), 1000);
        });
    }

    /**
     * Update booking information
     */
    updateBooking(data) {
        // Update booking displays
        if (window.operationsDashboard) {
            window.operationsDashboard.updateBooking(data);
        }
    }

    /**
     * Update user preferences
     */
    updateUserPreferences(data) {
        // Apply user preferences
        Object.keys(data).forEach(key => {
            localStorage.setItem(`pref_${key}`, JSON.stringify(data[key]));
        });
        
        // Trigger preference update event
        window.dispatchEvent(new CustomEvent('preferencesUpdated', {
            detail: data
        }));
    }

    /**
     * Save current application state
     */
    saveCurrentState() {
        const state = {
            timestamp: Date.now(),
            url: window.location.href,
            scrollPosition: {
                x: window.scrollX,
                y: window.scrollY
            },
            formData: this.captureFormData(),
            activeElements: this.captureActiveElements()
        };
        
        this.syncData('application_state', state, 'update');
    }

    /**
     * Capture form data
     */
    captureFormData() {
        const forms = {};
        document.querySelectorAll('form[data-sync]').forEach(form => {
            const formData = new FormData(form);
            const data = {};
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }
            forms[form.id || form.name] = data;
        });
        return forms;
    }

    /**
     * Capture active elements
     */
    captureActiveElements() {
        return {
            activeTab: document.querySelector('.nav-link.active')?.href,
            openModals: Array.from(document.querySelectorAll('.modal.show')).map(m => m.id),
            selectedItems: Array.from(document.querySelectorAll('.selected')).map(el => el.id)
        };
    }

    /**
     * Get last sync timestamp
     */
    async getLastSyncTimestamp() {
        if (this.storage === 'indexeddb') {
            // Get from IndexedDB
            return new Promise((resolve) => {
                const transaction = this.db.transaction(['syncData'], 'readonly');
                const store = transaction.objectStore('syncData');
                const request = store.get('last_sync_time');
                
                request.onsuccess = () => {
                    resolve(request.result?.timestamp || 0);
                };
                
                request.onerror = () => resolve(0);
            });
        } else {
            // Get from localStorage
            return parseInt(localStorage.getItem('last_sync_time') || '0');
        }
    }

    /**
     * Update sync timestamp
     */
    async updateSyncTimestamp(timestamp) {
        if (this.storage === 'indexeddb') {
            await this.storeInIndexedDB('syncData', {
                id: 'last_sync_time',
                timestamp: timestamp
            });
        } else {
            localStorage.setItem('last_sync_time', timestamp.toString());
        }
    }

    /**
     * Clean up resources
     */
    destroy() {
        // Clear intervals
        if (this.syncInterval) {
            clearInterval(this.syncInterval);
        }
        
        // Close connections
        if (this.webSocket) {
            this.webSocket.close();
        }
        
        if (this.broadcastChannel) {
            this.broadcastChannel.close();
        }
        
        // Close database
        if (this.db) {
            this.db.close();
        }
        
        console.log('[CrossDeviceSync] Destroyed');
    }
}

// Global instance
window.crossDeviceSync = null;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    if (!window.crossDeviceSync) {
        window.crossDeviceSync = new CrossDeviceSync();
    }
});

// Export for module use
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CrossDeviceSync;
}
