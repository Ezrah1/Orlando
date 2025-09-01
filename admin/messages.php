<?php
session_start();
include __DIR__ . '/db.php';
include __DIR__ . '/auth.php';
ensure_logged_in();

// Handle message actions
$action = $_GET['action'] ?? 'inbox';
$message_id = $_GET['id'] ?? null;
$success_message = '';
$error_message = '';

// Get current user info
$user_id = $_SESSION['user_id'];
$user_result = $con->query("SELECT * FROM users WHERE id = $user_id");
$current_user = $user_result->fetch_assoc();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_message'])) {
        $recipient_id = intval($_POST['recipient_id']);
        $subject = mysqli_real_escape_string($con, $_POST['subject']);
        $message = mysqli_real_escape_string($con, $_POST['message']);
        $priority = $_POST['priority'] ?? 'normal';
        
        $query = "INSERT INTO messages (sender_id, recipient_id, subject, message, priority, sent_at) 
                  VALUES ($user_id, $recipient_id, '$subject', '$message', '$priority', NOW())";
        
        if ($con->query($query)) {
            $success_message = "Message sent successfully!";
        } else {
            $error_message = "Failed to send message.";
        }
    }
    
    if (isset($_POST['mark_read'])) {
        $msg_id = intval($_POST['message_id']);
        $con->query("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = $msg_id AND recipient_id = $user_id");
        $success_message = "Message marked as read.";
    }
    
    if (isset($_POST['delete_message'])) {
        $msg_id = intval($_POST['message_id']);
        $con->query("UPDATE messages SET deleted_by_recipient = 1 WHERE id = $msg_id AND recipient_id = $user_id");
        $con->query("UPDATE messages SET deleted_by_sender = 1 WHERE id = $msg_id AND sender_id = $user_id");
        $success_message = "Message deleted.";
    }
}

// Create messages table if it doesn't exist
$create_table = "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    recipient_id INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    deleted_by_sender BOOLEAN DEFAULT FALSE,
    deleted_by_recipient BOOLEAN DEFAULT FALSE,
    INDEX idx_recipient (recipient_id),
    INDEX idx_sender (sender_id),
    INDEX idx_sent_at (sent_at)
)";
$con->query($create_table);

$page_title = 'Messages Center';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
?>

<style>
.messages-container {
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow: hidden;
}

.messages-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    text-align: center;
}

.messages-nav {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    padding: 0;
}

.nav-tabs {
    border-bottom: none;
}

.nav-tabs .nav-link {
    color: #495057;
    font-weight: 500;
    padding: 15px 20px;
    border: none;
    border-radius: 0;
    transition: all 0.3s ease;
}

.nav-tabs .nav-link:hover {
    background: #e9ecef;
    color: #007bff;
}

.nav-tabs .nav-link.active {
    background: #007bff;
    color: white;
    border: none;
}

.message-item {
    border-bottom: 1px solid #f0f0f0;
    padding: 15px;
    transition: background 0.2s ease;
    cursor: pointer;
}

.message-item:hover {
    background: #f8f9fa;
}

.message-item.unread {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}

.message-item.urgent {
    border-left: 4px solid #dc3545;
}

.message-item.high {
    border-left: 4px solid #fd7e14;
}

.message-subject {
    font-weight: bold;
    color: #333;
    margin-bottom: 5px;
}

.message-preview {
    color: #666;
    font-size: 14px;
    margin-bottom: 5px;
}

.message-meta {
    font-size: 12px;
    color: #999;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.priority-badge {
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: bold;
}

.priority-urgent {
    background: #dc3545;
    color: white;
}

.priority-high {
    background: #fd7e14;
    color: white;
}

.priority-normal {
    background: #6c757d;
    color: white;
}

.priority-low {
    background: #28a745;
    color: white;
}

.compose-form {
    padding: 20px;
}

.message-stats {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.stat-item {
    text-align: center;
    padding: 10px;
}

.stat-number {
    font-size: 24px;
    font-weight: bold;
    color: #007bff;
}

.stat-label {
    font-size: 12px;
    color: #666;
    text-transform: uppercase;
}

.message-detail {
    padding: 20px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    background: white;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state i {
    font-size: 48px;
    margin-bottom: 15px;
    color: #ccc;
}
</style>

<!-- Messages Center -->
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="messages-container">
                <!-- Header -->
                <div class="messages-header">
                    <h2><i class="fas fa-envelope"></i> Messages Center</h2>
                    <p>Manage your internal communications</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show m-3">
                        <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show m-3">
                        <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
                        <button type="button" class="close" data-dismiss="alert">
                            <span>&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <!-- Navigation Tabs -->
                <div class="messages-nav">
                    <ul class="nav nav-tabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'inbox' ? 'active' : ''; ?>" 
                               href="?action=inbox">
                                <i class="fas fa-inbox"></i> Inbox
                                <?php
                                $unread_count = $con->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = $user_id AND is_read = 0 AND deleted_by_recipient = 0")->fetch_assoc()['count'];
                                if ($unread_count > 0) echo "<span class='badge badge-danger ml-1'>$unread_count</span>";
                                ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'sent' ? 'active' : ''; ?>" 
                               href="?action=sent">
                                <i class="fas fa-paper-plane"></i> Sent
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'compose' ? 'active' : ''; ?>" 
                               href="?action=compose">
                                <i class="fas fa-edit"></i> Compose
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $action === 'archive' ? 'active' : ''; ?>" 
                               href="?action=archive">
                                <i class="fas fa-archive"></i> Archive
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Content Area -->
                <div class="tab-content">
                    <?php if ($action === 'inbox'): ?>
                        <!-- INBOX -->
                        <div class="tab-pane fade show active">
                            <?php
                            $inbox_query = "SELECT m.*, 
                                          u.username, u.email,
                                          r.name as role_name
                                          FROM messages m 
                                          JOIN users u ON m.sender_id = u.id 
                                          LEFT JOIN roles r ON u.role_id = r.id
                                          WHERE m.recipient_id = $user_id 
                                          AND m.deleted_by_recipient = 0 
                                          ORDER BY m.sent_at DESC";
                            $inbox_result = $con->query($inbox_query);
                            ?>

                            <!-- Message Statistics -->
                            <div class="message-stats m-3">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $inbox_result->num_rows; ?></div>
                                            <div class="stat-label">Total Messages</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <div class="stat-number"><?php echo $unread_count; ?></div>
                                            <div class="stat-label">Unread</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <?php $urgent_count = $con->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = $user_id AND priority = 'urgent' AND deleted_by_recipient = 0")->fetch_assoc()['count']; ?>
                                            <div class="stat-number"><?php echo $urgent_count; ?></div>
                                            <div class="stat-label">Urgent</div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="stat-item">
                                            <?php $today_count = $con->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = $user_id AND DATE(sent_at) = CURDATE() AND deleted_by_recipient = 0")->fetch_assoc()['count']; ?>
                                            <div class="stat-number"><?php echo $today_count; ?></div>
                                            <div class="stat-label">Today</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Messages List -->
                            <?php if ($inbox_result->num_rows > 0): ?>
                                <?php while ($message = $inbox_result->fetch_assoc()): ?>
                                    <div class="message-item <?php echo !$message['is_read'] ? 'unread' : ''; ?> <?php echo $message['priority']; ?>" 
                                         onclick="viewMessage(<?php echo $message['id']; ?>)">
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                            <span class="priority-badge priority-<?php echo $message['priority']; ?>">
                                                <?php echo strtoupper($message['priority']); ?>
                                            </span>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . '...'; ?>
                                        </div>
                                        <div class="message-meta">
                                            <span>
                                                <i class="fas fa-user"></i>
                                                <?php echo htmlspecialchars($message['username']); ?>
                                                (<?php echo htmlspecialchars($message['role_name'] ?? 'User'); ?>)
                                            </span>
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($message['sent_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <h4>No Messages</h4>
                                    <p>Your inbox is empty. Check back later for new messages.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($action === 'sent'): ?>
                        <!-- SENT MESSAGES -->
                        <div class="tab-pane fade show active">
                            <?php
                            $sent_query = "SELECT m.*, 
                                         u.username, u.email,
                                         r.name as role_name
                                         FROM messages m 
                                         JOIN users u ON m.recipient_id = u.id 
                                         LEFT JOIN roles r ON u.role_id = r.id
                                         WHERE m.sender_id = $user_id 
                                         AND m.deleted_by_sender = 0 
                                         ORDER BY m.sent_at DESC";
                            $sent_result = $con->query($sent_query);
                            ?>

                            <div class="p-3">
                                <h4><i class="fas fa-paper-plane"></i> Sent Messages (<?php echo $sent_result->num_rows; ?>)</h4>
                            </div>

                            <?php if ($sent_result->num_rows > 0): ?>
                                <?php while ($message = $sent_result->fetch_assoc()): ?>
                                    <div class="message-item">
                                        <div class="message-subject">
                                            <?php echo htmlspecialchars($message['subject']); ?>
                                            <?php if ($message['is_read']): ?>
                                                <span class="badge badge-success ml-2">Read</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary ml-2">Unread</span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="message-preview">
                                            <?php echo htmlspecialchars(substr($message['message'], 0, 100)) . '...'; ?>
                                        </div>
                                        <div class="message-meta">
                                            <span>
                                                <i class="fas fa-user"></i>
                                                To: <?php echo htmlspecialchars($message['username']); ?>
                                            </span>
                                            <span>
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('M j, Y g:i A', strtotime($message['sent_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-paper-plane"></i>
                                    <h4>No Sent Messages</h4>
                                    <p>You haven't sent any messages yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                    <?php elseif ($action === 'compose'): ?>
                        <!-- COMPOSE MESSAGE -->
                        <div class="tab-pane fade show active">
                            <div class="compose-form">
                                <h4><i class="fas fa-edit"></i> Compose New Message</h4>
                                
                                <form method="POST" action="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="recipient_id">Recipient:</label>
                                                <select name="recipient_id" id="recipient_id" class="form-control" required>
                                                    <option value="">Select Recipient</option>
                                                    <?php
                                                    $users_query = "SELECT u.id, u.username, u.email, r.name as role_name 
                                                                   FROM users u 
                                                                   LEFT JOIN roles r ON u.role_id = r.id 
                                                                   WHERE u.id != $user_id 
                                                                   ORDER BY u.username";
                                                    $users_result = $con->query($users_query);
                                                    while ($user = $users_result->fetch_assoc()): ?>
                                                        <option value="<?php echo $user['id']; ?>">
                                                            <?php echo htmlspecialchars($user['username']); ?>
                                                            (<?php echo htmlspecialchars($user['role_name'] ?? 'User'); ?>)
                                                        </option>
                                                    <?php endwhile; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="priority">Priority:</label>
                                                <select name="priority" id="priority" class="form-control">
                                                    <option value="low">Low</option>
                                                    <option value="normal" selected>Normal</option>
                                                    <option value="high">High</option>
                                                    <option value="urgent">Urgent</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="subject">Subject:</label>
                                        <input type="text" name="subject" id="subject" class="form-control" 
                                               placeholder="Enter message subject" required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="message">Message:</label>
                                        <textarea name="message" id="message" class="form-control" rows="8" 
                                                  placeholder="Type your message here..." required></textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" name="send_message" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Message
                                        </button>
                                        <button type="reset" class="btn btn-secondary ml-2">
                                            <i class="fas fa-undo"></i> Clear
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    <?php elseif ($action === 'view' && $message_id): ?>
                        <!-- VIEW MESSAGE -->
                        <?php
                        $view_query = "SELECT m.*, 
                                     u.username, u.email,
                                     r.name as role_name
                                     FROM messages m 
                                     JOIN users u ON m.sender_id = u.id 
                                     LEFT JOIN roles r ON u.role_id = r.id
                                     WHERE m.id = $message_id 
                                     AND (m.recipient_id = $user_id OR m.sender_id = $user_id)";
                        $view_result = $con->query($view_query);
                        
                        if ($view_result->num_rows > 0):
                            $message = $view_result->fetch_assoc();
                            
                            // Mark as read if current user is recipient
                            if ($message['recipient_id'] == $user_id && !$message['is_read']) {
                                $con->query("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = $message_id");
                            }
                        ?>
                            <div class="message-detail m-3">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h4><?php echo htmlspecialchars($message['subject']); ?></h4>
                                        <span class="priority-badge priority-<?php echo $message['priority']; ?>">
                                            <?php echo strtoupper($message['priority']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <a href="?action=inbox" class="btn btn-secondary btn-sm">
                                            <i class="fas fa-arrow-left"></i> Back to Inbox
                                        </a>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <strong>From:</strong> <?php echo htmlspecialchars($message['username']); ?>
                                    (<?php echo htmlspecialchars($message['role_name'] ?? 'User'); ?>)<br>
                                    <strong>Sent:</strong> <?php echo date('l, F j, Y \a\t g:i A', strtotime($message['sent_at'])); ?>
                                    <?php if ($message['read_at']): ?>
                                        <br><strong>Read:</strong> <?php echo date('l, F j, Y \a\t g:i A', strtotime($message['read_at'])); ?>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="message-content" style="border-top: 1px solid #dee2e6; padding-top: 15px;">
                                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="mt-3">
                                    <?php if ($message['recipient_id'] == $user_id): ?>
                                        <a href="?action=compose&reply=<?php echo $message['id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="fas fa-reply"></i> Reply
                                        </a>
                                        
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="delete_message" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Are you sure you want to delete this message?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-danger m-3">Message not found or access denied.</div>
                        <?php endif; ?>

                    <?php else: ?>
                        <!-- DEFAULT VIEW -->
                        <div class="empty-state">
                            <i class="fas fa-envelope-open"></i>
                            <h4>Welcome to Messages</h4>
                            <p>Select a tab above to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function viewMessage(id) {
    window.location.href = '?action=view&id=' + id;
}

// Auto-refresh inbox every 30 seconds
<?php if ($action === 'inbox'): ?>
setTimeout(function() {
    if (window.location.href.indexOf('action=inbox') !== -1 || window.location.href.indexOf('action=') === -1) {
        window.location.reload();
    }
}, 30000);
<?php endif; ?>

// Character counter for message composition
document.addEventListener('DOMContentLoaded', function() {
    const messageTextarea = document.getElementById('message');
    if (messageTextarea) {
        const maxLength = 1000;
        const counter = document.createElement('small');
        counter.className = 'text-muted';
        counter.style.float = 'right';
        messageTextarea.parentNode.appendChild(counter);
        
        function updateCounter() {
            const remaining = maxLength - messageTextarea.value.length;
            counter.textContent = remaining + ' characters remaining';
            if (remaining < 50) {
                counter.className = 'text-warning';
            } else if (remaining < 0) {
                counter.className = 'text-danger';
            } else {
                counter.className = 'text-muted';
            }
        }
        
        messageTextarea.addEventListener('input', updateCounter);
        updateCounter();
    }
});
</script>

<?php include '../includes/admin/footer.php'; ?>