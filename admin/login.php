<?php  
if (session_status() === PHP_SESSION_NONE) {
 session_start();  
}  

// Include hotel settings for dynamic content
require_once '../includes/common/hotel_settings.php';

if (isset($_SESSION["user"])) {  
      header("location:home.php");  
    exit();
}  

// Handle login processing
include('db.php');
  
$login_error = '';
$logout_message = '';
$available_roles = [];

// Check for logout success message
if (isset($_GET['logout']) && $_GET['logout'] === 'success') {
    $logout_message = 'You have been successfully logged out.';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $myusername = mysqli_real_escape_string($con, $_POST['user']);
    $mypassword = $_POST['pass']; 
    $selected_role_id = isset($_POST['role_id']) ? intval($_POST['role_id']) : null;

    // Get user with password verification
    $stmt = $con->prepare("SELECT id, username, password_hash, role_id FROM users WHERE username = ? AND status = 'active' LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $myusername);
        $stmt->execute();
        $res = $stmt->get_result();
        
        if ($res && $res->num_rows === 1) {
            $u = $res->fetch_assoc();
            
            if (password_verify($mypassword, $u['password_hash'])) {
                // Check if user has access to selected role (Admin can access any role)
                $user_role_id = $u['role_id'];
                $final_role_id = $selected_role_id;
                
                // Admin (role_id = 1) can switch to any role, others use their assigned role
                if ($user_role_id != 1 && $selected_role_id != $user_role_id) {
                    $login_error = 'You do not have permission to access the selected role.';
                } else {
                    // If no role selected or non-admin user, use their default role
                    if (!$selected_role_id || $user_role_id != 1) {
                        $final_role_id = $user_role_id;
                    }
                    
                    // Set session variables
                    $_SESSION['user_id'] = $u['id'];
                    $_SESSION['user'] = $u['username'];
                    $_SESSION['user_role_id'] = $final_role_id;
                    $_SESSION['original_role_id'] = $user_role_id; // Store original role
                    $_SESSION['login_time'] = time();
                    
                    // Get role name for dashboard routing
                    $role_stmt = $con->prepare("SELECT name FROM roles WHERE id = ?");
                    $role_stmt->bind_param('i', $final_role_id);
                    $role_stmt->execute();
                    $role_result = $role_stmt->get_result();
                    $role_data = $role_result->fetch_assoc();
                    $role_name = $role_data['name'];
                    
                    // Store role name in session for easy access
                    $_SESSION['user_role'] = $role_name;
                    
                    // Route to appropriate dashboard based on role
                    $dashboard_url = getDashboardUrl($role_name, $final_role_id);
                    header("location: $dashboard_url");
                    exit();
                }
            } else {
                $login_error = 'Invalid username or password';
            }
        } else {
            $login_error = 'Invalid username or password';
        }
    } else {
        $login_error = 'Database connection error';
    }
}

// Get available roles for dropdown - organized by hierarchy and function
$roles_query = "
    SELECT id, name, description 
    FROM roles 
    ORDER BY 
        CASE 
            WHEN name = 'Director' THEN 1
            WHEN name = 'Operations_Manager' THEN 2
            WHEN name = 'Finance' THEN 3
            WHEN name = 'Finance_Controller' THEN 4
            WHEN name = 'Finance_Officer' THEN 5
            WHEN name = 'IT_Admin' THEN 6
            WHEN name = 'DeptManager' THEN 7
            WHEN name = 'HR' THEN 8
            WHEN name = 'SalesMarketing' THEN 9
            WHEN name = 'Staff' THEN 10
            ELSE 99
        END, name
";
$roles_result = mysqli_query($con, $roles_query);

// Organize roles by category for better UX
$role_categories = [
    'Executive' => [],
    'Management' => [],
    'Operations' => [],
    'Support' => []
];

if ($roles_result) {
    while ($role = mysqli_fetch_assoc($roles_result)) {
        // Categorize roles for better organization
        switch ($role['name']) {
            case 'Director':
                $role['display_name'] = '👑 Director';
                $role_categories['Executive'][] = $role;
                break;
            case 'Operations_Manager':
                $role['display_name'] = '⚙️ Operations Manager';
                $role_categories['Management'][] = $role;
                break;
            case 'Finance':
                $role['display_name'] = '💰 Finance Manager';
                $role_categories['Management'][] = $role;
                break;
            case 'Finance_Controller':
                $role['display_name'] = '📊 Finance Controller';
                $role_categories['Management'][] = $role;
                break;
            case 'Finance_Officer':
                $role['display_name'] = '💳 Finance Officer';
                $role_categories['Operations'][] = $role;
                break;
            case 'IT_Admin':
                $role['display_name'] = '🖥️ IT Administrator';
                $role_categories['Support'][] = $role;
                break;
            case 'DeptManager':
                $role['display_name'] = '🏢 Department Manager';
                $role_categories['Management'][] = $role;
                break;
            case 'HR':
                $role['display_name'] = '👥 Human Resources';
                $role_categories['Support'][] = $role;
                break;
            case 'SalesMarketing':
                $role['display_name'] = '📈 Sales & Marketing';
                $role_categories['Operations'][] = $role;
                break;
            case 'Staff':
                $role['display_name'] = '👨‍💼 Staff Member';
                $role_categories['Operations'][] = $role;
                break;
            default:
                $role['display_name'] = $role['name'];
                $role_categories['Support'][] = $role;
        }
        $available_roles[] = $role;
    }
}

/**
 * Get dashboard URL based on role
 */
function getDashboardUrl($role_name, $role_id) {
    $dashboards = [
        'Admin' => 'home.php',                              // Executive Dashboard (Admin/Director access)
        'Staff' => 'staff_dashboard.php',                   // Staff Dashboard
        'DeptManager' => 'management_dashboard.php',        // Management Dashboard
        'Finance' => 'finance_dashboard.php',               // Finance Dashboard
        'Finance_Officer' => 'finance_dashboard.php',       // Finance Dashboard
        'Finance_Controller' => 'accounting_dashboard.php', // Accounting Dashboard
        'HR' => 'management_dashboard.php',                 // Management Dashboard
        'SalesMarketing' => 'management_dashboard.php',     // Management Dashboard
        'IT_Admin' => 'it_admin_dashboard.php',            // IT Admin Dashboard
        'Operations_Manager' => 'operations_manager_dashboard.php', // Operations Manager Dashboard
        'Director' => 'director_dashboard.php'              // Director Dashboard
    ];
    
    return $dashboards[$role_name] ?? 'home.php';
}
 ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?php echo get_hotel_info('name'); ?></title>
    <link rel="icon" type="image/png" href="/Hotel/images/logo-full.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 450px;
            padding: 40px;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transform: rotate(45deg);
            pointer-events: none;
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }

        .logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }

        .logo i {
            font-size: 2rem;
            color: white;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 8px rgba(255, 215, 0, 0.4));
        }

        .login-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 8px;
        }

        .login-subtitle {
            color: #718096;
            font-weight: 400;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #4a5568;
            font-size: 0.9rem;
        }

        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #f7fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .input-icon {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            font-size: 1.1rem;
        }

        .form-control.with-icon {
            padding-left: 55px;
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
            background-position: right 12px center;
            background-repeat: no-repeat;
            background-size: 16px 12px;
            padding-right: 40px;
        }

        select.form-control:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%23667eea' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
        }

        select.with-icon {
            padding-left: 55px;
            padding-right: 45px;
        }

        optgroup {
            font-weight: 600;
            font-size: 0.9rem;
            color: #4a5568;
            background: #f7fafc;
            padding: 8px;
        }

        option {
            font-size: 0.9rem;
            padding: 8px 12px;
            color: #2d3748;
        }

        option:hover {
            background: #e2e8f0;
        }

        .form-text {
            font-size: 0.8rem;
            color: #6c757d;
            margin-top: 8px;
        }

        .login-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }

        .login-btn:active {
            transform: translateY(0);
        }

        .error-message {
            background: #fed7d7;
            color: #c53030;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #e53e3e;
        }

        .success-message {
            background: #c6f6d5;
            color: #22543d;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            border-left: 4px solid #38a169;
        }

        .forgot-password {
            text-align: center;
            margin-top: 20px;
        }

        .forgot-password a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .forgot-password a:hover {
            color: #764ba2;
        }

        .features-note {
            background: #e6fffa;
            border: 1px solid #81e6d9;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-size: 0.85rem;
            color: #234e52;
        }

        .features-note h5 {
            margin-bottom: 8px;
            color: #234e52;
        }

        .features-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .features-list li {
            margin-bottom: 4px;
            position: relative;
            padding-left: 20px;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #38a169;
            font-weight: bold;
        }

        /* Selected Role Display Styles */
        .selected-role-display {
            background: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .selected-role-display:hover {
            border-color: #667eea;
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
        }

        .role-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }

        .role-icon-small {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }

        .role-icon-small i {
            font-size: 1.5rem;
            color: white;
        }

        .role-details {
            flex: 1;
        }

        .role-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 4px;
        }

        .role-description {
            font-size: 0.9rem;
            color: #718096;
        }

        .change-role-btn {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .change-role-btn:hover {
            background: #cbd5e0;
            color: #2d3748;
            text-decoration: none;
            transform: translateY(-1px);
        }

        /* Error state for no role selected */
        .no-role-selected .selected-role-display {
            border-color: #fed7d7;
            background: #fef5e7;
        }

        .no-role-selected .role-name {
            color: #c53030;
        }

        .no-role-selected .role-description {
            color: #dd6b20;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 10px;
            }
            
            .login-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <div class="logo">
                <img src="/Hotel/images/logo-full.png" alt="<?php echo get_hotel_info('name'); ?>" class="logo-img">
            </div>
            <h1 class="login-title"><?php echo htmlspecialchars(get_hotel_info('name')); ?></h1>
            <p class="login-subtitle">Admin Portal Access</p>
        </div>

        <?php if ($logout_message): ?>
        <div class="success-message">
            <i class="fas fa-check-circle"></i>
            <?php echo htmlspecialchars($logout_message); ?>
        </div>
        <?php endif; ?>

        <?php if ($login_error): ?>
        <div class="error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <?php echo htmlspecialchars($login_error); ?>
        </div>
        <?php endif; ?>

        <form method="post" action="" id="loginForm">
            <div class="form-group">
                <label class="form-label" for="username">Username</label>
                <div style="position: relative;">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="username" name="user" class="form-control with-icon" placeholder="Enter your username" required autocomplete="username">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div style="position: relative;">
                    <i class="fas fa-lock input-icon"></i>
                    <input type="password" id="password" name="pass" class="form-control with-icon" placeholder="Enter your password" required autocomplete="current-password">
                </div>
            </div>

            <!-- Selected Role Display (No Dropdown) -->
            <div class="form-group" id="role-display-group">
                <label class="form-label"><i class="fas fa-user-shield"></i> Selected Access Role</label>
                <div class="selected-role-display" id="selected-role-display">
                    <div class="role-info">
                        <div class="role-icon-small">
                            <i class="fas fa-user-shield"></i>
                        </div>
                        <div class="role-details">
                            <div class="role-name" id="display-role-name">Please select a role from the main page</div>
                            <div class="role-description" id="display-role-description">Go back to choose your access level</div>
                        </div>
                    </div>
                    <a href="index.php" class="change-role-btn">
                        <i class="fas fa-exchange-alt"></i>
                        Change Role
                    </a>
                </div>
                <input type="hidden" name="role_id" id="hidden_role_id" required>
                <small class="form-text text-muted mt-2">
                    <i class="fas fa-info-circle"></i> 
                    Role was selected from the previous page. Use "Change Role" to select a different role.
                </small>
            </div>

            <button type="submit" class="login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Access Admin Portal
            </button>
        </form>

        <div class="forgot-password">
            <a href="#" onclick="showPasswordReset()">
                <i class="fas fa-key"></i>
                Forgot your password?
            </a>
        </div>

        <div class="features-note">
            <h5><i class="fas fa-star"></i> What's New in Your System:</h5>
            <ul class="features-list">
                <li>Role-based dashboard access</li>
                <li>Real-time analytics & reporting</li>
                <li>Mobile-responsive design</li>
                <li>Advanced security features</li>
                <li>Performance monitoring</li>
            </ul>
        </div>
    </div>

    <script>
        // Form initialization and validation
        document.addEventListener('DOMContentLoaded', function() {
            // Username field focus on load
            document.getElementById('username').focus();
            
            // Handle pre-selected role from URL
            const urlParams = new URLSearchParams(window.location.search);
            const preSelectedRole = urlParams.get('role');
            const preSelectedRoleName = urlParams.get('role_name');
            
            if (preSelectedRole && preSelectedRoleName) {
                // Set the hidden input value
                document.getElementById('hidden_role_id').value = preSelectedRole;
                
                // Update the display
                updateRoleDisplay(preSelectedRoleName, preSelectedRole);
                
                // Show notification about pre-selected role
                showPreSelectionNotification(preSelectedRoleName);
                
                // Remove no-role-selected class if present
                document.getElementById('role-display-group').classList.remove('no-role-selected');
            } else {
                // No role selected - show error state
                document.getElementById('role-display-group').classList.add('no-role-selected');
                document.getElementById('display-role-name').textContent = 'No role selected';
                document.getElementById('display-role-description').textContent = 'Please go back and select your access role';
            }
        });

        function showPasswordReset() {
            alert('Password reset functionality:\n\n1. Contact your system administrator\n2. Or use the "Admin" role with password "admin123" for testing\n\nFor production, implement proper password reset flow.');
        }

        function updateRoleDisplay(roleName, roleId) {
            // Define role details with icons and descriptions
            const roleDetails = {
                'Admin': { icon: 'fas fa-crown', description: 'System Administrator - Full access to all features' },
                'Director': { icon: 'fas fa-chess-king', description: 'Executive Director - Strategic oversight and management' },
                'Operations_Manager': { icon: 'fas fa-cogs', description: 'Operations Manager - Daily operations and staff coordination' },
                'Finance': { icon: 'fas fa-chart-line', description: 'Finance Manager - Financial planning and analysis' },
                'Finance_Controller': { icon: 'fas fa-calculator', description: 'Finance Controller - Transaction approval and controls' },
                'Finance_Officer': { icon: 'fas fa-coins', description: 'Finance Officer - Transaction processing and invoicing' },
                'IT_Admin': { icon: 'fas fa-server', description: 'IT Administrator - Technical infrastructure management' },
                'DeptManager': { icon: 'fas fa-users-cog', description: 'Department Manager - Team leadership and coordination' },
                'HR': { icon: 'fas fa-user-friends', description: 'Human Resources - Employee relations and management' },
                'SalesMarketing': { icon: 'fas fa-bullhorn', description: 'Sales & Marketing - Customer relations and growth' },
                'Staff': { icon: 'fas fa-user', description: 'General Staff - Essential operational access' }
            };

            const details = roleDetails[roleName] || { icon: 'fas fa-user-shield', description: 'Access role' };
            
            // Update the display elements
            document.getElementById('display-role-name').textContent = roleName.replace('_', ' ');
            document.getElementById('display-role-description').textContent = details.description;
            
            // Update the icon
            const iconElement = document.querySelector('.role-icon-small i');
            if (iconElement) {
                iconElement.className = details.icon;
            }
        }

        function showPreSelectionNotification(roleName) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: rgba(102, 126, 234, 0.95);
                color: white;
                padding: 15px 25px;
                border-radius: 10px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                font-weight: 500;
                backdrop-filter: blur(10px);
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;
            notification.innerHTML = `
                <i class="fas fa-info-circle"></i>
                ${roleName} role pre-selected
            `;
            
            document.body.appendChild(notification);
            
            // Add slide-in animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);
            
            // Remove notification after 4 seconds
            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 4000);
        }

        // Form validation and enhancement
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            const selectedRole = document.getElementById('hidden_role_id').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('Please enter both username and password.');
                return;
            }
            
            if (!selectedRole) {
                e.preventDefault();
                alert('Please select an access role from the main page first.');
                // Redirect to index page if no role selected
                window.location.href = 'index.php';
                return;
            }
            
            // Show loading state
            const submitBtn = this.querySelector('.login-btn');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Authenticating...';
            submitBtn.disabled = true;
            
            // Re-enable if form submission fails (will be reset on page reload anyway)
            setTimeout(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }, 5000);
        });

        // Add some visual polish
        document.querySelectorAll('.form-control').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.02)';
            });
            
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>
