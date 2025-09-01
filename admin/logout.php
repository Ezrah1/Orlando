<?php
/**
 * Secure Logout Handler
 * Orlando International Resorts Admin System
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout event (if user was logged in)
if (isset($_SESSION['user_id']) || isset($_SESSION['user'])) {
    $username = $_SESSION['user'] ?? 'Unknown';
    $user_id = $_SESSION['user_id'] ?? 'Unknown';
    
    // You can add logging here if needed
    error_log("User logout: $username (ID: $user_id) at " . date('Y-m-d H:i:s'));
}

// Clear all session variables
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Prevent caching of this page
header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Redirect to login page with logout confirmation
header("Location: index.php?logout=success");
exit();
?>