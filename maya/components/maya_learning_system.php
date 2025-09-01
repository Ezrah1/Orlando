<?php
/**
 * Maya AI Learning System
 * Automatically improves Maya's responses based on user interactions
 */

class MayaLearningSystem {
    private $con;
    private $learningThreshold = 5; // Minimum interactions before pattern learning
    
    public function __construct($connection) {
        $this->con = $connection;
    }
    
    /**
     * Learn from user interaction patterns
     */
    public function learnFromInteraction($userMessage, $aiResponse, $sessionId, $context = []) {
        // Extract learning signals
        $signals = $this->extractLearningSignals($userMessage, $aiResponse, $context);
        
        // Update knowledge base if patterns detected
        if ($signals['should_learn']) {
            $this->updateKnowledgeBase($signals);
        }
        
        // Update response patterns
        $this->updateResponsePatterns($userMessage, $aiResponse, $signals);
        
        // Log learning event
        $this->logLearningEvent($userMessage, $aiResponse, $signals, $sessionId);
    }
    
    /**
     * Extract learning signals from conversation
     */
    private function extractLearningSignals($userMessage, $aiResponse, $context) {
        $signals = [
            'should_learn' => false,
            'confidence' => 0,
            'patterns' => [],
            'intent' => 'unknown',
            'sentiment' => 'neutral',
            'entities' => [],
            'response_quality' => 'medium'
        ];
        
        // Detect new patterns in user message
        $patterns = $this->detectPatterns($userMessage);
        $signals['patterns'] = $patterns;
        
        // Simple intent detection
        $signals['intent'] = $this->detectIntent($userMessage);
        
        // Simple sentiment analysis
        $signals['sentiment'] = $this->detectSentiment($userMessage);
        
        // Extract entities
        $signals['entities'] = $this->extractEntities($userMessage);
        
        // Evaluate response quality
        $signals['response_quality'] = $this->evaluateResponseQuality($aiResponse);
        
        // Determine if we should learn from this interaction
        $signals['should_learn'] = $this->shouldLearnFromInteraction($userMessage, $aiResponse, $signals);
        
        return $signals;
    }
    
    /**
     * Detect patterns in user messages
     */
    private function detectPatterns($message) {
        $patterns = [];
        $message_lower = strtolower($message);
        
        // Common booking patterns
        if (preg_match('/\b(book|reserve|reservation)\b.*\b(room|tonight|tomorrow)\b/i', $message)) {
            $patterns[] = 'immediate_booking';
        }
        
        if (preg_match('/\b(compare|difference|which|better)\b.*\b(room|accommodation)\b/i', $message)) {
            $patterns[] = 'room_comparison';
        }
        
        if (preg_match('/\b(price|cost|rate|expensive|cheap|budget)\b/i', $message)) {
            $patterns[] = 'pricing_inquiry';
        }
        
        if (preg_match('/\b(available|availability|free|vacant)\b/i', $message)) {
            $patterns[] = 'availability_check';
        }
        
        // Question patterns
        if (preg_match('/^(how|what|when|where|why|can|do|does|is|are)/i', $message)) {
            $patterns[] = 'question';
        }
        
        // Urgency patterns
        if (preg_match('/\b(urgent|asap|quickly|immediately|now|tonight)\b/i', $message)) {
            $patterns[] = 'urgent';
        }
        
        return $patterns;
    }
    
    /**
     * Simple intent detection
     */
    private function detectIntent($message) {
        $message_lower = strtolower($message);
        
        $intentKeywords = [
            'booking' => ['book', 'reserve', 'reservation', 'stay'],
            'pricing' => ['price', 'cost', 'rate', 'money', 'budget', 'expensive', 'cheap'],
            'availability' => ['available', 'free', 'vacancy', 'dates'],
            'amenities' => ['amenities', 'facilities', 'wifi', 'parking', 'service'],
            'location' => ['location', 'where', 'address', 'directions'],
            'complaint' => ['problem', 'issue', 'wrong', 'bad', 'terrible', 'disappointed'],
            'compliment' => ['good', 'great', 'excellent', 'amazing', 'wonderful', 'perfect']
        ];
        
        $maxScore = 0;
        $detectedIntent = 'general';
        
        foreach ($intentKeywords as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    $score++;
                }
            }
            
            if ($score > $maxScore) {
                $maxScore = $score;
                $detectedIntent = $intent;
            }
        }
        
        return $detectedIntent;
    }
    
    /**
     * Simple sentiment detection
     */
    private function detectSentiment($message) {
        $positive = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'perfect', 'love', 'like', 'happy', 'thank'];
        $negative = ['bad', 'terrible', 'awful', 'hate', 'disappointed', 'frustrated', 'angry', 'problem', 'issue'];
        
        $message_lower = strtolower($message);
        $positiveScore = 0;
        $negativeScore = 0;
        
        foreach ($positive as $word) {
            if (strpos($message_lower, $word) !== false) {
                $positiveScore++;
            }
        }
        
        foreach ($negative as $word) {
            if (strpos($message_lower, $word) !== false) {
                $negativeScore++;
            }
        }
        
        if ($positiveScore > $negativeScore) {
            return 'positive';
        } elseif ($negativeScore > $positiveScore) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * Extract entities from message
     */
    private function extractEntities($message) {
        $entities = [];
        
        // Extract numbers (guest count, budget, etc.)
        preg_match_all('/\b\d+\b/', $message, $numbers);
        if (!empty($numbers[0])) {
            $entities['numbers'] = $numbers[0];
        }
        
        // Extract room types
        $roomTypes = ['deluxe', 'suite', 'standard', 'premium', 'merit', 'eatonville'];
        foreach ($roomTypes as $room) {
            if (stripos($message, $room) !== false) {
                $entities['room_type'] = $room;
                break;
            }
        }
        
        // Extract time references
        $timeRefs = ['tonight', 'tomorrow', 'today', 'weekend', 'next week', 'next month'];
        foreach ($timeRefs as $time) {
            if (stripos($message, $time) !== false) {
                $entities['time_reference'] = $time;
                break;
            }
        }
        
        return $entities;
    }
    
    /**
     * Evaluate response quality
     */
    private function evaluateResponseQuality($response) {
        $length = strlen(strip_tags($response));
        
        // Too short responses are usually poor
        if ($length < 20) {
            return 'poor';
        }
        
        // Check for helpful indicators
        if (strpos($response, '✅') !== false || 
            strpos($response, '💰') !== false || 
            strpos($response, '🏨') !== false) {
            return 'good';
        }
        
        // Medium length, structured responses
        if ($length > 100 && (strpos($response, '<br>') !== false || strpos($response, '<strong>') !== false)) {
            return 'good';
        }
        
        return 'medium';
    }
    
    /**
     * Determine if we should learn from this interaction
     */
    private function shouldLearnFromInteraction($userMessage, $aiResponse, $signals) {
        // Learn from poor responses
        if ($signals['response_quality'] === 'poor') {
            return true;
        }
        
        // Learn from new patterns
        if (!empty($signals['patterns']) && !$this->patternExists($signals['patterns'])) {
            return true;
        }
        
        // Learn from emotional responses
        if ($signals['sentiment'] !== 'neutral') {
            return true;
        }
        
        // Learn from unrecognized intents
        if ($signals['intent'] === 'general' && strlen($userMessage) > 10) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if pattern exists in knowledge base
     */
    private function patternExists($patterns) {
        foreach ($patterns as $pattern) {
            $stmt = mysqli_prepare($this->con, "SELECT id FROM ai_knowledge_base WHERE category = ? OR question_keywords LIKE ?");
            $searchPattern = "%$pattern%";
            mysqli_stmt_bind_param($stmt, "ss", $pattern, $searchPattern);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Update knowledge base with new patterns
     */
    private function updateKnowledgeBase($signals) {
        // Only update if we have enough confidence
        if (empty($signals['patterns'])) {
            return;
        }
        
        foreach ($signals['patterns'] as $pattern) {
            // Check if this pattern needs reinforcement
            $stmt = mysqli_prepare($this->con, "
                SELECT id, question_keywords, priority 
                FROM ai_knowledge_base 
                WHERE category = ? 
                ORDER BY priority DESC 
                LIMIT 1
            ");
            mysqli_stmt_bind_param($stmt, "s", $pattern);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                // Pattern exists, increase priority
                $row = mysqli_fetch_assoc($result);
                $newPriority = min(100, $row['priority'] + 1);
                
                $updateStmt = mysqli_prepare($this->con, "UPDATE ai_knowledge_base SET priority = ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStmt, "ii", $newPriority, $row['id']);
                mysqli_stmt_execute($updateStmt);
            }
        }
    }
    
    /**
     * Update response patterns based on successful interactions
     */
    private function updateResponsePatterns($userMessage, $aiResponse, $signals) {
        // If response quality is good, reinforce the pattern
        if ($signals['response_quality'] === 'good') {
            $keywords = strtolower(str_replace([',', '.', '!', '?', ';'], '', $userMessage));
            
            // Find matching knowledge base entry and increase its priority
            $stmt = mysqli_prepare($this->con, "
                SELECT id, priority 
                FROM ai_knowledge_base 
                WHERE question_keywords LIKE ? 
                ORDER BY priority DESC 
                LIMIT 1
            ");
            $searchKeywords = "%$keywords%";
            mysqli_stmt_bind_param($stmt, "s", $searchKeywords);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            if (mysqli_num_rows($result) > 0) {
                $row = mysqli_fetch_assoc($result);
                $newPriority = min(100, $row['priority'] + 2);
                
                $updateStmt = mysqli_prepare($this->con, "UPDATE ai_knowledge_base SET priority = ? WHERE id = ?");
                mysqli_stmt_bind_param($updateStmt, "ii", $newPriority, $row['id']);
                mysqli_stmt_execute($updateStmt);
            }
        }
    }
    
    /**
     * Log learning events for analysis
     */
    private function logLearningEvent($userMessage, $aiResponse, $signals, $sessionId) {
        $stmt = mysqli_prepare($this->con, "
            INSERT INTO ai_conversations (session_id, user_message, ai_response, agent_id, knowledge_id, created_at) 
            VALUES (?, ?, ?, 1, NULL, NOW())
        ");
        
        $learningNote = "LEARNING: " . json_encode([
            'intent' => $signals['intent'],
            'sentiment' => $signals['sentiment'],
            'patterns' => $signals['patterns'],
            'entities' => $signals['entities'],
            'response_quality' => $signals['response_quality'],
            'should_learn' => $signals['should_learn']
        ]);
        
        mysqli_stmt_bind_param($stmt, "sss", $sessionId, $userMessage, $learningNote);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Get learning statistics
     */
    public function getLearningStats() {
        $stats = [];
        
        // Total learning events
        $result = mysqli_query($this->con, "
            SELECT COUNT(*) as count 
            FROM ai_conversations 
            WHERE ai_response LIKE 'LEARNING:%'
        ");
        $stats['total_learning_events'] = mysqli_fetch_assoc($result)['count'];
        
        // Knowledge base growth
        $result = mysqli_query($this->con, "
            SELECT COUNT(*) as count 
            FROM ai_knowledge_base 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['new_knowledge_week'] = mysqli_fetch_assoc($result)['count'];
        
        // Priority improvements
        $result = mysqli_query($this->con, "
            SELECT AVG(priority) as avg_priority 
            FROM ai_knowledge_base
        ");
        $stats['avg_knowledge_priority'] = round(mysqli_fetch_assoc($result)['avg_priority'], 1);
        
        return $stats;
    }
    
    /**
     * Auto-improve based on conversation patterns
     */
    public function autoImprove() {
        // Find frequently asked questions without good responses
        $result = mysqli_query($this->con, "
            SELECT user_message, COUNT(*) as frequency
            FROM ai_conversations 
            WHERE CHAR_LENGTH(ai_response) < 50 
              AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            GROUP BY user_message 
            HAVING frequency >= 3
            ORDER BY frequency DESC
            LIMIT 5
        ");
        
        $improvements = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $improvements[] = [
                'question' => $row['user_message'],
                'frequency' => $row['frequency'],
                'suggested_action' => 'needs_better_response'
            ];
        }
        
        return $improvements;
    }
}
?>
