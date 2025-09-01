<?php
$page_title = 'Order Status - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Track Your Order';

// Database connection
require_once '../../../db.php';

include('../../../includes/guest/header.php');

// Get order number from URL
$order_number = $_GET['order_number'] ?? '';

if (empty($order_number)) {
    header("Location: menu_enhanced.php");
    exit();
}

// Get order details with real-time status
$order_query = "SELECT fo.*, 
                       COUNT(oi.id) as total_items,
                       SUM(oi.total_price) as items_total,
                       u1.first_name as confirmed_by_name,
                       u2.first_name as prepared_by_name,
                       u3.first_name as served_by_name
                FROM food_orders fo
                LEFT JOIN order_items oi ON fo.id = oi.order_id
                LEFT JOIN users u1 ON fo.confirmed_by = u1.id
                LEFT JOIN users u2 ON fo.prepared_by = u2.id
                LEFT JOIN users u3 ON fo.served_by = u3.id
                WHERE fo.order_number = '" . mysqli_real_escape_string($con, $order_number) . "'
                GROUP BY fo.id";

$order_result = mysqli_query($con, $order_query);

if (!$order_result || mysqli_num_rows($order_result) === 0) {
    $error = "Order not found";
} else {
    $order = mysqli_fetch_assoc($order_result);
    
    // Calculate estimated delivery time
    $estimated_delivery = null;
    if ($order['status'] === 'confirmed') {
        $confirmed_time = strtotime($order['confirmed_time']);
        $estimated_delivery = date('g:i A', $confirmed_time + (30 * 60)); // 30 minutes from confirmation
    }
    
    // Get order items with individual status
    $items_query = "SELECT oi.*, mi.name as menu_item_name 
                    FROM order_items oi
                    LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                    WHERE oi.order_id = {$order['id']} 
                    ORDER BY oi.id";
    $items_result = mysqli_query($con, $items_query);
}
?>

<!-- Order Status Section -->
<div class="order-status-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <?php if (isset($error)): ?>
            <!-- Error State -->
            <div class="text-center">
                <div class="error-card" style="background: white; border-radius: 20px; padding: 60px 40px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;">
                    <div class="error-icon" style="font-size: 4rem; color: #e74c3c; margin-bottom: 20px;">
                        <i class="fa fa-exclamation-triangle"></i>
                    </div>
                    <h2 style="color: #2c3e50; margin-bottom: 15px;">Order Not Found</h2>
                    <p style="color: #6c757d; margin-bottom: 30px;">The order you're looking for could not be found. Please check your order number.</p>
                    <a href="menu_enhanced.php" class="btn btn-primary btn-lg">
                        <i class="fa fa-arrow-left"></i> Back to Menu
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Order Status Header -->
            <div class="text-center mb-5">
                <div class="status-icon" style="font-size: 5rem; color: white; margin-bottom: 20px;" id="statusIcon">
                    <?php 
                    switch($order['status']) {
                        case 'pending':
                            echo '<i class="fa fa-clock"></i>';
                            break;
                        case 'confirmed':
                            echo '<i class="fa fa-check-circle"></i>';
                            break;
                        case 'ready':
                            echo '<i class="fa fa-bell"></i>';
                            break;
                        case 'served':
                            echo '<i class="fa fa-smile"></i>';
                            break;
                        default:
                            echo '<i class="fa fa-info-circle"></i>';
                    }
                    ?>
                </div>
                <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                    Order #<?php echo htmlspecialchars($order['order_number']); ?>
                </h1>
                <div class="status-badge" style="display: inline-block; background: rgba(255,255,255,0.2); padding: 12px 24px; border-radius: 25px; font-size: 1.2rem; font-weight: 600; color: white; margin-bottom: 20px;">
                    <?php echo ucfirst($order['status']); ?>
                </div>
                
                <?php if ($estimated_delivery && $order['status'] === 'confirmed'): ?>
                    <div class="estimated-time" style="color: rgba(255,255,255,0.9); font-size: 1.1rem;">
                        <i class="fa fa-clock"></i> Estimated delivery: <?php echo $estimated_delivery; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <!-- Live Status Timeline -->
                    <div class="status-timeline-card" style="background: white; border-radius: 20px; overflow: hidden; box-shadow: 0 15px 35px rgba(0,0,0,0.15); margin-bottom: 30px;">
                        <div class="timeline-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 25px; text-align: center;">
                            <h3 style="margin: 0; font-weight: 600;">
                                <i class="fa fa-route"></i> Order Progress
                            </h3>
                        </div>

                        <div class="timeline-content" style="padding: 40px;">
                            <div class="progress-timeline">
                                <!-- Order Placed -->
                                <div class="timeline-step completed" data-step="pending">
                                    <div class="step-number">1</div>
                                    <div class="step-content">
                                        <h5>Order Placed</h5>
                                        <p>Your order has been received</p>
                                        <small class="text-muted"><?php echo date('M j, Y g:i A', strtotime($order['ordered_time'])); ?></small>
                                    </div>
                                    <div class="step-icon">
                                        <i class="fa fa-check"></i>
                                    </div>
                                </div>

                                <!-- Order Confirmed -->
                                <div class="timeline-step <?php echo in_array($order['status'], ['confirmed', 'ready', 'served']) ? 'completed' : ($order['status'] === 'confirmed' ? 'active' : ''); ?>" data-step="confirmed">
                                    <div class="step-number">2</div>
                                    <div class="step-content">
                                        <h5>Order Confirmed</h5>
                                        <p>Kitchen has received your order</p>
                                        <small class="text-muted">
                                            <?php if ($order['confirmed_time']): ?>
                                                <?php echo date('g:i A', strtotime($order['confirmed_time'])); ?>
                                                <?php if ($order['confirmed_by_name']): ?>
                                                    by <?php echo htmlspecialchars($order['confirmed_by_name']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Waiting for confirmation...
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="step-icon">
                                        <i class="fa fa-thumbs-up"></i>
                                    </div>
                                </div>

                                <!-- Order Ready -->
                                <div class="timeline-step <?php echo in_array($order['status'], ['ready', 'served']) ? 'completed' : ($order['status'] === 'ready' ? 'active' : ''); ?>" data-step="ready">
                                    <div class="step-number">3</div>
                                    <div class="step-content">
                                        <h5>Order Ready</h5>
                                        <p>Your delicious food is ready!</p>
                                        <small class="text-muted">
                                            <?php if ($order['ready_time']): ?>
                                                <?php echo date('g:i A', strtotime($order['ready_time'])); ?>
                                                <?php if ($order['prepared_by_name']): ?>
                                                    by Chef <?php echo htmlspecialchars($order['prepared_by_name']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                Being prepared...
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="step-icon">
                                        <i class="fa fa-utensils"></i>
                                    </div>
                                </div>

                                <!-- Order Delivered -->
                                <div class="timeline-step <?php echo $order['status'] === 'served' ? 'completed' : ''; ?>" data-step="served">
                                    <div class="step-number">4</div>
                                    <div class="step-content">
                                        <h5>Order Delivered</h5>
                                        <p>Enjoy your meal!</p>
                                        <small class="text-muted">
                                            <?php if ($order['served_time']): ?>
                                                <?php echo date('g:i A', strtotime($order['served_time'])); ?>
                                                <?php if ($order['served_by_name']): ?>
                                                    by <?php echo htmlspecialchars($order['served_by_name']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?php echo $order['order_type'] === 'room_service' ? 'On the way to your room...' : 'Waiting for pickup...'; ?>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                    <div class="step-icon">
                                        <i class="fa fa-smile"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Details Summary -->
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-card" style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 20px;">
                                <h5 style="color: #2c3e50; margin-bottom: 20px;">
                                    <i class="fa fa-info-circle"></i> Order Details
                                </h5>
                                <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Type:</span>
                                    <strong><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></strong>
                                </div>
                                <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Payment:</span>
                                    <strong><?php echo ucfirst($order['payment_method']); ?></strong>
                                </div>
                                <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Items:</span>
                                    <strong><?php echo $order['total_items']; ?></strong>
                                </div>
                                <div class="detail-row" style="display: flex; justify-content: space-between; font-weight: 700; font-size: 1.1rem; border-top: 1px solid #e9ecef; padding-top: 10px; margin-top: 15px;">
                                    <span>Total:</span>
                                    <span>KES <?php echo number_format($order['final_amount'], 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="info-card" style="background: white; border-radius: 15px; padding: 25px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); margin-bottom: 20px;">
                                <h5 style="color: #2c3e50; margin-bottom: 20px;">
                                    <i class="fa fa-user"></i> Guest Information
                                </h5>
                                <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Name:</span>
                                    <strong><?php echo htmlspecialchars($order['guest_name']); ?></strong>
                                </div>
                                <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                    <span>Phone:</span>
                                    <strong><?php echo htmlspecialchars($order['guest_phone']); ?></strong>
                                </div>
                                <?php if (!empty($order['room_number'])): ?>
                                    <div class="detail-row" style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                        <span>Room:</span>
                                        <strong><?php echo htmlspecialchars($order['room_number']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center" style="margin-top: 30px;">
                        <button onclick="location.reload()" class="btn btn-primary btn-lg" style="margin: 0 10px 10px;">
                            <i class="fa fa-refresh"></i> Refresh Status
                        </button>
                        <a href="menu_enhanced.php" class="btn btn-secondary btn-lg" style="margin: 0 10px 10px;">
                            <i class="fa fa-utensils"></i> Order More
                        </a>
                        <?php if ($order['status'] === 'served'): ?>
                            <a href="order_confirmation.php?order_number=<?php echo urlencode($order['order_number']); ?>" class="btn btn-success btn-lg" style="margin: 0 10px 10px;">
                                <i class="fa fa-receipt"></i> View Receipt
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
.progress-timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-step {
    display: flex;
    align-items: center;
    margin-bottom: 40px;
    position: relative;
    opacity: 0.5;
    transition: all 0.3s ease;
}

.timeline-step.completed,
.timeline-step.active {
    opacity: 1;
}

.timeline-step:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 30px;
    top: 60px;
    width: 2px;
    height: 60px;
    background: #e9ecef;
    z-index: 1;
}

.timeline-step.completed:not(:last-child)::after {
    background: #28a745;
}

.step-number {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: #e9ecef;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 1.2rem;
    margin-right: 20px;
    position: relative;
    z-index: 2;
    transition: all 0.3s ease;
}

.timeline-step.completed .step-number {
    background: #28a745;
    color: white;
}

.timeline-step.active .step-number {
    background: #667eea;
    color: white;
    animation: pulse 2s infinite;
}

.step-content {
    flex: 1;
    margin-right: 20px;
}

.step-content h5 {
    margin: 0 0 5px 0;
    font-weight: 600;
    color: #2c3e50;
}

.step-content p {
    margin: 0 0 5px 0;
    color: #6c757d;
}

.step-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f8f9fa;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    transition: all 0.3s ease;
}

.timeline-step.completed .step-icon {
    background: #28a745;
    color: white;
}

.timeline-step.active .step-icon {
    background: #667eea;
    color: white;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.detail-row {
    color: #6c757d;
}

.detail-row strong {
    color: #2c3e50;
}

@media (max-width: 768px) {
    .order-status-section {
        padding: 100px 0 60px;
    }
    
    .timeline-step {
        flex-direction: column;
        text-align: center;
        margin-bottom: 30px;
    }
    
    .timeline-step:not(:last-child)::after {
        left: 50%;
        transform: translateX(-50%);
        top: 80px;
        height: 40px;
    }
    
    .step-number {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .step-content {
        margin-right: 0;
        margin-bottom: 15px;
    }
    
    .step-icon {
        margin: 0 auto;
    }
}
</style>

<!-- Auto-refresh and notifications -->
<script>
const currentStatus = '<?php echo $order['status']; ?>';
const orderNumber = '<?php echo $order['order_number']; ?>';

// Auto-refresh every 15 seconds if order is not completed
<?php if ($order['status'] !== 'served' && $order['status'] !== 'cancelled'): ?>
setInterval(function() {
    // Only refresh if page is visible
    if (!document.hidden) {
        location.reload();
    }
}, 15000);
<?php endif; ?>

// Request notification permission
if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission();
}

// Show notifications for status changes
function showStatusNotification(status) {
    if (Notification.permission === 'granted') {
        let title, body, icon;
        
        switch(status) {
            case 'confirmed':
                title = 'Order Confirmed!';
                body = `Your order #${orderNumber} has been confirmed and is being prepared.`;
                break;
            case 'ready':
                title = 'Order Ready!';
                body = `Your order #${orderNumber} is ready for ${<?php echo $order['order_type'] === 'room_service' ? "'delivery'" : "'pickup'"; ?>}.`;
                break;
            case 'served':
                title = 'Order Delivered!';
                body = `Your order #${orderNumber} has been delivered. Enjoy your meal!`;
                break;
        }
        
        if (title) {
            new Notification(title, {
                body: body,
                icon: '/favicon.ico',
                badge: '/favicon.ico'
            });
        }
    }
}

// Add sound notification for status changes
function playNotificationSound() {
    // Create audio context for notification sound
    const audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const oscillator = audioContext.createOscillator();
    const gainNode = audioContext.createGain();
    
    oscillator.connect(gainNode);
    gainNode.connect(audioContext.destination);
    
    oscillator.frequency.value = 800;
    gainNode.gain.value = 0.1;
    
    oscillator.start();
    oscillator.stop(audioContext.currentTime + 0.2);
}

// Check for status changes on page visibility
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        // Page became visible, check for updates
        setTimeout(function() {
            location.reload();
        }, 1000);
    }
});
</script>

<?php include('../../../includes/guest/footer.php'); ?>
