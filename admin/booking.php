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

$page_title = 'Booking Management';

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $booking_id = intval($_POST['booking_id']);
                $new_status = mysqli_real_escape_string($con, $_POST['status']);
                
                $sql = "UPDATE roombook SET stat = '$new_status' WHERE id = $booking_id";
                if (mysqli_query($con, $sql)) {
                    $success_message = "Booking status updated successfully!";
                } else {
                    $error_message = "Error updating status: " . mysqli_error($con);
                }
                break;
                
            case 'cancel_booking':
                $booking_id = intval($_POST['booking_id']);
                
                $sql = "UPDATE roombook SET stat = 'Cancelled' WHERE id = $booking_id";
                if (mysqli_query($con, $sql)) {
                    $success_message = "Booking cancelled successfully!";
                } else {
                    $error_message = "Error cancelling booking: " . mysqli_error($con);
                }
                break;
        }
    }
}

// Get booking statistics
$total_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook"))['count'];
$confirmed_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Confirmed'"))['count'];
$pending_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Pending'"))['count'];
$cancelled_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'Cancelled'"))['count'];

// Get recent bookings with room details
$bookings_query = "
    SELECT 
        rb.*,
        nr.room_name,
        nr.base_price
    FROM roombook rb 
    LEFT JOIN named_rooms nr ON rb.troom = nr.room_name 
    ORDER BY rb.id DESC 
    LIMIT 50";
$bookings_result = mysqli_query($con, $bookings_query);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Booking Management</h1>
            <p class="page-subtitle">Manage hotel bookings and reservations</p>
        </div>
        <div>
            <a href="staff_booking.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Booking
            </a>
            <a href="roombook.php" class="btn btn-outline-secondary">
                <i class="fas fa-calendar me-2"></i>Booking Calendar
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

<!-- Booking Statistics -->
<div class="row mb-4">
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $total_bookings; ?></h3>
                        <p class="mb-0">Total Bookings</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-alt fa-2x"></i>
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
                        <h3 class="mb-0"><?php echo $confirmed_bookings; ?></h3>
                        <p class="mb-0">Confirmed</p>
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
                        <h3 class="mb-0"><?php echo $pending_bookings; ?></h3>
                        <p class="mb-0">Pending</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-3 col-md-6 mb-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $cancelled_bookings; ?></h3>
                        <p class="mb-0">Cancelled</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-2x"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bookings Table -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>Recent Bookings
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="filterTable('all')">All Bookings</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterTable('Confirmed')">Confirmed Only</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterTable('Pending')">Pending Only</a></li>
                                <li><a class="dropdown-item" href="#" onclick="filterTable('Cancelled')">Cancelled Only</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="bookingsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Booking ID</th>
                                <th>Guest Details</th>
                                <th>Room</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Amount</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($bookings_result) > 0): ?>
                                <?php while($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                <tr data-status="<?php echo $booking['stat']; ?>">
                                    <td>
                                        <span class="fw-bold text-primary">#<?php echo $booking['id']; ?></span>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($booking['Title'] . ' ' . $booking['FName'] . ' ' . $booking['LName']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($booking['Email']); ?>
                                                <br>
                                                <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($booking['Phone']); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($booking['room_name'] ?? $booking['troom']); ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                Base Price: KES <?php echo number_format($booking['base_price'] ?? 0, 2); ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <div>
                                            <strong>Check-in:</strong> <?php echo date('M d, Y', strtotime($booking['cin'])); ?>
                                            <br>
                                            <strong>Check-out:</strong> <?php echo date('M d, Y', strtotime($booking['cout'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php 
                                                $days = (strtotime($booking['cout']) - strtotime($booking['cin'])) / (60 * 60 * 24);
                                                echo $days . ' night' . ($days != 1 ? 's' : '');
                                                ?>
                                            </small>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $status_class = '';
                                        switch($booking['stat']) {
                                            case 'Confirmed': $status_class = 'success'; break;
                                            case 'Pending': $status_class = 'warning'; break;
                                            case 'Cancelled': $status_class = 'danger'; break;
                                            default: $status_class = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $status_class; ?>">
                                            <?php echo htmlspecialchars($booking['stat']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-bold text-primary">KES <?php echo number_format($booking['nodays'] * ($booking['base_price'] ?? 0), 2); ?></span>
                                        <br>
                                        <small class="text-muted">Total Amount</small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary" 
                                                    onclick="viewBooking(<?php echo $booking['id']; ?>)"
                                                    title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            
                                            <?php if ($booking['stat'] !== 'Cancelled'): ?>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-success dropdown-toggle" 
                                                        data-bs-toggle="dropdown" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <?php if ($booking['stat'] !== 'Confirmed'): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           onclick="updateStatus(<?php echo $booking['id']; ?>, 'Confirmed')">
                                                            <i class="fas fa-check text-success me-2"></i>Confirm
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($booking['stat'] !== 'Pending'): ?>
                                                    <li>
                                                        <a class="dropdown-item" href="#" 
                                                           onclick="updateStatus(<?php echo $booking['id']; ?>, 'Pending')">
                                                            <i class="fas fa-clock text-warning me-2"></i>Set Pending
                                                        </a>
                                                    </li>
                                                    <?php endif; ?>
                                                    
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger" href="#" 
                                                           onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                                            <i class="fas fa-times text-danger me-2"></i>Cancel
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-outline-info" title="Payment">
                                                <i class="fas fa-credit-card"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">
                                        <i class="fas fa-calendar-times fa-3x mb-3"></i>
                                        <br>
                                        No bookings found.
                                        <br>
                                        <a href="staff_booking.php" class="btn btn-primary mt-2">
                                            <i class="fas fa-plus me-2"></i>Create First Booking
                                        </a>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden forms for status updates -->
<form id="statusUpdateForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="booking_id" id="statusBookingId">
    <input type="hidden" name="status" id="statusValue">
</form>

<form id="cancelForm" method="POST" style="display: none;">
    <input type="hidden" name="action" value="cancel_booking">
    <input type="hidden" name="booking_id" id="cancelBookingId">
</form>

<!-- Booking Details Modal -->
<div class="modal fade" id="bookingModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Booking Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="bookingModalBody">
                <!-- Content will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script>
function updateStatus(bookingId, status) {
    if (confirm(`Are you sure you want to update this booking status to ${status}?`)) {
        document.getElementById('statusBookingId').value = bookingId;
        document.getElementById('statusValue').value = status;
        document.getElementById('statusUpdateForm').submit();
    }
}

function cancelBooking(bookingId) {
    if (confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        document.getElementById('cancelBookingId').value = bookingId;
        document.getElementById('cancelForm').submit();
    }
}

function viewBooking(bookingId) {
    // You can implement AJAX call to load booking details
    alert('View booking #' + bookingId + ' - Feature to be implemented');
}

function refreshTable() {
    location.reload();
}

function filterTable(status) {
    const table = document.getElementById('bookingsTable');
    const rows = table.getElementsByTagName('tr');
    
    for (let i = 1; i < rows.length; i++) { // Skip header row
        const row = rows[i];
        const rowStatus = row.getAttribute('data-status');
        
        if (status === 'all' || rowStatus === status) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    }
}
</script>

<?php include '../includes/admin/footer.php'; ?>
