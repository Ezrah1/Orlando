<?php
/**
 * Auto Setup Script for Hotel Settings
 * Checks if settings exist and initializes them if needed
 */

echo "🔧 Orlando International Resorts - Auto Setup\n";
echo "=" . str_repeat("=", 50) . "\n\n";

// Include database connection
require_once '../admin/db.php';

// Check if system_settings table exists
$table_check = mysqli_query($con, "SHOW TABLES LIKE 'system_settings'");
if (mysqli_num_rows($table_check) == 0) {
    echo "❌ system_settings table not found!\n";
    echo "Please run the SQL file: database/create_system_settings_table.sql\n";
    exit(1);
}

echo "✅ system_settings table found\n";

// Check if hotel settings exist
$settings_check = mysqli_query($con, "SELECT COUNT(*) as count FROM system_settings WHERE category = 'hotel'");
$settings_count = mysqli_fetch_assoc($settings_check)['count'];

if ($settings_count > 0) {
    echo "✅ Hotel settings already exist ({$settings_count} settings found)\n";
    echo "Settings are ready to use!\n\n";
    
    // Show current hotel name
    $name_query = mysqli_query($con, "SELECT setting_value FROM system_settings WHERE setting_key = 'hotel_name'");
    if ($name_row = mysqli_fetch_assoc($name_query)) {
        echo "Current Hotel Name: " . $name_row['setting_value'] . "\n";
    }
    
    echo "\n🎯 Access admin panel: http://localhost/Hotel/admin/settings.php\n";
} else {
    echo "⚠️ No hotel settings found. Initializing with your details...\n\n";
    
    // Run initialization
    include 'initialize_hotel_settings.php';
    
    echo "\n🎉 Setup completed!\n";
    echo "🎯 Access admin panel: http://localhost/Hotel/admin/settings.php\n";
}

echo "\n📖 Full documentation: docs/DYNAMIC_HOTEL_SETTINGS_GUIDE.md\n";

?>
