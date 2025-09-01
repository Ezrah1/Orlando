<?php
/**
 * Orlando International Resorts - Widget Data API
 * Real-time widget data endpoint for dashboard updates
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

try {
    // Include required files
    require_once '../includes/dashboard-core.php';
    require_once '../security_config.php';
    
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'error' => 'Authentication required',
            'code' => 'AUTH_REQUIRED'
        ]);
        exit;
    }
    
    // Get widget ID from request
    $widget_id = $_GET['widget'] ?? $_POST['widget'] ?? null;
    
    if (!$widget_id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Widget ID is required',
            'code' => 'WIDGET_ID_MISSING'
        ]);
        exit;
    }
    
    // Validate widget ID (prevent directory traversal)
    if (!preg_match('/^[a-zA-Z0-9_-]+$/', $widget_id)) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Invalid widget ID format',
            'code' => 'INVALID_WIDGET_ID'
        ]);
        exit;
    }
    
    // Get dashboard manager instance
    $dashboard_manager = getDashboardManager();
    
    if (!$dashboard_manager) {
        http_response_code(500);
        echo json_encode([
            'error' => 'Dashboard service unavailable',
            'code' => 'SERVICE_UNAVAILABLE'
        ]);
        exit;
    }
    
    // Get widget data
    $widget_data = $dashboard_manager->getWidgetData($widget_id);
    
    // Add metadata
    $response = [
        'success' => true,
        'widget_id' => $widget_id,
        'data' => $widget_data,
        'timestamp' => time(),
        'user_role' => $_SESSION['user_role'] ?? 'unknown'
    ];
    
    // Log the request for monitoring
    error_log("Widget data request: {$widget_id} by user " . ($_SESSION['user_id'] ?? 'unknown'));
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Widget API Error: " . $e->getMessage());
    
    echo json_encode([
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR',
        'timestamp' => time()
    ]);
}
?>
