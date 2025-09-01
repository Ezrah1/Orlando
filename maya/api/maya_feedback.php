<?php
/**
 * Maya AI Feedback API
 * Collects user feedback on Maya's responses for learning
 */

header('Content-Type: application/json; charset=utf-8');
require_once '../db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        $response['message'] = 'Invalid JSON input';
        echo json_encode($response);
        exit;
    }
    
    try {
        $message = $input['message'] ?? '';
        $rating = $input['rating'] ?? '';
        $sessionId = $input['session_id'] ?? session_id();
        $timestamp = $input['timestamp'] ?? date('Y-m-d H:i:s');
        
        if (empty($message) || empty($rating)) {
            $response['message'] = 'Message and rating required';
            echo json_encode($response);
            exit;
        }
        
        // Create feedback table if it doesn't exist
        $createTable = "CREATE TABLE IF NOT EXISTS `ai_feedback` (
            `id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `session_id` VARCHAR(255) NOT NULL,
            `message` TEXT NOT NULL,
            `rating` ENUM('positive', 'negative') NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `processed` TINYINT(1) DEFAULT 0,
            PRIMARY KEY (`id`),
            INDEX `idx_session_id` (`session_id`),
            INDEX `idx_rating` (`rating`),
            INDEX `idx_processed` (`processed`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
        
        mysqli_query($con, $createTable);
        
        // Insert feedback
        $stmt = mysqli_prepare($con, "
            INSERT INTO ai_feedback (session_id, message, rating, created_at) 
            VALUES (?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $sessionId, $message, $rating);
            
            if (mysqli_stmt_execute($stmt)) {
                $response['success'] = true;
                $response['message'] = 'Feedback recorded successfully';
                
                // If negative feedback, prioritize for improvement
                if ($rating === 'negative') {
                    // Find the original conversation and mark for review
                    $reviewStmt = mysqli_prepare($con, "
                        UPDATE ai_conversations 
                        SET ai_response = CONCAT(ai_response, '\n[NEEDS_REVIEW]') 
                        WHERE session_id = ? AND user_message LIKE ? 
                        ORDER BY created_at DESC LIMIT 1
                    ");
                    
                    $messagePattern = "%" . substr($message, 0, 50) . "%";
                    mysqli_stmt_bind_param($reviewStmt, "ss", $sessionId, $messagePattern);
                    mysqli_stmt_execute($reviewStmt);
                    mysqli_stmt_close($reviewStmt);
                    
                    // Add to knowledge base improvement queue
                    $improvementStmt = mysqli_prepare($con, "
                        INSERT INTO ai_knowledge_base (category, question_keywords, response_template, priority, is_active) 
                        VALUES ('improvement_needed', ?, '[NEEDS_BETTER_RESPONSE] Original: ' + ?, 25, 0)
                    ");
                    
                    $keywords = strtolower(str_replace([',', '.', '!', '?'], '', $message));
                    mysqli_stmt_bind_param($improvementStmt, "ss", $keywords, $message);
                    mysqli_stmt_execute($improvementStmt);
                    mysqli_stmt_close($improvementStmt);
                    
                    $response['learning_triggered'] = true;
                } else {
                    // Positive feedback - reinforce the response pattern
                    $reinforceStmt = mysqli_prepare($con, "
                        UPDATE ai_knowledge_base 
                        SET priority = LEAST(priority + 5, 100) 
                        WHERE question_keywords LIKE ? 
                        ORDER BY priority DESC LIMIT 1
                    ");
                    
                    $keywords = "%" . strtolower(str_replace([',', '.', '!', '?'], '', $message)) . "%";
                    mysqli_stmt_bind_param($reinforceStmt, "s", $keywords);
                    mysqli_stmt_execute($reinforceStmt);
                    mysqli_stmt_close($reinforceStmt);
                    
                    $response['pattern_reinforced'] = true;
                }
                
                // Get feedback statistics
                $stats = [];
                
                // Total feedback count
                $totalResult = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_feedback");
                $stats['total_feedback'] = mysqli_fetch_assoc($totalResult)['count'];
                
                // Positive vs negative ratio
                $positiveResult = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_feedback WHERE rating = 'positive'");
                $negativeResult = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_feedback WHERE rating = 'negative'");
                
                $positiveCount = mysqli_fetch_assoc($positiveResult)['count'];
                $negativeCount = mysqli_fetch_assoc($negativeResult)['count'];
                
                $stats['positive_feedback'] = $positiveCount;
                $stats['negative_feedback'] = $negativeCount;
                $stats['satisfaction_rate'] = $stats['total_feedback'] > 0 ? 
                    round(($positiveCount / $stats['total_feedback']) * 100, 1) : 0;
                
                $response['feedback_stats'] = $stats;
                
            } else {
                $response['message'] = 'Failed to record feedback: ' . mysqli_error($con);
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $response['message'] = 'Failed to prepare statement: ' . mysqli_error($con);
        }
        
    } catch (Exception $e) {
        $response['message'] = 'Feedback system error: ' . $e->getMessage();
        error_log("Maya Feedback Error: " . $e->getMessage());
    }
    
} else {
    $response['message'] = 'Only POST requests allowed';
}

echo json_encode($response);
?>
