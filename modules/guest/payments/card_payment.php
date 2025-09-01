<?php
$page_title = 'Card Payment - Orlando International Resorts';
$page_description = 'Complete your luxury hotel booking with secure card payment';

session_start();
include("../../../db.php");

// Check if user is logged in or has valid session
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
$booking_ref = isset($_GET['booking_ref']) ? $_GET['booking_ref'] : '';
$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 0;

// Validate required parameters
if((!$booking_id && !$booking_ref) || !$amount) {
    header("Location: ../booking/luxury_booking.php");
    exit();
}

// Fetch booking details
$booking = null;
$bookings = [];
$multiple_rooms = false;

if ($booking_ref) {
    // Fetch all bookings with this reference for multiple rooms
    $booking_query = "SELECT * FROM roombook WHERE booking_ref = ? ORDER BY id ASC";
    $stmt = mysqli_prepare($con, $booking_query);
    mysqli_stmt_bind_param($stmt, "s", $booking_ref);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $bookings[] = $row;
    }
    
    if (empty($bookings)) {
        header("Location: ../booking/luxury_booking.php");
        exit();
    }
    
    $booking = $bookings[0]; // Use first booking for main details
    $multiple_rooms = count($bookings) > 1;
} else {
    // Fetch single booking by ID
    $booking_query = "SELECT * FROM roombook WHERE id = ?";
    $stmt = mysqli_prepare($con, $booking_query);
    mysqli_stmt_bind_param($stmt, "i", $booking_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $booking = mysqli_fetch_assoc($result);
    
    if (!$booking) {
        header("Location: ../booking/luxury_booking.php");
        exit();
    }
    
    $bookings = [$booking];
}

// Handle form submission for card payment
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // In a real implementation, you would integrate with a payment gateway like Stripe, PayPal, etc.
    // For demo purposes, we'll simulate the payment process
    
    $card_number = $_POST['card_number'] ?? '';
    $expiry = $_POST['expiry'] ?? '';
    $cvv = $_POST['cvv'] ?? '';
    $cardholder_name = $_POST['cardholder_name'] ?? '';
    
    // Parse expiry date (MM/YY format)
    $expiry_month = '';
    $expiry_year = '';
    if (preg_match('/^(\d{2})\/(\d{2})$/', $expiry, $matches)) {
        $expiry_month = $matches[1];
        $expiry_year = $matches[2];
    }
    
    // Basic validation
    $card_number_clean = str_replace(' ', '', $card_number);
    if (strlen($card_number_clean) < 16 || strlen($cvv) < 3 || empty($expiry_month) || empty($expiry_year)) {
        $error = "Please enter valid card details.";
    } else {
        // Simulate payment processing
        // Update booking status
        if ($booking_ref) {
            $update_booking = "UPDATE roombook SET stat = 'confirmed', payment_status = 'paid' WHERE booking_ref = ?";
            $stmt = mysqli_prepare($con, $update_booking);
            mysqli_stmt_bind_param($stmt, "s", $booking_ref);
            mysqli_stmt_execute($stmt);
            
            // Update room status for all rooms
            foreach ($bookings as $book) {
                $room_update = "UPDATE room_status SET status = 'occupied' WHERE room_name = ?";
                $stmt = mysqli_prepare($con, $room_update);
                mysqli_stmt_bind_param($stmt, "s", $book['Room']);
                mysqli_stmt_execute($stmt);
            }
        } else {
            $update_booking = "UPDATE roombook SET stat = 'confirmed', payment_status = 'paid' WHERE id = ?";
            $stmt = mysqli_prepare($con, $update_booking);
            mysqli_stmt_bind_param($stmt, "i", $booking_id);
            mysqli_stmt_execute($stmt);
            
            $room_update = "UPDATE room_status SET status = 'occupied' WHERE room_name = ?";
            $stmt = mysqli_prepare($con, $room_update);
            mysqli_stmt_bind_param($stmt, "s", $booking['Room']);
            mysqli_stmt_execute($stmt);
        }
        
        // Create payment record
        $payment_ref = 'CARD' . date('YmdHis') . rand(1000, 9999);
        $payment_query = "INSERT INTO payment (booking_ref, amount, payment_method, transaction_ref, status, created_at) VALUES (?, ?, 'card', ?, 'completed', NOW())";
        $stmt = mysqli_prepare($con, $payment_query);
        $booking_reference = $booking_ref ?: $booking['booking_ref'];
        mysqli_stmt_bind_param($stmt, "sds", $booking_reference, $amount, $payment_ref);
        mysqli_stmt_execute($stmt);
        
        // Redirect to confirmation
        $redirect_ref = $booking_ref ?: $booking['booking_ref'];
        header("Location: ../booking/booking_confirmation.php?booking_ref=$redirect_ref&payment=success");
        exit();
    }
}
?>
<?php include('../../../includes/guest/header.php'); ?>

<style>
        .main-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .payment-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
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
            padding: 30px;
        }
        
        .payment-methods {
            display: flex;
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .payment-method {
            flex: 1;
            padding: 15px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: #495057;
        }
        
        .payment-method:hover {
            text-decoration: none;
            color: #495057;
        }
        
        .payment-method.active {
            border-color: #667eea;
            background: #f8f9ff;
            color: #667eea;
        }
        
        .payment-method i {
            font-size: 1.5rem;
            display: block;
            margin-bottom: 8px;
        }
        
        .card-form {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 30px;
        }
        
        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-group {
            flex: 1;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .form-control.valid {
            border-color: #28a745;
            background-color: #f8fff9;
        }
        
        .form-control.invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-icon {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
            pointer-events: none;
        }
        
        .card-preview {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-bottom: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .card-preview::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
        }
        
        .card-number {
            font-size: 1.2rem;
            letter-spacing: 2px;
            margin-bottom: 15px;
            font-family: monospace;
        }
        
        .card-info {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
        }
        
        .cardholder-name {
            font-size: 0.9rem;
            text-transform: uppercase;
        }
        
        .card-expiry {
            font-size: 0.9rem;
            font-family: monospace;
        }
        
        .payment-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .summary-row.total {
            border-top: 2px solid #dee2e6;
            padding-top: 10px;
            font-weight: 600;
            font-size: 1.1rem;
        }
        
        .btn-pay {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 40px;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        
        .btn-pay:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.3);
        }
        
        .security-info {
            text-align: center;
            margin-top: 20px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        
        /* Guided Card Entry Styles */
        .card-entry-guide {
            margin-bottom: 25px;
        }
        
        .guide-steps {
            margin-bottom: 30px;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            padding: 15px;
            border-radius: 12px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .step.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.05);
        }
        
        .step.completed {
            background: #28a745;
            color: white;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            background: rgba(255,255,255,0.2);
        }
        
        .step.active .step-number,
        .step.completed .step-number {
            background: rgba(255,255,255,0.3);
        }
        
        .step-label {
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .guide-step {
            display: none;
            background: #f8f9fa;
            padding: 30px;
            border-radius: 15px;
            text-align: center;
        }
        
        .guide-step.active {
            display: block;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .step-header {
            margin-bottom: 25px;
        }
        
        .step-header h4 {
            margin: 0 0 10px 0;
            color: #2c3e50;
            font-size: 1.3rem;
        }
        
        .step-header p {
            margin: 0;
            color: #6c757d;
            font-size: 1rem;
        }
        
        .large-input {
            font-size: 1.2rem !important;
            padding: 15px 20px !important;
            text-align: center;
            border: 2px solid #e9ecef !important;
            border-radius: 12px !important;
            max-width: 400px;
            margin: 0 auto;
        }
        
        .large-input:focus {
            border-color: #667eea !important;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1) !important;
        }
        
        .large-input.valid {
            border-color: #28a745 !important;
            background-color: #f8fff9 !important;
        }
        
        .large-input.invalid {
            border-color: #dc3545 !important;
            background-color: #fff5f5 !important;
        }
        
        .input-help {
            margin-top: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .cvv-help {
            margin-top: 8px;
            color: #6c757d;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }
        
        .btn-next, .btn-back {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 20px 10px 0 10px;
        }
        
        .btn-next:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-next:not(:disabled):hover,
        .btn-back:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }
        
        .btn-back {
            background: #6c757d;
        }
        
        .step-navigation {
            margin-top: 25px;
        }
        
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
            }
            
            .payment-methods {
                flex-direction: column;
            }
        }
    </style>

<div class="main-content">
    <div class="payment-container">
        <div class="payment-header">
            <h2><i class="fa fa-credit-card"></i> Secure Card Payment</h2>
            <p style="margin: 10px 0 0 0; opacity: 0.9;">Complete your booking with secure card payment</p>
        </div>
        
        <div class="payment-content">
            <!-- Payment Method Toggle -->
            <div class="payment-methods">
                <a href="mpesa_payment.php?<?php echo $booking_ref ? "booking_ref=$booking_ref" : "booking_id=$booking_id"; ?>&amount=<?php echo $amount; ?>" class="payment-method">
                    <i class="fa fa-mobile"></i>
                    <div><strong>M-Pesa</strong></div>
                    <small>Mobile payment</small>
                </a>
                <div class="payment-method active">
                    <i class="fa fa-credit-card"></i>
                    <div><strong>Credit/Debit Card</strong></div>
                    <small>Visa, Mastercard</small>
                </div>
            </div>
            
            <?php if(isset($error)): ?>
                <div class="error-message">
                    <i class="fa fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <!-- Payment Summary -->
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
                <p><strong><?php echo $multiple_rooms ? 'Rooms' : 'Room'; ?>:</strong> 
                <?php 
                    if ($multiple_rooms) {
                        $room_names = array_map(function($b) { return $b['Room']; }, $bookings);
                        echo htmlspecialchars(implode(', ', $room_names), ENT_QUOTES, 'UTF-8');
                        echo " <small>(" . count($bookings) . " rooms)</small>";
                    } else {
                        echo htmlspecialchars($booking['Room'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
                    }
                ?>
                </p>
                <p><strong>Duration:</strong> <?php echo htmlspecialchars($booking['nodays'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?> night(s)</p>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>KES <?php echo number_format((float)$amount * 0.84, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Taxes & Fees:</span>
                    <span>KES <?php echo number_format((float)$amount * 0.16, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount:</span>
                    <span>KES <?php echo number_format((float)$amount, 2); ?></span>
                </div>
            </div>
            
            <!-- Card Payment Form -->
            <form method="POST" id="card-payment-form">
                <!-- Card Preview -->
                <div class="card-preview">
                    <div class="card-number" id="card-display">**** **** **** ****</div>
                    <div class="card-info">
                        <div class="cardholder-name" id="name-display">CARDHOLDER NAME</div>
                        <div class="card-expiry" id="expiry-display">MM/YY</div>
                    </div>
                </div>
                
                <!-- Guided Card Entry Steps -->
                <div class="card-entry-guide">
                    <div class="guide-steps">
                        <div class="step-indicator">
                            <div class="step active" data-step="1">
                                <span class="step-number">1</span>
                                <span class="step-label">Card Number</span>
                            </div>
                            <div class="step" data-step="2">
                                <span class="step-number">2</span>
                                <span class="step-label">Cardholder</span>
                            </div>
                            <div class="step" data-step="3">
                                <span class="step-number">3</span>
                                <span class="step-label">Expiry & CVV</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Step 1: Card Number -->
                    <div class="guide-step active" id="step-1">
                        <div class="step-header">
                            <h4><i class="fa fa-credit-card"></i> Enter Your Card Number</h4>
                            <p>Please enter your 16-digit card number</p>
                        </div>
                        <div class="form-group">
                            <input type="text" id="card_number" name="card_number" class="form-control large-input" 
                                   placeholder="1234 5678 9012 3456" maxlength="19" required
                                   oninput="formatCardNumber(this); validateCardNumber(this); updateCardDisplay(); checkStepCompletion(1)">
                            <div class="input-help">
                                <i class="fa fa-info-circle"></i> We accept Visa, Mastercard, and other major cards
                            </div>
                        </div>
                        <button type="button" class="btn-next" onclick="nextStep(2)" disabled>
                            Continue <i class="fa fa-arrow-right"></i>
                        </button>
                    </div>
                    
                    <!-- Step 2: Cardholder Name -->
                    <div class="guide-step" id="step-2">
                        <div class="step-header">
                            <h4><i class="fa fa-user"></i> Cardholder Name</h4>
                            <p>Enter the name as it appears on your card</p>
                        </div>
                        <div class="form-group">
                            <input type="text" id="cardholder_name" name="cardholder_name" class="form-control large-input" 
                                   placeholder="JOHN DOE" required style="text-transform: uppercase;"
                                   oninput="updateCardDisplay(); checkStepCompletion(2)">
                            <div class="input-help">
                                <i class="fa fa-info-circle"></i> Enter the full name exactly as shown on your card
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn-back" onclick="prevStep(1)">
                                <i class="fa fa-arrow-left"></i> Back
                            </button>
                            <button type="button" class="btn-next" onclick="nextStep(3)" disabled>
                                Continue <i class="fa fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Step 3: Expiry & CVV -->
                    <div class="guide-step" id="step-3">
                        <div class="step-header">
                            <h4><i class="fa fa-calendar"></i> Card Details</h4>
                            <p>Enter your card's expiry month/year (MM/YY) and security code</p>
                            <div style="margin-top: 10px; padding: 8px 15px; background: #e3f2fd; border-radius: 8px; font-size: 0.9rem; color: #1565c0;">
                                <i class="fa fa-lightbulb"></i> <strong>Example:</strong> If your card expires in December 2025, enter "12/25"
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="expiry">Expiry Date (Month/Year)</label>
                                <input type="text" id="expiry" name="expiry" class="form-control large-input" 
                                       placeholder="MM/YY (e.g., 12/25)" maxlength="5" required
                                       oninput="formatExpiry(this); updateCardDisplay(); checkStepCompletion(3)"
                                       onfocus="showExpiryExample(this)" onblur="hideExpiryExample(this)">
                                <div class="input-help" style="margin-top: 8px; color: #6c757d; font-size: 0.85rem; text-align: center;">
                                    <i class="fa fa-info-circle"></i> Enter month and year from your card (MM/YY format)
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="cvv">CVV</label>
                                <input type="text" id="cvv" name="cvv" class="form-control large-input" 
                                       placeholder="123" maxlength="4" required
                                       oninput="formatCVV(this); checkStepCompletion(3)">
                                <div class="cvv-help">
                                    <i class="fa fa-question-circle"></i> 
                                    <span>3-4 digits on the back of your card</span>
                                </div>
                            </div>
                        </div>
                        <div class="step-navigation">
                            <button type="button" class="btn-back" onclick="prevStep(2)">
                                <i class="fa fa-arrow-left"></i> Back
                            </button>
                            <button type="submit" class="btn-pay" id="pay-button">
                                <i class="fa fa-lock"></i> Pay KES <?php echo number_format((float)$amount); ?>
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            
            <div class="security-info">
                <i class="fa fa-shield-alt"></i> Your payment is secured with 256-bit SSL encryption<br>
                <small>We never store your card details on our servers</small>
            </div>
        </div>
    </div>
    
    <script>
        function formatCardNumber(input) {
            let value = input.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedInputValue = value.match(/.{1,4}/g)?.join(' ') || '';
            input.value = formattedInputValue;
        }
        
        function formatExpiry(input) {
            let value = input.value.replace(/\D/g, ''); // Remove non-digits
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            input.value = value;
            
            // Validate expiry date
            validateExpiry(input);
        }
        
        function formatCVV(input) {
            input.value = input.value.replace(/[^0-9]/g, '');
            validateCVV(input);
        }
        
        function validateCardNumber(input) {
            const value = input.value.replace(/\s/g, '');
            if (value.length >= 16 && /^\d+$/.test(value)) {
                input.classList.add('valid');
                input.classList.remove('invalid');
            } else if (value.length > 0) {
                input.classList.add('invalid');
                input.classList.remove('valid');
            } else {
                input.classList.remove('valid', 'invalid');
            }
        }
        
        function validateExpiry(input) {
            const value = input.value;
            const regex = /^(0[1-9]|1[0-2])\/\d{2}$/;
            
            // Remove any existing error message
            const existingError = input.parentNode.querySelector('.format-error');
            if (existingError) {
                existingError.remove();
            }
            
            if (regex.test(value)) {
                const [month, year] = value.split('/');
                const expiry = new Date(2000 + parseInt(year), parseInt(month) - 1);
                const now = new Date();
                
                if (expiry > now) {
                    input.classList.add('valid');
                    input.classList.remove('invalid');
                } else {
                    input.classList.add('invalid');
                    input.classList.remove('valid');
                    // Show error for expired card
                    showExpiryError(input, 'This card has expired. Please use a valid card.');
                }
            } else if (value.length > 0) {
                input.classList.add('invalid');
                input.classList.remove('valid');
                
                // Show format help for invalid format
                if (value.length >= 2) {
                    showExpiryError(input, 'Please use MM/YY format (e.g., 12/25)');
                }
            } else {
                input.classList.remove('valid', 'invalid');
            }
        }
        
        function showExpiryError(input, message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'format-error';
            errorDiv.style.cssText = 'color: #dc3545; font-size: 0.8rem; margin-top: 5px; text-align: center;';
            errorDiv.innerHTML = `<i class="fa fa-exclamation-triangle"></i> ${message}`;
            input.parentNode.appendChild(errorDiv);
        }

        function showExpiryExample(input) {
            // Remove any existing example
            const existingExample = input.parentNode.querySelector('.expiry-example');
            if (existingExample) {
                existingExample.remove();
            }
            
            const exampleDiv = document.createElement('div');
            exampleDiv.className = 'expiry-example';
            exampleDiv.style.cssText = 'position: absolute; top: 100%; left: 0; right: 0; background: #f8f9fa; border: 1px solid #dee2e6; border-top: none; border-radius: 0 0 8px 8px; padding: 10px; font-size: 0.8rem; z-index: 10;';
            exampleDiv.innerHTML = `
                <div style="text-align: center; color: #495057;">
                    <strong>Examples:</strong><br>
                    <span style="color: #28a745;">✓ 01/25</span> (January 2025) &nbsp;&nbsp;
                    <span style="color: #28a745;">✓ 12/26</span> (December 2026)<br>
                    <span style="color: #dc3545;">✗ 1/25</span> (use 01/25) &nbsp;&nbsp;
                    <span style="color: #dc3545;">✗ 01/2025</span> (use 01/25)
                </div>
            `;
            
            input.parentNode.style.position = 'relative';
            input.parentNode.appendChild(exampleDiv);
        }

        function hideExpiryExample(input) {
            const example = input.parentNode.querySelector('.expiry-example');
            if (example && input.value.length === 0) {
                setTimeout(() => {
                    if (example.parentNode) {
                        example.remove();
                    }
                }, 200);
            }
        }
        
        function validateCVV(input) {
            const value = input.value;
            if (value.length >= 3 && value.length <= 4 && /^\d+$/.test(value)) {
                input.classList.add('valid');
                input.classList.remove('invalid');
            } else if (value.length > 0) {
                input.classList.add('invalid');
                input.classList.remove('valid');
            } else {
                input.classList.remove('valid', 'invalid');
            }
        }

        function updateCardDisplay() {
            const cardNumber = document.getElementById('card_number').value || '**** **** **** ****';
            const cardholderName = document.getElementById('cardholder_name').value || 'CARDHOLDER NAME';
            const expiry = document.getElementById('expiry').value || 'MM/YY';
            
            document.getElementById('card-display').textContent = cardNumber.length > 0 ? cardNumber : '**** **** **** ****';
            document.getElementById('name-display').textContent = cardholderName;
            document.getElementById('expiry-display').textContent = expiry;
        }
        
        // Form submission handling
        document.getElementById('card-payment-form').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('pay-button');
            submitBtn.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Processing Payment...';
            submitBtn.disabled = true;
        });
        
        // Auto-uppercase cardholder name
        document.getElementById('cardholder_name').addEventListener('input', function(e) {
            this.value = this.value.toUpperCase();
        });
        
        // Guided card entry functions
        let currentStep = 1;
        
        function nextStep(step) {
            // Hide current step
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('completed');
            
            // Show next step
            currentStep = step;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            
            // Focus on the first input in the new step
            const firstInput = document.querySelector(`#step-${currentStep} input`);
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 300);
            }
        }
        
        function prevStep(step) {
            // Hide current step
            document.getElementById(`step-${currentStep}`).classList.remove('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('active');
            
            // Show previous step
            currentStep = step;
            document.getElementById(`step-${currentStep}`).classList.add('active');
            document.querySelector(`[data-step="${currentStep}"]`).classList.remove('completed');
            document.querySelector(`[data-step="${currentStep}"]`).classList.add('active');
            
            // Focus on the input in the previous step
            const input = document.querySelector(`#step-${currentStep} input`);
            if (input) {
                setTimeout(() => input.focus(), 300);
            }
        }
        
        function checkStepCompletion(step) {
            let isComplete = false;
            
            switch(step) {
                case 1:
                    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                    isComplete = cardNumber.length >= 16 && /^\d+$/.test(cardNumber);
                    break;
                case 2:
                    const cardholderName = document.getElementById('cardholder_name').value.trim();
                    isComplete = cardholderName.length >= 2;
                    break;
                case 3:
                    const expiry = document.getElementById('expiry').value;
                    const cvv = document.getElementById('cvv').value;
                    const expiryValid = /^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry);
                    const cvvValid = cvv.length >= 3 && cvv.length <= 4 && /^\d+$/.test(cvv);
                    isComplete = expiryValid && cvvValid;
                    break;
            }
            
            // Enable/disable next button
            const nextButton = document.querySelector(`#step-${step} .btn-next`);
            if (nextButton) {
                nextButton.disabled = !isComplete;
            }
            
            // Enable/disable pay button for step 3
            if (step === 3) {
                const payButton = document.getElementById('pay-button');
                if (payButton) {
                    payButton.disabled = !isComplete;
                }
            }
        }
        
        // Allow Enter key to move to next step
        document.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                
                if (currentStep === 1) {
                    const nextBtn = document.querySelector('#step-1 .btn-next');
                    if (!nextBtn.disabled) nextStep(2);
                } else if (currentStep === 2) {
                    const nextBtn = document.querySelector('#step-2 .btn-next');
                    if (!nextBtn.disabled) nextStep(3);
                } else if (currentStep === 3) {
                    const payBtn = document.getElementById('pay-button');
                    if (!payBtn.disabled) {
                        document.getElementById('card-payment-form').submit();
                    }
                }
            }
        });
        
        // Initialize card display on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateCardDisplay();
            // Focus on first input
            document.getElementById('card_number').focus();
        });
    </script>
    </div> <!-- Close main-content -->

<?php include('../../../includes/guest/footer.php'); ?>
