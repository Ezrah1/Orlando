<?php
/**
 * Maya Intelligent Response Engine
 * Provides ChatGPT-like intelligent conversation capabilities
 */

class MayaIntelligentEngine {
    private $con;
    private $session_id;
    
    public function __construct($connection) {
        $this->con = $connection;
        $this->session_id = $this->getSessionId();
    }
    
    private function getSessionId() {
        if (!isset($_SESSION)) {
            session_start();
        }
        if (!isset($_SESSION['maya_session_id'])) {
            $_SESSION['maya_session_id'] = 'maya_' . uniqid() . '_' . time();
        }
        return $_SESSION['maya_session_id'];
    }
    
    /**
     * Main intelligent response generation - ChatGPT style
     */
    public function generateIntelligentResponse($user_query, $context = []) {
        // Step 1: Analyze user intent and emotion
        $analysis = $this->analyzeUserQuery($user_query);
        
        // Step 2: Get conversation history for context
        $conversation_history = $this->getConversationHistory();
        
        // Step 3: Determine response strategy
        $response_strategy = $this->determineResponseStrategy($analysis, $conversation_history);
        
        // Step 4: Generate intelligent response
        $response = $this->generateContextualResponse($user_query, $analysis, $response_strategy);
        
        // Step 5: Save conversation for future context
        $this->saveConversationTurn($user_query, $response, $analysis);
        
        return $response;
    }
    
    /**
     * Analyze user query for intent, emotion, and complexity
     */
    private function analyzeUserQuery($query) {
        $query_lower = strtolower($query);
        
        $analysis = [
            'intent' => $this->detectIntent($query_lower),
            'emotion' => $this->detectEmotion($query_lower),
            'complexity' => $this->assessComplexity($query_lower),
            'question_type' => $this->identifyQuestionType($query_lower),
            'needs_clarification' => $this->needsClarification($query_lower)
        ];
        
        return $analysis;
    }
    
    private function detectIntent($query) {
        if (preg_match('/\b(book|reserve|booking|reservation)\b/', $query)) return 'booking';
        if (preg_match('/\b(room|rooms|accommodation)\b/', $query)) return 'room_inquiry';
        if (preg_match('/\b(price|cost|rate|pricing|how much)\b/', $query)) return 'pricing';
        if (preg_match('/\b(available|availability|free)\b/', $query)) return 'availability';
        if (preg_match('/\b(help|problem|issue|stuck)\b/', $query)) return 'help_needed';
        if (preg_match('/\b(what|how|why|when|where)\b/', $query)) return 'information_seeking';
        if (preg_match('/\b(compare|vs|versus|difference)\b/', $query)) return 'comparison';
        if (preg_match('/\b(recommend|suggest|best|good)\b/', $query)) return 'recommendation';
        return 'general_inquiry';
    }
    
    private function detectEmotion($query) {
        if (preg_match('/\b(frustrated|angry|upset|disappointed|mad)\b/', $query)) return 'frustrated';
        if (preg_match('/\b(excited|amazing|fantastic|great|wonderful)\b/', $query)) return 'excited';
        if (preg_match('/\b(confused|lost|don\'t understand|unclear)\b/', $query)) return 'confused';
        if (preg_match('/\b(worried|anxious|concerned|nervous)\b/', $query)) return 'anxious';
        if (preg_match('/\b(thanks|thank you|appreciate|grateful)\b/', $query)) return 'grateful';
        return 'neutral';
    }
    
    private function assessComplexity($query) {
        $word_count = str_word_count($query);
        $question_marks = substr_count($query, '?');
        $has_multiple_topics = preg_match('/\band\b.*\band\b/', $query);
        
        if ($word_count > 20 || $question_marks > 2 || $has_multiple_topics) return 'high';
        if ($word_count > 10 || $question_marks > 1) return 'medium';
        return 'low';
    }
    
    private function identifyQuestionType($query) {
        if (preg_match('/^what\b/', $query)) return 'what';
        if (preg_match('/^how\b/', $query)) return 'how';
        if (preg_match('/^why\b/', $query)) return 'why';
        if (preg_match('/^when\b/', $query)) return 'when';
        if (preg_match('/^where\b/', $query)) return 'where';
        if (preg_match('/^can you\b|^could you\b/', $query)) return 'request';
        if (preg_match('/\?$/', $query)) return 'question';
        return 'statement';
    }
    
    private function needsClarification($query) {
        $vague_words = ['it', 'this', 'that', 'something', 'anything', 'stuff'];
        $word_count = str_word_count($query);
        
        foreach ($vague_words as $word) {
            if (strpos($query, $word) !== false && $word_count < 5) {
                return true;
            }
        }
        
        return $word_count < 3;
    }
    
    /**
     * Get recent conversation history for context
     */
    private function getConversationHistory($limit = 5) {
        $session_id = mysqli_real_escape_string($this->con, $this->session_id);
        $query = "SELECT user_query, maya_response, user_intent, conversation_mood 
                  FROM ai_conversation_context 
                  WHERE session_id = '$session_id' 
                  ORDER BY created_at DESC 
                  LIMIT $limit";
        
        $result = mysqli_query($this->con, $query);
        $history = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $history[] = $row;
            }
        }
        
        return array_reverse($history); // Return in chronological order
    }
    
    /**
     * Determine the best response strategy based on analysis
     */
    private function determineResponseStrategy($analysis, $history) {
        $strategy = [
            'approach' => 'direct',
            'tone' => 'helpful',
            'structure' => 'simple',
            'follow_up' => false
        ];
        
        // Adjust based on emotion
        switch ($analysis['emotion']) {
            case 'frustrated':
                $strategy['tone'] = 'empathetic';
                $strategy['approach'] = 'problem_solving';
                break;
            case 'confused':
                $strategy['approach'] = 'explanatory';
                $strategy['structure'] = 'step_by_step';
                break;
            case 'excited':
                $strategy['tone'] = 'enthusiastic';
                break;
            case 'anxious':
                $strategy['tone'] = 'reassuring';
                break;
        }
        
        // Adjust based on complexity
        if ($analysis['complexity'] === 'high') {
            $strategy['structure'] = 'detailed';
            $strategy['approach'] = 'comprehensive';
        }
        
        // Adjust based on clarification needs
        if ($analysis['needs_clarification']) {
            $strategy['approach'] = 'clarifying';
            $strategy['follow_up'] = true;
        }
        
        return $strategy;
    }
    
    /**
     * Generate contextual response using ChatGPT-like logic
     */
    private function generateContextualResponse($query, $analysis, $strategy) {
        // Get base response from knowledge base
        $base_response = $this->getKnowledgeBaseResponse($query, $analysis['intent']);
        
        // If no specific response found, generate intelligent fallback
        if (!$base_response) {
            $base_response = $this->generateIntelligentFallback($query, $analysis);
        }
        
        // Apply strategy modifications
        $response = $this->applyResponseStrategy($base_response, $strategy, $analysis);
        
        return $response;
    }
    
    private function getKnowledgeBaseResponse($query, $intent) {
        $query_escaped = mysqli_real_escape_string($this->con, $query);
        $intent_escaped = mysqli_real_escape_string($this->con, $intent);
        
        // Search for relevant responses
        $search_query = "SELECT response_template, category 
                        FROM ai_knowledge_base 
                        WHERE (question_keywords LIKE '%$query_escaped%' 
                           OR category = '$intent_escaped' 
                           OR question_keywords REGEXP '[[:<:]]" . implode('[[:>:]]|[[:<:]]', explode(' ', $query_escaped)) . "[[:>:]]')
                        AND is_active = 1
                        ORDER BY priority DESC, usage_count ASC 
                        LIMIT 1";
        
        $result = mysqli_query($this->con, $search_query);
        
        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            
            // Update usage count
            $update_query = "UPDATE ai_knowledge_base 
                           SET usage_count = usage_count + 1 
                           WHERE category = '" . $row['category'] . "'";
            mysqli_query($this->con, $update_query);
            
            return $row['response_template'];
        }
        
        return false;
    }
    
    private function generateIntelligentFallback($query, $analysis) {
        switch ($analysis['intent']) {
            case 'information_seeking':
                return $this->generateInformationResponse($query, $analysis);
            case 'help_needed':
                return $this->generateHelpResponse($query, $analysis);
            case 'comparison':
                return $this->generateComparisonResponse($query, $analysis);
            default:
                return $this->generateGeneralResponse($query, $analysis);
        }
    }
    
    private function generateInformationResponse($query, $analysis) {
        $response = "I'd be happy to help you with that information! ";
        
        if ($analysis['needs_clarification']) {
            $response .= "To give you the most accurate and helpful answer, could you provide a bit more detail about what specifically you'd like to know?\n\n";
            $response .= "For example:\n";
            $response .= "• Are you looking for general information or something specific?\n";
            $response .= "• Is this related to your stay, booking, or our services?\n";
            $response .= "• What would be most helpful for you to know?\n\n";
            $response .= "The more details you share, the better I can assist you! 😊";
        } else {
            $response .= "Let me break this down for you in a clear, helpful way:\n\n";
            $response .= "🔍 **Here's what I can tell you:**\n\n";
            $response .= "Based on your question, I understand you're asking about " . $this->extractKeyTopics($query) . ". ";
            $response .= "While I'd love to give you specific details, I want to make sure I provide exactly what you need.\n\n";
            $response .= "Could you help me understand what aspect interests you most? That way I can give you the most relevant and useful information!";
        }
        
        return $response;
    }
    
    private function generateHelpResponse($query, $analysis) {
        $response = "I'm here to help! Let me understand your situation better so I can provide the best solution.\n\n";
        $response .= "🤝 **Let's work through this together:**\n\n";
        $response .= "1. **What exactly are you trying to accomplish?**\n";
        $response .= "2. **What specific challenge are you facing?**\n";
        $response .= "3. **Have you tried anything already?**\n\n";
        
        switch ($analysis['emotion']) {
            case 'frustrated':
                $response .= "I completely understand how frustrating this can be. ";
                break;
            case 'confused':
                $response .= "Don't worry - confusion is totally normal with complex topics. ";
                break;
            default:
                $response .= "I'm confident we can figure this out. ";
        }
        
        $response .= "Once I understand your needs better, I can guide you step-by-step to the perfect solution. We'll get this sorted out! 💪";
        
        return $response;
    }
    
    private function generateComparisonResponse($query, $analysis) {
        $response = "Great question! I love helping with comparisons - it's so important to understand your options clearly.\n\n";
        $response .= "📊 **To give you the most useful comparison, let me understand:**\n\n";
        $response .= "• What specific aspects are most important to you?\n";
        $response .= "• Are you comparing based on price, features, or something else?\n";
        $response .= "• What would help you make the best decision?\n\n";
        $response .= "Once I know what matters most to you, I can provide a detailed, side-by-side comparison that will make your choice crystal clear! 🎯";
        
        return $response;
    }
    
    private function generateGeneralResponse($query, $analysis) {
        $response = "Thanks for reaching out! I'm Maya, and I'm here to make your experience amazing.\n\n";
        
        if ($analysis['needs_clarification']) {
            $response .= "I want to give you the most helpful response possible. Could you tell me a bit more about what you're looking for?\n\n";
            $response .= "💡 **I can help with:**\n";
            $response .= "• Room bookings and availability\n";
            $response .= "• Pricing and special offers\n";
            $response .= "• Hotel amenities and services\n";
            $response .= "• Local attractions and recommendations\n";
            $response .= "• Travel planning and assistance\n\n";
            $response .= "What would be most helpful for you right now? 😊";
        } else {
            $response .= "I understand you're asking about " . $this->extractKeyTopics($query) . ". ";
            $response .= "Let me make sure I give you exactly the information you need!\n\n";
            $response .= "What specific aspect would you like me to focus on? The more details you share, the better I can help! ✨";
        }
        
        return $response;
    }
    
    private function extractKeyTopics($query) {
        $important_words = [];
        $words = explode(' ', strtolower($query));
        $stopwords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'i', 'you', 'he', 'she', 'it', 'we', 'they', 'this', 'that', 'these', 'those'];
        
        foreach ($words as $word) {
            $word = trim($word, '.,!?;:');
            if (strlen($word) > 3 && !in_array($word, $stopwords)) {
                $important_words[] = $word;
            }
        }
        
        return implode(', ', array_slice($important_words, 0, 3));
    }
    
    private function applyResponseStrategy($response, $strategy, $analysis) {
        // Apply tone modifications
        switch ($strategy['tone']) {
            case 'empathetic':
                $response = "I completely understand how you're feeling, and I'm really sorry you're experiencing this. " . $response;
                break;
            case 'enthusiastic':
                $response = str_replace('!', '! ✨', $response);
                break;
            case 'reassuring':
                $response = "Don't worry - I'm here to help and we'll figure this out together. " . $response;
                break;
        }
        
        // Apply structural modifications
        if ($strategy['structure'] === 'step_by_step') {
            $response = $this->addStepByStepStructure($response);
        } elseif ($strategy['structure'] === 'detailed') {
            $response = $this->addDetailedStructure($response);
        }
        
        // Add follow-up if needed
        if ($strategy['follow_up']) {
            $response .= "\n\nIs there anything specific you'd like me to elaborate on?";
        }
        
        return $response;
    }
    
    private function addStepByStepStructure($response) {
        // Add numbered steps if not already present
        if (!preg_match('/\d\./', $response)) {
            $sentences = explode('.', $response);
            $structured = "";
            $step = 1;
            
            foreach ($sentences as $sentence) {
                $sentence = trim($sentence);
                if (!empty($sentence)) {
                    $structured .= "**Step $step**: " . $sentence . ".\n\n";
                    $step++;
                }
            }
            
            return $structured;
        }
        
        return $response;
    }
    
    private function addDetailedStructure($response) {
        // Add sections and formatting for detailed responses
        if (!preg_match('/\*\*/', $response)) {
            $response = "**Detailed Information:**\n\n" . $response;
            $response .= "\n\n**Additional Details:**\n";
            $response .= "• I'm here to provide any clarification you need\n";
            $response .= "• Feel free to ask follow-up questions\n";
            $response .= "• I can provide more specific information based on your needs";
        }
        
        return $response;
    }
    
    /**
     * Save conversation turn for context and learning
     */
    private function saveConversationTurn($user_query, $maya_response, $analysis) {
        $session_id = mysqli_real_escape_string($this->con, $this->session_id);
        $user_query_escaped = mysqli_real_escape_string($this->con, $user_query);
        $maya_response_escaped = mysqli_real_escape_string($this->con, $maya_response);
        $user_intent = mysqli_real_escape_string($this->con, $analysis['intent']);
        $mood = mysqli_real_escape_string($this->con, $analysis['emotion']);
        $context_data = mysqli_real_escape_string($this->con, json_encode($analysis));
        
        // Get current turn number
        $turn_query = "SELECT MAX(conversation_turn) as max_turn 
                      FROM ai_conversation_context 
                      WHERE session_id = '$session_id'";
        $turn_result = mysqli_query($this->con, $turn_query);
        $current_turn = 1;
        
        if ($turn_result && mysqli_num_rows($turn_result) > 0) {
            $turn_row = mysqli_fetch_assoc($turn_result);
            $current_turn = ($turn_row['max_turn'] ?? 0) + 1;
        }
        
        $insert_query = "INSERT INTO ai_conversation_context 
                        (session_id, user_query, maya_response, conversation_turn, context_data, user_intent, conversation_mood) 
                        VALUES 
                        ('$session_id', '$user_query_escaped', '$maya_response_escaped', $current_turn, '$context_data', '$user_intent', '$mood')";
        
        mysqli_query($this->con, $insert_query);
    }
}
?>
