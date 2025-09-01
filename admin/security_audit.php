<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection and auth functions
include 'db.php';
require_once 'auth.php';

// Define ADMIN_ACCESS for security config
define('ADMIN_ACCESS', true);
require_once 'security_config.php';

// Check permissions - only Admin, Director, and IT_Admin can access security audit
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Security audit requires high-level permissions
if (!in_array($user_role_id, [1, 11]) && // Admin and Director
    !in_array($user_role, ['Admin', 'Director', 'IT_Admin', 'super_admin']) &&
    !user_has_permission($con, 'security.audit')) {
    header('Location: access_denied.php');
    exit();
}

$page_title = 'Security Audit Dashboard';

// Log access to security audit
log_admin_activity('security_audit_access', 'Accessed security audit dashboard');

// Handle AJAX requests
if (isset($_GET['action']) && $_GET['action'] === 'get_audit_data') {
    header('Content-Type: application/json');
    
    $type = $_GET['type'] ?? 'overview';
    $response = [];
    
    try {
        switch($type) {
            case 'overview':
                $response = getSecurityOverview($con);
                break;
            case 'login_attempts':
                $response = getLoginAttempts($con);
                break;
            case 'activity_log':
                $response = getActivityLog($con);
                break;
            case 'failed_logins':
                $response = getFailedLogins($con);
                break;
            case 'user_sessions':
                $response = getUserSessions($con);
                break;
            case 'security_alerts':
                $response = getSecurityAlerts($con);
                break;
            default:
                $response = ['error' => 'Invalid request type'];
        }
    } catch (Exception $e) {
        $response = ['error' => 'Database error: ' . $e->getMessage()];
    }
    
    echo json_encode($response);
    exit();
}

// Security audit functions
function getSecurityOverview($con) {
    $overview = [];
    
    // Total login attempts today
    $stmt = $con->prepare("SELECT COUNT(*) as total FROM login_attempts WHERE DATE(attempted_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $overview['login_attempts_today'] = $result->fetch_assoc()['total'];
    
    // Failed login attempts today
    $stmt = $con->prepare("SELECT COUNT(*) as total FROM login_attempts WHERE success = 0 AND DATE(attempted_at) = CURDATE()");
    $stmt->execute();
    $result = $stmt->get_result();
    $overview['failed_logins_today'] = $result->fetch_assoc()['total'];
    
    // Active users (logged in within last hour)
    $stmt = $con->prepare("SELECT COUNT(DISTINCT user_id) as total FROM admin_activity_log WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute();
    $result = $stmt->get_result();
    $overview['active_users'] = $result->fetch_assoc()['total'];
    
    // Recent security events (last 24 hours)
    $stmt = $con->prepare("SELECT COUNT(*) as total FROM admin_activity_log WHERE action LIKE '%security%' OR action LIKE '%login%' OR action LIKE '%logout%' AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $result = $stmt->get_result();
    $overview['security_events_24h'] = $result->fetch_assoc()['total'];
    
    // Blocked IPs (failed login attempts > 5 in last hour)
    $stmt = $con->prepare("
        SELECT COUNT(DISTINCT ip_address) as total 
        FROM login_attempts 
        WHERE success = 0 
        AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) 
        GROUP BY ip_address 
        HAVING COUNT(*) >= 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $overview['blocked_ips'] = $result->num_rows;
    
    return $overview;
}

function getLoginAttempts($con) {
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    $stmt = $con->prepare("
        SELECT la.*, u.username 
        FROM login_attempts la 
        LEFT JOIN users u ON u.username = la.identifier 
        ORDER BY la.attempted_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $attempts = [];
    while ($row = $result->fetch_assoc()) {
        $attempts[] = $row;
    }
    
    return ['attempts' => $attempts];
}

function getActivityLog($con) {
    $limit = $_GET['limit'] ?? 50;
    $offset = $_GET['offset'] ?? 0;
    
    $stmt = $con->prepare("
        SELECT aal.*, u.username 
        FROM admin_activity_log aal 
        LEFT JOIN users u ON u.id = aal.user_id 
        ORDER BY aal.created_at DESC 
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }
    
    return ['activities' => $activities];
}

function getFailedLogins($con) {
    $stmt = $con->prepare("
        SELECT ip_address, identifier, COUNT(*) as attempts, MAX(attempted_at) as last_attempt
        FROM login_attempts 
        WHERE success = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        GROUP BY ip_address, identifier 
        HAVING attempts >= 3
        ORDER BY attempts DESC, last_attempt DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $failed_logins = [];
    while ($row = $result->fetch_assoc()) {
        $failed_logins[] = $row;
    }
    
    return ['failed_logins' => $failed_logins];
}

function getUserSessions($con) {
    // Note: This would require session storage in database
    // For now, return active users from activity log
    $stmt = $con->prepare("
        SELECT u.username, u.role_id, r.name as role_name, 
               MAX(aal.created_at) as last_activity,
               COUNT(*) as activities_today
        FROM users u 
        LEFT JOIN admin_activity_log aal ON u.id = aal.user_id AND DATE(aal.created_at) = CURDATE()
        LEFT JOIN roles r ON u.role_id = r.id
        WHERE aal.user_id IS NOT NULL
        GROUP BY u.id, u.username, u.role_id, r.name
        ORDER BY last_activity DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $sessions = [];
    while ($row = $result->fetch_assoc()) {
        $sessions[] = $row;
    }
    
    return ['sessions' => $sessions];
}

function getSecurityAlerts($con) {
    $alerts = [];
    
    // Check for suspicious activities
    
    // 1. Multiple failed logins from same IP
    $stmt = $con->prepare("
        SELECT ip_address, COUNT(*) as attempts 
        FROM login_attempts 
        WHERE success = 0 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
        GROUP BY ip_address 
        HAVING attempts >= 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = [
            'type' => 'high',
            'message' => "Suspicious activity: {$row['attempts']} failed login attempts from IP {$row['ip_address']}",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    // 2. Admin actions outside business hours
    $stmt = $con->prepare("
        SELECT aal.*, u.username 
        FROM admin_activity_log aal 
        JOIN users u ON aal.user_id = u.id 
        WHERE (HOUR(aal.created_at) < 6 OR HOUR(aal.created_at) > 22)
        AND aal.action IN ('user_create', 'user_delete', 'role_change', 'permission_grant')
        AND DATE(aal.created_at) = CURDATE()
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = [
            'type' => 'medium',
            'message' => "After-hours admin activity: {$row['username']} performed {$row['action']} at {$row['created_at']}",
            'timestamp' => $row['created_at']
        ];
    }
    
    // 3. Rapid successive logins
    $stmt = $con->prepare("
        SELECT identifier, COUNT(*) as rapid_logins 
        FROM login_attempts 
        WHERE success = 1 AND attempted_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        GROUP BY identifier 
        HAVING rapid_logins >= 3
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = [
            'type' => 'medium',
            'message' => "Rapid login pattern: {$row['identifier']} logged in {$row['rapid_logins']} times in 5 minutes",
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    return ['alerts' => $alerts];
}

// Include the header
include '../includes/admin/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-header">
                    <h1 class="page-title">
                        <i class="fas fa-shield-alt text-primary"></i>
                        Security Audit Dashboard
                    </h1>
                    <p class="page-description">Monitor security events, login attempts, and system activities</p>
                </div>
            </div>
        </div>

        <!-- Security Overview Cards -->
        <div class="row mb-4" id="securityOverview">
            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-sign-in-alt text-primary"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Login Attempts</p>
                                    <p class="card-title" id="loginAttemptsToday">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-exclamation-triangle text-danger"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Failed Logins</p>
                                    <p class="card-title" id="failedLoginsToday">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-users text-success"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Active Users</p>
                                    <p class="card-title" id="activeUsers">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card card-stats">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-5">
                                <div class="icon-big text-center icon-warning">
                                    <i class="fas fa-ban text-warning"></i>
                                </div>
                            </div>
                            <div class="col-7">
                                <div class="numbers">
                                    <p class="card-category">Blocked IPs</p>
                                    <p class="card-title" id="blockedIps">-</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Security Alerts -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="card-title">
                            <i class="fas fa-exclamation-circle text-warning"></i>
                            Security Alerts
                        </h4>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshAlerts()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="securityAlerts">
                            <div class="text-center">
                                <i class="fas fa-spinner fa-spin"></i> Loading alerts...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Audit Tabs -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="auditTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-tab="login_attempts" href="#login_attempts">
                                    <i class="fas fa-key"></i> Login Attempts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-tab="activity_log" href="#activity_log">
                                    <i class="fas fa-list"></i> Activity Log
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-tab="failed_logins" href="#failed_logins">
                                    <i class="fas fa-times-circle"></i> Failed Logins
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-tab="user_sessions" href="#user_sessions">
                                    <i class="fas fa-user-clock"></i> User Sessions
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <!-- Login Attempts Tab -->
                        <div class="tab-content" id="login_attempts">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Recent Login Attempts</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="exportData('login_attempts')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTab('login_attempts')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="loginAttemptsTable">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>Username</th>
                                            <th>IP Address</th>
                                            <th>Status</th>
                                            <th>User Agent</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Activity Log Tab -->
                        <div class="tab-content" id="activity_log" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Admin Activity Log</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="exportData('activity_log')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTab('activity_log')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="activityLogTable">
                                    <thead>
                                        <tr>
                                            <th>Timestamp</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th>Details</th>
                                            <th>IP Address</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Failed Logins Tab -->
                        <div class="tab-content" id="failed_logins" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Failed Login Analysis</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="exportData('failed_logins')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTab('failed_logins')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="failedLoginsTable">
                                    <thead>
                                        <tr>
                                            <th>IP Address</th>
                                            <th>Username</th>
                                            <th>Failed Attempts</th>
                                            <th>Last Attempt</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="6" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- User Sessions Tab -->
                        <div class="tab-content" id="user_sessions" style="display: none;">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5>Active User Sessions</h5>
                                <div class="btn-group">
                                    <button class="btn btn-sm btn-outline-secondary" onclick="exportData('user_sessions')">
                                        <i class="fas fa-download"></i> Export
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshTab('user_sessions')">
                                        <i class="fas fa-sync-alt"></i> Refresh
                                    </button>
                                </div>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="userSessionsTable">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Role</th>
                                            <th>Last Activity</th>
                                            <th>Activities Today</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <i class="fas fa-spinner fa-spin"></i> Loading data...
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Security Actions Modal -->
<div class="modal fade" id="securityActionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Security Action</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="modalContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmAction">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
class SecurityAuditDashboard {
    constructor() {
        this.currentTab = 'login_attempts';
        this.refreshInterval = null;
        this.init();
    }

    init() {
        this.loadOverview();
        this.loadAlerts();
        this.loadTabData(this.currentTab);
        this.bindEvents();
        this.startAutoRefresh();
    }

    bindEvents() {
        // Tab navigation
        document.querySelectorAll('#auditTabs .nav-link').forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();
                const tabName = e.target.getAttribute('data-tab');
                this.switchTab(tabName);
            });
        });

        // Auto-refresh toggle
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                this.refreshCurrentTab();
            }
        });
    }

    async loadOverview() {
        try {
            const response = await fetch('?action=get_audit_data&type=overview');
            const data = await response.json();
            
            if (data.error) {
                console.error('Overview error:', data.error);
                return;
            }

            document.getElementById('loginAttemptsToday').textContent = data.login_attempts_today || 0;
            document.getElementById('failedLoginsToday').textContent = data.failed_logins_today || 0;
            document.getElementById('activeUsers').textContent = data.active_users || 0;
            document.getElementById('blockedIps').textContent = data.blocked_ips || 0;

        } catch (error) {
            console.error('Error loading overview:', error);
        }
    }

    async loadAlerts() {
        try {
            const response = await fetch('?action=get_audit_data&type=security_alerts');
            const data = await response.json();
            
            if (data.error) {
                console.error('Alerts error:', data.error);
                return;
            }

            const alertsContainer = document.getElementById('securityAlerts');
            
            if (!data.alerts || data.alerts.length === 0) {
                alertsContainer.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        No security alerts detected. System appears secure.
                    </div>
                `;
                return;
            }

            let alertsHtml = '';
            data.alerts.forEach(alert => {
                const alertClass = alert.type === 'high' ? 'alert-danger' : 
                                 alert.type === 'medium' ? 'alert-warning' : 'alert-info';
                
                alertsHtml += `
                    <div class="alert ${alertClass} alert-dismissible">
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        <strong>${alert.type.toUpperCase()}:</strong> ${alert.message}
                        <small class="d-block text-muted">${alert.timestamp}</small>
                    </div>
                `;
            });

            alertsContainer.innerHTML = alertsHtml;

        } catch (error) {
            console.error('Error loading alerts:', error);
        }
    }

    async loadTabData(tabName) {
        try {
            const response = await fetch(`?action=get_audit_data&type=${tabName}&limit=50&offset=0`);
            const data = await response.json();
            
            if (data.error) {
                console.error(`${tabName} error:`, data.error);
                return;
            }

            switch(tabName) {
                case 'login_attempts':
                    this.renderLoginAttempts(data.attempts || []);
                    break;
                case 'activity_log':
                    this.renderActivityLog(data.activities || []);
                    break;
                case 'failed_logins':
                    this.renderFailedLogins(data.failed_logins || []);
                    break;
                case 'user_sessions':
                    this.renderUserSessions(data.sessions || []);
                    break;
            }

        } catch (error) {
            console.error(`Error loading ${tabName}:`, error);
        }
    }

    renderLoginAttempts(attempts) {
        const tbody = document.querySelector('#loginAttemptsTable tbody');
        
        if (attempts.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No login attempts found</td></tr>';
            return;
        }

        tbody.innerHTML = attempts.map(attempt => `
            <tr>
                <td>${this.formatDate(attempt.attempted_at)}</td>
                <td>${attempt.username || attempt.identifier}</td>
                <td>
                    <span class="font-monospace">${attempt.ip_address}</span>
                </td>
                <td>
                    <span class="badge ${attempt.success ? 'bg-success' : 'bg-danger'}">
                        ${attempt.success ? 'Success' : 'Failed'}
                    </span>
                </td>
                <td>
                    <small class="text-muted" title="${attempt.user_agent || ''}">
                        ${this.truncateText(attempt.user_agent || '', 40)}
                    </small>
                </td>
            </tr>
        `).join('');
    }

    renderActivityLog(activities) {
        const tbody = document.querySelector('#activityLogTable tbody');
        
        if (activities.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No activities found</td></tr>';
            return;
        }

        tbody.innerHTML = activities.map(activity => `
            <tr>
                <td>${this.formatDate(activity.created_at)}</td>
                <td>
                    <strong>${activity.username || 'Unknown'}</strong>
                </td>
                <td>
                    <span class="badge bg-primary">${activity.action}</span>
                </td>
                <td>${activity.details || ''}</td>
                <td>
                    <span class="font-monospace">${activity.ip_address || ''}</span>
                </td>
            </tr>
        `).join('');
    }

    renderFailedLogins(failedLogins) {
        const tbody = document.querySelector('#failedLoginsTable tbody');
        
        if (failedLogins.length === 0) {
            tbody.innerHTML = '<tr><td colspan="6" class="text-center">No failed logins found</td></tr>';
            return;
        }

        tbody.innerHTML = failedLogins.map(login => `
            <tr>
                <td>
                    <span class="font-monospace">${login.ip_address}</span>
                </td>
                <td>${login.identifier}</td>
                <td>
                    <span class="badge bg-danger">${login.attempts}</span>
                </td>
                <td>${this.formatDate(login.last_attempt)}</td>
                <td>
                    <span class="badge ${login.attempts >= 5 ? 'bg-danger' : 'bg-warning'}">
                        ${login.attempts >= 5 ? 'Blocked' : 'Suspicious'}
                    </span>
                </td>
                <td>
                    ${login.attempts >= 5 ? `
                        <button class="btn btn-sm btn-outline-danger" onclick="securityAudit.blockIP('${login.ip_address}')">
                            <i class="fas fa-ban"></i> Block
                        </button>
                    ` : ''}
                </td>
            </tr>
        `).join('');
    }

    renderUserSessions(sessions) {
        const tbody = document.querySelector('#userSessionsTable tbody');
        
        if (sessions.length === 0) {
            tbody.innerHTML = '<tr><td colspan="5" class="text-center">No active sessions found</td></tr>';
            return;
        }

        tbody.innerHTML = sessions.map(session => `
            <tr>
                <td>
                    <strong>${session.username}</strong>
                </td>
                <td>
                    <span class="badge bg-info">${session.role_name || 'Unknown'}</span>
                </td>
                <td>${this.formatDate(session.last_activity)}</td>
                <td>
                    <span class="badge bg-secondary">${session.activities_today}</span>
                </td>
                <td>
                    <span class="badge bg-success">Active</span>
                </td>
            </tr>
        `).join('');
    }

    switchTab(tabName) {
        // Hide all tab contents
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
        });

        // Remove active class from all tabs
        document.querySelectorAll('#auditTabs .nav-link').forEach(tab => {
            tab.classList.remove('active');
        });

        // Show selected tab
        document.getElementById(tabName).style.display = 'block';
        document.querySelector(`#auditTabs .nav-link[data-tab="${tabName}"]`).classList.add('active');

        this.currentTab = tabName;
        this.loadTabData(tabName);
    }

    refreshCurrentTab() {
        this.loadTabData(this.currentTab);
    }

    refreshAlerts() {
        this.loadAlerts();
    }

    startAutoRefresh() {
        // Refresh overview every 30 seconds
        this.refreshInterval = setInterval(() => {
            this.loadOverview();
            this.loadAlerts();
        }, 30000);
    }

    formatDate(dateString) {
        if (!dateString) return '';
        const date = new Date(dateString);
        return date.toLocaleString();
    }

    truncateText(text, maxLength) {
        if (!text) return '';
        return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
    }

    blockIP(ipAddress) {
        if (confirm(`Are you sure you want to block IP address ${ipAddress}?`)) {
            // Implement IP blocking functionality
            console.log('Blocking IP:', ipAddress);
            alert('IP blocking functionality would be implemented here');
        }
    }

    exportData(tabName) {
        // Implement data export functionality
        console.log('Exporting data for:', tabName);
        alert('Export functionality would be implemented here');
    }
}

// Convenience functions for template
function refreshTab(tabName) {
    securityAudit.switchTab(tabName);
}

function refreshAlerts() {
    securityAudit.refreshAlerts();
}

function exportData(tabName) {
    securityAudit.exportData(tabName);
}

// Initialize dashboard when page loads
document.addEventListener('DOMContentLoaded', function() {
    window.securityAudit = new SecurityAuditDashboard();
});
</script>

<style>
.card-stats {
    background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
    border: none;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: transform 0.2s ease-in-out;
}

.card-stats:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}

.icon-big {
    font-size: 2.5rem;
    width: 80px;
    height: 80px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0,123,255,0.1);
}

.card-title {
    font-size: 1.8rem;
    font-weight: bold;
    color: #2c3e50;
    margin-bottom: 0;
}

.card-category {
    font-size: 0.9rem;
    color: #6c757d;
    margin-bottom: 5px;
}

.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
}

.page-title {
    font-size: 2rem;
    margin-bottom: 0.5rem;
}

.page-description {
    opacity: 0.9;
    margin-bottom: 0;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    padding: 1rem 1.5rem;
    border-bottom: 3px solid transparent;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link.active {
    background: none;
    border-bottom-color: #007bff;
    color: #007bff;
    font-weight: 600;
}

.nav-tabs .nav-link:hover {
    border-bottom-color: #007bff;
    color: #007bff;
}

.table th {
    background-color: #f8f9fa;
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.75em;
    padding: 0.375em 0.75em;
}

.alert {
    border: none;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.font-monospace {
    font-family: 'Courier New', monospace;
    font-size: 0.9em;
}

.btn-group .btn {
    margin-left: 0.25rem;
}

.table-responsive {
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

@media (max-width: 768px) {
    .page-title {
        font-size: 1.5rem;
    }
    
    .card-stats {
        margin-bottom: 1rem;
    }
    
    .btn-group {
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin: 0.25rem 0;
    }
}
</style>

<?php include '../includes/admin/footer.php'; ?>
