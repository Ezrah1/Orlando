<?php
/**
 * Maya AI Analytics API
 * Provides real-time analytics data for the training dashboard
 */
session_start();
require_once '../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    // Get current analytics
    $analytics = [
        'total_conversations' => 0,
        'today_conversations' => 0,
        'this_week_conversations' => 0,
        'unique_sessions' => 0,
        'knowledge_entries' => 0,
        'avg_response_length' => 0,
        'most_active_hour' => 0,
        'popular_intents' => [],
        'response_times' => [],
        'user_satisfaction' => 0
    ];
    
    // Total conversations
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_conversations");
    $analytics['total_conversations'] = mysqli_fetch_assoc($result)['count'];
    
    // Today's conversations
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_conversations WHERE DATE(created_at) = CURDATE()");
    $analytics['today_conversations'] = mysqli_fetch_assoc($result)['count'];
    
    // This week's conversations
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_conversations WHERE WEEK(created_at) = WEEK(NOW())");
    $analytics['this_week_conversations'] = mysqli_fetch_assoc($result)['count'];
    
    // Unique sessions
    $result = mysqli_query($con, "SELECT COUNT(DISTINCT session_id) as count FROM ai_conversations");
    $analytics['unique_sessions'] = mysqli_fetch_assoc($result)['count'];
    
    // Knowledge entries
    $result = mysqli_query($con, "SELECT COUNT(*) as count FROM ai_knowledge_base WHERE is_active = 1");
    $analytics['knowledge_entries'] = mysqli_fetch_assoc($result)['count'];
    
    // Average response length
    $result = mysqli_query($con, "SELECT AVG(CHAR_LENGTH(ai_response)) as avg_length FROM ai_conversations");
    $analytics['avg_response_length'] = round(mysqli_fetch_assoc($result)['avg_length'] ?? 0);
    
    // Most active hour
    $result = mysqli_query($con, "
        SELECT HOUR(created_at) as hour, COUNT(*) as count 
        FROM ai_conversations 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY HOUR(created_at) 
        ORDER BY count DESC 
        LIMIT 1
    ");
    $row = mysqli_fetch_assoc($result);
    $analytics['most_active_hour'] = $row ? $row['hour'] : 0;
    
    // Popular categories from knowledge base
    $result = mysqli_query($con, "
        SELECT category, COUNT(*) as count 
        FROM ai_knowledge_base 
        WHERE is_active = 1 
        GROUP BY category 
        ORDER BY count DESC 
        LIMIT 5
    ");
    while ($row = mysqli_fetch_assoc($result)) {
        $analytics['popular_intents'][] = [
            'intent' => $row['category'],
            'count' => $row['count']
        ];
    }
    
    // Conversation trends (last 7 days)
    $result = mysqli_query($con, "
        SELECT DATE(created_at) as date, COUNT(*) as count 
        FROM ai_conversations 
        WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY DATE(created_at) 
        ORDER BY date
    ");
    $trends = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $trends[] = [
            'date' => $row['date'],
            'conversations' => $row['count']
        ];
    }
    $analytics['conversation_trends'] = $trends;
    
    // Hourly distribution
    $result = mysqli_query($con, "
        SELECT HOUR(created_at) as hour, COUNT(*) as count 
        FROM ai_conversations 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        GROUP BY HOUR(created_at) 
        ORDER BY hour
    ");
    $hourly = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $hourly[] = [
            'hour' => $row['hour'],
            'conversations' => $row['count']
        ];
    }
    $analytics['hourly_distribution'] = $hourly;
    
    // Recent activity (last 10 conversations)
    $result = mysqli_query($con, "
        SELECT user_message, ai_response, created_at, session_id
        FROM ai_conversations 
        ORDER BY created_at DESC 
        LIMIT 10
    ");
    $recent_activity = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $recent_activity[] = [
            'user_message' => substr($row['user_message'], 0, 50) . '...',
            'ai_response' => substr(strip_tags($row['ai_response']), 0, 50) . '...',
            'time' => date('H:i', strtotime($row['created_at'])),
            'session' => substr($row['session_id'], -8)
        ];
    }
    $analytics['recent_activity'] = $recent_activity;
    
    // Learning opportunities (conversations that might need training)
    $result = mysqli_query($con, "
        SELECT id, user_message, ai_response, created_at
        FROM ai_conversations 
        WHERE CHAR_LENGTH(ai_response) < 50 
           OR ai_response LIKE '%I don\'t understand%'
           OR ai_response LIKE '%I\'m not sure%'
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $learning_opportunities = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $learning_opportunities[] = [
            'id' => $row['id'],
            'user_message' => $row['user_message'],
            'ai_response' => $row['ai_response'],
            'created_at' => $row['created_at']
        ];
    }
    $analytics['learning_opportunities'] = $learning_opportunities;
    
    // Performance metrics
    $analytics['performance'] = [
        'response_coverage' => round(($analytics['knowledge_entries'] / max($analytics['total_conversations'], 1)) * 100, 1),
        'session_engagement' => round($analytics['total_conversations'] / max($analytics['unique_sessions'], 1), 1),
        'daily_growth' => $analytics['today_conversations'],
        'knowledge_utilization' => round(($analytics['total_conversations'] / max($analytics['knowledge_entries'], 1)) * 100, 1)
    ];
    
    echo json_encode($analytics);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Internal server error: ' . $e->getMessage()]);
}
?>
