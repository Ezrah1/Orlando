<?php
/**
 * Orlando International Resorts - Inventory Management API
 * Real-time inventory tracking with automated reordering and consumption analytics
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class InventoryAPI {
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
     * Get inventory dashboard
     */
    public function getInventoryDashboard() {
        $this->checkPermission('inventory.view');
        
        $dashboard = [
            'inventory_summary' => $this->getInventorySummary(),
            'low_stock_alerts' => $this->getLowStockAlerts(),
            'recent_movements' => $this->getRecentMovements(20),
            'consumption_trends' => $this->getConsumptionTrends(),
            'reorder_suggestions' => $this->getReorderSuggestions(),
            'department_breakdown' => $this->getDepartmentBreakdown(),
            'supplier_performance' => $this->getSupplierPerformance(),
            'cost_analysis' => $this->getCostAnalysis()
        ];
        
        $this->sendSuccess($dashboard);
    }
    
    /**
     * Get inventory items with filtering
     */
    public function getInventoryItems() {
        $this->checkPermission('inventory.view');
        
        $filters = [
            'category' => $_GET['category'] ?? '',
            'department' => $_GET['department'] ?? '',
            'location' => $_GET['location'] ?? '',
            'status' => $_GET['status'] ?? '',
            'search' => $_GET['search'] ?? '',
            'low_stock_only' => $_GET['low_stock_only'] ?? false,
            'limit' => intval($_GET['limit'] ?? 50),
            'offset' => intval($_GET['offset'] ?? 0)
        ];
        
        $items = $this->fetchInventoryItems($filters);
        $total_count = $this->getInventoryItemCount($filters);
        
        // Add calculated fields
        foreach ($items as &$item) {
            $item['days_of_stock'] = $this->calculateDaysOfStock($item);
            $item['reorder_status'] = $this->getReorderStatus($item);
            $item['last_movement'] = $this->getLastMovement($item['id']);
            $item['consumption_rate'] = $this->getConsumptionRate($item['id']);
        }
        
        $this->sendSuccess([
            'items' => $items,
            'pagination' => [
                'total' => $total_count,
                'limit' => $filters['limit'],
                'offset' => $filters['offset'],
                'pages' => ceil($total_count / $filters['limit'])
            ],
            'filters_applied' => $filters
        ]);
    }
    
    /**
     * Add new inventory item
     */
    public function addInventoryItem() {
        $this->checkPermission('inventory.add');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['name', 'category', 'unit', 'min_stock', 'max_stock'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Check if item already exists
        if ($this->itemExists($input['name'], $input['category'])) {
            $this->sendError('Item already exists in this category');
            return;
        }
        
        $item_data = [
            'name' => $input['name'],
            'description' => $input['description'] ?? '',
            'category' => $input['category'],
            'department' => $input['department'] ?? 'general',
            'unit' => $input['unit'],
            'current_stock' => floatval($input['current_stock'] ?? 0),
            'min_stock' => floatval($input['min_stock']),
            'max_stock' => floatval($input['max_stock']),
            'reorder_point' => floatval($input['reorder_point'] ?? $input['min_stock']),
            'cost_per_unit' => floatval($input['cost_per_unit'] ?? 0),
            'supplier_id' => $input['supplier_id'] ?? null,
            'location' => $input['location'] ?? '',
            'barcode' => $input['barcode'] ?? '',
            'created_by' => $this->user_id,
            'status' => 'active'
        ];
        
        $item_id = $this->insertInventoryItem($item_data);
        
        if ($item_id) {
            // Log initial stock if provided
            if ($item_data['current_stock'] > 0) {
                $this->logInventoryMovement($item_id, $item_data['current_stock'], 'in', 'Initial stock');
            }
            
            // Trigger item added event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('inventory.item_added', [
                    'item_id' => $item_id,
                    'name' => $item_data['name'],
                    'category' => $item_data['category'],
                    'initial_stock' => $item_data['current_stock'],
                    'created_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'item_id' => $item_id,
                'item_code' => $this->generateItemCode($item_id)
            ], 'Inventory item added successfully');
        } else {
            $this->sendError('Failed to add inventory item');
        }
    }
    
    /**
     * Update inventory stock
     */
    public function updateStock() {
        $this->checkPermission('inventory.update');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['item_id', 'quantity', 'movement_type'];
        foreach ($required as $field) {
            if (!isset($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        $item_id = intval($input['item_id']);
        $quantity = floatval($input['quantity']);
        $movement_type = $input['movement_type']; // 'in', 'out', 'adjustment'
        $reason = $input['reason'] ?? '';
        $reference_type = $input['reference_type'] ?? '';
        $reference_id = $input['reference_id'] ?? null;
        
        // Get current item data
        $item = $this->getInventoryItemById($item_id);
        if (!$item) {
            $this->sendError('Inventory item not found', 404);
            return;
        }
        
        $old_stock = floatval($item['current_stock']);
        
        // Calculate new stock
        switch ($movement_type) {
            case 'in':
                $new_stock = $old_stock + $quantity;
                break;
            case 'out':
                $new_stock = $old_stock - $quantity;
                break;
            case 'adjustment':
                $new_stock = $quantity; // Direct adjustment to specific quantity
                $quantity = $new_stock - $old_stock; // Calculate the difference for logging
                break;
            default:
                $this->sendError('Invalid movement type');
                return;
        }
        
        if ($new_stock < 0) {
            $this->sendError('Insufficient stock for this operation');
            return;
        }
        
        // Update stock level
        $success = $this->updateItemStock($item_id, $new_stock);
        
        if ($success) {
            // Log inventory movement
            $movement_id = $this->logInventoryMovement(
                $item_id, 
                $quantity, 
                $movement_type, 
                $reason, 
                $reference_type, 
                $reference_id
            );
            
            // Check for alerts
            $alerts = $this->checkStockAlerts($item_id, $new_stock, $item);
            
            // Trigger events based on stock levels
            $this->triggerStockEvents($item_id, $new_stock, $item);
            
            // Update last movement timestamp
            $this->updateLastMovement($item_id);
            
            $this->sendSuccess([
                'item_id' => $item_id,
                'movement_id' => $movement_id,
                'old_stock' => $old_stock,
                'new_stock' => $new_stock,
                'quantity_changed' => $quantity,
                'movement_type' => $movement_type,
                'alerts' => $alerts
            ], 'Stock updated successfully');
        } else {
            $this->sendError('Failed to update stock');
        }
    }
    
    /**
     * Process inventory transaction (consumption/receipt)
     */
    public function processTransaction() {
        $this->checkPermission('inventory.transactions');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['items', 'transaction_type'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        $items = $input['items']; // Array of items with quantities
        $transaction_type = $input['transaction_type']; // 'consumption', 'receipt', 'transfer'
        $department = $input['department'] ?? '';
        $reference = $input['reference'] ?? '';
        $notes = $input['notes'] ?? '';
        
        // Validate all items first
        foreach ($items as $item_data) {
            if (!isset($item_data['item_id']) || !isset($item_data['quantity'])) {
                $this->sendError('Each item must have item_id and quantity');
                return;
            }
            
            $item = $this->getInventoryItemById($item_data['item_id']);
            if (!$item) {
                $this->sendError("Item ID {$item_data['item_id']} not found");
                return;
            }
            
            // Check stock availability for consumption
            if ($transaction_type === 'consumption' && $item['current_stock'] < $item_data['quantity']) {
                $this->sendError("Insufficient stock for item: {$item['name']}");
                return;
            }
        }
        
        // Create transaction record
        $transaction_data = [
            'transaction_type' => $transaction_type,
            'department' => $department,
            'reference' => $reference,
            'notes' => $notes,
            'total_items' => count($items),
            'created_by' => $this->user_id,
            'status' => 'completed'
        ];
        
        $transaction_id = $this->insertInventoryTransaction($transaction_data);
        
        if ($transaction_id) {
            // Process each item
            $processed_items = [];
            $total_value = 0;
            
            foreach ($items as $item_data) {
                $item_id = intval($item_data['item_id']);
                $quantity = floatval($item_data['quantity']);
                
                // Update stock
                $movement_type = $transaction_type === 'consumption' ? 'out' : 'in';
                $this->updateStock([
                    'item_id' => $item_id,
                    'quantity' => $quantity,
                    'movement_type' => $movement_type,
                    'reason' => $transaction_type,
                    'reference_type' => 'transaction',
                    'reference_id' => $transaction_id
                ]);
                
                // Add to transaction items
                $item = $this->getInventoryItemById($item_id);
                $item_value = $quantity * $item['cost_per_unit'];
                $total_value += $item_value;
                
                $this->insertTransactionItem($transaction_id, $item_id, $quantity, $item_value);
                
                $processed_items[] = [
                    'item_id' => $item_id,
                    'name' => $item['name'],
                    'quantity' => $quantity,
                    'value' => $item_value
                ];
            }
            
            // Update transaction total value
            $this->updateTransactionValue($transaction_id, $total_value);
            
            // Trigger transaction event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('inventory.transaction_processed', [
                    'transaction_id' => $transaction_id,
                    'transaction_type' => $transaction_type,
                    'department' => $department,
                    'items_count' => count($items),
                    'total_value' => $total_value,
                    'processed_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'transaction_id' => $transaction_id,
                'processed_items' => $processed_items,
                'total_value' => $total_value,
                'transaction_number' => $this->generateTransactionNumber($transaction_id)
            ], 'Transaction processed successfully');
        } else {
            $this->sendError('Failed to process transaction');
        }
    }
    
    /**
     * Generate automatic reorder suggestions
     */
    public function generateReorderSuggestions() {
        $this->checkPermission('inventory.reorder');
        
        $department = $_GET['department'] ?? '';
        $urgency = $_GET['urgency'] ?? 'all'; // 'urgent', 'normal', 'all'
        
        $suggestions = $this->getAutomaticReorderSuggestions($department, $urgency);
        
        foreach ($suggestions as &$suggestion) {
            $suggestion['suggested_quantity'] = $this->calculateReorderQuantity($suggestion);
            $suggestion['estimated_cost'] = $suggestion['suggested_quantity'] * $suggestion['cost_per_unit'];
            $suggestion['supplier_info'] = $this->getSupplierInfo($suggestion['supplier_id']);
            $suggestion['lead_time'] = $this->getSupplierLeadTime($suggestion['supplier_id']);
        }
        
        $this->sendSuccess([
            'suggestions' => $suggestions,
            'summary' => [
                'total_items' => count($suggestions),
                'estimated_total_cost' => array_sum(array_column($suggestions, 'estimated_cost')),
                'urgent_items' => count(array_filter($suggestions, function($s) { 
                    return $s['current_stock'] <= 0; 
                }))
            ]
        ]);
    }
    
    /**
     * Create purchase order from reorder suggestions
     */
    public function createPurchaseOrder() {
        $this->checkPermission('inventory.purchase_orders');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['supplier_id', 'items'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        $supplier_id = intval($input['supplier_id']);
        $items = $input['items'];
        $priority = $input['priority'] ?? 'normal';
        $notes = $input['notes'] ?? '';
        
        // Validate supplier
        $supplier = $this->getSupplierById($supplier_id);
        if (!$supplier) {
            $this->sendError('Supplier not found', 404);
            return;
        }
        
        // Create purchase order
        $po_data = [
            'supplier_id' => $supplier_id,
            'priority' => $priority,
            'notes' => $notes,
            'total_items' => count($items),
            'status' => 'pending',
            'created_by' => $this->user_id,
            'expected_delivery' => $this->calculateExpectedDelivery($supplier_id)
        ];
        
        $po_id = $this->insertPurchaseOrder($po_data);
        
        if ($po_id) {
            $total_amount = 0;
            
            // Add items to purchase order
            foreach ($items as $item_data) {
                $item_id = intval($item_data['item_id']);
                $quantity = floatval($item_data['quantity']);
                
                $item = $this->getInventoryItemById($item_id);
                $line_total = $quantity * $item['cost_per_unit'];
                $total_amount += $line_total;
                
                $this->insertPurchaseOrderItem($po_id, $item_id, $quantity, $item['cost_per_unit'], $line_total);
            }
            
            // Update purchase order total
            $this->updatePurchaseOrderTotal($po_id, $total_amount);
            
            // Generate PO number
            $po_number = $this->generatePONumber($po_id);
            
            // Trigger purchase order event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('inventory.purchase_order_created', [
                    'po_id' => $po_id,
                    'po_number' => $po_number,
                    'supplier_id' => $supplier_id,
                    'supplier_name' => $supplier['name'],
                    'total_amount' => $total_amount,
                    'items_count' => count($items),
                    'priority' => $priority,
                    'created_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'po_id' => $po_id,
                'po_number' => $po_number,
                'total_amount' => $total_amount,
                'supplier' => $supplier,
                'expected_delivery' => $po_data['expected_delivery']
            ], 'Purchase order created successfully');
        } else {
            $this->sendError('Failed to create purchase order');
        }
    }
    
    /**
     * Get inventory analytics
     */
    public function getInventoryAnalytics() {
        $this->checkPermission('inventory.analytics');
        
        $period = $_GET['period'] ?? 'month';
        $department = $_GET['department'] ?? '';
        
        $analytics = [
            'consumption_trends' => $this->getConsumptionTrends($period, $department),
            'cost_analysis' => $this->getCostAnalysis($period, $department),
            'turnover_rates' => $this->getTurnoverRates($period, $department),
            'waste_analysis' => $this->getWasteAnalysis($period, $department),
            'supplier_performance' => $this->getSupplierPerformanceAnalytics($period),
            'stock_accuracy' => $this->getStockAccuracyMetrics($period),
            'seasonal_patterns' => $this->getSeasonalPatterns($department),
            'forecasting' => $this->generateDemandForecast($period, $department)
        ];
        
        $this->sendSuccess($analytics);
    }
    
    // Helper Methods
    
    private function getInventorySummary() {
        $query = "SELECT 
            COUNT(*) as total_items,
            SUM(current_stock * cost_per_unit) as total_value,
            COUNT(CASE WHEN current_stock <= min_stock THEN 1 END) as low_stock_items,
            COUNT(CASE WHEN current_stock <= 0 THEN 1 END) as out_of_stock_items,
            COUNT(CASE WHEN status = 'active' THEN 1 END) as active_items
            FROM inventory_items 
            WHERE status != 'deleted'";
        
        $result = $this->db->query($query);
        return $result->fetch_assoc();
    }
    
    private function getLowStockAlerts() {
        $query = "SELECT 
            i.*,
            CASE 
                WHEN current_stock <= 0 THEN 'critical'
                WHEN current_stock <= min_stock * 0.5 THEN 'urgent'
                WHEN current_stock <= min_stock THEN 'warning'
                ELSE 'normal'
            END as alert_level
            FROM inventory_items i
            WHERE current_stock <= min_stock 
            AND status = 'active'
            ORDER BY 
                CASE 
                    WHEN current_stock <= 0 THEN 1
                    WHEN current_stock <= min_stock * 0.5 THEN 2
                    ELSE 3
                END,
                current_stock ASC";
        
        $result = $this->db->query($query);
        $alerts = [];
        
        while ($row = $result->fetch_assoc()) {
            $alerts[] = $row;
        }
        
        return $alerts;
    }
    
    private function fetchInventoryItems($filters) {
        $where_conditions = ["i.status != 'deleted'"];
        $params = [];
        $param_types = "";
        
        if ($filters['category']) {
            $where_conditions[] = "i.category = ?";
            $params[] = $filters['category'];
            $param_types .= "s";
        }
        
        if ($filters['department']) {
            $where_conditions[] = "i.department = ?";
            $params[] = $filters['department'];
            $param_types .= "s";
        }
        
        if ($filters['location']) {
            $where_conditions[] = "i.location = ?";
            $params[] = $filters['location'];
            $param_types .= "s";
        }
        
        if ($filters['status']) {
            $where_conditions[] = "i.status = ?";
            $params[] = $filters['status'];
            $param_types .= "s";
        }
        
        if ($filters['search']) {
            $where_conditions[] = "(i.name LIKE ? OR i.description LIKE ? OR i.barcode LIKE ?)";
            $search_term = "%" . $filters['search'] . "%";
            $params[] = $search_term;
            $params[] = $search_term;
            $params[] = $search_term;
            $param_types .= "sss";
        }
        
        if ($filters['low_stock_only']) {
            $where_conditions[] = "i.current_stock <= i.min_stock";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
            i.*,
            s.name as supplier_name,
            u.full_name as created_by_name
            FROM inventory_items i
            LEFT JOIN suppliers s ON i.supplier_id = s.id
            LEFT JOIN users u ON i.created_by = u.id
            WHERE $where_clause
            ORDER BY i.name
            LIMIT ? OFFSET ?";
        
        $params[] = $filters['limit'];
        $params[] = $filters['offset'];
        $param_types .= "ii";
        
        $stmt = $this->db->prepare($query);
        if ($param_types) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $items = [];
        
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        
        return $items;
    }
    
    private function checkStockAlerts($item_id, $new_stock, $item) {
        $alerts = [];
        
        if ($new_stock <= 0) {
            $alerts[] = [
                'type' => 'out_of_stock',
                'level' => 'critical',
                'message' => 'Item is out of stock'
            ];
        } elseif ($new_stock <= $item['min_stock'] * 0.5) {
            $alerts[] = [
                'type' => 'critically_low',
                'level' => 'urgent',
                'message' => 'Stock is critically low'
            ];
        } elseif ($new_stock <= $item['min_stock']) {
            $alerts[] = [
                'type' => 'low_stock',
                'level' => 'warning',
                'message' => 'Stock is below minimum level'
            ];
        }
        
        return $alerts;
    }
    
    private function triggerStockEvents($item_id, $new_stock, $item) {
        if (!$this->event_manager) return;
        
        if ($new_stock <= 0) {
            $this->event_manager->triggerEvent('inventory.out_of_stock', [
                'item_id' => $item_id,
                'item_name' => $item['name'],
                'category' => $item['category'],
                'department' => $item['department'],
                'location' => $item['location']
            ]);
        } elseif ($new_stock <= $item['min_stock']) {
            $this->event_manager->triggerEvent('inventory.low_stock', [
                'item_id' => $item_id,
                'item_name' => $item['name'],
                'current_stock' => $new_stock,
                'min_stock' => $item['min_stock'],
                'reorder_point' => $item['reorder_point'],
                'category' => $item['category'],
                'department' => $item['department']
            ]);
        }
        
        // Check for reorder trigger
        if ($new_stock <= $item['reorder_point']) {
            $this->event_manager->triggerEvent('inventory.reorder_triggered', [
                'item_id' => $item_id,
                'item_name' => $item['name'],
                'current_stock' => $new_stock,
                'reorder_point' => $item['reorder_point'],
                'suggested_quantity' => $this->calculateReorderQuantity($item),
                'supplier_id' => $item['supplier_id']
            ]);
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
    
    $api = new InventoryAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $api->getInventoryDashboard();
            break;
            
        case 'items':
            $api->getInventoryItems();
            break;
            
        case 'add_item':
            $api->addInventoryItem();
            break;
            
        case 'update_stock':
            $api->updateStock();
            break;
            
        case 'process_transaction':
            $api->processTransaction();
            break;
            
        case 'reorder_suggestions':
            $api->generateReorderSuggestions();
            break;
            
        case 'create_purchase_order':
            $api->createPurchaseOrder();
            break;
            
        case 'analytics':
            $api->getInventoryAnalytics();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Inventory API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
