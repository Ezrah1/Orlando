<?php
/**
 * Orlando International Resorts - Guest Experience Manager
 * Smart concierge and communication hub for enhanced guest services
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class GuestExperienceManager {
    private $db;
    private $config;
    private $notificationEngine;
    private $aiConcierge;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->config = $this->loadConfiguration();
        $this->notificationEngine = new NotificationEngine($database_connection);
        $this->aiConcierge = new AIConcierge($this->config);
    }
    
    /**
     * Load guest experience configuration
     */
    private function loadConfiguration() {
        return [
            'concierge_enabled' => true,
            'auto_response_enabled' => true,
            'sentiment_analysis_enabled' => true,
            'recommendation_engine_enabled' => true,
            'multilingual_support' => ['en', 'es', 'fr', 'de'],
            'response_time_target' => 300, // 5 minutes
            'escalation_threshold' => 2, // escalate after 2 failed attempts
            'vip_guest_priority' => true,
            'ai_confidence_threshold' => 0.8
        ];
    }
    
    /**
     * Create new guest communication thread
     */
    public function createGuestThread($guest_id, $room_number, $initial_message = null, $channel = 'chat') {
        try {
            $thread_id = $this->generateThreadId();
            
            // Get guest information
            $guest_info = $this->getGuestInformation($guest_id);
            if (!$guest_info) {
                throw new Exception("Guest information not found");
            }
            
            // Create communication thread
            $sql = "INSERT INTO guest_communications (
                thread_id, guest_id, room_number, channel, status, 
                priority, language, created_at, updated_at
            ) VALUES (?, ?, ?, ?, 'active', ?, ?, NOW(), NOW())";
            
            $priority = $this->calculateGuestPriority($guest_info);
            $language = $guest_info['preferred_language'] ?? 'en';
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sissss', $thread_id, $guest_id, $room_number, 
                             $channel, $priority, $language);
            $stmt->execute();
            
            // Add initial message if provided
            if ($initial_message) {
                $this->addMessageToThread($thread_id, $initial_message, 'guest', $guest_id);
                
                // Generate AI response if enabled
                if ($this->config['auto_response_enabled']) {
                    $this->generateAIResponse($thread_id, $initial_message, $guest_info);
                }
            }
            
            // Notify appropriate staff
            $this->notifyStaffOfNewThread($thread_id, $guest_info, $priority);
            
            return [
                'success' => true,
                'thread_id' => $thread_id,
                'priority' => $priority,
                'estimated_response_time' => $this->calculateResponseTime($priority)
            ];
            
        } catch (Exception $e) {
            error_log("Guest Experience Manager - Create Thread Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Add message to communication thread
     */
    public function addMessageToThread($thread_id, $message, $sender_type, $sender_id, $metadata = []) {
        try {
            // Analyze message sentiment
            $sentiment = $this->analyzeSentiment($message);
            
            // Detect message intent
            $intent = $this->detectIntent($message);
            
            // Store message
            $sql = "INSERT INTO guest_messages (
                thread_id, message, sender_type, sender_id, 
                sentiment_score, intent, metadata, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $metadata_json = json_encode($metadata);
            $stmt->bind_param('sssisss', $thread_id, $message, $sender_type, 
                             $sender_id, $sentiment, $intent, $metadata_json);
            $stmt->execute();
            
            $message_id = $this->db->insert_id;
            
            // Update thread activity
            $this->updateThreadActivity($thread_id, $sender_type);
            
            // Handle automatic responses
            if ($sender_type === 'guest') {
                $this->processGuestMessage($thread_id, $message, $intent, $sentiment);
            }
            
            // Real-time notification
            $this->broadcastMessageUpdate($thread_id, $message_id, $sender_type);
            
            return [
                'success' => true,
                'message_id' => $message_id,
                'sentiment' => $sentiment,
                'intent' => $intent
            ];
            
        } catch (Exception $e) {
            error_log("Guest Experience Manager - Add Message Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Process guest message for automatic responses
     */
    private function processGuestMessage($thread_id, $message, $intent, $sentiment) {
        // Handle urgent/negative sentiment
        if ($sentiment < -0.5) {
            $this->escalateToManager($thread_id, 'negative_sentiment', $sentiment);
        }
        
        // Handle specific intents
        switch ($intent) {
            case 'room_service_request':
                $this->handleRoomServiceRequest($thread_id, $message);
                break;
                
            case 'housekeeping_request':
                $this->handleHousekeepingRequest($thread_id, $message);
                break;
                
            case 'maintenance_request':
                $this->handleMaintenanceRequest($thread_id, $message);
                break;
                
            case 'concierge_request':
                $this->handleConciergeRequest($thread_id, $message);
                break;
                
            case 'complaint':
                $this->handleComplaint($thread_id, $message, $sentiment);
                break;
                
            case 'general_inquiry':
                $this->handleGeneralInquiry($thread_id, $message);
                break;
                
            default:
                // Generate AI response for unclassified messages
                if ($this->config['auto_response_enabled']) {
                    $this->generateAIResponseForThread($thread_id, $message);
                }
        }
    }
    
    /**
     * Handle room service requests
     */
    private function handleRoomServiceRequest($thread_id, $message) {
        // Extract room service items using NLP
        $items = $this->extractRoomServiceItems($message);
        
        // Create room service order
        if (!empty($items)) {
            $order_id = $this->createRoomServiceOrder($thread_id, $items);
            
            $response = "I've created your room service order (Order #$order_id). ";
            $response .= "Your items will be delivered within 30-45 minutes. ";
            $response .= "You can track your order status in real-time through this chat.";
            
            // Add automated response
            $this->addMessageToThread($thread_id, $response, 'system', 0, [
                'type' => 'room_service_confirmation',
                'order_id' => $order_id,
                'items' => $items
            ]);
            
            // Notify kitchen staff
            $this->notifyDepartment('kitchen', 'new_room_service_order', [
                'thread_id' => $thread_id,
                'order_id' => $order_id,
                'items' => $items
            ]);
        } else {
            // Request clarification
            $response = "I'd be happy to help with your room service request! ";
            $response .= "Could you please specify which items you'd like to order? ";
            $response .= "You can also view our complete menu by typing 'menu'.";
            
            $this->addMessageToThread($thread_id, $response, 'system', 0, [
                'type' => 'clarification_request',
                'intent' => 'room_service'
            ]);
        }
    }
    
    /**
     * Handle housekeeping requests
     */
    private function handleHousekeepingRequest($thread_id, $message) {
        // Extract housekeeping requirements
        $services = $this->extractHousekeepingServices($message);
        
        // Create housekeeping task
        $task_id = $this->createHousekeepingTask($thread_id, $services);
        
        $response = "I've scheduled your housekeeping request (Task #$task_id). ";
        $response .= "Our housekeeping team will arrive within the next 2 hours. ";
        $response .= "Is there a preferred time for the service?";
        
        $this->addMessageToThread($thread_id, $response, 'system', 0, [
            'type' => 'housekeeping_confirmation',
            'task_id' => $task_id,
            'services' => $services
        ]);
        
        // Notify housekeeping department
        $this->notifyDepartment('housekeeping', 'new_task', [
            'thread_id' => $thread_id,
            'task_id' => $task_id,
            'services' => $services
        ]);
    }
    
    /**
     * Handle maintenance requests
     */
    private function handleMaintenanceRequest($thread_id, $message) {
        // Extract maintenance issues
        $issues = $this->extractMaintenanceIssues($message);
        
        // Determine urgency
        $urgency = $this->assessMaintenanceUrgency($message, $issues);
        
        // Create maintenance ticket
        $ticket_id = $this->createMaintenanceTicket($thread_id, $issues, $urgency);
        
        $response = "I've created a maintenance ticket (Ticket #$ticket_id) for your request. ";
        
        if ($urgency === 'high') {
            $response .= "This has been marked as urgent and our maintenance team will respond immediately.";
        } else {
            $response .= "Our maintenance team will address this within 4 hours.";
        }
        
        $this->addMessageToThread($thread_id, $response, 'system', 0, [
            'type' => 'maintenance_confirmation',
            'ticket_id' => $ticket_id,
            'urgency' => $urgency,
            'issues' => $issues
        ]);
        
        // Notify maintenance department
        $this->notifyDepartment('maintenance', 'new_ticket', [
            'thread_id' => $thread_id,
            'ticket_id' => $ticket_id,
            'urgency' => $urgency,
            'issues' => $issues
        ]);
    }
    
    /**
     * Handle concierge requests
     */
    private function handleConciergeRequest($thread_id, $message) {
        // Extract concierge service type
        $service_type = $this->extractConciergeServiceType($message);
        
        // Generate personalized recommendations
        $recommendations = $this->generateRecommendations($thread_id, $service_type, $message);
        
        $response = "I'd be delighted to help with your concierge request! ";
        
        if (!empty($recommendations)) {
            $response .= "Based on your preferences, here are my recommendations:\n\n";
            
            foreach ($recommendations as $rec) {
                $response .= "• {$rec['title']}\n";
                $response .= "  {$rec['description']}\n";
                $response .= "  Price: {$rec['price']} | Duration: {$rec['duration']}\n\n";
            }
            
            $response .= "Would you like me to make any reservations or provide more details about any of these options?";
        } else {
            $response .= "Could you please provide more details about what you're looking for? ";
            $response .= "I can help with restaurant reservations, tour bookings, transportation, and local attractions.";
        }
        
        $this->addMessageToThread($thread_id, $response, 'system', 0, [
            'type' => 'concierge_recommendations',
            'service_type' => $service_type,
            'recommendations' => $recommendations
        ]);
    }
    
    /**
     * Handle complaints
     */
    private function handleComplaint($thread_id, $message, $sentiment) {
        // Extract complaint details
        $complaint_details = $this->extractComplaintDetails($message);
        
        // Create complaint record
        $complaint_id = $this->createComplaintRecord($thread_id, $complaint_details, $sentiment);
        
        // Immediate empathetic response
        $response = "I sincerely apologize for the inconvenience you've experienced. ";
        $response .= "Your concern is very important to us, and I've created a priority case (Case #$complaint_id) ";
        $response .= "to ensure this is resolved immediately. ";
        $response .= "A manager will personally address this matter within the next 15 minutes.";
        
        $this->addMessageToThread($thread_id, $response, 'system', 0, [
            'type' => 'complaint_acknowledgment',
            'complaint_id' => $complaint_id,
            'escalated' => true
        ]);
        
        // Immediate escalation to management
        $this->escalateToManager($thread_id, 'complaint', $sentiment, [
            'complaint_id' => $complaint_id,
            'details' => $complaint_details
        ]);
    }
    
    /**
     * Generate AI-powered recommendations
     */
    private function generateRecommendations($thread_id, $service_type, $message) {
        try {
            // Get guest preferences and history
            $guest_profile = $this->getGuestProfile($thread_id);
            
            // Analyze current request
            $preferences = $this->extractPreferences($message);
            
            // Generate contextual recommendations
            $recommendations = [];
            
            switch ($service_type) {
                case 'dining':
                    $recommendations = $this->generateDiningRecommendations($guest_profile, $preferences);
                    break;
                    
                case 'entertainment':
                    $recommendations = $this->generateEntertainmentRecommendations($guest_profile, $preferences);
                    break;
                    
                case 'transportation':
                    $recommendations = $this->generateTransportationOptions($guest_profile, $preferences);
                    break;
                    
                case 'activities':
                    $recommendations = $this->generateActivityRecommendations($guest_profile, $preferences);
                    break;
                    
                default:
                    $recommendations = $this->generateGeneralRecommendations($guest_profile, $preferences);
            }
            
            return $recommendations;
            
        } catch (Exception $e) {
            error_log("Recommendation Generation Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Analyze message sentiment using basic sentiment analysis
     */
    private function analyzeSentiment($message) {
        // Simple sentiment analysis - in production, use a proper NLP service
        $positive_words = ['happy', 'great', 'excellent', 'wonderful', 'amazing', 'love', 'perfect', 'fantastic'];
        $negative_words = ['terrible', 'awful', 'horrible', 'hate', 'disgusting', 'worst', 'disappointed', 'frustrated'];
        
        $message_lower = strtolower($message);
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $positive_count++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $negative_count++;
            }
        }
        
        // Calculate sentiment score (-1 to 1)
        $total_words = str_word_count($message);
        $sentiment_score = ($positive_count - $negative_count) / max($total_words, 1);
        
        return max(-1, min(1, $sentiment_score));
    }
    
    /**
     * Detect message intent using keyword matching
     */
    private function detectIntent($message) {
        $message_lower = strtolower($message);
        
        // Intent keywords mapping
        $intent_keywords = [
            'room_service_request' => ['room service', 'food', 'order', 'menu', 'hungry', 'eat', 'drink'],
            'housekeeping_request' => ['housekeeping', 'clean', 'towels', 'sheets', 'vacuum', 'tidy'],
            'maintenance_request' => ['broken', 'not working', 'fix', 'repair', 'maintenance', 'issue', 'problem'],
            'concierge_request' => ['restaurant', 'recommendation', 'tour', 'attraction', 'book', 'reserve'],
            'complaint' => ['complaint', 'unhappy', 'dissatisfied', 'problem', 'issue', 'terrible', 'awful'],
            'general_inquiry' => ['question', 'information', 'help', 'when', 'where', 'how', 'what']
        ];
        
        $intent_scores = [];
        
        foreach ($intent_keywords as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $intent_scores[$intent] = $score;
            }
        }
        
        // Return intent with highest score
        if (!empty($intent_scores)) {
            arsort($intent_scores);
            return array_key_first($intent_scores);
        }
        
        return 'general_inquiry';
    }
    
    /**
     * Get comprehensive guest thread data
     */
    public function getGuestThreadData($thread_id, $include_messages = true) {
        try {
            // Get thread information
            $sql = "SELECT gc.*, g.full_name, g.email, g.phone, g.vip_status,
                           r.room_type, r.floor
                    FROM guest_communications gc
                    LEFT JOIN guests g ON gc.guest_id = g.id
                    LEFT JOIN rooms r ON gc.room_number = r.room_number
                    WHERE gc.thread_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('s', $thread_id);
            $stmt->execute();
            $thread_data = $stmt->get_result()->fetch_assoc();
            
            if (!$thread_data) {
                throw new Exception("Thread not found");
            }
            
            // Get messages if requested
            if ($include_messages) {
                $sql = "SELECT gm.*, 
                               CASE 
                                 WHEN gm.sender_type = 'staff' THEN s.full_name
                                 WHEN gm.sender_type = 'guest' THEN g.full_name
                                 ELSE 'System'
                               END as sender_name,
                               CASE 
                                 WHEN gm.sender_type = 'staff' THEN s.department
                                 ELSE NULL
                               END as sender_department
                        FROM guest_messages gm
                        LEFT JOIN staff s ON gm.sender_type = 'staff' AND gm.sender_id = s.id
                        LEFT JOIN guests g ON gm.sender_type = 'guest' AND gm.sender_id = g.id
                        WHERE gm.thread_id = ?
                        ORDER BY gm.created_at ASC";
                
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param('s', $thread_id);
                $stmt->execute();
                $messages_result = $stmt->get_result();
                
                $messages = [];
                while ($row = $messages_result->fetch_assoc()) {
                    $row['metadata'] = json_decode($row['metadata'], true);
                    $messages[] = $row;
                }
                
                $thread_data['messages'] = $messages;
            }
            
            // Get related tickets/orders
            $thread_data['related_items'] = $this->getRelatedItems($thread_id);
            
            return ['success' => true, 'data' => $thread_data];
            
        } catch (Exception $e) {
            error_log("Get Thread Data Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Get guest communication statistics
     */
    public function getGuestCommunicationStats($date_range = 7) {
        try {
            $stats = [];
            
            // Active threads
            $sql = "SELECT COUNT(*) as count FROM guest_communications 
                    WHERE status = 'active' AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $date_range);
            $stmt->execute();
            $stats['active_threads'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Resolved threads
            $sql = "SELECT COUNT(*) as count FROM guest_communications 
                    WHERE status = 'resolved' AND updated_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $date_range);
            $stmt->execute();
            $stats['resolved_threads'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Average response time
            $sql = "SELECT AVG(TIMESTAMPDIFF(MINUTE, gm1.created_at, gm2.created_at)) as avg_response_time
                    FROM guest_messages gm1
                    JOIN guest_messages gm2 ON gm1.thread_id = gm2.thread_id
                    WHERE gm1.sender_type = 'guest' 
                    AND gm2.sender_type IN ('staff', 'system')
                    AND gm2.created_at > gm1.created_at
                    AND gm1.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    AND gm2.id = (
                        SELECT MIN(id) FROM guest_messages 
                        WHERE thread_id = gm1.thread_id 
                        AND sender_type IN ('staff', 'system')
                        AND created_at > gm1.created_at
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $date_range);
            $stmt->execute();
            $stats['avg_response_time'] = round($stmt->get_result()->fetch_assoc()['avg_response_time'], 2);
            
            // Guest satisfaction (based on sentiment)
            $sql = "SELECT AVG(sentiment_score) as avg_sentiment
                    FROM guest_messages 
                    WHERE sender_type = 'guest' 
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $date_range);
            $stmt->execute();
            $avg_sentiment = $stmt->get_result()->fetch_assoc()['avg_sentiment'];
            $stats['satisfaction_score'] = round((($avg_sentiment + 1) / 2) * 100, 1); // Convert to 0-100 scale
            
            // Most common intents
            $sql = "SELECT intent, COUNT(*) as count
                    FROM guest_messages 
                    WHERE sender_type = 'guest' 
                    AND intent IS NOT NULL
                    AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY intent
                    ORDER BY count DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('i', $date_range);
            $stmt->execute();
            $intent_result = $stmt->get_result();
            
            $common_intents = [];
            while ($row = $intent_result->fetch_assoc()) {
                $common_intents[] = $row;
            }
            $stats['common_intents'] = $common_intents;
            
            return ['success' => true, 'stats' => $stats];
            
        } catch (Exception $e) {
            error_log("Get Communication Stats Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Generate unique thread ID
     */
    private function generateThreadId() {
        return 'thread_' . time() . '_' . substr(md5(uniqid()), 0, 8);
    }
    
    /**
     * Calculate guest priority based on profile
     */
    private function calculateGuestPriority($guest_info) {
        $priority = 'medium';
        
        if ($guest_info['vip_status'] === 'platinum' || $guest_info['vip_status'] === 'diamond') {
            $priority = 'high';
        } elseif ($guest_info['vip_status'] === 'gold') {
            $priority = 'medium';
        } else {
            $priority = 'normal';
        }
        
        return $priority;
    }
    
    /**
     * Get guest information
     */
    private function getGuestInformation($guest_id) {
        $sql = "SELECT * FROM guests WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('i', $guest_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Update thread activity timestamp
     */
    private function updateThreadActivity($thread_id, $sender_type) {
        $sql = "UPDATE guest_communications 
                SET updated_at = NOW(), last_message_from = ? 
                WHERE thread_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->bind_param('ss', $sender_type, $thread_id);
        $stmt->execute();
    }
    
    /**
     * Broadcast real-time message update
     */
    private function broadcastMessageUpdate($thread_id, $message_id, $sender_type) {
        // Implementation would use WebSocket or Server-Sent Events
        // For now, we'll just log the event
        error_log("Broadcasting message update: Thread $thread_id, Message $message_id, Sender: $sender_type");
    }
    
    /**
     * Notify staff of new thread
     */
    private function notifyStaffOfNewThread($thread_id, $guest_info, $priority) {
        $this->notificationEngine->sendNotification([
            'type' => 'new_guest_thread',
            'title' => 'New Guest Communication',
            'message' => "New message from {$guest_info['full_name']} (Room {$guest_info['room_number']})",
            'priority' => $priority,
            'data' => [
                'thread_id' => $thread_id,
                'guest_id' => $guest_info['id'],
                'room_number' => $guest_info['room_number']
            ],
            'recipients' => ['concierge', 'guest_services']
        ]);
    }
    
    /**
     * Escalate thread to manager
     */
    private function escalateToManager($thread_id, $reason, $severity = null, $metadata = []) {
        // Create escalation record
        $sql = "INSERT INTO guest_escalations (thread_id, reason, severity, metadata, created_at) 
                VALUES (?, ?, ?, ?, NOW())";
        $stmt = $this->db->prepare($sql);
        $metadata_json = json_encode($metadata);
        $stmt->bind_param('ssss', $thread_id, $reason, $severity, $metadata_json);
        $stmt->execute();
        
        // Notify management immediately
        $this->notificationEngine->sendUrgentNotification([
            'type' => 'guest_escalation',
            'title' => 'URGENT: Guest Issue Escalation',
            'message' => "Guest communication escalated: $reason",
            'priority' => 'urgent',
            'data' => [
                'thread_id' => $thread_id,
                'reason' => $reason,
                'severity' => $severity
            ],
            'recipients' => ['management', 'duty_manager']
        ]);
    }
}

/**
 * AI Concierge Helper Class
 */
class AIConcierge {
    private $config;
    
    public function __construct($config) {
        $this->config = $config;
    }
    
    /**
     * Generate AI response based on context
     */
    public function generateResponse($message, $context = []) {
        // Simple rule-based response system
        // In production, this would integrate with OpenAI API or similar
        
        $message_lower = strtolower($message);
        
        if (strpos($message_lower, 'menu') !== false) {
            return "I'd be happy to help you with our menu! We offer:\n\n" .
                   "🍽️ Room Service (24/7)\n" .
                   "🍳 Continental Breakfast (6:00 AM - 10:00 AM)\n" .
                   "🍸 Bar & Lounge (12:00 PM - 2:00 AM)\n\n" .
                   "Would you like me to send you our complete digital menu?";
        }
        
        if (strpos($message_lower, 'wifi') !== false || strpos($message_lower, 'internet') !== false) {
            return "Our complimentary Wi-Fi network is 'Orlando_Resorts_Guest'. " .
                   "The password is provided in your welcome packet. " .
                   "If you're having connection issues, I can arrange for our IT team to assist you immediately.";
        }
        
        if (strpos($message_lower, 'checkout') !== false) {
            return "Checkout time is 11:00 AM. You can extend your checkout until 1:00 PM for $50, " .
                   "or until 3:00 PM for $100 (subject to availability). " .
                   "You can also complete express checkout through this chat. Would you like me to help with that?";
        }
        
        // Default friendly response
        return "Thank you for reaching out! I'm here to help make your stay exceptional. " .
               "Our team will respond to your message shortly. In the meantime, " .
               "feel free to ask me about our amenities, services, or local recommendations!";
    }
}
?>
