<?php
/**
 * Orlando International Resorts - WebSocket Notification Handler
 * Real-time WebSocket server for instant notifications
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// This file provides WebSocket functionality using Ratchet/ReactPHP
// For production deployment, run this as a separate service

require_once __DIR__ . '/../includes/NotificationEngine.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../db.php';

/**
 * WebSocket Notification Server
 * Handles real-time connections and message broadcasting
 */
class WebSocketNotificationServer {
    private $connections;
    private $users;
    private $notification_engine;
    private $permission_manager;
    private $db;
    
    public function __construct($database_connection) {
        $this->connections = new SplObjectStorage;
        $this->users = [];
        $this->db = $database_connection;
        $this->notification_engine = getNotificationEngine();
        $this->permission_manager = getPermissionManager();
        
        echo "WebSocket Notification Server initialized\n";
    }
    
    /**
     * Handle new WebSocket connection
     */
    public function onOpen($conn) {
        $this->connections->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send welcome message
        $welcome_message = [
            'type' => 'connection',
            'message' => 'Connected to Orlando International Resorts notification service',
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($welcome_message));
    }
    
    /**
     * Handle incoming WebSocket messages
     */
    public function onMessage($from, $msg) {
        try {
            $data = json_decode($msg, true);
            
            if (!$data || !isset($data['type'])) {
                $this->sendError($from, 'Invalid message format');
                return;
            }
            
            switch ($data['type']) {
                case 'authenticate':
                    $this->handleAuthentication($from, $data);
                    break;
                    
                case 'subscribe':
                    $this->handleSubscription($from, $data);
                    break;
                    
                case 'unsubscribe':
                    $this->handleUnsubscription($from, $data);
                    break;
                    
                case 'mark_read':
                    $this->handleMarkAsRead($from, $data);
                    break;
                    
                case 'get_notifications':
                    $this->handleGetNotifications($from, $data);
                    break;
                    
                case 'ping':
                    $this->handlePing($from);
                    break;
                    
                default:
                    $this->sendError($from, 'Unknown message type');
            }
            
        } catch (Exception $e) {
            error_log("WebSocket message error: " . $e->getMessage());
            $this->sendError($from, 'Message processing error');
        }
    }
    
    /**
     * Handle connection close
     */
    public function onClose($conn) {
        // Remove user from authenticated users
        if (isset($this->users[$conn->resourceId])) {
            $user_info = $this->users[$conn->resourceId];
            echo "User {$user_info['username']} disconnected ({$conn->resourceId})\n";
            unset($this->users[$conn->resourceId]);
        }
        
        $this->connections->detach($conn);
        echo "Connection {$conn->resourceId} has disconnected\n";
    }
    
    /**
     * Handle connection errors
     */
    public function onError($conn, $e) {
        error_log("WebSocket error: " . $e->getMessage());
        $conn->close();
    }
    
    /**
     * Handle user authentication
     */
    private function handleAuthentication($conn, $data) {
        if (!isset($data['session_id']) || !isset($data['user_id'])) {
            $this->sendError($conn, 'Authentication data missing');
            return;
        }
        
        // Validate session
        if ($this->validateUserSession($data['session_id'], $data['user_id'])) {
            $user_info = $this->getUserInfo($data['user_id']);
            
            if ($user_info) {
                $this->users[$conn->resourceId] = [
                    'user_id' => $data['user_id'],
                    'username' => $user_info['username'],
                    'role' => $user_info['role'],
                    'session_id' => $data['session_id'],
                    'connected_at' => time(),
                    'subscriptions' => []
                ];
                
                echo "User {$user_info['username']} authenticated ({$conn->resourceId})\n";
                
                // Send authentication success
                $auth_response = [
                    'type' => 'authenticated',
                    'user_id' => $data['user_id'],
                    'username' => $user_info['username'],
                    'role' => $user_info['role'],
                    'timestamp' => time()
                ];
                
                $conn->send(json_encode($auth_response));
                
                // Send pending notifications
                $this->sendPendingNotifications($conn, $data['user_id']);
                
            } else {
                $this->sendError($conn, 'User not found');
            }
        } else {
            $this->sendError($conn, 'Invalid session');
        }
    }
    
    /**
     * Handle subscription to notification categories
     */
    private function handleSubscription($conn, $data) {
        if (!isset($this->users[$conn->resourceId])) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }
        
        $categories = $data['categories'] ?? [];
        if (!is_array($categories)) {
            $categories = [$categories];
        }
        
        $user_info = &$this->users[$conn->resourceId];
        foreach ($categories as $category) {
            if (!in_array($category, $user_info['subscriptions'])) {
                $user_info['subscriptions'][] = $category;
            }
        }
        
        echo "User {$user_info['username']} subscribed to: " . implode(', ', $categories) . "\n";
        
        $response = [
            'type' => 'subscribed',
            'categories' => $categories,
            'all_subscriptions' => $user_info['subscriptions'],
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($response));
    }
    
    /**
     * Handle unsubscription from notification categories
     */
    private function handleUnsubscription($conn, $data) {
        if (!isset($this->users[$conn->resourceId])) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }
        
        $categories = $data['categories'] ?? [];
        if (!is_array($categories)) {
            $categories = [$categories];
        }
        
        $user_info = &$this->users[$conn->resourceId];
        foreach ($categories as $category) {
            $key = array_search($category, $user_info['subscriptions']);
            if ($key !== false) {
                unset($user_info['subscriptions'][$key]);
            }
        }
        
        $user_info['subscriptions'] = array_values($user_info['subscriptions']);
        
        $response = [
            'type' => 'unsubscribed',
            'categories' => $categories,
            'remaining_subscriptions' => $user_info['subscriptions'],
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($response));
    }
    
    /**
     * Handle mark notification as read
     */
    private function handleMarkAsRead($conn, $data) {
        if (!isset($this->users[$conn->resourceId])) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }
        
        $notification_id = $data['notification_id'] ?? null;
        $user_id = $this->users[$conn->resourceId]['user_id'];
        
        if ($notification_id) {
            if ($this->notification_engine) {
                $success = $this->notification_engine->markAsRead($notification_id, $user_id);
                
                $response = [
                    'type' => 'mark_read_response',
                    'notification_id' => $notification_id,
                    'success' => $success,
                    'timestamp' => time()
                ];
                
                $conn->send(json_encode($response));
                
                // Broadcast updated unread count
                $this->sendUnreadCountUpdate($user_id);
            }
        } else {
            $this->sendError($conn, 'Notification ID missing');
        }
    }
    
    /**
     * Handle get notifications request
     */
    private function handleGetNotifications($conn, $data) {
        if (!isset($this->users[$conn->resourceId])) {
            $this->sendError($conn, 'Not authenticated');
            return;
        }
        
        $user_id = $this->users[$conn->resourceId]['user_id'];
        $options = [
            'limit' => $data['limit'] ?? 20,
            'offset' => $data['offset'] ?? 0,
            'unread_only' => $data['unread_only'] ?? false,
            'category' => $data['category'] ?? null
        ];
        
        if ($this->notification_engine) {
            $notifications = $this->notification_engine->getUserNotifications($user_id, $options);
            $unread_count = $this->notification_engine->getUnreadCount($user_id);
            
            $response = [
                'type' => 'notifications',
                'notifications' => $notifications,
                'unread_count' => $unread_count,
                'options' => $options,
                'timestamp' => time()
            ];
            
            $conn->send(json_encode($response));
        }
    }
    
    /**
     * Handle ping for connection keep-alive
     */
    private function handlePing($conn) {
        $response = [
            'type' => 'pong',
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($response));
    }
    
    /**
     * Send pending notifications to newly connected user
     */
    private function sendPendingNotifications($conn, $user_id) {
        if ($this->notification_engine) {
            $notifications = $this->notification_engine->getUserNotifications($user_id, [
                'limit' => 10,
                'unread_only' => true
            ]);
            
            if (!empty($notifications)) {
                $pending_message = [
                    'type' => 'pending_notifications',
                    'notifications' => $notifications,
                    'count' => count($notifications),
                    'timestamp' => time()
                ];
                
                $conn->send(json_encode($pending_message));
            }
        }
    }
    
    /**
     * Broadcast notification to all relevant users
     */
    public function broadcastNotification($notification_data) {
        if ($this->connections->count() === 0) {
            return;
        }
        
        $message = [
            'type' => 'notification',
            'data' => $notification_data,
            'timestamp' => time()
        ];
        
        foreach ($this->connections as $conn) {
            if (isset($this->users[$conn->resourceId])) {
                $user_info = $this->users[$conn->resourceId];
                
                // Check if user should receive this notification
                if ($this->shouldReceiveNotification($user_info, $notification_data)) {
                    try {
                        $conn->send(json_encode($message));
                    } catch (Exception $e) {
                        error_log("Failed to send notification to user {$user_info['user_id']}: " . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Send notification to specific user
     */
    public function sendNotificationToUser($user_id, $notification_data) {
        $message = [
            'type' => 'notification',
            'data' => $notification_data,
            'timestamp' => time()
        ];
        
        foreach ($this->connections as $conn) {
            if (isset($this->users[$conn->resourceId]) && 
                $this->users[$conn->resourceId]['user_id'] == $user_id) {
                
                try {
                    $conn->send(json_encode($message));
                } catch (Exception $e) {
                    error_log("Failed to send notification to user $user_id: " . $e->getMessage());
                }
                break;
            }
        }
    }
    
    /**
     * Send unread count update
     */
    private function sendUnreadCountUpdate($user_id) {
        if ($this->notification_engine) {
            $unread_count = $this->notification_engine->getUnreadCount($user_id);
            
            $message = [
                'type' => 'unread_count_update',
                'unread_count' => $unread_count,
                'timestamp' => time()
            ];
            
            $this->sendNotificationToUser($user_id, $message);
        }
    }
    
    /**
     * Check if user should receive notification
     */
    private function shouldReceiveNotification($user_info, $notification_data) {
        // Check if it's a broadcast notification
        if ($notification_data['is_broadcast']) {
            return true;
        }
        
        // Check if it's targeted to this specific user
        if (isset($notification_data['user_id']) && 
            $notification_data['user_id'] == $user_info['user_id']) {
            return true;
        }
        
        // Check if it's targeted to user's role
        if (isset($notification_data['role']) && 
            $notification_data['role'] == $user_info['role']) {
            return true;
        }
        
        // Check subscription preferences
        if (isset($notification_data['category']) && 
            in_array($notification_data['category'], $user_info['subscriptions'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Validate user session
     */
    private function validateUserSession($session_id, $user_id) {
        try {
            $query = "SELECT COUNT(*) as count FROM user_sessions 
                     WHERE user_id = ? AND session_token = ? AND is_active = 1";
            
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("is", $user_id, $session_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $data = $result->fetch_assoc();
            
            return $data['count'] > 0;
            
        } catch (Exception $e) {
            error_log("Session validation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user information
     */
    private function getUserInfo($user_id) {
        try {
            $query = "SELECT id, username, email, role, full_name FROM users WHERE id = ? AND status = 'active'";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Get user info error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Send error message to connection
     */
    private function sendError($conn, $message) {
        $error_response = [
            'type' => 'error',
            'message' => $message,
            'timestamp' => time()
        ];
        
        $conn->send(json_encode($error_response));
    }
    
    /**
     * Get connection statistics
     */
    public function getConnectionStats() {
        $stats = [
            'total_connections' => $this->connections->count(),
            'authenticated_users' => count($this->users),
            'users' => []
        ];
        
        foreach ($this->users as $resource_id => $user_info) {
            $stats['users'][] = [
                'resource_id' => $resource_id,
                'user_id' => $user_info['user_id'],
                'username' => $user_info['username'],
                'role' => $user_info['role'],
                'connected_at' => $user_info['connected_at'],
                'subscriptions' => $user_info['subscriptions']
            ];
        }
        
        return $stats;
    }
}

// WebSocket Server Startup Script
if (php_sapi_name() === 'cli') {
    echo "Starting Orlando International Resorts WebSocket Notification Server...\n";
    
    // This would require Ratchet/ReactPHP for full WebSocket support
    // For now, we provide the structure and handlers
    
    echo "WebSocket server configuration:\n";
    echo "- Host: 0.0.0.0\n";
    echo "- Port: 8080\n";
    echo "- Protocol: WebSocket\n";
    echo "- Authentication: Session-based\n";
    echo "- Real-time notifications: Enabled\n";
    
    /*
    // Example Ratchet implementation:
    use Ratchet\Server\IoServer;
    use Ratchet\Http\HttpServer;
    use Ratchet\WebSocket\WsServer;
    
    require dirname(__DIR__) . '/vendor/autoload.php';
    
    $server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new WebSocketNotificationServer($con)
            )
        ),
        8080
    );
    
    $server->run();
    */
    
    echo "Note: For production deployment, install Ratchet/ReactPHP and uncomment the server code.\n";
    echo "WebSocket server structure is ready for implementation.\n";
} else {
    // HTTP endpoint for WebSocket server management
    header('Content-Type: application/json');
    
    // Simple endpoint to check server status
    echo json_encode([
        'websocket_server' => 'configured',
        'status' => 'ready_for_deployment',
        'host' => '0.0.0.0',
        'port' => 8080,
        'features' => [
            'real_time_notifications',
            'user_authentication',
            'role_based_broadcasting',
            'subscription_management',
            'connection_statistics'
        ]
    ]);
}
?>
