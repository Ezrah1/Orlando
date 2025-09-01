<?php
/**
 * Generate sample notifications for testing
 * This script creates realistic notifications based on hotel operations
 */

include 'db.php';
include 'includes/NotificationEngine.php';

// Only allow direct access for testing
if (!isset($_GET['generate']) || $_GET['generate'] !== 'sample') {
    http_response_code(403);
    die('Access denied');
}

$ne = getNotificationEngine();

if (!$ne) {
    die('Notification engine not available');
}

// Sample notification data
$sample_notifications = [
    [
        'title' => 'New Booking Received',
        'message' => 'Room 201 has been booked for tonight by John Smith',
        'type' => 'success',
        'category' => 'booking',
        'priority' => 2,
        'is_broadcast' => 1,
        'data' => [
            'room' => '201',
            'guest' => 'John Smith',
            'action_url' => 'roombook.php'
        ]
    ],
    [
        'title' => 'Maintenance Request',
        'message' => 'AC unit in Room 305 requires immediate attention',
        'type' => 'warning',
        'category' => 'maintenance',
        'priority' => 3,
        'is_broadcast' => 1,
        'data' => [
            'room' => '305',
            'issue' => 'AC malfunction',
            'action_url' => 'maintenance.php'
        ]
    ],
    [
        'title' => 'Payment Received',
        'message' => 'Payment of $250 confirmed for Room 102',
        'type' => 'success',
        'category' => 'payment',
        'priority' => 2,
        'data' => [
            'amount' => 250,
            'room' => '102',
            'action_url' => 'finance_dashboard.php'
        ]
    ],
    [
        'title' => 'Check-in Reminder',
        'message' => '3 guests checking in within the next hour',
        'type' => 'info',
        'category' => 'booking',
        'priority' => 2,
        'is_broadcast' => 1,
        'data' => [
            'count' => 3,
            'action_url' => 'roombook.php?filter=checkin_today'
        ]
    ],
    [
        'title' => 'Low Inventory Alert',
        'message' => 'Towels are running low in housekeeping inventory',
        'type' => 'warning',
        'category' => 'inventory',
        'priority' => 3,
        'data' => [
            'item' => 'Towels',
            'current_stock' => 5,
            'action_url' => 'inventory.php'
        ]
    ],
    [
        'title' => 'System Backup Completed',
        'message' => 'Daily system backup completed successfully',
        'type' => 'success',
        'category' => 'system',
        'priority' => 1,
        'data' => [
            'backup_size' => '2.3GB',
            'action_url' => 'system_settings.php'
        ]
    ]
];

$created_count = 0;
$errors = [];

foreach ($sample_notifications as $notification) {
    try {
        $notification_id = $ne->createNotification($notification);
        if ($notification_id) {
            $created_count++;
            echo "Created notification: {$notification['title']} (ID: {$notification_id})\n";
        }
    } catch (Exception $e) {
        $errors[] = "Error creating '{$notification['title']}': " . $e->getMessage();
    }
}

// Also create some system alerts
$system_alerts = [
    [
        'title' => 'Server Maintenance Scheduled',
        'message' => 'System maintenance scheduled for tonight at 2:00 AM',
        'type' => 'warning',
        'category' => 'system',
        'priority' => 3,
        'is_broadcast' => 1,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+1 day'))
    ],
    [
        'title' => 'Staff Meeting Tomorrow',
        'message' => 'All department heads meeting at 9:00 AM in the conference room',
        'type' => 'info',
        'category' => 'staff',
        'priority' => 2,
        'is_broadcast' => 1,
        'expires_at' => date('Y-m-d H:i:s', strtotime('+2 days'))
    ]
];

foreach ($system_alerts as $alert) {
    try {
        $alert_id = $ne->createNotification($alert);
        if ($alert_id) {
            $created_count++;
            echo "Created system alert: {$alert['title']} (ID: {$alert_id})\n";
        }
    } catch (Exception $e) {
        $errors[] = "Error creating alert '{$alert['title']}': " . $e->getMessage();
    }
}

echo "\n=== Summary ===\n";
echo "Created {$created_count} notifications successfully\n";

if (!empty($errors)) {
    echo "Errors encountered:\n";
    foreach ($errors as $error) {
        echo "- {$error}\n";
    }
}

echo "\nTo view notifications, go to: notifications.php\n";
echo "To test the header dropdowns, refresh any admin page\n";
?>
