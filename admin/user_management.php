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

// Check permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Director (role_id = 11) and Admin (role_id = 1) get automatic access
if ($user_role_id == 11 || $user_role_id == 1) {
    // Director and Admin bypass all checks
} else {
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'IT_Admin', 'director', 'ceo', 'super_admin', 'it_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), $allowed_roles) && 
        !user_has_permission($con, 'user.create')) {
        header('Location: access_denied.php');
        exit();
    }
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle POST actions BEFORE any output
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header('Location: user_management.php');
        exit();
    }
    
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
        case 'create_user':
            // Validate input data
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role_id = (int)($_POST['role_id'] ?? 0);
            $dept_id = (int)($_POST['dept_id'] ?? 0);
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($username) || empty($email) || empty($password) || $role_id <= 0 || $dept_id <= 0) {
                throw new Exception("All required fields must be filled.");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }
            
            if (strlen($password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }
            
            // Check if username or email already exists
            $check_sql = "SELECT id FROM users WHERE username = ? OR email = ?";
            $check_stmt = mysqli_prepare($con, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ss", $username, $email);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Username or email already exists.");
            }
            
            // Hash password
            $password_hash = password_hash($password, PASSWORD_BCRYPT);
            
            // Use prepared statement for security
            $sql = "INSERT INTO users (username, password_hash, role_id, dept_id, email, phone, status) VALUES (?, ?, ?, ?, ?, ?, 'active')";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "ssiiss", $username, $password_hash, $role_id, $dept_id, $email, $phone);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User created successfully!";
            } else {
                throw new Exception("Error creating user: " . mysqli_error($con));
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'update_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $role_id = (int)($_POST['role_id'] ?? 0);
            $dept_id = (int)($_POST['dept_id'] ?? 0);
            $status = $_POST['status'] ?? '';
            
            // Validation
            if ($user_id <= 0 || empty($username) || empty($email) || $role_id <= 0 || $dept_id <= 0) {
                throw new Exception("All required fields must be filled.");
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email format.");
            }
            
            if (!in_array($status, ['active', 'inactive', 'suspended'])) {
                throw new Exception("Invalid status value.");
            }
            
            // Check if username or email already exists for other users
            $check_sql = "SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?";
            $check_stmt = mysqli_prepare($con, $check_sql);
            mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if (mysqli_num_rows($check_result) > 0) {
                throw new Exception("Username or email already exists.");
            }
            
            // Use prepared statement for security
            $sql = "UPDATE users SET username=?, email=?, phone=?, role_id=?, dept_id=?, status=? WHERE id=?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "sssiisi", $username, $email, $phone, $role_id, $dept_id, $status, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User updated successfully!";
            } else {
                throw new Exception("Error updating user: " . mysqli_error($con));
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'delete_user':
            $user_id = (int)($_POST['user_id'] ?? 0);
            
            if ($user_id <= 0) {
                throw new Exception("Invalid user ID.");
            }
            
            // Don't allow deletion of the current user
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("Cannot delete your own account!");
            }
            
            // Use prepared statement for security
            $sql = "DELETE FROM users WHERE id=?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "User deleted successfully!";
            } else {
                throw new Exception("Error deleting user: " . mysqli_error($con));
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'reset_password':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            if ($user_id <= 0) {
                throw new Exception("Invalid user ID.");
            }
            
            if (empty($new_password)) {
                throw new Exception("Password cannot be empty.");
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception("Password must be at least 6 characters long.");
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception("Passwords do not match.");
            }
            
            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
            
            // Use prepared statement for security
            $sql = "UPDATE users SET password_hash=? WHERE id=?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Password reset successfully!";
            } else {
                throw new Exception("Error resetting password: " . mysqli_error($con));
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'quick_status_change':
            $user_id = (int)($_POST['user_id'] ?? 0);
            $new_status = $_POST['new_status'] ?? '';
            
            if ($user_id <= 0) {
                throw new Exception("Invalid user ID.");
            }
            
            if (!in_array($new_status, ['active', 'inactive', 'suspended'])) {
                throw new Exception("Invalid status value.");
            }
            
            // Don't allow status change of the current user
            if ($user_id == $_SESSION['user_id']) {
                throw new Exception("Cannot change your own status!");
            }
            
            // Use prepared statement for security
            $sql = "UPDATE users SET status=? WHERE id=?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "si", $new_status, $user_id);
            
            if (mysqli_stmt_execute($stmt)) {
                $status_messages = [
                    'active' => "User has been ACTIVATED and can now access the system! 🟢",
                    'suspended' => "User has been PAUSED/SUSPENDED (reversible - you can resume anytime) 🟡",
                    'inactive' => "User has been set to INACTIVE 🔴"
                ];
                $_SESSION['success_message'] = $status_messages[$new_status] ?? "User status updated successfully!";
            } else {
                throw new Exception("Error updating user status: " . mysqli_error($con));
            }
            mysqli_stmt_close($stmt);
            break;
            
        case 'bulk_suspend':
        case 'bulk_activate':
        case 'bulk_delete':
        case 'bulk_reset_passwords':
            $user_ids = json_decode($_POST['user_ids'] ?? '[]', true);
            
            if (empty($user_ids) || !is_array($user_ids)) {
                throw new Exception("No users selected.");
            }
            
            $success_count = 0;
            $errors = [];
            
            foreach ($user_ids as $user_id) {
                $user_id = (int)$user_id;
                
                // Don't allow actions on current user
                if ($user_id == $_SESSION['user_id'] && in_array($action, ['bulk_suspend', 'bulk_delete'])) {
                    $errors[] = "Cannot perform action on your own account";
                    continue;
                }
                
                try {
                    switch ($action) {
                        case 'bulk_suspend':
                            $sql = "UPDATE users SET status='suspended' WHERE id=?";
                            $stmt = mysqli_prepare($con, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            break;
                            
                        case 'bulk_activate':
                            $sql = "UPDATE users SET status='active' WHERE id=?";
                            $stmt = mysqli_prepare($con, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            break;
                            
                        case 'bulk_delete':
                            $sql = "DELETE FROM users WHERE id=?";
                            $stmt = mysqli_prepare($con, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            break;
                            
                        case 'bulk_reset_passwords':
                            $new_password = bin2hex(random_bytes(8)); // Generate random password
                            $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                            $sql = "UPDATE users SET password_hash=? WHERE id=?";
                            $stmt = mysqli_prepare($con, $sql);
                            mysqli_stmt_bind_param($stmt, "si", $password_hash, $user_id);
                            break;
                    }
                    
                    if (mysqli_stmt_execute($stmt)) {
                        $success_count++;
                    } else {
                        $errors[] = "Failed to update user ID: $user_id";
                    }
                    mysqli_stmt_close($stmt);
                    
                } catch (Exception $e) {
                    $errors[] = "Error with user ID $user_id: " . $e->getMessage();
                }
            }
            
            // Set success message
            $action_messages = [
                'bulk_suspend' => "Successfully paused $success_count user(s)",
                'bulk_activate' => "Successfully activated $success_count user(s)",
                'bulk_delete' => "Successfully deleted $success_count user(s)",
                'bulk_reset_passwords' => "Successfully reset passwords for $success_count user(s)"
            ];
            
            $message = $action_messages[$action] ?? "Bulk action completed for $success_count user(s)";
            
            if (!empty($errors)) {
                $message .= ". Errors: " . implode(', ', $errors);
            }
            
            $_SESSION['success_message'] = $message;
            break;
            
        default:
            throw new Exception("Invalid action.");
    }
    
    // Redirect to prevent resubmission
    header('Location: user_management.php');
    exit();
    
    } catch (Exception $e) {
        $_SESSION['error_message'] = $e->getMessage();
        header('Location: user_management.php');
        exit();
    }
}

$page_title = 'User Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<?php
// Display session alerts
display_session_alerts();

// Pagination and search
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build search condition
$search_condition = '';
$search_params = [];
$search_types = '';

if (!empty($search)) {
    $search_condition = " WHERE (u.username LIKE ? OR u.email LIKE ? OR r.name LIKE ? OR d.name LIKE ?)";
    $search_term = "%$search%";
    $search_params = [$search_term, $search_term, $search_term, $search_term];
    $search_types = 'ssss';
}

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total 
              FROM users u 
              LEFT JOIN roles r ON u.role_id = r.id 
              LEFT JOIN departments d ON u.dept_id = d.id" . $search_condition;

if (!empty($search_condition)) {
    $count_stmt = mysqli_prepare($con, $count_sql);
    mysqli_stmt_bind_param($count_stmt, $search_types, ...$search_params);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
} else {
    $count_result = mysqli_query($con, $count_sql);
}

$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $records_per_page);

// Get users with pagination
$users_sql = "SELECT u.*, r.name as role_name, d.name as dept_name 
              FROM users u 
              LEFT JOIN roles r ON u.role_id = r.id 
              LEFT JOIN departments d ON u.dept_id = d.id" . 
              $search_condition . 
              " ORDER BY u.username LIMIT ? OFFSET ?";

if (!empty($search_condition)) {
    $users_stmt = mysqli_prepare($con, $users_sql);
    $all_params = array_merge($search_params, [$records_per_page, $offset]);
    $all_types = $search_types . 'ii';
    mysqli_stmt_bind_param($users_stmt, $all_types, ...$all_params);
    mysqli_stmt_execute($users_stmt);
    $users_result = mysqli_stmt_get_result($users_stmt);
} else {
    $users_stmt = mysqli_prepare($con, $users_sql);
    mysqli_stmt_bind_param($users_stmt, 'ii', $records_per_page, $offset);
    mysqli_stmt_execute($users_stmt);
    $users_result = mysqli_stmt_get_result($users_stmt);
}

// Get all roles for dropdown
$roles_sql = "SELECT * FROM roles ORDER BY name";
$roles_result = mysqli_query($con, $roles_sql);

// Get all departments for dropdown
$depts_sql = "SELECT * FROM departments ORDER BY name";
$depts_result = mysqli_query($con, $depts_sql);
?>


    <!-- Include Admin Sidebar -->
    <?php include '../includes/admin/sidebar.php'; ?>

    <div class="admin-main-content">
        <div class="content-wrapper p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-users"></i> User Management</h2>
                    <button class="btn btn-primary" data-toggle="modal" data-target="#createUserModal">
                        <i class="fas fa-plus"></i> Add New User
                    </button>
                </div>

                <!-- Bulk Actions Bar -->
                <div id="bulkActionsBar" class="card mb-4" style="display: none;">
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <span id="selectedCount" class="badge badge-primary badge-lg">0</span> 
                                <span class="text-muted">users selected</span>
                            </div>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="bulkEditUsers()">
                                    <i class="fas fa-edit"></i> Edit Selected
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="bulkSuspendUsers()">
                                    <i class="fas fa-pause"></i> Pause Selected
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkActivateUsers()">
                                    <i class="fas fa-play"></i> Activate Selected
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="bulkResetPasswords()">
                                    <i class="fas fa-key"></i> Reset Passwords
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDeleteUsers()">
                                    <i class="fas fa-trash"></i> Delete Selected
                                </button>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="clearSelection()">
                                    <i class="fas fa-times"></i> Clear Selection
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message']); unset($_SESSION['success_message']); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message']); unset($_SESSION['error_message']); ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Modern Search Section -->
                <div class="card mb-4 search-card">
                    <div class="card-body">
                        <form method="GET" class="row align-items-center">
                            <div class="col-md-8">
                                <div class="search-input-wrapper">
                                    <i class="fas fa-search search-icon"></i>
                                    <input type="text" class="form-control modern-search-input" name="search" 
                                           placeholder="Search users by name, email, role, or department..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                    <?php if (!empty($search)): ?>
                                        <a href="user_management.php" class="clear-search-btn" title="Clear search">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-4 text-right">
                                <button type="submit" class="btn btn-primary search-btn">
                                    <i class="fas fa-search"></i> Search
                                </button>
                                <div class="user-count-badge mt-2">
                                    <i class="fas fa-users"></i> <?php echo $total_records; ?> users total
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list"></i> All Users</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th width="40">
                                            <div class="custom-control custom-checkbox">
                                                <input type="checkbox" class="custom-control-input" id="selectAll" onchange="toggleSelectAll()">
                                                <label class="custom-control-label" for="selectAll"></label>
                                            </div>
                                        </th>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th class="text-center" width="80">Quick</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                                        <tr class="user-row" data-user-id="<?php echo $user['id']; ?>" data-username="<?php echo htmlspecialchars($user['username']); ?>">
                                            <td>
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input user-checkbox" 
                                                           id="user_<?php echo $user['id']; ?>" 
                                                           data-user-id="<?php echo $user['id']; ?>"
                                                           data-username="<?php echo htmlspecialchars($user['username']); ?>"
                                                           onchange="updateSelection()">
                                                    <label class="custom-control-label" for="user_<?php echo $user['id']; ?>"></label>
                                                </div>
                                            </td>
                                            <td data-label="Username">
                                                <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                            </td>
                                            <td data-label="Email"><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td data-label="Phone"><?php echo htmlspecialchars($user['phone']) ?: '<em class="text-muted">Not set</em>'; ?></td>
                                            <td data-label="Role">
                                                <span class="badge badge-info">
                                                    <?php echo htmlspecialchars($user['role_name']); ?>
                                                </span>
                                            </td>
                                            <td data-label="Department"><?php echo htmlspecialchars($user['dept_name']); ?></td>
                                            <td data-label="Status">
                                                <?php
                                                $status_icons = [
                                                    'active' => ['icon' => 'fa-check-circle', 'color' => 'success', 'text' => 'Active', 'next' => 'Can be paused ⏸️'],
                                                    'suspended' => ['icon' => 'fa-pause-circle', 'color' => 'warning', 'text' => 'Paused', 'next' => 'Can be resumed ▶️'],
                                                    'inactive' => ['icon' => 'fa-times-circle', 'color' => 'danger', 'text' => 'Inactive', 'next' => 'Can be enabled 🔛']
                                                ];
                                                $status_info = $status_icons[$user['status']] ?? $status_icons['inactive'];
                                                ?>
                                                <span class="badge badge-<?php echo $status_info['color']; ?>" 
                                                      title="<?php echo $status_info['text']; ?> - <?php echo $status_info['next']; ?>">
                                                    <i class="fas <?php echo $status_info['icon']; ?>"></i> 
                                                    <?php echo $status_info['text']; ?>
                                                </span>
                                            </td>
                                            <td data-label="Created">
                                                <span title="<?php echo date('F j, Y \a\t g:i A', strtotime($user['created_at'])); ?>">
                                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                                </span>
                                            </td>
                                            <td data-label="Quick" class="text-center">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                                            type="button" 
                                                            id="quickActions<?php echo $user['id']; ?>" 
                                                            data-toggle="dropdown" 
                                                            title="Quick Actions">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right">
                                                        <a class="dropdown-item" href="#" onclick="editUser(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-edit text-primary"></i> Edit
                                                        </a>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <?php if ($user['status'] == 'active'): ?>
                                                                <a class="dropdown-item" href="#" onclick="quickSuspendUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                                    <i class="fas fa-pause text-warning"></i> Pause
                                                                </a>
                                                            <?php else: ?>
                                                                <a class="dropdown-item" href="#" onclick="quickActivateUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                                    <i class="fas fa-play text-success"></i> Activate
                                                                </a>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                        <a class="dropdown-item" href="#" onclick="resetPassword(<?php echo $user['id']; ?>)">
                                                            <i class="fas fa-key text-info"></i> Reset Password
                                                        </a>
                                                        <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                            <div class="dropdown-divider"></div>
                                                            <a class="dropdown-item text-danger" href="#" onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>')">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div>
                                    <small class="text-muted">
                                        Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $records_per_page, $total_records); ?> 
                                        of <?php echo $total_records; ?> users
                                    </small>
                                </div>
                                <nav aria-label="Users pagination">
                                    <ul class="pagination pagination-sm mb-0">
                                        <?php if ($page > 1): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        for ($i = $start_page; $i <= $end_page; $i++): ?>
                                            <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                    <?php echo $i; ?>
                                                </a>
                                            </li>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <li class="page-item">
                                                <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_user">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="phone">
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="role_id" required>
                                <option value="">Select Role</option>
                                <?php while ($role = mysqli_fetch_assoc($roles_result)): ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Department</label>
                            <select class="form-control" name="dept_id" required>
                                <option value="">Select Department</option>
                                <?php while ($dept = mysqli_fetch_assoc($depts_result)): ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Password</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit User</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_user">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label>Username</label>
                            <input type="text" class="form-control" name="username" id="edit_username" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" name="email" id="edit_email" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="text" class="form-control" name="phone" id="edit_phone">
                        </div>
                        
                        <div class="form-group">
                            <label>Role</label>
                            <select class="form-control" name="role_id" id="edit_role_id" required>
                                <option value="">Select Role</option>
                                <?php 
                                mysqli_data_seek($roles_result, 0);
                                while ($role = mysqli_fetch_assoc($roles_result)): 
                                ?>
                                    <option value="<?php echo $role['id']; ?>">
                                        <?php echo htmlspecialchars($role['name']); ?> - <?php echo htmlspecialchars($role['description']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Department</label>
                            <select class="form-control" name="dept_id" id="edit_dept_id" required>
                                <option value="">Select Department</option>
                                <?php 
                                mysqli_data_seek($depts_result, 0);
                                while ($dept = mysqli_fetch_assoc($depts_result)): 
                                ?>
                                    <option value="<?php echo $dept['id']; ?>">
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Status <span class="text-info">(Pause/Resume is fully reversible)</span></label>
                            <select class="form-control" name="status" id="edit_status" required>
                                <option value="active">🟢 Active - Full Access</option>
                                <option value="suspended">🟡 Paused/Suspended - Temporary Block (Reversible)</option>
                                <option value="inactive">🔴 Inactive - Account Disabled</option>
                            </select>
                            <small class="text-muted">
                                <strong>🔄 Reversible Actions:</strong><br>
                                • <strong>Active ↔ Paused:</strong> You can pause and resume users anytime<br>
                                • <strong>Paused:</strong> User cannot login but account & data are preserved<br>
                                • <strong>Inactive:</strong> Account disabled (requires manual reactivation)<br>
                                <em>💡 Tip: Use Pause instead of Inactive for temporary access control</em>
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reset Password Modal -->
    <div class="modal fade" id="resetPasswordModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reset Password</h5>
                    <button type="button" class="close" data-dismiss="modal">
                        <span>&times;</span>
                    </button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reset_password">
                        <input type="hidden" name="user_id" id="reset_user_id">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" class="form-control" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <style>
    .status-active {
        color: #28a745;
    }
    .status-inactive {
        color: #dc3545;
    }
    .status-suspended {
        color: #ffc107;
    }
    .table td {
        vertical-align: middle;
    }
    .search-form {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 1rem;
    }
    .pagination .page-link {
        border-radius: 4px;
        margin: 0 2px;
    }
    .user-count-badge {
        background: #e9ecef;
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.875rem;
    }
    .btn-group .btn {
        margin: 0 1px;
    }
    .table .btn-group {
        white-space: nowrap;
    }
    .badge {
        font-size: 0.85em;
        padding: 0.4em 0.6em;
    }
    .modal .form-group small {
        color: #6c757d;
        font-size: 0.875em;
        line-height: 1.4;
    }
    .badge:hover {
        transform: scale(1.05);
        transition: transform 0.2s ease;
    }
    .btn-warning:hover, .btn-success:hover {
        transform: translateY(-1px);
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .status-toggle-indicator {
        font-size: 0.8em;
        opacity: 0.7;
    }
    .table-responsive {
        border-radius: 16px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        background: white;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
    }
    .table {
        margin-bottom: 0;
        border-collapse: separate;
        border-spacing: 0;
    }
    .table td {
        border-top: none;
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.25rem 1rem;
        vertical-align: middle;
        background: white;
        transition: all 0.2s ease;
        font-size: 0.9rem;
        color: #2d3748;
    }
    .table th {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 1px;
        color: white;
        padding: 1.5rem 1rem;
        position: sticky;
        top: 0;
        z-index: 10;
        text-shadow: 0 1px 2px rgba(0,0,0,0.1);
    }
    .table th:first-child {
        border-top-left-radius: 16px;
    }
    .table th:last-child {
        border-top-right-radius: 16px;
    }
    
    /* Row selection styling */
    .user-row {
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
    }
    .user-row:hover {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%) !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .user-row:hover td {
        background: transparent;
    }
    .user-row.selected {
        background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%) !important;
        border-left: 4px solid #3182ce;
        transform: translateX(2px);
        box-shadow: 0 4px 15px rgba(49, 130, 206, 0.15);
    }
    .user-row.selected td {
        background: transparent;
    }
    
    /* Modern status badges */
    .badge {
        font-weight: 500;
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border: none;
    }
    .badge-success {
        background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
        color: white;
    }
    .badge-warning {
        background: linear-gradient(135deg, #ed8936 0%, #dd6b20 100%);
        color: white;
    }
    .badge-danger {
        background: linear-gradient(135deg, #f56565 0%, #e53e3e 100%);
        color: white;
    }
    .badge-info {
        background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
        color: white;
    }
    .badge-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }
    
    /* Bulk actions bar */
    #bulkActionsBar {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border: none;
        animation: slideDown 0.3s ease-out;
    }
    #bulkActionsBar .badge-primary {
        background-color: rgba(255,255,255,0.2);
        color: white;
        font-size: 1rem;
        padding: 0.5rem 0.75rem;
    }
    #bulkActionsBar .text-muted {
        color: rgba(255,255,255,0.8) !important;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    /* Modern checkbox styling */
    .custom-control-input:checked ~ .custom-control-label::before {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-color: #667eea;
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    .custom-control-label::before {
        border-radius: 6px;
        border: 2px solid #e2e8f0;
        transition: all 0.2s ease;
    }
    .custom-control-label::after {
        background-size: 60% 60%;
    }
    
    /* Modern dropdown menu */
    .dropdown-menu {
        border: none;
        box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        border-radius: 12px;
        padding: 0.5rem 0;
        background: white;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .dropdown-item {
        padding: 0.75rem 1.25rem;
        transition: all 0.2s ease;
        border-radius: 0;
        color: #4a5568;
        font-weight: 500;
    }
    .dropdown-item:hover {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        transform: translateX(5px);
        color: #2d3748;
    }
    .dropdown-item i {
        width: 18px;
        margin-right: 10px;
        text-align: center;
    }
    .dropdown-divider {
        margin: 0.5rem 0;
        border-top: 1px solid rgba(0,0,0,0.1);
    }
    
    /* Modern button styling */
    .btn {
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.2s ease;
        border: none;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.75rem;
    }
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    }
    .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 2px 4px rgba(102, 126, 234, 0.3);
    }
    .btn-outline-secondary {
        border: 2px solid #e2e8f0;
        color: #4a5568;
        background: white;
    }
    .btn-outline-secondary:hover {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        border-color: #cbd5e0;
        color: #2d3748;
    }
    
    /* Card improvements */
    .card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        background: white;
        overflow: hidden;
    }
    .card-header {
        background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
        border-bottom: 1px solid rgba(0,0,0,0.05);
        padding: 1.5rem;
    }
    .card-body {
        padding: 1.5rem;
    }
    
    /* Username styling */
    .table td strong {
        color: #2d3748;
        font-weight: 600;
    }
    
    /* Phone and email styling */
    .table td em.text-muted {
        color: #a0aec0;
        font-style: italic;
        font-size: 0.85rem;
    }
    
    /* Modern animations */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .user-row {
        animation: slideInUp 0.3s ease-out;
    }
    
    /* Modern Search Styling */
    .search-card {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        border: 1px solid rgba(255,255,255,0.2);
    }
    .search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }
    .search-icon {
        position: absolute;
        left: 1rem;
        color: #a0aec0;
        z-index: 5;
    }
    .modern-search-input {
        padding: 0.875rem 1rem 0.875rem 3rem;
        border: 2px solid #e2e8f0;
        border-radius: 12px;
        background: white;
        font-size: 0.9rem;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .modern-search-input:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        transform: translateY(-1px);
    }
    .clear-search-btn {
        position: absolute;
        right: 1rem;
        color: #a0aec0;
        text-decoration: none;
        z-index: 5;
        padding: 0.25rem;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    .clear-search-btn:hover {
        color: #e53e3e;
        background: rgba(229, 62, 62, 0.1);
        text-decoration: none;
    }
    .search-btn {
        padding: 0.875rem 1.5rem;
        border-radius: 12px;
        font-weight: 600;
    }
    .user-count-badge {
        background: rgba(255,255,255,0.8);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        font-size: 0.85rem;
        color: #4a5568;
        font-weight: 500;
        display: inline-block;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.3);
    }
    
    /* Page Header Styling */
    .content-wrapper h2 {
        color: #2d3748;
        font-weight: 700;
        margin-bottom: 0;
    }
    .content-wrapper h2 i {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }
    
    /* Scrollbar styling */
    .table-responsive::-webkit-scrollbar {
        height: 8px;
    }
    .table-responsive::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        border-radius: 4px;
    }
    .table-responsive::-webkit-scrollbar-thumb:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
    }
    
    /* Pagination improvements */
    .pagination {
        margin: 0;
    }
    .page-link {
        border: none;
        color: #4a5568;
        background: white;
        border-radius: 8px;
        margin: 0 2px;
        padding: 0.5rem 0.75rem;
        font-weight: 500;
        transition: all 0.2s ease;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .page-link:hover {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }
    .page-item.active .page-link {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        box-shadow: 0 4px 8px rgba(102, 126, 234, 0.3);
    }
    @media (max-width: 768px) {
        .table-responsive table,
        .table-responsive thead,
        .table-responsive tbody,
        .table-responsive th,
        .table-responsive td,
        .table-responsive tr {
            display: block;
        }
        .table-responsive thead tr {
            position: absolute;
            top: -9999px;
            left: -9999px;
        }
        .table-responsive tr {
            border: 1px solid #ccc;
            margin-bottom: 10px;
            padding: 10px;
            border-radius: 8px;
            background: white;
        }
        .table-responsive td {
            border: none;
            position: relative;
            padding-left: 50%;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .table-responsive td:before {
            content: attr(data-label) ": ";
            position: absolute;
            left: 6px;
            width: 45%;
            padding-right: 10px;
            white-space: nowrap;
            font-weight: bold;
            color: #5a5c69;
        }
    }
    </style>

    <script>
    // Global variables for selection management
    let selectedUsers = new Set();
    
    // Row selection functions
    function toggleSelectAll() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
            updateRowSelection(checkbox);
        });
        
        updateSelection();
    }
    
    function updateSelection() {
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const checkedBoxes = document.querySelectorAll('.user-checkbox:checked');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        // Update selectedUsers set
        selectedUsers.clear();
        checkedBoxes.forEach(checkbox => {
            selectedUsers.add({
                id: checkbox.dataset.userId,
                username: checkbox.dataset.username
            });
            updateRowSelection(checkbox);
        });
        
        // Update select all checkbox state
        if (checkedBoxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (checkedBoxes.length === userCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
            selectAllCheckbox.checked = false;
        }
        
        // Show/hide bulk actions bar
        const bulkActionsBar = document.getElementById('bulkActionsBar');
        const selectedCount = document.getElementById('selectedCount');
        
        if (selectedUsers.size > 0) {
            selectedCount.textContent = selectedUsers.size;
            bulkActionsBar.style.display = 'block';
        } else {
            bulkActionsBar.style.display = 'none';
        }
    }
    
    function updateRowSelection(checkbox) {
        const row = checkbox.closest('.user-row');
        if (checkbox.checked) {
            row.classList.add('selected');
        } else {
            row.classList.remove('selected');
        }
    }
    
    function clearSelection() {
        const userCheckboxes = document.querySelectorAll('.user-checkbox');
        const selectAllCheckbox = document.getElementById('selectAll');
        
        userCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
            updateRowSelection(checkbox);
        });
        
        selectAllCheckbox.checked = false;
        selectAllCheckbox.indeterminate = false;
        updateSelection();
    }
    
    // Bulk action functions
    function bulkEditUsers() {
        if (selectedUsers.size === 0) {
            alert('Please select users to edit.');
            return;
        }
        
        if (selectedUsers.size > 1) {
            alert('Please select only one user to edit.');
            return;
        }
        
        const userId = Array.from(selectedUsers)[0].id;
        editUser(userId);
    }
    
    function bulkSuspendUsers() {
        if (selectedUsers.size === 0) {
            alert('Please select users to pause.');
            return;
        }
        
        const usernames = Array.from(selectedUsers).map(u => u.username).join(', ');
        if (confirm(`Are you sure you want to PAUSE ${selectedUsers.size} user(s)?\n\nUsers: ${usernames}\n\nThey will be temporarily suspended but can be resumed anytime.`)) {
            performBulkAction('suspend', Array.from(selectedUsers).map(u => u.id));
        }
    }
    
    function bulkActivateUsers() {
        if (selectedUsers.size === 0) {
            alert('Please select users to activate.');
            return;
        }
        
        const usernames = Array.from(selectedUsers).map(u => u.username).join(', ');
        if (confirm(`Are you sure you want to ACTIVATE ${selectedUsers.size} user(s)?\n\nUsers: ${usernames}\n\nThey will be able to login immediately.`)) {
            performBulkAction('activate', Array.from(selectedUsers).map(u => u.id));
        }
    }
    
    function bulkResetPasswords() {
        if (selectedUsers.size === 0) {
            alert('Please select users to reset passwords for.');
            return;
        }
        
        const usernames = Array.from(selectedUsers).map(u => u.username).join(', ');
        if (confirm(`Are you sure you want to RESET PASSWORDS for ${selectedUsers.size} user(s)?\n\nUsers: ${usernames}\n\nThis will generate new random passwords for all selected users.`)) {
            performBulkAction('reset_passwords', Array.from(selectedUsers).map(u => u.id));
        }
    }
    
    function bulkDeleteUsers() {
        if (selectedUsers.size === 0) {
            alert('Please select users to delete.');
            return;
        }
        
        const usernames = Array.from(selectedUsers).map(u => u.username).join(', ');
        if (confirm(`⚠️ DANGER: Are you sure you want to DELETE ${selectedUsers.size} user(s)?\n\nUsers: ${usernames}\n\n🚨 THIS ACTION CANNOT BE UNDONE! 🚨\n\nType "DELETE" to confirm this permanent action.`)) {
            const confirmation = prompt('Type "DELETE" to confirm permanent deletion:');
            if (confirmation === 'DELETE') {
                performBulkAction('delete', Array.from(selectedUsers).map(u => u.id));
            }
        }
    }
    
    function performBulkAction(action, userIds) {
        // Create a form to submit the bulk action
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'bulk_' + action;
        
        const userIdsInput = document.createElement('input');
        userIdsInput.type = 'hidden';
        userIdsInput.name = 'user_ids';
        userIdsInput.value = JSON.stringify(userIds);
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
        
        form.appendChild(actionInput);
        form.appendChild(userIdsInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    // JavaScript functions for user management operations
    
    function editUser(userId) {
        // Fetch user data via AJAX
        fetch(`get_user_data.php?id=${userId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const user = data.user;
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_username').value = user.username;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_phone').value = user.phone || '';
                    document.getElementById('edit_role_id').value = user.role_id;
                    document.getElementById('edit_dept_id').value = user.dept_id;
                    document.getElementById('edit_status').value = user.status;
                    
                    $('#editUserModal').modal('show');
                } else {
                    alert('Error fetching user data: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error fetching user data');
            });
    }
    
    function resetPassword(userId) {
        document.getElementById('reset_user_id').value = userId;
        $('#resetPasswordModal').modal('show');
    }
    
    function deleteUser(userId, username) {
        if (confirm(`Are you sure you want to delete user "${username}"? This action cannot be undone.`)) {
            submitQuickAction('delete_user', userId);
        }
    }
    
    function quickSuspendUser(userId, username) {
        if (confirm(`🟡 PAUSE USER: "${username}"\n\n` +
                   `This will temporarily suspend their access.\n` +
                   `✅ Their account data will be preserved\n` +
                   `✅ You can resume their access anytime\n` +
                   `❌ They cannot login until resumed\n\n` +
                   `Continue with pause?`)) {
            submitQuickStatusChange(userId, 'suspended');
        }
    }
    
    function quickActivateUser(userId, username) {
        if (confirm(`🟢 RESUME/ACTIVATE USER: "${username}"\n\n` +
                   `This will restore their full system access.\n` +
                   `✅ They can login immediately\n` +
                   `✅ All previous data is preserved\n` +
                   `✅ You can pause them again if needed\n\n` +
                   `Continue with activation?`)) {
            submitQuickStatusChange(userId, 'active');
        }
    }
    
    function submitQuickAction(action, userId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = action;
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
        
        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    function submitQuickStatusChange(userId, newStatus) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'action';
        actionInput.value = 'quick_status_change';
        
        const userIdInput = document.createElement('input');
        userIdInput.type = 'hidden';
        userIdInput.name = 'user_id';
        userIdInput.value = userId;
        
        const statusInput = document.createElement('input');
        statusInput.type = 'hidden';
        statusInput.name = 'new_status';
        statusInput.value = newStatus;
        
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = 'csrf_token';
        csrfInput.value = '<?php echo $_SESSION['csrf_token']; ?>';
        
        form.appendChild(actionInput);
        form.appendChild(userIdInput);
        form.appendChild(statusInput);
        form.appendChild(csrfInput);
        document.body.appendChild(form);
        form.submit();
    }
    
    // Password confirmation validation
    document.addEventListener('DOMContentLoaded', function() {
        const resetForm = document.querySelector('#resetPasswordModal form');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                const newPassword = this.querySelector('input[name="new_password"]').value;
                const confirmPassword = this.querySelector('input[name="confirm_password"]').value;
                
                if (newPassword !== confirmPassword) {
                    e.preventDefault();
                    alert('Passwords do not match!');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    e.preventDefault();
                    alert('Password must be at least 6 characters long!');
                    return false;
                }
            });
        }
    });
    </script>

<?php include '../includes/admin/footer.php'; ?>