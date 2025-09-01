<?php
/**
 * Orlando International Resorts - Advanced Report Builder API
 * Custom report generation with drag-and-drop interface and scheduled delivery
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class ReportBuilderAPI {
    private $db;
    private $permission_manager;
    private $event_manager;
    private $user_id;
    
    // Available data sources
    private $data_sources = [
        'bookings' => [
            'table' => 'bookings',
            'fields' => ['id', 'guest_name', 'email', 'phone', 'checkin_date', 'checkout_date', 'total_amount', 'status', 'created_at'],
            'joins' => ['named_rooms' => 'room_id', 'users' => 'created_by']
        ],
        'transactions' => [
            'table' => 'transactions',
            'fields' => ['id', 'amount', 'transaction_type', 'category', 'description', 'department', 'created_at'],
            'joins' => ['users' => 'created_by']
        ],
        'rooms' => [
            'table' => 'named_rooms',
            'fields' => ['id', 'room_number', 'room_type', 'floor', 'status', 'last_cleaned', 'updated_at'],
            'joins' => []
        ],
        'inventory' => [
            'table' => 'inventory_items',
            'fields' => ['id', 'name', 'category', 'current_stock', 'min_stock', 'cost_per_unit', 'department'],
            'joins' => ['suppliers' => 'supplier_id']
        ],
        'staff' => [
            'table' => 'users',
            'fields' => ['id', 'username', 'full_name', 'email', 'role', 'department', 'status', 'created_at'],
            'joins' => ['roles' => 'role_id']
        ]
    ];
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->permission_manager = getPermissionManager();
        $this->event_manager = getEventManager();
        $this->user_id = $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get available data sources and fields
     */
    public function getDataSources() {
        $this->checkPermission('reports.builder');
        
        $sources = [];
        foreach ($this->data_sources as $source_key => $source_config) {
            $sources[$source_key] = [
                'name' => ucfirst(str_replace('_', ' ', $source_key)),
                'fields' => $this->getSourceFields($source_key),
                'sample_data' => $this->getSampleData($source_key)
            ];
        }
        
        $this->sendSuccess($sources);
    }
    
    /**
     * Create new custom report
     */
    public function createReport() {
        $this->checkPermission('reports.create');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'data_source', 'selected_fields', 'report_type'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Validate data source
        if (!isset($this->data_sources[$input['data_source']])) {
            $this->sendError('Invalid data source');
            return;
        }
        
        // Validate fields
        $source_config = $this->data_sources[$input['data_source']];
        foreach ($input['selected_fields'] as $field) {
            if (!in_array($field, $source_config['fields'])) {
                $this->sendError("Invalid field: $field");
                return;
            }
        }
        
        $report_config = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'data_source' => $input['data_source'],
            'selected_fields' => json_encode($input['selected_fields']),
            'filters' => json_encode($input['filters'] ?? []),
            'grouping' => json_encode($input['grouping'] ?? []),
            'sorting' => json_encode($input['sorting'] ?? []),
            'aggregations' => json_encode($input['aggregations'] ?? []),
            'report_type' => $input['report_type'], // table, chart, summary
            'chart_config' => json_encode($input['chart_config'] ?? []),
            'date_range_type' => $input['date_range_type'] ?? 'relative',
            'date_range_value' => $input['date_range_value'] ?? 'last_30_days',
            'is_public' => $input['is_public'] ?? false,
            'auto_refresh' => $input['auto_refresh'] ?? false,
            'refresh_interval' => $input['refresh_interval'] ?? 3600, // 1 hour
            'created_by' => $this->user_id,
            'status' => 'active'
        ];
        
        $report_id = $this->insertReport($report_config);
        
        if ($report_id) {
            // Generate initial report data
            $report_data = $this->generateReportData($report_id, $report_config);
            
            // Save report data
            $this->saveReportData($report_id, $report_data);
            
            // Set up auto-refresh if enabled
            if ($report_config['auto_refresh']) {
                $this->scheduleReportRefresh($report_id, $report_config['refresh_interval']);
            }
            
            // Trigger report created event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('report.created', [
                    'report_id' => $report_id,
                    'report_name' => $report_config['name'],
                    'data_source' => $report_config['data_source'],
                    'created_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'report_id' => $report_id,
                'report_data' => $report_data,
                'report_url' => $this->generateReportUrl($report_id),
                'share_url' => $this->generateShareUrl($report_id)
            ], 'Report created successfully');
        } else {
            $this->sendError('Failed to create report');
        }
    }
    
    /**
     * Generate report data
     */
    public function generateReport() {
        $this->checkPermission('reports.view');
        
        $report_id = intval($_GET['report_id'] ?? 0);
        if (!$report_id) {
            $this->sendError('Report ID is required');
            return;
        }
        
        $report_config = $this->getReportConfig($report_id);
        if (!$report_config) {
            $this->sendError('Report not found', 404);
            return;
        }
        
        // Check permissions
        if (!$this->canAccessReport($report_id, $report_config)) {
            $this->sendError('Permission denied', 403);
            return;
        }
        
        // Apply runtime filters if provided
        $runtime_filters = $_GET['filters'] ?? '';
        if ($runtime_filters) {
            $decoded_filters = json_decode($runtime_filters, true);
            if ($decoded_filters) {
                $existing_filters = json_decode($report_config['filters'], true) ?? [];
                $report_config['filters'] = json_encode(array_merge($existing_filters, $decoded_filters));
            }
        }
        
        // Apply runtime date range if provided
        $date_range = $_GET['date_range'] ?? '';
        if ($date_range) {
            $report_config['date_range_value'] = $date_range;
        }
        
        $report_data = $this->generateReportData($report_id, $report_config);
        
        // Update last generated timestamp
        $this->updateReportLastGenerated($report_id);
        
        $this->sendSuccess($report_data);
    }
    
    /**
     * Get saved reports list
     */
    public function getReportsList() {
        $this->checkPermission('reports.view');
        
        $user_reports_only = $_GET['user_only'] ?? false;
        $category = $_GET['category'] ?? '';
        
        $where_conditions = ["status = 'active'"];
        $params = [];
        $param_types = "";
        
        if ($user_reports_only) {
            $where_conditions[] = "created_by = ?";
            $params[] = $this->user_id;
            $param_types .= "i";
        } else {
            $where_conditions[] = "(created_by = ? OR is_public = 1)";
            $params[] = $this->user_id;
            $param_types .= "i";
        }
        
        if ($category) {
            $where_conditions[] = "data_source = ?";
            $params[] = $category;
            $param_types .= "s";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
            r.*,
            u.full_name as created_by_name,
            COUNT(rr.id) as run_count,
            MAX(rr.generated_at) as last_run
            FROM custom_reports r
            LEFT JOIN users u ON r.created_by = u.id
            LEFT JOIN report_runs rr ON r.id = rr.report_id
            WHERE $where_clause
            GROUP BY r.id
            ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($query);
        if ($param_types) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $reports = [];
        
        while ($row = $result->fetch_assoc()) {
            $row['selected_fields'] = json_decode($row['selected_fields'], true);
            $row['filters'] = json_decode($row['filters'], true);
            $row['can_edit'] = ($row['created_by'] == $this->user_id || $this->hasPermission('reports.edit_all'));
            $reports[] = $row;
        }
        
        $this->sendSuccess($reports);
    }
    
    /**
     * Update existing report
     */
    public function updateReport() {
        $this->checkPermission('reports.edit');
        
        $report_id = intval($_GET['report_id'] ?? 0);
        if (!$report_id) {
            $this->sendError('Report ID is required');
            return;
        }
        
        $report_config = $this->getReportConfig($report_id);
        if (!$report_config) {
            $this->sendError('Report not found', 404);
            return;
        }
        
        // Check edit permissions
        if ($report_config['created_by'] != $this->user_id && !$this->hasPermission('reports.edit_all')) {
            $this->sendError('Permission denied', 403);
            return;
        }
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $updateable_fields = [
            'name', 'description', 'selected_fields', 'filters', 'grouping', 
            'sorting', 'aggregations', 'chart_config', 'is_public', 
            'auto_refresh', 'refresh_interval'
        ];
        
        $update_data = [];
        foreach ($updateable_fields as $field) {
            if (isset($input[$field])) {
                if (in_array($field, ['selected_fields', 'filters', 'grouping', 'sorting', 'aggregations', 'chart_config'])) {
                    $update_data[$field] = json_encode($input[$field]);
                } else {
                    $update_data[$field] = $input[$field];
                }
            }
        }
        
        if (!empty($update_data)) {
            $update_data['updated_at'] = date('Y-m-d H:i:s');
            $success = $this->updateReportConfig($report_id, $update_data);
            
            if ($success) {
                // Regenerate report data with new configuration
                $updated_config = array_merge($report_config, $update_data);
                $report_data = $this->generateReportData($report_id, $updated_config);
                $this->saveReportData($report_id, $report_data);
                
                $this->sendSuccess(['report_id' => $report_id], 'Report updated successfully');
            } else {
                $this->sendError('Failed to update report');
            }
        } else {
            $this->sendError('No valid fields to update');
        }
    }
    
    /**
     * Delete report
     */
    public function deleteReport() {
        $this->checkPermission('reports.delete');
        
        $report_id = intval($_GET['report_id'] ?? 0);
        if (!$report_id) {
            $this->sendError('Report ID is required');
            return;
        }
        
        $report_config = $this->getReportConfig($report_id);
        if (!$report_config) {
            $this->sendError('Report not found', 404);
            return;
        }
        
        // Check delete permissions
        if ($report_config['created_by'] != $this->user_id && !$this->hasPermission('reports.delete_all')) {
            $this->sendError('Permission denied', 403);
            return;
        }
        
        $success = $this->updateReportConfig($report_id, ['status' => 'deleted']);
        
        if ($success) {
            // Clean up associated data
            $this->cleanupReportData($report_id);
            
            $this->sendSuccess(['report_id' => $report_id], 'Report deleted successfully');
        } else {
            $this->sendError('Failed to delete report');
        }
    }
    
    /**
     * Schedule report delivery
     */
    public function scheduleReport() {
        $this->checkPermission('reports.schedule');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['report_id', 'schedule_type', 'recipients'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        $report_id = intval($input['report_id']);
        $report_config = $this->getReportConfig($report_id);
        if (!$report_config) {
            $this->sendError('Report not found', 404);
            return;
        }
        
        $schedule_config = [
            'report_id' => $report_id,
            'schedule_type' => $input['schedule_type'], // daily, weekly, monthly
            'schedule_value' => $input['schedule_value'] ?? '', // day of week, day of month, etc.
            'schedule_time' => $input['schedule_time'] ?? '09:00:00',
            'recipients' => json_encode($input['recipients']),
            'format' => $input['format'] ?? 'pdf',
            'include_data' => $input['include_data'] ?? true,
            'is_active' => true,
            'created_by' => $this->user_id
        ];
        
        $schedule_id = $this->insertReportSchedule($schedule_config);
        
        if ($schedule_id) {
            // Set up cron job or task scheduler entry
            $this->setupScheduledTask($schedule_id, $schedule_config);
            
            $this->sendSuccess([
                'schedule_id' => $schedule_id,
                'next_run' => $this->calculateNextRun($schedule_config)
            ], 'Report scheduled successfully');
        } else {
            $this->sendError('Failed to schedule report');
        }
    }
    
    /**
     * Export report data
     */
    public function exportReport() {
        $this->checkPermission('reports.export');
        
        $report_id = intval($_GET['report_id'] ?? 0);
        $format = $_GET['format'] ?? 'excel';
        
        if (!$report_id) {
            $this->sendError('Report ID is required');
            return;
        }
        
        $report_config = $this->getReportConfig($report_id);
        if (!$report_config) {
            $this->sendError('Report not found', 404);
            return;
        }
        
        if (!$this->canAccessReport($report_id, $report_config)) {
            $this->sendError('Permission denied', 403);
            return;
        }
        
        $report_data = $this->generateReportData($report_id, $report_config);
        
        switch ($format) {
            case 'excel':
                $this->exportToExcel($report_config, $report_data);
                break;
                
            case 'pdf':
                $this->exportToPDF($report_config, $report_data);
                break;
                
            case 'csv':
                $this->exportToCSV($report_config, $report_data);
                break;
                
            case 'json':
                header('Content-Type: application/json');
                header('Content-Disposition: attachment; filename="report-' . $report_id . '.json"');
                echo json_encode($report_data, JSON_PRETTY_PRINT);
                break;
                
            default:
                $this->sendError('Invalid export format');
        }
    }
    
    // Helper Methods
    
    /**
     * Generate actual report data from configuration
     */
    private function generateReportData($report_id, $config) {
        $data_source = $config['data_source'];
        $source_config = $this->data_sources[$data_source];
        
        // Build SQL query
        $query = $this->buildQuery($config, $source_config);
        
        // Execute query
        $result = $this->db->query($query);
        $raw_data = [];
        
        while ($row = $result->fetch_assoc()) {
            $raw_data[] = $row;
        }
        
        // Process data based on report type
        $processed_data = $this->processReportData($raw_data, $config);
        
        // Generate metadata
        $metadata = [
            'report_id' => $report_id,
            'generated_at' => date('Y-m-d H:i:s'),
            'data_source' => $data_source,
            'total_records' => count($raw_data),
            'query_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
        ];
        
        return [
            'metadata' => $metadata,
            'data' => $processed_data,
            'raw_data' => $raw_data
        ];
    }
    
    /**
     * Build SQL query from report configuration
     */
    private function buildQuery($config, $source_config) {
        $table = $source_config['table'];
        $selected_fields = json_decode($config['selected_fields'], true);
        $filters = json_decode($config['filters'], true) ?? [];
        $grouping = json_decode($config['grouping'], true) ?? [];
        $sorting = json_decode($config['sorting'], true) ?? [];
        $aggregations = json_decode($config['aggregations'], true) ?? [];
        
        // Build SELECT clause
        $select_parts = [];
        foreach ($selected_fields as $field) {
            $select_parts[] = $table . '.' . $field;
        }
        
        // Add aggregations
        foreach ($aggregations as $agg) {
            $select_parts[] = $agg['function'] . '(' . $table . '.' . $agg['field'] . ') as ' . $agg['alias'];
        }
        
        $select_clause = implode(', ', $select_parts);
        
        // Build FROM clause with JOINs
        $from_clause = $table;
        foreach ($source_config['joins'] as $join_table => $join_field) {
            $from_clause .= " LEFT JOIN $join_table ON $table.$join_field = $join_table.id";
        }
        
        // Build WHERE clause
        $where_conditions = ["$table.status != 'deleted'"];
        
        foreach ($filters as $filter) {
            $condition = $this->buildFilterCondition($filter, $table);
            if ($condition) {
                $where_conditions[] = $condition;
            }
        }
        
        // Add date range filter
        $date_condition = $this->buildDateRangeCondition($config, $table);
        if ($date_condition) {
            $where_conditions[] = $date_condition;
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        // Build GROUP BY clause
        $group_clause = '';
        if (!empty($grouping)) {
            $group_fields = array_map(function($field) use ($table) {
                return $table . '.' . $field;
            }, $grouping);
            $group_clause = 'GROUP BY ' . implode(', ', $group_fields);
        }
        
        // Build ORDER BY clause
        $order_clause = '';
        if (!empty($sorting)) {
            $order_parts = [];
            foreach ($sorting as $sort) {
                $order_parts[] = $table . '.' . $sort['field'] . ' ' . $sort['direction'];
            }
            $order_clause = 'ORDER BY ' . implode(', ', $order_parts);
        }
        
        // Combine all parts
        $query = "SELECT $select_clause FROM $from_clause WHERE $where_clause";
        if ($group_clause) $query .= " $group_clause";
        if ($order_clause) $query .= " $order_clause";
        
        return $query;
    }
    
    /**
     * Get sample data for data source
     */
    private function getSampleData($source_key, $limit = 5) {
        $source_config = $this->data_sources[$source_key];
        $table = $source_config['table'];
        $fields = implode(', ', array_slice($source_config['fields'], 0, 5));
        
        $query = "SELECT $fields FROM $table LIMIT $limit";
        $result = $this->db->query($query);
        
        $sample_data = [];
        while ($row = $result->fetch_assoc()) {
            $sample_data[] = $row;
        }
        
        return $sample_data;
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
    
    $api = new ReportBuilderAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'data_sources':
            $api->getDataSources();
            break;
            
        case 'create':
            $api->createReport();
            break;
            
        case 'generate':
            $api->generateReport();
            break;
            
        case 'list':
            $api->getReportsList();
            break;
            
        case 'update':
            $api->updateReport();
            break;
            
        case 'delete':
            $api->deleteReport();
            break;
            
        case 'schedule':
            $api->scheduleReport();
            break;
            
        case 'export':
            $api->exportReport();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Report Builder API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
