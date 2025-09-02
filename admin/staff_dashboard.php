<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has staff/operations permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'Staff', 'Operations', 'Operations_Manager', 'DeptManager', 'Super_Admin', 'staff', 'operations', 'admin', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'Staff Operations Dashboard';
include '../includes/admin/header.php';

// Staff Dashboard - Service Operations & Basic Access
$today = date('Y-m-d');
$current_user_id = $_SESSION['user_id'] ?? null;

// Room Service & Updates - Staff Access
$todays_checkins = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE()");
$checkins_data = mysqli_fetch_assoc($todays_checkins)['count'] ?? 0;

$todays_checkouts = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cout) = CURDATE()");
$checkouts_data = mysqli_fetch_assoc($todays_checkouts)['count'] ?? 0;

// Room Status Management - Staff Updates
$rooms_to_clean = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status IN ('cleaning', 'checkout')");
$cleaning_data = mysqli_fetch_assoc($rooms_to_clean)['count'] ?? 0;

$available_rooms = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status = 'available'");
$available_data = mysqli_fetch_assoc($available_rooms)['count'] ?? 0;

// Maintenance Requests - Staff Can View/Create
$maintenance_requests = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms nr LEFT JOIN room_status rs ON nr.room_name = rs.room_name WHERE rs.current_status = 'maintenance'");
$maintenance_data = mysqli_fetch_assoc($maintenance_requests)['count'] ?? 0;

$pending_maintenance = mysqli_query($con, "SELECT COUNT(*) as count FROM maintenance_requests WHERE status = 'pending'");
$pending_maintenance_data = mysqli_fetch_assoc($pending_maintenance)['count'] ?? 0;

// Guest Service - Staff Primary Function
$active_guests = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cin <= CURDATE() AND cout >= CURDATE() AND stat = 'Confirm'");
$active_guests_data = mysqli_fetch_assoc($active_guests)['count'] ?? 0;

$todays_arrivals = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cin) = CURDATE() AND stat = 'Confirm'");
$arrivals_data = mysqli_fetch_assoc($todays_arrivals)['count'] ?? 0;

$todays_departures = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE DATE(cout) = CURDATE() AND stat = 'Confirm'");
$departures_data = mysqli_fetch_assoc($todays_departures)['count'] ?? 0;

// Service Requests & Tasks
$housekeeping_tasks = mysqli_query($con, "SELECT COUNT(*) as total, COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending FROM housekeeping_tasks WHERE DATE(created_at) = CURDATE()");
$housekeeping_data = mysqli_fetch_assoc($housekeeping_tasks);

// Inventory Usage - Staff View
$low_stock_kitchen = mysqli_query($con, "SELECT COUNT(*) as count FROM kitchen_inventory WHERE quantity <= min_level");
$kitchen_alerts = mysqli_fetch_assoc($low_stock_kitchen)['count'] ?? 0;

$low_stock_bar = mysqli_query($con, "SELECT COUNT(*) as count FROM bar_inventory WHERE quantity <= minimum_level");
$bar_alerts = mysqli_fetch_assoc($low_stock_bar)['count'] ?? 0;

// Basic Reports Access
$todays_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE DATE(cin) = CURDATE()");
$revenue_today = mysqli_fetch_assoc($todays_revenue)['revenue'] ?? 0;
?>

<style>
.operations-dashboard {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.operations-header {
    background: linear-gradient(135deg, #1a365d 0%, #2d5016 100%);
    color: white;
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
}

.ops-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
    border-left: 4px solid var(--accent-color, #3182ce);
}

.ops-stat {
    text-align: center;
    padding: 15px;
}

.ops-stat .value {
    font-size: 2.2rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 8px;
}

.ops-stat .label {
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.85rem;
    letter-spacing: 1px;
}

.task-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 15px;
    border-left: 4px solid #e2e8f0;
    transition: all 0.3s ease;
}

.task-card:hover {
    border-left-color: #3182ce;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

.task-priority-high {
    border-left-color: #e53e3e;
}

.task-priority-medium {
    border-left-color: #d69e2e;
}

.task-priority-low {
    border-left-color: #38a169;
}

.operations-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-top: 30px;
}
</style>

<div class="operations-dashboard">
    <!-- Operations Header -->
    <div class="operations-header">
        <div class="container">
            <h1 class="mb-3">
                <i class="fas fa-tasks me-3"></i>
                Staff Operations Center
            </h1>
            <p class="mb-0">Daily operations management and task coordination</p>
        </div>
    </div>

    <div class="container">
        <!-- Operational Metrics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-card" style="--accent-color: #38a169;">
                    <div class="ops-stat">
                        <div class="value"><?php echo $checkins_data['count']; ?></div>
                        <div class="label">Today's Check-ins</div>
                        <small class="text-success">
                            <i class="fas fa-door-open"></i> Arrivals
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-card" style="--accent-color: #d69e2e;">
                    <div class="ops-stat">
                        <div class="value"><?php echo $checkouts_data['count']; ?></div>
                        <div class="label">Today's Check-outs</div>
                        <small class="text-warning">
                            <i class="fas fa-door-closed"></i> Departures
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-card" style="--accent-color: #3182ce;">
                    <div class="ops-stat">
                        <div class="value"><?php echo $cleaning_data['count']; ?></div>
                        <div class="label">Rooms to Clean</div>
                        <small class="text-info">
                            <i class="fas fa-broom"></i> Housekeeping
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="ops-card" style="--accent-color: #e53e3e;">
                    <div class="ops-stat">
                        <div class="value"><?php echo $maintenance_data['count']; ?></div>
                        <div class="label">Maintenance Items</div>
                        <small class="text-danger">
                            <i class="fas fa-tools"></i> Requires attention
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Operations Grid -->
        <div class="operations-grid">
            <!-- Housekeeping Tasks -->
            <div class="ops-card">
                <h4 class="mb-4">
                    <i class="fas fa-broom me-2 text-primary"></i>
                    Housekeeping Tasks
                </h4>
                
                <div class="task-card task-priority-high">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Room 101 - Deep Clean</h6>
                            <small class="text-muted">Guest checkout completed</small>
                        </div>
                        <span class="badge bg-danger">High</span>
                    </div>
                </div>

                <div class="task-card task-priority-medium">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Room 205 - Standard Clean</h6>
                            <small class="text-muted">Scheduled maintenance</small>
                        </div>
                        <span class="badge bg-warning">Medium</span>
                    </div>
                </div>

                <div class="task-card task-priority-low">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Lobby Area - Routine</h6>
                            <small class="text-muted">Daily cleaning schedule</small>
                        </div>
                        <span class="badge bg-success">Low</span>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="housekeeping.php" class="btn btn-primary btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Manage Housekeeping
                    </a>
                </div>
            </div>

            <!-- Maintenance Tasks -->
            <div class="ops-card">
                <h4 class="mb-4">
                    <i class="fas fa-tools me-2 text-warning"></i>
                    Maintenance Tasks
                </h4>
                
                <div class="task-card task-priority-high">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">AC Unit - Room 302</h6>
                            <small class="text-muted">Not cooling properly</small>
                        </div>
                        <span class="badge bg-danger">Urgent</span>
                    </div>
                </div>

                <div class="task-card task-priority-medium">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Elevator - Mechanical</h6>
                            <small class="text-muted">Monthly inspection due</small>
                        </div>
                        <span class="badge bg-warning">Scheduled</span>
                    </div>
                </div>

                <div class="task-card task-priority-low">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Garden - Landscaping</h6>
                            <small class="text-muted">Routine maintenance</small>
                        </div>
                        <span class="badge bg-info">Routine</span>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="maintenance_management.php" class="btn btn-warning btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Manage Maintenance
                    </a>
                </div>
            </div>

            <!-- Guest Services -->
            <div class="ops-card">
                <h4 class="mb-4">
                    <i class="fas fa-concierge-bell me-2 text-success"></i>
                    Guest Services
                </h4>
                
                <div class="task-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Airport Pickup</h6>
                            <small class="text-muted">Guest: John Smith - 3:30 PM</small>
                        </div>
                        <button class="btn btn-success btn-sm">Assign</button>
                    </div>
                </div>

                <div class="task-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Room Service</h6>
                            <small class="text-muted">Room 204 - Lunch order</small>
                        </div>
                        <button class="btn btn-info btn-sm">Process</button>
                    </div>
                </div>

                <div class="task-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="mb-1">Special Request</h6>
                            <small class="text-muted">Extra towels for Room 105</small>
                        </div>
                        <button class="btn btn-primary btn-sm">Fulfill</button>
                    </div>
                </div>
            </div>

            <!-- Inventory Alerts -->
            <div class="ops-card">
                <h4 class="mb-4">
                    <i class="fas fa-boxes me-2 text-info"></i>
                    Inventory Status
                </h4>
                
                <div class="task-card task-priority-high">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Towels - Bath</h6>
                            <small class="text-muted">Stock: 15 units (Low)</small>
                        </div>
                        <span class="badge bg-danger">Reorder</span>
                    </div>
                </div>

                <div class="task-card task-priority-medium">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Cleaning Supplies</h6>
                            <small class="text-muted">Stock: 45 units (Medium)</small>
                        </div>
                        <span class="badge bg-warning">Monitor</span>
                    </div>
                </div>

                <div class="task-card task-priority-low">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="mb-1">Linens - Fresh</h6>
                            <small class="text-muted">Stock: 120 units (Good)</small>
                        </div>
                        <span class="badge bg-success">Good</span>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="inventory.php" class="btn btn-info btn-sm">
                        <i class="fas fa-external-link-alt me-1"></i>
                        Manage Inventory
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="ops-card">
                    <h4 class="mb-4">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h4>
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="room.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-bed me-1"></i> Room Management
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="booking.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-calendar-plus me-1"></i> Bookings
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="orders.php" class="btn btn-outline-warning w-100">
                                <i class="fas fa-shopping-cart me-1"></i> Orders
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="pos.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-cash-register me-1"></i> Point of Sale
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="restaurant_menu.php" class="btn btn-outline-danger w-100">
                                <i class="fas fa-utensils me-1"></i> Restaurant Menu
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="rooms_dept.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-building me-1"></i> Rooms Dept
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="kitchen_inventory.php" class="btn btn-outline-dark w-100">
                                <i class="fas fa-kitchen-set me-1"></i> Kitchen
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="help_center.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-question-circle me-1"></i> Help Center
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Staff Schedule -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="ops-card">
                    <h4 class="mb-4">
                        <i class="fas fa-calendar-check me-2"></i>
                        Today's Staff Schedule
                    </h4>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Staff Member</th>
                                    <th>Department</th>
                                    <th>Shift</th>
                                    <th>Status</th>
                                    <th>Tasks Assigned</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-white">MK</span>
                                            </div>
                                            Mary Kamau
                                        </div>
                                    </td>
                                    <td>Housekeeping</td>
                                    <td>8:00 AM - 4:00 PM</td>
                                    <td><span class="badge bg-success">On Duty</span></td>
                                    <td>5 rooms assigned</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-info rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-white">JO</span>
                                            </div>
                                            John Ochieng
                                        </div>
                                    </td>
                                    <td>Maintenance</td>
                                    <td>7:00 AM - 3:00 PM</td>
                                    <td><span class="badge bg-warning">Break</span></td>
                                    <td>3 repairs pending</td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-sm bg-success rounded-circle d-flex align-items-center justify-content-center me-2">
                                                <span class="text-white">AW</span>
                                            </div>
                                            Alice Wanjiku
                                        </div>
                                    </td>
                                    <td>Front Desk</td>
                                    <td>9:00 AM - 5:00 PM</td>
                                    <td><span class="badge bg-success">On Duty</span></td>
                                    <td>Check-in counter</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.avatar-sm {
    width: 35px;
    height: 35px;
    font-size: 0.8rem;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
