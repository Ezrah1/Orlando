<?php
$page_title = 'Kitchen Inventory Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Kitchen Inventory Management</h1>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php



// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'add_inventory_item':
                $category_id = (int)$_POST['category_id'];
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $unit = mysqli_real_escape_string($con, $_POST['unit']);
                $current_stock = (float)$_POST['current_stock'];
                $minimum_stock = (float)$_POST['minimum_stock'];
                $maximum_stock = (float)$_POST['maximum_stock'];
                $unit_cost = (float)$_POST['unit_cost'];
                $supplier = mysqli_real_escape_string($con, $_POST['supplier']);
                $expiry_date = $_POST['expiry_date'] ? mysqli_real_escape_string($con, $_POST['expiry_date']) : NULL;
                
                $sql = "INSERT INTO kitchen_inventory (category_id, name, description, unit, current_stock, minimum_stock, maximum_stock, unit_cost, supplier, expiry_date) 
                        VALUES ($category_id, '$name', '$description', '$unit', $current_stock, $minimum_stock, $maximum_stock, $unit_cost, '$supplier', " . ($expiry_date ? "'$expiry_date'" : "NULL") . ")";
                
                if(mysqli_query($con, $sql)) {
                    $inventory_id = mysqli_insert_id($con);
                    
                    // Record initial stock movement
                    if($current_stock > 0) {
                        $total_cost = $current_stock * $unit_cost;
                        mysqli_query($con, "INSERT INTO inventory_movements (inventory_id, movement_type, quantity, unit_cost, total_cost, notes, recorded_by) 
                                           VALUES ($inventory_id, 'stock_in', $current_stock, $unit_cost, $total_cost, 'Initial stock', " . $_SESSION['user_id'] . ")");
                    }
                    
                    $success = "Inventory item added successfully!";
                } else {
                    $error = "Failed to add inventory item.";
                }
                break;
                
            case 'update_stock':
                $inventory_id = (int)$_POST['inventory_id'];
                $movement_type = mysqli_real_escape_string($con, $_POST['movement_type']);
                $quantity = (float)$_POST['quantity'];
                $unit_cost = (float)$_POST['unit_cost'];
                $total_cost = $quantity * $unit_cost;
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                // Get current stock
                $current_query = "SELECT current_stock FROM kitchen_inventory WHERE id = $inventory_id";
                $current_result = mysqli_query($con, $current_query);
                $current_row = mysqli_fetch_assoc($current_result);
                $current_stock = $current_row['current_stock'];
                
                // Calculate new stock
                $new_stock = $current_stock;
                if($movement_type == 'stock_in') {
                    $new_stock += $quantity;
                } elseif($movement_type == 'stock_out') {
                    $new_stock -= $quantity;
                } elseif($movement_type == 'adjustment') {
                    $new_stock = $quantity; // Direct adjustment
                } elseif($movement_type == 'waste') {
                    $new_stock -= $quantity;
                }
                
                if($new_stock >= 0) {
                    // Record movement
                    mysqli_query($con, "INSERT INTO inventory_movements (inventory_id, movement_type, quantity, unit_cost, total_cost, notes, recorded_by) 
                                       VALUES ($inventory_id, '$movement_type', $quantity, $unit_cost, $total_cost, '$notes', " . $_SESSION['user_id'] . ")");
                    
                    // Update current stock
                    mysqli_query($con, "UPDATE kitchen_inventory SET current_stock = $new_stock, updated_at = NOW() WHERE id = $inventory_id");
                    
                    $success = "Stock updated successfully!";
                } else {
                    $error = "Insufficient stock for this operation.";
                }
                break;
                
            case 'update_inventory_item':
                $inventory_id = (int)$_POST['inventory_id'];
                $category_id = (int)$_POST['category_id'];
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $unit = mysqli_real_escape_string($con, $_POST['unit']);
                $minimum_stock = (float)$_POST['minimum_stock'];
                $maximum_stock = (float)$_POST['maximum_stock'];
                $unit_cost = (float)$_POST['unit_cost'];
                $supplier = mysqli_real_escape_string($con, $_POST['supplier']);
                $expiry_date = $_POST['expiry_date'] ? mysqli_real_escape_string($con, $_POST['expiry_date']) : NULL;
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE kitchen_inventory SET 
                        category_id = $category_id,
                        name = '$name',
                        description = '$description',
                        unit = '$unit',
                        minimum_stock = $minimum_stock,
                        maximum_stock = $maximum_stock,
                        unit_cost = $unit_cost,
                        supplier = '$supplier',
                        expiry_date = " . ($expiry_date ? "'$expiry_date'" : "NULL") . ",
                        is_active = $is_active,
                        updated_at = NOW()
                        WHERE id = $inventory_id";
                
                if(mysqli_query($con, $sql)) {
                    $success = "Inventory item updated successfully!";
                } else {
                    $error = "Failed to update inventory item.";
                }
                break;
        }
    }
}

// Get inventory categories
$categories_query = "SELECT * FROM inventory_categories WHERE is_active = 1 ORDER BY name";
$categories_result = mysqli_query($con, $categories_query);

// Get inventory items with category names
$inventory_query = "SELECT ki.*, ic.name as category_name 
                    FROM kitchen_inventory ki 
                    LEFT JOIN inventory_categories ic ON ki.category_id = ic.id 
                    ORDER BY ic.name, ki.name";
$inventory_result = mysqli_query($con, $inventory_query);

// Calculate inventory summary
$summary_query = "SELECT 
                    COUNT(*) as total_items,
                    SUM(current_stock * unit_cost) as total_value,
                    COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items,
                    COUNT(CASE WHEN expiry_date IS NOT NULL AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as expiring_items
                  FROM kitchen_inventory 
                  WHERE is_active = 1";
$summary_result = mysqli_query($con, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);
?>


    
            <div class="container-fluid">
                
                            <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h3>KES <?php echo number_format($summary['total_value']); ?></h3>
                                    <p>Total Value</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h3><?php echo $summary['low_stock_items']; ?></h3>
                                    <p>Low Stock Items</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                    <h3><?php echo $summary['expiring_items']; ?></h3>
                                    <p>Expiring Soon</p>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <button class="btn btn-success" data-toggle="modal" data-target="#addInventoryModal">
                                    <i class="fa fa-plus"></i> Add Inventory Item
                                </button>
                                <a href="inventory_movements.php" class="btn btn-info">
                                    <i class="fa fa-exchange"></i> View Movements
                                </a>
                            </div>
                        </div>

                        <!-- Inventory Items -->
                        <?php if(mysqli_num_rows($inventory_result) > 0): ?>
                            <?php while($item = mysqli_fetch_assoc($inventory_result)): 
                                $stock_percentage = $item['maximum_stock'] > 0 ? ($item['current_stock'] / $item['maximum_stock']) * 100 : 0;
                                $stock_class = $item['current_stock'] <= $item['minimum_stock'] ? 'low' : ($stock_percentage > 80 ? 'high' : 'normal');
                                $card_class = '';
                                if($item['current_stock'] <= $item['minimum_stock']) $card_class = 'low-stock';
                                elseif($item['expiry_date'] && strtotime($item['expiry_date']) <= strtotime('+7 days')) $card_class = 'expiring';
                                elseif($stock_percentage > 80) $card_class = 'overstock';
                            ?>
                                <div class="inventory-card <?php echo $card_class; ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h5><?php echo $item['name']; ?></h5>
                                            <p class="text-muted"><?php echo $item['description']; ?></p>
                                            <p><strong>Category:</strong> <?php echo $item['category_name']; ?></p>
                                            <p><strong>Unit:</strong> <?php echo $item['unit']; ?></p>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <h4 class="text-primary"><?php echo number_format($item['current_stock'], 3); ?></h4>
                                            <p><strong>Current Stock</strong></p>
                                            <div class="stock-bar">
                                                <div class="stock-fill <?php echo $stock_class; ?>" style="width: <?php echo min($stock_percentage, 100); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($stock_percentage, 1); ?>% of max</small>
                                        </div>
                                        
                                        <div class="col-md-2">
                                            <p><strong>Min:</strong> <?php echo number_format($item['minimum_stock'], 3); ?></p>
                                            <p><strong>Max:</strong> <?php echo number_format($item['maximum_stock'], 3); ?></p>
                                            <p><strong>Unit Cost:</strong> KES <?php echo number_format($item['unit_cost'], 2); ?></p>
                                            <p><strong>Total Value:</strong> KES <?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></p>
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
                                                <button class="btn btn-sm btn-primary" onclick="updateStock(<?php echo $item['id']; ?>, '<?php echo $item['name']; ?>', <?php echo $item['current_stock']; ?>, <?php echo $item['unit_cost']; ?>)">
                                                    <i class="fa fa-edit"></i> Update Stock
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="editInventoryItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                                    <i class="fa fa-edit"></i> Edit Item
                                                </button>
                                                <a href="inventory_movements.php?item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-secondary">
                                                    <i class="fa fa-history"></i> View History
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h4>No inventory items found</h4>
                                <p>Start by adding your first inventory item.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Inventory Modal -->
    <div class="modal fade" id="addInventoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Inventory Item</h5>
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
                                    <label>Unit</label>
                                    <input type="text" name="unit" class="form-control" placeholder="e.g., kg, liters, pieces" required>
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
                                    <label>Supplier</label>
                                    <input type="text" name="supplier" class="form-control" placeholder="Supplier name or contact info">
                                </div>
                                
                                <div class="form-group">
                                    <label>Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control">
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

    <!-- Update Stock Modal -->
    <div class="modal fade" id="updateStockModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Stock</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_stock">
                        <input type="hidden" name="inventory_id" id="update_inventory_id">
                        
                        <div class="form-group">
                            <label>Item</label>
                            <input type="text" id="update_item_name" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Current Stock</label>
                            <input type="text" id="update_current_stock" class="form-control" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label>Movement Type</label>
                            <select name="movement_type" class="form-control" required>
                                <option value="stock_in">Stock In</option>
                                <option value="stock_out">Stock Out</option>
                                <option value="adjustment">Adjustment</option>
                                <option value="waste">Waste</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Quantity</label>
                            <input type="number" name="quantity" class="form-control" step="0.001" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Unit Cost (KES)</label>
                            <input type="number" name="unit_cost" id="update_unit_cost" class="form-control" step="0.01" min="0" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Reason for movement..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Inventory Modal -->
    <div class="modal fade" id="editInventoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Inventory Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_inventory_item">
                        <input type="hidden" name="inventory_id" id="edit_inventory_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Category</label>
                                    <select name="category_id" id="edit_category_id" class="form-control" required>
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
                                    <input type="text" name="name" id="edit_name" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Description</label>
                                    <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Unit</label>
                                    <input type="text" name="unit" id="edit_unit" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Minimum Stock Level</label>
                                    <input type="number" name="minimum_stock" id="edit_minimum_stock" class="form-control" step="0.001" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Maximum Stock Level</label>
                                    <input type="number" name="maximum_stock" id="edit_maximum_stock" class="form-control" step="0.001" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Unit Cost (KES)</label>
                                    <input type="number" name="unit_cost" id="edit_unit_cost" class="form-control" step="0.01" min="0" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Supplier</label>
                                    <input type="text" name="supplier" id="edit_supplier" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label>Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" id="edit_expiry_date" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_active" id="edit_is_active"> Active</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>