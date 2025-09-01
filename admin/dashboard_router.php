<?php
/**
 * Dashboard Router - Redirects users to appropriate dashboard based on role
 * Orlando International Resorts Admin System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role_id'])) {
    header('Location: index.php');
    exit();
}

include('db.php');

// Get user's role information
$role_id = $_SESSION['user_role_id'];
$stmt = $con->prepare("SELECT name, description FROM roles WHERE id = ?");
$stmt->bind_param('i', $role_id);
$stmt->execute();
$result = $stmt->get_result();
$role_data = $result->fetch_assoc();

if (!$role_data) {
    // Role not found, redirect to login
    session_destroy();
    header('Location: index.php?error=invalid_role');
    exit();
}

$role_name = $role_data['name'];

/**
 * Dashboard mapping based on roles
 */
$dashboard_mapping = [
    'Admin' => 'home.php',
    'Staff' => 'staff_dashboard.php',
    'DeptManager' => 'management_dashboard.php',
    'Finance' => 'finance_dashboard.php',
    'Finance_Officer' => 'finance_dashboard.php',
    'Finance_Controller' => 'accounting_dashboard.php',
    'HR' => 'management_dashboard.php',
    'SalesMarketing' => 'management_dashboard.php'
];

// Get the appropriate dashboard
$dashboard_url = $dashboard_mapping[$role_name] ?? 'home.php';

// Store role info in session for easy access
$_SESSION['user_role_name'] = $role_name;
$_SESSION['user_role_description'] = $role_data['description'];

// Redirect to appropriate dashboard
header("Location: $dashboard_url");
exit();
?>
