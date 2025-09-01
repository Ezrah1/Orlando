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

include 'db.php';
require_once 'auth.php';

// Check permissions
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

if ($user_role_id != 11 && $user_role_id != 1) {
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'IT_Admin', 'director', 'ceo', 'super_admin', 'it_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), $allowed_roles) && 
        !user_has_permission($con, 'user.read')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
}

if (isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $sql = "SELECT * FROM users WHERE id = $user_id";
    $result = mysqli_query($con, $sql);
    
    if ($user = mysqli_fetch_assoc($result)) {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'user' => $user]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'User not found']);
    }
} else {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID required']);
}
?>
