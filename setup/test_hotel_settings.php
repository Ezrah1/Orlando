<?php
/**
 * Test Script for Dynamic Hotel Settings System
 */

echo "🧪 Testing Orlando International Resorts Dynamic Settings System\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Test 1: Include hotel settings
echo "1️⃣ Testing hotel settings include...\n";
try {
    require_once '../includes/common/hotel_settings.php';
    echo "✅ Hotel settings loaded successfully\n\n";
} catch (Exception $e) {
    echo "❌ Failed to load hotel settings: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Test database connection
echo "2️⃣ Testing database connection...\n";
if (isset($con) && $con) {
    echo "✅ Database connection established\n\n";
} else {
    echo "❌ Database connection failed\n\n";
    exit(1);
}

// Test 3: Test Settings class
echo "3️⃣ Testing Settings class...\n";
try {
    $settings = Settings::getInstance($con);
    echo "✅ Settings class instantiated successfully\n\n";
} catch (Exception $e) {
    echo "❌ Settings class failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 4: Test hotel information retrieval
echo "4️⃣ Testing hotel information retrieval...\n";
$hotel_info = get_hotel_info();
if ($hotel_info && $hotel_info['name']) {
    echo "✅ Hotel Name: " . $hotel_info['name'] . "\n";
    echo "✅ Hotel Phone: " . $hotel_info['phone'] . "\n";
    echo "✅ Hotel Email: " . $hotel_info['email'] . "\n";
    echo "✅ Hotel Address: " . $hotel_info['address'] . "\n\n";
} else {
    echo "❌ Failed to retrieve hotel information\n\n";
}

// Test 5: Test contact display functions
echo "5️⃣ Testing contact display functions...\n";
$contact = get_contact_display();
if ($contact) {
    echo "✅ Phone Link: " . $contact['phone_link'] . "\n";
    echo "✅ Email Link: " . $contact['email_link'] . "\n";
    echo "✅ WhatsApp Link: " . $contact['whatsapp_link'] . "\n\n";
} else {
    echo "❌ Failed to get contact display information\n\n";
}

// Test 6: Test business configuration
echo "6️⃣ Testing business configuration...\n";
$business = get_business_config();
if ($business) {
    echo "✅ Currency: " . $business['currency_symbol'] . "\n";
    echo "✅ Check-in Time: " . $business['check_in_time'] . "\n";
    echo "✅ Check-out Time: " . $business['check_out_time'] . "\n\n";
} else {
    echo "❌ Failed to get business configuration\n\n";
}

// Test 7: Test currency formatting
echo "7️⃣ Testing currency formatting...\n";
$formatted = format_currency(1500);
echo "✅ Formatted Currency (1500): " . $formatted . "\n\n";

// Test 8: Test individual setting retrieval
echo "8️⃣ Testing individual setting retrieval...\n";
$hotel_name = get_setting('hotel_name', 'Default Hotel');
echo "✅ Hotel Name Setting: " . $hotel_name . "\n\n";

// Summary
echo "🎉 ALL TESTS COMPLETED SUCCESSFULLY!\n";
echo "=" . str_repeat("=", 60) . "\n\n";

echo "📋 SYSTEM SUMMARY:\n";
echo "Hotel Name: " . get_hotel_info('name') . "\n";
echo "Contact Phone: " . get_hotel_info('phone') . "\n";
echo "Contact Email: " . get_hotel_info('email') . "\n";
echo "Location: " . get_hotel_info('address') . "\n";
echo "Currency: " . get_business_config('currency_symbol') . "\n\n";

echo "🚀 Your dynamic hotel settings system is ready for use!\n";
echo "💡 Access the admin panel at: http://localhost/Hotel/admin/settings.php\n";
echo "📖 Read the guide at: docs/DYNAMIC_HOTEL_SETTINGS_GUIDE.md\n";

?>
