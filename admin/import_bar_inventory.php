<?php
// Import New Bar Inventory Items
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

$page_title = 'Import Bar Inventory Items';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle import request
if (isset($_POST['import_items'])) {
    try {
        // First check if we need to add new categories
        $new_categories = [
            [8, 'Beer/Cider', 'Beers and ciders by bottle/can', 1, 8],
            [9, 'RTD/Soft', 'Ready-to-drink and soft beverages', 1, 9],
            [10, 'Water', 'Bottled water in various sizes', 1, 10]
        ];
        
        $categories_added = 0;
        foreach ($new_categories as $cat) {
            $check_cat = "SELECT id FROM bar_categories WHERE id = " . $cat[0];
            if (mysqli_num_rows(mysqli_query($con, $check_cat)) == 0) {
                $insert_cat = "INSERT INTO bar_categories (id, name, description, is_active, display_order, created_at, updated_at) 
                               VALUES ({$cat[0]}, '{$cat[1]}', '{$cat[2]}', {$cat[3]}, {$cat[4]}, '2025-01-20 00:00:00', '2025-01-20 00:00:00')";
                if (mysqli_query($con, $insert_cat)) {
                    $categories_added++;
                }
            }
        }
        
        // Now add the new bar inventory items
        $new_items = [
            // Wine items
            [17, 2, '4th Street Red', 'Red wine 750ml', '4th Street', '750ml', 0.000, 5.000, 50.000, 1350.00, 1900.00, 'Wine Suppliers', NULL, 1, 13.00, 1],
            [18, 2, '4th Street White', 'White wine 750ml', '4th Street', '750ml', 0.000, 5.000, 50.000, 1350.00, 1900.00, 'Wine Suppliers', NULL, 1, 12.50, 1],
            [19, 2, 'Caprice Sweet Red', 'Sweet red wine 1L', 'Caprice', '1L', 0.000, 5.000, 30.000, 800.00, 1100.00, 'Wine Suppliers', NULL, 1, 12.00, 1],
            [20, 2, 'Casa Buena Red', 'Red wine 1L', 'Casa Buena', '1L', 0.000, 5.000, 30.000, 950.00, 1300.00, 'Wine Suppliers', NULL, 1, 13.50, 1],
            [21, 2, 'Casa Buena White', 'White wine 750ml', 'Casa Buena', '750ml', 0.000, 5.000, 30.000, 950.00, 1300.00, 'Wine Suppliers', NULL, 1, 12.80, 1],
            [22, 2, 'Robertson Rosé', 'Rosé wine 750ml', 'Robertson', '750ml', 0.000, 3.000, 25.000, 1300.00, 1800.00, 'Wine Suppliers', NULL, 1, 12.00, 1],
            [23, 2, 'Robertson Dry', 'Dry wine 750ml', 'Robertson', '750ml', 0.000, 3.000, 25.000, 1450.00, 2000.00, 'Wine Suppliers', NULL, 1, 13.50, 1],
            
            // Spirits items
            [24, 3, 'Captain Morgan 350ml', 'Spiced rum 350ml', 'Captain Morgan', '350ml', 0.000, 5.000, 50.000, 430.00, 600.00, 'Spirits Distributors', NULL, 1, 35.00, 1],
            [25, 3, 'Captain Morgan 750ml', 'Spiced rum 750ml', 'Captain Morgan', '750ml', 0.000, 3.000, 30.000, 1100.00, 1500.00, 'Spirits Distributors', NULL, 1, 35.00, 1],
            [26, 3, 'Gilbey\'s Gin 250ml', 'London dry gin 250ml', 'Gilbey\'s', '250ml', 0.000, 8.000, 60.000, 540.00, 750.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [27, 3, 'Gilbey\'s Gin 350ml', 'London dry gin 350ml', 'Gilbey\'s', '350ml', 0.000, 5.000, 40.000, 720.00, 1000.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [28, 3, 'Gilbey\'s Gin 750ml', 'London dry gin 750ml', 'Gilbey\'s', '750ml', 0.000, 3.000, 25.000, 1450.00, 2000.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [29, 3, 'Gordon\'s Gin 750ml', 'Premium gin 750ml', 'Gordon\'s', '750ml', 0.000, 2.000, 20.000, 2150.00, 3000.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [30, 3, 'Grant\'s 750ml', 'Scotch whisky 750ml', 'Grant\'s', '750ml', 0.000, 3.000, 25.000, 1800.00, 2500.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [31, 3, 'Grant\'s 1L', 'Scotch whisky 1L', 'Grant\'s', '1L', 0.000, 2.000, 20.000, 1950.00, 2700.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [32, 3, 'Hennessy VS 750ml', 'Cognac VS 750ml', 'Hennessy', '750ml', 0.000, 1.000, 10.000, 6500.00, 9050.00, 'Premium Spirits', NULL, 1, 40.00, 1],
            [33, 3, 'Hennessy VSOP 750ml', 'Cognac VSOP 750ml', 'Hennessy', '750ml', 0.000, 1.000, 8.000, 9300.00, 13000.00, 'Premium Spirits', NULL, 1, 40.00, 1],
            [34, 3, 'Jack Daniel\'s 750ml', 'Tennessee whiskey 750ml', 'Jack Daniel\'s', '750ml', 0.000, 2.000, 20.000, 3200.00, 4500.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [35, 3, 'Jameson 350ml', 'Irish whiskey 350ml', 'Jameson', '350ml', 0.000, 3.000, 30.000, 1300.00, 1800.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [36, 3, 'Jameson 750ml', 'Irish whiskey 750ml', 'Jameson', '750ml', 0.000, 2.000, 20.000, 2150.00, 3000.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [37, 3, 'Jameson 1L', 'Irish whiskey 1L', 'Jameson', '1L', 0.000, 2.000, 15.000, 2700.00, 3800.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [38, 3, 'Jägermeister 750ml', 'Herbal liqueur 750ml', 'Jägermeister', '750ml', 0.000, 2.000, 20.000, 2600.00, 3600.00, 'Spirits Distributors', NULL, 1, 35.00, 1],
            [39, 3, 'J&B Rare 375ml', 'Scotch whisky 375ml', 'J&B', '375ml', 0.000, 3.000, 30.000, 1100.00, 1500.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [40, 3, 'Singleton 750ml', 'Single malt whisky 750ml', 'Singleton', '750ml', 0.000, 1.000, 10.000, 4700.00, 6500.00, 'Premium Spirits', NULL, 1, 40.00, 1],
            [41, 3, 'Smirnoff Vodka 250ml', 'Premium vodka 250ml', 'Smirnoff', '250ml', 0.000, 8.000, 60.000, 500.00, 700.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [42, 3, 'Smirnoff Vodka 350ml', 'Premium vodka 350ml', 'Smirnoff', '350ml', 0.000, 5.000, 40.000, 720.00, 1000.00, 'Spirits Distributors', NULL, 1, 37.50, 1],
            [43, 3, 'Vat 69 250ml', 'Blended whisky 250ml', 'Vat 69', '250ml', 0.000, 5.000, 40.000, 650.00, 900.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [44, 3, 'Vat 69 375ml', 'Blended whisky 375ml', 'Vat 69', '375ml', 0.000, 3.000, 30.000, 950.00, 1300.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [45, 3, 'Vat 69 750ml', 'Blended whisky 750ml', 'Vat 69', '750ml', 0.000, 2.000, 20.000, 1600.00, 2200.00, 'Spirits Distributors', NULL, 1, 40.00, 1],
            [46, 3, 'Viceroy Brandy 250ml', 'Brandy 250ml', 'Viceroy', '250ml', 0.000, 5.000, 40.000, 500.00, 700.00, 'Spirits Distributors', NULL, 1, 36.00, 1],
            [47, 3, 'Viceroy Brandy 350ml', 'Brandy 350ml', 'Viceroy', '350ml', 0.000, 3.000, 30.000, 720.00, 1000.00, 'Spirits Distributors', NULL, 1, 36.00, 1],
            [48, 3, 'Viceroy Brandy 750ml', 'Brandy 750ml', 'Viceroy', '750ml', 0.000, 2.000, 20.000, 1450.00, 2000.00, 'Spirits Distributors', NULL, 1, 36.00, 1],
            
            // Beer/Cider items
            [49, 8, 'Guinness', 'Stout beer can/bottle', 'Guinness', 'can/bottle', 0.000, 20.000, 100.000, 215.00, 300.00, 'Beer Distributors', NULL, 1, 4.20, 1],
            [50, 8, 'Heineken', 'Lager beer can', 'Heineken', 'can', 0.000, 20.000, 100.000, 215.00, 300.00, 'Beer Distributors', NULL, 1, 5.00, 1],
            [51, 8, 'Pilsner', 'Pilsner beer can', 'Pilsner', 'can', 0.000, 20.000, 100.000, 215.00, 300.00, 'Beer Distributors', NULL, 1, 4.80, 1],
            [52, 8, 'Tusker Various', 'Tusker beer various sizes', 'Tusker', 'various', 0.000, 30.000, 150.000, 215.00, 300.00, 'Beer Distributors', NULL, 1, 5.20, 1],
            [53, 8, 'Hunter\'s Dry', 'Cider bottle/can', 'Hunter\'s', 'bottle/can', 0.000, 15.000, 80.000, 180.00, 250.00, 'Beer Distributors', NULL, 1, 4.50, 1],
            
            // RTD/Soft items
            [54, 9, 'Afia Juice RTD', 'Ready-to-drink juice 1L', 'Afia', '1L', 0.000, 10.000, 50.000, 120.00, 150.00, 'Beverage Suppliers', NULL, 0, 0.00, 1],
            [55, 9, 'Red Bull', 'Energy drink can', 'Red Bull', 'can', 0.000, 20.000, 100.000, 190.00, 300.00, 'Beverage Suppliers', NULL, 0, 0.00, 1],
            [56, 9, 'Soda Coke/Fanta', 'Carbonated soft drinks 1.25L', 'Coca-Cola', '1.25L', 0.000, 15.000, 75.000, 150.00, 200.00, 'Beverage Suppliers', NULL, 0, 0.00, 1],
            [57, 9, 'Tonic Water', 'Tonic water bottle', 'Various', 'bottle', 0.000, 10.000, 50.000, 75.00, 125.00, 'Beverage Suppliers', NULL, 0, 0.00, 1],
            
            // Water items
            [58, 10, 'Bottled Water 500ml', 'Drinking water 500ml', 'Various', '500ml', 0.000, 50.000, 200.000, 45.00, 60.00, 'Water Suppliers', NULL, 0, 0.00, 1],
            [59, 10, 'Bottled Water 1L', 'Drinking water 1L', 'Various', '1L', 0.000, 30.000, 120.000, 70.00, 100.00, 'Water Suppliers', NULL, 0, 0.00, 1]
        ];
        
        $imported_count = 0;
        $skipped_count = 0;
        
        foreach ($new_items as $item) {
            // Check if item already exists
            $check_item = "SELECT id FROM bar_inventory WHERE id = " . $item[0];
            if (mysqli_num_rows(mysqli_query($con, $check_item)) == 0) {
                // Item doesn't exist, insert it
                $expiry_date = $item[12] === NULL ? 'NULL' : "'" . mysqli_real_escape_string($con, $item[12]) . "'";
                
                // Properly escape all string values
                $name = mysqli_real_escape_string($con, $item[2]);
                $description = mysqli_real_escape_string($con, $item[3]);
                $brand = mysqli_real_escape_string($con, $item[4]);
                $unit = mysqli_real_escape_string($con, $item[5]);
                $supplier = mysqli_real_escape_string($con, $item[11]);
                
                $insert_item = "INSERT INTO bar_inventory 
                    (id, category_id, name, description, brand, unit, current_stock, minimum_stock, maximum_stock, unit_cost, selling_price, supplier, expiry_date, is_alcoholic, alcohol_percentage, is_active, created_at, updated_at) 
                    VALUES 
                    ({$item[0]}, {$item[1]}, '$name', '$description', '$brand', '$unit', {$item[6]}, {$item[7]}, {$item[8]}, {$item[9]}, {$item[10]}, '$supplier', $expiry_date, {$item[13]}, {$item[14]}, {$item[15]}, '2025-01-20 00:00:00', '2025-01-20 00:00:00')";
                
                if (mysqli_query($con, $insert_item)) {
                    $imported_count++;
                }
            } else {
                $skipped_count++;
            }
        }
        
        $success_message = "Import completed! Added $categories_added new categories, imported $imported_count new bar items, skipped $skipped_count existing items.";
        
    } catch (Exception $e) {
        $error_message = "Error importing bar inventory: " . $e->getMessage();
    }
}

// Get current counts
$current_items_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE is_active = 1";
$current_items_result = mysqli_query($con, $current_items_query);
$current_items = mysqli_fetch_assoc($current_items_result)['count'];

$current_categories_query = "SELECT COUNT(*) as count FROM bar_categories WHERE is_active = 1";
$current_categories_result = mysqli_query($con, $current_categories_query);
$current_categories = mysqli_fetch_assoc($current_categories_result)['count'];
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-download"></i> Import Bar Inventory Items
        </h1>
        <p class="page-subtitle">Add all the new bar inventory items to your database</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="bar_inventory.php" class="btn btn-primary">
                    <i class="fa fa-eye"></i> View Bar Inventory
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Import Overview -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="import-card">
                <div class="import-icon">
                    <i class="fa fa-database"></i>
                </div>
                <div class="import-info">
                    <h3><?php echo $current_items; ?></h3>
                    <p>Current Bar Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="import-card">
                <div class="import-icon">
                    <i class="fa fa-tags"></i>
                </div>
                <div class="import-info">
                    <h3><?php echo $current_categories; ?></h3>
                    <p>Current Categories</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-list"></i> Items to Import</h4>
                </div>
                <div class="card-body">
                    <div class="import-details">
                        <div class="category-section">
                            <h5><i class="fa fa-wine-glass"></i> Wine Items (7 items)</h5>
                            <ul>
                                <li>4th Street Red & White (750ml)</li>
                                <li>Caprice Sweet Red (1L)</li>
                                <li>Casa Buena Red & White</li>
                                <li>Robertson Rosé & Dry (750ml)</li>
                            </ul>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-glass-whiskey"></i> Spirits Items (25 items)</h5>
                            <ul>
                                <li>Captain Morgan (350ml & 750ml)</li>
                                <li>Gilbey's Gin (250ml, 350ml, 750ml)</li>
                                <li>Gordon's Gin, Grant's, Hennessy VS/VSOP</li>
                                <li>Jack Daniel's, Jameson (multiple sizes)</li>
                                <li>Jägermeister, J&B Rare, Singleton</li>
                                <li>Smirnoff Vodka, Vat 69, Viceroy Brandy</li>
                            </ul>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-beer"></i> Beer/Cider Items (5 items)</h5>
                            <ul>
                                <li>Guinness, Heineken, Pilsner</li>
                                <li>Tusker Various, Hunter's Dry</li>
                            </ul>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-glass"></i> RTD/Soft Items (4 items)</h5>
                            <ul>
                                <li>Afia Juice RTD, Red Bull</li>
                                <li>Soda (Coke/Fanta), Tonic Water</li>
                            </ul>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-tint"></i> Water Items (2 items)</h5>
                            <ul>
                                <li>Bottled Water 500ml & 1L</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-info-circle"></i> Import Details:</strong><br>
                        • <strong>43 new bar items</strong> with proper pricing and specifications<br>
                        • <strong>3 new categories:</strong> Beer/Cider, RTD/Soft, Water<br>
                        • <strong>Real pricing data</strong> from your provided list<br>
                        • <strong>Stock levels</strong> set to 0 (ready for initial stock entry)
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-cogs"></i> Import Actions</h4>
                </div>
                <div class="card-body text-center">
                    <?php if (!isset($success_message)): ?>
                        <form method="POST">
                            <button type="submit" name="import_items" class="btn btn-success btn-lg" 
                                    onclick="return confirm('This will import 43 new bar items and 3 new categories. Continue?')">
                                <i class="fa fa-download"></i> Import All Bar Items
                            </button>
                        </form>
                        
                        <div class="import-warning mt-3">
                            <small class="text-muted">
                                <i class="fa fa-exclamation-triangle"></i>
                                This will add new items to your database. Existing items will be skipped.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="bar_inventory.php" class="btn btn-outline-primary">
                            <i class="fa fa-list"></i> View Current Inventory
                        </a>
                        <a href="sync_bar_to_menu.php" class="btn btn-outline-secondary mt-2">
                            <i class="fa fa-sync-alt"></i> Sync to Menu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.import-card {
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

.import-card:hover {
    transform: translateY(-3px);
}

.import-icon {
    font-size: 2.5rem;
    color: #28a745;
    width: 60px;
    text-align: center;
}

.import-info h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.import-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.import-details {
    max-height: 400px;
    overflow-y: auto;
}

.category-section {
    margin-bottom: 25px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e9ecef;
}

.category-section:last-child {
    border-bottom: none;
}

.category-section h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.category-section ul {
    margin: 0;
    padding-left: 20px;
}

.category-section li {
    color: #6c757d;
    margin-bottom: 5px;
}

.import-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 10px;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
