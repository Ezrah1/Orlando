<?php
$page_title = 'Checkout - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Order Checkout';

// Database connection
require_once '../../../db.php';

// Check if cart is empty
if (!isset($_SESSION['order_cart']) || empty($_SESSION['order_cart'])) {
    header("Location: menu_enhanced.php");
    exit();
}

// Calculate totals
$cart_total = 0;
$cart_count = 0;
foreach ($_SESSION['order_cart'] as $item) {
    $cart_total += $item['unit_price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}

// Handle checkout submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = [];
    
    // Validate required fields
    $guest_name = trim($_POST['guest_name'] ?? '');
    $guest_phone = trim($_POST['guest_phone'] ?? '');
    $room_number = trim($_POST['room_number'] ?? '');
    $order_type = trim($_POST['order_type'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');
    $special_instructions = trim($_POST['special_instructions'] ?? '');
    
    if (empty($guest_name)) {
        $errors[] = "Guest name is required";
    }
    
    if (empty($guest_phone)) {
        $errors[] = "Phone number is required";
    } elseif (!preg_match('/^[\+]?[0-9\s\-\(\)]{10,15}$/', $guest_phone)) {
        $errors[] = "Please enter a valid phone number";
    }
    
    if (empty($order_type)) {
        $errors[] = "Please select an order type";
    }
    
    if (empty($payment_method)) {
        $errors[] = "Please select a payment method";
    }
    
    // Validate cart is not empty
    if (empty($_SESSION['order_cart'])) {
        $errors[] = "Your cart is empty";
    }
    
    // If room service, room number is required
    if ($order_type === 'room_service' && empty($room_number)) {
        $errors[] = "Room number is required for room service orders";
    }
    
    if (empty($errors)) {
        // Escape strings for database
        $guest_name = mysqli_real_escape_string($con, $guest_name);
        $guest_phone = mysqli_real_escape_string($con, $guest_phone);
        $room_number = mysqli_real_escape_string($con, $room_number);
        $order_type = mysqli_real_escape_string($con, $order_type);
        $payment_method = mysqli_real_escape_string($con, $payment_method);
        $special_instructions = mysqli_real_escape_string($con, $special_instructions);
    
    // Generate order number
    $order_number = 'ORD' . date('Ymd') . rand(1000, 9999);
    
    // Calculate tax and final amount
        $tax_amount = $cart_total * 0.16; // 16% VAT
    $final_amount = $cart_total + $tax_amount;
    
    // Insert order
    $order_sql = "INSERT INTO food_orders (order_number, guest_name, guest_phone, room_number, order_type, 
                    total_amount, tax_amount, final_amount, payment_method, special_instructions, status, ordered_time) 
                    VALUES ('$order_number', '$guest_name', '$guest_phone', '$room_number', '$order_type', 
                    $cart_total, $tax_amount, $final_amount, '$payment_method', '$special_instructions', 'pending', NOW())";
    
    if (mysqli_query($con, $order_sql)) {
        $order_id = mysqli_insert_id($con);
        
        // Insert order items
        foreach ($_SESSION['order_cart'] as $item) {
            $menu_item_id = (int)($item['item_id'] ?? 0);
            $quantity = (int)$item['quantity'];
            $unit_price = (float)$item['unit_price'];
            $total_price = $unit_price * $quantity;
            
            $item_sql = "INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price) 
                        VALUES ($order_id, $menu_item_id, $quantity, $unit_price, $total_price)";
            
            if (!mysqli_query($con, $item_sql)) {
                // Log the error but continue with other items
                error_log("Failed to insert order item: " . mysqli_error($con) . " - SQL: " . $item_sql);
            }
        }
        
        // Clear cart
        $_SESSION['order_cart'] = [];
        
        // Redirect to confirmation
        header("Location: order_confirmation.php?order_number=$order_number");
        exit();
    } else {
        $errors[] = "Order failed. Please try again.";
    }
    }
}

// Include header and components after form processing
include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');
?>

<!-- Checkout Section -->
<div class="checkout-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="page-header text-center mb-5">
            <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                <i class="fa fa-credit-card"></i> Complete Your Order
            </h1>
            <p class="lead text-white-50">Provide your details to confirm your order</p>
        </div>

        <div class="row">
            <!-- Checkout Form -->
            <div class="col-lg-8">
                <div class="checkout-form" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <h3><i class="fa fa-user"></i> Guest Information</h3>
                    
                    <?php if(!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" id="checkoutForm">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_name">Full Name *</label>
                                    <input type="text" class="form-control" name="guest_name" id="guest_name" 
                                           value="<?php echo htmlspecialchars($_POST['guest_name'] ?? ''); ?>" 
                                           required minlength="2" maxlength="100">
                                    <div class="invalid-feedback">Please enter your full name</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="guest_phone">Phone Number *</label>
                                    <input type="tel" class="form-control" name="guest_phone" id="guest_phone" 
                                           value="<?php echo htmlspecialchars($_POST['guest_phone'] ?? ''); ?>" 
                                           required pattern="[\+]?[0-9\s\-\(\)]{10,15}" 
                                           placeholder="e.g. +254 700 000 000">
                                    <div class="invalid-feedback">Please enter a valid phone number</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="room_number">Room Number</label>
                                    <input type="text" class="form-control" name="room_number" id="room_number" 
                                           value="<?php echo htmlspecialchars($_POST['room_number'] ?? ''); ?>" 
                                           placeholder="Required for room service">
                                    <div class="invalid-feedback">Room number is required for room service</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="order_type">Order Type *</label>
                                    <select class="form-control custom-select enhanced" name="order_type" id="order_type" required>
                                        <option value="">Select order type</option>
                                        <option value="dine_in" <?php echo ($_POST['order_type'] ?? '') === 'dine_in' ? 'selected' : ($post_data ? '' : 'selected'); ?>>Dine In</option>
                                        <option value="takeaway" <?php echo ($_POST['order_type'] ?? '') === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
                                        <option value="room_service" <?php echo ($_POST['order_type'] ?? '') === 'room_service' ? 'selected' : ''; ?>>Room Service</option>
                                    </select>
                                    <div class="invalid-feedback">Please select an order type</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="special_instructions">Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" id="special_instructions" rows="3" 
                                      placeholder="Any special requests..." maxlength="500"><?php echo htmlspecialchars($_POST['special_instructions'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Optional - maximum 500 characters</small>
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
                            <a href="order_cart.php" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back to Menu
                            </a>
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fa fa-check"></i> Confirm Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="col-lg-4">
                <div class="order-summary" style="background: white; border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); overflow: hidden;">
                    <div class="summary-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 25px;">
                        <h4><i class="fa fa-list"></i> Order Summary</h4>
                    </div>
                    
                    <div class="summary-content" style="padding: 25px;">
                        <?php foreach($_SESSION['order_cart'] as $item): ?>
                        <div class="summary-item" style="border-bottom: 1px solid #e9ecef; padding-bottom: 15px; margin-bottom: 15px;">
                            <div class="item-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                <h6 style="margin: 0; font-weight: 600;"><?php echo htmlspecialchars($item['item_name']); ?></h6>
                                <span style="font-weight: 600; color: #667eea;">KES <?php echo number_format($item['unit_price'] * $item['quantity'], 0); ?></span>
                            </div>
                            <div class="item-details" style="font-size: 0.85rem; color: #6c757d;">
                                <span>Qty: <?php echo $item['quantity']; ?></span>
                                <span>KES <?php echo number_format($item['unit_price'], 0); ?> each</span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="summary-totals" style="background: #f8f9fa; padding: 25px; border-top: 1px solid #e9ecef;">
                        <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span>KES <?php echo number_format($cart_total, 0); ?></span>
                        </div>
                        <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>VAT (16%):</span>
                            <span>KES <?php echo number_format($cart_total * 0.16, 0); ?></span>
                        </div>
                        <div class="total-row" style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.2rem; color: #2c3e50; border-top: 1px solid #dee2e6; padding-top: 10px; margin-top: 10px;">
                            <span>Total:</span>
                            <span>KES <?php echo number_format($cart_total * 1.16, 0); ?></span>
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
    
    // Order type change handler
    $('#order_type').change(function() {
        const orderType = $(this).val();
        const roomField = $('#room_number');
        
        if (orderType === 'room_service') {
            roomField.prop('required', true);
            roomField.closest('.form-group').find('label').html('Room Number *');
            roomField.attr('placeholder', 'Required for room service');
        } else {
            roomField.prop('required', false);
            roomField.closest('.form-group').find('label').html('Room Number');
            roomField.attr('placeholder', 'Optional');
        }
    });
    
    // Form validation
    $('#checkoutForm').on('submit', function(e) {
        let isValid = true;
        
        // Clear previous validation states
        $('.form-control').removeClass('is-valid is-invalid');
        
        // Validate name
        const name = $('#guest_name').val().trim();
        if (name.length < 2) {
            $('#guest_name').addClass('is-invalid');
            isValid = false;
        } else {
            $('#guest_name').addClass('is-valid');
        }
        
        // Validate phone
        const phone = $('#guest_phone').val().trim();
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,15}$/;
        if (!phoneRegex.test(phone)) {
            $('#guest_phone').addClass('is-invalid');
            isValid = false;
        } else {
            $('#guest_phone').addClass('is-valid');
        }
        
        // Validate order type
        const orderType = $('#order_type').val();
        if (!orderType) {
            $('#order_type').addClass('is-invalid');
            isValid = false;
        } else {
            $('#order_type').addClass('is-valid');
            
            // Check room number for room service
            if (orderType === 'room_service') {
                const roomNumber = $('#room_number').val().trim();
                if (!roomNumber) {
                    $('#room_number').addClass('is-invalid');
                    isValid = false;
                } else {
                    $('#room_number').addClass('is-valid');
                }
            }
        }
        
        // Validate payment method
        const paymentMethod = $('input[name="payment_method"]:checked').val();
        if (!paymentMethod) {
            $('.payment-options').addClass('border border-danger');
            isValid = false;
        } else {
            $('.payment-options').removeClass('border border-danger');
        }
        
        if (!isValid) {
            e.preventDefault();
            showValidationError('Please fix the errors above and try again.');
            $('html, body').animate({
                scrollTop: $('.is-invalid:first').offset().top - 100
            }, 500);
        } else {
            // Show loading state
            const submitBtn = $(this).find('button[type="submit"]');
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        }
    });
    
    // Real-time validation
    $('#guest_name').on('blur', function() {
        const value = $(this).val().trim();
        if (value.length >= 2) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    $('#guest_phone').on('blur', function() {
        const value = $(this).val().trim();
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,15}$/;
        if (phoneRegex.test(value)) {
            $(this).removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    // Character counter for special instructions
    $('#special_instructions').on('input', function() {
        const maxLength = 500;
        const currentLength = $(this).val().length;
        const remaining = maxLength - currentLength;
        
        let counterText = `${remaining} characters remaining`;
        if (remaining < 0) {
            counterText = `${Math.abs(remaining)} characters over limit`;
            $(this).addClass('is-invalid');
        } else {
            $(this).removeClass('is-invalid');
        }
        
        $(this).next('.form-text').text(counterText);
    });
    
    // Auto-save form data to localStorage
    const formFields = ['guest_name', 'guest_phone', 'room_number', 'order_type', 'special_instructions'];
    
    formFields.forEach(field => {
        const savedValue = localStorage.getItem(`checkout_${field}`);
        if (savedValue && !$(`#${field}`).val()) {
            $(`#${field}`).val(savedValue);
        }
        
        $(`#${field}`).on('change input', function() {
            localStorage.setItem(`checkout_${field}`, $(this).val());
        });
    });
    
    // Clear saved data on successful submission
    $('#checkoutForm').on('submit', function() {
        if ($(this)[0].checkValidity()) {
            formFields.forEach(field => {
                localStorage.removeItem(`checkout_${field}`);
            });
        }
    });
});

function showValidationError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show validation-alert" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
            <i class="fa fa-exclamation-circle"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.validation-alert').remove();
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.validation-alert').fadeOut(function() {
            $(this).remove();
        });
    }, 5000);
}
</script>

<?php include('../../../includes/guest/footer.php');?>
