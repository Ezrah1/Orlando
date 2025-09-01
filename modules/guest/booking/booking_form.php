<?php
// Redirect to the new luxury booking experience
header("Location: luxury_booking.php");
exit();
?>

// Database connection
require_once '../../../db.php';

// Handle form submission first (before any HTML output)
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = mysqli_real_escape_string($con, $_POST['title']);
    $fname = mysqli_real_escape_string($con, $_POST['fname']);
    $lname = mysqli_real_escape_string($con, $_POST['lname']);
    $email = mysqli_real_escape_string($con, $_POST['email']);
    $phone = mysqli_real_escape_string($con, $_POST['phone']);
    $national = mysqli_real_escape_string($con, $_POST['national']);
    $cin = mysqli_real_escape_string($con, $_POST['cin']);
    $cout = mysqli_real_escape_string($con, $_POST['cout']);
    $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
    $additional_services = mysqli_real_escape_string($con, $_POST['additional_services']);
    $special_notes = mysqli_real_escape_string($con, $_POST['special_notes']);
    
    // Calculate number of days
    $check_in = new DateTime($cin);
    $check_out = new DateTime($cout);
    $interval = $check_in->diff($check_out);
    $nodays = $interval->days;
    
    // Process multiple rooms
    $rooms_booked = [];
    $total_amount = 0;
    
    if (isset($_POST['rooms']) && is_array($_POST['rooms'])) {
        foreach ($_POST['rooms'] as $index => $room_data) {
            if (!empty($room_data['room_type'])) {
                $troom = mysqli_real_escape_string($con, $room_data['room_type']);
                $meal = mysqli_real_escape_string($con, $room_data['meal_plan']);
                $nroom = 1; // Each entry is 1 room
                
                // Get room price
                $price_query = "SELECT base_price FROM named_rooms WHERE room_name = '$troom'";
                $price_result = mysqli_query($con, $price_query);
                if ($price_row = mysqli_fetch_assoc($price_result)) {
                    $base_price = $price_row['base_price'];
                    
                    // Calculate room total
                    $room_total = $base_price * $nodays * $nroom;
                    $total_amount += $room_total;
                    
                    $rooms_booked[] = [
                        'room_type' => $troom,
                        'meal_plan' => $meal,
                        'base_price' => $base_price,
                        'room_total' => $room_total,
                        'bed_type' => isset($room_data['bed_type']) ? mysqli_real_escape_string($con, $room_data['bed_type']) : 'Double',
                        'adults' => isset($room_data['adults']) ? (int)$room_data['adults'] : 2,
                        'children' => isset($room_data['children']) ? (int)$room_data['children'] : 0
                    ];
                }
            }
        }
    } else {
        // Fallback to single room booking (backward compatibility)
        $troom = mysqli_real_escape_string($con, $_POST['troom']);
        $meal = mysqli_real_escape_string($con, $_POST['meal']);
        $nroom = 1;
        
        // Get room price
        $price_query = "SELECT base_price FROM named_rooms WHERE room_name = '$troom'";
        $price_result = mysqli_query($con, $price_query);
        $price_row = mysqli_fetch_assoc($price_result);
        $base_price = $price_row['base_price'];
        
        // Calculate total
        $total_amount = $base_price * $nodays * $nroom;
        
        $rooms_booked[] = [
            'room_type' => $troom,
            'meal_plan' => $meal,
            'base_price' => $base_price,
            'room_total' => $total_amount,
            'bed_type' => 'Double',
            'adults' => 2,
            'children' => 0
        ];
    }
    
    // Calculate tax and final total
    $tax = $total_amount * 0.16;
    $fintot = $total_amount + $tax;
    
    // Generate booking reference
    $booking_ref = 'BK' . date('Ymd') . rand(1000, 9999);
    
    // Check availability for all rooms
    $availability_errors = [];
    foreach ($rooms_booked as $room) {
        $availability_query = "SELECT COUNT(*) as count, GROUP_CONCAT(booking_ref) as conflicting_bookings FROM roombook 
                              WHERE TRoom = '{$room['room_type']}' 
                              AND (
                                  -- Check for any date overlap
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
        
        if($availability_row['count'] > 0) {
            $conflicting_refs = $availability_row['conflicting_bookings'];
            $availability_errors[] = "Room {$room['room_type']} is not available for the selected dates. Conflicts with bookings: $conflicting_refs";
        }
    }
    
    if(!empty($availability_errors)) {
        $error_message = implode('<br>', $availability_errors);
    } else {
        // Insert all bookings
        $all_bookings_successful = true;
        $booking_ids = [];
        
        foreach ($rooms_booked as $room) {
            $room_notes = "Bed Type: {$room['bed_type']}, Adults: {$room['adults']}, Children: {$room['children']}\n$additional_services\n\nSpecial Notes: $special_notes";
            
            $booking_sql = "INSERT INTO roombook (booking_ref, Title, FName, LName, Email, Phone, National, 
                            TRoom, NRoom, Meal, cin, cout, nodays, stat, payment_status, created_at, staff_notes) 
                            VALUES ('$booking_ref', '$title', '$fname', '$lname', '$email', '$phone', '$national',
                            '{$room['room_type']}', 1, '{$room['meal_plan']}', '$cin', '$cout', $nodays, 'pending', 'pending', NOW(), '$room_notes')";
            
            if(mysqli_query($con, $booking_sql)) {
                $booking_id = mysqli_insert_id($con);
                $booking_ids[] = $booking_id;
                
                // Update room status in room_status table
                $room_status_check = "SELECT * FROM room_status WHERE room_name = '{$room['room_type']}'";
                $room_status_result = mysqli_query($con, $room_status_check);
                
                if(mysqli_num_rows($room_status_result) > 0) {
                    // Update existing room status
                    $update_room_status = "UPDATE room_status SET current_status = 'occupied', updated_at = NOW() WHERE room_name = '{$room['room_type']}'";
                    mysqli_query($con, $update_room_status);
                } else {
                    // Insert new room status record
                    $insert_room_status = "INSERT INTO room_status (room_name, current_status, cleaning_status, updated_at) 
                                          VALUES ('{$room['room_type']}', 'occupied', 'clean', NOW())";
                    mysqli_query($con, $insert_room_status);
                }
                
                // Update housekeeping_status in the booking record
                $update_housekeeping = "UPDATE roombook SET housekeeping_status = 'occupied' WHERE id = $booking_id";
                mysqli_query($con, $update_housekeeping);
                
            } else {
                $all_bookings_successful = false;
                $error_message = "Error creating booking for room {$room['room_type']}: " . mysqli_error($con);
                error_log("Booking insertion failed: " . mysqli_error($con) . " SQL: " . $booking_sql);
                break;
            }
        }
        
        if($all_bookings_successful) {
            // Store booking details in session
            $_SESSION['booking_details'] = [
                'booking_ids' => $booking_ids,
                'booking_ref' => $booking_ref,
                'total_amount' => $fintot,
                'payment_method' => $payment_method,
                'rooms_count' => count($rooms_booked)
            ];
            
            if($payment_method == 'mpesa') {
                header("Location: ../payments/mpesa_payment.php?booking_ref=$booking_ref&amount=$fintot");
            } else {
                header("Location: booking_confirmation.php?booking_ref=$booking_ref");
            }
            exit();
        }
    }
}

// Get available rooms for the form (after form processing)
$rooms_query = "SELECT * FROM named_rooms WHERE is_active = 1 ORDER BY base_price ASC";
$rooms_result = mysqli_query($con, $rooms_query);
$rooms = [];
$default_room = null;
$standard_room = null;
while($room = mysqli_fetch_assoc($rooms_result)) {
    $rooms[] = $room;
    // Set default room (prefer standard/double rooms for best user experience)
    if (stripos($room['room_name'], 'standard') !== false || stripos($room['room_name'], 'double') !== false) {
        if (!$default_room) $default_room = $room['room_name'];
    }
    // Fallback to first available room
    if (!$standard_room && !$default_room) {
        $standard_room = $room['room_name'];
    }
}
// Final fallback
if (!$default_room) $default_room = $standard_room;

// Include header and components after form processing
include('../../../includes/guest/header.php');
include('../../../includes/components/forms.php');
include('../../../includes/components/alerts.php');
?>

<div class="booking-section" style="padding: 120px 0 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh;">
    <div class="container">
        <div class="page-header text-center mb-5">
            <h1 class="display-4 text-white mb-3" style="font-weight: 700;">
                <i class="fa fa-bed"></i> Book Your Perfect Stay
            </h1>
            <p class="lead text-white-50">Choose your dates and room preferences</p>
        </div>

        <div class="row">
            <!-- Main Booking Form -->
            <div class="col-lg-8">
                <div class="card" style="border-radius: 20px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <div class="card-body p-5">
                        
                        <?php if($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>

                        <form method="post" id="booking-form">
                            <!-- Guest Information -->
                            <div class="form-section mb-4">
                                <h4 class="section-title"><i class="fa fa-user"></i> Guest Information</h4>
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label>Title</label>
                                            <select name="title" class="form-control custom-select enhanced" required>
                                                <option value="">Select Title</option>
                                                <option value="Mr" selected>Mr</option>
                                                <option value="Mrs">Mrs</option>
                                                <option value="Ms">Ms</option>
                                                <option value="Dr">Dr</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" name="fname" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" name="lname" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Email</label>
                                            <input type="email" name="email" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Phone</label>
                                            <input type="tel" name="phone" class="form-control" required>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label>Nationality</label>
                                    <input type="text" name="national" class="form-control" required>
                                </div>
                            </div>
                            
                            <!-- Room Selection -->
                            <div class="form-section mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h4 class="section-title mb-0"><i class="fa fa-bed"></i> Room Selection</h4>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="addRoom()">
                                        <i class="fa fa-plus"></i> Add Room
                                    </button>
                                </div>
                                
                                <div id="rooms-container">
                                    <!-- Room 1 (Initial) -->
                                    <div class="room-item" data-room-index="0">
                                        <div class="room-header">
                                            <h5><i class="fa fa-bed"></i> Room 1</h5>
                                            <button type="button" class="btn btn-sm btn-danger room-remove" onclick="removeRoom(0)" style="display: none;">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Room Type *</label>
                                                    <select name="rooms[0][room_type]" class="form-control custom-select enhanced room-type" required>
                                                        <option value="">Select Room</option>
                                                        <?php foreach($rooms as $room): ?>
                                                            <option value="<?php echo htmlspecialchars($room['room_name']); ?>" 
                                                                    data-price="<?php echo $room['base_price']; ?>"
                                                                    <?php echo ($room['room_name'] == $default_room) ? 'selected' : ''; ?>>
                                                                <?php echo htmlspecialchars($room['room_name']); ?> - KES <?php echo number_format($room['base_price']); ?>/night
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="form-group">
                                                    <label>Meal Plan *</label>
                                                    <select name="rooms[0][meal_plan]" class="form-control custom-select enhanced" required>
                                                        <option value="">Select Meal Plan</option>
                                                        <option value="Room Only">Room Only</option>
                                                        <option value="Bed & Breakfast" selected>Bed & Breakfast</option>
                                                        <option value="Half Board">Half Board</option>
                                                        <option value="Full Board">Full Board</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Bed Type</label>
                                                    <select name="rooms[0][bed_type]" class="form-control custom-select enhanced">
                                                        <option value="">Select Bed</option>
                                                        <option value="Single">Single</option>
                                                        <option value="Double" selected>Double</option>
                                                        <option value="Twin">Twin</option>
                                                        <option value="King">King</option>
                                                        <option value="Queen">Queen</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Adults</label>
                                                    <select name="rooms[0][adults]" class="form-control custom-select enhanced">
                                                        <option value="1">1</option>
                                                        <option value="2" selected>2</option>
                                                        <option value="3">3</option>
                                                        <option value="4">4</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label>Children</label>
                                                    <select name="rooms[0][children]" class="form-control custom-select enhanced">
                                                        <option value="0" selected>0</option>
                                                        <option value="1">1</option>
                                                        <option value="2">2</option>
                                                        <option value="3">3</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="room-summary">
                                            <div class="price-display">
                                                <span class="room-price">KES 0/night</span>
                                                <span class="room-total">Total: KES 0</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Dates -->
                            <div class="form-section mb-4">
                                <h4 class="section-title"><i class="fa fa-calendar"></i> Booking Dates</h4>
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Check-in Date</label>
                                            <input type="date" name="cin" id="cin" class="form-control" 
                                                   min="<?php echo date('Y-m-d'); ?>" 
                                                   value="<?php echo date('Y-m-d'); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>Check-out Date</label>
                                            <input type="date" name="cout" id="cout" class="form-control" 
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                                                   value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Additional Services & Notes -->
                            <div class="form-section mb-4">
                                <h4 class="section-title"><i class="fa fa-plus-circle"></i> Additional Services & Notes</h4>
                                
                                <div class="form-group">
                                    <label>Additional Services</label>
                                    <textarea name="additional_services" class="form-control" rows="3" 
                                              placeholder="e.g., Airport transfer, Laundry service, Room service, etc."></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Special Notes/Requests</label>
                                    <textarea name="special_notes" class="form-control" rows="3" 
                                              placeholder="Any special requests, dietary requirements, accessibility needs, etc."></textarea>
                                </div>
                            </div>
                            
                            <!-- Payment Method -->
                            <div class="form-section mb-4">
                                <h4 class="section-title"><i class="fa fa-credit-card"></i> Payment Method</h4>
                                
                                <div class="payment-options">
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="mpesa" value="mpesa" checked required>
                                        <label for="mpesa">
                                            <i class="fa fa-mobile"></i>
                                            <span>M-Pesa</span>
                                        </label>
                                    </div>
                                    <div class="payment-option">
                                        <input type="radio" name="payment_method" id="card" value="card">
                                        <label for="card">
                                            <i class="fa fa-credit-card"></i>
                                            <span>Credit/Debit Card</span>
                                        </label>
                                    </div>
                                </div>
                                <div style="margin-top: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px; font-size: 0.9rem; color: #6c757d; border-left: 4px solid #667eea;">
                                    <i class="fa fa-info-circle"></i> <strong>Note:</strong> Cash payments are only available for walk-in guests at our front desk.
                                </div>
                            </div>
                            
                            <!-- Price Summary -->
                            <div class="price-summary" id="price-summary" style="display: none;">
                                <h4 class="section-title"><i class="fa fa-calculator"></i> Price Summary</h4>
                                <div class="price-details">
                                    <div class="price-row">
                                        <span>Room Rate:</span>
                                        <span id="room-rate">KES 0</span>
                                    </div>
                                    <div class="price-row">
                                        <span>Number of Nights:</span>
                                        <span id="nights">0</span>
                                    </div>
                                    <div class="price-row">
                                        <span>Number of Rooms:</span>
                                        <span id="room-count">0</span>
                                    </div>
                                    <div class="price-row subtotal">
                                        <span>Subtotal:</span>
                                        <span id="subtotal">KES 0</span>
                                    </div>
                                    <div class="price-row">
                                        <span>Tax (16%):</span>
                                        <span id="tax">KES 0</span>
                                    </div>
                                    <div class="price-row total">
                                        <span>Total:</span>
                                        <span id="total">KES 0</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Button -->
                            <div class="form-actions text-center">
                                <button type="submit" class="btn-book-now">
                                    <i class="fa fa-check"></i>
                                    <span>Confirm Booking</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-lg-4">
                <!-- Room Amenities -->
                <div class="sidebar-card" style="background: white; border-radius: 20px; padding: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <h4 class="sidebar-title"><i class="fa fa-star"></i> Room Amenities</h4>
                    
                    <div class="amenity-item">
                        <i class="fa fa-wifi"></i>
                        <div>
                            <strong>Free WiFi</strong>
                            <small>High-speed internet throughout</small>
                        </div>
                    </div>
                    
                    <div class="amenity-item">
                        <i class="fa fa-snowflake-o"></i>
                        <div>
                            <strong>Air Conditioning</strong>
                            <small>Climate control in all rooms</small>
                        </div>
                    </div>
                    
                    <div class="amenity-item">
                        <i class="fa fa-tv"></i>
                        <div>
                            <strong>Flat-screen TV</strong>
                            <small>Entertainment in every room</small>
                        </div>
                    </div>
                    
                    <div class="amenity-item">
                        <i class="fa fa-coffee"></i>
                        <div>
                            <strong>Breakfast Included</strong>
                            <small>Complimentary morning meal</small>
                        </div>
                    </div>
                    
                    <div class="amenity-item">
                        <i class="fa fa-shower"></i>
                        <div>
                            <strong>Private Bathroom</strong>
                            <small>En-suite facilities</small>
                        </div>
                    </div>
                    
                    <div class="amenity-item">
                        <i class="fa fa-car"></i>
                        <div>
                            <strong>Free Parking</strong>
                            <small>Secure parking available</small>
                        </div>
                    </div>
                </div>
                
                <!-- WhatsApp Booking -->
                <div class="sidebar-card" style="background: linear-gradient(135deg, #25d366 0%, #128c7e 100%); color: white; border-radius: 20px; padding: 30px; box-shadow: 0 15px 35px rgba(0,0,0,0.1);">
                    <h4 class="sidebar-title text-white"><i class="fa fa-whatsapp"></i> Need Help?</h4>
                    <p>Prefer to book with assistance? Our staff is here to help!</p>
                    
                    <div class="whatsapp-features">
                        <div class="feature-item">
                            <i class="fa fa-clock-o"></i>
                            <span>24/7 Support</span>
                        </div>
                        <div class="feature-item">
                            <i class="fa fa-comments"></i>
                            <span>Instant Response</span>
                        </div>
                        <div class="feature-item">
                            <i class="fa fa-heart"></i>
                            <span>Personalized Service</span>
                        </div>
                    </div>
                    
                    <button class="btn-whatsapp" onclick="openWhatsApp()">
                        <i class="fa fa-whatsapp"></i> Book via WhatsApp
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modern CSS Styles -->
<style>
/* Form Sections  */
.form-section {
    border-bottom: 1px solid #e9ecef;
    padding-bottom: 25px;
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.section-title i {
    color: #667eea;
    font-size: 1.2rem;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 12px 15px;
    font-size: 1rem;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    background: white;
}

/* Custom Select Styling  */
.custom-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
    background-position: right 0.5rem center;
    background-repeat: no-repeat;
    background-size: 1.5em 1.5em;
    padding-right: 2.5rem;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
}

/* Enhanced dropdown selection visibility */
.custom-select.enhanced option:checked,
.custom-select.enhanced option:selected {
    background-color: #667eea !important;
    color: #ffffff !important;
}

.custom-select.enhanced:focus {
    border-color: #667eea !important;
    background-color: #ffffff !important;
}

.custom-select.enhanced.has-selection {
    border-color: #28a745 !important;
    background-color: #f8fff9 !important;
}

.custom-select:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
}

/* Payment Options  */
.payment-options {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.payment-option {
    flex: 1;
    min-width: 120px;
}

.payment-option input[type="radio"] {
    display: none;
}

.payment-option label {
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 20px;
    border: 2px solid #e9ecef;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    background: #f8f9fa;
}

.payment-option input[type="radio"]:checked + label {
    border-color: #667eea;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
}

.payment-option label i {
    font-size: 2rem;
    margin-bottom: 10px;
}

.payment-option label span {
    font-weight: 600;
    font-size: 0.9rem;
}

/* Price Summary  */
.price-summary {
    background: #f8f9fa;
    border-radius: 15px;
    padding: 25px;
    border: 2px solid #e9ecef;
}

.price-details {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.price-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    font-size: 0.95rem;
}

.price-row.subtotal {
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
    margin-top: 5px;
    font-weight: 600;
    color: #2c3e50;
}

.price-row.total {
    border-top: 2px solid #667eea;
    padding-top: 15px;
    margin-top: 5px;
    font-weight: 700;
    font-size: 1.1rem;
    color: #667eea;
}

/* Submit Button  */
.btn-book-now {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
    color: white;
    padding: 15px 40px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    cursor: pointer;
}

.btn-book-now:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(40, 167, 69, 0.4);
}

/* Sidebar Styles  */
.sidebar-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.sidebar-title i {
    color: #667eea;
    font-size: 1.2rem;
}

.amenity-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px 0;
    border-bottom: 1px solid #f0f0f0;
}

.amenity-item:last-child {
    border-bottom: none;
}

.amenity-item i {
    color: #667eea;
    font-size: 1.2rem;
    width: 20px;
}

.amenity-item div {
    flex: 1;
}

.amenity-item strong {
    display: block;
    color: #2c3e50;
    font-size: 0.9rem;
}

.amenity-item small {
    color: #6c757d;
    font-size: 0.8rem;
}

/* WhatsApp Section  */
.whatsapp-features {
    margin: 20px 0;
}

.feature-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 10px 0;
    font-size: 0.9rem;
}

.feature-item i {
    width: 16px;
}

.btn-whatsapp {
    background: rgba(255,255,255,0.2);
    border: 2px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 20px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 1rem;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    cursor: pointer;
    width: 100%;
}

.btn-whatsapp:hover {
    background: rgba(255,255,255,0.3);
    transform: translateY(-2px);
    color: white;
    text-decoration: none;
}

/* Multiple Room Styling */
.room-item {
    border: 2px solid #e9ecef;
    border-radius: 15px;
    padding: 20px;
    margin-bottom: 20px;
    background: #f8f9fa;
    transition: all 0.3s ease;
}

.room-item:hover {
    border-color: #667eea;
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
}

.room-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #dee2e6;
}

.room-header h5 {
    color: #2c3e50;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
}

.room-header h5 i {
    color: #667eea;
}

.room-remove {
    background: #dc3545;
    border: none;
    color: white;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 0.8rem;
}

.room-remove:hover {
    background: #c82333;
}

.room-summary {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #dee2e6;
    background: white;
    padding: 15px;
    border-radius: 8px;
}

.price-display {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.room-price {
    color: #667eea;
    font-weight: 600;
}

.room-total {
    color: #28a745;
    font-weight: 700;
    font-size: 1.1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
    color: white;
    transition: all 0.3s ease;
}

.btn-primary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
}

/* Responsive Design  */
@media (max-width: 768px) {
    .booking-section {
        padding: 100px 0 60px;
    }
    
    .page-header h1 {
        font-size: 2rem;
    }
    
    .card-body {
        padding: 25px;
    }
    
    .payment-options {
        flex-direction: column;
    }
    
    .payment-option {
        min-width: auto;
    }
    
    .room-item {
        padding: 15px;
    }
    
    .price-display {
        flex-direction: column;
        align-items: flex-start;
        gap: 5px;
    }
}
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
let roomCount = 1;

$(document).ready(function() {
    // Date change handlers
    $('#cin, #cout').change(function() {
        calculateTotalPrice();
    });
    
    // Room selection change handlers
    $(document).on('change', '.room-type', function() {
        calculateTotalPrice();
    });
    
    // Initialize price calculation
    calculateTotalPrice();
});

function addRoom() {
    const container = document.getElementById('rooms-container');
    const roomHtml = `
        <div class="room-item" data-room-index="${roomCount}">
            <div class="room-header">
                <h5><i class="fa fa-bed"></i> Room ${roomCount + 1}</h5>
                <button type="button" class="btn btn-sm btn-danger room-remove" onclick="removeRoom(${roomCount})">
                    <i class="fa fa-trash"></i>
                </button>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Room Type *</label>
                        <select name="rooms[${roomCount}][room_type]" class="form-control custom-select enhanced room-type" required>
                            <option value="">Select Room</option>
                            <?php foreach($rooms as $room): ?>
                                <option value="<?php echo htmlspecialchars($room['room_name']); ?>" 
                                        data-price="<?php echo $room['base_price']; ?>">
                                    <?php echo htmlspecialchars($room['room_name']); ?> - KES <?php echo number_format($room['base_price']); ?>/night
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Meal Plan *</label>
                        <select name="rooms[${roomCount}][meal_plan]" class="form-control custom-select enhanced" required>
                            <option value="">Select Meal Plan</option>
                            <option value="Room Only">Room Only</option>
                            <option value="Bed & Breakfast" selected>Bed & Breakfast</option>
                            <option value="Half Board">Half Board</option>
                            <option value="Full Board">Full Board</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Bed Type</label>
                        <select name="rooms[${roomCount}][bed_type]" class="form-control custom-select enhanced">
                            <option value="">Select Bed</option>
                            <option value="Single">Single</option>
                            <option value="Double" selected>Double</option>
                            <option value="Twin">Twin</option>
                            <option value="King">King</option>
                            <option value="Queen">Queen</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Adults</label>
                        <select name="rooms[${roomCount}][adults]" class="form-control custom-select enhanced">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Children</label>
                        <select name="rooms[${roomCount}][children]" class="form-control custom-select enhanced">
                            <option value="0" selected>0</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                            <option value="3">3</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="room-summary">
                <div class="price-display">
                    <span class="room-price">KES 0/night</span>
                    <span class="room-total">Total: KES 0</span>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', roomHtml);
    roomCount++;
    
    // Show remove buttons if more than 1 room
    updateRemoveButtons();
    calculateTotalPrice();
}

function removeRoom(index) {
    const roomItem = document.querySelector(`[data-room-index="${index}"]`);
    if (roomItem) {
        roomItem.remove();
        updateRemoveButtons();
        calculateTotalPrice();
    }
}

function updateRemoveButtons() {
    const roomItems = document.querySelectorAll('.room-item');
    const removeButtons = document.querySelectorAll('.room-remove');
    
    removeButtons.forEach((btn, index) => {
        btn.style.display = roomItems.length > 1 ? 'inline-block' : 'none';
    });
}

function calculateTotalPrice() {
    const cinDate = $('#cin').val();
    const coutDate = $('#cout').val();
    
    if (!cinDate || !coutDate) {
        updatePriceSummary(0, 0, 0, 0);
        return;
    }
    
    const checkIn = new Date(cinDate);
    const checkOut = new Date(coutDate);
    const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
    
    if (nights <= 0) {
        updatePriceSummary(0, 0, 0, 0);
        return;
    }
    
    let subtotal = 0;
    let roomsBooked = 0;
    
    // Calculate price for each room
    $('.room-item').each(function() {
        const roomSelect = $(this).find('.room-type');
        const selectedOption = roomSelect.find('option:selected');
        
        if (selectedOption.val() && selectedOption.data('price')) {
            const price = parseFloat(selectedOption.data('price'));
            const roomTotal = price * nights;
            subtotal += roomTotal;
            roomsBooked++;
            
            // Update individual room display
            const priceDisplay = $(this).find('.room-price');
            const totalDisplay = $(this).find('.room-total');
            
            priceDisplay.text(`KES ${price.toLocaleString()}/night`);
            totalDisplay.text(`Total: KES ${roomTotal.toLocaleString()}`);
        } else {
            // Clear display for unselected rooms
            const priceDisplay = $(this).find('.room-price');
            const totalDisplay = $(this).find('.room-total');
            
            priceDisplay.text('KES 0/night');
            totalDisplay.text('Total: KES 0');
        }
    });
    
    const tax = subtotal * 0.16;
    const total = subtotal + tax;
    
    updatePriceSummary(subtotal, tax, total, roomsBooked);
}

function updatePriceSummary(subtotal, tax, total, roomCount) {
    $('#room-count').text(roomCount);
    $('#subtotal').text('KES ' + subtotal.toLocaleString());
    $('#tax').text('KES ' + tax.toLocaleString());
    $('#total').text('KES ' + total.toLocaleString());
    
    if (subtotal > 0) {
        $('#price-summary').show();
    } else {
        $('#price-summary').hide();
    }
}
    
    // Form validation
    $('#booking-form').submit(function(e) {
        const cin = $('#cin').val();
        const cout = $('#cout').val();
        
        if (cin >= cout) {
            e.preventDefault();
            alert('Check-out date must be after check-in date');
            return false;
        }
        
        if (!$('input[name="payment_method"]:checked').val()) {
            e.preventDefault();
            alert('Please select a payment method');
            return false;
        }
    });
});

// WhatsApp booking function
function openWhatsApp() {
    const form = document.getElementById('booking-form');
    const formData = new FormData(form);
    
    let message = "🏨 *<?php echo get_hotel_info('name'); ?> - Booking Request*\n\n";
    message += "*Guest Information:*\n";
    message += `Name: ${formData.get('fname')} ${formData.get('lname')}\n`;
    message += `Phone: ${formData.get('phone')}\n`;
    message += `Email: ${formData.get('email')}\n\n`;
    
    message += "*Booking Details:*\n";
    message += `Room: ${formData.get('troom')}\n`;
    message += `Check-in: ${formData.get('cin')}\n`;
    message += `Check-out: ${formData.get('cout')}\n`;
    message += `Meal Plan: ${formData.get('meal')}\n\n`;
    
    if (formData.get('additional_services')) {
        message += `*Additional Services:*\n${formData.get('additional_services')}\n\n`;
    }
    
    if (formData.get('special_notes')) {
        message += `*Special Notes:*\n${formData.get('special_notes')}\n\n`;
    }
    
    message += "Please assist with this booking request. Thank you! 🙏";
    
    const whatsappNumber = "254742824006";
    const encodedMessage = encodeURIComponent(message);
    const whatsappUrl = `https://wa.me/${whatsappNumber}?text=${encodedMessage}`;
    
    window.open(whatsappUrl, '_blank');
}
</script>

<?php include('../../../includes/guest/footer.php'); ?>

