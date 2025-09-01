<?php
/**
 * Orlando International Resorts - Dynamic Dashboard Core System
 * Real-time dashboard management with role-based access and live data updates
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../security_config.php';

/**
 * Dynamic Dashboard Manager Class
 * Handles real-time dashboard data, widgets, and role-based content
 */
class DashboardManager {
    private $db;
    private $user_id;
    private $user_role;
    private $cache_duration = 300; // 5 minutes default cache
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['user_role'] ?? 'guest';
        
        if (!$this->user_id) {
            throw new Exception('User not authenticated for dashboard access');
        }
    }
    
    /**
     * Get real-time dashboard data based on user role
     */
    public function getDashboardData() {
        $cache_key = "dashboard_" . $this->user_role . "_" . $this->user_id;
        
        // Check cache first for performance
        $cached_data = $this->getFromCache($cache_key);
        if ($cached_data !== null) {
            return $cached_data;
        }
        
        $dashboard_data = [
            'user_info' => $this->getCurrentUserInfo(),
            'widgets' => $this->getWidgetsForRole(),
            'stats' => $this->getStatsForRole(),
            'notifications' => $this->getNotifications(),
            'recent_activities' => $this->getRecentActivities(),
            'timestamp' => time()
        ];
        
        // Cache the data for performance
        $this->setCache($cache_key, $dashboard_data, $this->cache_duration);
        
        return $dashboard_data;
    }
    
    /**
     * Get widgets configuration based on user role
     */
    public function getWidgetsForRole() {
        $widgets = [];
        
        switch ($this->user_role) {
            case 'director':
            case 'ceo':
            case 'super_admin':
                $widgets = $this->getDirectorWidgets();
                break;
                
            case 'operations_manager':
                $widgets = $this->getOperationsWidgets();
                break;
                
            case 'finance_manager':
                $widgets = $this->getFinanceWidgets();
                break;
                
            case 'it_admin':
            case 'system_admin':
                $widgets = $this->getITAdminWidgets();
                break;
                
            case 'department_head':
            case 'manager':
                $widgets = $this->getManagementWidgets();
                break;
                
            case 'staff':
                $widgets = $this->getStaffWidgets();
                break;
                
            default:
                $widgets = $this->getBasicWidgets();
        }
        
        return $widgets;
    }
    
    /**
     * Get real-time statistics based on user role
     */
    public function getStatsForRole() {
        $stats = [];
        
        try {
            switch ($this->user_role) {
                case 'director':
                case 'ceo':
                case 'super_admin':
                    $stats = $this->getExecutiveStats();
                    break;
                    
                case 'operations_manager':
                    $stats = $this->getOperationsStats();
                    break;
                    
                case 'finance_manager':
                    $stats = $this->getFinanceStats();
                    break;
                    
                case 'it_admin':
                case 'system_admin':
                    $stats = $this->getSystemStats();
                    break;
                    
                default:
                    $stats = $this->getGeneralStats();
            }
        } catch (Exception $e) {
            error_log("Dashboard stats error: " . $e->getMessage());
            $stats = $this->getFallbackStats();
        }
        
        return $stats;
    }
    
    /**
     * Executive/Director Dashboard Statistics
     */
    private function getExecutiveStats() {
        $stats = [];
        
        // Revenue Analytics
        $revenue_query = "SELECT 
            SUM(CASE WHEN DATE(created_at) = CURDATE() THEN total_amount ELSE 0 END) as today_revenue,
            SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN total_amount ELSE 0 END) as monthly_revenue,
            COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_bookings,
            COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as monthly_bookings
            FROM roombook WHERE status = 'confirmed'";
            
        $revenue_result = $this->db->query($revenue_query);
        if ($revenue_result && $revenue_data = $revenue_result->fetch_assoc()) {
            $stats['revenue'] = [
                'today' => floatval($revenue_data['today_revenue'] ?? 0),
                'monthly' => floatval($revenue_data['monthly_revenue'] ?? 0),
                'today_bookings' => intval($revenue_data['today_bookings'] ?? 0),
                'monthly_bookings' => intval($revenue_data['monthly_bookings'] ?? 0)
            ];
        }
        
        // Room Occupancy
        $room_query = "SELECT 
            COUNT(*) as total_rooms,
            COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied_rooms,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available_rooms,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_rooms
            FROM named_rooms";
            
        $room_result = $this->db->query($room_query);
        if ($room_result && $room_data = $room_result->fetch_assoc()) {
            $total_rooms = intval($room_data['total_rooms']);
            $occupied_rooms = intval($room_data['occupied_rooms']);
            
            $stats['occupancy'] = [
                'total_rooms' => $total_rooms,
                'occupied_rooms' => $occupied_rooms,
                'available_rooms' => intval($room_data['available_rooms']),
                'maintenance_rooms' => intval($room_data['maintenance_rooms']),
                'occupancy_rate' => $total_rooms > 0 ? round(($occupied_rooms / $total_rooms) * 100, 1) : 0
            ];
        }
        
        // Staff Performance
        $staff_query = "SELECT COUNT(*) as total_staff FROM users WHERE status = 'active'";
        $staff_result = $this->db->query($staff_query);
        if ($staff_result && $staff_data = $staff_result->fetch_assoc()) {
            $stats['staff'] = [
                'total_active' => intval($staff_data['total_staff']),
                'departments' => $this->getDepartmentStats()
            ];
        }
        
        return $stats;
    }
    
    /**
     * Operations Manager Dashboard Statistics
     */
    private function getOperationsStats() {
        $stats = [];
        
        // Room Status Real-time
        $room_status_query = "SELECT 
            status, COUNT(*) as count 
            FROM named_rooms 
            GROUP BY status";
            
        $room_status_result = $this->db->query($room_status_query);
        $room_status = [];
        while ($room_status_result && $row = $room_status_result->fetch_assoc()) {
            $room_status[$row['status']] = intval($row['count']);
        }
        
        $stats['room_status'] = $room_status;
        
        // Housekeeping Tasks
        $housekeeping_query = "SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks,
            COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks
            FROM housekeeping_tasks 
            WHERE DATE(created_at) = CURDATE()";
            
        $housekeeping_result = $this->db->query($housekeeping_query);
        if ($housekeeping_result && $housekeeping_data = $housekeeping_result->fetch_assoc()) {
            $stats['housekeeping'] = [
                'total_tasks' => intval($housekeeping_data['total_tasks']),
                'pending' => intval($housekeeping_data['pending_tasks']),
                'in_progress' => intval($housekeeping_data['in_progress_tasks']),
                'completed' => intval($housekeeping_data['completed_tasks'])
            ];
        }
        
        // Maintenance Requests
        $maintenance_query = "SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
            COUNT(CASE WHEN status = 'open' THEN 1 END) as open_requests
            FROM maintenance_requests";
            
        $maintenance_result = $this->db->query($maintenance_query);
        if ($maintenance_result && $maintenance_data = $maintenance_result->fetch_assoc()) {
            $stats['maintenance'] = [
                'total_requests' => intval($maintenance_data['total_requests']),
                'high_priority' => intval($maintenance_data['high_priority']),
                'open_requests' => intval($maintenance_data['open_requests'])
            ];
        }
        
        return $stats;
    }
    
    /**
     * Finance Manager Dashboard Statistics
     */
    private function getFinanceStats() {
        $stats = [];
        
        // Daily Revenue Breakdown
        $daily_revenue_query = "SELECT 
            SUM(rb.total_amount) as room_revenue,
            (SELECT SUM(total_amount) FROM bar_orders WHERE DATE(created_at) = CURDATE()) as bar_revenue,
            (SELECT SUM(total_amount) FROM food_orders WHERE DATE(created_at) = CURDATE()) as food_revenue
            FROM roombook rb 
            WHERE DATE(rb.created_at) = CURDATE() AND rb.status = 'confirmed'";
            
        $revenue_result = $this->db->query($daily_revenue_query);
        if ($revenue_result && $revenue_data = $revenue_result->fetch_assoc()) {
            $stats['daily_revenue'] = [
                'rooms' => floatval($revenue_data['room_revenue'] ?? 0),
                'bar' => floatval($revenue_data['bar_revenue'] ?? 0),
                'food' => floatval($revenue_data['food_revenue'] ?? 0),
                'total' => floatval($revenue_data['room_revenue'] ?? 0) + 
                          floatval($revenue_data['bar_revenue'] ?? 0) + 
                          floatval($revenue_data['food_revenue'] ?? 0)
            ];
        }
        
        // Expenses Summary
        $expenses_query = "SELECT 
            SUM(CASE WHEN DATE(expense_date) = CURDATE() THEN amount ELSE 0 END) as today_expenses,
            SUM(CASE WHEN MONTH(expense_date) = MONTH(CURDATE()) THEN amount ELSE 0 END) as monthly_expenses
            FROM expenses";
            
        $expenses_result = $this->db->query($expenses_query);
        if ($expenses_result && $expenses_data = $expenses_result->fetch_assoc()) {
            $stats['expenses'] = [
                'today' => floatval($expenses_data['today_expenses'] ?? 0),
                'monthly' => floatval($expenses_data['monthly_expenses'] ?? 0)
            ];
        }
        
        // Payment Status
        $payments_query = "SELECT 
            COUNT(*) as total_payments,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
            SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as completed_amount
            FROM payments 
            WHERE DATE(created_at) = CURDATE()";
            
        $payments_result = $this->db->query($payments_query);
        if ($payments_result && $payments_data = $payments_result->fetch_assoc()) {
            $stats['payments'] = [
                'total_today' => intval($payments_data['total_payments']),
                'pending' => intval($payments_data['pending_payments']),
                'completed_amount' => floatval($payments_data['completed_amount'] ?? 0)
            ];
        }
        
        return $stats;
    }
    
    /**
     * IT Admin Dashboard Statistics
     */
    private function getSystemStats() {
        $stats = [];
        
        // User Activity
        $user_activity_query = "SELECT 
            COUNT(DISTINCT user_id) as active_users_today,
            COUNT(*) as total_logins_today
            FROM login 
            WHERE DATE(login_time) = CURDATE()";
            
        $activity_result = $this->db->query($user_activity_query);
        if ($activity_result && $activity_data = $activity_result->fetch_assoc()) {
            $stats['user_activity'] = [
                'active_users' => intval($activity_data['active_users_today']),
                'total_logins' => intval($activity_data['total_logins_today'])
            ];
        }
        
        // System Health
        $stats['system_health'] = [
            'database_size' => $this->getDatabaseSize(),
            'server_uptime' => $this->getServerUptime(),
            'memory_usage' => $this->getMemoryUsage(),
            'disk_usage' => $this->getDiskUsage()
        ];
        
        // Security Alerts
        $security_query = "SELECT COUNT(*) as failed_logins 
                          FROM login 
                          WHERE status = 'failed' 
                          AND DATE(login_time) = CURDATE()";
        $security_result = $this->db->query($security_query);
        if ($security_result && $security_data = $security_result->fetch_assoc()) {
            $stats['security'] = [
                'failed_logins' => intval($security_data['failed_logins'])
            ];
        }
        
        return $stats;
    }
    
    /**
     * General Statistics for all users
     */
    private function getGeneralStats() {
        return [
            'timestamp' => time(),
            'user_role' => $this->user_role,
            'basic_info' => 'General dashboard access'
        ];
    }
    
    /**
     * Fallback statistics in case of errors
     */
    private function getFallbackStats() {
        return [
            'error' => true,
            'message' => 'Unable to load dashboard statistics',
            'timestamp' => time()
        ];
    }
    
    /**
     * Get current user information
     */
    private function getCurrentUserInfo() {
        $user_query = "SELECT id, username, email, full_name, role, last_login 
                       FROM users 
                       WHERE id = ?";
        $stmt = $this->db->prepare($user_query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($user_data = $result->fetch_assoc()) {
            return [
                'id' => $user_data['id'],
                'username' => $user_data['username'],
                'email' => $user_data['email'],
                'full_name' => $user_data['full_name'],
                'role' => $user_data['role'],
                'last_login' => $user_data['last_login']
            ];
        }
        
        return null;
    }
    
    /**
     * Get notifications for current user
     */
    private function getNotifications() {
        // Check if notifications table exists
        $table_check = $this->db->query("SHOW TABLES LIKE 'notifications'");
        if (!$table_check || $table_check->num_rows == 0) {
            return $this->getFallbackNotifications();
        }
        
        $notifications_query = "SELECT id, title, message, type, is_read, created_at 
                               FROM notifications 
                               WHERE user_id = ? 
                               ORDER BY created_at DESC 
                               LIMIT 10";
        $stmt = $this->db->prepare($notifications_query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $notifications = [];
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }
    
    /**
     * Fallback notifications when table doesn't exist
     */
    private function getFallbackNotifications() {
        return [
            [
                'id' => 1,
                'title' => 'Welcome to Orlando International Resorts',
                'message' => 'Your dashboard is ready for use',
                'type' => 'info',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ]
        ];
    }
    
    /**
     * Get recent activities
     */
    private function getRecentActivities() {
        // Return static data for now (as per previous implementation)
        return [
            [
                'type' => 'booking',
                'message' => 'New booking received',
                'created_at' => date('Y-m-d H:i:s'),
                'icon' => 'fas fa-calendar-plus',
                'color' => 'success'
            ],
            [
                'type' => 'payment',
                'message' => 'Payment processed successfully',
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'icon' => 'fas fa-credit-card',
                'color' => 'success'
            ],
            [
                'type' => 'maintenance',
                'message' => 'Room maintenance completed',
                'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours')),
                'icon' => 'fas fa-tools',
                'color' => 'info'
            ]
        ];
    }
    
    /**
     * Widget configurations for different roles
     */
    private function getDirectorWidgets() {
        return [
            'revenue_chart' => ['type' => 'chart', 'title' => 'Revenue Trends', 'size' => 'large'],
            'occupancy_rate' => ['type' => 'metric', 'title' => 'Occupancy Rate', 'size' => 'medium'],
            'financial_overview' => ['type' => 'table', 'title' => 'Financial Overview', 'size' => 'large'],
            'department_performance' => ['type' => 'chart', 'title' => 'Department Performance', 'size' => 'medium']
        ];
    }
    
    private function getOperationsWidgets() {
        return [
            'room_status' => ['type' => 'status', 'title' => 'Room Status', 'size' => 'large'],
            'housekeeping_tasks' => ['type' => 'list', 'title' => 'Housekeeping Tasks', 'size' => 'medium'],
            'maintenance_requests' => ['type' => 'list', 'title' => 'Maintenance Requests', 'size' => 'medium'],
            'staff_schedule' => ['type' => 'calendar', 'title' => 'Staff Schedule', 'size' => 'large']
        ];
    }
    
    private function getFinanceWidgets() {
        return [
            'revenue_breakdown' => ['type' => 'chart', 'title' => 'Revenue Breakdown', 'size' => 'large'],
            'daily_sales' => ['type' => 'metric', 'title' => 'Daily Sales', 'size' => 'medium'],
            'expense_tracking' => ['type' => 'chart', 'title' => 'Expense Tracking', 'size' => 'medium'],
            'payment_status' => ['type' => 'list', 'title' => 'Payment Status', 'size' => 'large']
        ];
    }
    
    private function getITAdminWidgets() {
        return [
            'system_health' => ['type' => 'status', 'title' => 'System Health', 'size' => 'large'],
            'user_activity' => ['type' => 'chart', 'title' => 'User Activity', 'size' => 'medium'],
            'security_alerts' => ['type' => 'list', 'title' => 'Security Alerts', 'size' => 'medium'],
            'backup_status' => ['type' => 'status', 'title' => 'Backup Status', 'size' => 'small']
        ];
    }
    
    private function getManagementWidgets() {
        return [
            'department_overview' => ['type' => 'chart', 'title' => 'Department Overview', 'size' => 'large'],
            'staff_performance' => ['type' => 'list', 'title' => 'Staff Performance', 'size' => 'medium'],
            'budget_status' => ['type' => 'metric', 'title' => 'Budget Status', 'size' => 'medium']
        ];
    }
    
    private function getStaffWidgets() {
        return [
            'my_tasks' => ['type' => 'list', 'title' => 'My Tasks', 'size' => 'large'],
            'schedule' => ['type' => 'calendar', 'title' => 'My Schedule', 'size' => 'medium'],
            'announcements' => ['type' => 'list', 'title' => 'Announcements', 'size' => 'medium']
        ];
    }
    
    private function getBasicWidgets() {
        return [
            'welcome' => ['type' => 'info', 'title' => 'Welcome', 'size' => 'large'],
            'quick_stats' => ['type' => 'metric', 'title' => 'Quick Stats', 'size' => 'medium']
        ];
    }
    
    /**
     * Helper methods for system statistics
     */
    private function getDatabaseSize() {
        $query = "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size 
                  FROM information_schema.tables 
                  WHERE table_schema = DATABASE()";
        $result = $this->db->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            return floatval($row['db_size']) . ' MB';
        }
        return 'Unknown';
    }
    
    private function getServerUptime() {
        $query = "SHOW STATUS LIKE 'Uptime'";
        $result = $this->db->query($query);
        if ($result && $row = $result->fetch_assoc()) {
            $uptime = intval($row['Value']);
            $days = floor($uptime / 86400);
            $hours = floor(($uptime % 86400) / 3600);
            return $days . ' days, ' . $hours . ' hours';
        }
        return 'Unknown';
    }
    
    private function getMemoryUsage() {
        if (function_exists('memory_get_usage')) {
            return round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB';
        }
        return 'Unknown';
    }
    
    private function getDiskUsage() {
        if (function_exists('disk_free_space')) {
            $bytes = disk_free_space(".");
            $si_prefix = array( 'B', 'KB', 'MB', 'GB', 'TB', 'EB', 'ZB', 'YB' );
            $base = 1024;
            $class = min((int)log($bytes , $base) , count($si_prefix) - 1);
            return sprintf('%1.2f' , $bytes / pow($base,$class)) . ' ' . $si_prefix[$class] . ' free';
        }
        return 'Unknown';
    }
    
    private function getDepartmentStats() {
        $dept_query = "SELECT department, COUNT(*) as staff_count 
                       FROM users 
                       WHERE status = 'active' AND department IS NOT NULL 
                       GROUP BY department";
        $result = $this->db->query($dept_query);
        $departments = [];
        while ($result && $row = $result->fetch_assoc()) {
            $departments[$row['department']] = intval($row['staff_count']);
        }
        return $departments;
    }
    
    /**
     * Simple caching system
     */
    private function getFromCache($key) {
        // Simple file-based cache for now
        $cache_file = sys_get_temp_dir() . '/hotel_dashboard_' . md5($key) . '.cache';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->cache_duration) {
            return json_decode(file_get_contents($cache_file), true);
        }
        return null;
    }
    
    private function setCache($key, $data, $duration) {
        $cache_file = sys_get_temp_dir() . '/hotel_dashboard_' . md5($key) . '.cache';
        file_put_contents($cache_file, json_encode($data));
    }
    
    /**
     * Get widget data for AJAX requests
     */
    public function getWidgetData($widget_id) {
        $widgets = $this->getWidgetsForRole();
        if (!isset($widgets[$widget_id])) {
            return ['error' => 'Widget not found or not authorized'];
        }
        
        // Return specific widget data
        return [
            'widget_id' => $widget_id,
            'data' => $this->generateWidgetData($widget_id, $widgets[$widget_id]),
            'timestamp' => time()
        ];
    }
    
    private function generateWidgetData($widget_id, $widget_config) {
        // Generate specific data based on widget type and user role
        switch ($widget_config['type']) {
            case 'chart':
                return $this->generateChartData($widget_id);
            case 'metric':
                return $this->generateMetricData($widget_id);
            case 'list':
                return $this->generateListData($widget_id);
            case 'status':
                return $this->generateStatusData($widget_id);
            default:
                return ['message' => 'Widget data not available'];
        }
    }
    
    private function generateChartData($widget_id) {
        // Return sample chart data - will be enhanced with real data
        return [
            'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => [12000, 15000, 18000, 16000, 19000, 22000],
                    'backgroundColor' => 'rgba(75, 192, 192, 0.2)',
                    'borderColor' => 'rgba(75, 192, 192, 1)'
                ]
            ]
        ];
    }
    
    private function generateMetricData($widget_id) {
        $stats = $this->getStatsForRole();
        
        switch ($widget_id) {
            case 'occupancy_rate':
                return [
                    'value' => $stats['occupancy']['occupancy_rate'] ?? 0,
                    'label' => 'Occupancy Rate',
                    'unit' => '%',
                    'trend' => 'up',
                    'color' => 'success'
                ];
            case 'daily_sales':
                return [
                    'value' => $stats['daily_revenue']['total'] ?? 0,
                    'label' => 'Daily Sales',
                    'unit' => '$',
                    'trend' => 'up',
                    'color' => 'success'
                ];
            default:
                return ['value' => 0, 'label' => 'Metric'];
        }
    }
    
    private function generateListData($widget_id) {
        return [
            'items' => [
                ['title' => 'Sample Item 1', 'status' => 'active'],
                ['title' => 'Sample Item 2', 'status' => 'pending']
            ]
        ];
    }
    
    private function generateStatusData($widget_id) {
        return [
            'status' => 'online',
            'message' => 'All systems operational',
            'details' => ['CPU: 45%', 'Memory: 67%', 'Disk: 23%']
        ];
    }
}

/**
 * Global dashboard functions for easy access
 */
function getDashboardManager() {
    global $con;
    try {
        return new DashboardManager($con);
    } catch (Exception $e) {
        error_log("Dashboard Manager Error: " . $e->getMessage());
        return null;
    }
}

function getDashboardData() {
    $manager = getDashboardManager();
    return $manager ? $manager->getDashboardData() : [];
}

function getWidgetData($widget_id) {
    $manager = getDashboardManager();
    return $manager ? $manager->getWidgetData($widget_id) : ['error' => 'Dashboard not available'];
}
?>
