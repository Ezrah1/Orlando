<?php
session_start();
header('Content-Type: application/json');

// Include database connection and cart manager
require_once '../db.php';
require_once '../cart_manager.php';

// Include bar inventory integration if it exists
if (file_exists('../includes/bar_inventory_integration.php')) {
    require_once '../includes/bar_inventory_integration.php';
}

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
        case 'add_item_to_order_cart':
            $item_id = (int)($_POST['item_id'] ?? 0);
            $item_name = trim($_POST['item_name'] ?? '');
            $item_type = trim($_POST['item_type'] ?? '');
            $unit_price = (float)($_POST['unit_price'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 1);
            $description = trim($_POST['description'] ?? '');
            $image = trim($_POST['image'] ?? '');
            
            if ($item_id <= 0 || empty($item_name) || $unit_price <= 0 || $quantity <= 0) {
                throw new Exception('Invalid item data provided');
            }
            
            // Check if this is a bar item (starts with "bar_" or item_type contains "Bar")
            $is_bar_item = (strpos($item_id, 'bar_') === 0 || strpos($item_type, 'Bar') !== false);
            
            if ($is_bar_item && function_exists('check_bar_item_availability')) {
                // For bar items, check availability in bar inventory
                $bar_item_id = str_replace('bar_', '', $item_id);
                $availability = check_bar_item_availability($item_name, $quantity);
                
                if (!$availability['available']) {
                    throw new Exception("Insufficient stock for {$item_name}. Available: {$availability['current_stock']}");
                }
            } else {
                // For regular menu items, check in menu_items table
                $numeric_item_id = is_numeric($item_id) ? (int)$item_id : 0;
                if ($numeric_item_id > 0) {
                    $item_query = "SELECT * FROM menu_items WHERE id = $numeric_item_id AND is_available = 1";
                    $item_result = mysqli_query($con, $item_query);
                    
                    if (!$item_result || mysqli_num_rows($item_result) === 0) {
                        throw new Exception('Item not found or not available');
                    }
                }
            }
            
            $item = mysqli_fetch_assoc($item_result);
            
            // Prepare item data for cart
            $item_data = [
                'item_id' => $item_id,
                'item_name' => $item_name,
                'item_type' => $item_type,
                'unit_price' => $unit_price,
                'quantity' => $quantity,
                'total_price' => $unit_price * $quantity,
                'description' => $description,
                'image' => $image,
                'category_id' => $item['category_id']
            ];
        
        if (CartManager::addItemToOrderCart($item_data)) {
                $response['success'] = true;
                $response['message'] = "Added {$item_name} to cart";
                $response['cart_summary'] = CartManager::getOrderCartSummary();
        } else {
                throw new Exception('Failed to add item to cart');
        }
        break;
        
    case 'update_item_quantity':
            $item_id = (int)($_POST['item_id'] ?? 0);
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if ($item_id <= 0) {
                throw new Exception('Invalid item ID');
            }
        
        if (CartManager::updateItemQuantity($item_id, $quantity)) {
                $response['success'] = true;
                $response['message'] = $quantity > 0 ? 'Quantity updated' : 'Item removed from cart';
                $response['cart_summary'] = CartManager::getOrderCartSummary();
        } else {
                throw new Exception('Failed to update item quantity');
        }
        break;
        
        case 'remove_item_from_cart':
            $item_id = (int)($_POST['item_id'] ?? 0);
            
            if ($item_id <= 0) {
                throw new Exception('Invalid item ID');
            }
        
        if (CartManager::removeItemFromOrderCart($item_id)) {
                $response['success'] = true;
                $response['message'] = 'Item removed from cart';
                $response['cart_summary'] = CartManager::getOrderCartSummary();
        } else {
                throw new Exception('Failed to remove item from cart');
        }
        break;
        
        case 'get_cart_summary':
            $order_summary = CartManager::getOrderCartSummary();
            $booking_summary = CartManager::getBookingCartSummary();
            $response['success'] = true;
            $response['cart_summary'] = $order_summary; // For backward compatibility
            $response['order_summary'] = $order_summary;
            $response['booking_summary'] = $booking_summary;
            break;
            
        case 'clear_cart':
            CartManager::clearOrderCart();
            $response['success'] = true;
            $response['message'] = 'Cart cleared';
            $response['cart_summary'] = CartManager::getOrderCartSummary();
        break;
        
                    case 'validate_cart':
            $cart_items = CartManager::getOrderCart();
            $validation_errors = [];
            
            foreach ($cart_items as $item) {
                // Check if item still exists and is available
                $item_query = "SELECT is_available, price FROM menu_items WHERE id = {$item['item_id']}";
                $item_result = mysqli_query($con, $item_query);
                
                if (!$item_result || mysqli_num_rows($item_result) === 0) {
                    $validation_errors[] = "{$item['item_name']} is no longer available";
                } else {
                    $db_item = mysqli_fetch_assoc($item_result);
                    if (!$db_item['is_available']) {
                        $validation_errors[] = "{$item['item_name']} is currently unavailable";
                    }
                    if (abs($db_item['price'] - $item['unit_price']) > 0.01) {
                        $validation_errors[] = "Price for {$item['item_name']} has changed";
                    }
                }
            }
            
            $response['success'] = true;
            $response['validation_errors'] = $validation_errors;
            $response['cart_summary'] = CartManager::getOrderCartSummary();
        break;
        
    case 'get_cart_counts':
            $cart_counts = CartManager::getCartCounts();
            $order_summary = CartManager::getOrderCartSummary();
            $booking_summary = CartManager::getBookingCartSummary();
            
            $response['success'] = true;
            $response['counts'] = [
                'booking_items' => $booking_summary['rooms_count'] + $booking_summary['addons_count'],
                'order_items' => $order_summary['items_count'],
                'total_items' => $booking_summary['rooms_count'] + $booking_summary['addons_count'] + $order_summary['items_count']
        ];
        break;
        
    default:
            throw new Exception('Invalid action specified');
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Log error for debugging
    error_log("Cart API Error: " . $e->getMessage());
}

// Send JSON response
echo json_encode($response);
exit();
?>