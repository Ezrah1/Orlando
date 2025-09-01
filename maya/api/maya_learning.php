<?php
/**
 * Maya AI Learning API
 * Processes conversations and learns from user interactions
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';
require_once '../components/maya_learning_system.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'learned' => false, 'insights' => []];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Initialize learning system
        $learningSystem = new MayaLearningSystem($con);
        
        // Extract conversation data
        $userMessage = $input['user_message'] ?? '';
        $aiResponse = $input['ai_response'] ?? '';
        $sessionId = $input['session_id'] ?? session_id();
        $intent = $input['intent'] ?? 'unknown';
        $sentiment = $input['sentiment'] ?? 'neutral';
        $entities = $input['entities'] ?? '{}';
        $complexity = floatval($input['complexity'] ?? 0);
        
        // Prepare context
        $context = [
            'intent' => $intent,
            'sentiment' => $sentiment,
            'entities' => json_decode($entities, true),
            'complexity' => $complexity,
            'page' => $_SERVER['HTTP_REFERER'] ?? 'unknown',
            'timestamp' => time()
        ];
        
        // Learn from this interaction
        $learningSystem->learnFromInteraction($userMessage, $aiResponse, $sessionId, $context);
        
        // Log the conversation to database
        $stmt = mysqli_prepare($con, "
            INSERT INTO ai_conversations (session_id, user_message, ai_response, agent_id, created_at) 
            VALUES (?, ?, ?, 1, NOW())
        ");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $sessionId, $userMessage, $aiResponse);
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['learned'] = true;
                
                // Generate insights about what Maya learned
                $insights = [];
                
                // Analyze what was learned
                if (strlen($userMessage) > 5) {
                    if (strpos(strtolower($userMessage), 'price') !== false || 
                        strpos(strtolower($userMessage), 'cost') !== false) {
                        $insights[] = 'Detected pricing interest - Maya improved pricing responses';
                    }
                    
                    if (strpos(strtolower($userMessage), 'book') !== false || 
                        strpos(strtolower($userMessage), 'reserve') !== false) {
                        $insights[] = 'Detected booking intent - Maya enhanced booking assistance';
                    }
                    
                    if (strpos(strtolower($userMessage), 'room') !== false) {
                        $insights[] = 'Room-related query - Maya updated room knowledge';
                    }
                    
                    // Sentiment-based insights
                    if ($sentiment === 'positive') {
                        $insights[] = 'Positive interaction - Maya reinforced successful response patterns';
                    } elseif ($sentiment === 'negative') {
                        $insights[] = 'Negative sentiment detected - Maya flagged for response improvement';
                    }
                    
                    // Complexity insights
                    if ($complexity > 0.7) {
                        $insights[] = 'Complex query processed - Maya expanded advanced response capabilities';
                    }
                    
                    if (empty($insights)) {
                        $insights[] = 'General conversation pattern learned and stored';
                    }
                }
                
                $response['insights'] = $insights;
                
                // Get learning statistics
                $stats = $learningSystem->getLearningStats();
                $response['learning_stats'] = $stats;
                
                // Check for auto-improvement opportunities
                $improvements = $learningSystem->autoImprove();
                if (!empty($improvements)) {
                    $response['improvement_opportunities'] = count($improvements);
                }
                
            } else {
                $response['message'] = 'Failed to log conversation: ' . mysqli_error($con);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Failed to prepare statement: ' . mysqli_error($con);
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Learning system error: ' . $e->getMessage();
        error_log("Maya Learning Error: " . $e->getMessage());
    }
    
} else {
    $response['message'] = 'Only POST requests allowed';
}

echo json_encode($response);
?>
