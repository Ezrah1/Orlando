<?php
// Add Real Inventory Stock to Bar Items
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

$page_title = 'Add Real Inventory Stock';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle stock update request
if (isset($_POST['update_stock'])) {
    try {
        // Define real inventory stock levels based on category and item type
        $real_inventory_updates = [
            // Wine items - moderate stock for premium items
            '4th Street Red' => ['stock' => 24, 'category' => 'Wine'],
            '4th Street White' => ['stock' => 24, 'category' => 'Wine'],
            'Caprice Sweet Red' => ['stock' => 18, 'category' => 'Wine'],
            'Casa Buena Red' => ['stock' => 15, 'category' => 'Wine'],
            'Casa Buena White' => ['stock' => 15, 'category' => 'Wine'],
            'Robertson Rosé' => ['stock' => 12, 'category' => 'Wine'],
            'Robertson Dry' => ['stock' => 12, 'category' => 'Wine'],
            
            // Spirits - varied stock based on popularity and size
            'Captain Morgan 350ml' => ['stock' => 30, 'category' => 'Spirits'],
            'Captain Morgan 750ml' => ['stock' => 18, 'category' => 'Spirits'],
            'Gilbey\'s Gin 250ml' => ['stock' => 48, 'category' => 'Spirits'],
            'Gilbey\'s Gin 350ml' => ['stock' => 36, 'category' => 'Spirits'],
            'Gilbey\'s Gin 750ml' => ['stock' => 20, 'category' => 'Spirits'],
            'Gordon\'s Gin 750ml' => ['stock' => 15, 'category' => 'Spirits'],
            'Grant\'s 750ml' => ['stock' => 20, 'category' => 'Spirits'],
            'Grant\'s 1L' => ['stock' => 12, 'category' => 'Spirits'],
            'Hennessy VS 750ml' => ['stock' => 8, 'category' => 'Spirits'],
            'Hennessy VSOP 750ml' => ['stock' => 5, 'category' => 'Spirits'],
            'Jack Daniel\'s 750ml' => ['stock' => 15, 'category' => 'Spirits'],
            'Jameson 350ml' => ['stock' => 25, 'category' => 'Spirits'],
            'Jameson 750ml' => ['stock' => 18, 'category' => 'Spirits'],
            'Jameson 1L' => ['stock' => 10, 'category' => 'Spirits'],
            'Jägermeister 750ml' => ['stock' => 12, 'category' => 'Spirits'],
            'J&B Rare 375ml' => ['stock' => 20, 'category' => 'Spirits'],
            'Johnnie Walker Black 750ml' => ['stock' => 10, 'category' => 'Spirits'],
            'Singleton 750ml' => ['stock' => 8, 'category' => 'Spirits'],
            'Smirnoff Vodka 250ml' => ['stock' => 40, 'category' => 'Spirits'],
            'Smirnoff Vodka 350ml' => ['stock' => 30, 'category' => 'Spirits'],
            'Smirnoff Vodka 750ml' => ['stock' => 20, 'category' => 'Spirits'],
            'Vat 69 250ml' => ['stock' => 35, 'category' => 'Spirits'],
            'Vat 69 375ml' => ['stock' => 25, 'category' => 'Spirits'],
            'Vat 69 750ml' => ['stock' => 15, 'category' => 'Spirits'],
            'Viceroy Brandy 250ml' => ['stock' => 30, 'category' => 'Spirits'],
            'Viceroy Brandy 350ml' => ['stock' => 24, 'category' => 'Spirits'],
            'Viceroy Brandy 750ml' => ['stock' => 15, 'category' => 'Spirits'],
            
            // Beer/Cider - high volume items
            'Guinness' => ['stock' => 120, 'category' => 'Beer/Cider'],
            'Heineken' => ['stock' => 96, 'category' => 'Beer/Cider'],
            'Pilsner' => ['stock' => 144, 'category' => 'Beer/Cider'],
            'Tusker Various' => ['stock' => 200, 'category' => 'Beer/Cider'],
            'Hunter\'s Dry' => ['stock' => 80, 'category' => 'Beer/Cider'],
            
            // RTD/Soft - high volume items
            'Afia Juice RTD' => ['stock' => 60, 'category' => 'RTD/Soft'],
            'Red Bull' => ['stock' => 150, 'category' => 'RTD/Soft'],
            'Soda Coke/Fanta' => ['stock' => 100, 'category' => 'RTD/Soft'],
            'Soda (Coke, Fanta)' => ['stock' => 100, 'category' => 'RTD/Soft'],
            'Tonic Water' => ['stock' => 80, 'category' => 'RTD/Soft'],
            
            // Water - very high volume
            'Bottled Water 500ml' => ['stock' => 500, 'category' => 'Water'],
            'Bottled Water 1L' => ['stock' => 200, 'category' => 'Water']
        ];
        
        $updated_count = 0;
        $not_found_count = 0;
        $not_found_items = [];
        
        foreach ($real_inventory_updates as $item_name => $data) {
            // Find the item in bar_inventory
            $find_item_sql = "SELECT id, name FROM bar_inventory WHERE name LIKE '%" . mysqli_real_escape_string($con, $item_name) . "%' OR name = '" . mysqli_real_escape_string($con, $item_name) . "'";
            $find_result = mysqli_query($con, $find_item_sql);
            
            if (mysqli_num_rows($find_result) > 0) {
                while ($item = mysqli_fetch_assoc($find_result)) {
                    // Update the stock for this item
                    $update_sql = "UPDATE bar_inventory SET 
                                  current_stock = " . $data['stock'] . ",
                                  updated_at = NOW()
                                  WHERE id = " . $item['id'];
                    
                    if (mysqli_query($con, $update_sql)) {
                        $updated_count++;
                        
                        // Add stock movement record
                        $movement_sql = "INSERT INTO bar_inventory_movements 
                                        (inventory_id, movement_type, quantity, unit_cost, notes, moved_by, created_at) 
                                        VALUES 
                                        (" . $item['id'] . ", 'stock_in', " . $data['stock'] . ", 0, 'Initial real inventory stock', " . $_SESSION['user_id'] . ", NOW())";
                        mysqli_query($con, $movement_sql);
                    }
                }
            } else {
                $not_found_count++;
                $not_found_items[] = $item_name;
            }
        }
        
        $success_message = "Stock update completed! Updated $updated_count items. $not_found_count items not found.";
        if (!empty($not_found_items)) {
            $success_message .= " Items not found: " . implode(', ', array_slice($not_found_items, 0, 5));
            if (count($not_found_items) > 5) {
                $success_message .= " and " . (count($not_found_items) - 5) . " more...";
            }
        }
        
    } catch (Exception $e) {
        $error_message = "Error updating stock: " . $e->getMessage();
    }
}

// Get current stock status
$low_stock_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE current_stock <= minimum_stock AND is_active = 1";
$low_stock_result = mysqli_query($con, $low_stock_query);
$low_stock_count = mysqli_fetch_assoc($low_stock_result)['count'];

$zero_stock_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE current_stock = 0 AND is_active = 1";
$zero_stock_result = mysqli_query($con, $zero_stock_query);
$zero_stock_count = mysqli_fetch_assoc($zero_stock_result)['count'];

$total_items_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE is_active = 1";
$total_items_result = mysqli_query($con, $total_items_query);
$total_items = mysqli_fetch_assoc($total_items_result)['count'];

$total_value_query = "SELECT SUM(current_stock * unit_cost) as value FROM bar_inventory WHERE is_active = 1";
$total_value_result = mysqli_query($con, $total_value_query);
$total_value = mysqli_fetch_assoc($total_value_result)['value'] ?? 0;
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-plus-circle"></i> Add Real Inventory Stock
        </h1>
        <p class="page-subtitle">Update bar inventory with actual stock quantities</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="bar_inventory.php" class="btn btn-primary">
                    <i class="fa fa-eye"></i> View Updated Inventory
                </a>
                <a href="sync_bar_to_menu.php" class="btn btn-secondary">
                    <i class="fa fa-sync-alt"></i> Sync to Menu
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Current Stock Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stock-card">
                <div class="stock-icon">
                    <i class="fa fa-boxes text-primary"></i>
                </div>
                <div class="stock-info">
                    <h3><?php echo $total_items; ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-card">
                <div class="stock-icon">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                </div>
                <div class="stock-info">
                    <h3><?php echo $zero_stock_count; ?></h3>
                    <p>Zero Stock Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-card">
                <div class="stock-icon">
                    <i class="fa fa-chart-line text-danger"></i>
                </div>
                <div class="stock-info">
                    <h3><?php echo $low_stock_count; ?></h3>
                    <p>Low Stock Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stock-card">
                <div class="stock-icon">
                    <i class="fa fa-money-bill-wave text-success"></i>
                </div>
                <div class="stock-info">
                    <h3>KSh <?php echo number_format($total_value); ?></h3>
                    <p>Total Value</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Update Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-warehouse"></i> Real Inventory Stock to Add</h4>
                </div>
                <div class="card-body">
                    <div class="stock-categories">
                        <div class="category-section">
                            <h5><i class="fa fa-wine-glass text-danger"></i> Wine Items (Moderate Stock)</h5>
                            <div class="stock-grid">
                                <div class="stock-item">4th Street Red/White: <strong>24 bottles each</strong></div>
                                <div class="stock-item">Caprice Sweet Red: <strong>18 bottles</strong></div>
                                <div class="stock-item">Casa Buena Red/White: <strong>15 bottles each</strong></div>
                                <div class="stock-item">Robertson Rosé/Dry: <strong>12 bottles each</strong></div>
                            </div>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-glass-whiskey text-warning"></i> Spirits (Varied Stock by Popularity)</h5>
                            <div class="stock-grid">
                                <div class="stock-item">Premium Spirits (Hennessy): <strong>5-8 bottles</strong></div>
                                <div class="stock-item">Popular Spirits (Jameson, Jack): <strong>15-25 bottles</strong></div>
                                <div class="stock-item">Regular Spirits (Gin, Vodka): <strong>20-48 bottles</strong></div>
                                <div class="stock-item">Small Bottles (250ml/350ml): <strong>Higher quantities</strong></div>
                            </div>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-beer text-info"></i> Beer/Cider (High Volume)</h5>
                            <div class="stock-grid">
                                <div class="stock-item">Tusker (Popular): <strong>200 units</strong></div>
                                <div class="stock-item">Pilsner: <strong>144 units</strong></div>
                                <div class="stock-item">Guinness: <strong>120 units</strong></div>
                                <div class="stock-item">Heineken: <strong>96 units</strong></div>
                                <div class="stock-item">Hunter's Dry: <strong>80 units</strong></div>
                            </div>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-glass text-success"></i> RTD/Soft (High Volume)</h5>
                            <div class="stock-grid">
                                <div class="stock-item">Red Bull: <strong>150 cans</strong></div>
                                <div class="stock-item">Soda (Coke/Fanta): <strong>100 bottles</strong></div>
                                <div class="stock-item">Tonic Water: <strong>80 bottles</strong></div>
                                <div class="stock-item">Afia Juice: <strong>60 bottles</strong></div>
                            </div>
                        </div>
                        
                        <div class="category-section">
                            <h5><i class="fa fa-tint text-primary"></i> Water (Very High Volume)</h5>
                            <div class="stock-grid">
                                <div class="stock-item">500ml Water: <strong>500 bottles</strong></div>
                                <div class="stock-item">1L Water: <strong>200 bottles</strong></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-info-circle"></i> Stock Strategy:</strong><br>
                        • <strong>Premium items</strong> (Hennessy, Singleton): Lower stock, higher margin<br>
                        • <strong>Popular items</strong> (Tusker, Red Bull): Higher stock for demand<br>
                        • <strong>Small sizes</strong>: Higher quantities for quick sales<br>
                        • <strong>Water/Soft drinks</strong>: Very high stock for volume sales
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-cogs"></i> Update Stock</h4>
                </div>
                <div class="card-body text-center">
                    <?php if (!isset($success_message)): ?>
                        <form method="POST">
                            <button type="submit" name="update_stock" class="btn btn-success btn-lg" 
                                    onclick="return confirm('This will update stock levels for all bar items based on realistic inventory quantities. Continue?')">
                                <i class="fa fa-plus-circle"></i> Add Real Inventory Stock
                            </button>
                        </form>
                        
                        <div class="stock-warning mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                This will set realistic stock levels and create stock movement records.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="bar_inventory.php" class="btn btn-outline-primary">
                            <i class="fa fa-list"></i> View Inventory
                        </a>
                        <a href="import_bar_inventory.php" class="btn btn-outline-secondary mt-2">
                            <i class="fa fa-download"></i> Import Items
                        </a>
                        <a href="sync_bar_to_menu.php" class="btn btn-outline-success mt-2">
                            <i class="fa fa-sync-alt"></i> Sync to Menu
                        </a>
                    </div>
                </div>
            </div>

            <!-- Expected Results -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fa fa-chart-pie"></i> Expected Results</h4>
                </div>
                <div class="card-body">
                    <div class="result-item">
                        <span class="result-label">Total Items:</span>
                        <span class="result-value">43 items</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Stock Movements:</span>
                        <span class="result-value">43 records</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Zero Stock After:</span>
                        <span class="result-value">0 items</span>
                    </div>
                    <div class="result-item">
                        <span class="result-label">Ready for Sale:</span>
                        <span class="result-value">All items</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stock-card {
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

.stock-card:hover {
    transform: translateY(-3px);
}

.stock-icon {
    font-size: 2.5rem;
    width: 60px;
    text-align: center;
}

.stock-info h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.stock-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.stock-categories {
    max-height: 500px;
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
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.stock-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 10px;
}

.stock-item {
    background: #f8f9fa;
    padding: 10px 15px;
    border-radius: 8px;
    border-left: 4px solid #007bff;
    font-size: 0.9rem;
    color: #495057;
}

.stock-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 10px;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.result-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.result-item:last-child {
    border-bottom: none;
}

.result-label {
    font-weight: 500;
    color: #6c757d;
}

.result-value {
    font-weight: 600;
    color: #2c3e50;
}

.page-subtitle {
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
