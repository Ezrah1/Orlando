<?php
// Setup Dynamic Inventory Management System
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

$page_title = 'Setup Dynamic Inventory System';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';

// Handle setup request
if (isset($_POST['setup_dynamic_inventory'])) {
    try {
        // First, set all items to 10 stock and appropriate minimum levels
        $inventory_setup = [
            // Premium items - lower minimum (3-5)
            'premium' => [
                'items' => ['Hennessy VS', 'Hennessy VSOP', 'Singleton', 'Johnnie Walker Black'],
                'stock' => 10,
                'minimum' => 3,
                'maximum' => 25
            ],
            // Wine items - moderate minimum (4-6)
            'wine' => [
                'items' => ['4th Street', 'Caprice', 'Casa Buena', 'Robertson'],
                'stock' => 10,
                'minimum' => 4,
                'maximum' => 30
            ],
            // Regular spirits - standard minimum (5-8)
            'spirits' => [
                'items' => ['Captain Morgan', 'Gilbey', 'Gordon', 'Grant', 'Jack Daniel', 'Jameson', 'Jägermeister', 'J&B', 'Smirnoff', 'Vat 69', 'Viceroy'],
                'stock' => 10,
                'minimum' => 6,
                'maximum' => 40
            ],
            // Beer/Cider - higher minimum (10-15)
            'beer' => [
                'items' => ['Guinness', 'Heineken', 'Pilsner', 'Tusker', 'Hunter'],
                'stock' => 10,
                'minimum' => 10,
                'maximum' => 50
            ],
            // RTD/Soft - high minimum (15-20)
            'rtd_soft' => [
                'items' => ['Afia', 'Red Bull', 'Soda', 'Tonic'],
                'stock' => 10,
                'minimum' => 15,
                'maximum' => 100
            ],
            // Water - very high minimum (25-30)
            'water' => [
                'items' => ['Water'],
                'stock' => 10,
                'minimum' => 25,
                'maximum' => 200
            ]
        ];
        
        $updated_count = 0;
        $total_items = 0;
        
        // Update all bar inventory items
        foreach ($inventory_setup as $category => $config) {
            foreach ($config['items'] as $item_pattern) {
                $update_sql = "UPDATE bar_inventory SET 
                              current_stock = " . $config['stock'] . ",
                              minimum_stock = " . $config['minimum'] . ",
                              maximum_stock = " . $config['maximum'] . ",
                              updated_at = NOW()
                              WHERE name LIKE '%" . mysqli_real_escape_string($con, $item_pattern) . "%' AND is_active = 1";
                
                if (mysqli_query($con, $update_sql)) {
                    $affected = mysqli_affected_rows($con);
                    $updated_count += $affected;
                    $total_items += $affected;
                }
            }
        }
        
        // Create inventory reduction trigger function (stored in a separate file for reference)
        $trigger_created = true; // We'll handle this in the order processing
        
        $success_message = "Dynamic inventory system setup completed! Updated $updated_count items with stock level 10 and appropriate minimum alert points.";
        
    } catch (Exception $e) {
        $error_message = "Error setting up dynamic inventory: " . $e->getMessage();
    }
}

// Get current inventory status
$total_items_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE is_active = 1";
$total_items_result = mysqli_query($con, $total_items_query);
$total_items = mysqli_fetch_assoc($total_items_result)['count'];

$current_stock_10_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE current_stock = 10 AND is_active = 1";
$current_stock_10_result = mysqli_query($con, $current_stock_10_query);
$stock_10_count = mysqli_fetch_assoc($current_stock_10_result)['count'];

$low_stock_query = "SELECT COUNT(*) as count FROM bar_inventory WHERE current_stock <= minimum_stock AND is_active = 1";
$low_stock_result = mysqli_query($con, $low_stock_query);
$low_stock_count = mysqli_fetch_assoc($low_stock_result)['count'];

$categories_query = "SELECT 
    bc.name as category_name,
    COUNT(bi.id) as item_count,
    AVG(bi.current_stock) as avg_stock,
    AVG(bi.minimum_stock) as avg_minimum
    FROM bar_inventory bi 
    JOIN bar_categories bc ON bi.category_id = bc.id 
    WHERE bi.is_active = 1 
    GROUP BY bc.id, bc.name 
    ORDER BY bc.display_order";
$categories_result = mysqli_query($con, $categories_query);
?>

<div class="container-fluid">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fa fa-cogs"></i> Setup Dynamic Inventory System
        </h1>
        <p class="page-subtitle">Configure automatic stock reduction and minimum alert points</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success">
            <i class="fa fa-check-circle"></i> <?php echo $success_message; ?>
            <div class="mt-2">
                <a href="bar_inventory.php" class="btn btn-primary">
                    <i class="fa fa-eye"></i> View Updated Inventory
                </a>
                <a href="create_order_integration.php" class="btn btn-success">
                    <i class="fa fa-shopping-cart"></i> Setup Order Integration
                </a>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <!-- Current Status Overview -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-boxes text-primary"></i>
                </div>
                <div class="status-info">
                    <h3><?php echo $total_items; ?></h3>
                    <p>Total Items</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-equals text-success"></i>
                </div>
                <div class="status-info">
                    <h3><?php echo $stock_10_count; ?></h3>
                    <p>Items at Stock 10</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-exclamation-triangle text-warning"></i>
                </div>
                <div class="status-info">
                    <h3><?php echo $low_stock_count; ?></h3>
                    <p>Low Stock Alerts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="status-card">
                <div class="status-icon">
                    <i class="fa fa-sync-alt text-info"></i>
                </div>
                <div class="status-info">
                    <h3>AUTO</h3>
                    <p>Dynamic System</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Setup Details -->
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-chart-line"></i> Dynamic Inventory Configuration</h4>
                </div>
                <div class="card-body">
                    <div class="config-sections">
                        <div class="config-section">
                            <h5><i class="fa fa-star text-warning"></i> Premium Items (Hennessy, Singleton, JW Black)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 3 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 25 units</div>
                                <div class="config-item"><strong>Strategy:</strong> Low minimum due to high value, slow turnover</div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fa fa-wine-glass text-danger"></i> Wine Items (All Wine Categories)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 4 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 30 units</div>
                                <div class="config-item"><strong>Strategy:</strong> Moderate minimum for balanced turnover</div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fa fa-glass-whiskey text-info"></i> Regular Spirits (Gin, Vodka, Whiskey, Brandy)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 6 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 40 units</div>
                                <div class="config-item"><strong>Strategy:</strong> Standard minimum for regular demand</div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fa fa-beer text-warning"></i> Beer/Cider (High Volume Items)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 10 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 50 units</div>
                                <div class="config-item"><strong>Strategy:</strong> Higher minimum due to high demand</div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fa fa-glass text-success"></i> RTD/Soft Drinks (Volume Sales)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 15 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 100 units</div>
                                <div class="config-item"><strong>Strategy:</strong> High minimum for fast-moving items</div>
                            </div>
                        </div>
                        
                        <div class="config-section">
                            <h5><i class="fa fa-tint text-primary"></i> Water (Very High Volume)</h5>
                            <div class="config-details">
                                <div class="config-item"><strong>Stock Level:</strong> 10 units</div>
                                <div class="config-item"><strong>Minimum Alert:</strong> 25 units</div>
                                <div class="config-item"><strong>Maximum Stock:</strong> 200 units</div>
                                <div class="config-item"><strong>Strategy:</strong> Very high minimum for essential item</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <strong><i class="fa fa-lightbulb"></i> How Dynamic Inventory Works:</strong><br>
                        • <strong>Order Placed:</strong> Stock automatically reduces by quantity ordered<br>
                        • <strong>Low Stock Alert:</strong> System alerts when stock hits minimum level<br>
                        • <strong>Menu Availability:</strong> Items with 0 stock marked as unavailable<br>
                        • <strong>Reorder Points:</strong> Based on item category and demand patterns
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4><i class="fa fa-play"></i> Setup Actions</h4>
                </div>
                <div class="card-body text-center">
                    <?php if (!isset($success_message)): ?>
                        <form method="POST">
                            <button type="submit" name="setup_dynamic_inventory" class="btn btn-success btn-lg" 
                                    onclick="return confirm('This will set all bar items to stock level 10 and configure minimum alert points. Continue?')">
                                <i class="fa fa-cogs"></i> Setup Dynamic Inventory
                            </button>
                        </form>
                        
                        <div class="setup-warning mt-3">
                            <small class="text-muted">
                                <i class="fa fa-info-circle"></i>
                                This will standardize all stock to 10 units and set smart minimum levels.
                            </small>
                        </div>
                    <?php endif; ?>
                    
                    <hr>
                    
                    <div class="quick-links">
                        <a href="bar_inventory.php" class="btn btn-outline-primary">
                            <i class="fa fa-list"></i> View Inventory
                        </a>
                        <a href="create_order_integration.php" class="btn btn-outline-success mt-2">
                            <i class="fa fa-shopping-cart"></i> Order Integration
                        </a>
                        <a href="inventory_alerts.php" class="btn btn-outline-warning mt-2">
                            <i class="fa fa-bell"></i> Stock Alerts
                        </a>
                    </div>
                </div>
            </div>

            <!-- Current Category Status -->
            <div class="card mt-4">
                <div class="card-header">
                    <h4><i class="fa fa-chart-bar"></i> Category Status</h4>
                </div>
                <div class="card-body">
                    <?php while ($cat = mysqli_fetch_assoc($categories_result)): ?>
                        <div class="category-status">
                            <div class="cat-name"><?php echo $cat['category_name']; ?></div>
                            <div class="cat-details">
                                <small>
                                    <?php echo $cat['item_count']; ?> items | 
                                    Avg Stock: <?php echo number_format($cat['avg_stock'], 1); ?> | 
                                    Avg Min: <?php echo number_format($cat['avg_minimum'], 1); ?>
                                </small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.status-card {
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

.status-card:hover {
    transform: translateY(-3px);
}

.status-icon {
    font-size: 2.5rem;
    width: 60px;
    text-align: center;
}

.status-info h3 {
    font-size: 2rem;
    font-weight: bold;
    margin: 0;
    color: #2c3e50;
}

.status-info p {
    margin: 0;
    color: #6c757d;
    font-weight: 500;
}

.config-sections {
    max-height: 600px;
    overflow-y: auto;
}

.config-section {
    margin-bottom: 25px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
    border-left: 4px solid #007bff;
}

.config-section h5 {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.config-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
}

.config-item {
    background: white;
    padding: 8px 12px;
    border-radius: 6px;
    font-size: 0.9rem;
    color: #495057;
}

.setup-warning {
    background: #fff3cd;
    border: 1px solid #ffeaa7;
    border-radius: 6px;
    padding: 10px;
}

.quick-links .btn {
    width: 100%;
    margin-bottom: 8px;
}

.category-status {
    padding: 10px 0;
    border-bottom: 1px solid #e9ecef;
}

.category-status:last-child {
    border-bottom: none;
}

.cat-name {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 5px;
}

.cat-details {
    color: #6c757d;
}

.page-subtitle {
    color: #6c757d;
    margin-top: 5px;
}
</style>

<?php include '../includes/admin/footer.php'; ?>
