<?php
// Enhanced Sidebar navigation for admin panel
// This file is included by header.php and can be reused independently

// Ensure user role and role_id are available with fallbacks
$user_role = $user_role ?? $current_user['user_role'] ?? $_SESSION['user_role'] ?? 'Admin';
$user_role_id = $_SESSION['user_role_id'] ?? 1;

// Use the hasModuleAccess function from header.php (no redeclaration needed)

// Get current page for active highlighting
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!-- Modern Enhanced Sidebar -->
<nav class="modern-admin-sidebar" id="sidebar">
    <!-- Sidebar Header with User Info -->
    <div class="sidebar-header">
        <div class="logo-section">
            <div class="logo-icon">
                <img src="/Hotel/images/logo-full.png" alt="Orlando International Resorts" class="sidebar-logo-img">
            </div>
        </div>
        
        <!-- User Info in Sidebar -->
        <div class="sidebar-user-info">
            <div class="user-avatar-small">
                <?php echo strtoupper(substr($current_user['display_name'] ?? $user_role ?? 'A', 0, 1)); ?>
            </div>
            <div class="user-details-small">
                <div class="user-name-small"><?php echo htmlspecialchars($current_user['display_name'] ?? $user_role); ?></div>
                <div class="user-role-small"><?php echo htmlspecialchars($user_role); ?></div>
            </div>
        </div>
    </div>

    <!-- Navigation Menu -->
    <div class="sidebar-nav">
        
        <!-- EXECUTIVE MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'CEO'])): ?>
        <div class="nav-module" data-module="executive">
            <div class="module-header">
                <i class="fas fa-crown module-icon"></i>
                <span class="module-title">Executive</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="director_dashboard.php" class="nav-item <?php echo $current_page == 'director_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Executive Dashboard</span>
                </a>
                <a href="revenue_analytics.php" class="nav-item <?php echo $current_page == 'revenue_analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-area"></i>
                    <span>Revenue Analytics</span>
                </a>
                <a href="campaigns.php" class="nav-item <?php echo $current_page == 'campaigns.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i>
                    <span>Marketing Campaigns</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- OPERATIONS MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'Operations_Manager', 'DeptManager', 'Staff'])): ?>
        <div class="nav-module" data-module="operations">
            <div class="module-header">
                <i class="fas fa-cogs module-icon"></i>
                <span class="module-title">Operations</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="operations_manager_dashboard.php" class="nav-item <?php echo $current_page == 'operations_manager_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Operations Hub</span>
                </a>
                <a href="housekeeping.php" class="nav-item <?php echo $current_page == 'housekeeping.php' ? 'active' : ''; ?>">
                    <i class="fas fa-broom"></i>
                    <span>Housekeeping</span>
                </a>
                <a href="maintenance_management.php" class="nav-item <?php echo $current_page == 'maintenance_management.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tools"></i>
                    <span>Maintenance</span>
                </a>
                <a href="inventory.php" class="nav-item <?php echo $current_page == 'inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-boxes"></i>
                    <span>Inventory</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- RESERVATIONS MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'Operations_Manager', 'DeptManager', 'Staff'])): ?>
        <div class="nav-module" data-module="bookings">
            <div class="module-header">
                <i class="fas fa-calendar-alt module-icon"></i>
                <span class="module-title">Accommodation</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="booking.php" class="nav-item <?php echo $current_page == 'booking.php' ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i>
                    <span>All Bookings</span>
                </a>
                <a href="bookings_management.php" class="nav-item <?php echo $current_page == 'bookings_management.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Bookings Management</span>
                </a>
                <a href="staff_booking.php" class="nav-item <?php echo $current_page == 'staff_booking.php' ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i>
                    <span>New Booking</span>
                </a>
                <a href="room.php" class="nav-item <?php echo $current_page == 'room.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span>Room Management</span>
                </a>
                <a href="booking_calendar.php" class="nav-item <?php echo $current_page == 'booking_calendar.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>Calendar View</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- RESTAURANT MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'DeptManager', 'Staff'])): ?>
        <div class="nav-module" data-module="restaurant">
            <div class="module-header">
                <i class="fas fa-utensils module-icon"></i>
                <span class="module-title">Restaurant & Bar</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="pos.php" class="nav-item <?php echo $current_page == 'pos.php' ? 'active' : ''; ?>">
                    <i class="fas fa-cash-register"></i>
                    <span>Point of Sale</span>
                </a>
                <a href="orders.php" class="nav-item <?php echo $current_page == 'orders.php' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-check"></i>
                    <span>Orders</span>
                </a>
                <a href="menu_management.php" class="nav-item <?php echo $current_page == 'menu_management.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book-open"></i>
                    <span>Menu Management</span>
                </a>
                <a href="kitchen_inventory.php" class="nav-item <?php echo $current_page == 'kitchen_inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-carrot"></i>
                    <span>Kitchen Inventory</span>
                </a>
                <a href="bar_inventory.php" class="nav-item <?php echo $current_page == 'bar_inventory.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wine-bottle"></i>
                    <span>Bar Inventory</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- FINANCE MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'Finance', 'Finance_Controller', 'Finance_Officer'])): ?>
        <div class="nav-module" data-module="finance">
            <div class="module-header">
                <i class="fas fa-calculator module-icon"></i>
                <span class="module-title">Finance & Accounting</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="finance_dashboard.php" class="nav-item <?php echo $current_page == 'finance_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Finance Dashboard</span>
                </a>
                <a href="accounting_dashboard.php" class="nav-item <?php echo $current_page == 'accounting_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-calculator"></i>
                    <span>Accounting Center</span>
                </a>
                <a href="financial_reports.php" class="nav-item <?php echo $current_page == 'financial_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Financial Reports</span>
                </a>
                <a href="transactions.php" class="nav-item <?php echo $current_page == 'transactions.php' ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span>Transactions</span>
                </a>
                <a href="journal_entries.php" class="nav-item <?php echo $current_page == 'journal_entries.php' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Journal Entries</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- REPORTS MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'Finance', 'Finance_Controller', 'Operations_Manager'])): ?>
        <div class="nav-module" data-module="reports">
            <div class="module-header">
                <i class="fas fa-chart-pie module-icon"></i>
                <span class="module-title">Reports & Analytics</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="bar_sales_reports.php" class="nav-item <?php echo $current_page == 'bar_sales_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-wine-glass"></i>
                    <span>Bar Sales Reports</span>
                </a>
                <a href="food_cost_reports.php" class="nav-item <?php echo $current_page == 'food_cost_reports.php' ? 'active' : ''; ?>">
                    <i class="fas fa-receipt"></i>
                    <span>Food Cost Reports</span>
                </a>
                <a href="room_revenue.php" class="nav-item <?php echo $current_page == 'room_revenue.php' ? 'active' : ''; ?>">
                    <i class="fas fa-bed"></i>
                    <span>Room Revenue</span>
                </a>
                <a href="guest_analytics.php" class="nav-item <?php echo $current_page == 'guest_analytics.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Guest Analytics</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- ADMINISTRATION MODULE -->
        <?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'IT_Admin'])): ?>
        <div class="nav-module" data-module="admin">
            <div class="module-header">
                <i class="fas fa-cog module-icon"></i>
                <span class="module-title">Administration</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="user_management.php" class="nav-item <?php echo $current_page == 'user_management.php' ? 'active' : ''; ?>">
                    <i class="fas fa-users-cog"></i>
                    <span>User Management</span>
                </a>
                <a href="settings.php" class="nav-item <?php echo $current_page == 'settings.php' ? 'active' : ''; ?>">
                    <i class="fas fa-sliders-h"></i>
                    <span>System Settings</span>
                </a>
                <a href="security_audit.php" class="nav-item <?php echo $current_page == 'security_audit.php' ? 'active' : ''; ?>">
                    <i class="fas fa-shield-alt"></i>
                    <span>Security Audit</span>
                </a>
                <a href="maya/admin/maya_training_dashboard.php" class="nav-item <?php echo $current_page == 'maya_training_dashboard.php' ? 'active' : ''; ?>">
                    <i class="fas fa-robot"></i>
                    <span>Maya AI Training</span>
                </a>
                <a href="help_center.php" class="nav-item <?php echo $current_page == 'help_center.php' ? 'active' : ''; ?>">
                    <i class="fas fa-question-circle"></i>
                    <span>Help Center</span>
                </a>
            </div>
        </div>
        <?php endif; ?>

        <!-- GENERAL ACCESS -->
        <div class="nav-module always-visible" data-module="general">
            <div class="module-header">
                <i class="fas fa-home module-icon"></i>
                <span class="module-title">Quick Access</span>
                <i class="fas fa-chevron-down toggle-icon"></i>
            </div>
            <div class="module-content">
                <a href="home.php" class="nav-item <?php echo $current_page == 'home.php' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard Overview</span>
                </a>
                <a href="messages.php" class="nav-item <?php echo $current_page == 'messages.php' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                </a>
            </div>
        </div>

        <!-- Logout Section -->
        <div class="sidebar-logout">
            <a href="logout.php" class="logout-btn" onclick="return confirm('Are you sure you want to logout?')">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
    </div>
</nav>

<!-- Modern Sidebar Styles -->
<style>
/* Modern Sidebar Styling */
.modern-admin-sidebar {
    position: fixed;
    top: 0;
    left: 0;
    width: 280px;
    height: 100vh;
    background: linear-gradient(145deg, #1e293b 0%, #334155 100%);
    border-right: 3px solid #3b82f6;
    box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
    z-index: 1000;
    transition: all 0.3s ease;
    overflow-y: auto;
    scrollbar-width: thin;
    scrollbar-color: rgba(255, 255, 255, 0.2) transparent;
}

.modern-admin-sidebar::-webkit-scrollbar {
    width: 6px;
}

.modern-admin-sidebar::-webkit-scrollbar-track {
    background: transparent;
}

.modern-admin-sidebar::-webkit-scrollbar-thumb {
    background-color: rgba(255, 255, 255, 0.2);
    border-radius: 3px;
}

/* Sidebar Header */
.sidebar-header {
    padding: 25px 20px;
    border-bottom: 2px solid rgba(255, 255, 255, 0.1);
    background: rgba(0, 0, 0, 0.1);
}

.logo-section {
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
}

.logo-icon {
    width: 140px;
    height: 60px;
    background: transparent;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 0px;
    box-shadow: none;
}

.logo-icon i {
    font-size: 24px;
    color: white;
}

.sidebar-logo-img {
    width: 100%;
    height: 100%;
    object-fit: contain;
    filter: brightness(1.2);
    background: transparent;
}

.logo-text {
    color: white;
}

.brand-name {
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 2px;
}

.brand-subtitle {
    font-size: 12px;
    opacity: 0.8;
    color: #94a3b8;
}

/* User Info in Sidebar */
.sidebar-user-info {
    display: flex;
    align-items: center;
    background: rgba(255, 255, 255, 0.05);
    padding: 12px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.1);
}

.user-avatar-small {
    width: 35px;
    height: 35px;
    background: linear-gradient(135deg, #10b981, #059669);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 14px;
    margin-right: 12px;
}

.user-details-small {
    color: white;
    flex: 1;
}

.user-name-small {
    font-size: 14px;
    font-weight: 600;
    margin-bottom: 2px;
}

.user-role-small {
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Navigation Modules */
.sidebar-nav {
    padding: 20px 0;
}

.nav-module {
    margin-bottom: 8px;
    border-radius: 10px;
    overflow: hidden;
    transition: all 0.3s ease;
}

.module-header {
    display: flex;
    align-items: center;
    padding: 15px 20px;
    cursor: pointer;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    margin: 0 10px;
    border-radius: 10px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.module-header:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(59, 130, 246, 0.5);
    transform: translateX(3px);
}

.module-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: linear-gradient(to bottom, #3b82f6, #1d4ed8);
    opacity: 0;
    transition: opacity 0.3s ease;
}

.module-header:hover::before {
    opacity: 1;
}

.module-icon {
    font-size: 16px;
    color: #3b82f6;
    margin-right: 12px;
    width: 20px;
    text-align: center;
}

.module-title {
    color: white;
    font-weight: 600;
    font-size: 14px;
    flex: 1;
}

.toggle-icon {
    font-size: 10px;
    color: #94a3b8;
    transition: transform 0.3s ease;
}

.nav-module.expanded .toggle-icon {
    transform: rotate(180deg);
}

/* Module Content */
.module-content {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
    background: rgba(0, 0, 0, 0.1);
    margin: 0 10px;
    border-radius: 0 0 10px 10px;
}

.nav-module.expanded .module-content {
    max-height: 300px;
    padding: 10px 0;
}

/* Navigation Items */
.nav-item {
    display: flex;
    align-items: center;
    padding: 12px 20px;
    color: #cbd5e1;
    text-decoration: none;
    transition: all 0.3s ease;
    position: relative;
    border-radius: 8px;
    margin: 2px 10px;
}

.nav-item:hover {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    transform: translateX(5px);
    text-decoration: none;
}

.nav-item.active {
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    box-shadow: 0 4px 15px rgba(59, 130, 246, 0.3);
}

.nav-item i {
    font-size: 14px;
    margin-right: 12px;
    width: 16px;
    text-align: center;
    opacity: 0.8;
}

.nav-item.active i {
    opacity: 1;
}

.nav-item span {
    font-size: 13px;
    font-weight: 500;
}

/* Logout Section */
.sidebar-logout {
    margin-top: 20px;
    padding: 0 20px;
}

.logout-btn {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 15px 20px;
    background: linear-gradient(135deg, #ef4444, #dc2626);
    color: white;
    text-decoration: none;
    border-radius: 10px;
    transition: all 0.3s ease;
    border: 2px solid transparent;
    font-weight: 600;
}

.logout-btn:hover {
    background: linear-gradient(135deg, #dc2626, #b91c1c);
    border-color: rgba(255, 255, 255, 0.2);
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(239, 68, 68, 0.3);
    text-decoration: none;
    color: white;
}

.logout-btn i {
    margin-right: 10px;
    font-size: 16px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .modern-admin-sidebar {
        left: -280px;
        transition: left 0.3s ease;
    }
    
    .modern-admin-sidebar.show {
        left: 0;
    }
}

/* Auto-expand active module */
.nav-module:has(.nav-item.active) {
    .module-content {
        max-height: 300px;
        padding: 10px 0;
    }
    
    .toggle-icon {
        transform: rotate(180deg);
    }
}
</style>

<!-- Sidebar JavaScript for Interactions -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Module toggle functionality
    const moduleHeaders = document.querySelectorAll('.module-header');
    
    moduleHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const module = this.parentElement;
            const isExpanded = module.classList.contains('expanded');
            
            // Close all other modules
            document.querySelectorAll('.nav-module').forEach(m => {
                if (m !== module) {
                    m.classList.remove('expanded');
                }
            });
            
            // Toggle current module
            module.classList.toggle('expanded', !isExpanded);
        });
    });
    
    // Auto-expand module with active item
    const activeItem = document.querySelector('.nav-item.active');
    if (activeItem) {
        const activeModule = activeItem.closest('.nav-module');
        if (activeModule) {
            activeModule.classList.add('expanded');
        }
    }
    
    // Auto-expand first module if none are active
    if (!activeItem) {
        const firstModule = document.querySelector('.nav-module');
        if (firstModule) {
            firstModule.classList.add('expanded');
        }
    }
});
</script>