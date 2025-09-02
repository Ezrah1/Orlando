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

$page_title = 'Booking Calendar';

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Get current month/year or from URL parameters
$current_month = $_GET['month'] ?? date('n');
$current_year = $_GET['year'] ?? date('Y');

// Convert to integers
$current_month = intval($current_month);
$current_year = intval($current_year);

// Validate month and year
if ($current_month < 1 || $current_month > 12) $current_month = date('n');
if ($current_year < 2020 || $current_year > 2030) $current_year = date('Y');

// Get month name
$month_names = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

$month_name = $month_names[$current_month];

// Get first day of month and number of days
$first_day = mktime(0, 0, 0, $current_month, 1, $current_year);
$first_day_of_week = date('w', $first_day); // 0 = Sunday, 1 = Monday, etc.
$days_in_month = date('t', $first_day);

// Get all bookings for the current month
$start_date = date('Y-m-01', $first_day);
$end_date = date('Y-m-t', $first_day);

$bookings_query = "
    SELECT 
        rb.id,
        rb.FName,
        rb.LName,
        rb.troom,
        rb.cin,
        rb.cout,
        rb.stat,
        rb.nodays,
        nr.base_price
    FROM roombook rb 
    LEFT JOIN named_rooms nr ON rb.troom = nr.room_name 
    WHERE (rb.cin <= ? AND rb.cout >= ?) 
    AND rb.stat != 'Cancelled'
    ORDER BY rb.cin";

$stmt = mysqli_prepare($con, $bookings_query);
mysqli_stmt_bind_param($stmt, "ss", $end_date, $start_date);
mysqli_stmt_execute($stmt);
$bookings_result = mysqli_stmt_get_result($stmt);

// Organize bookings by date
$bookings_by_date = [];
while ($booking = mysqli_fetch_assoc($bookings_result)) {
    $checkin = new DateTime($booking['cin']);
    $checkout = new DateTime($booking['cout']);
    
    // Add booking to all relevant dates
    $current_date = clone $checkin;
    while ($current_date <= $checkout) {
        $date_key = $current_date->format('Y-m-d');
        if (!isset($bookings_by_date[$date_key])) {
            $bookings_by_date[$date_key] = [];
        }
        $bookings_by_date[$date_key][] = $booking;
        $current_date->add(new DateInterval('P1D'));
    }
}

// Navigation functions
function get_prev_month($month, $year) {
    if ($month == 1) {
        return [12, $year - 1];
    }
    return [$month - 1, $year];
}

function get_next_month($month, $year) {
    if ($month == 12) {
        return [1, $year + 1];
    }
    return [$month + 1, $year];
}

$prev_month = get_prev_month($current_month, $current_year);
$next_month = get_next_month($current_month, $current_year);
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Booking Calendar</h1>
            <p class="page-subtitle">Monthly view of all hotel bookings</p>
        </div>
        <div>
            <a href="booking.php" class="btn btn-outline-secondary">
                <i class="fas fa-list me-2"></i>List View
            </a>
            <a href="staff_booking.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>New Booking
            </a>
        </div>
    </div>
</div>

<!-- Calendar Navigation -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <a href="?month=<?php echo $prev_month[0]; ?>&year=<?php echo $prev_month[1]; ?>" 
                       class="btn btn-outline-secondary">
                        <i class="fas fa-chevron-left me-2"></i><?php echo $month_names[$prev_month[0]]; ?> <?php echo $prev_month[1]; ?>
                    </a>
                    
                    <h3 class="mb-0"><?php echo $month_name; ?> <?php echo $current_year; ?></h3>
                    
                    <a href="?month=<?php echo $next_month[0]; ?>&year=<?php echo $next_month[1]; ?>" 
                       class="btn btn-outline-secondary">
                        <?php echo $month_names[$next_month[0]]; ?> <?php echo $next_month[1]; ?><i class="fas fa-chevron-right ms-2"></i>
                    </a>
                </div>
                
                <!-- Quick Navigation -->
                <div class="text-center mt-3">
                    <div class="btn-group">
                        <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                           class="btn btn-sm btn-outline-primary">Current Month</a>
                        <a href="?month=<?php echo date('n', strtotime('+1 month')); ?>&year=<?php echo date('Y', strtotime('+1 month')); ?>" 
                           class="btn btn-sm btn-outline-info">Next Month</a>
                        <a href="?month=<?php echo date('n', strtotime('-1 month')); ?>&year=<?php echo date('Y', strtotime('-1 month')); ?>" 
                           class="btn btn-sm btn-outline-info">Previous Month</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body p-0">
                <div class="calendar-container">
                    <!-- Calendar Header -->
                    <div class="calendar-header">
                        <div class="calendar-day-header">Sun</div>
                        <div class="calendar-day-header">Mon</div>
                        <div class="calendar-day-header">Tue</div>
                        <div class="calendar-day-header">Wed</div>
                        <div class="calendar-day-header">Thu</div>
                        <div class="calendar-day-header">Fri</div>
                        <div class="calendar-day-header">Sat</div>
                    </div>
                    
                    <!-- Calendar Days -->
                    <div class="calendar-grid">
                        <?php
                        // Add empty cells for days before the first day of the month
                        for ($i = 0; $i < $first_day_of_week; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        
                        // Add days of the month
                        for ($day = 1; $day <= $days_in_month; $day++) {
                            $current_date = sprintf('%04d-%02d-%02d', $current_year, $current_month, $day);
                            $is_today = ($current_date == date('Y-m-d'));
                            $has_bookings = isset($bookings_by_date[$current_date]);
                            
                            $day_class = 'calendar-day';
                            if ($is_today) $day_class .= ' today';
                            if ($has_bookings) $day_class .= ' has-bookings';
                            
                            echo '<div class="' . $day_class . '" data-date="' . $current_date . '">';
                            echo '<div class="day-number">' . $day . '</div>';
                            
                            if ($has_bookings) {
                                $bookings = $bookings_by_date[$current_date];
                                echo '<div class="bookings-container">';
                                foreach ($bookings as $booking) {
                                    $status_class = strtolower($booking['stat']);
                                    $guest_name = htmlspecialchars($booking['FName'] . ' ' . $booking['LName']);
                                    $room_name = htmlspecialchars($booking['troom']);
                                    
                                    echo '<div class="booking-item ' . $status_class . '" 
                                              data-booking-id="' . $booking['id'] . '"
                                              title="' . $guest_name . ' - ' . $room_name . '">
                                              <div class="booking-guest">' . $guest_name . '</div>
                                              <div class="booking-room">' . $room_name . '</div>
                                          </div>';
                                }
                                echo '</div>';
                            }
                            
                            echo '</div>';
                        }
                        
                        // Add empty cells to complete the last week
                        $last_day_of_week = date('w', mktime(0, 0, 0, $current_month, $days_in_month, $current_year));
                        for ($i = $last_day_of_week; $i < 6; $i++) {
                            echo '<div class="calendar-day empty"></div>';
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Legend -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Calendar Legend</h6>
                <div class="d-flex flex-wrap gap-3">
                    <div class="d-flex align-items-center">
                        <div class="legend-item confirmed"></div>
                        <span class="ms-2">Confirmed Bookings</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="legend-item pending"></div>
                        <span class="ms-2">Pending Bookings</span>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="legend-item today"></div>
                        <span class="ms-2">Today</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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

<style>
.calendar-container {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
}

.calendar-day-header {
    padding: 15px 10px;
    text-align: center;
    font-weight: 600;
    color: #495057;
    border-right: 1px solid #dee2e6;
}

.calendar-day-header:last-child {
    border-right: none;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
    min-height: 120px;
    border-right: 1px solid #dee2e6;
    border-bottom: 1px solid #dee2e6;
    padding: 8px;
    position: relative;
    background: white;
    transition: background-color 0.2s;
}

.calendar-day:hover {
    background: #f8f9fa;
}

.calendar-day.empty {
    background: #f8f9fa;
}

.calendar-day.today {
    background: #e3f2fd;
    border: 2px solid #2196f3;
}

.calendar-day.has-bookings {
    background: #f3e5f5;
}

.day-number {
    font-weight: 600;
    color: #495057;
    margin-bottom: 5px;
}

.bookings-container {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.booking-item {
    padding: 4px 6px;
    border-radius: 4px;
    font-size: 11px;
    cursor: pointer;
    transition: all 0.2s;
    border-left: 3px solid;
}

.booking-item:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.booking-item.confirmed {
    background: #d4edda;
    border-left-color: #28a745;
    color: #155724;
}

.booking-item.pending {
    background: #fff3cd;
    border-left-color: #ffc107;
    color: #856404;
}

.booking-guest {
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.booking-room {
    font-size: 10px;
    opacity: 0.8;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.legend-item {
    width: 20px;
    height: 20px;
    border-radius: 4px;
    border: 1px solid #dee2e6;
}

.legend-item.confirmed {
    background: #d4edda;
    border-color: #28a745;
}

.legend-item.pending {
    background: #fff3cd;
    border-color: #ffc107;
}

.legend-item.today {
    background: #e3f2fd;
    border-color: #2196f3;
}

@media (max-width: 768px) {
    .calendar-day {
        min-height: 80px;
        padding: 4px;
    }
    
    .booking-item {
        font-size: 10px;
        padding: 2px 4px;
    }
    
    .day-number {
        font-size: 14px;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to booking items
    document.querySelectorAll('.booking-item').forEach(function(item) {
        item.addEventListener('click', function() {
            const bookingId = this.getAttribute('data-booking-id');
            showBookingDetails(bookingId);
        });
    });
    
    // Add click event to calendar days
    document.querySelectorAll('.calendar-day:not(.empty)').forEach(function(day) {
        day.addEventListener('click', function() {
            const date = this.getAttribute('data-date');
            if (date) {
                showDateBookings(date);
            }
        });
    });
});

function showBookingDetails(bookingId) {
    // You can implement AJAX call to load booking details
    alert('View booking #' + bookingId + ' - Feature to be implemented');
    // Alternative: redirect to booking details page
    // window.location.href = 'roombook.php?rid=' + bookingId;
}

function showDateBookings(date) {
    // You can implement a modal or redirect to show all bookings for a specific date
    alert('Show all bookings for ' + date + ' - Feature to be implemented');
}
</script>

<?php include '../includes/admin/footer.php'; ?>
