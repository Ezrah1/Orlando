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

$page_title = 'Bookings Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'update_status':
            $booking_id = (int)$_POST['booking_id'];
            $new_status = mysqli_real_escape_string($con, $_POST['new_status']);
            $notes = mysqli_real_escape_string($con, $_POST['notes']);
            
            $update_sql = "UPDATE roombook SET stat = '$new_status', staff_notes = '$notes' WHERE id = $booking_id";
            if (mysqli_query($con, $update_sql)) {
                $_SESSION['success_message'] = "Booking status updated successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to update booking status.";
            }
            break;
            
        case 'confirm_payment':
            $booking_id = (int)$_POST['booking_id'];
            $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
            
            $update_sql = "UPDATE roombook SET payment_status = 'paid' WHERE id = $booking_id";
            if (mysqli_query($con, $update_sql)) {
                $_SESSION['success_message'] = "Payment confirmed successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to confirm payment.";
            }
            break;
            
        case 'clear_for_rebooking':
            $booking_id = (int)$_POST['booking_id'];
            $staff_notes = mysqli_real_escape_string($con, $_POST['staff_notes']);
            $staff_id = $_SESSION['user_id'];
            $staff_name = $_SESSION['user'] ?? 'Staff';
            
            $clearance_note = "CLEARED FOR REBOOKING by $staff_name (ID: $staff_id) on " . date('Y-m-d H:i:s') . ". Reason: $staff_notes";
            
            $update_sql = "UPDATE roombook SET 
                          status = 'completed', 
                          stat = 'cleared_for_rebooking',
                          staff_notes = CONCAT(IFNULL(staff_notes, ''), '\n\n$clearance_note')
                          WHERE id = $booking_id";
            
            if (mysqli_query($con, $update_sql)) {
                // Update room status to available
                $room_query = "SELECT TRoom FROM roombook WHERE id = $booking_id";
                $room_result = mysqli_query($con, $room_query);
                $room = mysqli_fetch_assoc($room_result);
                
                if($room) {
                    $room_name = $room['TRoom'];
                    $update_room_sql = "UPDATE room_status SET current_status = 'available', cleaning_status = 'clean', updated_by = $staff_id, updated_at = NOW() WHERE room_name = '$room_name'";
                    mysqli_query($con, $update_room_sql);
                }
                
                $_SESSION['success_message'] = "Room cleared for rebooking successfully!";
            } else {
                $_SESSION['error_message'] = "Failed to clear room for rebooking.";
            }
            break;
    }
    header("Location: bookings_management.php");
    exit();
}

// Get filters
$status_filter = $_GET['status'] ?? 'all';
$date_filter = $_GET['date'] ?? 'all';
$payment_filter = $_GET['payment'] ?? 'all';

// Build WHERE clause
$where_conditions = ['1=1'];

if ($status_filter != 'all') {
    $where_conditions[] = "stat = '" . mysqli_real_escape_string($con, $status_filter) . "'";
}

if ($payment_filter != 'all') {
    $where_conditions[] = "payment_status = '" . mysqli_real_escape_string($con, $payment_filter) . "'";
}

if ($date_filter != 'all') {
    switch ($date_filter) {
        case 'today':
            $where_conditions[] = "DATE(created_at) = CURDATE()";
            break;
        case 'week':
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            break;
        case 'month':
            $where_conditions[] = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            break;
    }
}

$where_clause = implode(' AND ', $where_conditions);

// Get bookings with pagination
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$items_per_page = 20;
$offset = ($page - 1) * $items_per_page;

$bookings_sql = "SELECT rb.*, 
                        CONCAT(rb.Title, ' ', rb.FName, ' ', rb.LName) as guest_name,
                        CASE 
                            WHEN rb.payment_status = 'paid' THEN 'Paid'
                            WHEN rb.payment_status = 'pending' THEN 'Pending'
                            ELSE 'Not Set'
                        END as payment_display
                 FROM roombook rb
                 WHERE $where_clause
                 ORDER BY rb.created_at DESC
                 LIMIT $items_per_page OFFSET $offset";

$bookings_result = mysqli_query($con, $bookings_sql);

// Get total count for pagination
$count_sql = "SELECT COUNT(*) as total FROM roombook WHERE $where_clause";
$count_result = mysqli_query($con, $count_sql);
$total_bookings = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_bookings / $items_per_page);

// Get summary statistics
$stats_sql = "SELECT 
                COUNT(*) as total_bookings,
                SUM(CASE WHEN stat = 'pending' THEN 1 ELSE 0 END) as pending_bookings,
                SUM(CASE WHEN stat = 'confirmed' OR stat = 'Conform' THEN 1 ELSE 0 END) as confirmed_bookings,
                SUM(CASE WHEN payment_status = 'paid' THEN 1 ELSE 0 END) as paid_bookings,
                0 as total_revenue
              FROM roombook";
$stats_result = mysqli_query($con, $stats_sql);
$stats = mysqli_fetch_assoc($stats_result);
?>

<?php display_session_alerts(); ?>

<div class="container-fluid">
    <div class="row g-0">
        <?php include '../includes/admin/sidebar.php'; ?>
        
        <div class="admin-main-content">
            <div class="content-wrapper p-4">
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-calendar-check text-primary"></i> Bookings Management</h2>
                        <p class="text-muted mb-0">Manage all guest bookings and reservations</p>
                    </div>
                    <div>
                        <a href="staff_booking.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> New Booking
                        </a>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-calendar fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-primary"><?php echo $stats['total_bookings']; ?></div>
                                        <small class="text-muted">Total Bookings</small>
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
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-warning"><?php echo $stats['pending_bookings']; ?></div>
                                        <small class="text-muted">Pending Bookings</small>
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
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-success"><?php echo $stats['confirmed_bookings']; ?></div>
                                        <small class="text-muted">Confirmed Bookings</small>
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
                                        <i class="fas fa-money-bill fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-info">KES <?php echo number_format($stats['total_revenue']); ?></div>
                                        <small class="text-muted">Total Revenue</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="Conform" <?php echo $status_filter == 'Conform' ? 'selected' : ''; ?>>Checked In</option>
                                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Payment Status</label>
                                <select name="payment" class="form-select">
                                    <option value="all" <?php echo $payment_filter == 'all' ? 'selected' : ''; ?>>All Payments</option>
                                    <option value="pending" <?php echo $payment_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $payment_filter == 'paid' ? 'selected' : ''; ?>>Paid</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Date Range</label>
                                <select name="date" class="form-select">
                                    <option value="all" <?php echo $date_filter == 'all' ? 'selected' : ''; ?>>All Time</option>
                                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>Last 7 Days</option>
                                    <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>Last 30 Days</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary d-block">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Bookings Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Bookings (<?php echo $total_bookings; ?> total)</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Booking Ref</th>
                                        <th>Guest Name</th>
                                        <th>Room</th>
                                        <th>Check-in</th>
                                        <th>Check-out</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = mysqli_fetch_assoc($bookings_result)): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($booking['booking_ref'] ?? 'N/A'); ?></strong>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($booking['guest_name']); ?></strong>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['Email']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo htmlspecialchars($booking['TRoom']); ?></span>
                                            <br><small><?php echo $booking['nodays']; ?> night(s)</small>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($booking['cin'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($booking['cout'])); ?></td>
                                        <td>
                                            <strong>KES 0.00</strong>
                                        </td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch ($booking['stat']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'confirmed':
                                                case 'Conform':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    break;
                                                default:
                                                    $status_class = 'bg-secondary';
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo htmlspecialchars($booking['stat']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php
                                            $payment_class = $booking['payment_status'] == 'paid' ? 'bg-success' : 'bg-warning';
                                            ?>
                                            <span class="badge <?php echo $payment_class; ?>">
                                                <?php echo htmlspecialchars($booking['payment_display']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-outline-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)" title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-outline-success" onclick="updateStatus(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['stat']); ?>')" title="Update Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <?php if ($booking['payment_status'] != 'paid'): ?>
                                                <button class="btn btn-outline-info" onclick="confirmPayment(<?php echo $booking['id']; ?>)" title="Confirm Payment">
                                                    <i class="fas fa-money-bill"></i>
                                                </button>
                                                <?php endif; ?>
                                                <?php if ($booking['stat'] != 'cleared_for_rebooking' && $booking['status'] != 'cancelled'): ?>
                                                <button class="btn btn-outline-warning" onclick="clearForRebooking(<?php echo $booking['id']; ?>, '<?php echo htmlspecialchars($booking['TRoom']); ?>')" title="Clear for Rebooking">
                                                    <i class="fas fa-unlock"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Bookings pagination" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&payment=<?php echo $payment_filter; ?>&date=<?php echo $date_filter; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Booking Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" id="statusBookingId">
                    
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select name="new_status" class="form-select" required>
                            <option value="pending">Pending</option>
                            <option value="confirmed">Confirmed</option>
                            <option value="Conform">Checked In</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes..."></textarea>
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

<!-- Confirm Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="confirm_payment">
                    <input type="hidden" name="booking_id" id="paymentBookingId">
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select name="payment_method" class="form-select" required>
                            <option value="cash">Cash</option>
                            <option value="mpesa">M-Pesa</option>
                            <option value="card">Credit/Debit Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        This will mark the payment as received and confirmed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Confirm Payment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Clear for Rebooking Modal -->
<div class="modal fade" id="clearRebookingModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Clear Room for Rebooking</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="clear_for_rebooking">
                    <input type="hidden" name="booking_id" id="clearBookingId">
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Warning:</strong> This will mark the current booking as completed and clear the room for new bookings. 
                        This action should only be used when the guest has checked out or the booking needs to be manually cleared.
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Room:</label>
                        <strong id="clearRoomName"></strong>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reason for Clearing *</label>
                        <textarea name="staff_notes" class="form-control" rows="3" placeholder="Please provide a reason for clearing this room for rebooking..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Clear for Rebooking</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function viewBooking(bookingId) {
    window.location.href = 'roombook.php?rid=' + bookingId;
}

function updateStatus(bookingId, currentStatus) {
    document.getElementById('statusBookingId').value = bookingId;
    document.querySelector('[name="new_status"]').value = currentStatus;
    
    const modal = new bootstrap.Modal(document.getElementById('statusModal'));
    modal.show();
}

function confirmPayment(bookingId) {
    document.getElementById('paymentBookingId').value = bookingId;
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

function clearForRebooking(bookingId, roomName) {
    document.getElementById('clearBookingId').value = bookingId;
    document.getElementById('clearRoomName').textContent = roomName;
    
    const modal = new bootstrap.Modal(document.getElementById('clearRebookingModal'));
    modal.show();
}
</script>

<?php include '../includes/admin/footer.php'; ?>
