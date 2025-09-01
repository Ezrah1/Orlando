<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class CartManager {
    
    // Initialize carts if they don't exist
    public static function initCarts() {
        if (!isset($_SESSION['booking_cart'])) {
            $_SESSION['booking_cart'] = [];
        }
        if (!isset($_SESSION['order_cart'])) {
            $_SESSION['order_cart'] = [];
        }
    }
    
    // ===== ROOM BOOKING CART METHODS =====
    
    public static function addRoomToCart($room_data) {
        self::initCarts();
        
        $room_id = $room_data['room_name']; // Using room_name as unique identifier
        
        // Check if room already exists in cart
        foreach ($_SESSION['booking_cart'] as $index => $item) {
            if ($item['room_name'] === $room_id) {
                // Update existing room with new dates if different
                if ($item['check_in'] !== $room_data['check_in'] || $item['check_out'] !== $room_data['check_out']) {
                    $_SESSION['booking_cart'][$index] = $room_data;
                }
                return true;
            }
        }
        
        // Add new room to cart
        $_SESSION['booking_cart'][] = $room_data;
        return true;
    }
    
    public static function removeRoomFromCart($room_name) {
        self::initCarts();
        
        foreach ($_SESSION['booking_cart'] as $index => $item) {
            if ($item['room_name'] === $room_name) {
                unset($_SESSION['booking_cart'][$index]);
                $_SESSION['booking_cart'] = array_values($_SESSION['booking_cart']); // Reindex array
                return true;
            }
        }
        return false;
    }
    
    public static function updateRoomDates($room_name, $check_in, $check_out) {
        self::initCarts();
        
        foreach ($_SESSION['booking_cart'] as $index => $item) {
            if ($item['room_name'] === $room_name) {
                $_SESSION['booking_cart'][$index]['check_in'] = $check_in;
                $_SESSION['booking_cart'][$index]['check_out'] = $check_out;
                
                // Recalculate days and total
                $check_in_date = new DateTime($check_in);
                $check_out_date = new DateTime($check_out);
                $days = $check_in_date->diff($check_out_date)->days;
                
                $_SESSION['booking_cart'][$index]['days'] = $days;
                $_SESSION['booking_cart'][$index]['total'] = $item['base_price'] * $days;
                
                return true;
            }
        }
        return false;
    }
    
    public static function updateCartDates($check_in, $check_out) {
        self::initCarts();
        
        if (empty($_SESSION['booking_cart'])) {
            return false;
        }
        
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $days = $check_in_date->diff($check_out_date)->days;
        
        foreach ($_SESSION['booking_cart'] as $index => $item) {
            $_SESSION['booking_cart'][$index]['check_in'] = $check_in;
            $_SESSION['booking_cart'][$index]['check_out'] = $check_out;
            $_SESSION['booking_cart'][$index]['days'] = $days;
            $_SESSION['booking_cart'][$index]['total'] = $item['base_price'] * $days;
        }
        
        return true;
    }
    
    public static function getBookingCart() {
        self::initCarts();
        return $_SESSION['booking_cart'];
    }
    
    public static function getBookingCartTotal() {
        self::initCarts();
        $total = 0;
        foreach ($_SESSION['booking_cart'] as $item) {
            $total += $item['total'] ?? 0;
        }
        return $total;
    }
    
    public static function clearBookingCart() {
        self::initCarts();
        $_SESSION['booking_cart'] = [];
    }
    
    // ===== FOOD/BAR ORDER CART METHODS =====
    
    public static function addItemToOrderCart($item_data) {
        self::initCarts();
        
        $item_id = $item_data['item_id'];
        
        // Validate required fields
        if (!isset($item_data['item_id'], $item_data['item_name'], $item_data['unit_price'], $item_data['quantity'])) {
            return false;
        }
        
        // Check if item already exists in cart
        foreach ($_SESSION['order_cart'] as $index => $item) {
            if ($item['item_id'] === $item_id) {
                // Update quantity and recalculate total
                $_SESSION['order_cart'][$index]['quantity'] += $item_data['quantity'];
                $_SESSION['order_cart'][$index]['total_price'] = $_SESSION['order_cart'][$index]['quantity'] * $_SESSION['order_cart'][$index]['unit_price'];
                return true;
            }
        }
        
        // Ensure total_price is calculated
        $item_data['total_price'] = $item_data['unit_price'] * $item_data['quantity'];
        
        // Add new item to cart
        $_SESSION['order_cart'][] = $item_data;
        return true;
    }
    
    public static function updateItemQuantity($item_id, $quantity) {
        self::initCarts();
        
        foreach ($_SESSION['order_cart'] as $index => $item) {
            if ($item['item_id'] === $item_id) {
                if ($quantity <= 0) {
                    unset($_SESSION['order_cart'][$index]);
                    $_SESSION['order_cart'] = array_values($_SESSION['order_cart']);
                } else {
                    $_SESSION['order_cart'][$index]['quantity'] = (int)$quantity;
                    $_SESSION['order_cart'][$index]['total_price'] = $quantity * $item['unit_price'];
                }
                return true;
            }
        }
        return false;
    }
    
    public static function removeItemFromOrderCart($item_id) {
        self::initCarts();
        
        foreach ($_SESSION['order_cart'] as $index => $item) {
            if ($item['item_id'] === $item_id) {
                unset($_SESSION['order_cart'][$index]);
                $_SESSION['order_cart'] = array_values($_SESSION['order_cart']);
                return true;
            }
        }
        return false;
    }
    
    public static function getOrderCart() {
        self::initCarts();
        return $_SESSION['order_cart'];
    }
    
    public static function getOrderCartTotal() {
        self::initCarts();
        $total = 0;
        foreach ($_SESSION['order_cart'] as $item) {
            $total += $item['total_price'] ?? 0;
        }
        return $total;
    }
    
    public static function clearOrderCart() {
        self::initCarts();
        $_SESSION['order_cart'] = [];
    }
    
    // ===== ADD-ONS MANAGEMENT =====
    
    public static function addAddonToBooking($addon_data) {
        self::initCarts();
        
        if (!isset($_SESSION['booking_addons'])) {
            $_SESSION['booking_addons'] = [];
        }
        
        $addon_id = $addon_data['addon_id'];
        
        // Check if addon already exists
        foreach ($_SESSION['booking_addons'] as $index => $addon) {
            if ($addon['addon_id'] === $addon_id) {
                $_SESSION['booking_addons'][$index] = $addon_data;
                return true;
            }
        }
        
        // Add new addon
        $_SESSION['booking_addons'][] = $addon_data;
        return true;
    }
    
    public static function removeAddonFromBooking($addon_id) {
        self::initCarts();
        
        if (!isset($_SESSION['booking_addons'])) {
            return false;
        }
        
        foreach ($_SESSION['booking_addons'] as $index => $addon) {
            if ($addon['addon_id'] === $addon_id) {
                unset($_SESSION['booking_addons'][$index]);
                $_SESSION['booking_addons'] = array_values($_SESSION['booking_addons']);
                return true;
            }
        }
        return false;
    }
    
    public static function getBookingAddons() {
        self::initCarts();
        return $_SESSION['booking_addons'] ?? [];
    }
    
    public static function getBookingAddonsTotal() {
        self::initCarts();
        $total = 0;
        foreach ($_SESSION['booking_addons'] ?? [] as $addon) {
            $total += $addon['price'] ?? 0;
        }
        return $total;
    }
    
    public static function clearBookingAddons() {
        self::initCarts();
        $_SESSION['booking_addons'] = [];
    }
    
    // ===== CART SUMMARY METHODS =====
    
    public static function getBookingCartSummary() {
        self::initCarts();
        
        $rooms = self::getBookingCart();
        $addons = self::getBookingAddons();
        $rooms_total = self::getBookingCartTotal();
        $addons_total = self::getBookingAddonsTotal();
        $grand_total = $rooms_total + $addons_total;
        
        return [
            'rooms' => $rooms,
            'addons' => $addons,
            'rooms_total' => $rooms_total,
            'addons_total' => $addons_total,
            'grand_total' => $grand_total,
            'rooms_count' => count($rooms),
            'addons_count' => count($addons)
        ];
    }
    
    public static function getOrderCartSummary() {
        self::initCarts();
        
        $items = self::getOrderCart();
        $subtotal = self::getOrderCartTotal();
        $tax = $subtotal * 0.16; // 16% VAT
        $grand_total = $subtotal + $tax;
        
        return [
            'items' => $items,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'grand_total' => $grand_total,
            'items_count' => count($items)
        ];
    }
    
    // ===== UTILITY METHODS =====
    
    public static function hasBookingCart() {
        self::initCarts();
        return !empty($_SESSION['booking_cart']);
    }
    
    public static function hasOrderCart() {
        self::initCarts();
        return !empty($_SESSION['order_cart']);
    }
    
    public static function getCartCounts() {
        self::initCarts();
        return [
            'booking' => count($_SESSION['booking_cart']),
            'order' => count($_SESSION['order_cart'])
        ];
    }
}
?>
