<?php
/**
 * Maya Conversation Memory System
 * Maintains context and remembers conversation history
 */

class MayaConversationMemory {
    private $con;
    private $sessionId;
    private $memory = [];
    
    public function __construct($connection, $sessionId) {
        $this->con = $connection;
        $this->sessionId = $sessionId;
        $this->loadSessionMemory();
    }
    
    public function remember($key, $value, $importance = 'medium') {
        $this->memory[$key] = [
            'value' => $value,
            'importance' => $importance,
            'timestamp' => time(),
            'access_count' => 0
        ];
        
        $this->saveToDatabase($key, $value, $importance);
    }
    
    public function recall($key) {
        if (isset($this->memory[$key])) {
            $this->memory[$key]['access_count']++;
            return $this->memory[$key]['value'];
        }
        return null;
    }
    
    public function getConversationContext() {
        return [
            'user_preferences' => $this->getUserPreferences(),
            'mentioned_topics' => $this->getMentionedTopics(),
            'emotional_journey' => $this->getEmotionalJourney(),
            'booking_progress' => $this->getBookingProgress(),
            'conversation_depth' => $this->getConversationDepth(),
            'last_intent' => $this->recall('last_intent'),
            'recurring_interests' => $this->getRecurringInterests()
        ];
    }
    
    private function getUserPreferences() {
        return [
            'preferred_room' => $this->recall('preferred_room'),
            'budget_range' => $this->recall('budget_range'),
            'guest_count' => $this->recall('guest_count'),
            'stay_duration' => $this->recall('stay_duration'),
            'special_requests' => $this->recall('special_requests'),
            'communication_style' => $this->recall('communication_style')
        ];
    }
    
    private function getMentionedTopics() {
        $topics = $this->recall('mentioned_topics') ?: [];
        
        // Clean old topics (older than 1 hour)
        $currentTime = time();
        return array_filter($topics, function($topic) use ($currentTime) {
            return ($currentTime - $topic['timestamp']) < 3600;
        });
    }
    
    private function getEmotionalJourney() {
        return $this->recall('emotional_journey') ?: ['neutral'];
    }
    
    private function getBookingProgress() {
        return [
            'stage' => $this->recall('booking_stage') ?: 'browsing',
            'selected_room' => $this->recall('selected_room'),
            'selected_dates' => $this->recall('selected_dates'),
            'obstacles' => $this->recall('booking_obstacles') ?: []
        ];
    }
    
    private function getConversationDepth() {
        return $this->recall('conversation_depth') ?: 0;
    }
    
    private function getRecurringInterests() {
        $topics = $this->recall('mentioned_topics') ?: [];
        $counts = [];
        
        foreach ($topics as $topic) {
            $key = $topic['topic'];
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        
        // Return topics mentioned more than once
        return array_filter($counts, function($count) {
            return $count > 1;
        });
    }
    
    public function updateConversation($userMessage, $aiResponse, $intent, $sentiment, $entities) {
        // Update conversation depth
        $depth = $this->getConversationDepth() + 1;
        $this->remember('conversation_depth', $depth);
        
        // Remember last intent
        $this->remember('last_intent', $intent);
        
        // Track emotional journey
        $emotionalJourney = $this->getEmotionalJourney();
        $emotionalJourney[] = $sentiment;
        $this->remember('emotional_journey', array_slice($emotionalJourney, -5)); // Keep last 5
        
        // Extract and remember entities
        $this->extractAndRememberEntities($entities);
        
        // Update mentioned topics
        $this->updateMentionedTopics($userMessage, $intent);
        
        // Update booking progress
        $this->updateBookingProgress($intent, $entities);
        
        // Analyze communication style
        $this->analyzeCommmunicationStyle($userMessage);
    }
    
    private function extractAndRememberEntities($entities) {
        foreach ($entities as $type => $value) {
            switch ($type) {
                case 'preferred_room':
                    $this->remember('preferred_room', $value, 'high');
                    break;
                case 'guest_count':
                    $this->remember('guest_count', $value, 'high');
                    break;
                case 'budget':
                    $this->remember('budget_range', $value, 'high');
                    break;
                case 'time_preference':
                    $this->remember('time_preference', $value, 'medium');
                    break;
            }
        }
    }
    
    private function updateMentionedTopics($userMessage, $intent) {
        $topics = $this->getMentionedTopics();
        
        $topics[] = [
            'topic' => $intent,
            'message_snippet' => substr($userMessage, 0, 50),
            'timestamp' => time()
        ];
        
        $this->remember('mentioned_topics', $topics);
    }
    
    private function updateBookingProgress($intent, $entities) {
        $progress = $this->getBookingProgress();
        
        switch ($intent) {
            case 'room_comparison':
            case 'pricing_inquiry':
                $progress['stage'] = 'researching';
                break;
            case 'availability_check':
                $progress['stage'] = 'checking_dates';
                break;
            case 'booking_immediate':
            case 'booking_future':
                $progress['stage'] = 'ready_to_book';
                break;
        }
        
        if (isset($entities['preferred_room'])) {
            $progress['selected_room'] = $entities['preferred_room'];
        }
        
        if (isset($entities['preferred_date'])) {
            $progress['selected_dates'] = $entities['preferred_date'];
        }
        
        $this->remember('booking_stage', $progress['stage'], 'high');
        if (isset($progress['selected_room'])) {
            $this->remember('selected_room', $progress['selected_room'], 'high');
        }
        if (isset($progress['selected_dates'])) {
            $this->remember('selected_dates', $progress['selected_dates'], 'high');
        }
    }
    
    private function analyzeCommmunicationStyle($userMessage) {
        $style = $this->recall('communication_style') ?: [
            'formal' => 0,
            'casual' => 0,
            'direct' => 0,
            'detailed' => 0
        ];
        
        $message = strtolower($userMessage);
        
        // Detect formality
        if (strpos($message, 'please') !== false || strpos($message, 'thank you') !== false) {
            $style['formal']++;
        }
        
        // Detect casualness
        if (strpos($message, 'hey') !== false || strpos($message, 'yeah') !== false || strpos($message, '😊') !== false) {
            $style['casual']++;
        }
        
        // Detect directness
        if (str_word_count($userMessage) < 5) {
            $style['direct']++;
        }
        
        // Detect detail preference
        if (str_word_count($userMessage) > 15) {
            $style['detailed']++;
        }
        
        $this->remember('communication_style', $style);
    }
    
    public function getCommunicationPreferences() {
        $style = $this->recall('communication_style') ?: [];
        $preferences = [];
        
        if (!empty($style)) {
            $maxStyle = array_keys($style, max($style))[0];
            $preferences['preferred_style'] = $maxStyle;
            
            // Determine response adjustments
            switch ($maxStyle) {
                case 'formal':
                    $preferences['tone'] = 'professional';
                    $preferences['structure'] = 'organized';
                    break;
                case 'casual':
                    $preferences['tone'] = 'friendly';
                    $preferences['structure'] = 'conversational';
                    break;
                case 'direct':
                    $preferences['tone'] = 'concise';
                    $preferences['structure'] = 'bullet-points';
                    break;
                case 'detailed':
                    $preferences['tone'] = 'comprehensive';
                    $preferences['structure'] = 'detailed';
                    break;
            }
        }
        
        return $preferences;
    }
    
    private function loadSessionMemory() {
        try {
            $query = "SELECT memory_key, memory_value, importance, created_at 
                      FROM maya_session_memory 
                      WHERE session_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                      ORDER BY created_at DESC";
            
            $stmt = mysqli_prepare($this->con, $query);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 's', $this->sessionId);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                while ($row = mysqli_fetch_assoc($result)) {
                    $this->memory[$row['memory_key']] = [
                        'value' => json_decode($row['memory_value'], true),
                        'importance' => $row['importance'],
                        'timestamp' => strtotime($row['created_at']),
                        'access_count' => 0
                    ];
                }
                
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            error_log("Maya Memory Load Error: " . $e->getMessage());
        }
    }
    
    private function saveToDatabase($key, $value, $importance) {
        try {
            // Create table if not exists
            $createTable = "CREATE TABLE IF NOT EXISTS `maya_session_memory` (
                `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                `session_id` varchar(100) NOT NULL,
                `memory_key` varchar(100) NOT NULL,
                `memory_value` text NOT NULL,
                `importance` enum('low','medium','high') DEFAULT 'medium',
                `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                KEY `session_id` (`session_id`),
                KEY `memory_key` (`memory_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
            
            mysqli_query($this->con, $createTable);
            
            // Insert or update memory
            $query = "INSERT INTO maya_session_memory (session_id, memory_key, memory_value, importance) 
                      VALUES (?, ?, ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      memory_value = VALUES(memory_value), 
                      importance = VALUES(importance),
                      created_at = CURRENT_TIMESTAMP";
            
            $stmt = mysqli_prepare($this->con, $query);
            if ($stmt) {
                $valueJson = json_encode($value);
                mysqli_stmt_bind_param($stmt, 'ssss', $this->sessionId, $key, $valueJson, $importance);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } catch (Exception $e) {
            error_log("Maya Memory Save Error: " . $e->getMessage());
        }
    }
    
    public function forgetOldMemories() {
        try {
            $query = "DELETE FROM maya_session_memory WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)";
            mysqli_query($this->con, $query);
        } catch (Exception $e) {
            error_log("Maya Memory Cleanup Error: " . $e->getMessage());
        }
    }
}
?>
