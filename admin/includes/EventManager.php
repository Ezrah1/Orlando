<?php
/**
 * Orlando International Resorts - Event Manager
 * Event-driven architecture for system-wide event handling
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
 * Event Manager Class
 * Handles system events, triggers, and event-driven workflows
 */
class EventManager {
    private $db;
    private $notification_engine;
    private $event_handlers = [];
    private $event_queue = [];
    private static $instance = null;
    
    // System event types
    const EVENT_BOOKING_CREATED = 'booking.created';
    const EVENT_BOOKING_CONFIRMED = 'booking.confirmed';
    const EVENT_BOOKING_CANCELLED = 'booking.cancelled';
    const EVENT_BOOKING_CHECKIN = 'booking.checkin';
    const EVENT_BOOKING_CHECKOUT = 'booking.checkout';
    
    const EVENT_PAYMENT_RECEIVED = 'payment.received';
    const EVENT_PAYMENT_FAILED = 'payment.failed';
    const EVENT_PAYMENT_REFUND = 'payment.refund';
    
    const EVENT_ROOM_STATUS_CHANGED = 'room.status_changed';
    const EVENT_ROOM_ASSIGNED = 'room.assigned';
    const EVENT_ROOM_MAINTENANCE = 'room.maintenance_required';
    
    const EVENT_MAINTENANCE_REQUEST = 'maintenance.request_created';
    const EVENT_MAINTENANCE_URGENT = 'maintenance.urgent';
    const EVENT_MAINTENANCE_COMPLETED = 'maintenance.completed';
    const EVENT_MAINTENANCE_OVERDUE = 'maintenance.overdue';
    
    const EVENT_HOUSEKEEPING_TASK_ASSIGNED = 'housekeeping.task_assigned';
    const EVENT_HOUSEKEEPING_TASK_COMPLETED = 'housekeeping.task_completed';
    const EVENT_HOUSEKEEPING_INSPECTION_FAILED = 'housekeeping.inspection_failed';
    
    const EVENT_INVENTORY_LOW_STOCK = 'inventory.low_stock';
    const EVENT_INVENTORY_OUT_OF_STOCK = 'inventory.out_of_stock';
    const EVENT_INVENTORY_REORDER = 'inventory.reorder_triggered';
    
    const EVENT_STAFF_SHIFT_START = 'staff.shift_start';
    const EVENT_STAFF_SHIFT_END = 'staff.shift_end';
    const EVENT_STAFF_OVERTIME = 'staff.overtime_alert';
    const EVENT_STAFF_ABSENCE = 'staff.absence_reported';
    
    const EVENT_SYSTEM_ERROR = 'system.error';
    const EVENT_SYSTEM_BACKUP = 'system.backup_completed';
    const EVENT_SYSTEM_UPDATE = 'system.update_available';
    const EVENT_SECURITY_ALERT = 'system.security_alert';
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->initializeEventSystem();
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
     * Initialize event system
     */
    private function initializeEventSystem() {
        // Get notification engine
        require_once __DIR__ . '/NotificationEngine.php';
        $this->notification_engine = getNotificationEngine();
        
        // Register default event handlers
        $this->registerDefaultHandlers();
        
        // Create event tables if needed
        $this->createEventTables();
    }
    
    /**
     * Create event-related tables
     */
    private function createEventTables() {
        try {
            // Event log table
            $event_log_sql = "CREATE TABLE IF NOT EXISTS `event_log` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(100) NOT NULL,
                `event_data` json DEFAULT NULL,
                `triggered_by` int(11) DEFAULT NULL,
                `source_table` varchar(50) DEFAULT NULL,
                `source_id` int(11) DEFAULT NULL,
                `processed` tinyint(1) DEFAULT 0,
                `priority` tinyint(1) DEFAULT 1,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_event_type` (`event_type`),
                KEY `idx_processed` (`processed`),
                KEY `idx_priority` (`priority`),
                KEY `idx_created_at` (`created_at`),
                KEY `idx_source` (`source_table`, `source_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($event_log_sql);
            
            // Event handlers table
            $event_handlers_sql = "CREATE TABLE IF NOT EXISTS `event_handlers` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `event_type` varchar(100) NOT NULL,
                `handler_class` varchar(100) NOT NULL,
                `handler_method` varchar(100) NOT NULL,
                `priority` tinyint(1) DEFAULT 1,
                `is_active` tinyint(1) DEFAULT 1,
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `idx_event_type` (`event_type`),
                KEY `idx_active` (`is_active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            
            $this->db->query($event_handlers_sql);
            
        } catch (Exception $e) {
            error_log("Event table creation error: " . $e->getMessage());
        }
    }
    
    /**
     * Register default event handlers
     */
    private function registerDefaultHandlers() {
        // Booking events
        $this->addEventListener(self::EVENT_BOOKING_CREATED, [$this, 'handleBookingCreated']);
        $this->addEventListener(self::EVENT_BOOKING_CONFIRMED, [$this, 'handleBookingConfirmed']);
        $this->addEventListener(self::EVENT_BOOKING_CANCELLED, [$this, 'handleBookingCancelled']);
        $this->addEventListener(self::EVENT_BOOKING_CHECKIN, [$this, 'handleBookingCheckin']);
        $this->addEventListener(self::EVENT_BOOKING_CHECKOUT, [$this, 'handleBookingCheckout']);
        
        // Payment events
        $this->addEventListener(self::EVENT_PAYMENT_RECEIVED, [$this, 'handlePaymentReceived']);
        $this->addEventListener(self::EVENT_PAYMENT_FAILED, [$this, 'handlePaymentFailed']);
        
        // Room events
        $this->addEventListener(self::EVENT_ROOM_STATUS_CHANGED, [$this, 'handleRoomStatusChanged']);
        $this->addEventListener(self::EVENT_ROOM_MAINTENANCE, [$this, 'handleRoomMaintenance']);
        
        // Maintenance events
        $this->addEventListener(self::EVENT_MAINTENANCE_REQUEST, [$this, 'handleMaintenanceRequest']);
        $this->addEventListener(self::EVENT_MAINTENANCE_URGENT, [$this, 'handleUrgentMaintenance']);
        $this->addEventListener(self::EVENT_MAINTENANCE_COMPLETED, [$this, 'handleMaintenanceCompleted']);
        
        // Housekeeping events
        $this->addEventListener(self::EVENT_HOUSEKEEPING_TASK_ASSIGNED, [$this, 'handleHousekeepingTaskAssigned']);
        $this->addEventListener(self::EVENT_HOUSEKEEPING_TASK_COMPLETED, [$this, 'handleHousekeepingTaskCompleted']);
        
        // Inventory events
        $this->addEventListener(self::EVENT_INVENTORY_LOW_STOCK, [$this, 'handleInventoryLowStock']);
        $this->addEventListener(self::EVENT_INVENTORY_OUT_OF_STOCK, [$this, 'handleInventoryOutOfStock']);
        
        // System events
        $this->addEventListener(self::EVENT_SYSTEM_ERROR, [$this, 'handleSystemError']);
        $this->addEventListener(self::EVENT_SECURITY_ALERT, [$this, 'handleSecurityAlert']);
    }
    
    /**
     * Add event listener
     */
    public function addEventListener($event_type, $handler, $priority = 1) {
        if (!isset($this->event_handlers[$event_type])) {
            $this->event_handlers[$event_type] = [];
        }
        
        $this->event_handlers[$event_type][] = [
            'handler' => $handler,
            'priority' => $priority
        ];
        
        // Sort by priority (higher numbers first)
        usort($this->event_handlers[$event_type], function($a, $b) {
            return $b['priority'] - $a['priority'];
        });
    }
    
    /**
     * Trigger event
     */
    public function triggerEvent($event_type, $event_data = [], $options = []) {
        try {
            $event_id = $this->logEvent($event_type, $event_data, $options);
            
            // Process event handlers immediately
            $this->processEvent($event_type, $event_data, $event_id);
            
            // Add to notification engine if available
            if ($this->notification_engine) {
                $this->notification_engine->triggerEvent($event_type, $event_data);
            }
            
            return $event_id;
            
        } catch (Exception $e) {
            error_log("Event trigger error for $event_type: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log event to database
     */
    private function logEvent($event_type, $event_data, $options) {
        try {
            $query = "INSERT INTO event_log 
                     (event_type, event_data, triggered_by, source_table, source_id, priority) 
                     VALUES (?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($query);
            
            $event_json = json_encode($event_data);
            $triggered_by = $_SESSION['user_id'] ?? null;
            $source_table = $options['source_table'] ?? null;
            $source_id = $options['source_id'] ?? null;
            $priority = $options['priority'] ?? 1;
            
            $stmt->bind_param("ssisis", 
                $event_type, 
                $event_json, 
                $triggered_by, 
                $source_table, 
                $source_id, 
                $priority
            );
            
            $stmt->execute();
            return $this->db->insert_id;
            
        } catch (Exception $e) {
            error_log("Event logging error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Process event through handlers
     */
    private function processEvent($event_type, $event_data, $event_id) {
        if (!isset($this->event_handlers[$event_type])) {
            return;
        }
        
        foreach ($this->event_handlers[$event_type] as $handler_info) {
            try {
                $handler = $handler_info['handler'];
                
                if (is_callable($handler)) {
                    call_user_func($handler, $event_data, $event_id);
                }
                
            } catch (Exception $e) {
                error_log("Event handler error for $event_type: " . $e->getMessage());
            }
        }
        
        // Mark event as processed
        $this->markEventProcessed($event_id);
    }
    
    /**
     * Mark event as processed
     */
    private function markEventProcessed($event_id) {
        if (!$event_id) return;
        
        try {
            $query = "UPDATE event_log SET processed = 1 WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $event_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Mark event processed error: " . $e->getMessage());
        }
    }
    
    // Event Handlers
    
    /**
     * Handle booking created event
     */
    public function handleBookingCreated($event_data, $event_id) {
        // Update room status
        if (isset($event_data['room_id']) && isset($event_data['status'])) {
            $this->updateRoomStatus($event_data['room_id'], 'reserved');
        }
        
        // Send confirmation email (placeholder)
        $this->scheduleEmail([
            'type' => 'booking_confirmation',
            'recipient' => $event_data['guest_email'] ?? null,
            'data' => $event_data
        ]);
        
        // Create audit log
        $this->createAuditLog('booking_created', $event_data);
    }
    
    /**
     * Handle booking confirmed event
     */
    public function handleBookingConfirmed($event_data, $event_id) {
        // Update room status
        if (isset($event_data['room_id'])) {
            $this->updateRoomStatus($event_data['room_id'], 'occupied');
        }
        
        // Schedule housekeeping preparation
        $this->scheduleHousekeepingTask([
            'room_id' => $event_data['room_id'] ?? null,
            'task_type' => 'preparation',
            'priority' => 'high',
            'scheduled_for' => $event_data['checkin_date'] ?? date('Y-m-d')
        ]);
    }
    
    /**
     * Handle booking checkout event
     */
    public function handleBookingCheckout($event_data, $event_id) {
        // Update room status to cleaning
        if (isset($event_data['room_id'])) {
            $this->updateRoomStatus($event_data['room_id'], 'cleaning');
            
            // Schedule housekeeping cleaning task
            $this->scheduleHousekeepingTask([
                'room_id' => $event_data['room_id'],
                'task_type' => 'checkout_cleaning',
                'priority' => 'normal',
                'scheduled_for' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Process final billing
        $this->processFinalBilling($event_data);
    }
    
    /**
     * Handle payment received event
     */
    public function handlePaymentReceived($event_data, $event_id) {
        // Update booking status if payment is complete
        if (isset($event_data['booking_id']) && isset($event_data['amount'])) {
            $this->updateBookingPaymentStatus($event_data['booking_id'], 'paid');
        }
        
        // Generate receipt
        $this->generateReceipt($event_data);
        
        // Update financial records
        $this->updateFinancialRecords($event_data);
    }
    
    /**
     * Handle payment failed event
     */
    public function handlePaymentFailed($event_data, $event_id) {
        // Mark booking as payment pending
        if (isset($event_data['booking_id'])) {
            $this->updateBookingPaymentStatus($event_data['booking_id'], 'failed');
        }
        
        // Send payment failure notification
        $this->sendPaymentFailureNotification($event_data);
    }
    
    /**
     * Handle room status changed event
     */
    public function handleRoomStatusChanged($event_data, $event_id) {
        // Log status change
        $this->logRoomStatusChange($event_data);
        
        // Trigger dependent actions based on new status
        switch ($event_data['new_status'] ?? '') {
            case 'maintenance':
                $this->triggerEvent(self::EVENT_ROOM_MAINTENANCE, $event_data);
                break;
                
            case 'available':
                $this->notifyReservationTeam($event_data);
                break;
        }
    }
    
    /**
     * Handle maintenance request event
     */
    public function handleMaintenanceRequest($event_data, $event_id) {
        // Assign to maintenance team based on priority
        $assigned_team = $this->assignMaintenanceTeam($event_data);
        
        // Create work order
        $this->createMaintenanceWorkOrder($event_data, $assigned_team);
        
        // If urgent, trigger urgent maintenance event
        if (($event_data['priority'] ?? 'normal') === 'urgent') {
            $this->triggerEvent(self::EVENT_MAINTENANCE_URGENT, $event_data);
        }
    }
    
    /**
     * Handle urgent maintenance event
     */
    public function handleUrgentMaintenance($event_data, $event_id) {
        // Immediately notify operations manager
        $this->sendUrgentMaintenanceAlert($event_data);
        
        // Update room status if applicable
        if (isset($event_data['room_id'])) {
            $this->updateRoomStatus($event_data['room_id'], 'maintenance');
        }
        
        // Escalate to management
        $this->escalateToManagement($event_data);
    }
    
    /**
     * Handle housekeeping task assigned event
     */
    public function handleHousekeepingTaskAssigned($event_data, $event_id) {
        // Send task notification to assigned staff
        $this->notifyHousekeepingStaff($event_data);
        
        // Update task tracking
        $this->updateTaskTracking($event_data);
    }
    
    /**
     * Handle housekeeping task completed event
     */
    public function handleHousekeepingTaskCompleted($event_data, $event_id) {
        // Update room status
        if (isset($event_data['room_id'])) {
            $room_status = $this->determineRoomStatusAfterCleaning($event_data);
            $this->updateRoomStatus($event_data['room_id'], $room_status);
        }
        
        // Record task completion time
        $this->recordTaskCompletion($event_data);
        
        // Update staff performance metrics
        $this->updateStaffPerformance($event_data);
    }
    
    /**
     * Handle inventory low stock event
     */
    public function handleInventoryLowStock($event_data, $event_id) {
        // Check if automatic reordering is enabled
        if ($this->isAutoReorderEnabled($event_data['item_id'] ?? null)) {
            $this->triggerEvent(self::EVENT_INVENTORY_REORDER, $event_data);
        }
        
        // Notify procurement team
        $this->notifyProcurementTeam($event_data);
    }
    
    /**
     * Handle inventory out of stock event
     */
    public function handleInventoryOutOfStock($event_data, $event_id) {
        // Immediately notify operations and procurement
        $this->sendCriticalStockAlert($event_data);
        
        // Check for alternative items
        $alternatives = $this->findAlternativeItems($event_data['item_id'] ?? null);
        
        if (!empty($alternatives)) {
            $this->notifyAlternativeItems($event_data, $alternatives);
        }
    }
    
    /**
     * Handle system error event
     */
    public function handleSystemError($event_data, $event_id) {
        // Log detailed error information
        $this->logSystemError($event_data);
        
        // Notify IT team
        $this->notifyITTeam($event_data);
        
        // If critical, escalate immediately
        if (($event_data['severity'] ?? 'medium') === 'critical') {
            $this->escalateCriticalError($event_data);
        }
    }
    
    /**
     * Handle security alert event
     */
    public function handleSecurityAlert($event_data, $event_id) {
        // Immediately notify security team and IT admin
        $this->sendSecurityAlert($event_data);
        
        // Log security incident
        $this->logSecurityIncident($event_data);
        
        // If severe, lock down affected systems
        if (($event_data['severity'] ?? 'medium') === 'severe') {
            $this->initiateSecurityLockdown($event_data);
        }
    }
    
    // Helper Methods
    
    /**
     * Update room status
     */
    private function updateRoomStatus($room_id, $status) {
        try {
            $query = "UPDATE named_rooms SET status = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("si", $status, $room_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Update room status error: " . $e->getMessage());
        }
    }
    
    /**
     * Schedule housekeeping task
     */
    private function scheduleHousekeepingTask($task_data) {
        try {
            $query = "INSERT INTO housekeeping_tasks 
                     (room_id, task_type, priority, scheduled_for, status, created_at) 
                     VALUES (?, ?, ?, ?, 'pending', NOW())";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isss", 
                $task_data['room_id'],
                $task_data['task_type'],
                $task_data['priority'],
                $task_data['scheduled_for']
            );
            
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Schedule housekeeping task error: " . $e->getMessage());
        }
    }
    
    /**
     * Create audit log
     */
    private function createAuditLog($action, $data) {
        try {
            $query = "INSERT INTO audit_logs 
                     (user_id, action, table_name, details, ip_address, timestamp) 
                     VALUES (?, ?, 'events', ?, ?, NOW())";
            
            $stmt = $this->db->prepare($query);
            $user_id = $_SESSION['user_id'] ?? null;
            $details = json_encode($data);
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'system';
            
            $stmt->bind_param("isss", $user_id, $action, $details, $ip_address);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Create audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Schedule email (placeholder for email system)
     */
    private function scheduleEmail($email_data) {
        // This would integrate with an email service
        error_log("Email scheduled: " . json_encode($email_data));
    }
    
    /**
     * Process events from queue
     */
    public function processEventQueue() {
        if (empty($this->event_queue)) {
            return;
        }
        
        foreach ($this->event_queue as $index => $event) {
            try {
                $this->processEvent($event['type'], $event['data'], $event['id']);
                unset($this->event_queue[$index]);
                
            } catch (Exception $e) {
                error_log("Event queue processing error: " . $e->getMessage());
            }
        }
        
        $this->event_queue = array_values($this->event_queue);
    }
    
    /**
     * Get event statistics
     */
    public function getEventStatistics($timeframe = '24 HOUR') {
        try {
            $query = "SELECT 
                event_type,
                COUNT(*) as count,
                AVG(priority) as avg_priority
                FROM event_log 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL $timeframe)
                GROUP BY event_type 
                ORDER BY count DESC";
            
            $result = $this->db->query($query);
            $stats = [];
            
            while ($row = $result->fetch_assoc()) {
                $stats[] = $row;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get event statistics error: " . $e->getMessage());
            return [];
        }
    }
}

/**
 * Global event functions
 */
function getEventManager() {
    global $con;
    static $event_manager = null;
    
    if ($event_manager === null) {
        try {
            $event_manager = EventManager::getInstance($con);
        } catch (Exception $e) {
            error_log("Event Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $event_manager;
}

function triggerSystemEvent($event_type, $event_data = [], $options = []) {
    $em = getEventManager();
    return $em ? $em->triggerEvent($event_type, $event_data, $options) : false;
}

function addEventListener($event_type, $handler, $priority = 1) {
    $em = getEventManager();
    if ($em) {
        $em->addEventListener($event_type, $handler, $priority);
    }
}
?>
