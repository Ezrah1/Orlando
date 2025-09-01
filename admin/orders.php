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

$page_title = 'Enhanced Order Management';

// Include database connection
include 'db.php';

// Handle AJAX requests
if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'update_order_status':
                $order_id = (int)$_POST['order_id'];
                $status = mysqli_real_escape_string($con, $_POST['status']);
                
                $update_fields = "status = '$status'";
                if ($status == 'confirmed') {
                    $update_fields .= ", confirmed_time = NOW(), confirmed_by = " . $_SESSION['user_id'];
                } elseif ($status == 'ready') {
                    $update_fields .= ", ready_time = NOW(), prepared_by = " . $_SESSION['user_id'];
                } elseif ($status == 'served') {
                    $update_fields .= ", served_time = NOW(), served_by = " . $_SESSION['user_id'];
                } elseif ($status == 'cancelled') {
                    $update_fields .= ", cancelled_time = NOW(), cancelled_by = " . $_SESSION['user_id'];
                }
                
                $sql = "UPDATE food_orders SET $update_fields WHERE id = $order_id";
                
                if (mysqli_query($con, $sql)) {
                    echo json_encode(['success' => true, 'message' => 'Order status updated successfully']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update order status']);
                }
                break;
                
            case 'get_order_details':
                $order_id = (int)$_POST['order_id'];
                
                $order_query = "SELECT fo.*, u1.username as ordered_by_name, u2.username as prepared_by_name, u3.username as served_by_name
                               FROM food_orders fo
                               LEFT JOIN users u1 ON fo.ordered_by = u1.id
                               LEFT JOIN users u2 ON fo.prepared_by = u2.id
                               LEFT JOIN users u3 ON fo.served_by = u3.id
                               WHERE fo.id = $order_id";
                
                $order_result = mysqli_query($con, $order_query);
                
                if ($order_result && mysqli_num_rows($order_result) > 0) {
                    $order = mysqli_fetch_assoc($order_result);
                    
                    // Get order items
                    $items_query = "SELECT oi.*, mi.name as menu_item_name 
                                   FROM order_items oi
                                   LEFT JOIN menu_items mi ON oi.menu_item_id = mi.id
                                   WHERE oi.order_id = $order_id";
                    $items_result = mysqli_query($con, $items_query);
                    $items = [];
                    
                    while ($item = mysqli_fetch_assoc($items_result)) {
                        $items[] = $item;
                    }
                    
                    $order['items'] = $items;
                    echo json_encode(['success' => true, 'order' => $order]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Order not found']);
                }
                break;
                
            case 'get_orders_summary':
                $summary_query = "SELECT 
                                    COUNT(*) as total_orders,
                                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
                                    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed_orders,
                                    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_orders,
                                    SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served_orders,
                                    SUM(final_amount) as total_revenue
                                  FROM food_orders 
                                  WHERE DATE(ordered_time) = CURDATE()";
                
                $summary_result = mysqli_query($con, $summary_query);
                $summary = mysqli_fetch_assoc($summary_result);
                
                echo json_encode(['success' => true, 'summary' => $summary]);
                break;
                
            case 'bulk_update_status':
                $order_ids = json_decode($_POST['order_ids'], true);
                $status = mysqli_real_escape_string($con, $_POST['status']);
                
                if (!is_array($order_ids) || empty($order_ids)) {
                    echo json_encode(['success' => false, 'message' => 'No orders selected']);
                    break;
                }
                
                $ids_str = implode(',', array_map('intval', $order_ids));
                $update_fields = "status = '$status'";
                
                if ($status == 'confirmed') {
                    $update_fields .= ", confirmed_time = NOW(), confirmed_by = " . $_SESSION['user_id'];
                } elseif ($status == 'ready') {
                    $update_fields .= ", ready_time = NOW(), prepared_by = " . $_SESSION['user_id'];
                } elseif ($status == 'served') {
                    $update_fields .= ", served_time = NOW(), served_by = " . $_SESSION['user_id'];
                }
                
                $sql = "UPDATE food_orders SET $update_fields WHERE id IN ($ids_str)";
                
                if (mysqli_query($con, $sql)) {
                    $affected = mysqli_affected_rows($con);
                    echo json_encode(['success' => true, 'message' => "$affected orders updated successfully"]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update orders']);
                }
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit();
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$order_type_filter = $_GET['order_type'] ?? '';
$date_filter = $_GET['date'] ?? date('Y-m-d');
$search = $_GET['search'] ?? '';

// Build where clause
$where_conditions = ["DATE(fo.ordered_time) = '$date_filter'"];

if ($status_filter) {
    $where_conditions[] = "fo.status = '$status_filter'";
}

if ($order_type_filter) {
    $where_conditions[] = "fo.order_type = '$order_type_filter'";
}

if ($search) {
    $search_escaped = mysqli_real_escape_string($con, $search);
    $where_conditions[] = "(fo.order_number LIKE '%$search_escaped%' OR fo.guest_name LIKE '%$search_escaped%' OR fo.guest_phone LIKE '%$search_escaped%' OR fo.room_number LIKE '%$search_escaped%')";
}

$where_clause = implode(' AND ', $where_conditions);

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<style>
.orders-dashboard {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px 0;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    text-align: center;
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-icon {
    font-size: 2.5rem;
    margin-bottom: 10px;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 5px;
}

.stat-label {
    color: #6c757d;
    font-size: 0.9rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.orders-controls {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.orders-table {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.table-responsive {
    max-height: 70vh;
    overflow-y: auto;
}

.order-row {
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.order-row:hover {
    background-color: #f8f9fa;
}

.order-row.selected {
    background-color: #e3f2fd;
}

.order-row.highlighted-order {
    background-color: #fff3cd !important;
    border-left: 4px solid #ffc107;
    animation: pulse-highlight 2s ease-in-out;
}

@keyframes pulse-highlight {
    0%, 100% { 
        background-color: #fff3cd; 
        transform: scale(1);
    }
    50% { 
        background-color: #ffeaa7; 
        transform: scale(1.02);
    }
}

.status-badge {
    padding: 4px 12px;
    border-radius: 15px;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d4edda; color: #155724; }
.status-ready { background: #cce5ff; color: #004085; }
.status-served { background: #d1ecf1; color: #0c5460; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.order-actions {
    display: flex;
    gap: 5px;
}

.btn-action {
    padding: 4px 8px;
    border: none;
    border-radius: 4px;
    font-size: 0.75rem;
    cursor: pointer;
    transition: all 0.3s ease;
}

.floating-actions {
    position: fixed;
    bottom: 30px;
    right: 30px;
    z-index: 1000;
}

.fab {
    width: 56px;
    height: 56px;
    border-radius: 50%;
    border: none;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    cursor: pointer;
    transition: all 0.3s ease;
    margin-bottom: 10px;
}

.fab:hover {
    transform: scale(1.1);
}

.order-details-modal .modal-body {
    max-height: 70vh;
    overflow-y: auto;
}

.order-timeline {
    position: relative;
    padding-left: 30px;
}

.order-timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-dot {
    position: absolute;
    left: -37px;
    top: 5px;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #e9ecef;
}

.timeline-dot.completed {
    background: #28a745;
}

.bulk-actions {
    background: #fff;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    display: none;
}

.bulk-actions.show {
    display: block;
}
</style>

<!-- Enhanced Orders Dashboard -->
<div class="orders-dashboard">
    <div class="container-fluid">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="fa fa-list-alt"></i> Order Management Dashboard
            </h1>
            <p class="page-subtitle">Streamlined order management with real-time updates</p>
        </div>

        <!-- Stats Cards -->
        <div class="stats-cards" id="statsCards">
            <div class="stat-card">
                <div class="stat-icon" style="color: #ffc107;">
                    <i class="fa fa-clock"></i>
                </div>
                <div class="stat-number" id="pendingCount">0</div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #28a745;">
                    <i class="fa fa-check"></i>
                </div>
                <div class="stat-number" id="confirmedCount">0</div>
                <div class="stat-label">Confirmed</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #007bff;">
                    <i class="fa fa-bell"></i>
                </div>
                <div class="stat-number" id="readyCount">0</div>
                <div class="stat-label">Ready</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #17a2b8;">
                    <i class="fa fa-smile"></i>
                </div>
                <div class="stat-number" id="servedCount">0</div>
                <div class="stat-label">Served</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #6f42c1;">
                    <i class="fa fa-chart-line"></i>
                </div>
                <div class="stat-number" id="totalRevenue">KES 0</div>
                <div class="stat-label">Today's Revenue</div>
            </div>
        </div>

        <!-- Controls -->
        <div class="orders-controls">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <label for="dateFilter" class="form-label">Date:</label>
                    <input type="date" class="form-control" id="dateFilter" value="<?php echo $date_filter; ?>">
                </div>
                <div class="col-md-2">
                    <label for="statusFilter" class="form-label">Status:</label>
                    <select class="form-control" id="statusFilter">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="ready" <?php echo $status_filter === 'ready' ? 'selected' : ''; ?>>Ready</option>
                        <option value="served" <?php echo $status_filter === 'served' ? 'selected' : ''; ?>>Served</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="orderTypeFilter" class="form-label">Type:</label>
                    <select class="form-control" id="orderTypeFilter">
                        <option value="">All Types</option>
                        <option value="dine_in" <?php echo $order_type_filter === 'dine_in' ? 'selected' : ''; ?>>Dine In</option>
                        <option value="takeaway" <?php echo $order_type_filter === 'takeaway' ? 'selected' : ''; ?>>Takeaway</option>
                        <option value="room_service" <?php echo $order_type_filter === 'room_service' ? 'selected' : ''; ?>>Room Service</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="searchFilter" class="form-label">Search:</label>
                    <input type="text" class="form-control" id="searchFilter" placeholder="Order #, Name, Phone, Room" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" onclick="applyFilters()">
                            <i class="fa fa-search"></i> Filter
                        </button>
                        <button class="btn btn-outline-secondary" onclick="refreshOrders()">
                            <i class="fa fa-refresh"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Bulk Actions -->
        <div class="bulk-actions" id="bulkActions">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="selected-count">0 orders selected</span>
                </div>
                <div class="col-md-6 text-right">
                    <div class="btn-group">
                        <button class="btn btn-success btn-sm" onclick="bulkUpdateStatus('confirmed')">
                            <i class="fa fa-check"></i> Confirm Selected
                        </button>
                        <button class="btn btn-primary btn-sm" onclick="bulkUpdateStatus('ready')">
                            <i class="fa fa-bell"></i> Mark Ready
                        </button>
                        <button class="btn btn-info btn-sm" onclick="bulkUpdateStatus('served')">
                            <i class="fa fa-smile"></i> Mark Served
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orders Table -->
        <div class="orders-table">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead style="background: #f8f9fa; position: sticky; top: 0; z-index: 100;">
                        <tr>
                            <th width="40">
                                <input type="checkbox" id="selectAll" onchange="toggleSelectAll()">
                            </th>
                            <th>Order #</th>
                            <th>Time</th>
                            <th>Guest</th>
                            <th>Type</th>
                            <th>Items</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="ordersTableBody">
                        <!-- Orders will be loaded here via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Floating Action Buttons -->
<div class="floating-actions">
    <button class="fab btn-primary" onclick="refreshOrders()" title="Refresh Orders">
        <i class="fa fa-refresh"></i>
    </button>
    <button class="fab btn-success" onclick="toggleAutoRefresh()" title="Auto Refresh" id="autoRefreshBtn">
        <i class="fa fa-play"></i>
    </button>
</div>

<!-- Order Details Modal -->
<div class="modal fade" id="orderDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Order Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailsContent">
                <!-- Order details will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
let selectedOrders = new Set();
let autoRefreshInterval = null;
let isAutoRefresh = false;

$(document).ready(function() {
    loadOrdersSummary();
    loadOrders();
    
    // Enable auto-refresh by default every 30 seconds
    toggleAutoRefresh();
});

function loadOrdersSummary() {
            $.post('orders.php', {
        ajax: '1',
        action: 'get_orders_summary'
    }, function(response) {
        if (response.success) {
            const summary = response.summary;
            $('#pendingCount').text(summary.pending_orders || 0);
            $('#confirmedCount').text(summary.confirmed_orders || 0);
            $('#readyCount').text(summary.ready_orders || 0);
            $('#servedCount').text(summary.served_orders || 0);
            $('#totalRevenue').text('KES ' + (summary.total_revenue ? parseInt(summary.total_revenue).toLocaleString() : '0'));
        }
    }, 'json');
}

function loadOrders() {
    const filters = {
        date: $('#dateFilter').val(),
        status: $('#statusFilter').val(),
        order_type: $('#orderTypeFilter').val(),
        search: $('#searchFilter').val()
    };
    
    // Build query string
    const queryString = Object.keys(filters)
        .filter(key => filters[key])
        .map(key => key + '=' + encodeURIComponent(filters[key]))
        .join('&');
    
    // Show loading
    $('#ordersTableBody').html('<tr><td colspan="9" class="text-center py-4"><i class="fa fa-spinner fa-spin"></i> Loading orders...</td></tr>');
    
    // Load orders via AJAX
    $.get(`orders_data.php?${queryString}`, function(response) {
        if (response.success) {
            renderOrdersTable(response.orders);
        } else {
            $('#ordersTableBody').html('<tr><td colspan="9" class="text-center py-4 text-danger">Error loading orders</td></tr>');
        }
    }, 'json').fail(function() {
        $('#ordersTableBody').html('<tr><td colspan="9" class="text-center py-4 text-danger">Failed to load orders</td></tr>');
    });
}

function renderOrdersTable(orders) {
    let html = '';
    
    if (orders.length === 0) {
        html = '<tr><td colspan="9" class="text-center py-4 text-muted">No orders found</td></tr>';
    } else {
        orders.forEach(order => {
            const statusClass = `status-${order.status}`;
            const timeAgo = moment(order.ordered_time).fromNow();
            
            html += `
                <tr class="order-row" data-order-id="${order.id}" onclick="toggleOrderSelection(${order.id})">
                    <td>
                        <input type="checkbox" class="order-checkbox" value="${order.id}" onchange="updateOrderSelection(${order.id}, this.checked)">
                    </td>
                    <td>
                        <strong>${order.order_number}</strong>
                        ${order.room_number ? `<br><small class="text-muted">Room ${order.room_number}</small>` : ''}
                    </td>
                    <td>
                        <span title="${order.ordered_time}">${timeAgo}</span>
                    </td>
                    <td>
                        <div>${order.guest_name}</div>
                        <small class="text-muted">${order.guest_phone}</small>
                    </td>
                    <td>
                        <span class="badge badge-secondary">${order.order_type.replace('_', ' ')}</span>
                    </td>
                    <td>${order.total_items || 0}</td>
                    <td>KES ${parseInt(order.final_amount || 0).toLocaleString()}</td>
                    <td>
                        <span class="status-badge ${statusClass}">${order.status}</span>
                    </td>
                    <td>
                        <div class="order-actions">
                            <button class="btn-action btn-primary" onclick="viewOrderDetails(${order.id})" title="View Details">
                                <i class="fa fa-eye"></i>
                            </button>
                            ${renderStatusActions(order)}
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    $('#ordersTableBody').html(html);
    updateBulkActions();
}

function renderStatusActions(order) {
    let actions = '';
    
    switch (order.status) {
        case 'pending':
            actions += `<button class="btn-action btn-success" onclick="updateOrderStatus(${order.id}, 'confirmed')" title="Confirm">
                            <i class="fa fa-check"></i>
                        </button>`;
            break;
        case 'confirmed':
            actions += `<button class="btn-action btn-primary" onclick="updateOrderStatus(${order.id}, 'ready')" title="Mark Ready">
                            <i class="fa fa-bell"></i>
                        </button>`;
            break;
        case 'ready':
            actions += `<button class="btn-action btn-info" onclick="updateOrderStatus(${order.id}, 'served')" title="Mark Served">
                            <i class="fa fa-smile"></i>
                        </button>`;
            break;
    }
    
    if (order.status !== 'served' && order.status !== 'cancelled') {
        actions += `<button class="btn-action btn-danger" onclick="updateOrderStatus(${order.id}, 'cancelled')" title="Cancel">
                        <i class="fa fa-times"></i>
                    </button>`;
    }
    
    return actions;
}

function updateOrderStatus(orderId, status) {
            $.post('orders.php', {
        ajax: '1',
        action: 'update_order_status',
        order_id: orderId,
        status: status
    }, function(response) {
        if (response.success) {
            showNotification(response.message, 'success');
            loadOrders();
            loadOrdersSummary();
        } else {
            showNotification(response.message, 'error');
        }
    }, 'json');
}

function viewOrderDetails(orderId) {
            $.post('orders.php', {
        ajax: '1',
        action: 'get_order_details',
        order_id: orderId
    }, function(response) {
        if (response.success) {
            renderOrderDetails(response.order);
            const modal = new bootstrap.Modal(document.getElementById('orderDetailsModal'));
            modal.show();
        } else {
            showNotification(response.message, 'error');
        }
    }, 'json');
}

function renderOrderDetails(order) {
    const html = `
        <div class="row">
            <div class="col-md-6">
                <h6>Order Information</h6>
                <table class="table table-sm">
                    <tr><td>Order #:</td><td><strong>${order.order_number}</strong></td></tr>
                    <tr><td>Status:</td><td><span class="status-badge status-${order.status}">${order.status}</span></td></tr>
                    <tr><td>Type:</td><td>${order.order_type.replace('_', ' ')}</td></tr>
                    <tr><td>Payment:</td><td>${order.payment_method}</td></tr>
                    <tr><td>Total:</td><td><strong>KES ${parseInt(order.final_amount).toLocaleString()}</strong></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6>Guest Information</h6>
                <table class="table table-sm">
                    <tr><td>Name:</td><td>${order.guest_name}</td></tr>
                    <tr><td>Phone:</td><td>${order.guest_phone}</td></tr>
                    ${order.room_number ? `<tr><td>Room:</td><td>${order.room_number}</td></tr>` : ''}
                    <tr><td>Ordered:</td><td>${moment(order.ordered_time).format('MMM D, YYYY h:mm A')}</td></tr>
                </table>
            </div>
        </div>
        
        ${order.special_instructions ? `
            <div class="mb-3">
                <h6>Special Instructions</h6>
                <div class="alert alert-info">${order.special_instructions}</div>
            </div>
        ` : ''}
        
        <h6>Order Items</h6>
        <div class="table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    ${order.items.map(item => `
                        <tr>
                            <td>${item.item_name || item.menu_item_name || 'Unknown Item'}</td>
                            <td>${item.quantity}</td>
                            <td>KES ${parseInt(item.unit_price).toLocaleString()}</td>
                            <td>KES ${parseInt(item.total_price).toLocaleString()}</td>
                        </tr>
                    `).join('')}
                </tbody>
            </table>
        </div>
        
        <div class="order-timeline">
            <h6>Order Timeline</h6>
            <div class="timeline-item">
                <div class="timeline-dot completed"></div>
                <strong>Order Placed</strong> - ${moment(order.ordered_time).format('h:mm A')}
                ${order.ordered_by_name ? ` by ${order.ordered_by_name}` : ''}
            </div>
            ${order.confirmed_time ? `
                <div class="timeline-item">
                    <div class="timeline-dot completed"></div>
                    <strong>Confirmed</strong> - ${moment(order.confirmed_time).format('h:mm A')}
                </div>
            ` : ''}
            ${order.ready_time ? `
                <div class="timeline-item">
                    <div class="timeline-dot completed"></div>
                    <strong>Ready</strong> - ${moment(order.ready_time).format('h:mm A')}
                    ${order.prepared_by_name ? ` by ${order.prepared_by_name}` : ''}
                </div>
            ` : ''}
            ${order.served_time ? `
                <div class="timeline-item">
                    <div class="timeline-dot completed"></div>
                    <strong>Served</strong> - ${moment(order.served_time).format('h:mm A')}
                    ${order.served_by_name ? ` by ${order.served_by_name}` : ''}
                </div>
            ` : ''}
        </div>
    `;
    
    $('#orderDetailsContent').html(html);
}

function toggleOrderSelection(orderId) {
    const checkbox = $(`.order-checkbox[value="${orderId}"]`);
    checkbox.prop('checked', !checkbox.prop('checked'));
    updateOrderSelection(orderId, checkbox.prop('checked'));
}

function updateOrderSelection(orderId, isSelected) {
    if (isSelected) {
        selectedOrders.add(orderId);
        $(`.order-row[data-order-id="${orderId}"]`).addClass('selected');
    } else {
        selectedOrders.delete(orderId);
        $(`.order-row[data-order-id="${orderId}"]`).removeClass('selected');
    }
    
    updateBulkActions();
}

function toggleSelectAll() {
    const isChecked = $('#selectAll').prop('checked');
    $('.order-checkbox').prop('checked', isChecked);
    
    selectedOrders.clear();
    if (isChecked) {
        $('.order-checkbox').each(function() {
            selectedOrders.add(parseInt($(this).val()));
        });
        $('.order-row').addClass('selected');
    } else {
        $('.order-row').removeClass('selected');
    }
    
    updateBulkActions();
}

function updateBulkActions() {
    const count = selectedOrders.size;
    if (count > 0) {
        $('#bulkActions').addClass('show');
        $('#bulkActions .selected-count').text(`${count} order${count > 1 ? 's' : ''} selected`);
    } else {
        $('#bulkActions').removeClass('show');
    }
}

function bulkUpdateStatus(status) {
    if (selectedOrders.size === 0) {
        showNotification('No orders selected', 'error');
        return;
    }
    
    const orderIds = Array.from(selectedOrders);
    
            $.post('orders.php', {
        ajax: '1',
        action: 'bulk_update_status',
        order_ids: JSON.stringify(orderIds),
        status: status
    }, function(response) {
        if (response.success) {
            showNotification(response.message, 'success');
            selectedOrders.clear();
            $('#selectAll').prop('checked', false);
            loadOrders();
            loadOrdersSummary();
        } else {
            showNotification(response.message, 'error');
        }
    }, 'json');
}

function applyFilters() {
    loadOrders();
    loadOrdersSummary();
}

function refreshOrders() {
    loadOrders();
    loadOrdersSummary();
    showNotification('Orders refreshed', 'success');
}

function toggleAutoRefresh() {
    if (isAutoRefresh) {
        clearInterval(autoRefreshInterval);
        isAutoRefresh = false;
        $('#autoRefreshBtn').html('<i class="fa fa-play"></i>').attr('title', 'Start Auto Refresh');
    } else {
        autoRefreshInterval = setInterval(function() {
            loadOrders();
            loadOrdersSummary();
        }, 30000);
        isAutoRefresh = true;
        $('#autoRefreshBtn').html('<i class="fa fa-pause"></i>').attr('title', 'Stop Auto Refresh');
    }
}

function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    
    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show notification-alert" role="alert" style="position: fixed; top: 100px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 12px; box-shadow: 0 8px 25px rgba(0,0,0,0.15);">
            <i class="fa ${icon}"></i> ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('.notification-alert').remove();
    $('body').append(alertHtml);
    
    setTimeout(function() {
        $('.notification-alert').fadeOut(function() {
            $(this).remove();
        });
    }, 4000);
}

// Keyboard shortcuts
$(document).on('keydown', function(e) {
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        refreshOrders();
    }
});

// Handle URL anchors for direct order linking
function handleOrderAnchor() {
    const hash = window.location.hash;
    if (hash && hash.startsWith('#order-')) {
        const orderId = hash.replace('#order-', '');
        
        // Wait for orders to load, then highlight the specific order
        setTimeout(function() {
            const orderRow = $(`.order-row[data-order-id="${orderId}"]`);
            if (orderRow.length) {
                // Scroll to the order
                orderRow[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                
                // Highlight the order with animation
                orderRow.addClass('highlighted-order');
                
                // Show order details if found
                setTimeout(function() {
                    orderRow.click();
                    showNotification(`Showing details for Order #${orderId}`, 'success');
                }, 1000);
                
                // Remove highlight after 3 seconds
                setTimeout(function() {
                    orderRow.removeClass('highlighted-order');
                }, 3000);
            } else {
                showNotification(`Order #${orderId} not found in current view`, 'error');
            }
        }, 500);
    }
}

// Check for anchor on page load and after order refresh
$(document).ready(function() {
    handleOrderAnchor();
});

// Re-check anchor after orders are refreshed
const originalRefreshOrders = refreshOrders;
refreshOrders = function() {
    originalRefreshOrders();
    setTimeout(handleOrderAnchor, 1000);
};

// Ensure modal can be closed programmatically
function closeOrderModal() {
    const modalElement = document.getElementById('orderDetailsModal');
    const modal = bootstrap.Modal.getInstance(modalElement) || new bootstrap.Modal(modalElement);
    modal.hide();
}

// Add ESC key handler for modal
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modalElement = document.getElementById('orderDetailsModal');
        if (modalElement.classList.contains('show')) {
            closeOrderModal();
        }
    }
});
</script>

<!-- Include Moment.js for time formatting -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.4/moment.min.js"></script>

<?php include('../includes/admin/footer.php'); ?>
