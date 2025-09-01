<?php
/**
 * Security Tables Setup Script
 * Ensures all required security audit tables exist with proper structure
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin privileges
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role_id'] ?? 0, [1, 11])) {
    die('Unauthorized access. Admin privileges required.');
}

// Include database connection
include 'db.php';

$results = [];
$errors = [];

try {
    // Create admin_activity_log table if it doesn't exist
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
        INDEX idx_created_at (created_at),
        INDEX idx_ip_address (ip_address)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($con->query($activity_log_sql)) {
        $results[] = "✅ admin_activity_log table created/verified";
    } else {
        $errors[] = "❌ Error creating admin_activity_log: " . $con->error;
    }
    
    // Create login_attempts table if it doesn't exist
    $login_attempts_sql = "CREATE TABLE IF NOT EXISTS login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        identifier VARCHAR(100) NOT NULL,
        success BOOLEAN DEFAULT FALSE,
        ip_address VARCHAR(45),
        user_agent TEXT,
        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_identifier (identifier),
        INDEX idx_success (success),
        INDEX idx_ip_address (ip_address),
        INDEX idx_attempted_at (attempted_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($con->query($login_attempts_sql)) {
        $results[] = "✅ login_attempts table created/verified";
    } else {
        $errors[] = "❌ Error creating login_attempts: " . $con->error;
    }
    
    // Create user_permissions table if it doesn't exist
    $user_permissions_sql = "CREATE TABLE IF NOT EXISTS user_permissions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        permission VARCHAR(100) NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        granted_by INT,
        granted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        revoked_at TIMESTAMP NULL,
        UNIQUE KEY unique_user_permission (user_id, permission),
        INDEX idx_user_id (user_id),
        INDEX idx_permission (permission),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($con->query($user_permissions_sql)) {
        $results[] = "✅ user_permissions table created/verified";
    } else {
        $errors[] = "❌ Error creating user_permissions: " . $con->error;
    }
    
    // Create security_settings table for configuration
    $security_settings_sql = "CREATE TABLE IF NOT EXISTS security_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value TEXT,
        setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
        description TEXT,
        is_active BOOLEAN DEFAULT TRUE,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_setting_key (setting_key),
        INDEX idx_is_active (is_active)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($con->query($security_settings_sql)) {
        $results[] = "✅ security_settings table created/verified";
    } else {
        $errors[] = "❌ Error creating security_settings: " . $con->error;
    }
    
    // Insert default security settings
    $default_settings = [
        ['max_login_attempts', '5', 'integer', 'Maximum failed login attempts before lockout'],
        ['lockout_duration', '900', 'integer', 'Account lockout duration in seconds (15 minutes)'],
        ['session_timeout', '1800', 'integer', 'Session timeout in seconds (30 minutes)'],
        ['password_min_length', '8', 'integer', 'Minimum password length'],
        ['require_special_chars', '1', 'boolean', 'Require special characters in passwords'],
        ['security_audit_retention', '90', 'integer', 'Security audit log retention in days'],
        ['alert_after_hours', '1', 'boolean', 'Alert on after-hours admin activities'],
        ['alert_multiple_failures', '1', 'boolean', 'Alert on multiple failed login attempts'],
    ];
    
    $stmt = $con->prepare("INSERT IGNORE INTO security_settings (setting_key, setting_value, setting_type, description) VALUES (?, ?, ?, ?)");
    
    $settings_created = 0;
    foreach ($default_settings as $setting) {
        $stmt->bind_param('ssss', $setting[0], $setting[1], $setting[2], $setting[3]);
        if ($stmt->execute()) {
            if ($con->affected_rows > 0) {
                $settings_created++;
            }
        }
    }
    
    if ($settings_created > 0) {
        $results[] = "✅ Default security settings created ($settings_created new settings)";
    } else {
        $results[] = "ℹ️ Security settings already exist";
    }
    
    // Check if we need to add user_agent column to login_attempts (for compatibility)
    $check_column = $con->query("SHOW COLUMNS FROM login_attempts LIKE 'user_agent'");
    if ($check_column->num_rows === 0) {
        $add_column_sql = "ALTER TABLE login_attempts ADD COLUMN user_agent TEXT AFTER ip_address";
        if ($con->query($add_column_sql)) {
            $results[] = "✅ Added user_agent column to login_attempts table";
        } else {
            $errors[] = "❌ Error adding user_agent column: " . $con->error;
        }
    }
    
    // Verify audit_logs table exists (mentioned in hotel.sql)
    $check_audit = $con->query("SHOW TABLES LIKE 'audit_logs'");
    if ($check_audit->num_rows > 0) {
        $results[] = "ℹ️ audit_logs table found (existing)";
    } else {
        // Create audit_logs table compatible with the schema
        $audit_logs_sql = "CREATE TABLE IF NOT EXISTS audit_logs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT(10) UNSIGNED DEFAULT NULL,
            action VARCHAR(80) NOT NULL,
            entity_type VARCHAR(80) DEFAULT NULL,
            entity_id BIGINT(20) DEFAULT NULL,
            meta_json LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_entity_type (entity_type),
            INDEX idx_created_at (created_at),
            CONSTRAINT chk_meta_json CHECK (JSON_VALID(meta_json) OR meta_json IS NULL)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        if ($con->query($audit_logs_sql)) {
            $results[] = "✅ audit_logs table created";
        } else {
            $errors[] = "❌ Error creating audit_logs: " . $con->error;
        }
    }
    
} catch (Exception $e) {
    $errors[] = "❌ Exception: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Tables Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">
                            <i class="fas fa-shield-alt"></i>
                            Security Tables Setup
                        </h3>
                        <small>Database setup for Security Audit Dashboard</small>
                    </div>
                    <div class="card-body">
                        
                        <?php if (!empty($results)): ?>
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Setup Results</h5>
                            <ul class="mb-0">
                                <?php foreach ($results as $result): ?>
                                <li><?php echo $result; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-exclamation-triangle"></i> Errors</h5>
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                <li><?php echo $error; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                        
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Tables Created/Verified:</h6>
                            <ul class="mb-0">
                                <li><strong>admin_activity_log</strong> - Tracks all admin panel activities</li>
                                <li><strong>login_attempts</strong> - Records all login attempts (successful and failed)</li>
                                <li><strong>user_permissions</strong> - Manages individual user permissions</li>
                                <li><strong>security_settings</strong> - Stores security configuration settings</li>
                                <li><strong>audit_logs</strong> - General audit logging (compatible with existing schema)</li>
                            </ul>
                        </div>
                        
                        <div class="d-flex gap-2 mt-4">
                            <a href="security_audit.php" class="btn btn-primary">
                                <i class="fas fa-shield-alt"></i> Go to Security Audit Dashboard
                            </a>
                            <a href="api/security_audit_init.php" class="btn btn-secondary">
                                <i class="fas fa-database"></i> Initialize Sample Data
                            </a>
                            <a href="home.php" class="btn btn-outline-secondary">
                                <i class="fas fa-home"></i> Back to Dashboard
                            </a>
                        </div>
                        
                        <hr class="my-4">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="fas fa-cog"></i> Next Steps:</h6>
                                <ol>
                                    <li>Initialize sample data for testing</li>
                                    <li>Configure security settings as needed</li>
                                    <li>Set up automated security monitoring</li>
                                    <li>Review security audit dashboard</li>
                                </ol>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="fas fa-key"></i> Security Features:</h6>
                                <ul>
                                    <li>Login attempt monitoring</li>
                                    <li>Admin activity tracking</li>
                                    <li>Failed login analysis</li>
                                    <li>IP-based threat detection</li>
                                    <li>After-hours activity alerts</li>
                                    <li>User session monitoring</li>
                                </ul>
                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
