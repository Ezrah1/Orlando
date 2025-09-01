<?php
require_once 'db.php';

echo "<h2>Database Test</h2>";

// Test if named_rooms table exists
$result = mysqli_query($con, "SHOW TABLES LIKE 'named_rooms'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ named_rooms table exists</p>";
    
    // Count rooms
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms");
    $row = mysqli_fetch_assoc($result);
    echo "<p>Number of rooms: " . $row['count'] . "</p>";
    
    // Show first few rooms
    $result = mysqli_query($con, "SELECT room_name, base_price FROM named_rooms LIMIT 5");
    echo "<p>Sample rooms:</p><ul>";
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<li>" . $row['room_name'] . " - KES " . number_format($row['base_price']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ named_rooms table does not exist</p>";
}

// Test if roombook table has new columns
$result = mysqli_query($con, "SHOW COLUMNS FROM roombook LIKE 'discount'");
if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ discount column exists in roombook</p>";
} else {
    echo "<p style='color: red;'>✗ discount column does not exist in roombook</p>";
}
?>
