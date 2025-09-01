<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in (access denied page can be shown to logged in users)
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include 'db.php';

$page_title = 'Access Denied';

// Get current user info for display
$current_user = null;
if (isset($_SESSION['user_id'])) {
    $uid = intval($_SESSION['user_id']);
    $sql = "SELECT u.id, u.username, u.role_id, u.dept_id, u.phone, u.email, u.status, u.created_at,
            r.name as role_name,
            d.name as dept_name
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            LEFT JOIN departments d ON u.dept_id = d.id
            WHERE u.id = $uid AND u.status = 'active'
            LIMIT 1";
    $result = $con->query($sql);
    if ($result) {
        $current_user = $result->fetch_assoc();
    }
}

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Access Denied</h1>
    <p class="page-subtitle">You don't have permission to access this resource</p>
</div>

<!-- Access Denied Content -->
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-4">
                    <i class="fas fa-shield-alt text-danger" style="font-size: 4rem;"></i>
                </div>
                
                <h3 class="text-danger mb-3">Access Denied</h3>
                <p class="lead mb-4">
                    Sorry, you don't have the required permissions to access this page or resource.
                </p>
                
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> What you can do:</h6>
                    <ul class="text-left mb-0">
                        <li>Contact your system administrator to request access</li>
                        <li>Check if you're logged in with the correct account</li>
                        <li>Navigate to a page you have permission to access</li>
                    </ul>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-4">
                        <a href="home.php" class="btn btn-primary btn-block">
                            <i class="fas fa-home"></i> Go to Dashboard
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="help_center.php" class="btn btn-info btn-block">
                            <i class="fas fa-question-circle"></i> Get Help
                        </a>
                    </div>
                    <div class="col-md-4">
                        <a href="user_preferences.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-user-cog"></i> User Settings
                        </a>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <div class="row">
                    <div class="col-md-6">
                        <h6><i class="fas fa-user"></i> Current User Information</h6>
                        <p class="mb-1"><strong>Username:</strong> <?php echo htmlspecialchars($_SESSION['user'] ?? 'Unknown'); ?></p>
                        <p class="mb-1"><strong>Role:</strong> <?php 
                            // Try multiple ways to get the role
                            $role_display = $current_user['role_name'] ?? $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'Unknown';
                            echo htmlspecialchars($role_display);
                        ?></p>
                        <p class="mb-1"><strong>Debug - Session role:</strong> <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Not set'); ?></p>
                        <p class="mb-1"><strong>Debug - DB role:</strong> <?php echo htmlspecialchars($current_user['role_name'] ?? $current_user['user_role'] ?? 'Not found'); ?></p>
                        <p class="mb-1"><strong>Debug - Role ID:</strong> <?php echo htmlspecialchars($_SESSION['user_role_id'] ?? 'Not set'); ?></p>
                        <p class="mb-0"><strong>Department:</strong> <?php echo htmlspecialchars($current_user['dept_name'] ?? 'Not Assigned'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <h6><i class="fas fa-clock"></i> Session Information</h6>
                        <p class="mb-1"><strong>Login Time:</strong> <?php echo isset($_SESSION['login_time']) ? date('M d, Y H:i', $_SESSION['login_time']) : 'Unknown'; ?></p>
                        <p class="mb-1"><strong>Current Time:</strong> <?php echo date('M d, Y H:i'); ?></p>
                        <p class="mb-0"><strong>IP Address:</strong> <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Unknown'; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Navigation -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-compass"></i> Quick Navigation</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-tachometer-alt text-primary mb-2" style="font-size: 2rem;"></i>
                                <h6>Dashboard</h6>
                                <a href="home.php" class="btn btn-sm btn-primary">Go to Dashboard</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-users text-success mb-2" style="font-size: 2rem;"></i>
                                <h6>Guest Services</h6>
                                <a href="roombook.php" class="btn btn-sm btn-success">View Bookings</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line text-info mb-2" style="font-size: 2rem;"></i>
                                <h6>Reports</h6>
                                <a href="revenue_analytics.php" class="btn btn-sm btn-info">View Analytics</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 mb-3">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <i class="fas fa-cog text-warning mb-2" style="font-size: 2rem;"></i>
                                <h6>Settings</h6>
                                <a href="user_preferences.php" class="btn btn-sm btn-warning">User Settings</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card.bg-light {
    border: 1px solid #dee2e6;
    transition: transform 0.2s;
}

.card.bg-light:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
</style>

<?php include '../includes/admin/footer.php'; ?>
