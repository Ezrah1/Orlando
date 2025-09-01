<?php
/**
 * Maya AI Conversation API
 * Handles conversation logging and knowledge base updates
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

// Handle CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'log_conversation':
            logConversation();
            break;
            
        case 'update_knowledge_usage':
            updateKnowledgeUsage();
            break;
            
        case 'get_maya_data':
            getMayaData();
            break;
            
        case 'search_knowledge':
            searchKnowledge();
            break;
            
        case 'get_live_rooms':
            getLiveRoomData();
            break;
            
        case 'get_live_pricing':
            getLivePricingData();
            break;
            
        case 'get_live_availability':
            getLiveAvailabilityData();
            break;
            
        case 'generate_intelligent_response':
            generateIntelligentResponse();
            break;
            
        case 'record_feedback':
            recordUserFeedback();
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function logConversation() {
    global $con;
    
    $agent_id = 1; // Maya's ID
    $session_id = mysqli_real_escape_string($con, $_POST['session_id'] ?? '');
    $user_message = mysqli_real_escape_string($con, $_POST['user_message'] ?? '');
    $ai_response = mysqli_real_escape_string($con, $_POST['ai_response'] ?? '');
    $response_category = mysqli_real_escape_string($con, $_POST['response_category'] ?? '');
    $page_context = mysqli_real_escape_string($con, $_POST['page_context'] ?? '');
    
    $query = "INSERT INTO ai_conversations (agent_id, session_id, user_message, ai_response, response_category, page_context) 
              VALUES ($agent_id, '$session_id', '$user_message', '$ai_response', '$response_category', '$page_context')";
    
    if (mysqli_query($con, $query)) {
        echo json_encode([
            'success' => true,
            'conversation_id' => mysqli_insert_id($con)
        ]);
    } else {
        throw new Exception('Failed to log conversation');
    }
}

function updateKnowledgeUsage() {
    global $con;
    
    $knowledge_id = (int)($_POST['knowledge_id'] ?? 0);
    
    $query = "UPDATE ai_knowledge_base SET usage_count = usage_count + 1 WHERE id = $knowledge_id";
    
    if (mysqli_query($con, $query)) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Failed to update knowledge usage');
    }
}

function getMayaData() {
    global $con;
    
    // Get Maya's info
    $maya_query = "SELECT * FROM ai_agents WHERE name = 'Maya' AND status = 'active' LIMIT 1";
    $maya_result = mysqli_query($con, $maya_query);
    $maya = mysqli_fetch_assoc($maya_result);
    
    // Get knowledge base
    $knowledge_query = "SELECT * FROM ai_knowledge_base WHERE agent_id = 1 AND is_active = 1 ORDER BY priority DESC";
    $knowledge_result = mysqli_query($con, $knowledge_query);
    $knowledge = [];
    while ($row = mysqli_fetch_assoc($knowledge_result)) {
        $knowledge[] = $row;
    }
    
    // Get quick actions for current page
    $page_context = mysqli_real_escape_string($con, $_GET['page_context'] ?? 'all');
    $actions_query = "SELECT * FROM ai_quick_actions 
                      WHERE agent_id = 1 AND is_active = 1 
                      AND (page_context = 'all' OR page_context = '$page_context')
                      ORDER BY sort_order ASC";
    $actions_result = mysqli_query($con, $actions_query);
    $actions = [];
    while ($row = mysqli_fetch_assoc($actions_result)) {
        $actions[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'maya' => $maya,
        'knowledge' => $knowledge,
        'actions' => $actions
    ]);
}

function searchKnowledge() {
    global $con;
    
    $search_term = mysqli_real_escape_string($con, $_GET['q'] ?? '');
    $search_term_lower = strtolower($search_term);
    
    $query = "SELECT *, 
              CASE 
                WHEN LOWER(question_keywords) LIKE '%$search_term_lower%' THEN priority * 2
                ELSE priority
              END as relevance_score
              FROM ai_knowledge_base 
              WHERE agent_id = 1 AND is_active = 1 
              AND (LOWER(question_keywords) LIKE '%$search_term_lower%' 
                   OR LOWER(response_template) LIKE '%$search_term_lower%')
              ORDER BY relevance_score DESC, usage_count DESC
              LIMIT 5";
              
    $result = mysqli_query($con, $query);
    $results = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $results[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'results' => $results
    ]);
}

// Function to get conversation analytics
function getConversationAnalytics() {
    global $con;
    
    $analytics = [];
    
    // Total conversations
    $total_query = "SELECT COUNT(*) as total FROM ai_conversations WHERE agent_id = 1";
    $result = mysqli_query($con, $total_query);
    $analytics['total_conversations'] = mysqli_fetch_assoc($result)['total'];
    
    // Conversations today
    $today_query = "SELECT COUNT(*) as today FROM ai_conversations 
                    WHERE agent_id = 1 AND DATE(created_at) = CURDATE()";
    $result = mysqli_query($con, $today_query);
    $analytics['conversations_today'] = mysqli_fetch_assoc($result)['today'];
    
    // Top categories
    $categories_query = "SELECT response_category, COUNT(*) as count 
                         FROM ai_conversations 
                         WHERE agent_id = 1 AND response_category IS NOT NULL
                         GROUP BY response_category 
                         ORDER BY count DESC 
                         LIMIT 5";
    $result = mysqli_query($con, $categories_query);
    $categories = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $categories[] = $row;
    }
    $analytics['top_categories'] = $categories;
    
    echo json_encode([
        'success' => true,
        'analytics' => $analytics
    ]);
}

// Live data functions for Maya
function getLiveRoomData() {
    global $con;
    
    try {
        $query = "SELECT nr.room_name, nr.base_price, nr.description, nr.is_active,
                         COALESCE(rs.current_status, 'available') as current_status, 
                         COALESCE(rs.cleaning_status, 'clean') as cleaning_status, 
                         rs.last_cleaned
                  FROM named_rooms nr 
                  LEFT JOIN room_status rs ON nr.room_name = rs.room_name 
                  WHERE nr.is_active = 1 
                  ORDER BY nr.base_price ASC";
        
        $result = mysqli_query($con, $query);
        $rooms = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rooms[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'rooms' => $rooms,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to fetch room data: ' . $e->getMessage()
        ]);
    }
}

function getLivePricingData() {
    global $con;
    
    try {
        $today = date('Y-m-d');
        $day_of_week = date('w');
        $is_weekend = ($day_of_week == 5 || $day_of_week == 6); // Friday or Saturday
        
        $query = "SELECT room_name, base_price, description 
                  FROM named_rooms 
                  WHERE is_active = 1 
                  ORDER BY base_price ASC";
        
        $result = mysqli_query($con, $query);
        $pricing = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $base_price = floatval($row['base_price']);
                
                // Apply dynamic pricing
                if ($is_weekend) {
                    $adjusted_price = $base_price * 1.15; // 15% weekend surcharge
                } else {
                    $adjusted_price = $base_price * 0.95; // 5% weekday discount
                }
                
                $row['current_price'] = round($adjusted_price, 2);
                $row['base_price'] = $base_price;
                $row['is_weekend'] = $is_weekend;
                $row['discount_applied'] = !$is_weekend;
                $row['pricing_date'] = $today;
                
                $pricing[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'pricing' => $pricing,
            'is_weekend' => $is_weekend,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to fetch pricing data: ' . $e->getMessage()
        ]);
    }
}

function getLiveAvailabilityData() {
    global $con;
    
    try {
        $query = "SELECT TRoom, cin, cout, stat, FName, LName, nodays 
                  FROM roombook 
                  WHERE cout > CURDATE() AND stat != 'cancelled'
                  ORDER BY cin ASC";
        
        $result = mysqli_query($con, $query);
        $availability = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $availability[] = $row;
            }
        }
        
        // Also get room status
        $status_query = "SELECT room_name, current_status, cleaning_status 
                         FROM room_status 
                         ORDER BY room_name";
        
        $status_result = mysqli_query($con, $status_query);
        $room_status = [];
        
        if ($status_result) {
            while ($row = mysqli_fetch_assoc($status_result)) {
                $room_status[] = $row;
            }
        }
        
        echo json_encode([
            'success' => true,
            'availability' => $availability,
            'room_status' => $room_status,
            'checked_date' => date('Y-m-d'),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to fetch availability data: ' . $e->getMessage()
        ]);
    }
}

// Intelligent response generation using learning engine
function generateIntelligentResponse() {
    global $con;
    
    try {
        $user_query = $_POST['query'] ?? '';
        $context = json_decode($_POST['context'] ?? '{}', true);
        
        if (empty($user_query)) {
            throw new Exception('Query is required');
        }
        
        // Try the intelligent engine first
        try {
            require_once __DIR__ . '/../components/maya_intelligent_engine.php';
            $intelligentEngine = new MayaIntelligentEngine($con);
            $response = $intelligentEngine->generateIntelligentResponse($user_query, $context);
        } catch (Exception $e) {
            // Fallback to knowledge base search
            $response = getKnowledgeBaseResponse($user_query);
        }
        
        echo json_encode([
            'success' => true,
            'response' => $response,
            'chatgpt_mode' => true,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to generate intelligent response: ' . $e->getMessage()
        ]);
    }
}

// Fallback knowledge base response function
function getKnowledgeBaseResponse($query) {
    global $con;
    
    $query_escaped = mysqli_real_escape_string($con, strtolower($query));
    
    // Search knowledge base
    $search_sql = "SELECT response_template FROM ai_knowledge_base 
                  WHERE is_active = 1 
                  AND (question_keywords LIKE '%$query_escaped%' 
                       OR '$query_escaped' REGEXP CONCAT('(', REPLACE(question_keywords, ',', '|'), ')'))
                  ORDER BY priority DESC 
                  LIMIT 1";
    
    $result = mysqli_query($con, $search_sql);
    
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return $row['response_template'];
    }
    
    // Smart default responses based on keywords
    if (preg_match('/\b(room|available|show|list)\b/', $query_escaped)) {
        return "Great question about our rooms! 🏨<br><br>We have excellent options available:<br><br>🏨 <strong>Eatonville Room</strong> - KES 3,500/night<br>• Comfortable accommodation<br>• Free WiFi and parking<br>• 24/7 room service<br><br>🏨 <strong>Merit Room</strong> - KES 4,000/night<br>• Premium space and amenities<br>• Free WiFi and parking<br>• 24/7 room service<br><br>Both rooms are perfect for your stay! Which one interests you more?";
    }
    
    if (preg_match('/\b(book|booking|reserve)\b/', $query_escaped)) {
        return "I'd love to help you with your booking! 📅<br><br>Here's our simple process:<br>1. <strong>Choose your preferred room</strong><br>2. <strong>Select your dates</strong><br>3. <strong>Provide contact details</strong><br>4. <strong>Confirm</strong> (no deposit needed!)<br><br>💡 <strong>Payment:</strong> Cash or M-Pesa on arrival<br><br>Which room would you like to book?";
    }
    
    if (preg_match('/\b(hi|hello|hey|good morning|good evening)\b/', $query_escaped)) {
        return "Hello! Welcome to Orlando International Resorts! 👋<br><br>I'm Maya, your intelligent AI assistant. I'm here to provide exceptional service and help with:<br><br>🏨 <strong>Room Information & Booking</strong><br>💰 <strong>Pricing & Special Offers</strong><br>📅 <strong>Availability & Reservations</strong><br>🌟 <strong>Hotel Services & Amenities</strong><br>📍 <strong>Local Recommendations</strong><br><br>What can I help you with today?";
    }
    
    if (preg_match('/\b(price|cost|rate|pricing)\b/', $query_escaped)) {
        return "Our room rates are very competitive! 💰<br><br>🏨 <strong>Eatonville Room:</strong> KES 3,500 per night<br>🏨 <strong>Merit Room:</strong> KES 4,000 per night<br><br>✅ <strong>Included in both rates:</strong><br>• Free high-speed WiFi<br>• Complimentary parking<br>• 24/7 room service<br>• No deposit required<br>• Pay on arrival<br><br>Which room fits your budget?";
    }
    
    // Default intelligent response
    return "I'm here to help with all your hotel needs.<br><br>I can provide intelligent assistance with:<br><br>🏨 <strong>Room Information</strong> - Detailed descriptions and availability<br>💰 <strong>Pricing Details</strong> - Rates, offers, and payment options<br>📅 <strong>Booking Assistance</strong> - Step-by-step reservation help<br>🌟 <strong>Hotel Services</strong> - Amenities and special services<br>📍 <strong>Local Recommendations</strong> - Attractions and dining<br><br>What specific information can I provide for you?";
}

// Record user feedback for learning
function recordUserFeedback() {
    global $con;
    
    try {
        $conversation_id = intval($_POST['conversation_id'] ?? 0);
        $user_query = mysqli_real_escape_string($con, $_POST['user_query'] ?? '');
        $maya_response = mysqli_real_escape_string($con, $_POST['maya_response'] ?? '');
        $response_type = mysqli_real_escape_string($con, $_POST['response_type'] ?? '');
        $user_satisfaction = mysqli_real_escape_string($con, $_POST['user_satisfaction'] ?? '');
        $follow_up = mysqli_real_escape_string($con, $_POST['follow_up_question'] ?? '');
        $improvement = mysqli_real_escape_string($con, $_POST['improvement_suggestion'] ?? '');
        
        $query = "INSERT INTO maya_conversation_feedback 
                  (conversation_id, user_query, maya_response, response_type, user_satisfaction, follow_up_question, improvement_suggestion) 
                  VALUES (?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 'issssss', $conversation_id, $user_query, $maya_response, $response_type, $user_satisfaction, $follow_up, $improvement);
        
        if (mysqli_stmt_execute($stmt)) {
            // Update learning patterns based on feedback
            if ($user_satisfaction === 'helpful') {
                updateLearningPatterns($con, $user_query, $maya_response, 'positive');
            } elseif ($user_satisfaction === 'unhelpful') {
                updateLearningPatterns($con, $user_query, $maya_response, 'negative');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Feedback recorded successfully',
                'feedback_id' => mysqli_insert_id($con)
            ]);
        } else {
            throw new Exception('Failed to record feedback');
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Unable to record feedback: ' . $e->getMessage()
        ]);
    }
}

// Update learning patterns based on feedback
function updateLearningPatterns($con, $user_query, $maya_response, $feedback_type) {
    try {
        $pattern_data = json_encode([
            'query' => $user_query,
            'response' => $maya_response,
            'feedback' => $feedback_type,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        if ($feedback_type === 'positive') {
            // Increase confidence for successful patterns
            $query = "INSERT INTO maya_learned_patterns (pattern_type, pattern_data, confidence_score, usage_count, success_rate) 
                      VALUES ('successful_response', ?, 0.8, 1, 1.0)
                      ON DUPLICATE KEY UPDATE 
                      usage_count = usage_count + 1,
                      confidence_score = LEAST(confidence_score + 0.1, 1.0),
                      success_rate = (success_rate * usage_count + 1.0) / (usage_count + 1)";
        } else {
            // Decrease confidence for unsuccessful patterns
            $query = "INSERT INTO maya_learned_patterns (pattern_type, pattern_data, confidence_score, usage_count, success_rate) 
                      VALUES ('unsuccessful_response', ?, 0.3, 1, 0.0)
                      ON DUPLICATE KEY UPDATE 
                      usage_count = usage_count + 1,
                      confidence_score = GREATEST(confidence_score - 0.1, 0.1),
                      success_rate = (success_rate * usage_count + 0.0) / (usage_count + 1)";
        }
        
        $stmt = mysqli_prepare($con, $query);
        mysqli_stmt_bind_param($stmt, 's', $pattern_data);
        mysqli_stmt_execute($stmt);
        
    } catch (Exception $e) {
        error_log("Error updating learning patterns: " . $e->getMessage());
    }
}
?>
