<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has director permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'director', 'ceo', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'Director Executive Dashboard';
include '../includes/admin/header.php';

// Get comprehensive business metrics
$today = date('Y-m-d');
$this_month = date('Y-m');
$last_month = date('Y-m', strtotime('-1 month'));
$this_year = date('Y');

// Financial Performance - Director Full Access
$today_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE DATE(cin) = CURDATE()");
$today_revenue_data = mysqli_fetch_assoc($today_revenue)['revenue'] ?? 0;

$monthly_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$monthly_revenue_data = mysqli_fetch_assoc($monthly_revenue)['revenue'] ?? 0;

$yearly_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE YEAR(cin) = YEAR(CURDATE())");
$yearly_revenue_data = mysqli_fetch_assoc($yearly_revenue)['revenue'] ?? 0;

$last_month_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(cin) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))");
$last_month_revenue_data = mysqli_fetch_assoc($last_month_revenue)['revenue'] ?? 0;

// Booking Performance
$monthly_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())");
$monthly_bookings_data = mysqli_fetch_assoc($monthly_bookings)['count'] ?? 0;

$confirmed_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Confirm' AND MONTH(created_at) = MONTH(CURDATE())");
$confirmed_bookings_data = mysqli_fetch_assoc($confirmed_bookings)['count'] ?? 0;

// Operational Performance - Director Full Access
$room_stats = mysqli_query($con, "SELECT 
    COUNT(nr.room_name) as total_rooms,
    COUNT(CASE WHEN rs.current_status = 'occupied' THEN 1 END) as occupied_rooms,
    COUNT(CASE WHEN rs.current_status = 'available' THEN 1 END) as available_rooms,
    COUNT(CASE WHEN rs.current_status = 'maintenance' THEN 1 END) as maintenance_rooms
    FROM named_rooms nr 
    LEFT JOIN room_status rs ON nr.room_name = rs.room_name 
    WHERE nr.is_active = 1");
$operational = mysqli_fetch_assoc($room_stats);
$operational['occupancy_rate'] = $operational['total_rooms'] > 0 ? round(($operational['occupied_rooms'] / $operational['total_rooms']) * 100, 1) : 0;

// Staff Performance Overview - Director Access
$total_staff = mysqli_query($con, "SELECT COUNT(*) as count FROM users WHERE status = 'active'");
$total_staff_data = mysqli_fetch_assoc($total_staff)['count'] ?? 0;

$active_users_today = mysqli_query($con, "SELECT COUNT(DISTINCT created_by) as count FROM roombook WHERE DATE(created_at) = CURDATE()");
$active_users_data = mysqli_fetch_assoc($active_users_today)['count'] ?? 0;

// Financial Summary for Executive View
$financial = [
    'today_revenue' => $today_revenue_data,
    'monthly_revenue' => $monthly_revenue_data,
    'last_month_revenue' => $last_month_revenue_data,
    'yearly_revenue' => $yearly_revenue_data,
    'monthly_bookings' => $monthly_bookings_data,
    'confirmed_bookings' => $confirmed_bookings_data,
    'avg_booking_value' => $monthly_bookings_data > 0 ? round($monthly_revenue_data / $monthly_bookings_data, 2) : 0
];

// Guest Satisfaction & Reviews
$guest_satisfaction = 4.3; // This would come from reviews table
$total_reviews = 156; // This would come from reviews table
$repeat_guests = 34; // This would come from guest history

// Staff Performance
$staff_query = "
    SELECT 
        COUNT(*) as total_staff,
        COUNT(CASE WHEN status = 'active' THEN 1 END) as active_staff,
        COUNT(CASE WHEN created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as new_users
    FROM users
";
$staff_result = mysqli_query($con, $staff_query);
$staff = mysqli_fetch_assoc($staff_result) ?: ['total_staff' => 0, 'active_staff' => 0, 'new_users' => 0];

// Market Performance
$market_share = 12.5; // This would come from market analysis
$competitor_analysis = 'Outperforming'; // This would come from business intelligence

// Calculate growth percentages
$revenue_growth = $financial['last_month_revenue'] > 0 ? 
    (($financial['monthly_revenue'] - $financial['last_month_revenue']) / $financial['last_month_revenue']) * 100 : 0;

// Expense simulation (would come from expense tracking)
$monthly_expenses = 85000;
$monthly_profit = $financial['monthly_revenue'] - $monthly_expenses;
$profit_margin = $financial['monthly_revenue'] > 0 ? ($monthly_profit / $financial['monthly_revenue']) * 100 : 0;

// Department efficiency scores (would come from KPI tracking)
$dept_scores = [
    'front_office' => 94,
    'housekeeping' => 89,
    'food_beverage' => 87,
    'maintenance' => 82,
    'finance' => 96,
    'marketing' => 85
];
?>

<style>
.director-dashboard {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: calc(100vh - 100px);
    color: white;
}

.executive-header {
    padding: 40px 0;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.executive-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="executive" width="20" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/><circle cx="5" cy="5" r="0.5" fill="white" opacity="0.08"/><circle cx="15" cy="15" r="0.8" fill="white" opacity="0.06"/></pattern></defs><rect width="100" height="20" fill="url(%23executive)"/></svg>');
}

.executive-card {
    background: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(10px);
    border-radius: 20px;
    padding: 30px;
    margin-bottom: 30px;
    border: 1px solid rgba(255, 255, 255, 0.2);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
}

.metric-card {
    background: rgba(255, 255, 255, 0.15);
    border-radius: 15px;
    padding: 25px;
    text-align: center;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.metric-card:hover {
    transform: translateY(-5px);
    background: rgba(255, 255, 255, 0.2);
}

.metric-value {
    font-size: 3rem;
    font-weight: 800;
    margin-bottom: 10px;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.metric-label {
    font-size: 1rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    opacity: 0.9;
    margin-bottom: 10px;
}

.metric-trend {
    font-size: 0.9rem;
    font-weight: 600;
    padding: 5px 10px;
    border-radius: 20px;
    background: rgba(255, 255, 255, 0.2);
}

.executive-chart {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 25px;
    height: 400px;
}

.dept-performance-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.dept-performance-item:last-child {
    border-bottom: none;
}

.dept-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 20px;
}

.performance-score {
    margin-left: auto;
    font-size: 1.5rem;
    font-weight: 700;
}

.strategic-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
}

.action-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.action-card:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateY(-5px);
}

.action-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    background: rgba(255, 255, 255, 0.2);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
}

.executive-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
    padding: 10px 20px;
    border-radius: 25px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.executive-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
}

.kpi-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

/* Mobile Responsiveness Fixes */
@media (max-width: 768px) {
    .director-dashboard {
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .executive-header {
        padding: 20px 0;
    }
    
    .executive-header h1 {
        font-size: 1.8rem !important;
    }
    
    .executive-card {
        margin-bottom: 15px;
        padding: 15px;
    }
    
    .kpi-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    /* Ensure header elements remain responsive */
    .admin-header {
        position: relative !important;
        z-index: 1000 !important;
    }
    
    .header-right {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
    
    .notification-btn, .message-btn, .help-btn {
        padding: 8px !important;
        min-width: 40px !important;
        min-height: 40px !important;
    }
    
    .portal-access .btn {
        padding: 6px 10px !important;
        font-size: 12px !important;
    }
    
    .user-dropdown .user-info {
        gap: 8px !important;
    }
    
    .user-avatar {
        width: 32px !important;
        height: 32px !important;
        font-size: 14px !important;
    }
    
    .user-details {
        display: none !important;
    }
    
    /* Search responsiveness */
    .header-search {
        width: 100% !important;
        max-width: 200px !important;
    }
    
    .search-input {
        width: 100% !important;
        padding: 8px 12px 8px 35px !important;
        font-size: 14px !important;
    }
}

@media (max-width: 576px) {
    .header-right {
        gap: 5px !important;
    }
    
    .portal-access {
        display: none !important;
    }
    
    .header-search {
        max-width: 150px !important;
    }
    
    .search-input {
        padding: 6px 10px 6px 30px !important;
        font-size: 12px !important;
    }
}
</style>

<div class="director-dashboard">
    <!-- Executive Header -->
    <div class="executive-header">
        <div class="container">
            <h1 class="display-4 mb-3" style="position: relative; z-index: 2;">
                <i class="fas fa-crown me-3"></i>
                Executive Command Center
            </h1>
            <p class="lead mb-0" style="position: relative; z-index: 2;">
                Strategic oversight and business intelligence for Orlando International Resorts
            </p>
            <div class="row mt-4" style="position: relative; z-index: 2;">
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="h3"><?php echo date('H:i'); ?></div>
                        <small>Current Time</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="h3"><?php echo round($operational['occupancy_rate'], 1); ?>%</div>
                        <small>Current Occupancy</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="text-center">
                        <div class="h3">$<?php echo number_format($financial['today_revenue']); ?></div>
                        <small>Today's Revenue</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container pb-5">
        <!-- Key Performance Indicators -->
        <div class="kpi-grid">
            <div class="metric-card">
                <div class="metric-value">$<?php echo number_format($financial['monthly_revenue']); ?></div>
                <div class="metric-label">Monthly Revenue</div>
                <div class="metric-trend <?php echo $revenue_growth >= 0 ? 'text-success' : 'text-danger'; ?>">
                    <i class="fas fa-<?php echo $revenue_growth >= 0 ? 'arrow-up' : 'arrow-down'; ?>"></i>
                    <?php echo abs(round($revenue_growth, 1)); ?>% vs last month
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-value"><?php echo round($profit_margin, 1); ?>%</div>
                <div class="metric-label">Profit Margin</div>
                <div class="metric-trend">
                    <i class="fas fa-chart-line"></i>
                    $<?php echo number_format($monthly_profit); ?> profit
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-value"><?php echo round($operational['occupancy_rate'], 1); ?>%</div>
                <div class="metric-label">Avg Occupancy</div>
                <div class="metric-trend">
                    <i class="fas fa-bed"></i>
                    <?php echo $operational['total_rooms']; ?> total rooms
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-value"><?php echo $guest_satisfaction; ?></div>
                <div class="metric-label">Guest Rating</div>
                <div class="metric-trend">
                    <i class="fas fa-star"></i>
                    <?php echo $total_reviews; ?> reviews
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-value"><?php echo $market_share; ?>%</div>
                <div class="metric-label">Market Share</div>
                <div class="metric-trend">
                    <i class="fas fa-chart-pie"></i>
                    <?php echo $competitor_analysis; ?>
                </div>
            </div>

            <div class="metric-card">
                <div class="metric-value"><?php echo $staff['active_staff']; ?></div>
                <div class="metric-label">Active Staff</div>
                <div class="metric-trend">
                    <i class="fas fa-users"></i>
                    <?php echo round(($staff['new_users']/$staff['total_staff'])*100, 1); ?>% new users
                </div>
            </div>
        </div>

        <!-- Business Intelligence Dashboard -->
        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="executive-card">
                    <h3 class="mb-4">
                        <i class="fas fa-chart-area me-2"></i>
                        Revenue Performance Analysis
                    </h3>
                    <div class="executive-chart">
                        <canvas id="revenueAnalysisChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="col-xl-4 mb-4">
                <div class="executive-card">
                    <h3 class="mb-4">
                        <i class="fas fa-building me-2"></i>
                        Department Performance
                    </h3>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-calculator"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Finance & Accounting</h6>
                            <small class="opacity-75">Financial operations</small>
                        </div>
                        <div class="performance-score text-success">
                            <?php echo $dept_scores['finance']; ?>%
                        </div>
                    </div>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-concierge-bell"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Front Office</h6>
                            <small class="opacity-75">Guest services</small>
                        </div>
                        <div class="performance-score text-success">
                            <?php echo $dept_scores['front_office']; ?>%
                        </div>
                    </div>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Housekeeping</h6>
                            <small class="opacity-75">Room maintenance</small>
                        </div>
                        <div class="performance-score text-info">
                            <?php echo $dept_scores['housekeeping']; ?>%
                        </div>
                    </div>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-utensils"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Food & Beverage</h6>
                            <small class="opacity-75">Restaurant operations</small>
                        </div>
                        <div class="performance-score text-info">
                            <?php echo $dept_scores['food_beverage']; ?>%
                        </div>
                    </div>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-bullhorn"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Marketing</h6>
                            <small class="opacity-75">Promotions & sales</small>
                        </div>
                        <div class="performance-score text-warning">
                            <?php echo $dept_scores['marketing']; ?>%
                        </div>
                    </div>

                    <div class="dept-performance-item">
                        <div class="dept-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <div class="flex-grow-1">
                            <h6 class="mb-1">Maintenance</h6>
                            <small class="opacity-75">Facility upkeep</small>
                        </div>
                        <div class="performance-score text-warning">
                            <?php echo $dept_scores['maintenance']; ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Strategic Actions -->
        <div class="executive-card">
            <h3 class="mb-4">
                <i class="fas fa-chess-king me-2"></i>
                Strategic Management Center
            </h3>

            <div class="strategic-actions">
                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="mb-3">Financial Analytics</h5>
                    <p class="mb-3 opacity-75">Comprehensive financial performance and forecasting</p>
                    <a href="financial_reports.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Access Reports
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h5 class="mb-3">HR Management</h5>
                    <p class="mb-3 opacity-75">Staff performance and organizational development</p>
                    <a href="user_management.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Manage Staff
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-bullhorn"></i>
                    </div>
                    <h5 class="mb-3">Marketing Strategy</h5>
                    <p class="mb-3 opacity-75">Campaign management and brand development</p>
                    <a href="campaigns.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> View Campaigns
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-cog"></i>
                    </div>
                    <h5 class="mb-3">System Settings</h5>
                    <p class="mb-3 opacity-75">Configure business rules and policies</p>
                    <a href="settings.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Access Settings
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h5 class="mb-3">Security & Compliance</h5>
                    <p class="mb-3 opacity-75">Data security and regulatory compliance</p>
                    <a href="security_audit.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Security Audit
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-handshake"></i>
                    </div>
                    <h5 class="mb-3">Guest Experience</h5>
                    <p class="mb-3 opacity-75">Customer satisfaction and service quality</p>
                    <a href="guest_analytics.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> View Analytics
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-utensils"></i>
                    </div>
                    <h5 class="mb-3">Restaurant Management</h5>
                    <p class="mb-3 opacity-75">Food service operations and menu management</p>
                    <a href="restaurant_menu.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Manage Menu
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-wine-glass-alt"></i>
                    </div>
                    <h5 class="mb-3">Bar Operations</h5>
                    <p class="mb-3 opacity-75">Beverage inventory and sales management</p>
                    <a href="bar_inventory.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> View Bar
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-cash-register"></i>
                    </div>
                    <h5 class="mb-3">Point of Sale</h5>
                    <p class="mb-3 opacity-75">Restaurant and bar POS system</p>
                    <a href="pos.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Access POS
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h5 class="mb-3">Revenue Analytics</h5>
                    <p class="mb-3 opacity-75">Comprehensive revenue analysis and forecasting</p>
                    <a href="revenue_analytics.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> View Analytics
                    </a>
                </div>

                <div class="action-card">
                    <div class="action-icon">
                        <i class="fas fa-clipboard-list"></i>
                    </div>
                    <h5 class="mb-3">Kitchen Management</h5>
                    <p class="mb-3 opacity-75">Kitchen inventory and food cost tracking</p>
                    <a href="kitchen_inventory.php" class="executive-btn">
                        <i class="fas fa-external-link-alt"></i> Manage Kitchen
                    </a>
                </div>
            </div>
        </div>

        <!-- Executive Summary -->
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="executive-card">
                    <h4 class="mb-3">
                        <i class="fas fa-trophy me-2"></i>
                        Achievements
                    </h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Revenue target: 115% achieved</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Guest satisfaction: 4.3/5.0</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Staff retention: 94%</li>
                        <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i>Market share growth: +2.3%</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="executive-card">
                    <h4 class="mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Action Items
                    </h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-circle text-warning me-2"></i>Maintenance dept efficiency</li>
                        <li class="mb-2"><i class="fas fa-circle text-warning me-2"></i>Marketing ROI optimization</li>
                        <li class="mb-2"><i class="fas fa-circle text-info me-2"></i>New revenue streams</li>
                        <li class="mb-2"><i class="fas fa-circle text-info me-2"></i>Technology upgrades</li>
                    </ul>
                </div>
            </div>

            <div class="col-md-4">
                <div class="executive-card">
                    <h4 class="mb-3">
                        <i class="fas fa-lightbulb me-2"></i>
                        Opportunities
                    </h4>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fas fa-plus-circle text-success me-2"></i>Corporate packages</li>
                        <li class="mb-2"><i class="fas fa-plus-circle text-success me-2"></i>Event hosting expansion</li>
                        <li class="mb-2"><i class="fas fa-plus-circle text-success me-2"></i>Spa & wellness services</li>
                        <li class="mb-2"><i class="fas fa-plus-circle text-success me-2"></i>Digital transformation</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Advanced Chart.js Configuration -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueAnalysisChart').getContext('2d');
    
    // Sample data - in production this would come from your analytics API
    const monthlyData = [
        <?php echo $financial['monthly_revenue']; ?>,
        <?php echo $financial['last_month_revenue']; ?>,
        85000, 92000, 88000, 95000, 102000, 97000, 105000, 98000, 112000, 108000
    ];
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue',
                data: monthlyData.reverse(),
                borderColor: 'rgba(255, 255, 255, 0.8)',
                backgroundColor: 'rgba(255, 255, 255, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: 'white',
                pointBorderColor: 'rgba(255, 255, 255, 0.8)',
                pointBorderWidth: 2,
                pointRadius: 6
            }, {
                label: 'Target',
                data: [80000, 82000, 85000, 87000, 90000, 92000, 95000, 97000, 100000, 102000, 105000, 108000],
                borderColor: 'rgba(255, 255, 255, 0.4)',
                backgroundColor: 'transparent',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    labels: {
                        color: 'white',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                }
            },
            scales: {
                x: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                },
                y: {
                    ticks: {
                        color: 'rgba(255, 255, 255, 0.8)',
                        font: {
                            size: 12,
                            weight: 'bold'
                        },
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    },
                    grid: {
                        color: 'rgba(255, 255, 255, 0.1)'
                    }
                }
            },
            elements: {
                point: {
                    hoverRadius: 8
                }
            }
        }
    });

    // Auto-refresh executive data every 5 minutes
    setInterval(function() {
        fetch('get_executive_stats.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateExecutiveMetrics(data);
                }
            });
    }, 300000);
});

function updateExecutiveMetrics(data) {
    // Update executive dashboard with real-time data
    console.log('Updating executive metrics:', data);
}
</script>

<?php include '../includes/admin/footer.php'; ?>
