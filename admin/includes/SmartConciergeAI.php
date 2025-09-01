<?php
/**
 * Orlando International Resorts - Smart Concierge AI Engine
 * Advanced AI-powered concierge services with natural language processing
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class SmartConciergeAI {
    private $db;
    private $config;
    private $knowledgeBase;
    private $recommendationEngine;
    
    public function __construct($database_connection, $config = []) {
        $this->db = $database_connection;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->knowledgeBase = new ConciergeKnowledgeBase($database_connection);
        $this->recommendationEngine = new RecommendationEngine($database_connection);
    }
    
    /**
     * Get default AI configuration
     */
    private function getDefaultConfig() {
        return [
            'ai_enabled' => true,
            'confidence_threshold' => 0.8,
            'fallback_to_human' => true,
            'learning_enabled' => true,
            'multilingual_support' => true,
            'personalization_enabled' => true,
            'context_window' => 5, // Remember last 5 messages for context
            'max_response_length' => 500,
            'response_tone' => 'professional_friendly'
        ];
    }
    
    /**
     * Process guest message and generate AI response
     */
    public function processGuestMessage($thread_id, $message, $guest_context = []) {
        try {
            // Analyze message intent and entities
            $analysis = $this->analyzeMessage($message);
            
            // Get conversation context
            $context = $this->getConversationContext($thread_id);
            
            // Enhance context with guest information
            $enriched_context = array_merge($context, $guest_context);
            
            // Generate response based on intent
            $response = $this->generateResponse($analysis, $enriched_context);
            
            // Check confidence level
            if ($response['confidence'] < $this->config['confidence_threshold']) {
                return $this->handleLowConfidence($analysis, $enriched_context);
            }
            
            // Log successful AI interaction
            $this->logAIInteraction($thread_id, $message, $response, 'success');
            
            return [
                'success' => true,
                'response' => $response['message'],
                'confidence' => $response['confidence'],
                'intent' => $analysis['intent'],
                'actions' => $response['actions'] ?? [],
                'metadata' => [
                    'ai_generated' => true,
                    'confidence' => $response['confidence'],
                    'intent' => $analysis['intent'],
                    'response_type' => $response['type']
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Smart Concierge AI Error: " . $e->getMessage());
            return $this->getFallbackResponse();
        }
    }
    
    /**
     * Analyze message for intent and entities
     */
    private function analyzeMessage($message) {
        $message_lower = strtolower(trim($message));
        
        // Intent classification using keyword patterns
        $intent = $this->classifyIntent($message_lower);
        
        // Extract entities (numbers, dates, names, etc.)
        $entities = $this->extractEntities($message_lower);
        
        // Analyze sentiment
        $sentiment = $this->analyzeSentiment($message_lower);
        
        // Detect urgency indicators
        $urgency = $this->detectUrgency($message_lower);
        
        return [
            'original_message' => $message,
            'processed_message' => $message_lower,
            'intent' => $intent,
            'entities' => $entities,
            'sentiment' => $sentiment,
            'urgency' => $urgency,
            'language' => $this->detectLanguage($message)
        ];
    }
    
    /**
     * Classify message intent
     */
    private function classifyIntent($message) {
        $intent_patterns = [
            'room_service_order' => [
                'patterns' => ['order', 'food', 'drink', 'menu', 'hungry', 'thirsty', 'room service'],
                'weight' => 1.0
            ],
            'housekeeping_request' => [
                'patterns' => ['clean', 'housekeeping', 'towels', 'sheets', 'vacuum', 'tidy up'],
                'weight' => 1.0
            ],
            'maintenance_issue' => [
                'patterns' => ['broken', 'not working', 'fix', 'repair', 'problem', 'issue'],
                'weight' => 1.0
            ],
            'restaurant_reservation' => [
                'patterns' => ['restaurant', 'dinner', 'reservation', 'table', 'book'],
                'weight' => 1.0
            ],
            'local_recommendations' => [
                'patterns' => ['recommend', 'suggest', 'what to do', 'attractions', 'activities'],
                'weight' => 1.0
            ],
            'transportation_request' => [
                'patterns' => ['taxi', 'uber', 'transport', 'airport', 'shuttle'],
                'weight' => 1.0
            ],
            'spa_wellness' => [
                'patterns' => ['spa', 'massage', 'wellness', 'relax', 'treatment'],
                'weight' => 1.0
            ],
            'complaint' => [
                'patterns' => ['complain', 'dissatisfied', 'terrible', 'awful', 'disappointed'],
                'weight' => 1.0
            ],
            'information_request' => [
                'patterns' => ['what', 'when', 'where', 'how', 'information', 'tell me'],
                'weight' => 0.8
            ],
            'greeting' => [
                'patterns' => ['hello', 'hi', 'good morning', 'good evening', 'hey'],
                'weight' => 0.9
            ]
        ];
        
        $intent_scores = [];
        
        foreach ($intent_patterns as $intent => $config) {
            $score = 0;
            foreach ($config['patterns'] as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    $score += $config['weight'];
                }
            }
            if ($score > 0) {
                $intent_scores[$intent] = $score;
            }
        }
        
        if (empty($intent_scores)) {
            return 'general_inquiry';
        }
        
        arsort($intent_scores);
        return array_key_first($intent_scores);
    }
    
    /**
     * Extract entities from message
     */
    private function extractEntities($message) {
        $entities = [];
        
        // Extract times
        if (preg_match_all('/(\d{1,2}):(\d{2})\s*(am|pm)?/i', $message, $matches)) {
            $entities['times'] = $matches[0];
        }
        
        // Extract dates
        if (preg_match_all('/\b(today|tomorrow|yesterday|\d{1,2}\/\d{1,2})\b/i', $message, $matches)) {
            $entities['dates'] = $matches[0];
        }
        
        // Extract numbers
        if (preg_match_all('/\b\d+\b/', $message, $matches)) {
            $entities['numbers'] = $matches[0];
        }
        
        // Extract room references
        if (preg_match_all('/room\s*(\d+)/i', $message, $matches)) {
            $entities['rooms'] = $matches[1];
        }
        
        return $entities;
    }
    
    /**
     * Generate AI response based on analysis and context
     */
    private function generateResponse($analysis, $context) {
        $intent = $analysis['intent'];
        $entities = $analysis['entities'];
        $sentiment = $analysis['sentiment'];
        
        switch ($intent) {
            case 'room_service_order':
                return $this->generateRoomServiceResponse($analysis, $context);
                
            case 'housekeeping_request':
                return $this->generateHousekeepingResponse($analysis, $context);
                
            case 'maintenance_issue':
                return $this->generateMaintenanceResponse($analysis, $context);
                
            case 'restaurant_reservation':
                return $this->generateRestaurantResponse($analysis, $context);
                
            case 'local_recommendations':
                return $this->generateRecommendationsResponse($analysis, $context);
                
            case 'transportation_request':
                return $this->generateTransportationResponse($analysis, $context);
                
            case 'spa_wellness':
                return $this->generateSpaResponse($analysis, $context);
                
            case 'complaint':
                return $this->generateComplaintResponse($analysis, $context);
                
            case 'greeting':
                return $this->generateGreetingResponse($analysis, $context);
                
            case 'information_request':
                return $this->generateInformationResponse($analysis, $context);
                
            default:
                return $this->generateGeneralResponse($analysis, $context);
        }
    }
    
    /**
     * Generate room service response
     */
    private function generateRoomServiceResponse($analysis, $context) {
        $guest_name = $context['guest_name'] ?? 'Guest';
        
        $responses = [
            "I'd be delighted to help you with room service, {$guest_name}! Our kitchen is open 24/7.",
            "Absolutely! I can take your room service order right now. What would you like to enjoy?",
            "Our room service menu has something for everyone. What are you in the mood for today?"
        ];
        
        $base_response = $responses[array_rand($responses)];
        
        // Add menu suggestions based on time of day
        $hour = date('H');
        if ($hour >= 6 && $hour < 11) {
            $base_response .= " For breakfast, I recommend our signature eggs Benedict or fresh fruit parfait.";
        } elseif ($hour >= 11 && $hour < 15) {
            $base_response .= " For lunch, our gourmet sandwiches and Caesar salad are very popular.";
        } elseif ($hour >= 15 && $hour < 18) {
            $base_response .= " Perfect time for our afternoon tea service or light snacks.";
        } else {
            $base_response .= " For dinner, our grilled salmon and prime rib are chef's specialties.";
        }
        
        $base_response .= " Would you like me to send you our complete digital menu, or do you have something specific in mind?";
        
        return [
            'message' => $base_response,
            'confidence' => 0.95,
            'type' => 'room_service',
            'actions' => [
                ['type' => 'send_menu', 'label' => 'Send Menu'],
                ['type' => 'take_order', 'label' => 'Take Order Now']
            ]
        ];
    }
    
    /**
     * Generate housekeeping response
     */
    private function generateHousekeepingResponse($analysis, $context) {
        $room_number = $context['room_number'] ?? 'your room';
        
        $response = "I'll be happy to arrange housekeeping service for room {$room_number}. ";
        
        // Check current time to provide appropriate scheduling
        $hour = date('H');
        if ($hour >= 8 && $hour < 16) {
            $response .= "Our housekeeping team can service your room within the next 2 hours. ";
        } else {
            $response .= "Our housekeeping team will service your room first thing in the morning. ";
        }
        
        $response .= "Is there anything specific you need? Fresh towels, extra pillows, or a complete room refresh?";
        
        return [
            'message' => $response,
            'confidence' => 0.92,
            'type' => 'housekeeping',
            'actions' => [
                ['type' => 'schedule_housekeeping', 'label' => 'Schedule Now'],
                ['type' => 'specify_requests', 'label' => 'Specify Needs']
            ]
        ];
    }
    
    /**
     * Generate maintenance response
     */
    private function generateMaintenanceResponse($analysis, $context) {
        $urgency = $analysis['urgency'];
        
        $response = "I apologize for any inconvenience. I've created a maintenance request ";
        
        if ($urgency === 'high') {
            $response .= "marked as urgent. Our maintenance team will address this immediately. ";
        } else {
            $response .= "and our maintenance team will resolve this within 4 hours. ";
        }
        
        $response .= "Could you please describe the specific issue so I can provide our team with detailed information?";
        
        return [
            'message' => $response,
            'confidence' => 0.90,
            'type' => 'maintenance',
            'actions' => [
                ['type' => 'create_ticket', 'urgency' => $urgency],
                ['type' => 'escalate_if_needed']
            ]
        ];
    }
    
    /**
     * Generate restaurant reservation response
     */
    private function generateRestaurantResponse($analysis, $context) {
        $entities = $analysis['entities'];
        $guest_preferences = $context['dining_preferences'] ?? [];
        
        $response = "I'd be delighted to help you make a restaurant reservation! ";
        
        // Check for specific restaurant mentions
        if (!empty($entities['restaurants'])) {
            $response .= "I see you're interested in " . implode(' or ', $entities['restaurants']) . ". ";
        } else {
            $response .= "Based on your preferences, I have some excellent recommendations. ";
        }
        
        // Add personalized suggestions
        if (!empty($guest_preferences)) {
            $response .= "Given your preference for " . implode(', ', $guest_preferences) . " cuisine, ";
        }
        
        $response .= "What date and time would work best for you? I can also suggest restaurants based on your taste preferences.";
        
        return [
            'message' => $response,
            'confidence' => 0.88,
            'type' => 'restaurant_reservation',
            'actions' => [
                ['type' => 'show_restaurants', 'label' => 'Show Options'],
                ['type' => 'make_reservation', 'label' => 'Make Reservation']
            ]
        ];
    }
    
    /**
     * Generate local recommendations response
     */
    private function generateRecommendationsResponse($analysis, $context) {
        $guest_profile = $context['guest_profile'] ?? [];
        $stay_duration = $context['stay_duration'] ?? 1;
        
        $recommendations = $this->recommendationEngine->getPersonalizedRecommendations(
            $guest_profile,
            $analysis['entities'],
            $stay_duration
        );
        
        $response = "I have some wonderful recommendations for you! ";
        
        if (!empty($recommendations)) {
            $response .= "Based on your interests and the local area:\n\n";
            
            foreach (array_slice($recommendations, 0, 3) as $rec) {
                $response .= "• {$rec['name']}: {$rec['description']}\n";
                $response .= "  Distance: {$rec['distance']} | Rating: {$rec['rating']}/5\n\n";
            }
            
            $response .= "Would you like more details about any of these, or shall I help you make arrangements?";
        } else {
            $response .= "Let me know what type of activities you enjoy - dining, shopping, outdoor adventures, cultural sites, or entertainment - and I'll provide tailored recommendations!";
        }
        
        return [
            'message' => $response,
            'confidence' => 0.85,
            'type' => 'recommendations',
            'actions' => [
                ['type' => 'show_more', 'label' => 'Show More Options'],
                ['type' => 'make_arrangements', 'label' => 'Make Arrangements']
            ]
        ];
    }
    
    /**
     * Generate greeting response
     */
    private function generateGreetingResponse($analysis, $context) {
        $guest_name = $context['guest_name'] ?? '';
        $time_of_day = $this->getTimeOfDayGreeting();
        
        $personalized_greeting = $guest_name ? "Hello {$guest_name}! " : "Hello! ";
        $personalized_greeting .= "{$time_of_day} Welcome to Orlando International Resorts. ";
        
        // Add contextual information based on guest status
        if (!empty($context['vip_status'])) {
            $personalized_greeting .= "As our valued {$context['vip_status']} guest, ";
        }
        
        $personalized_greeting .= "I'm your AI concierge, here to make your stay exceptional. How may I assist you today?";
        
        return [
            'message' => $personalized_greeting,
            'confidence' => 0.98,
            'type' => 'greeting',
            'actions' => [
                ['type' => 'show_services', 'label' => 'Show Services'],
                ['type' => 'daily_recommendations', 'label' => 'Today\'s Recommendations']
            ]
        ];
    }
    
    /**
     * Generate complaint response
     */
    private function generateComplaintResponse($analysis, $context) {
        $guest_name = $context['guest_name'] ?? 'valued guest';
        
        $response = "I sincerely apologize for this experience, {$guest_name}. Your satisfaction is our top priority, ";
        $response .= "and I want to ensure this matter is resolved immediately. ";
        $response .= "I'm escalating this to our management team, and you can expect personal attention within 15 minutes. ";
        $response .= "In the meantime, please let me know if there's anything I can do to improve your stay right now.";
        
        return [
            'message' => $response,
            'confidence' => 0.95,
            'type' => 'complaint_response',
            'actions' => [
                ['type' => 'escalate_to_manager', 'priority' => 'urgent'],
                ['type' => 'offer_compensation'],
                ['type' => 'follow_up_required']
            ]
        ];
    }
    
    /**
     * Get conversation context from recent messages
     */
    private function getConversationContext($thread_id) {
        try {
            $sql = "SELECT gm.*, gc.guest_id, gc.room_number, g.full_name, g.vip_status, g.preferences
                    FROM guest_messages gm
                    JOIN guest_communications gc ON gm.thread_id = gc.thread_id
                    LEFT JOIN guests g ON gc.guest_id = g.id
                    WHERE gm.thread_id = ?
                    ORDER BY gm.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('si', $thread_id, $this->config['context_window']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $context = [
                'thread_id' => $thread_id,
                'recent_messages' => [],
                'guest_name' => '',
                'room_number' => '',
                'vip_status' => '',
                'guest_preferences' => []
            ];
            
            while ($row = $result->fetch_assoc()) {
                if (empty($context['guest_name'])) {
                    $context['guest_name'] = $row['full_name'];
                    $context['room_number'] = $row['room_number'];
                    $context['vip_status'] = $row['vip_status'];
                    $context['guest_preferences'] = json_decode($row['preferences'] ?? '[]', true);
                }
                
                $context['recent_messages'][] = [
                    'sender_type' => $row['sender_type'],
                    'message' => $row['message'],
                    'intent' => $row['intent'],
                    'created_at' => $row['created_at']
                ];
            }
            
            return $context;
            
        } catch (Exception $e) {
            error_log("Context retrieval error: " . $e->getMessage());
            return ['thread_id' => $thread_id];
        }
    }
    
    /**
     * Detect message urgency
     */
    private function detectUrgency($message) {
        $urgent_indicators = ['urgent', 'emergency', 'immediately', 'asap', 'critical', 'broken', 'not working'];
        $high_indicators = ['important', 'soon', 'quickly', 'problem', 'issue'];
        
        foreach ($urgent_indicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return 'high';
            }
        }
        
        foreach ($high_indicators as $indicator) {
            if (strpos($message, $indicator) !== false) {
                return 'medium';
            }
        }
        
        return 'normal';
    }
    
    /**
     * Basic sentiment analysis
     */
    private function analyzeSentiment($message) {
        $positive_words = ['great', 'excellent', 'wonderful', 'amazing', 'perfect', 'love', 'fantastic', 'happy'];
        $negative_words = ['terrible', 'awful', 'horrible', 'hate', 'worst', 'disappointed', 'frustrated', 'angry'];
        
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($message, $word) !== false) {
                $positive_count++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($message, $word) !== false) {
                $negative_count++;
            }
        }
        
        if ($negative_count > $positive_count) {
            return 'negative';
        } elseif ($positive_count > $negative_count) {
            return 'positive';
        } else {
            return 'neutral';
        }
    }
    
    /**
     * Detect message language
     */
    private function detectLanguage($message) {
        // Simple language detection based on common words
        $language_patterns = [
            'es' => ['hola', 'gracias', 'por favor', 'sí', 'no', 'bueno'],
            'fr' => ['bonjour', 'merci', 's\'il vous plaît', 'oui', 'non', 'bon'],
            'de' => ['hallo', 'danke', 'bitte', 'ja', 'nein', 'gut']
        ];
        
        foreach ($language_patterns as $lang => $patterns) {
            $matches = 0;
            foreach ($patterns as $pattern) {
                if (strpos($message, $pattern) !== false) {
                    $matches++;
                }
            }
            if ($matches >= 2) {
                return $lang;
            }
        }
        
        return 'en'; // Default to English
    }
    
    /**
     * Get time-appropriate greeting
     */
    private function getTimeOfDayGreeting() {
        $hour = date('H');
        
        if ($hour >= 5 && $hour < 12) {
            return 'Good morning!';
        } elseif ($hour >= 12 && $hour < 17) {
            return 'Good afternoon!';
        } elseif ($hour >= 17 && $hour < 22) {
            return 'Good evening!';
        } else {
            return 'Good evening!';
        }
    }
    
    /**
     * Handle low confidence responses
     */
    private function handleLowConfidence($analysis, $context) {
        if ($this->config['fallback_to_human']) {
            return [
                'success' => true,
                'response' => "I want to make sure I understand you correctly. Let me connect you with one of our guest services specialists who can provide you with the best assistance.",
                'confidence' => 0.5,
                'intent' => 'fallback_to_human',
                'actions' => [['type' => 'escalate_to_human', 'reason' => 'low_confidence']],
                'metadata' => [
                    'ai_generated' => true,
                    'fallback_reason' => 'low_confidence',
                    'original_intent' => $analysis['intent']
                ]
            ];
        } else {
            return $this->generateGeneralResponse($analysis, $context);
        }
    }
    
    /**
     * Generate general fallback response
     */
    private function generateGeneralResponse($analysis, $context) {
        $responses = [
            "Thank you for reaching out! I'm here to help make your stay wonderful. Could you please provide a bit more detail about what you need?",
            "I'd be happy to assist you! Could you tell me more about how I can help make your experience better?",
            "I'm here to ensure you have an exceptional stay. What can I do for you today?"
        ];
        
        return [
            'message' => $responses[array_rand($responses)],
            'confidence' => 0.7,
            'type' => 'general_inquiry',
            'actions' => [
                ['type' => 'request_clarification'],
                ['type' => 'show_services_menu']
            ]
        ];
    }
    
    /**
     * Get fallback response for errors
     */
    private function getFallbackResponse() {
        return [
            'success' => true,
            'response' => "I apologize, but I'm having a brief technical issue. Please allow me a moment, or I can connect you directly with our guest services team for immediate assistance.",
            'confidence' => 0.3,
            'intent' => 'technical_error',
            'actions' => [['type' => 'escalate_to_human', 'reason' => 'technical_error']],
            'metadata' => [
                'ai_generated' => true,
                'error_fallback' => true
            ]
        ];
    }
    
    /**
     * Log AI interaction for learning and improvement
     */
    private function logAIInteraction($thread_id, $message, $response, $status) {
        if (!$this->config['learning_enabled']) {
            return;
        }
        
        try {
            $sql = "INSERT INTO ai_interactions (
                thread_id, input_message, ai_response, confidence_score, 
                intent, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('sssdss', 
                $thread_id, 
                $message, 
                $response['message'], 
                $response['confidence'], 
                $response['intent'] ?? 'unknown', 
                $status
            );
            $stmt->execute();
            
        } catch (Exception $e) {
            error_log("AI interaction logging error: " . $e->getMessage());
        }
    }
}

/**
 * Concierge Knowledge Base Helper Class
 */
class ConciergeKnowledgeBase {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Search knowledge base for relevant information
     */
    public function search($query, $category = null) {
        $sql = "SELECT * FROM concierge_knowledge 
                WHERE MATCH(title, content, keywords) AGAINST(? IN NATURAL LANGUAGE MODE)";
        
        if ($category) {
            $sql .= " AND category = ?";
        }
        
        $sql .= " ORDER BY relevance_score DESC LIMIT 5";
        
        $stmt = $this->db->prepare($sql);
        if ($category) {
            $stmt->bind_param('ss', $query, $category);
        } else {
            $stmt->bind_param('s', $query);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}

/**
 * Recommendation Engine Helper Class
 */
class RecommendationEngine {
    private $db;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
    }
    
    /**
     * Get personalized recommendations
     */
    public function getPersonalizedRecommendations($guest_profile, $entities, $stay_duration) {
        // This would implement machine learning-based recommendations
        // For now, return sample recommendations
        return [
            [
                'name' => 'Orlando Science Center',
                'description' => 'Interactive science museum perfect for families',
                'distance' => '15 minutes',
                'rating' => 4.5,
                'category' => 'attraction'
            ],
            [
                'name' => 'Lake Eola Park',
                'description' => 'Beautiful downtown park with swan boats',
                'distance' => '20 minutes',
                'rating' => 4.3,
                'category' => 'outdoor'
            ],
            [
                'name' => 'The Rusty Spoon',
                'description' => 'Farm-to-table restaurant with local ingredients',
                'distance' => '10 minutes',
                'rating' => 4.7,
                'category' => 'dining'
            ]
        ];
    }
}
?>
