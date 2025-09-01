<?php
/**
 * Orlando International Resorts - Business Intelligence & Analytics API
 * Advanced analytics engine with predictive modeling and custom report generation
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class AnalyticsAPI {
    private $db;
    private $permission_manager;
    private $event_manager;
    private $user_id;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->permission_manager = getPermissionManager();
        $this->event_manager = getEventManager();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get comprehensive business intelligence dashboard
     */
    public function getBusinessIntelligenceDashboard() {
        $this->checkPermission('analytics.view');
        
        $timeframe = $_GET['timeframe'] ?? 'month';
        $comparison_period = $_GET['comparison'] ?? 'previous';
        
        $dashboard = [
            'key_metrics' => $this->getKeyBusinessMetrics($timeframe),
            'revenue_analytics' => $this->getRevenueAnalytics($timeframe),
            'occupancy_insights' => $this->getOccupancyInsights($timeframe),
            'guest_analytics' => $this->getGuestAnalytics($timeframe),
            'operational_efficiency' => $this->getOperationalEfficiency($timeframe),
            'predictive_insights' => $this->getPredictiveInsights($timeframe),
            'trend_analysis' => $this->getTrendAnalysis($timeframe, $comparison_period),
            'performance_indicators' => $this->getPerformanceIndicators($timeframe),
            'market_intelligence' => $this->getMarketIntelligence($timeframe),
            'recommendations' => $this->getBusinessRecommendations($timeframe)
        ];
        
        $this->sendSuccess($dashboard);
    }
    
    /**
     * Generate predictive forecasts
     */
    public function generatePredictiveForecasts() {
        $this->checkPermission('analytics.forecasting');
        
        $forecast_type = $_GET['type'] ?? 'revenue';
        $forecast_period = intval($_GET['period'] ?? 30); // days
        $include_scenarios = $_GET['scenarios'] ?? true;
        
        $forecasts = [];
        
        switch ($forecast_type) {
            case 'revenue':
                $forecasts = $this->generateRevenueForecast($forecast_period, $include_scenarios);
                break;
                
            case 'occupancy':
                $forecasts = $this->generateOccupancyForecast($forecast_period, $include_scenarios);
                break;
                
            case 'demand':
                $forecasts = $this->generateDemandForecast($forecast_period, $include_scenarios);
                break;
                
            case 'seasonal':
                $forecasts = $this->generateSeasonalForecast($forecast_period);
                break;
                
            case 'comprehensive':
                $forecasts = [
                    'revenue' => $this->generateRevenueForecast($forecast_period, false),
                    'occupancy' => $this->generateOccupancyForecast($forecast_period, false),
                    'demand' => $this->generateDemandForecast($forecast_period, false)
                ];
                break;
                
            default:
                $this->sendError('Invalid forecast type');
                return;
        }
        
        // Add confidence intervals and model accuracy
        $forecasts['model_info'] = [
            'accuracy_score' => $this->calculateModelAccuracy($forecast_type),
            'confidence_interval' => $this->calculateConfidenceInterval($forecast_type),
            'data_quality_score' => $this->assessDataQuality($forecast_type),
            'last_trained' => $this->getLastModelTraining($forecast_type)
        ];
        
        $this->sendSuccess($forecasts);
    }
    
    /**
     * Create custom analytics report
     */
    public function createCustomReport() {
        $this->checkPermission('analytics.custom_reports');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['report_name', 'data_sources', 'metrics', 'dimensions'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        $report_config = [
            'name' => $input['report_name'],
            'description' => $input['description'] ?? '',
            'data_sources' => $input['data_sources'],
            'metrics' => $input['metrics'],
            'dimensions' => $input['dimensions'],
            'filters' => $input['filters'] ?? [],
            'date_range' => $input['date_range'] ?? 'last_30_days',
            'visualization_type' => $input['visualization_type'] ?? 'table',
            'refresh_frequency' => $input['refresh_frequency'] ?? 'daily',
            'created_by' => $this->user_id,
            'is_public' => $input['is_public'] ?? false
        ];
        
        // Validate data sources and metrics
        $validation_result = $this->validateReportConfig($report_config);
        if (!$validation_result['valid']) {
            $this->sendError($validation_result['error']);
            return;
        }
        
        // Save report configuration
        $report_id = $this->saveReportConfig($report_config);
        
        if ($report_id) {
            // Generate initial report data
            $report_data = $this->generateReportData($report_config);
            
            // Cache report results
            $this->cacheReportData($report_id, $report_data);
            
            // Trigger report created event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('analytics.custom_report_created', [
                    'report_id' => $report_id,
                    'report_name' => $report_config['name'],
                    'created_by' => $this->user_id,
                    'data_sources' => $report_config['data_sources'],
                    'metrics_count' => count($report_config['metrics'])
                ]);
            }
            
            $this->sendSuccess([
                'report_id' => $report_id,
                'report_data' => $report_data,
                'report_url' => $this->generateReportUrl($report_id),
                'share_token' => $this->generateShareToken($report_id)
            ], 'Custom report created successfully');
        } else {
            $this->sendError('Failed to create custom report');
        }
    }
    
    /**
     * Get advanced revenue analytics
     */
    public function getAdvancedRevenueAnalytics() {
        $this->checkPermission('analytics.revenue');
        
        $timeframe = $_GET['timeframe'] ?? 'month';
        $breakdown = $_GET['breakdown'] ?? 'department';
        $include_forecasts = $_GET['include_forecasts'] ?? true;
        
        $analytics = [
            'revenue_trends' => $this->getRevenueTrends($timeframe, $breakdown),
            'revenue_composition' => $this->getRevenueComposition($timeframe),
            'pricing_analysis' => $this->getPricingAnalysis($timeframe),
            'channel_performance' => $this->getChannelPerformance($timeframe),
            'segment_analysis' => $this->getGuestSegmentAnalysis($timeframe),
            'profitability_analysis' => $this->getProfitabilityAnalysis($timeframe),
            'competitive_analysis' => $this->getCompetitiveAnalysis($timeframe),
            'seasonal_patterns' => $this->getSeasonalPatterns($timeframe)
        ];
        
        if ($include_forecasts) {
            $analytics['revenue_forecast'] = $this->generateRevenueForecast(30, false);
            $analytics['growth_projections'] = $this->getGrowthProjections($timeframe);
        }
        
        $this->sendSuccess($analytics);
    }
    
    /**
     * Get guest behavior analytics
     */
    public function getGuestBehaviorAnalytics() {
        $this->checkPermission('analytics.guest_behavior');
        
        $timeframe = $_GET['timeframe'] ?? 'month';
        
        $analytics = [
            'guest_journey' => $this->getGuestJourneyAnalysis($timeframe),
            'booking_patterns' => $this->getBookingPatterns($timeframe),
            'satisfaction_metrics' => $this->getSatisfactionMetrics($timeframe),
            'loyalty_analysis' => $this->getLoyaltyAnalysis($timeframe),
            'spending_behavior' => $this->getSpendingBehavior($timeframe),
            'service_preferences' => $this->getServicePreferences($timeframe),
            'feedback_sentiment' => $this->getFeedbackSentiment($timeframe),
            'churn_analysis' => $this->getChurnAnalysis($timeframe),
            'guest_lifetime_value' => $this->getGuestLifetimeValue($timeframe),
            'segmentation' => $this->getGuestSegmentation($timeframe)
        ];
        
        $this->sendSuccess($analytics);
    }
    
    /**
     * Get operational performance analytics
     */
    public function getOperationalAnalytics() {
        $this->checkPermission('analytics.operations');
        
        $timeframe = $_GET['timeframe'] ?? 'month';
        $department = $_GET['department'] ?? 'all';
        
        $analytics = [
            'efficiency_metrics' => $this->getEfficiencyMetrics($timeframe, $department),
            'staff_performance' => $this->getStaffPerformanceAnalytics($timeframe, $department),
            'resource_utilization' => $this->getResourceUtilization($timeframe, $department),
            'quality_metrics' => $this->getQualityMetrics($timeframe, $department),
            'cost_analysis' => $this->getOperationalCostAnalysis($timeframe, $department),
            'productivity_trends' => $this->getProductivityTrends($timeframe, $department),
            'bottleneck_analysis' => $this->getBottleneckAnalysis($timeframe, $department),
            'optimization_opportunities' => $this->getOptimizationOpportunities($timeframe, $department)
        ];
        
        $this->sendSuccess($analytics);
    }
    
    /**
     * Generate executive summary report
     */
    public function generateExecutiveSummary() {
        $this->checkPermission('analytics.executive_reports');
        
        $period = $_GET['period'] ?? 'month';
        $format = $_GET['format'] ?? 'json';
        
        $summary = [
            'performance_overview' => $this->getPerformanceOverview($period),
            'key_achievements' => $this->getKeyAchievements($period),
            'financial_highlights' => $this->getFinancialHighlights($period),
            'operational_insights' => $this->getOperationalInsights($period),
            'guest_satisfaction' => $this->getGuestSatisfactionSummary($period),
            'market_position' => $this->getMarketPosition($period),
            'strategic_recommendations' => $this->getStrategicRecommendations($period),
            'risk_assessment' => $this->getRiskAssessment($period),
            'future_outlook' => $this->getFutureOutlook($period)
        ];
        
        if ($format === 'pdf') {
            $this->generateExecutivePDF($summary);
        } elseif ($format === 'excel') {
            $this->generateExecutiveExcel($summary);
        } else {
            $this->sendSuccess($summary);
        }
    }
    
    /**
     * Real-time analytics data stream
     */
    public function getRealTimeAnalytics() {
        $this->checkPermission('analytics.realtime');
        
        $metrics = $_GET['metrics'] ?? 'all';
        $interval = intval($_GET['interval'] ?? 300); // 5 minutes default
        
        $realtime_data = [
            'current_occupancy' => $this->getCurrentOccupancyRate(),
            'daily_revenue' => $this->getDailyRevenueProgress(),
            'active_bookings' => $this->getActiveBookingsCount(),
            'guest_services' => $this->getActiveGuestServices(),
            'staff_efficiency' => $this->getCurrentStaffEfficiency(),
            'system_performance' => $this->getSystemPerformanceMetrics(),
            'alerts_summary' => $this->getActiveAlertsCount(),
            'last_updated' => time()
        ];
        
        // Add specific metrics if requested
        if ($metrics !== 'all') {
            $requested_metrics = explode(',', $metrics);
            $filtered_data = [];
            foreach ($requested_metrics as $metric) {
                if (isset($realtime_data[$metric])) {
                    $filtered_data[$metric] = $realtime_data[$metric];
                }
            }
            $realtime_data = $filtered_data;
        }
        
        $this->sendSuccess($realtime_data);
    }
    
    /**
     * Get data quality assessment
     */
    public function getDataQualityAssessment() {
        $this->checkPermission('analytics.data_quality');
        
        $assessment = [
            'overall_score' => $this->calculateOverallDataQuality(),
            'completeness' => $this->assessDataCompleteness(),
            'accuracy' => $this->assessDataAccuracy(),
            'consistency' => $this->assessDataConsistency(),
            'timeliness' => $this->assessDataTimeliness(),
            'validity' => $this->assessDataValidity(),
            'issues_summary' => $this->getDataQualityIssues(),
            'recommendations' => $this->getDataQualityRecommendations(),
            'improvement_plan' => $this->getDataQualityImprovementPlan()
        ];
        
        $this->sendSuccess($assessment);
    }
    
    /**
     * Export analytics data
     */
    public function exportAnalyticsData() {
        $this->checkPermission('analytics.export');
        
        $export_type = $_GET['type'] ?? 'excel';
        $data_range = $_GET['range'] ?? 'month';
        $include_raw_data = $_GET['include_raw'] ?? false;
        
        $export_data = [
            'summary_metrics' => $this->getExportSummaryMetrics($data_range),
            'detailed_analytics' => $this->getExportDetailedAnalytics($data_range),
            'visualizations' => $this->getExportVisualizations($data_range)
        ];
        
        if ($include_raw_data) {
            $export_data['raw_data'] = $this->getExportRawData($data_range);
        }
        
        switch ($export_type) {
            case 'excel':
                $this->generateExcelExport($export_data);
                break;
                
            case 'pdf':
                $this->generatePDFExport($export_data);
                break;
                
            case 'csv':
                $this->generateCSVExport($export_data);
                break;
                
            case 'json':
                $this->sendSuccess($export_data);
                break;
                
            default:
                $this->sendError('Invalid export type');
        }
    }
    
    // Analytics Calculation Methods
    
    /**
     * Generate revenue forecast using predictive modeling
     */
    private function generateRevenueForecast($days, $include_scenarios = true) {
        // Get historical revenue data
        $historical_data = $this->getHistoricalRevenueData($days * 2); // Use 2x period for training
        
        // Apply time series forecasting
        $forecast = $this->applyTimeSeriesForecasting($historical_data, $days);
        
        // Add seasonal adjustments
        $forecast = $this->applySeasonalAdjustments($forecast);
        
        // Include scenario analysis if requested
        if ($include_scenarios) {
            $forecast['scenarios'] = [
                'optimistic' => $this->generateOptimisticScenario($forecast['base']),
                'pessimistic' => $this->generatePessimisticScenario($forecast['base']),
                'realistic' => $forecast['base']
            ];
        }
        
        return $forecast;
    }
    
    /**
     * Calculate guest lifetime value
     */
    private function getGuestLifetimeValue($timeframe) {
        $query = "SELECT 
            AVG(total_spent) as avg_spend_per_visit,
            AVG(visits_count) as avg_visits_per_guest,
            AVG(DATEDIFF(last_visit, first_visit) / 365) as avg_relationship_years,
            COUNT(*) as total_guests
            FROM (
                SELECT 
                    guest_email,
                    SUM(total_amount) as total_spent,
                    COUNT(*) as visits_count,
                    MIN(checkin_date) as first_visit,
                    MAX(checkin_date) as last_visit
                FROM bookings 
                WHERE status = 'completed'
                AND " . $this->getTimeframeCondition($timeframe) . "
                GROUP BY guest_email
            ) guest_stats";
        
        $result = $this->db->query($query);
        $data = $result->fetch_assoc();
        
        // Calculate CLV using basic formula: Avg Spend × Avg Visits × Avg Years
        $clv = $data['avg_spend_per_visit'] * $data['avg_visits_per_guest'] * max($data['avg_relationship_years'], 1);
        
        return [
            'average_clv' => round($clv, 2),
            'avg_spend_per_visit' => round($data['avg_spend_per_visit'], 2),
            'avg_visits_per_guest' => round($data['avg_visits_per_guest'], 2),
            'avg_relationship_years' => round($data['avg_relationship_years'], 2),
            'total_analyzed_guests' => intval($data['total_guests'])
        ];
    }
    
    /**
     * Get operational efficiency metrics
     */
    private function getOperationalEfficiency($timeframe) {
        return [
            'room_turnover_time' => $this->calculateRoomTurnoverTime($timeframe),
            'staff_productivity' => $this->calculateStaffProductivity($timeframe),
            'resource_utilization' => $this->calculateResourceUtilization($timeframe),
            'cost_per_occupied_room' => $this->calculateCostPerOccupiedRoom($timeframe),
            'energy_efficiency' => $this->calculateEnergyEfficiency($timeframe),
            'waste_reduction' => $this->calculateWasteReduction($timeframe),
            'automation_impact' => $this->calculateAutomationImpact($timeframe)
        ];
    }
    
    /**
     * Apply time series forecasting algorithm
     */
    private function applyTimeSeriesForecasting($historical_data, $forecast_days) {
        // Simplified moving average with trend analysis
        $window_size = min(7, count($historical_data) / 4);
        $forecast = [];
        
        // Calculate moving average and trend
        $recent_values = array_slice($historical_data, -$window_size);
        $moving_avg = array_sum($recent_values) / count($recent_values);
        
        // Calculate trend (simple linear regression)
        $trend = $this->calculateTrend($recent_values);
        
        // Generate forecast
        for ($i = 1; $i <= $forecast_days; $i++) {
            $forecast_value = $moving_avg + ($trend * $i);
            $forecast[] = [
                'date' => date('Y-m-d', strtotime("+$i days")),
                'value' => max(0, $forecast_value), // Ensure non-negative
                'confidence' => max(0.5, 1 - ($i / $forecast_days) * 0.5) // Decreasing confidence
            ];
        }
        
        return ['base' => $forecast];
    }
    
    /**
     * Calculate simple trend from data points
     */
    private function calculateTrend($data_points) {
        $n = count($data_points);
        if ($n < 2) return 0;
        
        $x_sum = array_sum(range(1, $n));
        $y_sum = array_sum($data_points);
        $xy_sum = 0;
        $x_squared_sum = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $data_points[$i];
            $xy_sum += $x * $y;
            $x_squared_sum += $x * $x;
        }
        
        $slope = ($n * $xy_sum - $x_sum * $y_sum) / ($n * $x_squared_sum - $x_sum * $x_sum);
        return $slope;
    }
    
    /**
     * Get timeframe SQL condition
     */
    private function getTimeframeCondition($timeframe) {
        switch ($timeframe) {
            case 'today':
                return "DATE(created_at) = CURDATE()";
            case 'week':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'month':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'quarter':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            case 'year':
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        }
    }
    
    private function checkPermission($permission) {
        if (!$this->permission_manager || !$this->permission_manager->hasPermission($this->user_id, $permission)) {
            $this->sendError('Permission denied', 403);
            exit();
        }
    }
    
    private function sendSuccess($data, $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => time()
        ]);
    }
    
    private function sendError($message, $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code,
            'timestamp' => time()
        ]);
    }
}

// Handle API requests
try {
    ensure_logged_in();
    
    $api = new AnalyticsAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $api->getBusinessIntelligenceDashboard();
            break;
            
        case 'forecasts':
            $api->generatePredictiveForecasts();
            break;
            
        case 'custom_report':
            $api->createCustomReport();
            break;
            
        case 'revenue_analytics':
            $api->getAdvancedRevenueAnalytics();
            break;
            
        case 'guest_behavior':
            $api->getGuestBehaviorAnalytics();
            break;
            
        case 'operational_analytics':
            $api->getOperationalAnalytics();
            break;
            
        case 'executive_summary':
            $api->generateExecutiveSummary();
            break;
            
        case 'realtime':
            $api->getRealTimeAnalytics();
            break;
            
        case 'data_quality':
            $api->getDataQualityAssessment();
            break;
            
        case 'export':
            $api->exportAnalyticsData();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
