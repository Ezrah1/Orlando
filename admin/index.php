<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If user is already logged in, redirect to their dashboard
if (isset($_SESSION["user"])) {
    header("location:home.php");
    exit();
}

// Include database connection
include('db.php');

// Get all roles with extended descriptions for the role selection interface
$roles_query = "
    SELECT id, name, description 
    FROM roles 
    ORDER BY 
        CASE 
            WHEN name = 'Admin' THEN 1
            WHEN name = 'Director' THEN 2
            WHEN name = 'Operations_Manager' THEN 3
            WHEN name = 'Finance' THEN 4
            WHEN name = 'Finance_Controller' THEN 5
            WHEN name = 'Finance_Officer' THEN 6
            WHEN name = 'IT_Admin' THEN 7
            WHEN name = 'DeptManager' THEN 8
            WHEN name = 'HR' THEN 9
            WHEN name = 'SalesMarketing' THEN 10
            WHEN name = 'Staff' THEN 11
            ELSE 99
        END, name
";
$roles_result = mysqli_query($con, $roles_query);

// Define detailed role information with icons, summaries, and features
$role_details = [
    'Admin' => [
        'icon' => 'fas fa-crown',
        'display_name' => 'System Administrator',
        'category' => 'Executive',
        'color' => 'linear-gradient(135deg, #667eea, #764ba2)',
        'summary' => 'Complete system control with access to all features and administrative functions.',
        'key_features' => [
            'Full system access and control',
            'User management and permissions',
            'System configuration and settings',
            'Database management and backups',
            'Security and audit controls'
        ]
    ],
    'Director' => [
        'icon' => 'fas fa-chess-king',
        'display_name' => 'Executive Director',
        'category' => 'Executive',
        'color' => 'linear-gradient(135deg, #f093fb, #f5576c)',
        'summary' => 'Executive oversight with comprehensive access to all operational and financial data.',
        'key_features' => [
            'Executive dashboard and analytics',
            'Financial performance monitoring',
            'Strategic decision support',
            'All department access',
            'High-level reporting and insights'
        ]
    ],
    'Operations_Manager' => [
        'icon' => 'fas fa-cogs',
        'display_name' => 'Operations Manager',
        'category' => 'Management',
        'color' => 'linear-gradient(135deg, #4facfe, #00f2fe)',
        'summary' => 'Manage daily operations, staff coordination, and operational efficiency.',
        'key_features' => [
            'Daily operations monitoring',
            'Staff scheduling and management',
            'Inventory and supply management',
            'Quality control systems',
            'Operational reporting'
        ]
    ],
    'Finance' => [
        'icon' => 'fas fa-chart-line',
        'display_name' => 'Finance Manager',
        'category' => 'Management',
        'color' => 'linear-gradient(135deg, #43e97b, #38f9d7)',
        'summary' => 'Oversee financial operations, budgets, and strategic financial planning.',
        'key_features' => [
            'Financial planning and analysis',
            'Budget management and control',
            'Revenue and cost analysis',
            'Financial reporting and KPIs',
            'Investment and growth planning'
        ]
    ],
    'Finance_Controller' => [
        'icon' => 'fas fa-calculator',
        'display_name' => 'Finance Controller',
        'category' => 'Management',
        'color' => 'linear-gradient(135deg, #fa709a, #fee140)',
        'summary' => 'Control financial transactions, approve expenses, and maintain accounting integrity.',
        'key_features' => [
            'Transaction approval workflows',
            'Account reconciliation',
            'Expense management and control',
            'Compliance and audit support',
            'Financial controls and procedures'
        ]
    ],
    'Finance_Officer' => [
        'icon' => 'fas fa-coins',
        'display_name' => 'Finance Officer',
        'category' => 'Operations',
        'color' => 'linear-gradient(135deg, #a8edea, #fed6e3)',
        'summary' => 'Process transactions, handle invoicing, and manage day-to-day financial operations.',
        'key_features' => [
            'Transaction processing',
            'Invoice generation and management',
            'Payroll processing',
            'Daily financial operations',
            'Payment processing and tracking'
        ]
    ],
    'IT_Admin' => [
        'icon' => 'fas fa-server',
        'display_name' => 'IT Administrator',
        'category' => 'Support',
        'color' => 'linear-gradient(135deg, #667eea, #764ba2)',
        'summary' => 'Manage technical infrastructure, system security, and IT support services.',
        'key_features' => [
            'System administration and maintenance',
            'Security management and monitoring',
            'User support and troubleshooting',
            'Backup and recovery systems',
            'Technology infrastructure management'
        ]
    ],
    'DeptManager' => [
        'icon' => 'fas fa-users-cog',
        'display_name' => 'Department Manager',
        'category' => 'Management',
        'color' => 'linear-gradient(135deg, #ffecd2, #fcb69f)',
        'summary' => 'Lead department teams, manage workflows, and ensure departmental objectives.',
        'key_features' => [
            'Team leadership and development',
            'Department goal setting',
            'Performance management',
            'Resource allocation',
            'Interdepartmental coordination'
        ]
    ],
    'HR' => [
        'icon' => 'fas fa-user-friends',
        'display_name' => 'Human Resources',
        'category' => 'Support',
        'color' => 'linear-gradient(135deg, #d299c2, #fef9d7)',
        'summary' => 'Manage employee relations, recruitment, training, and HR policies.',
        'key_features' => [
            'Employee recruitment and onboarding',
            'Performance evaluation systems',
            'Training and development programs',
            'HR policy management',
            'Employee relations and support'
        ]
    ],
    'SalesMarketing' => [
        'icon' => 'fas fa-bullhorn',
        'display_name' => 'Sales & Marketing',
        'category' => 'Operations',
        'color' => 'linear-gradient(135deg, #89f7fe, #66a6ff)',
        'summary' => 'Drive sales growth, manage marketing campaigns, and customer relationship management.',
        'key_features' => [
            'Sales pipeline management',
            'Marketing campaign execution',
            'Customer relationship management',
            'Lead generation and conversion',
            'Market analysis and strategy'
        ]
    ],
    'Staff' => [
        'icon' => 'fas fa-user',
        'display_name' => 'General Staff',
        'category' => 'Operations',
        'color' => 'linear-gradient(135deg, #a8edea, #fed6e3)',
        'summary' => 'Access essential tools and features for daily work responsibilities.',
        'key_features' => [
            'Task management and tracking',
            'Basic reporting and analytics',
            'Communication tools',
            'Schedule and calendar access',
            'Essential operational features'
        ]
    ]
];

// Organize roles by category
$role_categories = [
    'Executive' => [],
    'Management' => [],
    'Operations' => [],
    'Support' => []
];

if ($roles_result) {
    while ($role = mysqli_fetch_assoc($roles_result)) {
        if (isset($role_details[$role['name']])) {
            $role_info = $role_details[$role['name']];
            $role['detailed_info'] = $role_info;
            $role_categories[$role_info['category']][] = $role;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Portal - Role Selection | Orlando International Resorts</title>
    <link rel="icon" type="image/png" href="/Hotel/images/logo-full.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
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
            position: relative;
            overflow-x: hidden;
        }

        /* Animated background particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .container {
            position: relative;
            z-index: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 60px;
            color: white;
        }

        .logo {
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 30px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            animation: pulse 2s ease-in-out infinite alternate;
        }

        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4); }
            100% { transform: scale(1.05); box-shadow: 0 0 20px 10px rgba(255, 255, 255, 0.2); }
        }

        .logo i {
            font-size: 3rem;
            color: white;
        }

        .logo-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            filter: drop-shadow(0 0 10px rgba(255, 215, 0, 0.3));
        }

        .main-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 15px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .main-subtitle {
            font-size: 1.2rem;
            font-weight: 300;
            opacity: 0.9;
            margin-bottom: 10px;
        }

        .main-description {
            font-size: 1rem;
            font-weight: 400;
            opacity: 0.8;
            max-width: 600px;
            margin: 0 auto;
        }

        .category-section {
            margin-bottom: 50px;
        }

        .category-header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .category-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: white;
            margin-bottom: 10px;
        }

        .category-description {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
        }

        .roles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .role-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .role-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: var(--role-color);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }

        .role-card:hover::before {
            transform: scaleX(1);
        }

        .role-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        }

        .role-card:active {
            transform: translateY(-5px);
        }

        .role-icon {
            width: 70px;
            height: 70px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            background: var(--role-color);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .role-icon i {
            font-size: 2rem;
            color: white;
        }

        .role-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .role-summary {
            color: #4a5568;
            font-size: 0.95rem;
            margin-bottom: 20px;
            line-height: 1.6;
        }

        .role-features {
            margin-bottom: 20px;
        }

        .role-features h4 {
            font-size: 0.9rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 10px;
        }

        .features-list {
            list-style: none;
            padding: 0;
        }

        .features-list li {
            font-size: 0.85rem;
            color: #718096;
            margin-bottom: 5px;
            position: relative;
            padding-left: 20px;
        }

        .features-list li::before {
            content: '✓';
            position: absolute;
            left: 0;
            color: #38a169;
            font-weight: 600;
        }

        .role-action {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
        }

        .access-btn {
            background: var(--role-color);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .access-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .role-badge {
            background: rgba(102, 126, 234, 0.1);
            color: #667eea;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .login-info {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            margin-top: 50px;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-info h3 {
            color: white;
            font-size: 1.5rem;
            margin-bottom: 15px;
        }

        .login-info p {
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 20px;
        }

        .direct-login-btn {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.3);
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .direct-login-btn:hover {
            background: rgba(255, 255, 255, 0.3);
            border-color: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }

        @media (max-width: 768px) {
            .roles-grid {
                grid-template-columns: 1fr;
            }
            
            .main-title {
                font-size: 2rem;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .role-card {
                padding: 20px;
            }
        }

        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .loading .access-btn {
            background: #a0aec0 !important;
        }

        .loading .access-btn::after {
            content: '';
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Animated background particles -->
    <div class="particles"></div>

    <div class="container">
        <div class="header animate__animated animate__fadeInDown">
            <div class="logo">
                <img src="/Hotel/images/logo-full.png" alt="Orlando International Resorts" class="logo-img">
            </div>
            <h1 class="main-title">Orlando International Resorts</h1>
            <p class="main-subtitle">Administrative Portal</p>
            <p class="main-description">
                Select your role to access the appropriate dashboard and management tools. 
                Each role provides access to specific features designed for your responsibilities.
            </p>
        </div>

        <!-- Executive Roles -->
        <?php if (!empty($role_categories['Executive'])): ?>
        <div class="category-section animate__animated animate__fadeInUp" data-category="Executive">
            <div class="category-header">
                <h2 class="category-title">🏆 Executive Leadership</h2>
                <p class="category-description">Strategic oversight and organizational management</p>
            </div>
            <div class="roles-grid">
                <?php foreach ($role_categories['Executive'] as $role): ?>
                <div class="role-card" 
                     data-role-id="<?php echo $role['id']; ?>" 
                     data-role-name="<?php echo htmlspecialchars($role['name']); ?>"
                     style="--role-color: <?php echo $role['detailed_info']['color']; ?>">
                    <div class="role-icon">
                        <i class="<?php echo $role['detailed_info']['icon']; ?>"></i>
                    </div>
                    <h3 class="role-title"><?php echo htmlspecialchars($role['detailed_info']['display_name']); ?></h3>
                    <p class="role-summary"><?php echo htmlspecialchars($role['detailed_info']['summary']); ?></p>
                    <div class="role-features">
                        <h4>Key Responsibilities:</h4>
                        <ul class="features-list">
                            <?php foreach ($role['detailed_info']['key_features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="role-action">
                        <span class="role-badge"><?php echo htmlspecialchars($role['detailed_info']['category']); ?> Level</span>
                        <button class="access-btn" onclick="accessRole('<?php echo $role['id']; ?>', '<?php echo htmlspecialchars($role['name']); ?>')">
                            <i class="fas fa-sign-in-alt"></i>
                            Access Portal
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Management Roles -->
        <?php if (!empty($role_categories['Management'])): ?>
        <div class="category-section animate__animated animate__fadeInUp" data-category="Management">
            <div class="category-header">
                <h2 class="category-title">👔 Management Team</h2>
                <p class="category-description">Departmental leadership and operational management</p>
            </div>
            <div class="roles-grid">
                <?php foreach ($role_categories['Management'] as $role): ?>
                <div class="role-card" 
                     data-role-id="<?php echo $role['id']; ?>" 
                     data-role-name="<?php echo htmlspecialchars($role['name']); ?>"
                     style="--role-color: <?php echo $role['detailed_info']['color']; ?>">
                    <div class="role-icon">
                        <i class="<?php echo $role['detailed_info']['icon']; ?>"></i>
                    </div>
                    <h3 class="role-title"><?php echo htmlspecialchars($role['detailed_info']['display_name']); ?></h3>
                    <p class="role-summary"><?php echo htmlspecialchars($role['detailed_info']['summary']); ?></p>
                    <div class="role-features">
                        <h4>Key Responsibilities:</h4>
                        <ul class="features-list">
                            <?php foreach ($role['detailed_info']['key_features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="role-action">
                        <span class="role-badge"><?php echo htmlspecialchars($role['detailed_info']['category']); ?> Level</span>
                        <button class="access-btn" onclick="accessRole('<?php echo $role['id']; ?>', '<?php echo htmlspecialchars($role['name']); ?>')">
                            <i class="fas fa-sign-in-alt"></i>
                            Access Portal
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Operations Roles -->
        <?php if (!empty($role_categories['Operations'])): ?>
        <div class="category-section animate__animated animate__fadeInUp" data-category="Operations">
            <div class="category-header">
                <h2 class="category-title">⚡ Operations Team</h2>
                <p class="category-description">Daily operations and customer-facing activities</p>
            </div>
            <div class="roles-grid">
                <?php foreach ($role_categories['Operations'] as $role): ?>
                <div class="role-card" 
                     data-role-id="<?php echo $role['id']; ?>" 
                     data-role-name="<?php echo htmlspecialchars($role['name']); ?>"
                     style="--role-color: <?php echo $role['detailed_info']['color']; ?>">
                    <div class="role-icon">
                        <i class="<?php echo $role['detailed_info']['icon']; ?>"></i>
                    </div>
                    <h3 class="role-title"><?php echo htmlspecialchars($role['detailed_info']['display_name']); ?></h3>
                    <p class="role-summary"><?php echo htmlspecialchars($role['detailed_info']['summary']); ?></p>
                    <div class="role-features">
                        <h4>Key Responsibilities:</h4>
                        <ul class="features-list">
                            <?php foreach ($role['detailed_info']['key_features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="role-action">
                        <span class="role-badge"><?php echo htmlspecialchars($role['detailed_info']['category']); ?> Level</span>
                        <button class="access-btn" onclick="accessRole('<?php echo $role['id']; ?>', '<?php echo htmlspecialchars($role['name']); ?>')">
                            <i class="fas fa-sign-in-alt"></i>
                            Access Portal
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Support Roles -->
        <?php if (!empty($role_categories['Support'])): ?>
        <div class="category-section animate__animated animate__fadeInUp" data-category="Support">
            <div class="category-header">
                <h2 class="category-title">🛠️ Support & Specialist</h2>
                <p class="category-description">Technical support and specialized services</p>
            </div>
            <div class="roles-grid">
                <?php foreach ($role_categories['Support'] as $role): ?>
                <div class="role-card" 
                     data-role-id="<?php echo $role['id']; ?>" 
                     data-role-name="<?php echo htmlspecialchars($role['name']); ?>"
                     style="--role-color: <?php echo $role['detailed_info']['color']; ?>">
                    <div class="role-icon">
                        <i class="<?php echo $role['detailed_info']['icon']; ?>"></i>
                    </div>
                    <h3 class="role-title"><?php echo htmlspecialchars($role['detailed_info']['display_name']); ?></h3>
                    <p class="role-summary"><?php echo htmlspecialchars($role['detailed_info']['summary']); ?></p>
                    <div class="role-features">
                        <h4>Key Responsibilities:</h4>
                        <ul class="features-list">
                            <?php foreach ($role['detailed_info']['key_features'] as $feature): ?>
                            <li><?php echo htmlspecialchars($feature); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="role-action">
                        <span class="role-badge"><?php echo htmlspecialchars($role['detailed_info']['category']); ?> Level</span>
                        <button class="access-btn" onclick="accessRole('<?php echo $role['id']; ?>', '<?php echo htmlspecialchars($role['name']); ?>')">
                            <i class="fas fa-sign-in-alt"></i>
                            Access Portal
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Direct Login Option -->
        <div class="login-info animate__animated animate__fadeInUp">
            <h3><i class="fas fa-key"></i> Already Know Your Credentials?</h3>
            <p>If you have your username and password ready, you can go directly to the login page.</p>
            <a href="login.php" class="direct-login-btn">
                <i class="fas fa-sign-in-alt"></i>
                Direct Login
            </a>
        </div>
    </div>

    <script>
        // Create floating particles
        function createParticles() {
            const particlesContainer = document.querySelector('.particles');
            const particleCount = 50;

            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                const size = Math.random() * 4 + 1;
                const x = Math.random() * window.innerWidth;
                const y = Math.random() * window.innerHeight;
                const delay = Math.random() * 6;
                
                particle.style.width = size + 'px';
                particle.style.height = size + 'px';
                particle.style.left = x + 'px';
                particle.style.top = y + 'px';
                particle.style.animationDelay = delay + 's';
                
                particlesContainer.appendChild(particle);
            }
        }

        // Role access function
        function accessRole(roleId, roleName) {
            const roleCard = event.target.closest('.role-card');
            
            // Add loading state
            roleCard.classList.add('loading');
            
            // Show feedback message
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
            `;
            notification.innerHTML = `
                <i class="fas fa-info-circle"></i>
                Redirecting to login for ${roleName} role...
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
            
            // Redirect after short delay for better UX
            setTimeout(() => {
                // Add role information to URL for pre-selection
                window.location.href = `login.php?role=${roleId}&role_name=${encodeURIComponent(roleName)}`;
            }, 1500);
        }

        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
            
            // Add hover effects to role cards
            const roleCards = document.querySelectorAll('.role-card');
            roleCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    if (!this.classList.contains('loading')) {
                        this.style.transform = 'translateY(0) scale(1)';
                    }
                });
            });
            
            // Staggered animation for role cards
            const categories = document.querySelectorAll('.category-section');
            categories.forEach((category, index) => {
                category.style.animationDelay = (index * 0.2) + 's';
            });
        });

        // Handle browser back button
        window.addEventListener('pageshow', function(event) {
            if (event.persisted) {
                // Reset any loading states if user navigates back
                document.querySelectorAll('.role-card').forEach(card => {
                    card.classList.remove('loading');
                });
            }
        });
    </script>
</body>
</html>