<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include database connection
include '../db.php';

header('Content-Type: application/json');

try {
    $action = $_GET['action'] ?? $_POST['action'] ?? '';
    
    switch ($action) {
        case 'get_new_orders':
            // Get orders from the last 5 minutes
            $query = "SELECT fo.*, COUNT(oi.id) as total_items
                     FROM food_orders fo
                     LEFT JOIN order_items oi ON fo.id = oi.order_id
                     WHERE fo.ordered_time >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                     AND fo.status = 'pending'
                     GROUP BY fo.id
                     ORDER BY fo.ordered_time DESC";
            
            $result = mysqli_query($con, $query);
            $new_orders = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $new_orders[] = [
                    'id' => $row['id'],
                    'order_number' => $row['order_number'],
                    'guest_name' => $row['guest_name'],
                    'order_type' => $row['order_type'],
                    'total_items' => $row['total_items'],
                    'final_amount' => $row['final_amount'],
                    'ordered_time' => $row['ordered_time'],
                    'room_number' => $row['room_number']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'new_orders' => $new_orders,
                'count' => count($new_orders)
            ]);
            break;
            
        case 'get_ready_orders':
            // Get orders that are ready for pickup/delivery
            $query = "SELECT fo.*, COUNT(oi.id) as total_items
                     FROM food_orders fo
                     LEFT JOIN order_items oi ON fo.id = oi.order_id
                     WHERE fo.status = 'ready'
                     AND fo.ready_time >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                     GROUP BY fo.id
                     ORDER BY fo.ready_time ASC";
            
            $result = mysqli_query($con, $query);
            $ready_orders = [];
            
            while ($row = mysqli_fetch_assoc($result)) {
                $ready_orders[] = [
                    'id' => $row['id'],
                    'order_number' => $row['order_number'],
                    'guest_name' => $row['guest_name'],
                    'order_type' => $row['order_type'],
                    'total_items' => $row['total_items'],
                    'ready_time' => $row['ready_time'],
                    'room_number' => $row['room_number'],
                    'guest_phone' => $row['guest_phone']
                ];
            }
            
            echo json_encode([
                'success' => true,
                'ready_orders' => $ready_orders,
                'count' => count($ready_orders)
            ]);
            break;
            
        case 'get_order_stats':
            // Get current order statistics
            $stats_query = "SELECT 
                               COUNT(*) as total_today,
                               SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                               SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
                               SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready,
                               SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served,
                               AVG(CASE WHEN status = 'served' AND confirmed_time IS NOT NULL AND served_time IS NOT NULL 
                                   THEN TIMESTAMPDIFF(MINUTE, confirmed_time, served_time) 
                                   ELSE NULL END) as avg_prep_time
                            FROM food_orders 
                            WHERE DATE(ordered_time) = CURDATE()";
            
            $stats_result = mysqli_query($con, $stats_query);
            $stats = mysqli_fetch_assoc($stats_result);
            
            echo json_encode([
                'success' => true,
                'stats' => [
                    'total_today' => (int)$stats['total_today'],
                    'pending' => (int)$stats['pending'],
                    'confirmed' => (int)$stats['confirmed'],
                    'ready' => (int)$stats['ready'],
                    'served' => (int)$stats['served'],
                    'avg_prep_time' => round($stats['avg_prep_time'] ?? 0, 1)
                ]
            ]);
            break;
            
        case 'mark_notification_read':
            $notification_id = (int)($_POST['notification_id'] ?? 0);
            
            if ($notification_id > 0) {
                $query = "UPDATE notifications SET is_read = 1 WHERE id = $notification_id AND user_id = " . $_SESSION['user_id'];
                mysqli_query($con, $query);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        case 'create_order_notification':
            $order_id = (int)($_POST['order_id'] ?? 0);
            $type = mysqli_real_escape_string($con, $_POST['type'] ?? '');
            $message = mysqli_real_escape_string($con, $_POST['message'] ?? '');
            
            if ($order_id > 0 && $type && $message) {
                // Create notification for kitchen staff (role_id = 3) and managers
                $query = "INSERT INTO notifications (user_id, type, title, message, related_id, created_at) 
                         SELECT u.id, '$type', 'New Order Alert', '$message', $order_id, NOW()
                         FROM users u 
                         WHERE u.role_id IN (1, 2, 3) AND u.is_active = 1";
                
                mysqli_query($con, $query);
            }
            
            echo json_encode(['success' => true]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Notifications API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}
?>
