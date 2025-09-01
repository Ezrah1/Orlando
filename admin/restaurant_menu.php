<?php
$page_title = 'Restaurant Menu Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Restaurant Menu Management</h1>
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
            case 'add_category':
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $display_order = (int)$_POST['display_order'];
                
                $sql = "INSERT INTO menu_categories (name, description, display_order) VALUES ('$name', '$description', $display_order)";
                if(mysqli_query($con, $sql)) {
                    $success = "Menu category added successfully!";
                } else {
                    $error = "Failed to add menu category.";
                }
                break;
                
            case 'add_menu_item':
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
                
                $sql = "INSERT INTO menu_items (category_id, name, description, price, cost_price, preparation_time, is_vegetarian, is_gluten_free, is_spicy, display_order) 
                        VALUES ($category_id, '$name', '$description', $price, $cost_price, $preparation_time, $is_vegetarian, $is_gluten_free, $is_spicy, $display_order)";
                if(mysqli_query($con, $sql)) {
                    $success = "Menu item added successfully!";
                } else {
                    $error = "Failed to add menu item.";
                }
                break;
                
            case 'update_menu_item':
                $item_id = (int)$_POST['item_id'];
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
                
                $sql = "UPDATE menu_items SET 
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
                        WHERE id = $item_id";
                if(mysqli_query($con, $sql)) {
                    $success = "Menu item updated successfully!";
                } else {
                    $error = "Failed to update menu item.";
                }
                break;
        }
    }
}

// Get menu categories
$categories_query = "SELECT * FROM menu_categories WHERE is_active = 1 ORDER BY display_order, name";
$categories_result = mysqli_query($con, $categories_query);

// Get menu items with category names
$menu_query = "SELECT mi.*, mc.name as category_name 
               FROM menu_items mi 
               LEFT JOIN menu_categories mc ON mi.category_id = mc.id 
               ORDER BY mc.display_order, mi.display_order, mi.name";
$menu_result = mysqli_query($con, $menu_query);
?>


    
            <div class="container-fluid">
                

                        <!-- Menu Items by Category -->
                        <?php 
                        $current_category = '';
                        while($item = mysqli_fetch_assoc($menu_result)): 
                            if($current_category != $item['category_name']):
                                if($current_category != '') echo '</div></div>'; // Close previous category
                                $current_category = $item['category_name'];
                        ?>
                            <div class="panel panel-default">
                                <div class="panel-heading">
                                    <h4><?php echo $item['category_name']; ?></h4>
                                </div>
                                <div class="panel-body">
                        <?php endif; ?>
                        
                        <div class="menu-item-card <?php echo !$item['is_available'] ? 'unavailable' : ''; ?>">
                            <div class="row">
                                <div class="col-md-4">
                                    <h5><?php echo $item['name']; ?></h5>
                                    <p class="text-muted"><?php echo $item['description']; ?></p>
                                    <div class="badges">
                                        <?php if($item['is_vegetarian']): ?>
                                            <span class="badge badge-vegetarian">Vegetarian</span>
                                        <?php endif; ?>
                                        <?php if($item['is_gluten_free']): ?>
                                            <span class="badge badge-gluten-free">Gluten Free</span>
                                        <?php endif; ?>
                                        <?php if($item['is_spicy']): ?>
                                            <span class="badge badge-spicy">Spicy</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="price-tag">KES <?php echo number_format($item['price']); ?></div>
                                    <div class="cost-tag">Cost: KES <?php echo number_format($item['cost_price']); ?></div>
                                </div>
                                <div class="col-md-2">
                                    <p><strong>Prep Time:</strong> <?php echo $item['preparation_time']; ?> min</p>
                                    <p><strong>Status:</strong> 
                                        <?php echo $item['is_available'] ? '<span class="text-success">Available</span>' : '<span class="text-danger">Unavailable</span>'; ?>
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <p><strong>Display Order:</strong> <?php echo $item['display_order']; ?></p>
                                    <p><strong>Profit Margin:</strong> 
                                        <?php 
                                        $margin = $item['price'] > 0 ? (($item['price'] - $item['cost_price']) / $item['price']) * 100 : 0;
                                        echo number_format($margin, 1) . '%';
                                        ?>
                                    </p>
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-sm btn-primary" onclick="editMenuItem(<?php echo htmlspecialchars(json_encode($item)); ?>)">
                                        <i class="fa fa-edit"></i> Edit
                                    </button>
                                    <a href="recipe_ingredients.php?item_id=<?php echo $item['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fa fa-list"></i> Recipe
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <?php endwhile; ?>
                        <?php if($current_category != ''): ?>
                            </div></div> <!-- Close last category -->
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Category Modal -->
    <div class="modal fade" id="addCategoryModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Category</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_category">
                        
                        <div class="form-group">
                            <label>Category Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Description</label>
                            <textarea name="description" class="form-control" rows="3"></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>Display Order</label>
                            <input type="number" name="display_order" class="form-control" value="0" min="0">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Add Category</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Add Menu Item Modal -->
    <div class="modal fade" id="addMenuItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Menu Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_menu_item">
                        
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
                                    <label>Price (KES)</label>
                                    <input type="number" name="price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cost Price (KES)</label>
                                    <input type="number" name="cost_price" class="form-control" step="0.01" value="0.00">
                                </div>
                                
                                <div class="form-group">
                                    <label>Preparation Time (minutes)</label>
                                    <input type="number" name="preparation_time" class="form-control" value="15" min="1">
                                </div>
                                
                                <div class="form-group">
                                    <label>Display Order</label>
                                    <input type="number" name="display_order" class="form-control" value="0" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label>Options</label>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_vegetarian"> Vegetarian</label>
                                    </div>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_gluten_free"> Gluten Free</label>
                                    </div>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_spicy"> Spicy</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Menu Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Menu Item Modal -->
    <div class="modal fade" id="editMenuItemModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Menu Item</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_menu_item">
                        <input type="hidden" name="item_id" id="edit_item_id">
                        
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
                                    <label>Price (KES)</label>
                                    <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cost Price (KES)</label>
                                    <input type="number" name="cost_price" id="edit_cost_price" class="form-control" step="0.01">
                                </div>
                                
                                <div class="form-group">
                                    <label>Preparation Time (minutes)</label>
                                    <input type="number" name="preparation_time" id="edit_preparation_time" class="form-control" min="1">
                                </div>
                                
                                <div class="form-group">
                                    <label>Display Order</label>
                                    <input type="number" name="display_order" id="edit_display_order" class="form-control" min="0">
                                </div>
                                
                                <div class="form-group">
                                    <label>Options</label>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_vegetarian" id="edit_is_vegetarian"> Vegetarian</label>
                                    </div>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_gluten_free" id="edit_is_gluten_free"> Gluten Free</label>
                                    </div>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_spicy" id="edit_is_spicy"> Spicy</label>
                                    </div>
                                    <div class="checkbox">
                                        <label><input type="checkbox" name="is_available" id="edit_is_available"> Available</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Menu Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>