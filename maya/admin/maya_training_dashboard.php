<?php
/**
 * Maya AI Training Dashboard
 * Admin interface for training and improving Maya AI
 */
session_start();
require_once '../db.php';

// Check admin authentication
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$page_title = 'Maya AI Training Dashboard';
include '../includes/admin/header.php';

// Handle training actions
if ($_POST['action'] ?? false) {
    switch ($_POST['action']) {
        case 'add_knowledge':
            $category = mysqli_real_escape_string($con, $_POST['category']);
            $keywords = mysqli_real_escape_string($con, $_POST['keywords']);
            $response = mysqli_real_escape_string($con, $_POST['response']);
            $priority = intval($_POST['priority']);
            
            $sql = "INSERT INTO ai_knowledge_base (category, question_keywords, response_template, priority) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "sssi", $category, $keywords, $response, $priority);
            
            if (mysqli_stmt_execute($stmt)) {
                echo "<div class='alert alert-success'>✅ Knowledge entry added successfully!</div>";
            } else {
                echo "<div class='alert alert-danger'>❌ Error adding knowledge entry</div>";
            }
            break;
            
        case 'train_response':
            $messageId = intval($_POST['message_id']);
            $improvedResponse = mysqli_real_escape_string($con, $_POST['improved_response']);
            $category = mysqli_real_escape_string($con, $_POST['category']);
            
            // Update the conversation with improved response
            $sql = "UPDATE ai_conversations SET ai_response = CONCAT(ai_response, '\n\n[IMPROVED]: ', ?) WHERE id = ?";
            $stmt = mysqli_prepare($con, $sql);
            mysqli_stmt_bind_param($stmt, "si", $improvedResponse, $messageId);
            mysqli_stmt_execute($stmt);
            
            // Add to knowledge base if new pattern
            if (!empty($category)) {
                $userMessage = mysqli_fetch_assoc(mysqli_query($con, "SELECT user_message FROM ai_conversations WHERE id = $messageId"))['user_message'];
                $keywords = strtolower(str_replace([',', '.', '!', '?'], '', $userMessage));
                
                $sql = "INSERT INTO ai_knowledge_base (category, question_keywords, response_template, priority) VALUES (?, ?, ?, 75)";
                $stmt = mysqli_prepare($con, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $category, $keywords, $improvedResponse);
                mysqli_stmt_execute($stmt);
            }
            
            echo "<div class='alert alert-success'>✅ Response training completed!</div>";
            break;
    }
}

// Get conversation analytics
$analytics = [
    'total_conversations' => mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM ai_conversations"))['count'],
    'today_conversations' => mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM ai_conversations WHERE DATE(created_at) = CURDATE()"))['count'],
    'unique_sessions' => mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(DISTINCT session_id) as count FROM ai_conversations"))['count'],
    'knowledge_entries' => mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM ai_knowledge_base"))['count']
];

// Get recent conversations for training
$recentConversations = mysqli_query($con, "
    SELECT id, session_id, user_message, ai_response, created_at 
    FROM ai_conversations 
    ORDER BY created_at DESC 
    LIMIT 20
");

// Get knowledge base categories
$categories = mysqli_query($con, "SELECT DISTINCT category FROM ai_knowledge_base ORDER BY category");

// Get popular keywords
$popularKeywords = mysqli_query($con, "
    SELECT 
        SUBSTRING_INDEX(SUBSTRING_INDEX(question_keywords, ',', numbers.n), ',', -1) as keyword,
        COUNT(*) as frequency
    FROM ai_knowledge_base
    CROSS JOIN (
        SELECT 1 n UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 UNION ALL SELECT 5
    ) numbers
    WHERE CHAR_LENGTH(question_keywords) - CHAR_LENGTH(REPLACE(question_keywords, ',', '')) >= numbers.n - 1
    GROUP BY keyword
    ORDER BY frequency DESC
    LIMIT 10
");
?>

<style>
.training-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
}

.training-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    box-shadow: 0 5px 15px rgba(0,0,0,0.08);
    border-left: 4px solid #0f2453;
}

.analytics-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.analytics-card {
    background: linear-gradient(135deg, #0f2453, #1a3567);
    color: white;
    padding: 25px;
    border-radius: 15px;
    text-align: center;
    box-shadow: 0 8px 25px rgba(15, 36, 83, 0.3);
}

.analytics-number {
    font-size: 2.5rem;
    font-weight: bold;
    margin-bottom: 10px;
    color: #ffce14;
}

.analytics-label {
    font-size: 1rem;
    opacity: 0.9;
}

.conversation-item {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 15px;
    transition: all 0.3s ease;
}

.conversation-item:hover {
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.user-message {
    background: #e3f2fd;
    color: #1976d2;
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 10px;
    font-weight: 500;
}

.ai-response {
    background: #f1f8e9;
    color: #388e3c;
    padding: 10px 15px;
    border-radius: 18px;
    margin-bottom: 10px;
}

.training-form {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 10px;
    padding: 15px;
    margin-top: 10px;
}

.btn-train {
    background: linear-gradient(135deg, #ffce14, #ffd700);
    color: #0f2453;
    border: none;
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-train:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 206, 20, 0.4);
}

.knowledge-form {
    background: #e8f5e8;
    border: 1px solid #c8e6c9;
    border-radius: 10px;
    padding: 20px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 10px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #0f2453;
    box-shadow: 0 0 0 0.2rem rgba(15, 36, 83, 0.25);
}

.keyword-tag {
    background: #0f2453;
    color: white;
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.8rem;
    margin: 2px;
    display: inline-block;
}

.frequency-bar {
    background: #ffce14;
    height: 20px;
    border-radius: 10px;
    margin: 5px 0;
    position: relative;
}

.frequency-text {
    position: absolute;
    left: 10px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.8rem;
    font-weight: 600;
    color: #0f2453;
}
</style>

<div class="training-dashboard">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <h2 style="color: #0f2453; margin-bottom: 30px;">
                    🤖 Maya AI Training Dashboard
                    <small style="color: #6c757d; font-size: 0.6em;">Improve Maya's intelligence through training</small>
                </h2>
                
                <!-- Analytics Overview -->
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="analytics-number"><?php echo $analytics['total_conversations']; ?></div>
                        <div class="analytics-label">Total Conversations</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-number"><?php echo $analytics['today_conversations']; ?></div>
                        <div class="analytics-label">Today's Conversations</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-number"><?php echo $analytics['unique_sessions']; ?></div>
                        <div class="analytics-label">Unique Sessions</div>
                    </div>
                    <div class="analytics-card">
                        <div class="analytics-number"><?php echo $analytics['knowledge_entries']; ?></div>
                        <div class="analytics-label">Knowledge Entries</div>
                    </div>
                </div>
                
                <div class="row">
                    <!-- Add New Knowledge -->
                    <div class="col-md-6">
                        <div class="training-card">
                            <h4 style="color: #0f2453; margin-bottom: 20px;">
                                📚 Add New Knowledge
                            </h4>
                            
                            <form method="post" class="knowledge-form">
                                <input type="hidden" name="action" value="add_knowledge">
                                
                                <div class="form-group mb-3">
                                    <label for="category">Category:</label>
                                    <select name="category" class="form-control" required>
                                        <option value="">Select category...</option>
                                        <?php while ($cat = mysqli_fetch_assoc($categories)): ?>
                                            <option value="<?php echo $cat['category']; ?>"><?php echo ucwords($cat['category']); ?></option>
                                        <?php endwhile; ?>
                                        <option value="new_category">+ Add New Category</option>
                                    </select>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="keywords">Keywords (comma-separated):</label>
                                    <input type="text" name="keywords" class="form-control" 
                                           placeholder="room, booking, price, availability" required>
                                    <small class="text-muted">Words that should trigger this response</small>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="response">Response Template:</label>
                                    <textarea name="response" class="form-control" rows="4" 
                                              placeholder="Maya's response when these keywords are detected..." required></textarea>
                                </div>
                                
                                <div class="form-group mb-3">
                                    <label for="priority">Priority (1-100):</label>
                                    <input type="number" name="priority" class="form-control" 
                                           value="50" min="1" max="100" required>
                                    <small class="text-muted">Higher priority responses are preferred</small>
                                </div>
                                
                                <button type="submit" class="btn btn-train btn-block">
                                    🧠 Add Knowledge
                                </button>
                            </form>
                        </div>
                        
                        <!-- Popular Keywords -->
                        <div class="training-card">
                            <h4 style="color: #0f2453; margin-bottom: 20px;">
                                🔥 Popular Keywords
                            </h4>
                            
                            <?php 
                            $maxFreq = 0;
                            $keywords = [];
                            mysqli_data_seek($popularKeywords, 0);
                            while ($kw = mysqli_fetch_assoc($popularKeywords)) {
                                $keywords[] = $kw;
                                $maxFreq = max($maxFreq, $kw['frequency']);
                            }
                            
                            foreach ($keywords as $kw): 
                                $width = ($kw['frequency'] / $maxFreq) * 100;
                            ?>
                                <div style="margin-bottom: 10px;">
                                    <span class="keyword-tag"><?php echo trim($kw['keyword']); ?></span>
                                    <div class="frequency-bar" style="width: <?php echo $width; ?>%;">
                                        <div class="frequency-text"><?php echo $kw['frequency']; ?> uses</div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Recent Conversations for Training -->
                    <div class="col-md-6">
                        <div class="training-card">
                            <h4 style="color: #0f2453; margin-bottom: 20px;">
                                💬 Recent Conversations - Training Opportunities
                            </h4>
                            
                            <div style="max-height: 600px; overflow-y: auto;">
                                <?php while ($conv = mysqli_fetch_assoc($recentConversations)): ?>
                                    <div class="conversation-item">
                                        <div class="user-message">
                                            👤 User: <?php echo htmlspecialchars($conv['user_message']); ?>
                                        </div>
                                        <div class="ai-response">
                                            🤖 Maya: <?php echo htmlspecialchars(substr($conv['ai_response'], 0, 200)) . (strlen($conv['ai_response']) > 200 ? '...' : ''); ?>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo date('M j, Y g:i A', strtotime($conv['created_at'])); ?>
                                            | Session: <?php echo substr($conv['session_id'], -8); ?>
                                        </small>
                                        
                                        <button class="btn btn-sm btn-train float-right" 
                                                onclick="toggleTraining(<?php echo $conv['id']; ?>)">
                                            🎓 Train This Response
                                        </button>
                                        
                                        <div id="training-<?php echo $conv['id']; ?>" class="training-form" style="display: none;">
                                            <form method="post">
                                                <input type="hidden" name="action" value="train_response">
                                                <input type="hidden" name="message_id" value="<?php echo $conv['id']; ?>">
                                                
                                                <div class="form-group mb-2">
                                                    <label>Improved Response:</label>
                                                    <textarea name="improved_response" class="form-control" rows="3" 
                                                              placeholder="Enter a better response for Maya..."></textarea>
                                                </div>
                                                
                                                <div class="form-group mb-2">
                                                    <label>Category (optional):</label>
                                                    <input type="text" name="category" class="form-control" 
                                                           placeholder="e.g., pricing, booking, amenities">
                                                </div>
                                                
                                                <button type="submit" class="btn btn-sm btn-train">
                                                    💾 Save Training
                                                </button>
                                                <button type="button" class="btn btn-sm btn-secondary ml-2" 
                                                        onclick="toggleTraining(<?php echo $conv['id']; ?>)">
                                                    Cancel
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleTraining(conversationId) {
    const trainingDiv = document.getElementById('training-' + conversationId);
    if (trainingDiv.style.display === 'none') {
        trainingDiv.style.display = 'block';
        trainingDiv.scrollIntoView({ behavior: 'smooth' });
    } else {
        trainingDiv.style.display = 'none';
    }
}

// Auto-refresh analytics every 30 seconds
setInterval(function() {
    fetch('maya_analytics_api.php')
        .then(response => response.json())
        .then(data => {
            document.querySelector('.analytics-card:nth-child(2) .analytics-number').textContent = data.today_conversations;
            document.querySelector('.analytics-card:nth-child(1) .analytics-number').textContent = data.total_conversations;
        })
        .catch(error => console.log('Analytics update failed:', error));
}, 30000);
</script>

<?php include 'includes/footer.php'; ?>
