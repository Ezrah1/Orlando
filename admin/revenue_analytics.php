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

$page_title = 'Revenue Analytics';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page content starts here -->

<?php
// Display session alerts
display_session_alerts();
?>

<?php



// Set page title
$page_title = 'Revenue Analytics';

// Header already included at the top

// Get filter parameters
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// Calculate date range for queries
$date_condition = "BETWEEN '$date_from' AND '$date_to'";

// Get revenue data by department
function getDepartmentRevenue($con, $department, $date_condition) {
    switch($department) {
        case 'accommodation':
            $sql = "                    SELECT 
                        DATE(rb.cin) as date,
                        COUNT(*) as bookings,
                        SUM(nr.base_price * rb.nodays) as revenue,
                        SUM(nr.base_price * rb.nodays * 0.15) as estimated_cost,
                        SUM(nr.base_price * rb.nodays * 0.85) as gross_profit
                    FROM roombook rb
                    LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
                    WHERE rb.cin $date_condition AND rb.stat = 'Conform'
                    GROUP BY DATE(rb.cin)
                    ORDER BY date";
            break;
            
        case 'food':
            $sql = "SELECT 
                        DATE(fo.ordered_time) as date,
                        COUNT(*) as orders,
                        SUM(fo.final_amount) as revenue,
                        SUM(oi.total_cost) as cost,
                        SUM(fo.final_amount - oi.total_cost) as gross_profit
                    FROM food_orders fo
                    LEFT JOIN order_items oi ON fo.id = oi.order_id
                    WHERE fo.ordered_time $date_condition AND fo.status = 'served'
                    GROUP BY DATE(fo.ordered_time)
                    ORDER BY date";
            break;
            
        case 'bar':
            $sql = "SELECT 
                        DATE(bo.ordered_time) as date,
                        COUNT(*) as orders,
                        SUM(bo.final_amount) as revenue,
                        SUM(boi.total_cost) as cost,
                        SUM(bo.final_amount - boi.total_cost) as gross_profit
                    FROM bar_orders bo
                    LEFT JOIN bar_order_items boi ON bo.id = boi.order_id
                    WHERE bo.ordered_time $date_condition AND bo.status = 'served'
                    GROUP BY DATE(bo.ordered_time)
                    ORDER BY date";
            break;
            
        default:
            return [];
    }
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get summary statistics
function getSummaryStats($con, $date_condition) {
    $stats = [];
    
    // Accommodation stats
    $acc_sql = "SELECT 
                    COUNT(*) as total_bookings,
                    SUM(nr.base_price * rb.nodays) as total_revenue,
                    AVG(nr.base_price * rb.nodays) as avg_booking_value
                FROM roombook rb
                LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
                WHERE rb.cin $date_condition AND rb.stat = 'Conform'";
    $acc_result = mysqli_query($con, $acc_sql);
    $stats['accommodation'] = mysqli_fetch_assoc($acc_result);
    
    // Food stats
    $food_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(fo.final_amount) as total_revenue,
                    AVG(fo.final_amount) as avg_order_value
                FROM food_orders fo
                WHERE fo.ordered_time $date_condition AND fo.status = 'served'";
    $food_result = mysqli_query($con, $food_sql);
    $stats['food'] = mysqli_fetch_assoc($food_result);
    
    // Bar stats
    $bar_sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(bo.final_amount) as total_revenue,
                    AVG(bo.final_amount) as avg_order_value
                FROM bar_orders bo
                WHERE bo.ordered_time $date_condition AND bo.status = 'served'";
    $bar_result = mysqli_query($con, $bar_sql);
    $stats['bar'] = mysqli_fetch_assoc($bar_result);
    
    return $stats;
}

// Get trend data for charts
function getTrendData($con, $date_condition) {
    $trend_sql = "SELECT 
                    DATE(date) as date,
                    SUM(CASE WHEN source = 'accommodation' THEN revenue ELSE 0 END) as accommodation_revenue,
                    SUM(CASE WHEN source = 'food' THEN revenue ELSE 0 END) as food_revenue,
                    SUM(CASE WHEN source = 'bar' THEN revenue ELSE 0 END) as bar_revenue,
                    SUM(revenue) as total_revenue
                FROM (
                    SELECT rb.cin as date, nr.base_price * rb.nodays as revenue, 'accommodation' as source
                    FROM roombook rb
                    LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
                    WHERE rb.cin $date_condition AND rb.stat = 'Conform'
                    UNION ALL
                    SELECT fo.ordered_time as date, fo.final_amount as revenue, 'food' as source
                    FROM food_orders fo
                    WHERE fo.ordered_time $date_condition AND fo.status = 'served'
                    UNION ALL
                    SELECT bo.ordered_time as date, bo.final_amount as revenue, 'bar' as source
                    FROM bar_orders bo
                    WHERE bo.ordered_time $date_condition AND bo.status = 'served'
                ) as combined_data
                GROUP BY DATE(date)
                ORDER BY date";
    
    $result = mysqli_query($con, $trend_sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

$summary_stats = getSummaryStats($con, $date_condition);
$trend_data = getTrendData($con, $date_condition);

// Get department-specific data if filter is applied
$department_data = [];
if($department_filter) {
    $department_data = getDepartmentRevenue($con, $department_filter, $date_condition);
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Revenue Analytics</h1>
    <p class="page-subtitle">Comprehensive revenue analysis and cross-department comparisons</p>
</div>

<!-- Action Buttons -->
<div class="row mb-4">
    <div class="col-md-12 text-right">
        <button class="btn btn-success" onclick="exportReport()">
            <i class="fas fa-download"></i> Export Report
        </button>
        <button class="btn btn-primary" onclick="printReport()">
            <i class="fas fa-print"></i> Print
        </button>
    </div>
</div>

                    <!-- Filters -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <form method="GET" class="row">
                                <div class="col-md-2">
                                    <label>Department</label>
                                    <select name="department" class="form-control">
                                        <option value="">All Departments</option>
                                        <option value="accommodation" <?php echo ($department_filter == 'accommodation') ? 'selected' : ''; ?>>Accommodation</option>
                                        <option value="food" <?php echo ($department_filter == 'food') ? 'selected' : ''; ?>>Food & Kitchen</option>
                                        <option value="bar" <?php echo ($department_filter == 'bar') ? 'selected' : ''; ?>>Bar</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>Date From</label>
                                    <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>Date To</label>
                                    <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                                </div>
                                <div class="col-md-2">
                                    <label>Report Type</label>
                                    <select name="report_type" class="form-control">
                                        <option value="summary" <?php echo ($report_type == 'summary') ? 'selected' : ''; ?>>Summary</option>
                                        <option value="detailed" <?php echo ($report_type == 'detailed') ? 'selected' : ''; ?>>Detailed</option>
                                        <option value="trends" <?php echo ($report_type == 'trends') ? 'selected' : ''; ?>>Trends</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">
                                        <i class="fas fa-filter"></i> Generate Report
                                    </button>
                                </div>
                                <div class="col-md-2">
                                    <label>&nbsp;</label>
                                    <a href="revenue_analytics.php" class="btn btn-secondary btn-block">
                                        <i class="fas fa-times"></i> Clear
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="stat-card accommodation">
                                <div class="row">
                                    <div class="col-8">
                                        <h4>KES <?php echo number_format($summary_stats['accommodation']['total_revenue'] ?? 0, 2); ?></h4>
                                        <p>Accommodation Revenue</p>
                                        <small><?php echo $summary_stats['accommodation']['total_bookings'] ?? 0; ?> bookings</small>
                                    </div>
                                    <div class="col-4 text-right">
                                        <i class="fas fa-bed fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card food">
                                <div class="row">
                                    <div class="col-8">
                                        <h4>KES <?php echo number_format($summary_stats['food']['total_revenue'] ?? 0, 2); ?></h4>
                                        <p>Food & Kitchen Revenue</p>
                                        <small><?php echo $summary_stats['food']['total_orders'] ?? 0; ?> orders</small>
                                    </div>
                                    <div class="col-4 text-right">
                                        <i class="fas fa-utensils fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card bar">
                                <div class="row">
                                    <div class="col-8">
                                        <h4>KES <?php echo number_format($summary_stats['bar']['total_revenue'] ?? 0, 2); ?></h4>
                                        <p>Bar Revenue</p>
                                        <small><?php echo $summary_stats['bar']['total_orders'] ?? 0; ?> orders</small>
                                    </div>
                                    <div class="col-4 text-right">
                                        <i class="fas fa-glass fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card total">
                                <div class="row">
                                    <div class="col-8">
                                        <h4>KES <?php echo number_format(($summary_stats['accommodation']['total_revenue'] ?? 0) + ($summary_stats['food']['total_revenue'] ?? 0) + ($summary_stats['bar']['total_revenue'] ?? 0), 2); ?></h4>
                                        <p>Total Revenue</p>
                                        <small>All Departments</small>
                                    </div>
                                    <div class="col-4 text-right">
                                        <i class="fas fa-chart-line fa-3x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue Trends Chart -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5><i class="fas fa-chart-area"></i> Revenue Trends</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="revenueTrendsChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Department Comparison Chart -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-pie"></i> Revenue by Department</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="departmentPieChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5><i class="fas fa-chart-bar"></i> Average Order Values</h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="avgOrderChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Detailed Data Table -->
                    <?php if($department_filter && $report_type == 'detailed'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-table"></i> Detailed Revenue Data - <?php echo ucfirst($department_filter); ?></h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Orders/Bookings</th>
                                            <th>Revenue (KES)</th>
                                            <th>Cost (KES)</th>
                                            <th>Gross Profit (KES)</th>
                                            <th>Profit Margin (%)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($department_data as $row): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($row['date'])); ?></td>
                                                <td><?php echo $row['orders'] ?? $row['bookings']; ?></td>
                                                <td><?php echo number_format($row['revenue'], 2); ?></td>
                                                <td><?php echo number_format($row['cost'] ?? $row['estimated_cost'], 2); ?></td>
                                                <td><?php echo number_format($row['gross_profit'], 2); ?></td>
                                                <td>
                                                    <?php 
                                                    $margin = $row['revenue'] > 0 ? ($row['gross_profit'] / $row['revenue']) * 100 : 0;
                                                    echo number_format($margin, 1) . '%';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
<?php include '../includes/admin/footer.php'; ?>