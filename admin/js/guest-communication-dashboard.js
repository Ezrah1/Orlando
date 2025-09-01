/**
 * Orlando International Resorts - Guest Communication Dashboard
 * Interactive dashboard for managing guest communications and concierge services
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class GuestCommunicationDashboard {
    constructor() {
        this.currentThread = null;
        this.threads = [];
        this.refreshInterval = null;
        this.messageTemplates = {};
        this.filters = {
            status: 'all',
            priority: 'all',
            staff: 'all'
        };
        
        this.init();
    }

    /**
     * Initialize the dashboard
     */
    async init() {
        console.log('[GuestCommDashboard] Initializing...');
        
        // Load initial data
        await this.loadInitialData();
        
        // Setup real-time updates
        this.setupRealTimeUpdates();
        
        // Setup UI event handlers
        this.setupEventHandlers();
        
        // Load message templates
        this.loadMessageTemplates();
        
        // Setup keyboard shortcuts
        this.setupKeyboardShortcuts();
        
        // Start auto-refresh
        this.startAutoRefresh();
        
        console.log('[GuestCommDashboard] Initialization complete');
    }

    /**
     * Load initial dashboard data
     */
    async loadInitialData() {
        try {
            // Load threads, stats, and active guests in parallel
            const [threadsResponse, statsResponse, guestsResponse] = await Promise.all([
                this.fetchThreads(),
                this.fetchStats(),
                this.fetchActiveGuests()
            ]);

            if (threadsResponse.success) {
                this.threads = threadsResponse.threads;
                this.renderThreadsList();
            }

            if (statsResponse.success) {
                this.updateDashboardStats(statsResponse.stats);
            }

            if (guestsResponse.success) {
                this.populateGuestSelect(guestsResponse.guests);
            }

        } catch (error) {
            console.error('Failed to load initial data:', error);
            this.showAlert('Failed to load dashboard data', 'error');
        }
    }

    /**
     * Fetch communication threads
     */
    async fetchThreads() {
        const params = new URLSearchParams({
            action: 'threads',
            status: this.filters.status,
            priority: this.filters.priority,
            staff_id: this.filters.staff,
            limit: 50
        });

        const response = await fetch(`api/guest-communication.php?${params}`);
        return await response.json();
    }

    /**
     * Fetch communication statistics
     */
    async fetchStats() {
        const response = await fetch('api/guest-communication.php?action=stats&days=7');
        return await response.json();
    }

    /**
     * Fetch active guests
     */
    async fetchActiveGuests() {
        const response = await fetch('api/guest-communication.php?action=active_guests');
        return await response.json();
    }

    /**
     * Render threads list
     */
    renderThreadsList() {
        const container = document.getElementById('threadsList');
        
        if (this.threads.length === 0) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-comments fa-3x"></i>
                    <h4>No Conversations</h4>
                    <p>No active guest conversations found.</p>
                    <button class="btn btn-primary" onclick="showNewThreadModal()">
                        Start New Conversation
                    </button>
                </div>
            `;
            return;
        }

        const threadsHtml = this.threads.map(thread => this.renderThreadItem(thread)).join('');
        container.innerHTML = threadsHtml;
    }

    /**
     * Render individual thread item
     */
    renderThreadItem(thread) {
        const timeAgo = this.formatTimeAgo(thread.updated_at);
        const priorityClass = `priority-${thread.priority}`;
        const vipBadge = thread.vip_status ? `<span class="vip-badge">${thread.vip_status.toUpperCase()}</span>` : '';
        
        return `
            <div class="thread-item" onclick="selectThread('${thread.thread_id}')" data-thread-id="${thread.thread_id}">
                <div class="thread-header">
                    <span class="guest-name">${thread.full_name} ${vipBadge}</span>
                    <span class="thread-time">${timeAgo}</span>
                </div>
                <div class="thread-preview">
                    Room ${thread.room_number} • ${thread.message_count} messages
                </div>
                <div class="thread-meta">
                    <span class="priority-badge ${priorityClass}">${thread.priority}</span>
                    <span class="status-badge status-${thread.status}">${thread.status}</span>
                </div>
            </div>
        `;
    }

    /**
     * Select and load a thread
     */
    async selectThread(threadId) {
        try {
            // Update UI to show selected thread
            document.querySelectorAll('.thread-item').forEach(item => {
                item.classList.remove('active');
            });
            document.querySelector(`[data-thread-id="${threadId}"]`)?.classList.add('active');

            // Show loading state
            this.showConversationLoading();

            // Fetch thread details
            const response = await fetch(`api/guest-communication.php?action=thread&thread_id=${threadId}`);
            const result = await response.json();

            if (result.success) {
                this.currentThread = result.data;
                this.renderConversation();
                this.renderGuestInfo();
                this.showMessageInput();
            } else {
                this.showAlert('Failed to load conversation', 'error');
            }

        } catch (error) {
            console.error('Failed to select thread:', error);
            this.showAlert('Failed to load conversation', 'error');
        }
    }

    /**
     * Render conversation messages
     */
    renderConversation() {
        const container = document.getElementById('conversationContent');
        const titleElement = document.getElementById('conversationTitle');
        const actionsElement = document.getElementById('conversationActions');

        // Update header
        titleElement.innerHTML = `
            <i class="fas fa-comment-dots"></i>
            ${this.currentThread.full_name} - Room ${this.currentThread.room_number}
        `;
        actionsElement.style.display = 'flex';

        // Render messages
        const messagesHtml = this.currentThread.messages.map(message => this.renderMessage(message)).join('');
        
        container.innerHTML = `
            <div class="messages-container">
                ${messagesHtml}
            </div>
        `;

        // Scroll to bottom
        container.scrollTop = container.scrollHeight;
    }

    /**
     * Render individual message
     */
    renderMessage(message) {
        const timeAgo = this.formatTimeAgo(message.created_at);
        const senderClass = message.sender_type;
        const avatar = this.getMessageAvatar(message);
        
        return `
            <div class="message ${senderClass}">
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    <div class="message-bubble">
                        ${this.formatMessageContent(message.message, message.metadata)}
                    </div>
                    <div class="message-time">${timeAgo} • ${message.sender_name || 'System'}</div>
                </div>
            </div>
        `;
    }

    /**
     * Get message avatar based on sender type
     */
    getMessageAvatar(message) {
        switch (message.sender_type) {
            case 'guest':
                return '<i class="fas fa-user"></i>';
            case 'staff':
                return '<i class="fas fa-user-tie"></i>';
            case 'system':
                return '<i class="fas fa-robot"></i>';
            default:
                return '<i class="fas fa-comment"></i>';
        }
    }

    /**
     * Format message content with metadata
     */
    formatMessageContent(message, metadata) {
        let content = this.escapeHtml(message);
        
        // Handle special message types based on metadata
        if (metadata && metadata.type) {
            switch (metadata.type) {
                case 'room_service_confirmation':
                    content += `<div class="message-attachment">
                        <i class="fas fa-utensils"></i>
                        Room Service Order #${metadata.order_id}
                    </div>`;
                    break;
                case 'housekeeping_confirmation':
                    content += `<div class="message-attachment">
                        <i class="fas fa-broom"></i>
                        Housekeeping Task #${metadata.task_id}
                    </div>`;
                    break;
                case 'maintenance_confirmation':
                    content += `<div class="message-attachment">
                        <i class="fas fa-tools"></i>
                        Maintenance Ticket #${metadata.ticket_id}
                    </div>`;
                    break;
            }
        }
        
        return content;
    }

    /**
     * Render guest information panel
     */
    renderGuestInfo() {
        const container = document.getElementById('guestInfoContent');
        const guest = this.currentThread;
        
        container.innerHTML = `
            <div class="guest-profile">
                <div class="guest-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="guest-details">
                    <h4>${guest.full_name}</h4>
                    ${guest.vip_status ? `<span class="vip-badge">${guest.vip_status.toUpperCase()}</span>` : ''}
                </div>
            </div>
            
            <div class="guest-info-grid">
                <div class="info-item">
                    <label>Room</label>
                    <span>${guest.room_number} (${guest.room_type})</span>
                </div>
                <div class="info-item">
                    <label>Email</label>
                    <span>${guest.email || 'Not provided'}</span>
                </div>
                <div class="info-item">
                    <label>Phone</label>
                    <span>${guest.phone || 'Not provided'}</span>
                </div>
                <div class="info-item">
                    <label>Language</label>
                    <span>${guest.language || 'English'}</span>
                </div>
                <div class="info-item">
                    <label>Check-in</label>
                    <span>${this.formatDate(guest.check_in_date)}</span>
                </div>
                <div class="info-item">
                    <label>Check-out</label>
                    <span>${this.formatDate(guest.check_out_date)}</span>
                </div>
            </div>
            
            <div class="guest-actions">
                <button class="btn btn-sm btn-outline-primary" onclick="viewGuestHistory('${guest.guest_id}')">
                    <i class="fas fa-history"></i> History
                </button>
                <button class="btn btn-sm btn-outline-primary" onclick="viewRoomDetails('${guest.room_number}')">
                    <i class="fas fa-bed"></i> Room Details
                </button>
            </div>
        `;
    }

    /**
     * Send message
     */
    async sendMessage() {
        const input = document.getElementById('messageInput');
        const message = input.value.trim();
        
        if (!message || !this.currentThread) {
            return;
        }

        try {
            // Disable input
            input.disabled = true;
            
            // Send message
            const response = await fetch('api/guest-communication.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'send_message',
                    thread_id: this.currentThread.thread_id,
                    message: message,
                    sender_type: 'staff'
                })
            });

            const result = await response.json();

            if (result.success) {
                // Clear input
                input.value = '';
                
                // Reload conversation
                await this.selectThread(this.currentThread.thread_id);
                
                // Update thread in list
                await this.refreshThreadsList();
                
            } else {
                this.showAlert('Failed to send message', 'error');
            }

        } catch (error) {
            console.error('Failed to send message:', error);
            this.showAlert('Failed to send message', 'error');
        } finally {
            input.disabled = false;
            input.focus();
        }
    }

    /**
     * Show new thread modal
     */
    showNewThreadModal() {
        $('#newThreadModal').modal('show');
    }

    /**
     * Create new thread
     */
    async createNewThread() {
        const form = document.getElementById('newThreadForm');
        const formData = new FormData(form);
        
        try {
            const response = await fetch('api/guest-communication.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'create_thread',
                    guest_id: document.getElementById('guestSelect').value,
                    room_number: document.getElementById('roomNumber').value,
                    channel: document.getElementById('channel').value,
                    initial_message: document.getElementById('initialMessage').value
                })
            });

            const result = await response.json();

            if (result.success) {
                $('#newThreadModal').modal('hide');
                form.reset();
                
                // Refresh threads list
                await this.refreshThreadsList();
                
                // Select new thread
                await this.selectThread(result.thread_id);
                
                this.showAlert('New conversation started', 'success');
            } else {
                this.showAlert('Failed to create conversation', 'error');
            }

        } catch (error) {
            console.error('Failed to create thread:', error);
            this.showAlert('Failed to create conversation', 'error');
        }
    }

    /**
     * Update dashboard statistics
     */
    updateDashboardStats(stats) {
        // Header stats
        document.getElementById('activeThreadsCount').textContent = stats.active_threads || 0;
        document.getElementById('avgResponseTime').textContent = `${stats.avg_response_time || 0}m`;
        document.getElementById('satisfactionScore').textContent = `${stats.satisfaction_score || 0}%`;

        // Detailed stats
        document.getElementById('totalThreads').textContent = stats.active_threads + stats.resolved_threads || 0;
        document.getElementById('avgResponseTimeDetailed').textContent = `${stats.avg_response_time || 0}m`;
        document.getElementById('satisfactionScoreDetailed').textContent = `${stats.satisfaction_score || 0}%`;
        document.getElementById('escalationsCount').textContent = stats.escalations_count || 0;

        // Update intent chart
        if (stats.common_intents) {
            this.updateIntentChart(stats.common_intents);
        }
    }

    /**
     * Update intent analysis chart
     */
    updateIntentChart(intents) {
        const ctx = document.getElementById('intentChart').getContext('2d');
        
        if (this.intentChart) {
            this.intentChart.destroy();
        }

        this.intentChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: intents.map(intent => this.formatIntentLabel(intent.intent)),
                datasets: [{
                    data: intents.map(intent => intent.count),
                    backgroundColor: [
                        '#667eea',
                        '#f093fb',
                        '#4facfe',
                        '#43e97b',
                        '#38f9d7'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 15
                        }
                    }
                }
            }
        });
    }

    /**
     * Format intent label for display
     */
    formatIntentLabel(intent) {
        return intent.replace(/_/g, ' ')
                    .replace(/\b\w/g, l => l.toUpperCase());
    }

    /**
     * Setup real-time updates using WebSocket or Server-Sent Events
     */
    setupRealTimeUpdates() {
        // For now, use polling. In production, implement WebSocket
        this.startAutoRefresh();
    }

    /**
     * Setup event handlers
     */
    setupEventHandlers() {
        // Message input enter key
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    this.sendMessage();
                }
            });
        }

        // Filter changes
        document.getElementById('statusFilter').addEventListener('change', () => this.filterThreads());
        document.getElementById('priorityFilter').addEventListener('change', () => this.filterThreads());
        document.getElementById('staffFilter').addEventListener('change', () => this.filterThreads());
    }

    /**
     * Setup keyboard shortcuts
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + N for new thread
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                this.showNewThreadModal();
            }
            
            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.refreshDashboard();
            }
            
            // Escape to close modals
            if (e.key === 'Escape') {
                $('.modal').modal('hide');
            }
        });
    }

    /**
     * Start auto-refresh timer
     */
    startAutoRefresh() {
        this.refreshInterval = setInterval(() => {
            this.refreshThreadsList();
            this.fetchStats().then(result => {
                if (result.success) {
                    this.updateDashboardStats(result.stats);
                }
            });
        }, 30000); // Refresh every 30 seconds
    }

    /**
     * Filter threads based on current filters
     */
    async filterThreads() {
        this.filters.status = document.getElementById('statusFilter').value;
        this.filters.priority = document.getElementById('priorityFilter').value;
        this.filters.staff = document.getElementById('staffFilter').value;

        const result = await this.fetchThreads();
        if (result.success) {
            this.threads = result.threads;
            this.renderThreadsList();
        }
    }

    /**
     * Refresh threads list
     */
    async refreshThreadsList() {
        const result = await this.fetchThreads();
        if (result.success) {
            this.threads = result.threads;
            this.renderThreadsList();
        }
    }

    /**
     * Refresh entire dashboard
     */
    async refreshDashboard() {
        await this.loadInitialData();
        this.showAlert('Dashboard refreshed', 'success');
    }

    /**
     * Populate guest select dropdown
     */
    populateGuestSelect(guests) {
        const select = document.getElementById('guestSelect');
        select.innerHTML = '<option value="">Select a guest...</option>';
        
        guests.forEach(guest => {
            const option = document.createElement('option');
            option.value = guest.id;
            option.textContent = `${guest.full_name} - Room ${guest.room_number}`;
            if (guest.vip_status) {
                option.textContent += ` (${guest.vip_status.toUpperCase()})`;
            }
            select.appendChild(option);
        });
    }

    /**
     * Show conversation loading state
     */
    showConversationLoading() {
        const container = document.getElementById('conversationContent');
        container.innerHTML = `
            <div class="loading-state">
                <div class="loading-spinner"></div>
                <p>Loading conversation...</p>
            </div>
        `;
    }

    /**
     * Show message input area
     */
    showMessageInput() {
        document.getElementById('messageInputArea').style.display = 'block';
    }

    /**
     * Show alert message
     */
    showAlert(message, type = 'info') {
        // Use the global AdminUtils alert function if available
        if (window.AdminUtils && window.AdminUtils.showAlert) {
            window.AdminUtils.showAlert(message, type);
        } else {
            alert(message);
        }
    }

    /**
     * Format time ago
     */
    formatTimeAgo(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diffInSeconds = Math.floor((now - date) / 1000);

        if (diffInSeconds < 60) return 'Just now';
        if (diffInSeconds < 3600) return `${Math.floor(diffInSeconds / 60)}m ago`;
        if (diffInSeconds < 86400) return `${Math.floor(diffInSeconds / 3600)}h ago`;
        return `${Math.floor(diffInSeconds / 86400)}d ago`;
    }

    /**
     * Format date
     */
    formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', {
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
    }

    /**
     * Escape HTML
     */
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    /**
     * Load message templates
     */
    loadMessageTemplates() {
        this.messageTemplates = {
            greeting1: "Thank you for contacting us! How may I assist you today?",
            greeting2: "Good day! I'm here to help make your stay exceptional.",
            roomservice1: "I'd be happy to take your room service order. Our menu is available 24/7.",
            roomservice2: "Your room service order will be delivered within 30-45 minutes.",
            housekeeping1: "I've scheduled housekeeping for your room. They'll arrive within 2 hours.",
            housekeeping2: "Is there a preferred time for housekeeping service?"
        };
    }

    /**
     * Insert template into message input
     */
    insertTemplate(templateId) {
        const input = document.getElementById('messageInput');
        const template = this.messageTemplates[templateId];
        
        if (template && input) {
            input.value = template;
            input.focus();
        }
        
        $('#quickResponseModal').modal('hide');
    }

    /**
     * Cleanup when destroying the dashboard
     */
    destroy() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
        }
        
        if (this.intentChart) {
            this.intentChart.destroy();
        }
    }
}

// Global functions for onclick handlers
let guestCommDashboard;

function selectThread(threadId) {
    guestCommDashboard.selectThread(threadId);
}

function sendMessage() {
    guestCommDashboard.sendMessage();
}

function showNewThreadModal() {
    guestCommDashboard.showNewThreadModal();
}

function createNewThread() {
    guestCommDashboard.createNewThread();
}

function refreshDashboard() {
    guestCommDashboard.refreshDashboard();
}

function filterThreads() {
    guestCommDashboard.filterThreads();
}

function insertTemplate(templateId) {
    guestCommDashboard.insertTemplate(templateId);
}

function showQuickResponse() {
    $('#quickResponseModal').modal('show');
}

function markResolved() {
    if (guestCommDashboard.currentThread) {
        // Implementation for marking thread as resolved
        console.log('Mark resolved:', guestCommDashboard.currentThread.thread_id);
    }
}

function escalateThread() {
    if (guestCommDashboard.currentThread) {
        // Implementation for escalating thread
        console.log('Escalate thread:', guestCommDashboard.currentThread.thread_id);
    }
}

function assignStaff() {
    if (guestCommDashboard.currentThread) {
        // Implementation for assigning staff
        console.log('Assign staff:', guestCommDashboard.currentThread.thread_id);
    }
}

// Quick action handlers
function createRoomServiceOrder() {
    console.log('Create room service order');
}

function scheduleHousekeeping() {
    console.log('Schedule housekeeping');
}

function createMaintenanceTicket() {
    console.log('Create maintenance ticket');
}

function makeReservation() {
    console.log('Make reservation');
}

function sendBroadcast() {
    console.log('Send broadcast');
}

function viewGuestHistory(guestId) {
    console.log('View guest history:', guestId);
}

function viewRoomDetails(roomNumber) {
    console.log('View room details:', roomNumber);
}

function exportThreads() {
    console.log('Export threads');
}

function insertQuickResponse() {
    showQuickResponse();
}

function attachFile() {
    console.log('Attach file');
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    guestCommDashboard = new GuestCommunicationDashboard();
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (guestCommDashboard) {
        guestCommDashboard.destroy();
    }
});
