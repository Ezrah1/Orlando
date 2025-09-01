<?php
$page_title = 'Mpesa Order Payment';
$page_description = 'Orlando International Resorts - Mpesa Order Payment';

// Include database connection
require_once('../../../db.php');

// Get order details
$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$phone = isset($_GET['phone']) ? $_GET['phone'] : '';

if(!$order_id || !$amount) {
    header("Location: ../menu/menu_enhanced.php");
    exit();
}

// Get order details from database
$order_query = "SELECT * FROM food_orders WHERE id = $order_id";
$order_result = mysqli_query($con, $order_query);
$order = mysqli_fetch_assoc($order_result);

if(!$order) {
    header("Location: ../menu/menu_enhanced.php");
    exit();
}

// Handle M-Pesa payment submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mpesa_phone = mysqli_real_escape_string($con, $_POST['mpesa_phone']);
    $mpesa_amount = (float)$_POST['mpesa_amount'];
    
    // Generate M-Pesa transaction reference
    $transaction_ref = 'MPESA' . date('YmdHis') . rand(100, 999);
    
    // Simulate M-Pesa payment process
    // In production, this would integrate with actual M-Pesa API
    $payment_success = true; // Simulate successful payment
    
    if($payment_success) {
        // Update order status
        $update_order = "UPDATE food_orders SET status = 'confirmed', payment_status = 'paid' WHERE id = $order_id";
        mysqli_query($con, $update_order);
        
        // Redirect to confirmation
        header("Location: order_confirmation.php?order_number=" . $order['order_number']);
        exit();
    } else {
        $error = "Payment failed. Please try again.";
    }
}

// Include header after processing
include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');
?>

<!-- M-Pesa Payment Section -->
<div class="payment-section" style="padding: 80px 0; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container">
        <div class="row">
            <div class="col-md-6 col-md-offset-3">
                <div class="payment-container">
                    <div class="mpesa-logo">
                        <h2 style="color: #28a745;">M-Pesa Payment</h2>
                        <p>Complete your order payment</p>
                    </div>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <script>
                        $(document).ready(function() {
                            showNotification('<?php echo addslashes($error); ?>', 'error');
                        });
                        </script>
                    <?php endif; ?>
                    
                    <div class="payment-summary">
                        <h4>Order Summary</h4>
                        <p><strong>Order Number:</strong> <?php echo $order['order_number']; ?></p>
                        <p><strong>Amount:</strong> KES <?php echo number_format($amount, 0); ?></p>
                        <p><strong>Guest:</strong> <?php echo $order['guest_name']; ?></p>
                    </div>
                    
                    <form method="post" class="payment-form">
                        <div class="form-group">
                            <label for="mpesa_phone">M-Pesa Phone Number:</label>
                            <input type="tel" class="form-control" name="mpesa_phone" id="mpesa_phone" value="<?php echo htmlspecialchars($phone); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="mpesa_amount">Amount (KES):</label>
                            <input type="number" class="form-control" name="mpesa_amount" id="mpesa_amount" value="<?php echo $amount; ?>" readonly>
                        </div>
                        
                        <div class="payment-steps">
                            <h5>Payment Steps:</h5>
                            <div class="step">
                                <span class="step-number">1</span>
                                <span>Dial *234# on your phone</span>
                            </div>
                            <div class="step">
                                <span class="step-number">2</span>
                                <span>Select "Send Money"</span>
                            </div>
                            <div class="step">
                                <span class="step-number">3</span>
                                <span>Enter phone number: <strong>254742824006</strong></span>
                            </div>
                            <div class="step">
                                <span class="step-number">4</span>
                                <span>Enter amount: <strong>KES <?php echo number_format($amount, 0); ?></strong></span>
                            </div>
                            <div class="step">
                                <span class="step-number">5</span>
                                <span>Enter your M-Pesa PIN</span>
                            </div>
                            <div class="step">
                                <span class="step-number">6</span>
                                <span>Confirm payment</span>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-success btn-lg btn-block" id="paymentBtn">Confirm Payment</button>
                        </div>
                        
                        <div class="text-center">
                            <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php" class="btn btn-default">Cancel Order</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.payment-container {
    background: white;
    border-radius: 20px;
    padding: 40px;
    box-shadow: 0 15px 35px rgba(0,0,0,0.1);
}

.mpesa-logo {
    text-align: center;
    margin-bottom: 30px;
}

.payment-summary {
    background: #28a745;
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}

.payment-steps {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 10px;
    margin: 20px 0;
}

.step {
    display: flex;
    align-items: center;
    margin: 10px 0;
    padding: 10px;
    background: white;
    border-radius: 5px;
}

.step-number {
    background: #28a745;
    color: white;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-weight: bold;
}

.payment-form .form-group {
    margin-bottom: 20px;
}

.payment-form .btn {
    margin-top: 20px;
}

@media (max-width: 768px) {
    .payment-container {
        padding: 20px;
    }
}
</style>

<script>
$(document).ready(function() {
    // Payment form submission with loading state and notifications
    $('#mpesaForm').on('submit', function(e) {
        const submitBtn = $('#paymentBtn');
        const originalText = submitBtn.html();
        
        // Show loading state
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing Payment...').prop('disabled', true);
        
        // Show processing notification
        showNotification('Processing your M-Pesa payment...', 'info');
    });
    
    // Phone number formatting
    $('#phone').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');
        if (value.length > 0) {
            // Format as Kenya phone number
            if (value.startsWith('0')) {
                value = '254' + value.substring(1);
            }
            if (value.startsWith('254')) {
                value = '+' + value;
            }
            $(this).val(value);
        }
    });
    
    // Validate phone number
    $('#phone').on('blur', function() {
        const phone = $(this).val();
        const kenyanPhoneRegex = /^\+254[17]\d{8}$/;
        
        if (phone && !kenyanPhoneRegex.test(phone)) {
            showNotification('Please enter a valid Kenyan phone number (e.g., +254700000000)', 'error');
            $(this).focus();
        }
    });
});
</script>

<?php include('../../../includes/guest/footer.php');?>
