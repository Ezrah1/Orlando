<?php
// Create Order Integration for Dynamic Inventory
require_once '../db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

$page_title = 'Order Integration Setup';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle integration setup
if (isset($_POST['create_integration'])) {
    try {
        // Create the inventory reduction function file
        $integration_code = '<?php
// Dynamic Inventory Reduction Function
function reduce_bar_inventory($item_name, $quantity, $order_id = null, $user_id = null) {
    global $con;
    
    try {
        // Find the bar inventory item by name (flexible matching)
        $find_item_sql = "SELECT id, name, current_stock, minimum_stock FROM bar_inventory 
                         WHERE (name LIKE \'%\' . mysqli_real_escape_string($con, $item_name) . \'%\' 
                               OR name = \'" . mysqli_real_escape_string($con, $item_name) . "\') 
                         AND is_active = 1 
                         ORDER BY 
                           CASE 
                             WHEN name = \'" . mysqli_real_escape_string($con, $item_name) . "\' THEN 1
                             ELSE 2
                           END
                         LIMIT 1";
        
        $find_result = mysqli_query($con, $find_item_sql);
        
        if (mysqli_num_rows($find_result) > 0) {
            $item = mysqli_fetch_assoc($find_result);
            $new_stock = max(0, $item[\'current_stock\'] - $quantity);
            
            // Update the inventory
            $update_sql = "UPDATE bar_inventory SET 
                          current_stock = $new_stock,
                          updated_at = NOW()
                          WHERE id = " . $item[\'id\'];
            
            if (mysqli_query($con, $update_sql)) {
                // Record the stock movement
                $movement_sql = "INSERT INTO bar_inventory_movements 
                                (inventory_id, movement_type, quantity, notes, moved_by, order_reference, created_at) 
                                VALUES 
                                (" . $item[\'id\'] . ", \'stock_out\', $quantity, \'Order sale - " . ($order_id ? "Order #$order_id" : "Direct sale") . "\', " . ($user_id ?: "NULL") . ", " . ($order_id ? "\'$order_id\'" : "NULL") . ", NOW())";
                mysqli_query($con, $movement_sql);
                
                // Check if stock is now at or below minimum
                if ($new_stock <= $item[\'minimum_stock\']) {
                    // Create low stock alert
                    $alert_sql = "INSERT INTO inventory_alerts 
                                 (inventory_id, alert_type, message, is_resolved, created_at) 
                                 VALUES 
                                 (" . $item[\'id\'] . ", \'low_stock\', \'Item " . $item[\'name\'] . " is now at $new_stock units (minimum: " . $item[\'minimum_stock\'] . ")\', 0, NOW())";
                    mysqli_query($con, $alert_sql);
                }
                
                return [
                    \'success\' => true,
                    \'message\' => "Inventory reduced successfully",
                    \'item_name\' => $item[\'name\'],
                    \'old_stock\' => $item[\'current_stock\'],
                    \'new_stock\' => $new_stock,
                    \'low_stock_alert\' => ($new_stock <= $item[\'minimum_stock\'])
                ];
            }
        } else {
            return [
                \'success\' => false,
                \'message\' => "Bar inventory item not found: $item_name"
            ];
        }
    } catch (Exception $e) {
        return [
            \'success\' => false,
            \'message\' => "Error reducing inventory: " . $e->getMessage()
        ];
    }
    
    return [
        \'success\' => false,
        \'message\' => "Unknown error occurred"
    ];
}

// Function to check if bar item is available
function check_bar_item_availability($item_name, $requested_quantity = 1) {
    global $con;
    
    $find_item_sql = "SELECT id, name, current_stock FROM bar_inventory 
                     WHERE (name LIKE \'%\' . mysqli_real_escape_string($con, $item_name) . \'%\' 
                           OR name = \'" . mysqli_real_escape_string($con, $item_name) . "\') 
                     AND is_active = 1 
                     LIMIT 1";
    
    $find_result = mysqli_query($con, $find_item_sql);
    
    if (mysqli_num_rows($find_result) > 0) {
        $item = mysqli_fetch_assoc($find_result);
        return [
            \'available\' => ($item[\'current_stock\'] >= $requested_quantity),
            \'current_stock\' => $item[\'current_stock\'],
            \'item_name\' => $item[\'name\']
        ];
    }
    
    return [
        \'available\' => false,
        \'current_stock\' => 0,
        \'item_name\' => $item_name
    ];
}
?>';

        // Save the integration file
        $integration_file = '../includes/bar_inventory_integration.php';
        if (file_put_contents($integration_file, $integration_code)) {
            $file_created = true;
        }

        // Create inventory alerts table if it doesn't exist
        $create_alerts_table = "CREATE TABLE IF NOT EXISTS `inventory_alerts` (
            `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
            `inventory_id` int(10) UNSIGNED NOT NULL,
            `alert_type` enum('low_stock','out_of_stock','expired','other') NOT NULL,
            `message` text NOT NULL,
            `is_resolved` tinyint(1) DEFAULT 0,
            `resolved_by` int(10) UNSIGNED DEFAULT NULL,
            `resolved_at` datetime DEFAULT NULL,
            `created_at` datetime DEFAULT current_timestamp(),
            PRIMARY KEY (`id`),
            KEY `inventory_id` (`inventory_id`),
            KEY `alert_type` (`alert_type`),
            KEY `is_resolved` (`is_resolved`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        mysqli_query($con, $create_alerts_table);

        // Update order movements table to include order reference
        $alter_movements = "ALTER TABLE bar_inventory_movements 
                           ADD COLUMN IF NOT EXISTS order_reference VARCHAR(50) DEFAULT NULL,
                           ADD INDEX IF NOT EXISTS idx_order_reference (order_reference)";
        mysqli_query($con, $alter_movements);

        $success_message = "Order integration system created successfully! Dynamic inventory reduction is now active.";
        
    } catch (Exception $e) {
        $error_message = "Error creating integration: " . $e->getMessage();
    }
}

// Check if integration already exists
$integration_exists = file_exists('../includes/bar_inventory_integration.php');

// Check if alerts table exists
$check_alerts_table = "SHOW TABLES LIKE 'inventory_alerts'";
$alerts_table_exists = mysqli_num_rows(mysqli_query($con, $check_alerts_table)) > 0;
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-link"></i> Order Integration Setup
        </h1>
        <p class="page-subtitle">Create automatic inventory reduction when orders are placed</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="test_integration.php" class="btn btn-primary">
                    <i class="fa fa-vial"></i> Test Integration
                </a>
                <a href="inventory_alerts.php" class="btn btn-secondary">
                    <i class="fa fa-bell"></i> View Alerts
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Integration Status -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="integration-card">
                <div class="integration-icon">
                    <i class="fa fa-file-code <?php echo $integration_exists ? 'text-success' : 'text-warning'; ?>"></i>
                </div>
                <div class="integration-info">
                    <h3><?php echo $integration_exists ? 'READY' : 'MISSING'; ?></h3>
                    <p>Integration File</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="integration-card">
                <div class="integration-icon">
                    <i class="fa fa-database <?php echo $alerts_table_exists ? 'text-success' : 'text-warning'; ?>"></i>
                </div>
                <div class="integration-info">
                    <h3><?php echo $alerts_table_exists ? 'EXISTS' : 'MISSING'; ?></h3>
                    <p>Alerts Table</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="integration-card">
                <div class="integration-icon">
                    <i class="fa fa-sync-alt text-info"></i>
                </div>
                <div class="integration-info">
                    <h3>AUTO</h3>
                    <p>Stock Reduction</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="integration-card">
                <div class="integration-icon">
                    <i class="fa fa-bell text-warning"></i>
                </div>
                <div class="integration-info">
                    <h3>SMART</h3>
                    <p>Low Stock Alerts</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Integration Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-cogs"></i> How Dynamic Inventory Works</h4>
                </div>
                <div class="card-body">
                    <div class="integration-flow">
                        <div class="flow-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h5>Customer Places Order</h5>
                                <p>Guest selects bar items from menu and places order through the system.</p>
                            </div>
                        </div>
                        
                        <div class="flow-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h5>Availability Check</h5>
                                <p>System checks if requested quantity is available in bar inventory.</p>
                            </div>
                        </div>
                        
                        <div class="flow-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h5>Automatic Stock Reduction</h5>
                                <p>Upon order confirmation, inventory stock is automatically reduced by ordered quantity.</p>
                            </div>
                        </div>
                        
                        <div class="flow-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h5>Movement Tracking</h5>
                                <p>Stock movement is recorded with order reference for audit trail.</p>
                            </div>
                        </div>
                        
                        <div class="flow-step">
                            <div class="step-number">5</div>
                            <div class="step-content">
                                <h5>Low Stock Alert</h5>
                                <p>If stock falls to minimum level, automatic alert is generated.</p>
                            </div>
                        </div>
                        
                        <div class="flow-step">
                            <div class="step-number">6</div>
                            <div class="step-content">
                                <h5>Menu Availability Update</h5>
                                <p>Items with 0 stock are automatically marked as unavailable in menu.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-lightbulb"></i> Key Features:</strong><br>
                        • <strong>Real-time inventory</strong> - Stock levels update immediately<br>
                        • <strong>Smart matching</strong> - Flexible item name matching for orders<br>
                        • <strong>Audit trail</strong> - Complete history of stock movements<br>
                        • <strong>Alert system</strong> - Automatic notifications for low stock
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-rocket"></i> Setup Integration</h4>
                </div>
                <div class="card-body text-center">
                    <?php if (!$integration_exists || !$alerts_table_exists): ?>
                        <form method="POST">
                            <button type="submit" name="create_integration" class="btn btn-success btn-lg" 
                                    onclick="return confirm('This will create the dynamic inventory integration system. Continue?')">
                                <i class="fa fa-link"></i> Create Integration
                            </button>
                        </form>
                        
                        <div class="integration-warning mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                This will create the integration file and database tables needed.
                            </small>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success">
                            <i class="fa fa-check-circle"></i> Integration is ready!
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="setup_dynamic_inventory.php" class="btn btn-outline-primary">
                            <i class="fa fa-cogs"></i> Setup Inventory
                        </a>
                        <a href="test_integration.php" class="btn btn-outline-success mt-2">
                            <i class="fa fa-vial"></i> Test System
                        </a>
                        <a href="inventory_alerts.php" class="btn btn-outline-warning mt-2">
                            <i class="fa fa-bell"></i> View Alerts
                        </a>
                    </div>
                </div>
            </div>

            <!-- Integration Functions -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fa fa-code"></i> Available Functions</h4>
                </div>
                <div class="card-body">
                    <div class="function-item">
                        <strong>reduce_bar_inventory()</strong>
                        <small class="d-block text-muted">Reduces stock when order is placed</small>
                    </div>
                    <div class="function-item">
                        <strong>check_bar_item_availability()</strong>
                        <small class="d-block text-muted">Checks if item is in stock</small>
                    </div>
                    <div class="function-item">
                        <strong>Automatic Alerts</strong>
                        <small class="d-block text-muted">Creates alerts for low stock</small>
                    </div>
                    <div class="function-item">
                        <strong>Movement Tracking</strong>
                        <small class="d-block text-muted">Records all stock changes</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.integration-card {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 20px;
    transition: transform 0.3s ease;
}

.integration-card:hover {
    transform: translateY(-3px);
}

.integration-icon {
    font-size: 2.5rem;
    width: 60px;
    text-align: center;
}

.integration-info h3 {
    font-size: 1.5rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.integration-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.integration-flow {
    margin: 20px 0;
}

.flow-step {
    display: flex;
    align-items: flex-start;
    gap: 20px;
    margin-bottom: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #e9ecef;
}

.flow-step:last-child {
    border-bottom: none;
}

.step-number {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    width: 35px;
    height: 35px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    flex-shrink: 0;
}

.step-content h5 {
    color: #2c3e50;
    margin-bottom: 8px;
    font-weight: 600;
}

.step-content p {
    color: #6c757d;
    margin: 0;
    line-height: 1.5;
}

.integration-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 10px;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.function-item {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.function-item:last-child {
    border-bottom: none;
}

.page-subtitle {
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
