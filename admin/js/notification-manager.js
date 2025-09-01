/**
 * Orlando International Resorts - Notification Manager
 * Client-side notification handling with WebSocket support
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class NotificationManager {
    constructor(options = {}) {
        this.options = {
            websocketUrl: options.websocketUrl || 'ws://localhost:8080',
            enableWebSocket: options.enableWebSocket !== false,
            enableBrowserNotifications: options.enableBrowserNotifications !== false,
            pollingInterval: options.pollingInterval || 30000, // 30 seconds fallback
            maxRetries: options.maxRetries || 5,
            retryDelay: options.retryDelay || 5000,
            debug: options.debug || false,
            ...options
        };
        
        this.websocket = null;
        this.isConnected = false;
        this.retryCount = 0;
        this.pollingTimer = null;
        this.unreadCount = 0;
        this.notifications = [];
        this.subscriptions = new Set();
        this.eventHandlers = new Map();
        
        this.init();
    }
    
    /**
     * Initialize notification manager
     */
    init() {
        this.log('Initializing Notification Manager...');
        
        // Request browser notification permission
        if (this.options.enableBrowserNotifications) {
            this.requestNotificationPermission();
        }
        
        // Setup WebSocket connection
        if (this.options.enableWebSocket) {
            this.connectWebSocket();
        } else {
            // Fallback to polling
            this.startPolling();
        }
        
        // Setup UI elements
        this.setupUI();
        
        // Load initial notifications
        this.loadNotifications();
        
        this.log('Notification Manager initialized');
    }
    
    /**
     * Setup WebSocket connection
     */
    connectWebSocket() {
        if (!window.WebSocket) {
            this.log('WebSocket not supported, falling back to polling');
            this.startPolling();
            return;
        }
        
        try {
            this.websocket = new WebSocket(this.options.websocketUrl);
            
            this.websocket.onopen = (event) => {
                this.log('WebSocket connected');
                this.isConnected = true;
                this.retryCount = 0;
                this.authenticate();
                this.showConnectionStatus(true);
            };
            
            this.websocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleWebSocketMessage(data);
                } catch (error) {
                    this.log('WebSocket message parse error:', error);
                }
            };
            
            this.websocket.onclose = (event) => {
                this.log('WebSocket disconnected');
                this.isConnected = false;
                this.showConnectionStatus(false);
                
                // Attempt to reconnect
                if (this.retryCount < this.options.maxRetries) {
                    this.retryCount++;
                    setTimeout(() => {
                        this.log(`Reconnecting... (attempt ${this.retryCount})`);
                        this.connectWebSocket();
                    }, this.options.retryDelay);
                } else {
                    this.log('Max retries reached, falling back to polling');
                    this.startPolling();
                }
            };
            
            this.websocket.onerror = (error) => {
                this.log('WebSocket error:', error);
                this.showConnectionStatus(false);
            };
            
        } catch (error) {
            this.log('WebSocket connection error:', error);
            this.startPolling();
        }
    }
    
    /**
     * Authenticate with WebSocket server
     */
    authenticate() {
        if (!this.isConnected) return;
        
        // Get session information from current page
        const sessionData = this.getSessionData();
        
        if (sessionData.user_id && sessionData.session_id) {
            const auth_message = {
                type: 'authenticate',
                user_id: sessionData.user_id,
                session_id: sessionData.session_id
            };
            
            this.sendWebSocketMessage(auth_message);
        }
    }
    
    /**
     * Handle WebSocket messages
     */
    handleWebSocketMessage(data) {
        this.log('WebSocket message received:', data);
        
        switch (data.type) {
            case 'connection':
                this.log('Connected to notification service');
                break;
                
            case 'authenticated':
                this.log('Authentication successful');
                this.subscribeToDefaultCategories();
                break;
                
            case 'notification':
                this.handleNewNotification(data.data);
                break;
                
            case 'notifications':
                this.handleNotificationsList(data);
                break;
                
            case 'pending_notifications':
                this.handlePendingNotifications(data);
                break;
                
            case 'unread_count_update':
                this.updateUnreadCount(data.unread_count);
                break;
                
            case 'subscribed':
                this.log('Subscribed to categories:', data.categories);
                break;
                
            case 'mark_read_response':
                this.handleMarkReadResponse(data);
                break;
                
            case 'pong':
                // Keep-alive response
                break;
                
            case 'error':
                this.log('WebSocket error:', data.message);
                break;
                
            default:
                this.log('Unknown message type:', data.type);
        }
    }
    
    /**
     * Subscribe to notification categories
     */
    subscribeToDefaultCategories() {
        const defaultCategories = [
            'booking', 'payment', 'maintenance', 'housekeeping', 
            'system', 'security', 'inventory', 'staff'
        ];
        
        this.subscribe(defaultCategories);
    }
    
    /**
     * Subscribe to notification categories
     */
    subscribe(categories) {
        if (!Array.isArray(categories)) {
            categories = [categories];
        }
        
        categories.forEach(category => this.subscriptions.add(category));
        
        if (this.isConnected) {
            const message = {
                type: 'subscribe',
                categories: categories
            };
            
            this.sendWebSocketMessage(message);
        }
    }
    
    /**
     * Unsubscribe from notification categories
     */
    unsubscribe(categories) {
        if (!Array.isArray(categories)) {
            categories = [categories];
        }
        
        categories.forEach(category => this.subscriptions.delete(category));
        
        if (this.isConnected) {
            const message = {
                type: 'unsubscribe',
                categories: categories
            };
            
            this.sendWebSocketMessage(message);
        }
    }
    
    /**
     * Send WebSocket message
     */
    sendWebSocketMessage(message) {
        if (this.websocket && this.websocket.readyState === WebSocket.OPEN) {
            this.websocket.send(JSON.stringify(message));
        }
    }
    
    /**
     * Handle new notification
     */
    handleNewNotification(notification) {
        this.log('New notification:', notification);
        
        // Add to notifications array
        this.notifications.unshift(notification);
        
        // Update unread count
        if (!notification.is_read) {
            this.unreadCount++;
            this.updateUnreadCount(this.unreadCount);
        }
        
        // Show browser notification
        if (this.options.enableBrowserNotifications) {
            this.showBrowserNotification(notification);
        }
        
        // Update UI
        this.updateNotificationUI();
        
        // Trigger custom event handlers
        this.triggerEvent('notification_received', notification);
        
        // Play notification sound
        this.playNotificationSound(notification);
    }
    
    /**
     * Handle notifications list
     */
    handleNotificationsList(data) {
        this.notifications = data.notifications || [];
        this.unreadCount = data.unread_count || 0;
        this.updateUnreadCount(this.unreadCount);
        this.updateNotificationUI();
    }
    
    /**
     * Handle pending notifications
     */
    handlePendingNotifications(data) {
        this.log(`Received ${data.count} pending notifications`);
        
        data.notifications.forEach(notification => {
            this.handleNewNotification(notification);
        });
    }
    
    /**
     * Update unread count
     */
    updateUnreadCount(count) {
        this.unreadCount = count;
        
        // Update badge
        const badge = document.querySelector('.notification-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline' : 'none';
        }
        
        // Update title
        if (count > 0) {
            document.title = `(${count}) Orlando International Resorts - Admin`;
        } else {
            document.title = 'Orlando International Resorts - Admin';
        }
        
        this.triggerEvent('unread_count_updated', count);
    }
    
    /**
     * Mark notification as read
     */
    markAsRead(notificationId) {
        // Update locally
        const notification = this.notifications.find(n => n.id == notificationId);
        if (notification && !notification.is_read) {
            notification.is_read = true;
            this.unreadCount = Math.max(0, this.unreadCount - 1);
            this.updateUnreadCount(this.unreadCount);
        }
        
        // Send to server
        if (this.isConnected) {
            const message = {
                type: 'mark_read',
                notification_id: notificationId
            };
            
            this.sendWebSocketMessage(message);
        } else {
            // Fallback to AJAX
            this.markAsReadAjax(notificationId);
        }
        
        this.updateNotificationUI();
    }
    
    /**
     * Mark notification as read via AJAX
     */
    markAsReadAjax(notificationId) {
        fetch('/admin/api/mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                notification_id: notificationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.log('Notification marked as read');
            }
        })
        .catch(error => {
            this.log('Error marking notification as read:', error);
        });
    }
    
    /**
     * Load notifications via AJAX
     */
    loadNotifications(options = {}) {
        const params = new URLSearchParams({
            limit: options.limit || 20,
            offset: options.offset || 0,
            unread_only: options.unread_only || false,
            category: options.category || ''
        });
        
        fetch(`/admin/api/get_notifications.php?${params}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.notifications = data.notifications || [];
                this.unreadCount = data.unread_count || 0;
                this.updateUnreadCount(this.unreadCount);
                this.updateNotificationUI();
            }
        })
        .catch(error => {
            this.log('Error loading notifications:', error);
        });
    }
    
    /**
     * Start polling fallback
     */
    startPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
        }
        
        this.pollingTimer = setInterval(() => {
            this.loadNotifications({ unread_only: true });
        }, this.options.pollingInterval);
        
        this.log('Started notification polling');
    }
    
    /**
     * Stop polling
     */
    stopPolling() {
        if (this.pollingTimer) {
            clearInterval(this.pollingTimer);
            this.pollingTimer = null;
        }
    }
    
    /**
     * Setup UI elements
     */
    setupUI() {
        // Create notification dropdown if it doesn't exist
        this.createNotificationDropdown();
        
        // Setup event listeners
        this.setupEventListeners();
    }
    
    /**
     * Create notification dropdown
     */
    createNotificationDropdown() {
        const container = document.querySelector('.notification-container');
        if (!container) return;
        
        const dropdown = document.createElement('div');
        dropdown.className = 'notification-dropdown';
        dropdown.style.display = 'none';
        
        dropdown.innerHTML = `
            <div class="notification-header">
                <h4>Notifications</h4>
                <button class="mark-all-read" onclick="notificationManager.markAllAsRead()">
                    Mark All Read
                </button>
            </div>
            <div class="notification-list">
                <div class="no-notifications">No new notifications</div>
            </div>
            <div class="notification-footer">
                <a href="/admin/notifications.php">View All Notifications</a>
            </div>
        `;
        
        container.appendChild(dropdown);
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Notification bell click
        const notificationBell = document.querySelector('.notification-bell');
        if (notificationBell) {
            notificationBell.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleNotificationDropdown();
            });
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const dropdown = document.querySelector('.notification-dropdown');
            const bell = document.querySelector('.notification-bell');
            
            if (dropdown && dropdown.style.display === 'block' && 
                !dropdown.contains(e.target) && !bell.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });
    }
    
    /**
     * Toggle notification dropdown
     */
    toggleNotificationDropdown() {
        const dropdown = document.querySelector('.notification-dropdown');
        if (!dropdown) return;
        
        if (dropdown.style.display === 'none' || !dropdown.style.display) {
            dropdown.style.display = 'block';
            this.loadNotifications({ limit: 10 });
        } else {
            dropdown.style.display = 'none';
        }
    }
    
    /**
     * Update notification UI
     */
    updateNotificationUI() {
        const listContainer = document.querySelector('.notification-list');
        if (!listContainer) return;
        
        if (this.notifications.length === 0) {
            listContainer.innerHTML = '<div class="no-notifications">No new notifications</div>';
            return;
        }
        
        const notificationsHTML = this.notifications.slice(0, 10).map(notification => 
            this.renderNotification(notification)
        ).join('');
        
        listContainer.innerHTML = notificationsHTML;
    }
    
    /**
     * Render single notification
     */
    renderNotification(notification) {
        const timeAgo = this.timeAgo(new Date(notification.created_at));
        const readClass = notification.is_read ? 'read' : 'unread';
        const typeClass = `notification-${notification.type}`;
        
        return `
            <div class="notification-item ${readClass} ${typeClass}" 
                 data-id="${notification.id}"
                 onclick="notificationManager.markAsRead(${notification.id})">
                <div class="notification-icon">
                    <i class="fas fa-${this.getNotificationIcon(notification.category)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${notification.title}</div>
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${timeAgo}</div>
                </div>
                ${!notification.is_read ? '<div class="unread-indicator"></div>' : ''}
            </div>
        `;
    }
    
    /**
     * Get notification icon based on category
     */
    getNotificationIcon(category) {
        const icons = {
            booking: 'calendar-plus',
            payment: 'credit-card',
            maintenance: 'tools',
            housekeeping: 'broom',
            system: 'server',
            security: 'shield-alt',
            inventory: 'boxes',
            staff: 'users'
        };
        
        return icons[category] || 'bell';
    }
    
    /**
     * Mark all notifications as read
     */
    markAllAsRead() {
        // Update locally
        this.notifications.forEach(notification => {
            notification.is_read = true;
        });
        
        this.unreadCount = 0;
        this.updateUnreadCount(0);
        this.updateNotificationUI();
        
        // Send to server
        fetch('/admin/api/mark_all_notifications_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.log('All notifications marked as read');
            }
        })
        .catch(error => {
            this.log('Error marking all notifications as read:', error);
        });
    }
    
    /**
     * Show browser notification
     */
    showBrowserNotification(notification) {
        if ('Notification' in window && Notification.permission === 'granted') {
            const browserNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/Hotel/images/logo-full.png',
                tag: notification.category || 'general',
                data: notification
            });
            
            browserNotification.onclick = () => {
                window.focus();
                this.markAsRead(notification.id);
                browserNotification.close();
            };
            
            // Auto-close after 5 seconds
            setTimeout(() => {
                browserNotification.close();
            }, 5000);
        }
    }
    
    /**
     * Request browser notification permission
     */
    requestNotificationPermission() {
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission().then(permission => {
                this.log('Notification permission:', permission);
            });
        }
    }
    
    /**
     * Play notification sound
     */
    playNotificationSound(notification) {
        // Only play sound for high priority notifications
        if (notification.priority >= 3) {
            try {
                const audio = new Audio('/admin/assets/sounds/notification.mp3');
                audio.volume = 0.3;
                audio.play().catch(error => {
                    // Ignore audio play errors (user interaction required)
                });
            } catch (error) {
                // Audio file not found or other error
            }
        }
    }
    
    /**
     * Show connection status
     */
    showConnectionStatus(connected) {
        const indicator = document.querySelector('.connection-indicator');
        if (indicator) {
            indicator.className = `connection-indicator ${connected ? 'connected' : 'disconnected'}`;
            indicator.title = connected ? 'Real-time notifications enabled' : 'Using fallback mode';
        }
    }
    
    /**
     * Get session data from page
     */
    getSessionData() {
        // Try to get from meta tags or global variables
        const userIdMeta = document.querySelector('meta[name="user-id"]');
        const sessionIdMeta = document.querySelector('meta[name="session-id"]');
        
        return {
            user_id: userIdMeta ? userIdMeta.content : (window.USER_ID || null),
            session_id: sessionIdMeta ? sessionIdMeta.content : (window.SESSION_ID || null)
        };
    }
    
    /**
     * Add event handler
     */
    on(event, handler) {
        if (!this.eventHandlers.has(event)) {
            this.eventHandlers.set(event, []);
        }
        this.eventHandlers.get(event).push(handler);
    }
    
    /**
     * Trigger event
     */
    triggerEvent(event, data) {
        if (this.eventHandlers.has(event)) {
            this.eventHandlers.get(event).forEach(handler => {
                try {
                    handler(data);
                } catch (error) {
                    this.log('Event handler error:', error);
                }
            });
        }
    }
    
    /**
     * Time ago helper
     */
    timeAgo(date) {
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffSecs < 60) return 'just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        return date.toLocaleDateString();
    }
    
    /**
     * Log messages (if debug enabled)
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[NotificationManager]', ...args);
        }
    }
    
    /**
     * Destroy notification manager
     */
    destroy() {
        // Close WebSocket
        if (this.websocket) {
            this.websocket.close();
        }
        
        // Stop polling
        this.stopPolling();
        
        // Clear event handlers
        this.eventHandlers.clear();
        
        this.log('Notification Manager destroyed');
    }
}

// Initialize notification manager when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on an admin page
    if (document.querySelector('body.admin-page') || window.location.pathname.includes('/admin/')) {
        window.notificationManager = new NotificationManager({
            debug: false,
            enableWebSocket: true,
            enableBrowserNotifications: true,
            websocketUrl: 'ws://localhost:8080'
        });
        
        // Add to global scope for easy access
        window.NotificationManager = NotificationManager;
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = NotificationManager;
}
