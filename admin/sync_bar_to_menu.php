<?php
// Sync Bar Inventory to Menu System
require_once '../db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Sync Bar Inventory to Menu';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle sync request
if (isset($_POST['sync_bar_items'])) {
    try {
        // First, remove existing bar items from menu that are no longer in inventory
        $cleanup_sql = "DELETE FROM menu_items WHERE category_id = 6 AND name NOT IN (
            SELECT DISTINCT bi.name FROM bar_inventory bi WHERE bi.is_active = 1
        )";
        mysqli_query($con, $cleanup_sql);
        
        // Get all active bar inventory items
        $bar_items_query = "SELECT bi.*, bc.name as category_name 
                           FROM bar_inventory bi 
                           LEFT JOIN bar_categories bc ON bi.category_id = bc.id 
                           WHERE bi.is_active = 1 
                           ORDER BY bc.display_order, bi.name";
        $bar_items_result = mysqli_query($con, $bar_items_query);
        
        $synced_count = 0;
        $updated_count = 0;
        
        while ($bar_item = mysqli_fetch_assoc($bar_items_result)) {
            // Check if item already exists in menu
            $check_query = "SELECT id FROM menu_items WHERE category_id = 6 AND name = '" . mysqli_real_escape_string($con, $bar_item['name']) . "'";
            $check_result = mysqli_query($con, $check_query);
            
            // Prepare menu item data
            $name = mysqli_real_escape_string($con, $bar_item['name']);
            $description = mysqli_real_escape_string($con, $bar_item['description'] . ' (' . $bar_item['category_name'] . ' - ' . $bar_item['unit'] . ')');
            $price = $bar_item['selling_price'];
            $cost_price = $bar_item['unit_cost'];
            $is_available = $bar_item['current_stock'] > 0 ? 1 : 0;
            $is_alcoholic = $bar_item['is_alcoholic'];
            
            if (mysqli_num_rows($check_result) > 0) {
                // Update existing item
                $menu_item = mysqli_fetch_assoc($check_result);
                $update_sql = "UPDATE menu_items SET 
                              description = '$description',
                              price = $price,
                              cost_price = $cost_price,
                              is_available = $is_available,
                              updated_at = NOW()
                              WHERE id = " . $menu_item['id'];
                
                if (mysqli_query($con, $update_sql)) {
                    $updated_count++;
                }
            } else {
                // Insert new item
                $insert_sql = "INSERT INTO menu_items 
                              (category_id, name, description, price, cost_price, preparation_time, is_available, is_vegetarian, is_gluten_free, is_spicy, display_order) 
                              VALUES 
                              (6, '$name', '$description', $price, $cost_price, 5, $is_available, 0, 0, 0, 0)";
                
                if (mysqli_query($con, $insert_sql)) {
                    $synced_count++;
                }
            }
        }
        
        $success_message = "Successfully synced bar inventory to menu! Added: $synced_count new items, Updated: $updated_count existing items.";
        
    } catch (Exception $e) {
        $error_message = "Error syncing bar inventory: " . $e->getMessage();
    }
}

// Get current bar inventory count
$bar_count_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE is_active = 1";
$bar_count_result = mysqli_query($con, $bar_count_query);
$bar_count = mysqli_fetch_assoc($bar_count_result)['count'];

// Get current menu bar items count
$menu_bar_count_query = "SELECT COUNT(*) as count FROM menu_items WHERE category_id = 6";
$menu_bar_count_result = mysqli_query($con, $menu_bar_count_query);
$menu_bar_count = mysqli_fetch_assoc($menu_bar_count_result)['count'];

// Get bar categories for mapping
$bar_categories_query = "SELECT * FROM bar_categories WHERE is_active = 1 ORDER BY display_order";
$bar_categories_result = mysqli_query($con, $bar_categories_query);
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-sync-alt"></i> Sync Bar Inventory to Menu
        </h1>
        <p class="page-subtitle">Link your bar inventory items to the guest menu system</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Sync Overview -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="sync-card">
                <div class="sync-icon">
                    <i class="fa fa-warehouse"></i>
                </div>
                <div class="sync-info">
                    <h3><?php echo $bar_count; ?></h3>
                    <p>Bar Inventory Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sync-card">
                <div class="sync-icon">
                    <i class="fa fa-arrow-right text-primary"></i>
                </div>
                <div class="sync-info">
                    <h3>SYNC</h3>
                    <p>Link Systems</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="sync-card">
                <div class="sync-icon">
                    <i class="fa fa-utensils"></i>
                </div>
                <div class="sync-info">
                    <h3><?php echo $menu_bar_count; ?></h3>
                    <p>Menu Bar Items</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Sync Instructions -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-info-circle"></i> How Synchronization Works</h4>
                </div>
                <div class="card-body">
                    <div class="sync-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h5>Extract Bar Inventory</h5>
                                <p>Reads all active items from your bar inventory system with current pricing and stock levels.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h5>Map to Menu Items</h5>
                                <p>Creates or updates menu items in the "Bar" category with proper descriptions and pricing.</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h5>Update Availability</h5>
                                <p>Sets menu availability based on current stock levels (items with 0 stock are marked unavailable).</p>
                            </div>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h5>Guest Menu Updated</h5>
                                <p>Bar items now appear in the guest menu system for ordering with real-time pricing.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-lightbulb"></i> Pro Tip:</strong> 
                        Run this sync regularly (daily/weekly) to keep menu prices and availability current with your bar inventory.
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-cogs"></i> Sync Actions</h4>
                </div>
                <div class="card-body text-center">
                    <form method="POST">
                        <button type="submit" name="sync_bar_items" class="btn btn-primary btn-lg" 
                                onclick="return confirm('This will sync all bar inventory items to the menu. Continue?')">
                            <i class="fa fa-sync-alt"></i> Sync Bar Inventory to Menu
                        </button>
                    </form>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="bar_inventory.php" class="btn btn-outline-secondary">
                            <i class="fa fa-warehouse"></i> Manage Bar Inventory
                        </a>
                        <a href="restaurant_menu.php" class="btn btn-outline-secondary mt-2">
                            <i class="fa fa-utensils"></i> Manage Menu
                        </a>
                        <a href="../modules/guest/menu/menu_enhanced.php?category=6" class="btn btn-outline-success mt-2" target="_blank">
                            <i class="fa fa-eye"></i> View Bar Menu
                        </a>
                    </div>
                </div>
            </div>

            <!-- Bar Categories Preview -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fa fa-tags"></i> Bar Categories</h4>
                </div>
                <div class="card-body">
                    <?php while ($cat = mysqli_fetch_assoc($bar_categories_result)): ?>
                        <div class="category-item">
                            <span class="category-name"><?php echo $cat['name']; ?></span>
                            <?php
                            $cat_count_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE category_id = " . $cat['id'] . " AND is_active = 1";
                            $cat_count_result = mysqli_query($con, $cat_count_query);
                            $cat_count = mysqli_fetch_assoc($cat_count_result)['count'];
                            ?>
                            <span class="category-count"><?php echo $cat_count; ?> items</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.sync-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.sync-card:hover {
    transform: translateY(-3px);
}

.sync-icon {
    font-size: 2.5rem;
    color: #667eea;
    width: 60px;
    text-align: center;
}

.sync-info h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.sync-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.sync-steps {
    margin: 20px 0;
}

.step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.step:last-child {
    border-bottom: none;
}

.step-number {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h5 {
    color: #2c3e50;
    margin-bottom: 8px;
    font-weight: 600;
}

.step-content p {
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.category-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.category-item:last-child {
    border-bottom: none;
}

.category-name {
    font-weight: 500;
    color: #2c3e50;
}

.category-count {
    background: #e9ecef;
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 0.85rem;
    color: #6c757d;
}

.page-subtitle {
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
