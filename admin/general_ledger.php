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

// Include database connection
include 'db.php';

$page_title = 'General Ledger';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">General Ledger</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

include '../db.php';

// Handle filters
$account_filter = $_GET['account_id'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$reference_type = $_GET['reference_type'] ?? '';

// Build WHERE clause
$where_conditions = [];
if ($account_filter) {
    $where_conditions[] = "gl.account_id = " . (int)$account_filter;
}
if ($date_from) {
    $where_conditions[] = "gl.entry_date >= '$date_from'";
}
if ($date_to) {
    $where_conditions[] = "gl.entry_date <= '$date_to'";
}
if ($reference_type) {
    $where_conditions[] = "gl.reference_type = '$reference_type'";
}

$where_clause = $where_conditions ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get ledger entries
$ledger_sql = "SELECT gl.*, 
                       coa.account_code, 
                       coa.account_name, 
                       coa.account_type,
                       u.username as created_by_name
                FROM general_ledger gl
                JOIN chart_of_accounts coa ON gl.account_id = coa.id
                LEFT JOIN users u ON gl.created_by = u.id
                $where_clause
                ORDER BY gl.entry_date DESC, gl.created_at DESC";
$ledger_result = mysqli_query($ledger_sql, "");

// Get accounts for filter dropdown
$accounts_sql = "SELECT id, account_code, account_name FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code";
$accounts_result = mysqli_query($accounts_sql, "");

// Calculate totals
$total_debit = 0;
$total_credit = 0;
$ledger_entries = [];
while ($entry = mysqli_fetch_assoc($ledger_result)) {
    $ledger_entries[] = $entry;
    $total_debit += $entry['debit_amount'];
    $total_credit += $entry['credit_amount'];
}
?>


    
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        
                    </div>
                </div>

                <!-- Filters -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Filters</h4>
                            </div>
                            <div class="panel-body">
                                <form method="GET" class="form-inline">
                                    <div class="form-group">
                                        <label>Account:</label>
                                        <select name="account_id" class="form-control">
                                            <option value="">All Accounts</option>
                                            <?php while ($account = mysqli_fetch_assoc($accounts_result)): ?>
                                                <option value="<?php echo $account['id']; ?>" <?php echo $account_filter == $account['id'] ? 'selected' : ''; ?>>
                                                    <?php echo $account['account_code'] . ' - ' . $account['account_name']; ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label>From Date:</label>
                                        <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>To Date:</label>
                                        <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                    </div>
                                    <div class="form-group">
                                        <label>Reference Type:</label>
                                        <select name="reference_type" class="form-control">
                                            <option value="">All Types</option>
                                            <option value="booking" <?php echo $reference_type == 'booking' ? 'selected' : ''; ?>>Booking</option>
                                            <option value="food_order" <?php echo $reference_type == 'food_order' ? 'selected' : ''; ?>>Food Order</option>
                                            <option value="bar_order" <?php echo $reference_type == 'bar_order' ? 'selected' : ''; ?>>Bar Order</option>
                                            <option value="laundry_order" <?php echo $reference_type == 'laundry_order' ? 'selected' : ''; ?>>Laundry Order</option>
                                            <option value="maintenance" <?php echo $reference_type == 'maintenance' ? 'selected' : ''; ?>>Maintenance</option>
                                            <option value="inventory" <?php echo $reference_type == 'inventory' ? 'selected' : ''; ?>>Inventory</option>
                                            <option value="payroll" <?php echo $reference_type == 'payroll' ? 'selected' : ''; ?>>Payroll</option>
                                            <option value="manual" <?php echo $reference_type == 'manual' ? 'selected' : ''; ?>>Manual</option>
                                            <option value="adjustment" <?php echo $reference_type == 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="btn btn-primary">Filter</button>
                                    <a href="general_ledger.php" class="btn btn-default">Clear</a>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="alert alert-info">
                            <strong>Summary:</strong> 
                            Total Debit: <span class="debit">KES <?php echo number_format($total_debit, 2); ?></span> | 
                            Total Credit: <span class="credit">KES <?php echo number_format($total_credit, 2); ?></span> | 
                            Net: <strong>KES <?php echo number_format($total_debit - $total_credit, 2); ?></strong>
                        </div>
                    </div>
                </div>

                <!-- General Ledger Entries -->
                <div class="row">
                    <div class="col-lg-12">
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4>Ledger Entries</h4>
                            </div>
                            <div class="panel-body">
                                <?php if (!empty($ledger_entries)): ?>
                                    <?php foreach ($ledger_entries as $entry): ?>
                                        <div class="ledger-row account-type-<?php echo $entry['account_type']; ?>">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <strong><?php echo date('M d, Y', strtotime($entry['entry_date'])); ?></strong><br>
                                                    <small class="text-muted"><?php echo $entry['account_code']; ?></small>
                                                </div>
                                                <div class="col-md-3">
                                                    <strong><?php echo $entry['account_name']; ?></strong><br>
                                                    <small class="text-muted"><?php echo ucfirst($entry['reference_type']); ?></small>
                                                </div>
                                                <div class="col-md-4">
                                                    <?php echo $entry['description']; ?>
                                                </div>
                                                <div class="col-md-2">
                                                    <span class="debit"><?php echo $entry['debit_amount'] > 0 ? 'KES ' . number_format($entry['debit_amount'], 2) : ''; ?></span><br>
                                                    <span class="credit"><?php echo $entry['credit_amount'] > 0 ? 'KES ' . number_format($entry['credit_amount'], 2) : ''; ?></span>
                                                </div>
                                                <div class="col-md-1">
                                                    <strong>KES <?php echo number_format($entry['balance'], 2); ?></strong>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="alert alert-info">No ledger entries found.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row">
                    <div class="col-lg-12">
                        <a href="accounting_dashboard.php" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                        <button class="btn btn-success" onclick="window.print()">
                            <i class="fa fa-print"></i> Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>