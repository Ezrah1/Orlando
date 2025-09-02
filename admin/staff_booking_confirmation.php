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

$booking_ref = isset($_GET['booking_ref']) ? $_GET['booking_ref'] : '';

if(!$booking_ref) {
    header("Location: staff_booking.php");
    exit();
}

// Get booking details
$booking_query = "SELECT * FROM roombook WHERE booking_ref = '$booking_ref'";
$booking_result = mysqli_query($con, $booking_query);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: staff_booking.php");
    exit();
}

// Handle payment processing
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_payment'])) {
    $payment_amount = (float)$_POST['payment_amount'];
    $transaction_ref = mysqli_real_escape_string($con, $_POST['transaction_ref']);
    $payment_notes = mysqli_real_escape_string($con, $_POST['payment_notes']);
    
    // For now, just update the payment status in roombook table
    // In a future update, we can integrate with the full payment system
    $update_sql = "UPDATE roombook SET payment_status = 'paid' WHERE booking_ref = '$booking_ref'";
    
    if(mysqli_query($con, $update_sql)) {
        $success = "Payment processed successfully!";
        // Refresh booking data
        $booking_result = mysqli_query($con, $booking_query);
        $booking = mysqli_fetch_assoc($booking_result);
    } else {
        $error = "Payment processing failed. Please try again.";
    }
}

// Get room price for total calculation
$room_query = "SELECT base_price FROM named_rooms WHERE room_name = '" . mysqli_real_escape_string($con, $booking['TRoom']) . "'";
$room_result = mysqli_query($con, $room_query);
$room_data = mysqli_fetch_assoc($room_result);
$room_price = $room_data['base_price'] ?? 0;
$total_amount = $room_price * $booking['nodays'];

$page_title = 'Staff Booking Confirmation';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Booking Confirmation</h1>
            <p class="page-subtitle">Reference: <?php echo $booking['booking_ref']; ?></p>
        </div>
        <div>
            <a href="staff_booking.php" class="btn btn-outline-secondary">
                <i class="fas fa-plus me-2"></i>New Booking
            </a>
            <a href="booking.php" class="btn btn-outline-info">
                <i class="fas fa-list me-2"></i>View All Bookings
            </a>
        </div>
    </div>
</div>

<?php
// Display alerts
display_session_alerts();
if (isset($success)) {
    echo render_alert($success, 'success');
}
if (isset($error)) {
    echo render_alert($error, 'danger');
}
?>

<div class="row">
    <!-- Booking Details -->
    <div class="col-md-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-check-circle me-2"></i>Booking Confirmed
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Guest Information</h6>
                        <div class="mb-2">
                            <strong>Name:</strong> <?php echo htmlspecialchars($booking['FName'] . ' ' . $booking['LName']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Email:</strong> <?php echo htmlspecialchars($booking['Email'] ?? 'N/A'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Phone:</strong> <?php echo htmlspecialchars($booking['Phone'] ?? 'N/A'); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Nationality:</strong> <?php echo htmlspecialchars($booking['National'] ?? 'N/A'); ?>
                        </div>
                        <?php if (!empty($booking['id_number'])): ?>
                        <div class="mb-2">
                            <strong>ID/Passport:</strong> <?php echo htmlspecialchars($booking['id_number']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">Stay Details</h6>
                        <div class="mb-2">
                            <strong>Room:</strong> <?php echo htmlspecialchars($booking['TRoom']); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Check-in:</strong> <?php echo date('d M Y', strtotime($booking['cin'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Check-out:</strong> <?php echo date('d M Y', strtotime($booking['cout'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong>Duration:</strong> <?php echo $booking['nodays']; ?> night(s)
                        </div>
                        <div class="mb-2">
                            <strong>Payment Status:</strong> 
                            <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <?php if (!empty($booking['staff_notes'])): ?>
                <div class="mt-3">
                    <h6 class="text-primary mb-2">Staff Notes</h6>
                    <div class="bg-light p-3 rounded">
                        <?php echo nl2br(htmlspecialchars($booking['staff_notes'])); ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Payment & Actions -->
    <div class="col-md-4">
        <!-- Payment Status -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-primary text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-credit-card me-2"></i>Payment Status
                </h6>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <h4 class="text-success">KES <?php echo number_format($total_amount, 2); ?></h4>
                    <small class="text-muted">Total Amount</small>
                </div>
                
                <div class="mb-3">
                    <strong>Current Status:</strong>
                    <span class="badge bg-<?php echo $booking['payment_status'] == 'paid' ? 'success' : 'warning'; ?> ms-2">
                        <?php echo ucfirst($booking['payment_status'] ?? 'pending'); ?>
                    </span>
                </div>
                
                <?php if ($booking['payment_status'] != 'paid'): ?>
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#paymentModal">
                    <i class="fas fa-credit-card me-2"></i>Process Payment
                </button>
                <?php else: ?>
                <div class="alert alert-success mb-0">
                    <i class="fas fa-check-circle me-2"></i>Payment Completed
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h6 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button onclick="printConfirmation()" class="btn btn-outline-primary">
                        <i class="fas fa-print me-2"></i>Print Confirmation
                    </button>
                    <button onclick="printInvoice()" class="btn btn-outline-success">
                        <i class="fas fa-file-invoice me-2"></i>Print Invoice
                    </button>
                    <a href="whatsapp://send?phone=254700123456&text=Booking Confirmation: <?php echo $booking['booking_ref']; ?>" 
                       class="btn btn-outline-success">
                        <i class="fab fa-whatsapp me-2"></i>Share via WhatsApp
                    </a>
                    <a href="mailto:<?php echo htmlspecialchars($booking['Email'] ?? ''); ?>?subject=Booking Confirmation <?php echo $booking['booking_ref']; ?>" 
                       class="btn btn-outline-info">
                        <i class="fas fa-envelope me-2"></i>Send Email
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="payment_amount" class="form-label">Amount (KES) *</label>
                        <input type="number" name="payment_amount" id="payment_amount" 
                               class="form-control" value="<?php echo $total_amount; ?>" 
                               step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="transaction_ref" class="form-label">Transaction Reference</label>
                        <input type="text" name="transaction_ref" id="transaction_ref" 
                               class="form-control" placeholder="e.g., MPESA-123456789">
                    </div>
                    
                    <div class="mb-3">
                        <label for="payment_notes" class="form-label">Payment Notes</label>
                        <textarea name="payment_notes" id="payment_notes" 
                                  class="form-control" rows="3" 
                                  placeholder="Any additional payment details..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="process_payment" class="btn btn-primary">
                        <i class="fas fa-check me-2"></i>Process Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function printConfirmation() {
    // Create a new window for printing
    const printWindow = window.open('', '_blank');
    const content = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Booking Confirmation - <?php echo $booking['booking_ref']; ?></title>
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 20px; }
                .section { margin-bottom: 20px; }
                .section h3 { color: #333; border-bottom: 1px solid #ccc; padding-bottom: 5px; }
                .detail { margin: 10px 0; }
                .detail strong { display: inline-block; width: 150px; }
                .total { font-size: 18px; font-weight: bold; text-align: center; background: #f0f0f0; padding: 15px; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>Orlando International Resorts</h1>
                <h2>Booking Confirmation</h2>
                <h3>Reference: <?php echo $booking['booking_ref']; ?></h3>
            </div>
            
            <div class="section">
                <h3>Guest Information</h3>
                <div class="detail"><strong>Name:</strong> <?php echo $booking['FName'] . ' ' . $booking['LName']; ?></div>
                <div class="detail"><strong>Email:</strong> <?php echo $booking['Email'] ?? 'N/A'; ?></div>
                <div class="detail"><strong>Phone:</strong> <?php echo $booking['Phone'] ?? 'N/A'; ?></div>
                <div class="detail"><strong>Nationality:</strong> <?php echo $booking['National'] ?? 'N/A'; ?></div>
            </div>
            
            <div class="section">
                <h3>Stay Details</h3>
                <div class="detail"><strong>Room:</strong> <?php echo $booking['TRoom']; ?></div>
                <div class="detail"><strong>Check-in:</strong> <?php echo date('d M Y', strtotime($booking['cin'])); ?></div>
                <div class="detail"><strong>Check-out:</strong> <?php echo date('d M Y', strtotime($booking['cout'])); ?></div>
                <div class="detail"><strong>Duration:</strong> <?php echo $booking['nodays']; ?> night(s)</div>
            </div>
            
            <div class="total">
                Total Amount: KES <?php echo number_format($total_amount, 2); ?>
            </div>
            
            <div style="text-align: center; margin-top: 30px; font-size: 12px; color: #666;">
                <p>Thank you for choosing Orlando International Resorts!</p>
                <p>Show this confirmation at check-in</p>
            </div>
        </body>
        </html>
    `;
    
    printWindow.document.write(content);
    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
}

function printInvoice() {
    // Similar to printConfirmation but with invoice formatting
    printConfirmation(); // For now, same as confirmation
}

// Auto-generate transaction reference
document.addEventListener('DOMContentLoaded', function() {
    const transactionRef = document.getElementById('transaction_ref');
    if (transactionRef) {
        transactionRef.value = 'PAY-' + Date.now();
    }
});
</script>

<?php include '../includes/admin/footer.php'; ?>
