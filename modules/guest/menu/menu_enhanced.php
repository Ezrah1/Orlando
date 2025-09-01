<?php
$page_title = 'Our Menu - Orlando International Resorts'; // Will be updated by hotel_settings from header
$page_description = 'Orlando International Resorts - Restaurant Menu';

// Database connection
require_once '../../../db.php';
require_once '../../../cart_manager.php';

include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');

// Initialize cart
CartManager::initCarts();

// Get search and filter parameters  
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$dietary_filter = isset($_GET['dietary']) ? $_GET['dietary'] : '';
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 10000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 12;
$offset = ($page - 1) * $items_per_page;

// Build WHERE clause
$where_conditions = ["mi.is_available = 1"];

if (!empty($search)) {
    $where_conditions[] = "(mi.name LIKE '%$search%' OR mi.description LIKE '%$search%')";
}

if ($category_filter > 0) {
    $where_conditions[] = "mi.category_id = $category_filter";
}

if ($price_min > 0) {
    $where_conditions[] = "mi.price >= $price_min";
}

if ($price_max < 10000) {
    $where_conditions[] = "mi.price <= $price_max";
}

switch ($dietary_filter) {
    case 'vegetarian':
        $where_conditions[] = "mi.is_vegetarian = 1";
        break;
    case 'gluten_free':
        $where_conditions[] = "mi.is_gluten_free = 1";
        break;
    case 'spicy':
        $where_conditions[] = "mi.is_spicy = 1";
        break;
}

$where_clause = implode(' AND ', $where_conditions);

// Sorting options
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "mi.price ASC";
        break;
    case 'price_high':
        $order_clause .= "mi.price DESC";
        break;
    case 'prep_time':
        $order_clause .= "mi.preparation_time ASC";
        break;
    case 'category':
        $order_clause .= "mc.name ASC, mi.name ASC";
        break;
    default:
        $order_clause .= "mi.name ASC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id 
                WHERE $where_clause";
$count_result = mysqli_query($con, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get menu items with pagination
$menu_query = "SELECT mi.*, mc.name as category_name 
               FROM menu_items mi 
               JOIN menu_categories mc ON mi.category_id = mc.id 
               WHERE $where_clause 
               $order_clause 
               LIMIT $items_per_page OFFSET $offset";
$menu_result = mysqli_query($con, $menu_query);

// Get all categories for filter dropdown
$categories_query = "SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY display_order";
$categories_result = mysqli_query($con, $categories_query);

// Get price range for slider
$price_range_query = "SELECT MIN(price) as min_price, MAX(price) as max_price FROM menu_items WHERE is_available = 1";
$price_range_result = mysqli_query($con, $price_range_query);
$price_range = mysqli_fetch_assoc($price_range_result);

// Get cart summary
$cart_summary = CartManager::getOrderCartSummary();
$cart_count = $cart_summary['items_count'];
?>

<!-- Modern Shopping Menu Section -->
<div class="shopping-menu-section">
    <!-- Hero Shopping Banner -->
    <div class="shopping-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">
                    <i class="fa fa-utensils"></i>
                    Delicious Food & Drinks
                </h1>
                <p class="hero-subtitle">Premium dining delivered to your room or enjoy in our restaurants</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_items; ?></span>
                        <span class="stat-label">Menu Items</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">30min</span>
                        <span class="stat-label">Avg Delivery</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">⭐4.8</span>
                        <span class="stat-label">Rating</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Modern Sidebar Filters -->
            <div class="col-lg-3 col-md-4">
                <div class="shopping-sidebar">
                    <div class="sidebar-header">
                        <h3><i class="fa fa-filter"></i> Filter & Search</h3>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="filter-section">
                        <div class="search-box">
                            <input type="text" id="search-input" placeholder="Search menu items..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="button" id="search-btn"><i class="fa fa-search"></i></button>
                        </div>
                    </div>

                    <!-- Categories Filter -->
                    <div class="filter-section">
                        <h4><i class="fa fa-list"></i> Categories</h4>
                        <div class="category-filters">
                            <label class="filter-option <?php echo $category_filter == 0 ? 'active' : ''; ?>">
                                <input type="radio" name="category" value="0" <?php echo $category_filter == 0 ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                All Categories
                            </label>
                            <?php while($category = mysqli_fetch_assoc($categories_result)): ?>
                            <label class="filter-option <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                                <input type="radio" name="category" value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                <i class="fa <?php echo $category['icon'] ?? 'fa-star'; ?>"></i>
                                <?php echo htmlspecialchars($category['name']); ?>
                            </label>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Price Range -->
                    <div class="filter-section">
                        <h4><i class="fa fa-money-bill"></i> Price Range</h4>
                        <div class="price-range-slider">
                            <div class="price-inputs">
                                <input type="number" id="price-min" placeholder="Min" value="<?php echo $price_min; ?>" min="0">
                                <span>to</span>
                                <input type="number" id="price-max" placeholder="Max" value="<?php echo $price_max == 10000 ? '' : $price_max; ?>" min="0">
                            </div>
                            <div class="price-display">
                                KES <?php echo number_format($price_range['min_price']); ?> - KES <?php echo number_format($price_range['max_price']); ?>
                            </div>
                        </div>
                    </div>

                    <!-- Dietary Options -->
                    <div class="filter-section">
                        <h4><i class="fa fa-leaf"></i> Dietary Options</h4>
                        <div class="dietary-filters">
                            <label class="filter-option <?php echo $dietary_filter == '' ? 'active' : ''; ?>">
                                <input type="radio" name="dietary" value="" <?php echo $dietary_filter == '' ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                All Options
                            </label>
                            <label class="filter-option <?php echo $dietary_filter == 'vegetarian' ? 'active' : ''; ?>">
                                <input type="radio" name="dietary" value="vegetarian" <?php echo $dietary_filter == 'vegetarian' ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                🥬 Vegetarian
                            </label>
                            <label class="filter-option <?php echo $dietary_filter == 'gluten_free' ? 'active' : ''; ?>">
                                <input type="radio" name="dietary" value="gluten_free" <?php echo $dietary_filter == 'gluten_free' ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                🌾 Gluten Free
                            </label>
                            <label class="filter-option <?php echo $dietary_filter == 'spicy' ? 'active' : ''; ?>">
                                <input type="radio" name="dietary" value="spicy" <?php echo $dietary_filter == 'spicy' ? 'checked' : ''; ?>>
                                <span class="checkmark"></span>
                                🌶️ Spicy
                            </label>
                        </div>
                    </div>

                    <!-- Apply Filters Button -->
                    <div class="filter-actions">
                        <button type="button" id="apply-filters" class="btn-apply-filters">
                            <i class="fa fa-check"></i> Apply Filters
                        </button>
                        <button type="button" id="clear-filters" class="btn-clear-filters">
                            <i class="fa fa-times"></i> Clear All
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main Shopping Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Shopping Toolbar -->
                <div class="shopping-toolbar">
                    <div class="toolbar-left">
                        <span class="results-count">
                            Showing <?php echo min($items_per_page, $total_items - $offset); ?> of <?php echo $total_items; ?> items
                        </span>
                    </div>
                    <div class="toolbar-right">
                        <div class="sort-dropdown">
                            <select id="sort-select" class="form-control custom-select enhanced">
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="category" <?php echo $sort_by == 'category' ? 'selected' : ''; ?>>Category</option>
                                <option value="prep_time" <?php echo $sort_by == 'prep_time' ? 'selected' : ''; ?>>Preparation Time</option>
                            </select>
                        </div>
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="grid"><i class="fa fa-th"></i></button>
                            <button class="view-btn" data-view="list"><i class="fa fa-list"></i></button>
                        </div>
                    </div>
                </div>

                <!-- Shopping Grid -->
                <div class="shopping-grid" id="menu-items-grid">
                    <?php if (mysqli_num_rows($menu_result) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($menu_result)): ?>
                        <div class="product-card" data-category="<?php echo $item['category_id']; ?>">
                            <div class="product-image">
                                <?php if (!empty($item['image'])): ?>
                                    <img src="../../../images/menu/<?php echo $item['image']; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                <?php else: ?>
                                    <div class="default-image">
                                        <i class="fa fa-utensils"></i>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Product Badges -->
                                <div class="product-badges">
                                    <?php if ($item['is_vegetarian']): ?>
                                        <span class="badge badge-vegetarian">🥬</span>
                                    <?php endif; ?>
                                    <?php if ($item['is_gluten_free']): ?>
                                        <span class="badge badge-gluten-free">🌾</span>
                                    <?php endif; ?>
                                    <?php if ($item['is_spicy']): ?>
                                        <span class="badge badge-spicy">🌶️</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quick Add Button -->
                                <div class="quick-add">
                                    <button class="btn-quick-add" data-item-id="<?php echo $item['id']; ?>">
                                        <i class="fa fa-plus"></i> Quick Add
                                    </button>
                                </div>
                            </div>
                            
                            <div class="product-info">
                                <div class="product-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <h3 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p class="product-description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                
                                <div class="product-meta">
                                    <?php if (!empty($item['preparation_time'])): ?>
                                        <span class="prep-time">
                                            <i class="fa fa-clock"></i> <?php echo $item['preparation_time']; ?> min
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-footer">
                                    <div class="product-price">
                                        <span class="price">KES <?php echo number_format($item['price'], 0); ?></span>
                                    </div>
                                    <div class="product-actions">
                                        <div class="quantity-selector">
                                            <button class="qty-btn minus" data-item-id="<?php echo $item['id']; ?>">-</button>
                                            <input type="number" class="qty-input" value="1" min="1" max="10" data-item-id="<?php echo $item['id']; ?>">
                                            <button class="qty-btn plus" data-item-id="<?php echo $item['id']; ?>">+</button>
                                        </div>
                                        <button class="btn-add-to-cart" data-item-id="<?php echo $item['id']; ?>" data-item-name="<?php echo htmlspecialchars($item['name']); ?>" data-item-price="<?php echo $item['price']; ?>" data-item-type="<?php echo htmlspecialchars($item['category_name']); ?>">
                                            <i class="fa fa-shopping-cart"></i> Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fa fa-search"></i>
                            </div>
                            <h3>No items found</h3>
                            <p>Try adjusting your filters or search terms</p>
                            <button class="btn btn-primary" id="reset-filters">Reset Filters</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="shopping-pagination">
                    <nav aria-label="Menu pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query($_GET); ?>">
                                        <i class="fa fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query($_GET); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query($_GET); ?>">
                                        Next <i class="fa fa-chevron-right"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Floating Shopping Cart is now globally included via footer -->

<!-- Modern Shopping Styles -->
<style>
/* ===== MODERN SHOPPING MENU STYLES ===== */

.shopping-menu-section {
    background: #f8f9fa;
    min-height: 100vh;
    padding-top: 80px;
}

/* Shopping Hero Section */
.shopping-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 60px 0;
    margin-bottom: 40px;
}

.hero-title {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 1.3rem;
    opacity: 0.9;
    margin-bottom: 40px;
}

.hero-stats {
    display: flex;
    justify-content: center;
    gap: 40px;
    flex-wrap: wrap;
}

.stat-item {
    text-align: center;
}

.stat-number {
    display: block;
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    opacity: 0.8;
}

/* Modern Sidebar */
.shopping-sidebar {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
    margin-bottom: 30px;
}

.sidebar-header h3 {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
    display: flex;
    align-items: center;
    gap: 10px;
}

.filter-section {
    margin-bottom: 30px;
}

.filter-section h4 {
    color: #495057;
    font-weight: 600;
    margin-bottom: 15px;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* Search Box */
.search-box {
    position: relative;
}

.search-box input {
    width: 100%;
    padding: 12px 45px 12px 15px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
}

.search-box input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    outline: none;
}

.search-box button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: #667eea;
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-box button:hover {
    background: #5a6fd8;
    transform: translateY(-50%) scale(1.05);
}

/* Filter Options */
.filter-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 0;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
    padding: 10px 12px;
    margin-bottom: 5px;
}

.filter-option:hover {
    background: #f8f9fa;
}

.filter-option.active {
    background: rgba(102, 126, 234, 0.1);
    color: #667eea;
    font-weight: 600;
}

.filter-option input {
    margin: 0;
}

.checkmark {
    width: 18px;
    height: 18px;
    border: 2px solid #dee2e6;
    border-radius: 50%;
    position: relative;
    transition: all 0.3s ease;
}

.filter-option.active .checkmark {
    border-color: #667eea;
    background: #667eea;
}

.filter-option.active .checkmark::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 6px;
    width: 4px;
    height: 8px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}

/* Price Range */
.price-inputs {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.price-inputs input {
    flex: 1;
    padding: 8px 12px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    font-size: 0.9rem;
}

.price-inputs span {
    color: #6c757d;
    font-weight: 500;
}

.price-display {
    color: #6c757d;
    font-size: 0.85rem;
    text-align: center;
}

/* Filter Actions */
.filter-actions {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.btn-apply-filters {
    flex: 1;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 12px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-apply-filters:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
}

.btn-clear-filters {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    color: #6c757d;
    padding: 12px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-clear-filters:hover {
    background: #e9ecef;
    color: #495057;
}

/* Shopping Toolbar */
.shopping-toolbar {
    background: white;
    padding: 20px 25px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    flex-wrap: wrap;
    gap: 15px;
}

.results-count {
    color: #6c757d;
    font-weight: 500;
}

.toolbar-right {
    display: flex;
    align-items: center;
    gap: 15px;
}

.sort-dropdown select {
    padding: 8px 15px;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    background: white;
    cursor: pointer;
}

.view-toggle {
    display: flex;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
}

.view-btn {
    padding: 8px 12px;
    background: white;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.view-btn.active {
    background: #667eea;
    color: white;
}

/* Shopping Grid */
.shopping-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

/* Product Cards */
.product-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
    cursor: pointer;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.product-image {
    position: relative;
    height: 200px;
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    overflow: hidden;
}

.product-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}

.product-card:hover .product-image img {
    transform: scale(1.05);
}

.default-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 3rem;
    color: #dee2e6;
}

.product-badges {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    gap: 5px;
}

.badge {
    background: rgba(255,255,255,0.9);
    padding: 4px 8px;
    border-radius: 15px;
    font-size: 0.8rem;
    backdrop-filter: blur(10px);
}

.quick-add {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: all 0.3s ease;
}

.product-card:hover .quick-add {
    opacity: 1;
}

.btn-quick-add {
    background: rgba(102, 126, 234, 0.9);
    border: none;
    color: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
}

.btn-quick-add:hover {
    background: rgba(102, 126, 234, 1);
    transform: scale(1.05);
}

/* Product Info */
.product-info {
    padding: 20px;
}

.product-category {
    color: #667eea;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.product-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 8px;
    line-height: 1.3;
}

.product-description {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 12px;
    height: 40px;
    overflow: hidden;
}

.product-meta {
    margin-bottom: 15px;
}

.prep-time {
    color: #28a745;
    font-size: 0.85rem;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

/* Product Footer */
.product-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 20px;
}

.product-price .price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #28a745;
}

.product-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    align-items: flex-end;
}

.quantity-selector {
    display: flex;
    align-items: center;
    border: 1px solid #dee2e6;
    border-radius: 6px;
    overflow: hidden;
}

.qty-btn {
    background: #f8f9fa;
    border: none;
    padding: 6px 10px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
}

.qty-btn:hover {
    background: #e9ecef;
}

.qty-input {
    border: none;
    width: 50px;
    text-align: center;
    padding: 6px 4px;
    font-weight: 600;
}

.btn-add-to-cart {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-add-to-cart:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

/* No Results */
.no-results {
    grid-column: 1 / -1;
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-results-icon {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

.no-results h3 {
    margin-bottom: 10px;
    color: #495057;
}

/* Pagination */
.shopping-pagination {
    display: flex;
    justify-content: center;
    margin-top: 40px;
}

.pagination {
    display: flex;
    gap: 5px;
    list-style: none;
    margin: 0;
    padding: 0;
}

.page-item .page-link {
    background: white;
    border: 1px solid #dee2e6;
    color: #495057;
    padding: 10px 15px;
    border-radius: 8px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.page-item.active .page-link {
    background: #667eea;
    border-color: #667eea;
    color: white;
}

.page-item .page-link:hover {
    background: #f8f9fa;
    text-decoration: none;
    color: #495057;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .shopping-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .shopping-menu-section {
        padding-top: 60px;
    }
    
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-stats {
        gap: 20px;
    }
    
    .stat-number {
        font-size: 1.5rem;
    }
    
    .shopping-sidebar {
        margin-bottom: 20px;
        position: static;
    }
    
    .shopping-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-right {
        justify-content: space-between;
    }
    
    .shopping-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .product-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .product-actions {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

@media (max-width: 480px) {
    .shopping-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<!-- Modern Shopping JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // Search functionality
    $('#search-btn, #search-input').on('click keypress', function(e) {
        if (e.type === 'click' || e.which === 13) {
            applyFilters();
        }
    });
    
    // Filter change handlers
    $('input[name="category"], input[name="dietary"], #sort-select').on('change', function() {
        if ($(this).is('#sort-select')) {
            applyFilters();
        }
    });
    
    // Price range inputs
    $('#price-min, #price-max').on('change', function() {
        // Auto-apply price filters after a brief delay
        clearTimeout(window.priceTimeout);
        window.priceTimeout = setTimeout(function() {
            applyFilters();
        }, 1000);
    });
    
    // Apply filters button
    $('#apply-filters').on('click', function() {
        applyFilters();
    });
    
    // Clear filters button
    $('#clear-filters, #reset-filters').on('click', function() {
        window.location.href = window.location.pathname;
    });
    
    // Quantity controls
    $('.qty-btn').on('click', function() {
        const itemId = $(this).data('item-id');
        const input = $(`.qty-input[data-item-id="${itemId}"]`);
        const isPlus = $(this).hasClass('plus');
        const currentVal = parseInt(input.val()) || 1;
        const newVal = isPlus ? currentVal + 1 : Math.max(1, currentVal - 1);
        input.val(newVal);
    });
    
    // Quick add buttons
    $('.btn-quick-add').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const itemId = $(this).data('item-id');
        const card = $(this).closest('.product-card');
        const itemName = card.find('.product-name').text();
        const itemPrice = card.find('.product-price .price').text().replace(/[^\d.]/g, '');
        const itemType = card.find('.product-category').text();
        
        addToCart(itemId, itemName, itemPrice, itemType, 1);
    });
    
    // Add to cart buttons
    $('.btn-add-to-cart').on('click', function() {
        const itemId = $(this).data('item-id');
        const itemName = $(this).data('item-name');
        const itemPrice = $(this).data('item-price');
        const itemType = $(this).data('item-type');
        const quantity = parseInt($(`.qty-input[data-item-id="${itemId}"]`).val()) || 1;
        
        addToCart(itemId, itemName, itemPrice, itemType, quantity);
    });
    
    // View toggle
    $('.view-btn').on('click', function() {
        $('.view-btn').removeClass('active');
        $(this).addClass('active');
        
        const view = $(this).data('view');
        if (view === 'list') {
            $('.shopping-grid').addClass('list-view');
        } else {
            $('.shopping-grid').removeClass('list-view');
        }
    });
});

function applyFilters() {
    const params = new URLSearchParams();
    
    // Search
    const search = $('#search-input').val().trim();
    if (search) params.set('search', search);
    
    // Category
    const category = $('input[name="category"]:checked').val();
    if (category && category !== '0') params.set('category', category);
    
    // Dietary
    const dietary = $('input[name="dietary"]:checked').val();
    if (dietary) params.set('dietary', dietary);
    
    // Price range
    const priceMin = $('#price-min').val();
    const priceMax = $('#price-max').val();
    if (priceMin) params.set('price_min', priceMin);
    if (priceMax) params.set('price_max', priceMax);
    
    // Sort
    const sort = $('#sort-select').val();
    if (sort && sort !== 'name') params.set('sort', sort);
    
    // Navigate to filtered results
    const url = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
    window.location.href = url;
}

function addToCart(itemId, itemName, itemPrice, itemType, quantity) {
    // Show loading state
    const button = $(`.btn-add-to-cart[data-item-id="${itemId}"], .btn-quick-add[data-item-id="${itemId}"]`);
    const originalText = button.html();
    button.html('<i class="fa fa-spinner fa-spin"></i> Adding...');
    button.prop('disabled', true);
    
    $.post('../../../api/cart.php', {
        action: 'add_item_to_order_cart',
        item_id: itemId,
        item_name: itemName,
        item_type: itemType,
        unit_price: itemPrice,
        quantity: quantity,
        description: '',
        image: ''
    }, function(response) {
        if (response.success) {
            showNotification('Item added to cart!', 'success');
            updateCartDisplay(response.cart_summary);
            
            // Reset quantity to 1
            $(`.qty-input[data-item-id="${itemId}"]`).val(1);
            
            // Add success animation
            button.html('<i class="fa fa-check"></i> Added!');
            setTimeout(function() {
                button.html(originalText);
                button.prop('disabled', false);
            }, 2000);
        } else {
            showNotification(response.message || 'Error adding item to cart', 'error');
            button.html(originalText);
            button.prop('disabled', false);
        }
    }, 'json').fail(function() {
        showNotification('Error adding item to cart', 'error');
        button.html(originalText);
        button.prop('disabled', false);
    });
}

// Cart functions are now globally available via floating_cart.php

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
            <i class="fa ${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.notification-alert').fadeOut(function() {
            $(this).remove();
        });
    }, 4000);
}

// Update filter labels when options change
$('input[name="category"], input[name="dietary"]').on('change', function() {
    $(this).closest('.filter-option').siblings().removeClass('active');
    $(this).closest('.filter-option').addClass('active');
});
</script>

    <?php include('../../../includes/guest/footer.php');?>
