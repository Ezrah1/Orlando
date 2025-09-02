<?php
$page_title = 'Staff Booking';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';

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

// Handle booking submission
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $guest_name = mysqli_real_escape_string($con, $_POST['guest_name']);
    $guest_email = mysqli_real_escape_string($con, $_POST['guest_email']);
    $guest_phone = mysqli_real_escape_string($con, $_POST['guest_phone']);
    $guest_nationality = mysqli_real_escape_string($con, $_POST['guest_nationality']);
    $guest_id_number = mysqli_real_escape_string($con, $_POST['guest_id_number']);
    $room_name = mysqli_real_escape_string($con, $_POST['room_name']);
    $check_in = mysqli_real_escape_string($con, $_POST['check_in']);
    $check_out = mysqli_real_escape_string($con, $_POST['check_out']);
    $adults = (int)$_POST['adults'];
    $children = (int)$_POST['children'];
    $discount = (float)$_POST['discount'];
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
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
    $booking_sql = "INSERT INTO roombook (FName, Email, Phone, National, id_number, TRoom, cin, cout, nodays, payment_status, booking_ref, stat, staff_notes, created_by, created_at) 
                    VALUES ('$guest_name', '$guest_email', '$guest_phone', '$guest_nationality', '$guest_id_number', '$room_name', '$check_in', '$check_out', $days, '$payment_status', '$booking_ref', 'confirmed', '$staff_notes', " . $_SESSION['user_id'] . ", NOW())";
    
    if(mysqli_query($con, $booking_sql)) {
        $booking_id = mysqli_insert_id($con);
        
        // Update payment status in roombook table
        if($payment_status == 'paid') {
            $update_payment_sql = "UPDATE roombook SET payment_status = 'paid' WHERE id = $booking_id";
            mysqli_query($con, $update_payment_sql);
        }
        
        $success = "Booking created successfully! Reference: $booking_ref";
        
        // Redirect to print invoice
        header("Location: print.php?id=$booking_id");
        exit();
    } else {
        $error = "Booking failed. Please try again.";
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Staff Booking</h1>
    <p class="page-subtitle">Create walk-in bookings and manage on-site reservations</p>
</div>

<?php
// Display session alerts
display_session_alerts();

// Display success or error messages
if (isset($success)) {
    echo render_alert($success, 'success');
}
if (isset($error)) {
    echo render_alert($error, 'danger');
}
?>

<style>
        .booking-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .price-display {
            background: #28a745;
            color: white;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .payment-section {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
        .quick-actions {
            background: #fff3cd;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div id="wrapper">
        <nav class="navbar navbar-default top-navbar" role="navigation">
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".sidebar-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="home.php"> <?php echo $_SESSION["user"]; ?> </a>
            </div>

            <ul class="nav navbar-top-links navbar-right">
                <li class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#" aria-expanded="false">
                        <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-user">
                        <li><a href="usersetting.php"><i class="fa fa-user fa-fw"></i> User Profile</a>
                        </li>
                        <li><a href="settings.php"><i class="fa fa-gear fa-fw"></i> Settings</a>
                        </li>
                        <li class="divider"></li>
                        <li><a href="logout.php"><i class="fa fa-sign-out fa-fw"></i> Logout</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </nav>
        
        <div id="page-wrapper">
            <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h2>📝 Staff Booking - Walk-in Guest</h2>
                <p class="lead">Quick booking interface for reception staff</p>
                
                <?php if(isset($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if(isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form method="POST" class="booking-form">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>👤 Guest Information</h4>
                            <div class="form-group">
                                <label>Full Name *</label>
                                <input type="text" name="guest_name" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Email</label>
                                <input type="email" name="guest_email" class="form-control">
                            </div>
                            <div class="form-group">
                                <label>Phone Number *</label>
                                <input type="tel" name="guest_phone" class="form-control" placeholder="254700123456" required>
                            </div>
                            <div class="form-group">
                                <label>Nationality</label>
                                <select name="guest_nationality" class="form-control enhanced">
                                    <option value="Kenyan" selected>Kenyan</option>
                                    <option value="Non Kenyan">Non Kenyan</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>ID Number/Passport</label>
                                <input type="text" name="guest_id_number" class="form-control" placeholder="For Kenyan guests">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h4>🏨 Room & Dates</h4>
                            <div class="form-group">
                                <label>Room Type *</label>
                                <select name="room_name" id="room_name" class="form-control enhanced" required>
                                    <option value="">Select a room</option>
                                    <?php foreach($rooms as $room): ?>
                                        <option value="<?php echo $room['room_name']; ?>" data-price="<?php echo $room['base_price']; ?>"
                                                <?php echo ($room['room_name'] == $default_room) ? 'selected' : ''; ?>>
                                            <?php echo $room['room_name']; ?> - KES <?php echo number_format($room['base_price']); ?>/night
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Check-in Date *</label>
                                <input type="date" name="check_in" id="check_in" class="form-control" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Check-out Date *</label>
                                <input type="date" name="check_out" id="check_out" class="form-control" 
                                       value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Adults</label>
                                        <select name="adults" class="form-control enhanced">
                                            <option value="1">1</option>
                                            <option value="2" selected>2</option>
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Children</label>
                                        <select name="children" class="form-control enhanced">
                                            <option value="0" selected>0</option>
                                            <option value="1">1</option>
                                            <option value="2">2</option>
                                            <option value="3">3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Discount (KES) - Optional</label>
                                <input type="number" name="discount" id="discount" class="form-control" value="0" min="0" step="100">
                            </div>
                        </div>
                    </div>

                    <div class="price-display" id="price_display" style="display: none;">
                        <h4>💰 Price Summary</h4>
                        <div id="price_details"></div>
                    </div>

                    <div class="payment-section">
                        <h4>💳 Payment Information</h4>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Method *</label>
                                    <select name="payment_method" class="form-control" required>
                                        <option value="mpesa">M-Pesa</option>
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank">Bank Transfer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Payment Status *</label>
                                    <select name="payment_status" class="form-control" required>
                                        <option value="paid">Paid</option>
                                        <option value="pending">Pending</option>
                                        <option value="partial">Partial Payment</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Staff Notes</label>
                            <textarea name="staff_notes" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <h4>⚡ Quick Actions</h4>
                        <button type="button" class="btn btn-info" onclick="setTodayCheckin()">Today Check-in</button>
                        <button type="button" class="btn btn-info" onclick="setTomorrowCheckin()">Tomorrow Check-in</button>
                        <button type="button" class="btn btn-warning" onclick="setOneNight()">One Night Stay</button>
                        <button type="button" class="btn btn-warning" onclick="setTwoNights()">Two Nights Stay</button>
                    </div>

                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fa fa-check"></i> Create Booking & Print Invoice
                    </button>
                </form>
            </div>
        </div>
            </div>
        </div>
    </div>

    <script src="assets/js/assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/assets/js/bootstrap.min.js"></script>
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
        document.getElementById('room_name').addEventListener('change', calculatePrice);
        document.getElementById('check_in').addEventListener('change', calculatePrice);
        document.getElementById('check_out').addEventListener('change', calculatePrice);
        document.getElementById('discount').addEventListener('input', calculatePrice);

        // Set minimum dates
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('check_in').min = today;
        document.getElementById('check_out').min = today;
    </script>

<?php include '../includes/admin/footer.php'; ?>
