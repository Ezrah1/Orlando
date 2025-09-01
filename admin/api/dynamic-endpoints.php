<?php
/**
 * Orlando International Resorts - Dynamic API Endpoints
 * Comprehensive real-time data API for all hotel operations
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required files
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';
require_once __DIR__ . '/../includes/NotificationEngine.php';
require_once __DIR__ . '/../auth.php';

/**
 * Dynamic API Router Class
 * Routes API requests to appropriate handlers with permission checking
 */
class DynamicApiRouter {
    private $db;
    private $permission_manager;
    private $event_manager;
    private $notification_engine;
    private $user_id;
    private $user_role;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->permission_manager = getPermissionManager();
        $this->event_manager = getEventManager();
        $this->notification_engine = getNotificationEngine();
        
        // Get user info from session
        if (isset($_SESSION['user_id'])) {
            $this->user_id = $_SESSION['user_id'];
            $this->user_role = $_SESSION['role'] ?? 'guest';
        }
    }
    
    /**
     * Main routing method
     */
    public function route() {
        $endpoint = $_GET['endpoint'] ?? '';
        $method = $_SERVER['REQUEST_METHOD'];
        
        try {
            // Check if user is authenticated for protected endpoints
            if (!$this->isPublicEndpoint($endpoint) && !$this->user_id) {
                $this->sendError('Authentication required', 401);
                return;
            }
            
            // Route to appropriate handler
            switch ($endpoint) {
                // Dashboard Data Endpoints
                case 'dashboard-stats':
                    $this->handleDashboardStats();
                    break;
                    
                case 'live-metrics':
                    $this->handleLiveMetrics();
                    break;
                
                // Booking & Reservation Endpoints
                case 'bookings':
                    $this->handleBookings($method);
                    break;
                    
                case 'booking-details':
                    $this->handleBookingDetails();
                    break;
                    
                case 'booking-calendar':
                    $this->handleBookingCalendar();
                    break;
                    
                case 'availability':
                    $this->handleAvailability();
                    break;
                
                // Room Management Endpoints
                case 'rooms':
                    $this->handleRooms($method);
                    break;
                    
                case 'room-status':
                    $this->handleRoomStatus($method);
                    break;
                    
                case 'room-assignments':
                    $this->handleRoomAssignments();
                    break;
                
                // Financial Endpoints
                case 'financial-summary':
                    $this->handleFinancialSummary();
                    break;
                    
                case 'revenue-data':
                    $this->handleRevenueData();
                    break;
                    
                case 'transactions':
                    $this->handleTransactions($method);
                    break;
                    
                case 'payment-status':
                    $this->handlePaymentStatus();
                    break;
                
                // Inventory Endpoints
                case 'inventory':
                    $this->handleInventory($method);
                    break;
                    
                case 'stock-levels':
                    $this->handleStockLevels();
                    break;
                    
                case 'reorder-alerts':
                    $this->handleReorderAlerts();
                    break;
                
                // Staff & Operations Endpoints
                case 'staff':
                    $this->handleStaff($method);
                    break;
                    
                case 'staff-schedules':
                    $this->handleStaffSchedules();
                    break;
                    
                case 'operations-status':
                    $this->handleOperationsStatus();
                    break;
                
                // Housekeeping Endpoints
                case 'housekeeping-tasks':
                    $this->handleHousekeepingTasks($method);
                    break;
                    
                case 'cleaning-status':
                    $this->handleCleaningStatus();
                    break;
                
                // Maintenance Endpoints
                case 'maintenance-requests':
                    $this->handleMaintenanceRequests($method);
                    break;
                    
                case 'maintenance-status':
                    $this->handleMaintenanceStatus();
                    break;
                
                // Restaurant & POS Endpoints
                case 'restaurant-orders':
                    $this->handleRestaurantOrders($method);
                    break;
                    
                case 'menu-items':
                    $this->handleMenuItems($method);
                    break;
                    
                case 'pos-sales':
                    $this->handlePosSales();
                    break;
                
                // Reports & Analytics Endpoints
                case 'occupancy-report':
                    $this->handleOccupancyReport();
                    break;
                    
                case 'revenue-report':
                    $this->handleRevenueReport();
                    break;
                    
                case 'performance-metrics':
                    $this->handlePerformanceMetrics();
                    break;
                
                // Notification Endpoints
                case 'notifications':
                    $this->handleNotifications($method);
                    break;
                    
                case 'notification-count':
                    $this->handleNotificationCount();
                    break;
                
                // System Endpoints
                case 'system-health':
                    $this->handleSystemHealth();
                    break;
                    
                case 'audit-logs':
                    $this->handleAuditLogs();
                    break;
                
                // Search & Filter Endpoints
                case 'search':
                    $this->handleSearch();
                    break;
                    
                case 'filters':
                    $this->handleFilters();
                    break;
                
                default:
                    $this->sendError('Endpoint not found', 404);
            }
            
        } catch (Exception $e) {
            error_log("API Error: " . $e->getMessage());
            $this->sendError('Internal server error', 500);
        }
    }
    
    /**
     * Dashboard Statistics Handler
     */
    private function handleDashboardStats() {
        $this->checkPermission('dashboard.view');
        
        $stats = [
            'occupancy' => $this->getOccupancyStats(),
            'revenue' => $this->getRevenueStats(),
            'bookings' => $this->getBookingStats(),
            'operations' => $this->getOperationsStats(),
            'staff' => $this->getStaffStats(),
            'maintenance' => $this->getMaintenanceStats()
        ];
        
        $this->sendSuccess($stats);
    }
    
    /**
     * Live Metrics Handler
     */
    private function handleLiveMetrics() {
        $this->checkPermission('dashboard.view');
        
        $metrics = [
            'current_occupancy' => $this->getCurrentOccupancy(),
            'daily_revenue' => $this->getDailyRevenue(),
            'pending_checkins' => $this->getPendingCheckins(),
            'pending_checkouts' => $this->getPendingCheckouts(),
            'maintenance_urgent' => $this->getUrgentMaintenance(),
            'staff_on_duty' => $this->getStaffOnDuty(),
            'housekeeping_tasks' => $this->getPendingHousekeeping()
        ];
        
        $this->sendSuccess($metrics);
    }
    
    /**
     * Bookings Handler
     */
    private function handleBookings($method) {
        switch ($method) {
            case 'GET':
                $this->checkPermission('booking.view');
                $bookings = $this->getBookings();
                $this->sendSuccess($bookings);
                break;
                
            case 'POST':
                $this->checkPermission('booking.create');
                $result = $this->createBooking();
                $this->sendSuccess($result);
                break;
                
            case 'PUT':
                $this->checkPermission('booking.modify');
                $result = $this->updateBooking();
                $this->sendSuccess($result);
                break;
                
            case 'DELETE':
                $this->checkPermission('booking.cancel');
                $result = $this->cancelBooking();
                $this->sendSuccess($result);
                break;
                
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Room Status Handler
     */
    private function handleRoomStatus($method) {
        switch ($method) {
            case 'GET':
                $this->checkPermission('room.view');
                $status = $this->getRoomStatus();
                $this->sendSuccess($status);
                break;
                
            case 'PUT':
                $this->checkPermission('room.status_update');
                $result = $this->updateRoomStatus();
                $this->sendSuccess($result);
                break;
                
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Financial Summary Handler
     */
    private function handleFinancialSummary() {
        $this->checkPermission('finance.view');
        
        $timeframe = $_GET['timeframe'] ?? 'daily';
        $department = $_GET['department'] ?? 'all';
        
        $summary = [
            'revenue' => $this->getRevenueSummary($timeframe, $department),
            'expenses' => $this->getExpenseSummary($timeframe, $department),
            'profit' => $this->getProfitSummary($timeframe, $department),
            'trends' => $this->getFinancialTrends($timeframe, $department)
        ];
        
        $this->sendSuccess($summary);
    }
    
    /**
     * Inventory Handler
     */
    private function handleInventory($method) {
        switch ($method) {
            case 'GET':
                $this->checkPermission('inventory.view');
                $inventory = $this->getInventory();
                $this->sendSuccess($inventory);
                break;
                
            case 'PUT':
                $this->checkPermission('inventory.update');
                $result = $this->updateInventory();
                $this->sendSuccess($result);
                break;
                
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Housekeeping Tasks Handler
     */
    private function handleHousekeepingTasks($method) {
        switch ($method) {
            case 'GET':
                $this->checkPermission('housekeeping.view');
                $tasks = $this->getHousekeepingTasks();
                $this->sendSuccess($tasks);
                break;
                
            case 'POST':
                $this->checkPermission('housekeeping.assign');
                $result = $this->createHousekeepingTask();
                $this->sendSuccess($result);
                break;
                
            case 'PUT':
                $this->checkPermission('housekeeping.update');
                $result = $this->updateHousekeepingTask();
                $this->sendSuccess($result);
                break;
                
            default:
                $this->sendError('Method not allowed', 405);
        }
    }
    
    /**
     * Get Occupancy Statistics
     */
    private function getOccupancyStats() {
        $query = "SELECT 
            COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
            COUNT(CASE WHEN status = 'cleaning' THEN 1 END) as cleaning,
            COUNT(*) as total
            FROM named_rooms";
        
        $result = $this->db->query($query);
        $stats = $result->fetch_assoc();
        
        $stats['occupancy_rate'] = $stats['total'] > 0 ? 
            round(($stats['occupied'] / $stats['total']) * 100, 2) : 0;
            
        return $stats;
    }
    
    /**
     * Get Revenue Statistics
     */
    private function getRevenueStats() {
        $query = "SELECT 
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN amount ELSE 0 END) as today,
            SUM(CASE WHEN WEEK(created_at, 1) = WEEK(CURDATE(), 1) THEN amount ELSE 0 END) as this_week,
            SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) THEN amount ELSE 0 END) as this_month,
            SUM(CASE WHEN YEAR(created_at) = YEAR(CURDATE()) THEN amount ELSE 0 END) as this_year
            FROM transactions 
            WHERE status = 'completed' AND transaction_type = 'credit'";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    /**
     * Get Current Occupancy
     */
    private function getCurrentOccupancy() {
        $query = "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status = 'occupied'";
        $result = $this->db->query($query);
        $data = $result->fetch_assoc();
        return intval($data['count']);
    }
    
    /**
     * Get Room Status
     */
    private function getRoomStatus() {
        $query = "SELECT 
            id,
            room_number,
            room_type,
            status,
            last_cleaned,
            next_checkin,
            current_guest,
            updated_at
            FROM named_rooms 
            ORDER BY room_number";
        
        $result = $this->db->query($query);
        $rooms = [];
        
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        
        return $rooms;
    }
    
    /**
     * Get Bookings
     */
    private function getBookings() {
        $limit = intval($_GET['limit'] ?? 50);
        $offset = intval($_GET['offset'] ?? 0);
        $status = $_GET['status'] ?? '';
        
        $where_clause = "WHERE 1=1";
        if ($status) {
            $where_clause .= " AND status = '" . $this->db->real_escape_string($status) . "'";
        }
        
        $query = "SELECT 
            b.*,
            r.room_number,
            r.room_type,
            u.full_name as guest_name
            FROM bookings b
            LEFT JOIN named_rooms r ON b.room_id = r.id
            LEFT JOIN users u ON b.user_id = u.id
            $where_clause
            ORDER BY b.created_at DESC
            LIMIT $limit OFFSET $offset";
        
        $result = $this->db->query($query);
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    /**
     * Update Room Status
     */
    private function updateRoomStatus() {
        $input = json_decode(file_get_contents('php://input'), true);
        
        $room_id = intval($input['room_id'] ?? 0);
        $new_status = $input['status'] ?? '';
        $notes = $input['notes'] ?? '';
        
        if (!$room_id || !$new_status) {
            throw new Exception('Room ID and status are required');
        }
        
        // Update room status
        $query = "UPDATE named_rooms 
                 SET status = ?, notes = ?, updated_at = NOW() 
                 WHERE id = ?";
        
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ssi", $new_status, $notes, $room_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Trigger room status change event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('room.status_changed', [
                    'room_id' => $room_id,
                    'old_status' => $input['old_status'] ?? '',
                    'new_status' => $new_status,
                    'notes' => $notes,
                    'updated_by' => $this->user_id
                ]);
            }
            
            return ['success' => true, 'message' => 'Room status updated successfully'];
        } else {
            throw new Exception('Failed to update room status');
        }
    }
    
    /**
     * Check if endpoint is public (doesn't require authentication)
     */
    private function isPublicEndpoint($endpoint) {
        $public_endpoints = ['system-health'];
        return in_array($endpoint, $public_endpoints);
    }
    
    /**
     * Check user permission
     */
    private function checkPermission($permission) {
        if (!$this->permission_manager || !$this->permission_manager->hasPermission($this->user_id, $permission)) {
            $this->sendError('Permission denied', 403);
            exit();
        }
    }
    
    /**
     * Send success response
     */
    private function sendSuccess($data, $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    /**
     * Send error response
     */
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ]);
    }
}

// Initialize and route the API request
try {
    // Ensure user is authenticated for protected endpoints
    if (!isset($_SESSION['user_id']) && !in_array($_GET['endpoint'] ?? '', ['system-health'])) {
        // Try to validate session if token is provided
        if (isset($_GET['token']) || isset($_SERVER['HTTP_AUTHORIZATION'])) {
            // Session token validation would go here
        }
    }
    
    $api = new DynamicApiRouter($con);
    $api->route();
    
} catch (Exception $e) {
    error_log("API Router Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'timestamp' => time()
    ]);
}
?>
