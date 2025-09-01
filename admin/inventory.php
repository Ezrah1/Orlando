<?php
$page_title = 'Inventory Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<?php
// Display session alerts
display_session_alerts();
?>

<?php
include '../db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_item':
                $inventory_type = mysqli_real_escape_string($con, $_POST['inventory_type']);
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $category_id = (int)$_POST['category_id'];
                $current_stock = (float)$_POST['current_stock'];
                $unit = mysqli_real_escape_string($con, $_POST['unit']);
                $cost_per_unit = (float)$_POST['cost_per_unit'];
                $reorder_level = (float)$_POST['reorder_level'];
                $supplier = mysqli_real_escape_string($con, $_POST['supplier']);
                
                if ($inventory_type == 'kitchen') {
                    $sql = "INSERT INTO kitchen_inventory (category_id, name, unit, current_stock, minimum_stock, unit_cost, supplier) 
                            VALUES ($category_id, '$name', '$unit', $current_stock, $reorder_level, $cost_per_unit, '$supplier')";
                } else {
                    $sql = "INSERT INTO bar_inventory (category_id, name, unit, current_stock, minimum_stock, unit_cost, supplier) 
                            VALUES ($category_id, '$name', '$unit', $current_stock, $reorder_level, $cost_per_unit, '$supplier')";
                }
                mysqli_query($con, $sql);
                $_SESSION['success_message'] = "Inventory item added successfully!";
                break;
                
            case 'update_stock':
                $item_id = (int)$_POST['item_id'];
                $inventory_type = mysqli_real_escape_string($con, $_POST['inventory_type']);
                $stock_change = (float)$_POST['stock_change'];
                $change_type = $_POST['change_type']; // 'add' or 'subtract'
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                // Update stock in appropriate table
                $operator = ($change_type == 'add') ? '+' : '-';
                if ($inventory_type == 'kitchen') {
                    $sql = "UPDATE kitchen_inventory SET current_stock = current_stock $operator $stock_change WHERE id = $item_id";
                    $table_ref = 'kitchen_inventory';
                } else {
                    $sql = "UPDATE bar_inventory SET current_stock = current_stock $operator $stock_change WHERE id = $item_id";
                    $table_ref = 'bar_inventory';
                }
                mysqli_query($con, $sql);
                
                // Log the stock movement
                if ($inventory_type == 'kitchen') {
                    $sql = "INSERT INTO inventory_movements (inventory_id, movement_type, quantity, notes, moved_by, movement_date) 
                            VALUES ($item_id, '$change_type', $stock_change, '$notes', {$_SESSION['user_id']}, NOW())";
                } else {
                    $sql = "INSERT INTO bar_inventory_movements (inventory_id, movement_type, quantity, notes, moved_by, movement_date) 
                            VALUES ($item_id, '$change_type', $stock_change, '$notes', {$_SESSION['user_id']}, NOW())";
                }
                mysqli_query($con, $sql);
                
                $_SESSION['success_message'] = "Stock updated successfully!";
                break;
                
            case 'add_category':
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $inventory_type = mysqli_real_escape_string($con, $_POST['inventory_type']);
                
                if ($inventory_type == 'kitchen') {
                    $sql = "INSERT INTO inventory_categories (name, description) VALUES ('$name', '$description')";
                } else {
                    $sql = "INSERT INTO bar_categories (name, description) VALUES ('$name', '$description')";
                }
                mysqli_query($con, $sql);
                $_SESSION['success_message'] = "Category added successfully!";
                break;
        }
        header("Location: inventory.php");
        exit();
    }
}

// Get inventory summary statistics
$kitchen_stats_sql = "SELECT 
                      COUNT(*) as total_items,
                      SUM(current_stock * unit_cost) as total_value,
                      COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items
                      FROM kitchen_inventory WHERE is_active = 1";
$kitchen_stats_result = mysqli_query($con, $kitchen_stats_sql);
$kitchen_stats = mysqli_fetch_assoc($kitchen_stats_result);

$bar_stats_sql = "SELECT 
                  COUNT(*) as total_items,
                  SUM(current_stock * unit_cost) as total_value,
                  COUNT(CASE WHEN current_stock <= minimum_stock THEN 1 END) as low_stock_items
                  FROM bar_inventory WHERE is_active = 1";
$bar_stats_result = mysqli_query($con, $bar_stats_sql);
$bar_stats = mysqli_fetch_assoc($bar_stats_result);

// Get low stock items (combining kitchen and bar)
$low_stock_sql = "
    (SELECT ki.id, ki.category_id, ki.name, ki.description, ki.unit, ki.current_stock, 
            ki.minimum_stock, ki.maximum_stock, ki.unit_cost, ki.supplier, ki.expiry_date, 
            ki.is_active, ki.created_at, ki.updated_at, ic.name as category_name, 'kitchen' as inventory_type 
     FROM kitchen_inventory ki
     LEFT JOIN inventory_categories ic ON ki.category_id = ic.id
     WHERE ki.current_stock <= ki.minimum_stock AND ki.is_active = 1)
    UNION ALL
    (SELECT bi.id, bi.category_id, bi.name, bi.description, bi.unit, bi.current_stock, 
            bi.minimum_stock, bi.maximum_stock, bi.unit_cost, bi.supplier, bi.expiry_date, 
            bi.is_active, bi.created_at, bi.updated_at, bc.name as category_name, 'bar' as inventory_type 
     FROM bar_inventory bi
     LEFT JOIN bar_categories bc ON bi.category_id = bc.id
     WHERE bi.current_stock <= bi.minimum_stock AND bi.is_active = 1)
    ORDER BY inventory_type, (minimum_stock - current_stock) DESC
    LIMIT 20";
$low_stock_result = mysqli_query($con, $low_stock_sql);

// Get all inventory items (combining kitchen and bar)
$all_items_sql = "
    (SELECT ki.id, ki.category_id, ki.name, ki.description, ki.unit, ki.current_stock, 
            ki.minimum_stock, ki.maximum_stock, ki.unit_cost, ki.supplier, ki.expiry_date, 
            ki.is_active, ki.created_at, ki.updated_at, ic.name as category_name, 'kitchen' as inventory_type 
     FROM kitchen_inventory ki
     LEFT JOIN inventory_categories ic ON ki.category_id = ic.id
     WHERE ki.is_active = 1)
    UNION ALL
    (SELECT bi.id, bi.category_id, bi.name, bi.description, bi.unit, bi.current_stock, 
            bi.minimum_stock, bi.maximum_stock, bi.unit_cost, bi.supplier, bi.expiry_date, 
            bi.is_active, bi.created_at, bi.updated_at, bc.name as category_name, 'bar' as inventory_type 
     FROM bar_inventory bi
     LEFT JOIN bar_categories bc ON bi.category_id = bc.id
     WHERE bi.is_active = 1)
    ORDER BY inventory_type, name";
$all_items_result = mysqli_query($con, $all_items_sql);

// Get categories (combining kitchen and bar categories)
$categories_sql = "
    (SELECT ic.id, ic.name, ic.description, ic.is_active, ic.created_at, 'kitchen' as inventory_type FROM inventory_categories ic WHERE ic.is_active = 1)
    UNION ALL
    (SELECT bc.id, bc.name, bc.description, bc.is_active, bc.created_at, 'bar' as inventory_type FROM bar_categories bc WHERE bc.is_active = 1)
    ORDER BY inventory_type, name";
$categories_result = mysqli_query($con, $categories_sql);

// Get recent movements (combining kitchen and bar movements)
$movements_sql = "
    (SELECT im.id, im.inventory_id, im.movement_type, im.quantity, im.notes, 
            im.moved_by, im.movement_date, ki.name as item_name, u.username as user_name, 'kitchen' as inventory_type
     FROM inventory_movements im
     LEFT JOIN kitchen_inventory ki ON im.inventory_id = ki.id
     LEFT JOIN users u ON im.moved_by = u.id
     ORDER BY im.movement_date DESC LIMIT 5)
    UNION ALL
    (SELECT bim.id, bim.inventory_id, bim.movement_type, bim.quantity, bim.notes, 
            bim.moved_by, bim.movement_date, bi.name as item_name, u.username as user_name, 'bar' as inventory_type
     FROM bar_inventory_movements bim
     LEFT JOIN bar_inventory bi ON bim.inventory_id = bi.id
     LEFT JOIN users u ON bim.moved_by = u.id
     ORDER BY bim.movement_date DESC LIMIT 5)
    ORDER BY movement_date DESC
    LIMIT 10";
$movements_result = mysqli_query($con, $movements_sql);
?>

<div class="container-fluid">
    <div class="row g-0">
    <?php include '../includes/admin/sidebar.php'; ?>
    
        <div class="admin-main-content">
            <div class="content-wrapper p-4">
                
        <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-boxes text-primary"></i> Inventory Management</h2>
                        <p class="text-muted mb-0">Manage kitchen and bar inventory, stock levels, and categories</p>
            </div>
                    <div>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addItemModal">
                            <i class="fas fa-plus"></i> Add Item
                        </button>
                        <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="fas fa-tags"></i> Add Category
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#stockUpdateModal">
                            <i class="fas fa-edit"></i> Update Stock
                        </button>
        </div>
            </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-utensils fa-2x text-primary"></i>
                </div>
                                    <div>
                                        <div class="h4 mb-0 text-primary"><?php echo $kitchen_stats['total_items']; ?></div>
                                        <small class="text-muted">Kitchen Items</small>
                                        <div class="small text-success">
                                            Value: KES <?php echo number_format($kitchen_stats['total_value'], 0); ?>
            </div>
                </div>
            </div>
                </div>
            </div>
                </div>
                    <div class="col-md-3">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-cocktail fa-2x text-info"></i>
            </div>
                                    <div>
                                        <div class="h4 mb-0 text-info"><?php echo $bar_stats['total_items']; ?></div>
                                        <small class="text-muted">Bar Items</small>
                                        <div class="small text-success">
                                            Value: KES <?php echo number_format($bar_stats['total_value'], 0); ?>
        </div>
                    </div>
                    </div>
                    </div>
                    </div>
                </div>
                    <div class="col-md-3">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
        </div>
                                    <div>
                                        <div class="h4 mb-0 text-warning"><?php echo $kitchen_stats['low_stock_items'] + $bar_stats['low_stock_items']; ?></div>
                                        <small class="text-muted">Low Stock Items</small>
                                        <div class="small text-muted">
                                            Kitchen: <?php echo $kitchen_stats['low_stock_items']; ?> | Bar: <?php echo $bar_stats['low_stock_items']; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-dollar-sign fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-success">KES <?php echo number_format($kitchen_stats['total_value'] + $bar_stats['total_value'], 0); ?></div>
                                        <small class="text-muted">Total Inventory Value</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
        </div>

                <!-- Main Content Tabs -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="inventoryTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="all-items-tab" data-bs-toggle="tab" data-bs-target="#all-items" type="button" role="tab">
                                    <i class="fas fa-list"></i> All Items
                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="low-stock-tab" data-bs-toggle="tab" data-bs-target="#low-stock" type="button" role="tab">
                                    <i class="fas fa-exclamation-triangle"></i> Low Stock
                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="movements-tab" data-bs-toggle="tab" data-bs-target="#movements" type="button" role="tab">
                                    <i class="fas fa-exchange-alt"></i> Recent Movements
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">
                                    <i class="fas fa-chart-bar"></i> Reports
                                </button>
                            </li>
                        </ul>
            </div>
                    <div class="card-body">
                        <div class="tab-content" id="inventoryTabsContent">
                            
                            <!-- All Items Tab -->
                            <div class="tab-pane fade show active" id="all-items" role="tabpanel">
                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <input type="text" class="form-control" id="searchItems" placeholder="Search items...">
                                        </div>
                                        <div class="col-md-3">
                                            <select class="form-select" id="filterType">
                                                <option value="">All Types</option>
                                                <option value="kitchen">Kitchen</option>
                                                <option value="bar">Bar</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover" id="itemsTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Name</th>
                                                <th>Type</th>
                                <th>Category</th>
                                <th>Current Stock</th>
                                <th>Unit</th>
                                                <th>Cost/Unit</th>
                                                <th>Total Value</th>
                                                <th>Reorder Level</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                            <?php while ($item = mysqli_fetch_assoc($all_items_result)): ?>
                                            <tr data-type="<?php echo $item['inventory_type']; ?>">
                                        <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <?php if ($item['supplier']): ?>
                                                        <br><small class="text-muted">Supplier: <?php echo htmlspecialchars($item['supplier']); ?></small>
                                                <?php endif; ?>
                                        </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['inventory_type'] == 'kitchen' ? 'primary' : 'info'; ?>">
                                                        <?php echo ucfirst($item['inventory_type']); ?>
                                            </span>
                                        </td>
                                                <td><?php echo htmlspecialchars($item['category_name'] ?? 'No Category'); ?></td>
                                                <td>
                                                    <strong><?php echo number_format($item['current_stock'], 2); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['unit']); ?></td>
                                                <td>KES <?php echo number_format($item['unit_cost'], 2); ?></td>
                                                <td>
                                                    <strong>KES <?php echo number_format($item['current_stock'] * $item['unit_cost'], 2); ?></strong>
                                                </td>
                                                <td><?php echo number_format($item['minimum_stock'], 2); ?></td>
                                                <td>
                                                    <?php if ($item['current_stock'] <= $item['minimum_stock']): ?>
                                                        <span class="badge bg-danger">Low Stock</span>
                                                    <?php elseif ($item['current_stock'] <= ($item['minimum_stock'] * 1.5)): ?>
                                                        <span class="badge bg-warning">Medium Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">Good Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-primary btn-sm" 
                                                                onclick="updateStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', '<?php echo $item['inventory_type']; ?>')"
                                                                title="Update Stock">
                                                            <i class="fas fa-edit"></i>
                                                </button>
                                                        <button class="btn btn-info btn-sm" 
                                                                onclick="viewItemHistory(<?php echo $item['id']; ?>)"
                                                                title="View History">
                                                            <i class="fas fa-history"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

                            <!-- Low Stock Tab -->
                            <div class="tab-pane fade" id="low-stock" role="tabpanel">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Attention:</strong> The following items are at or below their reorder levels.
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Item Name</th>
                                                <th>Type</th>
                                <th>Current Stock</th>
                                                <th>Reorder Level</th>
                                                <th>Shortage</th>
                                <th>Supplier</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                                            <?php mysqli_data_seek($low_stock_result, 0); ?>
                                            <?php while ($item = mysqli_fetch_assoc($low_stock_result)): ?>
                                            <tr class="<?php echo $item['current_stock'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                                <td>
                                                <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($item['category_name'] ?? 'No Category'); ?></small>
                                        </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['inventory_type'] == 'kitchen' ? 'primary' : 'info'; ?>">
                                                        <?php echo ucfirst($item['inventory_type']); ?>
                                            </span>
                                        </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $item['current_stock'] == 0 ? 'danger' : 'warning'; ?>">
                                                        <?php echo number_format($item['current_stock'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo number_format($item['minimum_stock'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?></td>
                                                <td>
                                                    <span class="text-danger">
                                                        <?php echo number_format($item['minimum_stock'] - $item['current_stock'], 2); ?> <?php echo htmlspecialchars($item['unit']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($item['supplier'] ?? 'N/A'); ?></td>
                                                                                                <td>
                                                    <button class="btn btn-primary btn-sm" 
                                                            onclick="updateStock(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['name']); ?>', '<?php echo $item['inventory_type']; ?>')">
                                                        <i class="fas fa-plus"></i> Restock
                                                    </button>
                                                </td>
                                    </tr>
                                <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Recent Movements Tab -->
                            <div class="tab-pane fade" id="movements" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date/Time</th>
                                                <th>Item</th>
                                                <th>Movement Type</th>
                                                <th>Quantity</th>
                                                <th>User</th>
                                                <th>Notes</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($movement = mysqli_fetch_assoc($movements_result)): ?>
                                            <tr>
                                                <td>
                                                    <?php echo date('M j, Y', strtotime($movement['movement_date'])); ?>
                                                    <br><small class="text-muted"><?php echo date('H:i', strtotime($movement['movement_date'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($movement['item_name'] ?? 'Unknown Item'); ?></strong>
                                                </td>
                                                <td>
                                                    <?php if ($movement['movement_type'] == 'add'): ?>
                                                        <span class="badge bg-success"><i class="fas fa-plus"></i> Stock In</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-danger"><i class="fas fa-minus"></i> Stock Out</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong><?php echo number_format($movement['quantity'], 2); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($movement['user_name'] ?? 'Unknown User'); ?></td>
                                                <td><?php echo htmlspecialchars($movement['notes'] ?? 'No notes'); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                        </div>
                            </div>

                            <!-- Reports Tab -->
                            <div class="tab-pane fade" id="reports" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Inventory Distribution</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row text-center">
                                                    <div class="col-6">
                                                        <h4 class="text-primary"><?php echo $kitchen_stats['total_items']; ?></h4>
                                                        <small class="text-muted">Kitchen Items</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <h4 class="text-info"><?php echo $bar_stats['total_items']; ?></h4>
                                                        <small class="text-muted">Bar Items</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Value Distribution</h6>
                                            </div>
                                            <div class="card-body">
                                                <div class="row text-center">
                                                    <div class="col-6">
                                                        <h4 class="text-success">KES <?php echo number_format($kitchen_stats['total_value'], 0); ?></h4>
                                                        <small class="text-muted">Kitchen Value</small>
                                                    </div>
                                                    <div class="col-6">
                                                        <h4 class="text-success">KES <?php echo number_format($bar_stats['total_value'], 0); ?></h4>
                                                        <small class="text-muted">Bar Value</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-header">
                                                <h6 class="mb-0">Top Categories by Value</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $category_stats_sql = "
                                                    (SELECT ic.name, 'kitchen' as inventory_type, 
                                                           COUNT(ki.id) as item_count,
                                                           SUM(ki.current_stock * ki.unit_cost) as total_value
                                                           FROM inventory_categories ic
                                                           LEFT JOIN kitchen_inventory ki ON ic.id = ki.category_id AND ki.is_active = 1
                                                           WHERE ic.is_active = 1
                                                           GROUP BY ic.id, ic.name)
                                                    UNION ALL
                                                    (SELECT bc.name, 'bar' as inventory_type, 
                                                           COUNT(bi.id) as item_count,
                                                           SUM(bi.current_stock * bi.unit_cost) as total_value
                                                           FROM bar_categories bc
                                                           LEFT JOIN bar_inventory bi ON bc.id = bi.category_id AND bi.is_active = 1
                                                           WHERE bc.is_active = 1
                                                           GROUP BY bc.id, bc.name)
                                                    ORDER BY total_value DESC
                                                    LIMIT 10";
                                                $category_stats_result = mysqli_query($con, $category_stats_sql);
                                                ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Category</th>
                                                                <th>Type</th>
                                                                <th>Items</th>
                                                                <th>Total Value</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php while ($category = mysqli_fetch_assoc($category_stats_result)): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                                <td>
                                                                    <span class="badge bg-<?php echo $category['inventory_type'] == 'kitchen' ? 'primary' : 'info'; ?>">
                                                                        <?php echo ucfirst($category['inventory_type']); ?>
                                                                    </span>
                                    </td>
                                                                <td><?php echo $category['item_count']; ?></td>
                                                                <td><strong>KES <?php echo number_format($category['total_value'] ?? 0, 0); ?></strong></td>
                                </tr>
                                                            <?php endwhile; ?>
                        </tbody>
                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                </div>
            </div>
        </div>
                    </div>
                </div>

            </div> <!-- End content-wrapper -->
        </div> <!-- End admin-main-content -->
    </div>
</div>

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title">Add New Inventory Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="add_item">
                
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Inventory Type</label>
                                <select class="form-select" name="inventory_type" required>
                        <option value="kitchen">Kitchen</option>
                        <option value="bar">Bar</option>
                    </select>
                </div>
                </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id">
                        <option value="">Select Category</option>
                                    <?php mysqli_data_seek($categories_result, 0); ?>
                                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['id']; ?>" data-type="<?php echo $category['inventory_type']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?> (<?php echo ucfirst($category['inventory_type']); ?>)
                                    </option>
                                    <?php endwhile; ?>
                    </select>
                            </div>
                        </div>
                </div>
                
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="name" required>
                </div>
                
                <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" class="form-control" name="current_stock" value="0" step="0.01" required>
                        </div>
                    </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit</label>
                                <input type="text" class="form-control" name="unit" placeholder="kg, liters, pieces" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Cost per Unit (KES)</label>
                                <input type="number" class="form-control" name="cost_per_unit" value="0" step="0.01" required>
                        </div>
                    </div>
                </div>
                
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Reorder Level</label>
                                <input type="number" class="form-control" name="reorder_level" value="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" name="supplier">
                            </div>
                        </div>
                </div>
            </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Item</button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                    <input type="hidden" name="action" value="add_category">
                    
                    <div class="mb-3">
                        <label class="form-label">Inventory Type</label>
                        <select class="form-select" name="inventory_type" required>
                            <option value="kitchen">Kitchen</option>
                            <option value="bar">Bar</option>
                        </select>
                </div>
                
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="name" required>
                </div>
                
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Add Category</button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- Stock Update Modal -->
<div class="modal fade" id="stockUpdateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
        <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <form method="POST">
                            <div class="modal-body">
                    <input type="hidden" name="action" value="update_stock">
                    <input type="hidden" name="item_id" id="updateItemId">
                    <input type="hidden" name="inventory_type" id="updateInventoryType">
                    
                    <div class="mb-3">
                        <label class="form-label">Item</label>
                        <input type="text" class="form-control" id="updateItemName" readonly>
                    </div>
                
                    <div class="mb-3">
                        <label class="form-label">Change Type</label>
                        <select class="form-select" name="change_type" required>
                            <option value="add">Add Stock (Stock In)</option>
                            <option value="subtract">Remove Stock (Stock Out)</option>
                        </select>
                </div>
                
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" name="stock_change" step="0.01" required>
                </div>
                
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="3" placeholder="Reason for stock change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Update Stock</button>
            </div>
        </form>
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchItems').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

// Filter by type
document.getElementById('filterType').addEventListener('change', function() {
    const filter = this.value;
    const rows = document.querySelectorAll('#itemsTable tbody tr');
    
    rows.forEach(row => {
        if (!filter || row.dataset.type === filter) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

function updateStock(itemId, itemName, inventoryType) {
    document.getElementById('updateItemId').value = itemId;
    document.getElementById('updateItemName').value = itemName;
    document.getElementById('updateInventoryType').value = inventoryType;
    
    const modal = new bootstrap.Modal(document.getElementById('stockUpdateModal'));
    modal.show();
}

function viewItemHistory(itemId) {
    // Placeholder for item history view
    alert('Item history functionality will be implemented. Item ID: ' + itemId);
}
</script>

<?php include '../includes/admin/footer.php'; ?>
