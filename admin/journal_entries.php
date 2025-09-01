<?php
$page_title = 'Journal Entries';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page content starts here -->

<?php
// Display session alerts
display_session_alerts();
?>

<?php



// Check finance permissions - Directors have full access
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Director (role_id = 11) and Admin (role_id = 1) get automatic access
if ($user_role_id == 11 || $user_role_id == 1) {
    // Director and Admin bypass all checks
} else {
    $allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'Finance', 'Finance_Controller', 'Finance_Officer', 'director', 'ceo', 'super_admin', 'finance'];
    if (!in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), $allowed_roles) && 
        !user_has_permission($con, 'finance.transactions') && 
        !user_has_permission($con, 'finance.full_access')) {
        header('Location: access_denied.php');
        exit();
    }
}

$page_title = 'Journal Entries';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'create_entry':
            // Check if user has permission to create journal entries
            $create_allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'director', 'ceo', 'super_admin'];
            if ($user_role_id == 1 || $user_role_id == 11 || 
                in_array($user_role, $create_allowed_roles) || 
                in_array(strtolower($user_role), array_map('strtolower', $create_allowed_roles)) ||
                user_has_permission($con, 'finance.transactions')) {
                // User has permission
            } else {
                $error = "You don't have permission to create journal entries.";
                break;
            }
            
            $entry_date = $con->real_escape_string($_POST['entry_date']);
            $reference = $con->real_escape_string($_POST['reference']);
            $description = $con->real_escape_string($_POST['description']);
            $entry_type = $con->real_escape_string($_POST['entry_type']);
            
            // Generate entry number
            $entry_number = 'JE-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
            
            // Calculate totals
            $total_debit = 0;
            $total_credit = 0;
            
            // Process line items
            $line_items = [];
            for ($i = 0; $i < count($_POST['account_id']); $i++) {
                if (!empty($_POST['account_id'][$i])) {
                    $account_id = (int)$_POST['account_id'][$i];
                    $line_description = $con->real_escape_string($_POST['line_description'][$i]);
                    $debit_amount = (float)$_POST['debit_amount'][$i];
                    $credit_amount = (float)$_POST['credit_amount'][$i];
                    
                    $total_debit += $debit_amount;
                    $total_credit += $credit_amount;
                    
                    $line_items[] = [
                        'account_id' => $account_id,
                        'description' => $line_description,
                        'debit_amount' => $debit_amount,
                        'credit_amount' => $credit_amount
                    ];
                }
            }
            
            // Validate that debits equal credits
            if (abs($total_debit - $total_credit) > 0.01) {
                $error = "Debits and credits must be equal. Difference: " . ($total_debit - $total_credit);
            } else {
                // Insert journal entry with DRAFT status (requires approval)
                $sql = "INSERT INTO journal_entries (entry_number, entry_date, reference, description, total_debit, total_credit, entry_type, status, created_by) 
;
                        VALUES (?, ?, ?, ?, ?, ?, ?, 'draft', ?)";
                $stmt = $con->prepare($sql);
                $stmt->bind_param("ssssddsi", $entry_number, $entry_date, $reference, $description, $total_debit, $total_credit, $entry_type, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $journal_entry_id = $con->insert_id;
                    
                    // Insert line items
                    foreach ($line_items as $item) {
                        $detail_sql = "INSERT INTO journal_entry_details (journal_entry_id, account_id, description, debit_amount, credit_amount) 
;
                                      VALUES (?, ?, ?, ?, ?)";
                        $detail_stmt = $con->prepare($detail_sql);
                        $detail_stmt->bind_param("iisdd", $journal_entry_id, $item['account_id'], $item['description'], $item['debit_amount'], $item['credit_amount']);
                        $detail_stmt->execute();
                    }
                    
                    $success = "Journal entry created successfully with number: $entry_number (Status: Draft - Pending Approval)";
                } else {
                    $error = "Failed to create journal entry.";
                }
            }
            break;
            
        case 'post_entry':
            // Check if user has permission to approve and post journal entries
            $post_allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'director', 'ceo', 'super_admin'];
            if ($user_role_id == 1 || $user_role_id == 11 || 
                in_array($user_role, $post_allowed_roles) || 
                in_array(strtolower($user_role), array_map('strtolower', $post_allowed_roles)) ||
                user_has_permission($con, 'finance.full_access')) {
                // User has permission
            } else {
                $error = "You don't have permission to approve and post journal entries.";
                break;
            }
            
            $entry_id = (int)$_POST['entry_id'];
            
            // Verify the entry is in draft status and created by a different user
            $check_sql = "SELECT je.*, u.username as created_by_name 
                         FROM journal_entries je 
                         JOIN users u ON je.created_by = u.id 
;
                         WHERE je.id = ? AND je.status = 'draft'";
            $check_stmt = $con->prepare($check_sql);
            $check_stmt->bind_param("i", $entry_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows == 0) {
                $error = "Journal entry not found or not in draft status.";
                break;
            }
            
            $entry = $check_result->fetch_assoc();
            
            // Update status to posted
            $update_sql = "UPDATE journal_entries SET status = 'posted', posted_by = ?, posted_at = NOW() WHERE id = ?";
            $update_stmt = $con->prepare($update_sql);
            $update_stmt->bind_param("ii", $_SESSION['user_id'], $entry_id);
            
            if ($update_stmt->execute()) {
                // Post to general ledger
                $details_sql = "SELECT * FROM journal_entry_details WHERE journal_entry_id = ?";
                $details_stmt = $con->prepare($details_sql);
                $details_stmt->bind_param("i", $entry_id);
                $details_stmt->execute();
                $details_result = $details_stmt->get_result();
                
                while ($detail = $details_result->fetch_assoc()) {
                    // Insert into general ledger
                    $gl_sql = "INSERT INTO general_ledger (account_id, entry_date, description, debit_amount, credit_amount, reference, journal_entry_id, created_by) 
;
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                    $gl_stmt = $con->prepare($gl_sql);
                    $gl_stmt->bind_param("isssdsii", $detail['account_id'], $entry['entry_date'], $detail['description'], 
                                       $detail['debit_amount'], $detail['credit_amount'], $entry['entry_number'], $entry_id, $_SESSION['user_id']);
                    $gl_stmt->execute();
                }
                
                $success = "Journal entry posted successfully to general ledger.";
            } else {
                $error = "Failed to post journal entry.";
            }
            break;
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');

// Build where clause
$where_clause = "WHERE je.entry_date BETWEEN ? AND ?";
$params = [$date_from, $date_to];
$types = "ss";

if ($status_filter) {
    $where_clause .= " AND je.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

// Get journal entries
$entries_sql = "SELECT je.*, u.username as created_by_name, 
                       CASE 
                           WHEN je.posted_by IS NOT NULL THEN pu.username 
                           ELSE NULL 
                       END as posted_by_name
                FROM journal_entries je
                JOIN users u ON je.created_by = u.id
                LEFT JOIN users pu ON je.posted_by = pu.id
                $where_clause
                ORDER BY je.entry_date DESC, je.id DESC";

$entries_stmt = $con->prepare($entries_sql);
$entries_stmt->bind_param($types, ...$params);
$entries_stmt->execute();
$entries_result = $entries_stmt->get_result();

// Get chart of accounts for dropdown
$accounts_sql = "SELECT id, account_code, account_name, account_type FROM chart_of_accounts ORDER BY account_code";
$accounts_result = $con->query($accounts_sql);

// Header already included at the top
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Journal Entries</h1>
    <p class="page-subtitle">Create and manage accounting journal entries</p>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?php echo $success; ?>
    </div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger">
        <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
    </div>
<?php endif; ?>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-filter"></i> Filters</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row">
            <div class="col-md-3">
                <label for="status">Status</label>
                <select name="status" id="status" class="form-control">
                    <option value="">All Statuses</option>
                    <option value="draft" <?php echo $status_filter == 'draft' ? 'selected' : ''; ?>>Draft</option>
                    <option value="posted" <?php echo $status_filter == 'posted' ? 'selected' : ''; ?>>Posted</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="date_from">From Date</label>
                <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label for="date_to">To Date</label>
                <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-3">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <button class="btn btn-success btn-block" data-toggle="modal" data-target="#createEntryModal">
                            <i class="fas fa-plus"></i> New Journal Entry
                        </button>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="general_ledger.php" class="btn btn-info btn-block">
                            <i class="fas fa-list"></i> View General Ledger
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="chart_of_accounts.php" class="btn btn-warning btn-block">
                            <i class="fas fa-book"></i> Chart of Accounts
                        </a>
                    </div>
                    <div class="col-md-3 mb-3">
                        <a href="financial_reports.php" class="btn btn-secondary btn-block">
                            <i class="fas fa-chart-line"></i> Financial Reports
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Journal Entries List -->
<div class="card">
    <div class="card-header">
        <h5><i class="fas fa-list"></i> Journal Entries</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Reference</th>
                        <th>Description</th>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($entry = $entries_result->fetch_assoc()): ?>
                        <tr>
;
                            <td><strong><?php echo htmlspecialchars($entry['entry_number']); ?></strong></td>
                            <td><?php echo date('M d, Y', strtotime($entry['entry_date'])); ?></td>
                            <td><?php echo htmlspecialchars($entry['reference']); ?></td>
                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                            <td>
                                <span class="badge badge-info"><?php echo htmlspecialchars($entry['entry_type']); ?></span>
                            </td>
                            <td>KES <?php echo number_format($entry['total_debit'], 2); ?></td>
                            <td>
                                <?php
                                $status_class = 'badge-secondary';
                                switch($entry['status']) {
                                    case 'draft': $status_class = 'badge-warning'; break;
                                    case 'posted': $status_class = 'badge-success'; break;
                                    case 'cancelled': $status_class = 'badge-danger'; break;
                                }
                                ?>
                                <span class="badge <?php echo $status_class; ?>">
                                    <?php echo ucfirst($entry['status']); ?>
                                </span>
                            </td>
                            <td><?php echo htmlspecialchars($entry['created_by_name']); ?></td>
                            <td>
                                <div class="btn-group" role="group">
                                    <button class="btn btn-sm btn-primary" onclick="viewEntry(<?php echo $entry['id']; ?>)">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <?php 
                                    $button_allowed_roles = ['Admin', 'Director', 'CEO', 'Super_Admin', 'director', 'ceo', 'super_admin'];
                                    if($entry['status'] == 'draft' && (
                                        $user_role_id == 1 || $user_role_id == 11 || 
                                        in_array($user_role, $button_allowed_roles) || 
                                        in_array(strtolower($user_role), array_map('strtolower', $button_allowed_roles)) ||
                                        user_has_permission($con, 'finance.full_access')
                                    )): ?>
                                        <button class="btn btn-sm btn-success" onclick="postEntry(<?php echo $entry['id']; ?>)">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Create Journal Entry Modal -->
<div class="modal fade" id="createEntryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Journal Entry</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST" id="journalEntryForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_entry">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="entry_date">Entry Date</label>
                                <input type="date" name="entry_date" id="entry_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="entry_type">Entry Type</label>
                                <select name="entry_type" id="entry_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="adjustment">Adjustment</option>
                                    <option value="correction">Correction</option>
                                    <option value="closing">Closing</option>
                                    <option value="opening">Opening</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="reference">Reference</label>
                                <input type="text" name="reference" id="reference" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <input type="text" name="description" id="description" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <h6>Line Items</h6>
                    <div id="lineItems">
                        <div class="line-item row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Account</label>
                                    <select name="account_id[]" class="form-control" required>
                                        <option value="">Select Account</option>
                                        <?php while($account = $accounts_result->fetch_assoc()): ?>
;
                                            <option value="<?php echo $account['id']; ?>">
                                                <?php echo htmlspecialchars($account['account_code'] . ' - ' . $account['account_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>Description</label>
                                    <input type="text" name="line_description[]" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Debit</label>
                                    <input type="number" name="debit_amount[]" class="form-control debit-amount" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Credit</label>
                                    <input type="number" name="credit_amount[]" class="form-control credit-amount" step="0.01" min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-1">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="button" class="btn btn-danger btn-sm remove-line" style="display:none;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button type="button" class="btn btn-info btn-sm" onclick="addLineItem()">
                        <i class="fas fa-plus"></i> Add Line Item
                    </button>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Total Debit: <span id="totalDebit">KES 0.00</span></h6>
                        </div>
                        <div class="col-md-6">
                            <h6>Total Credit: <span id="totalCredit">KES 0.00</span></h6>
                        </div>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Note:</strong> Debits and credits must be equal. The entry will be created as a draft and requires approval before posting to the general ledger.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Entry</button>
                </div>
            </form>
        </div>
    </div>
</div>



<?php include '../includes/admin/footer.php'; ?>