<?php
// Import Complete Bar Inventory with Size Variants
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

$page_title = 'Import Complete Bar Inventory (43 Items)';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle import request
if (isset($_POST['import_complete_inventory'])) {
    try {
        // First ensure we have all categories
        $categories_to_add = [
            [8, 'Beer/Cider', 'Beers and ciders by bottle/can', 1, 8],
            [9, 'RTD/Soft', 'Ready-to-drink and soft beverages', 1, 9],
            [10, 'Water', 'Bottled water in various sizes', 1, 10]
        ];
        
        $categories_added = 0;
        foreach ($categories_to_add as $cat) {
            $check_cat = "SELECT id FROM bar_categories WHERE id = " . $cat[0];
            if (mysqli_num_rows(mysqli_query($con, $check_cat)) == 0) {
                $name = mysqli_real_escape_string($con, $cat[1]);
                $description = mysqli_real_escape_string($con, $cat[2]);
                $insert_cat = "INSERT INTO bar_categories (id, name, description, is_active, display_order, created_at, updated_at) 
                               VALUES ({$cat[0]}, '$name', '$description', {$cat[3]}, {$cat[4]}, '2025-01-20 00:00:00', '2025-01-20 00:00:00')";
                if (mysqli_query($con, $insert_cat)) {
                    $categories_added++;
                }
            }
        }
        
        // Complete inventory list with exact name+size combinations (43 unique items)
        $complete_inventory = [
            // Wine Category (7 items)
            ['4th Street Red 750ml', 2, 'Red wine 750ml bottle', '4th Street', '750ml', 1350.00, 1900.00, 'Wine Suppliers', 1, 13.00],
            ['4th Street White 750ml', 2, 'White wine 750ml bottle', '4th Street', '750ml', 1350.00, 1900.00, 'Wine Suppliers', 1, 12.50],
            ['Caprice Sweet Red 1L', 2, 'Sweet red wine 1L bottle', 'Caprice', '1L', 800.00, 1100.00, 'Wine Suppliers', 1, 12.00],
            ['Casa Buena Red 1L', 2, 'Red wine 1L bottle', 'Casa Buena', '1L', 950.00, 1300.00, 'Wine Suppliers', 1, 13.50],
            ['Casa Buena White 750ml', 2, 'White wine 750ml bottle', 'Casa Buena', '750ml', 950.00, 1300.00, 'Wine Suppliers', 1, 12.80],
            ['Robertson Rosé 750ml', 2, 'Rosé wine 750ml bottle', 'Robertson', '750ml', 1300.00, 1800.00, 'Wine Suppliers', 1, 12.00],
            ['Robertson Dry 750ml', 2, 'Dry wine 750ml bottle', 'Robertson', '750ml', 1450.00, 2000.00, 'Wine Suppliers', 1, 13.50],
            
            // Spirits Category (25 items)
            ['Captain Morgan 350ml', 3, 'Spiced rum 350ml bottle', 'Captain Morgan', '350ml', 430.00, 600.00, 'Spirits Distributors', 1, 35.00],
            ['Captain Morgan 750ml', 3, 'Spiced rum 750ml bottle', 'Captain Morgan', '750ml', 1100.00, 1500.00, 'Spirits Distributors', 1, 35.00],
            ['Gilbey\'s Gin 250ml', 3, 'London dry gin 250ml bottle', 'Gilbey\'s', '250ml', 540.00, 750.00, 'Spirits Distributors', 1, 37.50],
            ['Gilbey\'s Gin 350ml', 3, 'London dry gin 350ml bottle', 'Gilbey\'s', '350ml', 720.00, 1000.00, 'Spirits Distributors', 1, 37.50],
            ['Gilbey\'s Gin 750ml', 3, 'London dry gin 750ml bottle', 'Gilbey\'s', '750ml', 1450.00, 2000.00, 'Spirits Distributors', 1, 37.50],
            ['Gordon\'s Gin 750ml', 3, 'Premium gin 750ml bottle', 'Gordon\'s', '750ml', 2150.00, 3000.00, 'Spirits Distributors', 1, 37.50],
            ['Grant\'s 750ml', 3, 'Scotch whisky 750ml bottle', 'Grant\'s', '750ml', 1800.00, 2500.00, 'Spirits Distributors', 1, 40.00],
            ['Grant\'s 1L', 3, 'Scotch whisky 1L bottle', 'Grant\'s', '1L', 1950.00, 2700.00, 'Spirits Distributors', 1, 40.00],
            ['Hennessy VS 750ml', 3, 'Cognac VS 750ml bottle', 'Hennessy', '750ml', 6500.00, 9050.00, 'Premium Spirits', 1, 40.00],
            ['Hennessy VSOP 750ml', 3, 'Cognac VSOP 750ml bottle', 'Hennessy', '750ml', 9300.00, 13000.00, 'Premium Spirits', 1, 40.00],
            ['Jack Daniel\'s 750ml', 3, 'Tennessee whiskey 750ml bottle', 'Jack Daniel\'s', '750ml', 3200.00, 4500.00, 'Spirits Distributors', 1, 40.00],
            ['Jameson 350ml', 3, 'Irish whiskey 350ml bottle', 'Jameson', '350ml', 1300.00, 1800.00, 'Spirits Distributors', 1, 40.00],
            ['Jameson 750ml', 3, 'Irish whiskey 750ml bottle', 'Jameson', '750ml', 2150.00, 3000.00, 'Spirits Distributors', 1, 40.00],
            ['Jameson 1L', 3, 'Irish whiskey 1L bottle', 'Jameson', '1L', 2700.00, 3800.00, 'Spirits Distributors', 1, 40.00],
            ['Jägermeister 750ml', 3, 'Herbal liqueur 750ml bottle', 'Jägermeister', '750ml', 2600.00, 3600.00, 'Spirits Distributors', 1, 35.00],
            ['J&B Rare 375ml', 3, 'Scotch whisky 375ml bottle', 'J&B', '375ml', 1100.00, 1500.00, 'Spirits Distributors', 1, 40.00],
            ['Johnnie Walker Black 750ml', 3, 'Premium Scotch whiskey 750ml', 'Johnnie Walker', '750ml', 4300.00, 6000.00, 'Premium Spirits', 1, 40.00],
            ['Singleton 750ml', 3, 'Single malt whisky 750ml bottle', 'Singleton', '750ml', 4700.00, 6500.00, 'Premium Spirits', 1, 40.00],
            ['Smirnoff Vodka 250ml', 3, 'Premium vodka 250ml bottle', 'Smirnoff', '250ml', 500.00, 700.00, 'Spirits Distributors', 1, 37.50],
            ['Smirnoff Vodka 350ml', 3, 'Premium vodka 350ml bottle', 'Smirnoff', '350ml', 720.00, 1000.00, 'Spirits Distributors', 1, 37.50],
            ['Smirnoff Vodka 750ml', 3, 'Premium vodka 750ml bottle', 'Smirnoff', '750ml', 1450.00, 2000.00, 'Spirits Distributors', 1, 37.50],
            ['Vat 69 250ml', 3, 'Blended whisky 250ml bottle', 'Vat 69', '250ml', 650.00, 900.00, 'Spirits Distributors', 1, 40.00],
            ['Vat 69 375ml', 3, 'Blended whisky 375ml bottle', 'Vat 69', '375ml', 950.00, 1300.00, 'Spirits Distributors', 1, 40.00],
            ['Vat 69 750ml', 3, 'Blended whisky 750ml bottle', 'Vat 69', '750ml', 1600.00, 2200.00, 'Spirits Distributors', 1, 40.00],
            ['Viceroy Brandy 250ml', 3, 'Brandy 250ml bottle', 'Viceroy', '250ml', 500.00, 700.00, 'Spirits Distributors', 1, 36.00],
            ['Viceroy Brandy 350ml', 3, 'Brandy 350ml bottle', 'Viceroy', '350ml', 720.00, 1000.00, 'Spirits Distributors', 1, 36.00],
            ['Viceroy Brandy 750ml', 3, 'Brandy 750ml bottle', 'Viceroy', '750ml', 1450.00, 2000.00, 'Spirits Distributors', 1, 36.00],
            
            // Beer/Cider Category (5 items)
            ['Guinness Can/Btl', 8, 'Stout beer can or bottle', 'Guinness', 'Can/Btl', 215.00, 300.00, 'Beer Distributors', 1, 4.20],
            ['Heineken Can', 8, 'Lager beer can', 'Heineken', 'Can', 215.00, 300.00, 'Beer Distributors', 1, 5.00],
            ['Pilsner Can', 8, 'Pilsner beer can', 'Pilsner', 'Can', 215.00, 300.00, 'Beer Distributors', 1, 4.80],
            ['Tusker Various', 8, 'Tusker beer various sizes', 'Tusker', 'Various', 215.00, 300.00, 'Beer Distributors', 1, 5.20],
            ['Hunter\'s Dry Btl/Can', 8, 'Cider bottle or can', 'Hunter\'s', 'Btl/Can', 180.00, 250.00, 'Beer Distributors', 1, 4.50],
            
            // RTD/Soft Category (4 items)
            ['Afia Juice RTD 1L', 9, 'Ready-to-drink juice 1L', 'Afia', '1L', 120.00, 150.00, 'Beverage Suppliers', 0, 0.00],
            ['Red Bull Can', 9, 'Energy drink can', 'Red Bull', 'Can', 190.00, 300.00, 'Beverage Suppliers', 0, 0.00],
            ['Soda Coke/Fanta 1.25L', 9, 'Carbonated soft drinks 1.25L', 'Coca-Cola', '1.25L', 150.00, 200.00, 'Beverage Suppliers', 0, 0.00],
            ['Tonic Water Btl', 9, 'Tonic water bottle', 'Various', 'Btl', 75.00, 125.00, 'Beverage Suppliers', 0, 0.00],
            
            // Water Category (2 items)
            ['Bottled Water 500ml', 10, 'Drinking water 500ml', 'Various', '500ml', 45.00, 60.00, 'Water Suppliers', 0, 0.00],
            ['Bottled Water 1L', 10, 'Drinking water 1L', 'Various', '1L', 70.00, 100.00, 'Water Suppliers', 0, 0.00]
        ];
        
        $imported_count = 0;
        $skipped_count = 0;
        $updated_count = 0;
        
        foreach ($complete_inventory as $item) {
            // Check if item already exists by name
            $name = mysqli_real_escape_string($con, $item[0]);
            $check_item = "SELECT id FROM bar_inventory WHERE name = '$name'";
            $check_result = mysqli_query($con, $check_item);
            
            if (mysqli_num_rows($check_result) == 0) {
                // Item doesn't exist, insert it
                $description = mysqli_real_escape_string($con, $item[2]);
                $brand = mysqli_real_escape_string($con, $item[3]);
                $unit = mysqli_real_escape_string($con, $item[4]);
                $supplier = mysqli_real_escape_string($con, $item[7]);
                
                // Set smart stock levels based on category and size
                $initial_stock = 10; // Standard starting stock
                $minimum_stock = 5;  // Standard minimum
                $maximum_stock = 50; // Standard maximum
                
                // Adjust based on category
                switch ($item[1]) {
                    case 8: // Beer/Cider
                        $minimum_stock = 10;
                        $maximum_stock = 100;
                        break;
                    case 9: // RTD/Soft
                        $minimum_stock = 15;
                        $maximum_stock = 150;
                        break;
                    case 10: // Water
                        $minimum_stock = 25;
                        $maximum_stock = 300;
                        break;
                    case 3: // Spirits
                        if ($item[5] > 5000) { // Premium spirits
                            $minimum_stock = 3;
                            $maximum_stock = 20;
                        } else {
                            $minimum_stock = 6;
                            $maximum_stock = 40;
                        }
                        break;
                    case 2: // Wine
                        $minimum_stock = 4;
                        $maximum_stock = 30;
                        break;
                }
                
                $insert_item = "INSERT INTO bar_inventory 
                    (category_id, name, description, brand, unit, current_stock, minimum_stock, maximum_stock, unit_cost, selling_price, supplier, is_alcoholic, alcohol_percentage, is_active, created_at, updated_at) 
                    VALUES 
                    ({$item[1]}, '$name', '$description', '$brand', '$unit', $initial_stock, $minimum_stock, $maximum_stock, {$item[5]}, {$item[6]}, '$supplier', {$item[8]}, {$item[9]}, 1, '2025-01-20 00:00:00', '2025-01-20 00:00:00')";
                
                if (mysqli_query($con, $insert_item)) {
                    $imported_count++;
                    
                    // Record initial stock movement
                    $inventory_id = mysqli_insert_id($con);
                    $movement_sql = "INSERT INTO bar_inventory_movements 
                                    (inventory_id, movement_type, quantity, unit_cost, total_cost, notes, moved_by) 
                                    VALUES 
                                    ($inventory_id, 'stock_in', $initial_stock, {$item[5]}, " . ($initial_stock * $item[5]) . ", 'Initial inventory import', " . $_SESSION['user_id'] . ")";
                    mysqli_query($con, $movement_sql);
                }
            } else {
                // Item exists, update pricing
                $existing_item = mysqli_fetch_assoc($check_result);
                $update_sql = "UPDATE bar_inventory SET 
                              unit_cost = {$item[5]},
                              selling_price = {$item[6]},
                              updated_at = NOW()
                              WHERE id = " . $existing_item['id'];
                
                if (mysqli_query($con, $update_sql)) {
                    $updated_count++;
                }
            }
        }
        
        $success_message = "Complete inventory import finished! Added $categories_added categories, imported $imported_count new items, updated $updated_count existing items, skipped $skipped_count duplicates.";
        
    } catch (Exception $e) {
        $error_message = "Error importing complete inventory: " . $e->getMessage();
    }
}

// Get current inventory counts
$total_items_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE is_active = 1";
$total_items_result = mysqli_query($con, $total_items_query);
$total_items = mysqli_fetch_assoc($total_items_result)['count'];

$categories_query = "SELECT 
    bc.name as category_name,
    COUNT(bi.id) as item_count
    FROM bar_inventory bi 
    JOIN bar_categories bc ON bi.category_id = bc.id 
    WHERE bi.is_active = 1 
    GROUP BY bc.id, bc.name 
    ORDER BY bc.display_order";
$categories_result = mysqli_query($con, $categories_query);
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-warehouse"></i> Import Complete Bar Inventory
        </h1>
        <p class="page-subtitle">Import all 43 unique name+size combinations as separate inventory items</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="bar_inventory.php" class="btn btn-primary">
                    <i class="fa fa-eye"></i> View Complete Inventory
                </a>
                <a href="setup_dynamic_inventory.php" class="btn btn-secondary">
                    <i class="fa fa-cogs"></i> Setup Dynamic System
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Current Status -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-boxes text-primary"></i>
                </div>
                <div class="status-info">
                    <h3><?php echo $total_items; ?></h3>
                    <p>Current Total Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-plus-circle text-success"></i>
                </div>
                <div class="status-info">
                    <h3>43</h3>
                    <p>Items to Import</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-list"></i> Complete Inventory List (43 Unique Items)</h4>
                </div>
                <div class="card-body">
                    <div class="inventory-breakdown">
                        <div class="category-breakdown">
                            <h5><i class="fa fa-wine-glass text-danger"></i> Wine Items (7 variants)</h5>
                            <div class="items-list">
                                <div class="item">4th Street Red 750ml - KSh 1,350 → KSh 1,900</div>
                                <div class="item">4th Street White 750ml - KSh 1,350 → KSh 1,900</div>
                                <div class="item">Caprice Sweet Red 1L - KSh 800 → KSh 1,100</div>
                                <div class="item">Casa Buena Red 1L - KSh 950 → KSh 1,300</div>
                                <div class="item">Casa Buena White 750ml - KSh 950 → KSh 1,300</div>
                                <div class="item">Robertson Rosé 750ml - KSh 1,300 → KSh 1,800</div>
                                <div class="item">Robertson Dry 750ml - KSh 1,450 → KSh 2,000</div>
                            </div>
                        </div>
                        
                        <div class="category-breakdown">
                            <h5><i class="fa fa-glass-whiskey text-warning"></i> Spirits Items (25 variants)</h5>
                            <div class="items-list">
                                <div class="item">Captain Morgan: 350ml (KSh 430→600), 750ml (KSh 1,100→1,500)</div>
                                <div class="item">Gilbey's Gin: 250ml (KSh 540→750), 350ml (KSh 720→1,000), 750ml (KSh 1,450→2,000)</div>
                                <div class="item">Gordon's Gin 750ml - KSh 2,150 → KSh 3,000</div>
                                <div class="item">Grant's: 750ml (KSh 1,800→2,500), 1L (KSh 1,950→2,700)</div>
                                <div class="item">Hennessy: VS 750ml (KSh 6,500→9,050), VSOP 750ml (KSh 9,300→13,000)</div>
                                <div class="item">Jack Daniel's 750ml - KSh 3,200 → KSh 4,500</div>
                                <div class="item">Jameson: 350ml (KSh 1,300→1,800), 750ml (KSh 2,150→3,000), 1L (KSh 2,700→3,800)</div>
                                <div class="item">Jägermeister 750ml - KSh 2,600 → KSh 3,600</div>
                                <div class="item">J&B Rare 375ml - KSh 1,100 → KSh 1,500</div>
                                <div class="item">Johnnie Walker Black 750ml - KSh 4,300 → KSh 6,000</div>
                                <div class="item">Singleton 750ml - KSh 4,700 → KSh 6,500</div>
                                <div class="item">Smirnoff Vodka: 250ml (KSh 500→700), 350ml (KSh 720→1,000), 750ml (KSh 1,450→2,000)</div>
                                <div class="item">Vat 69: 250ml (KSh 650→900), 375ml (KSh 950→1,300), 750ml (KSh 1,600→2,200)</div>
                                <div class="item">Viceroy Brandy: 250ml (KSh 500→700), 350ml (KSh 720→1,000), 750ml (KSh 1,450→2,000)</div>
                            </div>
                        </div>
                        
                        <div class="category-breakdown">
                            <h5><i class="fa fa-beer text-info"></i> Beer/Cider Items (5 variants)</h5>
                            <div class="items-list">
                                <div class="item">Guinness Can/Btl - KSh 215 → KSh 300</div>
                                <div class="item">Heineken Can - KSh 215 → KSh 300</div>
                                <div class="item">Pilsner Can - KSh 215 → KSh 300</div>
                                <div class="item">Tusker Various - KSh 215 → KSh 300</div>
                                <div class="item">Hunter's Dry Btl/Can - KSh 180 → KSh 250</div>
                            </div>
                        </div>
                        
                        <div class="category-breakdown">
                            <h5><i class="fa fa-glass text-success"></i> RTD/Soft Items (4 variants)</h5>
                            <div class="items-list">
                                <div class="item">Afia Juice RTD 1L - KSh 120 → KSh 150</div>
                                <div class="item">Red Bull Can - KSh 190 → KSh 300</div>
                                <div class="item">Soda Coke/Fanta 1.25L - KSh 150 → KSh 200</div>
                                <div class="item">Tonic Water Btl - KSh 75 → KSh 125</div>
                            </div>
                        </div>
                        
                        <div class="category-breakdown">
                            <h5><i class="fa fa-tint text-primary"></i> Water Items (2 variants)</h5>
                            <div class="items-list">
                                <div class="item">Bottled Water 500ml - KSh 45 → KSh 60</div>
                                <div class="item">Bottled Water 1L - KSh 70 → KSh 100</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-info-circle"></i> Import Features:</strong><br>
                        • <strong>Unique naming:</strong> Each size is a separate item (e.g., "Smirnoff Vodka 250ml")<br>
                        • <strong>Smart stock levels:</strong> Auto-set initial stock to 10 with category-appropriate minimums<br>
                        • <strong>Exact pricing:</strong> Uses your provided buying and selling prices<br>
                        • <strong>Complete tracking:</strong> Creates stock movement records for all items
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-rocket"></i> Import Actions</h4>
                </div>
                <div class="card-body text-center">
                    <?php if (!isset($success_message)): ?>
                        <form method="POST">
                            <button type="submit" name="import_complete_inventory" class="btn btn-success btn-lg" 
                                    onclick="return confirm('This will import all 43 unique bar inventory items with size variants. Continue?')">
                                <i class="fa fa-warehouse"></i> Import Complete Inventory
                            </button>
                        </form>
                        
                        <div class="import-note mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                Each size variant will be imported as a separate item with unique pricing.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="bar_inventory.php" class="btn btn-outline-primary">
                            <i class="fa fa-list"></i> View Inventory
                        </a>
                        <a href="setup_dynamic_inventory.php" class="btn btn-outline-secondary mt-2">
                            <i class="fa fa-cogs"></i> Setup Dynamic System
                        </a>
                    </div>
                </div>
            </div>

            <!-- Current Categories -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fa fa-tags"></i> Current Categories</h4>
                </div>
                <div class="card-body">
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <div class="category-status">
                            <span class="cat-name"><?php echo $cat['category_name']; ?></span>
                            <span class="cat-count"><?php echo $cat['item_count']; ?> items</span>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-card {
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

.status-card:hover {
    transform: translateY(-3px);
}

.status-icon {
    font-size: 2.5rem;
    width: 60px;
    text-align: center;
}

.status-info h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.status-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.inventory-breakdown {
    max-height: 600px;
    overflow-y: auto;
}

.category-breakdown {
    margin-bottom: 25px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.category-breakdown h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.items-list {
    max-height: 200px;
    overflow-y: auto;
}

.item {
    background: white;
    padding: 8px 12px;
    margin-bottom: 5px;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #495057;
    border-left: 3px solid #28a745;
}

.import-note {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 6px;
    padding: 10px;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.category-status {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
}

.category-status:last-child {
    border-bottom: none;
}

.cat-name {
    font-weight: 500;
    color: #2c3e50;
}

.cat-count {
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
