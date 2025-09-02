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

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$payment_status_filter = $_GET['payment_status'] ?? 'all';
$room_filter = $_GET['room'] ?? '';
$guest_search = $_GET['guest'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$amount_min = $_GET['amount_min'] ?? '';
$amount_max = $_GET['amount_max'] ?? '';

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

// Get payment statistics
$paid_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE payment_status = 'paid'"))['count'];
$pending_payments = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE payment_status = 'pending'"))['count'];

// Get today's check-ins and check-outs
$today = date('Y-m-d');
$todays_checkins = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cin = '$today'"))['count'];
$todays_checkouts = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cout = '$today'"))['count'];

// Build dynamic query with filters
$where_conditions = [];
$query_params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "rb.stat = ?";
    $query_params[] = $status_filter;
}

if ($payment_status_filter !== 'all') {
    $where_conditions[] = "rb.payment_status = ?";
    $query_params[] = $payment_status_filter;
}

if (!empty($room_filter)) {
    $where_conditions[] = "rb.troom = ?";
    $query_params[] = $room_filter;
}

if (!empty($guest_search)) {
    $where_conditions[] = "(rb.FName LIKE ? OR rb.LName LIKE ? OR rb.Email LIKE ? OR rb.Phone LIKE ?)";
    $search_term = "%$guest_search%";
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
    $query_params[] = $search_term;
}

if (!empty($date_from)) {
    $where_conditions[] = "rb.cin >= ?";
    $query_params[] = $date_from;
}

if (!empty($date_to)) {
    $where_conditions[] = "rb.cout <= ?";
    $query_params[] = $date_to;
}

if (!empty($amount_min)) {
    $where_conditions[] = "(rb.nodays * COALESCE(nr.base_price, 0)) >= ?";
    $query_params[] = $amount_min;
}

if (!empty($amount_max)) {
    $where_conditions[] = "(rb.nodays * COALESCE(nr.base_price, 0)) <= ?";
    $query_params[] = $amount_max;
}

// Build and execute the main query
$bookings_query = "
    SELECT 
        rb.*,
        nr.room_name,
        nr.base_price
    FROM roombook rb 
    LEFT JOIN named_rooms nr ON rb.troom = nr.room_name";

if (!empty($where_conditions)) {
    $bookings_query .= " WHERE " . implode(' AND ', $where_conditions);
}

$bookings_query .= " ORDER BY rb.id DESC LIMIT 100";

// Execute query with prepared statement if there are parameters
if (!empty($query_params)) {
    $stmt = mysqli_prepare($con, $bookings_query);
    if ($stmt) {
        $types = str_repeat('s', count($query_params));
        mysqli_stmt_bind_param($stmt, $types, ...$query_params);
        mysqli_stmt_execute($stmt);
        $bookings_result = mysqli_stmt_get_result($stmt);
    } else {
        $bookings_result = mysqli_query($con, $bookings_query);
    }
} else {
    $bookings_result = mysqli_query($con, $bookings_query);
}
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
            <a href="booking_calendar.php" class="btn btn-outline-secondary">
                <i class="fas fa-calendar me-2"></i>Calendar View
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

<!-- Advanced Filters Section -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">
            <i class="fas fa-filter me-2"></i>Advanced Filters
            <button class="btn btn-sm btn-outline-secondary float-end" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
                <i class="fas fa-chevron-down"></i>
            </button>
        </h6>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body">
            <form method="GET" action="" id="filterForm">
                <div class="row">
                    <!-- Status Filter -->
                    <div class="col-md-2 mb-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="Confirmed" <?php echo $status_filter === 'Confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="Pending" <?php echo $status_filter === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Cancelled" <?php echo $status_filter === 'Cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                        </select>
                    </div>
                    
                    <!-- Payment Status Filter -->
                    <div class="col-md-2 mb-3">
                        <label for="payment_status" class="form-label">Payment Status</label>
                        <select class="form-select" id="payment_status" name="payment_status">
                            <option value="all" <?php echo ($_GET['payment_status'] ?? 'all') === 'all' ? 'selected' : ''; ?>>All Payments</option>
                            <option value="paid" <?php echo ($_GET['payment_status'] ?? 'all') === 'paid' ? 'selected' : ''; ?>>Paid</option>
                            <option value="pending" <?php echo ($_GET['payment_status'] ?? 'all') === 'pending' ? 'selected' : ''; ?>>Pending Payment</option>
                            <option value="partial" <?php echo ($_GET['payment_status'] ?? 'all') === 'partial' ? 'selected' : ''; ?>>Partial Payment</option>
                        </select>
                    </div>
                    
                    <!-- Room Filter -->
                    <div class="col-md-2 mb-3">
                        <label for="room" class="form-label">Room Type</label>
                        <select class="form-select" id="room" name="room">
                            <option value="">All Rooms</option>
                            <?php
                            $rooms_query = "SELECT DISTINCT room_name FROM named_rooms ORDER BY room_name";
                            $rooms_result = mysqli_query($con, $rooms_query);
                            while ($room = mysqli_fetch_assoc($rooms_result)):
                            ?>
                                <option value="<?php echo htmlspecialchars($room['room_name']); ?>" 
                                        <?php echo $room_filter === $room['room_name'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($room['room_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Guest Search -->
                    <div class="col-md-3 mb-3">
                        <label for="guest" class="form-label">Guest Search</label>
                        <input type="text" class="form-control" id="guest" name="guest" 
                               placeholder="Name, Email, Phone" value="<?php echo htmlspecialchars($guest_search); ?>">
                    </div>
                    
                    <!-- Date From -->
                    <div class="col-md-2 mb-3">
                        <label for="date_from" class="form-label">Check-in From</label>
                        <input type="date" class="form-control" id="date_from" name="date_from" 
                               value="<?php echo $date_from; ?>">
                    </div>
                    
                    <!-- Date To -->
                    <div class="col-md-2 mb-3">
                        <label for="date_to" class="form-label">Check-out To</label>
                        <input type="date" class="form-control" id="date_to" name="date_to" 
                               value="<?php echo $date_to; ?>">
                    </div>
                </div>
                
                <div class="row">
                    <!-- Amount Range -->
                    <div class="col-md-2 mb-3">
                        <label for="amount_min" class="form-label">Min Amount</label>
                        <input type="number" class="form-control" id="amount_min" name="amount_min" 
                               placeholder="0" value="<?php echo $amount_min; ?>">
                    </div>
                    
                    <div class="col-md-2 mb-3">
                        <label for="amount_max" class="form-label">Max Amount</label>
                        <input type="number" class="form-control" id="amount_max" name="amount_max" 
                               placeholder="999999" value="<?php echo $amount_max; ?>">
                    </div>
                    
                    <!-- Filter Actions -->
                    <div class="col-md-8 mb-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search me-1"></i>Apply Filters
                        </button>
                        <a href="booking.php" class="btn btn-outline-secondary me-2">
                            <i class="fas fa-times me-1"></i>Clear All
                        </a>
                        <button type="button" class="btn btn-outline-info" onclick="exportBookings()">
                            <i class="fas fa-download me-1"></i>Export
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Booking Statistics -->
<div class="row mb-4">
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $total_bookings; ?></h4>
                        <p class="mb-0 small">Total Bookings</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-calendar-alt fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $confirmed_bookings; ?></h4>
                        <p class="mb-0 small">Confirmed</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-check-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $pending_bookings; ?></h4>
                        <p class="mb-0 small">Pending</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-clock fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $cancelled_bookings; ?></h4>
                        <p class="mb-0 small">Cancelled</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-times-circle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $paid_bookings; ?></h4>
                        <p class="mb-0 small">Paid</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-credit-card fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-2 col-md-4 mb-3">
        <div class="card bg-secondary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4 class="mb-0"><?php echo $pending_payments; ?></h4>
                        <p class="mb-0 small">Pending Payment</p>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-exclamation-triangle fa-lg"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Daily Operations -->
<div class="row mb-4">
    <div class="col-lg-6 mb-3">
        <div class="card border-info h-100">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0"><i class="fas fa-calendar-day me-2"></i>Today's Operations</h6>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h4 class="text-info"><?php echo $todays_checkins; ?></h4>
                        <p class="mb-0 small">Check-ins</p>
                    </div>
                    <div class="col-6">
                        <h4 class="text-info"><?php echo $todays_checkouts; ?></h4>
                        <p class="mb-0 small">Check-outs</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-lg-6 mb-3">
        <div class="card border-success h-100">
            <div class="card-header bg-success text-white">
                <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="staff_booking.php" class="btn btn-outline-success btn-sm">
                        <i class="fas fa-plus me-1"></i>Create New Booking
                    </a>
                    <a href="booking_calendar.php" class="btn btn-outline-info btn-sm">
                        <i class="fas fa-calendar me-1"></i>View Calendar
                    </a>
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
                        <i class="fas fa-list me-2"></i>Bookings
                        <?php if (!empty($where_conditions)): ?>
                            <span class="badge bg-info ms-2">Filtered</span>
                        <?php endif; ?>
                    </h5>
                    <div>
                        <button class="btn btn-sm btn-outline-primary" onclick="refreshTable()">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-filter me-1"></i>Quick Filter
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?status=all">All Bookings</a></li>
                                <li><a class="dropdown-item" href="?status=Confirmed">Confirmed Only</a></li>
                                <li><a class="dropdown-item" href="?status=Pending">Pending Only</a></li>
                                <li><a class="dropdown-item" href="?status=Cancelled">Cancelled Only</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?payment_status=paid">Paid Bookings</a></li>
                                <li><a class="dropdown-item" href="?payment_status=pending">Pending Payments</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="?date_from=<?php echo date('Y-m-d'); ?>">Today's Check-ins</a></li>
                                <li><a class="dropdown-item" href="?date_to=<?php echo date('Y-m-d'); ?>">Today's Check-outs</a></li>
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
                                <th>Payment</th>
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
                                        <?php
                                        $payment_class = '';
                                        $payment_status = $booking['payment_status'] ?? 'pending';
                                        switch($payment_status) {
                                            case 'paid': $payment_class = 'success'; break;
                                            case 'pending': $payment_class = 'warning'; break;
                                            case 'partial': $payment_class = 'info'; break;
                                            default: $payment_class = 'secondary';
                                        }
                                        ?>
                                        <span class="badge bg-<?php echo $payment_class; ?>">
                                            <?php echo ucfirst($payment_status); ?>
                                        </span>
                                        <?php if ($payment_status === 'pending'): ?>
                                            <br><small class="text-muted">Click payment button to process</small>
                                        <?php elseif ($payment_status === 'paid'): ?>
                                            <br><small class="text-success">Payment completed</small>
                                        <?php endif; ?>
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
                                            
                                            <?php if (($booking['payment_status'] ?? 'pending') === 'paid'): ?>
                                                <button type="button" class="btn btn-success btn-sm" disabled title="Payment Completed">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php else: ?>
                                                <a href="payment.php?booking_id=<?php echo $booking['id']; ?>" 
                                                   class="btn btn-outline-warning btn-sm" title="Process Payment">
                                                    <i class="fas fa-credit-card"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
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

function exportBookings() {
    // Get current filter parameters
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = 'export_bookings.php?' + urlParams.toString();
    
    // Create a temporary link and trigger download
    const link = document.createElement('a');
    link.href = exportUrl;
    link.download = 'bookings_export_' + new Date().toISOString().split('T')[0] + '.csv';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Auto-submit form when certain filters change
document.addEventListener('DOMContentLoaded', function() {
    const statusSelect = document.getElementById('status');
    const paymentStatusSelect = document.getElementById('payment_status');
    const roomSelect = document.getElementById('room');
    
    // Auto-submit when status, payment status, or room changes
    statusSelect.addEventListener('change', function() {
        if (this.value !== 'all') {
            document.getElementById('filterForm').submit();
        }
    });
    
    paymentStatusSelect.addEventListener('change', function() {
        if (this.value !== 'all') {
            document.getElementById('filterForm').submit();
        }
    });
    
    roomSelect.addEventListener('change', function() {
        if (this.value !== '') {
            document.getElementById('filterForm').submit();
        }
    });
    
    // Add date validation
    const dateFrom = document.getElementById('date_from');
    const dateTo = document.getElementById('date_to');
    
    dateFrom.addEventListener('change', function() {
        if (dateTo.value && this.value > dateTo.value) {
            alert('Check-in date cannot be after check-out date');
            this.value = '';
        }
    });
    
    dateTo.addEventListener('change', function() {
        if (dateFrom.value && this.value < dateFrom.value) {
            alert('Check-out date cannot be before check-in date');
            this.value = '';
        }
    });
    
    // Add amount validation
    const amountMin = document.getElementById('amount_min');
    const amountMax = document.getElementById('amount_max');
    
    amountMin.addEventListener('change', function() {
        if (amountMax.value && parseFloat(this.value) > parseFloat(amountMax.value)) {
            alert('Minimum amount cannot be greater than maximum amount');
            this.value = '';
        }
    });
    
    amountMax.addEventListener('change', function() {
        if (amountMin.value && parseFloat(this.value) < parseFloat(amountMin.value)) {
            alert('Maximum amount cannot be less than minimum amount');
            this.value = '';
        }
    });
    
    // Add table row highlighting for different statuses
    const tableRows = document.querySelectorAll('#bookingsTable tbody tr');
    tableRows.forEach(row => {
        const status = row.getAttribute('data-status');
        const paymentStatus = row.querySelector('td:nth-child(6) .badge').textContent.toLowerCase();
        
        if (status === 'Cancelled') {
            row.classList.add('table-danger');
        } else if (paymentStatus === 'pending') {
            row.classList.add('table-warning');
        } else if (status === 'Confirmed' && paymentStatus === 'paid') {
            row.classList.add('table-success');
        }
    });
    
    // Add search highlighting
    const guestSearch = document.getElementById('guest');
    if (guestSearch.value) {
        highlightSearchTerm(guestSearch.value);
    }
});

function highlightSearchTerm(searchTerm) {
    const tableCells = document.querySelectorAll('#bookingsTable tbody td');
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    
    tableCells.forEach(cell => {
        if (cell.textContent.toLowerCase().includes(searchTerm.toLowerCase())) {
            cell.innerHTML = cell.textContent.replace(regex, '<mark>$1</mark>');
        }
    });
}

// Add search functionality for guest field
document.getElementById('guest').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') {
        e.preventDefault();
        document.getElementById('filterForm').submit();
    }
});
</script>

<?php include '../includes/admin/footer.php'; ?>
