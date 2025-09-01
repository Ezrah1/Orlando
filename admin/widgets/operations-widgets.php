<?php
/**
 * Orlando International Resorts - Operations Manager Dashboard Widgets
 * Real-time operational monitoring and management widgets
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure proper authentication
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['user_id'])) {
    exit('Access denied');
}

// Verify operations access
$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['operations_manager', 'director', 'ceo', 'super_admin'])) {
    exit('Insufficient privileges');
}

/**
 * Real-time Room Status Widget
 */
function renderRoomStatusWidget($dashboard_manager) {
    $stats = $dashboard_manager->getStatsForRole();
    $room_status = $stats['room_status'] ?? [];
    ?>
    <div class="widget-card room-status-widget" data-widget="room_status" data-widget-type="status">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-bed"></i>
                Real-time Room Status
            </h3>
            <div class="widget-actions">
                <button class="btn-refresh" data-widget-refresh="room_status">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="widget-content">
            <div class="room-status-grid">
                <div class="status-item status-available">
                    <div class="status-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="status-data">
                        <div class="status-number"><?= $room_status['available'] ?? 0 ?></div>
                        <div class="status-label">Available</div>
                    </div>
                </div>
                
                <div class="status-item status-occupied">
                    <div class="status-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="status-data">
                        <div class="status-number"><?= $room_status['occupied'] ?? 0 ?></div>
                        <div class="status-label">Occupied</div>
                    </div>
                </div>
                
                <div class="status-item status-cleaning">
                    <div class="status-icon">
                        <i class="fas fa-broom"></i>
                    </div>
                    <div class="status-data">
                        <div class="status-number"><?= $room_status['cleaning'] ?? 0 ?></div>
                        <div class="status-label">Cleaning</div>
                    </div>
                </div>
                
                <div class="status-item status-maintenance">
                    <div class="status-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="status-data">
                        <div class="status-number"><?= $room_status['maintenance'] ?? 0 ?></div>
                        <div class="status-label">Maintenance</div>
                    </div>
                </div>
            </div>
            
            <div class="room-status-chart">
                <canvas id="roomStatusChart" width="300" height="200"></canvas>
            </div>
        </div>
        <div class="widget-loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('roomStatusChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Available', 'Occupied', 'Cleaning', 'Maintenance'],
                    datasets: [{
                        data: [
                            <?= $room_status['available'] ?? 0 ?>,
                            <?= $room_status['occupied'] ?? 0 ?>,
                            <?= $room_status['cleaning'] ?? 0 ?>,
                            <?= $room_status['maintenance'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            '#28a745',
                            '#007bff',
                            '#ffc107',
                            '#dc3545'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Housekeeping Tasks Widget
 */
function renderHousekeepingTasksWidget($dashboard_manager) {
    $stats = $dashboard_manager->getStatsForRole();
    $housekeeping = $stats['housekeeping'] ?? [];
    ?>
    <div class="widget-card housekeeping-tasks" data-widget="housekeeping_tasks" data-widget-type="list">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-clipboard-list"></i>
                Housekeeping Tasks
            </h3>
            <div class="widget-subtitle">Today's Progress</div>
        </div>
        <div class="widget-content">
            <div class="task-summary">
                <div class="summary-item">
                    <div class="summary-number"><?= $housekeeping['total_tasks'] ?? 0 ?></div>
                    <div class="summary-label">Total Tasks</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?= $housekeeping['completed'] ?? 0 ?></div>
                    <div class="summary-label">Completed</div>
                </div>
                <div class="summary-item">
                    <div class="summary-number"><?= $housekeeping['pending'] ?? 0 ?></div>
                    <div class="summary-label">Pending</div>
                </div>
            </div>
            
            <div class="task-progress">
                <div class="progress-bar">
                    <?php 
                    $total = $housekeeping['total_tasks'] ?? 1;
                    $completed = $housekeeping['completed'] ?? 0;
                    $percentage = $total > 0 ? round(($completed / $total) * 100) : 0;
                    ?>
                    <div class="progress-fill" style="width: <?= $percentage ?>%"></div>
                </div>
                <div class="progress-text"><?= $percentage ?>% Complete</div>
            </div>
            
            <div class="task-list">
                <div class="task-item task-priority-high">
                    <div class="task-room">Room 201</div>
                    <div class="task-type">Deep Cleaning</div>
                    <div class="task-status">In Progress</div>
                    <div class="task-time">15 min</div>
                </div>
                
                <div class="task-item task-priority-medium">
                    <div class="task-room">Room 105</div>
                    <div class="task-type">Standard Clean</div>
                    <div class="task-status">Pending</div>
                    <div class="task-time">30 min</div>
                </div>
                
                <div class="task-item task-priority-low">
                    <div class="task-room">Room 308</div>
                    <div class="task-type">Maintenance Check</div>
                    <div class="task-status">Scheduled</div>
                    <div class="task-time">45 min</div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Maintenance Requests Widget
 */
function renderMaintenanceRequestsWidget($dashboard_manager) {
    $stats = $dashboard_manager->getStatsForRole();
    $maintenance = $stats['maintenance'] ?? [];
    ?>
    <div class="widget-card maintenance-requests" data-widget="maintenance_requests" data-widget-type="list">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-wrench"></i>
                Maintenance Requests
            </h3>
            <div class="widget-actions">
                <button class="btn-new-request" onclick="openMaintenanceRequest()">
                    <i class="fas fa-plus"></i> New Request
                </button>
            </div>
        </div>
        <div class="widget-content">
            <div class="maintenance-summary">
                <div class="summary-card priority-high">
                    <div class="summary-icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="summary-data">
                        <div class="summary-number"><?= $maintenance['high_priority'] ?? 0 ?></div>
                        <div class="summary-label">High Priority</div>
                    </div>
                </div>
                
                <div class="summary-card priority-open">
                    <div class="summary-icon"><i class="fas fa-folder-open"></i></div>
                    <div class="summary-data">
                        <div class="summary-number"><?= $maintenance['open_requests'] ?? 0 ?></div>
                        <div class="summary-label">Open Requests</div>
                    </div>
                </div>
            </div>
            
            <div class="maintenance-list">
                <div class="maintenance-item priority-high">
                    <div class="maintenance-header">
                        <div class="maintenance-id">#MNT-001</div>
                        <div class="maintenance-priority">High</div>
                    </div>
                    <div class="maintenance-details">
                        <div class="maintenance-room">Room 501 - AC Unit</div>
                        <div class="maintenance-issue">Air conditioning not cooling properly</div>
                        <div class="maintenance-time">Reported 2 hours ago</div>
                    </div>
                    <div class="maintenance-actions">
                        <button class="btn-assign">Assign</button>
                        <button class="btn-details">Details</button>
                    </div>
                </div>
                
                <div class="maintenance-item priority-medium">
                    <div class="maintenance-header">
                        <div class="maintenance-id">#MNT-002</div>
                        <div class="maintenance-priority">Medium</div>
                    </div>
                    <div class="maintenance-details">
                        <div class="maintenance-room">Lobby - Light Fixture</div>
                        <div class="maintenance-issue">Flickering lights in main lobby</div>
                        <div class="maintenance-time">Reported 4 hours ago</div>
                    </div>
                    <div class="maintenance-actions">
                        <button class="btn-assign">Assign</button>
                        <button class="btn-details">Details</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Staff Schedule Widget
 */
function renderStaffScheduleWidget($dashboard_manager) {
    ?>
    <div class="widget-card staff-schedule" data-widget="staff_schedule" data-widget-type="calendar">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-calendar-alt"></i>
                Staff Schedule - Today
            </h3>
            <div class="widget-actions">
                <button class="btn-schedule" onclick="openScheduleManager()">
                    <i class="fas fa-edit"></i> Manage
                </button>
            </div>
        </div>
        <div class="widget-content">
            <div class="schedule-overview">
                <div class="shift-summary">
                    <div class="shift-item shift-morning">
                        <div class="shift-icon"><i class="fas fa-sun"></i></div>
                        <div class="shift-data">
                            <div class="shift-count">12</div>
                            <div class="shift-label">Morning Shift</div>
                            <div class="shift-time">6:00 AM - 2:00 PM</div>
                        </div>
                    </div>
                    
                    <div class="shift-item shift-afternoon">
                        <div class="shift-icon"><i class="fas fa-sun"></i></div>
                        <div class="shift-data">
                            <div class="shift-count">15</div>
                            <div class="shift-label">Afternoon Shift</div>
                            <div class="shift-time">2:00 PM - 10:00 PM</div>
                        </div>
                    </div>
                    
                    <div class="shift-item shift-night">
                        <div class="shift-icon"><i class="fas fa-moon"></i></div>
                        <div class="shift-data">
                            <div class="shift-count">8</div>
                            <div class="shift-label">Night Shift</div>
                            <div class="shift-time">10:00 PM - 6:00 AM</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="staff-alerts">
                <div class="alert-item alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span>2 staff members called in sick</span>
                </div>
                <div class="alert-item alert-info">
                    <i class="fas fa-info-circle"></i>
                    <span>3 new hires starting Monday</span>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Inventory Alerts Widget
 */
function renderInventoryAlertsWidget($dashboard_manager) {
    ?>
    <div class="widget-card inventory-alerts" data-widget="inventory_alerts" data-widget-type="list">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-boxes"></i>
                Inventory Alerts
            </h3>
        </div>
        <div class="widget-content">
            <div class="inventory-summary">
                <div class="alert-count alert-low">
                    <div class="alert-number">5</div>
                    <div class="alert-label">Low Stock</div>
                </div>
                <div class="alert-count alert-out">
                    <div class="alert-number">2</div>
                    <div class="alert-label">Out of Stock</div>
                </div>
            </div>
            
            <div class="inventory-list">
                <div class="inventory-item alert-critical">
                    <div class="item-icon"><i class="fas fa-toilet-paper"></i></div>
                    <div class="item-details">
                        <div class="item-name">Toilet Paper</div>
                        <div class="item-location">Housekeeping Storage</div>
                        <div class="item-status">Out of Stock</div>
                    </div>
                    <div class="item-action">
                        <button class="btn-order">Order Now</button>
                    </div>
                </div>
                
                <div class="inventory-item alert-warning">
                    <div class="item-icon"><i class="fas fa-shower"></i></div>
                    <div class="item-details">
                        <div class="item-name">Shampoo Bottles</div>
                        <div class="item-location">Room Amenities</div>
                        <div class="item-status">Low Stock (15 units)</div>
                    </div>
                    <div class="item-action">
                        <button class="btn-order">Order</button>
                    </div>
                </div>
                
                <div class="inventory-item alert-warning">
                    <div class="item-icon"><i class="fas fa-tshirt"></i></div>
                    <div class="item-details">
                        <div class="item-name">Clean Towels</div>
                        <div class="item-location">Laundry Department</div>
                        <div class="item-status">Low Stock (25 units)</div>
                    </div>
                    <div class="item-action">
                        <button class="btn-order">Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Operations Performance Widget
 */
function renderOperationsPerformanceWidget($dashboard_manager) {
    ?>
    <div class="widget-card operations-performance" data-widget="operations_performance" data-widget-type="chart">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-chart-bar"></i>
                Operations Performance
            </h3>
        </div>
        <div class="widget-content">
            <div class="performance-metrics">
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-clock"></i></div>
                    <div class="metric-data">
                        <div class="metric-value">4.2</div>
                        <div class="metric-label">Avg Check-in Time (min)</div>
                        <div class="metric-trend trend-down">↓ 0.3</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-broom"></i></div>
                    <div class="metric-data">
                        <div class="metric-value">28</div>
                        <div class="metric-label">Avg Cleaning Time (min)</div>
                        <div class="metric-trend trend-down">↓ 2</div>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-icon"><i class="fas fa-tools"></i></div>
                    <div class="metric-data">
                        <div class="metric-value">95%</div>
                        <div class="metric-label">Maintenance SLA</div>
                        <div class="metric-trend trend-up">↑ 3%</div>
                    </div>
                </div>
            </div>
            
            <div class="performance-chart">
                <canvas id="operationsChart" width="400" height="200"></canvas>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('operationsChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                    datasets: [{
                        label: 'Check-in Efficiency',
                        data: [85, 87, 89, 91, 88, 92, 90],
                        borderColor: '#007bff',
                        backgroundColor: 'rgba(0, 123, 255, 0.1)',
                        fill: true
                    }, {
                        label: 'Housekeeping Efficiency',
                        data: [82, 84, 86, 85, 87, 89, 88],
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            min: 75,
                            max: 100
                        }
                    }
                }
            });
        }
    });
    </script>
    <?php
}

/**
 * Render all operations manager widgets
 */
function renderOperationsDashboard($dashboard_manager) {
    echo '<div class="operations-dashboard">';
    
    echo '<div class="dashboard-row">';
    renderRoomStatusWidget($dashboard_manager);
    renderHousekeepingTasksWidget($dashboard_manager);
    echo '</div>';
    
    echo '<div class="dashboard-row">';
    renderMaintenanceRequestsWidget($dashboard_manager);
    renderStaffScheduleWidget($dashboard_manager);
    echo '</div>';
    
    echo '<div class="dashboard-row">';
    renderInventoryAlertsWidget($dashboard_manager);
    renderOperationsPerformanceWidget($dashboard_manager);
    echo '</div>';
    
    echo '</div>';
}

// JavaScript functions for widget interactions
?>
<script>
function openMaintenanceRequest() {
    // Implementation for opening maintenance request modal
    alert('Opening maintenance request form...');
}

function openScheduleManager() {
    // Implementation for opening schedule manager
    alert('Opening schedule manager...');
}
</script>
<?php
?>
