<?php
/**
 * Orlando International Resorts - Permission Check API
 * Real-time permission validation endpoint
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set JSON content type and headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: 0');

// CORS headers for development
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Include required files
    require_once '../includes/PermissionManager.php';
    require_once '../includes/SecurityFramework.php';
    require_once '../db.php';
    
    // Check if user is authenticated
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required',
            'code' => 'AUTH_REQUIRED'
        ]);
        exit;
    }
    
    // Get request data
    $request_method = $_SERVER['REQUEST_METHOD'];
    $input_data = null;
    
    if ($request_method === 'POST') {
        $input_data = json_decode(file_get_contents('php://input'), true);
    } else {
        $input_data = $_GET;
    }
    
    // Validate required parameters
    if (!isset($input_data['module'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Module parameter is required',
            'code' => 'MISSING_MODULE'
        ]);
        exit;
    }
    
    $module = $input_data['module'];
    $resource = $input_data['resource'] ?? '*';
    $permission = $input_data['permission'] ?? 'read';
    $check_type = $input_data['check_type'] ?? 'single';
    
    // Get permission manager
    $permission_manager = getPermissionManager();
    
    if (!$permission_manager) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Permission service unavailable',
            'code' => 'SERVICE_UNAVAILABLE'
        ]);
        exit;
    }
    
    $response = [
        'success' => true,
        'timestamp' => time(),
        'user_id' => $_SESSION['user_id'],
        'user_role' => $_SESSION['user_role'] ?? 'unknown'
    ];
    
    // Handle different check types
    switch ($check_type) {
        case 'single':
            $has_permission = $permission_manager->hasPermission($module, $resource, $permission);
            $response['permission_granted'] = $has_permission;
            $response['check'] = [
                'module' => $module,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $has_permission
            ];
            break;
            
        case 'multiple':
            if (!isset($input_data['permissions']) || !is_array($input_data['permissions'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Permissions array is required for multiple check',
                    'code' => 'MISSING_PERMISSIONS'
                ]);
                exit;
            }
            
            $permission_results = [];
            $all_granted = true;
            
            foreach ($input_data['permissions'] as $perm_check) {
                if (!is_array($perm_check) || count($perm_check) < 1) {
                    continue;
                }
                
                $check_module = $perm_check[0];
                $check_resource = $perm_check[1] ?? '*';
                $check_permission = $perm_check[2] ?? 'read';
                
                $granted = $permission_manager->hasPermission($check_module, $check_resource, $check_permission);
                
                $permission_results[] = [
                    'module' => $check_module,
                    'resource' => $check_resource,
                    'permission' => $check_permission,
                    'granted' => $granted
                ];
                
                if (!$granted) {
                    $all_granted = false;
                }
            }
            
            $response['permission_granted'] = $all_granted;
            $response['checks'] = $permission_results;
            break;
            
        case 'any':
            if (!isset($input_data['permissions']) || !is_array($input_data['permissions'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Permissions array is required for any check',
                    'code' => 'MISSING_PERMISSIONS'
                ]);
                exit;
            }
            
            $has_any = $permission_manager->hasAnyPermission($input_data['permissions']);
            $response['permission_granted'] = $has_any;
            $response['check_type'] = 'any';
            break;
            
        case 'all':
            if (!isset($input_data['permissions']) || !is_array($input_data['permissions'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Permissions array is required for all check',
                    'code' => 'MISSING_PERMISSIONS'
                ]);
                exit;
            }
            
            $has_all = $permission_manager->hasAllPermissions($input_data['permissions']);
            $response['permission_granted'] = $has_all;
            $response['check_type'] = 'all';
            break;
            
        case 'dashboard':
            $dashboard_type = $input_data['dashboard_type'] ?? 'general';
            $can_access = $permission_manager->canAccessDashboard($dashboard_type);
            $response['permission_granted'] = $can_access;
            $response['dashboard_type'] = $dashboard_type;
            break;
            
        case 'module_access':
            $accessible_modules = $permission_manager->getAccessibleModules();
            $response['permission_granted'] = in_array($module, $accessible_modules);
            $response['accessible_modules'] = $accessible_modules;
            break;
            
        case 'menu':
            $menu = $permission_manager->generateMenu();
            $response['permission_granted'] = true;
            $response['menu'] = $menu;
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid check type',
                'code' => 'INVALID_CHECK_TYPE',
                'valid_types' => ['single', 'multiple', 'any', 'all', 'dashboard', 'module_access', 'menu']
            ]);
            exit;
    }
    
    // Log permission check for auditing (if enabled)
    if (isset($input_data['log_check']) && $input_data['log_check']) {
        $security_framework = getSecurityFramework();
        if ($security_framework) {
            $log_details = [
                'check_type' => $check_type,
                'module' => $module,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $response['permission_granted']
            ];
            
            $security_framework->logActivity(
                SecurityFramework::ACTIVITY_PERMISSION_CHECK,
                'Permission check: ' . json_encode($log_details)
            );
        }
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    error_log("Permission Check API Error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'code' => 'INTERNAL_ERROR',
        'timestamp' => time()
    ]);
}
?>
