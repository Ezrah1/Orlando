<?php
session_start();
include('db.php');

// Initialize cart if not exists
if (!isset($_SESSION['order_cart'])) {
    $_SESSION['order_cart'] = [];
}

// Get parameters
$action = $_GET['action'] ?? '';
$item_id = (int)($_GET['item_id'] ?? 0);
$quantity = (int)($_GET['quantity'] ?? 1);
$redirect = $_GET['redirect'] ?? 'http://localhost/Hotel/modules/guest/menu/menu_enhanced.php';

$message = '';
$success = false;

if ($action === 'add' && $item_id > 0) {
    // Get item details from database
    $item_query = "SELECT * FROM menu_items WHERE id = $item_id AND is_available = 1";
    $item_result = mysqli_query($con, $item_query);
    $item = mysqli_fetch_assoc($item_result);
    
    if ($item) {
        // Use enhanced cart manager
        require_once 'cart_manager.php';
        CartManager::initCarts();
        
        $item_data = [
            'item_id' => $item['id'],
            'item_name' => $item['name'],
            'item_type' => 'menu_item',
            'unit_price' => $item['price'],
            'quantity' => $quantity,
            'total_price' => $item['price'] * $quantity,
            'description' => $item['description'] ?? '',
            'image' => $item['image'] ?? '',
            'category_id' => $item['category_id']
        ];
        
        if (CartManager::addItemToOrderCart($item_data)) {
            $message = "Added " . htmlspecialchars($item['name']) . " to cart";
            $success = true;
        } else {
            $message = "Failed to add item to cart";
        }
    } else {
        $message = "Item not found or not available";
    }
} else {
    $message = "Invalid request";
}

// Set session message for display
$_SESSION['cart_message'] = $message;
$_SESSION['cart_message_type'] = $success ? 'success' : 'error';

// Redirect back to the referring page or default
$redirect_url = $redirect;
if (isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) {
    $redirect_url = $_SERVER['HTTP_REFERER'];
}

header("Location: $redirect_url");
exit();
?>
