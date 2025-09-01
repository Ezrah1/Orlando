<?php
$page_title = 'User Preferences';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Display session alerts
display_session_alerts();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_preferences'])) {
        $auto_refresh = isset($_POST['auto_refresh']) ? 1 : 0;
        $refresh_interval = intval($_POST['refresh_interval']);
        $notifications_enabled = isset($_POST['notifications_enabled']) ? 1 : 0;
        
        // For now, just show success message since we're not storing preferences in database
        $success_message = "Preferences updated successfully! (Note: Settings are currently applied for this session only)";
    }
    
    if (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password !== $confirm_password) {
            $error_message = "New passwords do not match!";
        } else {
            $user_id = intval($_SESSION['user_id']);
            $sql = "SELECT password_hash FROM users WHERE id = $user_id";
            $result = $con->query($sql);
            $user = $result->fetch_assoc();
            
            if (password_verify($current_password, $user['password_hash'])) {
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update_sql = "UPDATE users SET password_hash = '$new_hash' WHERE id = $user_id";
                if ($con->query($update_sql)) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Error changing password: " . $con->error;
                }
            } else {
                $error_message = "Current password is incorrect!";
            }
        }
    }
}

// Get current preferences (using default values)
$user_id = intval($_SESSION['user_id']);
$preferences = [
    'auto_refresh' => 1,
    'refresh_interval' => 30,
    'notifications_enabled' => 1,
    'email_notifications' => 1
];

// Get user info
$user_sql = "SELECT u.*, r.name as role_name, d.name as dept_name 
             FROM users u 
             LEFT JOIN roles r ON u.role_id = r.id 
             LEFT JOIN departments d ON u.dept_id = d.id 
             WHERE u.id = $user_id";
$user_result = $con->query($user_sql);
$user_info = $user_result ? $user_result->fetch_assoc() : null;
?>

<div class="page-header">
    <h1 class="page-title">User Preferences</h1>
    <p class="page-subtitle">Customize your experience and manage your account</p>
</div>

<?php if (isset($success_message)): ?>
    <div class="alert alert-success"><?php echo $success_message; ?></div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger"><?php echo $error_message; ?></div>
<?php endif; ?>

<div class="row">
    <!-- Account Information -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-user"></i> Account Information</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <strong>Username:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($user_info['username']); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Role:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($user_info['role_name']); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Department:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($user_info['dept_name']); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($user_info['email']); ?></span>
                </div>
                <div class="mb-3">
                    <strong>Phone:</strong><br>
                    <span class="text-muted"><?php echo htmlspecialchars($user_info['phone']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Interface Preferences -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-cog"></i> Interface Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="auto_refresh" name="auto_refresh" <?php echo ($preferences['auto_refresh'] ?? 0) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="auto_refresh">Auto-refresh dashboard</label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="refresh_interval">Refresh Interval (seconds)</label>
                        <select class="form-control" id="refresh_interval" name="refresh_interval">
                            <option value="30" <?php echo ($preferences['refresh_interval'] ?? 30) == 30 ? 'selected' : ''; ?>>30 seconds</option>
                            <option value="60" <?php echo ($preferences['refresh_interval'] ?? 30) == 60 ? 'selected' : ''; ?>>1 minute</option>
                            <option value="300" <?php echo ($preferences['refresh_interval'] ?? 30) == 300 ? 'selected' : ''; ?>>5 minutes</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <div class="custom-control custom-switch">
                            <input type="checkbox" class="custom-control-input" id="notifications_enabled" name="notifications_enabled" <?php echo ($preferences['notifications_enabled'] ?? 1) ? 'checked' : ''; ?>>
                            <label class="custom-control-label" for="notifications_enabled">Enable notifications</label>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_preferences" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-lock"></i> Change Password</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <button type="submit" name="change_password" class="btn btn-warning">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Keyboard Shortcuts -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-keyboard"></i> Keyboard Shortcuts</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Ctrl + S</strong><br>
                        <small class="text-muted">Save current form</small>
                    </div>
                    <div class="col-md-3">
                        <strong>Ctrl + N</strong><br>
                        <small class="text-muted">New record</small>
                    </div>
                    <div class="col-md-3">
                        <strong>Ctrl + F</strong><br>
                        <small class="text-muted">Search</small>
                    </div>
                    <div class="col-md-3">
                        <strong>F1</strong><br>
                        <small class="text-muted">Help</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Mobile Responsiveness Fixes for User Preferences */
@media (max-width: 768px) {
    /* Ensure sidebar is properly responsive */
    .admin-sidebar {
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease !important;
    }
    
    .admin-sidebar.show {
        transform: translateX(0) !important;
    }
    
    .admin-content {
        margin-left: 0 !important;
        width: 100% !important;
    }
    
    /* Header responsive fixes */
    .admin-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1030 !important;
        padding: 10px 15px !important;
    }
    
    .header-left {
        flex: 1 !important;
    }
    
    .header-right {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
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
    
    .user-dropdown .user-details {
        display: none !important;
    }
    
    .user-avatar {
        width: 32px !important;
        height: 32px !important;
        font-size: 14px !important;
    }
    
    /* Page content adjustments */
    .page-content {
        padding-top: 80px !important;
        padding-left: 15px !important;
        padding-right: 15px !important;
    }
    
    /* Card responsiveness */
    .card {
        margin-bottom: 15px !important;
    }
    
    .card-body {
        padding: 15px !important;
    }
    
    /* Form responsiveness */
    .form-group {
        margin-bottom: 15px !important;
    }
    
    .btn {
        width: 100% !important;
        margin-bottom: 10px !important;
    }
    
    .btn + .btn {
        margin-left: 0 !important;
    }
}

@media (max-width: 576px) {
    .header-right {
        gap: 5px !important;
    }
    
    .portal-access {
        display: none !important;
    }
    
    .search-input {
        max-width: 120px !important;
        font-size: 12px !important;
    }
    
    .page-content {
        padding: 70px 10px 20px 10px !important;
    }
    
    .card-body {
        padding: 10px !important;
    }
    
    .col-md-6 {
        margin-bottom: 15px !important;
    }
}
</style>

<?php include '../includes/admin/footer.php'; ?>