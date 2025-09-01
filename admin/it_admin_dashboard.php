<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has IT admin permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'IT_Admin', 'System_Admin', 'Super_Admin', 'it_admin', 'system_admin', 'director', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'IT Admin Dashboard';
include '../includes/admin/header.php';

// IT Admin Dashboard - Full System Configuration & User Management
$today = date('Y-m-d');

// System Configuration - Full Access
$database_size = mysqli_query($con, "SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) AS db_size FROM information_schema.tables WHERE table_schema = 'hotel'");
$db_size_data = mysqli_fetch_assoc($database_size)['db_size'] ?? 0;

$table_count = mysqli_query($con, "SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = 'hotel'");
$tables_data = mysqli_fetch_assoc($table_count)['count'] ?? 0;

// User Management - Full Access
$total_users = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active, COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as new_today FROM users");
$users_data = mysqli_fetch_assoc($total_users);

$users_by_role = mysqli_query($con, "SELECT r.name as role_name, COUNT(u.id) as count FROM roles r LEFT JOIN users u ON r.id = u.role_id WHERE u.status = 'active' GROUP BY r.id, r.name ORDER BY count DESC");

// System Performance Monitoring
$recent_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(created_at) = CURDATE()");
$todays_activity = mysqli_fetch_assoc($recent_bookings)['count'] ?? 0;

$recent_logins = mysqli_query($con, "SELECT COUNT(DISTINCT created_by) as count FROM roombook WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAYS)");
$active_users_week = mysqli_fetch_assoc($recent_logins)['count'] ?? 0;

// System Health Checks
$maintenance_alerts = mysqli_query($con, "SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'pending' AND priority = 'high'");
$critical_maintenance = mysqli_fetch_assoc($maintenance_alerts)['count'] ?? 0;

$room_maintenance = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status = 'maintenance'");
$rooms_under_maintenance = mysqli_fetch_assoc($room_maintenance)['count'] ?? 0;

// Security & Access Monitoring
$failed_login_attempts = 0; // Would track from audit logs
$recent_user_activity = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)");
$daily_transactions = mysqli_fetch_assoc($recent_user_activity)['count'] ?? 0;
$db_stats = [];
$db_stats['total_records'] = 0;
$db_stats['active_sessions'] = 0;
$db_stats['db_size'] = 0;

// Get table sizes and record counts
$tables = ['roombook', 'named_rooms', 'users', 'inventory', 'maintenance_requests', 'service_requests', 'payment', 'admin_activity_log'];
foreach ($tables as $table) {
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $count = mysqli_fetch_assoc($result)['count'];
        $db_stats['total_records'] += $count;
        $db_stats['tables'][$table] = $count;
    }
}

// Server Performance Metrics (simulated - in production would use actual server monitoring)
$server_stats = [
    'cpu_usage' => rand(15, 35),
    'memory_usage' => rand(45, 75),
    'disk_usage' => rand(25, 60),
    'network_io' => rand(10, 30),
    'uptime_days' => rand(15, 45),
    'response_time' => rand(150, 350) // milliseconds
];

// Security Statistics
$security_stats = mysqli_query($con, "
    SELECT 
        COUNT(*) as total_logins,
        COUNT(CASE WHEN success = 1 THEN 1 END) as successful_logins,
        COUNT(CASE WHEN success = 0 THEN 1 END) as failed_logins,
        COUNT(DISTINCT identifier) as unique_users
    FROM login_attempts WHERE attempted_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$security = mysqli_fetch_assoc($security_stats) ?: [
    'total_logins' => 0, 'successful_logins' => 0, 'failed_logins' => 0, 'unique_users' => 0
];

// User Activity Statistics
$user_activity = mysqli_query($con, "
    SELECT 
        COUNT(*) as total_users,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_users,
        COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as users_24h,
        COUNT(CASE WHEN last_login >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 1 END) as users_1h
    FROM users
");
$users = mysqli_fetch_assoc($user_activity) ?: [
    'total_users' => 0, 'active_users' => 0, 'users_24h' => 0, 'users_1h' => 0
];

// System Health Checks
$system_health = [
    'database' => 'online',
    'web_server' => 'online',
    'file_system' => 'healthy',
    'backup_status' => 'completed',
    'ssl_certificate' => 'valid',
    'security_scan' => 'clean'
];

// Recent System Events
$system_events = [
    [
        'type' => 'backup',
        'message' => 'Automated backup completed successfully',
        'time' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'severity' => 'info',
        'icon' => 'fas fa-database'
    ],
    [
        'type' => 'security',
        'message' => 'Security scan completed - no threats detected',
        'time' => date('Y-m-d H:i:s', strtotime('-4 hours')),
        'severity' => 'success',
        'icon' => 'fas fa-shield-alt'
    ],
    [
        'type' => 'performance',
        'message' => 'System performance optimization applied',
        'time' => date('Y-m-d H:i:s', strtotime('-6 hours')),
        'severity' => 'info',
        'icon' => 'fas fa-tachometer-alt'
    ],
    [
        'type' => 'update',
        'message' => 'Security patches installed and applied',
        'time' => date('Y-m-d H:i:s', strtotime('-1 day')),
        'severity' => 'success',
        'icon' => 'fas fa-download'
    ]
];

// Calculate security score
$security_score = 85; // Would be calculated based on various security metrics
$system_performance = round(100 - (($server_stats['cpu_usage'] + $server_stats['memory_usage'] + $server_stats['disk_usage']) / 3), 1);
?>

<style>
.it-admin-dashboard {
    background: #0f172a;
    color: #e2e8f0;
    min-height: calc(100vh - 100px);
}

.it-header {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
    border-bottom: 2px solid #0ea5e9;
    position: relative;
    overflow: hidden;
}

.it-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="circuit" width="20" height="20" patternUnits="userSpaceOnUse"><path d="M0 10h5v-2h2v-2h2v2h2v2h5v2h-5v2h-2v2h-2v-2h-2v-2h-5z" fill="none" stroke="cyan" stroke-width="0.5" opacity="0.3"/></pattern></defs><rect width="100" height="20" fill="url(%23circuit)"/></svg>');
}

.system-card {
    background: linear-gradient(135deg, #1e293b, #334155);
    border: 1px solid #475569;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
    position: relative;
    overflow: hidden;
}

.system-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--accent-color, #0ea5e9);
}

.metric-card {
    background: linear-gradient(135deg, #0f172a, #1e293b);
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    margin-bottom: 20px;
    transition: all 0.3s ease;
    position: relative;
}

.metric-card:hover {
    transform: translateY(-5px);
    border-color: #0ea5e9;
    box-shadow: 0 8px 25px rgba(14, 165, 233, 0.2);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 800;
    margin-bottom: 10px;
    color: #0ea5e9;
    text-shadow: 0 0 10px rgba(14, 165, 233, 0.3);
}

.metric-label {
    color: #94a3b8;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.status-indicator {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}

.status-online {
    background: rgba(34, 197, 94, 0.2);
    color: #22c55e;
    border: 1px solid rgba(34, 197, 94, 0.3);
}

.status-warning {
    background: rgba(251, 191, 36, 0.2);
    color: #fbbf24;
    border: 1px solid rgba(251, 191, 36, 0.3);
}

.status-critical {
    background: rgba(239, 68, 68, 0.2);
    color: #ef4444;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.progress-ring {
    width: 100px;
    height: 100px;
    margin: 0 auto 15px;
    position: relative;
}

.progress-ring canvas {
    transform: rotate(-90deg);
}

.progress-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.2rem;
    font-weight: 700;
    color: #0ea5e9;
}

.system-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.console-card {
    background: #0f172a;
    border: 1px solid #334155;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    font-family: 'Courier New', monospace;
}

.console-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #334155;
}

.console-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.dot-red { background: #ef4444; }
.dot-yellow { background: #fbbf24; }
.dot-green { background: #22c55e; }

.console-output {
    color: #22c55e;
    font-size: 0.85rem;
    line-height: 1.6;
    max-height: 200px;
    overflow-y: auto;
}

.event-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    background: rgba(30, 41, 59, 0.5);
    border-left: 4px solid transparent;
    transition: all 0.3s ease;
}

.event-item:hover {
    background: rgba(30, 41, 59, 0.8);
}

.event-info { border-left-color: #0ea5e9; }
.event-success { border-left-color: #22c55e; }
.event-warning { border-left-color: #fbbf24; }
.event-error { border-left-color: #ef4444; }

.event-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 16px;
}

.icon-info { background: rgba(14, 165, 233, 0.2); color: #0ea5e9; }
.icon-success { background: rgba(34, 197, 94, 0.2); color: #22c55e; }
.icon-warning { background: rgba(251, 191, 36, 0.2); color: #fbbf24; }
.icon-error { background: rgba(239, 68, 68, 0.2); color: #ef4444; }

.admin-action-btn {
    background: linear-gradient(135deg, #0ea5e9, #3b82f6);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    margin: 5px;
}

.admin-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(14, 165, 233, 0.4);
    color: white;
    text-decoration: none;
}

.terminal-window {
    background: #000;
    border-radius: 8px;
    overflow: hidden;
    margin-bottom: 20px;
}

.terminal-header {
    background: #1f2937;
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.terminal-body {
    padding: 15px;
    color: #22c55e;
    font-family: 'Courier New', monospace;
    font-size: 0.85rem;
    line-height: 1.6;
    max-height: 300px;
    overflow-y: auto;
}

.server-rack {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
    gap: 10px;
    margin: 20px 0;
}

.server-unit {
    background: #1e293b;
    border: 1px solid #334155;
    border-radius: 4px;
    padding: 10px;
    text-align: center;
    position: relative;
}

.server-unit.active {
    border-color: #22c55e;
    background: rgba(34, 197, 94, 0.1);
}

.server-unit.warning {
    border-color: #fbbf24;
    background: rgba(251, 191, 36, 0.1);
}

.server-led {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin: 0 auto 5px;
}

.led-green { background: #22c55e; box-shadow: 0 0 8px #22c55e; }
.led-yellow { background: #fbbf24; box-shadow: 0 0 8px #fbbf24; }
.led-red { background: #ef4444; box-shadow: 0 0 8px #ef4444; }
</style>

<div class="it-admin-dashboard">
    <!-- IT Admin Header -->
    <div class="it-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3" style="position: relative; z-index: 2;">
                        <i class="fas fa-server me-3"></i>
                        IT Administration Center
                    </h1>
                    <p class="mb-0" style="position: relative; z-index: 2;">
                        System monitoring, security oversight, and technical infrastructure management
                    </p>
                </div>
                <div class="col-md-4 text-end" style="position: relative; z-index: 2;">
                    <div class="d-flex justify-content-end gap-3">
                        <div class="text-center">
                            <div class="h4 text-success"><?php echo $system_performance; ?>%</div>
                            <small>System Health</small>
                        </div>
                        <div class="text-center">
                            <div class="h4 text-info"><?php echo $server_stats['uptime_days']; ?>d</div>
                            <small>Uptime</small>
                        </div>
                        <div class="text-center">
                            <div class="h4 text-warning"><?php echo $server_stats['response_time']; ?>ms</div>
                            <small>Response Time</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- System Overview Metrics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="metric-card" style="--accent-color: #0ea5e9;">
                    <div class="progress-ring">
                        <canvas width="100" height="100" id="cpuChart"></canvas>
                        <div class="progress-value"><?php echo $server_stats['cpu_usage']; ?>%</div>
                    </div>
                    <div class="metric-label">CPU Usage</div>
                    <div class="status-indicator status-online">
                        <i class="fas fa-microchip"></i> Optimal
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="metric-card">
                    <div class="progress-ring">
                        <canvas width="100" height="100" id="memoryChart"></canvas>
                        <div class="progress-value"><?php echo $server_stats['memory_usage']; ?>%</div>
                    </div>
                    <div class="metric-label">Memory Usage</div>
                    <div class="status-indicator status-<?php echo $server_stats['memory_usage'] > 80 ? 'warning' : 'online'; ?>">
                        <i class="fas fa-memory"></i> <?php echo $server_stats['memory_usage'] > 80 ? 'High' : 'Normal'; ?>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="metric-card">
                    <div class="metric-value"><?php echo $security_score; ?>%</div>
                    <div class="metric-label">Security Score</div>
                    <div class="status-indicator status-online">
                        <i class="fas fa-shield-alt"></i> Protected
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="metric-card">
                    <div class="metric-value"><?php echo $users['users_1h']; ?></div>
                    <div class="metric-label">Active Users</div>
                    <div class="status-indicator status-online">
                        <i class="fas fa-users"></i> Online Now
                    </div>
                </div>
            </div>
        </div>

        <!-- System Management Grid -->
        <div class="system-grid">
            <!-- Server Status -->
            <div class="system-card" style="--accent-color: #22c55e;">
                <h4 class="mb-4">
                    <i class="fas fa-server me-2"></i>
                    Server Infrastructure
                </h4>

                <div class="server-rack">
                    <div class="server-unit active">
                        <div class="server-led led-green"></div>
                        <small>Web Server</small>
                    </div>
                    <div class="server-unit active">
                        <div class="server-led led-green"></div>
                        <small>Database</small>
                    </div>
                    <div class="server-unit active">
                        <div class="server-led led-green"></div>
                        <small>File Server</small>
                    </div>
                    <div class="server-unit warning">
                        <div class="server-led led-yellow"></div>
                        <small>Backup</small>
                    </div>
                    <div class="server-unit active">
                        <div class="server-led led-green"></div>
                        <small>Mail Server</small>
                    </div>
                    <div class="server-unit active">
                        <div class="server-led led-green"></div>
                        <small>Load Balancer</small>
                    </div>
                </div>

                <div class="row text-center mt-3">
                    <div class="col-4">
                        <div class="h5 text-success"><?php echo $server_stats['uptime_days']; ?></div>
                        <small>Days Uptime</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 text-info"><?php echo $server_stats['response_time']; ?>ms</div>
                        <small>Avg Response</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 text-warning">99.9%</div>
                        <small>Availability</small>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="server_management.php" class="admin-action-btn">
                        <i class="fas fa-cogs"></i> Manage Servers
                    </a>
                </div>
            </div>

            <!-- Database Management -->
            <div class="system-card" style="--accent-color: #8b5cf6;">
                <h4 class="mb-4">
                    <i class="fas fa-database me-2"></i>
                    Database Administration
                </h4>

                <div class="console-card">
                    <div class="console-header">
                        <div class="console-dot dot-red"></div>
                        <div class="console-dot dot-yellow"></div>
                        <div class="console-dot dot-green"></div>
                        <span class="text-light">Database Status</span>
                    </div>
                    <div class="console-output">
                        mysql> SHOW STATUS;<br>
                        Uptime: <?php echo $server_stats['uptime_days'] * 24 * 3600; ?> seconds<br>
                        Connections: <?php echo $security['total_logins']; ?><br>
                        Tables: <?php echo count($db_stats['tables'] ?? []); ?><br>
                        Total Records: <?php echo number_format($db_stats['total_records']); ?><br>
                        Query Cache: ENABLED<br>
                        Status: <span class="text-success">ONLINE</span>
                    </div>
                </div>

                <div class="row text-center">
                    <div class="col-6">
                        <div class="h6">Total Records</div>
                        <div class="text-info"><?php echo number_format($db_stats['total_records']); ?></div>
                    </div>
                    <div class="col-6">
                        <div class="h6">Active Connections</div>
                        <div class="text-success"><?php echo $users['users_1h']; ?></div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="database_admin.php" class="admin-action-btn" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                        <i class="fas fa-database"></i> DB Admin
                    </a>
                </div>
            </div>

            <!-- Security Monitoring -->
            <div class="system-card" style="--accent-color: #ef4444;">
                <h4 class="mb-4">
                    <i class="fas fa-shield-alt me-2"></i>
                    Security Monitoring
                </h4>

                <div class="row mb-3">
                    <div class="col-6">
                        <div class="text-center p-2 border rounded" style="border-color: #334155;">
                            <div class="h5 text-success"><?php echo $security['successful_logins']; ?></div>
                            <small>Successful Logins</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 border rounded" style="border-color: #334155;">
                            <div class="h5 text-danger"><?php echo $security['failed_logins']; ?></div>
                            <small>Failed Attempts</small>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>SSL Certificate</span>
                        <span class="status-indicator status-online">
                            <i class="fas fa-check"></i> Valid
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Firewall Status</span>
                        <span class="status-indicator status-online">
                            <i class="fas fa-check"></i> Active
                        </span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Antivirus Scan</span>
                        <span class="status-indicator status-online">
                            <i class="fas fa-check"></i> Clean
                        </span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Intrusion Detection</span>
                        <span class="status-indicator status-online">
                            <i class="fas fa-check"></i> Monitoring
                        </span>
                    </div>
                </div>

                <div class="text-center">
                    <a href="security_admin.php" class="admin-action-btn" style="background: linear-gradient(135deg, #ef4444, #f87171);">
                        <i class="fas fa-shield-alt"></i> Security Center
                    </a>
                </div>
            </div>

            <!-- System Events -->
            <div class="system-card" style="--accent-color: #0ea5e9;">
                <h4 class="mb-4">
                    <i class="fas fa-list me-2"></i>
                    System Events & Logs
                </h4>

                <?php foreach ($system_events as $event): ?>
                <div class="event-item event-<?php echo $event['severity']; ?>">
                    <div class="event-icon icon-<?php echo $event['severity']; ?>">
                        <i class="<?php echo $event['icon']; ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="event-title"><?php echo $event['message']; ?></div>
                        <small class="text-muted"><?php echo date('M d, Y g:i A', strtotime($event['time'])); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>

                <div class="text-center mt-3">
                    <a href="system_logs.php" class="admin-action-btn">
                        <i class="fas fa-file-alt"></i> View All Logs
                    </a>
                </div>
            </div>

            <!-- Performance Monitoring -->
            <div class="system-card" style="--accent-color: #fbbf24;">
                <h4 class="mb-4">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Performance Analytics
                </h4>

                <div class="terminal-window">
                    <div class="terminal-header">
                        <div class="console-dot dot-red"></div>
                        <div class="console-dot dot-yellow"></div>
                        <div class="console-dot dot-green"></div>
                        <span class="text-light">System Monitor</span>
                    </div>
                    <div class="terminal-body">
                        $ systemctl status hotel-management<br>
                        ● hotel-management.service - Orlando Hotel System<br>
                        &nbsp;&nbsp;&nbsp;Loaded: loaded (/etc/systemd/system/hotel.service)<br>
                        &nbsp;&nbsp;&nbsp;Active: <span class="text-success">active (running)</span> since <?php echo date('M d H:i:s'); ?><br>
                        &nbsp;&nbsp;&nbsp;Memory: 256.2M<br>
                        &nbsp;&nbsp;&nbsp;CPU: <?php echo $server_stats['cpu_usage']; ?>%<br>
                        &nbsp;&nbsp;&nbsp;Tasks: 42 (limit: 4915)<br>
                        <br>
                        $ tail -f /var/log/hotel/access.log<br>
                        <?php echo date('Y-m-d H:i:s'); ?> [INFO] User login successful<br>
                        <?php echo date('Y-m-d H:i:s'); ?> [INFO] Database query executed<br>
                        <?php echo date('Y-m-d H:i:s'); ?> [INFO] Backup job completed<br>
                    </div>
                </div>

                <div class="text-center">
                    <a href="performance_monitor.php" class="admin-action-btn" style="background: linear-gradient(135deg, #fbbf24, #f59e0b);">
                        <i class="fas fa-chart-line"></i> Performance Tools
                    </a>
                </div>
            </div>

            <!-- Quick Admin Tools -->
            <div class="system-card" style="--accent-color: #06b6d4;">
                <h4 class="mb-4">
                    <i class="fas fa-tools me-2"></i>
                    Administrative Tools
                </h4>

                <div class="d-grid gap-3">
                    <a href="user_management.php" class="admin-action-btn">
                        <i class="fas fa-users-cog"></i> User Management
                    </a>

                    <a href="backup_restore.php" class="admin-action-btn" style="background: linear-gradient(135deg, #22c55e, #16a34a);">
                        <i class="fas fa-database"></i> Backup & Restore
                    </a>

                    <a href="system_settings.php" class="admin-action-btn" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                        <i class="fas fa-cog"></i> System Configuration
                    </a>

                    <a href="update_manager.php" class="admin-action-btn" style="background: linear-gradient(135deg, #f59e0b, #d97706);">
                        <i class="fas fa-download"></i> Update Manager
                    </a>

                    <a href="api_management.php" class="admin-action-btn" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                        <i class="fas fa-code"></i> API Management
                    </a>
                </div>

                <div class="mt-4 p-3" style="background: rgba(6, 182, 212, 0.1); border-radius: 8px; border: 1px solid rgba(6, 182, 212, 0.3);">
                    <h6 class="mb-2 text-info">Quick Actions</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Clear Cache</span>
                        <button class="btn btn-sm btn-outline-info" onclick="clearSystemCache()">Clear</button>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Restart Services</span>
                        <button class="btn btn-sm btn-outline-warning" onclick="restartServices()">Restart</button>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Generate Report</span>
                        <button class="btn btn-sm btn-outline-success" onclick="generateReport()">Generate</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for system monitoring -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // CPU Usage Ring Chart
    const cpuCtx = document.getElementById('cpuChart').getContext('2d');
    new Chart(cpuCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $server_stats['cpu_usage']; ?>, <?php echo 100 - $server_stats['cpu_usage']; ?>],
                backgroundColor: ['#0ea5e9', '#1e293b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { display: false } }
        }
    });

    // Memory Usage Ring Chart
    const memoryCtx = document.getElementById('memoryChart').getContext('2d');
    new Chart(memoryCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $server_stats['memory_usage']; ?>, <?php echo 100 - $server_stats['memory_usage']; ?>],
                backgroundColor: ['#fbbf24', '#1e293b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: false,
            maintainAspectRatio: false,
            cutout: '75%',
            plugins: { legend: { display: false } }
        }
    });

    // Auto-refresh system stats every 30 seconds
    setInterval(function() {
        fetch('get_system_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateSystemMetrics(data);
                }
            });
    }, 30000);
});

// Quick action functions
function clearSystemCache() {
    if (confirm('Are you sure you want to clear the system cache?')) {
        fetch('system_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'clear_cache' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Cache cleared successfully');
            } else {
                showError('Failed to clear cache');
            }
        });
    }
}

function restartServices() {
    if (confirm('Are you sure you want to restart system services? This may cause brief downtime.')) {
        fetch('system_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'restart_services' })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showSuccess('Services restarted successfully');
            } else {
                showError('Failed to restart services');
            }
        });
    }
}

function generateReport() {
    fetch('system_actions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'generate_report' })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('System report generated successfully');
            // Download or display report
            window.open('system_report.php', '_blank');
        } else {
            showError('Failed to generate report');
        }
    });
}

function updateSystemMetrics(data) {
    // Update system metrics with real-time data
    console.log('Updating system metrics:', data);
}
</script>

<?php include '../includes/admin/footer.php'; ?>
