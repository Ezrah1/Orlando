<?php
$page_title = 'Track Your Order - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Track Your Order';

// Database connection
require_once '../../../db.php';

include('../../../includes/guest/header.php');

// Handle order lookup
$order = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_number = trim($_POST['order_number'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    if (empty($order_number)) {
        $error = "Please enter your order number";
    } elseif (empty($phone_number)) {
        $error = "Please enter your phone number";
    } else {
        // Look up the order
        $order_query = "SELECT fo.*, 
                               COUNT(oi.id) as total_items,
                               SUM(oi.total_price) as items_total
                        FROM food_orders fo
                        LEFT JOIN order_items oi ON fo.id = oi.order_id
                        WHERE fo.order_number = '" . mysqli_real_escape_string($con, $order_number) . "'
                        AND fo.guest_phone = '" . mysqli_real_escape_string($con, $phone_number) . "'
                        GROUP BY fo.id";
        
        $order_result = mysqli_query($con, $order_query);
        
        if ($order_result && mysqli_num_rows($order_result) > 0) {
            $order = mysqli_fetch_assoc($order_result);
            // Redirect to order status page
            header("Location: order_status.php?order_number=" . urlencode($order['order_number']));
            exit();
        } else {
            $error = "Order not found. Please check your order number and phone number.";
        }
    }
}
?>

<!-- Order Tracking Section -->
<div class="order-tracking-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                <!-- Header -->
                <div class="text-center mb-5">
                    <div class="tracking-icon" style="font-size: 5rem; color: white; margin-bottom: 20px;">
                        <i class="fa fa-search"></i>
                    </div>
                    <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                        Track Your Order
                    </h1>
                    <p class="lead text-white-50" style="font-size: 1.2rem;">
                        Enter your order details to check the status
                    </p>
                </div>

                <!-- Tracking Form -->
                <div class="tracking-form" style="background: white; border-radius: 20px; padding: 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.15);">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fa fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form method="post" id="trackingForm">
                        <div class="form-group mb-4">
                            <label for="order_number" class="form-label">
                                <i class="fa fa-receipt"></i> Order Number *
                            </label>
                            <input type="text" class="form-control form-control-lg" name="order_number" id="order_number" 
                                   placeholder="e.g. ORD20241201001" 
                                   value="<?php echo htmlspecialchars($_POST['order_number'] ?? ''); ?>" 
                                   required style="border-radius: 12px; padding: 15px 20px;">
                            <small class="form-text text-muted">
                                You can find this on your order confirmation
                            </small>
                        </div>

                        <div class="form-group mb-4">
                            <label for="phone_number" class="form-label">
                                <i class="fa fa-phone"></i> Phone Number *
                            </label>
                            <input type="tel" class="form-control form-control-lg" name="phone_number" id="phone_number" 
                                   placeholder="Phone number used for the order" 
                                   value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>" 
                                   required style="border-radius: 12px; padding: 15px 20px;">
                            <small class="form-text text-muted">
                                Enter the phone number you used when placing the order
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg btn-block" style="border-radius: 12px; padding: 15px; font-weight: 600; font-size: 1.1rem;">
                            <i class="fa fa-search"></i> Track My Order
                        </button>
                    </form>

                    <div class="text-center mt-4">
                        <div class="divider" style="margin: 30px 0; position: relative;">
                            <hr style="border-color: #e9ecef;">
                            <span style="background: white; padding: 0 20px; color: #6c757d; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);">
                                Or
                            </span>
                        </div>
                        
                        <a href="menu_enhanced.php" class="btn btn-outline-primary btn-lg" style="border-radius: 12px; padding: 12px 30px; font-weight: 600;">
                            <i class="fa fa-utensils"></i> Place New Order
                        </a>
                    </div>
                </div>

                <!-- Help Section -->
                <div class="help-section" style="background: rgba(255,255,255,0.1); border-radius: 15px; padding: 25px; margin-top: 30px; backdrop-filter: blur(10px);">
                    <h5 style="color: white; margin-bottom: 15px;">
                        <i class="fa fa-question-circle"></i> Need Help?
                    </h5>
                    <div style="color: rgba(255,255,255,0.9); line-height: 1.6;">
                        <p class="mb-2">
                            <strong>Can't find your order number?</strong><br>
                            Check your SMS/email confirmation or call us at +254 700 000 000
                        </p>
                        <p class="mb-0">
                            <strong>Having issues?</strong><br>
                            Our customer service team is available 24/7 to assist you
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Styles -->
<style>
.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

.btn-outline-primary {
    border-color: #667eea;
    color: #667eea;
    background: rgba(255,255,255,0.9);
    transition: all 0.3s ease;
}

.btn-outline-primary:hover {
    background: #667eea;
    border-color: #667eea;
    color: white;
    transform: translateY(-2px);
}

@media (max-width: 768px) {
    .order-tracking-section {
        padding: 100px 0 60px;
    }
    
    .tracking-form {
        margin: 0 15px;
        padding: 30px 20px;
    }
    
    .display-4 {
        font-size: 2rem;
    }
}

/* Loading animation for form submission */
.btn-loading {
    position: relative;
    pointer-events: none;
}

.btn-loading::after {
    content: '';
    position: absolute;
    width: 20px;
    height: 20px;
    border: 2px solid transparent;
    border-top: 2px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%);
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<script>
$(document).ready(function() {
    // Form validation and enhancement
    $('#trackingForm').on('submit', function(e) {
        const orderNumber = $('#order_number').val().trim();
        const phoneNumber = $('#phone_number').val().trim();
        
        if (!orderNumber || !phoneNumber) {
            e.preventDefault();
            showError('Please fill in all required fields');
            return;
        }
        
        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.addClass('btn-loading').prop('disabled', true);
        submitBtn.find('i').hide();
        
        // Let form submit normally
    });
    
    // Real-time validation
    $('#order_number').on('input', function() {
        const value = $(this).val().trim();
        if (value.length > 0 && !value.match(/^[A-Z0-9\-]+$/)) {
            $(this).addClass('is-invalid');
            showFieldError(this, 'Order number should contain only letters, numbers, and hyphens');
        } else {
            $(this).removeClass('is-invalid');
            hideFieldError(this);
        }
    });
    
    $('#phone_number').on('input', function() {
        const value = $(this).val().trim();
        if (value.length > 0 && !value.match(/^[\+]?[0-9\s\-\(\)]{10,15}$/)) {
            $(this).addClass('is-invalid');
            showFieldError(this, 'Please enter a valid phone number');
        } else {
            $(this).removeClass('is-invalid');
            hideFieldError(this);
        }
    });
});

function showError(message) {
    const alertHtml = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.alert').remove();
    $('.tracking-form').prepend(alertHtml);
}

function showFieldError(field, message) {
    const fieldGroup = $(field).closest('.form-group');
    let feedback = fieldGroup.find('.invalid-feedback');
    
    if (feedback.length === 0) {
        feedback = $('<div class="invalid-feedback"></div>');
        fieldGroup.append(feedback);
    }
    
    feedback.text(message);
}

function hideFieldError(field) {
    $(field).closest('.form-group').find('.invalid-feedback').remove();
}
</script>

<?php include('../../../includes/guest/footer.php'); ?>
