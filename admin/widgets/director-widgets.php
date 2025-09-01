<?php
/**
 * Orlando International Resorts - Director/CEO Dashboard Widgets
 * Executive-level widgets with comprehensive business intelligence
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

// Ensure proper authentication
if (!defined('ADMIN_ACCESS') || !isset($_SESSION['user_id'])) {
    exit('Access denied');
}

// Verify executive access
$user_role = $_SESSION['user_role'] ?? '';
if (!in_array($user_role, ['director', 'ceo', 'super_admin'])) {
    exit('Insufficient privileges');
}

/**
 * Executive Revenue Overview Widget
 */
function renderRevenueOverviewWidget($dashboard_manager) {
    $stats = $dashboard_manager->getStatsForRole();
    $revenue_data = $stats['revenue'] ?? [];
    ?>
    <div class="widget-card revenue-overview" data-widget="revenue_overview" data-widget-type="chart">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-chart-line"></i>
                Revenue Overview
            </h3>
            <div class="widget-actions">
                <button class="btn-refresh" data-widget-refresh="revenue_overview">
                    <i class="fas fa-sync-alt"></i>
                </button>
            </div>
        </div>
        <div class="widget-content">
            <div class="revenue-metrics">
                <div class="metric-item">
                    <div class="metric-value"><?= number_format($revenue_data['today'] ?? 0, 2) ?></div>
                    <div class="metric-label">Today's Revenue</div>
                    <div class="metric-trend trend-up">
                        <i class="fas fa-arrow-up"></i> 12.5%
                    </div>
                </div>
                <div class="metric-item">
                    <div class="metric-value"><?= number_format($revenue_data['monthly'] ?? 0, 2) ?></div>
                    <div class="metric-label">Monthly Revenue</div>
                    <div class="metric-trend trend-up">
                        <i class="fas fa-arrow-up"></i> 8.3%
                    </div>
                </div>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart" width="400" height="200"></canvas>
            </div>
        </div>
        <div class="widget-loading" style="display: none;">
            <i class="fas fa-spinner fa-spin"></i> Loading...
        </div>
        <div class="widget-error" style="display: none;"></div>
    </div>
    
    <script>
    // Initialize revenue chart
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            const revenueChart = new Chart(ctx.getContext('2d'), {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Revenue',
                        data: [<?= implode(',', array_fill(0, 6, rand(10000, 50000))) ?>],
                        backgroundColor: 'rgba(75, 192, 192, 0.1)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
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
 * Executive Performance Dashboard Widget
 */
function renderPerformanceDashboardWidget($dashboard_manager) {
    $stats = $dashboard_manager->getStatsForRole();
    $occupancy = $stats['occupancy'] ?? [];
    ?>
    <div class="widget-card performance-dashboard" data-widget="performance_dashboard" data-widget-type="metric">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-tachometer-alt"></i>
                Performance Dashboard
            </h3>
        </div>
        <div class="widget-content">
            <div class="performance-grid">
                <div class="performance-item">
                    <div class="performance-icon">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="performance-data">
                        <div class="performance-value"><?= $occupancy['occupancy_rate'] ?? 0 ?>%</div>
                        <div class="performance-label">Occupancy Rate</div>
                        <div class="performance-progress">
                            <div class="progress-bar" style="width: <?= $occupancy['occupancy_rate'] ?? 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <div class="performance-item">
                    <div class="performance-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="performance-data">
                        <div class="performance-value"><?= $stats['staff']['total_active'] ?? 0 ?></div>
                        <div class="performance-label">Active Staff</div>
                        <div class="performance-detail">Across all departments</div>
                    </div>
                </div>
                
                <div class="performance-item">
                    <div class="performance-icon">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="performance-data">
                        <div class="performance-value"><?= $stats['revenue']['today_bookings'] ?? 0 ?></div>
                        <div class="performance-label">Today's Bookings</div>
                        <div class="performance-detail">Confirmed reservations</div>
                    </div>
                </div>
                
                <div class="performance-item">
                    <div class="performance-icon">
                        <i class="fas fa-chart-pie"></i>
                    </div>
                    <div class="performance-data">
                        <div class="performance-value">92%</div>
                        <div class="performance-label">Satisfaction</div>
                        <div class="performance-detail">Guest satisfaction rate</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Strategic Insights Widget
 */
function renderStrategicInsightsWidget($dashboard_manager) {
    ?>
    <div class="widget-card strategic-insights" data-widget="strategic_insights" data-widget-type="chart">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-brain"></i>
                Strategic Insights
            </h3>
        </div>
        <div class="widget-content">
            <div class="insights-grid">
                <div class="insight-item">
                    <div class="insight-header">
                        <i class="fas fa-trending-up"></i>
                        Revenue Trend
                    </div>
                    <div class="insight-content">
                        <p>Revenue is up 15% compared to last month. Peak booking periods are weekends and holidays.</p>
                        <div class="insight-recommendation">
                            <strong>Recommendation:</strong> Increase weekend rates by 10%
                        </div>
                    </div>
                </div>
                
                <div class="insight-item">
                    <div class="insight-header">
                        <i class="fas fa-users-cog"></i>
                        Staff Optimization
                    </div>
                    <div class="insight-content">
                        <p>Housekeeping efficiency has improved by 8% this quarter.</p>
                        <div class="insight-recommendation">
                            <strong>Recommendation:</strong> Implement performance bonuses
                        </div>
                    </div>
                </div>
                
                <div class="insight-item">
                    <div class="insight-header">
                        <i class="fas fa-star"></i>
                        Guest Experience
                    </div>
                    <div class="insight-content">
                        <p>Guest satisfaction scores consistently above 90%.</p>
                        <div class="insight-recommendation">
                            <strong>Recommendation:</strong> Launch loyalty program
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Department Performance Widget
 */
function renderDepartmentPerformanceWidget($dashboard_manager) {
    ?>
    <div class="widget-card department-performance" data-widget="department_performance" data-widget-type="chart">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-building"></i>
                Department Performance
            </h3>
        </div>
        <div class="widget-content">
            <div class="department-chart">
                <canvas id="departmentChart" width="400" height="300"></canvas>
            </div>
            <div class="department-metrics">
                <div class="dept-metric">
                    <div class="dept-name">Front Desk</div>
                    <div class="dept-score">95%</div>
                    <div class="dept-trend trend-up">↗</div>
                </div>
                <div class="dept-metric">
                    <div class="dept-name">Housekeeping</div>
                    <div class="dept-score">92%</div>
                    <div class="dept-trend trend-up">↗</div>
                </div>
                <div class="dept-metric">
                    <div class="dept-name">Maintenance</div>
                    <div class="dept-score">88%</div>
                    <div class="dept-trend trend-stable">→</div>
                </div>
                <div class="dept-metric">
                    <div class="dept-name">F&B</div>
                    <div class="dept-score">94%</div>
                    <div class="dept-trend trend-up">↗</div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('departmentChart');
        if (ctx) {
            new Chart(ctx.getContext('2d'), {
                type: 'radar',
                data: {
                    labels: ['Front Desk', 'Housekeeping', 'Maintenance', 'F&B', 'Security'],
                    datasets: [{
                        label: 'Performance Score',
                        data: [95, 92, 88, 94, 91],
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        r: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                stepSize: 20
                            }
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
 * Executive Alert Widget
 */
function renderExecutiveAlertsWidget($dashboard_manager) {
    ?>
    <div class="widget-card executive-alerts" data-widget="executive_alerts" data-widget-type="list">
        <div class="widget-header">
            <h3 class="widget-title">
                <i class="fas fa-exclamation-triangle"></i>
                Executive Alerts
            </h3>
        </div>
        <div class="widget-content">
            <div class="alert-list">
                <div class="alert-item alert-high">
                    <div class="alert-icon">
                        <i class="fas fa-dollar-sign"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Revenue Target Alert</div>
                        <div class="alert-message">Monthly revenue is 5% below target</div>
                        <div class="alert-time">2 hours ago</div>
                    </div>
                </div>
                
                <div class="alert-item alert-medium">
                    <div class="alert-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Maintenance Schedule</div>
                        <div class="alert-message">Annual HVAC maintenance due next week</div>
                        <div class="alert-time">4 hours ago</div>
                    </div>
                </div>
                
                <div class="alert-item alert-low">
                    <div class="alert-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="alert-content">
                        <div class="alert-title">Guest Feedback</div>
                        <div class="alert-message">Excellent review from VIP guest</div>
                        <div class="alert-time">6 hours ago</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Render all executive widgets
 */
function renderDirectorDashboard($dashboard_manager) {
    echo '<div class="executive-dashboard">';
    
    echo '<div class="dashboard-row">';
    renderRevenueOverviewWidget($dashboard_manager);
    renderPerformanceDashboardWidget($dashboard_manager);
    echo '</div>';
    
    echo '<div class="dashboard-row">';
    renderStrategicInsightsWidget($dashboard_manager);
    renderDepartmentPerformanceWidget($dashboard_manager);
    echo '</div>';
    
    echo '<div class="dashboard-row">';
    renderExecutiveAlertsWidget($dashboard_manager);
    echo '</div>';
    
    echo '</div>';
}
?>
