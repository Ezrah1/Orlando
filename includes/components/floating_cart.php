<?php
/**
 * Floating Shopping Cart Widget
 * Reusable component for all guest pages
 */

// Ensure cart manager is loaded
if (!class_exists('CartManager')) {
    require_once dirname(__DIR__, 2) . '/cart_manager.php';
}

// Initialize cart
CartManager::initCarts();

// Get both cart summaries
$order_summary = CartManager::getOrderCartSummary();
$booking_summary = CartManager::getBookingCartSummary();
$cart_count = $order_summary['items_count'] + $booking_summary['rooms_count'] + $booking_summary['addons_count'];

// Only show if cart has items
if ($cart_count > 0):
?>

<!-- Floating Shopping Cart -->
<div class="floating-shopping-cart" id="floatingCart">
    <div class="cart-preview">
        <div class="cart-header" onclick="toggleCartPreview()">
            <div class="cart-icon">
                <i class="fa fa-shopping-cart"></i>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            </div>
            <div class="cart-info">
                <span class="cart-count"><?php echo $cart_count; ?> items</span>
                <span class="cart-total">KES <?php echo number_format($order_summary['grand_total'] + $booking_summary['grand_total'], 0); ?></span>
            </div>
            <i class="fa fa-chevron-up cart-toggle"></i>
        </div>
        
        <div class="cart-items-preview" id="cartItemsPreview" style="display: none;">
            <?php if (!empty($booking_summary['rooms'])): ?>
                <?php foreach($booking_summary['rooms'] as $room): ?>
                <div class="cart-item-mini booking-item">
                    <div class="item-info">
                        <span class="item-name">🏨 <?php echo htmlspecialchars($room['room_name']); ?></span>
                        <span class="item-details"><?php echo $room['days']; ?> nights × KES <?php echo number_format($room['base_price'], 0); ?></span>
                    </div>
                    <span class="item-total">KES <?php echo number_format($room['total'], 0); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($booking_summary['addons'])): ?>
                <?php foreach($booking_summary['addons'] as $addon): ?>
                <div class="cart-item-mini addon-item">
                    <div class="item-info">
                        <span class="item-name">➕ <?php echo htmlspecialchars($addon['name']); ?></span>
                        <span class="item-details">Add-on service</span>
                    </div>
                    <span class="item-total">KES <?php echo number_format($addon['price'], 0); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($order_summary['items'])): ?>
                <?php foreach($order_summary['items'] as $item): ?>
                <div class="cart-item-mini order-item">
                    <div class="item-info">
                        <span class="item-name">🍽️ <?php echo htmlspecialchars($item['item_name']); ?></span>
                        <span class="item-details">Qty: <?php echo $item['quantity']; ?> × KES <?php echo number_format($item['unit_price'], 0); ?></span>
                    </div>
                    <span class="item-total">KES <?php echo number_format($item['total_price'], 0); ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="cart-actions">
                <a href="<?php echo $floating_cart_base_url ?? ''; ?>modules/guest/cart/view_cart.php" class="btn-view-cart">View Cart</a>
                <a href="<?php echo $floating_cart_base_url ?? ''; ?>modules/guest/menu/order_checkout.php" class="btn-checkout">Checkout</a>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<!-- Floating Cart CSS (only include once) -->
<?php if (!defined('FLOATING_CART_CSS_INCLUDED')): ?>
<?php define('FLOATING_CART_CSS_INCLUDED', true); ?>
<style>
/* Floating Shopping Cart */
.floating-shopping-cart {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    max-width: 350px;
    min-width: 250px;
}

.cart-preview {
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
    overflow: hidden;
}

.cart-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 15px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.cart-header:hover {
    background: linear-gradient(135deg, #34495e 0%, #2c3e50 100%);
}

.cart-icon {
    position: relative;
}

.cart-icon i {
    font-size: 1.5rem;
}

.cart-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background: #e74c3c;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 700;
}

.cart-info {
    flex: 1;
}

.cart-count {
    display: block;
    font-weight: 600;
    font-size: 0.9rem;
}

.cart-total {
    display: block;
    font-size: 1.1rem;
    font-weight: 700;
}

.cart-toggle {
    transition: transform 0.3s ease;
}

.cart-items-preview {
    padding: 15px 20px;
    max-height: 300px;
    overflow-y: auto;
}

.cart-item-mini {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 10px 0;
    border-bottom: 1px solid #f1f3f4;
}

.cart-item-mini:last-child {
    border-bottom: none;
}

/* Different styles for different cart item types */
.cart-item-mini.booking-item {
    background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
    border-left: 3px solid #667eea;
    padding-left: 15px;
}

.cart-item-mini.addon-item {
    background: linear-gradient(90deg, rgba(40, 167, 69, 0.05), transparent);
    border-left: 3px solid #28a745;
    padding-left: 15px;
}

.cart-item-mini.order-item {
    background: linear-gradient(90deg, rgba(255, 193, 7, 0.05), transparent);
    border-left: 3px solid #ffc107;
    padding-left: 15px;
}

.item-info .item-name {
    display: block;
    font-weight: 600;
    color: #2c3e50;
    font-size: 0.9rem;
    margin-bottom: 3px;
}

.item-info .item-details {
    display: block;
    color: #6c757d;
    font-size: 0.8rem;
}

.item-total {
    font-weight: 700;
    color: #28a745;
    font-size: 0.9rem;
}

.cart-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #f1f3f4;
}

.btn-view-cart {
    flex: 1;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 10px 15px;
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-view-cart:hover {
    background: #e9ecef;
    text-decoration: none;
    color: #495057;
}

.btn-checkout {
    flex: 1;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 6px;
    text-align: center;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-checkout:hover {
    transform: translateY(-1px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
    text-decoration: none;
    color: white;
}

/* Responsive Design: Floating cart for large screens, header icon for mobile */
@media (min-width: 992px) {
    /* Desktop: Show floating cart, hide mobile header cart */
    .floating-shopping-cart {
        display: block;
    }
}

@media (max-width: 991px) and (min-width: 769px) {
    /* Tablet: Show floating cart with adjustments */
    .floating-shopping-cart {
        right: 15px;
        bottom: 20px;
        max-width: calc(100vw - 30px);
        min-width: 280px;
    }
}

@media (max-width: 768px) {
    /* Mobile: Hide floating cart, use header icon instead */
    .floating-shopping-cart {
        display: none !important;
    }
    
    .cart-header {
        padding: 16px 20px;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }
    
    .cart-header:hover {
        background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        transform: translateY(-2px);
    }
    
    .cart-badge {
        animation: pulse 2s infinite;
        background: #ff4757;
        box-shadow: 0 0 10px rgba(255, 71, 87, 0.5);
    }
    
    .cart-count {
        font-size: 1rem;
        font-weight: 700;
    }
    
    .cart-total {
        font-size: 1.2rem;
        font-weight: 800;
    }
}

@media (max-width: 480px) {
    .floating-shopping-cart {
        right: 10px;
        bottom: 15px;
        max-width: calc(100vw - 20px);
        min-width: auto;
    }
    
    .cart-header {
        padding: 14px 16px;
        gap: 12px;
        border-radius: 12px;
    }
    
    .cart-preview {
        border-radius: 12px;
        box-shadow: 0 8px 30px rgba(0,0,0,0.2);
    }
    
    .cart-icon i {
        font-size: 1.4rem;
    }
    
    .cart-badge {
        width: 22px;
        height: 22px;
        font-size: 0.8rem;
        top: -10px;
        right: -10px;
    }
    
    .cart-count {
        font-size: 0.95rem;
    }
    
    .cart-total {
        font-size: 1.1rem;
    }
    
    .cart-items-preview {
        padding: 12px 16px;
        max-height: 250px;
    }
    
    .cart-item-mini {
        padding: 8px 0;
    }
    
    .item-info .item-name {
        font-size: 0.85rem;
    }
    
    .item-info .item-details {
        font-size: 0.75rem;
    }
    
    .item-total {
        font-size: 0.85rem;
    }
    
    .cart-actions {
        flex-direction: column;
        gap: 8px;
        margin-top: 12px;
        padding-top: 12px;
    }
    
    .btn-view-cart,
    .btn-checkout {
        padding: 12px 16px;
        font-size: 0.9rem;
        border-radius: 8px;
    }
}

/* Enhanced animations for mobile */
@keyframes pulse {
    0% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(255, 71, 87, 0.5);
    }
    50% {
        transform: scale(1.1);
        box-shadow: 0 0 20px rgba(255, 71, 87, 0.8);
    }
    100% {
        transform: scale(1);
        box-shadow: 0 0 10px rgba(255, 71, 87, 0.5);
    }
}

/* Mobile-specific hover effects */
@media (max-width: 768px) {
    .cart-header:active {
        transform: scale(0.98);
    }
    
    .btn-view-cart:active,
    .btn-checkout:active {
        transform: scale(0.95);
    }
}
</style>
<?php endif; ?>

<!-- Floating Cart JavaScript (only include once) -->
<?php if (!defined('FLOATING_CART_JS_INCLUDED')): ?>
<?php define('FLOATING_CART_JS_INCLUDED', true); ?>
<script>
// Floating Cart Functions
function toggleCartPreview() {
    const preview = $('#cartItemsPreview');
    const toggle = $('.cart-toggle');
    
    if (preview.is(':visible')) {
        preview.slideUp(300);
        toggle.removeClass('fa-chevron-down').addClass('fa-chevron-up');
    } else {
        preview.slideDown(300);
        toggle.removeClass('fa-chevron-up').addClass('fa-chevron-down');
    }
}

function updateFloatingCartDisplay(cartSummary) {
    // Handle both order and booking cart summaries
    const orderCount = cartSummary.order_summary ? cartSummary.order_summary.items_count : (cartSummary.items_count || 0);
    const bookingCount = cartSummary.booking_summary ? 
        (cartSummary.booking_summary.rooms_count + cartSummary.booking_summary.addons_count) : 0;
    const totalCount = orderCount + bookingCount;
    const totalAmount = (cartSummary.order_summary ? cartSummary.order_summary.grand_total : (cartSummary.grand_total || 0)) +
                       (cartSummary.booking_summary ? cartSummary.booking_summary.grand_total : 0);
    
    if (totalCount > 0) {
        $('#floatingCart').show();
        $('.cart-badge').text(totalCount);
        $('.cart-count').text(totalCount + ' items');
        $('.cart-total').text('KES ' + totalAmount.toLocaleString());
        
        // Update cart items preview
        let itemsHtml = '';
        
        // Add booking items
        if (cartSummary.booking_summary && cartSummary.booking_summary.rooms) {
            cartSummary.booking_summary.rooms.forEach(room => {
                itemsHtml += `
                    <div class="cart-item-mini booking-item">
                        <div class="item-info">
                            <span class="item-name">🏨 ${room.room_name}</span>
                            <span class="item-details">${room.days} nights × KES ${room.base_price.toLocaleString()}</span>
                        </div>
                        <span class="item-total">KES ${room.total.toLocaleString()}</span>
                    </div>
                `;
            });
        }
        
        // Add addon items
        if (cartSummary.booking_summary && cartSummary.booking_summary.addons) {
            cartSummary.booking_summary.addons.forEach(addon => {
                itemsHtml += `
                    <div class="cart-item-mini addon-item">
                        <div class="item-info">
                            <span class="item-name">➕ ${addon.name}</span>
                            <span class="item-details">Add-on service</span>
                        </div>
                        <span class="item-total">KES ${addon.price.toLocaleString()}</span>
                    </div>
                `;
            });
        }
        
        // Add order items
        const orderItems = cartSummary.order_summary ? cartSummary.order_summary.items : (cartSummary.items || {});
        Object.values(orderItems).forEach(item => {
            itemsHtml += `
                <div class="cart-item-mini order-item">
                    <div class="item-info">
                        <span class="item-name">🍽️ ${item.item_name}</span>
                        <span class="item-details">Qty: ${item.quantity} × KES ${item.unit_price.toLocaleString()}</span>
                    </div>
                    <span class="item-total">KES ${item.total_price.toLocaleString()}</span>
                </div>
            `;
        });
        
        // Determine correct paths based on current location
        const currentPath = window.location.pathname;
        let viewCartPath, checkoutPath;
        
        if (currentPath.includes('/modules/guest/menu/')) {
            viewCartPath = '../cart/view_cart.php';
            checkoutPath = 'order_checkout.php';
        } else if (currentPath.includes('/modules/guest/cart/')) {
            viewCartPath = 'view_cart.php';
            checkoutPath = '../menu/order_checkout.php';
        } else if (currentPath.includes('/modules/guest/booking/')) {
            viewCartPath = '../cart/view_cart.php';
            checkoutPath = '../menu/order_checkout.php';
        } else if (currentPath.includes('/modules/guest/payments/')) {
            viewCartPath = '../cart/view_cart.php';
            checkoutPath = '../menu/order_checkout.php';
        } else {
            // Main site pages (index.php, etc.)
            viewCartPath = 'modules/guest/cart/view_cart.php';
            checkoutPath = 'modules/guest/menu/order_checkout.php';
        }
        
        itemsHtml += `
            <div class="cart-actions">
                <a href="${viewCartPath}" class="btn-view-cart">View Cart</a>
                <a href="${checkoutPath}" class="btn-checkout">Checkout</a>
            </div>
        `;
        
        $('#cartItemsPreview').html(itemsHtml);
    } else {
        $('#floatingCart').hide();
    }
}

// Update header cart count when floating cart updates
function updateHeaderCartCount(itemCount) {
    // Update mobile header cart indicator
    const headerCartCount = document.getElementById('cartCount');
    if (headerCartCount) {
        headerCartCount.textContent = itemCount || 0;
        headerCartCount.style.display = itemCount > 0 ? 'flex' : 'none';
    }
    
    // Update mobile cart icon visibility
    const mobileCartIcon = document.querySelector('.mobile-cart-icon');
    if (mobileCartIcon) {
        if (itemCount > 0) {
            mobileCartIcon.style.display = '';  // Show with CSS responsive rules
        } else {
            mobileCartIcon.style.display = 'none';  // Hide when empty
        }
    }
    
    // Call the global refresh function if available
    if (typeof window.refreshCartCount === 'function') {
        window.refreshCartCount();
    }
}

// Global function to update both floating cart and header
window.updateCartDisplay = function(cartSummary) {
    updateFloatingCartDisplay(cartSummary);
    updateHeaderCartCount(cartSummary.items_count);
};

// Initialize cart display on page load
$(document).ready(function() {
    // Refresh cart data on page load if cart exists
    if ($('#floatingCart').length) {
        // Determine correct API path
        const apiPath = window.location.pathname.includes('/modules/guest/')
            ? '../../../../api/cart.php'
            : window.location.pathname.includes('/admin/')
                ? '../api/cart.php'
                : 'api/cart.php';
        
        // Load current cart state via AJAX
        fetch(apiPath, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_cart_summary'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.cart_summary) {
                updateFloatingCartDisplay(data.cart_summary);
            }
        })
        .catch(error => {
            console.log('Cart refresh failed:', error);
        });
    }
});
</script>
<?php endif; ?>
