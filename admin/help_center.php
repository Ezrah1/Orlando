<?php
$page_title = 'Help Center';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<?php
// Display session alerts
display_session_alerts();

// Get user role for personalized help
$user_role = $current_user['role_name'] ?? 'User';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Help Center</h1>
    <p class="page-subtitle">Get help, tutorials, and documentation for Orlando International Resorts</p>
</div>

<!-- Search Bar -->
<div class="row mb-4">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-body">
                <div class="input-group">
                    <input type="text" class="form-control" id="helpSearch" placeholder="Search for help topics, tutorials, or features...">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="button" onclick="searchHelp()">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Start Guide -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-rocket"></i> Quick Start Guide</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 text-center mb-3">
                        <div class="help-step">
                            <div class="step-number">1</div>
                            <h6>Dashboard Overview</h6>
                            <p class="text-muted">Learn about your main dashboard and key metrics</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="showHelpModal('dashboard')">Learn More</button>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="help-step">
                            <div class="step-number">2</div>
                            <h6>Navigation</h6>
                            <p class="text-muted">Understand the menu structure and navigation</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="showHelpModal('navigation')">Learn More</button>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="help-step">
                            <div class="step-number">3</div>
                            <h6>Common Tasks</h6>
                            <p class="text-muted">Master essential daily operations</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="showHelpModal('tasks')">Learn More</button>
                        </div>
                    </div>
                    <div class="col-md-3 text-center mb-3">
                        <div class="help-step">
                            <div class="step-number">4</div>
                            <h6>Keyboard Shortcuts</h6>
                            <p class="text-muted">Speed up your workflow with shortcuts</p>
                            <button class="btn btn-sm btn-outline-primary" onclick="showHelpModal('shortcuts')">Learn More</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Categories -->
<div class="row">
    <!-- Getting Started -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-play-circle"></i> Getting Started</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('first-login')" class="text-decoration-none">
                            <i class="fas fa-sign-in-alt text-primary"></i> First Time Login
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('dashboard-tour')" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt text-primary"></i> Dashboard Tour
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('user-preferences')" class="text-decoration-none">
                            <i class="fas fa-cog text-primary"></i> Setting Up Preferences
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('profile-setup')" class="text-decoration-none">
                            <i class="fas fa-user text-primary"></i> Profile Setup
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Core Operations -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-tasks"></i> Core Operations</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('booking-process')" class="text-decoration-none">
                            <i class="fas fa-calendar-check text-success"></i> Booking Process
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('check-in-out')" class="text-decoration-none">
                            <i class="fas fa-door-open text-success"></i> Check-in/Check-out
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('order-management')" class="text-decoration-none">
                            <i class="fas fa-shopping-cart text-success"></i> Order Management
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('payment-processing')" class="text-decoration-none">
                            <i class="fas fa-credit-card text-success"></i> Payment Processing
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Reports & Analytics -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> Reports & Analytics</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('revenue-analytics')" class="text-decoration-none">
                            <i class="fas fa-chart-line text-warning"></i> Revenue Analytics
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('department-reports')" class="text-decoration-none">
                            <i class="fas fa-building text-warning"></i> Department Reports
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('export-data')" class="text-decoration-none">
                            <i class="fas fa-download text-warning"></i> Exporting Data
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('custom-reports')" class="text-decoration-none">
                            <i class="fas fa-chart-pie text-warning"></i> Custom Reports
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Troubleshooting -->
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5><i class="fas fa-tools"></i> Troubleshooting</h5>
            </div>
            <div class="card-body">
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('login-issues')" class="text-decoration-none">
                            <i class="fas fa-exclamation-triangle text-danger"></i> Login Issues
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('data-not-loading')" class="text-decoration-none">
                            <i class="fas fa-spinner text-danger"></i> Data Not Loading
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('permission-errors')" class="text-decoration-none">
                            <i class="fas fa-lock text-danger"></i> Permission Errors
                        </a>
                    </li>
                    <li class="mb-2">
                        <a href="#" onclick="showHelpModal('contact-support')" class="text-decoration-none">
                            <i class="fas fa-headset text-danger"></i> Contact Support
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Role-Specific Help -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-user-tag"></i> Help for <?php echo htmlspecialchars($user_role); ?> Role</h5>
            </div>
            <div class="card-body">
                <?php if ($user_role == 'Director' || $user_role == 'IT Admin'): ?>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>System Administration</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('user-management')">User Management</a></li>
                                <li><a href="#" onclick="showHelpModal('role-permissions')">Role & Permissions</a></li>
                                <li><a href="#" onclick="showHelpModal('system-settings')">System Settings</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Financial Oversight</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('financial-reports')">Financial Reports</a></li>
                                <li><a href="#" onclick="showHelpModal('audit-trails')">Audit Trails</a></li>
                                <li><a href="#" onclick="showHelpModal('budget-management')">Budget Management</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Strategic Planning</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('performance-metrics')">Performance Metrics</a></li>
                                <li><a href="#" onclick="showHelpModal('trend-analysis')">Trend Analysis</a></li>
                                <li><a href="#" onclick="showHelpModal('forecasting')">Forecasting</a></li>
                            </ul>
                        </div>
                    </div>
                <?php elseif ($user_role == 'Finance Manager'): ?>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Financial Management</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('chart-accounts')">Chart of Accounts</a></li>
                                <li><a href="#" onclick="showHelpModal('journal-entries')">Journal Entries</a></li>
                                <li><a href="#" onclick="showHelpModal('general-ledger')">General Ledger</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Payroll & HR</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('payroll-processing')">Payroll Processing</a></li>
                                <li><a href="#" onclick="showHelpModal('tax-calculations')">Tax Calculations</a></li>
                                <li><a href="#" onclick="showHelpModal('benefits-management')">Benefits Management</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Reporting</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('financial-statements')">Financial Statements</a></li>
                                <li><a href="#" onclick="showHelpModal('cash-flow')">Cash Flow Analysis</a></li>
                                <li><a href="#" onclick="showHelpModal('reconciliation')">Bank Reconciliation</a></li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <div class="col-md-4">
                            <h6>Daily Operations</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('daily-tasks')">Daily Tasks</a></li>
                                <li><a href="#" onclick="showHelpModal('customer-service')">Customer Service</a></li>
                                <li><a href="#" onclick="showHelpModal('inventory-management')">Inventory Management</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Communication</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('team-collaboration')">Team Collaboration</a></li>
                                <li><a href="#" onclick="showHelpModal('reporting-updates')">Reporting Updates</a></li>
                                <li><a href="#" onclick="showHelpModal('escalation-procedures')">Escalation Procedures</a></li>
                            </ul>
                        </div>
                        <div class="col-md-4">
                            <h6>Best Practices</h6>
                            <ul class="list-unstyled">
                                <li><a href="#" onclick="showHelpModal('data-entry')">Data Entry Best Practices</a></li>
                                <li><a href="#" onclick="showHelpModal('security-guidelines')">Security Guidelines</a></li>
                                <li><a href="#" onclick="showHelpModal('efficiency-tips')">Efficiency Tips</a></li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- FAQ Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-question-circle"></i> Frequently Asked Questions</h5>
            </div>
            <div class="card-body">
                <div class="accordion" id="faqAccordion">
                    <div class="card">
                        <div class="card-header" id="faq1">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse1">
                                    How do I reset my password?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse1" class="collapse" data-parent="#faqAccordion">
                            <div class="card-body">
                                To reset your password, go to User Preferences > Security Settings. Enter your current password and choose a new one that's at least 8 characters long. If you've forgotten your password, contact your system administrator.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="faq2">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse2">
                                    How do I export reports?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse2" class="collapse" data-parent="#faqAccordion">
                            <div class="card-body">
                                Most report pages have export buttons (CSV, Excel, PDF) in the top-right corner. Click the export button and choose your preferred format. The file will download automatically.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="faq3">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse3">
                                    What are the keyboard shortcuts?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse3" class="collapse" data-parent="#faqAccordion">
                            <div class="card-body">
                                Common shortcuts include: Ctrl+S (Save), Ctrl+N (New), Esc (Close), Ctrl+P (Print), Ctrl+E (Export). See the full list in User Preferences > Keyboard Shortcuts.
                            </div>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header" id="faq4">
                            <h2 class="mb-0">
                                <button class="btn btn-link" type="button" data-toggle="collapse" data-target="#collapse4">
                                    How do I customize my dashboard?
                                </button>
                            </h2>
                        </div>
                        <div id="collapse4" class="collapse" data-parent="#faqAccordion">
                            <div class="card-body">
                                Go to User Preferences > Interface Preferences. You can change the theme, layout, and enable auto-refresh. Your preferences are saved automatically.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Help Modal -->
<div class="modal fade" id="helpModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="helpModalTitle">Help Topic</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="helpModalBody">
                <!-- Content will be loaded dynamically -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="printHelp()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.help-step {
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    height: 100%;
}

.step-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    margin: 0 auto 15px;
}

.accordion .card-header button {
    text-decoration: none;
    color: #333;
    font-weight: 600;
}

.accordion .card-header button:hover {
    text-decoration: none;
    color: #667eea;
}
</style>

<?php include '../includes/admin/footer.php'; ?>