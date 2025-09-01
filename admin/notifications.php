<?php
$page_title = 'Notifications Center';
include '../includes/admin/header.php';
include 'includes/NotificationEngine.php';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $user_id = $_SESSION['user_id'] ?? 0;
    $ne = getNotificationEngine();
    
    switch ($_GET['action']) {
        case 'get_notifications':
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            $category = $_GET['category'] ?? null;
            $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
            
            $notifications = getUserNotifications($user_id, [
                'limit' => $limit,
                'offset' => $offset,
                'category' => $category,
                'unread_only' => $unread_only
            ]);
            
            echo json_encode([
                'success' => true,
                'notifications' => $notifications,
                'count' => getUnreadNotificationCount($user_id)
            ]);
            break;
            
        case 'mark_read':
            $notification_id = intval($_POST['notification_id'] ?? 0);
            $success = markNotificationAsRead($notification_id, $user_id);
            
            echo json_encode([
                'success' => $success,
                'count' => getUnreadNotificationCount($user_id)
            ]);
            break;
            
        case 'mark_all_read':
            $count = $ne ? $ne->markAllAsRead($user_id) : 0;
            
            echo json_encode([
                'success' => true,
                'marked_count' => $count,
                'count' => 0
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// Get user notifications for initial page load
$user_id = $_SESSION['user_id'] ?? 0;
$initial_notifications = getUserNotifications($user_id, ['limit' => 20]);
$unread_count = getUnreadNotificationCount($user_id);
?>

<style>
.notification-center {
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 15px;
    color: white;
    padding: 2rem;
    margin-bottom: 2rem;
}

.notification-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}

.stat-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 10px;
    padding: 1.5rem;
    text-align: center;
}

.stat-number {
    font-size: 2rem;
    font-weight: bold;
    display: block;
}

.notification-filters {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.notification-item {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-left: 4px solid #e2e8f0;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
}

.notification-item.unread {
    border-left-color: #667eea;
    background: linear-gradient(135deg, rgba(102, 126, 234, 0.05), rgba(118, 75, 162, 0.05));
}

.notification-item.type-success {
    border-left-color: #10b981;
}

.notification-item.type-warning {
    border-left-color: #f59e0b;
}

.notification-item.type-error, .notification-item.type-critical {
    border-left-color: #ef4444;
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 0.5rem;
}

.notification-title {
    font-weight: 600;
    color: #1e293b;
    margin: 0;
}

.notification-time {
    font-size: 0.8rem;
    color: #64748b;
}

.notification-message {
    color: #475569;
    margin: 0.5rem 0;
    line-height: 1.5;
}

.notification-category {
    display: inline-block;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 500;
    text-transform: uppercase;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.priority-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 0.5rem;
}

.priority-1, .priority-2 { background: #10b981; }
.priority-3 { background: #f59e0b; }
.priority-4, .priority-5 { background: #ef4444; }

.empty-state {
    text-align: center;
    padding: 3rem;
    color: #64748b;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

@media (max-width: 768px) {
    .notification-center {
        padding: 1rem;
    }
    
    .notification-filters {
        padding: 1rem;
    }
    
    .notification-item {
        padding: 1rem;
    }
    
    .notification-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.5rem;
    }
}
</style>

<!-- Page Header -->
<div class="notification-center">
    <div class="row align-items-center">
        <div class="col-md-8">
            <h1 class="mb-0">
                <i class="fas fa-bell me-3"></i>
                Notifications Center
            </h1>
            <p class="mb-0 opacity-75">Stay updated with real-time system notifications</p>
        </div>
        <div class="col-md-4 text-md-end">
            <button class="btn btn-light btn-lg" onclick="markAllAsRead()" id="markAllBtn">
                <i class="fas fa-check-double me-2"></i>
                Mark All Read
            </button>
        </div>
    </div>
    
    <div class="notification-stats mt-4">
        <div class="stat-card">
            <span class="stat-number" id="unreadCount"><?php echo $unread_count; ?></span>
            <small>Unread Notifications</small>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="totalCount"><?php echo count($initial_notifications); ?></span>
            <small>Total Notifications</small>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="todayCount">0</span>
            <small>Today's Notifications</small>
        </div>
        <div class="stat-card">
            <span class="stat-number" id="priorityCount">0</span>
            <small>High Priority</small>
        </div>
    </div>
</div>

<!-- Notification Filters -->
<div class="notification-filters">
    <div class="row align-items-center">
        <div class="col-md-6">
            <h5 class="mb-3 mb-md-0">Filter Notifications</h5>
        </div>
        <div class="col-md-6">
            <div class="d-flex gap-2 flex-wrap">
                <button class="btn btn-outline-primary btn-sm active" data-filter="all" onclick="filterNotifications('all')">
                    All
                </button>
                <button class="btn btn-outline-primary btn-sm" data-filter="unread" onclick="filterNotifications('unread')">
                    Unread
                </button>
                <button class="btn btn-outline-success btn-sm" data-filter="booking" onclick="filterNotifications('booking')">
                    Bookings
                </button>
                <button class="btn btn-outline-warning btn-sm" data-filter="maintenance" onclick="filterNotifications('maintenance')">
                    Maintenance
                </button>
                <button class="btn btn-outline-info btn-sm" data-filter="payment" onclick="filterNotifications('payment')">
                    Payments
                </button>
                <button class="btn btn-outline-danger btn-sm" data-filter="system" onclick="filterNotifications('system')">
                    System
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Notifications List -->
<div class="notifications-container">
    <div id="notificationsList">
        <!-- Notifications will be loaded here -->
    </div>
    
    <div class="text-center mt-4">
        <button class="btn btn-outline-primary" id="loadMoreBtn" onclick="loadMoreNotifications()">
            <i class="fas fa-plus me-2"></i>
            Load More Notifications
        </button>
    </div>
</div>

<script>
class NotificationCenter {
    constructor() {
        this.currentFilter = 'all';
        this.currentOffset = 0;
        this.pageSize = 20;
        this.loading = false;
        this.init();
    }
    
    init() {
        this.loadNotifications();
        this.startPolling();
        
        // Auto-refresh every 30 seconds
        setInterval(() => {
            this.refreshNotifications();
        }, 30000);
    }
    
    async loadNotifications(append = false) {
        if (this.loading) return;
        this.loading = true;
        
        try {
            const params = new URLSearchParams({
                action: 'get_notifications',
                limit: this.pageSize,
                offset: append ? this.currentOffset : 0,
                ...(this.currentFilter !== 'all' && this.currentFilter !== 'unread' && { category: this.currentFilter }),
                ...(this.currentFilter === 'unread' && { unread_only: 'true' })
            });
            
            const response = await fetch(`notifications.php?${params}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderNotifications(data.notifications, append);
                this.updateStats(data);
                
                if (append) {
                    this.currentOffset += this.pageSize;
                } else {
                    this.currentOffset = this.pageSize;
                }
                
                // Hide load more button if we got less than pageSize
                const loadMoreBtn = document.getElementById('loadMoreBtn');
                if (data.notifications.length < this.pageSize) {
                    loadMoreBtn.style.display = 'none';
                } else {
                    loadMoreBtn.style.display = 'block';
                }
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        } finally {
            this.loading = false;
        }
    }
    
    renderNotifications(notifications, append = false) {
        const container = document.getElementById('notificationsList');
        
        if (!append) {
            container.innerHTML = '';
        }
        
        if (notifications.length === 0 && !append) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h4>No notifications found</h4>
                    <p>You're all caught up! Check back later for updates.</p>
                </div>
            `;
            return;
        }
        
        notifications.forEach(notification => {
            const notificationEl = this.createNotificationElement(notification);
            container.appendChild(notificationEl);
        });
    }
    
    createNotificationElement(notification) {
        const div = document.createElement('div');
        div.className = `notification-item ${!notification.is_read ? 'unread' : ''} type-${notification.type}`;
        div.dataset.notificationId = notification.id;
        
        const categoryColors = {
            'booking': '#10b981',
            'payment': '#3b82f6',
            'maintenance': '#f59e0b',
            'system': '#ef4444',
            'housekeeping': '#8b5cf6',
            'inventory': '#f97316'
        };
        
        const categoryColor = categoryColors[notification.category] || '#64748b';
        
        div.innerHTML = `
            <div class="notification-header">
                <h6 class="notification-title">
                    <span class="priority-indicator priority-${notification.priority}"></span>
                    ${notification.title}
                </h6>
                <span class="notification-time">${this.timeAgo(notification.created_at)}</span>
            </div>
            <p class="notification-message">${notification.message}</p>
            ${notification.category ? `<span class="notification-category" style="background: ${categoryColor}20; color: ${categoryColor};">${notification.category}</span>` : ''}
            <div class="notification-actions">
                ${!notification.is_read ? `<button class="btn btn-sm btn-primary" onclick="markAsRead(${notification.id})">Mark as Read</button>` : ''}
                ${notification.data && notification.data.action_url ? `<a href="${notification.data.action_url}" class="btn btn-sm btn-outline-primary">View Details</a>` : ''}
            </div>
        `;
        
        // Add click handler to mark as read
        if (!notification.is_read) {
            div.addEventListener('click', (e) => {
                if (e.target.tagName !== 'BUTTON' && e.target.tagName !== 'A') {
                    this.markAsRead(notification.id);
                }
            });
        }
        
        return div;
    }
    
    updateStats(data) {
        document.getElementById('unreadCount').textContent = data.count || 0;
        
        // Update global notification count in header
        const headerBadge = document.getElementById('notificationCount');
        if (headerBadge) {
            headerBadge.textContent = data.count || 0;
            if (data.count > 0) {
                headerBadge.classList.add('has-notifications');
            } else {
                headerBadge.classList.remove('has-notifications');
            }
        }
    }
    
    async markAsRead(notificationId) {
        try {
            const formData = new FormData();
            formData.append('notification_id', notificationId);
            
            const response = await fetch('notifications.php?action=mark_read', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update UI
                const notificationEl = document.querySelector(`[data-notification-id="${notificationId}"]`);
                if (notificationEl) {
                    notificationEl.classList.remove('unread');
                    const actionsEl = notificationEl.querySelector('.notification-actions');
                    const markReadBtn = actionsEl.querySelector('button');
                    if (markReadBtn && markReadBtn.textContent === 'Mark as Read') {
                        markReadBtn.remove();
                    }
                }
                
                this.updateStats(data);
            }
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('notifications.php?action=mark_all_read', {
                method: 'POST'
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Refresh the entire list
                this.refreshNotifications();
            }
        } catch (error) {
            console.error('Error marking all notifications as read:', error);
        }
    }
    
    filterNotifications(filter) {
        this.currentFilter = filter;
        this.currentOffset = 0;
        
        // Update filter button states
        document.querySelectorAll('[data-filter]').forEach(btn => {
            btn.classList.remove('active');
        });
        document.querySelector(`[data-filter="${filter}"]`).classList.add('active');
        
        this.loadNotifications();
    }
    
    loadMoreNotifications() {
        this.loadNotifications(true);
    }
    
    refreshNotifications() {
        this.currentOffset = 0;
        this.loadNotifications();
    }
    
    startPolling() {
        // Poll for new notifications every 10 seconds
        setInterval(async () => {
            try {
                const response = await fetch('notifications.php?action=get_notifications&limit=1&offset=0');
                const data = await response.json();
                
                if (data.success) {
                    this.updateStats(data);
                }
            } catch (error) {
                console.error('Polling error:', error);
            }
        }, 10000);
    }
    
    timeAgo(dateString) {
        const now = new Date();
        const date = new Date(dateString);
        const diff = now - date;
        
        const seconds = Math.floor(diff / 1000);
        const minutes = Math.floor(seconds / 60);
        const hours = Math.floor(minutes / 60);
        const days = Math.floor(hours / 24);
        
        if (seconds < 60) return 'just now';
        if (minutes < 60) return `${minutes}m ago`;
        if (hours < 24) return `${hours}h ago`;
        if (days < 7) return `${days}d ago`;
        
        return date.toLocaleDateString();
    }
}

// Global functions
let notificationCenter;

function markAsRead(notificationId) {
    if (notificationCenter) {
        notificationCenter.markAsRead(notificationId);
    }
}

function markAllAsRead() {
    if (notificationCenter) {
        notificationCenter.markAllAsRead();
    }
}

function filterNotifications(filter) {
    if (notificationCenter) {
        notificationCenter.filterNotifications(filter);
    }
}

function loadMoreNotifications() {
    if (notificationCenter) {
        notificationCenter.loadMoreNotifications();
    }
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    notificationCenter = new NotificationCenter();
});
</script>

<?php include '../includes/admin/footer.php'; ?>
