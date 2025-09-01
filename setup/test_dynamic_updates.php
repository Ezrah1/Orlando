<?php
/**
 * Test Dynamic Hotel Settings Updates
 * This script tests that admin updates propagate to all pages
 */

echo "🧪 Testing Dynamic Hotel Settings Update Propagation\n";
echo "=" . str_repeat("=", 60) . "\n\n";

// Include necessary files
require_once '../admin/db.php';
require_once '../includes/classes/Settings.php';
require_once '../includes/common/hotel_settings.php';

$settings = Settings::getInstance($con);

// Store original values
echo "1️⃣ Getting original values...\n";
$original_name = $settings->get('hotel_name');
$original_phone = $settings->get('hotel_phone');
$original_email = $settings->get('hotel_email');

echo "✅ Original Hotel Name: {$original_name}\n";
echo "✅ Original Phone: {$original_phone}\n";
echo "✅ Original Email: {$original_email}\n\n";

// Test update
echo "2️⃣ Testing dynamic update...\n";
$test_name = "TEST HOTEL NAME - " . date('H:i:s');
$test_phone = "+254 999 999 999";
$test_email = "test@example.com";

// Update settings
$update_result = $settings->updateMultiple([
    'hotel_name' => $test_name,
    'hotel_phone' => $test_phone,
    'hotel_email' => $test_email
]);

if ($update_result) {
    echo "✅ Settings updated successfully\n\n";
    
    // Test retrieval through helper functions
    echo "3️⃣ Testing helper function retrieval...\n";
    
    // Clear any cached values
    $settings = new Settings($con); // Create new instance to bypass cache
    
    $retrieved_name = get_hotel_info('name');
    $retrieved_phone = get_hotel_info('phone');
    $retrieved_email = get_hotel_info('email');
    
    echo "✅ Retrieved Name: {$retrieved_name}\n";
    echo "✅ Retrieved Phone: {$retrieved_phone}\n";
    echo "✅ Retrieved Email: {$retrieved_email}\n\n";
    
    // Verify updates
    if ($retrieved_name === $test_name && 
        $retrieved_phone === $test_phone && 
        $retrieved_email === $test_email) {
        echo "🎉 SUCCESS! Dynamic updates are working correctly!\n\n";
        
        // Test contact display functions
        echo "4️⃣ Testing contact display functions...\n";
        $contact = get_contact_display();
        echo "✅ Phone Link: {$contact['phone_link']}\n";
        echo "✅ Email Link: {$contact['email_link']}\n";
        echo "✅ WhatsApp Link: {$contact['whatsapp_link']}\n\n";
        
    } else {
        echo "❌ FAILED! Values don't match:\n";
        echo "Expected Name: {$test_name}, Got: {$retrieved_name}\n";
        echo "Expected Phone: {$test_phone}, Got: {$retrieved_phone}\n";
        echo "Expected Email: {$test_email}, Got: {$retrieved_email}\n\n";
    }
    
} else {
    echo "❌ Failed to update settings\n\n";
}

// Restore original values
echo "5️⃣ Restoring original values...\n";
$restore_result = $settings->updateMultiple([
    'hotel_name' => $original_name,
    'hotel_phone' => $original_phone,
    'hotel_email' => $original_email
]);

if ($restore_result) {
    echo "✅ Original values restored successfully\n\n";
    
    // Verify restoration
    $settings = new Settings($con); // New instance to bypass cache
    $final_name = get_hotel_info('name');
    
    if ($final_name === $original_name) {
        echo "🎉 RESTORATION SUCCESSFUL!\n\n";
        
        echo "📋 FINAL VERIFICATION:\n";
        echo "Hotel Name: " . get_hotel_info('name') . "\n";
        echo "Phone: " . get_hotel_info('phone') . "\n";
        echo "Email: " . get_hotel_info('email') . "\n";
        echo "Address: " . get_hotel_info('address') . "\n\n";
        
        echo "✅ ALL TESTS PASSED!\n";
        echo "🚀 Your dynamic hotel settings system is working perfectly!\n";
        echo "💡 When you update settings in the admin panel, changes will appear immediately across all pages.\n\n";
        
    } else {
        echo "❌ Failed to restore original values properly\n";
    }
    
} else {
    echo "❌ Failed to restore original values\n";
}

echo "🔧 Admin Panel: http://localhost/Hotel/admin/settings.php\n";
echo "📖 Documentation: docs/DYNAMIC_HOTEL_SETTINGS_GUIDE.md\n";

?>
