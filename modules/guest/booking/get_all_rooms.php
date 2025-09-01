<?php
header('Content-Type: application/json');
require_once '../../../db.php';

try {
    // Get all active rooms
    $rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price DESC";
    $rooms_result = mysqli_query($con, $rooms_query);
    
    $rooms = [];
    while ($room = mysqli_fetch_assoc($rooms_result)) {
        $rooms[] = $room;
    }
    
    echo json_encode([
        'success' => true,
        'rooms' => $rooms
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Error fetching rooms: ' . $e->getMessage()
    ]);
}
?>
