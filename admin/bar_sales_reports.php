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

$page_title = 'Bar Sales Reports';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Bar Sales Reports</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

// Get date range filter
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get sales summary
$summary_query = "SELECT 
                    COUNT(DISTINCT bo.id) as total_orders,
                    SUM(bo.final_amount) as total_sales,
                    SUM(boi.total_cost) as total_cost,
                    SUM(bo.final_amount - boi.total_cost) as total_profit,
                    AVG(bo.final_amount) as average_order_value,
                    SUM(CASE WHEN bi.is_alcoholic = 1 THEN boi.total_price ELSE 0 END) as alcohol_sales,
                    SUM(CASE WHEN bi.is_alcoholic = 0 THEN boi.total_price ELSE 0 END) as non_alcohol_sales
                  FROM bar_orders bo
                  LEFT JOIN bar_order_items boi ON bo.id = boi.order_id
                  LEFT JOIN bar_inventory bi ON boi.inventory_id = bi.id
                  WHERE bo.ordered_time BETWEEN '$start_date' AND '$end_date'
                    AND bo.status IN ('served', 'completed')
                    AND bo.payment_status = 'paid'";
$summary_result = mysqli_query($con, $summary_query);
$summary = mysqli_fetch_assoc($summary_result);

// Calculate profit margin
$profit_margin = $summary['total_sales'] > 0 ? ($summary['total_profit'] / $summary['total_sales']) * 100 : 0;

// Get top selling items
$top_items_query = "SELECT 
                      bi.name,
                      bi.brand,
                      bc.name as category,
                      SUM(boi.quantity) as total_quantity,
                      SUM(boi.total_price) as total_revenue,
                      SUM(boi.total_cost) as total_cost,
                      SUM(boi.total_price - boi.total_cost) as total_profit
                    FROM bar_order_items boi
                    LEFT JOIN bar_inventory bi ON boi.inventory_id = bi.id
                    LEFT JOIN bar_categories bc ON bi.category_id = bc.id
                    LEFT JOIN bar_orders bo ON boi.order_id = bo.id
                    WHERE bo.ordered_time BETWEEN '$start_date' AND '$end_date'
                      AND bo.status IN ('served', 'completed')
                      AND bo.payment_status = 'paid'
                    GROUP BY boi.inventory_id
                    ORDER BY total_revenue DESC
                    LIMIT 10";
$top_items_result = mysqli_query($con, $top_items_query);

// Get sales by category
$category_sales_query = "SELECT 
                           bc.name as category,
                           SUM(boi.total_price) as total_sales,
                           SUM(boi.total_cost) as total_cost,
                           SUM(boi.total_price - boi.total_cost) as total_profit,
                           COUNT(DISTINCT bo.id) as order_count
                         FROM bar_order_items boi
                         LEFT JOIN bar_inventory bi ON boi.inventory_id = bi.id
                         LEFT JOIN bar_categories bc ON bi.category_id = bc.id
                         LEFT JOIN bar_orders bo ON boi.order_id = bo.id
                         WHERE bo.ordered_time BETWEEN '$start_date' AND '$end_date'
                           AND bo.status IN ('served', 'completed')
                           AND bo.payment_status = 'paid'
                         GROUP BY bc.id
                         ORDER BY total_sales DESC";
$category_sales_result = mysqli_query($con, $category_sales_query);

// Get daily sales trend
$daily_trend_query = "SELECT 
                        DATE(bo.ordered_time) as date,
                        COUNT(DISTINCT bo.id) as orders,
                        SUM(bo.final_amount) as sales,
                        SUM(boi.total_cost) as cost,
                        SUM(bo.final_amount - boi.total_cost) as profit
                      FROM bar_orders bo
                      LEFT JOIN bar_order_items boi ON bo.id = boi.order_id
                      WHERE bo.ordered_time BETWEEN '$start_date' AND '$end_date'
                        AND bo.status IN ('served', 'completed')
                        AND bo.payment_status = 'paid'
                      GROUP BY DATE(bo.ordered_time)
                      ORDER BY date";
$daily_trend_result = mysqli_query($con, $daily_trend_query);

// Get sales by shift
$shift_sales_query = "SELECT 
                        bs.shift_name,
                        COUNT(DISTINCT bo.id) as orders,
                        SUM(bo.final_amount) as sales,
                        AVG(bo.final_amount) as avg_order_value
                      FROM bar_orders bo
                      LEFT JOIN bar_shifts bs ON bo.shift_id = bs.id
                      WHERE bo.ordered_time BETWEEN '$start_date' AND '$end_date'
                        AND bo.status IN ('served', 'completed')
                        AND bo.payment_status = 'paid'
                      GROUP BY bs.id
                      ORDER BY sales DESC";
$shift_sales_result = mysqli_query($con, $shift_sales_query);
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
                        <a href="bar_sales_reports.php" class="btn btn-default">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="summary-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3><?php echo $summary['total_orders']; ?></h3>
                <p>Total Orders</p>
            </div>
        </div>
        <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h3>KES <?php echo number_format($summary['total_sales']); ?></h3>
                                    <p>Total Sales</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h3>KES <?php echo number_format($summary['total_profit']); ?></h3>
                                    <p>Total Profit</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="summary-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                    <h3><?php echo number_format($profit_margin, 1); ?>%</h3>
                                    <p>Profit Margin</p>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Metrics -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>Average Order Value</h5>
                                    <h3 class="text-primary">KES <?php echo number_format($summary['average_order_value'], 2); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>Alcohol Sales</h5>
                                    <h3 class="text-success">KES <?php echo number_format($summary['alcohol_sales']); ?></h3>
                                    <small><?php echo $summary['total_sales'] > 0 ? number_format(($summary['alcohol_sales'] / $summary['total_sales']) * 100, 1) : 0; ?>% of total</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="metric-card">
                                    <h5>Non-Alcohol Sales</h5>
                                    <h3 class="text-info">KES <?php echo number_format($summary['non_alcohol_sales']); ?></h3>
                                    <small><?php echo $summary['total_sales'] > 0 ? number_format(($summary['non_alcohol_sales'] / $summary['total_sales']) * 100, 1) : 0; ?>% of total</small>
                                </div>
                            </div>
                        </div>

                        <!-- Daily Sales Trend Chart -->
                        <div class="report-section">
                            <h4><i class="fa fa-chart-line"></i> Daily Sales Trend</h4>
                            <div class="chart-container">
                                <canvas id="dailyTrendChart"></canvas>
                            </div>
                        </div>

                        <!-- Top Selling Items -->
                        <div class="report-section">
                            <h4><i class="fa fa-star"></i> Top Selling Items</h4>
                            <?php if(mysqli_num_rows($top_items_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Item</th>
                                                <th>Brand</th>
                                                <th>Category</th>
                                                <th>Quantity Sold</th>
                                                <th>Revenue</th>
                                                <th>Cost</th>
                                                <th>Profit</th>
                                                <th>Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($item = mysqli_fetch_assoc($top_items_result)): 
                                                $margin = $item['total_revenue'] > 0 ? ($item['total_profit'] / $item['total_revenue']) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo $item['name']; ?></strong></td>
                                                    <td><?php echo $item['brand']; ?></td>
                                                    <td><?php echo $item['category']; ?></td>
                                                    <td><?php echo number_format($item['total_quantity'], 3); ?></td>
                                                    <td>KES <?php echo number_format($item['total_revenue']); ?></td>
                                                    <td>KES <?php echo number_format($item['total_cost']); ?></td>
                                                    <td class="<?php echo $item['total_profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                        KES <?php echo number_format($item['total_profit']); ?>
                                                    </td>
                                                    <td class="<?php echo $margin >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                        <?php echo number_format($margin, 1); ?>%
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No sales data found for the selected period.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Sales by Category -->
                        <div class="report-section">
                            <h4><i class="fa fa-list"></i> Sales by Category</h4>
                            <?php if(mysqli_num_rows($category_sales_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Category</th>
                                                <th>Orders</th>
                                                <th>Sales</th>
                                                <th>Cost</th>
                                                <th>Profit</th>
                                                <th>Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($category = mysqli_fetch_assoc($category_sales_result)): 
                                                $margin = $category['total_sales'] > 0 ? ($category['total_profit'] / $category['total_sales']) * 100 : 0;
                                            ?>
                                                <tr>
                                                    <td><strong><?php echo $category['category']; ?></strong></td>
                                                    <td><?php echo $category['order_count']; ?></td>
                                                    <td>KES <?php echo number_format($category['total_sales']); ?></td>
                                                    <td>KES <?php echo number_format($category['total_cost']); ?></td>
                                                    <td class="<?php echo $category['total_profit'] >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                        KES <?php echo number_format($category['total_profit']); ?>
                                                    </td>
                                                    <td class="<?php echo $margin >= 0 ? 'profit-positive' : 'profit-negative'; ?>">
                                                        <?php echo number_format($margin, 1); ?>%
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No category sales data found for the selected period.</div>
                            <?php endif; ?>
                        </div>

                        <!-- Sales by Shift -->
                        <div class="report-section">
                            <h4><i class="fa fa-clock-o"></i> Sales by Shift</h4>
                            <?php if(mysqli_num_rows($shift_sales_result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Shift</th>
                                                <th>Orders</th>
                                                <th>Sales</th>
                                                <th>Average Order Value</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while($shift = mysqli_fetch_assoc($shift_sales_result)): ?>
                                                <tr>
                                                    <td><strong><?php echo $shift['shift_name']; ?></strong></td>
                                                    <td><?php echo $shift['orders']; ?></td>
                                                    <td>KES <?php echo number_format($shift['sales']); ?></td>
                                                    <td>KES <?php echo number_format($shift['avg_order_value'], 2); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">No shift sales data found for the selected period.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.summary-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.summary-card h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.summary-card p {
    margin: 5px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.metric-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    text-align: center;
}

.report-section {
    background: white;
    margin: 20px 0;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.report-section h4 {
    margin-bottom: 20px;
    color: #333;
    border-bottom: 2px solid #f8f9fa;
    padding-bottom: 10px;
}

.chart-container {
    position: relative;
    height: 300px;
    margin: 20px 0;
}

.profit-positive {
    color: #28a745;
    font-weight: bold;
}

.profit-negative {
    color: #dc3545;
    font-weight: bold;
}

.form-inline .form-group {
    margin-right: 15px;
}

.form-inline label {
    margin-right: 5px;
}

.table th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0,0,0,0.02);
}
</style>

<script>
// Prepare data for charts
const dailyTrendData = [
    <?php
    $dates = [];
    $sales = [];
    $costs = [];
    $profits = [];
    
    mysqli_data_seek($daily_trend_result, 0);
    while($day = mysqli_fetch_assoc($daily_trend_result)) {
        echo "{ date: '" . $day['date'] . "', sales: " . ($day['sales'] ?: 0) . ", cost: " . ($day['cost'] ?: 0) . ", profit: " . ($day['profit'] ?: 0) . " },";
    }
    ?>
];

// Daily Trend Chart
const dailyCtx = document.getElementById('dailyTrendChart');
if (dailyCtx) {
    new Chart(dailyCtx, {
        type: 'line',
        data: {
            labels: dailyTrendData.map(d => d.date),
            datasets: [
                {
                    label: 'Sales',
                    data: dailyTrendData.map(d => d.sales),
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0,123,255,0.1)',
                    tension: 0.4
                },
                {
                    label: 'Cost',
                    data: dailyTrendData.map(d => d.cost),
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220,53,69,0.1)',
                    tension: 0.4
                },
                {
                    label: 'Profit',
                    data: dailyTrendData.map(d => d.profit),
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40,167,69,0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'KES ' + value.toLocaleString();
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': KES ' + context.parsed.y.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/admin/footer.php'; ?>