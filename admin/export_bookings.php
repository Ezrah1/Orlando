<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include 'db.php';

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$room_filter = $_GET['room'] ?? '';
$guest_search = $_GET['guest'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

// Build dynamic query with filters
$where_conditions = [];
$query_params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "rb.stat = ?";
    $query_params[] = $status_filter;
}

if (!empty($room_filter)) {
    $where_conditions[] = "rb.troom = ?";
    $query_params[] = $room_filter;
}

if (!empty($guest_search)) {
    $where_conditions[] = "(rb.FName LIKE ? OR rb.LName LIKE ? OR rb.Email LIKE ? OR rb.Phone LIKE ?)";
    $search_term = "%$guest_search%";
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

if (!empty($date_from)) {
    $where_conditions[] = "rb.cin >= ?";
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "rb.cout <= ?";
    $query_params[] = $date_to;
}

if (!empty($amount_min)) {
    $where_conditions[] = "(rb.nodays * COALESCE(nr.base_price, 0)) >= ?";
    $query_params[] = $amount_min;
}

if (!empty($amount_max)) {
    $where_conditions[] = "(rb.nodays * COALESCE(nr.base_price, 0)) <= ?";
    $query_params[] = $amount_max;
}

// Build the export query
$export_query = "
    SELECT 
        rb.id,
        rb.Title,
        rb.FName,
        rb.LName,
        rb.Email,
        rb.Phone,
        rb.troom,
        rb.cin,
        rb.cout,
        rb.nodays,
        rb.stat,
        rb.amount,
        nr.base_price,
        (rb.nodays * COALESCE(nr.base_price, 0)) as total_amount
    FROM roombook rb 
    LEFT JOIN named_rooms nr ON rb.troom = nr.room_name";

if (!empty($where_conditions)) {
    $export_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$export_query .= " ORDER BY rb.id DESC";

// Execute query with prepared statement if there are parameters
if (!empty($query_params)) {
    $stmt = mysqli_prepare($con, $export_query);
    if ($stmt) {
        $types = str_repeat('s', count($query_params));
        mysqli_stmt_bind_param($stmt, $types, ...$query_params);
        mysqli_stmt_execute($stmt);
        $export_result = mysqli_stmt_get_result($stmt);
    } else {
        $export_result = mysqli_query($con, $export_query);
    }
} else {
    $export_result = mysqli_query($con, $export_query);
}

// Set headers for CSV download
$filename = 'bookings_export_' . date('Y-m-d_H-i-s') . '.csv';
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper Excel encoding
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// CSV Headers
$headers = [
    'Booking ID',
    'Title',
    'First Name',
    'Last Name',
    'Email',
    'Phone',
    'Room',
    'Check-in Date',
    'Check-out Date',
    'Nights',
    'Status',
    'Amount',
    'Base Price',
    'Total Amount'
];
fputcsv($output, $headers);

// Export data
if ($export_result && mysqli_num_rows($export_result) > 0) {
    while ($row = mysqli_fetch_assoc($export_result)) {
        $csv_row = [
            $row['id'],
            $row['Title'],
            $row['FName'],
            $row['LName'],
            $row['Email'],
            $row['Phone'],
            $row['troom'],
            $row['cin'],
            $row['cout'],
            $row['nodays'],
            $row['stat'],
            $row['amount'],
            $row['base_price'],
            $row['total_amount']
        ];
        fputcsv($output, $csv_row);
    }
}

fclose($output);
exit();
?>
