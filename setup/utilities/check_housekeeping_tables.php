<?php
include 'db.php';

echo "<h2>Checking Housekeeping Tables</h2>";

// Check if housekeeping_tasks table exists
$sql = "SHOW TABLES LIKE 'housekeeping_tasks'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ housekeeping_tasks table exists</p>";
    
    // Show table structure
    echo "<h3>housekeeping_tasks table structure:</h3>";
    $sql = "DESCRIBE housekeeping_tasks";
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
    echo "<p style='color: red;'>✗ housekeeping_tasks table does NOT exist</p>";
}

// Check if housekeeping_status table exists
$sql = "SHOW TABLES LIKE 'housekeeping_status'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ housekeeping_status table exists</p>";
} else {
    echo "<p style='color: red;'>✗ housekeeping_status table does NOT exist</p>";
}

// Check if laundry_services table exists
$sql = "SHOW TABLES LIKE 'laundry_services'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ laundry_services table exists</p>";
} else {
    echo "<p style='color: red;'>✗ laundry_services table does NOT exist</p>";
}

// Check if laundry_orders table exists
$sql = "SHOW TABLES LIKE 'laundry_orders'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ laundry_orders table exists</p>";
} else {
    echo "<p style='color: red;'>✗ laundry_orders table does NOT exist</p>";
}

// Check if maintenance_categories table exists
$sql = "SHOW TABLES LIKE 'maintenance_categories'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_categories table exists</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_categories table does NOT exist</p>";
}

// Check if maintenance_requests table exists
$sql = "SHOW TABLES LIKE 'maintenance_requests'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_requests table exists</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_requests table does NOT exist</p>";
}

// Check if maintenance_parts table exists
$sql = "SHOW TABLES LIKE 'maintenance_parts'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_parts table exists</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_parts table does NOT exist</p>";
}

// Check if maintenance_schedules table exists
$sql = "SHOW TABLES LIKE 'maintenance_schedules'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_schedules table exists</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_schedules table does NOT exist</p>";
}

// Check if maintenance_work_orders table exists
$sql = "SHOW TABLES LIKE 'maintenance_work_orders'";
$result = mysqli_query($sql, "");

if (mysqli_num_rows($result) > 0) {
    echo "<p style='color: green;'>✓ maintenance_work_orders table exists</p>";
} else {
    echo "<p style='color: red;'>✗ maintenance_work_orders table does NOT exist</p>";
}

echo "<h3>All tables in database:</h3>";
$sql = "SHOW TABLES";
$result = mysqli_query($sql, "");

echo "<ul>";
while ($row = mysqli_fetch_array($result)) {
    echo "<li>" . $row[0] . "</li>";
}
echo "</ul>";
?>
