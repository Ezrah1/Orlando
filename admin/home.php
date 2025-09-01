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

$page_title = 'Executive Dashboard';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';

// Get real-time statistics
$stats = [];

// Room occupancy statistics
$room_stats = mysqli_query($con, "SELECT 
    COUNT(nr.room_name) as total_rooms,
    COUNT(CASE WHEN rs.current_status = 'occupied' THEN 1 END) as occupied_rooms,
    COUNT(CASE WHEN rs.current_status = 'available' THEN 1 END) as available_rooms,
    COUNT(CASE WHEN rs.current_status = 'maintenance' THEN 1 END) as maintenance_rooms
    FROM named_rooms nr 
    LEFT JOIN room_status rs ON nr.room_name = rs.room_name 
    WHERE nr.is_active = 1");
$room_data = mysqli_fetch_assoc($room_stats);

// Booking statistics for today
$today_bookings = mysqli_query($con, "SELECT 
    COUNT(*) as total_bookings,
    COUNT(CASE WHEN stat = 'Confirm' THEN 1 END) as confirmed_bookings,
    COUNT(CASE WHEN stat = 'Not Confirm' THEN 1 END) as pending_bookings
    FROM roombook WHERE DATE(created_at) = CURDATE()");
$booking_data = mysqli_fetch_assoc($today_bookings);

// Get today's revenue from payments
$today_revenue = mysqli_query($con, "SELECT SUM(ttot) as confirmed_revenue FROM payment WHERE DATE(cin) = CURDATE()");
$revenue_temp = mysqli_fetch_assoc($today_revenue);
$booking_data['confirmed_revenue'] = $revenue_temp['confirmed_revenue'] ?? 0;

// Monthly revenue
$monthly_revenue = mysqli_query($con, "SELECT 
    SUM(ttot) as monthly_revenue,
    COUNT(*) as monthly_bookings
    FROM payment WHERE MONTH(cin) = MONTH(CURDATE()) AND YEAR(cin) = YEAR(CURDATE())");
$revenue_data = mysqli_fetch_assoc($monthly_revenue);

// Recent activities - Join roombook with payment to get total amounts
$recent_bookings = mysqli_query($con, "SELECT 
    rb.id,
    CONCAT(rb.Title, ' ', rb.FName, ' ', rb.LName) as guest_name,
    rb.TRoom as room_type,
    rb.stat as booking_status,
    rb.created_at,
    COALESCE(p.ttot, 0) as total_amount
    FROM roombook rb 
    LEFT JOIN payment p ON rb.id = p.id 
    ORDER BY rb.created_at DESC 
    LIMIT 5");
$recent_users = mysqli_query($con, "SELECT username, created_at FROM users WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");

// Calculate occupancy rate
$occupancy_rate = $room_data['total_rooms'] > 0 ? round(($room_data['occupied_rooms'] / $room_data['total_rooms']) * 100, 1) : 0;
?>

<style>
.dashboard-container {
    background: #f8fafc;
    min-height: calc(100vh - 100px);
    padding: 0;
}

.dashboard-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 30px 0;
    margin: -20px -20px 30px -20px;
    position: relative;
    overflow: hidden;
}

.dashboard-header::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 20"><defs><pattern id="grain" width="100" height="20" patternUnits="userSpaceOnUse"><circle cx="10" cy="10" r="1" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="20" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.dashboard-title {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 10px;
    position: relative;
    z-index: 2;
}

.dashboard-subtitle {
    font-size: 1.1rem;
    opacity: 0.9;
    position: relative;
    z-index: 2;
}

.stat-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
    height: 140px;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.stat-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--accent-color, #667eea);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: white;
    margin-bottom: 15px;
}

.stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: #2d3748;
    line-height: 1;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 0.9rem;
    color: #718096;
    font-weight: 500;
}

.stat-change {
    font-size: 0.8rem;
    font-weight: 600;
    margin-top: 8px;
}

.stat-change.positive {
    color: #38a169;
}

.stat-change.negative {
    color: #e53e3e;
}

.chart-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    margin-bottom: 25px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #e2e8f0;
}

.chart-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2d3748;
}

.activity-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border: 1px solid rgba(0, 0, 0, 0.05);
    height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 15px 0;
    border-bottom: 1px solid #f7fafc;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 15px;
    font-size: 16px;
    color: white;
}

.activity-content {
    flex: 1;
}

.activity-title {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 2px;
}

.activity-meta {
    font-size: 0.85rem;
    color: #718096;
}

.progress-bar {
    width: 100%;
    height: 8px;
    background: #e2e8f0;
    border-radius: 4px;
    overflow: hidden;
    margin-top: 10px;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #667eea, #764ba2);
    border-radius: 4px;
    transition: width 0.8s ease;
}

.quick-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
    margin-top: 20px;
}

.quick-action-btn {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 20px;
    border-radius: 8px;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: all 0.3s ease;
}

.quick-action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
    text-decoration: none;
}

@media (max-width: 768px) {
    .dashboard-title {
        font-size: 2rem;
    }
    
    .stat-card {
        margin-bottom: 20px;
    }
}
</style>

<div class="dashboard-container">
    <!-- Dashboard Header -->
    <div class="dashboard-header">
        <div class="container">
            <div class="row">
                <div class="col-md-8">
                    <h1 class="dashboard-title">
                        <i class="fas fa-tachometer-alt me-3"></i>
                        Executive Dashboard
                    </h1>
                    <p class="dashboard-subtitle">
                        Welcome back! Here's what's happening at Orlando International Resorts today.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white">
                        <div style="font-size: 1.5rem; font-weight: 600;">
                            <?php echo date('M d, Y'); ?>
                        </div>
                        <div style="opacity: 0.9;">
                            <?php echo date('l, g:i A'); ?>
                        </div>
            </div>
                </div>
            </div>
        </div>
            </div>

    <div class="container">
        <!-- Key Performance Indicators -->
        <div class="row mb-4">
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stat-card" style="--accent-color: #667eea;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                        <i class="fas fa-bed"></i>
                    </div>
                    <div class="stat-value"><?php echo $occupancy_rate; ?>%</div>
                    <div class="stat-label">Room Occupancy</div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $occupancy_rate; ?>%;"></div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stat-card" style="--accent-color: #38a169;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #38a169, #48bb78);">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                    <div class="stat-value"><?php echo $booking_data['total_bookings'] ?: 0; ?></div>
                    <div class="stat-label">Today's Bookings</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <?php echo $booking_data['confirmed_bookings'] ?: 0; ?> confirmed
            </div>
                    </div>
                </div>
    
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stat-card" style="--accent-color: #ed8936;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #ed8936, #f6ad55);">
                        <i class="fas fa-dollar-sign"></i>
                </div>
                    <div class="stat-value">$<?php echo number_format($revenue_data['monthly_revenue'] ?: 0); ?></div>
                    <div class="stat-label">Monthly Revenue</div>
                    <div class="stat-change positive">
                        <i class="fas fa-arrow-up"></i> <?php echo $revenue_data['monthly_bookings'] ?: 0; ?> bookings
            </div>
        </div>
    </div>
    
            <div class="col-xl-3 col-lg-6 col-md-6 mb-4">
                <div class="stat-card" style="--accent-color: #9f7aea;">
                    <div class="stat-icon" style="background: linear-gradient(135deg, #9f7aea, #b794f6);">
                        <i class="fas fa-users"></i>
                        </div>
                    <div class="stat-value"><?php echo $room_data['available_rooms'] ?: 0; ?></div>
                    <div class="stat-label">Available Rooms</div>
                    <div class="stat-change">
                        <i class="fas fa-tools"></i> <?php echo $room_data['maintenance_rooms'] ?: 0; ?> in maintenance
            </div>
        </div>
    </div>
                                    </div>

        <!-- Charts and Analytics -->
        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-line me-2"></i>
                            Revenue Trends
                        </h3>
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-secondary btn-sm active">7 Days</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm">30 Days</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm">90 Days</button>
                        </div>
                    </div>
                    <div style="position: relative; height: 300px;">
                        <canvas id="revenueChart"></canvas>
                    </div>
                </div>
            </div>	
                
            <div class="col-xl-4 mb-4">
                <div class="chart-card">
                    <div class="chart-header">
                        <h3 class="chart-title">
                            <i class="fas fa-chart-pie me-2"></i>
                            Room Status
                        </h3>
                    </div>
                    <div style="position: relative; height: 280px;">
                        <canvas id="roomStatusChart"></canvas>
                    </div>
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span><span class="badge" style="background: #667eea;">●</span> Occupied</span>
                            <strong><?php echo $room_data['occupied_rooms']; ?></strong>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span><span class="badge" style="background: #38a169;">●</span> Available</span>
                            <strong><?php echo $room_data['available_rooms']; ?></strong>
                            </div>
                        <div class="d-flex justify-content-between">
                            <span><span class="badge" style="background: #ed8936;">●</span> Maintenance</span>
                            <strong><?php echo $room_data['maintenance_rooms']; ?></strong>
                        </div>
                    </div>
                                        </div>
            </div>
        </div>

        <!-- Recent Activity and Quick Actions -->
        <div class="row">
            <div class="col-xl-8 mb-4">
                <div class="activity-card">
                    <h3 class="chart-title mb-4">
                        <i class="fas fa-clock me-2"></i>
                        Recent Activity
                    </h3>
                    
                    <?php if ($recent_bookings && mysqli_num_rows($recent_bookings) > 0): ?>
                        <?php while($booking = mysqli_fetch_assoc($recent_bookings)): ?>
                            <div class="activity-item">
                                <div class="activity-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                                    <i class="fas fa-calendar-plus"></i>
                                </div>
                                <div class="activity-content">
                                    <div class="activity-title">New Booking: <?php echo htmlspecialchars($booking['guest_name']); ?></div>
                                    <div class="activity-meta">
                                        Room Type: <?php echo htmlspecialchars($booking['room_type']); ?> | 
                                        Amount: $<?php echo number_format($booking['total_amount'] ?: 0); ?> |
                                        <?php echo date('M d, Y g:i A', strtotime($booking['created_at'])); ?>
                                    </div>
                                </div>
                                <span class="badge bg-<?php echo $booking['booking_status'] == 'Confirm' ? 'success' : 'warning'; ?>">
                                    <?php echo htmlspecialchars($booking['booking_status']); ?>
                                </span>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No recent bookings to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="col-xl-4 mb-4">
                <div class="activity-card">
                    <h3 class="chart-title mb-4">
                        <i class="fas fa-bolt me-2"></i>
                        Quick Actions
                    </h3>
                    
                    <div class="d-grid gap-3">
                        <a href="staff_booking.php" class="quick-action-btn">
                            <i class="fas fa-plus-circle"></i>
                            New Booking
                        </a>
                        <a href="room.php" class="quick-action-btn" style="background: linear-gradient(135deg, #38a169, #48bb78);">
                            <i class="fas fa-bed"></i>
                            Manage Rooms
                        </a>
                        <a href="reservation.php" class="quick-action-btn" style="background: linear-gradient(135deg, #ed8936, #f6ad55);">
                            <i class="fas fa-calendar-alt"></i>
                            View Reservations
                        </a>
                        <a href="financial_reports.php" class="quick-action-btn" style="background: linear-gradient(135deg, #9f7aea, #b794f6);">
                            <i class="fas fa-chart-bar"></i>
                            Financial Reports
                        </a>
                        <a href="user_management.php" class="quick-action-btn" style="background: linear-gradient(135deg, #e53e3e, #fc8181);">
                            <i class="fas fa-users-cog"></i>
                            User Management
                        </a>
                    </div>

                    <div class="mt-4 p-3" style="background: #f7fafc; border-radius: 8px;">
                        <h6 class="text-muted mb-2">System Status</h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Server Status</span>
                            <span class="badge bg-success">Online</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Database</span>
                            <span class="badge bg-success">Connected</span>
            </div>
                        <div class="d-flex justify-content-between">
                            <span>Last Backup</span>
                            <span class="text-muted">2 hours ago</span>
                        </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
    </div>

<!-- Chart.js for analytics -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        datasets: [{
            label: 'Revenue ($)',
            data: [1200, 1900, 3000, 5000, 2000, 3000, 4500],
            borderColor: '#667eea',
            backgroundColor: 'rgba(102, 126, 234, 0.1)',
            tension: 0.4,
            fill: true,
            borderWidth: 3,
            pointBackgroundColor: '#667eea',
            pointBorderColor: '#ffffff',
            pointBorderWidth: 2,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: {
            intersect: false,
            mode: 'index'
        },
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#667eea',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        return 'Revenue: $' + context.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    color: '#718096',
                    font: {
                        size: 12
                    },
                    callback: function(value) {
                        return '$' + value.toLocaleString();
                    }
                }
            },
            x: {
                grid: {
                    color: '#f1f5f9',
                    drawBorder: false
                },
                ticks: {
                    color: '#718096',
                    font: {
                        size: 12
                    }
                }
            }
        }
    }
});

// Room Status Chart
const roomCtx = document.getElementById('roomStatusChart').getContext('2d');
const roomChart = new Chart(roomCtx, {
    type: 'doughnut',
    data: {
        labels: ['Occupied', 'Available', 'Maintenance'],
        datasets: [{
            data: [<?php echo $room_data['occupied_rooms']; ?>, <?php echo $room_data['available_rooms']; ?>, <?php echo $room_data['maintenance_rooms']; ?>],
            backgroundColor: ['#667eea', '#38a169', '#ed8936'],
            borderWidth: 3,
            borderColor: '#ffffff',
            hoverBorderWidth: 4,
            hoverBorderColor: '#ffffff'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: 'rgba(0, 0, 0, 0.8)',
                titleColor: '#ffffff',
                bodyColor: '#ffffff',
                borderColor: '#667eea',
                borderWidth: 1,
                callbacks: {
                    label: function(context) {
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        const percentage = total > 0 ? Math.round((context.parsed / total) * 100) : 0;
                        return context.label + ': ' + context.parsed + ' rooms (' + percentage + '%)';
                    }
                }
            }
        },
        cutout: '65%',
        elements: {
            arc: {
                borderWidth: 0
            }
        }
    }
});

// Auto-refresh data every 5 minutes
setInterval(function() {
    location.reload();
}, 300000);
</script>

<?php include '../includes/admin/footer.php'; ?>