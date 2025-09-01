/**
 * Orlando International Resorts - Operations Dashboard Manager
 * Real-time operations monitoring with live updates and interactive controls
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class OperationsDashboard {
    constructor(options = {}) {
        this.options = {
            refreshInterval: options.refreshInterval || 30000, // 30 seconds
            enableRealTime: options.enableRealTime !== false,
            enableNotifications: options.enableNotifications !== false,
            autoRefresh: options.autoRefresh !== false,
            debug: options.debug || false,
            ...options
        };
        
        this.isActive = false;
        this.refreshTimer = null;
        this.websocketConnection = null;
        this.operationsData = {};
        this.selectedRoom = null;
        this.selectedFloor = null;
        this.filterState = {
            status: 'all',
            floor: 'all',
            department: 'all'
        };
        
        this.init();
    }
    
    /**
     * Initialize operations dashboard
     */
    init() {
        this.log('Initializing Operations Dashboard...');
        
        // Setup UI components
        this.setupUI();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Connect to real-time updates
        if (this.options.enableRealTime) {
            this.connectWebSocket();
        }
        
        // Load initial data
        this.loadOperationsData();
        
        // Start auto-refresh
        if (this.options.autoRefresh) {
            this.startAutoRefresh();
        }
        
        this.isActive = true;
        this.log('Operations Dashboard initialized');
    }
    
    /**
     * Setup UI components
     */
    setupUI() {
        // Initialize room status grid
        this.initializeRoomGrid();
        
        // Initialize staff coordination panel
        this.initializeStaffPanel();
        
        // Initialize task management
        this.initializeTaskManagement();
        
        // Initialize filters
        this.initializeFilters();
        
        // Initialize real-time indicators
        this.initializeRealtimeIndicators();
    }
    
    /**
     * Initialize room status grid
     */
    initializeRoomGrid() {
        const roomGrid = document.getElementById('room-status-grid');
        if (!roomGrid) return;
        
        // Create room grid container
        roomGrid.innerHTML = `
            <div class="room-grid-header">
                <div class="floor-selector">
                    <label>Floor:</label>
                    <select id="floor-filter" class="form-control">
                        <option value="all">All Floors</option>
                        <option value="1">Floor 1</option>
                        <option value="2">Floor 2</option>
                        <option value="3">Floor 3</option>
                        <option value="4">Floor 4</option>
                    </select>
                </div>
                <div class="status-legend">
                    <span class="legend-item available">Available</span>
                    <span class="legend-item occupied">Occupied</span>
                    <span class="legend-item cleaning">Cleaning</span>
                    <span class="legend-item maintenance">Maintenance</span>
                    <span class="legend-item reserved">Reserved</span>
                </div>
            </div>
            <div class="room-grid-container" id="room-grid-container">
                <div class="loading-spinner">Loading rooms...</div>
            </div>
        `;
    }
    
    /**
     * Initialize staff coordination panel
     */
    initializeStaffPanel() {
        const staffPanel = document.getElementById('staff-coordination');
        if (!staffPanel) return;
        
        staffPanel.innerHTML = `
            <div class="staff-header">
                <h4>Staff Coordination</h4>
                <div class="staff-controls">
                    <button class="btn btn-primary btn-sm" onclick="operationsDashboard.showAssignTaskModal()">
                        <i class="fas fa-plus"></i> Assign Task
                    </button>
                    <button class="btn btn-info btn-sm" onclick="operationsDashboard.refreshStaffData()">
                        <i class="fas fa-sync"></i> Refresh
                    </button>
                </div>
            </div>
            <div class="staff-content">
                <div class="staff-summary" id="staff-summary">
                    <div class="loading-spinner">Loading staff data...</div>
                </div>
                <div class="staff-assignments" id="staff-assignments">
                    <h5>Active Assignments</h5>
                    <div class="assignment-list" id="assignment-list"></div>
                </div>
            </div>
        `;
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Room selection
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('room-card')) {
                this.selectRoom(e.target.dataset.roomId);
            }
        });
        
        // Floor filter
        const floorFilter = document.getElementById('floor-filter');
        if (floorFilter) {
            floorFilter.addEventListener('change', (e) => {
                this.filterState.floor = e.target.value;
                this.filterRooms();
            });
        }
        
        // Status filter
        const statusFilter = document.getElementById('status-filter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filterState.status = e.target.value;
                this.filterRooms();
            });
        }
        
        // Quick action buttons
        document.addEventListener('click', (e) => {
            if (e.target.dataset.action) {
                this.handleQuickAction(e.target.dataset.action, e.target.dataset);
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey || e.metaKey) {
                switch (e.key) {
                    case 'r':
                        e.preventDefault();
                        this.refreshAll();
                        break;
                    case 'f':
                        e.preventDefault();
                        this.focusSearch();
                        break;
                }
            }
        });
    }
    
    /**
     * Load operations data
     */
    async loadOperationsData() {
        try {
            this.showLoading(true);
            
            // Load dashboard data
            const dashboardData = await this.fetchData('/admin/api/operations-api.php?action=dashboard');
            this.operationsData.dashboard = dashboardData;
            
            // Load room status
            const roomData = await this.fetchData('/admin/api/operations-api.php?action=room_status');
            this.operationsData.rooms = roomData;
            
            // Load staff coordination
            const staffData = await this.fetchData('/admin/api/operations-api.php?action=staff_coordination');
            this.operationsData.staff = staffData;
            
            // Update UI
            this.updateDashboard();
            
        } catch (error) {
            this.log('Error loading operations data:', error);
            this.showError('Failed to load operations data');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Update dashboard with latest data
     */
    updateDashboard() {
        this.updateRoomGrid();
        this.updateStaffPanel();
        this.updateOperationsMetrics();
        this.updateAlerts();
    }
    
    /**
     * Update room status grid
     */
    updateRoomGrid() {
        const container = document.getElementById('room-grid-container');
        if (!container || !this.operationsData.rooms) return;
        
        const rooms = this.operationsData.rooms.rooms || [];
        
        container.innerHTML = rooms.map(room => this.renderRoomCard(room)).join('');
        
        // Update summary
        this.updateRoomSummary(this.operationsData.rooms.summary);
    }
    
    /**
     * Render individual room card
     */
    renderRoomCard(room) {
        const statusClass = `room-${room.status}`;
        const isSelected = this.selectedRoom == room.id;
        
        return `
            <div class="room-card ${statusClass} ${isSelected ? 'selected' : ''}" 
                 data-room-id="${room.id}" 
                 data-status="${room.status}">
                <div class="room-header">
                    <span class="room-number">${room.room_number}</span>
                    <span class="room-type">${room.room_type}</span>
                </div>
                <div class="room-status">
                    <span class="status-badge ${statusClass}">${this.formatStatus(room.status)}</span>
                </div>
                <div class="room-details">
                    ${room.current_guest ? `<div class="guest-info">Guest: ${room.current_guest}</div>` : ''}
                    ${room.next_arrival ? `<div class="next-arrival">Arrival: ${this.formatTime(room.next_arrival)}</div>` : ''}
                    ${room.next_departure ? `<div class="next-departure">Departure: ${this.formatTime(room.next_departure)}</div>` : ''}
                </div>
                <div class="room-actions">
                    ${this.renderRoomActions(room)}
                </div>
                ${room.maintenance_issues ? `<div class="maintenance-alert"><i class="fas fa-wrench"></i> Maintenance Required</div>` : ''}
            </div>
        `;
    }
    
    /**
     * Render room action buttons
     */
    renderRoomActions(room) {
        const actions = [];
        
        switch (room.status) {
            case 'available':
                actions.push(`<button class="btn-action btn-reserve" data-action="reserve" data-room-id="${room.id}">Reserve</button>`);
                actions.push(`<button class="btn-action btn-maintenance" data-action="maintenance" data-room-id="${room.id}">Maintenance</button>`);
                break;
                
            case 'occupied':
                actions.push(`<button class="btn-action btn-checkout" data-action="checkout" data-room-id="${room.id}">Check Out</button>`);
                actions.push(`<button class="btn-action btn-service" data-action="service" data-room-id="${room.id}">Service</button>`);
                break;
                
            case 'cleaning':
                actions.push(`<button class="btn-action btn-inspect" data-action="inspect" data-room-id="${room.id}">Inspect</button>`);
                break;
                
            case 'maintenance':
                actions.push(`<button class="btn-action btn-complete" data-action="complete_maintenance" data-room-id="${room.id}">Complete</button>`);
                break;
        }
        
        return actions.join('');
    }
    
    /**
     * Update staff coordination panel
     */
    updateStaffPanel() {
        if (!this.operationsData.staff) return;
        
        const summaryContainer = document.getElementById('staff-summary');
        const assignmentContainer = document.getElementById('assignment-list');
        
        if (summaryContainer) {
            summaryContainer.innerHTML = this.renderStaffSummary(this.operationsData.staff);
        }
        
        if (assignmentContainer) {
            assignmentContainer.innerHTML = this.renderStaffAssignments(this.operationsData.staff.task_assignments || []);
        }
    }
    
    /**
     * Render staff summary
     */
    renderStaffSummary(staffData) {
        const onDuty = staffData.on_duty_staff || [];
        const departments = {};
        
        onDuty.forEach(staff => {
            if (!departments[staff.department]) {
                departments[staff.department] = { total: 0, available: 0, busy: 0 };
            }
            departments[staff.department].total++;
            if (staff.status === 'available') {
                departments[staff.department].available++;
            } else {
                departments[staff.department].busy++;
            }
        });
        
        return Object.entries(departments).map(([dept, stats]) => `
            <div class="department-summary">
                <h6>${dept}</h6>
                <div class="staff-stats">
                    <span class="stat total">Total: ${stats.total}</span>
                    <span class="stat available">Available: ${stats.available}</span>
                    <span class="stat busy">Busy: ${stats.busy}</span>
                </div>
            </div>
        `).join('');
    }
    
    /**
     * Handle quick actions
     */
    async handleQuickAction(action, data) {
        try {
            switch (action) {
                case 'reserve':
                    await this.reserveRoom(data.roomId);
                    break;
                    
                case 'checkout':
                    await this.checkoutRoom(data.roomId);
                    break;
                    
                case 'maintenance':
                    await this.scheduleMaintenan(data.roomId);
                    break;
                    
                case 'service':
                    await this.requestRoomService(data.roomId);
                    break;
                    
                case 'inspect':
                    await this.scheduleInspection(data.roomId);
                    break;
                    
                case 'complete_maintenance':
                    await this.completeMaintenance(data.roomId);
                    break;
                    
                default:
                    this.log('Unknown action:', action);
            }
        } catch (error) {
            this.log('Error handling action:', error);
            this.showError('Failed to execute action');
        }
    }
    
    /**
     * Update room status
     */
    async updateRoomStatus(roomId, newStatus, notes = '') {
        try {
            const response = await this.fetchData('/admin/api/operations-api.php?action=update_room_status', {
                method: 'POST',
                body: JSON.stringify({
                    room_id: roomId,
                    status: newStatus,
                    notes: notes
                })
            });
            
            if (response.success) {
                this.showSuccess('Room status updated successfully');
                this.refreshRoomData();
            } else {
                this.showError(response.error || 'Failed to update room status');
            }
        } catch (error) {
            this.log('Error updating room status:', error);
            this.showError('Failed to update room status');
        }
    }
    
    /**
     * Assign task to staff
     */
    async assignTask(staffId, taskData) {
        try {
            const response = await this.fetchData('/admin/api/operations-api.php?action=assign_staff', {
                method: 'POST',
                body: JSON.stringify({
                    staff_id: staffId,
                    ...taskData
                })
            });
            
            if (response.success) {
                this.showSuccess('Task assigned successfully');
                this.refreshStaffData();
            } else {
                this.showError(response.error || 'Failed to assign task');
            }
        } catch (error) {
            this.log('Error assigning task:', error);
            this.showError('Failed to assign task');
        }
    }
    
    /**
     * Create housekeeping task
     */
    async createHousekeepingTask(roomId, taskType, priority = 'normal') {
        try {
            const response = await this.fetchData('/admin/api/operations-api.php?action=create_housekeeping_task', {
                method: 'POST',
                body: JSON.stringify({
                    room_id: roomId,
                    task_type: taskType,
                    priority: priority
                })
            });
            
            if (response.success) {
                this.showSuccess('Housekeeping task created successfully');
                this.refreshOperationsData();
            } else {
                this.showError(response.error || 'Failed to create housekeeping task');
            }
        } catch (error) {
            this.log('Error creating housekeeping task:', error);
            this.showError('Failed to create housekeeping task');
        }
    }
    
    /**
     * Filter rooms based on current filter state
     */
    filterRooms() {
        const roomCards = document.querySelectorAll('.room-card');
        
        roomCards.forEach(card => {
            let show = true;
            
            // Filter by floor
            if (this.filterState.floor !== 'all') {
                const roomNumber = card.querySelector('.room-number').textContent;
                const floor = roomNumber.charAt(0);
                if (floor !== this.filterState.floor) {
                    show = false;
                }
            }
            
            // Filter by status
            if (this.filterState.status !== 'all') {
                if (card.dataset.status !== this.filterState.status) {
                    show = false;
                }
            }
            
            card.style.display = show ? 'block' : 'none';
        });
    }
    
    /**
     * Connect to WebSocket for real-time updates
     */
    connectWebSocket() {
        if (window.notificationManager && window.notificationManager.isConnected) {
            // Listen for room status changes
            window.notificationManager.on('room_status_changed', (data) => {
                this.handleRoomStatusChange(data);
            });
            
            // Listen for staff updates
            window.notificationManager.on('staff_assignment_changed', (data) => {
                this.handleStaffUpdate(data);
            });
            
            // Listen for housekeeping updates
            window.notificationManager.on('housekeeping_task_updated', (data) => {
                this.handleHousekeepingUpdate(data);
            });
        }
    }
    
    /**
     * Handle real-time room status change
     */
    handleRoomStatusChange(data) {
        const roomCard = document.querySelector(`[data-room-id="${data.room_id}"]`);
        if (roomCard) {
            // Update room card status
            roomCard.className = roomCard.className.replace(/room-\w+/, `room-${data.new_status}`);
            roomCard.dataset.status = data.new_status;
            
            // Update status badge
            const statusBadge = roomCard.querySelector('.status-badge');
            if (statusBadge) {
                statusBadge.textContent = this.formatStatus(data.new_status);
                statusBadge.className = `status-badge room-${data.new_status}`;
            }
            
            // Update actions
            const actionsContainer = roomCard.querySelector('.room-actions');
            if (actionsContainer) {
                actionsContainer.innerHTML = this.renderRoomActions({
                    id: data.room_id,
                    status: data.new_status
                });
            }
            
            // Show notification
            this.showSuccess(`Room ${data.room_number} status changed to ${this.formatStatus(data.new_status)}`);
        }
    }
    
    /**
     * Start auto-refresh timer
     */
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.refreshTimer = setInterval(() => {
            if (this.isActive && document.visibilityState === 'visible') {
                this.refreshOperationsData();
            }
        }, this.options.refreshInterval);
    }
    
    /**
     * Refresh operations data
     */
    async refreshOperationsData() {
        try {
            await this.loadOperationsData();
        } catch (error) {
            this.log('Error refreshing operations data:', error);
        }
    }
    
    /**
     * Utility methods
     */
    formatStatus(status) {
        const statusMap = {
            'available': 'Available',
            'occupied': 'Occupied',
            'cleaning': 'Cleaning',
            'maintenance': 'Maintenance',
            'reserved': 'Reserved',
            'inspection': 'Inspection'
        };
        return statusMap[status] || status;
    }
    
    formatTime(timeString) {
        if (!timeString) return '';
        const date = new Date(timeString);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    
    async fetchData(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };
        
        const response = await fetch(url, { ...defaultOptions, ...options });
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.error || 'Request failed');
        }
        
        return data.data;
    }
    
    showLoading(show) {
        const spinner = document.querySelector('.loading-spinner');
        if (spinner) {
            spinner.style.display = show ? 'block' : 'none';
        }
    }
    
    showSuccess(message) {
        this.showNotification(message, 'success');
    }
    
    showError(message) {
        this.showNotification(message, 'error');
    }
    
    showNotification(message, type = 'info') {
        // Integration with notification system
        if (window.notificationManager) {
            window.notificationManager.createNotification({
                title: 'Operations Update',
                message: message,
                type: type
            });
        } else {
            // Fallback to browser alert
            alert(message);
        }
    }
    
    log(...args) {
        if (this.options.debug) {
            console.log('[OperationsDashboard]', ...args);
        }
    }
    
    /**
     * Destroy dashboard
     */
    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.isActive = false;
        this.log('Operations Dashboard destroyed');
    }
}

// Modal and UI helper functions
function showAssignTaskModal() {
    // Implementation for task assignment modal
    const modal = document.getElementById('assign-task-modal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function showMaintenanceModal(roomId) {
    // Implementation for maintenance request modal
    const modal = document.getElementById('maintenance-modal');
    if (modal) {
        modal.dataset.roomId = roomId;
        modal.style.display = 'block';
    }
}

function showRoomDetailsModal(roomId) {
    // Implementation for room details modal
    const modal = document.getElementById('room-details-modal');
    if (modal) {
        modal.dataset.roomId = roomId;
        modal.style.display = 'block';
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('.operations-dashboard')) {
        window.operationsDashboard = new OperationsDashboard({
            debug: false,
            enableRealTime: true,
            refreshInterval: 30000
        });
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = OperationsDashboard;
}
