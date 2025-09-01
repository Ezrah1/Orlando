<?php
/**
 * Orlando International Resorts - Authentication Middleware
 * Centralized authentication and authorization middleware
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Authentication Middleware Class
 * Handles request authentication, authorization, and security checks
 */
class AuthMiddleware {
    private $permission_manager;
    private $security_framework;
    private $config;
    
    public function __construct() {
        // Load required classes
        require_once __DIR__ . '/../includes/PermissionManager.php';
        require_once __DIR__ . '/../includes/SecurityFramework.php';
        
        $this->permission_manager = getPermissionManager();
        $this->security_framework = getSecurityFramework();
        
        $this->config = [
            'redirect_on_auth_failure' => '../index.php',
            'redirect_on_access_denied' => 'access_denied.php',
            'api_error_format' => 'json',
            'enable_csrf_protection' => true,
            'enable_rate_limiting' => true,
            'log_all_requests' => false
        ];
    }
    
    /**
     * Main middleware entry point
     */
    public function handle($options = []) {
        $options = array_merge([
            'require_auth' => true,
            'require_permissions' => [],
            'require_role' => null,
            'require_csrf' => false,
            'is_api' => false,
            'allow_ajax_only' => false,
            'custom_redirect' => null
        ], $options);
        
        try {
            // Step 1: Basic security checks
            $this->performSecurityChecks($options);
            
            // Step 2: Authentication check
            if ($options['require_auth']) {
                $this->checkAuthentication($options);
            }
            
            // Step 3: Authorization checks
            if (!empty($options['require_permissions'])) {
                $this->checkPermissions($options['require_permissions'], $options);
            }
            
            // Step 4: Role-based checks
            if ($options['require_role']) {
                $this->checkRole($options['require_role'], $options);
            }
            
            // Step 5: CSRF protection
            if ($options['require_csrf']) {
                $this->checkCSRF($options);
            }
            
            // Step 6: AJAX-only restriction
            if ($options['allow_ajax_only']) {
                $this->checkAjaxRequest($options);
            }
            
            // Step 7: Log request if enabled
            if ($this->config['log_all_requests']) {
                $this->logRequest($options);
            }
            
            return true;
            
        } catch (AuthException $e) {
            $this->handleAuthException($e, $options);
            return false;
        }
    }
    
    /**
     * Perform basic security checks
     */
    private function performSecurityChecks($options) {
        // Rate limiting check
        if ($this->config['enable_rate_limiting'] && $this->security_framework) {
            // Rate limiting is handled in SecurityFramework initialization
        }
        
        // IP whitelist check (if configured)
        if (!empty($this->config['ip_whitelist'])) {
            $client_ip = $this->getRealIpAddress();
            if (!in_array($client_ip, $this->config['ip_whitelist'])) {
                throw new AuthException('IP address not allowed', 'IP_DENIED');
            }
        }
        
        // User agent validation (basic bot protection)
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        if (empty($user_agent) || $this->isSuspiciousUserAgent($user_agent)) {
            $this->logSuspiciousActivity('Suspicious user agent: ' . $user_agent);
        }
    }
    
    /**
     * Check if user is authenticated
     */
    private function checkAuthentication($options) {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
            throw new AuthException('Authentication required', 'AUTH_REQUIRED');
        }
        
        // Check session validity
        if ($this->security_framework) {
            $time_remaining = $this->security_framework->getSessionTimeRemaining();
            if ($time_remaining <= 0) {
                throw new AuthException('Session expired', 'SESSION_EXPIRED');
            }
        }
        
        // Validate session token if available
        if (isset($_SESSION['session_token'])) {
            if (!$this->validateSessionToken($_SESSION['session_token'])) {
                throw new AuthException('Invalid session', 'INVALID_SESSION');
            }
        }
    }
    
    /**
     * Check user permissions
     */
    private function checkPermissions($required_permissions, $options) {
        if (!$this->permission_manager) {
            throw new AuthException('Permission service unavailable', 'SERVICE_ERROR');
        }
        
        // Handle different permission formats
        if (is_string($required_permissions)) {
            // Simple module.resource.permission format
            $parts = explode('.', $required_permissions);
            $module = $parts[0] ?? '';
            $resource = $parts[1] ?? '*';
            $permission = $parts[2] ?? 'read';
            
            if (!$this->permission_manager->hasPermission($module, $resource, $permission)) {
                throw new AuthException('Insufficient permissions', 'ACCESS_DENIED', [
                    'required' => $required_permissions,
                    'user_role' => $_SESSION['user_role'] ?? 'unknown'
                ]);
            }
        } elseif (is_array($required_permissions)) {
            // Array of permission checks
            if (isset($required_permissions['any'])) {
                // User needs ANY of these permissions
                if (!$this->permission_manager->hasAnyPermission($required_permissions['any'])) {
                    throw new AuthException('Insufficient permissions', 'ACCESS_DENIED');
                }
            } elseif (isset($required_permissions['all'])) {
                // User needs ALL of these permissions
                if (!$this->permission_manager->hasAllPermissions($required_permissions['all'])) {
                    throw new AuthException('Insufficient permissions', 'ACCESS_DENIED');
                }
            } else {
                // Treat as array of individual permissions (all required)
                foreach ($required_permissions as $perm) {
                    if (is_array($perm) && count($perm) >= 2) {
                        $module = $perm[0];
                        $resource = $perm[1] ?? '*';
                        $permission = $perm[2] ?? 'read';
                        
                        if (!$this->permission_manager->hasPermission($module, $resource, $permission)) {
                            throw new AuthException('Insufficient permissions', 'ACCESS_DENIED');
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Check user role
     */
    private function checkRole($required_role, $options) {
        $user_role = $_SESSION['user_role'] ?? '';
        
        if (is_string($required_role)) {
            $required_roles = [$required_role];
        } else {
            $required_roles = $required_role;
        }
        
        if (!in_array($user_role, $required_roles)) {
            // Check role hierarchy if permission manager is available
            if ($this->permission_manager) {
                $role_allowed = false;
                foreach ($required_roles as $role) {
                    if ($this->permission_manager->isInRole($role)) {
                        $role_allowed = true;
                        break;
                    }
                }
                
                if (!$role_allowed) {
                    throw new AuthException('Role not authorized', 'ROLE_DENIED', [
                        'required_roles' => $required_roles,
                        'user_role' => $user_role
                    ]);
                }
            } else {
                throw new AuthException('Role not authorized', 'ROLE_DENIED');
            }
        }
    }
    
    /**
     * Check CSRF token
     */
    private function checkCSRF($options) {
        if (!$this->config['enable_csrf_protection']) {
            return;
        }
        
        $request_method = $_SERVER['REQUEST_METHOD'];
        
        // CSRF check only for state-changing requests
        if (in_array($request_method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $token = null;
            
            // Get token from different sources
            if (isset($_POST['csrf_token'])) {
                $token = $_POST['csrf_token'];
            } elseif (isset($_SERVER['HTTP_X_CSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_CSRF_TOKEN'];
            } elseif (isset($_SERVER['HTTP_X_XSRF_TOKEN'])) {
                $token = $_SERVER['HTTP_X_XSRF_TOKEN'];
            }
            
            if (!$token || !$this->security_framework || !$this->security_framework->validateCSRFToken($token)) {
                throw new AuthException('CSRF token validation failed', 'CSRF_INVALID');
            }
        }
    }
    
    /**
     * Check if request is AJAX
     */
    private function checkAjaxRequest($options) {
        $is_ajax = (
            isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) || (
            isset($_SERVER['CONTENT_TYPE']) && 
            strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false
        );
        
        if (!$is_ajax) {
            throw new AuthException('AJAX request required', 'AJAX_REQUIRED');
        }
    }
    
    /**
     * Validate session token
     */
    private function validateSessionToken($token) {
        if (!$token || strlen($token) < 32) {
            return false;
        }
        
        // Additional token validation can be added here
        return true;
    }
    
    /**
     * Check for suspicious user agents
     */
    private function isSuspiciousUserAgent($user_agent) {
        $suspicious_patterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i'
        ];
        
        foreach ($suspicious_patterns as $pattern) {
            if (preg_match($pattern, $user_agent)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Log suspicious activity
     */
    private function logSuspiciousActivity($message) {
        if ($this->security_framework) {
            $this->security_framework->logActivity(
                SecurityFramework::ACTIVITY_ACCESS_DENIED,
                $message
            );
        }
    }
    
    /**
     * Log request
     */
    private function logRequest($options) {
        if ($this->security_framework) {
            $request_info = [
                'method' => $_SERVER['REQUEST_METHOD'],
                'uri' => $_SERVER['REQUEST_URI'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'ip' => $this->getRealIpAddress(),
                'options' => $options
            ];
            
            $this->security_framework->logActivity(
                SecurityFramework::ACTIVITY_DATA_ACCESS,
                'Request logged: ' . json_encode($request_info)
            );
        }
    }
    
    /**
     * Handle authentication exceptions
     */
    private function handleAuthException($exception, $options) {
        $error_code = $exception->getCode();
        $error_message = $exception->getMessage();
        $error_data = $exception->getData();
        
        // Log the authentication failure
        if ($this->security_framework) {
            $this->security_framework->logActivity(
                SecurityFramework::ACTIVITY_ACCESS_DENIED,
                "$error_code: $error_message"
            );
        }
        
        // Handle API vs web requests differently
        if ($options['is_api']) {
            $this->handleApiError($error_code, $error_message, $error_data);
        } else {
            $this->handleWebError($error_code, $error_message, $options);
        }
    }
    
    /**
     * Handle API errors
     */
    private function handleApiError($error_code, $error_message, $error_data = null) {
        $status_codes = [
            'AUTH_REQUIRED' => 401,
            'SESSION_EXPIRED' => 401,
            'INVALID_SESSION' => 401,
            'ACCESS_DENIED' => 403,
            'ROLE_DENIED' => 403,
            'CSRF_INVALID' => 403,
            'AJAX_REQUIRED' => 400,
            'IP_DENIED' => 403,
            'SERVICE_ERROR' => 500
        ];
        
        $status_code = $status_codes[$error_code] ?? 403;
        
        http_response_code($status_code);
        header('Content-Type: application/json');
        
        $response = [
            'success' => false,
            'error' => $error_message,
            'code' => $error_code,
            'timestamp' => time()
        ];
        
        if ($error_data) {
            $response['details'] = $error_data;
        }
        
        echo json_encode($response);
        exit;
    }
    
    /**
     * Handle web errors
     */
    private function handleWebError($error_code, $error_message, $options) {
        $redirect_url = null;
        
        switch ($error_code) {
            case 'AUTH_REQUIRED':
            case 'SESSION_EXPIRED':
            case 'INVALID_SESSION':
                $redirect_url = $options['custom_redirect'] ?? $this->config['redirect_on_auth_failure'];
                break;
                
            case 'ACCESS_DENIED':
            case 'ROLE_DENIED':
            case 'CSRF_INVALID':
                $redirect_url = $this->config['redirect_on_access_denied'];
                break;
                
            default:
                $redirect_url = $this->config['redirect_on_access_denied'];
        }
        
        // Store error message in session for display
        $_SESSION['auth_error'] = $error_message;
        $_SESSION['auth_error_code'] = $error_code;
        
        // Redirect
        header("Location: $redirect_url");
        exit;
    }
    
    /**
     * Get real IP address
     */
    private function getRealIpAddress() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}

/**
 * Authentication Exception Class
 */
class AuthException extends Exception {
    private $data;
    
    public function __construct($message, $code = 'AUTH_ERROR', $data = null) {
        parent::__construct($message, 0);
        $this->code = $code;
        $this->data = $data;
    }
    
    public function getData() {
        return $this->data;
    }
}

/**
 * Global middleware functions
 */
function auth_middleware($options = []) {
    $middleware = new AuthMiddleware();
    return $middleware->handle($options);
}

function require_auth($permissions = null, $options = []) {
    $middleware_options = array_merge($options, [
        'require_auth' => true,
        'require_permissions' => $permissions
    ]);
    
    return auth_middleware($middleware_options);
}

function require_role($roles, $options = []) {
    $middleware_options = array_merge($options, [
        'require_auth' => true,
        'require_role' => $roles
    ]);
    
    return auth_middleware($middleware_options);
}

function require_api_auth($permissions = null, $options = []) {
    $middleware_options = array_merge($options, [
        'require_auth' => true,
        'require_permissions' => $permissions,
        'is_api' => true,
        'allow_ajax_only' => true
    ]);
    
    return auth_middleware($middleware_options);
}

function require_csrf($options = []) {
    $middleware_options = array_merge($options, [
        'require_auth' => true,
        'require_csrf' => true
    ]);
    
    return auth_middleware($middleware_options);
}

// Quick permission checks
function ensure_permission($module, $resource = '*', $permission = 'read') {
    return require_auth([[$module, $resource, $permission]]);
}

function ensure_role($roles) {
    return require_role($roles);
}

function ensure_admin() {
    return require_role(['super_admin', 'director', 'it_admin']);
}

function ensure_financial_access() {
    return require_auth([['finance', '*', 'read']]);
}

function ensure_operations_access() {
    return require_auth([['operations', '*', 'read']]);
}
?>
