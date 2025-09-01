<?php
/**
 * Orlando International Resorts - Guest Communication API
 * RESTful API for guest communication and concierge services
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Load dependencies
require_once '../db.php';
require_once '../auth.php';
require_once '../includes/GuestExperienceManager.php';

// Security and session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Rate limiting
$rate_limit = new RateLimiter('guest_communication_api', 100, 3600); // 100 requests per hour
if (!$rate_limit->allow()) {
    http_response_code(429);
    echo json_encode(['error' => 'Rate limit exceeded']);
    exit();
}

// Initialize guest experience manager
$guestManager = new GuestExperienceManager($con);

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// Input validation and sanitization
function validateAndSanitizeInput($data, $required_fields = []) {
    $sanitized = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            throw new InvalidArgumentException("Missing required field: $field");
        }
    }
    
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

// Audit logging
function logApiAccess($action, $user_id, $guest_id = null, $status = 'success', $details = []) {
    global $con;
    
    $sql = "INSERT INTO api_audit_log (
        endpoint, action, user_id, guest_id, status, 
        ip_address, user_agent, request_details, created_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $stmt = $con->prepare($sql);
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    $details_json = json_encode($details);
    
    $stmt->bind_param('ssisssss', 'guest-communication', $action, $user_id, 
                     $guest_id, $status, $ip, $user_agent, $details_json);
    $stmt->execute();
}

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($action, $guestManager);
            break;
            
        case 'POST':
            handlePostRequest($action, $guestManager);
            break;
            
        case 'PUT':
            handlePutRequest($action, $guestManager);
            break;
            
        case 'DELETE':
            handleDeleteRequest($action, $guestManager);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
    }

} catch (Exception $e) {
    error_log("Guest Communication API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error', 'message' => $e->getMessage()]);
}

/**
 * Handle GET requests
 */
function handleGetRequest($action, $guestManager) {
    global $con;
    
    switch ($action) {
        case 'threads':
            getGuestThreads($guestManager);
            break;
            
        case 'thread':
            getGuestThread($guestManager);
            break;
            
        case 'messages':
            getThreadMessages($guestManager);
            break;
            
        case 'stats':
            getCommunicationStats($guestManager);
            break;
            
        case 'active_guests':
            getActiveGuests($con);
            break;
            
        case 'staff_workload':
            getStaffWorkload($con);
            break;
            
        case 'escalations':
            getEscalations($con);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Handle POST requests
 */
function handlePostRequest($action, $guestManager) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'create_thread':
            createGuestThread($guestManager, $input);
            break;
            
        case 'send_message':
            sendMessage($guestManager, $input);
            break;
            
        case 'ai_response':
            generateAIResponse($guestManager, $input);
            break;
            
        case 'escalate':
            escalateThread($guestManager, $input);
            break;
            
        case 'assign_staff':
            assignStaffToThread($guestManager, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Handle PUT requests
 */
function handlePutRequest($action, $guestManager) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($action) {
        case 'update_thread':
            updateThreadStatus($guestManager, $input);
            break;
            
        case 'mark_resolved':
            markThreadResolved($guestManager, $input);
            break;
            
        case 'update_priority':
            updateThreadPriority($guestManager, $input);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
}

/**
 * Get guest communication threads
 */
function getGuestThreads($guestManager) {
    try {
        // Get filters
        $status = $_GET['status'] ?? 'all';
        $priority = $_GET['priority'] ?? 'all';
        $staff_id = $_GET['staff_id'] ?? null;
        $limit = min(100, (int)($_GET['limit'] ?? 50));
        $offset = (int)($_GET['offset'] ?? 0);
        
        global $con;
        
        // Build query
        $where_conditions = [];
        $params = [];
        $param_types = '';
        
        if ($status !== 'all') {
            $where_conditions[] = "gc.status = ?";
            $params[] = $status;
            $param_types .= 's';
        }
        
        if ($priority !== 'all') {
            $where_conditions[] = "gc.priority = ?";
            $params[] = $priority;
            $param_types .= 's';
        }
        
        if ($staff_id) {
            $where_conditions[] = "gc.assigned_staff_id = ?";
            $params[] = $staff_id;
            $param_types .= 'i';
        }
        
        $where_clause = '';
        if (!empty($where_conditions)) {
            $where_clause = 'WHERE ' . implode(' AND ', $where_conditions);
        }
        
        $sql = "SELECT gc.*, g.full_name, g.email, g.vip_status, 
                       r.room_type, s.full_name as staff_name,
                       (SELECT COUNT(*) FROM guest_messages WHERE thread_id = gc.thread_id) as message_count,
                       (SELECT created_at FROM guest_messages WHERE thread_id = gc.thread_id ORDER BY created_at DESC LIMIT 1) as last_message_time
                FROM guest_communications gc
                LEFT JOIN guests g ON gc.guest_id = g.id
                LEFT JOIN rooms r ON gc.room_number = r.room_number
                LEFT JOIN staff s ON gc.assigned_staff_id = s.id
                $where_clause
                ORDER BY gc.updated_at DESC, gc.priority DESC
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        $param_types .= 'ii';
        
        $stmt = $con->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $threads = [];
        while ($row = $result->fetch_assoc()) {
            $threads[] = $row;
        }
        
        // Get total count
        $count_sql = str_replace("SELECT gc.*, g.full_name, g.email, g.vip_status, r.room_type, s.full_name as staff_name, (SELECT COUNT(*) FROM guest_messages WHERE thread_id = gc.thread_id) as message_count, (SELECT created_at FROM guest_messages WHERE thread_id = gc.thread_id ORDER BY created_at DESC LIMIT 1) as last_message_time", "SELECT COUNT(*)", $sql);
        $count_sql = preg_replace('/LIMIT.*/', '', $count_sql);
        
        $count_stmt = $con->prepare($count_sql);
        if (!empty($params) && count($params) > 2) {
            $count_params = array_slice($params, 0, -2);
            $count_types = substr($param_types, 0, -2);
            $count_stmt->bind_param($count_types, ...$count_params);
        }
        $count_stmt->execute();
        $total_count = $count_stmt->get_result()->fetch_assoc()['COUNT(*)'];
        
        echo json_encode([
            'success' => true,
            'threads' => $threads,
            'pagination' => [
                'total' => $total_count,
                'limit' => $limit,
                'offset' => $offset,
                'has_more' => ($offset + $limit) < $total_count
            ]
        ]);
        
        logApiAccess('get_threads', $_SESSION['user_id'] ?? 0);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('get_threads', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Get specific guest thread with messages
 */
function getGuestThread($guestManager) {
    try {
        $thread_id = $_GET['thread_id'] ?? '';
        
        if (empty($thread_id)) {
            http_response_code(400);
            echo json_encode(['error' => 'Thread ID required']);
            return;
        }
        
        $result = $guestManager->getGuestThreadData($thread_id, true);
        
        if ($result['success']) {
            echo json_encode($result);
            logApiAccess('get_thread', $_SESSION['user_id'] ?? 0, $result['data']['guest_id'] ?? null);
        } else {
            http_response_code(404);
            echo json_encode(['error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('get_thread', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Get communication statistics
 */
function getCommunicationStats($guestManager) {
    try {
        $date_range = (int)($_GET['days'] ?? 7);
        
        $result = $guestManager->getGuestCommunicationStats($date_range);
        
        if ($result['success']) {
            echo json_encode($result);
            logApiAccess('get_stats', $_SESSION['user_id'] ?? 0);
        } else {
            http_response_code(500);
            echo json_encode(['error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('get_stats', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Create new guest communication thread
 */
function createGuestThread($guestManager, $input) {
    try {
        $data = validateAndSanitizeInput($input, ['guest_id', 'room_number']);
        
        $result = $guestManager->createGuestThread(
            $data['guest_id'],
            $data['room_number'],
            $data['initial_message'] ?? null,
            $data['channel'] ?? 'chat'
        );
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
            logApiAccess('create_thread', $_SESSION['user_id'] ?? 0, $data['guest_id']);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('create_thread', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Send message to thread
 */
function sendMessage($guestManager, $input) {
    try {
        $data = validateAndSanitizeInput($input, ['thread_id', 'message']);
        
        $sender_type = $data['sender_type'] ?? 'staff';
        $sender_id = $data['sender_id'] ?? $_SESSION['user_id'] ?? 0;
        $metadata = $data['metadata'] ?? [];
        
        $result = $guestManager->addMessageToThread(
            $data['thread_id'],
            $data['message'],
            $sender_type,
            $sender_id,
            $metadata
        );
        
        if ($result['success']) {
            http_response_code(201);
            echo json_encode($result);
            logApiAccess('send_message', $sender_id);
        } else {
            http_response_code(400);
            echo json_encode(['error' => $result['error']]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('send_message', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Get active guests for quick access
 */
function getActiveGuests($con) {
    try {
        $sql = "SELECT g.id, g.full_name, g.room_number, g.vip_status, 
                       g.check_in_date, g.check_out_date,
                       (SELECT COUNT(*) FROM guest_communications WHERE guest_id = g.id AND status = 'active') as active_threads
                FROM guests g
                WHERE g.status = 'checked_in'
                AND g.check_out_date >= CURDATE()
                ORDER BY g.vip_status DESC, g.full_name ASC";
        
        $result = $con->query($sql);
        $guests = [];
        
        while ($row = $result->fetch_assoc()) {
            $guests[] = $row;
        }
        
        echo json_encode(['success' => true, 'guests' => $guests]);
        logApiAccess('get_active_guests', $_SESSION['user_id'] ?? 0);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('get_active_guests', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Get staff workload for assignment
 */
function getStaffWorkload($con) {
    try {
        $sql = "SELECT s.id, s.full_name, s.department, s.status,
                       COUNT(gc.id) as active_threads,
                       AVG(TIMESTAMPDIFF(MINUTE, gc.created_at, COALESCE(gc.updated_at, NOW()))) as avg_handling_time
                FROM staff s
                LEFT JOIN guest_communications gc ON s.id = gc.assigned_staff_id AND gc.status = 'active'
                WHERE s.status = 'active'
                AND s.department IN ('guest_services', 'concierge', 'management')
                GROUP BY s.id
                ORDER BY active_threads ASC, s.department, s.full_name";
        
        $result = $con->query($sql);
        $staff = [];
        
        while ($row = $result->fetch_assoc()) {
            $staff[] = $row;
        }
        
        echo json_encode(['success' => true, 'staff' => $staff]);
        logApiAccess('get_staff_workload', $_SESSION['user_id'] ?? 0);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('get_staff_workload', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Update thread status
 */
function updateThreadStatus($guestManager, $input) {
    try {
        $data = validateAndSanitizeInput($input, ['thread_id', 'status']);
        
        global $con;
        
        $sql = "UPDATE guest_communications SET status = ?, updated_at = NOW() WHERE thread_id = ?";
        $stmt = $con->prepare($sql);
        $stmt->bind_param('ss', $data['status'], $data['thread_id']);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Thread status updated']);
            logApiAccess('update_thread_status', $_SESSION['user_id'] ?? 0);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Thread not found']);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        logApiAccess('update_thread_status', $_SESSION['user_id'] ?? 0, null, 'error', ['error' => $e->getMessage()]);
    }
}

/**
 * Simple rate limiter class
 */
class RateLimiter {
    private $key;
    private $limit;
    private $window;
    
    public function __construct($key, $limit, $window) {
        $this->key = $key;
        $this->limit = $limit;
        $this->window = $window;
    }
    
    public function allow() {
        $current_time = time();
        $window_start = $current_time - $this->window;
        
        // Clean old entries
        $this->cleanup($window_start);
        
        // Get current count
        $count = $this->getCount($window_start);
        
        if ($count >= $this->limit) {
            return false;
        }
        
        // Record this request
        $this->record($current_time);
        
        return true;
    }
    
    private function cleanup($window_start) {
        // Simple file-based rate limiting (use Redis in production)
        $file = sys_get_temp_dir() . '/rate_limit_' . md5($this->key);
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true) ?: [];
            $data = array_filter($data, function($time) use ($window_start) {
                return $time > $window_start;
            });
            file_put_contents($file, json_encode($data));
        }
    }
    
    private function getCount($window_start) {
        $file = sys_get_temp_dir() . '/rate_limit_' . md5($this->key);
        if (!file_exists($file)) {
            return 0;
        }
        
        $data = json_decode(file_get_contents($file), true) ?: [];
        return count(array_filter($data, function($time) use ($window_start) {
            return $time > $window_start;
        }));
    }
    
    private function record($time) {
        $file = sys_get_temp_dir() . '/rate_limit_' . md5($this->key);
        $data = file_exists($file) ? json_decode(file_get_contents($file), true) ?: [] : [];
        $data[] = $time;
        file_put_contents($file, json_encode($data));
    }
}
?>
