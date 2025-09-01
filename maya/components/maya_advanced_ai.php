<?php
/**
 * Maya Advanced AI Engine
 * Enhanced natural language processing and conversation intelligence
 * Orlando International Resorts
 */

class MayaAdvancedAI {
    private $conversationContext = [];
    private $userProfile = [];
    private $emotionalState = 'neutral';
    private $conversationHistory = [];
    private $knowledgeBase = [];
    private $responseVariations = [];
    
    public function __construct() {
        $this->initializeAdvancedKnowledge();
        $this->initializeResponseVariations();
        $this->initializePersonalityTraits();
    }
    
    private function initializeAdvancedKnowledge() {
        $this->knowledgeBase = [
            // Enhanced room knowledge with detailed information
            'rooms' => [
                'patterns' => [
                    'basic' => ['room', 'rooms', 'accommodation', 'stay', 'book', 'booking'],
                    'specific' => ['deluxe', 'suite', 'standard', 'premium', 'luxury', 'budget'],
                    'features' => ['bed', 'bathroom', 'view', 'size', 'capacity', 'amenities'],
                    'comparison' => ['compare', 'difference', 'which', 'best', 'recommend', 'suggest']
                ],
                'responses' => [
                    'general' => [
                        "I'd be happy to help you with our rooms! We have several different options. What are you looking for?",
                        "Our rooms are all unique with different features and pricing. What kind of room would work best for you?",
                        "I can help you find the right room. Are you looking for something specific or would you like me to show you the options?"
                    ],
                    'specific' => [
                        "That's a great choice! Let me tell you what makes that room special.",
                        "Good pick! That room is popular with our guests. Here's what you should know about it."
                    ]
                ]
            ],
            
            // Advanced pricing with dynamic calculations
            'pricing' => [
                'patterns' => [
                    'basic' => ['price', 'cost', 'rate', 'charge', 'fee', 'expensive', 'cheap', 'budget'],
                    'comparative' => ['cheaper', 'more expensive', 'compare prices', 'best deal', 'discount'],
                    'temporal' => ['tonight', 'weekend', 'weekday', 'holiday', 'season', 'peak'],
                    'negotiation' => ['deal', 'discount', 'offer', 'special', 'promotion', 'lower price']
                ],
                'responses' => [
                    'general' => [
                        "💰 I love helping guests find the perfect balance of value and comfort! Our pricing is designed to be transparent and fair.",
                        "📊 Let me break down our pricing structure for you - it's quite competitive and includes many value-adds that other hotels charge extra for!",
                        "💡 Smart question! I can show you how to get the best value, including some insider tips for savings."
                    ]
                ]
            ],
            
            // Intelligent availability with predictive insights
            'availability' => [
                'patterns' => [
                    'immediate' => ['tonight', 'now', 'today', 'urgent', 'last minute'],
                    'future' => ['tomorrow', 'next week', 'next month', 'advance booking'],
                    'flexible' => ['flexible', 'any time', 'whenever', 'open dates'],
                    'specific' => ['january', 'february', 'weekend', 'holiday', 'christmas']
                ],
                'responses' => [
                    'immediate' => [
                        "⚡ Last-minute booking? I've got you covered! Let me check our real-time availability right now.",
                        "🕐 Perfect timing! I can secure a room for you tonight - sometimes the best adventures are spontaneous!"
                    ],
                    'future' => [
                        "📅 Planning ahead is smart! I can show you the best available dates and even suggest optimal timing for better rates.",
                        "🎯 Excellent forward planning! Let me help you find the perfect dates with the best availability and pricing."
                    ]
                ]
            ],
            
            // Emotional intelligence responses
            'emotions' => [
                'excitement' => [
                    "🎉 I can feel your excitement! That makes me so happy - I love helping guests plan amazing stays!",
                    "✨ Your enthusiasm is contagious! I'm thrilled to be part of your travel planning journey!"
                ],
                'concern' => [
                    "😊 I understand your concerns, and I'm here to address every single one. Let's make sure you feel completely confident about your choice.",
                    "💙 Your peace of mind is my priority. I'll walk you through everything step by step until you're completely comfortable."
                ],
                'confusion' => [
                    "🤝 No worries at all! Sometimes there's a lot to consider. Let me simplify everything for you.",
                    "💡 I get it - choosing the right room can feel overwhelming. Let me guide you through this step by step."
                ]
            ]
        ];
    }
    
    private function initializeResponseVariations() {
        $this->responseVariations = [
            'greeting' => [
                'first_time' => [
                    "Hi there! I'm Maya, and I'm here to help you with anything you need about our hotel. What can I do for you?",
                    "Hello! I'm Maya, your assistant at Orlando International Resorts. How can I help you today?",
                    "Hi! I'm Maya. I'm here to help you with bookings, room information, or any questions you might have."
                ],
                'returning' => [
                    "Welcome back! How can I help you today?",
                    "Good to see you again! What can I assist you with?",
                    "Hello again! What would you like to know?"
                ]
            ],
            
            'thinking' => [
                "Let me think about that for a moment...",
                "That's a good question! Let me see what I can tell you...",
                "Give me a second to check on that for you...",
                "Let me look into that..."
            ],
            
            'understanding' => [
                "Ah, I see what you're looking for!",
                "Got it! That makes sense.",
                "I understand exactly what you need.",
                "Right, I can help with that."
            ]
        ];
    }
    
    private function initializePersonalityTraits() {
        $this->personalityTraits = [
            'enthusiasm_level' => 0.8, // 0-1 scale
            'formality_level' => 0.3,  // 0 = casual, 1 = formal
            'helpfulness_level' => 0.95,
            'proactivity_level' => 0.7,
            'empathy_level' => 0.85
        ];
    }
    
    // Advanced natural language processing
    public function processMessage($userMessage, $context = []) {
        $this->updateConversationContext($userMessage, $context);
        $intent = $this->analyzeIntent($userMessage);
        $sentiment = $this->analyzeSentiment($userMessage);
        $entities = $this->extractEntities($userMessage);
        
        $response = $this->generateAdvancedResponse($intent, $sentiment, $entities, $userMessage);
        
        $this->logConversationStep($userMessage, $response, $intent, $sentiment);
        
        return [
            'response' => $response,
            'intent' => $intent,
            'sentiment' => $sentiment,
            'entities' => $entities,
            'suggestions' => $this->generateSmartSuggestions($intent, $entities),
            'follow_up_questions' => $this->generateFollowUpQuestions($intent)
        ];
    }
    
    private function analyzeIntent($message) {
        $message_lower = strtolower($message);
        $words = explode(' ', $message_lower);
        
        $intents = [
            'booking_immediate' => ['book now', 'reserve now', 'available tonight', 'urgent booking'],
            'booking_future' => ['book for', 'reserve for', 'plan ahead', 'future booking'],
            'room_comparison' => ['compare rooms', 'which room', 'best room', 'recommend room'],
            'pricing_inquiry' => ['how much', 'price', 'cost', 'rate', 'budget'],
            'availability_check' => ['available', 'vacancy', 'free rooms', 'check dates'],
            'amenities_question' => ['amenities', 'facilities', 'what included', 'features'],
            'location_question' => ['where located', 'address', 'directions', 'nearby'],
            'policy_question' => ['cancel', 'refund', 'policy', 'rules'],
            'complaint_concern' => ['problem', 'issue', 'wrong', 'bad', 'disappointed'],
            'compliment_praise' => ['great', 'excellent', 'amazing', 'love', 'perfect'],
            'help_general' => ['help', 'assist', 'support', 'guide'],
            'greeting' => ['hello', 'hi', 'hey', 'good morning', 'good evening']
        ];
        
        $scores = [];
        foreach ($intents as $intent => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                if (strpos($message_lower, $keyword) !== false) {
                    $score += strlen($keyword); // Longer matches get higher scores
                }
            }
            $scores[$intent] = $score;
        }
        
        $best_intent = array_keys($scores, max($scores))[0];
        return max($scores) > 0 ? $best_intent : 'general_inquiry';
    }
    
    private function analyzeSentiment($message) {
        $positive_words = ['great', 'excellent', 'amazing', 'love', 'perfect', 'wonderful', 'fantastic', 'awesome', 'good', 'nice', 'happy', 'excited'];
        $negative_words = ['bad', 'terrible', 'awful', 'hate', 'disappointed', 'frustrated', 'angry', 'sad', 'problem', 'issue', 'wrong'];
        $neutral_words = ['okay', 'fine', 'alright', 'maybe', 'perhaps'];
        
        $message_lower = strtolower($message);
        $positive_score = 0;
        $negative_score = 0;
        
        foreach ($positive_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $positive_score++;
            }
        }
        
        foreach ($negative_words as $word) {
            if (strpos($message_lower, $word) !== false) {
                $negative_score++;
            }
        }
        
        if ($positive_score > $negative_score) {
            return 'positive';
        } elseif ($negative_score > $positive_score) {
            return 'negative';
        } else {
            return 'neutral';
        }
    }
    
    private function extractEntities($message) {
        $entities = [];
        $message_lower = strtolower($message);
        
        // Extract room names
        $room_names = ['merit', 'eatonville', 'deluxe', 'suite', 'standard', 'premium'];
        foreach ($room_names as $room) {
            if (strpos($message_lower, $room) !== false) {
                $entities['room_type'] = $room;
            }
        }
        
        // Extract dates
        $date_patterns = [
            'tonight' => date('Y-m-d'),
            'tomorrow' => date('Y-m-d', strtotime('+1 day')),
            'next week' => date('Y-m-d', strtotime('+1 week')),
            'weekend' => date('Y-m-d', strtotime('next saturday'))
        ];
        
        foreach ($date_patterns as $pattern => $date) {
            if (strpos($message_lower, $pattern) !== false) {
                $entities['preferred_date'] = $date;
                $entities['date_preference'] = $pattern;
            }
        }
        
        // Extract number of guests
        preg_match('/(\d+)\s*(guest|person|people|adult)/', $message_lower, $matches);
        if (!empty($matches)) {
            $entities['guest_count'] = intval($matches[1]);
        }
        
        // Extract price range
        preg_match('/(\d+)\s*-\s*(\d+)/', $message, $price_matches);
        if (!empty($price_matches)) {
            $entities['price_min'] = intval($price_matches[1]);
            $entities['price_max'] = intval($price_matches[2]);
        }
        
        return $entities;
    }
    
    private function generateAdvancedResponse($intent, $sentiment, $entities, $original_message) {
        // Choose response based on sentiment
        if ($sentiment === 'negative') {
            return $this->generateEmpathticResponse($intent, $entities);
        } elseif ($sentiment === 'positive') {
            return $this->generateEnthusiasticResponse($intent, $entities);
        }
        
        // Generate contextual response based on intent
        switch ($intent) {
            case 'booking_immediate':
                return $this->generateImmediateBookingResponse($entities);
                
            case 'room_comparison':
                return $this->generateRoomComparisonResponse($entities);
                
            case 'pricing_inquiry':
                return $this->generateAdvancedPricingResponse($entities);
                
            case 'availability_check':
                return $this->generateIntelligentAvailabilityResponse($entities);
                
            case 'complaint_concern':
                return $this->generateProblemSolvingResponse($entities);
                
            case 'compliment_praise':
                return $this->generateGratefulResponse($entities);
                
            default:
                return $this->generateContextualResponse($intent, $entities);
        }
    }
    
    private function generateImmediateBookingResponse($entities) {
        $responses = [
            "⚡ Absolutely! I love the spontaneous spirit! Let me check what's available for immediate booking right now.",
            "🏃‍♀️ Last-minute booking coming right up! I'm checking our real-time availability as we speak.",
            "🎯 Perfect timing! Sometimes the best adventures are unplanned. Let me secure something amazing for you tonight!"
        ];
        
        $base_response = $responses[array_rand($responses)];
        
        if (isset($entities['guest_count'])) {
            $base_response .= " I see you need accommodation for {$entities['guest_count']} guest" . ($entities['guest_count'] > 1 ? 's' : '') . ".";
        }
        
        return $base_response . "<br><br>🟢 <strong>Good news!</strong> We have immediate availability. Would you like me to show you the best options for tonight?";
    }
    
    private function generateRoomComparisonResponse($entities) {
        return "🏆 Excellent question! I'm like a personal shopping assistant for rooms - I know exactly what makes each one special. Let me create a personalized comparison for you based on what matters most:<br><br>💰 <strong>Budget-Friendly:</strong> Eatonville (KES 3,500) - Great value with all essentials<br>⭐ <strong>Premium Choice:</strong> Merit (KES 4,000) - Our most popular for good reason<br><br>What's most important to you: price, luxury, or specific amenities?";
    }
    
    private function generateAdvancedPricingResponse($entities) {
        $response = "💡 I love helping guests understand our pricing - it's designed to be both fair and transparent!<br><br>";
        
        if (isset($entities['price_min']) && isset($entities['price_max'])) {
            $response .= "🎯 I see you're looking in the KES {$entities['price_min']} - {$entities['price_max']} range. Perfect! That gives us several excellent options.<br><br>";
        }
        
        $response .= "📊 <strong>Smart Pricing Breakdown:</strong><br>";
        $response .= "• <strong>Weekdays:</strong> Save 5% off base rates<br>";
        $response .= "• <strong>Weekends:</strong> Peak pricing (+15%)<br>";
        $response .= "• <strong>Extended Stay:</strong> 3+ nights = 10% discount<br>";
        $response .= "• <strong>No Hidden Fees:</strong> What you see is what you pay!<br><br>";
        $response .= "💰 <strong>Value Adds Included:</strong> WiFi, Parking, Security - worth KES 800+ elsewhere!";
        
        return $response;
    }
    
    private function generateIntelligentAvailabilityResponse($entities) {
        $response = "📅 I'm your personal availability detective! Let me check our real-time system...<br><br>";
        
        if (isset($entities['preferred_date'])) {
            $date_pref = $entities['date_preference'];
            $response .= "🎯 I see you're interested in <strong>$date_pref</strong>. Smart choice! ";
            
            switch ($date_pref) {
                case 'tonight':
                    $response .= "Tonight bookings often have the best rates!<br>";
                    break;
                case 'weekend':
                    $response .= "Weekends are popular, but I can secure you a spot!<br>";
                    break;
                case 'next week':
                    $response .= "Next week has excellent availability and better rates!<br>";
                    break;
            }
        }
        
        $response .= "<br>✅ <strong>Current Availability Status:</strong><br>";
        $response .= "🟢 <strong>Tonight:</strong> 3 rooms available<br>";
        $response .= "🟡 <strong>This Weekend:</strong> Limited (book quickly!)<br>";
        $response .= "🟢 <strong>Next Week:</strong> Excellent availability<br><br>";
        $response .= "🎯 <strong>Pro Tip:</strong> Booking 2+ days in advance often gets you better room selection!";
        
        return $response;
    }
    
    private function generateEmpathticResponse($intent, $entities) {
        $empathy_phrases = [
            "💙 I completely understand your concern, and I'm here to help resolve this.",
            "🤝 I hear you, and your satisfaction is my top priority. Let's fix this together.",
            "😊 I appreciate you sharing your concerns with me. Let me make this right."
        ];
        
        return $empathy_phrases[array_rand($empathy_phrases)] . " What specific issue can I help address for you?";
    }
    
    private function generateEnthusiasticResponse($intent, $entities) {
        $enthusiasm_phrases = [
            "🎉 I love your enthusiasm! It makes me so excited to help you!",
            "✨ Your positive energy is contagious! I'm thrilled to assist you!",
            "🌟 Amazing! I can feel your excitement, and I'm here to make it even better!"
        ];
        
        return $enthusiasm_phrases[array_rand($enthusiasm_phrases)];
    }
    
    private function generateSmartSuggestions($intent, $entities) {
        $suggestions = [];
        
        switch ($intent) {
            case 'booking_immediate':
                $suggestions = [
                    ['text' => '🌙 Show Tonight\'s Rooms', 'action' => 'show_tonight_rooms'],
                    ['text' => '💰 Best Rate Options', 'action' => 'show_best_rates'],
                    ['text' => '⚡ Quick Book Now', 'action' => 'quick_book']
                ];
                break;
                
            case 'pricing_inquiry':
                $suggestions = [
                    ['text' => '📊 Compare All Rates', 'action' => 'compare_rates'],
                    ['text' => '💸 Show Discounts', 'action' => 'show_discounts'],
                    ['text' => '📅 Price Calendar', 'action' => 'price_calendar']
                ];
                break;
                
            case 'room_comparison':
                $suggestions = [
                    ['text' => '🏆 Room Comparison Chart', 'action' => 'room_comparison'],
                    ['text' => '📸 Virtual Room Tour', 'action' => 'virtual_tour'],
                    ['text' => '⭐ Guest Reviews', 'action' => 'guest_reviews']
                ];
                break;
                
            default:
                $suggestions = [
                    ['text' => '🏨 Browse Rooms', 'action' => 'browse_rooms'],
                    ['text' => '💰 Check Pricing', 'action' => 'check_pricing'],
                    ['text' => '📅 View Calendar', 'action' => 'view_calendar'],
                    ['text' => '🚀 Book Now', 'action' => 'book_now']
                ];
        }
        
        return $suggestions;
    }
    
    private function generateFollowUpQuestions($intent) {
        $questions = [];
        
        switch ($intent) {
            case 'booking_immediate':
                $questions = [
                    "How many guests will be staying?",
                    "Do you have any specific room preferences?",
                    "Would you like me to check for any current promotions?"
                ];
                break;
                
            case 'pricing_inquiry':
                $questions = [
                    "What's your preferred price range?",
                    "Are you flexible with your dates for better rates?",
                    "Would you like to know about our loyalty discounts?"
                ];
                break;
                
            case 'room_comparison':
                $questions = [
                    "What amenities are most important to you?",
                    "Are you traveling for business or leisure?",
                    "Do you prefer a quieter room or central location?"
                ];
                break;
        }
        
        return array_slice($questions, 0, 2); // Return max 2 questions
    }
    
    private function updateConversationContext($message, $context) {
        $this->conversationContext['last_message'] = $message;
        $this->conversationContext['timestamp'] = time();
        $this->conversationContext['page'] = $context['page'] ?? 'unknown';
        
        // Build user profile based on conversation
        if (isset($context['room_preference'])) {
            $this->userProfile['preferred_room'] = $context['room_preference'];
        }
        
        // Track conversation flow
        $this->conversationHistory[] = [
            'message' => $message,
            'timestamp' => time(),
            'context' => $context
        ];
    }
    
    private function logConversationStep($userMessage, $response, $intent, $sentiment) {
        // This would normally log to database
        error_log("Maya Advanced AI - User: $userMessage | Intent: $intent | Sentiment: $sentiment");
    }
}
?>
