<?php
// Real-time Order Notifications Widget
// This widget provides real-time notifications for new orders and status updates
?>

<style>
.notification-widget {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1050;
    width: 350px;
    max-height: 80vh;
    overflow-y: auto;
}

.notification-bell {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1060;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 50%;
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
    box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    cursor: pointer;
    transition: all 0.3s ease;
}

.notification-bell:hover {
    transform: scale(1.1);
    background: #c82333;
}

.notification-bell.has-notifications {
    animation: pulse 2s infinite;
}

.notification-bell .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #ffc107;
    color: #212529;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    font-weight: 700;
}

.notifications-panel {
    background: white;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.15);
    overflow: hidden;
    margin-top: 80px;
    display: none;
}

.notifications-panel.show {
    display: block;
    animation: slideDown 0.3s ease-out;
}

.notifications-header {
    background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notifications-body {
    max-height: 400px;
    overflow-y: auto;
}

.notification-item {
    padding: 15px 20px;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background-color 0.3s ease;
    position: relative;
}

.notification-item:hover {
    background-color: #f8f9fa;
}

.notification-item.unread {
    background-color: #fff3cd;
    border-left: 4px solid #ffc107;
}

.notification-item.urgent {
    background-color: #f8d7da;
    border-left: 4px solid #dc3545;
}

.notification-content {
    display: flex;
    align-items: flex-start;
    gap: 12px;
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    color: white;
    flex-shrink: 0;
}

.notification-icon.new-order {
    background: #28a745;
}

.notification-icon.status-change {
    background: #007bff;
}

.notification-icon.urgent {
    background: #dc3545;
}

.notification-text {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 3px;
    font-size: 0.9rem;
}

.notification-message {
    color: #6c757d;
    font-size: 0.85rem;
    line-height: 1.3;
    margin-bottom: 5px;
}

.notification-time {
    color: #adb5bd;
    font-size: 0.75rem;
}

.notification-actions {
    display: flex;
    gap: 8px;
    margin-top: 8px;
}

.notification-actions .btn {
    padding: 4px 12px;
    font-size: 0.75rem;
    border-radius: 15px;
}

.no-notifications {
    padding: 40px 20px;
    text-align: center;
    color: #6c757d;
}

.sound-control {
    position: fixed;
    top: 90px;
    right: 20px;
    z-index: 1055;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    font-size: 0.8rem;
    cursor: pointer;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

@keyframes slideDown {
    from { transform: translateY(-20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

.notification-bell.shake {
    animation: shake 0.5s ease-in-out;
}
</style>

<!-- Notification Bell -->
<button class="notification-bell" id="notificationBell" onclick="toggleNotifications()">
    <i class="fa fa-bell"></i>
    <span class="badge" id="notificationCount" style="display: none;">0</span>
</button>

<!-- Sound Control -->
<div class="sound-control" id="soundControl" onclick="toggleSound()" title="Toggle notification sounds">
    <i class="fa fa-volume-up" id="soundIcon"></i>
</div>

<!-- Notifications Panel -->
<div class="notification-widget">
    <div class="notifications-panel" id="notificationsPanel">
        <div class="notifications-header">
            <span>
                <i class="fa fa-bell"></i> Order Notifications
            </span>
            <div>
                <button class="btn btn-sm btn-outline-light" onclick="markAllAsRead()">
                    <i class="fa fa-check"></i> Mark All Read
                </button>
                <button class="btn btn-sm btn-outline-light" onclick="toggleNotifications()">
                    <i class="fa fa-times"></i>
                </button>
            </div>
        </div>
        <div class="notifications-body" id="notificationsBody">
            <!-- Notifications will be loaded here -->
        </div>
    </div>
</div>

<script>
class OrderNotifications {
    constructor() {
        this.isVisible = false;
        this.soundEnabled = localStorage.getItem('notification_sound') !== 'false';
        this.notifications = [];
        this.lastCheck = Date.now();
        this.checkInterval = 15000; // Check every 15 seconds
        
        this.init();
    }
    
    init() {
        this.updateSoundIcon();
        this.startPolling();
        this.checkNewOrders();
        this.checkReadyOrders();
        
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
    
    startPolling() {
        setInterval(() => {
            this.checkNewOrders();
            this.checkReadyOrders();
            this.updateOrderStats();
        }, this.checkInterval);
    }
    
    async checkNewOrders() {
        try {
            const response = await fetch('api/notifications.php?action=get_new_orders');
            const data = await response.json();
            
            if (data.success && data.new_orders.length > 0) {
                data.new_orders.forEach(order => {
                    this.addNotification({
                        id: `new_order_${order.id}`,
                        type: 'new-order',
                        title: 'New Order Received',
                        message: `Order #${order.order_number} from ${order.guest_name}`,
                        data: order,
                        urgent: true,
                        timestamp: new Date(order.ordered_time)
                    });
                });
            }
        } catch (error) {
            console.error('Error checking new orders:', error);
        }
    }
    
    async checkReadyOrders() {
        try {
            const response = await fetch('api/notifications.php?action=get_ready_orders');
            const data = await response.json();
            
            if (data.success && data.ready_orders.length > 0) {
                data.ready_orders.forEach(order => {
                    // Only notify if order has been ready for more than 5 minutes
                    const readyTime = new Date(order.ready_time);
                    const minutesReady = (Date.now() - readyTime.getTime()) / (1000 * 60);
                    
                    if (minutesReady > 5) {
                        this.addNotification({
                            id: `ready_order_${order.id}`,
                            type: 'urgent',
                            title: 'Order Waiting for Delivery',
                            message: `Order #${order.order_number} has been ready for ${Math.floor(minutesReady)} minutes`,
                            data: order,
                            urgent: true,
                            timestamp: readyTime
                        });
                    }
                });
            }
        } catch (error) {
            console.error('Error checking ready orders:', error);
        }
    }
    
    async updateOrderStats() {
        try {
            const response = await fetch('api/notifications.php?action=get_order_stats');
            const data = await response.json();
            
            if (data.success) {
                // Update dashboard stats if elements exist
                if (typeof updateDashboardStats === 'function') {
                    updateDashboardStats(data.stats);
                }
            }
        } catch (error) {
            console.error('Error updating order stats:', error);
        }
    }
    
    addNotification(notification) {
        // Check if notification already exists
        const exists = this.notifications.find(n => n.id === notification.id);
        if (exists) return;
        
        // Add to notifications array
        this.notifications.unshift(notification);
        
        // Limit to 50 notifications
        if (this.notifications.length > 50) {
            this.notifications = this.notifications.slice(0, 50);
        }
        
        // Update UI
        this.updateNotificationBell();
        this.renderNotifications();
        
        // Show browser notification
        this.showBrowserNotification(notification);
        
        // Play sound
        if (this.soundEnabled) {
            this.playNotificationSound();
        }
        
        // Animate bell
        this.animateBell();
    }
    
    showBrowserNotification(notification) {
        if (Notification.permission === 'granted' && document.hidden) {
            const browserNotification = new Notification(notification.title, {
                body: notification.message,
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: notification.id
            });
            
            browserNotification.onclick = () => {
                window.focus();
                this.handleNotificationClick(notification);
                browserNotification.close();
            };
            
            setTimeout(() => browserNotification.close(), 5000);
        }
    }
    
    playNotificationSound() {
        // Create a subtle notification sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.frequency.setValueAtTime(600, audioContext.currentTime + 0.1);
        
        gainNode.gain.setValueAtTime(0, audioContext.currentTime);
        gainNode.gain.linearRampToValueAtTime(0.1, audioContext.currentTime + 0.01);
        gainNode.gain.linearRampToValueAtTime(0, audioContext.currentTime + 0.2);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.2);
    }
    
    animateBell() {
        const bell = document.getElementById('notificationBell');
        bell.classList.add('shake');
        setTimeout(() => bell.classList.remove('shake'), 500);
    }
    
    updateNotificationBell() {
        const unreadCount = this.notifications.filter(n => !n.read).length;
        const badge = document.getElementById('notificationCount');
        const bell = document.getElementById('notificationBell');
        
        if (unreadCount > 0) {
            badge.textContent = unreadCount > 99 ? '99+' : unreadCount;
            badge.style.display = 'flex';
            bell.classList.add('has-notifications');
        } else {
            badge.style.display = 'none';
            bell.classList.remove('has-notifications');
        }
    }
    
    renderNotifications() {
        const container = document.getElementById('notificationsBody');
        
        if (this.notifications.length === 0) {
            container.innerHTML = `
                <div class="no-notifications">
                    <i class="fa fa-bell-o" style="font-size: 2rem; margin-bottom: 10px; color: #dee2e6;"></i>
                    <div>No notifications</div>
                </div>
            `;
            return;
        }
        
        const html = this.notifications.map(notification => `
            <div class="notification-item ${notification.read ? '' : 'unread'} ${notification.urgent ? 'urgent' : ''}" 
                 onclick="orderNotifications.handleNotificationClick('${notification.id}')">
                <div class="notification-content">
                    <div class="notification-icon ${notification.type}">
                        <i class="fa ${this.getNotificationIcon(notification.type)}"></i>
                    </div>
                    <div class="notification-text">
                        <div class="notification-title">${notification.title}</div>
                        <div class="notification-message">${notification.message}</div>
                        <div class="notification-time">${this.formatTime(notification.timestamp)}</div>
                        ${this.renderNotificationActions(notification)}
                    </div>
                </div>
            </div>
        `).join('');
        
        container.innerHTML = html;
    }
    
    getNotificationIcon(type) {
        const icons = {
            'new-order': 'fa-plus-circle',
            'status-change': 'fa-exchange',
            'urgent': 'fa-exclamation-triangle'
        };
        return icons[type] || 'fa-bell';
    }
    
    renderNotificationActions(notification) {
        if (notification.type === 'new-order' && notification.data) {
            return `
                <div class="notification-actions">
                    <button class="btn btn-success btn-sm" onclick="event.stopPropagation(); orderNotifications.confirmOrder(${notification.data.id})">
                        <i class="fa fa-check"></i> Confirm
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); orderNotifications.viewOrder(${notification.data.id})">
                        <i class="fa fa-eye"></i> View
                    </button>
                </div>
            `;
        }
        
        if (notification.type === 'urgent' && notification.data) {
            return `
                <div class="notification-actions">
                    <button class="btn btn-info btn-sm" onclick="event.stopPropagation(); orderNotifications.markAsServed(${notification.data.id})">
                        <i class="fa fa-check"></i> Mark Served
                    </button>
                    <button class="btn btn-warning btn-sm" onclick="event.stopPropagation(); orderNotifications.callCustomer('${notification.data.guest_phone}')">
                        <i class="fa fa-phone"></i> Call
                    </button>
                </div>
            `;
        }
        
        return '';
    }
    
    formatTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diffMinutes = Math.floor((now - time) / (1000 * 60));
        
        if (diffMinutes < 1) return 'Just now';
        if (diffMinutes < 60) return `${diffMinutes}m ago`;
        if (diffMinutes < 1440) return `${Math.floor(diffMinutes / 60)}h ago`;
        return time.toLocaleDateString();
    }
    
    handleNotificationClick(notificationId) {
        const notification = this.notifications.find(n => n.id === notificationId);
        if (!notification) return;
        
        // Mark as read
        notification.read = true;
        this.updateNotificationBell();
        this.renderNotifications();
        
        // Handle different notification types
        if (notification.data && notification.data.id) {
            this.viewOrder(notification.data.id);
        }
    }
    
    async confirmOrder(orderId) {
        try {
            const response = await fetch('orders_enhanced.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&action=update_order_status&order_id=${orderId}&status=confirmed`
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Order confirmed successfully', 'success');
                this.removeNotificationsForOrder(orderId);
                
                // Refresh orders if function exists
                if (typeof loadOrders === 'function') {
                    loadOrders();
                }
            }
        } catch (error) {
            console.error('Error confirming order:', error);
        }
    }
    
    async markAsServed(orderId) {
        try {
            const response = await fetch('orders_enhanced.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `ajax=1&action=update_order_status&order_id=${orderId}&status=served`
            });
            
            const data = await response.json();
            if (data.success) {
                this.showToast('Order marked as served', 'success');
                this.removeNotificationsForOrder(orderId);
                
                // Refresh orders if function exists
                if (typeof loadOrders === 'function') {
                    loadOrders();
                }
            }
        } catch (error) {
            console.error('Error updating order:', error);
        }
    }
    
    viewOrder(orderId) {
        // Try to view order details if function exists
        if (typeof viewOrderDetails === 'function') {
            viewOrderDetails(orderId);
        } else {
            // Fallback: navigate to orders page
            window.location.href = `orders_enhanced.php#order-${orderId}`;
        }
    }
    
    callCustomer(phoneNumber) {
        // Open phone dialer
        window.open(`tel:${phoneNumber}`);
    }
    
    removeNotificationsForOrder(orderId) {
        this.notifications = this.notifications.filter(n => 
            !n.data || n.data.id !== orderId
        );
        this.updateNotificationBell();
        this.renderNotifications();
    }
    
    markAllAsRead() {
        this.notifications.forEach(n => n.read = true);
        this.updateNotificationBell();
        this.renderNotifications();
    }
    
    toggleSound() {
        this.soundEnabled = !this.soundEnabled;
        localStorage.setItem('notification_sound', this.soundEnabled.toString());
        this.updateSoundIcon();
        
        if (this.soundEnabled) {
            this.playNotificationSound();
        }
    }
    
    updateSoundIcon() {
        const icon = document.getElementById('soundIcon');
        icon.className = this.soundEnabled ? 'fa fa-volume-up' : 'fa fa-volume-off';
    }
    
    toggle() {
        this.isVisible = !this.isVisible;
        const panel = document.getElementById('notificationsPanel');
        
        if (this.isVisible) {
            panel.classList.add('show');
        } else {
            panel.classList.remove('show');
        }
    }
    
    showToast(message, type = 'info') {
        // Create and show a toast notification
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} alert-dismissible fade show`;
        toast.style.cssText = 'position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 8px;';
        toast.innerHTML = `
            ${message}
            <button type="button" class="close" onclick="this.parentElement.remove()">
                <span>&times;</span>
            </button>
        `;
        
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 4000);
    }
}

// Initialize notifications system
const orderNotifications = new OrderNotifications();

// Global functions for HTML onclick events
function toggleNotifications() {
    orderNotifications.toggle();
}

function toggleSound() {
    orderNotifications.toggleSound();
}

function markAllAsRead() {
    orderNotifications.markAllAsRead();
}

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, check for updates
        orderNotifications.checkNewOrders();
        orderNotifications.checkReadyOrders();
    }
});
</script>
