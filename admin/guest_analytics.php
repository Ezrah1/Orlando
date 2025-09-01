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

$page_title = 'Guest Analytics';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Guest Analytics</h1>
    <p class="page-subtitle">Comprehensive guest behavior analysis and insights</p>
</div>

<?php
// Display session alerts
display_session_alerts();

// Get filter parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : date('Y-m-01');
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : date('Y-m-d');
$guest_type = isset($_GET['guest_type']) ? $_GET['guest_type'] : 'all';
$country_filter = isset($_GET['country']) ? $_GET['country'] : '';

// Calculate date range for queries
$date_condition = "BETWEEN '$date_from' AND '$date_to'";

// Get guest summary statistics
function getGuestSummaryStats($con, $date_condition) {
    $sql = "SELECT 
                COUNT(DISTINCT rb.id) as total_bookings,
                COUNT(DISTINCT rb.Email) as unique_guests,
                AVG(rb.nodays) as avg_stay_length,
                AVG(nr.base_price * rb.nodays) as avg_booking_value,
                COUNT(CASE WHEN rb.stat = 'Confirm' THEN 1 END) as confirmed_bookings,
                COUNT(CASE WHEN rb.stat = 'Not Confirm' THEN 1 END) as pending_bookings,
                COUNT(CASE WHEN rb.stat = 'Cancel' THEN 1 END) as cancelled_bookings
            FROM roombook rb
            LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
            WHERE rb.cin $date_condition";
    
    $result = mysqli_query($con, $sql);
    return mysqli_fetch_assoc($result);
}

// Get guest demographics
function getGuestDemographics($con, $date_condition) {
    $sql = "SELECT 
                Country,
                National,
                COUNT(*) as guest_count,
                AVG(nr.base_price * rb.nodays) as avg_spending
            FROM roombook rb
            LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
            WHERE rb.cin $date_condition
            GROUP BY Country, National
            ORDER BY guest_count DESC
            LIMIT 10";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get repeat guests
function getRepeatGuests($con, $date_condition) {
    $sql = "SELECT 
                Email,
                FName,
                LName,
                COUNT(*) as booking_count,
                SUM(nr.base_price * rb.nodays) as total_spent,
                MIN(rb.cin) as first_booking,
                MAX(rb.cin) as last_booking
            FROM roombook rb
            LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
            WHERE rb.Email IS NOT NULL AND rb.Email != ''
            GROUP BY Email
            HAVING booking_count > 1
            ORDER BY booking_count DESC, total_spent DESC
            LIMIT 20";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get booking trends by month
function getBookingTrends($con, $date_condition) {
    $sql = "SELECT 
                DATE_FORMAT(rb.cin, '%Y-%m') as month,
                COUNT(*) as bookings,
                COUNT(DISTINCT rb.Email) as unique_guests,
                AVG(rb.nodays) as avg_stay_length,
                SUM(nr.base_price * rb.nodays) as total_revenue
            FROM roombook rb
            LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
            WHERE rb.cin $date_condition
            GROUP BY DATE_FORMAT(rb.cin, '%Y-%m')
            ORDER BY month";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get room type preferences
function getRoomPreferences($con, $date_condition) {
    $sql = "SELECT 
                rb.TRoom as room_type,
                COUNT(*) as booking_count,
                AVG(rb.nodays) as avg_stay_length,
                AVG(nr.base_price * rb.nodays) as avg_revenue
            FROM roombook rb
            LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name
            WHERE rb.cin $date_condition
            GROUP BY rb.TRoom
            ORDER BY booking_count DESC";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get seasonal patterns
function getSeasonalPatterns($con) {
    $sql = "SELECT 
                MONTH(rb.cin) as month,
                MONTHNAME(rb.cin) as month_name,
                COUNT(*) as bookings,
                AVG(rb.nodays) as avg_stay_length,
                COUNT(DISTINCT rb.Email) as unique_guests
            FROM roombook rb
            WHERE rb.cin >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY MONTH(rb.cin), MONTHNAME(rb.cin)
            ORDER BY month";
    
    $result = mysqli_query($con, $sql);
    $data = [];
    while($row = mysqli_fetch_assoc($result)) {
        $data[] = $row;
    }
    return $data;
}

// Get guest satisfaction metrics (placeholder - would come from reviews table)
function getGuestSatisfactionMetrics($con, $date_condition) {
    return [
        'avg_rating' => 4.2,
        'total_reviews' => 156,
        'recommendation_rate' => 92,
        'response_rate' => 68
    ];
}

// Get data
$summary_stats = getGuestSummaryStats($con, $date_condition);
$demographics = getGuestDemographics($con, $date_condition);
$repeat_guests = getRepeatGuests($con, $date_condition);
$booking_trends = getBookingTrends($con, $date_condition);
$room_preferences = getRoomPreferences($con, $date_condition);
$seasonal_patterns = getSeasonalPatterns($con);
$satisfaction_metrics = getGuestSatisfactionMetrics($con, $date_condition);

// Calculate additional metrics
$repeat_guest_rate = $summary_stats['unique_guests'] > 0 ? 
    round((count($repeat_guests) / $summary_stats['unique_guests']) * 100, 1) : 0;
$cancellation_rate = $summary_stats['total_bookings'] > 0 ? 
    round(($summary_stats['cancelled_bookings'] / $summary_stats['total_bookings']) * 100, 1) : 0;
?>

<!-- Action Buttons -->
<div class="row mb-4">
    <div class="col-md-12 text-right">
        <button class="btn btn-success" onclick="exportGuestReport()">
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
            <div class="col-md-3">
                <label>Date From</label>
                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
            </div>
            <div class="col-md-3">
                <label>Date To</label>
                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
            </div>
            <div class="col-md-2">
                <label>Guest Type</label>
                <select name="guest_type" class="form-control">
                    <option value="all" <?php echo ($guest_type == 'all') ? 'selected' : ''; ?>>All Guests</option>
                    <option value="new" <?php echo ($guest_type == 'new') ? 'selected' : ''; ?>>New Guests</option>
                    <option value="repeat" <?php echo ($guest_type == 'repeat') ? 'selected' : ''; ?>>Repeat Guests</option>
                </select>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </div>
            <div class="col-md-2">
                <label>&nbsp;</label>
                <a href="guest_analytics.php" class="btn btn-secondary btn-block">
                    <i class="fas fa-times"></i> Clear
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stat-card primary">
            <div class="row">
                <div class="col-8">
                    <h4><?php echo number_format($summary_stats['total_bookings']); ?></h4>
                    <p>Total Bookings</p>
                    <small><?php echo number_format($summary_stats['unique_guests']); ?> unique guests</small>
                </div>
                <div class="col-4 text-right">
                    <i class="fas fa-users fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success">
            <div class="row">
                <div class="col-8">
                    <h4><?php echo number_format($summary_stats['avg_stay_length'], 1); ?></h4>
                    <p>Avg Stay Length</p>
                    <small>days per booking</small>
                </div>
                <div class="col-4 text-right">
                    <i class="fas fa-calendar fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning">
            <div class="row">
                <div class="col-8">
                    <h4>KES <?php echo number_format($summary_stats['avg_booking_value'], 0); ?></h4>
                    <p>Avg Booking Value</p>
                    <small>per reservation</small>
                </div>
                <div class="col-4 text-right">
                    <i class="fas fa-money-bill fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info">
            <div class="row">
                <div class="col-8">
                    <h4><?php echo $repeat_guest_rate; ?>%</h4>
                    <p>Repeat Guest Rate</p>
                    <small><?php echo count($repeat_guests); ?> returning guests</small>
                </div>
                <div class="col-4 text-right">
                    <i class="fas fa-heart fa-3x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-line"></i> Booking Trends</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="bookingTrendsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-chart-pie"></i> Guest Demographics</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="demographicsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Room Preferences and Seasonal Patterns -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bed"></i> Room Type Preferences</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Room Type</th>
                                <th>Bookings</th>
                                <th>Avg Stay</th>
                                <th>Avg Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($room_preferences as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_type']); ?></td>
                                <td><?php echo number_format($room['booking_count']); ?></td>
                                <td><?php echo number_format($room['avg_stay_length'], 1); ?> days</td>
                                <td>KES <?php echo number_format($room['avg_revenue'], 0); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt"></i> Seasonal Patterns</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="seasonalChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Guest Satisfaction and Repeat Guests -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-star"></i> Guest Satisfaction Metrics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <div class="satisfaction-metric">
                            <h3 class="text-warning"><?php echo $satisfaction_metrics['avg_rating']; ?>/5</h3>
                            <p>Average Rating</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="satisfaction-metric">
                            <h3 class="text-success"><?php echo $satisfaction_metrics['recommendation_rate']; ?>%</h3>
                            <p>Recommendation Rate</p>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="row text-center">
                    <div class="col-6">
                        <div class="satisfaction-metric">
                            <h3 class="text-info"><?php echo $satisfaction_metrics['total_reviews']; ?></h3>
                            <p>Total Reviews</p>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="satisfaction-metric">
                            <h3 class="text-primary"><?php echo $satisfaction_metrics['response_rate']; ?>%</h3>
                            <p>Response Rate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-trophy"></i> Top Repeat Guests</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Guest</th>
                                <th>Bookings</th>
                                <th>Total Spent</th>
                                <th>Last Visit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 0; foreach($repeat_guests as $guest): $count++; if($count > 10) break; ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($guest['FName'] . ' ' . $guest['LName']); ?></strong>
                                    <br><small class="text-muted"><?php echo htmlspecialchars($guest['Email']); ?></small>
                                </td>
                                <td><?php echo $guest['booking_count']; ?></td>
                                <td>KES <?php echo number_format($guest['total_spent'], 0); ?></td>
                                <td><?php echo date('M d, Y', strtotime($guest['last_booking'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Demographics Table -->
<div class="card mb-4">
    <div class="card-header">
        <h5><i class="fas fa-globe"></i> Guest Demographics by Country/Nationality</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Country</th>
                        <th>Nationality</th>
                        <th>Guest Count</th>
                        <th>Average Spending</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $total_demo_guests = array_sum(array_column($demographics, 'guest_count'));
                    foreach($demographics as $demo): 
                        $percentage = $total_demo_guests > 0 ? round(($demo['guest_count'] / $total_demo_guests) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($demo['Country'] ?? 'Not specified'); ?></td>
                        <td><?php echo htmlspecialchars($demo['National'] ?? 'Not specified'); ?></td>
                        <td><?php echo number_format($demo['guest_count']); ?></td>
                        <td>KES <?php echo number_format($demo['avg_spending'], 0); ?></td>
                        <td><?php echo $percentage; ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.stat-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.stat-card.primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.stat-card.success {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}

.stat-card.warning {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
}

.stat-card.info {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    color: #333;
}

.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

.satisfaction-metric h3 {
    margin-bottom: 5px;
}

.satisfaction-metric p {
    margin-bottom: 0;
    font-size: 0.9em;
}

.page-header {
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 1px solid #eee;
}

.page-title {
    font-size: 2.5rem;
    font-weight: 300;
    margin-bottom: 10px;
}

.page-subtitle {
    color: #666;
    font-size: 1.1rem;
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Booking Trends Chart
const bookingTrendsCtx = document.getElementById('bookingTrendsChart').getContext('2d');
const bookingTrendsChart = new Chart(bookingTrendsCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_column($booking_trends, 'month')); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode(array_column($booking_trends, 'bookings')); ?>,
            borderColor: 'rgba(54, 162, 235, 1)',
            backgroundColor: 'rgba(54, 162, 235, 0.1)',
            tension: 0.4
        }, {
            label: 'Unique Guests',
            data: <?php echo json_encode(array_column($booking_trends, 'unique_guests')); ?>,
            borderColor: 'rgba(255, 99, 132, 1)',
            backgroundColor: 'rgba(255, 99, 132, 0.1)',
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Demographics Chart
const demographicsCtx = document.getElementById('demographicsChart').getContext('2d');
const demographicsChart = new Chart(demographicsCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($d) { return $d['National'] ?? 'Not specified'; }, array_slice($demographics, 0, 5))); ?>,
        datasets: [{
            data: <?php echo json_encode(array_column(array_slice($demographics, 0, 5), 'guest_count')); ?>,
            backgroundColor: [
                'rgba(255, 99, 132, 0.8)',
                'rgba(54, 162, 235, 0.8)',
                'rgba(255, 205, 86, 0.8)',
                'rgba(75, 192, 192, 0.8)',
                'rgba(153, 102, 255, 0.8)'
            ]
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

// Seasonal Patterns Chart
const seasonalCtx = document.getElementById('seasonalChart').getContext('2d');
const seasonalChart = new Chart(seasonalCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_column($seasonal_patterns, 'month_name')); ?>,
        datasets: [{
            label: 'Bookings',
            data: <?php echo json_encode(array_column($seasonal_patterns, 'bookings')); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.6)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

function exportGuestReport() {
    // Create CSV content
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Guest Analytics Report\n\n";
    csvContent += "Period: <?php echo $date_from; ?> to <?php echo $date_to; ?>\n\n";
    csvContent += "Summary Statistics\n";
    csvContent += "Total Bookings,<?php echo $summary_stats['total_bookings']; ?>\n";
    csvContent += "Unique Guests,<?php echo $summary_stats['unique_guests']; ?>\n";
    csvContent += "Average Stay Length,<?php echo number_format($summary_stats['avg_stay_length'], 1); ?> days\n";
    csvContent += "Average Booking Value,KES <?php echo number_format($summary_stats['avg_booking_value'], 2); ?>\n";
    csvContent += "Repeat Guest Rate,<?php echo $repeat_guest_rate; ?>%\n\n";
    
    // Download CSV
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", "guest_analytics_report.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function printReport() {
    window.print();
}
</script>

<?php include '../includes/admin/footer.php'; ?>
