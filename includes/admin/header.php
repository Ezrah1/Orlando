<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect to login if not authenticated
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include __DIR__ . '/../../admin/db.php';

// Include hotel settings for dynamic content
require_once __DIR__ . '/../common/hotel_settings.php';

// Update session activity
$_SESSION['last_activity'] = time();

// Get current user info with enhanced details
if (!function_exists('get_current_user_info')) {
function get_current_user_info($con) {
    if (!isset($_SESSION['user_id'])) return null;
    $uid = intval($_SESSION['user_id']);
    
    // Use the selected role ID from session if available, otherwise use user's default role
    $role_id = $_SESSION['user_role_id'] ?? null;
    
    if ($role_id) {
        // Get user info with the selected role
        $sql = "SELECT u.id, u.username, u.role_id, u.dept_id, u.phone, u.email, u.status, u.created_at,
                r.name as user_role,
                u.username as display_name
                FROM users u 
                LEFT JOIN roles r ON r.id = ?
                WHERE u.id = ? AND u.status = 'active'
                LIMIT 1";
        $stmt = $con->prepare($sql);
        $stmt->bind_param('ii', $role_id, $uid);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        // Fallback to user's default role
        $sql = "SELECT u.id, u.username, u.role_id, u.dept_id, u.phone, u.email, u.status, u.created_at,
                r.name as user_role,
                u.username as display_name
                FROM users u 
                LEFT JOIN roles r ON u.role_id = r.id
                WHERE u.id = ? AND u.status = 'active'
                LIMIT 1";
        $stmt = $con->prepare($sql);
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();
    }
    return $result ? $result->fetch_assoc() : null;
}
}

// Get notifications count
if (!function_exists('get_notifications_count')) {
function get_notifications_count($con) {
    $uid = $_SESSION['user_id'] ?? 0;
    // Check if notifications table exists first
    $table_check = $con->query("SHOW TABLES LIKE 'notifications'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $con->query("SELECT COUNT(*) as count FROM notifications WHERE user_id = $uid AND is_read = 0");
        return $result ? $result->fetch_assoc()['count'] : 0;
    }
    return 0; // Return 0 if table doesn't exist
}
}

// Get recent activities
if (!function_exists('get_recent_activities')) {
function get_recent_activities($con) {
    $uid = $_SESSION['user_id'] ?? 0;
    // Check if audit_logs table exists first
    $table_check = $con->query("SHOW TABLES LIKE 'audit_logs'");
    if ($table_check && $table_check->num_rows > 0) {
        $result = $con->query("SELECT * FROM audit_logs WHERE user_id = $uid ORDER BY created_at DESC LIMIT 5");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }
    return []; // Return empty array if table doesn't exist
}
}

// Get current user data (only if not already set)
if (!isset($current_user) || $current_user === null) {
    $current_user = get_current_user_info($con);
}
$notifications_count = get_notifications_count($con);
$recent_activities = get_recent_activities($con);

// Enhanced role-based access function
if (!function_exists('hasModuleAccess')) {
function hasModuleAccess($user_role, $allowed_roles) {
    // Admin and Director get full access
    $user_role_id = $_SESSION['user_role_id'] ?? 0;
    if (in_array($user_role_id, [1, 11])) {
        return true;
    }
    
    // Check both original role name and lowercase version
    return in_array($user_role, $allowed_roles) || 
           in_array(strtolower($user_role), array_map('strtolower', $allowed_roles));
}
}

// Get user role for navigation
$user_role = $current_user['user_role'] ?? $_SESSION['user_role'] ?? 'Admin';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title ?? 'Orlando Resort Admin'); ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Responsive Dashboard CSS -->
    <link href="css/responsive-dashboard.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #3b82f6;
            --light-color: #f8fafc;
            --dark-color: #1e293b;
            --sidebar-width: 280px;
            --header-height: 70px;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --border-color: #e2e8f0;
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: #f8fafc;
            color: var(--text-primary);
            overflow-x: hidden;
        }

        /* Sidebar width for main content positioning */

        /* Main Content */
        .admin-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding-top: var(--header-height);
        }

        .admin-header {
            position: fixed;
            top: 0;
            left: var(--sidebar-width);
            right: 0;
            height: var(--header-height);
            background: white;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            z-index: 999;
            box-shadow: var(--shadow-sm);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-search {
            position: relative;
            width: 300px;
        }

        .search-input {
            width: 100%;
            padding: 10px 15px 10px 45px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background: #f8fafc;
            font-size: 14px;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        /* Portal Access Styles */
        .portal-access .btn {
            border-radius: 8px;
            padding: 8px 15px;
            font-size: 14px;
            font-weight: 500;
        }

        /* Notification and Message Styles */
        .notification-btn, .message-btn {
            position: relative;
            color: var(--text-secondary) !important;
            border: none !important;
            padding: 10px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .notification-btn:hover, .message-btn:hover {
            color: var(--primary-color) !important;
            background-color: rgba(102, 126, 234, 0.1) !important;
        }

        .notification-badge, .message-badge {
            position: absolute;
            top: 3px;
            right: 3px;
            background: var(--danger-color);
            color: white;
            font-size: 10px;
            font-weight: bold;
            border-radius: 10px;
            padding: 2px 6px;
            min-width: 18px;
            text-align: center;
            display: none;
        }

        .notification-badge.has-notifications, .message-badge.has-messages {
            display: block;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        .notification-dropdown, .message-dropdown {
            width: 350px;
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1050;
            background: white;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .notification-dropdown.show, .message-dropdown.show {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }

        .notification-header, .message-header {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .notification-header h6, .message-header h6 {
            margin: 0;
            color: white;
        }

        .notification-header .btn, .message-header .btn {
            color: white !important;
            font-size: 12px;
            padding: 2px 8px;
        }

        .notification-list, .message-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .notification-item, .message-item {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            transition: background-color 0.2s ease;
            cursor: pointer;
        }

        .notification-item:hover, .message-item:hover {
            background-color: #f8fafc;
        }

        .notification-item:last-child, .message-item:last-child {
            border-bottom: none;
        }

        .notification-item.unread, .message-item.unread {
            background-color: rgba(102, 126, 234, 0.05);
            border-left: 3px solid var(--primary-color);
        }

        .notification-footer, .message-footer {
            padding: 10px;
            background-color: #f8fafc;
        }

        .notification-item .notification-content h6 {
            font-size: 13px;
            margin-bottom: 4px;
            color: var(--text-primary);
        }

        .notification-item .notification-content p {
            font-size: 12px;
            color: var(--text-secondary);
            margin-bottom: 4px;
        }

        .notification-item .notification-time {
            font-size: 11px;
            color: var(--text-secondary);
        }

        /* Enhanced Search Styles */
        .header-search {
            position: relative;
        }

        .search-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            box-shadow: var(--shadow-lg);
            z-index: 1000;
            display: none;
            margin-top: 5px;
            max-height: 400px;
            overflow-y: auto;
        }

        .search-dropdown.show {
            display: block;
        }

        .search-category {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }

        .search-category:last-child {
            border-bottom: none;
        }

        .search-category h6 {
            margin: 0 0 10px 0;
            color: var(--text-secondary);
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .search-items {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .search-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            border-radius: 6px;
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .search-item:hover {
            background-color: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
            text-decoration: none;
        }

        .search-item i {
            width: 16px;
            text-align: center;
        }

        /* Help Center Styles */
        .help-btn {
            position: relative;
            color: var(--text-secondary) !important;
            border: none !important;
            padding: 10px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }

        .help-btn:hover {
            color: var(--primary-color) !important;
            background-color: rgba(102, 126, 234, 0.1) !important;
        }

        .help-dropdown {
            width: 320px;
            border: none;
            box-shadow: var(--shadow-lg);
            border-radius: 12px;
            padding: 0;
            overflow: hidden;
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            z-index: 1050;
            background: white;
            opacity: 0;
            transform: translateY(-10px);
            transition: all 0.3s ease;
            pointer-events: none;
        }

        .help-dropdown.show {
            display: block !important;
            opacity: 1 !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }

        .help-header {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .help-header h6 {
            margin: 0;
            color: white;
        }

        .help-header .btn {
            color: white !important;
            font-size: 12px;
            padding: 2px 8px;
        }

        .help-list {
            max-height: 280px;
            overflow-y: auto;
        }

        .help-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.2s ease;
        }

        .help-item:hover {
            background-color: #f8fafc;
            color: var(--text-primary);
            text-decoration: none;
        }

        .help-item:last-child {
            border-bottom: none;
        }

        .help-item i {
            width: 20px;
            text-align: center;
            font-size: 16px;
        }

        .help-item div {
            flex: 1;
        }

        .help-item strong {
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
        }

        .help-item small {
            font-size: 11px;
            color: var(--text-secondary);
        }

        .help-footer {
            padding: 10px;
            background-color: #f8fafc;
        }

        /* Mobile Menu Toggle Styles */
        .mobile-menu-toggle {
            background: none;
            border: none;
            padding: 8px;
            cursor: pointer;
            margin-right: 15px;
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 4px;
        }

        .mobile-menu-toggle:hover {
            background-color: rgba(102, 126, 234, 0.1);
        }

        .hamburger-line {
            width: 20px;
            height: 2px;
            background-color: var(--text-secondary);
            transition: all 0.3s ease;
            border-radius: 2px;
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(1) {
            transform: translateY(6px) rotate(45deg);
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(2) {
            opacity: 0;
        }

        .mobile-menu-toggle.active .hamburger-line:nth-child(3) {
            transform: translateY(-6px) rotate(-45deg);
        }

        /* Responsive Sidebar Styles */
        @media (max-width: 768px) {
            .modern-admin-sidebar {
                position: fixed !important;
                top: 0 !important;
                left: -280px !important;
                width: 280px !important;
                height: 100vh !important;
                z-index: 1040 !important;
                transition: left 0.3s ease !important;
                background: white !important;
                box-shadow: 2px 0 10px rgba(0,0,0,0.1) !important;
            }

            .modern-admin-sidebar.show {
                left: 0 !important;
            }

            .admin-content {
                margin-left: 0 !important;
                width: 100% !important;
                transition: none !important;
            }

            .admin-header {
                position: fixed !important;
                top: 0 !important;
                left: 0 !important;
                right: 0 !important;
                z-index: 1030 !important;
                background: white !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }

            .page-content {
                padding-top: 80px !important;
            }

            /* Overlay for mobile sidebar */
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1035;
                display: none;
            }

            .sidebar-overlay.show {
                display: block;
            }
        }

        .user-dropdown .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            background: #f8fafc;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .user-avatar {
            width: 35px;
            height: 35px;
            background: var(--primary-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 14px;
        }

        .page-content {
            padding: 30px;
        }


    </style>
</head>
<body>
    <!-- Order Notifications System -->
    <?php include __DIR__ . '/../../admin/widgets/order_notifications.php'; ?>
    
    <!-- Enhanced Sidebar -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Enhanced Header -->
    <header class="admin-header">
        <div class="header-left">
            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle d-md-none" type="button" onclick="toggleSidebar()" aria-label="Toggle navigation">
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
                <span class="hamburger-line"></span>
            </button>
            
            <div class="header-search">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="globalSearch" placeholder="Search bookings, guests, rooms, help topics...">
                <div class="search-dropdown" id="searchDropdown">
                    <div class="search-category">
                        <h6>Recent Searches</h6>
                        <div class="search-items" id="recentSearches">
                            <!-- Recent searches will be populated here -->
                        </div>
                    </div>
                    <div class="search-category">
                        <h6>Quick Actions</h6>
                        <div class="search-items">
                            <a href="roombook.php" class="search-item">
                                <i class="fas fa-plus-circle text-primary"></i>
                                <span>New Booking</span>
                            </a>
                            <a href="guests.php" class="search-item">
                                <i class="fas fa-user-plus text-success"></i>
                                <span>Add Guest</span>
                            </a>
                            <a href="help_center.php" class="search-item">
                                <i class="fas fa-question-circle text-info"></i>
                                <span>Get Help</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="header-right">
            <!-- Guest Portal Quick Access -->
            <div class="portal-access dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-external-link-alt me-1"></i> Guest Portal
                </button>
                <ul class="dropdown-menu">
                    <li><h6 class="dropdown-header">Guest Portal Access</h6></li>
                    <li><a class="dropdown-item" href="../index.php" target="_blank"><i class="fas fa-home me-2"></i>Home Page</a></li>
                    <li><a class="dropdown-item" href="../modules/guest/booking/booking_form.php" target="_blank"><i class="fas fa-calendar-check me-2"></i>Make Reservation</a></li>
                    <li><a class="dropdown-item" href="../modules/guest/menu/menu_enhanced.php" target="_blank"><i class="fas fa-utensils me-2"></i>Restaurant Menu</a></li>
                    <li><a class="dropdown-item" href="../modules/guest/menu/order_cart.php" target="_blank"><i class="fas fa-shopping-cart me-2"></i>Food Orders</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="../" target="_blank"><i class="fas fa-eye me-2"></i>View Full Guest Portal</a></li>
                </ul>
            </div>

            <!-- Notifications -->
            <div class="notifications" style="position: relative;">
                <button class="btn btn-link notification-btn" type="button" onclick="simpleToggle('notification-dropdown')">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notificationCount">0</span>
                </button>
                <div class="notification-dropdown" style="display: none; position: absolute; top: 100%; right: 0; z-index: 1050;">
                    <div class="notification-header">
                        <h6 class="mb-0">Notifications</h6>
                        <button class="btn btn-sm btn-link text-primary" onclick="markAllAsRead()">Mark all as read</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <!-- Notifications will be loaded here dynamically -->
                        <div class="notification-item text-center py-3">
                            <i class="fas fa-bell-slash text-muted"></i>
                            <p class="text-muted mb-0">No new notifications</p>
                        </div>
                    </div>
                    <div class="notification-footer">
                        <a href="notifications.php" class="btn btn-sm btn-primary w-100">View All Notifications</a>
                    </div>
                </div>
            </div>

            <!-- Messages -->
            <div class="messages" style="position: relative;">
                <button class="btn btn-link message-btn" type="button" onclick="simpleToggle('message-dropdown')">
                    <i class="fas fa-envelope"></i>
                    <span class="message-badge" id="messageCount">0</span>
                </button>
                <div class="message-dropdown" style="display: none; position: absolute; top: 100%; right: 0; z-index: 1050;">
                    <div class="message-header">
                        <h6 class="mb-0">Messages</h6>
                        <a href="messages.php" class="btn btn-sm btn-link text-primary">View All</a>
                    </div>
                    <div class="message-list" id="messageList">
                        <!-- Messages will be loaded here dynamically -->
                        <div class="message-item text-center py-3">
                            <i class="fas fa-envelope-open text-muted"></i>
                            <p class="text-muted mb-0">No new messages</p>
                        </div>
                    </div>
                    <div class="message-footer">
                        <a href="compose.php" class="btn btn-sm btn-success w-100">
                            <i class="fas fa-plus me-1"></i>New Message
                        </a>
                    </div>
                </div>
            </div>

            <!-- Help Center Quick Access -->
            <div class="help-center" style="position: relative;">
                <button class="btn btn-link help-btn" type="button" onclick="simpleToggle('help-dropdown')" title="Help & Support">
                    <i class="fas fa-question-circle"></i>
                </button>
                <div class="help-dropdown" style="display: none; position: absolute; top: 100%; right: 0; z-index: 1050;">
                    <div class="help-header">
                        <h6 class="mb-0">Help & Support</h6>
                        <a href="help_center.php" class="btn btn-sm btn-link text-primary">Full Help Center</a>
                    </div>
                    <div class="help-list">
                        <a href="#" class="help-item" onclick="showQuickHelp('getting-started')">
                            <i class="fas fa-play-circle text-primary"></i>
                            <div>
                                <strong>Getting Started</strong>
                                <small>First time user guide</small>
                            </div>
                        </a>
                        <a href="#" class="help-item" onclick="showQuickHelp('navigation')">
                            <i class="fas fa-compass text-info"></i>
                            <div>
                                <strong>Navigation Guide</strong>
                                <small>Learn the interface</small>
                            </div>
                        </a>
                        <a href="#" class="help-item" onclick="showQuickHelp('shortcuts')">
                            <i class="fas fa-keyboard text-warning"></i>
                            <div>
                                <strong>Keyboard Shortcuts</strong>
                                <small>Speed up your workflow</small>
                            </div>
                        </a>
                        <a href="#" class="help-item" onclick="showQuickHelp('troubleshooting')">
                            <i class="fas fa-tools text-danger"></i>
                            <div>
                                <strong>Troubleshooting</strong>
                                <small>Common issues & fixes</small>
                            </div>
                        </a>
                    </div>
                    <div class="help-footer">
                        <a href="contact_support.php" class="btn btn-sm btn-outline-primary w-100">
                            <i class="fas fa-headset me-1"></i>Contact Support
                        </a>
                    </div>
                </div>
            </div>

            <div class="user-dropdown dropdown">
                <div class="user-info" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($current_user['display_name'] ?? 'A', 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo htmlspecialchars($current_user['display_name'] ?? 'Admin'); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars($user_role ?? 'Admin'); ?></div>
                    </div>
                    <i class="fas fa-chevron-down ms-2"></i>
                </div>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><h6 class="dropdown-header">Account</h6></li>
                    <li><a class="dropdown-item" href="user_preferences.php"><i class="fas fa-user-edit me-2"></i>Profile Settings</a></li>
                    <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Preferences</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                </ul>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="admin-content">
        <div class="page-content">
        
        <!-- Bootstrap JS -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
        
        <!-- Maya AI Widget for Admin -->
        <?php include_once __DIR__ . '/../../maya/components/maya_ai_widget.php'; ?>
        
        <!-- Notification and Message System -->
        <script>
        // Notification and Message Management
        class AdminNotifications {
            constructor() {
                this.notificationCount = 0;
                this.messageCount = 0;
                this.init();
            }

            init() {
                // Load initial notifications and messages
                this.loadNotifications();
                this.loadMessages();
                
                // Set up polling for new notifications (more frequent)
                setInterval(() => {
                    this.loadNotifications();
                    this.loadMessages();
                }, 15000); // Check every 15 seconds for live updates
                
                // Also poll for count updates every 5 seconds
                setInterval(() => {
                    this.updateNotificationCount();
                }, 5000);
            }

            async loadNotifications() {
                try {
                    // Simulate loading notifications (replace with actual API call)
                    const notifications = await this.fetchNotifications();
                    this.updateNotificationUI(notifications);
                } catch (error) {
                    console.log('Error loading notifications:', error);
                }
            }

            async loadMessages() {
                try {
                    // Simulate loading messages (replace with actual API call)
                    const messages = await this.fetchMessages();
                    this.updateMessageUI(messages);
                } catch (error) {
                    console.log('Error loading messages:', error);
                }
            }

            async fetchNotifications() {
                try {
                    const response = await fetch('get_notifications.php');
                    
                    // Check if response is ok
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    
                    const data = await response.json();
                    
                    if (data.success && data.notifications) {
                        // Convert API format to UI format
                        const uiNotifications = data.notifications.slice(0, 5).map(notification => ({
                            id: notification.id,
                            title: notification.title,
                            message: notification.message,
                            time: this.timeAgo(notification.created_at),
                            unread: !notification.is_read,
                            type: notification.type,
                            category: notification.category,
                            priority: notification.priority
                        }));
                        
                        return uiNotifications;
                    } else {
                        console.warn('No notifications found or API error:', data.error || 'Unknown error');
                        return this.getFallbackNotifications();
                    }
                } catch (error) {
                    console.error('Error fetching notifications:', error);
                    return this.getFallbackNotifications();
                }
            }

            getFallbackNotifications() {
                // Always provide some fallback data so dropdowns work
                return [
                    {
                        id: 'fallback-1',
                        title: 'System Status',
                        message: 'Notification system is initializing...',
                        time: 'just now',
                        unread: false,
                        type: 'info'
                    },
                    {
                        id: 'fallback-2',
                        title: 'Welcome',
                        message: '<?php echo get_hotel_info('name'); ?> Admin System',
                        time: '1m ago',
                        unread: false,
                        type: 'success'
                    }
                ];
            }

            timeAgo(dateString) {
                const now = new Date();
                const date = new Date(dateString);
                const diff = now - date;
                
                const seconds = Math.floor(diff / 1000);
                const minutes = Math.floor(seconds / 60);
                const hours = Math.floor(minutes / 60);
                const days = Math.floor(hours / 24);
                
                if (seconds < 60) return 'just now';
                if (minutes < 60) return `${minutes}m ago`;
                if (hours < 24) return `${hours}h ago`;
                if (days < 7) return `${days}d ago`;
                
                return date.toLocaleDateString();
            }

            async fetchMessages() {
                try {
                    // Always provide fallback messages to ensure dropdowns work
                    return this.getFallbackMessages();
                } catch (error) {
                    console.error('Error fetching messages:', error);
                    return this.getFallbackMessages();
                }
            }

            getFallbackMessages() {
                return [
                    {
                        id: 'msg-1',
                        from: 'Hotel Management',
                        subject: 'System Active',
                        preview: 'Admin system is running smoothly...',
                        time: 'just now',
                        unread: false
                    },
                    {
                        id: 'msg-2',
                        from: 'Operations Team',
                        subject: 'Daily Operations',
                        preview: 'All systems operational and ready...',
                        time: '5m ago',
                        unread: false
                    }
                ];
            }

            getMessageSender(category) {
                const senders = {
                    'booking': 'Reservations',
                    'payment': 'Finance Department',
                    'maintenance': 'Maintenance Team',
                    'housekeeping': 'Housekeeping',
                    'system': 'System Administrator',
                    'inventory': 'Inventory Manager',
                    'security': 'Security Team'
                };
                return senders[category] || 'Hotel Management';
            }

            updateNotificationUI(notifications) {
                const notificationList = document.getElementById('notificationList');
                const notificationBadge = document.getElementById('notificationCount');
                
                this.notificationCount = notifications.filter(n => n.unread).length;
                
                // Update badge
                if (this.notificationCount > 0) {
                    notificationBadge.textContent = this.notificationCount;
                    notificationBadge.classList.add('has-notifications');
                } else {
                    notificationBadge.classList.remove('has-notifications');
                }

                // Update notification list
                if (notifications.length > 0) {
                    notificationList.innerHTML = notifications.map(notification => `
                        <div class="notification-item ${notification.unread ? 'unread' : ''}" onclick="markAsRead(${notification.id})">
                            <div class="notification-content">
                                <h6>${notification.title}</h6>
                                <p>${notification.message}</p>
                                <small class="notification-time">${notification.time}</small>
                            </div>
                        </div>
                    `).join('');
                } else {
                    notificationList.innerHTML = `
                        <div class="notification-item text-center py-3">
                            <i class="fas fa-bell-slash text-muted"></i>
                            <p class="text-muted mb-0">No new notifications</p>
                        </div>
                    `;
                }
            }

            updateMessageUI(messages) {
                const messageList = document.getElementById('messageList');
                const messageBadge = document.getElementById('messageCount');
                
                this.messageCount = messages.filter(m => m.unread).length;
                
                // Update badge
                if (this.messageCount > 0) {
                    messageBadge.textContent = this.messageCount;
                    messageBadge.classList.add('has-messages');
                } else {
                    messageBadge.classList.remove('has-messages');
                }

                // Update message list
                if (messages.length > 0) {
                    messageList.innerHTML = messages.map(message => `
                        <div class="message-item ${message.unread ? 'unread' : ''}" onclick="openMessage(${message.id})">
                            <div class="d-flex justify-content-between">
                                <strong style="font-size: 13px;">${message.from}</strong>
                                <small class="text-muted">${message.time}</small>
                            </div>
                            <div style="font-size: 12px; color: var(--text-primary); margin: 4px 0;">${message.subject}</div>
                            <p style="font-size: 11px; color: var(--text-secondary); margin: 0;">${message.preview}</p>
                        </div>
                    `).join('');
                } else {
                    messageList.innerHTML = `
                        <div class="message-item text-center py-3">
                            <i class="fas fa-envelope-open text-muted"></i>
                            <p class="text-muted mb-0">No new messages</p>
                        </div>
                    `;
                }
            }

            async updateNotificationCount() {
                try {
                    const response = await fetch('get_notifications.php');
                    const data = await response.json();
                    
                    if (data.success) {
                        const notificationBadge = document.getElementById('notificationCount');
                        const unreadCount = data.notifications.filter(n => !n.is_read).length;
                        
                        if (unreadCount > 0) {
                            notificationBadge.textContent = unreadCount;
                            notificationBadge.classList.add('has-notifications');
                        } else {
                            notificationBadge.classList.remove('has-notifications');
                        }
                    }
                } catch (error) {
                    console.log('Error updating notification count:', error);
                }
            }
        }

        // Global functions
        function markAllAsRead() {
            // Call the notification system's mark all as read
            if (window.adminNotifications) {
                // For now, just refresh notifications
                window.adminNotifications.loadNotifications();
            }
        }

        function markAsRead(notificationId) {
            // Implement mark single notification as read
            console.log('Marking notification as read:', notificationId);
            
            // Make API call to mark as read
            fetch('notifications.php?action=mark_read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && window.adminNotifications) {
                    // Refresh notifications
                    window.adminNotifications.loadNotifications();
                }
            })
            .catch(error => {
                console.error('Error marking notification as read:', error);
            });
        }

        // Function to simulate live activity (for testing)
        function simulateLiveActivity() {
            fetch('live_notification_simulator.php?simulate=live&format=json')
            .then(response => response.json())
            .then(data => {
                if (data.success && window.adminNotifications) {
                    console.log(`Simulated ${data.created_count} notifications`);
                    // Refresh notifications after a short delay
                    setTimeout(() => {
                        window.adminNotifications.loadNotifications();
                    }, 1000);
                }
            })
            .catch(error => {
                console.error('Error simulating activity:', error);
            });
        }

        function openMessage(messageId) {
            // Implement open message functionality
            window.location.href = `messages.php?id=${messageId}`;
        }

        // Initialize notifications system when page loads
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing admin systems...');
            
            // Wait for Bootstrap to be fully loaded
            setTimeout(() => {
                try {
                    window.adminNotifications = new AdminNotifications();
                    console.log('AdminNotifications initialized');
                    
                    window.adminSearch = new AdminSearch();
                    console.log('AdminSearch initialized');
                    
                    window.adminHelp = new AdminHelp();
                    console.log('AdminHelp initialized');
                    
                    window.adminSidebar = new AdminSidebar();
                    console.log('AdminSidebar initialized');
                    
                    window.adminDropdowns = new AdminDropdowns();
                    console.log('AdminDropdowns initialized');
                    
                    console.log('All admin systems initialized successfully');
                } catch (error) {
                    console.error('Error initializing admin systems:', error);
                    
                    // Try to initialize dropdowns only as fallback
                    try {
                        window.adminDropdowns = new AdminDropdowns();
                        console.log('Fallback: AdminDropdowns initialized');
                    } catch (dropdownError) {
                        console.error('Failed to initialize dropdowns:', dropdownError);
                    }
                }
            }, 100);
        });

        // Dropdown Management System
        class AdminDropdowns {
            constructor() {
                this.init();
            }

            init() {
                console.log('Initializing admin dropdowns...');
                
                // Check if Bootstrap is available
                if (typeof bootstrap === 'undefined') {
                    console.error('Bootstrap is not loaded!');
                    this.fallbackDropdowns();
                    return;
                }

                // Initialize Bootstrap dropdowns manually if needed
                this.initializeBootstrapDropdowns();
                
                // Add click event listeners as fallback
                this.addFallbackListeners();
                
                console.log('Admin dropdowns initialized successfully');
            }

            initializeBootstrapDropdowns() {
                try {
                    // Initialize all dropdowns
                    const dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle, [data-bs-toggle="dropdown"]'));
                    const dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                        return new bootstrap.Dropdown(dropdownToggleEl);
                    });
                    
                    console.log('Bootstrap dropdowns initialized:', dropdownList.length);
                } catch (error) {
                    console.error('Error initializing Bootstrap dropdowns:', error);
                    this.fallbackDropdowns();
                }
            }

            addFallbackListeners() {
                // Notification dropdown
                const notificationBtn = document.querySelector('.notification-btn');
                const notificationDropdown = document.querySelector('.notification-dropdown');
                
                if (notificationBtn && notificationDropdown) {
                    notificationBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.toggleDropdown(notificationDropdown);
                        console.log('Notification dropdown toggled');
                    });
                }

                // Message dropdown
                const messageBtn = document.querySelector('.message-btn');
                const messageDropdown = document.querySelector('.message-dropdown');
                
                if (messageBtn && messageDropdown) {
                    messageBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.toggleDropdown(messageDropdown);
                        console.log('Message dropdown toggled');
                    });
                }

                // Help dropdown
                const helpBtn = document.querySelector('.help-btn');
                const helpDropdown = document.querySelector('.help-dropdown');
                
                if (helpBtn && helpDropdown) {
                    helpBtn.addEventListener('click', (e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        this.toggleDropdown(helpDropdown);
                        console.log('Help dropdown toggled');
                    });
                }

                // Close dropdowns when clicking outside
                document.addEventListener('click', (e) => {
                    if (!e.target.closest('.dropdown')) {
                        this.closeAllDropdowns();
                    }
                });
            }

            toggleDropdown(dropdown) {
                if (!dropdown) return;
                
                const isVisible = dropdown.style.display === 'block' || dropdown.classList.contains('show');
                
                // Close all other dropdowns first
                this.closeAllDropdowns();
                
                if (!isVisible) {
                    dropdown.style.display = 'block';
                    dropdown.classList.add('show');
                    this.positionDropdown(dropdown);
                }
            }

            closeAllDropdowns() {
                const dropdowns = document.querySelectorAll('.notification-dropdown, .message-dropdown, .help-dropdown');
                dropdowns.forEach(dropdown => {
                    dropdown.style.display = 'none';
                    dropdown.classList.remove('show');
                });
            }

            positionDropdown(dropdown) {
                // Ensure dropdown is positioned correctly
                const rect = dropdown.getBoundingClientRect();
                const viewportWidth = window.innerWidth;
                
                if (rect.right > viewportWidth) {
                    dropdown.style.right = '0';
                    dropdown.style.left = 'auto';
                }
            }

            fallbackDropdowns() {
                console.log('Using fallback dropdown system');
                // Remove Bootstrap classes and use custom implementation
                const dropdowns = document.querySelectorAll('.dropdown-menu');
                dropdowns.forEach(dropdown => {
                    dropdown.style.position = 'absolute';
                    dropdown.style.top = '100%';
                    dropdown.style.zIndex = '1000';
                    dropdown.style.display = 'none';
                });
            }
        }

        // Mobile Sidebar Management
        class AdminSidebar {
            constructor() {
                this.sidebar = document.getElementById('sidebar') || document.querySelector('.modern-admin-sidebar');
                this.toggle = document.querySelector('.mobile-menu-toggle');
                this.overlay = null;
                this.init();
            }

            init() {
                this.createOverlay();
                this.bindEvents();
            }

            createOverlay() {
                if (!document.querySelector('.sidebar-overlay')) {
                    this.overlay = document.createElement('div');
                    this.overlay.className = 'sidebar-overlay';
                    this.overlay.addEventListener('click', () => this.closeSidebar());
                    document.body.appendChild(this.overlay);
                }
            }

            bindEvents() {
                // Close sidebar when clicking on overlay
                if (this.overlay) {
                    this.overlay.addEventListener('click', () => this.closeSidebar());
                }

                // Close sidebar when pressing Escape key
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && this.isSidebarOpen()) {
                        this.closeSidebar();
                    }
                });

                // Handle window resize
                window.addEventListener('resize', () => {
                    if (window.innerWidth > 768 && this.isSidebarOpen()) {
                        this.closeSidebar();
                    }
                });
            }

            toggleSidebar() {
                if (this.isSidebarOpen()) {
                    this.closeSidebar();
                } else {
                    this.openSidebar();
                }
            }

            openSidebar() {
                if (this.sidebar) {
                    this.sidebar.classList.add('show');
                }
                if (this.toggle) {
                    this.toggle.classList.add('active');
                }
                if (this.overlay) {
                    this.overlay.classList.add('show');
                }
                document.body.style.overflow = 'hidden';
            }

            closeSidebar() {
                if (this.sidebar) {
                    this.sidebar.classList.remove('show');
                }
                if (this.toggle) {
                    this.toggle.classList.remove('active');
                }
                if (this.overlay) {
                    this.overlay.classList.remove('show');
                }
                document.body.style.overflow = '';
            }

            isSidebarOpen() {
                return this.sidebar && this.sidebar.classList.contains('show');
            }
        }

        // Global sidebar toggle function
        function toggleSidebar() {
            if (window.adminSidebar) {
                window.adminSidebar.toggleSidebar();
            }
        }

        // Ultra-simple dropdown system
        function simpleToggle(dropdownClass) {
            console.log('Toggling dropdown:', dropdownClass);
            const dropdown = document.querySelector('.' + dropdownClass);
            if (dropdown) {
                const isVisible = dropdown.style.display === 'block' || dropdown.classList.contains('show');
                
                // Hide all dropdowns first
                document.querySelectorAll('.notification-dropdown, .message-dropdown, .help-dropdown').forEach(d => {
                    d.style.display = 'none';
                    d.classList.remove('show');
                    d.style.opacity = '0';
                    d.style.transform = 'translateY(-10px)';
                });
                
                // Show this one if it wasn't visible
                if (!isVisible) {
                    dropdown.style.display = 'block';
                    dropdown.classList.add('show');
                    // Force opacity and transform for smooth animation
                    setTimeout(() => {
                        dropdown.style.opacity = '1';
                        dropdown.style.transform = 'translateY(0)';
                    }, 10);
                    console.log('Showing dropdown:', dropdownClass);
                } else {
                    console.log('Hiding dropdown:', dropdownClass);
                }
            }
        }

        // Global functions
        window.simpleToggle = simpleToggle;
        window.hideAllDropdowns = function() {
            document.querySelectorAll('.notification-dropdown, .message-dropdown, .help-dropdown').forEach(d => {
                d.style.display = 'none';
                d.classList.remove('show');
                d.style.opacity = '0';
                d.style.transform = 'translateY(-10px)';
            });
        };

        // Test function
        window.testSimple = function() {
            console.log('Testing simple toggle...');
            simpleToggle('notification-dropdown');
        };

        // Simple click-outside-to-close
        document.addEventListener('click', function(event) {
            // Check if click is on a dropdown button or inside a dropdown
            const isButton = event.target.closest('.notification-btn, .message-btn, .help-btn');
            const isDropdown = event.target.closest('.notification-dropdown, .message-dropdown, .help-dropdown');
            
            // If clicked outside both, close all dropdowns
            if (!isButton && !isDropdown) {
                hideAllDropdowns();
            }
        });

        // Add specific event listeners for each button as backup
        document.addEventListener('DOMContentLoaded', function() {
            // Notification button
            const notificationBtn = document.querySelector('.notification-btn');
            if (notificationBtn) {
                notificationBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    simpleToggle('notification-dropdown');
                });
            }

            // Message button
            const messageBtn = document.querySelector('.message-btn');
            if (messageBtn) {
                messageBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    simpleToggle('message-dropdown');
                });
            }

            // Help button
            const helpBtn = document.querySelector('.help-btn');
            if (helpBtn) {
                helpBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    simpleToggle('help-dropdown');
                });
            }
        });

        // Debug function to test all dropdowns
        function testDropdowns() {
            console.log('Testing all dropdowns...');
            console.log('Bootstrap available:', typeof bootstrap !== 'undefined');
            console.log('Notification button:', document.querySelector('.notification-btn'));
            console.log('Message button:', document.querySelector('.message-btn'));
            console.log('Help button:', document.querySelector('.help-btn'));
            console.log('AdminDropdowns instance:', window.adminDropdowns);
        }

        // Enhanced Global Search System
        class AdminSearch {
            constructor() {
                this.searchInput = document.getElementById('globalSearch');
                this.searchDropdown = document.getElementById('searchDropdown');
                this.recentSearches = JSON.parse(localStorage.getItem('adminRecentSearches') || '[]');
                this.init();
            }

            init() {
                if (this.searchInput) {
                    this.searchInput.addEventListener('focus', () => this.showSearchDropdown());
                    this.searchInput.addEventListener('blur', (e) => {
                        // Delay hiding to allow clicking on dropdown items
                        setTimeout(() => this.hideSearchDropdown(), 200);
                    });
                    this.searchInput.addEventListener('input', (e) => this.handleSearch(e.target.value));
                    this.searchInput.addEventListener('keydown', (e) => this.handleKeydown(e));
                }
                this.loadRecentSearches();
            }

            showSearchDropdown() {
                if (this.searchDropdown) {
                    this.searchDropdown.classList.add('show');
                }
            }

            hideSearchDropdown() {
                if (this.searchDropdown) {
                    this.searchDropdown.classList.remove('show');
                }
            }

            handleSearch(query) {
                if (query.length < 2) return;
                
                // Add live search functionality here
                this.performSearch(query);
            }

            handleKeydown(e) {
                if (e.key === 'Enter') {
                    const query = e.target.value.trim();
                    if (query) {
                        this.addToRecentSearches(query);
                        this.performSearch(query);
                    }
                }
            }

            performSearch(query) {
                // Add to recent searches
                this.addToRecentSearches(query);
                
                // Implement actual search functionality
                console.log('Searching for:', query);
                
                // You can redirect to a search results page or show results in dropdown
                // window.location.href = `search_results.php?q=${encodeURIComponent(query)}`;
            }

            addToRecentSearches(query) {
                if (!this.recentSearches.includes(query)) {
                    this.recentSearches.unshift(query);
                    this.recentSearches = this.recentSearches.slice(0, 5); // Keep only 5 recent
                    localStorage.setItem('adminRecentSearches', JSON.stringify(this.recentSearches));
                    this.loadRecentSearches();
                }
            }

            loadRecentSearches() {
                const container = document.getElementById('recentSearches');
                if (!container) return;

                if (this.recentSearches.length === 0) {
                    container.innerHTML = '<p class="text-muted small mb-0">No recent searches</p>';
                } else {
                    container.innerHTML = this.recentSearches.map(search => `
                        <a href="#" class="search-item" onclick="window.adminSearch.performSearch('${search}')">
                            <i class="fas fa-clock text-muted"></i>
                            <span>${search}</span>
                        </a>
                    `).join('');
                }
            }
        }

        // Enhanced Help System
        class AdminHelp {
            constructor() {
                this.helpContent = this.getHelpContent();
                this.init();
            }

            init() {
                // Add global help modal if it doesn't exist
                this.ensureHelpModal();
            }

            ensureHelpModal() {
                if (!document.getElementById('globalHelpModal')) {
                    const modalHTML = `
                        <div class="modal fade" id="globalHelpModal" tabindex="-1" role="dialog">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="globalHelpModalTitle">Help Topic</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body" id="globalHelpModalBody">
                                        <!-- Content will be loaded dynamically -->
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="button" class="btn btn-primary" onclick="window.adminHelp.printHelp()">
                                            <i class="fas fa-print"></i> Print
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    document.body.insertAdjacentHTML('beforeend', modalHTML);
                }
            }

            getHelpContent() {
                return {
                    'getting-started': {
                        title: 'Getting Started Guide',
                        content: `
                            <h6>Welcome to <?php echo get_hotel_info('name'); ?> Admin System</h6>
                            <p>This guide will help you get started with the system:</p>
                            <ol>
                                <li><strong>Dashboard Overview:</strong> Your main dashboard shows key metrics and recent activity</li>
                                <li><strong>Navigation:</strong> Use the sidebar to access different modules</li>
                                <li><strong>Quick Search:</strong> Use the search bar to find bookings, guests, or help topics</li>
                                <li><strong>Notifications:</strong> Check the bell icon for important alerts</li>
                                <li><strong>Messages:</strong> Use the envelope icon for internal communications</li>
                            </ol>
                            <div class="alert alert-info">
                                <strong>Tip:</strong> Hover over any icon to see tooltips with helpful information.
                            </div>
                        `
                    },
                    'navigation': {
                        title: 'Navigation Guide',
                        content: `
                            <h6>Understanding the Interface</h6>
                            <p>The admin interface is designed for efficiency:</p>
                            <ul>
                                <li><strong>Sidebar:</strong> Main navigation menu with all modules</li>
                                <li><strong>Header:</strong> Quick access to search, notifications, and user settings</li>
                                <li><strong>Breadcrumbs:</strong> Shows your current location in the system</li>
                                <li><strong>Quick Actions:</strong> Context-sensitive buttons for common tasks</li>
                            </ul>
                            <div class="alert alert-success">
                                <strong>Pro Tip:</strong> Use the Guest Portal button to see how guests view your hotel.
                            </div>
                        `
                    },
                    'shortcuts': {
                        title: 'Keyboard Shortcuts',
                        content: `
                            <h6>Speed Up Your Workflow</h6>
                            <p>Use these keyboard shortcuts to work faster:</p>
                            <table class="table table-sm">
                                <tr><td><kbd>Ctrl + /</kbd></td><td>Show this help</td></tr>
                                <tr><td><kbd>Ctrl + K</kbd></td><td>Focus search bar</td></tr>
                                <tr><td><kbd>Ctrl + N</kbd></td><td>New booking (on booking pages)</td></tr>
                                <tr><td><kbd>Ctrl + S</kbd></td><td>Save current form</td></tr>
                                <tr><td><kbd>Esc</kbd></td><td>Close modals/dropdowns</td></tr>
                            </table>
                            <div class="alert alert-warning">
                                <strong>Note:</strong> Some shortcuts may vary based on your current page.
                            </div>
                        `
                    },
                    'troubleshooting': {
                        title: 'Common Issues & Solutions',
                        content: `
                            <h6>Troubleshooting Guide</h6>
                            <div class="accordion" id="troubleshootingAccordion">
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#issue1">
                                            Page not loading or showing errors
                                        </button>
                                    </h2>
                                    <div id="issue1" class="accordion-collapse collapse show">
                                        <div class="accordion-body">
                                            Try refreshing the page (F5) or clearing your browser cache (Ctrl+Shift+R).
                                        </div>
                                    </div>
                                </div>
                                <div class="accordion-item">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#issue2">
                                            Can't access certain features
                                        </button>
                                    </h2>
                                    <div id="issue2" class="accordion-collapse collapse">
                                        <div class="accordion-body">
                                            Check with your administrator - you may need additional permissions for that feature.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `
                    }
                };
            }

            showQuickHelp(topic) {
                const content = this.helpContent[topic];
                if (!content) return;

                const modal = new bootstrap.Modal(document.getElementById('globalHelpModal'));
                document.getElementById('globalHelpModalTitle').textContent = content.title;
                document.getElementById('globalHelpModalBody').innerHTML = content.content;
                modal.show();
            }

            printHelp() {
                const printContent = document.getElementById('globalHelpModalBody').innerHTML;
                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                        <head>
                            <title>Help - ${document.getElementById('globalHelpModalTitle').textContent}</title>
                            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
                        </head>
                        <body class="p-4">
                            <h1>${document.getElementById('globalHelpModalTitle').textContent}</h1>
                            ${printContent}
                        </body>
                    </html>
                `);
                printWindow.document.close();
                printWindow.print();
            }
        }

        // Global help functions for backward compatibility with help_center.php
        function showQuickHelp(topic) {
            if (window.adminHelp) {
                window.adminHelp.showQuickHelp(topic);
            }
        }

        function showHelpModal(topic) {
            showQuickHelp(topic);
        }

        function searchHelp() {
            const query = document.getElementById('helpSearch')?.value || document.getElementById('globalSearch')?.value;
            if (query) {
                window.location.href = `help_center.php?search=${encodeURIComponent(query)}`;
            }
        }

        function printHelp() {
            if (window.adminHelp) {
                window.adminHelp.printHelp();
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl + K for search
            if (e.ctrlKey && e.key === 'k') {
                e.preventDefault();
                document.getElementById('globalSearch')?.focus();
            }
            
            // Ctrl + / for help
            if (e.ctrlKey && e.key === '/') {
                e.preventDefault();
                showQuickHelp('shortcuts');
            }
        });
        </script>
