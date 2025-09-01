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

$page_title = 'Transactions';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Transactions</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

// Get all payment transactions
$transactions_query = "SELECT 
    p.*
    FROM payment p
    ORDER BY p.id DESC";
$transactions_result = mysqli_query($con, $transactions_query);
?>

<!-- Transactions Table -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Payment Transactions</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Guest Name</th>
                                <th>Room</th>
                                <th>Amount</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($transactions_result) > 0): ?>
                                <?php while($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                <tr>
                                    <td>#<?php echo $transaction['id']; ?></td>
                                    <td><?php echo htmlspecialchars($transaction['FName'] . ' ' . $transaction['LName']); ?></td>
                                    <td><?php echo htmlspecialchars($transaction['troom']); ?></td>
                                    <td>KES <?php echo number_format($transaction['ttot'], 2); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($transaction['created_at'] ?? 'now')); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary">View Details</button>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center">No transactions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JS Scripts-->

<?php include '../includes/admin/footer.php'; ?>