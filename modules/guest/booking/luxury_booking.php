<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include hotel settings for dynamic content
require_once '../../../includes/common/hotel_settings.php';

$page_title = 'Luxury Reservations - ' . get_hotel_info('name');
$page_description = 'Experience unparalleled luxury at ' . get_hotel_info('name') . '. Book your premium accommodation with our exclusive five-star booking experience.';

// Database connection
require_once '../../../db.php';

// Handle form submission
$error_message = '';
$success_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Debug: Log form submission
    error_log("Luxury booking form submitted with POST data: " . print_r($_POST, true));
    
    // Process booking data (same as before but with enhanced validation)
    $title = mysqli_real_escape_string($con, $_POST['title'] ?? '');
    $fname = mysqli_real_escape_string($con, $_POST['fname'] ?? '');
    $lname = mysqli_real_escape_string($con, $_POST['lname'] ?? '');
    $email = mysqli_real_escape_string($con, $_POST['email'] ?? '');
    $phone = mysqli_real_escape_string($con, $_POST['phone'] ?? '');
    $national = mysqli_real_escape_string($con, $_POST['national'] ?? '');
    $cin = mysqli_real_escape_string($con, $_POST['cin'] ?? '');
    $cout = mysqli_real_escape_string($con, $_POST['cout'] ?? '');
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method'] ?? 'cash');
    $special_requests = mysqli_real_escape_string($con, $_POST['special_requests'] ?? '');
    
    // Validate required fields
    if (empty($title) || empty($fname) || empty($lname) || empty($email) || empty($phone) || empty($cin) || empty($cout)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Calculate number of days
        try {
            $check_in = new DateTime($cin);
            $check_out = new DateTime($cout);
            $interval = $check_in->diff($check_out);
            $nodays = $interval->days;
            
            if ($nodays <= 0) {
                $error_message = "Check-out date must be after check-in date.";
            }
        } catch (Exception $e) {
            $error_message = "Invalid date format provided.";
        }
    }
    
    // Only process rooms if no validation errors so far
    if (empty($error_message)) {
        // Process selected rooms
    $rooms_selected_raw = $_POST['selected_rooms'] ?? '';
    $rooms_selected = [];
    
    // Decode JSON if it's a string, otherwise use as array
    if (is_string($rooms_selected_raw) && !empty($rooms_selected_raw)) {
        $rooms_selected = json_decode($rooms_selected_raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $rooms_selected = []; // Fallback to empty array if JSON decode fails
        }
    } elseif (is_array($rooms_selected_raw)) {
        $rooms_selected = $rooms_selected_raw;
    }
    
    $total_amount = 0;
    $booking_ids = [];
    
    if (!empty($rooms_selected) && is_array($rooms_selected)) {
        $booking_ref = 'LUX' . date('Ymd') . rand(1000, 9999);
        
        // Process each selected room
        foreach ($rooms_selected as $room_data) {
            // Ensure room_data is an array
            if (!is_array($room_data)) {
                continue; // Skip invalid room data
            }
            $room_id = isset($room_data['id']) ? (int)$room_data['id'] : 0;
            $adults = isset($room_data['adults']) ? (int)$room_data['adults'] : 2;
            $children = isset($room_data['children']) ? (int)$room_data['children'] : 0;
            $bed_preference = isset($room_data['bed_preference']) ? mysqli_real_escape_string($con, $room_data['bed_preference']) : 'Double';
            
            // Skip if room_id is invalid
            if ($room_id <= 0) {
                continue;
            }
            
            // Get room details
            $room_query = "SELECT * FROM named_rooms WHERE id = $room_id";
            $room_result = mysqli_query($con, $room_query);
            $room = mysqli_fetch_assoc($room_result);
            
            if ($room) {
                $room_total = $room['base_price'] * $nodays;
                $total_amount += $room_total;
                
                // Check availability
                $availability_query = "SELECT COUNT(*) as count FROM roombook 
                                      WHERE TRoom = '{$room['room_name']}' 
                                      AND (
                                          (cin <= '$cin' AND cout > '$cin') OR 
                                          (cin < '$cout' AND cout >= '$cout') OR
                                          (cin >= '$cin' AND cout <= '$cout') OR
                                          (cin <= '$cin' AND cout >= '$cout')
                                      )
                                      AND status NOT IN ('cancelled', 'completed')
                                      AND stat != 'cleared_for_rebooking'
                                      AND payment_status != 'failed'";
                $availability_result = mysqli_query($con, $availability_query);
                $availability_row = mysqli_fetch_assoc($availability_result);
                
                if ($availability_row['count'] == 0) {
                    // Room is available, insert booking
                    $notes = "Bed Preference: $bed_preference\nAdults: $adults\nChildren: $children\nSpecial Requests: $special_requests";
                    
                    $booking_sql = "INSERT INTO roombook (booking_ref, Title, FName, LName, Email, Phone, National, 
                                    TRoom, NRoom, Meal, cin, cout, nodays, stat, payment_status, created_at, staff_notes) 
                                    VALUES ('$booking_ref', '$title', '$fname', '$lname', '$email', '$phone', '$national',
                                    '{$room['room_name']}', 1, 'Bed & Breakfast', '$cin', '$cout', $nodays, 'pending', 'pending', NOW(), '$notes')";
                    
                    if (mysqli_query($con, $booking_sql)) {
                        $booking_ids[] = mysqli_insert_id($con);
                    } else {
                        $error_message = "Error creating booking: " . mysqli_error($con);
                        break;
                    }
                } else {
                    $error_message = "Room {$room['room_name']} is not available for selected dates.";
                    break;
                }
            }
        }
        
        if (empty($error_message) && !empty($booking_ids)) {
            // Calculate tax and final total
            $tax = $total_amount * 0.16;
            $final_total = $total_amount + $tax;
            
            // Store booking details in session (consistent with existing system)
            $_SESSION['booking_details'] = [
                'booking_ids' => $booking_ids,
                'booking_ref' => $booking_ref,
                'total_amount' => $final_total,
                'payment_method' => $payment_method,
                'rooms_count' => count($rooms_selected)
            ];
            
            // Debug: Log redirect information
            error_log("Booking successful. Redirecting with: booking_ref=$booking_ref, payment_method=$payment_method, amount=$final_total");
            
            // Check if headers have already been sent
            if (headers_sent($filename, $linenum)) {
                error_log("Headers already sent in $filename on line $linenum");
                echo "<script>console.log('Headers already sent, redirecting via JavaScript');</script>";
                
                // Use JavaScript redirect as fallback
                if ($payment_method == 'mpesa') {
                    echo "<script>window.location.href = '../payments/mpesa_payment.php?booking_ref=$booking_ref&amount=$final_total';</script>";
                } elseif ($payment_method == 'card') {
                    echo "<script>window.location.href = '../payments/card_payment.php?booking_ref=$booking_ref&amount=$final_total';</script>";
                } else {
                    echo "<script>alert('Cash payments are only available for walk-in guests. Please select M-Pesa or Card payment.'); history.back();</script>";
                }
                exit();
            }
            
            // Redirect to payment based on method
            if ($payment_method == 'mpesa') {
                // For M-Pesa payment, use booking_ref to get all rooms for this booking
                $redirect_url = "../payments/mpesa_payment.php?booking_ref=$booking_ref&amount=$final_total";
                error_log("Redirecting to M-Pesa: $redirect_url");
                header("Location: $redirect_url");
            } elseif ($payment_method == 'card') {
                // For card payment, redirect to card payment page
                $redirect_url = "../payments/card_payment.php?booking_ref=$booking_ref&amount=$final_total";
                error_log("Redirecting to Card Payment: $redirect_url");
                header("Location: $redirect_url");
            } else {
                // Cash payments are not allowed for online bookings
                $error_message = "Cash payments are only available for walk-in guests. Please select M-Pesa or Card payment.";
                error_log("Cash payment attempted for online booking: $booking_ref");
            }
            exit();
        }
        } else {
            $error_message = "Please select at least one room.";
        }
    } // End of validation check
}

// Get available rooms with amenities
$rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price ASC";
$rooms_result = mysqli_query($con, $rooms_query);
$rooms = [];
while($room = mysqli_fetch_assoc($rooms_result)) {
    $rooms[] = $room;
}

// Check for pre-selected room from URL parameters
$preselected_room = $_GET['room'] ?? '';
$preselected_price = isset($_GET['price']) && is_numeric($_GET['price']) ? (float)$_GET['price'] : 0;
$is_preselected = isset($_GET['selected']) && $_GET['selected'] === 'true';

// Check for quick booking with dates
$preselected_checkin = $_GET['checkin'] ?? '';
$preselected_checkout = $_GET['checkout'] ?? '';
$is_quick_booking = isset($_GET['quick']) && $_GET['quick'] === 'true';

// If we have a room but no price, get it from database
if ($preselected_room && $preselected_price == 0) {
    $price_query = "SELECT base_price FROM named_rooms WHERE room_name = '" . mysqli_real_escape_string($con, $preselected_room) . "'";
    $price_result = mysqli_query($con, $price_query);
    if ($price_row = mysqli_fetch_assoc($price_result)) {
        $preselected_price = (float)$price_row['base_price'];
    }
}

include('../../../includes/guest/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        /* Luxury Header */
        .luxury-hero {
            background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.7)), 
                        url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><rect width="1200" height="800" fill="%23c3cfe2"/><circle cx="300" cy="200" r="150" fill="%23f5f7fa" opacity="0.3"/><circle cx="900" cy="600" r="200" fill="%23ffffff" opacity="0.2"/></svg>');
            background-size: cover;
            background-position: center;
            min-height: 60vh;
            display: flex;
            align-items: center;
            position: relative;
        }

        .luxury-header {
            text-align: center;
            color: white;
            z-index: 2;
        }

        .luxury-badge {
            display: inline-block;
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 8px 24px;
            border-radius: 50px;
            margin-bottom: 20px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            color: #f8f9fa;
        }

        .luxury-title {
            font-family: 'Playfair Display', serif;
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.2;
        }

        .luxury-title .highlight {
            color: #ffd700;
            position: relative;
        }

        .luxury-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            opacity: 0.9;
            max-width: 600px;
            margin: 0 auto;
        }

        /* Booking Steps */
        .booking-steps-container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            margin: -80px auto 60px;
            max-width: 900px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            position: relative;
            z-index: 3;
        }

        .booking-steps {
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
        }

        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            flex: 1;
            position: relative;
        }

        .step-circle {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: #e9ecef;
            color: #6c757d;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 1.2rem;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }

        .step.active .step-circle {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: scale(1.1);
        }

        .step.completed .step-circle {
            background: #28a745;
            color: white;
        }

        .step-label {
            font-weight: 500;
            color: #495057;
            font-size: 0.9rem;
        }

        .step.active .step-label {
            color: #667eea;
            font-weight: 600;
        }

        .step-line {
            height: 2px;
            background: #e9ecef;
            flex: 1;
            margin: 0 20px;
            position: relative;
            top: -30px;
        }

        .step.active + .step-line {
            background: linear-gradient(90deg, #667eea 0%, #e9ecef 100%);
        }

        /* Booking Form Container */
        .booking-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
        }

        .booking-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 40px;
            margin-bottom: 60px;
        }

        /* Form Steps */
        .form-step {
            display: none;
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
        }

        .form-step.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .step-header {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e9ecef;
        }

        .step-title {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .step-description {
            color: #6c757d;
            font-size: 1.1rem;
        }

        /* Date Selection */
        .date-selection {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .date-group {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .date-group:hover {
            background: #e9ecef;
            transform: translateY(-2px);
        }

        .date-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 10px;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .date-input {
            border: none;
            background: transparent;
            font-size: 1.3rem;
            font-weight: 600;
            color: #2c3e50;
            text-align: center;
            width: 100%;
            outline: none;
        }

        .stay-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
            margin-top: 20px;
        }

        /* Room Cards */
        .rooms-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .room-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border: 3px solid transparent;
            cursor: pointer;
            position: relative;
        }

        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        }

        .room-card.selected {
            border-color: #667eea;
            transform: translateY(-5px);
            box-shadow: 0 20px 50px rgba(102, 126, 234, 0.3);
        }

        .room-image {
            height: 200px;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 3rem;
        }

        .room-details {
            padding: 25px;
        }

        .room-name {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .room-description {
            color: #6c757d;
            margin-bottom: 15px;
            line-height: 1.5;
        }

        .room-amenities {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 20px;
        }

        .amenity-tag {
            background: #f8f9fa;
            color: #495057;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }

        .room-price {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .price-amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }

        .price-period {
            color: #6c757d;
            font-size: 0.9rem;
        }

        .select-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .select-button:hover {
            transform: scale(1.05);
        }
        
        .room-actions {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        
        .availability-button {
            background: #17a2b8;
            color: white;
            border: 2px solid #17a2b8;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        .availability-button:hover {
            background: #138496;
            border-color: #138496;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
        }

        .selected-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: none;
        }

        .room-card.selected .selected-badge {
            display: block;
        }

        /* Guest Form */
        .guest-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        /* Booking Summary Sidebar */
        .booking-summary {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 15px 40px rgba(0,0,0,0.1);
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .summary-header {
            font-family: 'Playfair Display', serif;
            font-size: 1.5rem;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-dates {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .summary-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .summary-total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin-top: 20px;
        }

        .total-amount {
            font-size: 2rem;
            font-weight: 700;
        }

        /* Next Step Button */
        .btn-next-step {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-next-step:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-next-step:disabled {
            background: #e9ecef;
            color: #6c757d;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .btn-next-step:disabled:hover {
            transform: none;
            box-shadow: none;
        }

        .step-indicator {
            font-size: 0.8rem;
            color: #6c757d;
            text-align: center;
            margin-top: 10px;
        }

        /* Payment Method Cards */
        .payment-method-card {
            transition: all 0.3s ease;
        }

        .payment-method-card:hover {
            border-color: #667eea !important;
            background-color: #f8f9ff !important;
        }

        .payment-method-card input[type="radio"]:checked + div {
            color: #667eea;
        }

        .payment-method-card:has(input[type="radio"]:checked) {
            border-color: #667eea !important;
            background-color: #f8f9ff !important;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.15) !important;
        }

        /* Navigation Buttons */
        .form-navigation {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #e9ecef;
        }

        .btn {
            padding: 15px 30px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: #f8f9fa;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .btn-secondary:hover {
            background: #e9ecef;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .luxury-title {
                font-size: 2.5rem;
            }
            
            .booking-content {
                grid-template-columns: 1fr;
            }
            
            .booking-steps {
                flex-wrap: wrap;
                gap: 20px;
            }
            
            .step-line {
                display: none;
            }
            
            .rooms-grid {
                grid-template-columns: 1fr;
            }
            
            .guest-form {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="luxury-hero">
        <div class="container">
            <div class="luxury-header">
                <div class="luxury-badge">★★★★★ LUXURY EXPERIENCE</div>
                
                <?php if ($is_quick_booking && $preselected_room && $preselected_checkin): ?>
                    <h1 class="luxury-title">
                        Quick <span class="highlight">Booking</span>
                    </h1>
                    <p class="luxury-subtitle">
                        Perfect! We found availability for <strong><?php echo htmlspecialchars($preselected_room); ?></strong>
                    </p>
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 50px; margin-top: 20px; display: inline-block;">
                        <i class="fa fa-calendar"></i> <?php echo date('M j', strtotime($preselected_checkin)); ?> - <?php echo date('M j, Y', strtotime($preselected_checkout)); ?> • 
                        <i class="fa fa-bed"></i> <?php echo htmlspecialchars($preselected_room); ?><?php if($preselected_price > 0): ?> - KES <?php echo number_format($preselected_price); ?>/night<?php endif; ?>
                    </div>
                <?php elseif ($is_preselected && $preselected_room): ?>
                    <h1 class="luxury-title">
                        Continue Your <span class="highlight">Reservation</span>
                    </h1>
                    <p class="luxury-subtitle">
                        Complete your booking for <strong><?php echo htmlspecialchars($preselected_room); ?></strong>
                    </p>
                    <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); padding: 15px 25px; border-radius: 50px; margin-top: 20px; display: inline-block;">
                        <i class="fa fa-bed"></i> <?php echo htmlspecialchars($preselected_room); ?><?php if($preselected_price > 0): ?> - KES <?php echo number_format($preselected_price); ?>/night<?php endif; ?>
                    </div>
                <?php else: ?>
                    <h1 class="luxury-title">
                        Reserve Your <span class="highlight">Luxury Suite</span>
                    </h1>
                    <p class="luxury-subtitle">
                        Indulge in unparalleled elegance and world-class hospitality
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Booking Steps Indicator -->
        <div class="booking-steps-container">
            <div class="booking-steps">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-label">Select Dates</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-label">Choose Rooms</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-label">Guest Details</div>
                </div>
                <div class="step-line"></div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-label">Confirmation</div>
                </div>
            </div>
        </div>

        <div class="booking-container">
            <div class="booking-content">
                <!-- Main Form Content -->
                <div class="form-container">
                    <?php if(!empty($error_message)): ?>
                        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                            <strong><i class="fa fa-exclamation-triangle"></i> Error:</strong> <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if($_SERVER['REQUEST_METHOD'] == 'POST' && empty($error_message)): ?>
                        <div style="background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid #bee5eb;">
                            <strong><i class="fa fa-info-circle"></i> Processing:</strong> Your booking is being processed...
                        </div>
                    <?php endif; ?>

                    <form id="luxury-booking-form" method="post">
                        
                        <!-- Step 1: Date Selection -->
                        <div class="form-step active" id="step-1">
                            <div class="step-header">
                                <h2 class="step-title">When would you like to stay?</h2>
                                <p class="step-description">Select your check-in and check-out dates</p>
                            </div>

                            <div class="date-selection">
                                <div class="date-group">
                                    <div class="date-label">Check-in</div>
                                    <input type="date" name="cin" id="cin" class="date-input" 
                                           min="<?php echo date('Y-m-d'); ?>" 
                                           value="<?php echo date('Y-m-d'); ?>" required
                                           onchange="updateStaySummary(); checkRoomAvailability()">
                                </div>
                                <div class="date-group">
                                    <div class="date-label">Check-out</div>
                                    <input type="date" name="cout" id="cout" class="date-input" 
                                           min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                           value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required
                                           onchange="updateStaySummary(); checkRoomAvailability()">
                                </div>
                            </div>

                            <div class="stay-summary" id="stay-summary" style="display: none;">
                                <h4>Your Stay</h4>
                                <p id="stay-details"></p>
                            </div>

                            <div class="form-navigation">
                                <div></div>
                                <button type="button" class="btn btn-primary" onclick="nextStep(2)">
                                    Choose Rooms <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: Room Selection -->
                        <div class="form-step" id="step-2">
                            <div class="step-header">
                                <h2 class="step-title">Select Your Perfect Room</h2>
                                <p class="step-description">Choose from our collection of luxury accommodations</p>
                            </div>

                            <div class="rooms-grid">
                                <?php foreach($rooms as $room): ?>
                                <div class="room-card" data-room-id="<?php echo $room['id']; ?>" data-room-price="<?php echo $room['base_price']; ?>">
                                    <div class="selected-badge">Selected</div>
                                    <div class="room-image">
                                        <i class="fa fa-bed"></i>
                                    </div>
                                    <div class="room-details">
                                        <h3 class="room-name"><?php echo htmlspecialchars($room['room_name']); ?></h3>
                                        <p class="room-description">
                                            <?php echo $room['description'] ?? 'Elegant accommodation with premium amenities and stunning views.'; ?>
                                        </p>
                                        <div class="room-amenities">
                                            <span class="amenity-tag">Free WiFi</span>
                                            <span class="amenity-tag">Room Service</span>
                                            <span class="amenity-tag">Mini Bar</span>
                                            <span class="amenity-tag">City View</span>
                                        </div>
                                        <div class="room-price">
                                            <div>
                                                <div class="price-amount">KES <?php echo number_format($room['base_price']); ?></div>
                                                <div class="price-period">per night</div>
                                            </div>
                                            <div class="room-actions">
                                                <button type="button" class="select-button" onclick="toggleRoom(this)">
                                                    Select Room
                                                </button>
                                                <button type="button" class="availability-button" onclick="checkAvailability('<?php echo htmlspecialchars($room['room_name']); ?>')">
                                                    <i class="fa fa-calendar"></i> Check Availability
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>

                            <div class="form-navigation">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(1)">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(3)">
                                    Guest Details <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 3: Guest Details -->
                        <div class="form-step" id="step-3">
                            <div class="step-header">
                                <h2 class="step-title">Guest Information</h2>
                                <p class="step-description">Please provide your details for the reservation</p>
                            </div>

                            <div class="guest-form">
                                <div class="form-group">
                                    <label class="form-label">Title</label>
                                    <select name="title" class="form-select enhanced" required>
                                        <option value="">Select Title</option>
                                        <option value="Mr" selected>Mr</option>
                                        <option value="Mrs">Mrs</option>
                                        <option value="Ms">Ms</option>
                                        <option value="Dr">Dr</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">First Name</label>
                                    <input type="text" name="fname" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Last Name</label>
                                    <input type="text" name="lname" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Phone Number</label>
                                    <input type="tel" name="phone" class="form-input" required>
                                </div>

                                <div class="form-group">
                                    <label class="form-label">Nationality</label>
                                    <input type="text" name="national" class="form-input" value="Kenyan" required>
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Special Requests</label>
                                    <textarea name="special_requests" class="form-input" rows="3" 
                                              placeholder="Any special requests or preferences..."></textarea>
                                </div>

                                <div class="form-group" style="grid-column: 1 / -1;">
                                    <label class="form-label">Payment Method</label>
                                    <div class="payment-methods-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 15px;">
                                        <label class="payment-method-card" style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px; border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.3s ease;">
                                            <input type="radio" name="payment_method" value="mpesa" checked>
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: #2c3e50;">
                                                    <i class="fa fa-mobile" style="color: #28a745; margin-right: 8px;"></i>
                                                    M-Pesa
                                                </div>
                                                <div style="font-size: 0.8rem; color: #6c757d;">Mobile money payment</div>
                                            </div>
                                        </label>
                                        <label class="payment-method-card" style="display: flex; align-items: center; gap: 12px; cursor: pointer; padding: 15px; border: 2px solid #e9ecef; border-radius: 12px; transition: all 0.3s ease;">
                                            <input type="radio" name="payment_method" value="card">
                                            <div style="flex: 1;">
                                                <div style="font-weight: 600; color: #2c3e50;">
                                                    <i class="fa fa-credit-card" style="color: #667eea; margin-right: 8px;"></i>
                                                    Credit/Debit Card
                                                </div>
                                                <div style="font-size: 0.8rem; color: #6c757d;">Visa, Mastercard, etc.</div>
                                            </div>
                                        </label>
                                    </div>
                                    <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 8px; font-size: 0.85rem; color: #6c757d;">
                                        <i class="fa fa-info-circle"></i> Cash payments are only available for walk-in guests through our front desk.
                                    </div>
                                </div>
                            </div>

                            <div class="form-navigation">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(2)">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary" onclick="nextStep(4)">
                                    Review Booking <i class="fa fa-arrow-right"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Step 4: Confirmation -->
                        <div class="form-step" id="step-4">
                            <div class="step-header">
                                <h2 class="step-title">Confirm Your Reservation</h2>
                                <p class="step-description">Please review your booking details before confirming</p>
                            </div>

                            <div id="booking-review">
                                <!-- Booking details will be populated by JavaScript -->
                            </div>

                            <div class="form-navigation">
                                <button type="button" class="btn btn-secondary" onclick="prevStep(3)">
                                    <i class="fa fa-arrow-left"></i> Back
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fa fa-check"></i> Confirm Reservation
                                </button>
                            </div>
                        </div>

                        <!-- Hidden input for selected rooms -->
                        <input type="hidden" name="selected_rooms" id="selected-rooms-input">

                    </form>
                </div>

                <!-- Booking Summary Sidebar -->
                <div class="booking-summary">
                    <h3 class="summary-header">Booking Summary</h3>
                    
                    <div class="summary-dates" id="summary-dates">
                        <div style="text-align: center; color: #6c757d;">
                            Select your dates to continue
                        </div>
                    </div>

                    <div id="summary-rooms">
                        <!-- Selected rooms will appear here -->
                    </div>

                    <div class="summary-total" id="summary-total" style="display: none;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Subtotal:</span>
                            <span id="subtotal">KES 0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Tax (16%):</span>
                            <span id="tax">KES 0</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span>Total:</span>
                            <span class="total-amount" id="total">KES 0</span>
                        </div>
                    </div>
                    
                    <!-- Next Step Button -->
                    <div class="summary-next-step" id="summary-next-step" style="margin-top: 20px;">
                        <button type="button" class="btn-next-step" id="next-step-btn" onclick="proceedToNextStep()">
                            <i class="fa fa-arrow-right"></i>
                            <span id="next-step-text">Select Dates</span>
                        </button>
                        <div class="step-indicator" id="step-indicator" style="margin-top: 10px; text-align: center; font-size: 0.8rem; color: #6c757d;">
                            Step <span id="current-step-number">1</span> of 4
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        let currentStep = 1;
        let selectedRooms = [];
        let checkIn = '';
        let checkOut = '';
        let nights = 0;

        // Pre-selected room data from URL
        const preselectedRoom = '<?php echo addslashes($preselected_room); ?>';
        const preselectedPrice = <?php echo $preselected_price ?: 0; ?>;
        const isPreselected = <?php echo $is_preselected ? 'true' : 'false'; ?>;
        
        // Quick booking data
        const isQuickBooking = <?php echo $is_quick_booking ? 'true' : 'false'; ?>;
        const preselectedCheckin = '<?php echo $preselected_checkin; ?>';
        const preselectedCheckout = '<?php echo $preselected_checkout; ?>';

        $(document).ready(function() {
            // Handle quick booking with pre-filled dates
            if (isQuickBooking && preselectedCheckin && preselectedCheckout) {
                $('#cin').val(preselectedCheckin);
                $('#cout').val(preselectedCheckout);
                updateStaySummary();
            }
            
            // Initialize dates
            updateStaySummary();
            
            // Initialize next step button
            updateNextStepButton();
            
            // If room is preselected, auto-select it
            if ((isPreselected || isQuickBooking) && preselectedRoom) {
                // Find and select the preselected room
                const preselectedCard = $(`.room-card[data-room-id]`).filter(function() {
                    return $(this).find('.room-name').text().trim() === preselectedRoom;
                });
                
                if (preselectedCard.length > 0) {
                    // Auto-select the room
                    const roomId = preselectedCard.data('room-id');
                    selectedRooms.push({
                        id: roomId,
                        name: preselectedRoom,
                        price: preselectedPrice,
                        adults: 2,
                        children: 0,
                        bed_preference: 'Double'
                    });
                    
                    preselectedCard.addClass('selected');
                    preselectedCard.find('.select-button').text('Selected');
                    
                                updateRoomsSummary();
            calculateTotal();
            updateNextStepButton();
            
            // Show notification
            setTimeout(() => {
                showPreselectedNotification();
            }, 1000);
                }
            }
            
            // Date change handlers
            $('#cin, #cout').change(function() {
                updateStaySummary();
                calculateTotal();
                updateNextStepButton();
            });

            // Form validation before step changes
            $('#luxury-booking-form').submit(function(e) {
                if (selectedRooms.length === 0) {
                    e.preventDefault();
                    alert('Please select at least one room.');
                    return false;
                }
                
                // Update hidden input with selected rooms data
                $('#selected-rooms-input').val(JSON.stringify(selectedRooms));
            });
        });

        function showPreselectedNotification() {
            // Create notification
            const notification = $(`
                <div style="position: fixed; top: 120px; right: 20px; z-index: 9999; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px 25px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); max-width: 350px; animation: slideInRight 0.5s ease;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <i class="fa fa-check-circle" style="font-size: 1.5rem;"></i>
                        <div>
                            <div style="font-weight: 600; margin-bottom: 5px;">${preselectedRoom} Selected!</div>
                            <div style="opacity: 0.9; font-size: 0.9rem;">Ready to proceed with your booking</div>
                        </div>
                        <div onclick="$(this).parent().parent().remove()" style="cursor: pointer; opacity: 0.7; hover: opacity: 1; font-size: 1.2rem;">×</div>
                    </div>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Add CSS animation
            $('<style>').text(`
                @keyframes slideInRight {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `).appendTo('head');
        }

        function updateStaySummary() {
            checkIn = $('#cin').val();
            checkOut = $('#cout').val();
            
            if (checkIn && checkOut) {
                const checkInDate = new Date(checkIn);
                const checkOutDate = new Date(checkOut);
                nights = Math.ceil((checkOutDate - checkInDate) / (1000 * 60 * 60 * 24));
                
                if (nights > 0) {
                    $('#stay-summary').show();
                    $('#stay-details').text(`${nights} night${nights > 1 ? 's' : ''} • ${checkInDate.toLocaleDateString()} to ${checkOutDate.toLocaleDateString()}`);
                    
                    // Update summary sidebar
                    $('#summary-dates').html(`
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>Check-in:</strong>
                            <span>${checkInDate.toLocaleDateString()}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <strong>Check-out:</strong>
                            <span>${checkOutDate.toLocaleDateString()}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <strong>Nights:</strong>
                            <span>${nights}</span>
                        </div>
                    `);
                } else {
                    $('#stay-summary').hide();
                }
            }
        }

        // Check room availability when dates change (using homepage logic)
        function checkRoomAvailability() {
            const checkin = $('#cin').val();
            const checkout = $('#cout').val();
            
            if (!checkin || !checkout) {
                return;
            }
            
            // Show loading state
            $('.rooms-grid').html('<div class="loading-rooms" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #6c757d;"><i class="fa fa-spinner fa-spin"></i> Checking room availability...</div>');
            
            // Fetch availability for all rooms using the homepage API approach
            fetch('check_availability.php?checkin=' + encodeURIComponent(checkin) + '&checkout=' + encodeURIComponent(checkout))
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateRoomsDisplay(data);
                    } else {
                        console.error('Error checking availability:', data.error);
                        showAllRooms();
                    }
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                    showAllRooms();
                });
        }

        // Update rooms display based on availability
        function updateRoomsDisplay(data) {
            const roomsContainer = $('.rooms-grid');
            if (!roomsContainer.length) {
                return;
            }
            
            let roomsHTML = '';
            
            // Show available rooms first
            if (data.available_rooms && data.available_rooms.length > 0) {
                roomsHTML += `
                    <div class="availability-section" style="grid-column: 1 / -1; margin-bottom: 20px;">
                        <h3 style="color: #28a745; margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-check-circle"></i> Available Rooms (${data.available_rooms.length})
                        </h3>
                    </div>
                `;
                
                data.available_rooms.forEach(room => {
                    roomsHTML += createRoomCard(room, true, data.nights);
                });
            }
            
            // Show unavailable rooms with next availability
            if (data.unavailable_rooms && data.unavailable_rooms.length > 0) {
                roomsHTML += `
                    <div class="availability-section" style="grid-column: 1 / -1; margin-top: 30px; margin-bottom: 20px;">
                        <h3 style="color: #dc3545; margin: 0; display: flex; align-items: center; gap: 10px;">
                            <i class="fa fa-calendar-times"></i> Not Available - Next Availability (${data.unavailable_rooms.length})
                        </h3>
                    </div>
                `;
                
                data.unavailable_rooms.forEach(room => {
                    roomsHTML += createRoomCard(room, false, data.nights);
                });
            }
            
            if ((!data.available_rooms || data.available_rooms.length === 0) && 
                (!data.unavailable_rooms || data.unavailable_rooms.length === 0)) {
                roomsHTML = `
                    <div class="no-rooms" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #6c757d;">
                        <i class="fa fa-calendar-times"></i> No rooms found
                    </div>
                `;
            }
            
            roomsContainer.html(roomsHTML);
            
            // Show notification
            showAvailabilityNotification(data);
        }

        // Create room card HTML (simplified working version)
        function createRoomCard(room, isAvailable, nights) {
            const totalPrice = parseFloat(room.base_price) * nights;
            
            // Next availability info for unavailable rooms
            let nextAvailabilityInfo = '';
            if (!isAvailable) {
                if (room.available_periods && room.available_periods.length > 0) {
                    const periods = room.available_periods.slice(0, 2);
                    nextAvailabilityInfo = `
                        <div class="next-availability" style="background: #fff3cd; padding: 12px; border-radius: 8px; margin: 10px 0; color: #856404; font-size: 0.9rem;">
                            <div style="font-weight: 600; margin-bottom: 8px;">
                                <i class="fa fa-calendar"></i> Next Available Periods:
                            </div>
                    `;
                    
                    periods.forEach(period => {
                        nextAvailabilityInfo += `
                            <div style="margin-bottom: 6px; padding: 6px; background: rgba(255,255,255,0.7); border-radius: 4px;">
                                <strong>${period.formatted_start}</strong> - <strong>${period.formatted_end}</strong>
                                <span style="color: #28a745;">(${period.days} days)</span>
                                <button onclick="quickBookPeriod('${room.room_name}', '${period.start_date}', '${period.end_date}')" 
                                        style="float: right; background: #28a745; color: white; border: none; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                    Quick Book
                                </button>
                            </div>
                        `;
                    });
                    
                    if (room.available_periods.length > 2) {
                        nextAvailabilityInfo += `
                            <div style="text-align: center; margin-top: 8px;">
                                <button onclick="showMorePeriods('${room.room_name}')" style="background: none; border: 1px solid #856404; color: #856404; padding: 4px 12px; border-radius: 4px; font-size: 0.8rem; cursor: pointer;">
                                    +${room.available_periods.length - 2} more periods
                                </button>
                            </div>
                        `;
                    }
                    
                    nextAvailabilityInfo += '</div>';
                } else if (room.next_available_from) {
                    nextAvailabilityInfo = `
                        <div class="next-availability" style="background: #fff3cd; padding: 10px; border-radius: 8px; margin: 10px 0; color: #856404; font-size: 0.9rem;">
                            <i class="fa fa-calendar"></i> Next available: ${formatDate(room.next_available_from)}
                            ${room.days_until_available ? ` (in ${room.days_until_available} days)` : ''}
                        </div>
                    `;
                } else {
                    nextAvailabilityInfo = `
                        <div class="next-availability" style="background: #f8d7da; padding: 10px; border-radius: 8px; margin: 10px 0; color: #721c24; font-size: 0.9rem;">
                            <i class="fa fa-times-circle"></i> No availability in the next 90 days
                        </div>
                    `;
                }
            }
            
            const selectButton = isAvailable ? 
                `<button class="room-select-btn" onclick="toggleRoom(this)" 
                         data-room-id="${room.id}" 
                         data-room-name="${room.room_name}" 
                         data-room-price="${room.base_price}"
                         style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; width: 100%;">
                    <i class="fa fa-plus"></i> Select Room
                </button>` :
                `<button class="room-select-btn disabled" disabled
                         style="background: #e9ecef; color: #6c757d; border: none; padding: 12px 20px; border-radius: 8px; font-weight: 600; cursor: not-allowed; width: 100%;">
                    <i class="fa fa-ban"></i> Not Available
                </button>`;
            
            return `
                <div class="room-card ${!isAvailable ? 'unavailable' : ''}" data-room-id="${room.id}" data-room-name="${room.room_name}" data-room-price="${room.base_price}"
                     style="background: white; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); padding: 20px; transition: all 0.3s ease; ${!isAvailable ? 'opacity: 0.7;' : ''}">
                    <div class="room-header" style="margin-bottom: 15px;">
                        <h3 class="room-name" style="margin: 0 0 10px 0; color: #2c3e50;">${room.room_name}</h3>
                        <div class="room-price">
                            <div style="color: #667eea; font-size: 1.1rem; font-weight: 600;">KES ${parseFloat(room.base_price).toLocaleString()}/night</div>
                            <div style="color: #2c3e50; font-size: 0.9rem; font-weight: 700;">Total: KES ${totalPrice.toLocaleString()} (${nights} nights)</div>
                        </div>
                    </div>
                    <div class="room-amenities" style="display: flex; gap: 15px; margin-bottom: 15px; font-size: 0.9rem; color: #6c757d;">
                        <span><i class="fa fa-users"></i> ${room.capacity || '2'} guests</span>
                        <span><i class="fa fa-bed"></i> ${room.bed_type || 'Standard'}</span>
                        <span><i class="fa fa-expand"></i> ${room.room_size || 'Spacious'}</span>
                    </div>
                    ${nextAvailabilityInfo}
                    <div class="room-actions">
                        ${selectButton}
                    </div>
                </div>
            `;
        }
        
        // Quick book for a specific period
        function quickBookPeriod(roomName, startDate, endDate) {
            $('#cin').val(startDate);
            $('#cout').val(endDate);
            updateStaySummary();
            checkRoomAvailability();
            
            // Show notification
            const notification = $(`
                <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                    <i class="fa fa-check-circle"></i> Dates updated! ${roomName} is now available.
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(), 3000);
        }
        
        // Show more available periods
        function showMorePeriods(roomName) {
            checkAvailability(roomName);
        }
        
        // Homepage availability functions integrated (enhanced for date awareness)
        function checkAvailability(roomName) {
            // Show loading modal
            showAvailabilityModal(roomName, true);
            
            // Check if dates are already selected
            const checkin = $('#cin').val();
            const checkout = $('#cout').val();
            
            if (checkin && checkout) {
                // Dates are selected - check specific availability for those dates
                fetch(`check_availability.php?checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Find this specific room in the response
                            const roomData = [...(data.available_rooms || []), ...(data.unavailable_rooms || [])]
                                .find(room => room.room_name === roomName);
                            
                            if (roomData) {
                                // Convert to homepage API format for modal
                                const homepageFormat = {
                                    room_name: roomName,
                                    room_details: roomData,
                                    selected_dates: {
                                        checkin: checkin,
                                        checkout: checkout,
                                        nights: data.nights,
                                        is_available: data.available_rooms && 
                                            data.available_rooms.some(r => r.room_name === roomName)
                                    },
                                    available_periods: roomData.available_periods || []
                                };
                                showAvailabilityModal(roomName, false, homepageFormat);
                            } else {
                                showAvailabilityModal(roomName, false, { error: 'Room not found' });
                            }
                        } else {
                            // Fallback to general availability check
                            checkGeneralAvailability(roomName);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking specific availability:', error);
                        // Fallback to general availability check
                        checkGeneralAvailability(roomName);
                    });
            } else {
                // No dates selected - show general availability
                checkGeneralAvailability(roomName);
            }
        }
        
        // General availability check (original homepage logic)
        function checkGeneralAvailability(roomName) {
            fetch(`check_availability.php?room=${encodeURIComponent(roomName)}&days=30`)
                .then(response => response.json())
                .then(data => {
                    showAvailabilityModal(roomName, false, data);
                })
                .catch(error => {
                    console.error('Error checking availability:', error);
                    showAvailabilityModal(roomName, false, { error: 'Failed to check availability' });
                });
        }
        
        // Show availability modal (from homepage)
        function showAvailabilityModal(roomName, isLoading, availabilityData) {
            // Remove existing modal
            const existingModal = document.getElementById('availability-modal');
            if (existingModal) {
                existingModal.remove();
            }
            
            let modalContent = '';
            
            if (isLoading) {
                modalContent = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-calendar"></i> Checking Availability</h3>
                            <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="loading-spinner">
                                <i class="fa fa-spinner fa-spin"></i>
                                <p>Checking availability for ${roomName}...</p>
                            </div>
                        </div>
                    </div>
                `;
            } else if (availabilityData && availabilityData.error) {
                modalContent = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-exclamation-triangle"></i> Error</h3>
                            <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="error-message">
                                <p>${availabilityData.error}</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                const availablePeriods = availabilityData.available_periods || [];
                const roomDetails = availabilityData.room_details || {};
                const selectedDates = availabilityData.selected_dates;
                
                let periodsHtml = '';
                let headerText = '';
                let modalActions = '';
                
                if (selectedDates) {
                    // Dates are already selected - show specific availability info
                    headerText = `
                        <div class="selected-dates-info" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                            <h4 style="margin: 0 0 10px 0; color: #1565c0;">
                                <i class="fa fa-calendar-check"></i> Your Selected Dates
                            </h4>
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <strong>${formatDate(selectedDates.checkin)}</strong> to <strong>${formatDate(selectedDates.checkout)}</strong>
                                    <br><small>${selectedDates.nights} night${selectedDates.nights > 1 ? 's' : ''}</small>
                                </div>
                                <div style="text-align: right;">
                                    ${selectedDates.is_available ? 
                                        '<span style="color: #28a745; font-weight: 600;"><i class="fa fa-check-circle"></i> Available!</span>' :
                                        '<span style="color: #dc3545; font-weight: 600;"><i class="fa fa-times-circle"></i> Not Available</span>'
                                    }
                                </div>
                            </div>
                        </div>
                    `;
                    
                    if (selectedDates.is_available) {
                        periodsHtml = `
                            <div class="availability-period" style="border-color: #28a745;">
                                <div class="period-dates" style="color: #28a745;">
                                    <i class="fa fa-check-circle"></i> <strong>Room is available for your selected dates!</strong>
                                </div>
                                <div class="period-info">
                                    Total: KES ${(parseFloat(roomDetails.base_price || 0) * selectedDates.nights).toLocaleString()}
                                    <button class="btn-quick-book" onclick="selectAndProceed('${roomName}')">
                                        Select This Room
                                    </button>
                                </div>
                            </div>
                        `;
                        
                        modalActions = `
                            <button class="btn btn-primary" onclick="selectAndProceed('${roomName}')">
                                Select This Room & Continue
                            </button>
                            <button class="btn btn-secondary" onclick="closeAvailabilityModal()">
                                Close
                            </button>
                        `;
                    } else {
                        // Show alternative dates
                        if (availablePeriods.length > 0) {
                            periodsHtml = availablePeriods.map(period => `
                                <div class="availability-period">
                                    <div class="period-dates">
                                        <strong>${period.formatted_start}</strong> to <strong>${period.formatted_end}</strong>
                                    </div>
                                    <div class="period-info">
                                        ${period.days} day${period.days > 1 ? 's' : ''} available
                                        <button class="btn-quick-book" onclick="quickBook('${roomName}', '${period.start_date}', '${period.end_date}')">
                                            Use These Dates
                                        </button>
                                    </div>
                                </div>
                            `).join('');
                        } else {
                            periodsHtml = '<div class="no-availability">No availability in the next 90 days</div>';
                        }
                        
                        modalActions = `
                            <button class="btn btn-secondary" onclick="closeAvailabilityModal()">
                                Choose Different Dates
                            </button>
                        `;
                    }
                } else {
                    // No dates selected - show general availability (original behavior)
                    if (availablePeriods.length > 0) {
                        periodsHtml = availablePeriods.map(period => `
                            <div class="availability-period">
                                <div class="period-dates">
                                    <strong>${period.formatted_start}</strong> to <strong>${period.formatted_end}</strong>
                                </div>
                                <div class="period-info">
                                    ${period.days} day${period.days > 1 ? 's' : ''} available
                                    <button class="btn-quick-book" onclick="quickBook('${roomName}', '${period.start_date}', '${period.end_date}')">
                                        Quick Book
                                    </button>
                                </div>
                            </div>
                        `).join('');
                    } else {
                        periodsHtml = '<div class="no-availability">No availability in the next 30 days</div>';
                    }
                    
                    modalActions = `
                        <button class="btn btn-primary" onclick="bookRoom('${roomName}', ${roomDetails.base_price || 0})">
                            Book This Room
                        </button>
                        <button class="btn btn-secondary" onclick="closeAvailabilityModal()">
                            Check Other Dates
                        </button>
                    `;
                }
                
                modalContent = `
                    <div class="modal-content">
                        <div class="modal-header">
                            <h3><i class="fa fa-bed"></i> ${roomName} Availability</h3>
                            <span class="close" onclick="closeAvailabilityModal()">&times;</span>
                        </div>
                        <div class="modal-body">
                            <div class="room-info">
                                <div class="room-price">KES ${roomDetails.base_price ? parseInt(roomDetails.base_price).toLocaleString() : 'N/A'}/night</div>
                                <p>${roomDetails.description || 'Comfortable accommodation with modern amenities'}</p>
                            </div>
                            ${headerText}
                            <div class="availability-section">
                                <h4>${selectedDates ? (selectedDates.is_available ? 'Confirmation' : 'Alternative Dates') : 'Next Available Periods'}</h4>
                                <div class="availability-periods">
                                    ${periodsHtml}
                                </div>
                            </div>
                            <div class="modal-actions">
                                ${modalActions}
                            </div>
                        </div>
                    </div>
                `;
            }
            
            // Create modal
            const modal = document.createElement('div');
            modal.id = 'availability-modal';
            modal.className = 'availability-modal';
            modal.innerHTML = modalContent;
            
            document.body.appendChild(modal);
            
            // Add click outside to close
            modal.addEventListener('click', function(e) {
                if (e.target === modal) {
                    closeAvailabilityModal();
                }
            });
        }
        
        // Close availability modal
        function closeAvailabilityModal() {
            const modal = document.getElementById('availability-modal');
            if (modal) {
                modal.remove();
            }
        }
        
        // Quick book from availability modal
        function quickBook(roomName, startDate, endDate) {
            // Set the dates in the form
            $('#cin').val(startDate);
            $('#cout').val(endDate);
            updateStaySummary();
            checkRoomAvailability();
            
            // Close the modal
            closeAvailabilityModal();
            
            // Show notification
            const notification = $(`
                <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                    <i class="fa fa-check-circle"></i> Dates updated! ${roomName} is now available for ${startDate} to ${endDate}.
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(), 4000);
        }
        
        // Book room from modal
        function bookRoom(roomName, roomPrice) {
            // Close modal
            closeAvailabilityModal();
            
            // Scroll to room selection step
            nextStep(2);
            
            // Find and select the room if it exists in the current display
            const roomCards = document.querySelectorAll('.room-card');
            roomCards.forEach(card => {
                const cardRoomName = card.querySelector('.room-name')?.textContent;
                if (cardRoomName === roomName) {
                    const selectBtn = card.querySelector('.room-select-btn');
                    if (selectBtn && !selectBtn.disabled) {
                        selectBtn.click();
                    }
                }
            });
            
            // Show notification
            const notification = $(`
                <div style="position: fixed; top: 20px; right: 20px; background: #667eea; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                    <i class="fa fa-bed"></i> Ready to book ${roomName}! Select your dates and continue.
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(), 4000);
        }
        
        // Select room and proceed to next step (when dates are already selected)
        function selectAndProceed(roomName) {
            // Close modal
            closeAvailabilityModal();
            
            // Find and select the room
            const roomCards = document.querySelectorAll('.room-card');
            let roomSelected = false;
            
            roomCards.forEach(card => {
                const cardRoomName = card.querySelector('.room-name')?.textContent;
                if (cardRoomName === roomName) {
                    const selectBtn = card.querySelector('.select-button');
                    if (selectBtn) {
                        selectBtn.click();
                        roomSelected = true;
                    }
                }
            });
            
            if (roomSelected) {
                // Show success notification
                const notification = $(`
                    <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                        <i class="fa fa-check-circle"></i> ${roomName} selected! Ready to proceed to guest details.
                    </div>
                `);
                
                $('body').append(notification);
                setTimeout(() => notification.fadeOut(), 3000);
                
                // Auto-proceed to next step after a short delay
                setTimeout(() => {
                    if (currentStep === 2) {
                        nextStep(3);
                    }
                }, 1500);
            } else {
                // Show error notification
                const notification = $(`
                    <div style="position: fixed; top: 20px; right: 20px; background: #dc3545; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                        <i class="fa fa-exclamation-triangle"></i> Could not select ${roomName}. Please try selecting it manually.
                    </div>
                `);
                
                $('body').append(notification);
                setTimeout(() => notification.fadeOut(), 4000);
            }
        }
        
        // Auto-filter rooms for selected dates (called after Step 1)
        function filterRoomsForSelectedDates() {
            const checkin = $('#cin').val();
            const checkout = $('#cout').val();
            
            if (!checkin || !checkout) {
                return;
            }
            
            // Show loading state in Step 2
            const step2Container = $('#step-2 .rooms-grid');
            if (step2Container.length) {
                step2Container.prepend(`
                    <div class="availability-filter-notice" style="grid-column: 1 / -1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 25px; text-align: center;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 15px;">
                            <i class="fa fa-spinner fa-spin" style="font-size: 1.5rem;"></i>
                            <div>
                                <h4 style="margin: 0; font-size: 1.2rem;">🎯 Filtering Rooms for Your Dates</h4>
                                <p style="margin: 5px 0 0 0; opacity: 0.9;">Checking availability from ${formatDate(checkin)} to ${formatDate(checkout)}</p>
                            </div>
                        </div>
                    </div>
                `);
            }
            
            // Fetch availability for selected dates
            fetch(`check_availability.php?checkin=${encodeURIComponent(checkin)}&checkout=${encodeURIComponent(checkout)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateStep2WithAvailability(data);
                    } else {
                        showAvailabilityError(data.error);
                    }
                })
                .catch(error => {
                    console.error('Error filtering rooms:', error);
                    showAvailabilityError('Unable to check room availability');
                });
        }
        
        // Update Step 2 with availability-filtered rooms and AI suggestions
        function updateStep2WithAvailability(data) {
            const step2Container = $('#step-2 .rooms-grid');
            if (!step2Container.length) return;
            
            // Remove loading notice
            $('.availability-filter-notice').remove();
            
            let availabilityNotice = '';
            
            if (data.available_rooms && data.available_rooms.length > 0) {
                // Show success notice with available rooms
                availabilityNotice = `
                    <div class="availability-success-notice" style="grid-column: 1 / -1; background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <i class="fa fa-check-circle" style="font-size: 2rem;"></i>
                                <div>
                                    <h4 style="margin: 0; font-size: 1.3rem;">🎉 Great News!</h4>
                                    <p style="margin: 5px 0 0 0; opacity: 0.9;">
                                        ${data.available_rooms.length} room${data.available_rooms.length > 1 ? 's' : ''} available for ${data.nights} night${data.nights > 1 ? 's' : ''}
                                        from ${formatDate(data.checkin)} to ${formatDate(data.checkout)}
                                    </p>
                                </div>
                            </div>
                            <div style="text-align: right; font-size: 1.1rem;">
                                <strong>KES ${calculateLowestPrice(data.available_rooms, data.nights).toLocaleString()}+</strong>
                                <br><small style="opacity: 0.8;">Starting from</small>
                            </div>
                        </div>
                    </div>
                `;
                
                // Hide unavailable rooms, show only available ones
                hideUnavailableRooms(data.available_rooms);
                
            } else {
                // No rooms available - show AI-powered suggestions
                const suggestions = generateAISuggestions(data);
                availabilityNotice = `
                    <div class="availability-suggestions-notice" style="grid-column: 1 / -1; background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%); color: white; padding: 25px; border-radius: 15px; margin-bottom: 25px;">
                        <div style="display: flex; align-items: flex-start; gap: 20px;">
                            <i class="fa fa-lightbulb" style="font-size: 2.5rem; margin-top: 5px;"></i>
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 15px 0; font-size: 1.4rem;">🤖 AI Booking Assistant</h4>
                                <p style="margin: 0 0 20px 0; opacity: 0.95; font-size: 1.1rem;">
                                    No rooms available for ${formatDate(data.checkin)} - ${formatDate(data.checkout)}, but I have smart suggestions:
                                </p>
                                ${suggestions}
                            </div>
                        </div>
                    </div>
                `;
                
                // Show all rooms but mark them as unavailable
                markAllRoomsUnavailable();
            }
            
            step2Container.prepend(availabilityNotice);
        }
        
        // Generate AI-powered booking suggestions
        function generateAISuggestions(data) {
            const checkinDate = new Date(data.checkin);
            const checkoutDate = new Date(data.checkout);
            const nights = data.nights;
            const dayOfWeek = checkinDate.getDay(); // 0 = Sunday, 6 = Saturday
            const isWeekend = (dayOfWeek === 5 || dayOfWeek === 6); // Friday or Saturday
            const isHoliday = checkHolidayPeriod(checkinDate);
            
            let suggestions = '';
            
            // Analyze booking pattern and provide suggestions
            if (nights === 1) {
                suggestions += `
                    <div class="ai-suggestion" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 10px 0;"><i class="fa fa-calendar-plus"></i> Extend Your Stay</h5>
                        <p style="margin: 0; opacity: 0.9;">Consider extending to 2-3 nights for better availability and potential discounts!</p>
                    </div>
                `;
            }
            
            if (isWeekend) {
                suggestions += `
                    <div class="ai-suggestion" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 10px 0;"><i class="fa fa-calendar-o"></i> Weekend Alternative</h5>
                        <p style="margin: 0; opacity: 0.9;">Weekends are popular! Try weekdays (Monday-Thursday) for better rates and availability.</p>
                    </div>
                `;
            }
            
            if (isHoliday) {
                suggestions += `
                    <div class="ai-suggestion" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 10px 0;"><i class="fa fa-star"></i> Holiday Period Detected</h5>
                        <p style="margin: 0; opacity: 0.9;">This is a busy holiday period. Book 2-3 weeks earlier or try dates just before/after.</p>
                    </div>
                `;
            }
            
            // Alternative date suggestions
            const altDates = generateAlternativeDates(checkinDate, checkoutDate, nights);
            if (altDates.length > 0) {
                suggestions += `
                    <div class="ai-suggestion" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px; margin-bottom: 15px;">
                        <h5 style="margin: 0 0 10px 0;"><i class="fa fa-magic"></i> Smart Date Alternatives</h5>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                `;
                
                altDates.forEach(altDate => {
                    suggestions += `
                        <button onclick="quickBookAlternative('${altDate.checkin}', '${altDate.checkout}')" 
                                style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 12px; border-radius: 20px; cursor: pointer; font-size: 0.9rem; transition: all 0.3s ease;"
                                onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                                onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                            ${altDate.label}
                        </button>
                    `;
                });
                
                suggestions += `
                        </div>
                    </div>
                `;
            }
            
            // Flexible dates suggestion
            suggestions += `
                <div class="ai-suggestion" style="background: rgba(255,255,255,0.1); padding: 15px; border-radius: 10px;">
                    <h5 style="margin: 0 0 10px 0;"><i class="fa fa-refresh"></i> Flexible Dates Tip</h5>
                    <p style="margin: 0 0 10px 0; opacity: 0.9;">Get better deals with flexible dates! Use our "Check Availability" on individual rooms for more options.</p>
                    <button onclick="enableFlexibleMode()" 
                            style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.4); color: white; padding: 10px 15px; border-radius: 25px; cursor: pointer; font-weight: 600;"
                            onmouseover="this.style.background='rgba(255,255,255,0.3)'"
                            onmouseout="this.style.background='rgba(255,255,255,0.2)'">
                        Enable Flexible Booking Mode
                    </button>
                </div>
            `;
            
            return suggestions;
        }
        
        // Helper functions for AI suggestions
        function checkHolidayPeriod(date) {
            const month = date.getMonth();
            const day = date.getDate();
            
            // Common holiday periods in Kenya
            return (
                (month === 11 && day >= 20) || // Christmas period
                (month === 0 && day <= 10) ||  // New Year period
                (month === 3 && day >= 15 && day <= 25) || // Easter period (approximate)
                (month === 7 && day >= 10 && day <= 20)    // August holidays
            );
        }
        
        function generateAlternativeDates(checkin, checkout, nights) {
            const alternatives = [];
            const baseDate = new Date(checkin);
            
            // One week earlier
            const weekEarlier = new Date(baseDate);
            weekEarlier.setDate(weekEarlier.getDate() - 7);
            const weekEarlierEnd = new Date(weekEarlier);
            weekEarlierEnd.setDate(weekEarlierEnd.getDate() + nights);
            
            alternatives.push({
                checkin: weekEarlier.toISOString().split('T')[0],
                checkout: weekEarlierEnd.toISOString().split('T')[0],
                label: '1 week earlier'
            });
            
            // One week later
            const weekLater = new Date(baseDate);
            weekLater.setDate(weekLater.getDate() + 7);
            const weekLaterEnd = new Date(weekLater);
            weekLaterEnd.setDate(weekLaterEnd.getDate() + nights);
            
            alternatives.push({
                checkin: weekLater.toISOString().split('T')[0],
                checkout: weekLaterEnd.toISOString().split('T')[0],
                label: '1 week later'
            });
            
            // Next weekend (if not already weekend)
            if (baseDate.getDay() !== 5 && baseDate.getDay() !== 6) {
                const nextFriday = new Date(baseDate);
                const daysUntilFriday = (5 - baseDate.getDay() + 7) % 7;
                nextFriday.setDate(nextFriday.getDate() + daysUntilFriday);
                const nextSunday = new Date(nextFriday);
                nextSunday.setDate(nextSunday.getDate() + 2);
                
                alternatives.push({
                    checkin: nextFriday.toISOString().split('T')[0],
                    checkout: nextSunday.toISOString().split('T')[0],
                    label: 'Next weekend'
                });
            }
            
            return alternatives;
        }
        
        function calculateLowestPrice(rooms, nights) {
            if (!rooms || rooms.length === 0) return 0;
            const lowestPriceRoom = rooms.reduce((min, room) => 
                parseFloat(room.base_price) < parseFloat(min.base_price) ? room : min
            );
            return parseFloat(lowestPriceRoom.base_price) * nights;
        }
        
        function hideUnavailableRooms(availableRooms) {
            const allRoomCards = $('#step-2 .room-card');
            const availableRoomNames = availableRooms.map(room => room.room_name);
            
            allRoomCards.each(function() {
                const roomName = $(this).find('.room-name').text().trim();
                if (!availableRoomNames.includes(roomName)) {
                    $(this).slideUp(300).addClass('filtered-out');
                } else {
                    $(this).slideDown(300).removeClass('filtered-out')
                           .css('border', '2px solid #28a745')
                           .css('box-shadow', '0 5px 20px rgba(40, 167, 69, 0.2)');
                }
            });
        }
        
        function markAllRoomsUnavailable() {
            const allRoomCards = $('#step-2 .room-card');
            allRoomCards.each(function() {
                const selectBtn = $(this).find('.select-button');
                selectBtn.prop('disabled', true)
                        .text('Not Available for Selected Dates')
                        .css('background', '#dc3545')
                        .css('cursor', 'not-allowed');
                
                $(this).css('opacity', '0.6')
                       .css('border', '2px solid #dc3545');
            });
        }
        
        // Quick book alternative dates
        function quickBookAlternative(checkin, checkout) {
            $('#cin').val(checkin);
            $('#cout').val(checkout);
            updateStaySummary();
            
            // Show loading and re-filter
            $('.availability-suggestions-notice').slideUp(300);
            setTimeout(() => {
                filterRoomsForSelectedDates();
            }, 400);
            
            // Show notification
            const notification = $(`
                <div style="position: fixed; top: 20px; right: 20px; background: #17a2b8; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                    <i class="fa fa-calendar-check"></i> Dates updated! Checking new availability...
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(), 3000);
        }
        
        // Enable flexible booking mode
        function enableFlexibleMode() {
            // Remove availability notices
            $('.availability-suggestions-notice, .availability-success-notice').slideUp(300);
            
            // Reset all rooms to available
            const allRoomCards = $('#step-2 .room-card');
            allRoomCards.each(function() {
                const selectBtn = $(this).find('.select-button');
                selectBtn.prop('disabled', false)
                        .text('Select Room')
                        .css('background', 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)')
                        .css('cursor', 'pointer');
                
                $(this).css('opacity', '1')
                       .css('border', '1px solid #e9ecef')
                       .slideDown(300);
            });
            
            // Show flexible mode notice
            $('#step-2 .rooms-grid').prepend(`
                <div class="flexible-mode-notice" style="grid-column: 1 / -1; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 15px; margin-bottom: 25px;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fa fa-unlock" style="font-size: 2rem;"></i>
                        <div>
                            <h4 style="margin: 0; font-size: 1.3rem;">🔄 Flexible Booking Mode Enabled</h4>
                            <p style="margin: 5px 0 0 0; opacity: 0.9;">All rooms are now selectable. Use "Check Availability" on each room to find the best dates!</p>
                        </div>
                    </div>
                </div>
            `);
            
            // Show notification
            const notification = $(`
                <div style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000;">
                    <i class="fa fa-unlock"></i> Flexible mode enabled! All rooms are now available for selection.
                </div>
            `);
            
            $('body').append(notification);
            setTimeout(() => notification.fadeOut(), 4000);
        }
        
        // Show all rooms (fallback when availability check fails)
        function showAllRooms() {
            // Default display - show all rooms as available
            fetch('../../../modules/guest/booking/get_all_rooms.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const nights = parseInt($('#cin').val() && $('#cout').val() ? 
                            Math.ceil((new Date($('#cout').val()) - new Date($('#cin').val())) / (1000 * 60 * 60 * 24)) : 1);
                        
                        let roomsHTML = '';
                        if (data.rooms && data.rooms.length > 0) {
                            roomsHTML += `
                                <div class="availability-section" style="grid-column: 1 / -1; margin-bottom: 20px;">
                                    <h3 style="color: #2c3e50; margin: 0; display: flex; align-items: center; gap: 10px;">
                                        <i class="fa fa-home"></i> All Rooms (${data.rooms.length})
                                    </h3>
                                    <p style="margin: 5px 0; color: #6c757d; font-size: 0.9rem;">Select dates to check availability</p>
                                </div>
                            `;
                            
                            data.rooms.forEach(room => {
                                roomsHTML += createRoomCard(room, true, nights);
                            });
                        } else {
                            roomsHTML = `
                                <div class="no-rooms" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #6c757d;">
                                    <i class="fa fa-home"></i> No rooms available
                                </div>
                            `;
                        }
                        
                        $('.rooms-grid').html(roomsHTML);
                    }
                })
                .catch(error => {
                    console.error('Error loading rooms:', error);
                    $('.rooms-grid').html(`
                        <div class="error-message" style="grid-column: 1 / -1; text-align: center; padding: 40px; color: #dc3545;">
                            <i class="fa fa-exclamation-triangle"></i> Unable to load rooms. Please refresh the page.
                        </div>
                    `);
                });
        }

        // Show availability notification
        function showAvailabilityNotification(data) {
            // Remove existing notification
            $('.availability-notification').remove();
            
            const notification = $(`
                <div class="availability-notification" style="position: fixed; top: 20px; right: 20px; background: #28a745; color: white; padding: 15px 20px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.2); z-index: 1000; max-width: 300px;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fa fa-check-circle"></i>
                        <div>
                            <strong>${data.total_available} rooms available</strong><br>
                            <small>${data.nights} night${data.nights > 1 ? 's' : ''} • ${formatDate(data.checkin)} to ${formatDate(data.checkout)}</small>
                        </div>
                    </div>
                    <button onclick="$(this).parent().fadeOut()" style="position: absolute; top: 5px; right: 8px; background: none; border: none; color: white; font-size: 16px; cursor: pointer;">&times;</button>
                </div>
            `);
            
            $('body').append(notification);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                notification.fadeOut();
            }, 5000);
        }

        // Format date for display
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                weekday: 'short', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        function nextStep(step) {
            // Validate current step
            if (currentStep === 1 && (!checkIn || !checkOut || nights <= 0)) {
                alert('Please select valid check-in and check-out dates.');
                return;
            }
            
            // Auto-filter rooms when moving from Step 1 to Step 2
            if (currentStep === 1 && step === 2 && checkIn && checkOut) {
                setTimeout(() => {
                    filterRoomsForSelectedDates();
                }, 500); // Small delay to let the step transition complete
            }
            
            if (currentStep === 2 && selectedRooms.length === 0) {
                alert('Please select at least one room.');
                return;
            }

            if (currentStep === 3) {
                // Validate guest details
                const requiredFields = ['title', 'fname', 'lname', 'email', 'phone', 'national'];
                let isValid = true;
                
                requiredFields.forEach(field => {
                    const value = $(`[name="${field}"]`).val();
                    if (!value || value.trim() === '') {
                        isValid = false;
                    }
                });
                
                if (!isValid) {
                    alert('Please fill in all required fields.');
                    return;
                }
                
                // Populate review step
                populateBookingReview();
            }
            
            // Hide current step
            $(`.form-step`).removeClass('active');
            $(`.step`).removeClass('active completed');
            
            // Show next step
            $(`#step-${step}`).addClass('active');
            $(`.step[data-step="${step}"]`).addClass('active');
            
            // Mark previous steps as completed
            for (let i = 1; i < step; i++) {
                $(`.step[data-step="${i}"]`).addClass('completed');
            }
            
            currentStep = step;
            updateNextStepButton();
        }

        function proceedToNextStep() {
            let nextStepNumber = currentStep + 1;
            
            // Determine next step based on current step
            if (currentStep === 1 && checkIn && checkOut && nights > 0) {
                nextStep(2);
            } else if (currentStep === 2 && selectedRooms.length > 0) {
                nextStep(3);
            } else if (currentStep === 3) {
                // Validate guest details first
                const requiredFields = ['title', 'fname', 'lname', 'email', 'phone', 'national'];
                let isValid = true;
                
                requiredFields.forEach(field => {
                    const value = $(`[name="${field}"]`).val();
                    if (!value || value.trim() === '') {
                        isValid = false;
                    }
                });
                
                if (isValid) {
                    nextStep(4);
                } else {
                    alert('Please fill in all required fields before proceeding.');
                }
            } else if (currentStep === 4) {
                // Ensure selected rooms data is set before submission
                if (selectedRooms.length === 0) {
                    alert('Please select at least one room before confirming.');
                    return;
                }
                
                // Update hidden input with selected rooms data
                $('#selected-rooms-input').val(JSON.stringify(selectedRooms));
                
                console.log('Submitting form with rooms:', selectedRooms);
                
                // Submit the form
                $('#luxury-booking-form').submit();
            } else {
                // Show validation message based on current step
                if (currentStep === 1) {
                    alert('Please select your check-in and check-out dates.');
                } else if (currentStep === 2) {
                    alert('Please select at least one room.');
                }
            }
        }

        function updateNextStepButton() {
            const nextStepBtn = $('#next-step-btn');
            const stepText = $('#next-step-text');
            const stepNumber = $('#current-step-number');
            
            // Update step number
            stepNumber.text(currentStep);
            
            // Update button text and state
            switch (currentStep) {
                case 1:
                    stepText.text('Choose Rooms');
                    nextStepBtn.prop('disabled', !checkIn || !checkOut || nights <= 0);
                    break;
                case 2:
                    stepText.text('Guest Details');
                    nextStepBtn.prop('disabled', selectedRooms.length === 0);
                    break;
                case 3:
                    stepText.text('Review Booking');
                    nextStepBtn.prop('disabled', false);
                    break;
                case 4:
                    stepText.html('<i class="fa fa-check"></i> Confirm Booking');
                    nextStepBtn.prop('disabled', false);
                    break;
                default:
                    stepText.text('Next Step');
                    nextStepBtn.prop('disabled', false);
            }
        }

        function prevStep(step) {
            $(`.form-step`).removeClass('active');
            $(`.step`).removeClass('active completed');
            
            $(`#step-${step}`).addClass('active');
            $(`.step[data-step="${step}"]`).addClass('active');
            
            for (let i = 1; i < step; i++) {
                $(`.step[data-step="${i}"]`).addClass('completed');
            }
            
            currentStep = step;
        }

        function toggleRoom(button) {
            const roomCard = button.closest('.room-card');
            const roomId = roomCard.dataset.roomId;
            const roomPrice = parseFloat(roomCard.dataset.roomPrice);
            const roomName = roomCard.querySelector('.room-name').textContent;
            
            if (roomCard.classList.contains('selected')) {
                // Deselect room
                roomCard.classList.remove('selected');
                button.textContent = 'Select Room';
                
                // Remove from selectedRooms array
                selectedRooms = selectedRooms.filter(room => room.id !== roomId);
            } else {
                // Select room
                roomCard.classList.add('selected');
                button.textContent = 'Selected';
                
                // Add to selectedRooms array
                selectedRooms.push({
                    id: roomId,
                    name: roomName,
                    price: roomPrice,
                    adults: 2,
                    children: 0,
                    bed_preference: 'Double'
                });
            }
            
            updateRoomsSummary();
            calculateTotal();
            updateNextStepButton();
        }

        function updateRoomsSummary() {
            const summaryRooms = $('#summary-rooms');
            
            if (selectedRooms.length === 0) {
                summaryRooms.html('<div style="text-align: center; color: #6c757d; padding: 20px;">No rooms selected</div>');
                return;
            }
            
            let html = '<h4 style="margin-bottom: 15px;">Selected Rooms</h4>';
            selectedRooms.forEach(room => {
                html += `
                    <div class="summary-item">
                        <div>
                            <strong>${room.name}</strong><br>
                            <small>${room.adults} adults, ${room.children} children</small>
                        </div>
                        <div style="text-align: right;">
                            <strong>KES ${room.price.toLocaleString()}</strong><br>
                            <small>per night</small>
                        </div>
                    </div>
                `;
            });
            
            summaryRooms.html(html);
        }

        function calculateTotal() {
            if (selectedRooms.length === 0 || nights === 0) {
                $('#summary-total').hide();
                return;
            }
            
            let subtotal = 0;
            selectedRooms.forEach(room => {
                subtotal += room.price * nights;
            });
            
            const tax = subtotal * 0.16;
            const total = subtotal + tax;
            
            $('#subtotal').text('KES ' + subtotal.toLocaleString());
            $('#tax').text('KES ' + tax.toLocaleString());
            $('#total').text('KES ' + total.toLocaleString());
            $('#summary-total').show();
        }

        function populateBookingReview() {
            const guest = {
                title: $('[name="title"]').val(),
                fname: $('[name="fname"]').val(),
                lname: $('[name="lname"]').val(),
                email: $('[name="email"]').val(),
                phone: $('[name="phone"]').val(),
                national: $('[name="national"]').val(),
                payment: $('[name="payment_method"]:checked').val(),
                requests: $('[name="special_requests"]').val()
            };
            
            let html = `
                <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; margin-bottom: 25px;">
                    <h4 style="margin-bottom: 15px;">Guest Information</h4>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                        <div><strong>Name:</strong> ${guest.title} ${guest.fname} ${guest.lname}</div>
                        <div><strong>Email:</strong> ${guest.email}</div>
                        <div><strong>Phone:</strong> ${guest.phone}</div>
                        <div><strong>Nationality:</strong> ${guest.national}</div>
                        <div><strong>Payment:</strong> ${guest.payment.toUpperCase()}</div>
                    </div>
            `;
            
            if (guest.requests) {
                html += `<div style="margin-top: 15px;"><strong>Special Requests:</strong><br>${guest.requests}</div>`;
            }
            
            html += '</div>';
            
            // Add stay details
            html += `
                <div style="background: #f8f9fa; padding: 25px; border-radius: 15px; margin-bottom: 25px;">
                    <h4 style="margin-bottom: 15px;">Stay Details</h4>
                    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px;">
                        <div><strong>Check-in:</strong><br>${new Date(checkIn).toLocaleDateString()}</div>
                        <div><strong>Check-out:</strong><br>${new Date(checkOut).toLocaleDateString()}</div>
                        <div><strong>Nights:</strong><br>${nights}</div>
                    </div>
                </div>
            `;
            
            // Add room details
            html += `
                <div style="background: #f8f9fa; padding: 25px; border-radius: 15px;">
                    <h4 style="margin-bottom: 15px;">Room Selection</h4>
            `;
            
            selectedRooms.forEach(room => {
                html += `
                    <div style="border-bottom: 1px solid #dee2e6; padding-bottom: 15px; margin-bottom: 15px;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${room.name}</strong><br>
                                <small>${room.adults} adults, ${room.children} children • ${room.bed_preference} bed</small>
                            </div>
                            <div style="text-align: right;">
                                <strong>KES ${(room.price * nights).toLocaleString()}</strong><br>
                                <small>KES ${room.price.toLocaleString()} × ${nights} nights</small>
                            </div>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            
            $('#booking-review').html(html);
        }
    </script>

    <style>
    /* Availability Modal Styles (from homepage) */
    .availability-modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        justify-content: center;
        align-items: center;
        animation: fadeIn 0.3s ease-in-out;
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .availability-modal .modal-content {
        background: white;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        max-width: 600px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
        animation: slideIn 0.3s ease-in-out;
    }

    @keyframes slideIn {
        from { transform: translateY(-50px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .availability-modal .modal-header {
        padding: 20px;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }

    .availability-modal .modal-header h3 {
        margin: 0;
        font-family: 'Playfair Display', serif;
        font-size: 1.5rem;
    }

    .availability-modal .close {
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
        color: white;
        background: none;
        border: none;
        padding: 0;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        transition: all 0.3s ease;
    }

    .availability-modal .close:hover {
        background-color: rgba(255, 255, 255, 0.2);
        transform: rotate(90deg);
    }

    .availability-modal .modal-body {
        padding: 25px;
    }

    .loading-spinner {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .loading-spinner i {
        font-size: 2rem;
        margin-bottom: 15px;
        color: #667eea;
    }

    .error-message {
        text-align: center;
        padding: 40px 20px;
        color: #dc3545;
    }

    .room-info {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 25px;
        border-left: 4px solid #667eea;
    }

    .room-price {
        font-size: 1.5rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
    }

    .availability-section h4 {
        color: #2c3e50;
        margin-bottom: 15px;
        font-family: 'Playfair Display', serif;
        font-size: 1.2rem;
    }

    .availability-period {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        padding: 20px;
        margin-bottom: 15px;
        transition: all 0.3s ease;
        position: relative;
        overflow: hidden;
    }

    .availability-period:hover {
        border-color: #667eea;
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        transform: translateY(-2px);
    }

    .period-dates {
        font-size: 1.1rem;
        margin-bottom: 10px;
        color: #2c3e50;
    }

    .period-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
        color: #6c757d;
    }

    .btn-quick-book {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        font-size: 0.9rem;
    }

    .btn-quick-book:hover {
        background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
    }

    .no-availability {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
        font-style: italic;
        background: #f8f9fa;
        border-radius: 12px;
        border: 2px dashed #dee2e6;
    }

    .modal-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
        justify-content: center;
    }

    .modal-actions .btn {
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        border: none;
        font-size: 1rem;
        min-width: 140px;
    }

    .modal-actions .btn-primary {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
    }

    .modal-actions .btn-primary:hover {
        background: linear-gradient(135deg, #5a67d8 0%, #6b46c1 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .modal-actions .btn-secondary {
        background: #6c757d;
        color: white;
    }

    .modal-actions .btn-secondary:hover {
        background: #5a6268;
        transform: translateY(-1px);
    }

    /* Button styles for room actions */
    .btn-availability:hover {
        background: #138496 !important;
        transform: translateY(-1px);
        color: white;
        box-shadow: 0 4px 12px rgba(23, 162, 184, 0.3);
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .availability-modal .modal-content {
            width: 95%;
            margin: 10px;
        }
        
        .availability-modal .modal-header {
            padding: 15px;
        }
        
        .availability-modal .modal-body {
            padding: 20px;
        }
        
        .modal-actions {
            flex-direction: column;
        }
        
        .period-info {
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
    }
    </style>
</body>
</html>

<?php include('../../../includes/guest/footer.php'); ?>
