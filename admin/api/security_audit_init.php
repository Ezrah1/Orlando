<?php
/**
 * Security Audit Initialization Script
 * Creates sample security data for testing and demonstration
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
include '../db.php';

// Define ADMIN_ACCESS for security config
define('ADMIN_ACCESS', true);
require_once '../security_config.php';

// Check if user has permission to run this script
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role_id'] ?? 0, [1, 11])) {
    die('Unauthorized access');
}

/**
 * Initialize security audit tables with sample data
 */
function initializeSecurityAuditData($con) {
    $results = [];
    
    try {
        // Clear existing test data
        $con->query("DELETE FROM admin_activity_log WHERE details LIKE '%TEST_DATA%'");
        $con->query("DELETE FROM login_attempts WHERE identifier LIKE '%test%'");
        
        // Add sample login attempts
        $sampleAttempts = [
            ['admin', true, '192.168.1.100'],
            ['john.doe', true, '192.168.1.101'],
            ['hacker123', false, '203.0.113.45'],
            ['admin', false, '203.0.113.45'],
            ['root', false, '203.0.113.45'],
            ['administrator', false, '203.0.113.45'],
            ['guest', false, '203.0.113.45'],
            ['test', false, '203.0.113.45'],
            ['manager', true, '192.168.1.102'],
            ['staff01', true, '192.168.1.103'],
            ['hacker123', false, '198.51.100.25'],
            ['admin', false, '198.51.100.25'],
            ['finance', true, '192.168.1.104'],
            ['support', true, '192.168.1.105'],
        ];
        
        $stmt = $con->prepare("INSERT INTO login_attempts (identifier, success, ip_address, attempted_at) VALUES (?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))");
        
        foreach ($sampleAttempts as $i => $attempt) {
            $minutes_ago = rand(1, 1440); // Random time within last 24 hours
            $stmt->bind_param('sisi', $attempt[0], $attempt[1], $attempt[2], $minutes_ago);
            $stmt->execute();
        }
        
        $results['login_attempts'] = count($sampleAttempts);
        
        // Add sample admin activity log entries
        $user_id = $_SESSION['user_id'];
        $activities = [
            ['user_login', 'User logged in successfully - TEST_DATA'],
            ['page_access', 'Accessed user management page - TEST_DATA'],
            ['user_create', 'Created new user account - TEST_DATA'],
            ['security_audit_access', 'Accessed security audit dashboard - TEST_DATA'],
            ['permission_check', 'Checked user permissions - TEST_DATA'],
            ['password_change', 'Changed user password - TEST_DATA'],
            ['role_assignment', 'Assigned role to user - TEST_DATA'],
            ['system_settings', 'Modified system settings - TEST_DATA'],
            ['data_export', 'Exported user data - TEST_DATA'],
            ['session_timeout', 'Session timeout warning - TEST_DATA'],
        ];
        
        $stmt = $con->prepare("INSERT INTO admin_activity_log (user_id, action, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))");
        
        foreach ($activities as $i => $activity) {
            $minutes_ago = rand(1, 720); // Random time within last 12 hours
            $ip = '192.168.1.' . rand(100, 200);
            $user_agent = 'Mozilla/5.0 (Test Browser) TEST_DATA';
            
            $stmt->bind_param('issssi', $user_id, $activity[0], $activity[1], $ip, $user_agent, $minutes_ago);
            $stmt->execute();
        }
        
        $results['activity_log'] = count($activities);
        
        // Add some failed login patterns for testing alerts
        $malicious_ips = ['203.0.113.45', '198.51.100.25', '192.0.2.100'];
        
        foreach ($malicious_ips as $ip) {
            for ($i = 0; $i < 8; $i++) {
                $minutes_ago = rand(1, 60); // Within last hour
                $stmt = $con->prepare("INSERT INTO login_attempts (identifier, success, ip_address, attempted_at) VALUES (?, 0, ?, DATE_SUB(NOW(), INTERVAL ? MINUTE))");
                $identifier = 'hacker' . rand(1, 99);
                $stmt->bind_param('ssi', $identifier, $ip, $minutes_ago);
                $stmt->execute();
            }
        }
        
        $results['malicious_attempts'] = count($malicious_ips) * 8;
        
        // Add some after-hours activities
        $stmt = $con->prepare("INSERT INTO admin_activity_log (user_id, action, details, ip_address, created_at) VALUES (?, ?, ?, ?, DATE_SUB(CURDATE(), INTERVAL -23 HOUR))");
        $after_hours_action = 'user_delete';
        $after_hours_details = 'Deleted user account after hours - TEST_DATA';
        $after_hours_ip = '192.168.1.200';
        $stmt->bind_param('isss', $user_id, $after_hours_action, $after_hours_details, $after_hours_ip);
        $stmt->execute();
        
        $results['after_hours_activities'] = 1;
        
        $results['status'] = 'success';
        $results['message'] = 'Security audit sample data initialized successfully';
        
    } catch (Exception $e) {
        $results['status'] = 'error';
        $results['message'] = 'Error initializing data: ' . $e->getMessage();
    }
    
    return $results;
}

// Handle AJAX request
if (isset($_GET['action']) && $_GET['action'] === 'init') {
    header('Content-Type: application/json');
    echo json_encode(initializeSecurityAuditData($con));
    exit();
}

// Handle direct access
if ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['init'])) {
    $results = initializeSecurityAuditData($con);
    
    if ($results['status'] === 'success') {
        $message = "✅ " . $results['message'] . "\n\n";
        $message .= "📊 Sample data created:\n";
        $message .= "• Login attempts: " . $results['login_attempts'] . "\n";
        $message .= "• Activity log entries: " . $results['activity_log'] . "\n";
        $message .= "• Malicious attempts: " . $results['malicious_attempts'] . "\n";
        $message .= "• After-hours activities: " . $results['after_hours_activities'] . "\n";
        
        echo "<pre>$message</pre>";
        echo '<p><a href="../security_audit.php" class="btn btn-primary">Go to Security Audit Dashboard</a></p>';
    } else {
        echo "<div class='alert alert-danger'>❌ " . $results['message'] . "</div>";
    }
    
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Security Audit Initialization</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3><i class="fas fa-shield-alt"></i> Security Audit Initialization</h3>
                    </div>
                    <div class="card-body">
                        <p>This script will create sample security audit data for testing and demonstration purposes.</p>
                        
                        <div class="alert alert-info">
                            <strong>Note:</strong> This will create test data including:
                            <ul>
                                <li>Sample login attempts (successful and failed)</li>
                                <li>Admin activity log entries</li>
                                <li>Simulated security threats</li>
                                <li>After-hours activities</li>
                            </ul>
                        </div>
                        
                        <form method="POST">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-database"></i> Initialize Sample Data
                            </button>
                            <a href="../security_audit.php" class="btn btn-secondary ms-2">
                                <i class="fas fa-arrow-left"></i> Back to Security Audit
                            </a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
