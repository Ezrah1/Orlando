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

// Check finance permissions - Directors have full access
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Director (role_id = 11) and Admin (role_id = 1) get automatic access
if ($user_role_id == 11 || $user_role_id == 1) {
    // Director and Admin bypass all checks
} else {
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'Finance', 'Finance_Controller', 'Finance_Officer', 'director', 'ceo', 'super_admin', 'finance'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), $allowed_roles) && 
        !user_has_permission($con, 'finance.reports') && 
        !user_has_permission($con, 'finance.full_access')) {
        header('Location: access_denied.php');
        exit();
    }
}

$page_title = 'Financial Reports';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page content starts here -->

<?php
// Display session alerts
display_session_alerts();
// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'income_statement';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$department = isset($_GET['department']) ? $_GET['department'] : '';

// Header already included at the top

// Get financial data based on report type
function getFinancialData($con, $report_type, $date_from, $date_to, $department = '') {
    $data = [];
    
    switch($report_type) {
        case 'income_statement':
            // Revenue
            $revenue_sql = "SELECT coa.account_name, SUM(gl.credit_amount) as amount
                           FROM general_ledger gl
                           JOIN chart_of_accounts coa ON gl.account_id = coa.id
                           WHERE coa.account_type = 'revenue'
                           AND gl.entry_date BETWEEN ? AND ?
                           GROUP BY coa.id, coa.account_name
                           ORDER BY amount DESC";
            $stmt = $con->prepare($revenue_sql);
            $stmt->bind_param("ss", $date_from, $date_to);
            $stmt->execute();
            $data['revenue'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Expenses
            $expense_sql = "SELECT coa.account_name, SUM(gl.debit_amount) as amount
                           FROM general_ledger gl
                           JOIN chart_of_accounts coa ON gl.account_id = coa.id
                           WHERE coa.account_type = 'expense'
                           AND gl.entry_date BETWEEN ? AND ?
                           GROUP BY coa.id, coa.account_name
                           ORDER BY amount DESC";
            $stmt = $con->prepare($expense_sql);
            $stmt->bind_param("ss", $date_from, $date_to);
            $stmt->execute();
            $data['expenses'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'balance_sheet':
            // Assets
            $assets_sql = "SELECT coa.account_name, SUM(gl.debit_amount - gl.credit_amount) as balance
                          FROM general_ledger gl
                          JOIN chart_of_accounts coa ON gl.account_id = coa.id
                          WHERE coa.account_type = 'asset'
                          AND gl.entry_date <= ?
                          GROUP BY coa.id, coa.account_name
                          HAVING balance > 0
                          ORDER BY balance DESC";
            $stmt = $con->prepare($assets_sql);
            $stmt->bind_param("s", $date_to);
            $stmt->execute();
            $data['assets'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Liabilities
            $liabilities_sql = "SELECT coa.account_name, SUM(gl.credit_amount - gl.debit_amount) as balance
                               FROM general_ledger gl
                               JOIN chart_of_accounts coa ON gl.account_id = coa.id
                               WHERE coa.account_type = 'liability'
                               AND gl.entry_date <= ?
                               GROUP BY coa.id, coa.account_name
                               HAVING balance > 0
                               ORDER BY balance DESC";
            $stmt = $con->prepare($liabilities_sql);
            $stmt->bind_param("s", $date_to);
            $stmt->execute();
            $data['liabilities'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
            
        case 'cash_flow':
            // Operating activities
            $operating_sql = "SELECT coa.account_name, 
                                    SUM(CASE WHEN coa.account_type = 'revenue' THEN gl.credit_amount ELSE 0 END) as inflows,
                                    SUM(CASE WHEN coa.account_type = 'expense' THEN gl.debit_amount ELSE 0 END) as outflows
                             FROM general_ledger gl
                             JOIN chart_of_accounts coa ON gl.account_id = coa.id
                             WHERE (coa.account_type = 'revenue' OR coa.account_type = 'expense')
                             AND gl.entry_date BETWEEN ? AND ?
                             GROUP BY coa.id, coa.account_name
                             ORDER BY (inflows - outflows) DESC";
            $stmt = $con->prepare($operating_sql);
            $stmt->bind_param("ss", $date_from, $date_to);
            $stmt->execute();
            $data['operating'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            break;
    }
    
    return $data;
}

$financial_data = getFinancialData($con, $report_type, $date_from, $date_to, $department);
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Financial Reports</h1>
    <p class="page-subtitle">Generate and export comprehensive financial reports</p>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Report Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <label for="report_type">Report Type</label>
                <select name="report_type" id="report_type" class="form-control">
                    <option value="income_statement" <?php echo $report_type == 'income_statement' ? 'selected' : ''; ?>>Income Statement</option>
                    <option value="balance_sheet" <?php echo $report_type == 'balance_sheet' ? 'selected' : ''; ?>>Balance Sheet</option>
                    <option value="cash_flow" <?php echo $report_type == 'cash_flow' ? 'selected' : ''; ?>>Cash Flow Statement</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-2">
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <label for="department">Department</label>
                <select name="department" id="department" class="form-control">
                    <option value="">All Departments</option>
                    <option value="accommodation" <?php echo $department == 'accommodation' ? 'selected' : ''; ?>>Accommodation</option>
                    <option value="food" <?php echo $department == 'food' ? 'selected' : ''; ?>>Food & Beverage</option>
                    <option value="bar" <?php echo $department == 'bar' ? 'selected' : ''; ?>>Bar</option>
                    <option value="housekeeping" <?php echo $department == 'housekeeping' ? 'selected' : ''; ?>>Housekeeping</option>
                    <option value="maintenance" <?php echo $department == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Generate
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Export Options -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-download"></i> Export Options</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 mb-2">
                <button class="btn btn-success btn-block" onclick="exportPDF()">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
            </div>
            <div class="col-md-3 mb-2">
                <button class="btn btn-info btn-block" onclick="exportExcel()">
                    <i class="fas fa-file-excel"></i> Export Excel
                </button>
            </div>
            <div class="col-md-3 mb-2">
                <button class="btn btn-warning btn-block" onclick="exportCSV()">
                    <i class="fas fa-file-csv"></i> Export CSV
                </button>
            </div>
            <div class="col-md-3 mb-2">
                <button class="btn btn-secondary btn-block" onclick="printReport()">
                    <i class="fas fa-print"></i> Print Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Report Content -->
<div class="card">
    <div class="card-header">
        <h5>
            <i class="fas fa-chart-line"></i> 
            <?php 
            switch($report_type) {
                case 'income_statement': echo 'Income Statement'; break;
                case 'balance_sheet': echo 'Balance Sheet'; break;
                case 'cash_flow': echo 'Cash Flow Statement'; break;
            }
            ?>
        </h5>
        <div class="float-right">
            <small class="text-muted">
                Period: <?php echo date('M d, Y', strtotime($date_from)); ?> - <?php echo date('M d, Y', strtotime($date_to)); ?>
            </small>
        </div>
    </div>
    <div class="card-body">
        <?php if($report_type == 'income_statement'): ?>
            <!-- Income Statement -->
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-success"><i class="fas fa-plus-circle"></i> Revenue</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php 
;
                                $total_revenue = 0;
                                foreach($financial_data['revenue'] as $item): 
                                    $total_revenue += $item['amount'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td class="text-right">KES <?php echo number_format($item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-success font-weight-bold">
                                    <td>Total Revenue</td>
                                    <td class="text-right">KES <?php echo number_format($total_revenue, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-danger"><i class="fas fa-minus-circle"></i> Expenses</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php 
                                $total_expenses = 0;
                                foreach($financial_data['expenses'] as $item): 
                                    $total_expenses += $item['amount'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td class="text-right">KES <?php echo number_format($item['amount'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-danger font-weight-bold">
                                    <td>Total Expenses</td>
                                    <td class="text-right">KES <?php echo number_format($total_expenses, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-12">
                    <div class="alert <?php echo ($total_revenue - $total_expenses) >= 0 ? 'alert-success' : 'alert-danger'; ?>">
                        <h5 class="mb-0">
                            Net Income: KES <?php echo number_format($total_revenue - $total_expenses, 2); ?>
                            <small class="float-right">
                                Profit Margin: <?php echo $total_revenue > 0 ? number_format((($total_revenue - $total_expenses) / $total_revenue) * 100, 1) : 0; ?>%
                            </small>
                        </h5>
                    </div>
                </div>
            </div>
            
        <?php elseif($report_type == 'balance_sheet'): ?>
            <!-- Balance Sheet -->
            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-primary"><i class="fas fa-building"></i> Assets</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php 
;
                                $total_assets = 0;
                                foreach($financial_data['assets'] as $item): 
                                    $total_assets += $item['balance'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td class="text-right">KES <?php echo number_format($item['balance'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-primary font-weight-bold">
                                    <td>Total Assets</td>
                                    <td class="text-right">KES <?php echo number_format($total_assets, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <h6 class="text-warning"><i class="fas fa-exclamation-triangle"></i> Liabilities</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <?php 
                                $total_liabilities = 0;
                                foreach($financial_data['liabilities'] as $item): 
                                    $total_liabilities += $item['balance'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td class="text-right">KES <?php echo number_format($item['balance'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-warning font-weight-bold">
                                    <td>Total Liabilities</td>
                                    <td class="text-right">KES <?php echo number_format($total_liabilities, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <hr>
            
            <div class="row">
                <div class="col-12">
                    <div class="alert <?php echo ($total_assets - $total_liabilities) >= 0 ? 'alert-success' : 'alert-danger'; ?>">
                        <h5 class="mb-0">
                            Net Worth: KES <?php echo number_format($total_assets - $total_liabilities, 2); ?>
                            <small class="float-right">
                                Debt Ratio: <?php echo $total_assets > 0 ? number_format(($total_liabilities / $total_assets) * 100, 1) : 0; ?>%
                            </small>
                        </h5>
                    </div>
                </div>
            </div>
            
        <?php elseif($report_type == 'cash_flow'): ?>
            <!-- Cash Flow Statement -->
            <div class="row">
                <div class="col-12">
                    <h6 class="text-info"><i class="fas fa-money-bill-wave"></i> Operating Activities</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="text-right">Cash Inflows</th>
                                    <th class="text-right">Cash Outflows</th>
                                    <th class="text-right">Net Cash Flow</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
;
                                $total_inflows = 0;
                                $total_outflows = 0;
                                foreach($financial_data['operating'] as $item): 
                                    $total_inflows += $item['inflows'];
                                    $total_outflows += $item['outflows'];
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['account_name']); ?></td>
                                        <td class="text-right text-success">KES <?php echo number_format($item['inflows'], 2); ?></td>
                                        <td class="text-right text-danger">KES <?php echo number_format($item['outflows'], 2); ?></td>
                                        <td class="text-right <?php echo ($item['inflows'] - $item['outflows']) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                            KES <?php echo number_format($item['inflows'] - $item['outflows'], 2); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info font-weight-bold">
                                    <td>Total Operating Cash Flow</td>
                                    <td class="text-right text-success">KES <?php echo number_format($total_inflows, 2); ?></td>
                                    <td class="text-right text-danger">KES <?php echo number_format($total_outflows, 2); ?></td>
                                    <td class="text-right <?php echo ($total_inflows - $total_outflows) >= 0 ? 'text-success' : 'text-danger'; ?>">
                                        KES <?php echo number_format($total_inflows - $total_outflows, 2); ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>



<style>
@media print {
    .card-header, .btn, .form-control, .page-header {
        display: none !important;
    }
    
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    
    .card-body {
        padding: 0 !important;
    }
}
</style>

<?php include '../includes/admin/footer.php'; ?>