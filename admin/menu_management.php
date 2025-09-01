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

$page_title = 'Menu Management';

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_item':
            $category_id = (int)$_POST['category_id'];
            $name = mysqli_real_escape_string($con, $_POST['name']);
            $description = mysqli_real_escape_string($con, $_POST['description']);
            $price = (float)$_POST['price'];
            $cost_price = (float)$_POST['cost_price'];
            $preparation_time = (int)$_POST['preparation_time'];
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $is_spicy = isset($_POST['is_spicy']) ? 1 : 0;
            $display_order = (int)$_POST['display_order'];
            
            $query = "INSERT INTO menu_items (category_id, name, description, price, cost_price, preparation_time, is_vegetarian, is_gluten_free, is_spicy, display_order) 
                      VALUES ($category_id, '$name', '$description', $price, $cost_price, $preparation_time, $is_vegetarian, $is_gluten_free, $is_spicy, $display_order)";
            
            if (mysqli_query($con, $query)) {
                $success_msg = "Menu item added successfully!";
            } else {
                $error_msg = "Error adding menu item: " . mysqli_error($con);
            }
            break;
            
        case 'update_item':
            $id = (int)$_POST['id'];
            $category_id = (int)$_POST['category_id'];
            $name = mysqli_real_escape_string($con, $_POST['name']);
            $description = mysqli_real_escape_string($con, $_POST['description']);
            $price = (float)$_POST['price'];
            $cost_price = (float)$_POST['cost_price'];
            $preparation_time = (int)$_POST['preparation_time'];
            $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
            $is_gluten_free = isset($_POST['is_gluten_free']) ? 1 : 0;
            $is_spicy = isset($_POST['is_spicy']) ? 1 : 0;
            $is_available = isset($_POST['is_available']) ? 1 : 0;
            $display_order = (int)$_POST['display_order'];
            
            $query = "UPDATE menu_items SET 
                      category_id = $category_id,
                      name = '$name',
                      description = '$description',
                      price = $price,
                      cost_price = $cost_price,
                      preparation_time = $preparation_time,
                      is_vegetarian = $is_vegetarian,
                      is_gluten_free = $is_gluten_free,
                      is_spicy = $is_spicy,
                      is_available = $is_available,
                      display_order = $display_order,
                      updated_at = NOW()
                      WHERE id = $id";
            
            if (mysqli_query($con, $query)) {
                $success_msg = "Menu item updated successfully!";
            } else {
                $error_msg = "Error updating menu item: " . mysqli_error($con);
            }
            break;
            
        case 'delete_item':
            $id = (int)$_POST['id'];
            $query = "DELETE FROM menu_items WHERE id = $id";
            
            if (mysqli_query($con, $query)) {
                $success_msg = "Menu item deleted successfully!";
            } else {
                $error_msg = "Error deleting menu item: " . mysqli_error($con);
            }
            break;
            
        case 'toggle_availability':
            $id = (int)$_POST['id'];
            $query = "UPDATE menu_items SET is_available = NOT is_available WHERE id = $id";
            
            if (mysqli_query($con, $query)) {
                $success_msg = "Item availability updated!";
            } else {
                $error_msg = "Error updating availability: " . mysqli_error($con);
            }
            break;
    }
}

// Get filters
$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$availability_filter = isset($_GET['availability']) ? $_GET['availability'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($con, $_GET['search']) : '';

// Build query
$where_conditions = [];

if ($category_filter > 0) {
    $where_conditions[] = "mi.category_id = $category_filter";
}

if ($availability_filter === 'available') {
    $where_conditions[] = "mi.is_available = 1";
} elseif ($availability_filter === 'unavailable') {
    $where_conditions[] = "mi.is_available = 0";
}

if (!empty($search)) {
    $where_conditions[] = "(mi.name LIKE '%$search%' OR mi.description LIKE '%$search%')";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get menu items
$items_query = "SELECT mi.*, mc.name as category_name 
                FROM menu_items mi 
                JOIN menu_categories mc ON mi.category_id = mc.id 
                $where_clause 
                ORDER BY mc.display_order, mi.display_order, mi.name";
$items_result = mysqli_query($con, $items_query);

// Get categories
$categories_query = "SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY display_order";
$categories_result = mysqli_query($con, $categories_query);

// Get statistics
$stats_query = "SELECT 
                COUNT(*) as total_items,
                COUNT(CASE WHEN is_available = 1 THEN 1 END) as available_items,
                COUNT(CASE WHEN is_available = 0 THEN 1 END) as unavailable_items,
                AVG(price) as avg_price,
                MIN(price) as min_price,
                MAX(price) as max_price
                FROM menu_items";
$stats_result = mysqli_query($con, $stats_query);
$stats = mysqli_fetch_assoc($stats_result);

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Menu Management</h1>
    <p class="page-subtitle">Manage restaurant menu items and categories</p>
</div>

<style>
        .menu-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3498db;
        }
        
        .stat-label {
            color: #666;
            margin-top: 5px;
        }
        
        .menu-controls {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .menu-table {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .menu-item-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .dietary-badges {
            display: flex;
            gap: 5px;
        }
        
        .badge {
            padding: 2px 6px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-vegetarian { background: #27ae60; color: white; }
        .badge-gluten-free { background: #f39c12; color: white; }
        .badge-spicy { background: #e74c3c; color: white; }
        .badge-available { background: #27ae60; color: white; }
        .badge-unavailable { background: #95a5a6; color: white; }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 4px 8px;
            font-size: 12px;
        }
        
        .modal-lg {
            max-width: 800px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        
        .form-row.single {
            grid-template-columns: 1fr;
        }
        
        .filter-controls {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .search-box {
            position: relative;
            max-width: 300px;
        }
        
        .search-box input {
            padding-right: 40px;
        }
        
        .search-box .fa-search {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
        }
    </style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">

                    <?php if (isset($success_msg)): ?>
                        <div class="alert alert-success alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php echo $success_msg; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error_msg)): ?>
                        <div class="alert alert-danger alert-dismissible">
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                            <?php echo $error_msg; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Statistics -->
                    <div class="menu-stats">
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['total_items']; ?></div>
                            <div class="stat-label">Total Items</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['available_items']; ?></div>
                            <div class="stat-label">Available</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number"><?php echo $stats['unavailable_items']; ?></div>
                            <div class="stat-label">Unavailable</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">KES <?php echo number_format($stats['avg_price'], 0); ?></div>
                            <div class="stat-label">Average Price</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-number">KES <?php echo number_format($stats['min_price'], 0); ?> - <?php echo number_format($stats['max_price'], 0); ?></div>
                            <div class="stat-label">Price Range</div>
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="menu-controls">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h3><i class="fa fa-filter"></i> Filter & Search</h3>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                                <i class="fa fa-plus"></i> Add New Item
                            </button>
                        </div>

                        <form method="GET" action="">
                            <div class="filter-controls">
                                <!-- Search -->
                                <div class="search-box">
                                    <input type="text" name="search" class="form-control" placeholder="Search items..." value="<?php echo htmlspecialchars($search); ?>">
                                    <i class="fa fa-search"></i>
                                </div>

                                <!-- Category Filter -->
                                <select name="category" class="form-control" style="width: auto;">
                                    <option value="0">All Categories</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['id']; ?>" <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>

                                <!-- Availability Filter -->
                                <select name="availability" class="form-control" style="width: auto;">
                                    <option value="all" <?php echo $availability_filter === 'all' ? 'selected' : ''; ?>>All Items</option>
                                    <option value="available" <?php echo $availability_filter === 'available' ? 'selected' : ''; ?>>Available Only</option>
                                    <option value="unavailable" <?php echo $availability_filter === 'unavailable' ? 'selected' : ''; ?>>Unavailable Only</option>
                                </select>

                                <button type="submit" class="btn btn-secondary">
                                    <i class="fa fa-search"></i> Filter
                                </button>

                                <?php if (!empty($search) || $category_filter > 0 || $availability_filter !== 'all'): ?>
                                    <a href="menu_management.php" class="btn btn-outline-secondary">
                                        <i class="fa fa-times"></i> Clear
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>

                    <!-- Menu Items Table -->
                    <div class="menu-table">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Image</th>
                                        <th>Name</th>
                                        <th>Category</th>
                                        <th>Price</th>
                                        <th>Cost</th>
                                        <th>Prep Time</th>
                                        <th>Dietary</th>
                                        <th>Status</th>
                                        <th>Order</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while($item = mysqli_fetch_assoc($items_result)): ?>
                                    <tr>
                                        <td><?php echo $item['id']; ?></td>
                                        <td>
                                            <img src="../images/menu/<?php echo $item['image_url'] ?: 'default-food.jpg'; ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="menu-item-image">
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars(substr($item['description'], 0, 50)); ?>...</small>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                        <td>KES <?php echo number_format($item['price'], 0); ?></td>
                                        <td>KES <?php echo number_format($item['cost_price'], 0); ?></td>
                                        <td><?php echo $item['preparation_time']; ?> mins</td>
                                        <td>
                                            <div class="dietary-badges">
                                                <?php if ($item['is_vegetarian']): ?>
                                                    <span class="badge badge-vegetarian">V</span>
                                                <?php endif; ?>
                                                <?php if ($item['is_gluten_free']): ?>
                                                    <span class="badge badge-gluten-free">GF</span>
                                                <?php endif; ?>
                                                <?php if ($item['is_spicy']): ?>
                                                    <span class="badge badge-spicy">🌶️</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $item['is_available'] ? 'badge-available' : 'badge-unavailable'; ?>">
                                                <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $item['display_order']; ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button type="button" class="btn btn-sm btn-primary edit-item" 
                                                        data-item='<?php echo json_encode($item); ?>'>
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Toggle availability for this item?');">
                                                    <input type="hidden" name="action" value="toggle_availability">
                                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm <?php echo $item['is_available'] ? 'btn-warning' : 'btn-success'; ?>">
                                                        <i class="fa fa-<?php echo $item['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                    </button>
                                                </form>
                                                
                                                <form method="POST" style="display: inline;" 
                                                      onsubmit="return confirm('Are you sure you want to delete this item?');">
                                                    <input type="hidden" name="action" value="delete_item">
                                                    <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
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

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fa fa-plus"></i> Add New Menu Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="add_item">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="name">Item Name *</label>
                                <input type="text" class="form-control" name="name" required>
                            </div>
                            <div class="form-group">
                                <label for="category_id">Category *</label>
                                <select class="form-control" name="category_id" required>
                                    <option value="">Select Category</option>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row single">
                            <div class="form-group">
                                <label for="description">Description</label>
                                <textarea class="form-control" name="description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Selling Price (KES) *</label>
                                <input type="number" class="form-control" name="price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="cost_price">Cost Price (KES)</label>
                                <input type="number" class="form-control" name="cost_price" step="0.01" value="0">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="preparation_time">Preparation Time (minutes)</label>
                                <input type="number" class="form-control" name="preparation_time" value="15">
                            </div>
                            <div class="form-group">
                                <label for="display_order">Display Order</label>
                                <input type="number" class="form-control" name="display_order" value="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Dietary Information</label>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_vegetarian" value="1">
                                    Vegetarian
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_gluten_free" value="1">
                                    Gluten Free
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_spicy" value="1">
                                    Spicy
                                </label>
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

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fa fa-edit"></i> Edit Menu Item</h4>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="editItemForm">
                    <input type="hidden" name="action" value="update_item">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-body">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_name">Item Name *</label>
                                <input type="text" class="form-control" name="name" id="edit_name" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_category_id">Category *</label>
                                <select class="form-control" name="category_id" id="edit_category_id" required>
                                    <?php 
                                    mysqli_data_seek($categories_result, 0);
                                    while($category = mysqli_fetch_assoc($categories_result)): 
                                    ?>
                                        <option value="<?php echo $category['id']; ?>">
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="form-row single">
                            <div class="form-group">
                                <label for="edit_description">Description</label>
                                <textarea class="form-control" name="description" id="edit_description" rows="3"></textarea>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_price">Selling Price (KES) *</label>
                                <input type="number" class="form-control" name="price" id="edit_price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label for="edit_cost_price">Cost Price (KES)</label>
                                <input type="number" class="form-control" name="cost_price" id="edit_cost_price" step="0.01">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="edit_preparation_time">Preparation Time (minutes)</label>
                                <input type="number" class="form-control" name="preparation_time" id="edit_preparation_time">
                            </div>
                            <div class="form-group">
                                <label for="edit_display_order">Display Order</label>
                                <input type="number" class="form-control" name="display_order" id="edit_display_order">
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label>Availability</label>
                                <div class="form-check">
                                    <label class="form-check-label">
                                        <input type="checkbox" class="form-check-input" name="is_available" id="edit_is_available" value="1">
                                        Available for ordering
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Dietary Information</label>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_vegetarian" id="edit_is_vegetarian" value="1">
                                    Vegetarian
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_gluten_free" id="edit_is_gluten_free" value="1">
                                    Gluten Free
                                </label>
                            </div>
                            <div class="form-check-inline">
                                <label class="form-check-label">
                                    <input type="checkbox" class="form-check-input" name="is_spicy" id="edit_is_spicy" value="1">
                                    Spicy
                                </label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        // Edit item functionality
        $(document).on('click', '.edit-item', function() {
            const item = $(this).data('item');
            
            $('#edit_id').val(item.id);
            $('#edit_name').val(item.name);
            $('#edit_category_id').val(item.category_id);
            $('#edit_description').val(item.description);
            $('#edit_price').val(item.price);
            $('#edit_cost_price').val(item.cost_price);
            $('#edit_preparation_time').val(item.preparation_time);
            $('#edit_display_order').val(item.display_order);
            
            $('#edit_is_available').prop('checked', item.is_available == 1);
            $('#edit_is_vegetarian').prop('checked', item.is_vegetarian == 1);
            $('#edit_is_gluten_free').prop('checked', item.is_gluten_free == 1);
            $('#edit_is_spicy').prop('checked', item.is_spicy == 1);
            
            $('#editItemModal').modal('show');
        });

        // Auto-dismiss alerts
        setTimeout(function() {
            $('.alert').fadeOut();
        }, 5000);
    </script>

<?php include '../includes/admin/footer.php'; ?>
