<?php
/**
 * Global Hotel Settings Helper
 * Provides easy access to dynamic hotel settings throughout the application
 */

// Ensure database connection and Settings class are available
if (!isset($con)) {
    // Try to include database connection from various possible locations
    $possible_db_paths = [
        __DIR__ . '/../../admin/db.php',
        __DIR__ . '/../../db.php',
        $_SERVER['DOCUMENT_ROOT'] . '/Hotel/admin/db.php',
        $_SERVER['DOCUMENT_ROOT'] . '/Hotel/db.php'
    ];
    
    foreach ($possible_db_paths as $db_path) {
        if (file_exists($db_path)) {
            require_once $db_path;
            break;
        }
    }
}

if (!class_exists('Settings')) {
    require_once __DIR__ . '/../classes/Settings.php';
}

// Global function to get hotel information
if (!function_exists('get_hotel_info')) {
    function get_hotel_info($key = null) {
        global $con;
        if (!$con) return null;
        
        static $hotel_info = null;
        if ($hotel_info === null) {
            $settings = Settings::getInstance($con);
            $hotel_info = $settings->getHotelInfo();
        }
        
        return $key ? ($hotel_info[$key] ?? null) : $hotel_info;
    }
}

// Global function to get business configuration
if (!function_exists('get_business_config')) {
    function get_business_config($key = null) {
        global $con;
        if (!$con) return null;
        
        static $business_config = null;
        if ($business_config === null) {
            $settings = Settings::getInstance($con);
            $business_config = $settings->getBusinessConfig();
        }
        
        return $key ? ($business_config[$key] ?? null) : $business_config;
    }
}

// Global function to get contact display information
if (!function_exists('get_contact_display')) {
    function get_contact_display($key = null) {
        global $con;
        if (!$con) return null;
        
        static $contact_display = null;
        if ($contact_display === null) {
            $settings = Settings::getInstance($con);
            $contact_display = $settings->getContactDisplay();
        }
        
        return $key ? ($contact_display[$key] ?? null) : $contact_display;
    }
}

// Global function to get any setting value
if (!function_exists('get_setting')) {
    function get_setting($key, $default = null) {
        global $con;
        if (!$con) return $default;
        
        $settings = Settings::getInstance($con);
        return $settings->get($key, $default);
    }
}

// Global function to format currency
if (!function_exists('format_currency')) {
    function format_currency($amount, $show_symbol = true) {
        $currency_symbol = get_business_config('currency_symbol') ?: 'KES';
        $formatted = number_format($amount, 0);
        
        return $show_symbol ? $currency_symbol . ' ' . $formatted : $formatted;
    }
}

// Global function to get formatted phone number for links
if (!function_exists('get_phone_link')) {
    function get_phone_link($type = 'main') {
        $contact = get_contact_display();
        if (!$contact) return '#';
        
        switch ($type) {
            case 'whatsapp':
                return $contact['whatsapp_link'] ?? '#';
            case 'main':
            default:
                return $contact['phone_link'] ?? '#';
        }
    }
}

// Global function to get email link
if (!function_exists('get_email_link')) {
    function get_email_link() {
        $contact = get_contact_display();
        return $contact ? $contact['email_link'] : '#';
    }
}

// Quick access variables for templates (only set if not already set)
if (!isset($HOTEL_NAME)) {
    $HOTEL_NAME = get_hotel_info('name');
    $HOTEL_PHONE = get_hotel_info('phone');
    $HOTEL_EMAIL = get_hotel_info('email');
    $HOTEL_ADDRESS = get_hotel_info('address');
    $HOTEL_WHATSAPP = get_hotel_info('whatsapp');
    $CURRENCY_SYMBOL = get_business_config('currency_symbol');
}

?>
