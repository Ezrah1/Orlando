<?php
/**
 * Orlando International Resorts - Permission Manager
 * Comprehensive Role-Based Access Control (RBAC) Engine
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure session is active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Permission Manager Class
 * Handles dynamic permission checking and hierarchical role system
 */
class PermissionManager {
    private $db;
    private $user_id;
    private $user_role;
    private $permissions_cache = [];
    private $role_hierarchy = [];
    private $cache_duration = 900; // 15 minutes
    
    // Permission constants
    const PERMISSION_READ = 'read';
    const PERMISSION_WRITE = 'write';
    const PERMISSION_DELETE = 'delete';
    const PERMISSION_ADMIN = 'admin';
    
    // Module constants
    const MODULE_DASHBOARD = 'dashboard';
    const MODULE_BOOKINGS = 'bookings';
    const MODULE_ROOMS = 'rooms';
    const MODULE_GUESTS = 'guests';
    const MODULE_FINANCE = 'finance';
    const MODULE_OPERATIONS = 'operations';
    const MODULE_STAFF = 'staff';
    const MODULE_MAINTENANCE = 'maintenance';
    const MODULE_REPORTS = 'reports';
    const MODULE_SETTINGS = 'settings';
    const MODULE_USERS = 'users';
    const MODULE_INVENTORY = 'inventory';
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->user_id = $_SESSION['user_id'] ?? null;
        $this->user_role = $_SESSION['user_role'] ?? 'guest';
        
        $this->initializeRoleHierarchy();
        $this->loadUserPermissions();
    }
    
    /**
     * Initialize role hierarchy system
     */
    private function initializeRoleHierarchy() {
        $this->role_hierarchy = [
            'super_admin' => ['director', 'ceo', 'it_admin', 'finance_manager', 'operations_manager', 'department_head', 'staff'],
            'director' => ['ceo', 'finance_manager', 'operations_manager', 'department_head', 'staff'],
            'ceo' => ['finance_manager', 'operations_manager', 'department_head', 'staff'],
            'it_admin' => ['staff'],
            'finance_manager' => ['staff'],
            'operations_manager' => ['department_head', 'staff'],
            'department_head' => ['staff'],
            'staff' => [],
            'guest' => []
        ];
    }
    
    /**
     * Load user permissions from database and cache
     */
    private function loadUserPermissions() {
        if (!$this->user_id) {
            return;
        }
        
        // Check cache first
        $cache_key = "permissions_{$this->user_id}_{$this->user_role}";
        $cached_permissions = $this->getFromCache($cache_key);
        
        if ($cached_permissions !== null) {
            $this->permissions_cache = $cached_permissions;
            return;
        }
        
        // Load from database
        $permissions = $this->loadPermissionsFromDatabase();
        
        // Add inherited permissions
        $permissions = array_merge($permissions, $this->getInheritedPermissions());
        
        // Cache the permissions
        $this->permissions_cache = $permissions;
        $this->setCache($cache_key, $permissions, $this->cache_duration);
    }
    
    /**
     * Load permissions from database
     */
    private function loadPermissionsFromDatabase() {
        $permissions = [];
        
        try {
            // Get role-based permissions
            $role_query = "SELECT rp.module, rp.permission, rp.resource 
                          FROM role_permissions rp 
                          JOIN roles r ON r.id = rp.role_id 
                          WHERE r.role_name = ? AND r.is_active = 1";
            
            $stmt = $this->db->prepare($role_query);
            $stmt->bind_param("s", $this->user_role);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $key = $row['module'] . '.' . $row['resource'] . '.' . $row['permission'];
                $permissions[$key] = true;
            }
            
            // Get user-specific permissions (overrides)
            $user_query = "SELECT module, permission, resource, granted 
                          FROM user_permissions 
                          WHERE user_id = ?";
            
            $stmt = $this->db->prepare($user_query);
            $stmt->bind_param("i", $this->user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $key = $row['module'] . '.' . $row['resource'] . '.' . $row['permission'];
                $permissions[$key] = (bool)$row['granted'];
            }
            
        } catch (Exception $e) {
            error_log("Permission loading error: " . $e->getMessage());
            
            // Fallback to default permissions
            $permissions = $this->getDefaultPermissions();
        }
        
        return $permissions;
    }
    
    /**
     * Get inherited permissions from role hierarchy
     */
    private function getInheritedPermissions() {
        $inherited_permissions = [];
        
        // Get all roles this user inherits from
        $inherited_roles = $this->getInheritedRoles($this->user_role);
        
        foreach ($inherited_roles as $role) {
            try {
                $query = "SELECT rp.module, rp.permission, rp.resource 
                         FROM role_permissions rp 
                         JOIN roles r ON r.id = rp.role_id 
                         WHERE r.role_name = ? AND r.is_active = 1";
                
                $stmt = $this->db->prepare($query);
                $stmt->bind_param("s", $role);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $key = $row['module'] . '.' . $row['resource'] . '.' . $row['permission'];
                    $inherited_permissions[$key] = true;
                }
                
            } catch (Exception $e) {
                error_log("Inherited permissions error: " . $e->getMessage());
            }
        }
        
        return $inherited_permissions;
    }
    
    /**
     * Get roles inherited by current role
     */
    private function getInheritedRoles($role) {
        return $this->role_hierarchy[$role] ?? [];
    }
    
    /**
     * Check if user has specific permission
     */
    public function hasPermission($module, $resource = '*', $permission = self::PERMISSION_READ) {
        // Super admin has all permissions
        if ($this->user_role === 'super_admin') {
            return true;
        }
        
        // Check specific permission
        $key = $module . '.' . $resource . '.' . $permission;
        if (isset($this->permissions_cache[$key])) {
            return $this->permissions_cache[$key];
        }
        
        // Check wildcard resource permission
        $wildcard_key = $module . '.*.' . $permission;
        if (isset($this->permissions_cache[$wildcard_key])) {
            return $this->permissions_cache[$wildcard_key];
        }
        
        // Check default role-based permissions
        return $this->checkDefaultPermission($module, $resource, $permission);
    }
    
    /**
     * Check multiple permissions at once
     */
    public function hasAnyPermission($permissions) {
        foreach ($permissions as $perm) {
            if (is_array($perm) && count($perm) >= 2) {
                $module = $perm[0];
                $resource = $perm[1] ?? '*';
                $permission = $perm[2] ?? self::PERMISSION_READ;
                
                if ($this->hasPermission($module, $resource, $permission)) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Check if user has all specified permissions
     */
    public function hasAllPermissions($permissions) {
        foreach ($permissions as $perm) {
            if (is_array($perm) && count($perm) >= 2) {
                $module = $perm[0];
                $resource = $perm[1] ?? '*';
                $permission = $perm[2] ?? self::PERMISSION_READ;
                
                if (!$this->hasPermission($module, $resource, $permission)) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Check if user can access dashboard
     */
    public function canAccessDashboard($dashboard_type = 'general') {
        switch ($dashboard_type) {
            case 'director':
            case 'executive':
                return $this->hasPermission(self::MODULE_DASHBOARD, 'executive', self::PERMISSION_READ);
                
            case 'operations':
                return $this->hasPermission(self::MODULE_OPERATIONS, '*', self::PERMISSION_READ);
                
            case 'finance':
                return $this->hasPermission(self::MODULE_FINANCE, '*', self::PERMISSION_READ);
                
            case 'it_admin':
                return $this->hasPermission(self::MODULE_SETTINGS, 'system', self::PERMISSION_ADMIN);
                
            default:
                return $this->hasPermission(self::MODULE_DASHBOARD, 'general', self::PERMISSION_READ);
        }
    }
    
    /**
     * Check if user can manage users
     */
    public function canManageUsers($action = 'read') {
        return $this->hasPermission(self::MODULE_USERS, '*', $action);
    }
    
    /**
     * Check if user can access financial data
     */
    public function canAccessFinance($resource = '*', $action = 'read') {
        return $this->hasPermission(self::MODULE_FINANCE, $resource, $action);
    }
    
    /**
     * Check if user can manage rooms
     */
    public function canManageRooms($action = 'read') {
        return $this->hasPermission(self::MODULE_ROOMS, '*', $action);
    }
    
    /**
     * Check if user can access reports
     */
    public function canAccessReports($report_type = '*') {
        return $this->hasPermission(self::MODULE_REPORTS, $report_type, self::PERMISSION_READ);
    }
    
    /**
     * Get user's accessible modules
     */
    public function getAccessibleModules() {
        $modules = [];
        $all_modules = [
            self::MODULE_DASHBOARD,
            self::MODULE_BOOKINGS,
            self::MODULE_ROOMS,
            self::MODULE_GUESTS,
            self::MODULE_FINANCE,
            self::MODULE_OPERATIONS,
            self::MODULE_STAFF,
            self::MODULE_MAINTENANCE,
            self::MODULE_REPORTS,
            self::MODULE_SETTINGS,
            self::MODULE_USERS,
            self::MODULE_INVENTORY
        ];
        
        foreach ($all_modules as $module) {
            if ($this->hasPermission($module, '*', self::PERMISSION_READ)) {
                $modules[] = $module;
            }
        }
        
        return $modules;
    }
    
    /**
     * Generate dynamic menu based on permissions
     */
    public function generateMenu() {
        $menu = [];
        
        // Dashboard
        if ($this->hasPermission(self::MODULE_DASHBOARD, '*', self::PERMISSION_READ)) {
            $menu['dashboard'] = [
                'title' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => 'home.php',
                'submenu' => $this->getDashboardSubmenu()
            ];
        }
        
        // Bookings & Reservations
        if ($this->hasPermission(self::MODULE_BOOKINGS, '*', self::PERMISSION_READ)) {
            $menu['bookings'] = [
                'title' => 'Bookings',
                'icon' => 'fas fa-calendar-alt',
                'url' => 'booking.php',
                'submenu' => $this->getBookingsSubmenu()
            ];
        }
        
        // Room Management
        if ($this->hasPermission(self::MODULE_ROOMS, '*', self::PERMISSION_READ)) {
            $menu['rooms'] = [
                'title' => 'Room Management',
                'icon' => 'fas fa-bed',
                'url' => 'room.php',
                'submenu' => $this->getRoomsSubmenu()
            ];
        }
        
        // Operations
        if ($this->hasPermission(self::MODULE_OPERATIONS, '*', self::PERMISSION_READ)) {
            $menu['operations'] = [
                'title' => 'Operations',
                'icon' => 'fas fa-cogs',
                'url' => 'operations.php',
                'submenu' => $this->getOperationsSubmenu()
            ];
        }
        
        // Finance
        if ($this->hasPermission(self::MODULE_FINANCE, '*', self::PERMISSION_READ)) {
            $menu['finance'] = [
                'title' => 'Finance',
                'icon' => 'fas fa-dollar-sign',
                'url' => 'finance.php',
                'submenu' => $this->getFinanceSubmenu()
            ];
        }
        
        // Reports
        if ($this->hasPermission(self::MODULE_REPORTS, '*', self::PERMISSION_READ)) {
            $menu['reports'] = [
                'title' => 'Reports',
                'icon' => 'fas fa-chart-bar',
                'url' => 'reports.php',
                'submenu' => $this->getReportsSubmenu()
            ];
        }
        
        // Settings
        if ($this->hasPermission(self::MODULE_SETTINGS, '*', self::PERMISSION_READ)) {
            $menu['settings'] = [
                'title' => 'Settings',
                'icon' => 'fas fa-cog',
                'url' => 'settings.php',
                'submenu' => $this->getSettingsSubmenu()
            ];
        }
        
        return $menu;
    }
    
    /**
     * Get dashboard submenu based on permissions
     */
    private function getDashboardSubmenu() {
        $submenu = [];
        
        if ($this->canAccessDashboard('general')) {
            $submenu['general'] = ['title' => 'General Overview', 'url' => 'home.php'];
        }
        
        if ($this->canAccessDashboard('executive')) {
            $submenu['executive'] = ['title' => 'Executive Dashboard', 'url' => 'director_dashboard.php'];
        }
        
        if ($this->canAccessDashboard('operations')) {
            $submenu['operations'] = ['title' => 'Operations Dashboard', 'url' => 'operations_manager_dashboard.php'];
        }
        
        if ($this->canAccessDashboard('finance')) {
            $submenu['finance'] = ['title' => 'Finance Dashboard', 'url' => 'finance_dashboard.php'];
        }
        
        if ($this->canAccessDashboard('it_admin')) {
            $submenu['it_admin'] = ['title' => 'IT Admin Dashboard', 'url' => 'it_admin_dashboard.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get bookings submenu based on permissions
     */
    private function getBookingsSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_BOOKINGS, 'list', self::PERMISSION_READ)) {
            $submenu['list'] = ['title' => 'All Reservations', 'url' => 'reservation.php'];
        }
        
        if ($this->hasPermission(self::MODULE_BOOKINGS, 'create', self::PERMISSION_WRITE)) {
            $submenu['create'] = ['title' => 'New Booking', 'url' => 'roombook.php'];
        }
        
        if ($this->hasPermission(self::MODULE_BOOKINGS, 'staff', self::PERMISSION_WRITE)) {
            $submenu['staff'] = ['title' => 'Staff Booking', 'url' => 'staff_booking.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get rooms submenu based on permissions
     */
    private function getRoomsSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_ROOMS, 'list', self::PERMISSION_READ)) {
            $submenu['list'] = ['title' => 'Room List', 'url' => 'room.php'];
        }
        
        if ($this->hasPermission(self::MODULE_OPERATIONS, 'housekeeping', self::PERMISSION_READ)) {
            $submenu['housekeeping'] = ['title' => 'Housekeeping', 'url' => 'housekeeping.php'];
        }
        
        if ($this->hasPermission(self::MODULE_MAINTENANCE, '*', self::PERMISSION_READ)) {
            $submenu['maintenance'] = ['title' => 'Maintenance', 'url' => 'maintenance_management.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get operations submenu based on permissions
     */
    private function getOperationsSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_OPERATIONS, 'housekeeping', self::PERMISSION_READ)) {
            $submenu['housekeeping'] = ['title' => 'Housekeeping Management', 'url' => 'housekeeping_management.php'];
        }
        
        if ($this->hasPermission(self::MODULE_INVENTORY, '*', self::PERMISSION_READ)) {
            $submenu['inventory'] = ['title' => 'Inventory', 'url' => 'inventory.php'];
        }
        
        if ($this->hasPermission(self::MODULE_STAFF, '*', self::PERMISSION_READ)) {
            $submenu['staff'] = ['title' => 'Staff Management', 'url' => 'user_management.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get finance submenu based on permissions
     */
    private function getFinanceSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_FINANCE, 'reports', self::PERMISSION_READ)) {
            $submenu['reports'] = ['title' => 'Financial Reports', 'url' => 'financial_reports.php'];
        }
        
        if ($this->hasPermission(self::MODULE_FINANCE, 'accounting', self::PERMISSION_READ)) {
            $submenu['accounting'] = ['title' => 'Chart of Accounts', 'url' => 'chart_of_accounts.php'];
        }
        
        if ($this->hasPermission(self::MODULE_FINANCE, 'petty_cash', self::PERMISSION_READ)) {
            $submenu['petty_cash'] = ['title' => 'Petty Cash', 'url' => 'petty_cash.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get reports submenu based on permissions
     */
    private function getReportsSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_REPORTS, 'revenue', self::PERMISSION_READ)) {
            $submenu['revenue'] = ['title' => 'Revenue Analytics', 'url' => 'revenue_analytics.php'];
        }
        
        if ($this->hasPermission(self::MODULE_REPORTS, 'operations', self::PERMISSION_READ)) {
            $submenu['operations'] = ['title' => 'Operations Reports', 'url' => 'operations_reports.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Get settings submenu based on permissions
     */
    private function getSettingsSubmenu() {
        $submenu = [];
        
        if ($this->hasPermission(self::MODULE_SETTINGS, 'general', self::PERMISSION_WRITE)) {
            $submenu['general'] = ['title' => 'General Settings', 'url' => 'settings.php'];
        }
        
        if ($this->hasPermission(self::MODULE_USERS, '*', self::PERMISSION_ADMIN)) {
            $submenu['users'] = ['title' => 'User Management', 'url' => 'user_management.php'];
        }
        
        return $submenu;
    }
    
    /**
     * Check default permission based on role
     */
    private function checkDefaultPermission($module, $resource, $permission) {
        $defaults = $this->getDefaultPermissions();
        
        // Check exact match
        $key = $module . '.' . $resource . '.' . $permission;
        if (isset($defaults[$key])) {
            return $defaults[$key];
        }
        
        // Check wildcard
        $wildcard_key = $module . '.*.' . $permission;
        if (isset($defaults[$wildcard_key])) {
            return $defaults[$wildcard_key];
        }
        
        return false;
    }
    
    /**
     * Get default permissions for current role
     */
    private function getDefaultPermissions() {
        $role_permissions = [
            'super_admin' => [
                '*.*.*' => true // Super admin has all permissions
            ],
            
            'director' => [
                'dashboard.*.read' => true,
                'dashboard.executive.read' => true,
                'bookings.*.read' => true,
                'rooms.*.read' => true,
                'finance.*.read' => true,
                'finance.*.write' => true,
                'operations.*.read' => true,
                'reports.*.read' => true,
                'staff.*.read' => true,
                'settings.general.read' => true
            ],
            
            'ceo' => [
                'dashboard.*.read' => true,
                'dashboard.executive.read' => true,
                'bookings.*.read' => true,
                'rooms.*.read' => true,
                'finance.*.read' => true,
                'operations.*.read' => true,
                'reports.*.read' => true,
                'staff.*.read' => true
            ],
            
            'finance_manager' => [
                'dashboard.*.read' => true,
                'dashboard.finance.read' => true,
                'finance.*.read' => true,
                'finance.*.write' => true,
                'finance.*.delete' => true,
                'bookings.*.read' => true,
                'reports.revenue.read' => true,
                'reports.finance.read' => true
            ],
            
            'operations_manager' => [
                'dashboard.*.read' => true,
                'dashboard.operations.read' => true,
                'operations.*.read' => true,
                'operations.*.write' => true,
                'rooms.*.read' => true,
                'rooms.*.write' => true,
                'maintenance.*.read' => true,
                'maintenance.*.write' => true,
                'inventory.*.read' => true,
                'inventory.*.write' => true,
                'staff.*.read' => true,
                'bookings.*.read' => true
            ],
            
            'it_admin' => [
                'dashboard.*.read' => true,
                'dashboard.it_admin.read' => true,
                'settings.*.read' => true,
                'settings.*.write' => true,
                'settings.*.admin' => true,
                'users.*.read' => true,
                'users.*.write' => true,
                'users.*.admin' => true,
                'reports.system.read' => true
            ],
            
            'department_head' => [
                'dashboard.*.read' => true,
                'dashboard.management.read' => true,
                'operations.*.read' => true,
                'staff.department.read' => true,
                'staff.department.write' => true,
                'reports.department.read' => true
            ],
            
            'staff' => [
                'dashboard.general.read' => true,
                'dashboard.staff.read' => true,
                'bookings.*.read' => true,
                'bookings.create.write' => true,
                'rooms.status.read' => true,
                'rooms.status.write' => true,
                'operations.tasks.read' => true,
                'operations.tasks.write' => true
            ]
        ];
        
        return $role_permissions[$this->user_role] ?? [];
    }
    
    /**
     * Log permission check for auditing
     */
    private function logPermissionCheck($module, $resource, $permission, $granted) {
        try {
            $query = "INSERT INTO audit_logs (user_id, action, table_name, details, ip_address, timestamp) 
                     VALUES (?, 'permission_check', 'permissions', ?, ?, NOW())";
            
            $details = json_encode([
                'module' => $module,
                'resource' => $resource,
                'permission' => $permission,
                'granted' => $granted,
                'role' => $this->user_role
            ]);
            
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("iss", $this->user_id, $details, $ip_address);
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("Permission audit log error: " . $e->getMessage());
        }
    }
    
    /**
     * Cache management
     */
    private function getFromCache($key) {
        $cache_file = sys_get_temp_dir() . '/hotel_permissions_' . md5($key) . '.cache';
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $this->cache_duration) {
            return json_decode(file_get_contents($cache_file), true);
        }
        return null;
    }
    
    private function setCache($key, $data, $duration) {
        $cache_file = sys_get_temp_dir() . '/hotel_permissions_' . md5($key) . '.cache';
        file_put_contents($cache_file, json_encode($data));
    }
    
    /**
     * Clear permissions cache
     */
    public function clearCache() {
        $cache_pattern = sys_get_temp_dir() . '/hotel_permissions_*.cache';
        foreach (glob($cache_pattern) as $cache_file) {
            unlink($cache_file);
        }
    }
    
    /**
     * Get current user role
     */
    public function getUserRole() {
        return $this->user_role;
    }
    
    /**
     * Get current user ID
     */
    public function getUserId() {
        return $this->user_id;
    }
    
    /**
     * Check if current user is in role or inherits from role
     */
    public function isInRole($role) {
        if ($this->user_role === $role) {
            return true;
        }
        
        $inherited_roles = $this->getInheritedRoles($this->user_role);
        return in_array($role, $inherited_roles);
    }
}

/**
 * Global permission functions for easy access
 */
function getPermissionManager() {
    global $con;
    static $permission_manager = null;
    
    if ($permission_manager === null) {
        try {
            $permission_manager = new PermissionManager($con);
        } catch (Exception $e) {
            error_log("Permission Manager Error: " . $e->getMessage());
            return null;
        }
    }
    
    return $permission_manager;
}

function hasPermission($module, $resource = '*', $permission = 'read') {
    $pm = getPermissionManager();
    return $pm ? $pm->hasPermission($module, $resource, $permission) : false;
}

function canAccessDashboard($type = 'general') {
    $pm = getPermissionManager();
    return $pm ? $pm->canAccessDashboard($type) : false;
}

function generateDynamicMenu() {
    $pm = getPermissionManager();
    return $pm ? $pm->generateMenu() : [];
}

function requirePermission($module, $resource = '*', $permission = 'read') {
    if (!hasPermission($module, $resource, $permission)) {
        header('Location: access_denied.php');
        exit;
    }
}
?>
