<?php
$page_title = 'Bar Menu - Orlando International Resorts';
$page_description = 'Orlando International Resorts - Bar & Beverages Menu';

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
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 10000;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'category';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

// Build WHERE clause for bar inventory
$where_conditions = ["bi.is_active = 1"];

if (!empty($search)) {
    $where_conditions[] = "(bi.name LIKE '%$search%' OR bi.description LIKE '%$search%' OR bi.brand LIKE '%$search%')";
}

if ($category_filter > 0) {
    $where_conditions[] = "bi.category_id = $category_filter";
}

if ($price_min > 0) {
    $where_conditions[] = "bi.selling_price >= $price_min";
}

if ($price_max < 10000) {
    $where_conditions[] = "bi.selling_price <= $price_max";
}

$where_clause = implode(' AND ', $where_conditions);

// Sorting options
$order_clause = "ORDER BY ";
switch ($sort_by) {
    case 'price_low':
        $order_clause .= "bi.selling_price ASC";
        break;
    case 'price_high':
        $order_clause .= "bi.selling_price DESC";
        break;
    case 'name':
        $order_clause .= "bi.name ASC";
        break;
    case 'category':
        $order_clause .= "bc.display_order ASC, bi.name ASC";
        break;
    default:
        $order_clause .= "bc.display_order ASC, bi.name ASC";
}

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total 
                FROM bar_inventory bi 
                JOIN bar_categories bc ON bi.category_id = bc.id 
                WHERE $where_clause";
$count_result = mysqli_query($con, $count_query);
$total_items = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_items / $items_per_page);

// Get bar inventory items with pagination (directly from bar_inventory)
$bar_query = "SELECT bi.*, bc.name as category_name, bc.id as category_id
              FROM bar_inventory bi 
              JOIN bar_categories bc ON bi.category_id = bc.id 
              WHERE $where_clause 
              $order_clause 
              LIMIT $items_per_page OFFSET $offset";
$bar_result = mysqli_query($con, $bar_query);

// Get all bar categories for filter dropdown
$categories_query = "SELECT * FROM bar_categories WHERE is_active = 1 ORDER BY display_order";
$categories_result = mysqli_query($con, $categories_query);

// Get price range for slider
$price_range_query = "SELECT MIN(selling_price) as min_price, MAX(selling_price) as max_price FROM bar_inventory WHERE is_active = 1";
$price_range_result = mysqli_query($con, $price_range_query);
$price_range = mysqli_fetch_assoc($price_range_result);

// Get cart summary
$cart_summary = CartManager::getOrderCartSummary();
$cart_count = $cart_summary['items_count'];
?>

<!-- Modern Bar Menu Section -->
<div class="bar-menu-section">
    <!-- Hero Banner -->
    <div class="bar-hero">
        <div class="container">
            <div class="hero-content text-center">
                <h1 class="hero-title">
                    <i class="fa fa-glass-cheers"></i>
                    Premium Bar & Beverages
                </h1>
                <p class="hero-subtitle">Finest spirits, wines, and cocktails delivered to your room</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $total_items; ?></span>
                        <span class="stat-label">Premium Beverages</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Room Service</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">⭐5.0</span>
                        <span class="stat-label">Premium Quality</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Enhanced Sidebar Filters -->
            <div class="col-lg-3 col-md-4">
                <div class="bar-sidebar">
                    <div class="sidebar-header">
                        <h3><i class="fa fa-filter"></i> Filter Beverages</h3>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="filter-section">
                        <div class="search-box">
                            <input type="text" id="search-input" placeholder="Search beverages..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="button" id="search-btn"><i class="fa fa-search"></i></button>
                        </div>
                    </div>

                    <!-- Categories Filter -->
                    <div class="filter-section">
                        <h4><i class="fa fa-tags"></i> Categories</h4>
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

            <!-- Main Bar Content -->
            <div class="col-lg-9 col-md-8">
                <!-- Bar Toolbar -->
                <div class="bar-toolbar">
                    <div class="toolbar-left">
                        <span class="results-count">
                            Showing <?php echo min($items_per_page, $total_items - $offset); ?> of <?php echo $total_items; ?> beverages
                        </span>
                    </div>
                    <div class="toolbar-right">
                        <div class="sort-dropdown">
                            <select id="sort-select" class="form-control custom-select enhanced">
                                <option value="category" <?php echo $sort_by == 'category' ? 'selected' : ''; ?>>Sort by Category</option>
                                <option value="name" <?php echo $sort_by == 'name' ? 'selected' : ''; ?>>Sort by Name</option>
                                <option value="price_low" <?php echo $sort_by == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sort_by == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Bar Items Grid -->
                <div class="bar-grid" id="bar-items-grid">
                    <?php if (mysqli_num_rows($bar_result) > 0): ?>
                        <?php while($item = mysqli_fetch_assoc($bar_result)): 
                            $is_available = $item['current_stock'] > 0;
                            $profit = $item['selling_price'] - $item['unit_cost'];
                            $stock_level = $item['current_stock'] <= $item['minimum_stock'] ? 'low' : 'good';
                        ?>
                        <div class="bar-card <?php echo !$is_available ? 'out-of-stock' : ''; ?>" data-category="<?php echo $item['category_id']; ?>">
                            <div class="bar-image">
                                <div class="default-image">
                                    <i class="fa fa-glass-martini-alt"></i>
                                </div>
                                
                                <!-- Bar Badges -->
                                <div class="bar-badges">
                                    <?php if ($item['is_alcoholic']): ?>
                                        <span class="badge badge-alcoholic">
                                            <i class="fa fa-wine-glass"></i> <?php echo $item['alcohol_percentage']; ?>% ABV
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-non-alcoholic">Non-Alcoholic</span>
                                    <?php endif; ?>
                                    
                                    <?php if ($stock_level == 'low'): ?>
                                        <span class="badge badge-low-stock">Low Stock</span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Quick Add Button -->
                                <?php if ($is_available): ?>
                                <div class="quick-add">
                                    <button class="btn-quick-add" data-item-id="bar_<?php echo $item['id']; ?>">
                                        <i class="fa fa-plus"></i> Quick Add
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="bar-info">
                                <div class="bar-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                                <h3 class="bar-name"><?php echo htmlspecialchars($item['name']); ?></h3>
                                <?php if ($item['brand']): ?>
                                    <p class="bar-brand"><strong><?php echo htmlspecialchars($item['brand']); ?></strong></p>
                                <?php endif; ?>
                                <p class="bar-description"><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                                
                                <div class="bar-meta">
                                    <span class="bar-unit">
                                        <i class="fa fa-info-circle"></i> <?php echo $item['unit']; ?>
                                    </span>
                                    <span class="bar-stock stock-<?php echo $stock_level; ?>">
                                        <i class="fa fa-warehouse"></i> <?php echo number_format($item['current_stock'], 0); ?> in stock
                                    </span>
                                </div>
                                
                                <div class="bar-footer">
                                    <div class="bar-price">
                                        <span class="price">KES <?php echo number_format($item['selling_price'], 0); ?></span>
                                        <span class="profit">+KES <?php echo number_format($profit, 0); ?> profit</span>
                                    </div>
                                    <div class="bar-actions">
                                        <?php if ($is_available): ?>
                                            <div class="quantity-selector">
                                                <button class="qty-btn minus" data-item-id="bar_<?php echo $item['id']; ?>">-</button>
                                                <input type="number" class="qty-input" value="1" min="1" max="<?php echo min(10, $item['current_stock']); ?>" data-item-id="bar_<?php echo $item['id']; ?>">
                                                <button class="qty-btn plus" data-item-id="bar_<?php echo $item['id']; ?>">+</button>
                                            </div>
                                            <button class="btn-add-to-cart" 
                                                    data-item-id="bar_<?php echo $item['id']; ?>" 
                                                    data-item-name="<?php echo htmlspecialchars($item['name']); ?>" 
                                                    data-item-price="<?php echo $item['selling_price']; ?>" 
                                                    data-item-type="Bar - <?php echo htmlspecialchars($item['category_name']); ?>">
                                                <i class="fa fa-shopping-cart"></i> Add to Cart
                                            </button>
                                        <?php else: ?>
                                            <button class="btn-out-of-stock" disabled>
                                                <i class="fa fa-times-circle"></i> Out of Stock
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-results">
                            <div class="no-results-icon">
                                <i class="fa fa-glass-martini"></i>
                            </div>
                            <h3>No beverages found</h3>
                            <p>Try adjusting your filters or search terms</p>
                            <button class="btn btn-primary" id="reset-filters">Reset Filters</button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bar-pagination">
                    <nav aria-label="Bar menu pagination">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page-1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
                                        <i class="fa fa-chevron-left"></i> Previous
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo $page+1; ?>&<?php echo http_build_query(array_filter($_GET, function($k) { return $k !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>">
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

<!-- Enhanced Bar Menu Styles -->
<style>
/* ===== ENHANCED BAR MENU STYLES ===== */

.bar-menu-section {
    background: #f8f9fa;
    min-height: 100vh;
    padding-top: 80px;
}

/* Bar Hero Section */
.bar-hero {
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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

/* Enhanced Sidebar */
.bar-sidebar {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    position: sticky;
    top: 100px;
    margin-bottom: 30px;
}

.sidebar-header h3 {
    color: #6f42c1;
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
    border-color: #6f42c1;
    box-shadow: 0 0 0 3px rgba(111, 66, 193, 0.1);
    outline: none;
}

.search-box button {
    position: absolute;
    right: 5px;
    top: 50%;
    transform: translateY(-50%);
    background: #6f42c1;
    border: none;
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s ease;
}

.search-box button:hover {
    background: #5a379c;
    transform: translateY(-50%) scale(1.05);
}

/* Filter Options */
.filter-option {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    cursor: pointer;
    transition: all 0.3s ease;
    border-radius: 8px;
    margin-bottom: 5px;
}

.filter-option:hover {
    background: #f8f9fa;
}

.filter-option.active {
    background: rgba(111, 66, 193, 0.1);
    color: #6f42c1;
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
    border-color: #6f42c1;
    background: #6f42c1;
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
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
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

/* Bar Toolbar */
.bar-toolbar {
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

/* Bar Grid */
.bar-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

/* Bar Cards */
.bar-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
    position: relative;
}

.bar-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
}

.bar-card.out-of-stock {
    opacity: 0.7;
    background: #f8f9fa;
}

.bar-image {
    position: relative;
    height: 180px;
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
    overflow: hidden;
}

.default-image {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    font-size: 3rem;
    color: rgba(255,255,255,0.8);
}

.bar-badges {
    position: absolute;
    top: 10px;
    left: 10px;
    display: flex;
    flex-direction: column;
    gap: 5px;
}

.badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 500;
    backdrop-filter: blur(10px);
}

.badge-alcoholic {
    background: rgba(220, 53, 69, 0.9);
    color: white;
}

.badge-non-alcoholic {
    background: rgba(40, 167, 69, 0.9);
    color: white;
}

.badge-low-stock {
    background: rgba(255, 193, 7, 0.9);
    color: #212529;
}

.quick-add {
    position: absolute;
    top: 10px;
    right: 10px;
    opacity: 0;
    transition: all 0.3s ease;
}

.bar-card:hover .quick-add {
    opacity: 1;
}

.btn-quick-add {
    background: rgba(255, 255, 255, 0.9);
    border: none;
    color: #6f42c1;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.85rem;
    cursor: pointer;
    backdrop-filter: blur(10px);
    transition: all 0.3s ease;
    font-weight: 600;
}

.btn-quick-add:hover {
    background: white;
    transform: scale(1.05);
}

/* Bar Info */
.bar-info {
    padding: 20px;
}

.bar-category {
    color: #6f42c1;
    font-size: 0.85rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.bar-name {
    font-size: 1.2rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 5px;
    line-height: 1.3;
}

.bar-brand {
    color: #e83e8c;
    font-size: 0.9rem;
    margin-bottom: 8px;
}

.bar-description {
    color: #6c757d;
    font-size: 0.9rem;
    line-height: 1.4;
    margin-bottom: 12px;
    height: 38px;
    overflow: hidden;
}

.bar-meta {
    display: flex;
    justify-content: space-between;
    margin-bottom: 15px;
    font-size: 0.85rem;
}

.bar-unit {
    color: #6c757d;
    display: flex;
    align-items: center;
    gap: 5px;
}

.bar-stock {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: 500;
}

.stock-good {
    color: #28a745;
}

.stock-low {
    color: #ffc107;
}

/* Bar Footer */
.bar-footer {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    margin-top: 20px;
}

.bar-price .price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #28a745;
    display: block;
}

.bar-price .profit {
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 500;
}

.bar-actions {
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
    background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);
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
    box-shadow: 0 5px 15px rgba(111, 66, 193, 0.3);
}

.btn-out-of-stock {
    background: #6c757d;
    border: none;
    color: white;
    padding: 10px 15px;
    border-radius: 8px;
    font-weight: 600;
    cursor: not-allowed;
    font-size: 0.9rem;
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
    color: #6f42c1;
}

.no-results h3 {
    margin-bottom: 10px;
    color: #495057;
}

/* Pagination */
.bar-pagination {
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
    background: #6f42c1;
    border-color: #6f42c1;
    color: white;
}

.page-item .page-link:hover {
    background: #f8f9fa;
    text-decoration: none;
    color: #495057;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .bar-grid {
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    }
}

@media (max-width: 768px) {
    .bar-menu-section {
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
    
    .bar-sidebar {
        margin-bottom: 20px;
        position: static;
    }
    
    .bar-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .toolbar-right {
        justify-content: space-between;
    }
    
    .bar-grid {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 15px;
    }
    
    .bar-footer {
        flex-direction: column;
        align-items: stretch;
        gap: 15px;
    }
    
    .bar-actions {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
    }
}

@media (max-width: 480px) {
    .bar-grid {
        grid-template-columns: 1fr;
    }
    
    .hero-stats {
        flex-direction: column;
        gap: 15px;
    }
}
</style>

<!-- Enhanced Bar JavaScript -->
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
    $('input[name="category"], #sort-select').on('change', function() {
        if ($(this).is('#sort-select')) {
            applyFilters();
        }
    });
    
    // Price range inputs
    $('#price-min, #price-max').on('change', function() {
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
        const maxVal = parseInt(input.attr('max')) || 10;
        const newVal = isPlus ? Math.min(maxVal, currentVal + 1) : Math.max(1, currentVal - 1);
        input.val(newVal);
    });
    
    // Quick add buttons
    $('.btn-quick-add').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const itemId = $(this).data('item-id');
        const card = $(this).closest('.bar-card');
        const itemName = card.find('.bar-name').text();
        const itemPrice = card.find('.bar-price .price').text().replace(/[^\d.]/g, '');
        const itemType = card.find('.bar-category').text();
        
        addToCart(itemId, itemName, itemPrice, 'Bar - ' + itemType, 1);
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
});

function applyFilters() {
    const params = new URLSearchParams();
    
    // Search
    const search = $('#search-input').val().trim();
    if (search) params.set('search', search);
    
    // Category
    const category = $('input[name="category"]:checked').val();
    if (category && category !== '0') params.set('category', category);
    
    // Price range
    const priceMin = $('#price-min').val();
    const priceMax = $('#price-max').val();
    if (priceMin) params.set('price_min', priceMin);
    if (priceMax) params.set('price_max', priceMax);
    
    // Sort
    const sort = $('#sort-select').val();
    if (sort && sort !== 'category') params.set('sort', sort);
    
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
            showNotification('Bar item added to cart!', 'success');
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
$('input[name="category"]').on('change', function() {
    $(this).closest('.filter-option').siblings().removeClass('active');
    $(this).closest('.filter-option').addClass('active');
});
</script>

<?php include('../../../includes/guest/footer.php'); ?>
