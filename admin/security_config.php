<?php
/**
 * Enhanced Security Configuration for Admin Panel
 * Features: CSRF protection, secure headers, session security, access logging
 */

// Prevent direct access
if (!defined('ADMIN_ACCESS')) {
    die('Direct access not permitted');
}

// Define security constants
define('CSRF_TOKEN_NAME', '_csrf_token');
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_TIME', 900); // 15 minutes

/**
 * Set secure headers
 */
function set_security_headers() {
    // Prevent clickjacking
    header('X-Frame-Options: DENY');
    
    // Prevent MIME type sniffing
    header('X-Content-Type-Options: nosniff');
    
    // Enable XSS protection
    header('X-XSS-Protection: 1; mode=block');
    
    // Enforce HTTPS (uncomment in production)
    // header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
    
    // Content Security Policy
    header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://code.jquery.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com; img-src 'self' data: https:; connect-src 'self';");
    
    // Referrer Policy
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Feature Policy
    header("Permissions-Policy: geolocation=(), camera=(), microphone=()");
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && 
           hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Get CSRF input field
 */
function csrf_input() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . htmlspecialchars($token) . '">';
}

/**
 * Enhanced session security
 */
function secure_session() {
    // Regenerate session ID on login
    session_regenerate_id(true);
    
    // Set session timeout
    $_SESSION['last_activity'] = time();
    $_SESSION['created'] = $_SESSION['created'] ?? time();
    
    // Check session timeout
    if (isset($_SESSION['last_activity']) && 
        (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        session_destroy();
        return false;
    }
    
    // Update last activity
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Log admin activities
 */
function log_admin_activity($action, $details = '', $user_id = null) {
    global $con;
    
    $user_id = $user_id ?: ($_SESSION['user_id'] ?? 0);
    $ip_address = get_client_ip();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    $stmt = $con->prepare("INSERT INTO admin_activity_log (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
    if ($stmt) {
        $stmt->bind_param('issss', $user_id, $action, $details, $ip_address, $user_agent);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Get client IP address
 */
function get_client_ip() {
    $ip_keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (array_map('trim', explode(',', $_SERVER[$key])) as $ip) {
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Rate limiting for login attempts
 */
function check_rate_limit($identifier, $max_attempts = MAX_LOGIN_ATTEMPTS, $window = LOCKOUT_TIME) {
    global $con;
    
    $stmt = $con->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE identifier = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? SECOND)");
    if ($stmt) {
        $stmt->bind_param('si', $identifier, $window);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        return $row['attempts'] < $max_attempts;
    }
    
    return true;
}

/**
 * Record login attempt
 */
function record_login_attempt($identifier, $success = false) {
    global $con;
    
    $stmt = $con->prepare("INSERT INTO login_attempts (identifier, success, ip_address, attempted_at) VALUES (?, ?, ?, NOW())");
    if ($stmt) {
        $ip_address = get_client_ip();
        $stmt->bind_param('sis', $identifier, $success, $ip_address);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    
    return $data;
}

/**
 * Validate admin permissions
 */
function check_admin_permission($permission, $user_id = null) {
    global $con;
    
    $user_id = $user_id ?: ($_SESSION['user_id'] ?? 0);
    
    // Super admin has all permissions
    if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'super_admin') {
        return true;
    }
    
    $stmt = $con->prepare("SELECT 1 FROM user_permissions WHERE user_id = ? AND permission = ? AND is_active = 1");
    if ($stmt) {
        $stmt->bind_param('is', $user_id, $permission);
        $stmt->execute();
        $result = $stmt->get_result();
        $has_permission = $result->num_rows > 0;
        $stmt->close();
        
        return $has_permission;
    }
    
    return false;
}

/**
 * Generate secure password
 */
function generate_secure_password($length = 12) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    $password = '';
    
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $password;
}

/**
 * Password strength checker
 */
function check_password_strength($password) {
    $score = 0;
    $feedback = [];
    
    // Length check
    if (strlen($password) >= 8) {
        $score += 2;
    } else {
        $feedback[] = 'Password should be at least 8 characters long';
    }
    
    // Uppercase letter
    if (preg_match('/[A-Z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Add uppercase letters';
    }
    
    // Lowercase letter
    if (preg_match('/[a-z]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Add lowercase letters';
    }
    
    // Numbers
    if (preg_match('/[0-9]/', $password)) {
        $score += 1;
    } else {
        $feedback[] = 'Add numbers';
    }
    
    // Special characters
    if (preg_match('/[^A-Za-z0-9]/', $password)) {
        $score += 2;
    } else {
        $feedback[] = 'Add special characters (!@#$%^&*)';
    }
    
    $strength_levels = [
        0 => ['level' => 'Very Weak', 'class' => 'danger'],
        1 => ['level' => 'Weak', 'class' => 'warning'],
        3 => ['level' => 'Fair', 'class' => 'info'],
        5 => ['level' => 'Good', 'class' => 'success'],
        7 => ['level' => 'Strong', 'class' => 'success']
    ];
    
    $strength = $strength_levels[min($score, 7)];
    
    return [
        'score' => $score,
        'strength' => $strength,
        'feedback' => $feedback
    ];
}

// Initialize security measures
set_security_headers();

// Check if admin tables exist, create if needed
function ensure_security_tables() {
    global $con;
    
    // Admin activity log table
    $activity_log_sql = "CREATE TABLE IF NOT EXISTS admin_activity_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        user_agent TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_action (action),
        INDEX idx_created_at (created_at)
    )";
    
    // Login attempts table
    $login_attempts_sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(100) NOT NULL,
        success BOOLEAN DEFAULT FALSE,
        ip_address VARCHAR(45),
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier (identifier),
        INDEX idx_attempted_at (attempted_at)
    )";
    
    // User permissions table
    $user_permissions_sql = "CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_user_permission (user_id, permission),
        INDEX idx_user_id (user_id),
        INDEX idx_permission (permission)
    )";
    
    mysqli_query($con, $activity_log_sql);
    mysqli_query($con, $login_attempts_sql);
    mysqli_query($con, $user_permissions_sql);
}

// Initialize security tables if they don't exist
ensure_security_tables();
?>
