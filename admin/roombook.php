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

// Check for booking ID BEFORE including header
if(!isset($_GET["rid"])) {
    header("location:home.php");
    exit();
}

// Get booking details and validate BEFORE including header
$id = $_GET['rid'];
$sql = "SELECT * FROM roombook WHERE id = ?";
$stmt = $con->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0) {
    header("location:home.php");
    exit();
}

$booking = $result->fetch_assoc();

$page_title = 'Booking Details';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Booking Details</h1>
</div>

<?php
// Display session alerts
display_session_alerts();

// Header already included at the top
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">Booking Details</h1>
    <p class="page-subtitle">View and manage booking information</p>
            </div>

<!-- Booking Information -->
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-check"></i> Guest Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Guest Name:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Title'] . ' ' . $booking['FName'] . ' ' . $booking['LName']); ?></td>
                                        </tr>
                                        <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Email']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Phone']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Nationality:</strong></td>
                                <td><?php echo htmlspecialchars($booking['National']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Country:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Country']); ?></td>
                                        </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless">
										<tr>
                                <td><strong>Room Type:</strong></td>
                                <td><?php echo htmlspecialchars($booking['TRoom']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Bedding:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Bed']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Number of Rooms:</strong></td>
                                <td><?php echo htmlspecialchars($booking['NRoom']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Meal Plan:</strong></td>
                                <td><?php echo htmlspecialchars($booking['Meal']); ?></td>
                                        </tr>
										<tr>
                                <td><strong>Number of Days:</strong></td>
                                <td><?php echo htmlspecialchars($booking['nodays']); ?></td>
                                        </tr>
                                </table>
                            </div>
										 </div>
                        </div>
                    </div>
					</div>
					
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar-alt"></i> Booking Details</h5>
                        </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <td><strong>Check-in:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($booking['cin'])); ?></td>
							</tr>
							<tr>
                        <td><strong>Check-out:</strong></td>
                        <td><?php echo date('M d, Y', strtotime($booking['cout'])); ?></td>
							</tr>
							<tr>
                        <td><strong>Status:</strong></td>
                        <td>
                            <?php 
                            $status_class = $booking['stat'] == 'Conform' ? 'badge-success' : 'badge-warning';
                            echo "<span class='badge $status_class'>" . htmlspecialchars($booking['stat']) . "</span>";
                            ?>
                        </td>
							</tr>
							<tr>
                        <td><strong>Booking Ref:</strong></td>
                        <td><?php echo htmlspecialchars($booking['booking_ref']); ?></td>
							</tr>
							<tr>
                        <td><strong>Created:</strong></td>
                        <td><?php echo date('M d, Y H:i', strtotime($booking['created_at'])); ?></td>
							</tr>
						</table>
						
                <hr>
                
                <div class="text-center">
                    <a href="roombook.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Bookings
                    </a>
                    <a href="edit_booking_form.php?id=<?php echo $booking['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Booking
                    </a>
						</div>
                        </div>
                    </div>
					</div>
                </div>

<?php include '../includes/admin/footer.php'; ?>