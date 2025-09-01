<?php
/**
 * Orlando International Resorts - Bookings & Reservations API
 * Real-time booking management with advanced search and filtering
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class BookingsAPI {
    private $db;
    private $permission_manager;
    private $event_manager;
    private $user_id;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->permission_manager = getPermissionManager();
        $this->event_manager = getEventManager();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get all bookings with filters
     */
    public function getBookings() {
        $this->checkPermission('booking.view');
        
        // Get filters from request
        $filters = [
            'status' => $_GET['status'] ?? '',
            'date_from' => $_GET['date_from'] ?? '',
            'date_to' => $_GET['date_to'] ?? '',
            'room_type' => $_GET['room_type'] ?? '',
            'guest_name' => $_GET['guest_name'] ?? '',
            'limit' => intval($_GET['limit'] ?? 50),
            'offset' => intval($_GET['offset'] ?? 0),
            'sort_by' => $_GET['sort_by'] ?? 'created_at',
            'sort_order' => $_GET['sort_order'] ?? 'DESC'
        ];
        
        $bookings = $this->fetchBookings($filters);
        $total_count = $this->getBookingsCount($filters);
        
        $this->sendSuccess([
            'bookings' => $bookings,
            'pagination' => [
                'total' => $total_count,
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
                'pages' => ceil($total_count / $filters['limit'])
            ],
            'filters_applied' => $filters
        ]);
    }
    
    /**
     * Get booking details by ID
     */
    public function getBookingDetails() {
        $this->checkPermission('booking.view');
        
        $booking_id = intval($_GET['id'] ?? 0);
        if (!$booking_id) {
            $this->sendError('Booking ID is required');
            return;
        }
        
        $booking = $this->fetchBookingById($booking_id);
        if (!$booking) {
            $this->sendError('Booking not found', 404);
            return;
        }
        
        // Get additional details
        $booking['payments'] = $this->getBookingPayments($booking_id);
        $booking['history'] = $this->getBookingHistory($booking_id);
        $booking['services'] = $this->getBookingServices($booking_id);
        
        $this->sendSuccess($booking);
    }
    
    /**
     * Create new booking
     */
    public function createBooking() {
        $this->checkPermission('booking.create');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Validate required fields
        $required_fields = ['guest_name', 'email', 'phone', 'checkin_date', 'checkout_date', 'room_type'];
        foreach ($required_fields as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Check room availability
        $available_room = $this->findAvailableRoom(
            $input['room_type'],
            $input['checkin_date'],
            $input['checkout_date']
        );
        
        if (!$available_room) {
            $this->sendError('No rooms available for the selected dates');
            return;
        }
        
        // Calculate pricing
        $pricing = $this->calculateBookingPrice(
            $available_room['id'],
            $input['checkin_date'],
            $input['checkout_date'],
            $input['guests'] ?? 2
        );
        
        // Create booking
        $booking_data = [
            'room_id' => $available_room['id'],
            'guest_name' => $input['guest_name'],
            'email' => $input['email'],
            'phone' => $input['phone'],
            'checkin_date' => $input['checkin_date'],
            'checkout_date' => $input['checkout_date'],
            'adults' => $input['adults'] ?? 2,
            'children' => $input['children'] ?? 0,
            'total_amount' => $pricing['total'],
            'status' => 'confirmed',
            'special_requests' => $input['special_requests'] ?? '',
            'created_by' => $this->user_id
        ];
        
        $booking_id = $this->insertBooking($booking_data);
        
        if ($booking_id) {
            // Update room status
            $this->updateRoomStatus($available_room['id'], 'reserved');
            
            // Trigger booking created event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('booking.created', [
                    'booking_id' => $booking_id,
                    'room_id' => $available_room['id'],
                    'guest_name' => $input['guest_name'],
                    'guest_email' => $input['email'],
                    'checkin_date' => $input['checkin_date'],
                    'checkout_date' => $input['checkout_date'],
                    'total_amount' => $pricing['total']
                ]);
            }
            
            // Create initial payment record if deposit provided
            if (!empty($input['deposit_amount'])) {
                $this->createPaymentRecord($booking_id, $input['deposit_amount'], 'deposit');
            }
            
            $this->sendSuccess([
                'booking_id' => $booking_id,
                'room_number' => $available_room['room_number'],
                'total_amount' => $pricing['total'],
                'confirmation_code' => $this->generateConfirmationCode($booking_id)
            ], 'Booking created successfully');
        } else {
            $this->sendError('Failed to create booking');
        }
    }
    
    /**
     * Update existing booking
     */
    public function updateBooking() {
        $this->checkPermission('booking.modify');
        
        $booking_id = intval($_GET['id'] ?? 0);
        if (!$booking_id) {
            $this->sendError('Booking ID is required');
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Get current booking
        $current_booking = $this->fetchBookingById($booking_id);
        if (!$current_booking) {
            $this->sendError('Booking not found', 404);
            return;
        }
        
        // Check if dates are being changed
        $dates_changed = (
            !empty($input['checkin_date']) && $input['checkin_date'] !== $current_booking['checkin_date']
        ) || (
            !empty($input['checkout_date']) && $input['checkout_date'] !== $current_booking['checkout_date']
        );
        
        if ($dates_changed) {
            // Check availability for new dates
            $available = $this->checkRoomAvailability(
                $current_booking['room_id'],
                $input['checkin_date'] ?? $current_booking['checkin_date'],
                $input['checkout_date'] ?? $current_booking['checkout_date'],
                $booking_id // Exclude current booking
            );
            
            if (!$available) {
                $this->sendError('Room not available for the new dates');
                return;
            }
        }
        
        // Update booking
        $update_data = array_intersect_key($input, array_flip([
            'guest_name', 'email', 'phone', 'checkin_date', 'checkout_date',
            'adults', 'children', 'special_requests', 'status'
        ]));
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            $update_data['updated_by'] = $this->user_id;
            
            $success = $this->updateBookingRecord($booking_id, $update_data);
            
            if ($success) {
                // Log booking history
                $this->logBookingHistory($booking_id, 'updated', $update_data);
                
                // Trigger booking updated event
                if ($this->event_manager) {
                    $this->event_manager->triggerEvent('booking.updated', [
                        'booking_id' => $booking_id,
                        'changes' => $update_data,
                        'updated_by' => $this->user_id
                    ]);
                }
                
                $this->sendSuccess(['booking_id' => $booking_id], 'Booking updated successfully');
            } else {
                $this->sendError('Failed to update booking');
            }
        } else {
            $this->sendError('No valid fields to update');
        }
    }
    
    /**
     * Cancel booking
     */
    public function cancelBooking() {
        $this->checkPermission('booking.cancel');
        
        $booking_id = intval($_GET['id'] ?? 0);
        if (!$booking_id) {
            $this->sendError('Booking ID is required');
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        $reason = $input['reason'] ?? 'No reason provided';
        
        // Get current booking
        $booking = $this->fetchBookingById($booking_id);
        if (!$booking) {
            $this->sendError('Booking not found', 404);
            return;
        }
        
        if ($booking['status'] === 'cancelled') {
            $this->sendError('Booking is already cancelled');
            return;
        }
        
        // Update booking status
        $update_data = [
            'status' => 'cancelled',
            'cancellation_reason' => $reason,
            'cancelled_at' => date('Y-m-d H:i:s'),
            'cancelled_by' => $this->user_id
        ];
        
        $success = $this->updateBookingRecord($booking_id, $update_data);
        
        if ($success) {
            // Free up the room
            $this->updateRoomStatus($booking['room_id'], 'available');
            
            // Process refund if applicable
            $refund_amount = $this->calculateRefundAmount($booking_id, $reason);
            if ($refund_amount > 0) {
                $this->processRefund($booking_id, $refund_amount);
            }
            
            // Log cancellation
            $this->logBookingHistory($booking_id, 'cancelled', $update_data);
            
            // Trigger booking cancelled event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('booking.cancelled', [
                    'booking_id' => $booking_id,
                    'room_id' => $booking['room_id'],
                    'guest_name' => $booking['guest_name'],
                    'reason' => $reason,
                    'refund_amount' => $refund_amount,
                    'cancelled_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'booking_id' => $booking_id,
                'refund_amount' => $refund_amount
            ], 'Booking cancelled successfully');
        } else {
            $this->sendError('Failed to cancel booking');
        }
    }
    
    /**
     * Get booking calendar data
     */
    public function getBookingCalendar() {
        $this->checkPermission('booking.view');
        
        $month = $_GET['month'] ?? date('Y-m');
        $view = $_GET['view'] ?? 'month'; // month, week, day
        
        $calendar_data = $this->generateCalendarData($month, $view);
        
        $this->sendSuccess($calendar_data);
    }
    
    /**
     * Check room availability
     */
    public function checkAvailability() {
        $checkin = $_GET['checkin'] ?? '';
        $checkout = $_GET['checkout'] ?? '';
        $room_type = $_GET['room_type'] ?? '';
        $guests = intval($_GET['guests'] ?? 2);
        
        if (!$checkin || !$checkout) {
            $this->sendError('Check-in and check-out dates are required');
            return;
        }
        
        $available_rooms = $this->getAvailableRooms($checkin, $checkout, $room_type, $guests);
        
        $this->sendSuccess([
            'available_rooms' => $available_rooms,
            'checkin_date' => $checkin,
            'checkout_date' => $checkout,
            'guests' => $guests
        ]);
    }
    
    /**
     * Process check-in
     */
    public function processCheckin() {
        $this->checkPermission('booking.checkin');
        
        $booking_id = intval($_GET['id'] ?? 0);
        if (!$booking_id) {
            $this->sendError('Booking ID is required');
            return;
        }
        
        $booking = $this->fetchBookingById($booking_id);
        if (!$booking) {
            $this->sendError('Booking not found', 404);
            return;
        }
        
        if ($booking['status'] !== 'confirmed') {
            $this->sendError('Booking must be confirmed before check-in');
            return;
        }
        
        // Update booking status
        $update_data = [
            'status' => 'checked_in',
            'actual_checkin' => date('Y-m-d H:i:s'),
            'checked_in_by' => $this->user_id
        ];
        
        $success = $this->updateBookingRecord($booking_id, $update_data);
        
        if ($success) {
            // Update room status
            $this->updateRoomStatus($booking['room_id'], 'occupied');
            
            // Create room key cards, welcome package, etc.
            $this->processCheckinServices($booking_id);
            
            // Trigger check-in event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('booking.checkin', [
                    'booking_id' => $booking_id,
                    'room_id' => $booking['room_id'],
                    'guest_name' => $booking['guest_name'],
                    'checked_in_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess(['booking_id' => $booking_id], 'Check-in processed successfully');
        } else {
            $this->sendError('Failed to process check-in');
        }
    }
    
    /**
     * Process check-out
     */
    public function processCheckout() {
        $this->checkPermission('booking.checkout');
        
        $booking_id = intval($_GET['id'] ?? 0);
        if (!$booking_id) {
            $this->sendError('Booking ID is required');
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $booking = $this->fetchBookingById($booking_id);
        if (!$booking) {
            $this->sendError('Booking not found', 404);
            return;
        }
        
        if ($booking['status'] !== 'checked_in') {
            $this->sendError('Guest must be checked in before checkout');
            return;
        }
        
        // Calculate final bill
        $final_bill = $this->calculateFinalBill($booking_id, $input);
        
        // Update booking status
        $update_data = [
            'status' => 'checked_out',
            'actual_checkout' => date('Y-m-d H:i:s'),
            'final_amount' => $final_bill['total'],
            'checked_out_by' => $this->user_id
        ];
        
        $success = $this->updateBookingRecord($booking_id, $update_data);
        
        if ($success) {
            // Update room status to cleaning
            $this->updateRoomStatus($booking['room_id'], 'cleaning');
            
            // Process final payment if needed
            if ($final_bill['balance_due'] > 0) {
                $this->processFinalPayment($booking_id, $final_bill['balance_due']);
            }
            
            // Trigger checkout event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('booking.checkout', [
                    'booking_id' => $booking_id,
                    'room_id' => $booking['room_id'],
                    'guest_name' => $booking['guest_name'],
                    'final_amount' => $final_bill['total'],
                    'checked_out_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'booking_id' => $booking_id,
                'final_bill' => $final_bill
            ], 'Check-out processed successfully');
        } else {
            $this->sendError('Failed to process check-out');
        }
    }
    
    // Helper Methods
    
    private function fetchBookings($filters) {
        $where_conditions = ["1=1"];
        $params = [];
        $param_types = "";
        
        if ($filters['status']) {
            $where_conditions[] = "b.status = ?";
            $params[] = $filters['status'];
            $param_types .= "s";
        }
        
        if ($filters['date_from']) {
            $where_conditions[] = "b.checkin_date >= ?";
            $params[] = $filters['date_from'];
            $param_types .= "s";
        }
        
        if ($filters['date_to']) {
            $where_conditions[] = "b.checkout_date <= ?";
            $params[] = $filters['date_to'];
            $param_types .= "s";
        }
        
        if ($filters['room_type']) {
            $where_conditions[] = "r.room_type = ?";
            $params[] = $filters['room_type'];
            $param_types .= "s";
        }
        
        if ($filters['guest_name']) {
            $where_conditions[] = "b.guest_name LIKE ?";
            $params[] = "%" . $filters['guest_name'] . "%";
            $param_types .= "s";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
            b.*,
            r.room_number,
            r.room_type,
            r.floor,
            u.full_name as created_by_name
            FROM bookings b
            LEFT JOIN named_rooms r ON b.room_id = r.id
            LEFT JOIN users u ON b.created_by = u.id
            WHERE $where_clause
            ORDER BY {$filters['sort_by']} {$filters['sort_order']}
            LIMIT ? OFFSET ?";
        
        $params[] = $filters['limit'];
        $params[] = $filters['offset'];
        $param_types .= "ii";
        
        $stmt = $this->db->prepare($query);
        if ($param_types) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $bookings = [];
        
        while ($row = $result->fetch_assoc()) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    private function checkPermission($permission) {
        if (!$this->permission_manager || !$this->permission_manager->hasPermission($this->user_id, $permission)) {
            $this->sendError('Permission denied', 403);
            exit();
        }
    }
    
    private function sendSuccess($data, $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ]);
    }
}

// Handle API requests
try {
    ensure_logged_in();
    
    $api = new BookingsAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'list':
            $api->getBookings();
            break;
            
        case 'details':
            $api->getBookingDetails();
            break;
            
        case 'create':
            $api->createBooking();
            break;
            
        case 'update':
            $api->updateBooking();
            break;
            
        case 'cancel':
            $api->cancelBooking();
            break;
            
        case 'calendar':
            $api->getBookingCalendar();
            break;
            
        case 'availability':
            $api->checkAvailability();
            break;
            
        case 'checkin':
            $api->processCheckin();
            break;
            
        case 'checkout':
            $api->processCheckout();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Bookings API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
