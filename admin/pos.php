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

$page_title = 'Orlando POS System';

// Initialize variables
$message = '';
$error = '';
$current_order = [];
$guest_info = null;

// Handle AJAX requests
if (isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'search_guest':
            $search = mysqli_real_escape_string($con, $_POST['search']);
            $guests_sql = "SELECT rb.*, nr.room_name 
                          FROM roombook rb 
                          LEFT JOIN named_rooms nr ON rb.troom = nr.room_name 
                          WHERE (rb.FName LIKE '%$search%' OR rb.LName LIKE '%$search%' OR rb.Email LIKE '%$search%' OR rb.troom LIKE '%$search%') 
                          AND rb.stat = 'Confirm' 
                          ORDER BY rb.cin DESC LIMIT 10";
            $guests_result = mysqli_query($con, $guests_sql);
            $guests = [];
            while ($guest = mysqli_fetch_assoc($guests_result)) {
                $guests[] = $guest;
            }
            echo json_encode($guests);
            exit;
            
        case 'get_menu_items':
            $category = mysqli_real_escape_string($con, $_POST['category']);
            $items_sql = "SELECT * FROM restaurant_menu WHERE category = '$category' AND status = 'active' ORDER BY name";
            $items_result = mysqli_query($con, $items_sql);
            $items = [];
            while ($item = mysqli_fetch_assoc($items_result)) {
                $items[] = $item;
            }
            echo json_encode($items);
            exit;
            
        case 'get_bar_items':
            $items_sql = "SELECT bi.*, ic.name as category_name 
                         FROM bar_inventory bi 
                         LEFT JOIN inventory_categories ic ON bi.category_id = ic.id 
                         WHERE bi.current_stock > 0 
                         ORDER BY bi.name";
            $items_result = mysqli_query($con, $items_sql);
            $items = [];
            while ($item = mysqli_fetch_assoc($items_result)) {
                $items[] = $item;
            }
            echo json_encode($items);
            exit;
            
        case 'process_payment':
            $order_data = json_decode($_POST['order_data'], true);
            $payment_method = mysqli_real_escape_string($con, $_POST['payment_method']);
            $guest_id = intval($_POST['guest_id'] ?? 0);
            $room_charge = intval($_POST['room_charge'] ?? 0);
            
            // Calculate totals
            $subtotal = 0;
            foreach ($order_data as $item) {
                $subtotal += $item['price'] * $item['quantity'];
            }
            $tax = $subtotal * 0.16; // 16% VAT
            $total = $subtotal + $tax;
            
            // Create order record
            $order_ref = 'POS-' . date('YmdHis') . '-' . rand(1000, 9999);
            $cashier_id = $_SESSION['user_id'];
            
            // For now, use a simple transactions table approach
            $insert_sql = "INSERT INTO transactions (source, description, amount_gross, tax_amount, net_amount, currency, cashier_user_id, payment_status, ref_code) 
                          VALUES ('pos', 'POS Order - $order_ref', $subtotal, $tax, $total, 'KES', $cashier_id, 'paid', '$order_ref')";
            
            if (mysqli_query($con, $insert_sql)) {
                echo json_encode(['success' => true, 'order_ref' => $order_ref, 'total' => $total]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Failed to process order']);
            }
            exit;
    }
}

// Include the dynamic admin header
include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<div class="pos-container">
    <!-- POS Header -->
    <div class="pos-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1 class="pos-title">Orlando POS System</h1>
                <p class="pos-subtitle">Integrated Point of Sale - Restaurant • Bar • Room Service</p>
            </div>
            <div class="pos-info">
                <div class="cashier-info">
                    <i class="fas fa-user-tie"></i>
                    <span>Cashier: <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?></span>
                </div>
                <div class="datetime-info">
                    <i class="fas fa-clock"></i>
                    <span id="currentDateTime"></span>
                </div>
            </div>
        </div>
    </div>

    <div class="row pos-main">
        <!-- Left Panel - Menu & Inventory -->
        <div class="col-lg-8 pos-left">
            <!-- Guest Search & Selection -->
            <div class="guest-section mb-4">
                <h5><i class="fas fa-user-friends"></i> Guest Selection</h5>
                <div class="input-group">
                    <input type="text" id="guestSearch" class="form-control" placeholder="Search by name, email, or room number...">
                    <button class="btn btn-outline-secondary" type="button" id="newGuestBtn">
                        <i class="fas fa-user-plus"></i> Walk-in Customer
                    </button>
                </div>
                <div id="guestResults" class="guest-results mt-2"></div>
                <div id="selectedGuest" class="selected-guest mt-2" style="display: none;"></div>
            </div>

            <!-- Category Tabs -->
            <div class="category-tabs">
                <ul class="nav nav-pills" id="categoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="restaurant-tab" data-bs-toggle="pill" data-bs-target="#restaurant" type="button" role="tab">
                            <i class="fas fa-utensils"></i> Restaurant
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="bar-tab" data-bs-toggle="pill" data-bs-target="#bar" type="button" role="tab">
                            <i class="fas fa-wine-glass-alt"></i> Bar
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="room-service-tab" data-bs-toggle="pill" data-bs-target="#room-service" type="button" role="tab">
                            <i class="fas fa-bed"></i> Room Service
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="packages-tab" data-bs-toggle="pill" data-bs-target="#packages" type="button" role="tab">
                            <i class="fas fa-gift"></i> Packages
                        </button>
                    </li>
                </ul>
            </div>

            <!-- Tab Content -->
            <div class="tab-content pos-content" id="categoryTabContent">
                <!-- Restaurant Tab -->
                <div class="tab-pane fade show active" id="restaurant" role="tabpanel">
                    <div class="subcategory-pills mb-3">
                        <button class="btn btn-outline-primary category-btn active" data-category="appetizers">Appetizers</button>
                        <button class="btn btn-outline-primary category-btn" data-category="main_course">Main Course</button>
                        <button class="btn btn-outline-primary category-btn" data-category="desserts">Desserts</button>
                        <button class="btn btn-outline-primary category-btn" data-category="beverages">Beverages</button>
                    </div>
                    <div class="items-grid" id="restaurantItems">
                        <!-- Restaurant items will be loaded here -->
                    </div>
                </div>

                <!-- Bar Tab -->
                <div class="tab-pane fade" id="bar" role="tabpanel">
                    <div class="subcategory-pills mb-3">
                        <button class="btn btn-outline-success category-btn active" data-category="all">All Drinks</button>
                        <button class="btn btn-outline-success category-btn" data-category="spirits">Spirits</button>
                        <button class="btn btn-outline-success category-btn" data-category="beer">Beer</button>
                        <button class="btn btn-outline-success category-btn" data-category="wine">Wine</button>
                        <button class="btn btn-outline-success category-btn" data-category="cocktails">Cocktails</button>
                    </div>
                    <div class="items-grid" id="barItems">
                        <!-- Bar items will be loaded here -->
                    </div>
                </div>

                <!-- Room Service Tab -->
                <div class="tab-pane fade" id="room-service" role="tabpanel">
                    <div class="room-service-options">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="service-card" onclick="addServiceItem('Room Cleaning', 2500)">
                                    <i class="fas fa-broom"></i>
                                    <h6>Room Cleaning</h6>
                                    <span class="price">KES 2,500</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="service-card" onclick="addServiceItem('Laundry Service', 1500)">
                                    <i class="fas fa-tshirt"></i>
                                    <h6>Laundry Service</h6>
                                    <span class="price">KES 1,500</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="service-card" onclick="addServiceItem('Wake-up Call', 500)">
                                    <i class="fas fa-phone"></i>
                                    <h6>Wake-up Call</h6>
                                    <span class="price">KES 500</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="service-card" onclick="addServiceItem('Airport Transfer', 5000)">
                                    <i class="fas fa-car"></i>
                                    <h6>Airport Transfer</h6>
                                    <span class="price">KES 5,000</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Packages Tab -->
                <div class="tab-pane fade" id="packages" role="tabpanel">
                    <div class="packages-grid">
                        <div class="package-card" onclick="addServiceItem('Spa Package', 10000)">
                            <i class="fas fa-spa"></i>
                            <h5>Spa Relaxation Package</h5>
                            <p>Full body massage + facial treatment</p>
                            <span class="price">KES 10,000</span>
                        </div>
                        <div class="package-card" onclick="addServiceItem('Dinner Package', 7500)">
                            <i class="fas fa-utensils"></i>
                            <h5>Romantic Dinner Package</h5>
                            <p>3-course meal for two with wine</p>
                            <span class="price">KES 7,500</span>
                        </div>
                        <div class="package-card" onclick="addServiceItem('Adventure Package', 15000)">
                            <i class="fas fa-mountain"></i>
                            <h5>Adventure Day Package</h5>
                            <p>Activities + lunch + transport</p>
                            <span class="price">KES 15,000</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Order Summary -->
        <div class="col-lg-4 pos-right">
            <div class="order-panel">
                <div class="order-header">
                    <h5><i class="fas fa-shopping-cart"></i> Current Order</h5>
                    <button class="btn btn-sm btn-outline-danger" onclick="clearOrder()">
                        <i class="fas fa-trash"></i> Clear
                    </button>
                </div>

                <div class="order-items" id="orderItems">
                    <!-- Order items will appear here -->
                    <div class="empty-order">
                        <i class="fas fa-cart-plus"></i>
                        <p>Start adding items to your order</p>
                    </div>
                </div>

                <div class="order-summary">
                    <div class="summary-line">
                        <span>Subtotal:</span>
                        <span id="subtotal">KES 0.00</span>
                    </div>
                    <div class="summary-line">
                        <span>Tax (16%):</span>
                        <span id="tax">KES 0.00</span>
                    </div>
                    <div class="summary-line total">
                        <span><strong>Total:</strong></span>
                        <span id="total"><strong>KES 0.00</strong></span>
                    </div>
                </div>

                <div class="payment-options">
                    <h6>Payment Method</h6>
                    <div class="payment-methods">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="cash" value="cash" checked>
                            <label class="form-check-label" for="cash">
                                <i class="fas fa-money-bill-wave"></i> Cash
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="card" value="card">
                            <label class="form-check-label" for="card">
                                <i class="fas fa-credit-card"></i> Card
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="mpesa" value="mpesa">
                            <label class="form-check-label" for="mpesa">
                                <i class="fas fa-mobile-alt"></i> M-Pesa
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="paymentMethod" id="room" value="room">
                            <label class="form-check-label" for="room">
                                <i class="fas fa-bed"></i> Charge to Room
                            </label>
                        </div>
                    </div>
                </div>

                <div class="action-buttons">
                    <button class="btn btn-primary btn-lg w-100 mb-2" onclick="processPayment()" id="payBtn" disabled>
                        <i class="fas fa-credit-card"></i> Process Payment
                    </button>
                    <button class="btn btn-outline-secondary w-100" onclick="holdOrder()">
                        <i class="fas fa-pause"></i> Hold Order
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pos-container {
    background: #f8f9fa;
    min-height: 100vh;
    padding: 20px;
}

.pos-header {
    background: white;
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pos-title {
    color: #2c3e50;
    margin: 0;
    font-weight: 700;
}

.pos-subtitle {
    color: #7f8c8d;
    margin: 0;
}

.pos-info {
    display: flex;
    gap: 20px;
    align-items: center;
}

.cashier-info, .datetime-info {
    display: flex;
    align-items: center;
    gap: 8px;
    color: #34495e;
    font-weight: 500;
}

.pos-main {
    gap: 20px;
}

.pos-left {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.pos-right {
    background: white;
    border-radius: 10px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

.guest-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
}

.guest-results {
    max-height: 200px;
    overflow-y: auto;
}

.guest-item {
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-bottom: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.guest-item:hover {
    background: #e9ecef;
    border-color: #007bff;
}

.selected-guest {
    background: #d4edda;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #c3e6cb;
}

.category-tabs .nav-pills .nav-link {
    border-radius: 25px;
    margin-right: 10px;
    padding: 12px 20px;
    font-weight: 600;
    transition: all 0.3s;
}

.category-tabs .nav-pills .nav-link.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.subcategory-pills {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.category-btn {
    border-radius: 20px;
    font-weight: 500;
    transition: all 0.3s;
}

.category-btn.active {
    background: #007bff;
    color: white;
    border-color: #007bff;
}

.items-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 15px;
    max-height: 500px;
    overflow-y: auto;
    padding: 10px 0;
}

.item-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 15px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.item-card:hover {
    border-color: #007bff;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(0,123,255,0.3);
}

.item-card .item-image {
    width: 60px;
    height: 60px;
    background: #f8f9fa;
    border-radius: 50%;
    margin: 0 auto 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    color: #6c757d;
}

.item-card h6 {
    margin: 10px 0 5px;
    font-weight: 600;
    color: #2c3e50;
}

.item-card .price {
    color: #28a745;
    font-weight: 700;
    font-size: 16px;
}

.service-card, .package-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    margin-bottom: 15px;
}

.service-card:hover, .package-card:hover {
    border-color: #28a745;
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(40,167,69,0.3);
}

.service-card i, .package-card i {
    font-size: 36px;
    color: #28a745;
    margin-bottom: 10px;
}

.order-panel {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-bottom: 15px;
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 15px;
}

.order-items {
    flex: 1;
    max-height: 300px;
    overflow-y: auto;
    margin-bottom: 20px;
}

.empty-order {
    text-align: center;
    color: #6c757d;
    padding: 40px 20px;
}

.empty-order i {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.order-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    border-bottom: 1px solid #e9ecef;
}

.order-item-info h6 {
    margin: 0;
    font-size: 14px;
    font-weight: 600;
}

.order-item-info small {
    color: #6c757d;
}

.quantity-controls {
    display: flex;
    align-items: center;
    gap: 8px;
}

.quantity-btn {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    border: 1px solid #dee2e6;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 12px;
}

.quantity-btn:hover {
    background: #f8f9fa;
}

.order-summary {
    border-top: 2px solid #e9ecef;
    padding-top: 15px;
    margin-bottom: 20px;
}

.summary-line {
    display: flex;
    justify-content: space-between;
    margin-bottom: 8px;
}

.summary-line.total {
    border-top: 1px solid #dee2e6;
    padding-top: 8px;
    margin-top: 10px;
    font-size: 18px;
}

.payment-methods {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 10px;
    margin-bottom: 20px;
}

.form-check {
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.form-check:hover {
    border-color: #007bff;
}

.form-check input:checked + label {
    color: #007bff;
    font-weight: 600;
}

.action-buttons button {
    font-weight: 600;
    border-radius: 8px;
    padding: 12px;
}

@media (max-width: 768px) {
    .pos-main {
        flex-direction: column;
    }
    
    .items-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    }
    
    .payment-methods {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
let currentOrder = [];
let selectedGuest = null;

// Initialize POS system
document.addEventListener('DOMContentLoaded', function() {
    updateDateTime();
    setInterval(updateDateTime, 1000);
    loadRestaurantItems('appetizers');
    
    // Guest search functionality
    document.getElementById('guestSearch').addEventListener('input', debounce(searchGuests, 500));
    
    // Category switching
    document.querySelectorAll('.category-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const category = this.dataset.category;
            const activeTab = document.querySelector('.tab-pane.show.active').id;
            
            // Update active state
            this.parentElement.querySelectorAll('.category-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            if (activeTab === 'restaurant') {
                loadRestaurantItems(category);
            } else if (activeTab === 'bar') {
                loadBarItems(category);
            }
        });
    });
    
    // Tab switching
    document.querySelectorAll('[data-bs-toggle="pill"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', function(e) {
            const target = e.target.getAttribute('data-bs-target');
            if (target === '#restaurant') {
                loadRestaurantItems('appetizers');
            } else if (target === '#bar') {
                loadBarItems('all');
            }
        });
    });
});

function updateDateTime() {
    const now = new Date();
    const options = { 
        year: 'numeric', 
        month: 'short', 
        day: 'numeric', 
        hour: '2-digit', 
        minute: '2-digit', 
        second: '2-digit' 
    };
    document.getElementById('currentDateTime').textContent = now.toLocaleDateString('en-US', options);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function searchGuests() {
    const search = document.getElementById('guestSearch').value;
    if (search.length < 2) {
        document.getElementById('guestResults').innerHTML = '';
        return;
    }
    
    fetch('pos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=search_guest&search=${encodeURIComponent(search)}`
    })
    .then(response => response.json())
    .then(guests => {
        const resultsDiv = document.getElementById('guestResults');
        if (guests.length === 0) {
            resultsDiv.innerHTML = '<div class="text-muted">No guests found</div>';
            return;
        }
        
        resultsDiv.innerHTML = guests.map(guest => 
            `<div class="guest-item" onclick="selectGuest(${guest.id}, '${guest.FName} ${guest.LName}', '${guest.troom}', '${guest.Email}')">
                <strong>${guest.FName} ${guest.LName}</strong><br>
                <small>Room: ${guest.troom || 'N/A'} | Email: ${guest.Email}</small>
            </div>`
        ).join('');
    });
}

function selectGuest(id, name, room, email) {
    selectedGuest = {id, name, room, email};
    document.getElementById('selectedGuest').innerHTML = 
        `<strong>Selected Guest:</strong> ${name}<br>
         <small>Room: ${room} | Email: ${email}</small>
         <button class="btn btn-sm btn-outline-danger float-end" onclick="clearGuestSelection()">Clear</button>`;
    document.getElementById('selectedGuest').style.display = 'block';
    document.getElementById('guestResults').innerHTML = '';
    document.getElementById('guestSearch').value = '';
}

function clearGuestSelection() {
    selectedGuest = null;
    document.getElementById('selectedGuest').style.display = 'none';
}

function loadRestaurantItems(category) {
    fetch('pos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_menu_items&category=${category}`
    })
    .then(response => response.json())
    .then(items => {
        const itemsDiv = document.getElementById('restaurantItems');
        if (items.length === 0) {
            itemsDiv.innerHTML = '<div class="col-12 text-center text-muted">No items found in this category</div>';
            return;
        }
        itemsDiv.innerHTML = items.map(item => 
            `<div class="item-card" onclick="addToOrder(${item.id}, '${item.name}', ${item.price}, 'food')">
                <div class="item-image">
                    <i class="fas fa-utensils"></i>
                </div>
                <h6>${item.name}</h6>
                <div class="price">KES ${parseFloat(item.price).toFixed(2)}</div>
                <small class="text-muted">${item.description || ''}</small>
            </div>`
        ).join('');
    });
}

function loadBarItems(category) {
    fetch('pos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=get_bar_items&category=${category}`
    })
    .then(response => response.json())
    .then(items => {
        const itemsDiv = document.getElementById('barItems');
        if (items.length === 0) {
            itemsDiv.innerHTML = '<div class="col-12 text-center text-muted">No items available</div>';
            return;
        }
        itemsDiv.innerHTML = items.map(item => 
            `<div class="item-card" onclick="addToOrder(${item.id}, '${item.name}', ${item.selling_price}, 'bar')">
                <div class="item-image">
                    <i class="fas fa-wine-glass-alt"></i>
                </div>
                <h6>${item.name}</h6>
                <div class="price">KES ${parseFloat(item.selling_price).toFixed(2)}</div>
                <small class="text-muted">Stock: ${item.current_stock}</small>
            </div>`
        ).join('');
    });
}

function addToOrder(id, name, price, type) {
    const existingItem = currentOrder.find(item => item.id === id && item.type === type);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        currentOrder.push({
            id: id,
            name: name,
            price: parseFloat(price),
            type: type,
            quantity: 1
        });
    }
    
    updateOrderDisplay();
}

function addServiceItem(name, price) {
    const existingItem = currentOrder.find(item => item.name === name && item.type === 'service');
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        currentOrder.push({
            id: Date.now(), // Use timestamp as ID for services
            name: name,
            price: parseFloat(price),
            type: 'service',
            quantity: 1
        });
    }
    
    updateOrderDisplay();
}

function updateOrderDisplay() {
    const orderItemsDiv = document.getElementById('orderItems');
    
    if (currentOrder.length === 0) {
        orderItemsDiv.innerHTML = `
            <div class="empty-order">
                <i class="fas fa-cart-plus"></i>
                <p>Start adding items to your order</p>
            </div>`;
        document.getElementById('payBtn').disabled = true;
    } else {
        orderItemsDiv.innerHTML = currentOrder.map((item, index) => 
            `<div class="order-item">
                <div class="order-item-info">
                    <h6>${item.name}</h6>
                    <small>KES ${item.price.toFixed(2)} each</small>
                </div>
                <div class="quantity-controls">
                    <button class="quantity-btn" onclick="changeQuantity(${index}, -1)">-</button>
                    <span>${item.quantity}</span>
                    <button class="quantity-btn" onclick="changeQuantity(${index}, 1)">+</button>
                    <button class="btn btn-sm btn-outline-danger ms-2" onclick="removeItem(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>`
        ).join('');
        document.getElementById('payBtn').disabled = false;
    }
    
    updateTotals();
}

function changeQuantity(index, change) {
    if (currentOrder[index].quantity + change <= 0) {
        removeItem(index);
        return;
    }
    
    currentOrder[index].quantity += change;
    updateOrderDisplay();
}

function removeItem(index) {
    currentOrder.splice(index, 1);
    updateOrderDisplay();
}

function updateTotals() {
    const subtotal = currentOrder.reduce((sum, item) => sum + (item.price * item.quantity), 0);
    const tax = subtotal * 0.16; // 16% VAT
    const total = subtotal + tax;
    
    document.getElementById('subtotal').textContent = `KES ${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `KES ${tax.toFixed(2)}`;
    document.getElementById('total').textContent = `KES ${total.toFixed(2)}`;
}

function clearOrder() {
    if (confirm('Are you sure you want to clear the order?')) {
        currentOrder = [];
        updateOrderDisplay();
    }
}

function processPayment() {
    if (currentOrder.length === 0) {
        alert('Please add items to the order first');
        return;
    }
    
    const paymentMethod = document.querySelector('input[name="paymentMethod"]:checked').value;
    const roomCharge = paymentMethod === 'room' ? 1 : 0;
    
    if (roomCharge && !selectedGuest) {
        alert('Please select a guest for room charge');
        return;
    }
    
    const orderData = JSON.stringify(currentOrder);
    
    fetch('pos.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=process_payment&order_data=${encodeURIComponent(orderData)}&payment_method=${paymentMethod}&guest_id=${selectedGuest ? selectedGuest.id : 0}&room_charge=${roomCharge}`
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            alert(`Order processed successfully!\nOrder Reference: ${result.order_ref}\nTotal: KES ${result.total.toFixed(2)}`);
            currentOrder = [];
            updateOrderDisplay();
            clearGuestSelection();
        } else {
            alert(`Error: ${result.error}`);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while processing the payment');
    });
}

function holdOrder() {
    if (currentOrder.length === 0) {
        alert('No items in order to hold');
        return;
    }
    
    // Save order to localStorage for later retrieval
    const heldOrders = JSON.parse(localStorage.getItem('heldOrders') || '[]');
    const orderRef = `HOLD-${Date.now()}`;
    
    heldOrders.push({
        ref: orderRef,
        order: [...currentOrder],
        guest: selectedGuest,
        timestamp: new Date().toISOString()
    });
    
    localStorage.setItem('heldOrders', JSON.stringify(heldOrders));
    
    alert(`Order held successfully!\nReference: ${orderRef}`);
    currentOrder = [];
    updateOrderDisplay();
    clearGuestSelection();
}
</script>

<?php include '../includes/admin/footer.php'; ?>


