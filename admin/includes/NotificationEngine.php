<?php
/**
 * Orlando International Resorts - Notification Engine
 * Real-time notification system with WebSocket support and event-driven architecture
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Notification Engine Class
 * Handles real-time notifications, event processing, and WebSocket integration
 */
class NotificationEngine {
    private $db;
    private $websocket_clients = [];
    private $event_listeners = [];
    private $notification_queue = [];
    private static $instance = null;
    
    // Notification types
    const TYPE_INFO = 'info';
    const TYPE_SUCCESS = 'success';
    const TYPE_WARNING = 'warning';
    const TYPE_ERROR = 'error';
    const TYPE_CRITICAL = 'critical';
    
    // Notification categories
    const CATEGORY_BOOKING = 'booking';
    const CATEGORY_PAYMENT = 'payment';
    const CATEGORY_MAINTENANCE = 'maintenance';
    const CATEGORY_HOUSEKEEPING = 'housekeeping';
    const CATEGORY_SYSTEM = 'system';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_INVENTORY = 'inventory';
    const CATEGORY_STAFF = 'staff';
    
    // Priority levels
    const PRIORITY_LOW = 1;
    const PRIORITY_NORMAL = 2;
    const PRIORITY_HIGH = 3;
    const PRIORITY_URGENT = 4;
    const PRIORITY_CRITICAL = 5;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->initializeNotificationSystem();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($database_connection = null) {
        if (self::$instance === null) {
            if ($database_connection === null) {
                throw new Exception('Database connection required for first initialization');
            }
            self::$instance = new self($database_connection);
        }
        return self::$instance;
    }
    
    /**
     * Initialize notification system
     */
    private function initializeNotificationSystem() {
        // Create notifications table if it doesn't exist
        $this->createNotificationTables();
        
        // Set up default event listeners
        $this->registerDefaultEventListeners();
        
        // Initialize WebSocket server configuration
        $this->initializeWebSocketConfig();
    }
    
    /**
     * Create notification tables
     */
    private function createNotificationTables() {
        try {
            // Create notifications table
            $notifications_sql = "CREATE TABLE IF NOT EXISTS `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `role` varchar(50) DEFAULT NULL,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `type` enum('info','success','warning','error','critical') DEFAULT 'info',
                `category` varchar(50) DEFAULT NULL,
                `priority` tinyint(1) DEFAULT 2,
                `data` json DEFAULT NULL,
                `is_read` tinyint(1) DEFAULT 0,
                `is_broadcast` tinyint(1) DEFAULT 0,
                `expires_at` datetime DEFAULT NULL,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `read_at` timestamp NULL DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_user_notifications` (`user_id`, `is_read`),
                KEY `idx_role_notifications` (`role`, `is_read`),
                KEY `idx_category_priority` (`category`, `priority`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_expires_at` (`expires_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($notifications_sql);
            
            // Create notification_preferences table
            $preferences_sql = "CREATE TABLE IF NOT EXISTS `notification_preferences` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `category` varchar(50) NOT NULL,
                `email_enabled` tinyint(1) DEFAULT 1,
                `push_enabled` tinyint(1) DEFAULT 1,
                `websocket_enabled` tinyint(1) DEFAULT 1,
                `min_priority` tinyint(1) DEFAULT 2,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `unique_user_category` (`user_id`, `category`),
                KEY `idx_user_preferences` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($preferences_sql);
            
            // Create notification_events table for event tracking
            $events_sql = "CREATE TABLE IF NOT EXISTS `notification_events` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(100) NOT NULL,
                `event_data` json DEFAULT NULL,
                `triggered_by` int(11) DEFAULT NULL,
                `notification_id` int(11) DEFAULT NULL,
                `processed` tinyint(1) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_event_type` (`event_type`),
                KEY `idx_processed` (`processed`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($events_sql);
            
        } catch (Exception $e) {
            error_log("Notification table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Register default event listeners
     */
    private function registerDefaultEventListeners() {
        // Booking events
        $this->addEventListener('booking.created', [$this, 'handleBookingCreated']);
        $this->addEventListener('booking.confirmed', [$this, 'handleBookingConfirmed']);
        $this->addEventListener('booking.cancelled', [$this, 'handleBookingCancelled']);
        $this->addEventListener('booking.checkin', [$this, 'handleCheckIn']);
        $this->addEventListener('booking.checkout', [$this, 'handleCheckOut']);
        
        // Payment events
        $this->addEventListener('payment.received', [$this, 'handlePaymentReceived']);
        $this->addEventListener('payment.failed', [$this, 'handlePaymentFailed']);
        $this->addEventListener('payment.refund', [$this, 'handlePaymentRefund']);
        
        // Maintenance events
        $this->addEventListener('maintenance.request_created', [$this, 'handleMaintenanceRequest']);
        $this->addEventListener('maintenance.urgent', [$this, 'handleUrgentMaintenance']);
        $this->addEventListener('maintenance.completed', [$this, 'handleMaintenanceCompleted']);
        
        // Housekeeping events
        $this->addEventListener('housekeeping.task_assigned', [$this, 'handleHousekeepingTask']);
        $this->addEventListener('housekeeping.task_completed', [$this, 'handleTaskCompleted']);
        $this->addEventListener('housekeeping.inspection_failed', [$this, 'handleInspectionFailed']);
        
        // System events
        $this->addEventListener('system.backup_completed', [$this, 'handleSystemBackup']);
        $this->addEventListener('system.error', [$this, 'handleSystemError']);
        $this->addEventListener('system.security_alert', [$this, 'handleSecurityAlert']);
        
        // Inventory events
        $this->addEventListener('inventory.low_stock', [$this, 'handleLowStock']);
        $this->addEventListener('inventory.out_of_stock', [$this, 'handleOutOfStock']);
        
        // Staff events
        $this->addEventListener('staff.shift_reminder', [$this, 'handleShiftReminder']);
        $this->addEventListener('staff.overtime_alert', [$this, 'handleOvertimeAlert']);
    }
    
    /**
     * Initialize WebSocket configuration
     */
    private function initializeWebSocketConfig() {
        // WebSocket server configuration will be set up
        // This prepares the system for WebSocket integration
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($data) {
        $required_fields = ['title', 'message'];
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                throw new Exception("Required field '$field' is missing");
            }
        }
        
        $notification = [
            'user_id' => $data['user_id'] ?? null,
            'role' => $data['role'] ?? null,
            'title' => $data['title'],
            'message' => $data['message'],
            'type' => $data['type'] ?? self::TYPE_INFO,
            'category' => $data['category'] ?? null,
            'priority' => $data['priority'] ?? self::PRIORITY_NORMAL,
            'data' => isset($data['data']) ? json_encode($data['data']) : null,
            'is_broadcast' => $data['is_broadcast'] ?? 0,
            'expires_at' => $data['expires_at'] ?? null
        ];
        
        try {
            $query = "INSERT INTO notifications 
                     (user_id, role, title, message, type, category, priority, data, is_broadcast, expires_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isssssisis", 
                $notification['user_id'],
                $notification['role'],
                $notification['title'],
                $notification['message'],
                $notification['type'],
                $notification['category'],
                $notification['priority'],
                $notification['data'],
                $notification['is_broadcast'],
                $notification['expires_at']
            );
            
            $stmt->execute();
            $notification_id = $this->db->insert_id;
            
            // Send real-time notification
            $this->sendRealTimeNotification($notification_id, $notification);
            
            return $notification_id;
            
        } catch (Exception $e) {
            error_log("Notification creation error: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Send real-time notification via WebSocket
     */
    private function sendRealTimeNotification($notification_id, $notification_data) {
        // Prepare WebSocket message
        $websocket_message = [
            'type' => 'notification',
            'id' => $notification_id,
            'data' => $notification_data,
            'timestamp' => time()
        ];
        
        // Add to notification queue for WebSocket processing
        $this->notification_queue[] = $websocket_message;
        
        // Process queue immediately if WebSocket is available
        $this->processNotificationQueue();
        
        // Also trigger browser notifications if supported
        $this->triggerBrowserNotification($notification_data);
    }
    
    /**
     * Process notification queue
     */
    private function processNotificationQueue() {
        if (empty($this->notification_queue)) {
            return;
        }
        
        foreach ($this->notification_queue as $index => $message) {
            try {
                // Send to appropriate users based on targeting
                $this->distributeWebSocketMessage($message);
                
                // Remove processed message
                unset($this->notification_queue[$index]);
                
            } catch (Exception $e) {
                error_log("WebSocket message processing error: " . $e->getMessage());
            }
        }
        
        // Reset queue
        $this->notification_queue = array_values($this->notification_queue);
    }
    
    /**
     * Distribute WebSocket message to appropriate users
     */
    private function distributeWebSocketMessage($message) {
        $notification = $message['data'];
        $target_users = [];
        
        if ($notification['user_id']) {
            // Specific user notification
            $target_users = [$notification['user_id']];
        } elseif ($notification['role']) {
            // Role-based notification
            $target_users = $this->getUsersByRole($notification['role']);
        } elseif ($notification['is_broadcast']) {
            // Broadcast to all connected users
            $target_users = $this->getAllActiveUsers();
        }
        
        // Send message to each target user
        foreach ($target_users as $user_id) {
            $this->sendWebSocketToUser($user_id, $message);
        }
    }
    
    /**
     * Send WebSocket message to specific user
     */
    private function sendWebSocketToUser($user_id, $message) {
        // This will be implemented with actual WebSocket server
        // For now, we'll store in a real-time queue table
        try {
            $queue_query = "INSERT INTO websocket_queue (user_id, message, created_at) 
                           VALUES (?, ?, NOW())";
            
            // Create websocket_queue table if it doesn't exist
            $this->createWebSocketQueueTable();
            
            $stmt = $this->db->prepare($queue_query);
            $message_json = json_encode($message);
            $stmt->bind_param("is", $user_id, $message_json);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("WebSocket queue error: " . $e->getMessage());
        }
    }
    
    /**
     * Create WebSocket queue table
     */
    private function createWebSocketQueueTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `websocket_queue` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `message` json NOT NULL,
                `processed` tinyint(1) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_processed` (`user_id`, `processed`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($sql);
            
        } catch (Exception $e) {
            // Table might already exist
        }
    }
    
    /**
     * Trigger browser notification
     */
    private function triggerBrowserNotification($notification_data) {
        // Store for client-side pickup via polling or Server-Sent Events
        try {
            $browser_notification = [
                'title' => $notification_data['title'],
                'body' => $notification_data['message'],
                'icon' => '/Hotel/images/logo-full.png',
                'tag' => $notification_data['category'] ?? 'general',
                'data' => $notification_data['data'] ?? null
            ];
            
            $query = "INSERT INTO browser_notifications (user_id, notification_data, created_at) 
                     VALUES (?, ?, NOW())";
            
            // Create browser_notifications table if needed
            $this->createBrowserNotificationTable();
            
            $stmt = $this->db->prepare($query);
            $notification_json = json_encode($browser_notification);
            $stmt->bind_param("is", $notification_data['user_id'], $notification_json);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Browser notification error: " . $e->getMessage());
        }
    }
    
    /**
     * Create browser notification table
     */
    private function createBrowserNotificationTable() {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `browser_notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) DEFAULT NULL,
                `notification_data` json NOT NULL,
                `shown` tinyint(1) DEFAULT 0,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_user_shown` (`user_id`, `shown`),
                KEY `idx_created_at` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($sql);
            
        } catch (Exception $e) {
            // Table might already exist
        }
    }
    
    /**
     * Add event listener
     */
    public function addEventListener($event_type, $callback) {
        if (!isset($this->event_listeners[$event_type])) {
            $this->event_listeners[$event_type] = [];
        }
        
        $this->event_listeners[$event_type][] = $callback;
    }
    
    /**
     * Trigger event
     */
    public function triggerEvent($event_type, $event_data = []) {
        try {
            // Log event
            $this->logEvent($event_type, $event_data);
            
            // Process event listeners
            if (isset($this->event_listeners[$event_type])) {
                foreach ($this->event_listeners[$event_type] as $callback) {
                    if (is_callable($callback)) {
                        call_user_func($callback, $event_data);
                    }
                }
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log("Event trigger error for $event_type: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log event
     */
    private function logEvent($event_type, $event_data) {
        try {
            $query = "INSERT INTO notification_events 
                     (event_type, event_data, triggered_by, created_at) 
                     VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $event_json = json_encode($event_data);
            $triggered_by = $_SESSION['user_id'] ?? null;
            
            $stmt->bind_param("isi", $event_type, $event_json, $triggered_by);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Event logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get notifications for user
     */
    public function getUserNotifications($user_id, $options = []) {
        $limit = $options['limit'] ?? 50;
        $offset = $options['offset'] ?? 0;
        $unread_only = $options['unread_only'] ?? false;
        $category = $options['category'] ?? null;
        
        try {
            $where_conditions = ["(user_id = ? OR is_broadcast = 1)"];
            $params = [$user_id];
            $param_types = "i";
            
            if ($unread_only) {
                $where_conditions[] = "is_read = 0";
            }
            
            if ($category) {
                $where_conditions[] = "category = ?";
                $params[] = $category;
                $param_types .= "s";
            }
            
            // Add expiration check
            $where_conditions[] = "(expires_at IS NULL OR expires_at > NOW())";
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $query = "SELECT * FROM notifications 
                     WHERE $where_clause 
                     ORDER BY priority DESC, created_at DESC 
                     LIMIT ? OFFSET ?";
            
            $params[] = $limit;
            $params[] = $offset;
            $param_types .= "ii";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param($param_types, ...$params);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $notifications = [];
            
            while ($row = $result->fetch_assoc()) {
                if ($row['data']) {
                    $row['data'] = json_decode($row['data'], true);
                }
                $notifications[] = $row;
            }
            
            return $notifications;
            
        } catch (Exception $e) {
            error_log("Get user notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            $query = "UPDATE notifications 
                     SET is_read = 1, read_at = NOW() 
                     WHERE id = ? AND (user_id = ? OR is_broadcast = 1)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ii", $notification_id, $user_id);
            $stmt->execute();
            
            return $stmt->affected_rows > 0;
            
        } catch (Exception $e) {
            error_log("Mark notification as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        try {
            $query = "UPDATE notifications 
                     SET is_read = 1, read_at = NOW() 
                     WHERE (user_id = ? OR is_broadcast = 1) AND is_read = 0";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            return $stmt->affected_rows;
            
        } catch (Exception $e) {
            error_log("Mark all notifications as read error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM notifications 
                     WHERE (user_id = ? OR is_broadcast = 1) 
                     AND is_read = 0 
                     AND (expires_at IS NULL OR expires_at > NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            return intval($data['count']);
            
        } catch (Exception $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Clean up expired notifications
     */
    public function cleanupExpiredNotifications() {
        try {
            $query = "DELETE FROM notifications 
                     WHERE expires_at IS NOT NULL 
                     AND expires_at < NOW()";
            
            $result = $this->db->query($query);
            $deleted_count = $this->db->affected_rows;
            
            if ($deleted_count > 0) {
                error_log("Cleaned up $deleted_count expired notifications");
            }
            
            return $deleted_count;
            
        } catch (Exception $e) {
            error_log("Cleanup expired notifications error: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get users by role
     */
    private function getUsersByRole($role) {
        try {
            $query = "SELECT id FROM users WHERE role = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $role);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row['id'];
            }
            
            return $users;
            
        } catch (Exception $e) {
            error_log("Get users by role error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all active users
     */
    private function getAllActiveUsers() {
        try {
            $query = "SELECT id FROM users WHERE status = 'active'";
            $result = $this->db->query($query);
            $users = [];
            
            while ($row = $result->fetch_assoc()) {
                $users[] = $row['id'];
            }
            
            return $users;
            
        } catch (Exception $e) {
            error_log("Get all active users error: " . $e->getMessage());
            return [];
        }
    }
    
    // Event Handlers
    
    /**
     * Handle booking created event
     */
    public function handleBookingCreated($event_data) {
        $this->createNotification([
            'role' => 'operations_manager',
            'title' => 'New Booking Received',
            'message' => 'A new booking has been created for ' . ($event_data['guest_name'] ?? 'guest'),
            'type' => self::TYPE_INFO,
            'category' => self::CATEGORY_BOOKING,
            'priority' => self::PRIORITY_NORMAL,
            'data' => $event_data,
            'is_broadcast' => 1
        ]);
    }
    
    /**
     * Handle booking confirmed event
     */
    public function handleBookingConfirmed($event_data) {
        $this->createNotification([
            'user_id' => $event_data['user_id'] ?? null,
            'title' => 'Booking Confirmed',
            'message' => 'Booking #' . ($event_data['booking_id'] ?? 'N/A') . ' has been confirmed',
            'type' => self::TYPE_SUCCESS,
            'category' => self::CATEGORY_BOOKING,
            'priority' => self::PRIORITY_NORMAL,
            'data' => $event_data
        ]);
    }
    
    /**
     * Handle payment received event
     */
    public function handlePaymentReceived($event_data) {
        $this->createNotification([
            'role' => 'finance_manager',
            'title' => 'Payment Received',
            'message' => 'Payment of $' . ($event_data['amount'] ?? '0') . ' received',
            'type' => self::TYPE_SUCCESS,
            'category' => self::CATEGORY_PAYMENT,
            'priority' => self::PRIORITY_NORMAL,
            'data' => $event_data
        ]);
    }
    
    /**
     * Handle urgent maintenance event
     */
    public function handleUrgentMaintenance($event_data) {
        $this->createNotification([
            'role' => 'operations_manager',
            'title' => 'Urgent Maintenance Required',
            'message' => 'Urgent maintenance needed in ' . ($event_data['location'] ?? 'unknown location'),
            'type' => self::TYPE_CRITICAL,
            'category' => self::CATEGORY_MAINTENANCE,
            'priority' => self::PRIORITY_URGENT,
            'data' => $event_data,
            'is_broadcast' => 1
        ]);
    }
    
    /**
     * Handle low stock event
     */
    public function handleLowStock($event_data) {
        $this->createNotification([
            'role' => 'operations_manager',
            'title' => 'Low Stock Alert',
            'message' => ($event_data['item_name'] ?? 'Item') . ' is running low on stock',
            'type' => self::TYPE_WARNING,
            'category' => self::CATEGORY_INVENTORY,
            'priority' => self::PRIORITY_HIGH,
            'data' => $event_data
        ]);
    }
    
    /**
     * Handle security alert event
     */
    public function handleSecurityAlert($event_data) {
        $this->createNotification([
            'role' => 'it_admin',
            'title' => 'Security Alert',
            'message' => $event_data['message'] ?? 'Security event detected',
            'type' => self::TYPE_CRITICAL,
            'category' => self::CATEGORY_SECURITY,
            'priority' => self::PRIORITY_CRITICAL,
            'data' => $event_data,
            'is_broadcast' => 1
        ]);
    }
}

/**
 * Global notification functions
 */
function getNotificationEngine() {
    global $con;
    static $notification_engine = null;
    
    if ($notification_engine === null) {
        try {
            $notification_engine = NotificationEngine::getInstance($con);
        } catch (Exception $e) {
            error_log("Notification Engine Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $notification_engine;
}

function createNotification($data) {
    $ne = getNotificationEngine();
    return $ne ? $ne->createNotification($data) : false;
}

function triggerEvent($event_type, $event_data = []) {
    $ne = getNotificationEngine();
    return $ne ? $ne->triggerEvent($event_type, $event_data) : false;
}

function getUserNotifications($user_id, $options = []) {
    $ne = getNotificationEngine();
    return $ne ? $ne->getUserNotifications($user_id, $options) : [];
}

function getUnreadNotificationCount($user_id) {
    $ne = getNotificationEngine();
    return $ne ? $ne->getUnreadCount($user_id) : 0;
}

function markNotificationAsRead($notification_id, $user_id) {
    $ne = getNotificationEngine();
    return $ne ? $ne->markAsRead($notification_id, $user_id) : false;
}
?>
