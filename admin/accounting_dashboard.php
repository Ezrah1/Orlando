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

// Check permissions BEFORE including header - Finance/Accounting access
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Director (role_id = 11) and Admin (role_id = 1) get automatic access
if ($user_role_id == 11 || $user_role_id == 1) {
    // Director and Admin bypass all checks
} else {
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'Finance', 'Finance_Controller', 'Finance_Officer', 'director', 'ceo', 'super_admin', 'finance_manager', 'finance'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), $allowed_roles) && 
        !user_has_permission($con, 'finance.full_access') && 
        !user_has_permission($con, 'accounting.access')) {
        header('Location: access_denied.php');
        exit();
    }
}

$page_title = 'Accounting Dashboard';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Get current financial data
$current_month = date('Y-m');
$current_year = date('Y');

// Account Categories Summary (balance would be calculated from journal entries)
$account_balances_sql = "SELECT account_type, COUNT(*) as account_count 
                        FROM chart_of_accounts 
                        WHERE is_active = 1 
                        GROUP BY account_type";
$account_balances_result = mysqli_query($con, $account_balances_sql);
$account_balances = [];
while ($row = mysqli_fetch_assoc($account_balances_result)) {
    $account_balances[$row['account_type']] = $row['account_count'];
}

// Recent Transactions
$recent_transactions_sql = "SELECT t.* 
                           FROM transactions t 
                           ORDER BY t.timestamp DESC 
                           LIMIT 10";
$recent_transactions_result = mysqli_query($con, $recent_transactions_sql);

// Monthly Financial Summary
$monthly_revenue_sql = "SELECT SUM(ttot) as revenue FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())";
$monthly_revenue_result = mysqli_query($con, $monthly_revenue_sql);
$monthly_revenue = mysqli_fetch_assoc($monthly_revenue_result)['revenue'] ?? 0;

$monthly_expenses_sql = "SELECT SUM(net_amount) as expenses FROM transactions WHERE amount_gross < 0 AND MONTH(timestamp) = MONTH(CURDATE()) AND YEAR(timestamp) = YEAR(CURDATE())";
$monthly_expenses_result = mysqli_query($con, $monthly_expenses_sql);
$monthly_expenses = abs(mysqli_fetch_assoc($monthly_expenses_result)['expenses'] ?? 0);

// Pending Journal Entries
$pending_entries_sql = "SELECT COUNT(*) as count FROM journal_entries WHERE status = 'draft'";
$pending_entries_result = mysqli_query($con, $pending_entries_sql);
$pending_entries = mysqli_fetch_assoc($pending_entries_result)['count'] ?? 0;

// Outstanding Payables/Receivables
$receivables_sql = "SELECT SUM(p.ttot) as total FROM payment p 
                     JOIN roombook r ON p.fname = r.FName AND p.lname = r.LName AND p.troom = r.TRoom AND p.cin = r.cin 
                     WHERE r.stat = 'Confirm' AND r.payment_status = 'pending'";
$receivables_result = mysqli_query($con, $receivables_sql);
$outstanding_receivables = mysqli_fetch_assoc($receivables_result)['total'] ?? 0;

// Cash flow data for the last 6 months
$cashflow_data = [];
for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $revenue_sql = "SELECT SUM(ttot) as revenue FROM payment WHERE DATE_FORMAT(cin, '%Y-%m') = '$month'";
    $revenue_result = mysqli_query($con, $revenue_sql);
    $revenue = mysqli_fetch_assoc($revenue_result)['revenue'] ?? 0;
    
    $expense_sql = "SELECT SUM(ABS(net_amount)) as expenses FROM transactions WHERE amount_gross < 0 AND DATE_FORMAT(timestamp, '%Y-%m') = '$month'";
    $expense_result = mysqli_query($con, $expense_sql);
    $expenses = mysqli_fetch_assoc($expense_result)['expenses'] ?? 0;
    
    $cashflow_data[] = [
        'month' => date('M Y', strtotime($month)),
        'revenue' => $revenue,
        'expenses' => $expenses,
        'net' => $revenue - $expenses
    ];
}
?>

<div class="accounting-dashboard">
    <!-- Header Section -->
    <div class="dashboard-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="dashboard-title">
                    <i class="fas fa-calculator me-3"></i>
                    Accounting Dashboard
                </h1>
                <p class="dashboard-subtitle">Comprehensive Financial Management & Reporting</p>
            </div>
            <div class="header-actions">
                <button class="btn btn-outline-primary me-2" onclick="exportFinancialReport()">
                    <i class="fas fa-download"></i> Export Report
                </button>
                <button class="btn btn-primary" onclick="openQuickEntry()">
                    <i class="fas fa-plus"></i> Quick Entry
                </button>
            </div>
        </div>
    </div>

    <!-- Key Metrics Row -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card revenue">
                <div class="metric-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="metric-info">
                    <h3>KES <?php echo number_format($monthly_revenue, 2); ?></h3>
                    <p>Monthly Revenue</p>
                    <small class="text-success">+12.5% from last month</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card expenses">
                <div class="metric-icon">
                    <i class="fas fa-chart-line-down"></i>
                </div>
                <div class="metric-info">
                    <h3>KES <?php echo number_format($monthly_expenses, 2); ?></h3>
                    <p>Monthly Expenses</p>
                    <small class="text-warning">+5.2% from last month</small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card profit">
                <div class="metric-icon">
                    <i class="fas fa-money-bill-trend-up"></i>
                </div>
                <div class="metric-info">
                    <h3>KES <?php echo number_format($monthly_revenue - $monthly_expenses, 2); ?></h3>
                    <p>Net Income</p>
                    <small class="<?php echo ($monthly_revenue - $monthly_expenses) > 0 ? 'text-success' : 'text-danger'; ?>">
                        <?php echo ($monthly_revenue - $monthly_expenses) > 0 ? 'Profit' : 'Loss'; ?>
                    </small>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="metric-card receivables">
                <div class="metric-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="metric-info">
                    <h3>KES <?php echo number_format($outstanding_receivables, 2); ?></h3>
                    <p>Outstanding Receivables</p>
                    <small class="text-info"><?php echo $pending_entries; ?> pending entries</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Cash Flow Chart -->
            <div class="accounting-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-water me-2"></i>Cash Flow Analysis</h5>
                    <div class="card-actions">
                        <select class="form-select form-select-sm" id="cashflowPeriod">
                            <option value="6">Last 6 Months</option>
                            <option value="12">Last 12 Months</option>
                            <option value="24">Last 24 Months</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="cashFlowChart" height="300"></canvas>
                </div>
            </div>

            <!-- Account Balances -->
            <div class="accounting-card">
                <div class="card-header">
                    <h5><i class="fas fa-balance-scale me-2"></i>Account Balances by Type</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php 
                        $account_types = [
                            'Assets' => ['icon' => 'fas fa-building', 'color' => 'success'],
                            'Liabilities' => ['icon' => 'fas fa-credit-card', 'color' => 'warning'],
                            'Equity' => ['icon' => 'fas fa-chart-pie', 'color' => 'info'],
                            'Revenue' => ['icon' => 'fas fa-arrow-trend-up', 'color' => 'primary'],
                            'Expenses' => ['icon' => 'fas fa-arrow-trend-down', 'color' => 'danger']
                        ];
                        
                        foreach ($account_types as $type => $config): 
                            $account_count = $account_balances[$type] ?? 0;
                        ?>
                        <div class="col-md-6 col-lg-4 mb-3">
                            <div class="balance-card">
                                <div class="balance-icon text-<?php echo $config['color']; ?>">
                                    <i class="<?php echo $config['icon']; ?>"></i>
                                </div>
                                <div class="balance-info">
                                    <h6><?php echo $type; ?></h6>
                                    <h4 class="text-<?php echo $config['color']; ?>">
                                        <?php echo $account_count; ?> accounts
                                    </h4>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="accounting-card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="journal_entries.php" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-2"></i>New Journal Entry
                        </a>
                        <a href="chart_of_accounts.php" class="btn btn-outline-success">
                            <i class="fas fa-list me-2"></i>Chart of Accounts
                        </a>
                        <a href="general_ledger.php" class="btn btn-outline-info">
                            <i class="fas fa-book me-2"></i>General Ledger
                        </a>
                        <a href="financial_reports.php" class="btn btn-outline-warning">
                            <i class="fas fa-file-alt me-2"></i>Financial Reports
                        </a>
                        <a href="petty_cash.php" class="btn btn-outline-secondary">
                            <i class="fas fa-wallet me-2"></i>Petty Cash
                        </a>
                        <a href="transactions.php" class="btn btn-outline-dark">
                            <i class="fas fa-exchange-alt me-2"></i>All Transactions
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="accounting-card">
                <div class="card-header">
                    <h5><i class="fas fa-history me-2"></i>Recent Transactions</h5>
                    <a href="transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body">
                    <div class="transaction-list">
                        <?php if (mysqli_num_rows($recent_transactions_result) > 0): ?>
                            <?php while ($transaction = mysqli_fetch_assoc($recent_transactions_result)): ?>
                            <div class="transaction-item">
                                <div class="transaction-info">
                                    <h6 class="mb-1"><?php echo htmlspecialchars($transaction['description'] ?? 'Transaction'); ?></h6>
                                    <small class="text-muted">
                                        <?php echo htmlspecialchars($transaction['account_name'] ?? 'N/A'); ?>
                                    </small>
                                </div>
                                <div class="transaction-amount">
                                    <span class="<?php echo $transaction['net_amount'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                        <?php echo $transaction['net_amount'] > 0 ? '+' : ''; ?>KES <?php echo number_format($transaction['net_amount'], 2); ?>
                                    </span>
                                    <small class="text-muted d-block">
                                        <?php echo date('M j, g:i A', strtotime($transaction['timestamp'] ?? 'now')); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-3">
                                <i class="fas fa-inbox fa-2x mb-2"></i>
                                <p>No recent transactions</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.accounting-dashboard {
    padding: 20px;
    background: #f8f9fa;
    min-height: 100vh;
}

.dashboard-header {
    background: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
}

.dashboard-title {
    color: #2c3e50;
    font-weight: 700;
    margin: 0;
}

.dashboard-subtitle {
    color: #7f8c8d;
    margin: 0;
    font-size: 16px;
}

.header-actions .btn {
    border-radius: 25px;
    font-weight: 600;
    padding: 10px 20px;
}

.metric-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    display: flex;
    align-items: center;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transition: transform 0.3s;
    border-left: 5px solid;
}

.metric-card:hover {
    transform: translateY(-5px);
}

.metric-card.revenue { border-left-color: #28a745; }
.metric-card.expenses { border-left-color: #ffc107; }
.metric-card.profit { border-left-color: #17a2b8; }
.metric-card.receivables { border-left-color: #6f42c1; }

.metric-icon {
    font-size: 48px;
    margin-right: 20px;
    opacity: 0.8;
}

.metric-card.revenue .metric-icon { color: #28a745; }
.metric-card.expenses .metric-icon { color: #ffc107; }
.metric-card.profit .metric-icon { color: #17a2b8; }
.metric-card.receivables .metric-icon { color: #6f42c1; }

.metric-info h3 {
    margin: 0;
    font-weight: 700;
    color: #2c3e50;
    font-size: 24px;
}

.metric-info p {
    margin: 5px 0;
    color: #6c757d;
    font-weight: 500;
}

.accounting-card {
    background: white;
    border-radius: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    border: none;
}

.accounting-card .card-header {
    background: transparent;
    border-bottom: 1px solid #e9ecef;
    padding: 20px 25px;
    display: flex;
    justify-content: between;
    align-items: center;
}

.accounting-card .card-header h5 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

.accounting-card .card-body {
    padding: 25px;
}

.balance-card {
    background: #f8f9fa;
    border-radius: 10px;
    padding: 20px;
    display: flex;
    align-items: center;
    transition: all 0.3s;
}

.balance-card:hover {
    background: #e9ecef;
    transform: translateY(-2px);
}

.balance-icon {
    font-size: 32px;
    margin-right: 15px;
}

.balance-info h6 {
    margin: 0;
    color: #6c757d;
    font-size: 14px;
    font-weight: 500;
}

.balance-info h4 {
    margin: 5px 0 0;
    font-weight: 700;
    font-size: 18px;
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f8f9fa;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-info h6 {
    margin: 0;
    color: #2c3e50;
    font-weight: 600;
}

.transaction-amount {
    text-align: right;
}

.card-actions .form-select {
    width: auto;
    min-width: 150px;
}

@media (max-width: 768px) {
    .dashboard-header {
        padding: 20px;
    }
    
    .dashboard-header .d-flex {
        flex-direction: column;
        align-items: flex-start !important;
    }
    
    .header-actions {
        margin-top: 15px;
        width: 100%;
    }
    
    .header-actions .btn {
        width: 100%;
        margin-bottom: 10px;
    }
    
    .metric-card {
        flex-direction: column;
        text-align: center;
    }
    
    .metric-icon {
        margin-right: 0;
        margin-bottom: 15px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Cash Flow Chart
    const cashFlowCtx = document.getElementById('cashFlowChart').getContext('2d');
    const cashFlowData = <?php echo json_encode($cashflow_data); ?>;
    
    new Chart(cashFlowCtx, {
        type: 'line',
        data: {
            labels: cashFlowData.map(d => d.month),
            datasets: [
                {
                    label: 'Revenue',
                    data: cashFlowData.map(d => d.revenue),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Expenses',
                    data: cashFlowData.map(d => d.expenses),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    tension: 0.4,
                    fill: true
                },
                {
                    label: 'Net Income',
                    data: cashFlowData.map(d => d.net),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    tension: 0.4,
                    fill: false
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                title: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            interaction: {
                mode: 'index',
                intersect: false,
            },
        }
    });
});

function exportFinancialReport() {
    // Implement export functionality
    alert('Export functionality will be implemented here');
}

function openQuickEntry() {
    // Redirect to journal entries for quick entry
    window.location.href = 'journal_entries.php';
}
</script>

<?php include '../includes/admin/footer.php'; ?>
