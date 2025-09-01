<?php
/**
 * Initialize Hotel Settings for Orlando International Resorts
 * This script sets up the dynamic hotel details system with your specific information
 */

require_once '../admin/db.php';
require_once '../includes/classes/Settings.php';

echo "Initializing Orlando International Resorts Settings...\n";

// Initialize settings manager
$settings = new Settings($con);

// Your specific hotel details
$hotel_settings = [
    // Core hotel information
    'hotel_name' => 'Orlando International Resorts',
    'hotel_address' => 'Machakos Town, Kenya',
    'hotel_phone' => '+254 742 824 006',
    'hotel_email' => 'info@orlandointernationalresort.net',
    'hotel_website' => 'https://www.orlandointernationalresort.net',
    'hotel_description' => 'Located in the heart of Machakos Town, Orlando International Resorts offers the perfect blend of luxury and affordability. Our uniquely named rooms provide comfortable accommodation for business travelers, families, and tourists.',
    
    // Additional contact details
    'hotel_city' => 'Machakos',
    'hotel_state' => 'Machakos County',
    'hotel_country' => 'Kenya',
    'hotel_postal_code' => '90100',
    'hotel_whatsapp' => '+254742824006',
    'hotel_facebook' => '',
    'hotel_instagram' => '',
    'hotel_twitter' => '',
    
    // Business details
    'hotel_registration_number' => '',
    'hotel_tax_id' => '',
    'hotel_license_number' => '',
];

// Business settings
$business_settings = [
    'check_in_time' => '14:00',
    'check_out_time' => '11:00',
    'currency_symbol' => 'KES',
    'currency_code' => 'KES',
    'tax_rate' => '16.00',
    'service_charge' => '10.00',
    'cancellation_policy' => 'Free cancellation up to 24 hours before check-in. Late cancellations and no-shows will be charged one night stay.',
    'payment_methods' => 'M-Pesa, Cash, Bank Transfer, Credit/Debit Cards',
    'min_advance_booking_hours' => '2',
    'max_advance_booking_days' => '365',
];

// Update hotel settings
echo "Setting hotel information...\n";
foreach ($hotel_settings as $key => $value) {
    $success = $settings->set($key, $value, 'text', 'hotel', '', 1); // Make public
    if ($success) {
        echo "✓ Set {$key}: {$value}\n";
    } else {
        echo "✗ Failed to set {$key}\n";
    }
}

// Update business settings
echo "\nSetting business configuration...\n";
foreach ($business_settings as $key => $value) {
    $public = in_array($key, ['check_in_time', 'check_out_time', 'currency_symbol', 'currency_code', 'cancellation_policy', 'payment_methods']) ? 1 : 0;
    $success = $settings->set($key, $value, 'text', 'business', '', $public);
    if ($success) {
        echo "✓ Set {$key}: {$value}\n";
    } else {
        echo "✗ Failed to set {$key}\n";
    }
}

// Set some additional system settings
$system_settings = [
    'timezone' => 'Africa/Nairobi',
    'date_format' => 'Y-m-d',
    'time_format' => 'H:i',
    'language' => 'en',
    'maintenance_mode' => '0',
    'allow_guest_registration' => '1',
];

echo "\nSetting system configuration...\n";
foreach ($system_settings as $key => $value) {
    $success = $settings->set($key, $value, 'text', 'system', '', 0);
    if ($success) {
        echo "✓ Set {$key}: {$value}\n";
    } else {
        echo "✗ Failed to set {$key}\n";
    }
}

echo "\n✅ Hotel settings initialization completed!\n";
echo "You can now access these settings through the admin panel at: /admin/settings.php\n";
echo "Or programmatically using the Settings class throughout your application.\n";

// Test retrieval
echo "\n--- Testing Settings Retrieval ---\n";
echo "Hotel Name: " . $settings->get('hotel_name') . "\n";
echo "Hotel Phone: " . $settings->get('hotel_phone') . "\n";
echo "Hotel Email: " . $settings->get('hotel_email') . "\n";
echo "Hotel Address: " . $settings->get('hotel_address') . "\n";

?>
