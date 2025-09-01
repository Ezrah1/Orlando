<?php
$page_title = 'Bar Orders Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Bar Orders Management</h1>
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
            case 'create_order':
                $table_number = mysqli_real_escape_string($con, $_POST['table_number']);
                $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
                $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
                $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
                $order_type = mysqli_real_escape_string($con, $_POST['order_type']);
                $special_instructions = mysqli_real_escape_string($con, $_POST['special_instructions']);
                $shift_id = (int)$_POST['shift_id'];
                
                // Generate order number
                $order_number = 'BO-' . date('Ymd') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO bar_orders (order_number, table_number, room_number, guest_name, guest_phone, order_type, special_instructions, ordered_by, shift_id) 
                        VALUES ('$order_number', '$table_number', '$room_number', '$guest_name', '$guest_phone', '$order_type', '$special_instructions', " . $_SESSION['user_id'] . ", $shift_id)";
                
                if(mysqli_query($con, $sql)) {
                    $order_id = mysqli_insert_id($con);
                    
                    // Add order items
                    if(isset($_POST['inventory_items']) && is_array($_POST['inventory_items'])) {
                        foreach($_POST['inventory_items'] as $item) {
                            $inventory_id = (int)$item['inventory_id'];
                            $quantity = (float)$item['quantity'];
                            $unit_price = (float)$item['unit_price'];
                            $total_price = $quantity * $unit_price;
                            $special_instructions = mysqli_real_escape_string($con, $item['special_instructions']);
                            
                            // Get cost price for this inventory item
                            $cost_query = "SELECT unit_cost FROM bar_inventory WHERE id = $inventory_id";
                            $cost_result = mysqli_query($cost_query, "");
                            $cost_row = mysqli_fetch_assoc($cost_result);
                            $cost_price = $cost_row['unit_cost'] ?? 0;
                            $total_cost = $quantity * $cost_price;
                            
                            mysqli_query($con, "INSERT INTO bar_order_items (order_id, inventory_id, quantity, unit_price, total_price, cost_price, total_cost, special_instructions) 
                                               VALUES ($order_id, $inventory_id, $quantity, $unit_price, $total_price, $cost_price, $total_cost, '$special_instructions')");
                        }
                    }
                    
                    // Update order totals
                    $total_query = "SELECT SUM(total_price) as total FROM bar_order_items WHERE order_id = $order_id";
                    $total_result = mysqli_query($total_query, "");
                    $total_row = mysqli_fetch_assoc($total_result);
                    $total_amount = $total_row['total'] ?? 0;
                    
                    mysqli_query($con, "UPDATE bar_orders SET total_amount = $total_amount, final_amount = $total_amount WHERE id = $order_id");
                    
                    $success = "Bar order created successfully! Order #: $order_number";
                } else {
                    $error = "Failed to create order.";
                }
                break;
                
            case 'update_order_status':
                $order_id = (int)$_POST['order_id'];
                $status = mysqli_real_escape_string($con, $_POST['status']);
                
                $update_fields = "status = '$status'";
                if($status == 'confirmed') {
                    $update_fields .= ", confirmed_time = NOW()";
                } elseif($status == 'ready') {
                    $update_fields .= ", ready_time = NOW(), prepared_by = " . $_SESSION['user_id'];
                } elseif($status == 'served') {
                    $update_fields .= ", served_time = NOW(), served_by = " . $_SESSION['user_id'];
                }
                
                $sql = "UPDATE bar_orders SET $update_fields WHERE id = $order_id";
                if(mysqli_query($con, $sql)) {
                    $success = "Order status updated successfully!";
                } else {
                    $error = "Failed to update order status.";
                }
                break;
        }
    }
}

// Get orders with status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = "WHERE 1=1";
if($status_filter) {
    $where_clause .= " AND bo.status = '$status_filter'";
}

$orders_query = "SELECT bo.*, 
                        COUNT(boi.id) as total_items,
                        SUM(boi.total_price) as calculated_total
                 FROM bar_orders bo
                 LEFT JOIN bar_order_items boi ON bo.id = boi.order_id
                 $where_clause
                 GROUP BY bo.id
                 ORDER BY bo.ordered_time DESC";
$orders_result = mysqli_query($orders_query, "");

// Get bar inventory items for new orders
$inventory_query = "SELECT bi.*, bc.name as category_name 
                   FROM bar_inventory bi 
                   LEFT JOIN bar_categories bc ON bi.category_id = bc.id 
                   WHERE bi.is_active = 1
                   ORDER BY bc.display_order, bi.name";
$inventory_result = mysqli_query($inventory_query, "");

// Get bar shifts
$shifts_query = "SELECT * FROM bar_shifts WHERE is_active = 1 ORDER BY start_time";
$shifts_result = mysqli_query($shifts_query, "");
?>


    
            <div class="container-fluid">
                
                        </div>

                        <!-- Orders List -->
                        <?php if(mysqli_num_rows($orders_result) > 0): ?>
                            <?php while($order = mysqli_fetch_assoc($orders_result)): ?>
                                <div class="order-card <?php echo $order['status']; ?>">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <h5><strong>Order #<?php echo $order['order_number']; ?></strong></h5>
                                            <p><strong>Type:</strong> <span class="order-type-badge"><?php echo ucfirst(str_replace('_', ' ', $order['order_type'])); ?></span></p>
                                            <p><strong>Time:</strong> <?php echo date('M j, Y g:i A', strtotime($order['ordered_time'])); ?></p>
                                            <?php if($order['table_number']): ?>
                                                <p><strong>Table:</strong> <?php echo $order['table_number']; ?></p>
                                            <?php endif; ?>
                                            <?php if($order['room_number']): ?>
                                                <p><strong>Room:</strong> <?php echo $order['room_number']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <p><strong>Guest:</strong> <?php echo $order['guest_name']; ?></p>
                                            <?php if($order['guest_phone']): ?>
                                                <p><strong>Phone:</strong> <?php echo $order['guest_phone']; ?></p>
                                            <?php endif; ?>
                                            <p><strong>Items:</strong> <?php echo $order['total_items']; ?></p>
                                            <p><strong>Total:</strong> KES <?php echo number_format($order['final_amount']); ?></p>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <p><strong>Status:</strong> 
                                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </p>
                                            <p><strong>Payment:</strong> 
                                                <span class="badge badge-<?php echo $order['payment_status'] == 'paid' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($order['payment_status']); ?>
                                                </span>
                                            </p>
                                            <?php if($order['special_instructions']): ?>
                                                <p><strong>Notes:</strong> <?php echo $order['special_instructions']; ?></p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-3">
                                            <div class="btn-group-vertical">
                                                <?php if($order['status'] == 'pending'): ?>
                                                    <button class="btn btn-sm btn-primary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'confirmed')">
                                                        Confirm Order
                                                    </button>
                                                <?php elseif($order['status'] == 'confirmed'): ?>
                                                    <button class="btn btn-sm btn-info" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'preparing')">
                                                        Start Preparing
                                                    </button>
                                                <?php elseif($order['status'] == 'preparing'): ?>
                                                    <button class="btn btn-sm btn-success" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'ready')">
                                                        Mark Ready
                                                    </button>
                                                <?php elseif($order['status'] == 'ready'): ?>
                                                    <button class="btn btn-sm btn-secondary" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'served')">
                                                        Mark Served
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <button class="btn btn-sm btn-info" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                                                    View Details
                                                </button>
                                                
                                                <?php if($order['status'] != 'cancelled' && $order['status'] != 'served'): ?>
                                                    <button class="btn btn-sm btn-danger" onclick="updateOrderStatus(<?php echo $order['id']; ?>, 'cancelled')">
                                                        Cancel Order
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <h4>No bar orders found</h4>
                                <p>There are no orders matching the current filter criteria.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Order Modal -->
    <div class="modal fade" id="createOrderModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Bar Order</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST" id="createOrderForm">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_order">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Order Type</label>
                                    <select name="order_type" class="form-control" required>
                                        <option value="dine_in">Dine In</option>
                                        <option value="takeaway">Takeaway</option>
                                        <option value="room_service">Room Service</option>
                                        <option value="delivery">Delivery</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Shift</label>
                                    <select name="shift_id" class="form-control" required>
                                        <option value="">Select Shift</option>
                                        <?php 
                                        mysqli_data_seek($shifts_result, 0);
                                        while($shift = mysqli_fetch_assoc($shifts_result)): 
                                        ?>
                                            <option value="<?php echo $shift['id']; ?>">
                                                <?php echo $shift['shift_name']; ?> (<?php echo $shift['start_time']; ?> - <?php echo $shift['end_time']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Table Number</label>
                                    <input type="text" name="table_number" class="form-control" placeholder="e.g., T1, T2">
                                </div>
                                
                                <div class="form-group">
                                    <label>Room Number</label>
                                    <select name="room_number" class="form-control">
                                        <option value="">Select Room (Optional)</option>
                                        <?php
                                        // Fetch rooms from database
                                        $rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY room_name ASC";
                                        $rooms_result = mysqli_query($rooms_query, "");
                                        while($room = mysqli_fetch_assoc($rooms_result)) {
                                            echo '<option value="' . htmlspecialchars($room['room_name']) . '">' . htmlspecialchars($room['room_name']) . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Guest Name</label>
                                    <input type="text" name="guest_name" class="form-control" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Guest Phone</label>
                                    <input type="text" name="guest_phone" class="form-control" placeholder="e.g., 0712345678">
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Instructions</label>
                                    <textarea name="special_instructions" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <hr>
                        
                        <h5>Order Items</h5>
                        <div id="orderItems">
                            <div class="order-item-row">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select name="inventory_items[0][inventory_id]" class="form-control inventory-item-select" required>
                                            <option value="">Select Bar Item</option>
                                            <?php 
                                            mysqli_data_seek($inventory_result, 0);
                                            while($item = mysqli_fetch_assoc($inventory_result)): 
                                            ?>
                                                <option value="<?php echo $item['id']; ?>" data-price="<?php echo $item['selling_price']; ?>">
                                                    <?php echo $item['name']; ?> - KES <?php echo number_format($item['selling_price']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="inventory_items[0][quantity]" class="form-control quantity-input" value="1" min="0.001" step="0.001" required>
                                    </div>
                                    <div class="col-md-2">
                                        <input type="number" name="inventory_items[0][unit_price]" class="form-control price-input" step="0.01" readonly>
                                    </div>
                                    <div class="col-md-3">
                                        <input type="text" name="inventory_items[0][special_instructions]" class="form-control" placeholder="Special instructions...">
                                    </div>
                                    <div class="col-md-1">
                                        <button type="button" class="btn btn-danger btn-sm remove-item">×</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <button type="button" class="btn btn-success btn-sm" id="addItemBtn">
                            <i class="fa fa-plus"></i> Add Item
                        </button>
                        
                        <hr>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Summary</h5>
                                <p><strong>Total Items:</strong> <span id="totalItems">1</span></p>
                                <p><strong>Total Amount:</strong> KES <span id="totalAmount">0.00</span></p>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>