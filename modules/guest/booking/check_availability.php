<?php
header('Content-Type: application/json');
session_start();
require_once '../../../db.php';

// Get room name from request (homepage style)
$room_name = $_GET['room'] ?? '';
$check_days = intval($_GET['days'] ?? 30); // Check next 30 days by default

// Alternative: check specific dates (luxury booking style)
$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

if (empty($room_name) && (empty($checkin) || empty($checkout))) {
    echo json_encode(['error' => 'Room name or check-in/check-out dates required']);
    exit;
}

// Homepage style: Check availability for a specific room
if (!empty($room_name)) {
    // Sanitize input
    $room_name = mysqli_real_escape_string($con, $room_name);
    
    // Get current date
    $today = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime("+$check_days days"));
    
    // Get all bookings for this room in the next period
    $bookings_query = "SELECT cin, cout, stat, payment_status, booking_ref 
                       FROM roombook 
                       WHERE TRoom = '$room_name' 
                       AND cout >= '$today' 
                       AND cin <= '$end_date'
                       AND stat NOT IN ('cancelled', 'completed')
                       AND stat != 'cleared_for_rebooking'
                       AND payment_status != 'failed'
                       ORDER BY cin ASC";
    
    $bookings_result = mysqli_query($con, $bookings_query);
    $occupied_dates = [];
    $bookings = [];
    
    while ($booking = mysqli_fetch_assoc($bookings_result)) {
        $bookings[] = $booking;
        
        // Generate all dates between check-in and check-out
        $start = new DateTime($booking['cin']);
        $end = new DateTime($booking['cout']);
        
        while ($start < $end) {
            $occupied_dates[] = $start->format('Y-m-d');
            $start->add(new DateInterval('P1D'));
        }
    }
    
    // Find next available periods
    $available_periods = [];
    $current_date = new DateTime($today);
    $end_check = new DateTime($end_date);
    
    while ($current_date <= $end_check) {
        $date_str = $current_date->format('Y-m-d');
        
        if (!in_array($date_str, $occupied_dates)) {
            // Find how many consecutive days are available
            $consecutive_days = 0;
            $temp_date = clone $current_date;
            
            while ($temp_date <= $end_check && !in_array($temp_date->format('Y-m-d'), $occupied_dates)) {
                $consecutive_days++;
                $temp_date->add(new DateInterval('P1D'));
            }
            
            if ($consecutive_days >= 1) {
                $available_periods[] = [
                    'start_date' => $date_str,
                    'end_date' => $temp_date->sub(new DateInterval('P1D'))->format('Y-m-d'),
                    'days' => $consecutive_days,
                    'formatted_start' => $current_date->format('M j, Y'),
                    'formatted_end' => $temp_date->format('M j, Y')
                ];
                
                // Skip ahead to avoid duplicates
                $current_date = clone $temp_date;
                $current_date->add(new DateInterval('P1D'));
            } else {
                $current_date->add(new DateInterval('P1D'));
            }
        } else {
            $current_date->add(new DateInterval('P1D'));
        }
    }
    
    // Get room details
    $room_query = "SELECT * FROM named_rooms WHERE room_name = '$room_name'";
    $room_result = mysqli_query($con, $room_query);
    $room_details = mysqli_fetch_assoc($room_result);
    
    // Prepare response for homepage
    $response = [
        'room_name' => $room_name,
        'room_details' => $room_details,
        'occupied_dates' => $occupied_dates,
        'available_periods' => array_slice($available_periods, 0, 5), // Show next 5 available periods
        'current_bookings' => $bookings,
        'check_period' => [
            'start' => $today,
            'end' => $end_date,
            'days' => $check_days
        ]
    ];
    
    echo json_encode($response);
    exit;
}

// Luxury booking style: Check availability for all rooms between specific dates
try {
    // Parse dates
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    $nights = $checkin_date->diff($checkout_date)->days;
    
    if ($nights <= 0) {
        echo json_encode([
            'success' => false,
            'error' => 'Check-out date must be after check-in date'
        ]);
        exit;
    }
    
    // Get all rooms
    $rooms_query = "SELECT * FROM named_rooms ORDER BY base_price DESC";
    $rooms_result = mysqli_query($con, $rooms_query);
    
    $available_rooms = [];
    $unavailable_rooms = [];
    
    while ($room = mysqli_fetch_assoc($rooms_result)) {
        // Check availability using homepage logic
        $bookings_query = "SELECT cin, cout, stat, payment_status, booking_ref 
                          FROM roombook 
                          WHERE TRoom = ? 
                          AND cout >= ? 
                          AND cin <= ?
                          AND stat NOT IN ('cancelled', 'completed')
                          AND stat != 'cleared_for_rebooking'
                          AND payment_status != 'failed'
                          ORDER BY cin ASC";
        
        $stmt = mysqli_prepare($con, $bookings_query);
        mysqli_stmt_bind_param($stmt, "sss", $room['room_name'], $checkin, $checkout);
        mysqli_stmt_execute($stmt);
        $bookings_result = mysqli_stmt_get_result($stmt);
        
        $occupied_dates = [];
        $room_bookings = [];
        
        while ($booking = mysqli_fetch_assoc($bookings_result)) {
            $room_bookings[] = $booking;
            
            // Generate all dates between check-in and check-out
            $start = new DateTime($booking['cin']);
            $end = new DateTime($booking['cout']);
            
            while ($start < $end) {
                $occupied_dates[] = $start->format('Y-m-d');
                $start->add(new DateInterval('P1D'));
            }
        }
        
        // Check if requested period is available
        $is_available = true;
        $check_date = clone $checkin_date;
        
        while ($check_date < $checkout_date) {
            if (in_array($check_date->format('Y-m-d'), $occupied_dates)) {
                $is_available = false;
                break;
            }
            $check_date->add(new DateInterval('P1D'));
        }
        
        // Add room data
        $room['nights'] = $nights;
        $room['total_price'] = $room['base_price'] * $nights;
        $room['checkin'] = $checkin;
        $room['checkout'] = $checkout;
        $room['occupied_dates'] = $occupied_dates;
        
        if ($is_available) {
            $available_rooms[] = $room;
        } else {
            // Find next availability
            $next_available = findNextAvailability($con, $room['room_name'], $checkout_date);
            if ($next_available) {
                $room['next_available_from'] = $next_available['from'];
                $room['next_available_to'] = $next_available['to'];
                $room['days_until_available'] = $next_available['days_until'];
                $room['available_periods'] = $next_available['periods'] ?? [];
            }
            $unavailable_rooms[] = $room;
        }
    }
    
    // Return response for luxury booking
    echo json_encode([
        'success' => true,
        'checkin' => $checkin,
        'checkout' => $checkout,
        'nights' => $nights,
        'available_rooms' => $available_rooms,
        'unavailable_rooms' => $unavailable_rooms,
        'total_available' => count($available_rooms),
        'total_unavailable' => count($unavailable_rooms)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid date format: ' . $e->getMessage()
    ]);
}

function findNextAvailability($con, $room_name, $from_date) {
    $check_days = 90; // Check next 90 days
    $today = $from_date->format('Y-m-d');
    $end_date = (clone $from_date)->add(new DateInterval("P{$check_days}D"))->format('Y-m-d');
    
    // Get all bookings for this room in the period
    $bookings_query = "SELECT cin, cout, stat, payment_status, booking_ref 
                       FROM roombook 
                       WHERE TRoom = ? 
                       AND cout >= ? 
                       AND cin <= ?
                       AND stat NOT IN ('cancelled', 'completed')
                       AND stat != 'cleared_for_rebooking'
                       AND payment_status != 'failed'
                       ORDER BY cin ASC";
    
    $stmt = mysqli_prepare($con, $bookings_query);
    mysqli_stmt_bind_param($stmt, "sss", $room_name, $today, $end_date);
    mysqli_stmt_execute($stmt);
    $bookings_result = mysqli_stmt_get_result($stmt);
    
    $occupied_dates = [];
    
    while ($booking = mysqli_fetch_assoc($bookings_result)) {
        // Generate all dates between check-in and check-out
        $start = new DateTime($booking['cin']);
        $end = new DateTime($booking['cout']);
        
        while ($start < $end) {
            $occupied_dates[] = $start->format('Y-m-d');
            $start->add(new DateInterval('P1D'));
        }
    }
    
    // Find next available periods
    $available_periods = [];
    $current_date = clone $from_date;
    $end_check = new DateTime($end_date);
    
    while ($current_date <= $end_check) {
        $date_str = $current_date->format('Y-m-d');
        
        if (!in_array($date_str, $occupied_dates)) {
            // Find how many consecutive days are available
            $consecutive_days = 0;
            $temp_date = clone $current_date;
            
            while ($temp_date <= $end_check && !in_array($temp_date->format('Y-m-d'), $occupied_dates)) {
                $consecutive_days++;
                $temp_date->add(new DateInterval('P1D'));
            }
            
            if ($consecutive_days >= 1) {
                $end_period = clone $temp_date;
                $end_period->sub(new DateInterval('P1D'));
                
                $available_periods[] = [
                    'start_date' => $date_str,
                    'end_date' => $end_period->format('Y-m-d'),
                    'days' => $consecutive_days,
                    'formatted_start' => $current_date->format('M j, Y'),
                    'formatted_end' => $end_period->format('M j, Y')
                ];
                
                // Skip ahead to avoid duplicates
                $current_date = clone $temp_date;
            } else {
                $current_date->add(new DateInterval('P1D'));
            }
        } else {
            $current_date->add(new DateInterval('P1D'));
        }
    }
    
    if (!empty($available_periods)) {
        $first_period = $available_periods[0];
        $days_until = $from_date->diff(new DateTime($first_period['start_date']))->days;
        
        return [
            'from' => $first_period['start_date'],
            'to' => $first_period['end_date'],
            'days_until' => $days_until,
            'periods' => array_slice($available_periods, 0, 3) // Show next 3 available periods
        ];
    }
    
    return null; // No availability found in the next 90 days
}
?>