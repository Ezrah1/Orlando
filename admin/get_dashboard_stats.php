<?php
/**
 * Real-time Dashboard Statistics API
 * Returns JSON data for dashboard widgets and charts
 */

define('ADMIN_ACCESS', true);
require_once 'auth.php';
require_once 'security_config.php';

// Include the new dashboard core
require_once 'includes/dashboard-core.php';

// Ensure user is logged in
ensure_logged_in();

// Set JSON headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

try {
    // Try to use the new dashboard manager first
    $dashboard_manager = getDashboardManager();
    if ($dashboard_manager) {
        $dashboard_data = $dashboard_manager->getDashboardData();
        
        // Return the enhanced data format
        echo json_encode([
            'success' => true,
            'timestamp' => time(),
            'user_role' => $_SESSION['user_role'] ?? 'unknown',
            'data' => $dashboard_data,
            'enhanced' => true
        ], JSON_PRETTY_PRINT);
        
        // Log the API access
        log_admin_activity('dashboard_stats_accessed', 'Retrieved enhanced dashboard statistics');
        exit;
    }
    
    // Fallback to legacy implementation
    // Get current timestamp for cache busting
    $timestamp = time();
    
    // Get room statistics
    $room_stats_query = "SELECT 
        COUNT(*) as total_rooms,
        COUNT(CASE WHEN status = 'occupied' THEN 1 END) as occupied_rooms,
        COUNT(CASE WHEN status = 'available' THEN 1 END) as available_rooms,
        COUNT(CASE WHEN status = 'maintenance' THEN 1 END) as maintenance_rooms,
        COUNT(CASE WHEN status = 'cleaning' THEN 1 END) as cleaning_rooms
        FROM named_rooms WHERE is_active = 1";
    
    $room_result = mysqli_query($con, $room_stats_query);
    $room_stats = mysqli_fetch_assoc($room_result);
    
    // Calculate occupancy rate
    $occupancy_rate = $room_stats['total_rooms'] > 0 ? 
        round(($room_stats['occupied_rooms'] / $room_stats['total_rooms']) * 100, 1) : 0;
    
    // Get today's bookings
    $today_bookings_query = "SELECT 
        COUNT(*) as total_bookings,
        COUNT(CASE WHEN status = 'confirmed' THEN 1 END) as confirmed_bookings,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_bookings,
        COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_bookings,
        SUM(CASE WHEN status = 'confirmed' THEN total_amount ELSE 0 END) as today_revenue,
        AVG(CASE WHEN status = 'confirmed' THEN total_amount ELSE NULL END) as avg_booking_value
        FROM roombook 
        WHERE DATE(created_at) = CURDATE()";
    
    $booking_result = mysqli_query($con, $today_bookings_query);
    $booking_stats = mysqli_fetch_assoc($booking_result);
    
    // Get weekly revenue trend
    $weekly_revenue_query = "SELECT 
        DATE(created_at) as date,
        SUM(total_amount) as revenue,
        COUNT(*) as bookings
        FROM roombook 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) 
        AND status = 'confirmed'
        GROUP BY DATE(created_at)
        ORDER BY date ASC";
    
    $weekly_result = mysqli_query($con, $weekly_revenue_query);
    $weekly_data = [];
    while ($row = mysqli_fetch_assoc($weekly_result)) {
        $weekly_data[] = [
            'date' => $row['date'],
            'revenue' => floatval($row['revenue']),
            'bookings' => intval($row['bookings'])
        ];
    }
    
    // Get monthly statistics
    $monthly_stats_query = "SELECT 
        SUM(total_amount) as monthly_revenue,
        COUNT(*) as monthly_bookings,
        AVG(total_amount) as avg_monthly_booking
        FROM roombook 
        WHERE MONTH(created_at) = MONTH(CURDATE()) 
        AND YEAR(created_at) = YEAR(CURDATE()) 
        AND status = 'confirmed'";
    
    $monthly_result = mysqli_query($con, $monthly_stats_query);
    $monthly_stats = mysqli_fetch_assoc($monthly_result);
    
    // Get recent activities (last 24 hours)
    $activities_query = "SELECT 
        'booking' as type,
        CONCAT('New booking by ', name) as message,
        created_at,
        booking_ref as reference,
        total_amount
        FROM roombook 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        
        UNION ALL
        
        SELECT 
            'payment' as type,
            CONCAT('Payment received - ', payment_method) as message,
            payment_date as created_at,
            transaction_ref as reference,
            amount as total_amount
        FROM payment 
        WHERE payment_date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        AND status = 'completed'
        
        ORDER BY created_at DESC 
        LIMIT 10";
    
    $activities_result = mysqli_query($con, $activities_query);
    $recent_activities = [];
    while ($row = mysqli_fetch_assoc($activities_result)) {
        $recent_activities[] = [
            'type' => $row['type'],
            'message' => $row['message'],
            'created_at' => $row['created_at'],
            'reference' => $row['reference'],
            'amount' => floatval($row['total_amount'] ?? 0)
        ];
    }
    
    // Get pending orders (if restaurant module exists)
    $pending_orders = 0;
    $orders_result = mysqli_query($con, "SELECT COUNT(*) as count FROM orders WHERE status = 'pending'");
    if ($orders_result) {
        $orders_data = mysqli_fetch_assoc($orders_result);
        $pending_orders = intval($orders_data['count']);
    }
    
    // Get maintenance requests
    $maintenance_query = "SELECT 
        COUNT(*) as total_requests,
        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_requests,
        COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as active_requests
        FROM maintenance_requests 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
    
    $maintenance_result = mysqli_query($con, $maintenance_query);
    $maintenance_stats = $maintenance_result ? mysqli_fetch_assoc($maintenance_result) : [
        'total_requests' => 0,
        'pending_requests' => 0,
        'active_requests' => 0
    ];
    
    // Get check-ins and check-outs for today
    $checkin_query = "SELECT 
        COUNT(CASE WHEN DATE(cin) = CURDATE() THEN 1 END) as todays_checkins,
        COUNT(CASE WHEN DATE(cout) = CURDATE() THEN 1 END) as todays_checkouts,
        COUNT(CASE WHEN DATE(cin) = CURDATE() AND status != 'checked_in' THEN 1 END) as pending_checkins
        FROM roombook 
        WHERE (DATE(cin) = CURDATE() OR DATE(cout) = CURDATE())
        AND status IN ('confirmed', 'checked_in', 'checked_out')";
    
    $checkin_result = mysqli_query($con, $checkin_query);
    $checkin_stats = mysqli_fetch_assoc($checkin_result);
    
    // Calculate performance indicators
    $yesterday_revenue_query = "SELECT COALESCE(SUM(total_amount), 0) as yesterday_revenue 
        FROM roombook 
        WHERE DATE(created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) 
        AND status = 'confirmed'";
    
    $yesterday_result = mysqli_query($con, $yesterday_revenue_query);
    $yesterday_data = mysqli_fetch_assoc($yesterday_result);
    $yesterday_revenue = floatval($yesterday_data['yesterday_revenue']);
    $today_revenue = floatval($booking_stats['today_revenue']);
    
    $revenue_change = $yesterday_revenue > 0 ? 
        (($today_revenue - $yesterday_revenue) / $yesterday_revenue) * 100 : 0;
    
    // Compile response data
    $response = [
        'success' => true,
        'timestamp' => $timestamp,
        'statistics' => [
            'rooms' => [
                'total' => intval($room_stats['total_rooms']),
                'occupied' => intval($room_stats['occupied_rooms']),
                'available' => intval($room_stats['available_rooms']),
                'maintenance' => intval($room_stats['maintenance_rooms']),
                'cleaning' => intval($room_stats['cleaning_rooms']),
                'occupancy_rate' => $occupancy_rate
            ],
            'bookings' => [
                'today_total' => intval($booking_stats['total_bookings']),
                'today_confirmed' => intval($booking_stats['confirmed_bookings']),
                'today_pending' => intval($booking_stats['pending_bookings']),
                'today_cancelled' => intval($booking_stats['cancelled_bookings']),
                'today_revenue' => $today_revenue,
                'avg_booking_value' => floatval($booking_stats['avg_booking_value'] ?? 0),
                'revenue_change_percent' => round($revenue_change, 1)
            ],
            'monthly' => [
                'revenue' => floatval($monthly_stats['monthly_revenue'] ?? 0),
                'bookings' => intval($monthly_stats['monthly_bookings'] ?? 0),
                'avg_booking' => floatval($monthly_stats['avg_monthly_booking'] ?? 0)
            ],
            'operations' => [
                'todays_checkins' => intval($checkin_stats['todays_checkins']),
                'todays_checkouts' => intval($checkin_stats['todays_checkouts']),
                'pending_checkins' => intval($checkin_stats['pending_checkins']),
                'pending_orders' => $pending_orders,
                'maintenance_requests' => intval($maintenance_stats['pending_requests'])
            ]
        ],
        'charts' => [
            'weekly_revenue' => $weekly_data,
            'room_distribution' => [
                'occupied' => intval($room_stats['occupied_rooms']),
                'available' => intval($room_stats['available_rooms']),
                'maintenance' => intval($room_stats['maintenance_rooms']),
                'cleaning' => intval($room_stats['cleaning_rooms'])
            ]
        ],
        'recent_activities' => $recent_activities
    ];
    
    // Log the API access
    log_admin_activity('dashboard_stats_accessed', 'Retrieved real-time dashboard statistics');
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Log error
    error_log("Dashboard stats API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve dashboard statistics',
        'timestamp' => time()
    ]);
}
?>
