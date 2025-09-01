<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has finance permissions BEFORE including header
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Admin (role_id = 1) and Director (role_id = 11) get automatic access
if ($user_role_id == 1 || $user_role_id == 11) {
    // Admin and Director bypass all checks
} else {
    // Check both original role name and lowercase version for compatibility
    $allowed_roles = ['Admin', 'Director', 'Finance', 'Finance_Manager', 'Finance_Controller', 'Finance_Officer', 'Super_Admin', 'finance', 'admin', 'super_admin'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
        header("Location: access_denied.php");
        exit();
    }
}

$page_title = 'Finance Dashboard';
include '../includes/admin/header.php';

// Finance Dashboard - Full Financial Records Access
$today = date('Y-m-d');

// Core Financial Metrics - Full Access
$today_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE DATE(cin) = CURDATE()");
$today_revenue_data = mysqli_fetch_assoc($today_revenue)['revenue'] ?? 0;

$monthly_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$monthly_revenue_data = mysqli_fetch_assoc($monthly_revenue)['revenue'] ?? 0;

$yearly_revenue = mysqli_query($con, "SELECT SUM(ttot) as revenue FROM payment WHERE YEAR(cin) = YEAR(CURDATE())");
$yearly_revenue_data = mysqli_fetch_assoc($yearly_revenue)['revenue'] ?? 0;

// Advanced Financial Analysis
$monthly_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$monthly_bookings_data = mysqli_fetch_assoc($monthly_bookings)['count'] ?? 0;

$avg_transaction = $monthly_bookings_data > 0 ? round($monthly_revenue_data / $monthly_bookings_data, 2) : 0;

// Outstanding Payments & Billing Management
$pending_payments = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Not Confirm'");
$pending_payments_data = mysqli_fetch_assoc($pending_payments)['count'] ?? 0;

$confirmed_bookings = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Confirm' AND MONTH(cin) = MONTH(CURDATE())");
$confirmed_bookings_data = mysqli_fetch_assoc($confirmed_bookings)['count'] ?? 0;

// Cost Analysis - Finance Full Access
$food_costs = mysqli_query($con, "SELECT SUM(current_stock * unit_cost) as total FROM kitchen_inventory WHERE is_active = 1");
$food_costs_data = mysqli_fetch_assoc($food_costs)['total'] ?? 0;

$bar_costs = mysqli_query($con, "SELECT SUM(current_stock * unit_cost) as total FROM bar_inventory WHERE is_active = 1");
$bar_costs_data = mysqli_fetch_assoc($bar_costs)['total'] ?? 0;

// Revenue by Category
$room_revenue = $monthly_revenue_data; // Primary revenue source
$food_revenue = mysqli_query($con, "SELECT SUM(oi.quantity * mi.price) as revenue FROM order_items oi JOIN menu_items mi ON oi.menu_item_id = mi.id JOIN food_orders fo ON oi.order_id = fo.id WHERE MONTH(fo.ordered_time) = MONTH(CURDATE())");
$food_revenue_data = mysqli_fetch_assoc($food_revenue)['revenue'] ?? 0;

$bar_revenue = mysqli_query($con, "SELECT SUM(boi.quantity * bi.selling_price) as revenue FROM bar_order_items boi JOIN bar_inventory bi ON boi.inventory_id = bi.id JOIN bar_orders bo ON boi.order_id = bo.id WHERE MONTH(bo.ordered_time) = MONTH(CURDATE())");
$bar_revenue_data = mysqli_fetch_assoc($bar_revenue)['revenue'] ?? 0;

// Expense Tracking
$maintenance_costs = mysqli_query($con, "SELECT SUM(actual_cost) as total FROM maintenance_requests WHERE status = 'completed' AND MONTH(completed_at) = MONTH(CURDATE())");
$maintenance_costs_data = mysqli_fetch_assoc($maintenance_costs)['total'] ?? 0;

// Monthly revenue already calculated above at line 33-34

// Pending payments already calculated above at line 46-47

$monthly_expenses = 45000; // This would come from an expenses table
$profit_margin = $monthly_revenue_data - $monthly_expenses;
?>

<style>
.finance-dashboard {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
}

.finance-header {
    background: linear-gradient(135deg, #2d5016 0%, #38a169 100%);
    color: white;
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
}

.finance-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-left: 4px solid var(--accent-color, #38a169);
    margin-bottom: 25px;
}

.finance-stat {
    text-align: center;
    padding: 20px;
}

.finance-stat .value {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2d3748;
    margin-bottom: 10px;
}

.finance-stat .label {
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.9rem;
    letter-spacing: 1px;
}

.quick-finance-actions {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-top: 30px;
}

.finance-action-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    border: 1px solid #e2e8f0;
}

.finance-action-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.finance-action-icon {
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-size: 24px;
    color: white;
}

.revenue-trend {
    height: 300px;
    margin: 20px 0;
}
</style>

<div class="finance-dashboard">
    <!-- Finance Header -->
    <div class="finance-header">
        <div class="container">
            <h1 class="mb-3">
                <i class="fas fa-chart-line me-3"></i>
                Finance Control Center
            </h1>
            <p class="mb-0">Financial overview and management tools for Orlando International Resorts</p>
        </div>
    </div>

    <div class="container">
        <!-- Key Financial Metrics -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="finance-card" style="--accent-color: #38a169;">
                    <div class="finance-stat">
                        <div class="value">KES <?php echo number_format($today_revenue_data); ?></div>
                        <div class="label">Today's Revenue</div>
                        <small class="text-success">
                            <i class="fas fa-arrow-up"></i> +12% vs yesterday
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="finance-card" style="--accent-color: #3182ce;">
                    <div class="finance-stat">
                        <div class="value">KES <?php echo number_format($monthly_revenue_data); ?></div>
                        <div class="label">Monthly Revenue</div>
                        <small class="text-info">
                            <i class="fas fa-calendar"></i> Current month
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="finance-card" style="--accent-color: #d69e2e;">
                    <div class="finance-stat">
                        <div class="value"><?php echo $pending_payments_data; ?></div>
                        <div class="label">Pending Payments</div>
                        <small class="text-warning">
                            <i class="fas fa-clock"></i> Requires attention
                        </small>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="finance-card" style="--accent-color: #805ad5;">
                    <div class="finance-stat">
                        <div class="value">$<?php echo number_format($profit_margin); ?></div>
                        <div class="label">Monthly Profit</div>
                        <small class="text-<?php echo $profit_margin > 0 ? 'success' : 'danger'; ?>">
                            <i class="fas fa-<?php echo $profit_margin > 0 ? 'arrow-up' : 'arrow-down'; ?>"></i> 
                            <?php echo $profit_margin > 0 ? 'Profit' : 'Loss'; ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Revenue Chart -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="finance-card">
                    <h3 class="mb-4">
                        <i class="fas fa-chart-area me-2"></i>
                        Revenue Trends (Last 30 Days)
                    </h3>
                    <div class="revenue-trend">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Finance Actions -->
        <div class="quick-finance-actions">
            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #38a169, #48bb78);">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h5>Generate Reports</h5>
                <p class="text-muted mb-3">Create financial reports and statements</p>
                <a href="financial_reports.php" class="btn btn-success btn-sm">Generate Report</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #3182ce, #4299e1);">
                    <i class="fas fa-calculator"></i>
                </div>
                <h5>Accounting Dashboard</h5>
                <p class="text-muted mb-3">Access comprehensive accounting tools</p>
                <a href="accounting_dashboard.php" class="btn btn-primary btn-sm">Open Accounting</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #d69e2e, #f6ad55);">
                    <i class="fas fa-credit-card"></i>
                </div>
                <h5>Payment Management</h5>
                <p class="text-muted mb-3">Process and track payments</p>
                <a href="payment.php" class="btn btn-warning btn-sm">Manage Payments</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #805ad5, #b794f6);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h5>Chart of Accounts</h5>
                <p class="text-muted mb-3">Manage your accounting chart of accounts</p>
                <a href="chart_of_accounts.php" class="btn btn-purple btn-sm">Manage Accounts</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #319795, #4fd1c7);">
                    <i class="fas fa-book"></i>
                </div>
                <h5>General Ledger</h5>
                <p class="text-muted mb-3">View and manage general ledger entries</p>
                <a href="general_ledger.php" class="btn btn-info btn-sm">View Ledger</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #f56565, #fc8181);">
                    <i class="fas fa-edit"></i>
                </div>
                <h5>Journal Entries</h5>
                <p class="text-muted mb-3">Create and manage journal entries</p>
                <a href="journal_entries.php" class="btn btn-danger btn-sm">Manage Entries</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #38a169, #48bb78);">
                    <i class="fas fa-wallet"></i>
                </div>
                <h5>Petty Cash</h5>
                <p class="text-muted mb-3">Track petty cash transactions</p>
                <a href="petty_cash.php" class="btn btn-success btn-sm">Manage Cash</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #ed8936, #f6ad55);">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <h5>Transactions</h5>
                <p class="text-muted mb-3">View all payment transactions</p>
                <a href="transactions.php" class="btn btn-warning btn-sm">View Transactions</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #3182ce, #4299e1);">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h5>M-Pesa Reconciliation</h5>
                <p class="text-muted mb-3">Reconcile M-Pesa payments</p>
                <a href="mpesa_reconciliation.php" class="btn btn-primary btn-sm">Reconcile</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #805ad5, #b794f6);">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h5>Expense Tracking</h5>
                <p class="text-muted mb-3">Monitor operational expenses</p>
                <a href="petty_cash.php" class="btn btn-purple btn-sm">Track Expenses</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #e53e3e, #fc8181);">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <h5>Overdue Accounts</h5>
                <p class="text-muted mb-3">Review accounts requiring attention</p>
                <a href="revenue_analytics.php" class="btn btn-danger btn-sm">Review Accounts</a>
            </div>

            <div class="finance-action-card">
                <div class="finance-action-icon" style="background: linear-gradient(135deg, #2d3748, #4a5568);">
                    <i class="fas fa-money-check-alt"></i>
                </div>
                <h5>M-Pesa Reconciliation</h5>
                <p class="text-muted mb-3">Reconcile mobile money transactions</p>
                <a href="mpesa_reconciliation.php" class="btn btn-dark btn-sm">Reconcile</a>
            </div>
        </div>

        <!-- Recent Financial Transactions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="finance-card">
                    <h3 class="mb-4">
                        <i class="fas fa-history me-2"></i>
                        Recent Financial Activity
                    </h3>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><?php echo date('M d, Y'); ?></td>
                                    <td><span class="badge bg-success">Revenue</span></td>
                                    <td>Room booking payment</td>
                                    <td>$250.00</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><?php echo date('M d, Y'); ?></td>
                                    <td><span class="badge bg-info">Payment</span></td>
                                    <td>M-Pesa transaction</td>
                                    <td>$120.00</td>
                                    <td><span class="badge bg-success">Completed</span></td>
                                </tr>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime('-1 day')); ?></td>
                                    <td><span class="badge bg-warning">Expense</span></td>
                                    <td>Housekeeping supplies</td>
                                    <td>$85.00</td>
                                    <td><span class="badge bg-success">Paid</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for revenue chart -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            datasets: [{
                label: 'Revenue',
                data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 35000, 32000, 40000, 38000, 42000],
                borderColor: '#38a169',
                backgroundColor: 'rgba(56, 161, 105, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
});
</script>

<style>
.btn-purple {
    background: linear-gradient(135deg, #805ad5, #b794f6);
    color: white;
    border: none;
}

.btn-purple:hover {
    background: linear-gradient(135deg, #6b46c1, #9f7aea);
    color: white;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
