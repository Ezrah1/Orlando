/**
 * Orlando International Resorts - Advanced Analytics Dashboard
 * Interactive business intelligence with predictive modeling and custom reporting
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class AnalyticsDashboard {
    constructor(options = {}) {
        this.options = {
            refreshInterval: options.refreshInterval || 60000, // 1 minute
            enableRealTime: options.enableRealTime !== false,
            enableInteractivity: options.enableInteractivity !== false,
            chartLibrary: options.chartLibrary || 'Chart.js',
            defaultTimeframe: options.defaultTimeframe || 'month',
            debug: options.debug || false,
            ...options
        };
        
        this.charts = new Map();
        this.data = new Map();
        this.refreshTimer = null;
        this.currentTimeframe = this.options.defaultTimeframe;
        this.selectedMetrics = new Set();
        this.customReports = new Map();
        this.predictiveModels = new Map();
        
        this.init();
    }
    
    /**
     * Initialize analytics dashboard
     */
    init() {
        this.log('Initializing Analytics Dashboard...');
        
        // Setup UI components
        this.setupUI();
        
        // Initialize chart library
        this.initializeChartLibrary();
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Load initial data
        this.loadAnalyticsData();
        
        // Start real-time updates
        if (this.options.enableRealTime) {
            this.startRealTimeUpdates();
        }
        
        this.log('Analytics Dashboard initialized');
    }
    
    /**
     * Setup user interface
     */
    setupUI() {
        // Create main dashboard structure
        this.createDashboardStructure();
        
        // Initialize control panels
        this.initializeControlPanels();
        
        // Setup metric selectors
        this.setupMetricSelectors();
        
        // Initialize export controls
        this.initializeExportControls();
    }
    
    /**
     * Create dashboard structure
     */
    createDashboardStructure() {
        const container = document.getElementById('analytics-dashboard');
        if (!container) return;
        
        container.innerHTML = `
            <div class="analytics-header">
                <div class="dashboard-title">
                    <h2>Business Intelligence Dashboard</h2>
                    <div class="last-updated">Last updated: <span id="last-updated-time">Loading...</span></div>
                </div>
                <div class="dashboard-controls">
                    <div class="timeframe-selector">
                        <select id="timeframe-select" class="form-control">
                            <option value="today">Today</option>
                            <option value="week">This Week</option>
                            <option value="month" selected>This Month</option>
                            <option value="quarter">This Quarter</option>
                            <option value="year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="view-controls">
                        <button class="btn btn-primary" onclick="analyticsDashboard.showCustomReportBuilder()">
                            <i class="fas fa-plus"></i> Custom Report
                        </button>
                        <button class="btn btn-success" onclick="analyticsDashboard.exportDashboard()">
                            <i class="fas fa-download"></i> Export
                        </button>
                        <button class="btn btn-info" onclick="analyticsDashboard.refreshAllData()">
                            <i class="fas fa-sync"></i> Refresh
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="analytics-content">
                <!-- Key Metrics Row -->
                <div class="metrics-row">
                    <div class="metric-card revenue">
                        <div class="metric-header">
                            <h4>Total Revenue</h4>
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="metric-value" id="metric-revenue">$0</div>
                        <div class="metric-change" id="metric-revenue-change">0%</div>
                    </div>
                    <div class="metric-card occupancy">
                        <div class="metric-header">
                            <h4>Occupancy Rate</h4>
                            <i class="fas fa-bed"></i>
                        </div>
                        <div class="metric-value" id="metric-occupancy">0%</div>
                        <div class="metric-change" id="metric-occupancy-change">0%</div>
                    </div>
                    <div class="metric-card adr">
                        <div class="metric-header">
                            <h4>Average Daily Rate</h4>
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="metric-value" id="metric-adr">$0</div>
                        <div class="metric-change" id="metric-adr-change">0%</div>
                    </div>
                    <div class="metric-card guest-satisfaction">
                        <div class="metric-header">
                            <h4>Guest Satisfaction</h4>
                            <i class="fas fa-smile"></i>
                        </div>
                        <div class="metric-value" id="metric-satisfaction">0.0</div>
                        <div class="metric-change" id="metric-satisfaction-change">0%</div>
                    </div>
                </div>
                
                <!-- Charts Grid -->
                <div class="charts-grid">
                    <div class="chart-container large">
                        <div class="chart-header">
                            <h5>Revenue Trends & Forecast</h5>
                            <div class="chart-controls">
                                <button class="btn btn-sm btn-outline-primary" onclick="analyticsDashboard.toggleForecast()">
                                    Toggle Forecast
                                </button>
                            </div>
                        </div>
                        <canvas id="revenue-trend-chart"></canvas>
                    </div>
                    
                    <div class="chart-container medium">
                        <div class="chart-header">
                            <h5>Occupancy Analysis</h5>
                        </div>
                        <canvas id="occupancy-chart"></canvas>
                    </div>
                    
                    <div class="chart-container medium">
                        <div class="chart-header">
                            <h5>Revenue by Department</h5>
                        </div>
                        <canvas id="department-revenue-chart"></canvas>
                    </div>
                    
                    <div class="chart-container large">
                        <div class="chart-header">
                            <h5>Guest Behavior Analytics</h5>
                        </div>
                        <canvas id="guest-behavior-chart"></canvas>
                    </div>
                    
                    <div class="chart-container medium">
                        <div class="chart-header">
                            <h5>Operational Efficiency</h5>
                        </div>
                        <canvas id="efficiency-chart"></canvas>
                    </div>
                    
                    <div class="chart-container medium">
                        <div class="chart-header">
                            <h5>Predictive Insights</h5>
                        </div>
                        <canvas id="predictive-chart"></canvas>
                    </div>
                </div>
                
                <!-- Advanced Analytics Section -->
                <div class="advanced-analytics">
                    <div class="analytics-tabs">
                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#predictive-tab">Predictive Analytics</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#guest-insights-tab">Guest Insights</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#operational-tab">Operational Intelligence</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#custom-reports-tab">Custom Reports</a>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <div id="predictive-tab" class="tab-pane active">
                                <div class="predictive-content">
                                    <div class="forecast-controls">
                                        <label>Forecast Type:</label>
                                        <select id="forecast-type" class="form-control">
                                            <option value="revenue">Revenue Forecast</option>
                                            <option value="occupancy">Occupancy Forecast</option>
                                            <option value="demand">Demand Forecast</option>
                                            <option value="comprehensive">Comprehensive</option>
                                        </select>
                                        <label>Period:</label>
                                        <select id="forecast-period" class="form-control">
                                            <option value="7">7 Days</option>
                                            <option value="30" selected>30 Days</option>
                                            <option value="90">90 Days</option>
                                            <option value="365">1 Year</option>
                                        </select>
                                        <button class="btn btn-primary" onclick="analyticsDashboard.generateForecast()">
                                            Generate Forecast
                                        </button>
                                    </div>
                                    <div id="forecast-results" class="forecast-results">
                                        <!-- Forecast results will be displayed here -->
                                    </div>
                                </div>
                            </div>
                            
                            <div id="guest-insights-tab" class="tab-pane">
                                <div id="guest-insights-content" class="insights-content">
                                    <!-- Guest insights will be loaded here -->
                                </div>
                            </div>
                            
                            <div id="operational-tab" class="tab-pane">
                                <div id="operational-insights-content" class="insights-content">
                                    <!-- Operational insights will be loaded here -->
                                </div>
                            </div>
                            
                            <div id="custom-reports-tab" class="tab-pane">
                                <div id="custom-reports-content" class="reports-content">
                                    <!-- Custom reports will be displayed here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Initialize Chart.js library
     */
    initializeChartLibrary() {
        // Set Chart.js defaults
        if (typeof Chart !== 'undefined') {
            Chart.defaults.font.family = "'Segoe UI', 'Roboto', sans-serif";
            Chart.defaults.font.size = 12;
            Chart.defaults.color = '#6c757d';
            Chart.defaults.plugins.legend.position = 'top';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(0,0,0,0.8)';
            Chart.defaults.plugins.tooltip.cornerRadius = 4;
            Chart.defaults.elements.line.tension = 0.4;
            Chart.defaults.elements.point.radius = 4;
            Chart.defaults.elements.point.hoverRadius = 6;
        }
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Timeframe selector
        const timeframeSelect = document.getElementById('timeframe-select');
        if (timeframeSelect) {
            timeframeSelect.addEventListener('change', (e) => {
                this.currentTimeframe = e.target.value;
                this.refreshAllData();
            });
        }
        
        // Tab switching
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('nav-link')) {
                this.handleTabSwitch(e.target);
            }
        });
        
        // Chart interactions
        document.addEventListener('click', (e) => {
            if (e.target.dataset.chartAction) {
                this.handleChartAction(e.target.dataset.chartAction, e.target.dataset);
            }
        });
        
        // Real-time data updates
        if (window.notificationManager) {
            window.notificationManager.on('analytics_update', (data) => {
                this.handleRealTimeUpdate(data);
            });
        }
    }
    
    /**
     * Load analytics data
     */
    async loadAnalyticsData() {
        try {
            this.showLoading(true);
            
            // Load business intelligence dashboard
            const dashboardData = await this.fetchData('/admin/api/analytics-api.php?action=dashboard&timeframe=' + this.currentTimeframe);
            this.data.set('dashboard', dashboardData);
            
            // Load predictive forecasts
            const forecastData = await this.fetchData('/admin/api/analytics-api.php?action=forecasts&type=comprehensive&period=30');
            this.data.set('forecasts', forecastData);
            
            // Update UI with data
            this.updateDashboard();
            
        } catch (error) {
            this.log('Error loading analytics data:', error);
            this.showError('Failed to load analytics data');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Update dashboard with latest data
     */
    updateDashboard() {
        this.updateKeyMetrics();
        this.updateCharts();
        this.updateLastUpdatedTime();
    }
    
    /**
     * Update key metrics display
     */
    updateKeyMetrics() {
        const dashboardData = this.data.get('dashboard');
        if (!dashboardData || !dashboardData.key_metrics) return;
        
        const metrics = dashboardData.key_metrics;
        
        // Update revenue metric
        this.updateMetricCard('revenue', metrics.total_revenue || 0, metrics.revenue_change || 0);
        
        // Update occupancy metric
        this.updateMetricCard('occupancy', (metrics.occupancy_rate || 0) + '%', metrics.occupancy_change || 0);
        
        // Update ADR metric
        this.updateMetricCard('adr', '$' + (metrics.average_daily_rate || 0), metrics.adr_change || 0);
        
        // Update satisfaction metric
        this.updateMetricCard('satisfaction', (metrics.guest_satisfaction || 0).toFixed(1), metrics.satisfaction_change || 0);
    }
    
    /**
     * Update individual metric card
     */
    updateMetricCard(metric, value, change) {
        const valueElement = document.getElementById(`metric-${metric}`);
        const changeElement = document.getElementById(`metric-${metric}-change`);
        
        if (valueElement) {
            valueElement.textContent = value;
        }
        
        if (changeElement) {
            const changeText = (change > 0 ? '+' : '') + change.toFixed(1) + '%';
            changeElement.textContent = changeText;
            changeElement.className = 'metric-change ' + (change > 0 ? 'positive' : change < 0 ? 'negative' : 'neutral');
        }
    }
    
    /**
     * Update all charts
     */
    updateCharts() {
        this.updateRevenueTrendChart();
        this.updateOccupancyChart();
        this.updateDepartmentRevenueChart();
        this.updateGuestBehaviorChart();
        this.updateEfficiencyChart();
        this.updatePredictiveChart();
    }
    
    /**
     * Update revenue trend chart with forecast
     */
    updateRevenueTrendChart() {
        const ctx = document.getElementById('revenue-trend-chart');
        if (!ctx) return;
        
        const dashboardData = this.data.get('dashboard');
        const forecastData = this.data.get('forecasts');
        
        if (!dashboardData || !dashboardData.revenue_analytics) return;
        
        const revenueData = dashboardData.revenue_analytics.revenue_trends || [];
        const forecastRevenue = forecastData?.revenue?.base || [];
        
        // Prepare chart data
        const labels = revenueData.map(item => this.formatDate(item.date));
        const actualData = revenueData.map(item => item.amount);
        
        // Add forecast labels and data
        const forecastLabels = forecastRevenue.map(item => this.formatDate(item.date));
        const forecastValues = forecastRevenue.map(item => item.value);
        
        const chartData = {
            labels: [...labels, ...forecastLabels],
            datasets: [{
                label: 'Actual Revenue',
                data: [...actualData, ...Array(forecastValues.length).fill(null)],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
            }, {
                label: 'Forecast',
                data: [...Array(actualData.length).fill(null), ...forecastValues],
                borderColor: '#28a745',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderDash: [5, 5],
                fill: false,
                tension: 0.4
            }]
        };
        
        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => '$' + this.formatNumber(value)
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                return context.dataset.label + ': $' + this.formatNumber(context.parsed.y);
                            }
                        }
                    }
                }
            }
        };
        
        this.updateOrCreateChart('revenue-trend-chart', config);
    }
    
    /**
     * Update occupancy analysis chart
     */
    updateOccupancyChart() {
        const ctx = document.getElementById('occupancy-chart');
        if (!ctx) return;
        
        const dashboardData = this.data.get('dashboard');
        if (!dashboardData || !dashboardData.occupancy_insights) return;
        
        const occupancyData = dashboardData.occupancy_insights;
        
        const chartData = {
            labels: ['Available', 'Occupied', 'Maintenance', 'Cleaning'],
            datasets: [{
                data: [
                    occupancyData.available_rooms || 0,
                    occupancyData.occupied_rooms || 0,
                    occupancyData.maintenance_rooms || 0,
                    occupancyData.cleaning_rooms || 0
                ],
                backgroundColor: [
                    '#28a745',
                    '#007bff',
                    '#ffc107',
                    '#6f42c1'
                ],
                borderWidth: 0
            }]
        };
        
        const config = {
            type: 'doughnut',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    tooltip: {
                        callbacks: {
                            label: (context) => {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        };
        
        this.updateOrCreateChart('occupancy-chart', config);
    }
    
    /**
     * Generate predictive forecast
     */
    async generateForecast() {
        try {
            const forecastType = document.getElementById('forecast-type').value;
            const forecastPeriod = document.getElementById('forecast-period').value;
            
            this.showLoading(true, 'Generating forecast...');
            
            const forecastData = await this.fetchData(
                `/admin/api/analytics-api.php?action=forecasts&type=${forecastType}&period=${forecastPeriod}&scenarios=true`
            );
            
            this.displayForecastResults(forecastData);
            
        } catch (error) {
            this.log('Error generating forecast:', error);
            this.showError('Failed to generate forecast');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Display forecast results
     */
    displayForecastResults(forecastData) {
        const resultsContainer = document.getElementById('forecast-results');
        if (!resultsContainer) return;
        
        const scenarios = forecastData.scenarios || {};
        const modelInfo = forecastData.model_info || {};
        
        resultsContainer.innerHTML = `
            <div class="forecast-summary">
                <div class="model-accuracy">
                    <h6>Model Accuracy</h6>
                    <div class="accuracy-score">${(modelInfo.accuracy_score * 100 || 0).toFixed(1)}%</div>
                    <div class="confidence-interval">
                        Confidence: ${(modelInfo.confidence_interval * 100 || 0).toFixed(1)}%
                    </div>
                </div>
                
                <div class="scenario-analysis">
                    <h6>Scenario Analysis</h6>
                    <div class="scenarios">
                        ${Object.entries(scenarios).map(([scenario, data]) => `
                            <div class="scenario ${scenario}">
                                <h7>${scenario.charAt(0).toUpperCase() + scenario.slice(1)}</h7>
                                <div class="scenario-value">
                                    ${this.formatForecastValue(data)}
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
            
            <div class="forecast-chart-container">
                <canvas id="forecast-chart"></canvas>
            </div>
        `;
        
        // Create forecast chart
        this.createForecastChart(forecastData);
    }
    
    /**
     * Create or update chart
     */
    updateOrCreateChart(chartId, config) {
        if (this.charts.has(chartId)) {
            const chart = this.charts.get(chartId);
            chart.data = config.data;
            chart.update();
        } else {
            const ctx = document.getElementById(chartId);
            if (ctx) {
                const chart = new Chart(ctx, config);
                this.charts.set(chartId, chart);
            }
        }
    }
    
    /**
     * Show custom report builder
     */
    showCustomReportBuilder() {
        // Implementation for custom report builder modal
        const modal = document.getElementById('custom-report-modal');
        if (modal) {
            modal.style.display = 'block';
        } else {
            // Create modal dynamically
            this.createCustomReportModal();
        }
    }
    
    /**
     * Export dashboard data
     */
    async exportDashboard() {
        try {
            const exportFormat = 'excel'; // Could be made configurable
            
            this.showLoading(true, 'Preparing export...');
            
            const response = await fetch(
                `/admin/api/analytics-api.php?action=export&type=${exportFormat}&range=${this.currentTimeframe}&include_raw=false`
            );
            
            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `analytics-dashboard-${new Date().toISOString().split('T')[0]}.${exportFormat}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                this.showSuccess('Dashboard exported successfully');
            } else {
                throw new Error('Export failed');
            }
            
        } catch (error) {
            this.log('Error exporting dashboard:', error);
            this.showError('Failed to export dashboard');
        } finally {
            this.showLoading(false);
        }
    }
    
    /**
     * Start real-time updates
     */
    startRealTimeUpdates() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.refreshTimer = setInterval(() => {
            this.updateRealTimeMetrics();
        }, this.options.refreshInterval);
    }
    
    /**
     * Update real-time metrics
     */
    async updateRealTimeMetrics() {
        try {
            const realTimeData = await this.fetchData('/admin/api/analytics-api.php?action=realtime');
            
            // Update key metrics with real-time data
            this.updateRealTimeDisplay(realTimeData);
            
        } catch (error) {
            this.log('Error updating real-time metrics:', error);
        }
    }
    
    /**
     * Utility methods
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString();
    }
    
    formatNumber(num) {
        return new Intl.NumberFormat().format(num);
    }
    
    formatForecastValue(data) {
        if (Array.isArray(data) && data.length > 0) {
            const totalValue = data.reduce((sum, item) => sum + item.value, 0);
            return '$' + this.formatNumber(totalValue.toFixed(0));
        }
        return '$0';
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
    
    showLoading(show, message = 'Loading...') {
        // Implementation for loading indicator
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
                title: 'Analytics',
                message: message,
                type: type
            });
        }
    }
    
    updateLastUpdatedTime() {
        const timeElement = document.getElementById('last-updated-time');
        if (timeElement) {
            timeElement.textContent = new Date().toLocaleTimeString();
        }
    }
    
    log(...args) {
        if (this.options.debug) {
            console.log('[AnalyticsDashboard]', ...args);
        }
    }
    
    /**
     * Destroy dashboard
     */
    destroy() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        // Destroy all charts
        this.charts.forEach(chart => chart.destroy());
        this.charts.clear();
        
        this.log('Analytics Dashboard destroyed');
    }
}

// Initialize dashboard when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.querySelector('#analytics-dashboard')) {
        window.analyticsDashboard = new AnalyticsDashboard({
            debug: false,
            enableRealTime: true,
            refreshInterval: 60000
        });
    }
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AnalyticsDashboard;
}
