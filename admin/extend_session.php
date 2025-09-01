<?php
/**
 * Session Extension API
 * Extends user session and updates activity timestamp
 */

define('ADMIN_ACCESS', true);
require_once 'auth.php';
require_once 'security_config.php';

// Ensure user is logged in
ensure_logged_in();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Extend session
        $_SESSION['last_activity'] = time();
        $_SESSION['login_time'] = time(); // Reset login time
        
        // Log the session extension
        log_admin_activity('session_extended', 'User extended their session');
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        echo json_encode([
            'success' => true,
            'message' => 'Session extended successfully',
            'expires_at' => time() + SESSION_TIMEOUT,
            'timestamp' => time()
        ]);
        
    } catch (Exception $e) {
        error_log("Session extension error: " . $e->getMessage());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Failed to extend session',
            'timestamp' => time()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'error' => 'Method not allowed',
        'timestamp' => time()
    ]);
}
?>
