<?php
$page_title = 'Order Confirmation - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Order Confirmation';

// Database connection
require_once '../../../db.php';

include('../../../includes/guest/header.php');

// Get order number from URL
$order_number = $_GET['order_number'] ?? '';

if (empty($order_number)) {
    header("Location: menu_enhanced.php");
    exit();
}

// Get order details
$order_query = "SELECT fo.*, 
                       COUNT(oi.id) as total_items,
                       SUM(oi.total_price) as items_total
                FROM food_orders fo
                LEFT JOIN order_items oi ON fo.id = oi.order_id
                WHERE fo.order_number = '" . mysqli_real_escape_string($con, $order_number) . "'
                GROUP BY fo.id";

$order_result = mysqli_query($con, $order_query);

if (!$order_result || mysqli_num_rows($order_result) === 0) {
    $error = "Order not found";
} else {
    $order = mysqli_fetch_assoc($order_result);
    
    // Get order items
    $items_query = "SELECT * FROM order_items WHERE order_id = {$order['id']} ORDER BY id";
    $items_result = mysqli_query($con, $items_query);
}
?>

<!-- Order Confirmation Section -->
<div class="confirmation-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <?php if (isset($error)): ?>
            <!-- Error State -->
            <div class="text-center">
                <div class="error-card" style="background: white; border-radius: 20px; padding: 60px 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
                    <div class="error-icon" style="font-size: 4rem; color: #e74c3c; margin-bottom: 20px;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <h2 style="color: #2c3e50; margin-bottom: 15px;">Order Not Found</h2>
                    <p style="color: #6c757d; margin-bottom: 30px;">The order you're looking for could not be found. Please check your order number or contact support.</p>
                    <a href="menu_enhanced.php" class="btn btn-primary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Menu
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Success State -->
            <div class="text-center mb-5">
                <div class="success-icon" style="font-size: 5rem; color: white; margin-bottom: 20px; animation: bounce 2s infinite;">
                    <i class="fa fa-check-circle"></i>
                </div>
                <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                    Order Confirmed!
                </h1>
                <p class="lead text-white-50" style="font-size: 1.3rem;">
                    Thank you for your order. We'll prepare it with care.
                </p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <!-- Order Details Card -->
                    <div class="order-details-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.15); margin-bottom: 30px;">
                        <!-- Order Header -->
                        <div class="order-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 30px; text-align: center;">
                            <h3 style="margin: 0 0 10px 0; font-weight: 600;">
                                <i class="fa fa-receipt"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?>
                            </h3>
                            <div class="order-status" style="display: inline-block; background: rgba(255,255,255,0.2); padding: 8px 16px; border-radius: 20px; font-weight: 600;">
                                <i class="fa fa-clock"></i> <?php echo ucfirst($order['status']); ?>
                            </div>
                        </div>

                        <!-- Order Info -->
                        <div class="order-info" style="padding: 30px;">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="info-group" style="margin-bottom: 20px;">
                                        <h5 style="color: #2c3e50; margin-bottom: 8px;">
                                            <i class="fa fa-user"></i> Guest Information
                                        </h5>
                                        <div style="color: #6c757d;">
                                            <div><strong>Name:</strong> <?php echo htmlspecialchars($order['guest_name']); ?></div>
                                            <div><strong>Phone:</strong> <?php echo htmlspecialchars($order['guest_phone']); ?></div>
                                            <?php if (!empty($order['room_number'])): ?>
                                                <div><strong>Room:</strong> <?php echo htmlspecialchars($order['room_number']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-group" style="margin-bottom: 20px;">
                                        <h5 style="color: #2c3e50; margin-bottom: 8px;">
                                            <i class="fa fa-info-circle"></i> Order Details
                                        </h5>
                                        <div style="color: #6c757d;">
                                            <div><strong>Type:</strong> <?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></div>
                                            <div><strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?></div>
                                            <div><strong>Ordered:</strong> <?php echo date('M j, Y g:i A', strtotime($order['ordered_time'])); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($order['special_instructions'])): ?>
                                <div class="info-group" style="margin-bottom: 20px;">
                                    <h5 style="color: #2c3e50; margin-bottom: 8px;">
                                        <i class="fa fa-comments"></i> Special Instructions
                                    </h5>
                                    <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; color: #6c757d;">
                                        <?php echo nl2br(htmlspecialchars($order['special_instructions'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Order Items -->
                        <div class="order-items" style="border-top: 1px solid #e9ecef;">
                            <div style="padding: 30px 30px 20px;">
                                <h5 style="color: #2c3e50; margin-bottom: 20px;">
                                    <i class="fa fa-list"></i> Ordered Items (<?php echo $order['total_items']; ?>)
                                </h5>
                                
                                <div class="items-list">
                                    <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                                        <div class="order-item" style="display: flex; justify-content: space-between; align-items: center; padding: 15px 0; border-bottom: 1px solid #f1f3f4;">
                                            <div class="item-details" style="flex: 1;">
                                                <h6 style="margin: 0 0 5px 0; font-weight: 600; color: #2c3e50;">
                                                    <?php echo htmlspecialchars($item['item_name']); ?>
                                                </h6>
                                                <div style="color: #6c757d; font-size: 0.9rem;">
                                                    Qty: <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['unit_price'], 0); ?>
                                                </div>
                                            </div>
                                            <div class="item-total" style="font-weight: 700; color: #667eea; font-size: 1.1rem;">
                                                KES <?php echo number_format($item['total_price'], 0); ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>

                            <!-- Order Totals -->
                            <div class="order-totals" style="background: #f8f9fa; padding: 25px 30px;">
                                <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #6c757d;">
                                    <span>Subtotal:</span>
                                    <span>KES <?php echo number_format($order['total_amount'], 0); ?></span>
                                </div>
                                <?php if ($order['tax_amount'] > 0): ?>
                                    <div class="total-row" style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #6c757d;">
                                        <span>VAT (16%):</span>
                                        <span>KES <?php echo number_format($order['tax_amount'], 0); ?></span>
                                    </div>
                                <?php endif; ?>
                                <div class="total-row" style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.3rem; color: #2c3e50; border-top: 2px solid #dee2e6; padding-top: 15px; margin-top: 15px;">
                                    <span>Total:</span>
                                    <span>KES <?php echo number_format($order['final_amount'], 0); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Status Timeline -->
                    <div class="status-timeline" style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin-bottom: 30px;">
                        <h5 style="color: #2c3e50; margin-bottom: 25px; text-align: center;">
                            <i class="fa fa-clock"></i> Order Status
                        </h5>
                        
                        <div class="timeline">
                            <div class="timeline-item <?php echo in_array($order['status'], ['pending', 'confirmed', 'ready', 'served']) ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fa fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Order Placed</h6>
                                    <small><?php echo date('g:i A', strtotime($order['ordered_time'])); ?></small>
                                </div>
                            </div>
                            
                            <div class="timeline-item <?php echo in_array($order['status'], ['confirmed', 'ready', 'served']) ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fa fa-thumbs-up"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Order Confirmed</h6>
                                    <small><?php echo $order['confirmed_time'] ? date('g:i A', strtotime($order['confirmed_time'])) : 'Pending'; ?></small>
                                </div>
                            </div>
                            
                            <div class="timeline-item <?php echo in_array($order['status'], ['ready', 'served']) ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fa fa-utensils"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Order Ready</h6>
                                    <small><?php echo $order['ready_time'] ? date('g:i A', strtotime($order['ready_time'])) : 'Preparing'; ?></small>
                                </div>
                            </div>
                            
                            <div class="timeline-item <?php echo $order['status'] === 'served' ? 'completed' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fa fa-check-circle"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Order Delivered</h6>
                                    <small><?php echo $order['served_time'] ? date('g:i A', strtotime($order['served_time'])) : 'Pending'; ?></small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center">
                        <a href="menu_enhanced.php" class="btn btn-primary btn-lg" style="margin: 0 10px 10px;">
                            <i class="fa fa-utensils"></i> Order More
                        </a>
                        <button onclick="window.print()" class="btn btn-secondary btn-lg" style="margin: 0 10px 10px;">
                            <i class="fa fa-print"></i> Print Receipt
                        </button>
                        <?php if ($order['payment_method'] === 'mpesa' && $order['status'] === 'pending'): ?>
                            <a href="../payments/mpesa_order_payment.php?order_id=<?php echo $order['id']; ?>" class="btn btn-success btn-lg" style="margin: 0 10px 10px;">
                                <i class="fa fa-mobile"></i> Complete Payment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Styles -->
<style>
@keyframes bounce {
    0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
    40% { transform: translateY(-10px); }
    60% { transform: translateY(-5px); }
}

.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 40px;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-icon {
    position: absolute;
    left: -37px;
    top: 0;
    width: 30px;
    height: 30px;
    background: #e9ecef;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    font-size: 0.8rem;
}

.timeline-item.completed .timeline-icon {
    background: #28a745;
    color: white;
}

.timeline-content h6 {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #2c3e50;
}

.timeline-content small {
    color: #6c757d;
}

@media (max-width: 768px) {
    .confirmation-section {
        padding: 100px 0 60px;
    }
    
    .order-details-card {
        margin: 0 15px 30px;
    }
    
    .order-header,
    .order-info,
    .order-items > div {
        padding: 20px !important;
    }
    
    .order-item {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 10px;
    }
    
    .item-total {
        align-self: flex-end;
    }
}

@media print {
    .confirmation-section {
        background: white !important;
        padding: 20px 0 !important;
    }
    
    .btn, .fa-print {
        display: none !important;
    }
    
    .order-details-card {
        box-shadow: none !important;
        border: 1px solid #ddd !important;
    }
}
</style>

<!-- Auto-refresh script for status updates -->
<script>
// Refresh page every 30 seconds to check for status updates
<?php if ($order['status'] !== 'served' && $order['status'] !== 'cancelled'): ?>
setTimeout(function() {
    location.reload();
}, 30000);
<?php endif; ?>

// Show notification about order status
<?php if ($order['status'] === 'ready'): ?>
    // You could add browser notification here
    if (Notification.permission === 'granted') {
        new Notification('Your order is ready!', {
            body: 'Order #<?php echo $order['order_number']; ?> is ready for pickup/delivery.',
            icon: '/favicon.ico'
        });
    }
<?php endif; ?>
</script>

<?php include('../../../includes/guest/footer.php'); ?>