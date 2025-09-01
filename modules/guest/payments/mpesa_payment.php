<?php
$page_title = 'M-Pesa Payment - Orlando International Resorts';
$page_description = 'Complete your luxury hotel booking with M-Pesa mobile payment';

// Include database connection
require_once('../../../db.php');

// Get booking details - support both booking_id and booking_ref
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking_ref = isset($_GET['booking_ref']) ? $_GET['booking_ref'] : '';
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;
$phone = isset($_GET['phone']) ? $_GET['phone'] : '';

if((!$booking_id && !$booking_ref) || !$amount) {
    header("Location: ../booking/booking_form.php");
    exit();
}

// Get booking details from database
if ($booking_ref) {
    // For luxury bookings with multiple rooms, get all bookings with this reference
    $booking_query = "SELECT * FROM roombook WHERE booking_ref = ? ORDER BY id ASC";
    $stmt = mysqli_prepare($con, $booking_query);
    mysqli_stmt_bind_param($stmt, 's', $booking_ref);
    mysqli_stmt_execute($stmt);
    $booking_result = mysqli_stmt_get_result($stmt);
    
    $bookings = [];
    while($row = mysqli_fetch_assoc($booking_result)) {
        $bookings[] = $row;
    }
    
    if(empty($bookings)) {
        header("Location: ../booking/booking_form.php");
        exit();
    }
    
    // Use first booking for main details, but we'll show all rooms
    $booking = $bookings[0];
    $multiple_rooms = count($bookings) > 1;
} else {
    // Single booking by ID (original functionality)
    $booking_query = "SELECT * FROM roombook WHERE id = ?";
    $stmt = mysqli_prepare($con, $booking_query);
    mysqli_stmt_bind_param($stmt, 'i', $booking_id);
    mysqli_stmt_execute($stmt);
    $booking_result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($booking_result);
    
    if(!$booking) {
        header("Location: ../booking/booking_form.php");
        exit();
    }
    
    $bookings = [$booking];
    $multiple_rooms = false;
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
        // Update booking status using correct column name
        // Update booking status for all rooms
        if ($booking_ref) {
            // Update all rooms in this booking reference
            $update_booking = "UPDATE roombook SET stat = 'confirmed' WHERE booking_ref = ?";
            $stmt = mysqli_prepare($con, $update_booking);
            mysqli_stmt_bind_param($stmt, 's', $booking_ref);
            mysqli_stmt_execute($stmt);
        } else {
            // Single booking update (original functionality)
            $update_booking = "UPDATE roombook SET stat = 'confirmed' WHERE id = ?";
            $stmt = mysqli_prepare($con, $update_booking);
            mysqli_stmt_bind_param($stmt, 'i', $booking_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Record payment in a separate table or add payment info to booking
        // Update all bookings with the same booking reference
        if ($booking_ref) {
            // Update all rooms in this booking reference
            $payment_update = "UPDATE roombook SET payment_status = 'paid' WHERE booking_ref = ?";
            $stmt = mysqli_prepare($con, $payment_update);
            mysqli_stmt_bind_param($stmt, 's', $booking_ref);
            mysqli_stmt_execute($stmt);
        } else {
            // Single booking update (original functionality)
            $payment_update = "UPDATE roombook SET payment_status = 'paid' WHERE id = ?";
            $stmt = mysqli_prepare($con, $payment_update);
            mysqli_stmt_bind_param($stmt, 'i', $booking_id);
            mysqli_stmt_execute($stmt);
        }
        
        // Update room status when payment is confirmed - handle multiple rooms
        foreach ($bookings as $room_booking) {
            $room_name = $room_booking['TRoom'];
            $room_status_check = "SELECT * FROM room_status WHERE room_name = '$room_name'";
            $room_status_result = mysqli_query($con, $room_status_check);
            
            if(mysqli_num_rows($room_status_result) > 0) {
                // Update existing room status to occupied
                $update_room_status = "UPDATE room_status SET current_status = 'occupied', updated_at = NOW() WHERE room_name = '$room_name'";
                mysqli_query($con, $update_room_status);
            } else {
                // Insert new room status record
                $insert_room_status = "INSERT INTO room_status (room_name, current_status, cleaning_status, updated_at) 
                                      VALUES ('$room_name', 'occupied', 'clean', NOW())";
                mysqli_query($con, $insert_room_status);
            }
        }
        
        // Update housekeeping_status in all booking records
        if ($booking_ref) {
            $update_housekeeping = "UPDATE roombook SET housekeeping_status = 'occupied' WHERE booking_ref = '$booking_ref'";
        } else {
            $update_housekeeping = "UPDATE roombook SET housekeeping_status = 'occupied' WHERE id = $booking_id";
        }
        mysqli_query($con, $update_housekeeping);
        
        // Redirect to confirmation
        header("Location: ../booking/booking_confirmation.php?booking_ref=" . urlencode($booking['booking_ref']));
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

<style>
    .main-content {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        min-height: 100vh;
        padding: 40px 0;
    }

    .payment-container {
        max-width: 900px;
        margin: 0 auto;
        background: white;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0,0,0,0.1);
        overflow: hidden;
    }

    .payment-header {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 30px;
        text-align: center;
    }

    .payment-header h2 {
        margin: 0;
        font-size: 2rem;
        font-weight: 300;
    }

    .payment-content {
        padding: 40px;
    }

    .payment-summary {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 15px;
        margin-bottom: 30px;
    }

    .payment-summary h4 {
        margin-bottom: 20px;
        color: #2c3e50;
    }

    .payment-steps {
        background: #f8f9fa;
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
    }

    .step {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }

    .step:last-child {
        margin-bottom: 0;
    }

    .step-number {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-right: 20px;
        font-size: 1.1rem;
    }

    .btn {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        border: none;
        padding: 15px 30px;
        border-radius: 10px;
        color: white;
        font-weight: 600;
        font-size: 1.1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: block;
        width: 100%;
        text-align: center;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
    }

    .alert {
        padding: 15px;
        border-radius: 10px;
        margin-bottom: 20px;
    }

    .alert-danger {
        background: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }

    .alert-success {
        background: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    /* Payment Method Toggle Styles */
    .payment-method {
        transition: all 0.3s ease;
    }

    .payment-method:hover {
        border-color: #667eea !important;
        background-color: #f8f9ff !important;
        color: #667eea !important;
        text-decoration: none !important;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.2);
    }

    .payment-method.active {
        border-color: #667eea !important;
        background-color: #f8f9ff !important;
        color: #667eea !important;
        box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
    }

    a.payment-method:hover,
    a.payment-method:focus {
        text-decoration: none;
        color: #667eea !important;
    }

    @media (max-width: 768px) {
        .payment-container {
            margin: 20px;
            border-radius: 15px;
        }
        
        .payment-content {
            padding: 25px;
        }
        
        .payment-header {
            padding: 20px;
        }
        
        .payment-header h2 {
            font-size: 1.5rem;
        }
    }
</style>

<div class="main-content">
    <div class="container">
        <div class="payment-container">
            <div class="payment-header">
                <h2><i class="fa fa-mobile"></i> M-Pesa Payment</h2>
                <p style="margin: 10px 0 0 0; opacity: 0.9;">Complete your luxury hotel booking with M-Pesa</p>
            </div>
            
            <div class="payment-content">

            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Payment Method Toggle -->
            <div class="payment-methods-toggle" style="display: flex; gap: 15px; margin-bottom: 30px;">
                <div class="payment-method active" style="flex: 1; padding: 15px; border: 2px solid #667eea; border-radius: 12px; text-align: center; cursor: pointer; background: #f8f9ff; color: #667eea; transition: all 0.3s ease;">
                    <i class="fa fa-mobile" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i>
                    <div><strong>M-Pesa</strong></div>
                    <small>Mobile payment</small>
                </div>
                <a href="card_payment.php?<?php echo $booking_ref ? "booking_ref=$booking_ref" : "booking_id=$booking_id"; ?>&amount=<?php echo $amount; ?>" 
                   class="payment-method" style="flex: 1; padding: 15px; border: 2px solid #e9ecef; border-radius: 12px; text-align: center; cursor: pointer; background: #ffffff; color: #495057; transition: all 0.3s ease; text-decoration: none;">
                    <i class="fa fa-credit-card" style="font-size: 1.5rem; display: block; margin-bottom: 8px;"></i>
                    <div><strong>Credit/Debit Card</strong></div>
                    <small>Visa, Mastercard</small>
                </a>
            </div>

            <div class="payment-summary">
                <h4>💰 Payment Summary</h4>
                <p><strong>Booking Reference:</strong> <?php echo htmlspecialchars($booking['booking_ref'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></p>
                <p><strong>Guest:</strong> <?php 
                    // Build guest name from available fields
                    $guest_name = '';
                    if (isset($booking['Title']) && isset($booking['FName']) && isset($booking['LName'])) {
                        $guest_name = trim($booking['Title'] . ' ' . $booking['FName'] . ' ' . $booking['LName']);
                    } elseif (isset($booking['name'])) {
                        $guest_name = $booking['name'];
                    } elseif (isset($booking['FName'])) {
                        $guest_name = $booking['FName'];
                    }
                    echo htmlspecialchars($guest_name ?: 'N/A', ENT_QUOTES, 'UTF-8'); 
                ?></p>
                <p><strong><?php echo $multiple_rooms ? 'Rooms:' : 'Room:'; ?></strong> 
                    <?php 
                    if ($multiple_rooms) {
                        echo '<br>';
                        foreach ($bookings as $index => $room_booking) {
                            $room_name = $room_booking['TRoom'] ?? $room_booking['room_name'] ?? 'N/A';
                            echo ($index + 1) . '. ' . htmlspecialchars($room_name, ENT_QUOTES, 'UTF-8');
                            if ($index < count($bookings) - 1) echo '<br>';
                        }
                    } else {
                        $room_name = $booking['TRoom'] ?? $booking['room_name'] ?? 'N/A';
                        echo htmlspecialchars($room_name, ENT_QUOTES, 'UTF-8');
                    }
                    ?>
                </p>
                <p><strong>Duration:</strong> <?php echo htmlspecialchars($booking['nodays'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?> night(s)</p>
                <?php if ($multiple_rooms): ?>
                <p><strong>Total Rooms:</strong> <?php echo count($bookings); ?></p>
                <?php endif; ?>
                <p><strong>Amount:</strong> KES <?php echo number_format((float)$amount, 2); ?></p>
            </div>

            <form method="POST" class="payment-steps">
                <h4>📱 M-Pesa Payment Steps</h4>
                
                <div class="step">
                    <div class="step-number">1</div>
                    <div>
                        <strong>Enter your M-Pesa phone number</strong><br>
                        <small>This should be the number registered with M-Pesa</small>
                    </div>
                </div>

                <div class="form-group">
                    <label>M-Pesa Phone Number *</label>
                    <input type="tel" name="mpesa_phone" class="form-control" value="<?php echo htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'); ?>" placeholder="254700123456" required>
                    <small class="text-muted">Format: 254700123456 (without + or spaces)</small>
                </div>

                <div class="form-group">
                    <label>Amount (KES) *</label>
                    <input type="number" name="mpesa_amount" class="form-control" value="<?php echo htmlspecialchars($amount, ENT_QUOTES, 'UTF-8'); ?>" readonly>
                </div>

                <div class="step">
                    <div class="step-number">2</div>
                    <div>
                        <strong>Click "Pay with M-Pesa"</strong><br>
                        <small>You'll receive an M-Pesa prompt on your phone</small>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">3</div>
                    <div>
                        <strong>Enter your M-Pesa PIN</strong><br>
                        <small>Complete the payment on your phone</small>
                    </div>
                </div>

                <div class="step">
                    <div class="step-number">4</div>
                    <div>
                        <strong>Receive confirmation</strong><br>
                        <small>You'll get instant booking confirmation</small>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-lg btn-block" style="margin-top: 20px;">
                    <i class="fa fa-mobile"></i> Pay with M-Pesa - KES <?php echo number_format((float)$amount); ?>
                </button>
            </form>

            <div class="text-center" style="margin-top: 20px;">
                <p><small>By clicking "Pay with M-Pesa", you agree to our terms and conditions</small></p>
                <a href="../booking/luxury_booking.php" class="btn btn-link">← Back to Booking</a>
            </div>
            </div> <!-- Close payment-content -->
        </div> <!-- Close payment-container -->
    </div> <!-- Close container -->
</div> <!-- Close main-content -->

    <script src="../../../js/jquery-2.1.4.min.js"></script>
    <script src="../../../js/bootstrap-3.1.1.min.js"></script>
    <script>
        // Simulate M-Pesa payment process
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing Payment...';
            submitBtn.disabled = true;
            
            // Simulate payment processing
            setTimeout(() => {
                // Submit the form
                this.submit();
            }, 2000);
        });
    </script>

<?php include('../../../includes/guest/footer.php'); ?>
