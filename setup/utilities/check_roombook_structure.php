<?php
include 'db.php';

echo "<h2>Checking roombook table structure</h2>";

// Check if roombook table exists
$sql = "SHOW TABLES LIKE 'roombook'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ roombook table exists</p>";
    
    // Show table structure
    echo "<h3>roombook table structure:</h3>";
    $sql = "DESCRIBE roombook";
    $result = mysqli_query($sql, "");
    
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>✗ roombook table does NOT exist</p>";
}

// Check if housekeeping_status column exists
$sql = "SHOW COLUMNS FROM roombook LIKE 'housekeeping_status'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ housekeeping_status column exists in roombook</p>";
} else {
    echo "<p style='color: red;'>✗ housekeeping_status column does NOT exist in roombook</p>";
}

// Check if maintenance_notes column exists
$sql = "SHOW COLUMNS FROM roombook LIKE 'maintenance_notes'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_notes column exists in roombook</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_notes column does NOT exist in roombook</p>";
}
?>
