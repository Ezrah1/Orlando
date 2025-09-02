<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include hotel settings for dynamic content
require_once '../../../includes/common/hotel_settings.php';

$page_title = 'Booking Confirmation';
$page_description = get_hotel_info('name') . ' - Booking Confirmation';

// Include database connection
require_once('../../../db.php');

$booking_ref = isset($_GET['booking_ref']) ? $_GET['booking_ref'] : '';

if(!$booking_ref) {
    header("Location: booking_form.php");
    exit();
}

// Get booking details
$booking_query = "SELECT * FROM roombook WHERE booking_ref = '$booking_ref'";
$booking_result = mysqli_query($con, $booking_query);
$booking = mysqli_fetch_assoc($booking_result);

if(!$booking) {
    header("Location: booking_form.php");
    exit();
}

// Combine first and last name
$guest_name = trim($booking['FName'] . ' ' . $booking['LName']);

// Don't include header for cleaner print layout
// include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');
include('../../../includes/components/qr_generator.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Booking Confirmation - Orlando International Resorts</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link href="../../../css/bootstrap.css" rel="stylesheet" type="text/css" media="all" />
    <link href="../../../css/font-awesome.css" rel="stylesheet"> 
    <link href="../../../css/style.css" rel="stylesheet" type="text/css" media="all" />
    <link href="https://fonts.googleapis.com/css?family=Oswald:300,400,700" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Federo" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700,900" rel="stylesheet">
    <style>
        /* Print-optimized styles for single page */
        @media print {
            * { 
                -webkit-print-color-adjust: exact !important; 
                color-adjust: exact !important;
                box-sizing: border-box !important;
            }
            
            html, body { 
                margin: 0 !important; 
                padding: 0 !important; 
                font-size: 12px !important; 
                line-height: 1.5 !important; 
                background: white !important;
                color: black !important;
                font-family: Arial, sans-serif !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
            }
            
            /* Hide web elements */
            .w3_navigation, 
            .qr-alternatives,
            script,
            .header,
            .footer,
            nav,
            .navbar,
            .menu,
            .navigation,
            .top-bar,
            .contact-info { 
                display: none !important; 
            }
            
            /* Main container */
            .confirmation-container { 
                max-width: 100% !important; 
                width: 100% !important;
                margin: 0 !important; 
                padding: 20px !important; 
                box-shadow: none !important; 
                border: 2px solid #000 !important;
                page-break-inside: avoid !important;
                background: white !important;
                position: relative !important;
                overflow: visible !important;
            }
            
            /* Header Section */
            .header-section {
                border-bottom: 2px solid #000 !important;
                margin-bottom: 20px !important;
                padding-bottom: 15px !important;
                text-align: center !important;
                clear: both !important;
            }
            
            .hotel-logo {
                font-size: 20px !important;
                font-weight: bold !important;
                color: #000 !important;
                margin-bottom: 10px !important;
                display: block !important;
            }
            
            .confirmation-title {
                font-size: 22px !important;
                font-weight: bold !important;
                color: #000 !important;
                margin: 10px 0 !important;
                display: block !important;
            }
            
            .booking-ref-highlight {
                background: #e9ecef !important;
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 8px 16px !important;
                margin: 10px auto !important;
                display: inline-block !important;
                font-weight: bold !important;
                font-size: 14px !important;
            }
            
            /* Two Column Layout */
            .two-column { 
                display: block !important;
                width: 100% !important;
                margin: 20px 0 !important;
                clear: both !important;
            }
            
            .left-column, .right-column { 
                width: 48% !important;
                float: left !important;
                margin: 0 !important;
                padding: 0 1% !important;
                box-sizing: border-box !important;
            }
            
            .right-column {
                float: right !important;
            }
            
            /* Section Titles */
            .section-title {
                font-size: 14px !important;
                font-weight: bold !important;
                color: #000 !important;
                border-bottom: 1px solid #000 !important;
                margin-bottom: 10px !important;
                padding-bottom: 5px !important;
                display: block !important;
                clear: both !important;
            }
            
            /* Detail Items */
            .detail-item {
                font-size: 11px !important;
                margin: 8px 0 !important;
                padding: 3px 0 !important;
                display: block !important;
                width: 100% !important;
                clear: both !important;
                overflow: hidden !important;
            }
            
            .detail-label {
                font-weight: bold !important;
                color: #000 !important;
                float: left !important;
                width: 40% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .detail-value {
                color: #000 !important;
                font-weight: normal !important;
                float: right !important;
                width: 55% !important;
                text-align: right !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            
            /* Amount Highlight */
            .amount-highlight {
                background: #f5f5f5 !important;
                border: 1px solid #000 !important;
                border-left: 4px solid #000 !important;
                color: #000 !important;
                padding: 10px !important;
                margin: 15px 0 !important;
                text-align: center !important;
                clear: both !important;
                display: block !important;
            }
            
            .amount-text {
                color: #000 !important;
                font-size: 16px !important;
                font-weight: bold !important;
                margin: 0 !important;
            }
            
            /* Status Badge */
            .status-badge {
                background: #e9ecef !important;
                color: #000 !important;
                border: 1px solid #000 !important;
                padding: 3px 8px !important;
                font-size: 10px !important;
                font-weight: bold !important;
            }
            
            /* Clear floats after columns */
            .two-column:after {
                content: "" !important;
                display: table !important;
                clear: both !important;
            }
            
            /* QR Section */
            .qr-section {
                margin: 20px auto !important;
                padding: 15px !important;
                border: 2px solid #000 !important;
                background: white !important;
                text-align: center !important;
                clear: both !important;
                display: block !important;
                width: 180px !important;
                border-radius: 8px !important;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
            }
            
            .qr-section h5 {
                color: #000 !important;
                font-size: 12px !important;
                margin: 0 0 10px 0 !important;
                font-weight: bold !important;
            }
            
            /* QR styles moved to second print section to avoid conflicts */
            
            .qr-section p {
                margin: 8px 0 0 0 !important;
                font-size: 10px !important;
                color: #333 !important;
                font-weight: normal !important;
            }
            
            .qr-ref {
                margin: 8px 0 !important;
                font-size: 11px !important;
                color: #000 !important;
                font-weight: bold !important;
            }
            
            /* QR placeholder styles removed - now using qr-container and qr-fallback */
            
            /* Info Section */
            .info-section {
                margin: 20px 0 !important;
                padding: 15px !important;
                border: 1px solid #000 !important;
                background: #f9f9f9 !important;
                clear: both !important;
                display: block !important;
            }
            
            .info-section h5 {
                color: #000 !important;
                font-size: 12px !important;
                margin: 0 0 10px 0 !important;
                font-weight: bold !important;
            }
            
            .info-grid {
                display: block !important;
                width: 100% !important;
            }
            
            .info-item {
                font-size: 10px !important;
                color: #000 !important;
                margin: 5px 0 !important;
                display: block !important;
                float: left !important;
                width: 48% !important;
                padding: 2px !important;
            }
            
            .info-grid:after {
                content: "" !important;
                display: table !important;
                clear: both !important;
            }
            
            /* Footer */
            .footer-note {
                font-size: 10px !important;
                color: #000 !important;
                border-top: 1px solid #000 !important;
                margin-top: 20px !important;
                padding-top: 10px !important;
                text-align: center !important;
                clear: both !important;
                display: block !important;
            }
            
            .footer-note p {
                margin: 5px 0 !important;
                line-height: 1.3 !important;
            }
        }

        .confirmation-container {
            max-width: 800px;
            margin: 20px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border: 2px solid #28a745;
            font-family: 'Arial', sans-serif;
            min-height: auto;
        }

        .header-section {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #28a745;
        }

        .hotel-logo {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
        }

        .confirmation-title {
            font-size: 24px;
            color: #28a745;
            margin: 10px 0 8px 0;
            font-weight: bold;
        }

        .booking-ref-highlight {
            background: #28a745;
            color: white;
            padding: 6px 14px;
            border-radius: 15px;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            margin: 8px 0;
        }

        .two-column {
            display: flex;
            gap: 25px;
            margin: 15px 0;
            width: 100%;
        }

        .left-column, .right-column {
            flex: 1;
            width: 48%;
        }

        /* Clear fix for float layouts */
        .clearfix:after {
            content: "";
            display: table;
            clear: both;
        }

        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 10px;
            padding-bottom: 3px;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            margin: 5px 0;
            padding: 3px 0;
            font-size: 13px;
        }

        .detail-label {
            font-weight: 500;
            color: #495057;
            width: 45%;
        }

        .detail-value {
            color: #2c3e50;
            font-weight: 600;
            text-align: right;
            width: 50%;
        }

        .amount-highlight {
            background: #f8f9fa;
            padding: 8px;
            border-radius: 5px;
            border-left: 4px solid #28a745;
            margin: 10px 0;
            text-align: center;
        }

        .amount-text {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }

        .qr-section {
            text-align: center;
            margin: 20px auto;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 2px solid #007bff;
            width: 200px;
            box-shadow: 0 2px 8px rgba(0,123,255,0.15);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .qr-section h5 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #495057;
            font-weight: 600;
            text-align: center;
        }
        
        .qr-section img,
        .qr-code-container img {
            margin: 0;
            display: block;
            width: 100px;
            height: 100px;
            max-width: 100px;
            max-height: 100px;
        }
        
        .qr-section p {
            margin: 10px 0 0 0;
            font-size: 11px;
            color: #6c757d;
            font-style: italic;
            text-align: center;
        }
        
        .qr-ref {
            margin: 10px 0;
            font-size: 12px;
            color: #495057;
            font-weight: bold;
            text-align: center;
        }
        
        /* QR placeholder styles moved to qr-container and qr-fallback */
        
        .qr-code-container {
            margin: 12px 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .qr-container {
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .qr-fallback {
            border: 2px dashed #333;
            background: #f8f9fa;
            color: #333;
            font-weight: bold;
            text-align: center;
            font-size: 10px;
            line-height: 1.2;
            display: none; /* Hidden by default */
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        /* Show fallback only when explicitly needed */
        .qr-fallback.show {
            display: flex;
        }
        
        .qr-info {
            margin-top: 8px;
            text-align: center;
        }

        .info-section {
            background: #e8f5e8;
            border: 1px solid #28a745;
            border-radius: 5px;
            padding: 12px;
            margin: 15px 0;
        }

        .info-section h5 {
            margin: 0 0 8px 0;
            font-size: 14px;
            color: #155724;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 8px;
            margin-top: 8px;
        }

        .info-item {
            font-size: 12px;
            color: #495057;
        }

        .action-buttons {
            text-align: center;
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #e9ecef;
        }

        .print-button {
            background: #007bff;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
        }

        .print-button:hover {
            background: #0056b3;
        }

        .footer-note {
            text-align: center;
            margin-top: 15px;
            font-size: 11px;
            color: #6c757d;
            padding-top: 10px;
            border-top: 1px dotted #dee2e6;
        }

        .status-badge {
            background: #28a745;
            color: white;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            font-weight: bold;
        }
        
        /* Print-specific styles for compact single-page layout */
        @media print {
            body {
                margin: 0 !important;
                padding: 0 !important;
            }
            
            .confirmation-container {
                max-width: 100% !important;
                margin: 0 !important;
                padding: 15px !important;
                box-shadow: none !important;
                border: none !important;
                border-radius: 0 !important;
                min-height: auto !important;
            }
            
            .header-section {
                padding: 10px 0 !important;
                margin-bottom: 8px !important;
            }
            
            .hotel-logo {
                font-size: 16px !important;
                margin-bottom: 3px !important;
            }
            
            .confirmation-title {
                font-size: 14px !important;
                margin: 3px 0 !important;
            }
            
            .booking-ref-highlight {
                font-size: 12px !important;
                padding: 6px 10px !important;
                margin: 5px 0 !important;
            }
            
            .two-column {
                margin: 8px 0 !important;
            }
            
            .left-column, .right-column {
                padding: 8px !important;
                width: 48% !important;
            }
            
            .section-title {
                font-size: 11px !important;
                margin-bottom: 6px !important;
                padding: 6px 0 3px 0 !important;
            }
            
            .detail-item {
                margin-bottom: 3px !important;
                font-size: 9px !important;
                line-height: 1.2 !important;
            }
            
            .detail-label {
                font-size: 9px !important;
            }
            
            .detail-value {
                font-size: 9px !important;
            }
            
            .amount-highlight {
                padding: 6px !important;
                margin: 8px 0 !important;
            }
            
            .amount-text {
                font-size: 12px !important;
            }
            
            .qr-section {
                padding: 8px !important;
                margin: 10px auto !important;
                text-align: center !important;
                width: 150px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .qr-section h5 {
                font-size: 10px !important;
                margin-bottom: 5px !important;
            }
            
            .qr-section p {
                font-size: 8px !important;
                margin: 3px 0 !important;
            }
            
            .qr-ref {
                font-size: 9px !important;
                margin: 3px 0 !important;
            }
            
            .info-section {
                padding: 8px !important;
                margin: 8px 0 !important;
            }
            
            .info-section h5 {
                font-size: 10px !important;
                margin-bottom: 4px !important;
            }
            
            .info-grid {
                gap: 4px !important;
                margin-top: 4px !important;
            }
            
            .info-item {
                font-size: 8px !important;
                line-height: 1.2 !important;
            }
            
            .footer-note {
                font-size: 8px !important;
                margin-top: 8px !important;
                padding-top: 4px !important;
            }
            
            .action-buttons {
                display: none !important;
            }
            
            /* Hide unnecessary elements for print */
            .print-button {
                display: none !important;
            }
            
            /* Page break control */
            .confirmation-container {
                page-break-inside: avoid;
            }
            
            .qr-section {
                page-break-inside: avoid;
            }
            
            /* Optimize QR code for print */
            .qr-code-container {
                margin: 8px 0 !important;
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }
            
            .qr-container {
                width: 80px !important;
                height: 80px !important;
                margin: 0 auto !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            
            .qr-container img {
                width: 80px !important;
                height: 80px !important;
                max-width: 80px !important;
                max-height: 80px !important;
                margin: 0 !important;
                display: block !important;
            }
            
            .qr-fallback {
                width: 80px !important;
                height: 80px !important;
                font-size: 8px !important;
                display: none !important; /* Hidden by default in print */
                align-items: center !important;
                justify-content: center !important;
                flex-direction: column !important;
                margin: 0 !important;
                border: 2px dashed #333 !important;
                background: #f8f9fa !important;
                color: #333 !important;
            }
            
            /* Show fallback only when image fails to load */
            .qr-container img[style*="display: none"] + .qr-fallback,
            .qr-fallback.show {
                display: flex !important;
            }
            
            .qr-info {
                margin-top: 5px !important;
                text-align: center !important;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation hidden for clean print layout -->

    <div class="container">
        <div class="confirmation-container">
            <!-- Header Section -->
            <div class="header-section">
                <div class="hotel-logo"><?php echo htmlspecialchars(get_hotel_info('name')); ?></div>
                <div class="confirmation-title">✅ Booking Confirmed</div>
                <div class="booking-ref-highlight"><?php echo $booking['booking_ref']; ?></div>
                <p style="margin: 5px 0; color: #666; font-size: 14px;">Thank you for choosing us!</p>
                <p style="margin: 2px 0; color: #888; font-size: 11px;">Show this at check-in</p>
            </div>

            <!-- Two Column Layout -->
            <div class="two-column clearfix">
                <!-- Left Column - Guest & Booking Details -->
                <div class="left-column">
                    <div class="section-title">📋 Booking Details</div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Guest Name:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($guest_name, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Email:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['Email'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Phone:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['Phone'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Room Type:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($booking['TRoom'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Guests:</span>
                        <span class="detail-value"><?php 
                            // Try to get from session first, fallback to database or defaults
                            $adults = $_SESSION['booking_details']['adults'] ?? 2;
                            $children = $_SESSION['booking_details']['children'] ?? 0;
                            echo $adults . ' adult(s), ' . $children . ' child(ren)'; 
                        ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Payment:</span>
                        <span class="detail-value"><?php 
                            // Try to get from session first, fallback to database or defaults
                            $payment_method = $_SESSION['booking_details']['payment_method'] ?? 'N/A';
                            echo ucfirst($payment_method); 
                        ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value"><span class="status-badge"><?php echo ucfirst($booking['stat'] ?? 'pending'); ?></span></span>
                    </div>
                </div>

                <!-- Right Column - Stay Details -->
                <div class="right-column">
                    <div class="section-title">🗓️ Stay Details</div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Check-in:</span>
                        <span class="detail-value"><?php echo date('d M Y', strtotime($booking['cin'])); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Check-out:</span>
                        <span class="detail-value"><?php echo date('d M Y', strtotime($booking['cout'])); ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Duration:</span>
                        <span class="detail-value"><?php echo $booking['nodays']; ?> night(s)</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Check-in Time:</span>
                        <span class="detail-value"><?php echo get_business_config('check_in_time') ?: '14:00'; ?> onwards</span>
                    </div>
                    
                    <div class="detail-item">
                        <span class="detail-label">Check-out Time:</span>
                        <span class="detail-value"><?php echo get_business_config('check_out_time') ?: '11:00'; ?></span>
                    </div>
                    
                    <!-- Amount Highlight -->
                    <div class="amount-highlight">
                        <div class="amount-text">Total: KES <?php 
                            // Try to get from session first, fallback to database calculation
                            $total_amount = $_SESSION['booking_details']['total_amount'] ?? 0;
                            if ($total_amount == 0) {
                                // Calculate from database for staff bookings
                                $room_query = "SELECT base_price FROM named_rooms WHERE room_name = '" . mysqli_real_escape_string($con, $booking['TRoom']) . "'";
                                $room_result = mysqli_query($con, $room_query);
                                if ($room_result && $room_data = mysqli_fetch_assoc($room_result)) {
                                    $total_amount = $room_data['base_price'] * $booking['nodays'];
                                }
                            }
                            echo number_format((float)$total_amount, 2); 
                        ?></div>
                    </div>
                </div>
            </div>

            <!-- QR Code Section -->
            <div class="qr-section">
                <h5>📱 Quick Check-in QR Code</h5>
                
                <div class="qr-code-container">
                    <?php
                    // Include the QR generator
                    require_once('../../../includes/components/qr_booking_generator.php');
                    
                    // Generate QR code for this booking
                    echo BookingQRGenerator::generateBookingQR($booking['booking_ref'], 80);
                    
                    // Store URL for debug display
                    $base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                    $confirmation_url = $base_url . "/Hotel/modules/guest/booking/booking_confirmation.php?booking_ref=" . urlencode($booking['booking_ref']);
                    ?>
                </div>
                
                <div class="qr-info">
                    <div class="qr-ref">
                        <strong>Ref: <?php echo htmlspecialchars($booking['booking_ref']); ?></strong>
                    </div>
                    <p style="font-size: 10px; color: #888; margin: 5px 0;">Scan for instant access</p>
                    <?php if (isset($_GET['debug']) && $_GET['debug'] == '1'): ?>
                    <p style="font-size: 8px; color: #666; margin: 2px 0; word-break: break-all;">
                        URL: <?php echo htmlspecialchars($confirmation_url); ?>
                    </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Hotel Information -->
            <div class="info-section">
                <h5>🏨 Hotel Information</h5>
                <div class="info-grid">
                    <div class="info-item"><strong>Address:</strong> <?php echo htmlspecialchars(get_hotel_info('address')); ?></div>
                    <div class="info-item"><strong>Phone:</strong> <?php echo htmlspecialchars(get_hotel_info('phone')); ?></div>
                    <div class="info-item"><strong>Email:</strong> <?php echo htmlspecialchars(get_hotel_info('email')); ?></div>
                    <div class="info-item"><strong>WiFi:</strong> Free throughout property</div>
                    <div class="info-item"><strong>Parking:</strong> Secure parking available</div>
                    <div class="info-item"><strong>Reception:</strong> 24/7 available</div>
                </div>
            </div>

            <!-- Action Buttons (hidden in print) -->
            <div class="action-buttons">
                <button onclick="printConfirmation()" class="print-button">
                    <i class="fa fa-print"></i> Print Confirmation
                </button>
                <a href="../../../index.php" class="btn btn-primary" style="margin-left: 10px; padding: 10px 20px; text-decoration: none; background: #28a745; color: white; border-radius: 5px;">
                    <i class="fa fa-home"></i> Back to Home
                </a>
            </div>

            <!-- Footer Note -->
            <div class="footer-note">
                <p>A confirmation email has been sent to <?php echo htmlspecialchars($booking['Email'] ?? 'your registered email', ENT_QUOTES, 'UTF-8'); ?></p>
                <p>For assistance, contact us at +254 700 123 456 | Booking Reference: <?php echo $booking['booking_ref']; ?></p>
            </div>
        </div>
    </div>

    <script src="../../../js/jquery-2.1.4.min.js"></script>
    <script src="../../../js/bootstrap-3.1.1.min.js"></script>
    
    <script>
        // Improve print quality
        function printConfirmation() {
            // Add print-specific class to body
            document.body.classList.add('printing');
            
            // Print with small delay to ensure styles are applied
            setTimeout(function() {
                window.print();
                // Remove print class after printing
                setTimeout(function() {
                    document.body.classList.remove('printing');
                }, 1000);
            }, 100);
        }
        
        // Override default print button behavior
        document.addEventListener('DOMContentLoaded', function() {
            var printBtn = document.querySelector('.print-button');
            if (printBtn) {
                printBtn.onclick = function(e) {
                    e.preventDefault();
                    printConfirmation();
                    return false;
                };
            }
        });
        
        // Handle browser print shortcut (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                printConfirmation();
                return false;
            }
        });
    </script>
</body>
</html>
