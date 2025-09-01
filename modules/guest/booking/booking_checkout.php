<?php
$page_title = 'Checkout - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Booking Checkout';

// Load required files
require_once '../../../db.php';
require_once '../../../cart_manager.php';

include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');
// Get cart summary
$cart_summary = CartManager::getBookingCartSummary();

// Redirect if cart is empty
if($cart_summary['rooms_count'] == 0) {
    header("Location: booking_form.php");
    exit();
}

// Handle checkout submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
    $guest_email = mysqli_real_escape_string($con, $_POST['guest_email']);
    $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
    $guest_nationality = mysqli_real_escape_string($con, $_POST['guest_nationality']);
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    
    // Split guest name
    $name_parts = explode(' ', $guest_name, 2);
    $fname = $name_parts[0];
    $lname = isset($name_parts[1]) ? $name_parts[1] : '';
    
    // Generate booking reference
    $booking_ref = 'ORL' . date('Ymd') . rand(1000, 9999);
    
    $success = true;
    $booking_ids = [];
    
    // Create booking for each room in cart
    foreach($cart_summary['rooms'] as $room) {
        $check_in = $room['check_in'];
        $check_out = $room['check_out'];
        $days = $room['days'];
        $total = $room['total'];
        
        $booking_sql = "INSERT INTO roombook (booking_ref, FName, LName, Email, Phone, National, TRoom, cin, cout, nodays, payment_status, stat, created_at) 
                        VALUES ('$booking_ref', '$fname', '$lname', '$guest_email', '$guest_phone', '$guest_nationality', '" . $room['room_name'] . "', '$check_in', '$check_out', $days, 'pending', 'pending', NOW())";
        
        if(mysqli_query($con, $booking_sql)) {
            $booking_id = mysqli_insert_id($con);
            $booking_ids[] = $booking_id;
            
            // Update room status in room_status table
            $room_name = $room['room_name'];
            $room_status_check = "SELECT * FROM room_status WHERE room_name = '$room_name'";
            $room_status_result = mysqli_query($con, $room_status_check);
            
            if(mysqli_num_rows($room_status_result) > 0) {
                // Update existing room status
                $update_room_status = "UPDATE room_status SET current_status = 'occupied', updated_at = NOW() WHERE room_name = '$room_name'";
                mysqli_query($con, $update_room_status);
            } else {
                // Insert new room status record
                $insert_room_status = "INSERT INTO room_status (room_name, current_status, cleaning_status, updated_at) 
                                      VALUES ('$room_name', 'occupied', 'clean', NOW())";
                mysqli_query($con, $insert_room_status);
            }
            
            // Update housekeeping_status in the booking record
            $update_housekeeping = "UPDATE roombook SET housekeeping_status = 'occupied' WHERE id = $booking_id";
            mysqli_query($con, $update_housekeeping);
        } else {
            $success = false;
            $error = "Booking failed. Please try again.";
            break;
        }
    }
    
    if($success) {
        // Store booking details in session
        $_SESSION['booking_details'] = [
            'booking_ids' => $booking_ids,
            'booking_ref' => $booking_ref,
            'total_amount' => $cart_summary['grand_total'],
            'guest_phone' => $guest_phone,
            'payment_method' => $payment_method
        ];
        
        // Clear cart after successful booking
        CartManager::clearBookingCart();
        
        if($payment_method == 'mpesa') {
            header("Location: ../payments/mpesa_payment.php?booking_id=" . $booking_ids[0] . "&amount=" . $cart_summary['grand_total'] . "&phone=" . urlencode($guest_phone));
            exit();
        } else {
            foreach($booking_ids as $booking_id) {
                $update_sql = "UPDATE roombook SET status = 'confirmed', payment_status = 'paid' WHERE id = $booking_id";
                mysqli_query($update_sql, "");
            }
            
            header("Location: booking_confirmation.php?booking_ref=$booking_ref");
            exit();
        }
    }
}
?>

<div class="checkout-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="page-header text-center mb-5">
            <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                <i class="fa fa-credit-card"></i> Complete Your Booking
            </h1>
            <p class="lead text-white-50">Provide your details to confirm your stay</p>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="checkout-form" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <h3><i class="fa fa-user"></i> Guest Information</h3>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <form method="post" id="checkoutForm">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="guest_name">Full Name *</label>
                                    <input type="text" class="form-control" name="guest_name" id="guest_name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_email">Email Address *</label>
                                    <input type="email" class="form-control" name="guest_email" id="guest_email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_phone">Phone Number *</label>
                                    <input type="tel" class="form-control" name="guest_phone" id="guest_phone" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_nationality">Nationality *</label>
                                    <input type="text" class="form-control" name="guest_nationality" id="guest_nationality" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="payment-section" style="margin: 30px 0; padding: 25px; background: #f8f9fa; border-radius: 15px;">
                            <h4><i class="fa fa-credit-card"></i> Payment Method</h4>
                            <div class="payment-options" style="display: flex; gap: 15px;">
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="mpesa" value="mpesa" required>
                                    <label for="mpesa" style="display: block; padding: 15px; border: 2px solid #ddd; border-radius: 12px; text-align: center; cursor: pointer;">
                                        <i class="fa fa-mobile"></i> M-Pesa
                                    </label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="cash" value="cash" required>
                                    <label for="cash" style="display: block; padding: 15px; border: 2px solid #ddd; border-radius: 12px; text-align: center; cursor: pointer;">
                                        <i class="fa fa-money"></i> Cash
                                    </label>
                                </div>
                                <div class="payment-option">
                                    <input type="radio" name="payment_method" id="card" value="card" required>
                                    <label for="card" style="display: block; padding: 15px; border: 2px solid #ddd; border-radius: 12px; text-align: center; cursor: pointer;">
                                        <i class="fa fa-credit-card"></i> Card
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-navigation" style="display: flex; justify-content: space-between; align-items: center; margin-top: 30px;">
                            <a href="booking_form.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Rooms
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Confirm Booking
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="order-summary" style="background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden;">
                    <div class="summary-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 25px;">
                        <h4><i class="fa fa-list"></i> Order Summary</h4>
                    </div>
                    
                    <div class="summary-content" style="padding: 25px;">
                        <?php foreach($cart_summary['rooms'] as $room): ?>
                        <div class="summary-item" style="border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 15px;">
                            <div class="item-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <h6 style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($room['room_name']); ?></h6>
                                <span style="font-weight: 600; color: #667eea;">KES <?php echo number_format($room['total']); ?></span>
                            </div>
                            <div class="item-details" style="font-size: 0.85rem; color: #6c757d;">
                                <span><?php echo $room['check_in']; ?> - <?php echo $room['check_out']; ?></span>
                                <span><?php echo $room['days']; ?> night(s)</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals" style="background: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                        <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($cart_summary['subtotal']); ?></span>
                        </div>
                        <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Tax (16%):</span>
                            <span>KES <?php echo number_format($cart_summary['tax']); ?></span>
                        </div>
                        <div class="total-row" style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem; color: #2c3e50; border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 10px;">
                            <span>Total:</span>
                            <span>KES <?php echo number_format($cart_summary['grand_total']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Payment method selection styling
    $('input[name="payment_method"]').change(function() {
        $('.payment-option label').css({
            'border-color': '#ddd',
            'background': 'white',
            'color': '#333'
        });
        
        if($(this).is(':checked')) {
            $(this).next('label').css({
                'border-color': '#667eea',
                'background': '#667eea',
                'color': 'white'
            });
        }
    });
});
</script>

<?php include('../../../includes/guest/footer.php');?>
