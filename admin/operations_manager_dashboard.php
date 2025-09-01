<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has operations manager permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'Operations_Manager', 'DeptManager', 'Super_Admin', 'operations_manager', 'director', 'admin', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'Operations Manager Dashboard';
include '../includes/admin/header.php';

// Include database connection
include 'db.php';

// Operations Manager Dashboard - Full Operations Access, View-Only Financial
$today = date('Y-m-d');
$this_month = date('Y-m');

// Room Management - Full Access (using roombook table since room_status doesn't exist)
$room_stats = mysqli_query($con, "SELECT 
    COUNT(nr.room_name) as total_rooms,
    COUNT(CASE WHEN rb.TRoom IS NOT NULL AND rb.cin <= CURDATE() AND rb.cout >= CURDATE() AND rb.stat = 'Confirm' THEN 1 END) as occupied_rooms,
    COUNT(CASE WHEN rb.TRoom IS NULL OR rb.cout < CURDATE() OR rb.stat != 'Confirm' THEN 1 END) as available_rooms,
    0 as maintenance_rooms,
    0 as cleaning_rooms
    FROM named_rooms nr 
    LEFT JOIN roombook rb ON nr.room_name = rb.TRoom 
    WHERE nr.is_active = 1");
$room_data = mysqli_fetch_assoc($room_stats);
$room_data['occupancy_rate'] = $room_data['total_rooms'] > 0 ? round(($room_data['occupied_rooms'] / $room_data['total_rooms']) * 100, 1) : 0;

// Guest Management - Full Access
$todays_checkins = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE()");
$checkins_data = mysqli_fetch_assoc($todays_checkins)['count'] ?? 0;

$todays_checkouts = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cout) = CURDATE()");
$checkouts_data = mysqli_fetch_assoc($todays_checkouts)['count'] ?? 0;

$active_guests = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cin <= CURDATE() AND cout >= CURDATE() AND stat = 'Confirm'");
$active_guests_data = mysqli_fetch_assoc($active_guests)['count'] ?? 0;

// Inventory Management - Full Access
$kitchen_items = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock FROM kitchen_inventory WHERE is_active = 1");
$kitchen_data = mysqli_fetch_assoc($kitchen_items);

$bar_items = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock FROM bar_inventory WHERE is_active = 1");
$bar_data = mysqli_fetch_assoc($bar_items);

// Maintenance System - Full Access
$maintenance_requests = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'open' THEN 1 END) as pending, COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress FROM maintenance_requests");
$maintenance_data = mysqli_fetch_assoc($maintenance_requests);

// Staff Coordination
$total_staff = mysqli_query($con, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$staff_count = mysqli_fetch_assoc($total_staff)['count'] ?? 0;

$housekeeping_tasks = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending FROM housekeeping_tasks WHERE DATE(created_at) = CURDATE()");
$housekeeping_data = mysqli_fetch_assoc($housekeeping_tasks);

// Financial View-Only Data
$todays_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE DATE(cin) = CURDATE()");
$revenue_data = mysqli_fetch_assoc($todays_revenue)['revenue'] ?? 0;

$monthly_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$monthly_revenue_data = mysqli_fetch_assoc($monthly_revenue)['revenue'] ?? 0;

// Room status statistics - using roombook table for occupancy
$room_stats = mysqli_query($con, "
    SELECT 
        COUNT(DISTINCT nr.room_name) as total_rooms,
        COUNT(CASE WHEN rb.stat = 'Confirm' AND CURDATE() BETWEEN rb.cin AND rb.cout THEN 1 END) as occupied,
        COUNT(CASE WHEN rb.stat IS NULL OR rb.stat != 'Confirm' OR CURDATE() NOT BETWEEN rb.cin AND rb.cout THEN 1 END) as available,
        0 as maintenance,
        0 as cleaning,
        0 as out_of_order
    FROM named_rooms nr
    LEFT JOIN roombook rb ON nr.room_name = rb.TRoom 
    WHERE nr.room_name IS NOT NULL
");
$rooms = mysqli_fetch_assoc($room_stats);

// Today's check-ins and check-outs
$checkin_stats = mysqli_query($con, "
    SELECT 
        COUNT(CASE WHEN DATE(cin) = '$today' AND status = 'confirmed' THEN 1 END) as todays_checkins,
        COUNT(CASE WHEN DATE(cout) = '$today' AND status IN ('checked_in', 'confirmed') THEN 1 END) as todays_checkouts,
        COUNT(CASE WHEN DATE(cin) = '$today' AND status = 'confirmed' AND cin <= NOW() THEN 1 END) as pending_checkins,
        COUNT(CASE WHEN DATE(cout) = '$today' AND status = 'checked_in' THEN 1 END) as pending_checkouts
    FROM roombook
");
$checkins = mysqli_fetch_assoc($checkin_stats);

// Staff on duty today - using all active users since role names aren't directly in users table
$staff_stats = mysqli_query($con, "
    SELECT 
        COUNT(*) as total_staff,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_staff,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 8 HOUR) THEN 1 END) as staff_online
    FROM users WHERE status = 'active'
");
$staff = mysqli_fetch_assoc($staff_stats) ?: ['total_staff' => 0, 'active_staff' => 0, 'staff_online' => 0];

// Maintenance requests
$maintenance_stats = mysqli_query($con, "SELECT COUNT(*) as total_requests, COUNT(CASE WHEN priority = 'high' THEN 1 END) as `high_priority`, COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as `urgent`, COUNT(CASE WHEN status = 'open' THEN 1 END) as `pending` FROM maintenance_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$maintenance = mysqli_fetch_assoc($maintenance_stats) ?: ['total_requests' => 0, 'high_priority' => 0, 'urgent' => 0, 'pending' => 0];

// Inventory alerts - combining kitchen and bar inventory
$kitchen_inventory_stats = mysqli_query($con, "SELECT COUNT(*) as total_items, COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock, COUNT(CASE WHEN current_stock = 0 THEN 1 END) as out_of_stock FROM kitchen_inventory WHERE is_active = 1");
$kitchen_inventory = mysqli_fetch_assoc($kitchen_inventory_stats) ?: ['total_items' => 0, 'low_stock' => 0, 'out_of_stock' => 0];

$bar_inventory_stats = mysqli_query($con, "SELECT COUNT(*) as total_items, COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock, COUNT(CASE WHEN current_stock = 0 THEN 1 END) as out_of_stock FROM bar_inventory WHERE is_active = 1");
$bar_inventory = mysqli_fetch_assoc($bar_inventory_stats) ?: ['total_items' => 0, 'low_stock' => 0, 'out_of_stock' => 0];

// Combine inventory stats
$inventory = [
    'total_items' => $kitchen_inventory['total_items'] + $bar_inventory['total_items'],
    'low_stock' => $kitchen_inventory['low_stock'] + $bar_inventory['low_stock'],
    'out_of_stock' => $kitchen_inventory['out_of_stock'] + $bar_inventory['out_of_stock']
];

// Service requests - using maintenance_requests as proxy since service_requests table doesn't exist
$service_stats = mysqli_query($con, "SELECT COUNT(*) as total_requests, COUNT(CASE WHEN status = 'open' THEN 1 END) as pending, COUNT(CASE WHEN priority = 'high' THEN 1 END) as `high_priority` FROM maintenance_requests WHERE DATE(created_at) = '$today'");
$services = mysqli_fetch_assoc($service_stats) ?: ['total_requests' => 0, 'pending' => 0, 'high_priority' => 0];

// Calculate efficiency metrics
$occupancy_rate = $rooms['total_rooms'] > 0 ? round(($rooms['occupied'] / $rooms['total_rooms']) * 100, 1) : 0;
$maintenance_efficiency = $maintenance['total_requests'] > 0 ? round((($maintenance['total_requests'] - $maintenance['pending']) / $maintenance['total_requests']) * 100, 1) : 100;
$staff_availability = $staff['total_staff'] > 0 ? round(($staff['staff_online'] / $staff['total_staff']) * 100, 1) : 0;
?>

<style>
.ops-manager-dashboard {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.ops-header {
    background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%);
    color: white;
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
    position: relative;
    overflow: hidden;
}

.ops-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grid" width="10" height="10" patternUnits="userSpaceOnUse"><path d="M 10 0 L 0 0 0 10" fill="none" stroke="white" stroke-width="0.5" opacity="0.3"/></pattern></defs><rect width="100" height="20" fill="url(%23grid)"/></svg>');
}

.ops-metric-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-left: 4px solid var(--accent-color, #3b82f6);
    margin-bottom: 25px;
    transition: transform 0.3s ease;
}

.ops-metric-card:hover {
    transform: translateY(-5px);
}

.metric-value {
    font-size: 2.5rem;
    font-weight: 800;
    color: #2d3748;
    margin-bottom: 8px;
}

.metric-label {
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
    margin-bottom: 10px;
}

.metric-trend {
    font-size: 0.8rem;
    font-weight: 600;
}

.efficiency-ring {
    width: 80px;
    height: 80px;
    position: relative;
    margin: 0 auto 15px;
}

.efficiency-ring canvas {
    transform: rotate(-90deg);
}

.efficiency-value {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 1.2rem;
    font-weight: 700;
    color: #2d3748;
}

.operations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 25px;
}

.ops-section {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    height: fit-content;
}

.task-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 10px;
    border: 1px solid #f1f5f9;
    transition: all 0.3s ease;
}

.task-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
}

.task-priority-urgent {
    border-left: 4px solid #ef4444;
    background: #fef2f2;
}

.task-priority-high {
    border-left: 4px solid #f59e0b;
    background: #fffbeb;
}

.task-priority-medium {
    border-left: 4px solid #3b82f6;
    background: #eff6ff;
}

.task-priority-low {
    border-left: 4px solid #10b981;
    background: #f0fdf4;
}

.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 10px;
}

.status-online { background: #10b981; }
.status-busy { background: #f59e0b; }
.status-offline { background: #ef4444; }
.status-break { background: #6b7280; }

.quick-action-btn {
    background: linear-gradient(135deg, var(--btn-color-1), var(--btn-color-2));
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    margin: 5px;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    color: white;
    text-decoration: none;
}
</style>

<div class="ops-manager-dashboard">
    <!-- Operations Header -->
    <div class="ops-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-3" style="position: relative; z-index: 2;">
                        <i class="fas fa-cogs me-3"></i>
                        Operations Command Center
                    </h1>
                    <p class="mb-0" style="position: relative; z-index: 2;">
                        Real-time operational oversight and performance monitoring
                    </p>
                </div>
                <div class="col-md-4 text-end" style="position: relative; z-index: 2;">
                    <div class="text-white">
                        <div style="font-size: 1.5rem; font-weight: 600;">
                            <?php echo date('H:i'); ?>
                        </div>
                        <div style="opacity: 0.9;">
                            <?php echo date('l, M d, Y'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Key Operational Metrics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-metric-card" style="--accent-color: #10b981;">
                    <div class="text-center">
                        <div class="efficiency-ring">
                            <canvas width="80" height="80" id="occupancyRing"></canvas>
                            <div class="efficiency-value"><?php echo $occupancy_rate; ?>%</div>
                        </div>
                        <div class="metric-label">Room Occupancy</div>
                        <div class="metric-trend text-success">
                            <i class="fas fa-bed"></i> <?php echo $rooms['occupied']; ?>/<?php echo $rooms['total_rooms']; ?> rooms
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-metric-card" style="--accent-color: #3b82f6;">
                    <div class="text-center">
                        <div class="metric-value"><?php echo $checkins['todays_checkins']; ?></div>
                        <div class="metric-label">Today's Check-ins</div>
                        <div class="metric-trend text-info">
                            <i class="fas fa-clock"></i> <?php echo $checkins['pending_checkins']; ?> pending
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-metric-card" style="--accent-color: #f59e0b;">
                    <div class="text-center">
                        <div class="efficiency-ring">
                            <canvas width="80" height="80" id="staffRing"></canvas>
                            <div class="efficiency-value"><?php echo $staff_availability; ?>%</div>
                        </div>
                        <div class="metric-label">Staff Availability</div>
                        <div class="metric-trend text-warning">
                            <i class="fas fa-users"></i> <?php echo $staff['staff_online']; ?>/<?php echo $staff['total_staff']; ?> online
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-metric-card" style="--accent-color: #ef4444;">
                    <div class="text-center">
                        <div class="metric-value"><?php echo $maintenance['pending']; ?></div>
                        <div class="metric-label">Pending Issues</div>
                        <div class="metric-trend text-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo $maintenance['urgent']; ?> urgent
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operational Overview Grid -->
        <div class="operations-grid">
            <!-- Room Status Overview -->
            <div class="ops-section">
                <h4 class="mb-4">
                    <i class="fas fa-door-open me-2 text-primary"></i>
                    Room Status Overview
                </h4>

                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="h4 text-success mb-1"><?php echo $rooms['available']; ?></div>
                            <small class="text-muted">Available</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="h4 text-primary mb-1"><?php echo $rooms['occupied']; ?></div>
                            <small class="text-muted">Occupied</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="h4 text-warning mb-1"><?php echo $rooms['cleaning']; ?></div>
                            <small class="text-muted">Cleaning</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="text-center p-3 border rounded">
                            <div class="h4 text-danger mb-1"><?php echo $rooms['maintenance']; ?></div>
                            <small class="text-muted">Maintenance</small>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="room.php" class="quick-action-btn" style="--btn-color-1: #3b82f6; --btn-color-2: #60a5fa;">
                        <i class="fas fa-cog"></i> Manage Rooms
                    </a>
                </div>
            </div>

            <!-- Priority Tasks -->
            <div class="ops-section">
                <h4 class="mb-4">
                    <i class="fas fa-tasks me-2 text-warning"></i>
                    Priority Tasks & Alerts
                </h4>

                <?php if ($maintenance['urgent'] > 0): ?>
                <div class="task-item task-priority-urgent">
                    <i class="fas fa-exclamation-triangle text-danger me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Urgent Maintenance</strong>
                        <div class="small text-muted"><?php echo $maintenance['urgent']; ?> item(s) need immediate attention</div>
                    </div>
                    <a href="maintenance_management.php" class="btn btn-danger btn-sm">Fix Now</a>
                </div>
                <?php endif; ?>

                <?php if ($inventory['out_of_stock'] > 0): ?>
                <div class="task-item task-priority-high">
                    <i class="fas fa-boxes text-warning me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Out of Stock Items</strong>
                        <div class="small text-muted"><?php echo $inventory['out_of_stock']; ?> items completely depleted</div>
                    </div>
                    <a href="inventory.php" class="btn btn-warning btn-sm">Reorder</a>
                </div>
                <?php endif; ?>

                <?php if ($checkins['pending_checkins'] > 0): ?>
                <div class="task-item task-priority-medium">
                    <i class="fas fa-door-open text-primary me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Pending Check-ins</strong>
                        <div class="small text-muted"><?php echo $checkins['pending_checkins']; ?> guests waiting to check in</div>
                    </div>
                    <a href="reservation.php" class="btn btn-primary btn-sm">Process</a>
                </div>
                <?php endif; ?>

                <?php if ($services['pending'] > 0): ?>
                <div class="task-item task-priority-low">
                    <i class="fas fa-concierge-bell text-success me-3"></i>
                    <div class="flex-grow-1">
                        <strong>Service Requests</strong>
                        <div class="small text-muted"><?php echo $services['pending']; ?> pending guest requests</div>
                    </div>
                    <a href="guest_services.php" class="btn btn-success btn-sm">Handle</a>
                </div>
                <?php endif; ?>

                <?php if ($maintenance['urgent'] == 0 && $inventory['out_of_stock'] == 0 && $checkins['pending_checkins'] == 0 && $services['pending'] == 0): ?>
                <div class="text-center text-muted py-4">
                    <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                    <p>All systems operational. No urgent tasks.</p>
                </div>
                <?php endif; ?>
            </div>

            <!-- Staff Status -->
            <div class="ops-section">
                <h4 class="mb-4">
                    <i class="fas fa-users me-2 text-info"></i>
                    Staff Status & Availability
                </h4>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Housekeeping</span>
                        <span class="badge bg-success">3 online</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" style="width: 75%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Maintenance</span>
                        <span class="badge bg-warning">2 online</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" style="width: 67%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Front Desk</span>
                        <span class="badge bg-primary">4 online</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-primary" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span>Security</span>
                        <span class="badge bg-info">2 online</span>
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-info" style="width: 100%;"></div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="user_management.php" class="quick-action-btn" style="--btn-color-1: #06b6d4; --btn-color-2: #67e8f9;">
                        <i class="fas fa-users-cog"></i> Manage Staff
                    </a>
                </div>
            </div>

            <!-- Quick Operations -->
            <div class="ops-section">
                <h4 class="mb-4">
                    <i class="fas fa-bolt me-2 text-success"></i>
                    Quick Operations
                </h4>

                <div class="d-grid gap-3">
                    <a href="staff_booking.php" class="quick-action-btn" style="--btn-color-1: #10b981; --btn-color-2: #34d399;">
                        <i class="fas fa-plus-circle"></i> New Booking
                    </a>

                    <a href="housekeeping.php" class="quick-action-btn" style="--btn-color-1: #3b82f6; --btn-color-2: #60a5fa;">
                        <i class="fas fa-broom"></i> Housekeeping Tasks
                    </a>

                    <a href="maintenance_management.php" class="quick-action-btn" style="--btn-color-1: #f59e0b; --btn-color-2: #fbbf24;">
                        <i class="fas fa-tools"></i> Maintenance Queue
                    </a>

                    <a href="inventory.php" class="quick-action-btn" style="--btn-color-1: #8b5cf6; --btn-color-2: #a78bfa;">
                        <i class="fas fa-boxes"></i> Inventory Check
                    </a>

                    <a href="room_revenue.php" class="quick-action-btn" style="--btn-color-1: #ef4444; --btn-color-2: #f87171;">
                        <i class="fas fa-chart-bar"></i> Room Revenue
                    </a>

                    <a href="housekeeping_management.php" class="quick-action-btn" style="--btn-color-1: #06b6d4; --btn-color-2: #67e8f9;">
                        <i class="fas fa-spray-can"></i> Housekeeping Management
                    </a>

                    <a href="reservation.php" class="quick-action-btn" style="--btn-color-1: #10b981; --btn-color-2: #34d399;">
                        <i class="fas fa-calendar-check"></i> Reservations
                    </a>

                    <a href="revenue_analytics.php" class="quick-action-btn" style="--btn-color-1: #8b5cf6; --btn-color-2: #a78bfa;">
                        <i class="fas fa-analytics"></i> Revenue Analytics
                    </a>
                </div>

                <div class="mt-4 p-3 bg-light rounded">
                    <h6 class="mb-2">System Health</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Server Status</span>
                        <span class="badge bg-success">Online</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database</span>
                        <span class="badge bg-success">Connected</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Last Backup</span>
                        <span class="text-muted small">2 hours ago</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for efficiency rings -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Occupancy Ring Chart
    const occupancyCtx = document.getElementById('occupancyRing').getContext('2d');
    new Chart(occupancyCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $occupancy_rate; ?>, <?php echo 100 - $occupancy_rate; ?>],
                backgroundColor: ['#10b981', '#f3f4f6'],
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

    // Staff Availability Ring Chart
    const staffCtx = document.getElementById('staffRing').getContext('2d');
    new Chart(staffCtx, {
        type: 'doughnut',
        data: {
            datasets: [{
                data: [<?php echo $staff_availability; ?>, <?php echo 100 - $staff_availability; ?>],
                backgroundColor: ['#f59e0b', '#f3f4f6'],
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

    // Auto-refresh data every 2 minutes
    setInterval(function() {
        fetch('get_operations_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update metrics with new data
                    updateOperationsMetrics(data);
                }
            });
    }, 120000);
});

function updateOperationsMetrics(data) {
    // Update DOM elements with new data
    console.log('Updating operations metrics:', data);
}
</script>

<?php include '../includes/admin/footer.php'; ?>
