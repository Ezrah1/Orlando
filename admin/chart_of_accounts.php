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

$page_title = 'Chart of Accounts';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Chart of Accounts</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

include '../db.php';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_account':
            $account_code = mysqli_real_escape_string($con, $_POST['account_code']);
            $account_name = mysqli_real_escape_string($con, $_POST['account_name']);
            $account_type = mysqli_real_escape_string($con, $_POST['account_type']);
            $account_category = mysqli_real_escape_string($con, $_POST['account_category']);
            $parent_account_id = !empty($_POST['parent_account_id']) ? (int)$_POST['parent_account_id'] : 'NULL';
            $description = mysqli_real_escape_string($con, $_POST['description']);
            
            $sql = "INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_category, parent_account_id, description) 
                    VALUES ('$account_code', '$account_name', '$account_type', '$account_category', " . ($parent_account_id ? $parent_account_id : "NULL") . ", '$description')";
            mysqli_query($sql, "");
            break;
            
        case 'update_account':
            $account_id = (int)$_POST['account_id'];
            $account_code = mysqli_real_escape_string($con, $_POST['account_code']);
            $account_name = mysqli_real_escape_string($con, $_POST['account_name']);
            $account_type = mysqli_real_escape_string($con, $_POST['account_type']);
            $account_category = mysqli_real_escape_string($con, $_POST['account_category']);
            $parent_account_id = !empty($_POST['parent_account_id']) ? (int)$_POST['parent_account_id'] : 'NULL';
            $description = mysqli_real_escape_string($con, $_POST['description']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $sql = "UPDATE chart_of_accounts SET 
                    account_code = '$account_code',
                    account_name = '$account_name',
                    account_type = '$account_type',
                    account_category = '$account_category',
                    parent_account_id = " . ($parent_account_id ? $parent_account_id : "NULL") . ",
                    description = '$description',
                    is_active = $is_active
                    WHERE id = $account_id";
            mysqli_query($sql, "");
            break;
            
        case 'delete_account':
            $account_id = (int)$_POST['account_id'];
            $sql = "DELETE FROM chart_of_accounts WHERE id = $account_id";
            mysqli_query($sql, "");
            break;
    }
}

// Get accounts organized by type
$accounts_sql = "SELECT coa.*, 
                        parent.account_name as parent_account_name,
                        (SELECT COUNT(*) FROM general_ledger WHERE account_id = coa.id) as transaction_count
                 FROM chart_of_accounts coa 
                 LEFT JOIN chart_of_accounts parent ON coa.parent_account_id = parent.id
                 ORDER BY coa.account_type, coa.account_code";
$accounts_result = mysqli_query($accounts_sql, "");

// Get parent accounts for dropdown
$parent_accounts_sql = "SELECT id, account_code, account_name, account_type FROM chart_of_accounts WHERE is_active = 1 ORDER BY account_code";
$parent_accounts_result = mysqli_query($parent_accounts_sql, "");
?>


    
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12">
                        
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="row">
                    <div class="col-lg-12">
                        <button class="btn btn-primary" data-toggle="modal" data-target="#addAccountModal">
                            <i class="fa fa-plus"></i> Add New Account
                        </button>
                        <a href="accounting_dashboard.php" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </div>

                <!-- Chart of Accounts Display -->
                <div class="row">
                    <div class="col-lg-12">
                        <?php
                        $current_type = '';
                        while ($account = mysqli_fetch_assoc($accounts_result)) {
                            if ($account['account_type'] != $current_type) {
                                if ($current_type != '') echo '</div>';
                                $current_type = $account['account_type'];
                                echo '<div class="account-type-header">' . ucfirst($current_type) . 's</div>';
                                echo '<div class="account-type-' . $current_type . '">';
                            }
                        ?>
                            <div class="account-row account-type-<?php echo $account['account_type']; ?>">
                                <div class="row">
                                    <div class="col-md-2">
                                        <span class="account-code"><?php echo $account['account_code']; ?></span>
                                    </div>
                                    <div class="col-md-3">
                                        <strong><?php echo $account['account_name']; ?></strong>
                                        <?php if ($account['parent_account_name']): ?>
                                            <br><small class="text-muted">Parent: <?php echo $account['parent_account_name']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="label label-info"><?php echo ucfirst($account['account_category']); ?></span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="badge"><?php echo $account['transaction_count']; ?> transactions</span>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="label label-<?php echo $account['is_active'] ? 'success' : 'danger'; ?>">
                                            <?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </div>
                                    <div class="col-md-1">
                                        <div class="btn-group">
                                            <button class="btn btn-sm btn-default" onclick="editAccount(<?php echo $account['id']; ?>)">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <?php if ($account['transaction_count'] == 0): ?>
                                                <button class="btn btn-sm btn-danger" onclick="deleteAccount(<?php echo $account['id']; ?>)">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php if ($account['description']): ?>
                                    
                                <?php endif; ?>
                            </div>
                        <?php
                        }
                        if ($current_type != '') echo '</div>';
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Account Modal -->
    <div class="modal fade" id="addAccountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Add New Account</h4>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_account">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Account Code</label>
                            <input type="text" name="account_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Account Name</label>
                            <input type="text" name="account_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Account Type</label>
                            <select name="account_type" class="form-control" required>
                                <option value="">Select Type</option>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="revenue">Revenue</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account Category</label>
                            <input type="text" name="account_category" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Parent Account (Optional)</label>
                            <select name="parent_account_id" class="form-control">
                                <option value="">No Parent</option>
                                <?php while ($parent = mysqli_fetch_assoc($parent_accounts_result)): ?>
                                    <option value="<?php echo $parent['id']; ?>">
                                        <?php echo $parent['account_code'] . ' - ' . $parent['account_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="modal fade" id="editAccountModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Edit Account</h4>
                </div>
                <form method="POST" id="editAccountForm">
                    <input type="hidden" name="action" value="update_account">
                    <input type="hidden" name="account_id" id="edit_account_id">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Account Code</label>
                            <input type="text" name="account_code" id="edit_account_code" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Account Name</label>
                            <input type="text" name="account_name" id="edit_account_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Account Type</label>
                            <select name="account_type" id="edit_account_type" class="form-control" required>
                                <option value="asset">Asset</option>
                                <option value="liability">Liability</option>
                                <option value="equity">Equity</option>
                                <option value="revenue">Revenue</option>
                                <option value="expense">Expense</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Account Category</label>
                            <input type="text" name="account_category" id="edit_account_category" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Parent Account (Optional)</label>
                            <select name="parent_account_id" id="edit_parent_account_id" class="form-control">
                                <option value="">No Parent</option>
                                <?php 
                                mysqli_data_seek($parent_accounts_result, 0);
                                while ($parent = mysqli_fetch_assoc($parent_accounts_result)): 
                                ?>
                                    <option value="<?php echo $parent['id']; ?>">
                                        <?php echo $parent['account_code'] . ' - ' . $parent['account_name']; ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_active" id="edit_is_active"> Active
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>