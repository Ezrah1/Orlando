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

// Handle booking submission BEFORE including header.php
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
    $guest_email = mysqli_real_escape_string($con, $_POST['guest_email']);
    $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
    $guest_nationality = mysqli_real_escape_string($con, $_POST['guest_nationality']);
    $guest_id_number = mysqli_real_escape_string($con, $_POST['guest_id_number']);
    
    // Split guest name into first and last name for compatibility with guest system
    $name_parts = explode(' ', trim($guest_name), 2);
    $fname = $name_parts[0];
    $lname = isset($name_parts[1]) ? $name_parts[1] : '';
    $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
    $check_in = mysqli_real_escape_string($con, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($con, $_POST['check_out']);
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];
    $discount = (float)$_POST['discount'];
    $payment_status = mysqli_real_escape_string($con, $_POST['payment_status']);
    $staff_notes = mysqli_real_escape_string($con, $_POST['staff_notes']);
    
    // Calculate number of days
    $check_in_date = new DateTime($check_in);
    $check_out_date = new DateTime($check_out);
    $days = $check_in_date->diff($check_out_date)->days;
    
    // Get room price
    $room_query = "SELECT base_price FROM named_rooms WHERE room_name = '$room_name'";
    $room_result = mysqli_query($con, $room_query);
    $room_data = mysqli_fetch_assoc($room_result);
    $room_price = $room_data['base_price'];
    
    // Calculate total
    $subtotal = $room_price * $days;
    $total = $subtotal - $discount;
    
    // Generate booking reference
    $booking_ref = 'ORL' . date('Ymd') . rand(1000, 9999);
    
    // Insert booking
    $booking_sql = "INSERT INTO roombook (FName, LName, Email, Phone, National, id_number, TRoom, cin, cout, nodays, payment_status, booking_ref, status, staff_notes, created_by, created_at) 
                    VALUES ('$fname', '$lname', '$guest_email', '$guest_phone', '$guest_nationality', '$guest_id_number', '$room_name', '$check_in', '$check_out', $days, '$payment_status', '$booking_ref', 'confirmed', '$staff_notes', " . $_SESSION['user_id'] . ", NOW())";
    
    if(mysqli_query($con, $booking_sql)) {
        $booking_id = mysqli_insert_id($con);
        

        
        // Redirect to staff booking confirmation page
        header("Location: staff_booking_confirmation.php?booking_ref=" . urlencode($booking_ref));
        exit();
    } else {
        $error = "Booking failed. Please try again.";
    }
}

// Get room data for the booking form
$rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price ASC";
$rooms_result = mysqli_query($con, $rooms_query);
$rooms = [];
$default_room = null;
$affordable_room = null;
while($room = mysqli_fetch_assoc($rooms_result)) {
    $rooms[] = $room;
    // For staff bookings, prefer affordable standard rooms
    if (stripos($room['room_name'], 'standard') !== false || $room['base_price'] <= 5000) {
        if (!$default_room) $default_room = $room['room_name'];
    }
    // Fallback to most affordable room
    if (!$affordable_room) {
        $affordable_room = $room['room_name'];
    }
}
// Final fallback
if (!$default_room) $default_room = $affordable_room;

// Now include header files AFTER all potential redirects
$page_title = 'Staff Booking';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
?>

<!-- Page Header -->
<div class="page-header">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="page-title">Staff Booking</h1>
            <p class="page-subtitle">Create walk-in bookings and manage on-site reservations</p>
        </div>
        <div>
            <a href="booking.php" class="btn btn-outline-secondary">
                <i class="fas fa-list me-2"></i>View All Bookings
            </a>
            <a href="booking_calendar.php" class="btn btn-outline-info">
                <i class="fas fa-calendar me-2"></i>Calendar View
            </a>
        </div>
    </div>
</div>

<?php
// Display session alerts
display_session_alerts();

// Display error messages only (success redirects immediately)
if (isset($error)) {
    echo render_alert($error, 'danger');
}
?>

<!-- Staff Booking Form -->
<div class="row">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-user-plus me-2"></i>Walk-in Guest Booking
                </h5>
            </div>
            <div class="card-body">
                <form method="POST" id="staffBookingForm">
                    <!-- Guest Information Section -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="section-header mb-3">
                                <h6 class="text-primary">
                                    <i class="fas fa-user me-2"></i>Guest Information
                                </h6>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_name" class="form-label">Full Name *</label>
                                <input type="text" name="guest_name" id="guest_name" class="form-control" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_email" class="form-label">Email</label>
                                <input type="email" name="guest_email" id="guest_email" class="form-control">
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_phone" class="form-label">Phone Number *</label>
                                <input type="tel" name="guest_phone" id="guest_phone" class="form-control" placeholder="254700123456" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_nationality" class="form-label">Nationality</label>
                                <select name="guest_nationality" id="guest_nationality" class="form-select">
                                    <option value="Kenyan" selected>Kenyan</option>
                                    <option value="Non Kenyan">Non Kenyan</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="guest_id_number" class="form-label">ID Number/Passport</label>
                                <input type="text" name="guest_id_number" id="guest_id_number" class="form-control" placeholder="For Kenyan guests">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="section-header mb-3">
                                <h6 class="text-primary">
                                    <i class="fas fa-bed me-2"></i>Room & Dates
                                </h6>
                            </div>
                            
                            <div class="mb-3">
                                <label for="room_name" class="form-label">Room Type *</label>
                                <select name="room_name" id="room_name" class="form-select" required>
                                    <option value="">Select a room</option>
                                    <?php foreach($rooms as $room): ?>
                                        <option value="<?php echo htmlspecialchars($room['room_name']); ?>" 
                                                data-price="<?php echo $room['base_price']; ?>"
                                                <?php echo ($room['room_name'] == $default_room) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($room['room_name']); ?> - KES <?php echo number_format($room['base_price']); ?>/night
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="check_in" class="form-label">Check-in Date *</label>
                                <input type="date" name="check_in" id="check_in" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="check_out" class="form-label">Check-out Date *</label>
                                <input type="date" name="check_out" id="check_out" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="adults" class="form-label">Adults</label>
                                        <select name="adults" id="adults" class="form-select">
                                            <option value="1">1</option>
                                            <option value="2" selected>2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="children" class="form-label">Children</label>
                                        <select name="children" id="children" class="form-select">
                                            <option value="0" selected>0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="discount" class="form-label">Discount (KES) - Optional</label>
                                <input type="number" name="discount" id="discount" class="form-control" value="0" min="0" step="100">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Price Summary Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card bg-light border-0" id="price_display" style="display: none;">
                                <div class="card-body">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-calculator me-2"></i>Price Summary
                                    </h6>
                                    <div id="price_details"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payment Information Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="section-header mb-3">
                                <h6 class="text-primary">
                                    <i class="fas fa-credit-card me-2"></i>Payment Information
                                </h6>
                            </div>
                            
                                                        <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="payment_status" class="form-label">Payment Status *</label>
                                        <select name="payment_status" id="payment_status" class="form-select" required>
                                            <option value="paid">Paid</option>
                                            <option value="pending">Pending</option>
                                            <option value="partial">Partial Payment</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="staff_notes" class="form-label">Staff Notes</label>
                                        <textarea name="staff_notes" id="staff_notes" class="form-control" rows="3" 
                                                  placeholder="Any special requests or notes..."></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="staff_notes" class="form-label">Staff Notes</label>
                                <textarea name="staff_notes" id="staff_notes" class="form-control" rows="3" 
                                          placeholder="Any special requests or notes..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Actions Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="section-header mb-3">
                                <h6 class="text-warning">
                                    <i class="fas fa-bolt me-2"></i>Quick Actions
                                </h6>
                            </div>
                            
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-info" onclick="setTodayCheckin()">
                                    <i class="fas fa-calendar-day me-1"></i>Today Check-in
                                </button>
                                <button type="button" class="btn btn-outline-info" onclick="setTomorrowCheckin()">
                                    <i class="fas fa-calendar-plus me-1"></i>Tomorrow Check-in
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="setOneNight()">
                                    <i class="fas fa-moon me-1"></i>One Night Stay
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="setTwoNights()">
                                    <i class="fas fa-moon me-1"></i>Two Nights Stay
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="row">
                        <div class="col-12">
                            <div class="d-grid">
                                                                 <button type="submit" class="btn btn-success btn-lg">
                                     <i class="fas fa-check me-2"></i>Create Booking
                                 </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.section-header {
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 10px;
}

.section-header h6 {
    margin-bottom: 0;
    font-weight: 600;
}

#price_display {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border: 1px solid #c3e6cb;
}

#price_details p {
    margin-bottom: 8px;
}

#price_details h5 {
    color: #155724;
    font-weight: 700;
}

.btn-group .btn {
    margin-right: 5px;
}

@media (max-width: 768px) {
    .btn-group {
        display: flex;
        flex-direction: column;
    }
    
    .btn-group .btn {
        margin-right: 0;
        margin-bottom: 5px;
    }
}
</style>

<script>
function calculatePrice() {
    const roomSelect = document.getElementById('room_name');
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const discount = parseFloat(document.getElementById('discount').value) || 0;
    
    if (roomSelect.value && checkIn && checkOut) {
        const selectedOption = roomSelect.options[roomSelect.selectedIndex];
        const pricePerNight = parseFloat(selectedOption.dataset.price);
        
        const startDate = new Date(checkIn);
        const endDate = new Date(checkOut);
        const days = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
        
        if (days > 0) {
            const subtotal = pricePerNight * days;
            const total = subtotal - discount;
            
            document.getElementById('price_details').innerHTML = `
                <p><strong>Room:</strong> ${selectedOption.text}</p>
                <p><strong>Duration:</strong> ${days} night(s)</p>
                <p><strong>Subtotal:</strong> KES ${subtotal.toLocaleString()}</p>
                <p><strong>Discount:</strong> KES ${discount.toLocaleString()}</p>
                <hr>
                <h5><strong>Total: KES ${total.toLocaleString()}</strong></h5>
            `;
            
            document.getElementById('price_display').style.display = 'block';
        }
    }
}

function setTodayCheckin() {
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('check_in').value = today;
    calculatePrice();
}

function setTomorrowCheckin() {
    const tomorrow = new Date();
    tomorrow.setDate(tomorrow.getDate() + 1);
    document.getElementById('check_in').value = tomorrow.toISOString().split('T')[0];
    calculatePrice();
}

function setOneNight() {
    const checkIn = document.getElementById('check_in').value;
    if (checkIn) {
        const checkInDate = new Date(checkIn);
        checkInDate.setDate(checkInDate.getDate() + 1);
        document.getElementById('check_out').value = checkInDate.toISOString().split('T')[0];
        calculatePrice();
    }
}

function setTwoNights() {
    const checkIn = document.getElementById('check_in').value;
    if (checkIn) {
        const checkInDate = new Date(checkIn);
        checkInDate.setDate(checkInDate.getDate() + 2);
        document.getElementById('check_out').value = checkInDate.toISOString().split('T')[0];
        calculatePrice();
    }
}

// Add event listeners
document.addEventListener('DOMContentLoaded', function() {
    const roomSelect = document.getElementById('room_name');
    const checkIn = document.getElementById('check_in');
    const checkOut = document.getElementById('check_out');
    const discount = document.getElementById('discount');
    
    roomSelect.addEventListener('change', calculatePrice);
    checkIn.addEventListener('change', calculatePrice);
    checkOut.addEventListener('change', calculatePrice);
    discount.addEventListener('input', calculatePrice);
    
    // Set minimum dates
    const today = new Date().toISOString().split('T')[0];
    checkIn.min = today;
    checkOut.min = today;
    
    // Initial price calculation
    calculatePrice();
});
</script>

<?php include '../includes/admin/footer.php'; ?>
