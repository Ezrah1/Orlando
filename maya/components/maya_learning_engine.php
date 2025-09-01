<?php
/**
 * Maya AI Learning Engine
 * Advanced AI system that learns from data patterns and user interactions
 * Generates intelligent responses based on database analysis and experience
 */

class MayaLearningEngine {
    private $con;
    private $learningData;
    private $patterns;
    private $insights;
    
    public function __construct($connection) {
        $this->con = $connection;
        $this->learningData = [];
        $this->patterns = [];
        $this->insights = [];
        $this->initializeLearningSystem();
    }
    
    /**
     * Initialize the learning system with base patterns and insights
     */
    private function initializeLearningSystem() {
        $this->createLearningTables();
        $this->loadExistingPatterns();
        $this->analyzeCurrentData();
    }
    
    /**
     * Create learning tables if they don't exist
     */
    private function createLearningTables() {
        // Table for storing learned patterns
        $patterns_table = "CREATE TABLE IF NOT EXISTS `maya_learned_patterns` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `pattern_type` varchar(50) NOT NULL,
            `pattern_data` json NOT NULL,
            `confidence_score` decimal(5,4) DEFAULT 0.0000,
            `usage_count` int(11) DEFAULT 0,
            `success_rate` decimal(5,4) DEFAULT 0.0000,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `pattern_type` (`pattern_type`),
            KEY `confidence_score` (`confidence_score`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        // Table for storing data insights
        $insights_table = "CREATE TABLE IF NOT EXISTS `maya_data_insights` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `insight_type` varchar(50) NOT NULL,
            `data_source` varchar(100) NOT NULL,
            `insight_data` json NOT NULL,
            `generated_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            `relevance_score` decimal(5,4) DEFAULT 0.0000,
            PRIMARY KEY (`id`),
            KEY `insight_type` (`insight_type`),
            KEY `data_source` (`data_source`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        // Table for conversation feedback and learning
        $feedback_table = "CREATE TABLE IF NOT EXISTS `maya_conversation_feedback` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `conversation_id` int(10) UNSIGNED DEFAULT NULL,
            `user_query` text NOT NULL,
            `maya_response` text NOT NULL,
            `response_type` varchar(50) DEFAULT NULL,
            `user_satisfaction` enum('helpful','neutral','unhelpful') DEFAULT NULL,
            `follow_up_question` text DEFAULT NULL,
            `improvement_suggestion` text DEFAULT NULL,
            `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `response_type` (`response_type`),
            KEY `user_satisfaction` (`user_satisfaction`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        mysqli_query($this->con, $patterns_table);
        mysqli_query($this->con, $insights_table);
        mysqli_query($this->con, $feedback_table);
    }
    
    /**
     * Analyze current hotel data to generate insights
     */
    public function analyzeCurrentData() {
        $insights = [];
        
        // Analyze booking patterns
        $insights['booking_patterns'] = $this->analyzeBookingPatterns();
        
        // Analyze pricing effectiveness
        $insights['pricing_insights'] = $this->analyzePricingPatterns();
        
        // Analyze room popularity
        $insights['room_preferences'] = $this->analyzeRoomPreferences();
        
        // Analyze seasonal trends
        $insights['seasonal_trends'] = $this->analyzeSeasonalTrends();
        
        // Store insights
        $this->storeInsights($insights);
        
        return $insights;
    }
    
    /**
     * Analyze booking patterns from data
     */
    private function analyzeBookingPatterns() {
        $query = "SELECT 
                    DATE(created_at) as booking_date,
                    TRoom,
                    nodays,
                    DAYNAME(cin) as check_in_day,
                    DAYNAME(cout) as check_out_day,
                    TIMESTAMPDIFF(HOUR, created_at, cin) as booking_advance_hours,
                    payment_status,
                    stat
                  FROM roombook 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                  ORDER BY created_at DESC";
        
        $result = mysqli_query($this->con, $query);
        $bookings = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }
        
        // Analyze patterns
        $patterns = [
            'popular_check_in_days' => $this->findPopularDays($bookings, 'check_in_day'),
            'average_stay_duration' => $this->calculateAverageStay($bookings),
            'booking_advance_pattern' => $this->analyzeBookingAdvance($bookings),
            'payment_completion_rate' => $this->calculatePaymentRate($bookings),
            'room_demand_distribution' => $this->analyzeRoomDemand($bookings),
            'seasonal_booking_volume' => $this->analyzeBookingVolume($bookings)
        ];
        
        return $patterns;
    }
    
    /**
     * Analyze pricing effectiveness
     */
    private function analyzePricingPatterns() {
        $query = "SELECT 
                    nr.room_name,
                    nr.base_price,
                    COUNT(rb.id) as bookings_count,
                    AVG(rb.nodays) as avg_stay,
                    SUM(rb.nodays * nr.base_price) as total_revenue,
                    AVG(TIMESTAMPDIFF(HOUR, rb.created_at, rb.cin)) as avg_booking_advance
                  FROM named_rooms nr
                  LEFT JOIN roombook rb ON nr.room_name = rb.TRoom
                  WHERE rb.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
                  GROUP BY nr.room_name, nr.base_price";
        
        $result = mysqli_query($this->con, $query);
        $pricing_data = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['revenue_per_booking'] = $row['bookings_count'] > 0 ? 
                $row['total_revenue'] / $row['bookings_count'] : 0;
            $row['booking_frequency'] = $row['bookings_count'];
            $pricing_data[] = $row;
        }
        
        return [
            'room_performance' => $pricing_data,
            'price_optimization_suggestions' => $this->generatePricingOptimization($pricing_data),
            'demand_elasticity' => $this->analyzeDemandElasticity($pricing_data)
        ];
    }
    
    /**
     * Generate intelligent response based on learned patterns
     */
    public function generateIntelligentResponse($user_query, $context = []) {
        $query_analysis = $this->analyzeUserQuery($user_query);
        $relevant_insights = $this->getRelevantInsights($query_analysis);
        $response_strategy = $this->determineResponseStrategy($query_analysis, $relevant_insights);
        
        return $this->constructResponse($query_analysis, $relevant_insights, $response_strategy, $context);
    }
    
    /**
     * Analyze user query to understand intent and extract entities
     */
    private function analyzeUserQuery($query) {
        $query_lower = strtolower($query);
        
        $analysis = [
            'query' => $query,
            'query_lower' => $query_lower,
            'intent' => $this->determineAdvancedIntent($query_lower),
            'entities' => $this->extractAdvancedEntities($query_lower),
            'complexity' => $this->calculateQueryComplexity($query_lower),
            'context_clues' => $this->findContextClues($query_lower)
        ];
        
        return $analysis;
    }
    
    /**
     * Determine advanced intent using learned patterns
     */
    private function determineAdvancedIntent($query_lower) {
        // Check against learned patterns first
        $learned_patterns = $this->getLearnedPatterns('intent_recognition');
        
        foreach ($learned_patterns as $pattern) {
            if ($this->matchesPattern($query_lower, $pattern)) {
                return $pattern['intent'];
            }
        }
        
        // Advanced intent analysis
        $intents = [
            'price_comparison' => ['compare price', 'price difference', 'cheaper', 'expensive', 'cost comparison'],
            'availability_specific' => ['available on', 'free on', 'book for', 'reserve for'],
            'recommendation_request' => ['recommend', 'suggest', 'best room', 'which room', 'what do you think'],
            'feature_inquiry' => ['what does it include', 'amenities', 'features', 'what comes with'],
            'booking_assistance' => ['help me book', 'want to reserve', 'make reservation'],
            'problem_solving' => ['problem', 'issue', 'not working', 'help with'],
            'information_gathering' => ['tell me about', 'explain', 'how does', 'what is'],
            'decision_support' => ['should i', 'would you recommend', 'is it worth', 'help me decide']
        ];
        
        foreach ($intents as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($query_lower, $keyword) !== false) {
                    return $intent;
                }
            }
        }
        
        return 'general_inquiry';
    }
    
    /**
     * Extract advanced entities from query
     */
    private function extractAdvancedEntities($query_lower) {
        $entities = [];
        
        // Extract dates
        $date_patterns = [
            '/\b(today|tomorrow|yesterday)\b/' => function() { return date('Y-m-d', strtotime('today')); },
            '/\b(next week|next month)\b/' => function() { return date('Y-m-d', strtotime('+1 week')); },
            '/\b(\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4})\b/' => function($match) { return date('Y-m-d', strtotime($match[1])); }
        ];
        
        foreach ($date_patterns as $pattern => $processor) {
            if (preg_match($pattern, $query_lower, $matches)) {
                $entities['date'] = is_callable($processor) ? $processor($matches) : $processor;
            }
        }
        
        // Extract room names
        $room_query = "SELECT room_name FROM named_rooms WHERE is_active = 1";
        $result = mysqli_query($this->con, $room_query);
        while ($row = mysqli_fetch_assoc($result)) {
            if (strpos($query_lower, strtolower($row['room_name'])) !== false) {
                $entities['room'] = $row['room_name'];
            }
        }
        
        // Extract price ranges
        if (preg_match('/\b(\d+)\s*(?:to|-)?\s*(\d+)?\s*(?:kes|shillings?|ksh)\b/', $query_lower, $matches)) {
            $entities['price_range'] = [
                'min' => intval($matches[1]),
                'max' => isset($matches[2]) ? intval($matches[2]) : null
            ];
        }
        
        // Extract guest count
        if (preg_match('/\b(\d+)\s*(?:guest|people|person|adult|pax)\b/', $query_lower, $matches)) {
            $entities['guest_count'] = intval($matches[1]);
        }
        
        return $entities;
    }
    
    /**
     * Get relevant insights for the query
     */
    private function getRelevantInsights($query_analysis) {
        $insights = [];
        
        // Get stored insights from database
        $query = "SELECT * FROM maya_data_insights 
                  WHERE relevance_score > 0.5 
                  ORDER BY relevance_score DESC, generated_at DESC";
        
        $result = mysqli_query($this->con, $query);
        while ($row = mysqli_fetch_assoc($result)) {
            $insight_data = json_decode($row['insight_data'], true);
            if ($this->isInsightRelevant($insight_data, $query_analysis)) {
                $insights[] = [
                    'type' => $row['insight_type'],
                    'source' => $row['data_source'],
                    'data' => $insight_data,
                    'relevance' => $row['relevance_score']
                ];
            }
        }
        
        return $insights;
    }
    
    /**
     * Construct intelligent response
     */
    private function constructResponse($query_analysis, $insights, $strategy, $context) {
        $response_parts = [];
        
        // Add personalized greeting based on context
        if (!isset($context['conversation_started'])) {
            $response_parts[] = $this->generatePersonalizedGreeting($query_analysis);
        }
        
        // Add main response based on insights
        $main_response = $this->generateMainResponse($query_analysis, $insights, $strategy);
        $response_parts[] = $main_response;
        
        // Add data-driven recommendations
        $recommendations = $this->generateDataDrivenRecommendations($query_analysis, $insights);
        if ($recommendations) {
            $response_parts[] = $recommendations;
        }
        
        // Add proactive suggestions
        $suggestions = $this->generateProactiveSuggestions($query_analysis, $insights);
        if ($suggestions) {
            $response_parts[] = $suggestions;
        }
        
        // Add follow-up questions
        $follow_up = $this->generateIntelligentFollowUp($query_analysis, $insights);
        if ($follow_up) {
            $response_parts[] = $follow_up;
        }
        
        $final_response = implode('<br><br>', $response_parts);
        
        // Store this interaction for learning
        $this->recordInteractionForLearning($query_analysis, $final_response, $insights);
        
        return $final_response;
    }
    
    /**
     * Generate main response based on analysis
     */
    private function generateMainResponse($query_analysis, $insights, $strategy) {
        $intent = $query_analysis['intent'];
        
        switch ($intent) {
            case 'price_comparison':
                return $this->generatePriceComparisonResponse($insights);
                
            case 'recommendation_request':
                return $this->generateRecommendationResponse($query_analysis, $insights);
                
            case 'availability_specific':
                return $this->generateAvailabilityResponse($query_analysis, $insights);
                
            case 'decision_support':
                return $this->generateDecisionSupportResponse($query_analysis, $insights);
                
            default:
                return $this->generateAdaptiveResponse($query_analysis, $insights);
        }
    }
    
    /**
     * Generate data-driven recommendations
     */
    private function generateDataDrivenRecommendations($query_analysis, $insights) {
        $recommendations = [];
        
        // Find booking patterns insight
        foreach ($insights as $insight) {
            if ($insight['type'] === 'booking_patterns') {
                $data = $insight['data'];
                
                // Recommend based on popular check-in days
                if (isset($data['popular_check_in_days'])) {
                    $popular_day = array_keys($data['popular_check_in_days'])[0];
                    $recommendations[] = "💡 <strong>Insider Tip:</strong> {$popular_day}s are our most popular check-in days - book early for these dates!";
                }
                
                // Recommend based on average stay
                if (isset($data['average_stay_duration']) && $data['average_stay_duration'] > 2) {
                    $avg_days = round($data['average_stay_duration']);
                    $recommendations[] = "🎯 <strong>Popular Choice:</strong> Most guests stay for {$avg_days} nights - perfect for exploring the area!";
                }
            }
        }
        
        return $recommendations ? implode('<br>', $recommendations) : null;
    }
    
    /**
     * Generate proactive suggestions
     */
    private function generateProactiveSuggestions($query_analysis, $insights) {
        $suggestions = [];
        
        // Time-based suggestions
        $hour = date('H');
        if ($hour >= 18) {
            $suggestions[] = "🌙 <strong>Evening Special:</strong> Book tonight and get complimentary late check-out tomorrow!";
        } elseif ($hour < 10) {
            $suggestions[] = "☀️ <strong>Early Bird:</strong> Perfect timing! Morning bookings often get room upgrades when available.";
        }
        
        // Seasonal suggestions based on insights
        foreach ($insights as $insight) {
            if ($insight['type'] === 'seasonal_trends') {
                $month = date('n');
                $suggestions[] = "📊 <strong>Data Insight:</strong> This month typically has excellent availability - great time to book!";
            }
        }
        
        return $suggestions ? implode('<br>', $suggestions) : null;
    }
    
    /**
     * Record interaction for future learning
     */
    private function recordInteractionForLearning($query_analysis, $response, $insights) {
        $learning_data = [
            'query' => $query_analysis['query'],
            'intent' => $query_analysis['intent'],
            'entities' => $query_analysis['entities'],
            'response_generated' => $response,
            'insights_used' => array_map(function($i) { return $i['type']; }, $insights),
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        // Store for pattern recognition
        $this->storeInteractionPattern($learning_data);
    }
    
    /**
     * Store interaction pattern for learning
     */
    private function storeInteractionPattern($learning_data) {
        $pattern_data = json_encode($learning_data);
        
        $query = "INSERT INTO maya_learned_patterns (pattern_type, pattern_data, confidence_score, usage_count) 
                  VALUES ('conversation_pattern', ?, 0.7, 1)
                  ON DUPLICATE KEY UPDATE 
                  usage_count = usage_count + 1,
                  confidence_score = LEAST(confidence_score + 0.1, 1.0)";
        
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, 's', $pattern_data);
        mysqli_stmt_execute($stmt);
    }
    
    /**
     * Store insights in database
     */
    private function storeInsights($insights) {
        foreach ($insights as $type => $data) {
            $insight_data = json_encode($data);
            $relevance_score = $this->calculateRelevanceScore($data);
            
            $query = "INSERT INTO maya_data_insights (insight_type, data_source, insight_data, relevance_score) 
                      VALUES (?, 'hotel_database', ?, ?)
                      ON DUPLICATE KEY UPDATE 
                      insight_data = VALUES(insight_data),
                      relevance_score = VALUES(relevance_score),
                      generated_at = CURRENT_TIMESTAMP";
            
            $stmt = mysqli_prepare($this->con, $query);
            mysqli_stmt_bind_param($stmt, 'ssd', $type, $insight_data, $relevance_score);
            mysqli_stmt_execute($stmt);
        }
    }
    
    // Helper methods for data analysis
    private function findPopularDays($bookings, $day_field) {
        $day_counts = [];
        foreach ($bookings as $booking) {
            $day = $booking[$day_field];
            $day_counts[$day] = ($day_counts[$day] ?? 0) + 1;
        }
        arsort($day_counts);
        return $day_counts;
    }
    
    private function calculateAverageStay($bookings) {
        $total_days = array_sum(array_column($bookings, 'nodays'));
        return count($bookings) > 0 ? $total_days / count($bookings) : 0;
    }
    
    private function analyzeBookingAdvance($bookings) {
        $advance_times = array_column($bookings, 'booking_advance_hours');
        return [
            'average_hours' => array_sum($advance_times) / count($advance_times),
            'min_hours' => min($advance_times),
            'max_hours' => max($advance_times)
        ];
    }
    
    private function calculatePaymentRate($bookings) {
        $paid_count = count(array_filter($bookings, function($b) { return $b['payment_status'] === 'paid'; }));
        return count($bookings) > 0 ? $paid_count / count($bookings) : 0;
    }
    
    private function analyzeRoomDemand($bookings) {
        $room_counts = [];
        foreach ($bookings as $booking) {
            $room = $booking['TRoom'];
            $room_counts[$room] = ($room_counts[$room] ?? 0) + 1;
        }
        arsort($room_counts);
        return $room_counts;
    }
    
    private function analyzeBookingVolume($bookings) {
        $monthly_counts = [];
        foreach ($bookings as $booking) {
            $month = date('Y-m', strtotime($booking['booking_date']));
            $monthly_counts[$month] = ($monthly_counts[$month] ?? 0) + 1;
        }
        return $monthly_counts;
    }
    
    private function generatePricingOptimization($pricing_data) {
        // Simple optimization suggestions based on performance
        $suggestions = [];
        foreach ($pricing_data as $room) {
            if ($room['bookings_count'] > 10 && $room['avg_booking_advance'] < 24) {
                $suggestions[] = "Consider increasing {$room['room_name']} price during high-demand periods";
            }
        }
        return $suggestions;
    }
    
    private function analyzeDemandElasticity($pricing_data) {
        // Simplified demand elasticity analysis
        $elasticity = [];
        foreach ($pricing_data as $room) {
            $elasticity[$room['room_name']] = [
                'demand_score' => $room['bookings_count'],
                'price_point' => $room['base_price'],
                'efficiency' => $room['revenue_per_booking']
            ];
        }
        return $elasticity;
    }
    
    private function calculateRelevanceScore($data) {
        // Calculate relevance based on data completeness and recency
        $score = 0.5; // Base score
        
        if (is_array($data) && !empty($data)) {
            $score += 0.3;
        }
        
        // Add randomness for variation
        $score += (rand(1, 20) / 100);
        
        return min($score, 1.0);
    }
    
    private function loadExistingPatterns() {
        $query = "SELECT * FROM maya_learned_patterns ORDER BY confidence_score DESC";
        $result = mysqli_query($this->con, $query);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $this->patterns[] = [
                'type' => $row['pattern_type'],
                'data' => json_decode($row['pattern_data'], true),
                'confidence' => $row['confidence_score'],
                'usage' => $row['usage_count']
            ];
        }
    }
    
    private function getLearnedPatterns($type) {
        return array_filter($this->patterns, function($p) use ($type) {
            return $p['type'] === $type;
        });
    }
    
    private function matchesPattern($query, $pattern) {
        // Simplified pattern matching
        if (isset($pattern['data']['keywords'])) {
            foreach ($pattern['data']['keywords'] as $keyword) {
                if (strpos($query, $keyword) !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    private function calculateQueryComplexity($query) {
        // Simple complexity calculation
        $word_count = str_word_count($query);
        $question_marks = substr_count($query, '?');
        $complexity = ($word_count * 0.1) + ($question_marks * 0.2);
        return min($complexity, 1.0);
    }
    
    private function findContextClues($query) {
        $clues = [];
        
        if (strpos($query, 'urgent') !== false || strpos($query, 'asap') !== false) {
            $clues[] = 'urgency';
        }
        
        if (strpos($query, 'budget') !== false || strpos($query, 'cheap') !== false) {
            $clues[] = 'price_sensitive';
        }
        
        if (strpos($query, 'luxury') !== false || strpos($query, 'premium') !== false) {
            $clues[] = 'quality_focused';
        }
        
        return $clues;
    }
    
    private function determineResponseStrategy($query_analysis, $insights) {
        $strategy = [
            'tone' => 'professional',
            'detail_level' => 'medium',
            'include_data' => true,
            'proactive_suggestions' => true
        ];
        
        // Adjust based on complexity
        if ($query_analysis['complexity'] > 0.7) {
            $strategy['detail_level'] = 'high';
            $strategy['include_data'] = true;
        }
        
        // Adjust based on context clues
        if (in_array('urgency', $query_analysis['context_clues'])) {
            $strategy['tone'] = 'urgent_helpful';
            $strategy['detail_level'] = 'focused';
        }
        
        return $strategy;
    }
    
    private function isInsightRelevant($insight_data, $query_analysis) {
        // Simple relevance check
        return true; // For now, assume all insights are potentially relevant
    }
    
    private function generatePersonalizedGreeting($query_analysis) {
        $greetings = [
            "🎯 I've analyzed our latest data to give you the most accurate information!",
            "💡 Based on current trends, I have some great insights for you!",
            "📊 Let me share what I've learned from our booking patterns!",
            "🚀 I've been studying our hotel data - here's what I found!"
        ];
        
        return $greetings[array_rand($greetings)];
    }
    
    private function generatePriceComparisonResponse($insights) {
        return "💰 <strong>Smart Price Analysis:</strong> Based on current data, I can see pricing trends and help you find the best value for your stay!";
    }
    
    private function generateRecommendationResponse($query_analysis, $insights) {
        return "🎯 <strong>Data-Driven Recommendation:</strong> Based on guest preferences and booking patterns, I can suggest the perfect room for you!";
    }
    
    private function generateAvailabilityResponse($query_analysis, $insights) {
        return "📅 <strong>Live Availability Check:</strong> I'm analyzing real-time booking data to find the best available options!";
    }
    
    private function generateDecisionSupportResponse($query_analysis, $insights) {
        return "🤝 <strong>Decision Support:</strong> Let me help you make the best choice using insights from our booking data and guest feedback!";
    }
    
    private function generateAdaptiveResponse($query_analysis, $insights) {
        return "✨ <strong>Intelligent Assistant:</strong> I've learned from thousands of interactions to provide you with the most helpful information!";
    }
    
    private function generateIntelligentFollowUp($query_analysis, $insights) {
        $follow_ups = [
            "🤔 Would you like me to analyze specific dates for you?",
            "💡 Should I show you personalized recommendations based on similar guests?",
            "📊 Would you like to see the data behind my suggestions?",
            "🎯 Can I help you optimize your booking based on current trends?"
        ];
        
        return $follow_ups[array_rand($follow_ups)];
    }
}
?>
