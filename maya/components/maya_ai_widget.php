<?php
/**
 * Maya AI Assistant Widget
 * Reusable AI chat component for all pages
 * Orlando International Resorts
 */

// Prevent multiple inclusions
if (defined('MAYA_AI_WIDGET_LOADED')) {
    return;
}
define('MAYA_AI_WIDGET_LOADED', true);

// Include database connection if not already included
if (!isset($con)) {
    require_once __DIR__ . '/../../db.php';
}

// Include advanced AI engine and learning system
require_once __DIR__ . '/maya_advanced_ai.php';
require_once __DIR__ . '/maya_learning_system.php';
require_once __DIR__ . '/maya_natural_language.php';
require_once __DIR__ . '/maya_conversation_memory.php';

// Maya Database Query Functions
class MayaDatabaseManager {
    private $con;
    
    public function __construct($connection) {
        $this->con = $connection;
    }
    
    // Get real-time room information
    public function getRoomsData() {
        $query = "SELECT nr.room_name, nr.base_price, nr.description, nr.is_active,
                         rs.current_status, rs.cleaning_status, rs.last_cleaned
                  FROM named_rooms nr 
                  LEFT JOIN room_status rs ON nr.room_name = rs.room_name 
                  WHERE nr.is_active = 1 
                  ORDER BY nr.base_price ASC";
        
        $result = mysqli_query($this->con, $query);
        $rooms = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $rooms[] = $row;
            }
        }
        
        return $rooms;
    }
    
    // Check room availability for specific dates
    public function checkRoomAvailability($room_name = null, $check_in = null, $check_out = null) {
        $where_clause = "1=1";
        $params = [];
        
        if ($room_name) {
            $where_clause .= " AND TRoom = ?";
            $params[] = $room_name;
        }
        
        if ($check_in && $check_out) {
            $where_clause .= " AND NOT (cout <= ? OR cin >= ?)";
            $params[] = $check_in;
            $params[] = $check_out;
        } else {
            // Check for current and future bookings
            $where_clause .= " AND cout > CURDATE() AND stat != 'cancelled'";
        }
        
        $query = "SELECT TRoom, cin, cout, stat, FName, LName 
                  FROM roombook 
                  WHERE $where_clause
                  ORDER BY cin ASC";
        
        $stmt = mysqli_prepare($this->con, $query);
        if ($params) {
            $types = str_repeat('s', count($params));
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $bookings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $bookings[] = $row;
        }
        
        return $bookings;
    }
    
    // Get current room status
    public function getRoomStatus($room_name = null) {
        $where_clause = $room_name ? "WHERE room_name = ?" : "";
        
        $query = "SELECT room_name, current_status, cleaning_status, 
                         last_cleaned, housekeeping_notes
                  FROM room_status 
                  $where_clause
                  ORDER BY room_name";
        
        $stmt = mysqli_prepare($this->con, $query);
        if ($room_name) {
            mysqli_stmt_bind_param($stmt, 's', $room_name);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $status = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $status[] = $row;
        }
        
        return $status;
    }
    
    // Get recent bookings and statistics
    public function getBookingStats($days = 30) {
        $query = "SELECT 
                    COUNT(*) as total_bookings,
                    COUNT(CASE WHEN stat = 'confirmed' THEN 1 END) as confirmed_bookings,
                    COUNT(CASE WHEN stat = 'pending' THEN 1 END) as pending_bookings,
                    COUNT(CASE WHEN payment_status = 'paid' THEN 1 END) as paid_bookings,
                    AVG(nodays) as avg_stay_duration,
                    TRoom,
                    COUNT(*) as room_bookings
                  FROM roombook 
                  WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                  GROUP BY TRoom
                  ORDER BY room_bookings DESC";
        
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, 'i', $days);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $stats = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $stats[] = $row;
        }
        
        return $stats;
    }
    
    // Get pricing with dynamic adjustments
    public function getDynamicPricing($room_name = null, $date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        $day_of_week = date('w', strtotime($date));
        $is_weekend = ($day_of_week == 5 || $day_of_week == 6); // Friday or Saturday
        
        $query = "SELECT room_name, base_price, description 
                  FROM named_rooms 
                  WHERE is_active = 1";
        
        if ($room_name) {
            $query .= " AND room_name = ?";
        }
        
        $stmt = mysqli_prepare($this->con, $query);
        if ($room_name) {
            mysqli_stmt_bind_param($stmt, 's', $room_name);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $pricing = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $base_price = $row['base_price'];
            
            // Apply dynamic pricing
            if ($is_weekend) {
                $adjusted_price = $base_price * 1.15; // 15% weekend surcharge
            } else {
                $adjusted_price = $base_price * 0.95; // 5% weekday discount
            }
            
            $row['current_price'] = $adjusted_price;
            $row['base_price'] = $base_price;
            $row['is_weekend'] = $is_weekend;
            $row['discount_applied'] = !$is_weekend;
            
            $pricing[] = $row;
        }
        
        return $pricing;
    }
    
    // Get menu items for restaurant recommendations
    public function getMenuHighlights($limit = 5) {
        $query = "SELECT name, description, price, category 
                  FROM menu_items 
                  WHERE is_available = 1 
                  ORDER BY popularity DESC, price ASC 
                  LIMIT ?";
        
        $stmt = mysqli_prepare($this->con, $query);
        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        $menu = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $menu[] = $row;
        }
        
        return $menu;
    }
}

// Initialize Maya Database Manager and Learning System
$mayaDB = new MayaDatabaseManager($con);
$mayaLearningSystem = new MayaLearningSystem($con);

// Check if Maya AI tables exist
function mayaTablesExist() {
    global $con;
    $result = mysqli_query($con, "SHOW TABLES LIKE 'ai_agents'");
    return mysqli_num_rows($result) > 0;
}

// Get Maya's data from database with fallback
function getMayaAgent() {
    global $con;
    
    if (!mayaTablesExist()) {
        return [
            'name' => 'Maya',
            'role' => 'AI Booking Assistant',
            'personality' => 'Friendly AI assistant helping with hotel bookings',
            'avatar_emoji' => '🤖',
            'status' => 'active'
        ];
    }
    
    $query = "SELECT * FROM ai_agents WHERE name = 'Maya' AND status = 'active' LIMIT 1";
    $result = mysqli_query($con, $query);
    return mysqli_fetch_assoc($result) ?: [
        'name' => 'Maya',
        'role' => 'AI Booking Assistant',
        'personality' => 'Friendly AI assistant helping with hotel bookings',
        'avatar_emoji' => '🤖',
        'status' => 'active'
    ];
}

function getMayaKnowledge($category = null) {
    global $con;
    
    if (!mayaTablesExist()) {
        return getDefaultKnowledge($category);
    }
    
    $where = "agent_id = 1 AND is_active = 1";
    if ($category) {
        $where .= " AND category = '" . mysqli_real_escape_string($con, $category) . "'";
    }
    $query = "SELECT * FROM ai_knowledge_base WHERE $where ORDER BY priority DESC";
    $result = mysqli_query($con, $query);
    
    $knowledge = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $knowledge[] = $row;
    }
    return $knowledge ?: getDefaultKnowledge($category);
}

function getMayaQuickActions($page_context = 'all') {
    global $con;
    
    // Always use default actions for now (quick actions stored in knowledge base)
    return getDefaultActions($page_context);
}

// Fallback knowledge base
function getDefaultKnowledge($category = null) {
    $knowledge = [
        [
            'id' => 1,
            'category' => 'greeting',
            'question_keywords' => 'hello,hi,hey,good morning,good afternoon,good evening',
            'response_template' => 'Hi! I\'m Maya, and I\'m here to help you with anything you need about our hotel. What can I do for you today?',
            'priority' => 100
        ],
        [
            'id' => 2,
            'category' => 'rooms',
            'question_keywords' => 'rooms,room,accommodation,stay,book,booking,available,show rooms,room details',
            'response_template' => '🏨 <strong>Our Available Rooms:</strong><br><br><div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;"><div style="background: #e8f4fd; padding: 15px; border-radius: 10px;"><strong style="color: #0f2453;">🏷️ Eatonville Room</strong><br>💰 KES 3,500/night<br>👥 Perfect for 1-2 guests<br>✅ Free WiFi & Parking<br>✅ 24/7 Room Service<br>📱 M-Pesa Payment</div><div style="background: #fff3cd; padding: 15px; border-radius: 10px;"><strong style="color: #856404;">⭐ Merit Room</strong><br>💰 KES 4,000/night<br>👥 Spacious for 1-3 guests<br>✅ Premium amenities<br>✅ Enhanced comfort<br>🌟 Our signature room</div></div>🎯 Which room interests you, or would you like me to help you book one?',
            'priority' => 90
        ],
        [
            'id' => 3,
            'category' => 'pricing',
            'question_keywords' => 'price,cost,rate,how much,expensive,cheap,budget,show me,display,pricing,view pricing',
            'response_template' => '💰 Great question about pricing! Our rooms start from KES 1,300 per night. Prices vary based on room type, dates, and season. I can show you specific pricing for any room you\'re interested in!',
            'priority' => 85
        ],
        [
            'id' => 4,
            'category' => 'availability',
            'question_keywords' => 'available,availability,dates,when,free,vacant',
            'response_template' => '📅 Let me check availability for you! I can show you real-time room availability and help you find the perfect dates for your stay. What dates were you considering?',
            'priority' => 85
        ],
        [
            'id' => 5,
            'category' => 'amenities',
            'question_keywords' => 'amenities,facilities,included,wifi,parking,service',
            'response_template' => '✨ Our rooms come with fantastic amenities! All rooms include: 🔌 Free High-Speed WiFi, 🚗 Complimentary Parking, 🍽️ 24/7 Room Service, 📱 M-Pesa Payment (No fees), 🔒 24/7 Security, and 🧹 Daily Housekeeping!',
            'priority' => 80
        ],
        [
            'id' => 6,
            'category' => 'follow_up',
            'question_keywords' => 'respond,answer,reply,why not,continue,show me,tell me,more,details,respond please',
            'response_template' => '💬 I\'m absolutely here to help! I can provide detailed information about our rooms, pricing, availability, amenities, and help you with bookings. What specific information would you like me to share?',
            'priority' => 75
        ]
    ];
    
    if ($category) {
        return array_filter($knowledge, function($k) use ($category) {
            return $k['category'] === $category;
        });
    }
    
    return $knowledge;
}

// Fallback quick actions
function getDefaultActions($page_context = 'all') {
    return [
        [
            'action_key' => 'check_rooms',
            'action_label' => 'Check Rooms',
            'action_emoji' => '🏨',
            'target_category' => 'rooms'
        ],
        [
            'action_key' => 'view_pricing',
            'action_label' => 'View Pricing', 
            'action_emoji' => '💰',
            'target_category' => 'pricing'
        ],
        [
            'action_key' => 'check_availability',
            'action_label' => 'Check Availability',
            'action_emoji' => '📅', 
            'target_category' => 'availability'
        ],
        [
            'action_key' => 'book_now',
            'action_label' => 'Book Now',
            'action_emoji' => '🚀',
            'target_category' => 'booking_help'
        ]
    ];
}

// Get current page context
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_context = $current_page;

// Map some pages to better contexts
$page_mapping = [
    'index' => 'homepage',
    'rooms' => 'rooms',
    'menu_enhanced' => 'menu'
];

if (isset($page_mapping[$current_page])) {
    $page_context = $page_mapping[$current_page];
}

// Get Maya's data
$maya = getMayaAgent();
$maya_knowledge = getMayaKnowledge();
$maya_actions = getMayaQuickActions($page_context);

// Generate knowledge base for JavaScript
$knowledge_js = json_encode($maya_knowledge);
$actions_js = json_encode($maya_actions);
?>

<!-- Maya AI is now fully installed and operational -->

<!-- Maya AI Assistant Floating Button -->
<div id="mayaFloatingButton" style="
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
    cursor: pointer;
    transition: all 0.3s ease;
">
    <div style="
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #ffce14, #ffd700);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 8px 25px rgba(255, 206, 20, 0.4);
        animation: mayaPulse 2s infinite;
        position: relative;
    " onclick="openMayaChat()">
        <span style="font-size: 28px;"><?php echo $maya['avatar_emoji'] ?? '🤖'; ?></span>
        
        <!-- Notification Badge -->
        <div id="mayaNotificationBadge" style="
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            background: #dc3545;
            border-radius: 50%;
            color: white;
            font-size: 12px;
            font-weight: bold;
            display: none;
            align-items: center;
            justify-content: center;
        ">1</div>
    </div>
</div>

<!-- Maya AI Chat Modal -->
<div id="mayaChatModal" style="display: none; position: fixed; z-index: 1001; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.7);">
    <div style="
        background: white;
        margin: 2% auto;
        padding: 0;
        border-radius: 20px;
        width: 95%;
        max-width: 900px;
        height: 85vh;
        position: relative;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    ">
        <!-- Maya Header -->
        <div style="
            background: linear-gradient(135deg, #0f2453, #1a3567);
            color: white;
            padding: 20px 30px;
            position: relative;
            flex-shrink: 0;
        ">
            <div style="position: absolute; top: 15px; right: 20px;">
                <span onclick="closeMayaChat()" style="
                    font-size: 28px;
                    font-weight: bold;
                    cursor: pointer;
                    color: white;
                    transition: all 0.3s ease;
                " onmouseover="this.style.color='#ffce14'" onmouseout="this.style.color='white'">&times;</span>
            </div>
            <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
                <div id="mayaAvatar" style="
                    width: 50px;
                    height: 50px;
                    background: #ffce14;
                    border-radius: 50%;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 24px;
                    color: #0f2453;
                    animation: mayaPulse 2s infinite;
                "><?php echo $maya['avatar_emoji'] ?? '🤖'; ?></div>
                <div>
                    <h3 style="margin: 0; font-size: 1.5rem;"><?php echo htmlspecialchars($maya['name'] ?? 'Maya'); ?></h3>
                    <p style="margin: 0; opacity: 0.8; font-size: 14px;" id="mayaStatus"><?php echo htmlspecialchars($maya['role'] ?? 'AI Assistant'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Chat Container -->
        <div style="flex: 1; display: flex; flex-direction: column; overflow: hidden;">
            <!-- Chat Messages -->
            <div id="mayaChatMessages" style="
                flex: 1;
                overflow-y: auto;
                padding: 20px 30px;
                background: #f8f9fa;
            ">
                <!-- Messages will be populated here -->
            </div>
            
            <!-- Quick Actions (Hidden by default - only shown when Maya suggests them) -->
            <div id="mayaQuickActions" style="
                padding: 15px 30px;
                background: white;
                border-top: 1px solid #eee;
                display: none;
                gap: 10px;
                flex-wrap: wrap;
                justify-content: center;
            ">
                <!-- Quick action buttons will be populated dynamically when Maya suggests them -->
            </div>
            
            <!-- Chat Input -->
            <div style="
                padding: 20px 30px;
                background: white;
                border-top: 1px solid #eee;
                flex-shrink: 0;
            ">
                <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="text" id="mayaChatInput" placeholder="Ask me anything..." style="
                        flex: 1;
                        padding: 12px 20px;
                        border: 2px solid #e9ecef;
                        border-radius: 25px;
                        font-size: 14px;
                        outline: none;
                        transition: all 0.3s ease;
                    " onkeypress="if(event.key==='Enter') sendMayaMessage()">
                    <button onclick="sendMayaMessage()" style="
                        background: linear-gradient(135deg, #ffce14, #ffd700);
                        color: #0f2453;
                        border: none;
                        padding: 12px 20px;
                        border-radius: 25px;
                        font-weight: 600;
                        cursor: pointer;
                        transition: all 0.3s ease;
                    " onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <i class="fa fa-paper-plane"></i> Send
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@keyframes mayaPulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.05); }
}

@keyframes mayaSlideIn {
    from { opacity: 0; transform: translateX(-20px); }
    to { opacity: 1; transform: translateX(0); }
}

@keyframes mayaFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

#mayaFloatingButton:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 35px rgba(255, 206, 20, 0.6);
}

#mayaChatInput:focus {
    border-color: #0f2453;
    box-shadow: 0 0 0 0.2rem rgba(15, 36, 83, 0.25);
}

/* Mobile responsive */
@media (max-width: 768px) {
    #mayaFloatingButton {
        bottom: 20px;
        right: 20px;
    }
    
    #mayaChatModal > div {
        width: 100%;
        height: 100vh;
        margin: 0;
        border-radius: 0;
    }
    
    #mayaChatMessages {
        padding: 15px 20px;
    }
    
    #mayaQuickActions {
        padding: 10px 20px;
    }
    
    #mayaChatModal input {
        font-size: 16px; /* Prevent zoom on iOS */
    }
}
</style>

<script>
// Maya Advanced AI System Global Variables
let mayaKnowledgeBase = <?php echo $knowledge_js; ?>;
let mayaQuickActions = <?php echo $actions_js; ?>;
let mayaAvatarEmoji = '<?php echo $maya['avatar_emoji'] ?? '🤖'; ?>';
let mayaRole = '<?php echo htmlspecialchars($maya['role'] ?? 'AI Assistant'); ?>';
let mayaCurrentSession = generateSessionId();
let mayaConversationStep = 0;
let mayaUserPreferences = {};
let mayaConversationContext = {};
let mayaAdvancedAI;
let mayaLearningEnabled = false; // Using advanced AI system instead
let mayaResponseHistory = [];
let mayaNaturalLanguage;
let mayaPersonality;

function generateSessionId() {
    return 'maya_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Maya Personality System
class MayaPersonality {
    constructor() {
        this.traits = {
            helpfulness: 0.95,
            enthusiasm: 0.85,
            empathy: 0.88,
            professionalism: 0.80,
            humor: 0.70,
            patience: 0.90
        };
        
        this.moods = ['helpful', 'excited', 'focused', 'caring', 'playful'];
        this.currentMood = 'helpful';
        this.energyLevel = 0.8;
    }
    
    adjustMoodBasedOnInteraction(sentiment, complexity, userStyle) {
        // Adjust Maya's mood based on user interaction
        if (sentiment === 'positive') {
            this.energyLevel = Math.min(1.0, this.energyLevel + 0.1);
            this.currentMood = Math.random() > 0.5 ? 'excited' : 'helpful';
        } else if (sentiment === 'negative') {
            this.currentMood = 'caring';
            this.energyLevel = Math.max(0.3, this.energyLevel - 0.1);
        }
        
        if (complexity > 0.8) {
            this.currentMood = 'focused';
        }
        
        if (userStyle && userStyle.includes('casual')) {
            this.currentMood = 'playful';
        }
    }
    
    getPersonalityAdjustedResponse(response, context) {
        switch (this.currentMood) {
            case 'excited':
                return this.addExcitement(response);
            case 'caring':
                return this.addEmpathy(response);
            case 'focused':
                return this.addProfessionalism(response);
            case 'playful':
                return this.addPlayfulness(response);
            default:
                return response;
        }
    }
    
    addExcitement(response) {
        const excitementPhrases = [
            "I'm so excited to help with this!",
            "This is one of my favorite things to assist with!",
            "Perfect! I love this question!",
            "Ooh, this is going to be great!"
        ];
        
        if (Math.random() > 0.7) {
            const phrase = excitementPhrases[Math.floor(Math.random() * excitementPhrases.length)];
            return phrase + " " + response;
        }
        
        return response;
    }
    
    addEmpathy(response) {
        const empathyPhrases = [
            "I completely understand your concern.",
            "I hear you, and I want to make sure we get this right.",
            "Your satisfaction is really important to me.",
            "I can sense this is important to you."
        ];
        
        const phrase = empathyPhrases[Math.floor(Math.random() * empathyPhrases.length)];
        return phrase + " " + response;
    }
    
    addProfessionalism(response) {
        // Make response more structured and detailed
        return response.replace(/\!/g, '.').replace(/\?$/, '. Let me know if you need any clarification.');
    }
    
    addPlayfulness(response) {
        // Add casual language and friendly touches
        return response
            .replace('I can help', 'I\'d love to help')
            .replace('Our rooms', 'We\'ve got some amazing rooms')
            .replace('The price', 'As for the cost');
    }
}

// Natural Language Processor
class MayaNaturalProcessor {
    constructor() {
        this.conversationStarters = [
            "You know what's interesting about that?",
            "Here's something I think you'll find helpful:",
            "Let me share what I know about this:",
            "I have some great insights on that:",
            "Here's what I'm thinking:",
        ];
        
        this.transitionPhrases = [
            "Actually,", "What's cool is that", "Here's the thing -", 
            "You know what?", "The great news is", "What I love about this is"
        ];
        
        this.confirmationPhrases = [
            "Does that make sense?", "How does that sound?", "What do you think?",
            "Does that help clarify things?", "Are you following me so far?"
        ];
    }
    
    makeResponseConversational(response, context) {
        let natural = response;
        
        // Add conversation starter occasionally
        if (Math.random() > 0.7 && context.complexity > 0.5) {
            const starter = this.conversationStarters[Math.floor(Math.random() * this.conversationStarters.length)];
            natural = starter + " " + natural;
        }
        
        // Add transitions between points
        natural = this.addTransitions(natural);
        
        // Add confirmation questions
        if (Math.random() > 0.6 && !natural.includes('?')) {
            const confirmation = this.confirmationPhrases[Math.floor(Math.random() * this.confirmationPhrases.length)];
            natural += " " + confirmation;
        }
        
        // Make language more conversational
        natural = this.makeLanguageCasual(natural);
        
        return natural;
    }
    
    addTransitions(text) {
        // Add natural transitions between sentences
        const sentences = text.split('. ');
        if (sentences.length > 2) {
            const midPoint = Math.floor(sentences.length / 2);
            const transition = this.transitionPhrases[Math.floor(Math.random() * this.transitionPhrases.length)];
            sentences[midPoint] = transition + " " + sentences[midPoint].toLowerCase();
        }
        
        return sentences.join('. ');
    }
    
    makeLanguageCasual(text) {
        const casualizations = {
            'I would like to': 'I\'d love to',
            'You will find': 'You\'ll find',
            'It is': 'It\'s',
            'We have': 'We\'ve got',
            'This is': 'This\'s',
            'would be': '\'d be',
            'cannot': 'can\'t',
            'should not': 'shouldn\'t'
        };
        
        let casual = text;
        for (const [formal, informal] of Object.entries(casualizations)) {
            casual = casual.replace(new RegExp(formal, 'gi'), informal);
        }
        
        return casual;
    }
    
    addThinkingDelay() {
        const thinkingPhrases = [
            "Let me think about that for a sec...",
            "Hmm, give me just a moment...",
            "Let me check on that for you...",
            "One second while I look that up...",
            "Interesting question, let me see..."
        ];
        
        return thinkingPhrases[Math.floor(Math.random() * thinkingPhrases.length)];
    }
}

// Advanced AI Processor Class
class MayaAdvancedProcessor {
    constructor() {
        this.conversationHistory = [];
        this.userProfile = {};
        this.emotionalState = 'neutral';
        this.contextMemory = {};
        this.responseVariations = this.initializeResponseVariations();
        this.intentPatterns = this.initializeIntentPatterns();
    }
    
    initializeResponseVariations() {
        return {
            thinking: [
                "🤔 Let me think about the best way to help you with that...",
                "💭 Interesting question! I'm analyzing all the options for you...",
                "🧠 Processing your request... I want to give you the most helpful answer possible!",
                "⚡ Great question! Let me pull together all the relevant information..."
            ],
            understanding: [
                "💡 Ah, I see exactly what you're looking for!",
                "🎯 Perfect! I understand your needs completely.",
                "✨ Got it! That makes perfect sense.",
                "🤝 I'm with you 100%! Let me help with that."
            ],
            enthusiasm: [
                "🎉 I love your enthusiasm! It makes me so excited to help you!",
                "✨ Your positive energy is contagious! I'm thrilled to assist you!",
                "🌟 Amazing! I can feel your excitement, and I'm here to make it even better!"
            ],
            empathy: [
                "💙 I completely understand your concern, and I'm here to help resolve this.",
                "🤝 I hear you, and your satisfaction is my top priority. Let's fix this together.",
                "😊 I appreciate you sharing your concerns with me. Let me make this right."
            ]
        };
    }
    
    initializeIntentPatterns() {
        return {
            booking_immediate: {
                patterns: ['book now', 'reserve now', 'available tonight', 'urgent booking', 'last minute', 'book', 'booking'],
                entities: ['time', 'urgency'],
                complexity: 0.8
            },
            booking_future: {
                patterns: ['book for', 'reserve for', 'plan ahead', 'future booking', 'next week', 'next month'],
                entities: ['date', 'planning'],
                complexity: 0.6
            },
            room_inquiry: {
                patterns: ['what rooms', 'rooms available', 'show rooms', 'available rooms', 'room options', 'rooms are available', 'what rooms are', 'rooms do you have'],
                entities: ['room_types'],
                complexity: 0.6
            },
            room_comparison: {
                patterns: ['compare rooms', 'which room', 'best room', 'recommend room', 'difference between'],
                entities: ['room_types', 'preferences'],
                complexity: 0.9
            },
            pricing_inquiry: {
                patterns: ['how much', 'price', 'cost', 'rate', 'budget', 'expensive', 'cheap', 'show me', 'tell me', 'display', 'pricing'],
                entities: ['price_range', 'budget'],
                complexity: 0.7
            },
            availability_check: {
                patterns: ['available', 'availability', 'free', 'vacant', 'check dates', 'when available'],
                entities: ['dates'],
                complexity: 0.5
            },
            greeting: {
                patterns: ['hi', 'hello', 'hey', 'good morning', 'good evening', 'greetings'],
                entities: ['greeting'],
                complexity: 0.2
            },
            emotional_positive: {
                patterns: ['great', 'excellent', 'amazing', 'love', 'perfect', 'wonderful', 'fantastic'],
                entities: ['satisfaction'],
                complexity: 0.3
            },
            emotional_negative: {
                patterns: ['bad', 'terrible', 'disappointed', 'frustrated', 'problem', 'issue', 'wrong'],
                entities: ['concern', 'problem'],
                complexity: 0.9
            },
            general_follow_up: {
                patterns: ['respond', 'answer', 'reply', 'why not', 'continue', 'go on', 'more info', 'details'],
                entities: ['follow_up'],
                complexity: 0.5
            },
            confirmation_with_details: {
                patterns: ['and yes', 'yes and', 'merit and', 'eatonville and', 'one night', 'two nights', 'three nights'],
                entities: ['confirmation', 'room', 'duration'],
                complexity: 0.8
            },
            compound_booking: {
                patterns: ['merit one', 'eatonville one', 'merit two', 'eatonville two', 'room and', 'night and'],
                entities: ['room', 'duration'],
                complexity: 0.9
            }
        };
    }
    
    processMessage(message, context) {
        const intent = this.analyzeIntent(message);
        const sentiment = this.analyzeSentiment(message);
        const entities = this.extractEntities(message);
        const complexity = this.calculateComplexity(intent, sentiment, entities);
        
        this.updateConversationHistory(message, intent, sentiment, entities);
        
        const response = this.generateIntelligentResponse(intent, sentiment, entities, message, complexity);
        const suggestions = this.generateContextualSuggestions(intent, entities);
        const followUpQuestions = this.generateIntelligentFollowUp(intent, entities);
        
        return {
            response: response,
            intent: intent,
            sentiment: sentiment,
            entities: entities,
            complexity: complexity,
            suggestions: suggestions,
            follow_up_questions: followUpQuestions
        };
    }
    
    analyzeIntent(message) {
        const messageLower = message.toLowerCase();
        let bestIntent = 'general_inquiry';
        let bestScore = 0;
        
        for (const [intent, config] of Object.entries(this.intentPatterns)) {
            let score = 0;
            for (const pattern of config.patterns) {
                if (messageLower.includes(pattern)) {
                    score += pattern.length * 2; // Longer patterns get more weight
                }
            }
            
            if (score > bestScore) {
                bestScore = score;
                bestIntent = intent;
            }
        }
        
        return bestIntent;
    }
    
    analyzeSentiment(message) {
        const positive = ['great', 'excellent', 'amazing', 'love', 'perfect', 'wonderful', 'fantastic', 'awesome', 'good', 'nice', 'happy', 'excited', 'thank'];
        const negative = ['bad', 'terrible', 'awful', 'hate', 'disappointed', 'frustrated', 'angry', 'sad', 'problem', 'issue', 'wrong', 'horrible'];
        
        const messageLower = message.toLowerCase();
        let positiveScore = 0;
        let negativeScore = 0;
        
        positive.forEach(word => {
            if (messageLower.includes(word)) positiveScore++;
        });
        
        negative.forEach(word => {
            if (messageLower.includes(word)) negativeScore++;
        });
        
        if (positiveScore > negativeScore) return 'positive';
        if (negativeScore > positiveScore) return 'negative';
        return 'neutral';
    }
    
    extractEntities(message) {
        const entities = {};
        const messageLower = message.toLowerCase();
        
        // Extract room preferences
        const roomTypes = ['merit', 'eatonville', 'deluxe', 'suite', 'standard', 'premium', 'luxury', 'budget'];
        roomTypes.forEach(room => {
            if (messageLower.includes(room)) {
                entities.preferred_room = room;
            }
        });
        
        // Extract time preferences and durations
        const timePatterns = {
            'tonight': 'immediate',
            'today': 'immediate',
            'tomorrow': 'next_day',
            'next week': 'future',
            'weekend': 'weekend',
            'holiday': 'special',
            'one night': 'one_night',
            'two nights': 'two_nights',
            'three nights': 'three_nights',
            'week': 'one_week'
        };
        
        for (const [pattern, category] of Object.entries(timePatterns)) {
            if (messageLower.includes(pattern)) {
                entities.time_preference = category;
                entities.specific_time = pattern;
                
                // Extract duration
                if (pattern.includes('night')) {
                    const nightMatch = pattern.match(/(\w+)\s+night/);
                    if (nightMatch) {
                        const numberWords = {'one': 1, 'two': 2, 'three': 3, 'four': 4, 'five': 5};
                        entities.duration = numberWords[nightMatch[1]] || 1;
                    }
                }
            }
        }
        
        // Extract numbers (guests, budget, etc.)
        const numbers = message.match(/\d+/g);
        if (numbers) {
            const firstNumber = parseInt(numbers[0]);
            
            // Check context for what the number refers to
            if (messageLower.includes('guest') || messageLower.includes('people') || messageLower.includes('person')) {
                entities.guest_count = firstNumber;
            } else if (messageLower.includes('budget') || messageLower.includes('price') || messageLower.includes('cost')) {
                entities.budget = firstNumber;
            } else if (messageLower.includes('night') || messageLower.includes('day')) {
                entities.duration = firstNumber;
            } else {
                // If number appears standalone, assume it's guest count in booking context
                entities.guest_count = firstNumber;
            }
        }
        
        // Extract confirmation words
        const confirmations = ['yes', 'yeah', 'yep', 'sure', 'ok', 'okay', 'definitely', 'absolutely'];
        const denials = ['no', 'nope', 'not', 'don\'t', 'won\'t'];
        
        confirmations.forEach(word => {
            if (messageLower.includes(word)) {
                entities.confirmation = 'yes';
            }
        });
        
        denials.forEach(word => {
            if (messageLower.includes(word)) {
                entities.confirmation = 'no';
            }
        });
        
        return entities;
    }
    
    calculateComplexity(intent, sentiment, entities) {
        let complexity = 0.5; // Base complexity
        
        // Intent-based complexity
        if (this.intentPatterns[intent]) {
            complexity = this.intentPatterns[intent].complexity;
        }
        
        // Sentiment affects complexity
        if (sentiment === 'negative') complexity += 0.2;
        if (sentiment === 'positive') complexity -= 0.1;
        
        // More entities = more complexity
        complexity += Object.keys(entities).length * 0.1;
        
        return Math.min(1.0, Math.max(0.0, complexity));
    }
    
    generateIntelligentResponse(intent, sentiment, entities, originalMessage, complexity) {
        // Handle emotional responses first
        if (sentiment === 'negative') {
            return this.generateEmpathicResponse(intent, entities);
        }
        if (sentiment === 'positive') {
            return this.generateEnthusiasticResponse(intent, entities);
        }
        
        // Generate response based on intent
        switch (intent) {
            case 'booking_immediate':
                return this.generateUrgentBookingResponse(entities);
            case 'room_inquiry':
                return this.generateRoomAvailabilityResponse(entities);
            case 'room_comparison':
                return this.generateDetailedComparisonResponse(entities);
            case 'pricing_inquiry':
                return this.generateSmartPricingResponse(entities);
            case 'availability_check':
                return this.generateIntelligentAvailabilityResponse(entities);
            case 'greeting':
                return this.generatePersonalizedGreeting(entities);
            case 'confirmation_with_details':
            case 'compound_booking':
                return this.generateCompoundBookingResponse(entities, originalMessage);
            case 'general_follow_up':
                return this.generateFollowUpResponse(entities, originalMessage);
            default:
                return this.generateContextualResponse(intent, entities, originalMessage);
        }
    }
    
    generateUrgentBookingResponse(entities) {
        let response = "⚡ <strong>Immediate Booking Alert!</strong> I love the spontaneous spirit! Let me check what's available right now...<br><br>";
        
        if (entities.guest_count) {
            response += `👥 Perfect! I see you need accommodation for ${entities.guest_count} guest${entities.guest_count > 1 ? 's' : ''}.<br>`;
        }
        
        response += "🟢 <strong>GREAT NEWS:</strong> We have immediate availability!<br>";
        response += "💡 <strong>Tonight's Special:</strong> Book now and save 5% on walk-in rates!<br><br>";
        response += "✨ <strong>Available Right Now:</strong><br>";
        response += "• Eatonville - KES 3,325 (5% off!)<br>";
        response += "• Merit - KES 3,800 (5% off!)<br><br>";
        response += "🏃‍♀️ Shall I secure one of these for you immediately?";
        
        return response;
    }
    
    generateDetailedComparisonResponse(entities) {
        let response = "🏆 <strong>Room Comparison Analysis</strong><br><br>";
        response += "Let me break down our two main room options for you:<br><br>";
        
        response += "📊 <strong>Eatonville vs Merit:</strong><br><br>";
        response += "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 15px 0;'>";
        response += "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px;'>";
        response += "<strong style='color: #0f2453;'>🏷️ Eatonville (KES 3,500)</strong><br>";
        response += "✅ Great value option<br>";
        response += "✅ All standard amenities<br>";
        response += "✅ Popular choice<br>";
        response += "✅ Reliable and comfortable";
        response += "</div>";
        response += "<div style='background: #fff3cd; padding: 15px; border-radius: 10px;'>";
        response += "<strong style='color: #856404;'>⭐ Merit (KES 4,000)</strong><br>";
        response += "✅ Premium features<br>";
        response += "✅ More spacious<br>";
        response += "✅ Enhanced amenities<br>";
        response += "✅ Upgraded experience";
        response += "</div>";
        response += "</div>";
        
        if (entities.budget) {
            if (entities.budget <= 3500) {
                response += `💰 Based on your budget of KES ${entities.budget}, I'd recommend Eatonville - it's a great choice!`;
            } else {
                response += `💰 With your budget of KES ${entities.budget}, you could go with either room, or upgrade to Merit for the premium experience.`;
            }
        } else {
            response += "What's most important to you - saving money or having the premium features?";
        }
        
        return response;
    }
    
    generateSmartPricingResponse(entities) {
        let response = "💰 <strong>Pricing Information</strong><br><br>";
        response += "Here's how our pricing works:<br><br>";
        
        response += "📊 <strong>Room Rates:</strong><br>";
        response += "• Eatonville: KES 3,500 per night<br>";
        response += "• Merit: KES 4,000 per night<br><br>";
        
        response += "💡 <strong>Ways to Save:</strong><br>";
        response += "• Weekdays (Sun-Thu): 5% discount<br>";
        response += "• Stay 3+ nights: 10% off<br>";
        response += "• Same-day booking: 5% off<br><br>";
        
        if (entities.budget) {
            response += `🎯 With your budget of KES ${entities.budget}:<br>`;
            if (entities.budget >= 4000) {
                response += "You can choose any room with full flexibility!<br>";
            } else if (entities.budget >= 3500) {
                response += "Eatonville fits perfectly, or Merit with weekday discount!<br>";
            } else {
                response += "Eatonville with weekday discount would work great!<br>";
            }
            response += "<br>";
        }
        
        response += "All rates include WiFi, parking, and security - no extra fees!";
        
        return response;
    }
    
    generateEmpathicResponse(intent, entities) {
        const empathyResponses = this.responseVariations.empathy;
        const baseResponse = empathyResponses[Math.floor(Math.random() * empathyResponses.length)];
        
        return baseResponse + "<br><br>🔧 <strong>Let's solve this together:</strong> What specific issue can I help address? I'm here to make your experience exceptional.";
    }
    
    generateEnthusiasticResponse(intent, entities) {
        const enthusiasmResponses = this.responseVariations.enthusiasm;
        const baseResponse = enthusiasmResponses[Math.floor(Math.random() * enthusiasmResponses.length)];
        
        return baseResponse + "<br><br>🚀 I'm energized to help you have an amazing experience! What can I do to make it even better?";
    }
    
    generateFollowUpResponse(entities, originalMessage) {
        const lastIntent = this.conversationHistory.length > 0 ? 
            this.conversationHistory[this.conversationHistory.length - 1].intent : null;
        
        // Generate contextual follow-up based on last conversation
        if (lastIntent === 'pricing_inquiry' || originalMessage.toLowerCase().includes('show me')) {
            return this.generateDetailedPricingDisplay();
        } else if (lastIntent === 'room_comparison') {
            return this.generateDetailedComparisonResponse(entities);
        } else if (originalMessage.toLowerCase().includes('respond') || originalMessage.toLowerCase().includes('answer')) {
            return "💬 I'm absolutely here to help! I apologize if there was any delay. I'm ready to assist you with:<br><br>🏨 <strong>Room Information</strong> - Details about our accommodations<br>💰 <strong>Pricing</strong> - Current rates and special offers<br>📅 <strong>Availability</strong> - Real-time room availability<br>🚀 <strong>Booking</strong> - Help you secure your reservation<br><br>What would you like to know more about?";
        } else {
            return "✨ I'm here and ready to help! What specific information can I provide about Orlando International Resorts? I can help with rooms, pricing, availability, or booking assistance.";
        }
    }
    
    generateDetailedPricingDisplay() {
        return "💰 <strong>Complete Pricing Breakdown:</strong><br><br>" +
               "🏷️ <strong>Room Rates:</strong><br>" +
               "• <strong>Eatonville:</strong> KES 3,500/night<br>" +
               "• <strong>Merit:</strong> KES 4,000/night<br><br>" +
               "📊 <strong>Dynamic Pricing:</strong><br>" +
               "• <strong>Weekdays (Sun-Thu):</strong> Base rate - 5%<br>" +
               "• <strong>Weekends (Fri-Sat):</strong> Base rate + 15%<br>" +
               "• <strong>Extended Stay (3+ nights):</strong> Additional 10% off<br>" +
               "• <strong>Same-day booking:</strong> 5% discount<br><br>" +
               "💎 <strong>Included Value (KES 800+ worth):</strong><br>" +
               "✅ Free High-Speed WiFi<br>" +
               "✅ Complimentary Parking<br>" +
               "✅ 24/7 Room Service<br>" +
               "✅ M-Pesa Payment (No transaction fees)<br>" +
               "✅ 24/7 Security<br>" +
               "✅ Daily Housekeeping<br><br>" +
               "🎯 Which room and dates interest you most?";
    }
    
    generateContextualResponse(intent, entities, originalMessage) {
        const messageLower = originalMessage.toLowerCase();
        
        // Analyze the actual message content and generate appropriate responses
        if (messageLower.includes('room') && (messageLower.includes('available') || messageLower.includes('what rooms'))) {
            return this.generateRoomAvailabilityResponse(entities);
        }
        
        if (messageLower.includes('book') || messageLower.includes('reserve')) {
            return this.generateBookingAssistanceResponse(entities);
        }
        
        if (messageLower.includes('price') || messageLower.includes('cost') || messageLower.includes('rate')) {
            return this.generateSmartPricingResponse(entities);
        }
        
        if (messageLower.includes('hi') || messageLower.includes('hello') || messageLower.includes('hey')) {
            return this.generatePersonalizedGreeting(entities);
        }
        
        // Analyze message complexity and respond accordingly
        const wordCount = originalMessage.split(' ').length;
        if (wordCount === 1) {
            return this.generateSingleWordResponse(originalMessage, intent);
        }
        
        // Generate contextual response based on detected intent
        return this.generateIntentBasedResponse(intent, entities, originalMessage);
    }
    
    generateRoomAvailabilityResponse(entities) {
        return "Perfect! You're asking about our available rooms. Let me show you what we have:<br><br>" +
               "🏨 <strong>Currently Available:</strong><br>" +
               "• <strong>Eatonville Room</strong> - KES 3,500/night (Great value option)<br>" +
               "• <strong>Merit Room</strong> - KES 4,000/night (Premium choice with extra space)<br><br>" +
               "Both rooms include free WiFi, parking, and 24/7 service. Which one interests you, or would you like more details about either room?";
    }
    
    generateBookingAssistanceResponse(entities) {
        return "Excellent! I'd love to help you book a room. Here's what I can do for you:<br><br>" +
               "🚀 <strong>Quick Booking Process:</strong><br>" +
               "1. Choose your preferred room (Eatonville or Merit)<br>" +
               "2. Select your dates<br>" +
               "3. Confirm details - no deposit required!<br><br>" +
               "What dates are you looking at, and do you have a room preference?";
    }
    
    generatePersonalizedGreeting(entities) {
        const greetings = [
            "Hey there! Great to meet you! I'm Maya, and I'm genuinely excited to help you find the perfect room.",
            "Hi! Welcome to Orlando International Resorts! I'm Maya, your personal booking assistant.",
            "Hello! I'm Maya, and I'm here to make your hotel booking experience amazing!"
        ];
        
        return greetings[Math.floor(Math.random() * greetings.length)] + 
               "<br><br>What brings you here today? Looking for a room, checking prices, or just exploring your options?";
    }
    
    generateSingleWordResponse(word, intent) {
        const wordLower = word.toLowerCase();
        
        switch(wordLower) {
            case 'book':
            case 'booking':
                return "Ready to book? Awesome! I can help you secure a room right now. What dates work for you?";
            case 'price':
            case 'prices':
            case 'cost':
                return "Smart to check pricing first! Our rooms are very competitively priced:<br>• Eatonville: KES 3,500/night<br>• Merit: KES 4,000/night<br><br>What's your budget range?";
            case 'rooms':
                return "Great question about our rooms! We have two fantastic options with different features and pricing. Would you like me to break down the differences?";
            case 'availability':
            case 'available':
                return "Checking availability - smart move! Both our rooms are currently available. What dates are you considering?";
            default:
                return `Interesting! You mentioned "${word}". I want to give you the most helpful response - could you tell me a bit more about what you're looking for?`;
        }
    }
    
    generateIntentBasedResponse(intent, entities, originalMessage) {
        // Check for entity combinations and context
        if (this.hasBookingContext() && entities.confirmation === 'yes') {
            return this.generateBookingConfirmationResponse(entities);
        }
        
        if (entities.preferred_room && (entities.duration || entities.time_preference)) {
            return this.generateRoomAndDurationResponse(entities, originalMessage);
        }
        
        if (entities.guest_count && entities.confirmation === 'yes') {
            return this.generateGuestConfirmationResponse(entities);
        }
        
        switch(intent) {
            case 'general_inquiry':
                return this.analyzeAndRespondToGeneralInquiry(originalMessage, entities);
            case 'booking_immediate':
                return "I can sense you're ready to book soon! That's exciting. Let me help you find the perfect room and dates.";
            case 'pricing_inquiry':
                return this.generateAdvancedPricingResponse(entities);
            default:
                return this.generateAdaptiveResponse(originalMessage, entities);
        }
    }
    
    hasBookingContext() {
        // Check if previous messages were about booking
        return this.conversationHistory.some(item => 
            item.intent === 'booking_immediate' || 
            item.message.toLowerCase().includes('book')
        );
    }
    
    generateBookingConfirmationResponse(entities) {
        let response = "Perfect! I've got your confirmation. ";
        
        if (entities.guest_count) {
            response += `So that's ${entities.guest_count} guest${entities.guest_count > 1 ? 's' : ''}`;
        }
        
        if (entities.preferred_room) {
            response += ` for the ${entities.preferred_room.charAt(0).toUpperCase() + entities.preferred_room.slice(1)} room`;
        }
        
        if (entities.duration) {
            response += ` for ${entities.duration} night${entities.duration > 1 ? 's' : ''}`;
        }
        
        response += ".<br><br>🎯 <strong>Let me get this booked for you:</strong><br>";
        response += "• Room: " + (entities.preferred_room ? entities.preferred_room.charAt(0).toUpperCase() + entities.preferred_room.slice(1) : "Merit") + "<br>";
        response += "• Guests: " + (entities.guest_count || 1) + "<br>";
        response += "• Duration: " + (entities.duration || 1) + " night(s)<br><br>";
        response += "What date would you like to check in?";
        
        return response;
    }
    
    generateRoomAndDurationResponse(entities, originalMessage) {
        const room = entities.preferred_room.charAt(0).toUpperCase() + entities.preferred_room.slice(1);
        let response = `Excellent choice! The ${room} room `;
        
        if (entities.duration) {
            response += `for ${entities.duration} night${entities.duration > 1 ? 's' : ''} `;
        } else if (entities.time_preference === 'one_night') {
            response += `for one night `;
        }
        
        response += "is a great option!<br><br>";
        
        // Add room details
        if (entities.preferred_room === 'merit') {
            response += "🏨 <strong>Merit Room Details:</strong><br>";
            response += "• KES 4,000 per night<br>";
            response += "• Spacious accommodation for 1-3 guests<br>";
            response += "• Premium amenities and enhanced comfort<br>";
        } else if (entities.preferred_room === 'eatonville') {
            response += "🏨 <strong>Eatonville Room Details:</strong><br>";
            response += "• KES 3,500 per night<br>";
            response += "• Great value for 1-2 guests<br>";
            response += "• All standard amenities included<br>";
        }
        
        // Calculate total
        const price = entities.preferred_room === 'merit' ? 4000 : 3500;
        const nights = entities.duration || 1;
        const total = price * nights;
        
        response += `<br>💰 <strong>Total Cost:</strong> KES ${total.toLocaleString()} for ${nights} night${nights > 1 ? 's' : ''}<br><br>`;
        response += "Ready to book this? Just let me know what date you'd like to check in!";
        
        return response;
    }
    
    generateGuestConfirmationResponse(entities) {
        let response = `Perfect! ${entities.guest_count} guest${entities.guest_count > 1 ? 's' : ''} it is. `;
        
        // Reference previous booking context
        if (this.hasBookingContext()) {
            response += "Now I have all the details I need:<br><br>";
            response += `👥 <strong>Guests:</strong> ${entities.guest_count}<br>`;
            response += `🏨 <strong>Room:</strong> Merit (KES 3,800 with 5% discount)<br>`;
            response += `⏰ <strong>Duration:</strong> Tonight<br><br>`;
            response += "🚀 Shall I go ahead and secure this booking for you? What time would you like to check in?";
        } else {
            response += "What room would you prefer for your stay?";
        }
        
        return response;
    }
    
    generateCompoundBookingResponse(entities, originalMessage) {
        let response = "Perfect! I understand exactly what you want. ";
        
        // Handle "merit and one night" type messages
        if (entities.preferred_room && (entities.duration || entities.time_preference)) {
            return this.generateRoomAndDurationResponse(entities, originalMessage);
        }
        
        // Handle "1 and yes" type messages
        if (entities.guest_count && entities.confirmation === 'yes') {
            return this.generateGuestConfirmationResponse(entities);
        }
        
        // Handle other compound messages
        const messageLower = originalMessage.toLowerCase();
        
        if (messageLower.includes('merit') && messageLower.includes('night')) {
            entities.preferred_room = 'merit';
            entities.duration = 1;
            return this.generateRoomAndDurationResponse(entities, originalMessage);
        }
        
        if (messageLower.includes('eatonville') && messageLower.includes('night')) {
            entities.preferred_room = 'eatonville';
            entities.duration = 1;
            return this.generateRoomAndDurationResponse(entities, originalMessage);
        }
        
        return response + "Let me break that down and help you with each part.";
    }
    
    analyzeAndRespondToGeneralInquiry(message, entities = {}) {
        const messageLower = message.toLowerCase();
        
        // Check for entity combinations first
        if (entities.preferred_room && entities.duration) {
            return this.generateRoomAndDurationResponse(entities, message);
        }
        
        if (entities.guest_count && entities.confirmation) {
            return this.generateGuestConfirmationResponse(entities);
        }
        
        // Look for specific topics within the message
        if (messageLower.includes('help')) {
            return "I'm absolutely here to help! I specialize in making hotel bookings easy and finding you the perfect room. What specific information can I provide?";
        }
        
        if (messageLower.includes('question')) {
            return "I love questions! That's what I'm here for. Fire away - I can help with rooms, pricing, availability, amenities, or anything else about our hotel.";
        }
        
        if (messageLower.includes('information') || messageLower.includes('info')) {
            return "I've got tons of information to share! What would be most helpful - room details, pricing, location info, or something else?";
        }
        
        // Analyze message length and complexity
        const words = message.split(' ');
        if (words.length > 10) {
            return "I can see you have a detailed question! I've read through what you said, and I want to make sure I address everything properly. Let me help you step by step - what's the most important thing you'd like to know first?";
        }
        
        // Enhanced analysis for short messages with entities
        if (words.length <= 3 && Object.keys(entities).length > 0) {
            return this.generateShortMessageWithEntitiesResponse(message, entities);
        }
        
        return `I want to understand exactly what you need! You mentioned "${message}" - could you help me focus on the specific aspect that's most important to you right now?`;
    }
    
    generateShortMessageWithEntitiesResponse(message, entities) {
        let response = "Got it! ";
        
        if (entities.preferred_room) {
            response += `You're interested in the ${entities.preferred_room.charAt(0).toUpperCase() + entities.preferred_room.slice(1)} room`;
        }
        
        if (entities.duration) {
            response += ` for ${entities.duration} night${entities.duration > 1 ? 's' : ''}`;
        }
        
        if (entities.guest_count) {
            response += ` for ${entities.guest_count} guest${entities.guest_count > 1 ? 's' : ''}`;
        }
        
        if (entities.confirmation === 'yes') {
            response += ". Perfect! Let me help you with that booking.";
        } else {
            response += ". Let me give you all the details about that option!";
        }
        
        return response;
    }
    
    generateAdaptiveResponse(message, entities) {
        return `I'm analyzing your message: "${message}". Based on what you've said, I think you might be interested in learning more about our hotel services. What specific aspect would be most helpful for you?`;
    }
    
    generateContextualSuggestions(intent, entities) {
        const suggestions = [];
        
        switch (intent) {
            case 'booking_immediate':
                suggestions.push(
                    {text: '🌙 Show Tonight\'s Rooms', action: 'show_tonight_rooms'},
                    {text: '💰 Best Rate Options', action: 'show_best_rates'},
                    {text: '⚡ Quick Book Now', action: 'quick_book'}
                );
                break;
                
            case 'room_comparison':
                suggestions.push(
                    {text: '📊 Detailed Comparison', action: 'detailed_comparison'},
                    {text: '💰 Price Comparison', action: 'price_comparison'},
                    {text: '⭐ Guest Reviews', action: 'guest_reviews'}
                );
                break;
                
            case 'pricing_inquiry':
                suggestions.push(
                    {text: '📅 Price Calendar', action: 'price_calendar'},
                    {text: '💸 Available Discounts', action: 'show_discounts'},
                    {text: '🎯 Budget Optimizer', action: 'budget_optimizer'}
                );
                break;
                
            case 'general_follow_up':
                suggestions.push(
                    {text: '🏨 Browse Rooms', action: 'browse_rooms'},
                    {text: '💰 View Pricing', action: 'check_pricing'},
                    {text: '📅 Check Availability', action: 'check_availability'},
                    {text: '🚀 Book Now', action: 'book_now'}
                );
                break;
                
            default:
                suggestions.push(
                    {text: '🏨 Browse Rooms', action: 'browse_rooms'},
                    {text: '💰 Check Pricing', action: 'check_pricing'},
                    {text: '📅 View Availability', action: 'check_availability'},
                    {text: '🚀 Book Now', action: 'book_now'}
                );
        }
        
        return suggestions;
    }
    
    generateIntelligentFollowUp(intent, entities) {
        const questions = [];
        
        switch (intent) {
            case 'booking_immediate':
                if (!entities.guest_count) questions.push("How many guests will be staying?");
                if (!entities.preferred_room) questions.push("Do you have any room preferences?");
                break;
                
            case 'room_comparison':
                questions.push("What's most important to you: budget, location, or luxury amenities?");
                if (!entities.guest_count) questions.push("How many guests will you be accommodating?");
                break;
                
            case 'pricing_inquiry':
                if (!entities.time_preference) questions.push("When are you planning to stay?");
                if (!entities.budget) questions.push("What's your preferred price range?");
                break;
                
            case 'general_follow_up':
                questions.push("What specific information would be most helpful for you?");
                break;
        }
        
        return questions.slice(0, 2); // Maximum 2 follow-up questions
    }
    
    getThinkingMessage() {
        return this.responseVariations.thinking[Math.floor(Math.random() * this.responseVariations.thinking.length)];
    }
    
    updateConversationHistory(message, intent, sentiment, entities) {
        this.conversationHistory.push({
            message: message,
            intent: intent,
            sentiment: sentiment,
            entities: entities,
            timestamp: Date.now()
        });
        
        // Keep only last 10 interactions for context
        if (this.conversationHistory.length > 10) {
            this.conversationHistory.shift();
        }
        
        // Update user profile
        if (entities.preferred_room) {
            this.userProfile.preferred_room = entities.preferred_room;
        }
        if (entities.budget) {
            this.userProfile.budget_range = entities.budget;
        }
    }
}

function openMayaChat() {
    document.getElementById('mayaChatModal').style.display = 'block';
    document.getElementById('mayaNotificationBadge').style.display = 'none';
    
    // Initialize chat if empty
    const chatMessages = document.getElementById('mayaChatMessages');
    if (chatMessages.children.length === 0) {
        initializeMayaChat();
    }
}

function closeMayaChat() {
    document.getElementById('mayaChatModal').style.display = 'none';
}

function initializeMayaChat() {
    // Welcome message
    setTimeout(() => {
        addMayaMessage("Hi! I'm <?php echo $maya['name'] ?? 'Maya'; ?>, and I'm here to help you with anything you need about our hotel.");
        
        setTimeout(() => {
            addMayaMessage("I can help you with room bookings, pricing, availability, and answer any questions you might have. What would you like to know?");
            // Don't show quick actions by default - let Maya suggest them dynamically
        }, 1500);
    }, 500);
}

function addMayaMessage(message, isUser = false) {
    const chatContainer = document.getElementById('mayaChatMessages');
    const messageDiv = document.createElement('div');
    
    messageDiv.style.cssText = `
        margin-bottom: 15px;
        display: flex;
        ${isUser ? 'justify-content: flex-end' : 'justify-content: flex-start'};
        animation: mayaSlideIn 0.3s ease;
    `;
    
    const bubbleStyle = isUser ? 
        'background: linear-gradient(135deg, #0f2453, #1a3567); color: white; margin-left: 60px;' :
        'background: white; color: #333; margin-right: 60px; border: 1px solid #e9ecef;';
    
    messageDiv.innerHTML = `
        <div style="
            padding: 12px 18px;
            border-radius: 18px;
            max-width: 80%;
            ${bubbleStyle}
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: relative;
        ">
            ${!isUser ? '<div style="position: absolute; left: -40px; top: 5px; width: 30px; height: 30px; background: #ffce14; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 14px;">' + mayaAvatarEmoji + '</div>' : ''}
            <div style="font-size: 14px; line-height: 1.4;">${message}</div>
            <div style="font-size: 11px; opacity: 0.7; margin-top: 5px; text-align: right;">${new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</div>

        </div>
    `;
    
    chatContainer.appendChild(messageDiv);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Update status for AI messages
    if (!isUser) {
        updateMayaStatus('typing');
        setTimeout(() => updateMayaStatus('online'), 2000);
    }
}

function sendMayaMessage() {
    console.log('sendMayaMessage called');
    const input = document.getElementById('mayaChatInput');
    const message = input.value.trim();
    console.log('Message to send:', message);
    
    if (message) {
        console.log('Adding user message:', message);
        addMayaMessage(message, true);
        input.value = '';
        
        // Increment conversation step
        mayaConversationStep++;
        
        // Process Maya's response
        setTimeout(() => {
            console.log('Processing Maya response for:', message);
            processMayaResponse(message);
        }, 1000);
        
        // Log conversation to database
        logMayaConversation(message);
    } else {
        console.log('No message to send');
    }
}

function processMayaResponse(userMessage) {
    console.log('Maya processing message:', userMessage);
    
    // Always use ChatGPT-like intelligent engine
    processWithLearningEngine(userMessage);
    return;
    
    try {
        // Debug: Check if classes are properly initialized
        if (typeof mayaAdvancedAI === 'undefined' || mayaAdvancedAI === null) {
            console.error('mayaAdvancedAI is not initialized');
            addMayaMessage("I heard you! Let me help with that...");
            setTimeout(() => {
                const simpleResponse = getSimpleResponse(userMessage);
                addMayaMessage(simpleResponse);
                showMayaQuickActions();
            }, 1000);
            return;
        }
        
        // Use advanced AI processing as fallback
        const aiResponse = mayaAdvancedAI.processMessage(userMessage, mayaConversationContext);
        console.log('Maya AI Response:', aiResponse);
        
        // Check if personality and natural language processors are available
        let naturalResponse = aiResponse.response;
        
        if (typeof mayaPersonality !== 'undefined' && typeof mayaNaturalLanguage !== 'undefined') {
            // Adjust Maya's personality based on interaction
            mayaPersonality.adjustMoodBasedOnInteraction(
                aiResponse.sentiment, 
                aiResponse.complexity, 
                mayaConversationContext.communication_style
            );
            
            // Make response more natural and conversational
            naturalResponse = mayaNaturalLanguage.makeResponseConversational(aiResponse.response, {
                complexity: aiResponse.complexity,
                intent: aiResponse.intent,
                conversation_depth: mayaConversationStep
            });
            
            // Apply personality adjustments
            naturalResponse = mayaPersonality.getPersonalityAdjustedResponse(naturalResponse, {
                mood: mayaPersonality.currentMood,
                energy: mayaPersonality.energyLevel
            });
        }
        
        // Add thinking indicator for more complex responses or randomly for naturalness
        if (aiResponse.complexity > 0.7 || (Math.random() > 0.7 && aiResponse.complexity > 0.4)) {
            const thinkingMessage = (typeof mayaNaturalLanguage !== 'undefined') ? 
                mayaNaturalLanguage.addThinkingDelay() : 
                "Let me think about that...";
            addMayaMessage(thinkingMessage, false);
            
            setTimeout(() => {
                addMayaMessage(naturalResponse);
                
                // Show intelligent suggestions
                if (aiResponse.suggestions && aiResponse.suggestions.length > 0) {
                    showMayaSmartSuggestions(aiResponse.suggestions);
                }
                
                // Ask follow-up questions if appropriate
                if (aiResponse.follow_up_questions && aiResponse.follow_up_questions.length > 0) {
                    setTimeout(() => {
                        const followUp = aiResponse.follow_up_questions[0];
                        // Make follow-up more natural
                        const naturalFollowUp = Math.random() > 0.5 ? 
                            "Oh, and " + followUp.toLowerCase() :
                            "By the way, " + followUp.toLowerCase();
                        addMayaMessage(naturalFollowUp);
                    }, 2000);
                }
            }, Math.random() * 1000 + 1000); // Variable delay for naturalness
        } else {
            // Immediate response for simple queries, but add slight delay for naturalness
            setTimeout(() => {
                addMayaMessage(naturalResponse);
                
                if (aiResponse.suggestions && aiResponse.suggestions.length > 0) {
                    showMayaSmartSuggestions(aiResponse.suggestions);
                }
            }, Math.random() * 500 + 300);
        }
        
        // Update conversation context
        mayaConversationContext.last_intent = aiResponse.intent;
        mayaConversationContext.last_sentiment = aiResponse.sentiment;
        mayaConversationContext.entities = aiResponse.entities;
        
        // Log conversation for learning
        logAdvancedConversation(userMessage, aiResponse);
        
    } catch (error) {
        console.error('Maya AI Error:', error);
        // Fallback to simple knowledge base matching
        addMayaMessage("I heard you! Let me help with that...");
        setTimeout(() => {
            const simpleResponse = getSimpleResponse(userMessage);
            addMayaMessage(simpleResponse);
            showMayaQuickActions();
        }, 800);
    }
}

// Process using learning engine with failsafe
async function processWithLearningEngine(userMessage) {
    try {
        // Show intelligent processing indicator
        
        // Add timeout to prevent infinite loading
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 second timeout
        
        // Call learning engine
        const response = await fetch('maya/api/maya_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=generateIntelligentResponse&query=${encodeURIComponent(userMessage)}&context=${encodeURIComponent(JSON.stringify({...mayaConversationContext, chatgpt_mode: true}))}`,
            signal: controller.signal
        });
        
        clearTimeout(timeoutId);
        
        const data = await response.json();
        
        if (data.success) {
            // Add intelligent response
            setTimeout(() => {
                addMayaMessage(data.response);
                
                // Store response for feedback learning
                mayaResponseHistory.push({
                    query: userMessage,
                    response: data.response,
                    timestamp: Date.now(),
                    learning_applied: data.learning_applied
                });
                
                // Show feedback options
                showFeedbackOptions(userMessage, data.response);
                
                // Show learned insights if available
                if (data.insights && Object.keys(data.insights).length > 0) {
                    setTimeout(() => {
                        showLearningInsights(data.insights);
                    }, 2000);
                }
                
                showMayaQuickActions();
            }, 1500);
            
        } else {
            console.warn('Learning engine failed, using simplified response');
            // Use simplified fallback instead of recursive call
            const fallbackResponse = getSimplifiedMayaResponse(userMessage);
            setTimeout(() => {
                addMayaMessage(fallbackResponse);
                showMayaQuickActions();
            }, 500);
        }
        
    } catch (error) {
        console.error('Learning engine error:', error);
        
        // Check if it's a timeout error
        if (error.name === 'AbortError') {
            setTimeout(() => {
                addMayaMessage("⏰ <strong>Sorry for the delay!</strong><br>My advanced AI is taking longer than usual. Let me give you a quick response:<br><br>" + getSimplifiedMayaResponse(userMessage));
                showMayaQuickActions();
            }, 500);
        } else {
            // Use simplified fallback for other errors
            setTimeout(() => {
                addMayaMessage(getSimplifiedMayaResponse(userMessage));
                showMayaQuickActions();
            }, 500);
        }
    }
}

// Simplified Maya Response System (Backup)
function getSimplifiedMayaResponse(query) {
    const queryLower = query.toLowerCase();
    
    // Room-related queries
    if (queryLower.includes("room") || queryLower.includes("available")) {
        return `Great question about our rooms! 🏨<br><br>We have several excellent options:<br>• <strong>Eatonville Room</strong> - KES 3,500/night<br>• <strong>Merit Room</strong> - KES 4,000/night<br><br>Both include free WiFi, parking, and 24/7 service. Which interests you more?`;
    }
    
    // Pricing queries
    if (queryLower.includes("price") || queryLower.includes("cost") || queryLower.includes("rate")) {
        return `Our room rates are very competitive! 💰<br><br>• <strong>Eatonville Room:</strong> KES 3,500 per night<br>• <strong>Merit Room:</strong> KES 4,000 per night<br><br>Both rates include all amenities and no deposit required. Would you like to book one?`;
    }
    
    // Booking queries
    if (queryLower.includes("book") || queryLower.includes("reserve")) {
        return `I'd love to help you with your booking! 📅<br><br>Here's how easy it is:<br>1. Choose your preferred room<br>2. Select your dates<br>3. Confirm your details<br>4. Pay on arrival (no deposit needed)<br><br>Which room would you like to book?`;
    }
    
    // General greetings
    if (queryLower.includes("hello") || queryLower.includes("hi") || queryLower.includes("hey")) {
        return `Hello! Welcome to Orlando International Resorts! 👋<br><br>I'm Maya, your AI assistant. I'm here to help you with:<br>• Room bookings and availability<br>• Pricing information<br>• Hotel amenities and services<br>• Local recommendations<br><br>What can I help you with today?`;
    }
    
    // Default response
    return `I'm here to help you with anything about Orlando International Resorts. I can assist with:<br><br>🏨 <strong>Room Information</strong><br>💰 <strong>Pricing Details</strong><br>📅 <strong>Booking Assistance</strong><br>🌟 <strong>Hotel Services</strong><br><br>What would you like to know more about?`;
}

function getSimpleResponse(userMessage) {
    const messageLower = userMessage.toLowerCase();
    
    // Debug: Show that Maya received the message
    console.log('Maya received:', userMessage);
    
    // Handle simple greetings and confirmations first
    if (messageLower.includes('hi') || messageLower.includes('hello') || messageLower.includes('hey')) {
        return "Hey there! 👋 I'm Maya, and I'm so glad you're here! I can help you with rooms, pricing, booking, and anything else about our hotel. What would you like to know?";
    }
    
    if (messageLower.includes('what is happening') || messageLower.includes('what\'s happening')) {
        return "Great question! I'm here and ready to help you with anything about Orlando International Resorts! I can show you our rooms, check pricing, help with bookings, or answer any questions you have. What would you like to explore first?";
    }
    
    // Check for database-driven responses first
    if (messageLower.includes('room') || messageLower.includes('available') || messageLower.includes('book')) {
        return getDynamicRoomResponse(messageLower);
    }
    
    // Handle "list all" or "show all" queries specifically
    if ((messageLower.includes('list') && messageLower.includes('all')) || 
        (messageLower.includes('show') && messageLower.includes('all')) ||
        messageLower.includes('all rooms') || messageLower.includes('all available')) {
        return getDynamicRoomResponse('list all rooms');
    }
    
    if (messageLower.includes('price') || messageLower.includes('cost') || messageLower.includes('rate')) {
        return getDynamicPricingResponse(messageLower);
    }
    
    if (messageLower.includes('availability') || messageLower.includes('dates')) {
        return getDynamicAvailabilityResponse(messageLower);
    }
    
    // Check against knowledge base for other queries
    for (const knowledge of mayaKnowledgeBase) {
        const keywords = knowledge.question_keywords.split(',');
        for (const keyword of keywords) {
            if (messageLower.includes(keyword.trim())) {
                return knowledge.response_template;
            }
        }
    }
    
    // Enhanced fallback responses for common phrases
    if (messageLower.includes('show') && (messageLower.includes('room') || messageLower.includes('available'))) {
        return "🏨 <strong>Our Available Rooms:</strong><br><br>" +
               "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #0f2453;'>🏷️ Eatonville Room</strong><br>" +
               "💰 KES 3,500/night<br>" +
               "👥 Perfect for 1-2 guests<br>" +
               "✅ Free WiFi & Parking<br>" +
               "✅ 24/7 Room Service<br>" +
               "📱 M-Pesa Payment<br>" +
               "</div>" +
               "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #856404;'>⭐ Merit Room</strong><br>" +
               "💰 KES 4,000/night<br>" +
               "👥 Spacious for 1-3 guests<br>" +
               "✅ Premium amenities<br>" +
               "✅ Enhanced comfort<br>" +
               "🌟 Our signature room<br>" +
               "</div>" +
               "🎯 Which room would you like to book?";
    }
    
    if (messageLower.includes('room') && (messageLower.includes('details') || messageLower.includes('comparison') || messageLower.includes('compare'))) {
        return "📊 <strong>Detailed Room Comparison:</strong><br><br>" +
               "<table style='width: 100%; border-collapse: collapse;'>" +
               "<tr style='background: #f8f9fa;'><th style='padding: 10px; text-align: left;'>Feature</th><th style='padding: 10px;'>Eatonville</th><th style='padding: 10px;'>Merit</th></tr>" +
               "<tr><td style='padding: 8px;'><strong>Price/Night</strong></td><td style='padding: 8px;'>KES 3,500</td><td style='padding: 8px;'>KES 4,000</td></tr>" +
               "<tr style='background: #f8f9fa;'><td style='padding: 8px;'><strong>Capacity</strong></td><td style='padding: 8px;'>1-2 guests</td><td style='padding: 8px;'>1-3 guests</td></tr>" +
               "<tr><td style='padding: 8px;'><strong>Size</strong></td><td style='padding: 8px;'>Standard</td><td style='padding: 8px;'>Spacious</td></tr>" +
               "<tr style='background: #f8f9fa;'><td style='padding: 8px;'><strong>Best For</strong></td><td style='padding: 8px;'>Budget-conscious</td><td style='padding: 8px;'>Comfort seekers</td></tr>" +
               "</table><br>" +
               "🏆 <strong>Both include:</strong> Free WiFi, Parking, 24/7 Service, M-Pesa Payment<br><br>" +
               "💡 Which room fits your needs better?";
    }
    
    if (messageLower.includes('show me') || messageLower.includes('tell me') || messageLower.includes('display')) {
        return "💰 <strong>Here's what I can show you:</strong><br><br>" +
               "🏷️ <strong>Room Rates:</strong><br>" +
               "• <strong>Eatonville:</strong> KES 3,500/night<br>" +
               "• <strong>Merit:</strong> KES 4,000/night<br><br>" +
               "📊 <strong>Pricing includes:</strong><br>" +
               "✅ Free WiFi • ✅ Parking • ✅ 24/7 Service<br>" +
               "✅ M-Pesa Payment • ✅ Security • ✅ Housekeeping<br><br>" +
               "🎯 Which room interests you most?";
    }
    
    if (messageLower.includes('respond') || messageLower.includes('answer') || messageLower.includes('why')) {
        return "💬 I'm absolutely here and responding! I can help you with:<br><br>" +
               "🏨 <strong>Room Information</strong> - Details about accommodations<br>" +
               "💰 <strong>Pricing</strong> - Current rates and offers<br>" +
               "📅 <strong>Availability</strong> - Real-time room status<br>" +
               "🚀 <strong>Booking</strong> - Reserve your stay<br><br>" +
               "What specific information would you like?";
    }
    
    if (messageLower.includes('price') || messageLower.includes('cost') || messageLower.includes('rate')) {
        return "💰 <strong>Our Current Rates:</strong><br><br>" +
               "🏨 <strong>Eatonville:</strong> KES 3,500/night<br>" +
               "⭐ <strong>Merit:</strong> KES 4,000/night<br><br>" +
               "💡 <strong>Special Offers:</strong><br>" +
               "• Weekday discount: 5% off<br>" +
               "• Extended stay (3+ nights): 10% off<br>" +
               "• Same-day booking: 5% off<br><br>" +
               "📅 What dates work for you?";
    }
    
    // Handle simple affirmative responses
    if (messageLower === 'yes' || messageLower === 'sure' || messageLower === 'ok' || messageLower === 'okay') {
        return "🏨 <strong>Perfect! Here are our available rooms:</strong><br><br>" +
               "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #0f2453;'>🏷️ Eatonville Room</strong><br>" +
               "💰 KES 3,500/night<br>" +
               "👥 Perfect for 1-2 guests<br>" +
               "✅ All standard amenities included<br>" +
               "</div>" +
               "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #856404;'>⭐ Merit Room</strong><br>" +
               "💰 KES 4,000/night<br>" +
               "👥 Spacious for 1-3 guests<br>" +
               "✅ Premium features & enhanced comfort<br>" +
               "</div>" +
               "🚀 Ready to book one of these rooms?";
    }
    
    // Handle direct requests for room info
    if (messageLower.includes('available') || messageLower.includes('rooms are') || messageLower.includes('what rooms')) {
        return "🏨 <strong>Our Available Rooms:</strong><br><br>" +
               "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #0f2453;'>🏷️ Eatonville Room</strong><br>" +
               "💰 KES 3,500/night • 👥 1-2 guests<br>" +
               "✅ Free WiFi • ✅ Parking • ✅ 24/7 Service<br>" +
               "</div>" +
               "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
               "<strong style='color: #856404;'>⭐ Merit Room</strong><br>" +
               "💰 KES 4,000/night • 👥 1-3 guests<br>" +
               "✅ Premium amenities • ✅ Enhanced comfort<br>" +
               "</div>" +
               "📅 Both rooms are currently available! Which one interests you?";
    }
    
    // Generic helpful response
    return `✨ I received your message: "${userMessage}"<br><br>I'm here to help with Orlando International Resorts! I can assist with:<br><br>` +
           "🏨 Room details and comparisons<br>" +
           "💰 Pricing and special offers<br>" +
           "📅 Availability checking<br>" +
           "🚀 Booking assistance<br><br>" +
           "What would you like to know more about?";
}

// Show feedback options for learning
function showFeedbackOptions(userQuery, mayaResponse) {
    const feedbackContainer = document.createElement('div');
    feedbackContainer.style.cssText = `
        background: #f8f9fa;
        padding: 10px;
        border-radius: 8px;
        margin: 10px 0;
        border-left: 4px solid #28a745;
        font-size: 12px;
    `;
    
    feedbackContainer.innerHTML = `
        <div style="margin-bottom: 8px; font-weight: 600; color: #28a745;">
            🎯 Help Maya Learn: Was this response helpful?
        </div>
        <div style="display: flex; gap: 8px;">
            <button onclick="recordFeedback('${userQuery}', '${mayaResponse}', 'helpful')" style="
                background: #28a745; color: white; border: none; padding: 5px 10px; 
                border-radius: 4px; font-size: 11px; cursor: pointer;
            ">👍 Helpful</button>
            <button onclick="recordFeedback('${userQuery}', '${mayaResponse}', 'neutral')" style="
                background: #6c757d; color: white; border: none; padding: 5px 10px; 
                border-radius: 4px; font-size: 11px; cursor: pointer;
            ">😐 Neutral</button>
            <button onclick="recordFeedback('${userQuery}', '${mayaResponse}', 'unhelpful')" style="
                background: #dc3545; color: white; border: none; padding: 5px 10px; 
                border-radius: 4px; font-size: 11px; cursor: pointer;
            ">👎 Not Helpful</button>
        </div>
    `;
    
    const chatContainer = document.getElementById('mayaChatMessages');
    chatContainer.appendChild(feedbackContainer);
    chatContainer.scrollTop = chatContainer.scrollHeight;
    
    // Auto-hide after 30 seconds
    setTimeout(() => {
        if (feedbackContainer.parentNode) {
            feedbackContainer.remove();
        }
    }, 30000);
}

// Record user feedback
async function recordFeedback(userQuery, mayaResponse, satisfaction) {
    try {
        const response = await fetch('maya/api/maya_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `action=record_feedback&user_query=${encodeURIComponent(userQuery)}&maya_response=${encodeURIComponent(mayaResponse)}&user_satisfaction=${satisfaction}&response_type=learning_engine`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Show thank you message
            addMayaMessage(`✨ Thank you for the feedback! I'm ${satisfaction === 'helpful' ? 'learning and improving' : 'noting this to improve'} based on your input.`);
            
            // Remove feedback buttons
            const feedbackElements = document.querySelectorAll('[onclick*="recordFeedback"]');
            feedbackElements.forEach(el => {
                if (el.parentNode && el.parentNode.parentNode) {
                    el.parentNode.parentNode.remove();
                }
            });
        }
        
    } catch (error) {
        console.error('Error recording feedback:', error);
    }
}

// Show learning insights
function showLearningInsights(insights) {
    let insightMessage = "🧠 <strong>AI Learning Insights:</strong><br><br>";
    
    if (insights.booking_patterns) {
        const patterns = insights.booking_patterns;
        if (patterns.popular_check_in_days) {
            const popularDay = Object.keys(patterns.popular_check_in_days)[0];
            insightMessage += `📊 <strong>Pattern Discovery:</strong> ${popularDay}s are the most popular check-in day!<br>`;
        }
        
        if (patterns.average_stay_duration) {
            const avgStay = Math.round(patterns.average_stay_duration);
            insightMessage += `📈 <strong>Stay Trend:</strong> Guests typically stay ${avgStay} nights.<br>`;
        }
    }
    
    if (insights.room_preferences) {
        insightMessage += `🏨 <strong>Room Analytics:</strong> I've analyzed guest preferences to make better recommendations.<br>`;
    }
    
    insightMessage += "<br>💡 <em>I use this data to provide more personalized assistance!</em>";
    
    addMayaMessage(insightMessage);
}

// Dynamic database-driven response functions
function getDynamicRoomResponse(messageLower) {
    // This will be populated with real data via AJAX call
    fetchLiveRoomData().then(data => {
        if (data.success) {
            displayLiveRoomData(data.rooms);
        } else {
            // Fallback to static response
            addMayaMessage(getStaticRoomResponse());
        }
    }).catch(() => {
        addMayaMessage(getStaticRoomResponse());
    });
    
    // Return immediate response while loading
    return "🔄 <strong>Fetching live room data...</strong><br><br>Please wait while I get the most current room availability and pricing information from our system!";
}

function getDynamicPricingResponse(messageLower) {
    fetchLivePricingData().then(data => {
        if (data.success) {
            displayLivePricingData(data.pricing);
        } else {
            addMayaMessage(getStaticPricingResponse());
        }
    }).catch(() => {
        addMayaMessage(getStaticPricingResponse());
    });
    
    return "💰 <strong>Loading current pricing...</strong><br><br>Let me fetch today's rates with any applicable discounts!";
}

function getDynamicAvailabilityResponse(messageLower) {
    fetchLiveAvailabilityData().then(data => {
        if (data.success) {
            displayLiveAvailabilityData(data.availability);
        } else {
            addMayaMessage(getStaticAvailabilityResponse());
        }
    }).catch(() => {
        addMayaMessage(getStaticAvailabilityResponse());
    });
    
    return "📅 <strong>Checking availability...</strong><br><br>I'm looking up real-time room availability for you!";
}

// AJAX functions to fetch live data
async function fetchLiveRoomData() {
    try {
        const response = await fetch('maya/api/maya_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_live_rooms'
        });
        return await response.json();
    } catch (error) {
        console.error('Error fetching room data:', error);
        throw error;
    }
}

async function fetchLivePricingData() {
    try {
        const response = await fetch('maya/api/maya_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_live_pricing'
        });
        return await response.json();
    } catch (error) {
        console.error('Error fetching pricing data:', error);
        throw error;
    }
}

async function fetchLiveAvailabilityData() {
    try {
        const response = await fetch('maya/api/maya_conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=get_live_availability'
        });
        return await response.json();
    } catch (error) {
        console.error('Error fetching availability data:', error);
        throw error;
    }
}

// Display functions for live data
function displayLiveRoomData(rooms) {
    let response = "🏨 <strong>Live Room Information:</strong><br><br>";
    
    rooms.forEach(room => {
        const statusColor = room.current_status === 'available' ? '#28a745' : '#dc3545';
        const statusIcon = room.current_status === 'available' ? '✅' : '🚫';
        
        response += `<div style='background: #f8f9fa; padding: 15px; border-radius: 10px; margin: 10px 0; border-left: 4px solid ${statusColor};'>`;
        response += `<strong style='color: ${statusColor};'>${statusIcon} ${room.room_name}</strong><br>`;
        response += `💰 KES ${parseFloat(room.base_price).toLocaleString()}/night<br>`;
        response += `📊 Status: ${room.current_status || 'Available'}<br>`;
        response += `🧹 Cleaning: ${room.cleaning_status || 'Clean'}<br>`;
        if (room.description) {
            response += `📝 ${room.description}<br>`;
        }
        response += `</div>`;
    });
    
    response += "<br>🎯 Which room would you like to book?";
    addMayaMessage(response);
    showMayaQuickActions();
}

function displayLivePricingData(pricing) {
    let response = "💰 <strong>Current Live Pricing:</strong><br><br>";
    
    const today = new Date();
    const isWeekend = today.getDay() === 0 || today.getDay() === 6;
    
    response += `📅 <strong>Today (${today.toLocaleDateString()}):</strong> ${isWeekend ? 'Weekend Rates' : 'Weekday Rates'}<br><br>`;
    
    pricing.forEach(room => {
        const discountBadge = room.discount_applied ? '🏷️ 5% OFF' : (room.is_weekend ? '⬆️ +15%' : '');
        
        response += `<div style='background: ${room.discount_applied ? '#d4edda' : '#fff3cd'}; padding: 12px; border-radius: 8px; margin: 8px 0;'>`;
        response += `<strong>${room.room_name}</strong> ${discountBadge}<br>`;
        response += `💵 <strong>Current Price: KES ${parseFloat(room.current_price).toLocaleString()}</strong><br>`;
        response += `<small>Base Rate: KES ${parseFloat(room.base_price).toLocaleString()}</small>`;
        response += `</div>`;
    });
    
    response += "<br>📞 Prices include all taxes and fees. Ready to book?";
    addMayaMessage(response);
    showMayaQuickActions();
}

function displayLiveAvailabilityData(availability) {
    let response = "📅 <strong>Real-Time Availability:</strong><br><br>";
    
    if (availability.length === 0) {
        response += "🎉 <strong>Great news!</strong> All our rooms are currently available!<br><br>";
        response += "✅ <strong>Eatonville</strong> - Ready for immediate booking<br>";
        response += "✅ <strong>Merit</strong> - Ready for immediate booking<br><br>";
        response += "🚀 Perfect time to make your reservation!";
    } else {
        response += "📊 <strong>Current Bookings:</strong><br><br>";
        
        const groupedByRoom = {};
        availability.forEach(booking => {
            if (!groupedByRoom[booking.TRoom]) {
                groupedByRoom[booking.TRoom] = [];
            }
            groupedByRoom[booking.TRoom].push(booking);
        });
        
        Object.keys(groupedByRoom).forEach(roomName => {
            response += `<strong>🏨 ${roomName}:</strong><br>`;
            groupedByRoom[roomName].forEach(booking => {
                const checkIn = new Date(booking.cin).toLocaleDateString();
                const checkOut = new Date(booking.cout).toLocaleDateString();
                response += `• ${checkIn} - ${checkOut} (${booking.stat})<br>`;
            });
            response += "<br>";
        });
        
        response += "💡 I can help you find available dates around these bookings!";
    }
    
    addMayaMessage(response);
    showMayaQuickActions();
}

// Static fallback responses
function getStaticRoomResponse() {
    return "🏨 <strong>Our Available Rooms:</strong><br><br>" +
           "<div style='background: #e8f4fd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
           "<strong style='color: #0f2453;'>🏷️ Eatonville Room</strong><br>" +
           "💰 KES 3,500/night<br>" +
           "👥 Perfect for 1-2 guests<br>" +
           "✅ All standard amenities included<br>" +
           "</div>" +
           "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; margin: 10px 0;'>" +
           "<strong style='color: #856404;'>⭐ Merit Room</strong><br>" +
           "💰 KES 4,000/night<br>" +
           "👥 Spacious for 1-3 guests<br>" +
           "✅ Premium features & enhanced comfort<br>" +
           "</div>" +
           "🚀 Ready to book one of these rooms?";
}

function getStaticPricingResponse() {
    return "💰 <strong>Our Current Rates:</strong><br><br>" +
           "🏨 <strong>Eatonville:</strong> KES 3,500/night<br>" +
           "⭐ <strong>Merit:</strong> KES 4,000/night<br><br>" +
           "💡 <strong>Special Offers:</strong><br>" +
           "• Weekday discount: 5% off<br>" +
           "• Extended stay (3+ nights): 10% off<br>" +
           "• Same-day booking: 5% off<br><br>" +
           "📅 What dates work for you?";
}

function getStaticAvailabilityResponse() {
    return "📅 <strong>Availability Status:</strong><br><br>" +
           "✅ <strong>Today:</strong> Both rooms available<br>" +
           "✅ <strong>This Weekend:</strong> Limited availability<br>" +
           "✅ <strong>Next Week:</strong> Excellent availability<br><br>" +
           "🗓️ What specific dates are you considering?";
}

function showMayaQuickActions() {
    const quickActionsContainer = document.getElementById('mayaQuickActions');
    quickActionsContainer.innerHTML = '';
    
    mayaQuickActions.forEach(action => {
        const button = document.createElement('button');
        button.textContent = (action.action_emoji || '') + ' ' + action.action_label;
        button.style.cssText = `
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #0f2453;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
        `;
        
        button.onmouseover = () => {
            button.style.background = 'linear-gradient(135deg, #ffce14, #ffd700)';
            button.style.transform = 'translateY(-2px)';
        };
        
        button.onmouseout = () => {
            button.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
            button.style.transform = 'translateY(0)';
        };
        
        button.onclick = () => handleMayaQuickAction(action);
        quickActionsContainer.appendChild(button);
    });
}

function showMayaQuickActionsForCategory(category) {
    const categoryActions = mayaQuickActions.filter(action => 
        action.target_category === category || action.target_category === 'all'
    );
    
    if (categoryActions.length > 0) {
        const quickActionsContainer = document.getElementById('mayaQuickActions');
        quickActionsContainer.innerHTML = '';
        
        categoryActions.forEach(action => {
            const button = document.createElement('button');
            button.textContent = (action.action_emoji || '') + ' ' + action.action_label;
            button.style.cssText = `
                background: linear-gradient(135deg, #f8f9fa, #e9ecef);
                color: #0f2453;
                border: 1px solid #dee2e6;
                padding: 8px 16px;
                border-radius: 20px;
                font-size: 13px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
                margin: 2px;
            `;
            
            button.onmouseover = () => {
                button.style.background = 'linear-gradient(135deg, #ffce14, #ffd700)';
                button.style.transform = 'translateY(-2px)';
            };
            
            button.onmouseout = () => {
                button.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
                button.style.transform = 'translateY(0)';
            };
            
            button.onclick = () => handleMayaQuickAction(action);
            quickActionsContainer.appendChild(button);
        });
    }
}

function handleMayaQuickAction(action) {
    // Simulate user clicking action
    addMayaMessage(action.action_label, true);
    
    // Process the action
    setTimeout(() => {
        switch(action.action_key) {
            case 'check_rooms':
                if (window.location.pathname.includes('rooms.php')) {
                    addMayaMessage("🏨 You're already on our rooms page! I can help you with any specific room questions or booking assistance.");
                } else {
                    addMayaMessage("🏨 Let me take you to our rooms page where you can see all available accommodations!");
                    setTimeout(() => {
                        window.location.href = 'rooms.php';
                    }, 2000);
                }
                break;
                
            case 'view_menu':
                addMayaMessage("🍽️ Let me show you our delicious restaurant menu!");
                setTimeout(() => {
                    window.location.href = 'modules/guest/menu/menu_enhanced.php';
                }, 2000);
                break;
                
            case 'book_now':
                addMayaMessage("🚀 Perfect! Let me guide you to our booking page where you can make a reservation.");
                setTimeout(() => {
                    window.location.href = 'modules/guest/booking/booking_form.php';
                }, 2000);
                break;
                
            default:
                // Find matching knowledge for this action
                const knowledge = mayaKnowledgeBase.find(k => k.category === action.target_category);
                if (knowledge) {
                    addMayaMessage(knowledge.response_template);
                } else {
                    addMayaMessage("✨ Thanks for that! How else can I help you today?");
                }
        }
        
        showMayaQuickActions();
    }, 500);
}

function updateMayaStatus(status) {
    const statusElement = document.getElementById('mayaStatus');
    const avatar = document.getElementById('mayaAvatar');
    
    switch(status) {
        case 'typing':
            statusElement.textContent = 'Maya is typing...';
            avatar.innerHTML = '💭';
            break;
        case 'thinking':
            statusElement.textContent = 'Thinking...';
            avatar.innerHTML = '🤔';
            break;
        case 'online':
            statusElement.textContent = mayaRole;
            avatar.innerHTML = mayaAvatarEmoji;
            break;
    }
}

function logMayaConversation(userMessage) {
    // This would normally send to server to log in database
    // For now, we'll just log to console
    console.log('Maya Conversation:', {
        session: mayaCurrentSession,
        user_message: userMessage,
        timestamp: new Date().toISOString(),
        page: window.location.pathname
    });
}

function updateKnowledgeUsage(knowledgeId) {
    // This would normally update the usage count in database
    console.log('Knowledge used:', knowledgeId);
}

// Room-specific Maya chat opener
function openMayaChatForRoom(roomName, roomPrice) {
    openMayaChat();
    
    // Set context for room booking
    mayaUserPreferences.selectedRoom = roomName;
    mayaUserPreferences.selectedPrice = roomPrice;
    
    // Clear existing messages and start room-specific conversation
    setTimeout(() => {
        document.getElementById('mayaChatMessages').innerHTML = '';
                addMayaMessage(`Hi! I'm Maya, and I'd be happy to help you with ${roomName}.`);

        setTimeout(() => {
            addMayaMessage(`This room is KES ${new Intl.NumberFormat().format(roomPrice)} per night. I can help you check availability, learn about amenities, or start your booking right away!`);
            
            // Show room-specific quick actions
            const roomActions = [
                {action_key: 'room_availability', action_label: 'Check Availability', action_emoji: '📅', target_category: 'availability'},
                {action_key: 'room_amenities', action_label: 'Room Amenities', action_emoji: '✨', target_category: 'amenities'},
                {action_key: 'room_book_now', action_label: 'Book This Room', action_emoji: '🚀', target_category: 'booking_help'},
                {action_key: 'room_pricing', action_label: 'Pricing Details', action_emoji: '💰', target_category: 'pricing'}
            ];
            
            showRoomSpecificActions(roomActions);
        }, 1500);
    }, 500);
}

function showRoomSpecificActions(actions) {
    const quickActionsContainer = document.getElementById('mayaQuickActions');
    quickActionsContainer.innerHTML = '';
    
    actions.forEach(action => {
        const button = document.createElement('button');
        button.textContent = (action.action_emoji || '') + ' ' + action.action_label;
        button.style.cssText = `
            background: linear-gradient(135deg, #f8f9fa, #e9ecef);
            color: #0f2453;
            border: 1px solid #dee2e6;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 2px;
        `;
        
        button.onmouseover = () => {
            button.style.background = 'linear-gradient(135deg, #ffce14, #ffd700)';
            button.style.transform = 'translateY(-2px)';
        };
        
        button.onmouseout = () => {
            button.style.background = 'linear-gradient(135deg, #f8f9fa, #e9ecef)';
            button.style.transform = 'translateY(0)';
        };
        
        button.onclick = () => handleRoomSpecificAction(action);
        quickActionsContainer.appendChild(button);
    });
}

function handleRoomSpecificAction(action) {
    addMayaMessage(action.action_label, true);
    
    setTimeout(() => {
        switch(action.action_key) {
            case 'room_availability':
                addMayaMessage(`📅 Let me check availability for ${mayaUserPreferences.selectedRoom}! Based on current data:<br><br>🟢 <strong>Tonight:</strong> Available<br>🟡 <strong>This Weekend:</strong> Limited availability<br>🟢 <strong>Next Week:</strong> Excellent availability<br><br>What dates work best for you?`);
                break;
                
            case 'room_amenities':
                addMayaMessage(`✨ ${mayaUserPreferences.selectedRoom} includes all our standard amenities plus:<br><br>🔌 Free High-Speed WiFi<br>🚗 Complimentary Parking<br>🍽️ 24/7 Room Service<br>📱 M-Pesa Payment (No fees)<br>🔒 24/7 Security<br>🧹 Daily Housekeeping<br>❄️ Air Conditioning<br>📺 Cable TV<br><br>Perfect for a comfortable stay!`);
                break;
                
            case 'room_book_now':
                addMayaMessage(`🚀 Excellent choice! I'll help you book ${mayaUserPreferences.selectedRoom} right away. Let me take you to our booking form with all the details pre-filled!`);
                setTimeout(() => {
                    window.location.href = `modules/guest/booking/booking_form.php?room=${encodeURIComponent(mayaUserPreferences.selectedRoom)}&ai=maya`;
                }, 2000);
                break;
                
            case 'room_pricing':
                const weekendPrice = Math.round(mayaUserPreferences.selectedPrice * 1.15);
                const weekdayPrice = Math.round(mayaUserPreferences.selectedPrice * 0.95);
                addMayaMessage(`💰 Here's the complete pricing for ${mayaUserPreferences.selectedRoom}:<br><br>📅 <strong>Weekdays:</strong> KES ${weekdayPrice.toLocaleString()}<br>📅 <strong>Weekends:</strong> KES ${weekendPrice.toLocaleString()}<br>📅 <strong>Base Rate:</strong> KES ${mayaUserPreferences.selectedPrice.toLocaleString()}<br><br>💡 <strong>Special:</strong> No deposit required - pay on arrival!`);
                break;
        }
        
        showMayaQuickActions();
    }, 500);
}

// Initialize Maya AI after all classes are loaded
function initializeMayaAI() {
    try {
        mayaAdvancedAI = new MayaAdvancedProcessor();
        mayaNaturalLanguage = new MayaNaturalProcessor();
        mayaPersonality = new MayaPersonality();
        console.log('Maya AI initialized successfully');
    } catch (error) {
        console.error('Maya AI initialization error:', error);
        // Set fallback mode
        mayaAdvancedAI = null;
        mayaNaturalLanguage = null;
        mayaPersonality = null;
    }
}

// Show notification badge after page load
window.addEventListener('load', function() {
    // Initialize Maya AI
    initializeMayaAI();
    
    setTimeout(() => {
        document.getElementById('mayaNotificationBadge').style.display = 'flex';
    }, 3000);
});

// Close modal when clicking outside
document.addEventListener('click', function(event) {
    const modal = document.getElementById('mayaChatModal');
    if (event.target === modal) {
        closeMayaChat();
    }
});

function showMayaSmartSuggestions(suggestions) {
    const quickActionsContainer = document.getElementById('mayaQuickActions');
    quickActionsContainer.innerHTML = '';
    quickActionsContainer.style.display = 'flex'; // Show when suggestions are provided

    suggestions.forEach(suggestion => {
        const button = document.createElement('button');
        button.textContent = suggestion.text;
        button.style.cssText = `
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin: 3px;
            box-shadow: 0 2px 8px rgba(23, 162, 184, 0.3);
        `;

        button.onmouseover = () => {
            button.style.transform = 'translateY(-2px) scale(1.05)';
            button.style.boxShadow = '0 4px 15px rgba(23, 162, 184, 0.4)';
        };

        button.onmouseout = () => {
            button.style.transform = 'translateY(0) scale(1)';
            button.style.boxShadow = '0 2px 8px rgba(23, 162, 184, 0.3)';
        };

        button.onclick = () => handleMayaSmartAction(suggestion);
        quickActionsContainer.appendChild(button);
    });
}

function handleMayaSmartAction(suggestion) {
    addMayaMessage(suggestion.text, true);

    setTimeout(() => {
        switch(suggestion.action) {
            case 'show_tonight_rooms':
                addMayaMessage("🌙 <strong>Tonight's Available Rooms:</strong><br><br>✅ <strong>Eatonville</strong> - KES 3,325 (5% off tonight!)<br>✅ <strong>Merit</strong> - KES 3,800 (5% off tonight!)<br><br>Both rooms are ready for immediate check-in. Shall I book one for you?");
                break;
                
            case 'show_best_rates':
                addMayaMessage("💰 <strong>Best Rate Analysis:</strong><br><br>🏆 <strong>Best Value:</strong> Eatonville weekdays (KES 3,325)<br>⭐ <strong>Premium Deal:</strong> Merit weekdays (KES 3,800)<br>🎯 <strong>Weekend Special:</strong> Any room for 3+ nights = 10% extra off<br><br>When would you like to stay?");
                break;
                
            case 'detailed_comparison':
                addMayaMessage("📊 <strong>Advanced Room Analysis:</strong><br><br><strong>Eatonville Highlights:</strong><br>• Best value proposition<br>• Perfect for business travelers<br>• 95% guest satisfaction<br>• Quick WiFi (100+ Mbps)<br><br><strong>Merit Advantages:</strong><br>• 20% more space<br>• Premium bathroom fixtures<br>• Enhanced soundproofing<br>• Luxury bedding collection<br><br>Which features matter most to you?");
                break;
                
            case 'budget_optimizer':
                addMayaMessage("🎯 <strong>Budget Optimization Mode:</strong><br><br>Tell me your target budget, and I'll show you:<br>• Best dates for that budget<br>• Room upgrade opportunities<br>• Hidden savings and discounts<br>• Value-add inclusions<br><br>What's your preferred spending range?");
                break;
                
            case 'price_calendar':
                addMayaMessage("📅 <strong>Price Calendar View:</strong><br><br><strong>This Week:</strong><br>Mon-Thu: Eatonville KES 3,325 | Merit KES 3,800<br>Fri-Sat: Eatonville KES 4,025 | Merit KES 4,600<br>Sun: Eatonville KES 3,325 | Merit KES 3,800<br><br><strong>Best Deals:</strong><br>🎯 Weekdays: Save up to 15%<br>⭐ 3+ nights: Extra 10% off<br><br>Which dates work for you?");
                break;
                
            case 'show_discounts':
                addMayaMessage("💸 <strong>Available Discounts:</strong><br><br>🕐 <strong>Early Bird:</strong> Book 7+ days ahead = 5% off<br>📅 <strong>Extended Stay:</strong> 3+ nights = 10% off<br>🌙 <strong>Last Minute:</strong> Same day booking = 5% off<br>💰 <strong>Weekday Special:</strong> Sun-Thu = 5% off<br>🎉 <strong>New Guest:</strong> First booking = Welcome gift<br><br>Which discount interests you most?");
                break;
                
            default:
                addMayaMessage("✨ Let me help you with that! What specific information would you like me to provide?");
        }
        
        setTimeout(() => {
            showMayaQuickActions();
        }, 2000);
    }, 800);
}

function logAdvancedConversation(userMessage, aiResponse) {
    const logData = {
        user_message: userMessage,
        ai_response: aiResponse.response,
        intent: aiResponse.intent,
        sentiment: aiResponse.sentiment,
        entities: JSON.stringify(aiResponse.entities),
        complexity: aiResponse.complexity,
        suggestions_shown: JSON.stringify(aiResponse.suggestions),
        session_id: mayaCurrentSession,
        agent_id: 1,
        timestamp: new Date().toISOString()
    };
    
    console.log('Maya Advanced Conversation:', logData);
    
    // Send to learning system
    fetch('maya/api/maya_learning.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(logData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.learned) {
            console.log('Maya learned from this interaction:', data.insights);
        }
    })
    .catch(error => console.log('Learning system error:', error));
    
    if (typeof logMayaConversation === 'function') {
        logMayaConversation(userMessage, aiResponse.response);
    }
}

// Feedback system for Maya responses
function rateMayaResponse(rating, buttonElement) {
    // Get the message text from the parent div
    const messageDiv = buttonElement.closest('div').parentElement;
    const messageText = messageDiv.querySelector('div').textContent;
    
    // Send feedback to learning system
    fetch('maya/api/maya_feedback.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            message: messageText,
            rating: rating,
            session_id: mayaCurrentSession,
            timestamp: new Date().toISOString()
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button to show feedback was received
            const feedbackDiv = buttonElement.parentElement;
            feedbackDiv.innerHTML = `
                <span style="color: ${rating === 'positive' ? '#28a745' : '#dc3545'}; font-size: 0.7em;">
                    ${rating === 'positive' ? '✅ Thanks for the feedback!' : '📝 Thanks! We\'ll improve this response.'}
                </span>
            `;
            
            // If negative feedback, trigger learning
            if (rating === 'negative') {
                console.log('Maya received negative feedback - learning triggered');
            }
        }
    })
    .catch(error => {
        console.log('Feedback system error:', error);
        buttonElement.style.opacity = '0.5';
        buttonElement.textContent = 'Error';
    });
}
</script>

<!-- Load Dynamic Room Data -->
<script src="js/maya_dynamic_rooms.js"></script>
