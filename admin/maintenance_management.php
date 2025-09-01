<?php
$page_title = 'Maintenance Management';
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<?php
// Display session alerts
display_session_alerts();
?>

<?php
include '../db.php';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_request':
                $category_id = (int)$_POST['category_id'];
                $title = mysqli_real_escape_string($con, $_POST['title']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $location = mysqli_real_escape_string($con, $_POST['location']);
                $room_number = mysqli_real_escape_string($con, $_POST['room_number']);
                $priority = mysqli_real_escape_string($con, $_POST['priority']);
                $estimated_cost = (float)$_POST['estimated_cost'];
                
                $request_number = 'MR-' . date('Y') . '-' . str_pad(rand(1, 999), 3, '0', STR_PAD_LEFT);
                
                $sql = "INSERT INTO maintenance_requests (request_number, category_id, title, description, location, room_number, priority, estimated_cost, reported_by) 
                        VALUES ('$request_number', $category_id, '$title', '$description', '$location', '$room_number', '$priority', $estimated_cost, {$_SESSION['user_id']})";
                mysqli_query($con, $sql);
                $_SESSION['success_message'] = "Maintenance request created successfully!";
                break;
                
            case 'update_request_status':
                $request_id = (int)$_POST['request_id'];
                $status = mysqli_real_escape_string($con, $_POST['status']);
                $actual_cost = (float)$_POST['actual_cost'];
                $resolution_notes = mysqli_real_escape_string($con, $_POST['resolution_notes']);
                
                $sql = "UPDATE maintenance_requests SET status = '$status', actual_cost = $actual_cost, resolution_notes = '$resolution_notes'";
                if ($status == 'in_progress') {
                    $sql .= ", started_at = NOW()";
                } elseif ($status == 'completed') {
                    $sql .= ", completed_at = NOW()";
                }
                $sql .= " WHERE id = $request_id";
                mysqli_query($con, $sql);
                $_SESSION['success_message'] = "Request status updated successfully!";
                break;
                
            case 'add_part':
                $name = mysqli_real_escape_string($con, $_POST['name']);
                $description = mysqli_real_escape_string($con, $_POST['description']);
                $part_number = mysqli_real_escape_string($con, $_POST['part_number']);
                $supplier = mysqli_real_escape_string($con, $_POST['supplier']);
                $unit_cost = (float)$_POST['unit_cost'];
                $current_stock = (int)$_POST['current_stock'];
                $minimum_stock = (int)$_POST['minimum_stock'];
                $location = mysqli_real_escape_string($con, $_POST['location']);
                
                $sql = "INSERT INTO maintenance_parts (name, description, part_number, supplier, unit_cost, current_stock, minimum_stock, location) 
                        VALUES ('$name', '$description', '$part_number', '$supplier', $unit_cost, $current_stock, $minimum_stock, '$location')";
                mysqli_query($con, $sql);
                $_SESSION['success_message'] = "Part added successfully!";
                break;
        }
        header("Location: maintenance_management.php");
        exit();
    }
}

// Get maintenance requests summary
$requests_summary_sql = "SELECT status, COUNT(*) as count FROM maintenance_requests GROUP BY status";
$requests_summary_result = mysqli_query($con, $requests_summary_sql);
$requests_summary = [];
while ($row = mysqli_fetch_assoc($requests_summary_result)) {
    $requests_summary[$row['status']] = $row['count'];
}

// Get open maintenance requests
$open_requests_sql = "SELECT mr.*, mc.name as category_name, u.username as reported_by_name
                      FROM maintenance_requests mr
                      LEFT JOIN maintenance_categories mc ON mr.category_id = mc.id
                      LEFT JOIN users u ON mr.reported_by = u.id
                      WHERE mr.status IN ('open', 'assigned', 'in_progress')
                      ORDER BY mr.priority DESC, mr.reported_at ASC
                      LIMIT 20";
$open_requests_result = mysqli_query($con, $open_requests_sql);

// Get low stock parts
$low_stock_sql = "SELECT * FROM maintenance_parts WHERE current_stock <= minimum_stock ORDER BY (minimum_stock - current_stock) DESC LIMIT 10";
$low_stock_result = mysqli_query($con, $low_stock_sql);

// Get maintenance categories
$categories_sql = "SELECT * FROM maintenance_categories WHERE is_active = 1 ORDER BY display_order";
$categories_result = mysqli_query($con, $categories_sql);
?>

<div class="container-fluid">
    <div class="row g-0">
        <?php include '../includes/admin/sidebar.php'; ?>

        <div class="admin-main-content">
            <div class="content-wrapper p-4">
                
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-tools text-primary"></i> Maintenance Management</h2>
                        <p class="text-muted mb-0">Manage maintenance requests, inventory, and analytics</p>
                    </div>
                    <div>
                        <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#createRequestModal">
                            <i class="fas fa-plus"></i> New Request
                        </button>
                        <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addPartModal">
                            <i class="fas fa-cube"></i> Add Part
                        </button>
                    </div>
                </div>

                <!-- Status Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-2">
                        <div class="card border-left-danger">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-exclamation-triangle fa-2x text-danger"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-danger"><?php echo $requests_summary['open'] ?? 0; ?></div>
                                        <small class="text-muted">Open Requests</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-left-warning">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-user-cog fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-warning"><?php echo $requests_summary['assigned'] ?? 0; ?></div>
                                        <small class="text-muted">Assigned</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-left-info">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-spinner fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-info"><?php echo $requests_summary['in_progress'] ?? 0; ?></div>
                                        <small class="text-muted">In Progress</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-left-success">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-success"><?php echo $requests_summary['completed'] ?? 0; ?></div>
                                        <small class="text-muted">Completed</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-left-primary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-certificate fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-primary"><?php echo $requests_summary['verified'] ?? 0; ?></div>
                                        <small class="text-muted">Verified</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card border-left-secondary">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-archive fa-2x text-secondary"></i>
                                    </div>
                                    <div>
                                        <div class="h4 mb-0 text-secondary"><?php echo $requests_summary['closed'] ?? 0; ?></div>
                                        <small class="text-muted">Closed</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content Tabs -->
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="maintenanceTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">
                                    <i class="fas fa-clipboard-list"></i> Active Requests
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="inventory-tab" data-bs-toggle="tab" data-bs-target="#inventory" type="button" role="tab">
                                    <i class="fas fa-boxes"></i> Parts Inventory
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="analytics-tab" data-bs-toggle="tab" data-bs-target="#analytics" type="button" role="tab">
                                    <i class="fas fa-chart-bar"></i> Analytics
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="maintenanceTabsContent">
                            <!-- Active Requests Tab -->
                            <div class="tab-pane fade show active" id="requests" role="tabpanel">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Request #</th>
                                                <th>Title</th>
                                                <th>Location</th>
                                                <th>Category</th>
                                                <th>Priority</th>
                                                <th>Status</th>
                                                <th>Reported By</th>
                                                <th>Est. Cost</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($request = mysqli_fetch_assoc($open_requests_result)): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['request_number']); ?></strong>
                                                </td>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($request['title']); ?></strong>
                                                    <?php if ($request['room_number']): ?>
                                                        <br><small class="text-muted">Room <?php echo htmlspecialchars($request['room_number']); ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($request['location']); ?></td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($request['category_name'] ?? 'N/A'); ?></span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $priority_class = '';
                                                    switch($request['priority']) {
                                                        case 'urgent':
                                                        case 'critical':
                                                            $priority_class = 'bg-danger';
                                                            break;
                                                        case 'high':
                                                            $priority_class = 'bg-warning';
                                                            break;
                                                        case 'normal':
                                                            $priority_class = 'bg-info';
                                                            break;
                                                        default:
                                                            $priority_class = 'bg-success';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $priority_class; ?>">
                                                        <?php echo ucfirst($request['priority']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $status_class = '';
                                                    switch($request['status']) {
                                                        case 'open':
                                                            $status_class = 'bg-danger';
                                                            break;
                                                        case 'assigned':
                                                            $status_class = 'bg-warning';
                                                            break;
                                                        case 'in_progress':
                                                            $status_class = 'bg-info';
                                                            break;
                                                        case 'completed':
                                                            $status_class = 'bg-success';
                                                            break;
                                                        default:
                                                            $status_class = 'bg-secondary';
                                                    }
                                                    ?>
                                                    <span class="badge <?php echo $status_class; ?>">
                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($request['reported_by_name'] ?? 'N/A'); ?></small>
                                                    <br><small class="text-muted"><?php echo date('M j, Y', strtotime($request['reported_at'])); ?></small>
                                                </td>
                                                <td>
                                                    <strong>KES <?php echo number_format($request['estimated_cost'], 0); ?></strong>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="btn btn-primary btn-sm" 
                                                                onclick="updateRequestStatus(<?php echo $request['id']; ?>, '<?php echo $request['status']; ?>')"
                                                                title="Update Status">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-info btn-sm" 
                                                                onclick="viewRequestDetails(<?php echo $request['id']; ?>)"
                                                                title="View Details">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Parts Inventory Tab -->
                            <div class="tab-pane fade" id="inventory" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <div class="alert alert-warning">
                                            <strong>Low Stock Items:</strong> 
                                            <?php echo mysqli_num_rows($low_stock_result); ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-info">
                                            <strong>Total Parts:</strong> 
                                            <?php 
                                            $total_parts_sql = "SELECT COUNT(*) as total FROM maintenance_parts";
                                            $total_parts_result = mysqli_query($con, $total_parts_sql);
                                            $total_parts = mysqli_fetch_assoc($total_parts_result)['total'];
                                            echo $total_parts;
                                            ?>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="alert alert-success">
                                            <strong>Inventory Value:</strong> KES 
                                            <?php 
                                            $inventory_value_sql = "SELECT SUM(current_stock * unit_cost) as total_value FROM maintenance_parts";
                                            $inventory_value_result = mysqli_query($con, $inventory_value_sql);
                                            $inventory_value = mysqli_fetch_assoc($inventory_value_result)['total_value'] ?? 0;
                                            echo number_format($inventory_value, 0);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Part Name</th>
                                                <th>Part Number</th>
                                                <th>Supplier</th>
                                                <th>Current Stock</th>
                                                <th>Minimum</th>
                                                <th>Unit Cost</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php mysqli_data_seek($low_stock_result, 0); ?>
                                            <?php while ($part = mysqli_fetch_assoc($low_stock_result)): ?>
                                            <tr class="<?php echo $part['current_stock'] == 0 ? 'table-danger' : 'table-warning'; ?>">
                                                <td><strong><?php echo htmlspecialchars($part['name']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($part['part_number']); ?></td>
                                                <td><?php echo htmlspecialchars($part['supplier']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $part['current_stock'] == 0 ? 'danger' : 'warning'; ?>">
                                                        <?php echo $part['current_stock']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $part['minimum_stock']; ?></td>
                                                <td>KES <?php echo number_format($part['unit_cost'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($part['location']); ?></td>
                                                <td>
                                                    <?php if ($part['current_stock'] == 0): ?>
                                                        <span class="badge bg-danger">Out of Stock</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">Low Stock</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Analytics Tab -->
                            <div class="tab-pane fade" id="analytics" role="tabpanel">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Top Maintenance Categories</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $category_stats_sql = "SELECT mc.name, COUNT(mr.id) as request_count, 
                                                                       AVG(mr.actual_cost) as avg_cost
                                                                       FROM maintenance_categories mc
                                                                       LEFT JOIN maintenance_requests mr ON mc.id = mr.category_id
                                                                       WHERE mc.is_active = 1
                                                                       GROUP BY mc.id, mc.name
                                                                       ORDER BY request_count DESC
                                                                       LIMIT 5";
                                                $category_stats_result = mysqli_query($con, $category_stats_sql);
                                                ?>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Category</th>
                                                                <th>Requests</th>
                                                                <th>Avg Cost</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php while ($category = mysqli_fetch_assoc($category_stats_result)): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                                <td><span class="badge bg-primary"><?php echo $category['request_count']; ?></span></td>
                                                                <td>KES <?php echo number_format($category['avg_cost'] ?? 0, 0); ?></td>
                                                            </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card mb-3">
                                            <div class="card-header">
                                                <h6 class="mb-0">Cost Summary</h6>
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                $cost_summary_sql = "SELECT 
                                                                     SUM(CASE WHEN status = 'completed' THEN actual_cost ELSE 0 END) as completed_cost,
                                                                     SUM(CASE WHEN status IN ('open', 'assigned', 'in_progress') THEN estimated_cost ELSE 0 END) as pending_cost,
                                                                     COUNT(CASE WHEN status = 'completed' AND MONTH(completed_at) = MONTH(CURRENT_DATE()) THEN 1 END) as completed_this_month
                                                                     FROM maintenance_requests";
                                                $cost_summary_result = mysqli_query($con, $cost_summary_sql);
                                                $cost_summary = mysqli_fetch_assoc($cost_summary_result);
                                                ?>
                                                <div class="row text-center">
                                                    <div class="col-4">
                                                        <h4 class="text-success">KES <?php echo number_format($cost_summary['completed_cost'], 0); ?></h4>
                                                        <small class="text-muted">Completed Work</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <h4 class="text-warning">KES <?php echo number_format($cost_summary['pending_cost'], 0); ?></h4>
                                                        <small class="text-muted">Pending Cost</small>
                                                    </div>
                                                    <div class="col-4">
                                                        <h4 class="text-info"><?php echo $cost_summary['completed_this_month']; ?></h4>
                                                        <small class="text-muted">This Month</small>
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

            </div> <!-- End content-wrapper -->
        </div> <!-- End admin-main-content -->
    </div>
</div>

<!-- Create Maintenance Request Modal -->
<div class="modal fade" id="createRequestModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create Maintenance Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_request">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select class="form-select" name="category_id" required>
                                    <?php mysqli_data_seek($categories_result, 0); ?>
                                    <?php while ($category = mysqli_fetch_assoc($categories_result)): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Priority</label>
                                <select class="form-select" name="priority" required>
                                    <option value="low">Low</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4" required></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" class="form-control" name="location" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Room Number (Optional)</label>
                                <input type="text" class="form-control" name="room_number">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Estimated Cost (KES)</label>
                        <input type="number" class="form-control" name="estimated_cost" value="0" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Request</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Part Modal -->
<div class="modal fade" id="addPartModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Part</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_part">
                    
                    <div class="mb-3">
                        <label class="form-label">Part Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Part Number</label>
                                <input type="text" class="form-control" name="part_number">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Supplier</label>
                                <input type="text" class="form-control" name="supplier">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Unit Cost (KES)</label>
                                <input type="number" class="form-control" name="unit_cost" value="0" step="0.01" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Current Stock</label>
                                <input type="number" class="form-control" name="current_stock" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label class="form-label">Minimum Stock</label>
                                <input type="number" class="form-control" name="minimum_stock" value="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Storage Location</label>
                        <input type="text" class="form-control" name="location">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Add Part</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Update Request Status Modal -->
<div class="modal fade" id="updateRequestStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Request Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_request_status">
                    <input type="hidden" name="request_id" id="updateRequestId">
                    
                    <div class="mb-3">
                        <label class="form-label">New Status</label>
                        <select class="form-select" name="status" required>
                            <option value="open">Open</option>
                            <option value="assigned">Assigned</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                            <option value="verified">Verified</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Actual Cost (KES)</label>
                        <input type="number" class="form-control" name="actual_cost" value="0" step="0.01">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Resolution Notes</label>
                        <textarea class="form-control" name="resolution_notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateRequestStatus(requestId, currentStatus) {
    // Set the request ID in the modal
    document.getElementById('updateRequestId').value = requestId;
    
    // Set the current status as selected
    const statusSelect = document.querySelector('#updateRequestStatusModal select[name="status"]');
    statusSelect.value = currentStatus;
    
    // Show the modal
    const modal = new bootstrap.Modal(document.getElementById('updateRequestStatusModal'));
    modal.show();
}

function viewRequestDetails(requestId) {
    // Placeholder for request details view
    alert('Request details functionality will be implemented. Request ID: ' + requestId);
}
</script>

<?php include '../includes/admin/footer.php'; ?>
