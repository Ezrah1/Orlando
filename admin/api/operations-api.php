<?php
/**
 * Orlando International Resorts - Operations Management API
 * Real-time operations control with room status, staff coordination, and workflow automation
 * 
 * @version 1.0.0
 * @author Orlando International Resorts Development Team
 * @created December 20, 2024
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../includes/PermissionManager.php';
require_once __DIR__ . '/../includes/EventManager.php';

class OperationsAPI {
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
     * Get comprehensive operations dashboard
     */
    public function getOperationsDashboard() {
        $this->checkPermission('operations.view');
        
        $dashboard = [
            'room_status_overview' => $this->getRoomStatusOverview(),
            'staff_status' => $this->getStaffStatus(),
            'housekeeping_summary' => $this->getHousekeepingSummary(),
            'maintenance_summary' => $this->getMaintenanceSummary(),
            'guest_services' => $this->getGuestServicesSummary(),
            'daily_operations' => $this->getDailyOperations(),
            'alerts_notifications' => $this->getOperationalAlerts(),
            'performance_metrics' => $this->getOperationalMetrics()
        ];
        
        $this->sendSuccess($dashboard);
    }
    
    /**
     * Real-time room status management
     */
    public function getRoomStatus() {
        $this->checkPermission('room.view');
        
        $floor = $_GET['floor'] ?? '';
        $status = $_GET['status'] ?? '';
        $room_type = $_GET['room_type'] ?? '';
        
        $rooms = $this->fetchRoomStatus($floor, $status, $room_type);
        
        // Add real-time occupancy data
        foreach ($rooms as &$room) {
            $room['current_guest'] = $this->getCurrentGuest($room['id']);
            $room['next_arrival'] = $this->getNextArrival($room['id']);
            $room['next_departure'] = $this->getNextDeparture($room['id']);
            $room['housekeeping_status'] = $this->getHousekeepingStatus($room['id']);
            $room['maintenance_issues'] = $this->getMaintenanceIssues($room['id']);
            $room['amenities_status'] = $this->getAmenitiesStatus($room['id']);
        }
        
        $this->sendSuccess([
            'rooms' => $rooms,
            'summary' => $this->calculateRoomSummary($rooms),
            'last_updated' => time()
        ]);
    }
    
    /**
     * Update room status with automation
     */
    public function updateRoomStatus() {
        $this->checkPermission('room.status_update');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $room_id = intval($input['room_id'] ?? 0);
        $new_status = $input['status'] ?? '';
        $notes = $input['notes'] ?? '';
        $reason = $input['reason'] ?? '';
        
        if (!$room_id || !$new_status) {
            $this->sendError('Room ID and status are required');
            return;
        }
        
        // Get current room data
        $current_room = $this->getRoomById($room_id);
        if (!$current_room) {
            $this->sendError('Room not found', 404);
            return;
        }
        
        $old_status = $current_room['status'];
        
        // Validate status transition
        if (!$this->isValidStatusTransition($old_status, $new_status)) {
            $this->sendError('Invalid status transition from ' . $old_status . ' to ' . $new_status);
            return;
        }
        
        // Update room status
        $update_data = [
            'status' => $new_status,
            'notes' => $notes,
            'last_status_change' => date('Y-m-d H:i:s'),
            'updated_by' => $this->user_id
        ];
        
        $success = $this->updateRoomRecord($room_id, $update_data);
        
        if ($success) {
            // Log status change
            $this->logRoomStatusChange($room_id, $old_status, $new_status, $reason);
            
            // Trigger automated workflows
            $this->triggerStatusWorkflows($room_id, $old_status, $new_status);
            
            // Trigger events
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('room.status_changed', [
                    'room_id' => $room_id,
                    'room_number' => $current_room['room_number'],
                    'old_status' => $old_status,
                    'new_status' => $new_status,
                    'notes' => $notes,
                    'reason' => $reason,
                    'updated_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'room_id' => $room_id,
                'old_status' => $old_status,
                'new_status' => $new_status,
                'workflows_triggered' => $this->getTriggeredWorkflows($old_status, $new_status)
            ], 'Room status updated successfully');
        } else {
            $this->sendError('Failed to update room status');
        }
    }
    
    /**
     * Staff coordination and scheduling
     */
    public function getStaffCoordination() {
        $this->checkPermission('staff.view');
        
        $department = $_GET['department'] ?? '';
        $shift = $_GET['shift'] ?? '';
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $coordination = [
            'on_duty_staff' => $this->getOnDutyStaff($department, $shift, $date),
            'shift_schedules' => $this->getShiftSchedules($department, $date),
            'task_assignments' => $this->getTaskAssignments($department, $date),
            'staff_locations' => $this->getStaffLocations($department),
            'performance_metrics' => $this->getStaffPerformance($department, $date),
            'break_schedules' => $this->getBreakSchedules($department, $date),
            'overtime_tracking' => $this->getOvertimeTracking($department, $date)
        ];
        
        $this->sendSuccess($coordination);
    }
    
    /**
     * Assign staff to tasks
     */
    public function assignStaffTask() {
        $this->checkPermission('staff.assign_tasks');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['staff_id', 'task_type', 'description', 'priority'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Validate staff availability
        $staff_available = $this->checkStaffAvailability($input['staff_id']);
        if (!$staff_available) {
            $this->sendError('Staff member is not available');
            return;
        }
        
        // Create task assignment
        $assignment_data = [
            'staff_id' => intval($input['staff_id']),
            'task_type' => $input['task_type'],
            'description' => $input['description'],
            'priority' => $input['priority'],
            'room_id' => $input['room_id'] ?? null,
            'department' => $input['department'] ?? '',
            'estimated_duration' => $input['estimated_duration'] ?? 0,
            'scheduled_start' => $input['scheduled_start'] ?? date('Y-m-d H:i:s'),
            'assigned_by' => $this->user_id,
            'status' => 'assigned'
        ];
        
        $task_id = $this->insertTaskAssignment($assignment_data);
        
        if ($task_id) {
            // Update staff status
            $this->updateStaffStatus($input['staff_id'], 'assigned_task');
            
            // Send notification to staff
            $this->notifyStaffAssignment($input['staff_id'], $task_id);
            
            // Trigger task assigned event
            if ($this->event_manager) {
                $event_type = $input['task_type'] === 'housekeeping' ? 
                    'housekeeping.task_assigned' : 'staff.task_assigned';
                
                $this->event_manager->triggerEvent($event_type, [
                    'task_id' => $task_id,
                    'staff_id' => $input['staff_id'],
                    'task_type' => $input['task_type'],
                    'room_id' => $input['room_id'],
                    'priority' => $input['priority'],
                    'assigned_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'task_id' => $task_id,
                'estimated_completion' => $this->calculateEstimatedCompletion($assignment_data)
            ], 'Task assigned successfully');
        } else {
            $this->sendError('Failed to assign task');
        }
    }
    
    /**
     * Housekeeping operations management
     */
    public function getHousekeepingOperations() {
        $this->checkPermission('housekeeping.view');
        
        $date = $_GET['date'] ?? date('Y-m-d');
        $floor = $_GET['floor'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $operations = [
            'daily_tasks' => $this->getHousekeepingTasks($date, $floor, $status),
            'room_priorities' => $this->getRoomCleaningPriorities($date),
            'staff_assignments' => $this->getHousekeepingAssignments($date),
            'supply_levels' => $this->getHousekeepingSupplies(),
            'quality_inspections' => $this->getQualityInspections($date),
            'productivity_metrics' => $this->getHousekeepingMetrics($date),
            'equipment_status' => $this->getHousekeepingEquipment()
        ];
        
        $this->sendSuccess($operations);
    }
    
    /**
     * Create housekeeping task
     */
    public function createHousekeepingTask() {
        $this->checkPermission('housekeeping.create_task');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['room_id', 'task_type', 'priority'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Get room information
        $room = $this->getRoomById($input['room_id']);
        if (!$room) {
            $this->sendError('Room not found', 404);
            return;
        }
        
        // Create housekeeping task
        $task_data = [
            'room_id' => intval($input['room_id']),
            'task_type' => $input['task_type'],
            'priority' => $input['priority'],
            'special_instructions' => $input['special_instructions'] ?? '',
            'estimated_duration' => $this->getTaskEstimatedDuration($input['task_type']),
            'scheduled_start' => $input['scheduled_start'] ?? date('Y-m-d H:i:s'),
            'created_by' => $this->user_id,
            'status' => 'pending'
        ];
        
        $task_id = $this->insertHousekeepingTask($task_data);
        
        if ($task_id) {
            // Auto-assign staff if available
            $assigned_staff = $this->autoAssignHousekeepingStaff($task_id, $task_data);
            
            // Update room status if needed
            if ($input['task_type'] === 'checkout_cleaning') {
                $this->updateRoomRecord($input['room_id'], ['status' => 'cleaning']);
            }
            
            // Trigger housekeeping event
            if ($this->event_manager) {
                $this->event_manager->triggerEvent('housekeeping.task_created', [
                    'task_id' => $task_id,
                    'room_id' => $input['room_id'],
                    'room_number' => $room['room_number'],
                    'task_type' => $input['task_type'],
                    'priority' => $input['priority'],
                    'assigned_staff' => $assigned_staff,
                    'created_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'task_id' => $task_id,
                'assigned_staff' => $assigned_staff,
                'estimated_completion' => $this->calculateTaskCompletion($task_data)
            ], 'Housekeeping task created successfully');
        } else {
            $this->sendError('Failed to create housekeeping task');
        }
    }
    
    /**
     * Maintenance operations management
     */
    public function getMaintenanceOperations() {
        $this->checkPermission('maintenance.view');
        
        $status = $_GET['status'] ?? '';
        $priority = $_GET['priority'] ?? '';
        $department = $_GET['department'] ?? '';
        
        $operations = [
            'open_requests' => $this->getMaintenanceRequests($status, $priority, $department),
            'scheduled_maintenance' => $this->getScheduledMaintenance(),
            'preventive_maintenance' => $this->getPreventiveMaintenance(),
            'work_orders' => $this->getMaintenanceWorkOrders(),
            'technician_assignments' => $this->getTechnicianAssignments(),
            'parts_inventory' => $this->getMaintenanceParts(),
            'equipment_status' => $this->getEquipmentStatus(),
            'maintenance_metrics' => $this->getMaintenanceMetrics()
        ];
        
        $this->sendSuccess($operations);
    }
    
    /**
     * Create maintenance request
     */
    public function createMaintenanceRequest() {
        $this->checkPermission('maintenance.create');
        
        $input = json_decode(file_get_contents('php://input'), true);
        
        $required = ['description', 'priority', 'location'];
        foreach ($required as $field) {
            if (empty($input[$field])) {
                $this->sendError("Field '$field' is required");
                return;
            }
        }
        
        // Create maintenance request
        $request_data = [
            'description' => $input['description'],
            'priority' => $input['priority'],
            'location' => $input['location'],
            'room_id' => $input['room_id'] ?? null,
            'category' => $input['category'] ?? 'general',
            'urgency' => $input['urgency'] ?? 'normal',
            'reported_by' => $this->user_id,
            'status' => 'open',
            'requested_completion' => $input['requested_completion'] ?? null
        ];
        
        $request_id = $this->insertMaintenanceRequest($request_data);
        
        if ($request_id) {
            // Auto-assign technician if available
            $assigned_tech = $this->autoAssignTechnician($request_id, $request_data);
            
            // Update room status if room-related
            if ($request_data['room_id'] && $request_data['priority'] === 'urgent') {
                $this->updateRoomRecord($request_data['room_id'], ['status' => 'maintenance']);
            }
            
            // Generate work order number
            $work_order = $this->generateWorkOrderNumber($request_id);
            
            // Trigger maintenance events
            if ($this->event_manager) {
                $event_type = $request_data['priority'] === 'urgent' ? 
                    'maintenance.urgent' : 'maintenance.request_created';
                
                $this->event_manager->triggerEvent($event_type, [
                    'request_id' => $request_id,
                    'work_order' => $work_order,
                    'description' => $request_data['description'],
                    'priority' => $request_data['priority'],
                    'location' => $request_data['location'],
                    'room_id' => $request_data['room_id'],
                    'assigned_tech' => $assigned_tech,
                    'reported_by' => $this->user_id
                ]);
            }
            
            $this->sendSuccess([
                'request_id' => $request_id,
                'work_order' => $work_order,
                'assigned_technician' => $assigned_tech,
                'estimated_completion' => $this->estimateMaintenanceCompletion($request_data)
            ], 'Maintenance request created successfully');
        } else {
            $this->sendError('Failed to create maintenance request');
        }
    }
    
    /**
     * Guest services coordination
     */
    public function getGuestServices() {
        $this->checkPermission('guest_services.view');
        
        $date = $_GET['date'] ?? date('Y-m-d');
        
        $services = [
            'active_requests' => $this->getActiveGuestRequests($date),
            'concierge_services' => $this->getConciergeServices($date),
            'special_arrangements' => $this->getSpecialArrangements($date),
            'vip_services' => $this->getVIPServices($date),
            'guest_feedback' => $this->getGuestFeedback($date),
            'service_metrics' => $this->getServiceMetrics($date)
        ];
        
        $this->sendSuccess($services);
    }
    
    /**
     * Inventory management integration
     */
    public function getInventoryStatus() {
        $this->checkPermission('inventory.view');
        
        $department = $_GET['department'] ?? '';
        $location = $_GET['location'] ?? '';
        
        $inventory = [
            'stock_levels' => $this->getStockLevels($department, $location),
            'low_stock_alerts' => $this->getLowStockAlerts(),
            'reorder_points' => $this->getReorderPoints($department),
            'recent_movements' => $this->getInventoryMovements(),
            'consumption_patterns' => $this->getConsumptionPatterns($department),
            'supplier_status' => $this->getSupplierStatus()
        ];
        
        $this->sendSuccess($inventory);
    }
    
    /**
     * Update inventory levels
     */
    public function updateInventory() {
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
        $quantity = intval($input['quantity']);
        $movement_type = $input['movement_type']; // 'in', 'out', 'adjustment'
        $reason = $input['reason'] ?? '';
        $location = $input['location'] ?? '';
        
        // Get current stock level
        $current_stock = $this->getCurrentStock($item_id, $location);
        
        // Calculate new stock level
        $new_stock = $this->calculateNewStock($current_stock, $quantity, $movement_type);
        
        if ($new_stock < 0) {
            $this->sendError('Insufficient stock for this operation');
            return;
        }
        
        // Update inventory
        $success = $this->updateStockLevel($item_id, $new_stock, $location);
        
        if ($success) {
            // Log inventory movement
            $this->logInventoryMovement($item_id, $quantity, $movement_type, $reason, $location);
            
            // Check for low stock alerts
            $this->checkLowStockAlerts($item_id, $new_stock);
            
            // Trigger inventory events
            if ($this->event_manager) {
                if ($new_stock <= $this->getReorderPoint($item_id)) {
                    $this->event_manager->triggerEvent('inventory.low_stock', [
                        'item_id' => $item_id,
                        'current_stock' => $new_stock,
                        'reorder_point' => $this->getReorderPoint($item_id),
                        'location' => $location
                    ]);
                }
                
                if ($new_stock <= 0) {
                    $this->event_manager->triggerEvent('inventory.out_of_stock', [
                        'item_id' => $item_id,
                        'location' => $location,
                        'last_movement' => $movement_type
                    ]);
                }
            }
            
            $this->sendSuccess([
                'item_id' => $item_id,
                'previous_stock' => $current_stock,
                'new_stock' => $new_stock,
                'movement_quantity' => $quantity,
                'movement_type' => $movement_type
            ], 'Inventory updated successfully');
        } else {
            $this->sendError('Failed to update inventory');
        }
    }
    
    // Helper Methods
    
    private function getRoomStatusOverview() {
        $query = "SELECT 
            status,
            COUNT(*) as count,
            COUNT(*) * 100.0 / SUM(COUNT(*)) OVER() as percentage
            FROM named_rooms 
            GROUP BY status";
        
        $result = $this->db->query($query);
        $overview = [];
        
        while ($row = $result->fetch_assoc()) {
            $overview[] = [
                'status' => $row['status'],
                'count' => intval($row['count']),
                'percentage' => round(floatval($row['percentage']), 2)
            ];
        }
        
        return $overview;
    }
    
    private function fetchRoomStatus($floor, $status, $room_type) {
        $where_conditions = ["1=1"];
        $params = [];
        $param_types = "";
        
        if ($floor) {
            $where_conditions[] = "floor = ?";
            $params[] = $floor;
            $param_types .= "s";
        }
        
        if ($status) {
            $where_conditions[] = "status = ?";
            $params[] = $status;
            $param_types .= "s";
        }
        
        if ($room_type) {
            $where_conditions[] = "room_type = ?";
            $params[] = $room_type;
            $param_types .= "s";
        }
        
        $where_clause = implode(" AND ", $where_conditions);
        
        $query = "SELECT 
            id,
            room_number,
            room_type,
            floor,
            status,
            last_cleaned,
            last_status_change,
            notes,
            updated_at
            FROM named_rooms 
            WHERE $where_clause
            ORDER BY floor, room_number";
        
        $stmt = $this->db->prepare($query);
        if ($param_types) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        
        $result = $stmt->get_result();
        $rooms = [];
        
        while ($row = $result->fetch_assoc()) {
            $rooms[] = $row;
        }
        
        return $rooms;
    }
    
    private function isValidStatusTransition($old_status, $new_status) {
        $valid_transitions = [
            'available' => ['occupied', 'maintenance', 'cleaning', 'reserved'],
            'occupied' => ['available', 'maintenance', 'cleaning'],
            'cleaning' => ['available', 'maintenance', 'inspection'],
            'maintenance' => ['available', 'cleaning'],
            'reserved' => ['occupied', 'available', 'maintenance'],
            'inspection' => ['available', 'cleaning', 'maintenance']
        ];
        
        return isset($valid_transitions[$old_status]) && 
               in_array($new_status, $valid_transitions[$old_status]);
    }
    
    private function triggerStatusWorkflows($room_id, $old_status, $new_status) {
        // Define automated workflows based on status changes
        $workflows = [];
        
        switch ($new_status) {
            case 'cleaning':
                // Auto-create housekeeping task
                $workflows[] = $this->createAutomaticHousekeepingTask($room_id);
                break;
                
            case 'maintenance':
                // Notify maintenance team
                $workflows[] = $this->notifyMaintenanceTeam($room_id);
                break;
                
            case 'available':
                // Update in reservation system
                $workflows[] = $this->updateReservationAvailability($room_id);
                break;
        }
        
        return $workflows;
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
    
    $api = new OperationsAPI($con);
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'dashboard':
            $api->getOperationsDashboard();
            break;
            
        case 'room_status':
            $api->getRoomStatus();
            break;
            
        case 'update_room_status':
            $api->updateRoomStatus();
            break;
            
        case 'staff_coordination':
            $api->getStaffCoordination();
            break;
            
        case 'assign_staff':
            $api->assignStaffTask();
            break;
            
        case 'housekeeping':
            $api->getHousekeepingOperations();
            break;
            
        case 'create_housekeeping_task':
            $api->createHousekeepingTask();
            break;
            
        case 'maintenance':
            $api->getMaintenanceOperations();
            break;
            
        case 'create_maintenance':
            $api->createMaintenanceRequest();
            break;
            
        case 'guest_services':
            $api->getGuestServices();
            break;
            
        case 'inventory':
            $api->getInventoryStatus();
            break;
            
        case 'update_inventory':
            $api->updateInventory();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    error_log("Operations API Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}
?>
