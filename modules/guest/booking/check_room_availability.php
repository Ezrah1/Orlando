<?php
header('Content-Type: application/json');
include('../../../db.php');

$checkin = $_GET['checkin'] ?? '';
$checkout = $_GET['checkout'] ?? '';

if (empty($checkin) || empty($checkout)) {
    echo json_encode(['error' => 'Missing check-in or check-out date']);
    exit();
}

try {
    // Debug: Log input dates
    error_log("API Debug - Checkin: $checkin, Checkout: $checkout");
    
    // Parse dates
    $checkin_date = new DateTime($checkin);
    $checkout_date = new DateTime($checkout);
    
    error_log("API Debug - Parsed dates: " . $checkin_date->format('Y-m-d') . " to " . $checkout_date->format('Y-m-d'));
    
    if ($checkin_date >= $checkout_date) {
        echo json_encode(['error' => 'Check-out date must be after check-in date']);
        exit();
    }
    
    $checkin_str = $checkin_date->format('Y-m-d');
    $checkout_str = $checkout_date->format('Y-m-d');
    
    // Get all rooms with their details
    $rooms_query = "SELECT * FROM named_rooms ORDER BY base_price DESC, room_name ASC";
    $rooms_result = mysqli_query($con, $rooms_query);
    
    $available_rooms = [];
    $unavailable_rooms = [];
    
    while ($room = mysqli_fetch_assoc($rooms_result)) {
        // Get all bookings for this room in the requested period (using homepage logic)
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
        mysqli_stmt_bind_param($stmt, "sss", $room['room_name'], $checkin_str, $checkout_str);
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
        
        $room['nights'] = $checkin_date->diff($checkout_date)->days;
        $room['total_price'] = $room['base_price'] * $room['nights'];
        $room['checkin'] = $checkin_str;
        $room['checkout'] = $checkout_str;
        $room['occupied_dates'] = $occupied_dates;
        
        if ($is_available) {
            // Room is available for the full requested period
            $available_rooms[] = $room;
        } else {
            // Room is not available, find next availability using improved logic
            $next_available = findNextAvailabilityImproved($con, $room['room_name'], $checkout_date);
            $room['next_available_from'] = $next_available['from'] ?? null;
            $room['next_available_to'] = $next_available['to'] ?? null;
            $room['days_until_available'] = $next_available['days_until'] ?? null;
            $room['available_periods'] = $next_available['periods'] ?? [];
            $unavailable_rooms[] = $room;
        }
    }
    
    // Sort available rooms by popularity (you can implement your own logic)
    // For now, sort by price (higher price rooms first, assuming they're more popular)
    usort($available_rooms, function($a, $b) {
        return $b['base_price'] - $a['base_price'];
    });
    
    echo json_encode([
        'success' => true,
        'checkin' => $checkin_str,
        'checkout' => $checkout_str,
        'nights' => $checkin_date->diff($checkout_date)->days,
        'available_rooms' => $available_rooms,
        'unavailable_rooms' => $unavailable_rooms,
        'total_available' => count($available_rooms),
        'total_unavailable' => count($unavailable_rooms)
    ]);
    
} catch (Exception $e) {
    error_log("API Error: " . $e->getMessage());
    echo json_encode(['error' => 'Invalid date format: ' . $e->getMessage()]);
}

function findNextAvailabilityImproved($con, $room_name, $from_date) {
    $check_days = 90; // Check next 90 days
    $today = $from_date->format('Y-m-d');
    $end_date = (clone $from_date)->add(new DateInterval("P{$check_days}D"))->format('Y-m-d');
    
    // Get all bookings for this room in the period using homepage logic
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
    
    // Find next available periods (using homepage logic)
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
