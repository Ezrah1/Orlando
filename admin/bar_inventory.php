<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include 'db.php';

$page_title = 'Bar Inventory Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Bar Inventory Management</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_inventory_item':
                $category_id = (int)$_POST['category_id'];
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $brand = mysqli_real_escape_string($con, $_POST['brand']);
                $unit = mysqli_real_escape_string($con, $_POST['unit']);
                $current_stock = (float)$_POST['current_stock'];
                $minimum_stock = (float)$_POST['minimum_stock'];
                $maximum_stock = (float)$_POST['maximum_stock'];
                $unit_cost = (float)$_POST['unit_cost'];
                $selling_price = (float)$_POST['selling_price'];
                $supplier = mysqli_real_escape_string($con, $_POST['supplier']);
                $expiry_date = $_POST['expiry_date'] ? mysqli_real_escape_string($con, $_POST['expiry_date']) : NULL;
                $is_alcoholic = isset($_POST['is_alcoholic']) ? 1 : 0;
                $alcohol_percentage = (float)$_POST['alcohol_percentage'];
                
                $sql = "INSERT INTO bar_inventory (category_id, name, description, brand, unit, current_stock, minimum_stock, maximum_stock, unit_cost, selling_price, supplier, expiry_date, is_alcoholic, alcohol_percentage) 
                        VALUES ($category_id, '$name', '$description', '$brand', '$unit', $current_stock, $minimum_stock, $maximum_stock, $unit_cost, $selling_price, '$supplier', " . ($expiry_date ? "'$expiry_date'" : "NULL") . ", $is_alcoholic, $alcohol_percentage)";
                
                if(mysqli_query($con, $sql)) {
                    $inventory_id = mysqli_insert_id($con);
                    
                    // Record initial stock movement
                    if($current_stock > 0) {
                        $total_cost = $current_stock * $unit_cost;
                        mysqli_query($con, "INSERT INTO bar_inventory_movements (inventory_id, movement_type, quantity, unit_cost, total_cost, notes, moved_by) 
                                           VALUES ($inventory_id, 'stock_in', $current_stock, $unit_cost, $total_cost, 'Initial stock', " . $_SESSION['user_id'] . ")");
                    }
                    
                    $success = "Bar inventory item added successfully!";
                } else {
                    $error = "Failed to add inventory item.";
                }
                break;
        }
    }
}

// Get bar categories
$categories_query = "SELECT * FROM bar_categories WHERE is_active = 1 ORDER BY display_order, name";
$categories_result = mysqli_query($con, $categories_query);

// Get bar inventory items with category names
$inventory_query = "SELECT bi.*, bc.name as category_name 
                    FROM bar_inventory bi 
                    LEFT JOIN bar_categories bc ON bi.category_id = bc.id 
                    ORDER BY bc.display_order, bc.name, bi.name";
$inventory_result = mysqli_query($con, $inventory_query);

// Calculate inventory summary
$summary_query = "SELECT 
                    COUNT(*) as total_items,
                    SUM(current_stock * unit_cost) as total_value,
                    COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
                    COUNT(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as expiring_items,
                    COUNT(CASE WHEN is_alcoholic = 1 THEN 1 END) as alcoholic_items,
                    COUNT(CASE WHEN is_alcoholic = 0 THEN 1 END) as non_alcoholic_items
                  FROM bar_inventory 
                  WHERE is_active = 1";
$summary_result = mysqli_query($con, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);
?>


    
            <div class="container-fluid">
                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <h3><?php echo $summary['total_items']; ?></h3>
                            <p>Total Items</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <h3>KES <?php echo number_format($summary['total_value']); ?></h3>
                            <p>Total Value</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                            <h3><?php echo $summary['low_stock_items']; ?></h3>
                            <p>Low Stock</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                            <h3><?php echo $summary['expiring_items']; ?></h3>
                            <p>Expiring Soon</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #6f42c1 0%, #e83e8c 100%);">
                            <h3><?php echo $summary['alcoholic_items']; ?></h3>
                            <p>Alcoholic</p>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="summary-card" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                            <h3><?php echo $summary['non_alcoholic_items']; ?></h3>
                            <p>Non-Alcoholic</p>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <button class="btn btn-success" data-toggle="modal" data-target="#addInventoryModal">
                            <i class="fa fa-plus"></i> Add Bar Item
                        </button>
                        <a href="sync_bar_to_menu.php" class="btn btn-primary">
                            <i class="fa fa-sync-alt"></i> Sync to Menu
                        </a>
                        <a href="bar_orders.php" class="btn btn-info">
                            <i class="fa fa-glass"></i> Bar Orders
                        </a>
                        <a href="bar_sales_reports.php" class="btn btn-warning">
                            <i class="fa fa-chart-bar"></i> Sales Reports
                        </a>
                    </div>
                </div>

                <!-- Inventory Items -->
                <div class="row">
                    <div class="col-12">
                        <?php if(mysqli_num_rows($inventory_result) > 0): ?>
                            <?php while($item = mysqli_fetch_assoc($inventory_result)): 
                                $card_class = '';
                                if($item['current_stock'] <= $item['minimum_stock']) $card_class = 'low-stock';
                                $profit = $item['selling_price'] - $item['unit_cost'];
                                $profit_margin = $item['unit_cost'] > 0 ? (($profit / $item['unit_cost']) * 100) : 0;
                            ?>
                                <div class="inventory-card <?php echo $card_class; ?> mb-3" style="border: 1px solid #dee2e6; border-radius: 8px; padding: 20px; background: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h5><?php echo $item['name']; ?></h5>
                                            <p class="text-muted"><?php echo $item['description']; ?></p>
                                            <p><strong>Category:</strong> <?php echo $item['category_name']; ?></p>
                                            <p><strong>Brand:</strong> <?php echo $item['brand']; ?></p>
                                            <p><strong>Unit:</strong> <?php echo $item['unit']; ?></p>
                                            <span class="<?php echo $item['is_alcoholic'] ? 'alcohol-badge' : 'non-alcohol-badge'; ?>">
                                                <?php echo $item['is_alcoholic'] ? 'Alcoholic' : 'Non-Alcoholic'; ?>
                                            </span>
                                            <?php if($item['is_alcoholic'] && $item['alcohol_percentage'] > 0): ?>
                                                <span class="badge badge-info"><?php echo $item['alcohol_percentage']; ?>% ABV</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <h4 class="text-primary"><?php echo number_format($item['current_stock'], 3); ?></h4>
                                            <p><strong>Current Stock</strong></p>
                                            <p><strong>Min:</strong> <?php echo number_format($item['minimum_stock'], 3); ?></p>
                                            <p><strong>Max:</strong> <?php echo number_format($item['maximum_stock'], 3); ?></p>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <p><strong>Cost:</strong> KES <?php echo number_format($item['unit_cost'], 2); ?></p>
                                            <p><strong>Price:</strong> KES <?php echo number_format($item['selling_price'], 2); ?></p>
                                            <p><strong>Profit:</strong> <span class="text-success">KES <?php echo number_format($profit, 2); ?></span></p>
                                            <p><strong>Margin:</strong> <span class="text-info"><?php echo number_format($profit_margin, 1); ?>%</span></p>
                                            <p><strong>Stock Value:</strong> KES <?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></p>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <p><strong>Supplier:</strong> <?php echo $item['supplier']; ?></p>
                                            <?php if($item['expiry_date']): ?>
                                                <p><strong>Expires:</strong> <?php echo date('M j, Y', strtotime($item['expiry_date'])); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Status:</strong> 
                                                <?php echo $item['is_active'] ? '<span class="text-success">Active</span>' : '<span class="text-danger">Inactive</span>'; ?>
                                            </p>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="btn-group-vertical">
                                                <button class="btn btn-sm btn-primary">
                                                    <i class="fa fa-edit"></i> Update Stock
                                                </button>
                                                <button class="btn btn-sm btn-info">
                                                    <i class="fa fa-edit"></i> Edit Item
                                                </button>
                                                <a href="bar_inventory_movements.php?item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-history"></i> View History
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h4>No bar inventory items found</h4>
                                <p>Start by adding your first bar inventory item.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    <!-- Add Inventory Modal -->
    <div class="modal fade" id="addInventoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Bar Inventory Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_inventory_item">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category_id" class="form-control" required>
                                        <option value="">Select Category</option>
                                        <?php 
                                        mysqli_data_seek($categories_result, 0);
                                        while($cat = mysqli_fetch_assoc($categories_result)): 
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>"><?php echo $cat['name']; ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Item Name</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Brand</label>
                                    <input type="text" name="brand" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="e.g., bottle, glass, packet" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Current Stock</label>
                                    <input type="number" name="current_stock" class="form-control" step="0.001" value="0" min="0" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Minimum Stock Level</label>
                                    <input type="number" name="minimum_stock" class="form-control" step="0.001" value="0" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Maximum Stock Level</label>
                                    <input type="number" name="maximum_stock" class="form-control" step="0.001" value="0" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Unit Cost (KES)</label>
                                    <input type="number" name="unit_cost" class="form-control" step="0.01" value="0.00" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Selling Price (KES)</label>
                                    <input type="number" name="selling_price" class="form-control" step="0.01" value="0.00" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" name="supplier" class="form-control" placeholder="Supplier name or contact info">
                                </div>
                                
                                <div class="form-group">
                                    <label>Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_alcoholic" checked> Alcoholic Beverage</label>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Alcohol Percentage (%)</label>
                                    <input type="number" name="alcohol_percentage" class="form-control" step="0.01" value="0.00" min="0" max="100">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<style>
.summary-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    transition: transform 0.3s ease;
}

.summary-card:hover {
    transform: translateY(-5px);
}

.summary-card h3 {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 5px;
}

.summary-card p {
    font-size: 14px;
    margin: 0;
    opacity: 0.9;
}

.inventory-card.low-stock {
    border-left: 5px solid #dc3545 !important;
    background-color: #fff5f5 !important;
}

.alcohol-badge {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.non-alcohol-badge {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.inventory-card h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 10px;
}

.inventory-card .text-primary {
    color: #3498db !important;
    font-weight: bold;
}

.btn-group-vertical .btn {
    margin-bottom: 5px;
}

.inventory-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    transform: translateY(-2px);
    transition: all 0.3s ease;
}

@media (max-width: 768px) {
    .summary-card {
        margin-bottom: 15px;
    }
    
    .inventory-card .col-md-3,
    .inventory-card .col-md-2 {
        margin-bottom: 15px;
    }
}
</style>

<?php include '../includes/admin/footer.php'; ?>