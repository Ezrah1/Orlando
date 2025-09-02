<?php
$page_title = 'Rooms Department';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Rooms Department</h1>
    <p class="page-subtitle">Manage hotel rooms and room assignments</p>
</div>

<?php
// Display session alerts
if (function_exists('display_session_alerts')) {
    display_session_alerts();
}

include 'db.php';

// Get room statistics
$total_rooms_result = mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms WHERE is_active = 1");
$total_rooms = mysqli_fetch_assoc($total_rooms_result)['count'] ?? 0;

$occupied_rooms_result = mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE stat = 'confirm'");
$occupied_rooms = mysqli_fetch_assoc($occupied_rooms_result)['count'] ?? 0;

$available_rooms = $total_rooms - $occupied_rooms;
$occupancy_rate = $total_rooms > 0 ? ($occupied_rooms / $total_rooms) * 100 : 0;

// Get rooms with details
$rooms_query = "SELECT nr.*, 
                       CASE 
                           WHEN nr.base_price <= 5000 THEN 'Standard'
                           WHEN nr.base_price <= 10000 THEN 'Deluxe'
                           ELSE 'Suite'
                       END as room_type_name,
                       nr.base_price as price
                FROM named_rooms nr 
                WHERE nr.is_active = 1 
                ORDER BY nr.room_name";
$rooms_result = mysqli_query($con, $rooms_query);
?>

<div class="row">
    <!-- Statistics Cards -->
    <div class="col-md-3">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $total_rooms; ?></h3>
                        <span>Total Rooms</span>
                    </div>
                    <i class="fas fa-bed fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $available_rooms; ?></h3>
                        <span>Available</span>
                    </div>
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo $occupied_rooms; ?></h3>
                        <span>Occupied</span>
                    </div>
                    <i class="fas fa-user fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h3 class="mb-0"><?php echo number_format($occupancy_rate, 1); ?>%</h3>
                        <span>Occupancy Rate</span>
                    </div>
                    <i class="fas fa-chart-pie fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Rooms</h5>
                <a href="room.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Manage Rooms
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Room Type</th>
                                <th>Price/Night</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($rooms_result) > 0): ?>
                                <?php while($room = mysqli_fetch_assoc($rooms_result)): ?>
                                    <?php
                                    // Check if room is occupied
                                    $occupied_check = mysqli_query($con, "SELECT id FROM roombook WHERE TRoom = '{$room['room_name']}' AND stat = 'Confirm' LIMIT 1");
                                    $is_occupied = mysqli_num_rows($occupied_check) > 0;
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($room['room_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($room['room_type_name'] ?? 'Standard'); ?></td>
                                        <td>KES <?php echo number_format($room['price'] ?? 0, 2); ?></td>
                                        <td>
                                            <?php if ($is_occupied): ?>
                                                <span class="badge bg-warning">Occupied</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="room.php?edit=<?php echo $room['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <?php if (!$is_occupied): ?>
                                                <a href="staff_booking.php?room=<?php echo $room['room_name']; ?>" class="btn btn-sm btn-outline-success">
                                                    <i class="fas fa-calendar-plus"></i> Book
                                                </a>
                                            <?php else: ?>
                                                                                            <a href="booking.php?room=<?php echo $room['room_name']; ?>" class="btn btn-sm btn-outline-info">
                                                <i class="fas fa-eye"></i> View Booking
                                            </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No rooms found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Quick Actions</h6>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <a href="room.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-bed text-primary"></i> Manage Rooms
                    </a>
                    <a href="staff_booking.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-calendar-plus text-success"></i> New Booking
                    </a>
                    <a href="booking.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-list text-info"></i> All Bookings
                    </a>
                    <a href="housekeeping.php" class="list-group-item list-group-item-action">
                        <i class="fas fa-broom text-warning"></i> Housekeeping
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0">Recent Activity</h6>
            </div>
            <div class="card-body">
                <?php
                // Get recent room activities
                $recent_query = "SELECT rb.*, nr.room_name 
                               FROM roombook rb 
                               LEFT JOIN named_rooms nr ON rb.TRoom = nr.room_name 
                               ORDER BY rb.created_at DESC 
                               LIMIT 5";
                $recent_result = mysqli_query($con, $recent_query);
                ?>
                
                <?php if (mysqli_num_rows($recent_result) > 0): ?>
                    <div class="list-group list-group-flush">
                        <?php while($activity = mysqli_fetch_assoc($recent_result)): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">Room <?php echo htmlspecialchars($activity['room_name']); ?></h6>
                                    <small><?php echo date('M d, Y', strtotime($activity['created_at'] ?? 'now')); ?></small>
                                </div>
                                <p class="mb-1">Guest: <?php echo htmlspecialchars($activity['FName'] . ' ' . $activity['LName']); ?></p>
                                <small>Status: <?php echo htmlspecialchars($activity['stat']); ?></small>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No recent activity</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

        </div>
    </main>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>