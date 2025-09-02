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

$page_title = 'Room Management';

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Handle form submissions for adding/editing rooms
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_room':
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $base_price = floatval($_POST['base_price']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                
                // Check if room already exists
                $check_sql = "SELECT * FROM named_rooms WHERE room_name = '$room_name'";
                $check_result = mysqli_query($con, $check_sql);
                
                if (mysqli_num_rows($check_result) > 0) {
                    $error_message = "Room '$room_name' already exists!";
                } else {
                    $sql = "INSERT INTO named_rooms (room_name, base_price, description, is_active, created_at) 
                            VALUES ('$room_name', $base_price, '$description', 1, NOW())";
                    if (mysqli_query($con, $sql)) {
                        $success_message = "Room '$room_name' added successfully!";
                    } else {
                        $error_message = "Error adding room: " . mysqli_error($con);
                    }
                }
                break;
                
            case 'update_room':
                $room_id = intval($_POST['room_id']);
                $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
                $base_price = floatval($_POST['base_price']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $sql = "UPDATE named_rooms SET 
                        room_name = '$room_name', 
                        base_price = $base_price, 
                        description = '$description', 
                        is_active = $is_active 
                        WHERE id = $room_id";
                if (mysqli_query($con, $sql)) {
                    $success_message = "Room updated successfully!";
                } else {
                    $error_message = "Error updating room: " . mysqli_error($con);
                }
                break;
                
            case 'delete_room':
                $room_id = intval($_POST['room_id']);
                
                // Soft delete - set is_active to 0
                $sql = "UPDATE named_rooms SET is_active = 0 WHERE id = $room_id";
                if (mysqli_query($con, $sql)) {
                    $success_message = "Room deactivated successfully!";
                } else {
                    $error_message = "Error deactivating room: " . mysqli_error($con);
                }
                break;
        }
    }
}

// Get room statistics
$total_rooms = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms WHERE is_active = 1"))['count'];
$occupied_rooms = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT troom) as count FROM roombook WHERE cout >= CURDATE() AND stat = 'Confirmed'"))['count'];
$available_rooms = $total_rooms - $occupied_rooms;
$avg_price = mysqli_fetch_assoc(mysqli_query($con, "SELECT AVG(base_price) as avg FROM named_rooms WHERE is_active = 1"))['avg'] ?? 0;

// Get all rooms for display
$rooms_query = "SELECT * FROM named_rooms ORDER BY is_active DESC, base_price DESC";
$rooms_result = mysqli_query($con, $rooms_query);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Room Management</h1>
            <p class="page-subtitle">Manage hotel rooms and pricing</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                <i class="fas fa-plus me-2"></i>Add New Room
            </button>
            <a href="housekeeping.php" class="btn btn-outline-secondary">
                <i class="fas fa-broom me-2"></i>Housekeeping
            </a>
        </div>
    </div>
</div>

<!-- Success/Error Messages -->
<?php if (isset($success_message)): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($error_message)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Room Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $total_rooms; ?></h3>
                        <p class="mb-0">Total Rooms</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-bed fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $available_rooms; ?></h3>
                        <p class="mb-0">Available</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $occupied_rooms; ?></h3>
                        <p class="mb-0">Occupied</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-user fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0">KES <?php echo number_format($avg_price, 0); ?></h3>
                        <p class="mb-0">Avg. Price</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-money-bill fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rooms Grid -->
<div class="row">
    <?php if (mysqli_num_rows($rooms_result) > 0): ?>
        <?php while($room = mysqli_fetch_assoc($rooms_result)): ?>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 <?php echo $room['is_active'] ? '' : 'border-danger'; ?>">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0"><?php echo htmlspecialchars($room['room_name']); ?></h5>
                    <div>
                        <?php if ($room['is_active']): ?>
                            <span class="badge bg-success">Active</span>
                        <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h4 class="text-primary">KES <?php echo number_format($room['base_price'], 2); ?></h4>
                        <small class="text-muted">per night</small>
                    </div>
                    
                    <p class="card-text"><?php echo htmlspecialchars($room['description']); ?></p>
                    
                    <div class="row text-center mb-3">
                        <div class="col-6">
                            <small class="text-muted">Room ID</small>
                            <div class="fw-bold">#<?php echo $room['id']; ?></div>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Created</small>
                            <div class="fw-bold"><?php echo date('M Y', strtotime($room['created_at'])); ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent">
                    <div class="btn-group w-100" role="group">
                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                onclick="editRoom(<?php echo htmlspecialchars(json_encode($room)); ?>)">
                            <i class="fas fa-edit"></i> Edit
                        </button>
                        <button type="button" class="btn btn-outline-info btn-sm" 
                                onclick="viewBookings('<?php echo $room['room_name']; ?>')">
                            <i class="fas fa-calendar"></i> Bookings
                        </button>
                        <?php if ($room['is_active']): ?>
                        <button type="button" class="btn btn-outline-danger btn-sm" 
                                onclick="deactivateRoom(<?php echo $room['id']; ?>)">
                            <i class="fas fa-times"></i> Deactivate
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-outline-success btn-sm" 
                                onclick="activateRoom(<?php echo $room['id']; ?>)">
                            <i class="fas fa-check"></i> Activate
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="fas fa-bed fa-4x text-muted mb-4"></i>
                    <h4>No Rooms Found</h4>
                    <p class="text-muted">Start by adding your first room to the system.</p>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRoomModal">
                        <i class="fas fa-plus me-2"></i>Add First Room
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Add Room Modal -->
<div class="modal fade" id="addRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_room">
                    
                    <div class="mb-3">
                        <label for="room_name" class="form-label">Room Name *</label>
                        <input type="text" class="form-control" id="room_name" name="room_name" 
                               placeholder="e.g., Presidential Suite, Deluxe Room 101" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="base_price" class="form-label">Base Price (KES) *</label>
                        <input type="number" class="form-control" id="base_price" name="base_price" 
                               min="0" step="0.01" placeholder="e.g., 5000.00" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" 
                                  placeholder="Describe the room amenities and features..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Room Modal -->
<div class="modal fade" id="editRoomModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Room</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_room">
                    <input type="hidden" name="room_id" id="edit_room_id">
                    
                    <div class="mb-3">
                        <label for="edit_room_name" class="form-label">Room Name *</label>
                        <input type="text" class="form-control" id="edit_room_name" name="room_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_base_price" class="form-label">Base Price (KES) *</label>
                        <input type="number" class="form-control" id="edit_base_price" name="base_price" 
                               min="0" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="edit_is_active" name="is_active" checked>
                            <label class="form-check-label" for="edit_is_active">
                                Room is Active
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Room</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hidden Forms for Quick Actions -->
<form id="deactivateForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="delete_room">
    <input type="hidden" name="room_id" id="deactivate_room_id">
</form>

<form id="activateForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_room">
    <input type="hidden" name="room_id" id="activate_room_id">
    <input type="hidden" name="room_name" id="activate_room_name">
    <input type="hidden" name="base_price" id="activate_base_price">
    <input type="hidden" name="description" id="activate_description">
    <input type="hidden" name="is_active" value="1">
</form>

<script>
function editRoom(roomData) {
    document.getElementById('edit_room_id').value = roomData.id;
    document.getElementById('edit_room_name').value = roomData.room_name;
    document.getElementById('edit_base_price').value = roomData.base_price;
    document.getElementById('edit_description').value = roomData.description;
    document.getElementById('edit_is_active').checked = roomData.is_active == 1;
    
    new bootstrap.Modal(document.getElementById('editRoomModal')).show();
}

function deactivateRoom(roomId) {
    if (confirm('Are you sure you want to deactivate this room? It will no longer be available for booking.')) {
        document.getElementById('deactivate_room_id').value = roomId;
        document.getElementById('deactivateForm').submit();
    }
}

function activateRoom(roomId) {
    // You might want to get room data via AJAX, but for now we'll use a simple approach
    if (confirm('Are you sure you want to activate this room?')) {
        // For simplicity, we'll reload the page and let the user edit if needed
        location.href = '?activate=' + roomId;
    }
}

function viewBookings(roomName) {
    // Redirect to bookings page with room filter
            window.open('booking.php?room=' + encodeURIComponent(roomName), '_blank');
}

// Auto-hide alerts after 5 seconds
setTimeout(function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        const bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 5000);
</script>

<?php include '../includes/admin/footer.php'; ?>