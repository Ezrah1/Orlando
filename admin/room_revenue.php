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

$page_title = 'Room Revenue Reports';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Room Revenue Reports</h1>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php



// Get date range for filtering
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // First day of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t'); // Last day of current month

// Get room revenue data
$revenue_query = "SELECT 
                    nr.room_name,
                    nr.base_price,
                    COUNT(rb.id) as total_bookings,
                    SUM(COALESCE(p.ttot, 0)) as total_revenue,
                    AVG(COALESCE(p.ttot, 0)) as average_revenue,
                    SUM(rb.nodays) as total_nights,
                    COUNT(DISTINCT DATE(rb.cin)) as occupied_days,
                    DATEDIFF('$end_date', '$start_date') + 1 as period_days,
                    ROUND((COUNT(DISTINCT DATE(rb.cin)) / (DATEDIFF('$end_date', '$start_date') + 1)) * 100, 2) as occupancy_rate
                  FROM named_rooms nr
                  LEFT JOIN roombook rb ON nr.room_name = rb.TRoom 
                    AND rb.cin BETWEEN '$start_date' AND '$end_date'
                    AND rb.stat IN ('Conform', 'completed')
                  LEFT JOIN payment p ON rb.fname = p.fname AND rb.lname = p.lname AND rb.TRoom = p.troom AND rb.cin = p.cin
                  GROUP BY nr.id, nr.room_name, nr.base_price
                  ORDER BY total_revenue DESC";

$revenue_result = mysqli_query($con, $revenue_query);

// Get daily revenue breakdown
$daily_revenue_query = "SELECT 
                          DATE(rb.cin) as date,
                          COUNT(rb.id) as bookings,
                          SUM(COALESCE(p.ttot, 0)) as revenue,
                          AVG(COALESCE(p.ttot, 0)) as avg_revenue
                        FROM roombook rb
                        LEFT JOIN payment p ON rb.fname = p.fname AND rb.lname = p.lname AND rb.TRoom = p.troom AND rb.cin = p.cin
                        WHERE rb.cin BETWEEN '$start_date' AND '$end_date'
                          AND rb.stat IN ('Conform', 'completed')
                        GROUP BY DATE(rb.cin)
                        ORDER BY date DESC";

$daily_revenue_result = mysqli_query($con, $daily_revenue_query);

// Calculate totals
$total_revenue = 0;
$total_bookings = 0;
$total_nights = 0;
$avg_occupancy = 0;
$room_count = 0;

$revenue_data = [];
while($row = mysqli_fetch_assoc($revenue_result)) {
    $revenue_data[] = $row;
    $total_revenue += $row['total_revenue'] ?: 0;
    $total_bookings += $row['total_bookings'] ?: 0;
    $total_nights += $row['total_nights'] ?: 0;
    $avg_occupancy += $row['occupancy_rate'] ?: 0;
    $room_count++;
}

$avg_occupancy = $room_count > 0 ? $avg_occupancy / $room_count : 0;
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
                        <a href="room_revenue.php" class="btn btn-default">Reset</a>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-md-3">
            <div class="revenue-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <h3>KES <?php echo number_format($total_revenue); ?></h3>
                <p>Total Revenue</p>
            </div>
        </div>
        <div class="col-md-3">
                                <div class="revenue-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                    <h3><?php echo $total_bookings; ?></h3>
                                    <p>Total Bookings</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="revenue-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                    <h3><?php echo $total_nights; ?></h3>
                                    <p>Total Nights</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="revenue-card" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                    <h3><?php echo number_format($avg_occupancy, 1); ?>%</h3>
                                    <p>Avg Occupancy</p>
                                </div>
                            </div>
                        </div>

                        <!-- Room Revenue Breakdown -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="panel panel-default">
                                    <div class="panel-heading">
                                        <h4>📊 Room Revenue Breakdown</h4>
                                        <p>Revenue performance by room for <?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
                                    </div>
                                    <div class="panel-body">
                                        <?php if(count($revenue_data) > 0): ?>
                                            <?php foreach($revenue_data as $room): ?>
                                                <div class="room-revenue-row">
                                                    <div class="row">
                                                        <div class="col-md-3">
                                                            <h5><?php echo $room['room_name']; ?></h5>
                                                            <small class="text-muted">KES <?php echo number_format($room['base_price']); ?>/night</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong>KES <?php echo number_format($room['total_revenue'] ?: 0); ?></strong>
                                                            <br><small class="text-muted">Revenue</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong><?php echo $room['total_bookings'] ?: 0; ?></strong>
                                                            <br><small class="text-muted">Bookings</small>
                                                        </div>
                                                        <div class="col-md-2">
                                                            <strong><?php echo $room['total_nights'] ?: 0; ?></strong>
                                                            <br><small class="text-muted">Nights</small>
                                                        </div>
                                                        <div class="col-md-3">
                                                            <div class="occupancy-bar">
                                                <div class="occupancy-fill" style="width: <?php echo min($room['occupancy_rate'] ?: 0, 100); ?>%"></div>
                                            </div>
                                            <small class="text-muted"><?php echo number_format($room['occupancy_rate'] ?: 0, 1); ?>% occupancy</small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No revenue data for the selected period.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Revenue Chart -->
            <div class="col-md-4">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>📈 Revenue Distribution</h4>
                    </div>
                    <div class="panel-body">
                        <canvas id="revenueChart" width="400" height="300"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daily Revenue Trend -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>📅 Daily Revenue Trend</h4>
                        <p>Daily booking revenue for <?php echo date('M j', strtotime($start_date)); ?> - <?php echo date('M j, Y', strtotime($end_date)); ?></p>
                    </div>
                    <div class="panel-body">
                        <?php if(mysqli_num_rows($daily_revenue_result) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Bookings</th>
                                            <th>Revenue</th>
                                            <th>Average Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while($day = mysqli_fetch_assoc($daily_revenue_result)): ?>
                                            <tr>
                                                <td><?php echo date('M j, Y', strtotime($day['date'])); ?></td>
                                                <td><?php echo $day['bookings']; ?></td>
                                                <td>KES <?php echo number_format($day['revenue'] ?: 0); ?></td>
                                                <td>KES <?php echo number_format($day['avg_revenue'] ?: 0, 2); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted">No daily revenue data for the selected period.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Performing Rooms -->
        <div class="row">
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>🏆 Top Performing Rooms</h4>
                    </div>
                    <div class="panel-body">
                        <?php 
                        $top_rooms = array_slice($revenue_data, 0, 5);
                        foreach($top_rooms as $index => $room): 
                            if($room['total_revenue'] > 0):
                        ?>
                            <div class="room-revenue-row">
                                <div class="row">
                                    <div class="col-md-1">
                                        <h4 class="text-primary">#<?php echo $index + 1; ?></h4>
                                    </div>
                                    <div class="col-md-4">
                                        <h5><?php echo $room['room_name']; ?></h5>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>KES <?php echo number_format($room['total_revenue']); ?></strong>
                                    </div>
                                    <div class="col-md-2">
                                        <?php echo $room['total_bookings']; ?> bookings
                                    </div>
                                    <div class="col-md-2">
                                        <?php echo number_format($room['occupancy_rate'], 1); ?>%
                                    </div>
                                </div>
                            </div>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
            </div>

            <!-- Revenue Insights -->
            <div class="col-md-6">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <h4>💡 Revenue Insights</h4>
                    </div>
                    <div class="panel-body">
                        <?php
                        $highest_revenue_room = $revenue_data[0] ?? null;
                        $lowest_revenue_room = end($revenue_data) ?? null;
                        $avg_revenue_per_booking = $total_bookings > 0 ? $total_revenue / $total_bookings : 0;
                        ?>
                        
                        <div class="alert alert-info">
                            <strong>💰 Highest Revenue:</strong> 
                            <?php echo $highest_revenue_room ? $highest_revenue_room['room_name'] . ' (KES ' . number_format($highest_revenue_room['total_revenue']) . ')' : 'N/A'; ?>
                        </div>
                        
                        <div class="alert alert-warning">
                            <strong>📉 Lowest Revenue:</strong> 
                            <?php echo $lowest_revenue_room && $lowest_revenue_room['total_revenue'] > 0 ? $lowest_revenue_room['room_name'] . ' (KES ' . number_format($lowest_revenue_room['total_revenue']) . ')' : 'N/A'; ?>
                        </div>
                        
                        <div class="alert alert-success">
                            <strong>📊 Average Revenue per Booking:</strong> 
                            KES <?php echo number_format($avg_revenue_per_booking, 2); ?>
                        </div>
                        
                        <div class="alert alert-primary">
                            <strong>🏨 Overall Occupancy Rate:</strong> 
                            <?php echo number_format($avg_occupancy, 1); ?>%
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.revenue-card {
    padding: 20px;
    border-radius: 10px;
    color: white;
    text-align: center;
    margin-bottom: 20px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.revenue-card h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
}

.revenue-card p {
    margin: 5px 0 0 0;
    font-size: 14px;
    opacity: 0.9;
}

.room-revenue-row {
    padding: 15px;
    border-bottom: 1px solid #eee;
    margin-bottom: 10px;
}

.room-revenue-row:last-child {
    border-bottom: none;
}

.occupancy-bar {
    background-color: #e9ecef;
    height: 10px;
    border-radius: 5px;
    overflow: hidden;
    margin-bottom: 5px;
}

.occupancy-fill {
    background: linear-gradient(90deg, #007bff, #0056b3);
    height: 100%;
    transition: width 0.3s ease;
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

.panel {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
    border-radius: 8px;
    margin-bottom: 20px;
}

.panel-heading {
    background: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
    border-radius: 8px 8px 0 0;
}

.panel-body {
    padding: 20px;
}
</style>

<script>
// Revenue Chart Data
const roomNames = [
    <?php
    foreach($revenue_data as $room) {
        if($room['total_revenue'] > 0) {
            echo "'" . addslashes($room['room_name']) . "',";
        }
    }
    ?>
];

const roomRevenues = [
    <?php
    foreach($revenue_data as $room) {
        if($room['total_revenue'] > 0) {
            echo ($room['total_revenue'] ?: 0) . ",";
        }
    }
    ?>
];

// Revenue Distribution Chart
const revenueCtx = document.getElementById('revenueChart');
if (revenueCtx) {
    new Chart(revenueCtx, {
        type: 'doughnut',
        data: {
            labels: roomNames,
            datasets: [{
                data: roomRevenues,
                backgroundColor: [
                    '#007bff', '#28a745', '#dc3545', '#ffc107', '#17a2b8',
                    '#6f42c1', '#e83e8c', '#fd7e14', '#20c997', '#6c757d'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': KES ' + context.parsed.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}
</script>

<?php include '../includes/admin/footer.php'; ?>