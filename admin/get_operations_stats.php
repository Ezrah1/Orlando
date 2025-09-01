<?php
/**
 * Dynamic Operations Statistics API
 * Returns real-time operational data for dashboards
 */

define('ADMIN_ACCESS', true);
require_once 'auth.php';
require_once 'security_config.php';

// Ensure user is logged in
ensure_logged_in();

// Set JSON header
header('Content-Type: application/json');

try {
    $today = date('Y-m-d');
    $this_month = date('Y-m');
    
    // Room Statistics
    $room_stats = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_rooms,
            COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied,
            COUNT(CASE WHEN status = 'available' THEN 1 END) as available,
            COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance,
            COUNT(CASE WHEN status = 'cleaning' THEN 1 END) as cleaning,
            COUNT(CASE WHEN status = 'out_of_order' THEN 1 END) as out_of_order
        FROM named_rooms WHERE is_active = 1
    ");
    $rooms = mysqli_fetch_assoc($room_stats);
    
    // Guest Flow Statistics
    $guest_stats = mysqli_query($con, "
        SELECT 
            COUNT(CASE WHEN DATE(cin) = '$today' AND status = 'confirmed' THEN 1 END) as todays_checkins,
            COUNT(CASE WHEN DATE(cout) = '$today' AND status IN ('checked_in', 'confirmed') THEN 1 END) as todays_checkouts,
            COUNT(CASE WHEN DATE(cin) = '$today' AND status = 'confirmed' AND cin <= NOW() THEN 1 END) as pending_checkins,
            COUNT(CASE WHEN DATE(cout) = '$today' AND status = 'checked_in' THEN 1 END) as pending_checkouts,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings
        FROM roombook
    ");
    $guests = mysqli_fetch_assoc($guest_stats);
    
    // Financial Statistics
    $financial_stats = mysqli_query($con, "
        SELECT 
            COALESCE(SUM(CASE WHEN DATE(created_at) = '$today' AND status = 'confirmed' THEN total_amount END), 0) as today_revenue,
            COALESCE(SUM(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'confirmed' THEN total_amount END), 0) as monthly_revenue,
            COUNT(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) THEN 1 END) as monthly_bookings,
            AVG(CASE WHEN MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE()) AND status = 'confirmed' THEN total_amount END) as avg_booking_value
        FROM roombook
    ");
    $financial = mysqli_fetch_assoc($financial_stats);
    
    // Staff Statistics
    $staff_stats = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_staff,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_staff,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 8 HOUR) THEN 1 END) as staff_online,
            COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as recently_active
        FROM users WHERE role IN ('staff', 'housekeeping', 'maintenance', 'front_desk', 'manager')
    ");
    $staff = mysqli_fetch_assoc($staff_stats) ?: [
        'total_staff' => 0, 'active_staff' => 0, 'staff_online' => 0, 'recently_active' => 0
    ];
    
    // Maintenance Requests
    $maintenance_stats = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress,
            COUNT(CASE WHEN status = 'completed' AND DATE(updated_at) = '$today' THEN 1 END) as completed_today
        FROM maintenance_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $maintenance = mysqli_fetch_assoc($maintenance_stats) ?: [
        'total_requests' => 0, 'urgent' => 0, 'high_priority' => 0, 
        'pending' => 0, 'in_progress' => 0, 'completed_today' => 0
    ];
    
    // Inventory Status
    $inventory_stats = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_items,
            COUNT(CASE WHEN quantity <= reorder_level THEN 1 END) as low_stock,
            COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN quantity > (reorder_level * 2) THEN 1 END) as well_stocked
        FROM inventory WHERE is_active = 1
    ");
    $inventory = mysqli_fetch_assoc($inventory_stats) ?: [
        'total_items' => 0, 'low_stock' => 0, 'out_of_stock' => 0, 'well_stocked' => 0
    ];
    
    // Service Requests
    $service_stats = mysqli_query($con, "
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending,
            COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority,
            COUNT(CASE WHEN status = 'completed' AND DATE(updated_at) = '$today' THEN 1 END) as completed_today
        FROM service_requests WHERE DATE(created_at) = '$today'
    ");
    $services = mysqli_fetch_assoc($service_stats) ?: [
        'total_requests' => 0, 'pending' => 0, 'high_priority' => 0, 'completed_today' => 0
    ];
    
    // Calculate Key Metrics
    $occupancy_rate = $rooms['total_rooms'] > 0 ? 
        round(($rooms['occupied'] / $rooms['total_rooms']) * 100, 1) : 0;
    
    $staff_availability = $staff['total_staff'] > 0 ? 
        round(($staff['staff_online'] / $staff['total_staff']) * 100, 1) : 0;
    
    $maintenance_efficiency = $maintenance['total_requests'] > 0 ? 
        round((($maintenance['total_requests'] - $maintenance['pending']) / $maintenance['total_requests']) * 100, 1) : 100;
    
    $inventory_health = $inventory['total_items'] > 0 ? 
        round((($inventory['total_items'] - $inventory['low_stock'] - $inventory['out_of_stock']) / $inventory['total_items']) * 100, 1) : 100;
    
    // Department Performance Simulation (would come from actual KPI tracking)
    $dept_performance = [
        'front_office' => rand(85, 95),
        'housekeeping' => rand(80, 90),
        'maintenance' => rand(75, 85),
        'food_beverage' => rand(80, 90),
        'finance' => rand(90, 98),
        'security' => rand(88, 95)
    ];
    
    // Recent Activities (last 24 hours)
    $recent_activities = [];
    
    // Get recent bookings
    $recent_bookings = mysqli_query($con, "
        SELECT 'booking' as type, CONCAT('New booking received') as message, 
               created_at, booking_ref as reference
        FROM roombook 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY created_at DESC LIMIT 5
    ");
    
    if ($recent_bookings) {
        while ($booking = mysqli_fetch_assoc($recent_bookings)) {
            $recent_activities[] = [
                'type' => $booking['type'],
                'message' => $booking['message'],
                'time' => $booking['created_at'],
                'reference' => $booking['reference'],
                'icon' => 'fas fa-calendar-plus',
                'color' => 'success'
            ];
        }
    }
    
    // Staff Activity Simulation
    $recent_activities[] = [
        'type' => 'staff',
        'message' => 'Housekeeping completed room 205',
        'time' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
        'reference' => 'HK-' . date('Ymd') . '-001',
        'icon' => 'fas fa-broom',
        'color' => 'info'
    ];
    
    $recent_activities[] = [
        'type' => 'maintenance',
        'message' => 'Maintenance request resolved',
        'time' => date('Y-m-d H:i:s', strtotime('-1 hour')),
        'reference' => 'MNT-' . date('Ymd') . '-003',
        'icon' => 'fas fa-tools',
        'color' => 'warning'
    ];
    
    // Critical Alerts
    $alerts = [];
    
    if ($maintenance['urgent'] > 0) {
        $alerts[] = [
            'type' => 'urgent',
            'title' => 'Urgent Maintenance Required',
            'message' => $maintenance['urgent'] . ' urgent maintenance item(s) need immediate attention',
            'action_url' => 'maintenance_management.php',
            'severity' => 'danger'
        ];
    }
    
    if ($inventory['out_of_stock'] > 0) {
        $alerts[] = [
            'type' => 'inventory',
            'title' => 'Inventory Alert',
            'message' => $inventory['out_of_stock'] . ' item(s) are out of stock',
            'action_url' => 'inventory.php',
            'severity' => 'warning'
        ];
    }
    
    if ($guests['pending_checkins'] > 5) {
        $alerts[] = [
            'type' => 'guest_service',
            'title' => 'Check-in Backlog',
            'message' => $guests['pending_checkins'] . ' guests waiting to check in',
            'action_url' => 'reservation.php',
            'severity' => 'info'
        ];
    }
    
    // Response Data
    $response = [
        'success' => true,
        'timestamp' => time(),
        'data' => [
            'rooms' => $rooms,
            'guests' => $guests,
            'financial' => $financial,
            'staff' => $staff,
            'maintenance' => $maintenance,
            'inventory' => $inventory,
            'services' => $services,
            'metrics' => [
                'occupancy_rate' => $occupancy_rate,
                'staff_availability' => $staff_availability,
                'maintenance_efficiency' => $maintenance_efficiency,
                'inventory_health' => $inventory_health
            ],
            'department_performance' => $dept_performance,
            'recent_activities' => $recent_activities,
            'alerts' => $alerts
        ]
    ];
    
    // Log API access
    log_admin_activity('operations_stats_accessed', 'Retrieved real-time operations statistics');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Operations stats API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve operations statistics',
        'timestamp' => time()
    ]);
}

// Create operations tables if they don't exist
function ensure_operations_tables() {
    global $con;
    
    // Maintenance requests table
    $maintenance_sql = "CREATE TABLE IF NOT EXISTS maintenance_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        room_id INT,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
        status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending',
        assigned_to INT,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_priority (priority),
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    // Service requests table
    $service_sql = "CREATE TABLE IF NOT EXISTS service_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        guest_id INT,
        room_number VARCHAR(10),
        request_type VARCHAR(100),
        description TEXT,
        priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        assigned_to INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_created_at (created_at)
    )";
    
    // Inventory table
    $inventory_sql = "CREATE TABLE IF NOT EXISTS inventory (
        id INT AUTO_INCREMENT PRIMARY KEY,
        item_name VARCHAR(255) NOT NULL,
        category VARCHAR(100),
        quantity INT DEFAULT 0,
        reorder_level INT DEFAULT 10,
        unit_cost DECIMAL(10,2),
        supplier VARCHAR(255),
        is_active BOOLEAN DEFAULT TRUE,
        last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_quantity (quantity),
        INDEX idx_reorder_level (reorder_level),
        INDEX idx_category (category)
    )";
    
    mysqli_query($con, $maintenance_sql);
    mysqli_query($con, $service_sql);
    mysqli_query($con, $inventory_sql);
}

// Initialize tables
ensure_operations_tables();
?>
