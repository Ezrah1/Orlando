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
include 'db.php';

header('Content-Type: application/json');

try {
    // Get filter parameters
    $status_filter = $_GET['status'] ?? '';
    $order_type_filter = $_GET['order_type'] ?? '';
    $date_filter = $_GET['date'] ?? date('Y-m-d');
    $search = $_GET['search'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 100), 500); // Max 500 orders
    $offset = max((int)($_GET['offset'] ?? 0), 0);

    // Build where clause
    $where_conditions = ["DATE(fo.ordered_time) = '" . mysqli_real_escape_string($con, $date_filter) . "'"];

    if ($status_filter) {
        $where_conditions[] = "fo.status = '" . mysqli_real_escape_string($con, $status_filter) . "'";
    }

    if ($order_type_filter) {
        $where_conditions[] = "fo.order_type = '" . mysqli_real_escape_string($con, $order_type_filter) . "'";
    }

    if ($search) {
        $search_escaped = mysqli_real_escape_string($con, $search);
        $where_conditions[] = "(fo.order_number LIKE '%$search_escaped%' OR fo.guest_name LIKE '%$search_escaped%' OR fo.guest_phone LIKE '%$search_escaped%' OR fo.room_number LIKE '%$search_escaped%')";
    }

    $where_clause = implode(' AND ', $where_conditions);

    // Get orders with pagination
    $orders_query = "SELECT fo.*, 
                            COUNT(oi.id) as total_items,
                            SUM(oi.total_price) as calculated_total
                     FROM food_orders fo
                     LEFT JOIN order_items oi ON fo.id = oi.order_id
                     WHERE $where_clause
                     GROUP BY fo.id
                     ORDER BY fo.ordered_time DESC
                     LIMIT $limit OFFSET $offset";

    $orders_result = mysqli_query($con, $orders_query);

    if (!$orders_result) {
        throw new Exception('Database query failed: ' . mysqli_error($con));
    }

    $orders = [];
    while ($row = mysqli_fetch_assoc($orders_result)) {
        // Format the order data
        $row['ordered_time'] = date('Y-m-d H:i:s', strtotime($row['ordered_time']));
        if ($row['confirmed_time']) {
            $row['confirmed_time'] = date('Y-m-d H:i:s', strtotime($row['confirmed_time']));
        }
        if ($row['ready_time']) {
            $row['ready_time'] = date('Y-m-d H:i:s', strtotime($row['ready_time']));
        }
        if ($row['served_time']) {
            $row['served_time'] = date('Y-m-d H:i:s', strtotime($row['served_time']));
        }
        
        // Ensure numeric fields are properly typed
        $row['id'] = (int)$row['id'];
        $row['total_items'] = (int)($row['total_items'] ?? 0);
        $row['total_amount'] = (float)($row['total_amount'] ?? 0);
        $row['tax_amount'] = (float)($row['tax_amount'] ?? 0);
        $row['final_amount'] = (float)($row['final_amount'] ?? 0);
        
        $orders[] = $row;
    }

    // Get total count for pagination
    $count_query = "SELECT COUNT(DISTINCT fo.id) as total
                    FROM food_orders fo
                    LEFT JOIN order_items oi ON fo.id = oi.order_id
                    WHERE $where_clause";
    
    $count_result = mysqli_query($con, $count_query);
    $total_count = 0;
    
    if ($count_result) {
        $count_row = mysqli_fetch_assoc($count_result);
        $total_count = (int)$count_row['total'];
    }

    // Response
    echo json_encode([
        'success' => true,
        'orders' => $orders,
        'pagination' => [
            'total' => $total_count,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total_count
        ]
    ]);

} catch (Exception $e) {
    error_log("Orders data API error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ]);
}
?>
