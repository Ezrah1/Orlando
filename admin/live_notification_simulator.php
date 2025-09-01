<?php
/**
 * Live Notification Simulator
 * Generates realistic notifications based on current hotel activity
 */

include 'db.php';
include 'includes/NotificationEngine.php';

// Only allow direct access for testing
if (!isset($_GET['simulate']) || $_GET['simulate'] !== 'live') {
    http_response_code(403);
    die('Access denied');
}

$ne = getNotificationEngine();

if (!$ne) {
    die('Notification engine not available');
}

// Get current system state to generate realistic notifications
$current_time = date('H:i');
$current_hour = intval(date('H'));

// Check recent bookings
$recent_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
$booking_count = $recent_bookings ? mysqli_fetch_assoc($recent_bookings)['count'] : 0;

// Check today's check-ins
$todays_checkins = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE() AND stat = 'Confirm'");
$checkin_count = $todays_checkins ? mysqli_fetch_assoc($todays_checkins)['count'] : 0;

// Check pending payments
$pending_payments = mysqli_query($con, "SELECT COUNT(*) as count FROM payment WHERE payment_status = 'pending'");
$payment_count = $pending_payments ? mysqli_fetch_assoc($pending_payments)['count'] : 0;

// Generate notifications based on current state and time
$notifications_to_create = [];

// Morning notifications (6 AM - 12 PM)
if ($current_hour >= 6 && $current_hour < 12) {
    if ($checkin_count > 0) {
        $notifications_to_create[] = [
            'title' => 'Daily Check-in Reminder',
            'message' => "{$checkin_count} guests are checking in today. Please ensure rooms are ready.",
            'type' => 'info',
            'category' => 'booking',
            'priority' => 2,
            'is_broadcast' => 1
        ];
    }
    
    $notifications_to_create[] = [
        'title' => 'Daily Operations Started',
        'message' => 'Good morning! Hotel operations are active for the day.',
        'type' => 'success',
        'category' => 'system',
        'priority' => 1,
        'is_broadcast' => 1
    ];
}

// Afternoon notifications (12 PM - 6 PM)
if ($current_hour >= 12 && $current_hour < 18) {
    if ($booking_count > 0) {
        $notifications_to_create[] = [
            'title' => 'Recent Booking Activity',
            'message' => "{$booking_count} new booking(s) received in the last hour.",
            'type' => 'success',
            'category' => 'booking',
            'priority' => 2,
            'is_broadcast' => 1
        ];
    }
    
    if ($payment_count > 3) {
        $notifications_to_create[] = [
            'title' => 'Pending Payments Alert',
            'message' => "{$payment_count} payments are pending review and processing.",
            'type' => 'warning',
            'category' => 'payment',
            'priority' => 3,
            'is_broadcast' => 1
        ];
    }
}

// Evening notifications (6 PM - 11 PM)
if ($current_hour >= 18 && $current_hour < 23) {
    // Simulate room service orders
    $room_service_rooms = ['101', '205', '308', '412'];
    $random_room = $room_service_rooms[array_rand($room_service_rooms)];
    
    $notifications_to_create[] = [
        'title' => 'Room Service Request',
        'message' => "Room {$random_room} has requested dinner service.",
        'type' => 'info',
        'category' => 'housekeeping',
        'priority' => 2,
        'data' => [
            'room' => $random_room,
            'service_type' => 'dinner',
            'action_url' => 'room_service.php'
        ]
    ];
}

// Night notifications (11 PM - 6 AM)
if ($current_hour >= 23 || $current_hour < 6) {
    $notifications_to_create[] = [
        'title' => 'Night Audit Status',
        'message' => 'Night audit procedures are running automatically.',
        'type' => 'info',
        'category' => 'system',
        'priority' => 1
    ];
}

// Random maintenance alerts (any time)
if (rand(1, 4) === 1) { // 25% chance
    $maintenance_issues = [
        'Air conditioning unit requires filter replacement',
        'Elevator requires routine inspection',
        'Pool maintenance scheduled for tomorrow',
        'Kitchen equipment needs cleaning cycle',
        'Fire safety system monthly test due'
    ];
    
    $random_issue = $maintenance_issues[array_rand($maintenance_issues)];
    
    $notifications_to_create[] = [
        'title' => 'Maintenance Schedule',
        'message' => $random_issue,
        'type' => 'warning',
        'category' => 'maintenance',
        'priority' => 2,
        'is_broadcast' => 1
    ];
}

// Create the notifications
$created_count = 0;
foreach ($notifications_to_create as $notification) {
    try {
        $notification_id = $ne->createNotification($notification);
        if ($notification_id) {
            $created_count++;
            echo "Created: {$notification['title']}\n";
        }
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}

echo "\n=== Live Simulation Complete ===\n";
echo "Created {$created_count} live notifications at {$current_time}\n";
echo "Based on current hotel state:\n";
echo "- Recent bookings: {$booking_count}\n";
echo "- Today's check-ins: {$checkin_count}\n";
echo "- Pending payments: {$payment_count}\n";

// Return JSON for AJAX calls
if (isset($_GET['format']) && $_GET['format'] === 'json') {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'created_count' => $created_count,
        'time' => $current_time,
        'stats' => [
            'bookings' => $booking_count,
            'checkins' => $checkin_count,
            'payments' => $payment_count
        ]
    ]);
}
?>
