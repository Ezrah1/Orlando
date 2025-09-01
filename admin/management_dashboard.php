<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has management permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'Manager', 'Department_Head', 'DeptManager', 'Super_Admin', 'manager', 'department_head', 'admin', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'Management Dashboard';
include '../includes/admin/header.php';

// Management Dashboard - Department Head Access Level
$today = date('Y-m-d');

// Get current user's department for filtering
$current_user_dept = $_SESSION['user_dept_id'] ?? null;

// Department-Specific Financial View
$dept_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$revenue_data = mysqli_fetch_assoc($dept_revenue)['revenue'] ?? 0;

// Department Staff Management 
$dept_staff = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'active' THEN 1 END) as active FROM users" . ($current_user_dept ? " WHERE dept_id = $current_user_dept" : ""));
$staff_data = mysqli_fetch_assoc($dept_staff);

// Room Management - Related to Department
$room_status = mysqli_query($con, "SELECT 
    COUNT(nr.room_name) as total_rooms,
    COUNT(CASE WHEN rs.current_status = 'occupied' THEN 1 END) as occupied,
    COUNT(CASE WHEN rs.current_status = 'available' THEN 1 END) as available,
    COUNT(CASE WHEN rs.current_status = 'maintenance' THEN 1 END) as maintenance
    FROM named_rooms nr 
    LEFT JOIN room_status rs ON nr.room_name = rs.room_name 
    WHERE nr.is_active = 1");
$room_data = mysqli_fetch_assoc($room_status);

// Guest Service Management - Department Related
$todays_checkins = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE()");
$checkins_data = mysqli_fetch_assoc($todays_checkins)['count'] ?? 0;

$active_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Confirm' AND cin <= CURDATE() AND cout >= CURDATE()");
$active_bookings_data = mysqli_fetch_assoc($active_bookings)['count'] ?? 0;

// Department-Specific Inventory (if applicable)
$dept_inventory_alerts = 0;
if ($current_user_dept) {
    // Kitchen inventory for F&B departments
    $kitchen_alerts = mysqli_query($con, "SELECT COUNT(*) as count FROM kitchen_inventory WHERE quantity <= min_level");
    $kitchen_alerts_data = mysqli_fetch_assoc($kitchen_alerts)['count'] ?? 0;
    
    // Bar inventory alerts
    $bar_alerts = mysqli_query($con, "SELECT COUNT(*) as count FROM bar_inventory WHERE quantity <= minimum_level");
    $bar_alerts_data = mysqli_fetch_assoc($bar_alerts)['count'] ?? 0;
    
    $dept_inventory_alerts = $kitchen_alerts_data + $bar_alerts_data;
}

// Department-Specific Maintenance Requests
$dept_maintenance = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending FROM maintenance_requests WHERE MONTH(created_at) = MONTH(CURDATE())");
$maintenance_data = mysqli_fetch_assoc($dept_maintenance);

// Performance Metrics for Department
$monthly_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$bookings_data = mysqli_fetch_assoc($monthly_bookings)['count'] ?? 0;

$total_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$bookings_data = mysqli_fetch_assoc($total_bookings);

$avg_occupancy = mysqli_query($con, "SELECT COUNT(CASE WHEN status = 'occupied' THEN 1 END) * 100.0 / COUNT(*) as rate FROM named_rooms WHERE is_active = 1");
$occupancy_data = mysqli_fetch_assoc($avg_occupancy);

$customer_satisfaction = 4.2; // This would come from a reviews/ratings table
?>

<style>
.management-dashboard {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.management-header {
    background: linear-gradient(135deg, #4c1d95 0%, #7c3aed 100%);
    color: white;
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
}

.kpi-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
    position: relative;
    overflow: hidden;
}

.kpi-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--accent-color, #7c3aed);
}

.kpi-stat {
    text-align: center;
    padding: 15px;
}

.kpi-stat .value {
    font-size: 2.8rem;
    font-weight: 800;
    color: #2d3748;
    margin-bottom: 10px;
}

.kpi-stat .label {
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 1px;
}

.dept-performance {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
}

.dept-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f1f5f9;
}

.dept-item:last-child {
    border-bottom: none;
}

.dept-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
    color: white;
}

.progress-ring {
    width: 60px;
    height: 60px;
    margin-left: auto;
}

.strategic-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.action-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease;
}

.action-card:hover {
    transform: translateY(-5px);
}
</style>

<div class="management-dashboard">
    <!-- Management Header -->
    <div class="management-header">
        <div class="container">
            <h1 class="mb-3">
                <i class="fas fa-chart-line me-3"></i>
                Executive Management Center
            </h1>
            <p class="mb-0">Strategic overview and departmental performance monitoring</p>
        </div>
    </div>

    <div class="container">
        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="kpi-card" style="--accent-color: #10b981;">
                    <div class="kpi-stat">
                        <div class="value">$<?php echo number_format($revenue_data['revenue']); ?></div>
                        <div class="label">Monthly Revenue</div>
                        <small class="text-success">
                            <i class="fas fa-trending-up"></i> +15% vs last month
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="kpi-card" style="--accent-color: #3b82f6;">
                    <div class="kpi-stat">
                        <div class="value"><?php echo number_format($occupancy_data['rate'], 1); ?>%</div>
                        <div class="label">Occupancy Rate</div>
                        <small class="text-info">
                            <i class="fas fa-bed"></i> Average this month
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="kpi-card" style="--accent-color: #f59e0b;">
                    <div class="kpi-stat">
                        <div class="value"><?php echo $bookings_data['count']; ?></div>
                        <div class="label">Total Bookings</div>
                        <small class="text-warning">
                            <i class="fas fa-calendar-check"></i> This month
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="kpi-card" style="--accent-color: #8b5cf6;">
                    <div class="kpi-stat">
                        <div class="value"><?php echo number_format($customer_satisfaction, 1); ?>/5</div>
                        <div class="label">Guest Satisfaction</div>
                        <small class="text-purple">
                            <i class="fas fa-star"></i> Average rating
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Department Performance Overview -->
        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="dept-performance">
                    <h4 class="mb-4">
                        <i class="fas fa-building me-2"></i>
                        Department Performance Overview
                    </h4>

                    <div class="dept-item">
                        <div class="dept-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Front Office & Reservations</h6>
                            <p class="text-muted mb-2">Guest services and booking management</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 92%;"></div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="h5 mb-0 text-success">92%</div>
                            <small class="text-muted">Efficiency</small>
                        </div>
                    </div>

                    <div class="dept-item">
                        <div class="dept-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa);">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Housekeeping</h6>
                            <p class="text-muted mb-2">Room cleaning and maintenance</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-primary" style="width: 88%;"></div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="h5 mb-0 text-primary">88%</div>
                            <small class="text-muted">Efficiency</small>
                        </div>
                    </div>

                    <div class="dept-item">
                        <div class="dept-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24);">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Food & Beverage</h6>
                            <p class="text-muted mb-2">Restaurant and bar operations</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-warning" style="width: 85%;"></div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="h5 mb-0 text-warning">85%</div>
                            <small class="text-muted">Efficiency</small>
                        </div>
                    </div>

                    <div class="dept-item">
                        <div class="dept-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa);">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Maintenance</h6>
                            <p class="text-muted mb-2">Facility maintenance and repairs</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-purple" style="width: 78%;"></div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="h5 mb-0 text-purple">78%</div>
                            <small class="text-muted">Efficiency</small>
                        </div>
                    </div>

                    <div class="dept-item">
                        <div class="dept-icon" style="background: linear-gradient(135deg, #10b981, #34d399);">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Finance & Accounting</h6>
                            <p class="text-muted mb-2">Financial management and reporting</p>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-success" style="width: 95%;"></div>
                            </div>
                        </div>
                        <div class="text-end ms-3">
                            <div class="h5 mb-0 text-success">95%</div>
                            <small class="text-muted">Efficiency</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="dept-performance">
                    <h4 class="mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Critical Alerts
                    </h4>

                    <div class="alert alert-danger d-flex align-items-center mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <div>
                            <strong>Maintenance Required</strong><br>
                            <small>2 rooms need immediate attention</small>
                        </div>
                    </div>

                    <div class="alert alert-warning d-flex align-items-center mb-3">
                        <i class="fas fa-clock me-2"></i>
                        <div>
                            <strong>Staff Shortage</strong><br>
                            <small>Housekeeping shift tomorrow</small>
                        </div>
                    </div>

                    <div class="alert alert-info d-flex align-items-center mb-3">
                        <i class="fas fa-chart-line me-2"></i>
                        <div>
                            <strong>Revenue Target</strong><br>
                            <small>85% of monthly goal reached</small>
                        </div>
                    </div>

                    <div class="text-center">
                        <a href="alerts_management.php" class="btn btn-outline-primary btn-sm">
                            View All Alerts
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategic Actions -->
        <div class="strategic-actions">
            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #10b981, #34d399); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-chart-bar"></i>
                    </div>
                    <h5 class="mb-0 ms-3">Revenue Analytics</h5>
                </div>
                <p class="text-muted mb-3">Comprehensive financial analysis and forecasting</p>
                <a href="revenue_analytics.php" class="btn btn-success btn-sm">Access Analytics</a>
            </div>

            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #3b82f6, #60a5fa); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5 class="mb-0 ms-3">Staff Management</h5>
                </div>
                <p class="text-muted mb-3">Employee scheduling and performance tracking</p>
                <a href="user_management.php" class="btn btn-primary btn-sm">Manage Staff</a>
            </div>

            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #f59e0b, #fbbf24); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-file-contract"></i>
                    </div>
                    <h5 class="mb-0 ms-3">Strategic Reports</h5>
                </div>
                <p class="text-muted mb-3">Executive reports and business intelligence</p>
                <a href="financial_reports.php" class="btn btn-warning btn-sm">Generate Reports</a>
            </div>

            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #8b5cf6, #a78bfa); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h5 class="mb-0 ms-3">System Settings</h5>
                </div>
                <p class="text-muted mb-3">Configure hotel operations and policies</p>
                <a href="settings.php" class="btn btn-purple btn-sm">Access Settings</a>
            </div>

            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #ef4444, #f87171); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h5 class="mb-0 ms-3">Marketing Campaigns</h5>
                </div>
                <p class="text-muted mb-3">Promotional campaigns and guest outreach</p>
                <a href="campaigns.php" class="btn btn-danger btn-sm">Manage Campaigns</a>
            </div>

            <div class="action-card">
                <div class="d-flex align-items-center mb-3">
                    <div class="dept-icon" style="background: linear-gradient(135deg, #06b6d4, #67e8f9); width: 40px; height: 40px; font-size: 16px;">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h5 class="mb-0 ms-3">Guest Relations</h5>
                </div>
                <p class="text-muted mb-3">Customer satisfaction and feedback management</p>
                <a href="guest_feedback.php" class="btn btn-info btn-sm">View Feedback</a>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-action-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="action-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <h5 class="mb-0 ms-3">Restaurant Operations</h5>
                    </div>
                    <p class="text-muted mb-3">Food service and menu management</p>
                    <a href="menu_management.php" class="btn btn-success btn-sm">Manage Menu</a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-action-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="action-icon">
                            <i class="fas fa-wine-bottle"></i>
                        </div>
                        <h5 class="mb-0 ms-3">Bar Management</h5>
                    </div>
                    <p class="text-muted mb-3">Beverage inventory and bar operations</p>
                    <a href="bar_orders.php" class="btn btn-warning btn-sm">Manage Bar</a>
                </div>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <div class="management-action-card">
                    <div class="d-flex align-items-center mb-3">
                        <div class="action-icon">
                            <i class="fas fa-warehouse"></i>
                        </div>
                        <h5 class="mb-0 ms-3">Inventory Control</h5>
                    </div>
                    <p class="text-muted mb-3">Comprehensive inventory management</p>
                    <a href="inventory.php" class="btn btn-purple btn-sm">View Inventory</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.text-purple {
    color: #8b5cf6 !important;
}

.bg-purple {
    background-color: #8b5cf6 !important;
}

.btn-purple {
    background: linear-gradient(135deg, #8b5cf6, #a78bfa);
    color: white;
    border: none;
}

.btn-purple:hover {
    background: linear-gradient(135deg, #7c3aed, #8b5cf6);
    color: white;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
