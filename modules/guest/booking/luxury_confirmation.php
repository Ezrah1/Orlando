<?php
session_start();
require_once '../../../includes/common/hotel_settings.php';
require_once '../../../db.php';

$page_title = 'Reservation Confirmed - ' . get_hotel_info('name');

// Get booking reference from URL
$booking_ref = $_GET['booking_ref'] ?? '';

if (!$booking_ref) {
    header("Location: luxury_booking.php");
    exit();
}

// Get booking details
$booking_query = "SELECT * FROM roombook WHERE booking_ref = '$booking_ref' ORDER BY id DESC";
$booking_result = mysqli_query($con, $booking_query);
$bookings = [];
while($booking = mysqli_fetch_assoc($booking_result)) {
    $bookings[] = $booking;
}

if (empty($bookings)) {
    header("Location: luxury_booking.php");
    exit();
}

$main_booking = $bookings[0];
$total_amount = 0;
foreach($bookings as $booking) {
    $room_total = $booking['nodays'] * 
                  (mysqli_fetch_assoc(mysqli_query($con, "SELECT base_price FROM named_rooms WHERE room_name = '{$booking['TRoom']}'"))['base_price'] ?? 0);
    $total_amount += $room_total;
}
$tax = $total_amount * 0.16;
$final_total = $total_amount + $tax;

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
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            margin: 0;
            padding: 60px 20px;
        }

        .confirmation-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.1);
            overflow: hidden;
            position: relative;
        }

        .success-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 60px 40px;
            text-align: center;
            position: relative;
        }

        .success-icon {
            width: 80px;
            height: 80px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2.5rem;
        }

        .success-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .success-subtitle {
            font-size: 1.2rem;
            opacity: 0.9;
        }

        .booking-ref {
            background: rgba(255,255,255,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 50px;
            margin-top: 25px;
            display: inline-block;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .confirmation-details {
            padding: 40px;
        }

        .detail-section {
            margin-bottom: 35px;
            padding-bottom: 25px;
            border-bottom: 1px solid #e9ecef;
        }

        .detail-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 1.4rem;
            color: #2c3e50;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .detail-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }

        .detail-label {
            font-size: 0.9rem;
            color: #6c757d;
            font-weight: 500;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: 600;
            color: #2c3e50;
        }

        .room-card {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 15px;
        }

        .room-name {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .room-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 10px;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .pricing-summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .pricing-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .pricing-row.total {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid rgba(255,255,255,0.2);
            font-size: 1.3rem;
            font-weight: 700;
        }

        .action-buttons {
            padding: 30px 40px;
            background: #f8f9fa;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 25px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-secondary {
            background: white;
            color: #495057;
            border: 2px solid #e9ecef;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .next-steps {
            background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
            padding: 25px;
            border-radius: 15px;
            margin-top: 20px;
        }

        .next-steps h4 {
            color: #2c3e50;
            margin-bottom: 15px;
            font-family: 'Playfair Display', serif;
        }

        .step-item {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            color: #495057;
        }

        .step-number {
            width: 25px;
            height: 25px;
            background: #667eea;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .confirmation-container {
                margin: 20px;
            }
            
            .success-header {
                padding: 40px 20px;
            }
            
            .success-title {
                font-size: 2rem;
            }
            
            .confirmation-details {
                padding: 30px 20px;
            }
            
            .action-buttons {
                padding: 20px;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="confirmation-container">
        <!-- Success Header -->
        <div class="success-header">
            <div class="success-icon">
                <i class="fa fa-check"></i>
            </div>
            <h1 class="success-title">Reservation Confirmed!</h1>
            <p class="success-subtitle">Your luxury stay has been successfully booked</p>
            <div class="booking-ref">
                Confirmation #<?php echo $booking_ref; ?>
            </div>
        </div>

        <!-- Booking Details -->
        <div class="confirmation-details">
            <!-- Guest Information -->
            <div class="detail-section">
                <h3 class="section-title">Guest Information</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Guest Name</div>
                        <div class="detail-value"><?php echo $main_booking['Title'] . ' ' . $main_booking['FName'] . ' ' . $main_booking['LName']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?php echo $main_booking['Email']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Phone Number</div>
                        <div class="detail-value"><?php echo $main_booking['Phone']; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Nationality</div>
                        <div class="detail-value"><?php echo $main_booking['National']; ?></div>
                    </div>
                </div>
            </div>

            <!-- Stay Details -->
            <div class="detail-section">
                <h3 class="section-title">Stay Details</h3>
                <div class="detail-grid">
                    <div class="detail-item">
                        <div class="detail-label">Check-in Date</div>
                        <div class="detail-value"><?php echo date('l, F j, Y', strtotime($main_booking['cin'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Check-out Date</div>
                        <div class="detail-value"><?php echo date('l, F j, Y', strtotime($main_booking['cout'])); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Duration</div>
                        <div class="detail-value"><?php echo $main_booking['nodays']; ?> night<?php echo $main_booking['nodays'] > 1 ? 's' : ''; ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Total Rooms</div>
                        <div class="detail-value"><?php echo count($bookings); ?> room<?php echo count($bookings) > 1 ? 's' : ''; ?></div>
                    </div>
                </div>
            </div>

            <!-- Room Details -->
            <div class="detail-section">
                <h3 class="section-title">Room Selection</h3>
                <?php foreach($bookings as $index => $booking): ?>
                    <?php 
                    $room_query = "SELECT base_price FROM named_rooms WHERE room_name = '{$booking['TRoom']}'";
                    $room_result = mysqli_query($con, $room_query);
                    $room_data = mysqli_fetch_assoc($room_result);
                    $room_total = $booking['nodays'] * ($room_data['base_price'] ?? 0);
                    ?>
                    <div class="room-card">
                        <div class="room-name">Room <?php echo $index + 1; ?>: <?php echo $booking['TRoom']; ?></div>
                        <div class="room-details">
                            <div><strong>Meal Plan:</strong> <?php echo $booking['Meal']; ?></div>
                            <div><strong>Rate:</strong> KES <?php echo number_format($room_data['base_price'] ?? 0); ?>/night</div>
                            <div><strong>Total:</strong> KES <?php echo number_format($room_total); ?></div>
                        </div>
                        <?php if($booking['staff_notes']): ?>
                            <div style="margin-top: 10px; font-size: 0.9rem; color: #6c757d;">
                                <strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($booking['staff_notes'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pricing Summary -->
            <div class="pricing-summary">
                <h4 style="margin-bottom: 20px;">Payment Summary</h4>
                <div class="pricing-row">
                    <span>Subtotal:</span>
                    <span>KES <?php echo number_format($total_amount); ?></span>
                </div>
                <div class="pricing-row">
                    <span>Tax (16%):</span>
                    <span>KES <?php echo number_format($tax); ?></span>
                </div>
                <div class="pricing-row total">
                    <span>Total Amount:</span>
                    <span>KES <?php echo number_format($final_total); ?></span>
                </div>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h4>What's Next?</h4>
                <div class="step-item">
                    <div class="step-number">1</div>
                    <span>You'll receive a confirmation email within 5 minutes</span>
                </div>
                <div class="step-item">
                    <div class="step-number">2</div>
                    <span>Complete payment using your selected method</span>
                </div>
                <div class="step-item">
                    <div class="step-number">3</div>
                    <span>Arrive at the hotel with a valid ID for check-in</span>
                </div>
                <div class="step-item">
                    <div class="step-number">4</div>
                    <span>Enjoy your luxury stay with us!</span>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="luxury_booking.php" class="btn btn-secondary">
                <i class="fa fa-plus"></i> Book Another Stay
            </a>
            <a href="../../../index.php" class="btn btn-primary">
                <i class="fa fa-home"></i> Return to Homepage
            </a>
            <button onclick="window.print()" class="btn btn-secondary">
                <i class="fa fa-print"></i> Print Confirmation
            </button>
        </div>
    </div>

    <script>
        // Auto-scroll to top on page load
        window.scrollTo(0, 0);
        
        // Add some animation to the success icon
        document.addEventListener('DOMContentLoaded', function() {
            const icon = document.querySelector('.success-icon');
            icon.style.transform = 'scale(0)';
            setTimeout(() => {
                icon.style.transition = 'transform 0.5s ease';
                icon.style.transform = 'scale(1)';
            }, 200);
        });
    </script>
</body>
</html>

<?php include('../../../includes/guest/footer.php'); ?>
