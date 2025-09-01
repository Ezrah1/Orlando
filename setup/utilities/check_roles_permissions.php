<?php
include 'db.php';

echo "=== CURRENT ROLES ===\n";
$result = mysqli_query('SELECT * FROM roles', "");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($con) . "\n";
}

echo "\n=== CURRENT DEPARTMENTS ===\n";
$result = mysqli_query('SELECT * FROM departments', "");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($con) . "\n";
}

echo "\n=== CURRENT USERS ===\n";
$result = mysqli_query('SELECT u.*, r.name as role_name, d.name as dept_name FROM users u LEFT JOIN roles r ON u.role_id = r.id LEFT JOIN departments d ON u.dept_id = d.id', "");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($con) . "\n";
}

echo "\n=== CURRENT ROLE PERMISSIONS ===\n";
$result = mysqli_query('SELECT rp.*, r.name as role_name FROM role_permissions rp JOIN roles r ON rp.role_id = r.id ORDER BY r.name, rp.permission', "");
if ($result) {
    while($row = mysqli_fetch_assoc($result)) {
        print_r($row);
    }
} else {
    echo "Error: " . mysqli_error($con) . "\n";
}
?>
