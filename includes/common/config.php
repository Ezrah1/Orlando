<?php
/**
 * Common Configuration File
 * Shared settings and paths for all user types
 */

// Define application root and URL paths
define('APP_ROOT', dirname(dirname(__DIR__)));
define('APP_URL', '/Hotel');

// Database configuration
require_once APP_ROOT . '/db.php';

// Session is handled by the main header files that include this config

// Helper function to calculate path prefix
function get_path_prefix() {
    $current_dir = dirname($_SERVER['SCRIPT_NAME']);
    $root_dir = APP_URL;
    
    if ($current_dir === $root_dir) {
        return '';
    } else {
        $relative_path = str_replace($root_dir, '', $current_dir);
        $depth = substr_count($relative_path, '/');
        return str_repeat('../', $depth);
    }
}

// Set global path prefix
$GLOBALS['path_prefix'] = get_path_prefix();

// Common functions
function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// format_currency function is now handled by hotel_settings.php for dynamic currency support
// This function is left as a fallback for compatibility
if (!function_exists('format_currency')) {
    function format_currency($amount, $show_symbol = true) {
        $formatted = number_format($amount, 2);
        return $show_symbol ? 'KSh ' . $formatted : $formatted;
    }
}

function format_date($date) {
    return date('M j, Y', strtotime($date));
}

function format_datetime($datetime) {
    return date('M j, Y \a\t g:i A', strtotime($datetime));
}

// Application constants
define('SITE_NAME', 'Orlando International Resorts');
define('SITE_TAGLINE', 'Luxury Meets Affordability');
define('SITE_EMAIL', 'info@orlandointernationalresort.net');
define('SITE_PHONE', '+254 742 824 006');
define('SITE_ADDRESS', 'Machakos Town, Kenya');

// Default page titles
$default_page_titles = [
    'guest' => SITE_NAME . ' - ' . SITE_TAGLINE,
    'admin' => 'Admin Dashboard - ' . SITE_NAME,
    'staff' => 'Staff Portal - ' . SITE_NAME
];

// Set page title if not already set
if (!isset($page_title)) {
    $user_type = isset($_SESSION['user_id']) ? 'admin' : 'guest';
    $page_title = $default_page_titles[$user_type];
}
?>
