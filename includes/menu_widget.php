<?php
// Menu Widget Include File
// Feature-rich menu component with search, filters, and cart functionality
// Supports both simple and advanced modes for backward compatibility

// Default parameters
if (!isset($widget_title)) $widget_title = "Our Menu";
if (!isset($widget_items_limit)) $widget_items_limit = 8;
if (!isset($widget_show_prices)) $widget_show_prices = true;
if (!isset($widget_show_filters)) $widget_show_filters = true;
if (!isset($widget_show_search)) $widget_show_search = true;
if (!isset($widget_show_cart_actions)) $widget_show_cart_actions = true;
if (!isset($widget_style)) $widget_style = "compact"; // compact, full

// Backward compatibility for old parameter names
if (isset($widget_show_order_btn) && !isset($widget_show_cart_actions)) {
    $widget_show_cart_actions = $widget_show_order_btn;
}

// Get filter parameters from URL or defaults
$widget_search = isset($_GET['widget_search']) ? mysqli_real_escape_string($con, $_GET['widget_search']) : '';
$widget_category = isset($_GET['widget_category']) ? (int)$_GET['widget_category'] : 0;
$widget_dietary = isset($_GET['widget_dietary']) ? $_GET['widget_dietary'] : '';
$widget_sort = isset($_GET['widget_sort']) ? $_GET['widget_sort'] : 'name';

// Build WHERE clause for filtering
$widget_where_conditions = ["mi.is_available = 1"];

if (!empty($widget_search)) {
    $widget_where_conditions[] = "(mi.name LIKE '%$widget_search%' OR mi.description LIKE '%$widget_search%')";
}

if ($widget_category > 0) {
    $widget_where_conditions[] = "mi.category_id = $widget_category";
}

switch ($widget_dietary) {
    case 'vegetarian':
        $widget_where_conditions[] = "mi.is_vegetarian = 1";
        break;
    case 'gluten_free':
        $widget_where_conditions[] = "mi.is_gluten_free = 1";
        break;
    case 'spicy':
        $widget_where_conditions[] = "mi.is_spicy = 1";
        break;
}

$widget_where_clause = implode(' AND ', $widget_where_conditions);

// Sorting
$widget_order_clause = "ORDER BY ";
switch ($widget_sort) {
    case 'price_low':
        $widget_order_clause .= "mi.price ASC";
        break;
    case 'price_high':
        $widget_order_clause .= "mi.price DESC";
        break;
    case 'category':
        $widget_order_clause .= "mc.name ASC, mi.name ASC";
        break;
    default:
        $widget_order_clause .= "mi.name ASC";
}

// Get menu items with filters applied
$widget_query = "SELECT mi.*, mc.name as category_name
                 FROM menu_items mi 
                 LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
                 WHERE $widget_where_clause 
                 $widget_order_clause 
                 LIMIT $widget_items_limit";
$widget_result = mysqli_query($con, $widget_query);
$widget_items = [];
while($item = mysqli_fetch_assoc($widget_result)) {
    $widget_items[] = $item;
}

// Get categories for filter dropdown
$widget_categories_query = "SELECT id, name FROM menu_categories WHERE is_active = 1 ORDER BY name";
$widget_categories_result = mysqli_query($con, $widget_categories_query);
$widget_categories = [];
while($category = mysqli_fetch_assoc($widget_categories_result)) {
    $widget_categories[] = $category;
}

// Get cart summary if cart system is available
$widget_cart_count = 0;
if (class_exists('CartManager')) {
    $cart_summary = CartManager::getOrderCartSummary();
    $widget_cart_count = $cart_summary['items_count'] ?? 0;
}
?>

<style>
/* Enhanced Menu Widget Styles */
.menu-widget-enhanced {
    background: white;
    border-radius: 20px;
    padding: 25px;
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
    margin-bottom: 30px;
    border: 1px solid rgba(102, 126, 234, 0.1);
}

.menu-widget-enhanced.full-style {
    padding: 30px;
    border-radius: 25px;
}

.widget-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #667eea;
}

.widget-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.widget-cart-indicator {
    background: #667eea;
    color: white;
    border-radius: 15px;
    padding: 5px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 5px;
    min-width: 60px;
    justify-content: center;
}

.widget-cart-indicator.has-items {
    background: #e74c3c;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

/* Widget Filters */
.widget-filters {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 25px;
    border: 1px solid #e9ecef;
}

.widget-filters.compact {
    padding: 15px;
    border-radius: 12px;
}

.filter-row {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.filter-row:last-child {
    margin-bottom: 0;
}

.widget-search-box {
    flex: 1;
    min-width: 200px;
    position: relative;
}

.widget-search-box input {
    width: 100%;
    padding: 10px 40px 10px 15px;
    border: 2px solid #ddd;
    border-radius: 25px;
    font-size: 0.9rem;
    transition: all 0.3s ease;
}

.widget-search-box input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.widget-search-btn {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
}

.widget-search-btn:hover {
    background: #5a67d8;
    transform: translateY(-50%) scale(1.1);
}

.widget-filter-select {
    padding: 8px 15px;
    border: 2px solid #ddd;
    border-radius: 20px;
    font-size: 0.85rem;
    background: white;
    min-width: 120px;
    transition: all 0.3s ease;
}

.widget-filter-select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.filter-actions {
    display: flex;
    gap: 10px;
}

.widget-filter-btn {
    background: #667eea;
    color: white;
    border: none;
    border-radius: 15px;
    padding: 8px 15px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.widget-filter-btn:hover {
    background: #5a67d8;
    transform: translateY(-1px);
}

.widget-filter-btn.clear {
    background: #6c757d;
}

.widget-filter-btn.clear:hover {
    background: #5a6268;
}

/* Widget Items */
.widget-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.widget-items-grid.compact {
    grid-template-columns: 1fr;
    gap: 15px;
}

.widget-menu-item {
    background: white;
    border-radius: 15px;
    padding: 15px;
    border: 2px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.widget-menu-item:hover {
    border-color: #667eea;
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
}

.widget-menu-item.compact {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
}

.item-image {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(45deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
    position: relative;
    overflow: hidden;
}

.item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
}

.item-badges {
    position: absolute;
    top: -5px;
    right: -5px;
    display: flex;
    gap: 2px;
    flex-direction: column;
}

.item-badge {
    background: rgba(255, 255, 255, 0.9);
    border-radius: 50%;
    width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.7rem;
    border: 1px solid #ddd;
}

.item-content {
    flex: 1;
}

.item-category {
    font-size: 0.75rem;
    color: #667eea;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 5px;
    font-weight: 600;
}

.item-name {
    font-weight: 700;
    color: #2c3e50;
    margin: 0 0 8px 0;
    font-size: 1rem;
    line-height: 1.2;
}

.item-description {
    font-size: 0.85rem;
    color: #666;
    margin: 0 0 12px 0;
    line-height: 1.4;
}

.item-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-top: 15px;
}

.item-price {
    font-weight: 700;
    color: #e74c3c;
    font-size: 1.1rem;
}

.item-actions {
    display: flex;
    align-items: center;
    gap: 10px;
}

.qty-controls {
    display: flex;
    align-items: center;
    background: #f8f9fa;
    border-radius: 20px;
    padding: 3px;
}

.qty-btn {
    background: #667eea;
    color: white;
    border: none;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 0.8rem;
    transition: all 0.3s ease;
}

.qty-btn:hover {
    background: #5a67d8;
    transform: scale(1.1);
}

.qty-input {
    width: 35px;
    text-align: center;
    border: none;
    background: transparent;
    font-weight: 600;
    font-size: 0.9rem;
    margin: 0 5px;
}

.item-add-btn {
    background: #28a745;
    color: white;
    border: none;
    border-radius: 15px;
    padding: 6px 12px;
    font-size: 0.8rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 5px;
}

.item-add-btn:hover {
    background: #218838;
    transform: translateY(-1px);
}

.item-add-btn:disabled {
    background: #6c757d;
    cursor: not-allowed;
    transform: none;
}

/* Widget Footer */
.widget-footer {
    text-align: center;
    margin-top: 25px;
    padding-top: 20px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
}

.widget-footer-btn {
    background: #667eea;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 12px 25px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.widget-footer-btn:hover {
    background: #5a67d8;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.widget-footer-btn.secondary {
    background: #6c757d;
}

.widget-footer-btn.secondary:hover {
    background: #5a6268;
}

/* No Results */
.widget-no-results {
    text-align: center;
    padding: 40px 20px;
    color: #666;
}

.widget-no-results i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 15px;
}

.widget-no-results h4 {
    margin-bottom: 10px;
    color: #888;
}

/* Responsive */
@media (max-width: 768px) {
    .widget-items-grid {
        grid-template-columns: 1fr;
    }
    
    .filter-row {
        flex-direction: column;
        gap: 10px;
    }
    
    .widget-search-box {
        min-width: auto;
    }
    
    .widget-footer {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<!-- Enhanced Menu Widget -->
<div class="menu-widget-enhanced <?php echo $widget_style; ?>-style">
    <!-- Widget Header -->
    <div class="widget-header">
        <h4 class="widget-title">
            <i class="fa fa-utensils"></i> 
            <?php echo htmlspecialchars($widget_title); ?>
        </h4>
        <?php if ($widget_show_cart_actions && $widget_cart_count > 0): ?>
        <div class="widget-cart-indicator has-items">
            <i class="fa fa-shopping-cart"></i>
            <span><?php echo $widget_cart_count; ?></span>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Widget Filters -->
    <?php if ($widget_show_filters || $widget_show_search): ?>
    <div class="widget-filters <?php echo $widget_style; ?>">
        <!-- Search and Quick Filters -->
        <div class="filter-row">
            <?php if ($widget_show_search): ?>
            <div class="widget-search-box">
                <input type="text" id="widgetSearch" placeholder="Search menu items..." 
                       value="<?php echo htmlspecialchars($widget_search); ?>">
                <button type="button" class="widget-search-btn" onclick="applyWidgetFilters()">
                    <i class="fa fa-search"></i>
                </button>
            </div>
            <?php endif; ?>
            
            <?php if ($widget_show_filters): ?>
            <select id="widgetCategory" class="widget-filter-select" onchange="applyWidgetFilters()">
                <option value="0">All Categories</option>
                <?php foreach($widget_categories as $category): ?>
                <option value="<?php echo $category['id']; ?>" <?php echo $widget_category == $category['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($category['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
            
            <select id="widgetDietary" class="widget-filter-select" onchange="applyWidgetFilters()">
                <option value="">All Options</option>
                <option value="vegetarian" <?php echo $widget_dietary == 'vegetarian' ? 'selected' : ''; ?>>🥬 Vegetarian</option>
                <option value="gluten_free" <?php echo $widget_dietary == 'gluten_free' ? 'selected' : ''; ?>>🌾 Gluten Free</option>
                <option value="spicy" <?php echo $widget_dietary == 'spicy' ? 'selected' : ''; ?>>🌶️ Spicy</option>
            </select>
            
            <select id="widgetSort" class="widget-filter-select" onchange="applyWidgetFilters()">
                <option value="name" <?php echo $widget_sort == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                <option value="price_low" <?php echo $widget_sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                <option value="price_high" <?php echo $widget_sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                <option value="category" <?php echo $widget_sort == 'category' ? 'selected' : ''; ?>>Category</option>
            </select>
            <?php endif; ?>
        </div>
        
        <?php if ($widget_show_filters): ?>
        <div class="filter-actions">
            <button type="button" class="widget-filter-btn" onclick="applyWidgetFilters()">
                <i class="fa fa-filter"></i> Apply
            </button>
            <button type="button" class="widget-filter-btn clear" onclick="clearWidgetFilters()">
                <i class="fa fa-times"></i> Clear
            </button>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    
    <!-- Widget Items -->
    <?php if (count($widget_items) > 0): ?>
    <div class="widget-items-grid <?php echo $widget_style; ?>">
        <?php foreach ($widget_items as $index => $item): ?>
        <div class="widget-menu-item <?php echo $widget_style; ?>">
            <div class="item-image">
                <?php if (!empty($item['image'])): ?>
                    <img src="<?php echo file_exists("images/menu/".$item['image']) ? "images/menu/".$item['image'] : "modules/guest/menu/../../../images/menu/".$item['image']; ?>" 
                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <i class="fa fa-utensils" style="display: none;"></i>
                <?php else: ?>
                    <i class="fa fa-utensils"></i>
                <?php endif; ?>
                
                <!-- Dietary Badges -->
                <div class="item-badges">
                    <?php if ($item['is_vegetarian']): ?>
                        <span class="item-badge" title="Vegetarian">🥬</span>
                    <?php endif; ?>
                    <?php if ($item['is_gluten_free']): ?>
                        <span class="item-badge" title="Gluten Free">🌾</span>
                    <?php endif; ?>
                    <?php if ($item['is_spicy']): ?>
                        <span class="item-badge" title="Spicy">🌶️</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="item-content">
                <?php if ($item['category_name']): ?>
                <div class="item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                <?php endif; ?>
                
                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                <div class="item-description">
                    <?php 
                    $desc = $item['description'] ?? '';
                    echo htmlspecialchars($widget_style == 'compact' ? substr($desc, 0, 80) : substr($desc, 0, 120)); 
                    echo strlen($desc) > ($widget_style == 'compact' ? 80 : 120) ? '...' : ''; 
                    ?>
                </div>
                
                <div class="item-meta">
                    <?php if ($widget_show_prices): ?>
                    <div class="item-price">KES <?php echo number_format($item['price'], 0); ?></div>
                    <?php endif; ?>
                    
                    <?php if ($widget_show_cart_actions): ?>
                    <div class="item-actions">
                        <div class="qty-controls">
                            <button class="qty-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, -1)">-</button>
                            <input type="number" class="qty-input" id="qty-<?php echo $item['id']; ?>" value="1" min="1" max="10" readonly>
                            <button class="qty-btn" onclick="changeQuantity(<?php echo $item['id']; ?>, 1)">+</button>
                        </div>
                        <button class="item-add-btn" onclick="addToCartFromWidget(<?php echo $item['id']; ?>, '<?php echo addslashes($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes($item['category_name'] ?? ''); ?>')">
                            <i class="fa fa-plus"></i> Add
                        </button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="widget-no-results">
        <i class="fa fa-search"></i>
        <h4>No items found</h4>
        <p>Try adjusting your filters or search terms</p>
    </div>
    <?php endif; ?>
    
    <!-- Widget Footer -->
    <div class="widget-footer">
        <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php" class="widget-footer-btn">
            <i class="fa fa-eye"></i> View Full Menu
        </a>
        
        <?php if ($widget_show_cart_actions && $widget_cart_count > 0): ?>
        <a href="http://localhost/Hotel/modules/guest/cart/view_cart.php" class="widget-footer-btn secondary">
            <i class="fa fa-shopping-cart"></i> View Cart (<?php echo $widget_cart_count; ?>)
        </a>
        <?php endif; ?>
    </div>
</div>

<script>
// Widget JavaScript Functions
function applyWidgetFilters() {
    const params = new URLSearchParams(window.location.search);
    
    // Get current filter values
    const search = document.getElementById('widgetSearch')?.value.trim() || '';
    const category = document.getElementById('widgetCategory')?.value || '0';
    const dietary = document.getElementById('widgetDietary')?.value || '';
    const sort = document.getElementById('widgetSort')?.value || 'name';
    
    // Update URL parameters
    if (search) params.set('widget_search', search);
    else params.delete('widget_search');
    
    if (category && category !== '0') params.set('widget_category', category);
    else params.delete('widget_category');
    
    if (dietary) params.set('widget_dietary', dietary);
    else params.delete('widget_dietary');
    
    if (sort && sort !== 'name') params.set('widget_sort', sort);
    else params.delete('widget_sort');
    
    // Reload page with new parameters
    window.location.search = params.toString();
}

function clearWidgetFilters() {
    const params = new URLSearchParams(window.location.search);
    
    // Remove widget-specific parameters
    params.delete('widget_search');
    params.delete('widget_category');
    params.delete('widget_dietary');
    params.delete('widget_sort');
    
    // Reload page with cleared parameters
    window.location.search = params.toString();
}

function changeQuantity(itemId, change) {
    const input = document.getElementById('qty-' + itemId);
    const currentVal = parseInt(input.value) || 1;
    const newVal = Math.max(1, Math.min(10, currentVal + change));
    input.value = newVal;
}

function addToCartFromWidget(itemId, itemName, itemPrice, itemType) {
    const qtyInput = document.getElementById('qty-' + itemId);
    const quantity = parseInt(qtyInput.value) || 1;
    const button = event.target.closest('.item-add-btn');
    
    // Show loading state
    const originalContent = button.innerHTML;
    button.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Adding...';
    button.disabled = true;
    
    // Use enhanced cart API
    if (typeof $ !== 'undefined') {
        // Determine correct API path based on current location
        const apiPath = window.location.pathname.includes('/admin/') 
            ? '../api/cart.php' 
            : window.location.pathname.includes('/modules/') 
                ? '../../../api/cart.php'
                : 'api/cart.php';
                
        $.post(apiPath, {
            action: 'add_item_to_order_cart',
            item_id: itemId,
            item_name: itemName,
            item_type: itemType || 'menu_item',
            unit_price: itemPrice,
            quantity: quantity,
            description: '',
            image: ''
        }, function(response) {
            if (response.success) {
                showWidgetNotification('Added ' + itemName + ' to cart!', 'success');
                
                // Reset quantity to 1
                qtyInput.value = 1;
                
                // Update cart indicator
                updateWidgetCartIndicator(response.cart_summary);
                
                // Show success state
                button.innerHTML = '<i class="fa fa-check"></i> Added!';
                setTimeout(function() {
                    button.innerHTML = originalContent;
                    button.disabled = false;
                }, 1500);
            } else {
                showWidgetNotification(response.message || 'Error adding item to cart', 'error');
                button.innerHTML = originalContent;
                button.disabled = false;
            }
        }, 'json').fail(function() {
            showWidgetNotification('Network error. Please try again.', 'error');
            button.innerHTML = originalContent;
            button.disabled = false;
        });
    } else {
        // Fallback to simple redirect
        window.location.href = 'add_to_cart.php?action=add&item_id=' + itemId + '&quantity=' + quantity;
    }
}

function updateWidgetCartIndicator(cartSummary) {
    const indicator = document.querySelector('.widget-cart-indicator');
    if (indicator && cartSummary) {
        const count = cartSummary.items_count || 0;
        indicator.innerHTML = '<i class="fa fa-shopping-cart"></i> ' + count;
        
        if (count > 0) {
            indicator.classList.add('has-items');
        } else {
            indicator.classList.remove('has-items');
        }
        
        // Update any other cart indicators on the page
        const headerCartCount = document.getElementById('cartCount');
        if (headerCartCount) {
            headerCartCount.textContent = count;
            headerCartCount.style.display = count > 0 ? 'flex' : 'none';
        }
        
        // Update mobile cart icon visibility
        const mobileCartIcon = document.querySelector('.mobile-cart-icon');
        if (mobileCartIcon) {
            mobileCartIcon.style.display = count > 0 ? 'flex' : 'none';
        }
        
        // Update floating cart if global function is available
        if (typeof window.updateCartDisplay === 'function') {
            window.updateCartDisplay(cartSummary);
        }
    }
}

function showWidgetNotification(message, type) {
    // Remove any existing notifications
    const existingNotifications = document.querySelectorAll('.widget-notification');
    existingNotifications.forEach(n => n.remove());
    
    // Create notification element
    const notification = document.createElement('div');
    notification.className = 'widget-notification';
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? 'linear-gradient(135deg, #28a745 0%, #20c997 100%)' : 'linear-gradient(135deg, #dc3545 0%, #c82333 100%)'};
        color: white;
        padding: 12px 20px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 10000;
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        animation: slideInRight 0.3s ease-out;
        min-width: 250px;
        max-width: 350px;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 10px;
    `;
    
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    notification.innerHTML = `<i class="fa ${icon}"></i> <span>${message}</span>`;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto-remove after 4 seconds
    setTimeout(function() {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }, 4000);
    
    // Click to dismiss
    notification.addEventListener('click', function() {
        if (notification.parentNode) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(function() {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    });
}

// Add CSS animations
if (!document.getElementById('widget-animations')) {
    const style = document.createElement('style');
    style.id = 'widget-animations';
    style.textContent = `
        @keyframes slideInRight {
            from { transform: translateX(100%); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        @keyframes slideOutRight {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Search on Enter key
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('widgetSearch');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyWidgetFilters();
            }
        });
    }
});
</script>
