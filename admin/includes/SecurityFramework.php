<?php
/**
 * Orlando International Resorts - Security Framework
 * Enterprise-grade security with session management and audit logging
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
 * Security Framework Class
 * Handles authentication, session security, and audit logging
 */
class SecurityFramework {
    private $db;
    private $config;
    private static $instance = null;
    
    // Security constants
    const MAX_LOGIN_ATTEMPTS = 5;
    const LOCKOUT_DURATION = 900; // 15 minutes
    const SESSION_TIMEOUT = 1800; // 30 minutes
    const CSRF_TOKEN_LENGTH = 32;
    const PASSWORD_MIN_LENGTH = 8;
    
    // Activity types for logging
    const ACTIVITY_LOGIN = 'login';
    const ACTIVITY_LOGOUT = 'logout';
    const ACTIVITY_ACCESS_DENIED = 'access_denied';
    const ACTIVITY_PASSWORD_CHANGE = 'password_change';
    const ACTIVITY_PERMISSION_CHECK = 'permission_check';
    const ACTIVITY_DATA_ACCESS = 'data_access';
    const ACTIVITY_SYSTEM_CONFIG = 'system_config';
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->loadSecurityConfig();
        $this->initializeSecurity();
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance($database_connection = null) {
        if (self::$instance === null) {
            if ($database_connection === null) {
                throw new Exception('Database connection required for first initialization');
            }
            self::$instance = new self($database_connection);
        }
        return self::$instance;
    }
    
    /**
     * Load security configuration
     */
    private function loadSecurityConfig() {
        $this->config = [
            'session_timeout' => self::SESSION_TIMEOUT,
            'max_login_attempts' => self::MAX_LOGIN_ATTEMPTS,
            'lockout_duration' => self::LOCKOUT_DURATION,
            'password_min_length' => self::PASSWORD_MIN_LENGTH,
            'require_https' => false, // Set to true in production
            'ip_whitelist' => [], // Add IP restrictions if needed
            'audit_level' => 'full', // basic, medium, full
            'rate_limit_enabled' => true,
            'csrf_protection' => true
        ];
    }
    
    /**
     * Initialize security measures
     */
    private function initializeSecurity() {
        // Set secure session parameters
        $this->configureSession();
        
        // Set security headers
        $this->setSecurityHeaders();
        
        // Check session validity
        $this->validateSession();
        
        // Rate limiting
        if ($this->config['rate_limit_enabled']) {
            $this->checkRateLimit();
        }
    }
    
    /**
     * Configure secure session parameters
     */
    private function configureSession() {
        // Configure session security
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', $this->config['require_https'] ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Set session timeout
        ini_set('session.gc_maxlifetime', $this->config['session_timeout']);
        
        // Regenerate session ID periodically
        if (isset($_SESSION['last_regeneration'])) {
            if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutes
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        } else {
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Set security headers
     */
    private function setSecurityHeaders() {
        // Prevent XSS attacks
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; img-src 'self' data: https:; font-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // HTTPS enforcement (if enabled)
        if ($this->config['require_https'] && !isset($_SERVER['HTTPS'])) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
    
    /**
     * Validate current session
     */
    private function validateSession() {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            return;
        }
        
        // Check session timeout
        if (isset($_SESSION['last_activity'])) {
            if (time() - $_SESSION['last_activity'] > $this->config['session_timeout']) {
                $this->logActivity(self::ACTIVITY_LOGOUT, 'Session timeout');
                $this->destroySession();
                return;
            }
        }
        
        // Update last activity
        $_SESSION['last_activity'] = time();
        
        // Validate session against database
        $this->validateSessionToken();
        
        // Check for session hijacking
        $this->detectSessionHijacking();
    }
    
    /**
     * Validate session token against database
     */
    private function validateSessionToken() {
        if (!isset($_SESSION['session_token']) || !isset($_SESSION['user_id'])) {
            return;
        }
        
        try {
            $query = "SELECT session_token, last_activity, ip_address 
                     FROM user_sessions 
                     WHERE user_id = ? AND session_token = ? AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, 'Invalid session token');
                $this->destroySession();
                return;
            }
            
            // Update session activity in database
            $update_query = "UPDATE user_sessions 
                           SET last_activity = NOW() 
                           WHERE user_id = ? AND session_token = ?";
            
            $stmt = $this->db->prepare($update_query);
            $stmt->bind_param("is", $_SESSION['user_id'], $_SESSION['session_token']);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
        }
    }
    
    /**
     * Detect potential session hijacking
     */
    private function detectSessionHijacking() {
        // Check IP address consistency
        if (isset($_SESSION['ip_address'])) {
            $current_ip = $this->getRealIpAddress();
            if ($_SESSION['ip_address'] !== $current_ip) {
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, 'IP address mismatch - possible session hijacking');
                $this->destroySession();
                return;
            }
        } else {
            $_SESSION['ip_address'] = $this->getRealIpAddress();
        }
        
        // Check User-Agent consistency
        if (isset($_SESSION['user_agent'])) {
            $current_ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
            if ($_SESSION['user_agent'] !== $current_ua) {
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, 'User agent mismatch - possible session hijacking');
                $this->destroySession();
                return;
            }
        } else {
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        }
    }
    
    /**
     * Authenticate user login
     */
    public function authenticateUser($username, $password) {
        // Check for account lockout
        if ($this->isAccountLocked($username)) {
            $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Login attempt on locked account: $username");
            return [
                'success' => false,
                'message' => 'Account is temporarily locked due to multiple failed attempts.',
                'locked_until' => $this->getLockoutExpiry($username)
            ];
        }
        
        // Rate limiting check
        if (!$this->checkLoginRateLimit($username)) {
            $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Rate limit exceeded for: $username");
            return [
                'success' => false,
                'message' => 'Too many login attempts. Please try again later.'
            ];
        }
        
        try {
            // Get user from database
            $query = "SELECT id, username, password, email, full_name, role, status, 
                            failed_login_attempts, last_failed_login 
                     FROM users 
                     WHERE (username = ? OR email = ?) AND status = 'active'";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $this->recordFailedLogin($username);
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Login attempt with invalid username: $username");
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            $user = $result->fetch_assoc();
            
            // Verify password
            if (!$this->verifyPassword($password, $user['password'])) {
                $this->recordFailedLogin($username, $user['id']);
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Invalid password for user: $username", $user['id']);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password.'
                ];
            }
            
            // Reset failed login attempts
            $this->resetFailedLoginAttempts($user['id']);
            
            // Create new session
            $session_token = $this->createUserSession($user);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['session_token'] = $session_token;
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['ip_address'] = $this->getRealIpAddress();
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            
            // Log successful login
            $this->logActivity(self::ACTIVITY_LOGIN, "Successful login", $user['id']);
            
            // Update last login time
            $this->updateLastLogin($user['id']);
            
            return [
                'success' => true,
                'message' => 'Login successful.',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'full_name' => $user['full_name']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Login failed due to system error.'
            ];
        }
    }
    
    /**
     * Create user session in database
     */
    private function createUserSession($user) {
        $session_token = bin2hex(random_bytes(32));
        $ip_address = $this->getRealIpAddress();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            // Deactivate old sessions
            $deactivate_query = "UPDATE user_sessions 
                               SET is_active = 0 
                               WHERE user_id = ?";
            $stmt = $this->db->prepare($deactivate_query);
            $stmt->bind_param("i", $user['id']);
            $stmt->execute();
            
            // Create new session
            $create_query = "INSERT INTO user_sessions 
                           (user_id, session_token, ip_address, user_agent, created_at, last_activity, is_active) 
                           VALUES (?, ?, ?, ?, NOW(), NOW(), 1)";
            
            $stmt = $this->db->prepare($create_query);
            $stmt->bind_param("isss", $user['id'], $session_token, $ip_address, $user_agent);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Session creation error: " . $e->getMessage());
        }
        
        return $session_token;
    }
    
    /**
     * Verify password (supports both legacy and new hashing)
     */
    private function verifyPassword($password, $hash) {
        // Try modern password_verify first
        if (password_verify($password, $hash)) {
            return true;
        }
        
        // Fallback to legacy verification (MD5/SHA1)
        if (md5($password) === $hash || sha1($password) === $hash) {
            return true;
        }
        
        // Plain text comparison (for testing only - should be removed in production)
        if ($password === $hash) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Hash password securely
     */
    public function hashPassword($password) {
        return password_hash($password, PASSWORD_ARGON2ID);
    }
    
    /**
     * Check if account is locked
     */
    private function isAccountLocked($username) {
        try {
            $query = "SELECT failed_login_attempts, last_failed_login 
                     FROM users 
                     WHERE (username = ? OR email = ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                return false;
            }
            
            $user = $result->fetch_assoc();
            $failed_attempts = intval($user['failed_login_attempts']);
            $last_failed = strtotime($user['last_failed_login']);
            
            if ($failed_attempts >= $this->config['max_login_attempts']) {
                $lockout_expiry = $last_failed + $this->config['lockout_duration'];
                return time() < $lockout_expiry;
            }
            
        } catch (Exception $e) {
            error_log("Account lock check error: " . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get lockout expiry time
     */
    private function getLockoutExpiry($username) {
        try {
            $query = "SELECT last_failed_login 
                     FROM users 
                     WHERE (username = ? OR email = ?)";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("ss", $username, $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $user = $result->fetch_assoc();
                $last_failed = strtotime($user['last_failed_login']);
                return $last_failed + $this->config['lockout_duration'];
            }
            
        } catch (Exception $e) {
            error_log("Lockout expiry check error: " . $e->getMessage());
        }
        
        return time();
    }
    
    /**
     * Record failed login attempt
     */
    private function recordFailedLogin($username, $user_id = null) {
        try {
            if ($user_id) {
                // Update existing user record
                $query = "UPDATE users 
                         SET failed_login_attempts = failed_login_attempts + 1, 
                             last_failed_login = NOW() 
                         WHERE id = ?";
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("i", $user_id);
            } else {
                // Try to find user first
                $find_query = "SELECT id FROM users WHERE (username = ? OR email = ?)";
                $stmt = $this->db->prepare($find_query);
                $stmt->bind_param("ss", $username, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $user = $result->fetch_assoc();
                    $query = "UPDATE users 
                             SET failed_login_attempts = failed_login_attempts + 1, 
                                 last_failed_login = NOW() 
                             WHERE id = ?";
                    $stmt = $this->db->prepare($query);
                    $stmt->bind_param("i", $user['id']);
                } else {
                    // Log attempt even for non-existent users
                    $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Failed login for non-existent user: $username");
                    return;
                }
            }
            
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Failed login recording error: " . $e->getMessage());
        }
    }
    
    /**
     * Reset failed login attempts
     */
    private function resetFailedLoginAttempts($user_id) {
        try {
            $query = "UPDATE users 
                     SET failed_login_attempts = 0, 
                         last_failed_login = NULL 
                     WHERE id = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Reset failed login attempts error: " . $e->getMessage());
        }
    }
    
    /**
     * Update last login time
     */
    private function updateLastLogin($user_id) {
        try {
            $query = "UPDATE users SET last_login = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Update last login error: " . $e->getMessage());
        }
    }
    
    /**
     * Logout user
     */
    public function logoutUser() {
        $user_id = $_SESSION['user_id'] ?? null;
        
        if ($user_id) {
            $this->logActivity(self::ACTIVITY_LOGOUT, "User logout", $user_id);
            
            // Deactivate session in database
            $this->deactivateUserSession($user_id);
        }
        
        $this->destroySession();
    }
    
    /**
     * Deactivate user session in database
     */
    private function deactivateUserSession($user_id) {
        try {
            $session_token = $_SESSION['session_token'] ?? '';
            
            $query = "UPDATE user_sessions 
                     SET is_active = 0, 
                         logout_time = NOW() 
                     WHERE user_id = ? AND session_token = ?";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("is", $user_id, $session_token);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Session deactivation error: " . $e->getMessage());
        }
    }
    
    /**
     * Destroy session completely
     */
    private function destroySession() {
        // Clear session data
        $_SESSION = [];
        
        // Delete session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(self::CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        $ip = $this->getRealIpAddress();
        $current_time = time();
        $window = 60; // 1 minute window
        $max_requests = 100; // Max requests per window
        
        try {
            // Clean old entries
            $clean_query = "DELETE FROM rate_limit_log 
                           WHERE request_time < ?";
            $stmt = $this->db->prepare($clean_query);
            $cleanup_time = $current_time - $window;
            $stmt->bind_param("i", $cleanup_time);
            $stmt->execute();
            
            // Count recent requests
            $count_query = "SELECT COUNT(*) as request_count 
                           FROM rate_limit_log 
                           WHERE ip_address = ? AND request_time > ?";
            $stmt = $this->db->prepare($count_query);
            $stmt->bind_param("si", $ip, $cleanup_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $count_data = $result->fetch_assoc();
            
            if ($count_data['request_count'] >= $max_requests) {
                $this->logActivity(self::ACTIVITY_ACCESS_DENIED, "Rate limit exceeded from IP: $ip");
                http_response_code(429);
                die('Rate limit exceeded. Please try again later.');
            }
            
            // Log current request
            $log_query = "INSERT INTO rate_limit_log (ip_address, request_time) VALUES (?, ?)";
            $stmt = $this->db->prepare($log_query);
            $stmt->bind_param("si", $ip, $current_time);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Rate limit check error: " . $e->getMessage());
        }
    }
    
    /**
     * Check login-specific rate limiting
     */
    private function checkLoginRateLimit($username) {
        $ip = $this->getRealIpAddress();
        $current_time = time();
        $window = 300; // 5 minute window
        $max_attempts = 10; // Max login attempts per window
        
        try {
            $count_query = "SELECT COUNT(*) as attempt_count 
                           FROM login_attempts 
                           WHERE (ip_address = ? OR username = ?) 
                           AND attempt_time > ?";
            
            $stmt = $this->db->prepare($count_query);
            $cutoff_time = $current_time - $window;
            $stmt->bind_param("ssi", $ip, $username, $cutoff_time);
            $stmt->execute();
            $result = $stmt->get_result();
            $count_data = $result->fetch_assoc();
            
            return $count_data['attempt_count'] < $max_attempts;
            
        } catch (Exception $e) {
            error_log("Login rate limit check error: " . $e->getMessage());
            return true; // Allow on error
        }
    }
    
    /**
     * Log security activity
     */
    public function logActivity($activity_type, $description, $user_id = null) {
        if ($this->config['audit_level'] === 'none') {
            return;
        }
        
        try {
            $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
            $ip_address = $this->getRealIpAddress();
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $request_uri = $_SERVER['REQUEST_URI'] ?? '';
            
            $query = "INSERT INTO audit_logs 
                     (user_id, action, table_name, details, ip_address, user_agent, request_uri, timestamp) 
                     VALUES (?, ?, 'security', ?, ?, ?, ?, NOW())";
            
            $details = json_encode([
                'activity_type' => $activity_type,
                'description' => $description,
                'session_id' => session_id()
            ]);
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("isssss", $user_id, $activity_type, $details, $ip_address, $user_agent, $request_uri);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Security activity logging error: " . $e->getMessage());
        }
    }
    
    /**
     * Get real IP address
     */
    private function getRealIpAddress() {
        $ip_headers = [
            'HTTP_CF_CONNECTING_IP',     // CloudFlare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];
        
        foreach ($ip_headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = $_SERVER[$header];
                
                // Handle comma-separated IPs
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                
                // Validate IP
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
    
    /**
     * Check if current request is secure
     */
    public function isSecureRequest() {
        return isset($_SERVER['HTTPS']) || 
               (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');
    }
    
    /**
     * Get session time remaining
     */
    public function getSessionTimeRemaining() {
        if (!isset($_SESSION['last_activity'])) {
            return 0;
        }
        
        $elapsed = time() - $_SESSION['last_activity'];
        $remaining = $this->config['session_timeout'] - $elapsed;
        
        return max(0, $remaining);
    }
    
    /**
     * Extend current session
     */
    public function extendSession() {
        if (isset($_SESSION['user_id'])) {
            $_SESSION['last_activity'] = time();
            $this->logActivity(self::ACTIVITY_DATA_ACCESS, "Session extended");
            return true;
        }
        return false;
    }
    
    /**
     * Clean up expired sessions
     */
    public function cleanupExpiredSessions() {
        try {
            $expire_time = time() - $this->config['session_timeout'];
            
            $query = "UPDATE user_sessions 
                     SET is_active = 0 
                     WHERE last_activity < FROM_UNIXTIME(?) AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $expire_time);
            $stmt->execute();
            
            $affected_rows = $stmt->affected_rows;
            if ($affected_rows > 0) {
                error_log("Cleaned up $affected_rows expired sessions");
            }
            
        } catch (Exception $e) {
            error_log("Session cleanup error: " . $e->getMessage());
        }
    }
}

/**
 * Global security functions
 */
function getSecurityFramework() {
    global $con;
    static $security_framework = null;
    
    if ($security_framework === null) {
        try {
            $security_framework = SecurityFramework::getInstance($con);
        } catch (Exception $e) {
            error_log("Security Framework Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $security_framework;
}

function generateCSRFToken() {
    $sf = getSecurityFramework();
    return $sf ? $sf->generateCSRFToken() : '';
}

function validateCSRFToken($token) {
    $sf = getSecurityFramework();
    return $sf ? $sf->validateCSRFToken($token) : false;
}

function logSecurityActivity($type, $description, $user_id = null) {
    $sf = getSecurityFramework();
    if ($sf) {
        $sf->logActivity($type, $description, $user_id);
    }
}

function extendUserSession() {
    $sf = getSecurityFramework();
    return $sf ? $sf->extendSession() : false;
}

function requireSecureConnection() {
    $sf = getSecurityFramework();
    if ($sf && !$sf->isSecureRequest()) {
        header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
        exit;
    }
}
?>
