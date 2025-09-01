<?php
/**
 * Cart View Page
 * Unified cart view for both room bookings and food/drink orders
 */

$page_title = 'Your Cart - Orlando International Resorts'; // Will be updated by hotel_settings from header
$page_description = 'Review your selected rooms and orders before checkout';

// Include necessary files
require_once '../../../cart_manager.php';
include('../../../includes/guest/header.php');

// Initialize cart
CartManager::initCarts();

// Get cart summaries
$booking_summary = CartManager::getBookingCartSummary();
$order_summary = CartManager::getOrderCartSummary();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $response = ['success' => false, 'message' => 'Invalid action'];
    
    switch ($action) {
        case 'remove_room':
            $room_name = $_POST['room_name'] ?? '';
            if (CartManager::removeRoomFromCart($room_name)) {
                $response = ['success' => true, 'message' => 'Room removed from cart'];
            }
            break;
            
        case 'remove_order_item':
            $item_id = $_POST['item_id'] ?? '';
            if (CartManager::removeItemFromOrderCart($item_id)) {
                $response = ['success' => true, 'message' => 'Item removed from cart'];
            }
            break;
            
        case 'update_order_quantity':
            $item_id = $_POST['item_id'] ?? '';
            $quantity = (int)($_POST['quantity'] ?? 1);
            if (CartManager::updateItemQuantity($item_id, $quantity)) {
                $response = ['success' => true, 'message' => 'Quantity updated'];
            }
            break;
            
        case 'clear_booking_cart':
            CartManager::clearBookingCart();
            CartManager::clearBookingAddons();
            $response = ['success' => true, 'message' => 'Booking cart cleared'];
            break;
            
        case 'clear_order_cart':
            CartManager::clearOrderCart();
            $response = ['success' => true, 'message' => 'Order cart cleared'];
            break;
    }
    
    // Return JSON response for AJAX requests
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    }
    
    // Redirect to prevent form resubmission
    if ($response['success']) {
        $_SESSION['cart_message'] = $response['message'];
        header('Location: view_cart.php');
        exit;
    }
}

// Check if carts have items
$has_booking_items = $booking_summary['rooms_count'] > 0 || $booking_summary['addons_count'] > 0;
$has_order_items = $order_summary['items_count'] > 0;
$has_any_items = $has_booking_items || $has_order_items;
?>

<!-- Cart View Section -->
<div class="cart-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <!-- Page Header -->
        <div class="page-header text-center mb-5">
            <h1 class="display-4 text-white mb-3" style="font-weight: 700; text-shadow: 0 2px 10px rgba(0,0,0,0.3);">
                <i class="fa fa-shopping-cart"></i> Your Cart
            </h1>
            <p class="lead text-white-50" style="font-size: 1.2rem; max-width: 600px; margin: 0 auto;">
                Review your selected rooms and orders before proceeding to checkout
            </p>
        </div>

        <?php if (isset($_SESSION['cart_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" style="border-radius: 15px; background: rgba(40, 167, 69, 0.9); border: none; color: white;">
            <i class="fa fa-check-circle"></i> <?php echo $_SESSION['cart_message']; unset($_SESSION['cart_message']); ?>
            <button type="button" class="close text-white" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
        <?php endif; ?>

        <?php if (!$has_any_items): ?>
        <!-- Empty Cart -->
        <div class="empty-cart text-center">
            <div class="empty-cart-card">
                <div class="empty-cart-icon">
                    <i class="fa fa-shopping-cart"></i>
                </div>
                <h3>Your cart is empty</h3>
                <p>Start by adding some rooms or menu items to your cart</p>
                <div class="empty-cart-actions">
                    <a href="../../../index.php" class="btn btn-primary btn-lg">
                        <i class="fa fa-bed"></i> Browse Rooms
                    </a>
                    <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php" class="btn btn-secondary btn-lg">
                        <i class="fa fa-utensils"></i> View Menu
                    </a>
                </div>
            </div>
        </div>
        <?php else: ?>
        
        <div class="row">
            <!-- Booking Cart -->
            <?php if ($has_booking_items): ?>
            <div class="col-lg-<?php echo $has_order_items ? '6' : '12'; ?> mb-4">
                <div class="cart-card">
                    <div class="cart-header">
                        <h3><i class="fa fa-bed"></i> Room Bookings</h3>
                        <button class="btn btn-clear" onclick="clearBookingCart()">
                            <i class="fa fa-trash"></i> Clear All
                        </button>
                    </div>
                    
                    <div class="cart-body">
                        <!-- Room Items -->
                        <?php foreach ($booking_summary['rooms'] as $room): ?>
                        <div class="cart-item booking-item" data-room="<?php echo htmlspecialchars($room['room_name']); ?>">
                            <div class="item-image">
                                <i class="fa fa-bed"></i>
                            </div>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($room['room_name']); ?></h4>
                                <p class="room-type"><?php echo htmlspecialchars($room['room_type']); ?></p>
                                <div class="booking-dates">
                                    <span><i class="fa fa-calendar"></i> <?php echo date('M j, Y', strtotime($room['check_in'])); ?></span>
                                    <span><i class="fa fa-calendar"></i> <?php echo date('M j, Y', strtotime($room['check_out'])); ?></span>
                                    <span><i class="fa fa-moon"></i> <?php echo $room['days']; ?> night<?php echo $room['days'] > 1 ? 's' : ''; ?></span>
                                </div>
                                <div class="guest-info">
                                    <span><i class="fa fa-user"></i> <?php echo $room['adults']; ?> Adult<?php echo $room['adults'] > 1 ? 's' : ''; ?></span>
                                    <?php if ($room['children'] > 0): ?>
                                    <span><i class="fa fa-child"></i> <?php echo $room['children']; ?> Child<?php echo $room['children'] > 1 ? 'ren' : ''; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="item-price">
                                <div class="price-breakdown">
                                    <span class="unit-price">KES <?php echo number_format($room['base_price'], 0); ?>/night</span>
                                    <span class="total-price">KES <?php echo number_format($room['total'], 0); ?></span>
                                </div>
                                <button class="btn btn-remove" onclick="removeRoom('<?php echo htmlspecialchars($room['room_name']); ?>')">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Add-ons -->
                        <?php if (!empty($booking_summary['addons'])): ?>
                        <div class="addons-section">
                            <h5><i class="fa fa-plus-circle"></i> Add-ons</h5>
                            <?php foreach ($booking_summary['addons'] as $addon): ?>
                            <div class="addon-item">
                                <span class="addon-name"><?php echo htmlspecialchars($addon['addon_name']); ?></span>
                                <span class="addon-price">KES <?php echo number_format($addon['price'], 0); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="cart-footer">
                        <div class="cart-totals">
                            <?php if ($booking_summary['addons_total'] > 0): ?>
                            <div class="total-line">
                                <span>Rooms Subtotal:</span>
                                <span>KES <?php echo number_format($booking_summary['rooms_total'], 0); ?></span>
                            </div>
                            <div class="total-line">
                                <span>Add-ons:</span>
                                <span>KES <?php echo number_format($booking_summary['addons_total'], 0); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="total-line grand-total">
                                <span>Total:</span>
                                <span>KES <?php echo number_format($booking_summary['grand_total'], 0); ?></span>
                            </div>
                        </div>
                        <a href="../booking/booking_checkout.php" class="btn btn-checkout">
                            <i class="fa fa-credit-card"></i> Proceed to Booking Checkout
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Order Cart -->
            <?php if ($has_order_items): ?>
            <div class="col-lg-<?php echo $has_booking_items ? '6' : '12'; ?> mb-4">
                <div class="cart-card">
                    <div class="cart-header">
                        <h3><i class="fa fa-utensils"></i> Food & Drinks</h3>
                        <button class="btn btn-clear" onclick="clearOrderCart()">
                            <i class="fa fa-trash"></i> Clear All
                        </button>
                    </div>
                    
                    <div class="cart-body">
                        <?php foreach ($order_summary['items'] as $item): ?>
                        <div class="cart-item order-item" data-item-id="<?php echo $item['item_id']; ?>">
                            <div class="item-image">
                                <i class="fa fa-utensils"></i>
                            </div>
                            <div class="item-details">
                                <h4><?php echo htmlspecialchars($item['item_name']); ?></h4>
                                <p class="item-type"><?php echo ucfirst($item['item_type']); ?></p>
                                <?php if (!empty($item['description'])): ?>
                                <p class="item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="item-controls">
                                <div class="quantity-controls">
                                    <button class="btn-quantity" onclick="updateOrderQuantity('<?php echo $item['item_id']; ?>', -1)">-</button>
                                    <span class="quantity"><?php echo $item['quantity']; ?></span>
                                    <button class="btn-quantity" onclick="updateOrderQuantity('<?php echo $item['item_id']; ?>', 1)">+</button>
                                </div>
                                <div class="item-price">
                                    <span class="unit-price">KES <?php echo number_format($item['unit_price'], 0); ?> each</span>
                                    <span class="total-price">KES <?php echo number_format($item['total_price'], 0); ?></span>
                                </div>
                                <button class="btn btn-remove" onclick="removeOrderItem('<?php echo $item['item_id']; ?>')">
                                    <i class="fa fa-times"></i>
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="cart-footer">
                        <div class="cart-totals">
                            <div class="total-line">
                                <span>Subtotal:</span>
                                <span>KES <?php echo number_format($order_summary['subtotal'], 0); ?></span>
                            </div>
                            <div class="total-line">
                                <span>VAT (16%):</span>
                                <span>KES <?php echo number_format($order_summary['tax'], 0); ?></span>
                            </div>
                            <div class="total-line grand-total">
                                <span>Total:</span>
                                <span>KES <?php echo number_format($order_summary['grand_total'], 0); ?></span>
                            </div>
                        </div>
                        <a href="../menu/order_checkout.php" class="btn btn-checkout">
                            <i class="fa fa-credit-card"></i> Proceed to Order Checkout
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Combined Checkout Section -->
        <?php if ($has_booking_items && $has_order_items): ?>
        <div class="combined-checkout text-center mt-4">
            <div class="combined-card">
                <h4><i class="fa fa-shopping-cart"></i> Complete Purchase</h4>
                <p>You can checkout your bookings and orders separately, or combine them for convenience.</p>
                <div class="combined-total">
                    <span>Total Amount: KES <?php echo number_format($booking_summary['grand_total'] + $order_summary['grand_total'], 0); ?></span>
                </div>
                <button class="btn btn-combined-checkout" onclick="proceedToCombinedCheckout()">
                    <i class="fa fa-credit-card"></i> Proceed with Combined Checkout
                </button>
            </div>
        </div>
        <?php endif; ?>
        
        <?php endif; ?>
    </div>
</div>

<!-- CSS Styles -->
<style>
/* ===== CART VIEW STYLES ===== */

.cart-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    min-height: 100vh;
    padding-top: 120px;
}

.page-header h1 {
    font-weight: 700;
    color: white;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
    margin-bottom: 1rem;
}

.page-header p {
    color: rgba(255,255,255,0.8);
    font-size: 1.2rem;
}

/* Empty Cart Styles */
.empty-cart-card {
    background: white;
    border-radius: 25px;
    padding: 60px 40px;
    text-align: center;
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    max-width: 500px;
    margin: 0 auto;
}

.empty-cart-icon {
    font-size: 5rem;
    color: #e9ecef;
    margin-bottom: 30px;
}

.empty-cart-card h3 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 15px;
}

.empty-cart-card p {
    color: #6c757d;
    margin-bottom: 30px;
}

.empty-cart-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.empty-cart-actions .btn {
    padding: 12px 25px;
    border-radius: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.empty-cart-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}

/* Cart Card Styles */
.cart-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    height: fit-content;
}

.cart-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.cart-header h3 {
    margin: 0;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 10px;
}

.btn-clear {
    background: rgba(231, 76, 60, 0.2);
    border: 1px solid rgba(231, 76, 60, 0.3);
    color: white;
    padding: 8px 15px;
    border-radius: 8px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.btn-clear:hover {
    background: rgba(231, 76, 60, 0.3);
    border-color: rgba(231, 76, 60, 0.5);
    color: white;
}

.cart-body {
    padding: 25px;
    max-height: 500px;
    overflow-y: auto;
}

/* Cart Item Styles */
.cart-item {
    display: flex;
    align-items: center;
    padding: 20px 0;
    border-bottom: 1px solid #e9ecef;
    gap: 20px;
}

.cart-item:last-child {
    border-bottom: none;
}

.cart-item .item-image {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.5rem;
    flex-shrink: 0;
}

.cart-item .item-details {
    flex: 1;
}

.cart-item .item-details h4 {
    margin: 0 0 5px 0;
    font-weight: 700;
    color: #2c3e50;
    font-size: 1.1rem;
}

.cart-item .room-type,
.cart-item .item-type {
    color: #667eea;
    font-weight: 600;
    margin-bottom: 8px;
    font-size: 0.9rem;
}

.cart-item .item-description {
    color: #6c757d;
    font-size: 0.85rem;
    margin-bottom: 5px;
}

.booking-dates,
.guest-info {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 5px;
}

.booking-dates span,
.guest-info span {
    color: #6c757d;
    font-size: 0.85rem;
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Item Controls */
.item-price,
.item-controls {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 10px;
}

.price-breakdown {
    text-align: right;
}

.unit-price {
    display: block;
    color: #6c757d;
    font-size: 0.85rem;
}

.total-price {
    display: block;
    color: #2c3e50;
    font-weight: 700;
    font-size: 1.1rem;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.btn-quantity {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #6c757d;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
}

.btn-quantity:hover {
    background: #667eea;
    color: white;
    border-color: #667eea;
}

.quantity {
    font-weight: 600;
    color: #2c3e50;
    min-width: 25px;
    text-align: center;
}

.btn-remove {
    background: rgba(231, 76, 60, 0.1);
    border: 1px solid rgba(231, 76, 60, 0.2);
    color: #e74c3c;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-remove:hover {
    background: #e74c3c;
    color: white;
    border-color: #e74c3c;
}

/* Add-ons Section */
.addons-section {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #e9ecef;
}

.addons-section h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.addon-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    color: #6c757d;
    font-size: 0.9rem;
}

.addon-price {
    font-weight: 600;
    color: #2c3e50;
}

/* Cart Footer */
.cart-footer {
    background: #f8f9fa;
    padding: 25px;
    border-top: 1px solid #e9ecef;
}

.cart-totals {
    margin-bottom: 20px;
}

.total-line {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    color: #6c757d;
}

.total-line.grand-total {
    font-weight: 700;
    font-size: 1.2rem;
    color: #2c3e50;
    border-top: 2px solid #e9ecef;
    padding-top: 15px;
    margin-top: 10px;
}

.btn-checkout {
    width: 100%;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 15px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    text-decoration: none;
}

.btn-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
    color: white;
    text-decoration: none;
}

/* Combined Checkout */
.combined-card {
    background: white;
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    max-width: 600px;
    margin: 0 auto;
}

.combined-card h4 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

.combined-total {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    margin: 20px 0;
    font-size: 1.3rem;
    font-weight: 700;
    color: #2c3e50;
}

.btn-combined-checkout {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 15px 30px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-combined-checkout:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
    color: white;
}

/* Loading States */
.cart-item.removing {
    opacity: 0.5;
    pointer-events: none;
}

.btn-quantity:disabled,
.btn-remove:disabled {
    opacity: 0.5;
    pointer-events: none;
}

.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #667eea;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .cart-section {
        padding-top: 100px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .cart-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
    
    .item-price,
    .item-controls {
        align-items: flex-start;
        width: 100%;
    }
    
    .booking-dates,
    .guest-info {
        flex-direction: column;
        gap: 5px;
    }
    
    .empty-cart-actions {
        flex-direction: column;
    }
}
</style>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
// Cart management functions
function removeRoom(roomName) {
    if (confirm('Are you sure you want to remove this room from your cart?')) {
        $.post('view_cart.php', {
            action: 'remove_room',
            room_name: roomName,
            ajax: true
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                showNotification('Error removing room', 'error');
            }
        }, 'json').fail(function() {
            showNotification('Error removing room', 'error');
        });
    }
}

function removeOrderItem(itemId) {
    if (confirm('Are you sure you want to remove this item from your cart?')) {
        // Show loading state
        const itemElement = $(`.cart-item[data-item-id="${itemId}"]`);
        itemElement.addClass('removing');
        
        $.post('../../../api/cart.php', {
            action: 'remove_item_from_cart',
            item_id: itemId
        }, function(response) {
            if (response.success) {
                // Animate item removal
                itemElement.fadeOut(300, function() {
                    $(this).remove();
                    
                    // Update totals
                    updateCartTotals(response.cart_summary);
                    
                    // Check if cart is empty
                    if (response.cart_summary.items_count === 0) {
                        location.reload();
                    }
                });
                
                showNotification('Item removed from cart', 'success');
            } else {
                itemElement.removeClass('removing');
                showNotification(response.message || 'Error removing item', 'error');
            }
        }, 'json').fail(function() {
            itemElement.removeClass('removing');
            showNotification('Error removing item', 'error');
        });
    }
}

function updateOrderQuantity(itemId, change) {
    const currentQuantity = parseInt($(`.cart-item[data-item-id="${itemId}"] .quantity`).text());
    const newQuantity = currentQuantity + change;
    
    if (newQuantity <= 0) {
        removeOrderItem(itemId);
        return;
    }
    
    // Show loading state
    const quantityElement = $(`.cart-item[data-item-id="${itemId}"] .quantity`);
    const originalText = quantityElement.text();
    quantityElement.html('<i class="fa fa-spinner fa-spin"></i>');
    
    $.post('../../../api/cart.php', {
        action: 'update_item_quantity',
        item_id: itemId,
        quantity: newQuantity
    }, function(response) {
        if (response.success) {
            // Update quantity display
            quantityElement.text(newQuantity);
            
            // Update totals
            updateCartTotals(response.cart_summary);
            
            showNotification('Quantity updated', 'success');
        } else {
            quantityElement.text(originalText);
            showNotification(response.message || 'Error updating quantity', 'error');
        }
    }, 'json').fail(function() {
        quantityElement.text(originalText);
        showNotification('Error updating quantity', 'error');
    });
}

function clearBookingCart() {
    if (confirm('Are you sure you want to clear all room bookings from your cart?')) {
        $.post('view_cart.php', {
            action: 'clear_booking_cart',
            ajax: true
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                showNotification('Error clearing cart', 'error');
            }
        }, 'json').fail(function() {
            showNotification('Error clearing cart', 'error');
        });
    }
}

function clearOrderCart() {
    if (confirm('Are you sure you want to clear all food & drink orders from your cart?')) {
        $.post('../../../api/cart.php', {
            action: 'clear_cart'
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                showNotification('Error clearing cart', 'error');
            }
        }, 'json').fail(function() {
            showNotification('Error clearing cart', 'error');
        });
    }
}

function updateCartTotals(cartSummary) {
    // Update subtotal
    $('.cart-totals .total-line:contains("Subtotal") span:last-child').text('KES ' + cartSummary.subtotal.toLocaleString());
    
    // Update tax
    $('.cart-totals .total-line:contains("VAT") span:last-child').text('KES ' + cartSummary.tax.toLocaleString());
    
    // Update grand total
    $('.cart-totals .grand-total span:last-child').text('KES ' + cartSummary.grand_total.toLocaleString());
    
    // Update header cart count and floating cart
    updateHeaderCartCount(cartSummary.items_count);
    
    // Update floating cart if global function is available
    if (typeof window.updateCartDisplay === 'function') {
        window.updateCartDisplay(cartSummary);
    }
}

function updateHeaderCartCount(itemCount) {
    // Update header cart indicator
    const headerCartCount = document.getElementById('cartCount');
    if (headerCartCount) {
        headerCartCount.textContent = itemCount || 0;
        headerCartCount.style.display = itemCount > 0 ? 'flex' : 'none';
    }
    
    // Also call the global refresh function if available
    if (typeof window.refreshCartCount === 'function') {
        window.refreshCartCount();
    }
}

function proceedToCombinedCheckout() {
    // This would redirect to a combined checkout page
    // For now, let's show a message
    showNotification('Combined checkout feature coming soon!', 'info');
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-danger' : 
                      type === 'info' ? 'alert-info' : 'alert-warning';
    const icon = type === 'success' ? 'fa-check-circle' : 
                type === 'error' ? 'fa-exclamation-circle' : 
                type === 'info' ? 'fa-info-circle' : 'fa-exclamation-triangle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
            <i class="fa ${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.alert').fadeOut();
    }, 4000);
}
</script>

<?php include('../../../includes/guest/footer.php'); ?>
