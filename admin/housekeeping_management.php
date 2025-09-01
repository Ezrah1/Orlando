<?php
$page_title = 'Housekeeping Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Housekeeping Management</h1>
</div>

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
            case 'update_room_status':
                $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
                $status = mysqli_real_escape_string($con, $_POST['status']);
                
                $sql = "UPDATE roombook SET housekeeping_status = '$status' WHERE TRoom = '$room_number'";
                mysqli_query($sql, "");
                break;
                
            case 'create_task':
                $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
                $task_type = mysqli_real_escape_string($con, $_POST['task_type']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $assigned_to = mysqli_real_escape_string($con, $_POST['assigned_to']);
                
                $sql = "INSERT INTO housekeeping_tasks (room_name, task_type, status, assigned_to, scheduled_date, notes, created_by) 
                        VALUES ('$room_number', '$task_type', 'pending', " . ($assigned_to ? $assigned_to : "NULL") . ", CURDATE(), '$description', {$_SESSION['user_id']})";
                mysqli_query($sql, "");
                break;
                
            case 'update_task_status':
                $task_id = (int)$_POST['task_id'];
                $status = mysqli_real_escape_string($con, $_POST['status']);
                $user_id = $_SESSION['user_id'];
                
                $sql = "UPDATE housekeeping_tasks SET status = '$status'";
                if ($status == 'completed') {
                    $sql .= ", completed_date = NOW()";
                }
                $sql .= " WHERE id = $task_id";
                mysqli_query($sql, "");
                break;
                
            case 'create_laundry_order':
                $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
                $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
                $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
                $service_id = (int)$_POST['service_id'];
                $quantity = (int)$_POST['quantity'];
                $special_instructions = mysqli_real_escape_string($con, $_POST['special_instructions']);
                
                // Get service details
                $service_sql = "SELECT price FROM laundry_services WHERE id = $service_id";
                $service_result = mysqli_query($service_sql, "");
                $service = mysqli_fetch_assoc($service_result);
                $unit_price = $service['price'];
                $total_amount = $unit_price * $quantity;
                
                $order_number = 'LO-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO laundry_orders (order_number, room_number, guest_name, guest_phone, service_id, quantity, unit_price, total_amount, special_instructions, created_by) 
                        VALUES ('$order_number', '$room_number', '$guest_name', '$guest_phone', $service_id, $quantity, $unit_price, $total_amount, '$special_instructions', {$_SESSION['user_id']})";
                mysqli_query($sql, "");
                break;
        }
    }
}

// Get room status summary
$status_summary_sql = "SELECT 
                        housekeeping_status,
                        COUNT(*) as count
                       FROM roombook 
                       WHERE housekeeping_status IS NOT NULL
                       GROUP BY housekeeping_status";
$status_summary_result = mysqli_query($status_summary_sql, "");
$status_summary = [];
while ($row = mysqli_fetch_assoc($status_summary_result)) {
    $status_summary[$row['housekeeping_status']] = $row['count'];
}

// Get pending tasks
$pending_tasks_sql = "SELECT ht.*, 
                             CASE 
                                 WHEN ht.assigned_to IS NOT NULL THEN 'Assigned'
                                 ELSE 'Unassigned'
                             END as assignment_status
                      FROM housekeeping_tasks ht
                      WHERE ht.status IN ('pending', 'in_progress')
                      ORDER BY ht.created_at ASC
                      LIMIT 10";
$pending_tasks_result = mysqli_query($pending_tasks_sql, "");

// Get recent laundry orders
$laundry_orders_sql = "SELECT lo.*, ls.service_name
                       FROM laundry_orders lo
                       JOIN laundry_services ls ON lo.service_id = ls.id
                       ORDER BY lo.created_at DESC
                       LIMIT 10";
$laundry_orders_result = mysqli_query($laundry_orders_sql, "");

// Get all rooms with their status
$rooms_sql = "SELECT TRoom, housekeeping_status, stat
              FROM roombook 
              WHERE housekeeping_status IS NOT NULL
              ORDER BY TRoom";
$rooms_result = mysqli_query($rooms_sql, "");

// Get housekeeping statuses
$statuses_sql = "SELECT * FROM housekeeping_status WHERE is_active = 1 ORDER BY display_order";
$statuses_result = mysqli_query($statuses_sql, "");

// Get laundry services
$services_sql = "SELECT * FROM laundry_services WHERE is_active = 1 ORDER BY service_name";
$services_result = mysqli_query($services_sql, "");
?>


    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="fas fa-broom"></i> Housekeeping Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#createLaundryModal">
                            <i class="fas fa-tshirt"></i> Laundry Order
                        </button>
                    </div>
                </div>

                <!-- Status Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card status-card status-ready">
                            <div class="card-body text-center">
                                <h5 class="card-title text-success">Ready</h5>
                                <h3 class="text-success"><?php echo $status_summary['ready'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card status-card status-occupied">
                            <div class="card-body text-center">
                                <h5 class="card-title text-primary">Occupied</h5>
                                <h3 class="text-primary"><?php echo $status_summary['occupied'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card status-card status-needs_cleaning">
                            <div class="card-body text-center">
                                <h5 class="card-title text-warning">Needs Cleaning</h5>
                                <h3 class="text-warning"><?php echo $status_summary['needs_cleaning'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card status-card status-being_cleaned">
                            <div class="card-body text-center">
                                <h5 class="card-title text-info">Being Cleaned</h5>
                                <h3 class="text-info"><?php echo $status_summary['being_cleaned'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card status-card status-out_of_order">
                            <div class="card-body text-center">
                                <h5 class="card-title text-danger">Out of Order</h5>
                                <h3 class="text-danger"><?php echo $status_summary['out_of_order'] ?? 0; ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Room Status Management -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-bed"></i> Room Status Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Room</th>
                                                <th>Current Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($room = mysqli_fetch_assoc($rooms_result)): ?>
                                            <tr>
                                                <td><strong><?php echo $room['TRoom']; ?></strong></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $room['housekeeping_status'] == 'ready' ? 'success' : 
                                                            ($room['housekeeping_status'] == 'occupied' ? 'primary' : 
                                                            ($room['housekeeping_status'] == 'needs_cleaning' ? 'warning' : 
                                                            ($room['housekeeping_status'] == 'being_cleaned' ? 'info' : 'danger'))); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $room['housekeeping_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick="updateRoomStatus('<?php echo $room['TRoom']; ?>', '<?php echo $room['housekeeping_status']; ?>')">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Tasks -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tasks"></i> Pending Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Room</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($task = mysqli_fetch_assoc($pending_tasks_result)): ?>
                                            <tr>
                                                <td>
                                                    <strong>Task #<?php echo $task['id']; ?></strong><br>
                                                    <small><?php echo ucfirst(str_replace('_', ' ', $task['task_type'])); ?></small>
                                                </td>
                                                <td><?php echo $task['room_name']; ?></td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        Normal
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $task['status'] == 'pending' ? 'secondary' : 
                                                            ($task['status'] == 'assigned' ? 'primary' : 'info'); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-success" 
                                                            onclick="updateTaskStatus(<?php echo $task['id']; ?>, '<?php echo $task['status']; ?>')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
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

                <!-- Recent Laundry Orders -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5><i class="fas fa-tshirt"></i> Recent Laundry Orders</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Order #</th>
                                                <th>Room</th>
                                                <th>Guest</th>
                                                <th>Service</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Created</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($order = mysqli_fetch_assoc($laundry_orders_result)): ?>
                                            <tr>
                                                <td><strong><?php echo $order['order_number']; ?></strong></td>
                                                <td><?php echo $order['room_number']; ?></td>
                                                <td><?php echo $order['guest_name']; ?></td>
                                                <td><?php echo $order['service_name']; ?></td>
                                                <td>KES <?php echo number_format($order['total_amount'], 2); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $order['status'] == 'completed' ? 'success' : 
                                                            ($order['status'] == 'in_progress' ? 'info' : 'secondary'); 
                                                    ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Create Task Modal -->
    <div class="modal fade" id="createTaskModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_task">
                        
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <select class="form-select" name="room_number" required>
                                <option value="">Select Room</option>
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
                        
                        <div class="mb-3">
                            <label class="form-label">Task Type</label>
                            <select class="form-select" name="task_type" required>
                                <option value="daily_cleaning">Daily Cleaning</option>
                                <option value="deep_cleaning">Deep Cleaning</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inspection">Inspection</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3" required></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Assigned To (Optional)</label>
                            <input type="number" class="form-control" name="assigned_to" placeholder="Staff ID">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Laundry Order Modal -->
    <div class="modal fade" id="createLaundryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Laundry Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_laundry_order">
                        
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <select class="form-select" name="room_number" required>
                                <option value="">Select Room</option>
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
                        
                        <div class="mb-3">
                            <label class="form-label">Guest Name</label>
                            <input type="text" class="form-control" name="guest_name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Guest Phone</label>
                            <input type="text" class="form-control" name="guest_phone" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Service</label>
                            <select class="form-select" name="service_id" required>
                                <?php mysqli_data_seek($services_result, 0); ?>
                                <?php while ($service = mysqli_fetch_assoc($services_result)): ?>
                                <option value="<?php echo $service['id']; ?>">
                                    <?php echo $service['service_name']; ?> - KES <?php echo number_format($service['price'], 2); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Quantity</label>
                            <input type="number" class="form-control" name="quantity" value="1" min="1" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Special Instructions</label>
                            <textarea class="form-control" name="special_instructions" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Order</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Room Status Modal -->
    <div class="modal fade" id="updateRoomStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Room Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_room_status">
                        <input type="hidden" name="room_number" id="updateRoomNumber">
                        
                        <div class="mb-3">
                            <label class="form-label">Room Number</label>
                            <input type="text" class="form-control" id="updateRoomDisplay" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" name="status" required>
                                <?php mysqli_data_seek($statuses_result, 0); ?>
                                <?php while ($status = mysqli_fetch_assoc($statuses_result)): ?>
                                <option value="<?php echo $status['name']; ?>">
                                    <?php echo $status['name']; ?> - <?php echo $status['description']; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Task Status Modal -->
    <div class="modal fade" id="updateTaskStatusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Task Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_task_status">
                        <input type="hidden" name="task_id" id="updateTaskId">
                        
                        <div class="mb-3">
                            <label class="form-label">New Status</label>
                            <select class="form-select" name="status" required>
                                <option value="pending">Pending</option>
                                <option value="assigned">Assigned</option>
                                <option value="in_progress">In Progress</option>
                                <option value="completed">Completed</option>
                                <option value="verified">Verified</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php include '../includes/admin/footer.php'; ?>