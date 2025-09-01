/**
 * Orlando International Resorts - Dynamic Dashboard Manager
 * Real-time dashboard management with WebSocket support and live updates
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

class DashboardManager {
    constructor(options = {}) {
        this.options = {
            refreshInterval: 30000, // 30 seconds default
            websocketUrl: options.websocketUrl || null,
            apiBaseUrl: options.apiBaseUrl || '/admin/api/',
            enableWebSocket: options.enableWebSocket !== false,
            enableAutoRefresh: options.enableAutoRefresh !== false,
            debug: options.debug || false,
            ...options
        };
        
        this.widgets = new Map();
        this.charts = new Map();
        this.websocket = null;
        this.refreshTimer = null;
        this.lastUpdate = null;
        this.isConnected = false;
        
        this.init();
    }
    
    /**
     * Initialize the dashboard manager
     */
    init() {
        this.log('Initializing Dashboard Manager...');
        
        // Initialize widgets
        this.initializeWidgets();
        
        // Setup WebSocket connection if enabled
        if (this.options.enableWebSocket && this.options.websocketUrl) {
            this.initializeWebSocket();
        }
        
        // Setup auto-refresh
        if (this.options.enableAutoRefresh) {
            this.startAutoRefresh();
        }
        
        // Setup event listeners
        this.setupEventListeners();
        
        // Initial data load
        this.refreshAllWidgets();
        
        this.log('Dashboard Manager initialized successfully');
    }
    
    /**
     * Initialize all widgets on the page
     */
    initializeWidgets() {
        const widgetElements = document.querySelectorAll('[data-widget]');
        
        widgetElements.forEach(element => {
            const widgetId = element.getAttribute('data-widget');
            const widgetType = element.getAttribute('data-widget-type') || 'default';
            const refreshInterval = parseInt(element.getAttribute('data-refresh-interval')) || this.options.refreshInterval;
            
            this.widgets.set(widgetId, {
                element: element,
                type: widgetType,
                refreshInterval: refreshInterval,
                lastUpdate: null,
                loading: false
            });
            
            this.log(`Widget registered: ${widgetId} (${widgetType})`);
        });
    }
    
    /**
     * Initialize WebSocket connection for real-time updates
     */
    initializeWebSocket() {
        try {
            this.websocket = new WebSocket(this.options.websocketUrl);
            
            this.websocket.onopen = (event) => {
                this.isConnected = true;
                this.log('WebSocket connected');
                this.showConnectionStatus(true);
            };
            
            this.websocket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleWebSocketMessage(data);
                } catch (error) {
                    this.log('WebSocket message parse error:', error);
                }
            };
            
            this.websocket.onclose = (event) => {
                this.isConnected = false;
                this.log('WebSocket disconnected');
                this.showConnectionStatus(false);
                
                // Attempt to reconnect after 5 seconds
                setTimeout(() => {
                    if (!this.isConnected) {
                        this.initializeWebSocket();
                    }
                }, 5000);
            };
            
            this.websocket.onerror = (error) => {
                this.log('WebSocket error:', error);
                this.showConnectionStatus(false);
            };
            
        } catch (error) {
            this.log('WebSocket initialization error:', error);
        }
    }
    
    /**
     * Handle incoming WebSocket messages
     */
    handleWebSocketMessage(data) {
        this.log('WebSocket message received:', data);
        
        switch (data.type) {
            case 'widget_update':
                this.updateWidget(data.widget_id, data.data);
                break;
            case 'notification':
                this.handleNotification(data);
                break;
            case 'system_alert':
                this.handleSystemAlert(data);
                break;
            case 'refresh_dashboard':
                this.refreshAllWidgets();
                break;
            default:
                this.log('Unknown WebSocket message type:', data.type);
        }
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Page visibility change
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                this.pauseUpdates();
            } else {
                this.resumeUpdates();
            }
        });
        
        // Window focus/blur
        window.addEventListener('focus', () => this.resumeUpdates());
        window.addEventListener('blur', () => this.pauseUpdates());
        
        // Manual refresh button
        const refreshButton = document.getElementById('dashboard-refresh');
        if (refreshButton) {
            refreshButton.addEventListener('click', () => this.refreshAllWidgets());
        }
        
        // Widget-specific refresh buttons
        document.addEventListener('click', (event) => {
            if (event.target.matches('[data-widget-refresh]')) {
                const widgetId = event.target.getAttribute('data-widget-refresh');
                this.refreshWidget(widgetId);
            }
        });
    }
    
    /**
     * Start auto-refresh timer
     */
    startAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
        }
        
        this.refreshTimer = setInterval(() => {
            if (!document.hidden) {
                this.refreshAllWidgets();
            }
        }, this.options.refreshInterval);
        
        this.log(`Auto-refresh started (${this.options.refreshInterval}ms)`);
    }
    
    /**
     * Stop auto-refresh timer
     */
    stopAutoRefresh() {
        if (this.refreshTimer) {
            clearInterval(this.refreshTimer);
            this.refreshTimer = null;
        }
        this.log('Auto-refresh stopped');
    }
    
    /**
     * Pause updates (when page is hidden)
     */
    pauseUpdates() {
        this.stopAutoRefresh();
        this.log('Updates paused');
    }
    
    /**
     * Resume updates (when page is visible)
     */
    resumeUpdates() {
        this.startAutoRefresh();
        this.refreshAllWidgets();
        this.log('Updates resumed');
    }
    
    /**
     * Refresh all widgets
     */
    async refreshAllWidgets() {
        this.log('Refreshing all widgets...');
        
        const promises = Array.from(this.widgets.keys()).map(widgetId => 
            this.refreshWidget(widgetId)
        );
        
        try {
            await Promise.all(promises);
            this.lastUpdate = new Date();
            this.updateLastRefreshTime();
            this.log('All widgets refreshed successfully');
        } catch (error) {
            this.log('Error refreshing widgets:', error);
        }
    }
    
    /**
     * Refresh a specific widget
     */
    async refreshWidget(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (!widget || widget.loading) {
            return;
        }
        
        widget.loading = true;
        this.showWidgetLoading(widgetId, true);
        
        try {
            const response = await fetch(`${this.options.apiBaseUrl}get_widget_data.php?widget=${widgetId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            this.updateWidget(widgetId, data);
            widget.lastUpdate = new Date();
            
        } catch (error) {
            this.log(`Error refreshing widget ${widgetId}:`, error);
            this.showWidgetError(widgetId, error.message);
        } finally {
            widget.loading = false;
            this.showWidgetLoading(widgetId, false);
        }
    }
    
    /**
     * Update a specific widget with new data
     */
    updateWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        if (!widget) {
            this.log(`Widget ${widgetId} not found`);
            return;
        }
        
        this.log(`Updating widget: ${widgetId}`, data);
        
        try {
            switch (widget.type) {
                case 'chart':
                    this.updateChartWidget(widgetId, data);
                    break;
                case 'metric':
                    this.updateMetricWidget(widgetId, data);
                    break;
                case 'list':
                    this.updateListWidget(widgetId, data);
                    break;
                case 'status':
                    this.updateStatusWidget(widgetId, data);
                    break;
                case 'table':
                    this.updateTableWidget(widgetId, data);
                    break;
                default:
                    this.updateGenericWidget(widgetId, data);
            }
            
            // Add update animation
            this.animateWidgetUpdate(widgetId);
            
        } catch (error) {
            this.log(`Error updating widget ${widgetId}:`, error);
            this.showWidgetError(widgetId, 'Failed to update widget');
        }
    }
    
    /**
     * Update chart widget
     */
    updateChartWidget(widgetId, data) {
        const chartCanvas = widget.element.querySelector('canvas');
        if (!chartCanvas) return;
        
        let chart = this.charts.get(widgetId);
        
        if (!chart) {
            // Create new chart
            const ctx = chartCanvas.getContext('2d');
            chart = new Chart(ctx, {
                type: data.chart_type || 'line',
                data: data.data || {},
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    animation: {
                        duration: 750
                    },
                    ...data.options
                }
            });
            this.charts.set(widgetId, chart);
        } else {
            // Update existing chart
            if (data.data) {
                chart.data = data.data;
                chart.update('active');
            }
        }
    }
    
    /**
     * Update metric widget
     */
    updateMetricWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        const element = widget.element;
        
        const valueElement = element.querySelector('.metric-value');
        const labelElement = element.querySelector('.metric-label');
        const trendElement = element.querySelector('.metric-trend');
        
        if (valueElement && data.value !== undefined) {
            valueElement.textContent = this.formatValue(data.value, data.unit);
        }
        
        if (labelElement && data.label) {
            labelElement.textContent = data.label;
        }
        
        if (trendElement && data.trend) {
            trendElement.className = `metric-trend trend-${data.trend}`;
            trendElement.innerHTML = data.trend === 'up' ? 
                '<i class="fas fa-arrow-up"></i>' : 
                '<i class="fas fa-arrow-down"></i>';
        }
        
        // Update color if specified
        if (data.color) {
            element.classList.remove('metric-success', 'metric-warning', 'metric-danger', 'metric-info');
            element.classList.add(`metric-${data.color}`);
        }
    }
    
    /**
     * Update list widget
     */
    updateListWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        const listContainer = widget.element.querySelector('.widget-list');
        
        if (!listContainer || !data.items) return;
        
        listContainer.innerHTML = '';
        
        data.items.forEach(item => {
            const listItem = document.createElement('div');
            listItem.className = 'list-item';
            listItem.innerHTML = `
                <div class="list-item-content">
                    <div class="list-item-title">${item.title || ''}</div>
                    <div class="list-item-meta">${item.meta || ''}</div>
                </div>
                <div class="list-item-status status-${item.status || 'default'}">
                    ${item.status || ''}
                </div>
            `;
            listContainer.appendChild(listItem);
        });
    }
    
    /**
     * Update status widget
     */
    updateStatusWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        const statusElement = widget.element.querySelector('.status-indicator');
        const messageElement = widget.element.querySelector('.status-message');
        const detailsElement = widget.element.querySelector('.status-details');
        
        if (statusElement && data.status) {
            statusElement.className = `status-indicator status-${data.status}`;
        }
        
        if (messageElement && data.message) {
            messageElement.textContent = data.message;
        }
        
        if (detailsElement && data.details) {
            detailsElement.innerHTML = data.details.map(detail => 
                `<div class="status-detail">${detail}</div>`
            ).join('');
        }
    }
    
    /**
     * Update table widget
     */
    updateTableWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        const tableBody = widget.element.querySelector('tbody');
        
        if (!tableBody || !data.rows) return;
        
        tableBody.innerHTML = '';
        
        data.rows.forEach(row => {
            const tr = document.createElement('tr');
            row.forEach(cell => {
                const td = document.createElement('td');
                td.innerHTML = cell;
                tr.appendChild(td);
            });
            tableBody.appendChild(tr);
        });
    }
    
    /**
     * Update generic widget
     */
    updateGenericWidget(widgetId, data) {
        const widget = this.widgets.get(widgetId);
        const contentElement = widget.element.querySelector('.widget-content');
        
        if (contentElement && data.html) {
            contentElement.innerHTML = data.html;
        }
    }
    
    /**
     * Show/hide widget loading state
     */
    showWidgetLoading(widgetId, loading) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const loadingElement = widget.element.querySelector('.widget-loading');
        if (loadingElement) {
            loadingElement.style.display = loading ? 'block' : 'none';
        }
        
        widget.element.classList.toggle('widget-loading-state', loading);
    }
    
    /**
     * Show widget error
     */
    showWidgetError(widgetId, message) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        const errorElement = widget.element.querySelector('.widget-error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
            
            // Hide error after 5 seconds
            setTimeout(() => {
                errorElement.style.display = 'none';
            }, 5000);
        }
    }
    
    /**
     * Animate widget update
     */
    animateWidgetUpdate(widgetId) {
        const widget = this.widgets.get(widgetId);
        if (!widget) return;
        
        widget.element.classList.add('widget-updated');
        setTimeout(() => {
            widget.element.classList.remove('widget-updated');
        }, 1000);
    }
    
    /**
     * Show connection status
     */
    showConnectionStatus(connected) {
        const statusElement = document.querySelector('.connection-status');
        if (statusElement) {
            statusElement.className = `connection-status ${connected ? 'connected' : 'disconnected'}`;
            statusElement.textContent = connected ? 'Connected' : 'Disconnected';
        }
    }
    
    /**
     * Update last refresh time display
     */
    updateLastRefreshTime() {
        const timeElement = document.querySelector('.last-refresh-time');
        if (timeElement && this.lastUpdate) {
            timeElement.textContent = `Last updated: ${this.lastUpdate.toLocaleTimeString()}`;
        }
    }
    
    /**
     * Handle notifications
     */
    handleNotification(data) {
        this.log('Notification received:', data);
        
        // Show browser notification if permission granted
        if ('Notification' in window && Notification.permission === 'granted') {
            new Notification(data.title || 'Orlando International Resorts', {
                body: data.message || '',
                icon: '/Hotel/images/logo-full.png'
            });
        }
        
        // Update notification widget if exists
        const notificationWidget = document.querySelector('[data-widget="notifications"]');
        if (notificationWidget) {
            this.refreshWidget('notifications');
        }
        
        // Show in-app notification
        this.showInAppNotification(data);
    }
    
    /**
     * Handle system alerts
     */
    handleSystemAlert(data) {
        this.log('System alert received:', data);
        
        // Show alert modal or toast
        if (data.severity === 'critical') {
            this.showCriticalAlert(data);
        } else {
            this.showToast(data.message, data.severity);
        }
    }
    
    /**
     * Show in-app notification
     */
    showInAppNotification(data) {
        const notification = document.createElement('div');
        notification.className = `notification notification-${data.type || 'info'}`;
        notification.innerHTML = `
            <div class="notification-content">
                <div class="notification-title">${data.title || ''}</div>
                <div class="notification-message">${data.message || ''}</div>
            </div>
            <button class="notification-close">&times;</button>
        `;
        
        const container = document.querySelector('.notifications-container') || document.body;
        container.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);
        
        // Close button
        notification.querySelector('.notification-close').addEventListener('click', () => {
            notification.remove();
        });
    }
    
    /**
     * Show critical alert modal
     */
    showCriticalAlert(data) {
        // Implementation for critical alerts
        alert(`CRITICAL ALERT: ${data.message}`);
    }
    
    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        // Implementation for toast notifications
        console.log(`Toast (${type}): ${message}`);
    }
    
    /**
     * Format value with unit
     */
    formatValue(value, unit = '') {
        if (typeof value === 'number') {
            if (unit === '%') {
                return `${value}%`;
            } else if (unit === '$') {
                return `$${value.toLocaleString()}`;
            } else {
                return value.toLocaleString();
            }
        }
        return value;
    }
    
    /**
     * Log messages (if debug enabled)
     */
    log(...args) {
        if (this.options.debug) {
            console.log('[DashboardManager]', ...args);
        }
    }
    
    /**
     * Destroy the dashboard manager
     */
    destroy() {
        this.stopAutoRefresh();
        
        if (this.websocket) {
            this.websocket.close();
        }
        
        // Destroy charts
        this.charts.forEach(chart => chart.destroy());
        this.charts.clear();
        
        this.widgets.clear();
        
        this.log('Dashboard Manager destroyed');
    }
}

/**
 * Initialize dashboard manager when DOM is ready
 */
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a dashboard page
    if (document.querySelector('[data-widget]')) {
        // Initialize dashboard manager with configuration
        window.dashboardManager = new DashboardManager({
            refreshInterval: 30000, // 30 seconds
            enableWebSocket: true,
            enableAutoRefresh: true,
            debug: false,
            apiBaseUrl: '/admin/api/',
            websocketUrl: 'ws://localhost:8080' // Will be configured based on server setup
        });
        
        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    }
});

/**
 * Global functions for external access
 */
window.DashboardManager = DashboardManager;

window.refreshDashboard = function() {
    if (window.dashboardManager) {
        window.dashboardManager.refreshAllWidgets();
    }
};

window.refreshWidget = function(widgetId) {
    if (window.dashboardManager) {
        window.dashboardManager.refreshWidget(widgetId);
    }
};
