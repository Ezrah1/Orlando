<?php
/**
 * Real-time Notifications API
 * Returns JSON data for admin notifications
 */

define('ADMIN_ACCESS', true);
require_once 'auth.php';
require_once 'security_config.php';

// Ensure user is logged in
ensure_logged_in();

// Set JSON header
header('Content-Type: application/json');

try {
    $user_id = $_SESSION['user_id'] ?? 0;
    
    // Get unread notifications count
    $count_query = "SELECT COUNT(*) as count FROM notifications 
                    WHERE user_id = ? AND is_read = 0";
    $count_stmt = $con->prepare($count_query);
    $count_stmt->bind_param('i', $user_id);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $notification_count = $count_result->fetch_assoc()['count'];
    
    // Get recent notifications (last 50)
    $notifications_query = "SELECT id, title, message, type, is_read, created_at, action_url
                           FROM notifications 
                           WHERE user_id = ? 
                           ORDER BY created_at DESC 
                           LIMIT 50";
    $notifications_stmt = $con->prepare($notifications_query);
    $notifications_stmt->bind_param('i', $user_id);
    $notifications_stmt->execute();
    $notifications_result = $notifications_stmt->get_result();
    
    $notifications = [];
    while ($row = $notifications_result->fetch_assoc()) {
        $notifications[] = [
            'id' => intval($row['id']),
            'title' => $row['title'],
            'message' => $row['message'],
            'type' => $row['type'],
            'is_read' => boolval($row['is_read']),
            'created_at' => $row['created_at'],
            'action_url' => $row['action_url'],
            'time_ago' => time_ago($row['created_at'])
        ];
    }
    
    // Get system alerts (for all admins)
    $alerts_query = "SELECT id, title, message, type, created_at, action_url
                     FROM system_alerts 
                     WHERE is_active = 1 
                     AND (expires_at IS NULL OR expires_at > NOW())
                     ORDER BY priority DESC, created_at DESC 
                     LIMIT 10";
    $alerts_result = mysqli_query($con, $alerts_query);
    
    $system_alerts = [];
    if ($alerts_result) {
        while ($row = mysqli_fetch_assoc($alerts_result)) {
            $system_alerts[] = [
                'id' => intval($row['id']),
                'title' => $row['title'],
                'message' => $row['message'],
                'type' => $row['type'],
                'created_at' => $row['created_at'],
                'action_url' => $row['action_url'],
                'time_ago' => time_ago($row['created_at'])
            ];
        }
    }
    
    // Generate automatic notifications based on system state
    $auto_notifications = generate_auto_notifications();
    
    $response = [
        'success' => true,
        'count' => intval($notification_count),
        'notifications' => $notifications,
        'system_alerts' => $system_alerts,
        'auto_notifications' => $auto_notifications,
        'timestamp' => time()
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    error_log("Notifications API error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to retrieve notifications',
        'timestamp' => time()
    ]);
}

/**
 * Generate automatic notifications based on system conditions
 */
function generate_auto_notifications() {
    global $con;
    
    $notifications = [];
    
    // Check for rooms needing attention
    $room_check = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status = 'maintenance'");
    if ($room_check) {
        $maintenance_rooms = mysqli_fetch_assoc($room_check)['count'];
        if ($maintenance_rooms > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fas fa-tools',
                'title' => 'Rooms Under Maintenance',
                'message' => "{$maintenance_rooms} room(s) require maintenance attention",
                'action_url' => 'room.php',
                'priority' => 'medium'
            ];
        }
    }
    
    // Check for pending bookings
    $pending_check = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE status = 'pending' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    if ($pending_check) {
        $pending_bookings = mysqli_fetch_assoc($pending_check)['count'];
        if ($pending_bookings > 5) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fas fa-clock',
                'title' => 'Pending Bookings',
                'message' => "{$pending_bookings} booking(s) awaiting confirmation",
                'action_url' => 'reservation.php?status=pending',
                'priority' => 'medium'
            ];
        }
    }
    
    // Check for today's check-ins
    $checkin_check = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE() AND status = 'confirmed'");
    if ($checkin_check) {
        $todays_checkins = mysqli_fetch_assoc($checkin_check)['count'];
        if ($todays_checkins > 0) {
            $notifications[] = [
                'type' => 'success',
                'icon' => 'fas fa-calendar-check',
                'title' => 'Today\'s Check-ins',
                'message' => "{$todays_checkins} guest(s) checking in today",
                'action_url' => 'reservation.php?checkin_date=' . date('Y-m-d'),
                'priority' => 'high'
            ];
        }
    }
    
    // Check for expiring user sessions
    $user_count = mysqli_query($con, "SELECT COUNT(DISTINCT user_id) as count FROM admin_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    if ($user_count) {
        $active_users = mysqli_fetch_assoc($user_count)['count'];
        if ($active_users > 1) {
            $notifications[] = [
                'type' => 'info',
                'icon' => 'fas fa-users',
                'title' => 'Active Admin Users',
                'message' => "{$active_users} admin(s) currently active",
                'action_url' => 'user_management.php',
                'priority' => 'low'
            ];
        }
    }
    
    // Check for low inventory (if inventory module exists)
    $inventory_check = mysqli_query($con, "SELECT COUNT(*) as count FROM inventory WHERE quantity <= reorder_level AND is_active = 1");
    if ($inventory_check) {
        $low_inventory = mysqli_fetch_assoc($inventory_check)['count'];
        if ($low_inventory > 0) {
            $notifications[] = [
                'type' => 'warning',
                'icon' => 'fas fa-boxes',
                'title' => 'Low Inventory Alert',
                'message' => "{$low_inventory} item(s) below reorder level",
                'action_url' => 'inventory.php?filter=low_stock',
                'priority' => 'medium'
            ];
        }
    }
    
    return $notifications;
}

/**
 * Convert timestamp to human-readable time ago
 */
function time_ago($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'just now';
    if ($time < 3600) return floor($time/60) . ' minutes ago';
    if ($time < 86400) return floor($time/3600) . ' hours ago';
    if ($time < 2592000) return floor($time/86400) . ' days ago';
    if ($time < 31536000) return floor($time/2592000) . ' months ago';
    
    return floor($time/31536000) . ' years ago';
}

// Create notifications table if it doesn't exist
function ensure_notifications_tables() {
    global $con;
    
    $notifications_sql = "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        action_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_is_read (is_read),
        INDEX idx_created_at (created_at)
    )";
    
    $system_alerts_sql = "CREATE TABLE IF NOT EXISTS system_alerts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
        priority ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
        is_active BOOLEAN DEFAULT TRUE,
        action_url VARCHAR(255),
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_is_active (is_active),
        INDEX idx_priority (priority),
        INDEX idx_expires_at (expires_at)
    )";
    
    mysqli_query($con, $notifications_sql);
    mysqli_query($con, $system_alerts_sql);
}

// Initialize notification tables
ensure_notifications_tables();
?>
