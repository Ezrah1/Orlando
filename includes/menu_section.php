<?php
// Menu Section Include File
// This file can be included on any page to display the menu section

// Default parameters - can be overridden before including this file
if (!isset($menu_title)) $menu_title = "Our Menu";
if (!isset($menu_subtitle)) $menu_subtitle = "Delicious food and refreshing drinks available 24/7";
if (!isset($show_search)) $show_search = false; // Simple menu by default
if (!isset($items_limit)) $items_limit = 12; // Default items to show
if (!isset($show_all_link)) $show_all_link = true; // Show "View All" link
if (!isset($menu_style)) $menu_style = "simple"; // simple or advanced

// Get menu items with categories
$menu_categories_query = "SELECT * FROM menu_categories ORDER BY name";
$menu_categories_result = mysqli_query($con, $menu_categories_query);
$categories = [];
while($cat = mysqli_fetch_assoc($menu_categories_result)) {
    $categories[] = $cat;
}

// Get featured menu items (ordered by display_order, then by name)
$featured_query = "SELECT mi.*, mc.name as category_name 
                   FROM menu_items mi 
                   LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
                   WHERE mi.is_available = 1 
                   ORDER BY mi.display_order DESC, mi.name ASC 
                   LIMIT $items_limit";
$featured_result = mysqli_query($con, $featured_query);
$menu_items = [];
while($item = mysqli_fetch_assoc($featured_result)) {
    $menu_items[] = $item;
}
?>

<style>
/* Menu Section Styles */
.menu-section {
    padding: 80px 0;
    background: #f8f9fa;
}

.menu-section-header {
    text-align: center;
    margin-bottom: 50px;
}

.menu-section-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 15px;
}

.menu-section-title i {
    color: #667eea;
    margin-right: 15px;
}

.menu-section-subtitle {
    font-size: 1.1rem;
    color: #666;
    margin-bottom: 0;
}

.menu-categories-tabs {
    display: flex;
    justify-content: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 40px;
}

.menu-category-tab {
    background: white;
    border: 2px solid #667eea;
    color: #667eea;
    padding: 10px 20px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
    cursor: pointer;
}

.menu-category-tab:hover,
.menu-category-tab.active {
    background: #667eea;
    color: white;
    text-decoration: none;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
}

.menu-items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 25px;
    margin-bottom: 40px;
}

.menu-item-card {
    background: white;
    border-radius: 15px;
    overflow: hidden;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border: 2px solid transparent;
}

.menu-item-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    border-color: #667eea;
}

.menu-item-image {
    height: 200px;
    background: linear-gradient(45deg, #667eea, #764ba2);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 3rem;
    position: relative;
    overflow: hidden;
}

.menu-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    position: absolute;
    top: 0;
    left: 0;
}

.menu-item-content {
    padding: 20px;
}

.menu-item-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 10px;
}

.menu-item-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 0;
    flex: 1;
    margin-right: 10px;
}

.menu-item-price {
    font-size: 1.4rem;
    font-weight: 700;
    color: #e74c3c;
    white-space: nowrap;
}

.menu-item-category {
    font-size: 0.8rem;
    color: #667eea;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 8px;
}

.menu-item-description {
    color: #666;
    font-size: 0.95rem;
    line-height: 1.5;
    margin-bottom: 15px;
}

.menu-item-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 5px;
    margin-bottom: 15px;
}

.menu-tag {
    background: #f1f3f4;
    color: #5f6368;
    padding: 3px 8px;
    border-radius: 12px;
    font-size: 0.7rem;
    font-weight: 600;
}

.menu-tag.vegetarian {
    background: #d4edda;
    color: #155724;
}

.menu-tag.spicy {
    background: #f8d7da;
    color: #721c24;
}

.menu-tag.gluten-free {
    background: #d1ecf1;
    color: #0c5460;
}

.menu-item-actions {
    display: flex;
    gap: 10px;
}

.btn-order {
    flex: 1;
    background: #28a745;
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
    transition: all 0.3s ease;
    text-decoration: none;
    text-align: center;
    display: block;
}

.btn-order:hover {
    background: #218838;
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

.menu-view-all {
    text-align: center;
    margin-top: 40px;
}

.btn-view-all {
    background: #667eea;
    color: white;
    border: none;
    border-radius: 25px;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
    transition: all 0.3s ease;
}

.btn-view-all:hover {
    background: #5a67d8;
    color: white;
    text-decoration: none;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

/* Simple Search Bar */
.simple-search {
    max-width: 500px;
    margin: 0 auto 40px;
    position: relative;
}

.simple-search input {
    width: 100%;
    padding: 15px 50px 15px 20px;
    border: 2px solid #e9ecef;
    border-radius: 25px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
}

.simple-search input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

.simple-search button {
    position: absolute;
    right: 5px;
    top: 5px;
    background: #667eea;
    border: none;
    border-radius: 20px;
    width: 40px;
    height: 40px;
    color: white;
    cursor: pointer;
    transition: background 0.3s ease;
}

.simple-search button:hover {
    background: #5a67d8;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .menu-section-title {
        font-size: 2rem;
    }
    
    .menu-items-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }
    
    .menu-categories-tabs {
        flex-direction: column;
        align-items: center;
    }
}
</style>

<!-- Menu Section -->
<div class="menu-section" id="menu">
    <div class="container">
        <!-- Header -->
        <div class="menu-section-header">
            <h2 class="menu-section-title">
                <i class="fa fa-cutlery"></i><?php echo $menu_title; ?>
            </h2>
            <p class="menu-section-subtitle"><?php echo $menu_subtitle; ?></p>
        </div>

        <?php if ($show_search): ?>
        <!-- Simple Search -->
        <div class="simple-search">
            <form method="GET" action="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php">
                <input type="text" name="search" placeholder="Search dishes, ingredients, or categories...">
                <button type="submit"><i class="fa fa-search"></i></button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Category Tabs -->
        <?php if (count($categories) > 0): ?>
        <div class="menu-categories-tabs">
            <a href="#" class="menu-category-tab active" data-category="all">All Items</a>
            <?php foreach ($categories as $category): ?>
            <a href="#" class="menu-category-tab" data-category="<?php echo $category['id']; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Menu Items Grid -->
        <div class="menu-items-grid" id="menu-items-container">
            <?php foreach ($menu_items as $item): ?>
            <div class="menu-item-card" data-category="<?php echo $item['category_id']; ?>">
                <div class="menu-item-image">
                    <i class="fa fa-cutlery"></i>
                    <!-- You can add actual images here -->
                </div>
                <div class="menu-item-content">
                    <?php if ($item['category_name']): ?>
                    <div class="menu-item-category"><?php echo htmlspecialchars($item['category_name']); ?></div>
                    <?php endif; ?>
                    
                    <div class="menu-item-header">
                        <h4 class="menu-item-name"><?php echo htmlspecialchars($item['name']); ?></h4>
                        <div class="menu-item-price">KES <?php echo number_format($item['price'], 0); ?></div>
                    </div>
                    
                    <p class="menu-item-description"><?php echo htmlspecialchars($item['description']); ?></p>
                    
                    <div class="menu-item-tags">
                        <?php if ($item['is_vegetarian']): ?>
                        <span class="menu-tag vegetarian">Vegetarian</span>
                        <?php endif; ?>
                        <?php if ($item['is_spicy']): ?>
                        <span class="menu-tag spicy">Spicy</span>
                        <?php endif; ?>
                        <?php if ($item['is_gluten_free']): ?>
                        <span class="menu-tag gluten-free">Gluten Free</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="menu-item-actions">
                        <a href="add_to_cart.php?action=add&item_id=<?php echo $item['id']; ?>&quantity=1" class="btn-order">
                            <i class="fa fa-shopping-cart"></i> Order Now
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($show_all_link): ?>
        <!-- View All Menu Link -->
        <div class="menu-view-all">
            <a href="http://localhost/Hotel/modules/guest/menu/menu_enhanced.php" class="btn-view-all">
                <i class="fa fa-eye"></i> View Full Menu
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Category filtering for menu section
document.addEventListener('DOMContentLoaded', function() {
    const categoryTabs = document.querySelectorAll('.menu-category-tab');
    const menuItems = document.querySelectorAll('.menu-item-card');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function(e) {
            e.preventDefault();
            const category = this.getAttribute('data-category');
            
            // Update active tab
            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            // Filter menu items
            menuItems.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'block';
                    item.style.animation = 'fadeInUp 0.6s ease';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });
});
</script>
