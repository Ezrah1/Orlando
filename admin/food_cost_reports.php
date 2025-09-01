<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// Include database connection
include 'db.php';

$page_title = 'Food Cost Reports';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Food Cost Reports</h1>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php



// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get food cost data
$cost_query = "SELECT 
                DATE(fo.ordered_time) as date,
                COUNT(fo.id) as total_orders,
                SUM(fo.final_amount) as total_sales,
                SUM(oi.total_cost) as total_cost,
                SUM(fo.final_amount - oi.total_cost) as gross_profit,
                AVG(fo.final_amount) as average_order_value,
                ROUND(((SUM(fo.final_amount - oi.total_cost) / SUM(fo.final_amount)) * 100), 2) as gross_profit_margin
              FROM food_orders fo
              LEFT JOIN order_items oi ON fo.id = oi.order_id
              WHERE fo.ordered_time BETWEEN '$start_date' AND '$end_date'
                AND fo.status IN ('served', 'completed')
                AND fo.payment_status = 'paid'
              GROUP BY DATE(fo.ordered_time)
              ORDER BY date DESC";

$cost_result = mysqli_query($con, $cost_query);

// Get category-wise cost analysis
$category_cost_query = "SELECT 
                         mc.name as category_name,
                         COUNT(fo.id) as total_orders,
                         SUM(fo.final_amount) as total_sales,
                         SUM(oi.total_cost) as total_cost,
                         SUM(fo.final_amount - oi.total_cost) as gross_profit,
                         ROUND(((SUM(fo.final_amount - oi.total_cost) / SUM(fo.final_amount)) * 100), 2) as gross_profit_margin,
                         AVG(fo.final_amount) as average_order_value
                       FROM food_orders fo
                       LEFT JOIN order_items oi ON fo.id = oi.order_id
                       LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                       LEFT JOIN menu_categories mc ON mi.category_id = mc.id
                       WHERE fo.ordered_time BETWEEN '$start_date' AND '$end_date'
                         AND fo.status IN ('served', 'completed')
                         AND fo.payment_status = 'paid'
                       GROUP BY mc.id, mc.name
                       ORDER BY total_sales DESC";

$category_cost_result = mysqli_query($con, $category_cost_query);

// Get top selling items
$top_items_query = "SELECT 
                     mi.name as item_name,
                     mc.name as category_name,
                     COUNT(oi.id) as times_ordered,
                     SUM(oi.quantity) as total_quantity,
                     SUM(oi.total_price) as total_revenue,
                     SUM(oi.total_cost) as total_cost,
                     SUM(oi.total_price - oi.total_cost) as gross_profit,
                     ROUND(((SUM(oi.total_price - oi.total_cost) / SUM(oi.total_price)) * 100), 2) as profit_margin
                   FROM order_items oi
                   LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                   LEFT JOIN menu_categories mc ON mi.category_id = mc.id
                   LEFT JOIN food_orders fo ON oi.order_id = fo.id
                   WHERE fo.ordered_time BETWEEN '$start_date' AND '$end_date'
                     AND fo.status IN ('served', 'completed')
                     AND fo.payment_status = 'paid'
                   GROUP BY mi.id, mi.name, mc.name
                   ORDER BY total_revenue DESC
                   LIMIT 10";

$top_items_result = mysqli_query($con, $top_items_query);

// Calculate totals
$total_sales = 0;
$total_cost = 0;
$total_profit = 0;
$total_orders = 0;
$avg_profit_margin = 0;

$cost_data = [];
while($row = mysqli_fetch_assoc($cost_result)) {
    $cost_data[] = $row;
    $total_sales += $row['total_sales'] ?: 0;
    $total_cost += $row['total_cost'] ?: 0;
    $total_profit += $row['gross_profit'] ?: 0;
    $total_orders += $row['total_orders'] ?: 0;
}

$avg_profit_margin = $total_sales > 0 ? ($total_profit / $total_sales) * 100 : 0;
$avg_order_value = $total_orders > 0 ? $total_sales / $total_orders : 0;
?>

<!-- Date Filter Form -->
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <h4>📅 Filter by Date Range</h4>
                </div>
                <div class="panel-body">
                    <form method="GET" class="form-inline">
                        <div class="form-group">
                            <label for="start_date">From:</label>
                            <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo $start_date; ?>">
                        </div>
                        <div class="form-group">
                            <label for="end_date">To:</label>
                            <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo $end_date; ?>">
                        </div>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="food_cost_reports.php" class="btn btn-default">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="cost-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3>KES <?php echo number_format($total_sales); ?></h3>
                <p>Total Sales</p>
            </div>
        </div>
        <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h3>KES <?php echo number_format($total_cost); ?></h3>
                                    <p>Total Cost</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h3>KES <?php echo number_format($total_profit); ?></h3>
                                    <p>Gross Profit</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                    <h3><?php echo number_format($avg_profit_margin, 1); ?>%</h3>
                                    <p>Profit Margin</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Metrics -->
                        <div class="row">
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                    <h3><?php echo $total_orders; ?></h3>
                                    <p>Total Orders</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);">
                                    <h3>KES <?php echo number_format($avg_order_value, 2); ?></h3>
                                    <p>Avg Order Value</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);">
                                    <h3><?php echo $total_orders > 0 ? number_format($total_profit / $total_orders, 2) : '0.00'; ?></h3>
                                    <p>Profit per Order</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="cost-card" style="background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);">
                                    <h3><?php echo $total_cost > 0 ? number_format(($total_sales / $total_cost) * 100, 1) : '0.0'; ?>%</h3>
                                    <p>Cost Ratio</p>
                                </div>
                            </div>
                        </div>

                        <!-- Category-wise Cost Analysis -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>📊 Category-wise Cost Analysis</h4>
                                        <p>Revenue, cost, and profit breakdown by menu category for <?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
                                    </div>
                                    <div class="panel-body">
                                        <?php if(mysqli_num_rows($category_cost_result) > 0): ?>
                                            <?php while($category = mysqli_fetch_assoc($category_cost_result)): ?>
                                                <div class="category-cost-row">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <h5><?php echo $category['category_name']; ?></h5>
                                                            <small class="text-muted"><?php echo $category['total_orders']; ?> orders</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>KES <?php echo number_format($category['total_sales'] ?: 0); ?></strong>
                                                            <br><small class="text-muted">Revenue</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>KES <?php echo number_format($category['total_cost'] ?: 0); ?></strong>
                                                            <br><small class="text-muted">Cost</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>KES <?php echo number_format($category['gross_profit'] ?: 0); ?></strong>
                                                            <br><small class="text-muted">Profit</small>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="profit-margin-bar">
                                                                <div class="profit-margin-fill" style="width: <?php echo min($category['gross_profit_margin'] ?: 0, 100); ?>%"></div>
                                                            </div>
                                                            <small class="text-muted"><?php echo number_format($category['gross_profit_margin'] ?: 0, 1); ?>% margin</small>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <p class="text-muted">No cost data for the selected period.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Cost vs Revenue Chart -->
                            <div class="col-md-4">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>📈 Cost vs Revenue</h4>
                                    </div>
                                    <div class="panel-body">
                                        <canvas id="costRevenueChart" width="400" height="300"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Cost Trend -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>📅 Daily Cost Trend</h4>
                                        <p>Daily cost and profit analysis for <?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
                                    </div>
                                    <div class="panel-body">
                                        <?php if(count($cost_data) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Date</th>
                                                            <th>Orders</th>
                                                            <th>Sales</th>
                                                            <th>Cost</th>
                                                            <th>Profit</th>
                                                            <th>Margin</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach($cost_data as $day): ?>
                                                            <tr>
                                                                <td><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                                                <td><?php echo $day['total_orders']; ?></td>
                                                                <td>KES <?php echo number_format($day['total_sales'] ?: 0); ?></td>
                                                                <td>KES <?php echo number_format($day['total_cost'] ?: 0); ?></td>
                                                                <td>KES <?php echo number_format($day['gross_profit'] ?: 0); ?></td>
                                                                <td><?php echo number_format($day['gross_profit_margin'] ?: 0, 1); ?>%</td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No daily cost data for the selected period.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top Selling Items -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>🏆 Top Selling Items</h4>
                                        <p>Most profitable menu items for <?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
                                    </div>
                                    <div class="panel-body">
                                        <?php if(mysqli_num_rows($top_items_result) > 0): ?>
                                            <div class="table-responsive">
                                                <table class="table table-striped">
                                                    <thead>
                                                        <tr>
                                                            <th>Item</th>
                                                            <th>Category</th>
                                                            <th>Qty Sold</th>
                                                            <th>Revenue</th>
                                                            <th>Cost</th>
                                                            <th>Profit</th>
                                                            <th>Margin</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php while($item = mysqli_fetch_assoc($top_items_result)): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                                                <td><?php echo $item['total_quantity']; ?></td>
                                                                <td>KES <?php echo number_format($item['total_revenue'] ?: 0); ?></td>
                                                                <td>KES <?php echo number_format($item['total_cost'] ?: 0); ?></td>
                                                                <td>KES <?php echo number_format($item['gross_profit'] ?: 0); ?></td>
                                                                <td>
                                                                    <span class="badge <?php echo ($item['profit_margin'] >= 50) ? 'badge-success' : (($item['profit_margin'] >= 30) ? 'badge-warning' : 'badge-danger'); ?>">
                                                                        <?php echo number_format($item['profit_margin'] ?: 0, 1); ?>%
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        <?php endwhile; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php else: ?>
                                            <p class="text-muted">No item data for the selected period.</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Cost Insights -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>💡 Cost Insights</h4>
                                    </div>
                                    <div class="panel-body">
                                        <?php
                                        $best_margin_category = null;
                                        $worst_margin_category = null;
                                        $highest_cost_category = null;
                                        
                                        if($category_cost_result) mysqli_data_seek($category_cost_result, 0);
                                        while($cat = mysqli_fetch_assoc($category_cost_result)) {
                                            if(!$best_margin_category || $cat['gross_profit_margin'] > $best_margin_category['gross_profit_margin']) {
                                                $best_margin_category = $cat;
                                            }
                                            if(!$worst_margin_category || $cat['gross_profit_margin'] < $worst_margin_category['gross_profit_margin']) {
                                                $worst_margin_category = $cat;
                                            }
                                            if(!$highest_cost_category || $cat['total_cost'] > $highest_cost_category['total_cost']) {
                                                $highest_cost_category = $cat;
                                            }
                                        }
                                        ?>
                                        
                                        <div class="alert alert-success">
                                            <strong>💰 Best Profit Margin:</strong> 
                                            <?php echo $best_margin_category ? $best_margin_category['category_name'] . ' (' . number_format($best_margin_category['gross_profit_margin'], 1) . '%)' : 'N/A'; ?>
                                        </div>
                                        
                                        <div class="alert alert-warning">
                                            <strong>📉 Lowest Profit Margin:</strong> 
                                            <?php echo $worst_margin_category ? $worst_margin_category['category_name'] . ' (' . number_format($worst_margin_category['gross_profit_margin'], 1) . '%)' : 'N/A'; ?>
                                        </div>
                                        
                                        <div class="alert alert-info">
                                            <strong>💸 Highest Cost Category:</strong> 
                                            <?php echo $highest_cost_category ? $highest_cost_category['category_name'] . ' (KES ' . number_format($highest_cost_category['total_cost']) . ')' : 'N/A'; ?>
                                        </div>
                                        
                                        <div class="alert alert-primary">
                                            <strong>📊 Overall Performance:</strong> 
                                            <?php 
                                            if($avg_profit_margin >= 70) echo "Excellent profit margins!";
                                            elseif($avg_profit_margin >= 50) echo "Good profit margins.";
                                            elseif($avg_profit_margin >= 30) echo "Average profit margins.";
                                            else echo "Low profit margins - consider cost optimization.";
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>📋 Recommendations</h4>
                                    </div>
                                    <div class="panel-body">
                                        <div class="alert alert-info">
                                            <strong>🔍 Cost Analysis:</strong>
                                            <ul>
                                                <li>Monitor high-cost ingredients regularly</li>
                                                <li>Negotiate better supplier prices</li>
                                                <li>Optimize portion sizes for better margins</li>
                                                <li>Consider menu engineering for low-margin items</li>
                                            </ul>
                                        </div>
                                        
                                        <div class="alert alert-success">
                                            <strong>📈 Growth Opportunities:</strong>
                                            <ul>
                                                <li>Focus on high-margin menu categories</li>
                                                <li>Promote premium items with better margins</li>
                                                <li>Implement upselling strategies</li>
                                                <li>Review pricing strategy for low-margin items</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.cost-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.cost-card h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.cost-card p {
    margin: 5px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.category-cost-row {
    padding: 15px;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.category-cost-row:last-child {
    border-bottom: none;
}

.profit-margin-bar {
    background-color: #e9ecef;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 5px;
}

.profit-margin-fill {
    background: linear-gradient(90deg, #28a745, #20c997);
    height: 100%;
    transition: width 0.3s ease;
}

.form-inline .form-group {
    margin-right: 15px;
}

.form-inline label {
    margin-right: 5px;
}
</style>

<script>
// Cost vs Revenue Chart
const ctx = document.getElementById('costRevenueChart');
if (ctx) {
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Cost', 'Profit'],
            datasets: [{
                data: [<?php echo $total_cost; ?>, <?php echo $total_profit; ?>],
                backgroundColor: ['#dc3545', '#28a745'],
                borderWidth: 0
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
</script>

<?php include '../includes/admin/footer.php'; ?>