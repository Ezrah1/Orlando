<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user has housekeeping/operations permissions
$user_role = $_SESSION['user_role'] ?? '';
$user_role_id = $_SESSION['user_role_id'] ?? 0;

// Allow Admin, Director, Manager, Operations Manager, and Housekeeping staff
$allowed_roles = ['Admin', 'Director', 'Manager', 'Operations_Manager', 'Housekeeping', 'Staff', 'admin', 'director', 'manager', 'operations_manager', 'housekeeping', 'staff'];
if ($user_role_id != 1 && $user_role_id != 11 && !in_array($user_role, $allowed_roles) && !in_array(strtolower($user_role), array_map('strtolower', $allowed_roles))) {
    header("Location: access_denied.php");
    exit();
}

$page_title = 'Room Status & Housekeeping';

// Include database connection for form processing
include 'db.php';

// Handle form submissions
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    if(isset($_POST['action'])) {
        switch($_POST['action']) {
            case 'update_room_status':
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $current_status = mysqli_real_escape_string($con, $_POST['current_status']);
                $cleaning_status = mysqli_real_escape_string($con, $_POST['cleaning_status']);
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                $sql = "UPDATE room_status SET 
                        current_status = '$current_status',
                        cleaning_status = '$cleaning_status',
                        housekeeping_notes = '$notes',
                        updated_by = " . $_SESSION['user_id'] . ",
                        updated_at = NOW()
                        WHERE room_name = '$room_name'";
                
                if(mysqli_query($con, $sql)) {
                    $success = "Room status updated successfully!";
                } else {
                    $error = "Failed to update room status.";
                }
                break;
                
            case 'create_task':
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $task_type = mysqli_real_escape_string($con, $_POST['task_type']);
                $assigned_to = (int)$_POST['assigned_to'];
                $scheduled_date = mysqli_real_escape_string($con, $_POST['scheduled_date']);
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                $sql = "INSERT INTO housekeeping_tasks (room_name, task_type, assigned_to, scheduled_date, notes, created_by) 
                        VALUES ('$room_name', '$task_type', $assigned_to, '$scheduled_date', '$notes', " . $_SESSION['user_id'] . ")";
                
                if(mysqli_query($con, $sql)) {
                    $task_id = mysqli_insert_id($con);
                    
                    // Create checklist items for this task
                    $checklist_sql = "SELECT id FROM housekeeping_checklist WHERE task_type = '$task_type'";
                    $checklist_result = mysqli_query($con, $checklist_sql);
                    while($item = mysqli_fetch_assoc($checklist_result)) {
                        $item_id = $item['id'];
                        mysqli_query($con, "INSERT INTO housekeeping_task_items (task_id, checklist_item_id) VALUES ($task_id, $item_id)");
                    }
                    
                    $success = "Housekeeping task created successfully!";
                } else {
                    $error = "Failed to create housekeeping task.";
                }
                break;
                
            case 'check_in':
                $booking_id = (int)$_POST['booking_id'];
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                // Update booking
                mysqli_query($con, "UPDATE roombook SET stat = 'Conform' WHERE id = $booking_id");
                
                // Update room status
                mysqli_query($con, "UPDATE room_status SET current_status = 'occupied', updated_by = " . $_SESSION['user_id'] . " WHERE room_name = '$room_name'");
                
                // Log check-in
                mysqli_query($con, "INSERT INTO check_in_out_log (booking_id, room_name, action, action_time, performed_by, notes) 
                                   VALUES ($booking_id, '$room_name', 'check_in', NOW(), " . $_SESSION['user_id'] . ", '$notes')");
                
                $success = "Guest checked in successfully!";
                break;
                
            case 'check_out':
                $booking_id = (int)$_POST['booking_id'];
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $notes = mysqli_real_escape_string($con, $_POST['notes']);
                
                // Update booking
                mysqli_query($con, "UPDATE roombook SET stat = 'completed' WHERE id = $booking_id");
                
                // Update room status
                mysqli_query($con, "UPDATE room_status SET current_status = 'cleaning', cleaning_status = 'dirty', updated_by = " . $_SESSION['user_id'] . " WHERE room_name = '$room_name'");
                
                // Log check-out
                mysqli_query($con, "INSERT INTO check_in_out_log (booking_id, room_name, action, action_time, performed_by, notes) 
                                   VALUES ($booking_id, '$room_name', 'check_out', NOW(), " . $_SESSION['user_id'] . ", '$notes')");
                
                $success = "Guest checked out successfully! Room marked for cleaning.";
                break;
        }
    }
}

// Include the admin header with navigation (this provides $con database connection)
include '../includes/admin/header.php';

// Get room status data
$room_status_query = "SELECT rs.*, nr.base_price, 
                      (SELECT COUNT(*) FROM roombook rb WHERE rb.TRoom = rs.room_name AND rb.stat = 'Conform') as current_occupancy
                      FROM room_status rs 
                      LEFT JOIN named_rooms nr ON rs.room_name = nr.room_name 
                      ORDER BY rs.room_name";
$room_status_result = mysqli_query($con, $room_status_query);

// Get active housekeeping tasks
$tasks_query = "SELECT ht.*, u.username as assigned_to_name 
                FROM housekeeping_tasks ht 
                LEFT JOIN users u ON ht.assigned_to = u.id 
                WHERE ht.status IN ('pending', 'in_progress') 
                ORDER BY ht.scheduled_date ASC, ht.created_at DESC";
$tasks_result = mysqli_query($con, $tasks_query);

// Get check-in/out pending bookings
$bookings_query = "SELECT rb.*, rs.current_status, rs.cleaning_status 
                   FROM roombook rb 
                   LEFT JOIN room_status rs ON rb.TRoom = rs.room_name 
                   WHERE rb.stat IN ('Not Conform', 'Conform') 
                   ORDER BY rb.cin ASC";
$bookings_result = mysqli_query($con, $bookings_query);

// Get users for task assignment
$users_query = "SELECT id, username FROM users WHERE role_id IN (SELECT id FROM roles WHERE name LIKE '%housekeeping%' OR name LIKE '%staff%')";
$users_result = mysqli_query($con, $users_query);
?>

<style>
.room-card {
    border: 1px solid #ddd;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
    background: #fff;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.room-card.available { border-left: 5px solid #28a745; }
.room-card.occupied { border-left: 5px solid #dc3545; }
.room-card.cleaning { border-left: 5px solid #ffc107; }
.room-card.maintenance { border-left: 5px solid #17a2b8; }
.room-card.out_of_service { border-left: 5px solid #6c757d; }

.status-badge {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
}
.status-available { background: #d4edda; color: #155724; }
.status-occupied { background: #f8d7da; color: #721c24; }
.status-cleaning { background: #fff3cd; color: #856404; }
.status-maintenance { background: #d1ecf1; color: #0c5460; }
.status-out_of_service { background: #e2e3e5; color: #383d41; }

.task-card {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 10px;
    border: 1px solid #dee2e6;
}
.task-pending { border-left: 5px solid #ffc107; }
.task-in_progress { border-left: 5px solid #17a2b8; }
.task-completed { border-left: 5px solid #28a745; }

.housekeeping-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 2rem;
    border-radius: 10px;
    margin-bottom: 2rem;
    text-align: center;
}

.stats-card {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
}

.content-section {
    background: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #eee;
}

.section-header h3 {
    margin: 0;
    color: #333;
    font-size: 1.2rem;
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.room-name {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 600;
}

.room-details {
    margin: 1rem 0;
}

.detail-item, .detail-row {
    display: flex;
    align-items: center;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.detail-item i, .detail-row i {
    width: 16px;
    margin-right: 0.5rem;
    color: #666;
}

.room-actions {
    text-align: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.task-header, .booking-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.task-title, .guest-name {
    margin: 0;
    font-size: 1rem;
    font-weight: 600;
}

.task-status {
    font-size: 0.8rem;
}

.room-badge {
    background: #f8f9fa;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 600;
    color: #495057;
}

.booking-actions {
    text-align: center;
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #eee;
}

.empty-state {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 2rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>

<!-- Main Content -->
<div class="content-header">
    <div class="housekeeping-header">
        <h1><i class="fas fa-broom"></i> Housekeeping Management</h1>
        <p class="mb-0">Manage room status, cleaning tasks, and guest check-in/out operations</p>
    </div>
</div>

    <!-- Alert Messages -->
    <?php if(isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <?php if(isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Room Status Overview -->
    <div class="content-section">
        <div class="section-header">
            <h3><i class="fas fa-bed"></i> Room Status Overview</h3>
            <button class="btn btn-primary" data-toggle="modal" data-target="#createTaskModal">
                <i class="fas fa-plus"></i> New Task
            </button>
        </div>
        
        <div class="row">
            <?php while($room = mysqli_fetch_assoc($room_status_result)): ?>
                <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                    <div class="room-card <?php echo $room['current_status']; ?>">
                        <div class="room-header">
                            <h5 class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></h5>
                            <span class="status-badge status-<?php echo $room['current_status']; ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $room['current_status'])); ?>
                            </span>
                        </div>
                        
                        <div class="room-details">
                            <div class="detail-item">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>KES <?php echo number_format($room['base_price']); ?></span>
                            </div>
                            <div class="detail-item">
                                <i class="fas fa-broom"></i>
                                <span><?php echo ucfirst($room['cleaning_status']); ?></span>
                            </div>
                            <?php if($room['current_occupancy'] > 0): ?>
                                <div class="detail-item">
                                    <i class="fas fa-users"></i>
                                    <span><?php echo $room['current_occupancy']; ?> guest(s)</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="room-actions">
                            <button class="btn btn-sm btn-outline-primary" 
                                    onclick="editRoomStatus('<?php echo $room['room_name']; ?>', '<?php echo $room['current_status']; ?>', '<?php echo $room['cleaning_status']; ?>', '<?php echo htmlspecialchars($room['housekeeping_notes']); ?>')">
                                <i class="fas fa-edit"></i> Update
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Active Tasks and Check-in/out -->
    <div class="row">
        <!-- Active Housekeeping Tasks -->
        <div class="col-lg-6">
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-tasks"></i> Active Housekeeping Tasks</h3>
                </div>
                
                <div class="tasks-container">
                    <?php if(mysqli_num_rows($tasks_result) > 0): ?>
                        <?php while($task = mysqli_fetch_assoc($tasks_result)): ?>
                            <div class="task-card task-<?php echo $task['status']; ?>">
                                <div class="task-header">
                                    <h6 class="task-title">
                                        <i class="fas fa-broom"></i>
                                        <?php echo ucfirst(str_replace('_', ' ', $task['task_type'])); ?> - <?php echo htmlspecialchars($task['room_name']); ?>
                                    </h6>
                                    <span class="task-status badge badge-<?php echo $task['status'] == 'pending' ? 'warning' : ($task['status'] == 'in_progress' ? 'info' : 'success'); ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
                                    </span>
                                </div>
                                
                                <div class="task-details">
                                    <div class="detail-row">
                                        <i class="fas fa-user-tie"></i>
                                        <span><?php echo htmlspecialchars($task['assigned_to_name'] ?: 'Unassigned'); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span><?php echo date('M j, Y', strtotime($task['scheduled_date'])); ?></span>
                                    </div>
                                    <?php if($task['notes']): ?>
                                        <div class="detail-row">
                                            <i class="fas fa-sticky-note"></i>
                                            <span><?php echo htmlspecialchars($task['notes']); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-clipboard-check"></i>
                            <p>No active housekeeping tasks.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Check-in/out Management -->
        <div class="col-lg-6">
            <div class="content-section">
                <div class="section-header">
                    <h3><i class="fas fa-door-open"></i> Guest Check-in/out</h3>
                </div>
                
                <div class="bookings-container">
                    <?php if(mysqli_num_rows($bookings_result) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                            <div class="task-card booking-card">
                                <div class="booking-header">
                                    <h6 class="guest-name">
                                        <i class="fas fa-user"></i>
                                        <?php echo htmlspecialchars($booking['FName'] . ' ' . $booking['LName']); ?>
                                    </h6>
                                    <span class="room-badge"><?php echo htmlspecialchars($booking['TRoom']); ?></span>
                                </div>
                                
                                <div class="booking-details">
                                    <div class="detail-row">
                                        <i class="fas fa-sign-in-alt"></i>
                                        <span>Check-in: <?php echo date('M j, Y', strtotime($booking['cin'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-sign-out-alt"></i>
                                        <span>Check-out: <?php echo date('M j, Y', strtotime($booking['cout'])); ?></span>
                                    </div>
                                    <div class="detail-row">
                                        <i class="fas fa-info-circle"></i>
                                        <span>Status: <?php echo ucfirst($booking['stat']); ?></span>
                                    </div>
                                </div>
                                
                                <div class="booking-actions">
                                    <?php if($booking['stat'] == 'Not Conform'): ?>
                                        <button class="btn btn-sm btn-success" onclick="checkInGuest(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['TRoom']); ?>')">
                                            <i class="fas fa-door-open"></i> Check In
                                        </button>
                                    <?php elseif($booking['stat'] == 'Conform'): ?>
                                        <button class="btn btn-sm btn-warning" onclick="checkOutGuest(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['TRoom']); ?>')">
                                            <i class="fas fa-door-closed"></i> Check Out
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-bed"></i>
                            <p>No pending check-ins or check-outs.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Room Status Update Modal -->
    <div class="modal fade" id="roomStatusModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Update Room Status</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="update_room_status">
                        <input type="hidden" name="room_name" id="edit_room_name">
                        
                        <div class="form-group">
                            <label>Room Status</label>
                            <select name="current_status" id="edit_current_status" class="form-control">
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="cleaning">Cleaning</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="out_of_service">Out of Service</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Cleaning Status</label>
                            <select name="cleaning_status" id="edit_cleaning_status" class="form-control">
                                <option value="clean">Clean</option>
                                <option value="dirty">Dirty</option>
                                <option value="being_cleaned">Being Cleaned</option>
                                <option value="inspected">Inspected</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" id="edit_notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Status</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create Task Modal -->
    <div class="modal fade" id="createTaskModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create Housekeeping Task</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_task">
                        
                        <div class="form-group">
                            <label>Room</label>
                            <select name="room_name" class="form-control" required>
                                <option value="">Select a room</option>
                                <?php 
                                $rooms_query = "SELECT room_name FROM named_rooms ORDER BY room_name";
                                $rooms_result = mysqli_query($con, $rooms_query);
                                while($room = mysqli_fetch_assoc($rooms_result)):
                                ?>
                                    <option value="<?php echo $room['room_name']; ?>"><?php echo $room['room_name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Task Type</label>
                            <select name="task_type" class="form-control" required>
                                <option value="daily_cleaning">Daily Cleaning</option>
                                <option value="deep_cleaning">Deep Cleaning</option>
                                <option value="maintenance">Maintenance</option>
                                <option value="inspection">Inspection</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Assign To</label>
                            <select name="assigned_to" class="form-control">
                                <option value="">Unassigned</option>
                                <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                    <option value="<?php echo $user['id']; ?>"><?php echo $user['username']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Scheduled Date</label>
                            <input type="date" name="scheduled_date" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Create Task</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Check-in Modal -->
    <div class="modal fade" id="checkInModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check In Guest</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="check_in">
                        <input type="hidden" name="booking_id" id="checkin_booking_id">
                        <input type="hidden" name="room_name" id="checkin_room_name">
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Any special notes for this check-in..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Check In</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Check-out Modal -->
    <div class="modal fade" id="checkOutModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Check Out Guest</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="check_out">
                        <input type="hidden" name="booking_id" id="checkout_booking_id">
                        <input type="hidden" name="room_name" id="checkout_room_name">
                        
                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="notes" class="form-control" rows="3" placeholder="Any notes about the check-out or room condition..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-warning">Check Out</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.min.js"></script>
    <script>
        function editRoomStatus(roomName, currentStatus, cleaningStatus, notes) {
            document.getElementById('edit_room_name').value = roomName;
            document.getElementById('edit_current_status').value = currentStatus;
            document.getElementById('edit_cleaning_status').value = cleaningStatus;
            document.getElementById('edit_notes').value = notes || '';
            $('#roomStatusModal').modal('show');
        }
        
        function checkInGuest(bookingId, roomName) {
            document.getElementById('checkin_booking_id').value = bookingId;
            document.getElementById('checkin_room_name').value = roomName;
            $('#checkInModal').modal('show');
        }
        
        function checkOutGuest(bookingId, roomName) {
            document.getElementById('checkout_booking_id').value = bookingId;
            document.getElementById('checkout_room_name').value = roomName;
            $('#checkOutModal').modal('show');
        }
        
        // Set default date for new tasks
        document.querySelector('input[name="scheduled_date"]').value = new Date().toISOString().split('T')[0];
    </script>

<?php include '../includes/admin/footer.php'; ?>
