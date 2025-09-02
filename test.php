<?php
// Simple test file to check basic functionality
echo "<h1>Hotel System Test</h1>";

// Test 1: PHP is working
echo "<p>✅ PHP is working</p>";

// Test 2: Check if database connection file exists
if (file_exists('db.php')) {
    echo "<p>✅ db.php exists</p>";
} else {
    echo "<p>❌ db.php not found</p>";
}

// Test 3: Check if includes directory exists
if (is_dir('includes')) {
    echo "<p>✅ includes directory exists</p>";
} else {
    echo "<p>❌ includes directory not found</p>";
}

// Test 4: Check if Settings class file exists
if (file_exists('includes/classes/Settings.php')) {
    echo "<p>✅ Settings.php exists</p>";
} else {
    echo "<p>❌ Settings.php not found</p>";
}

// Test 5: Try to include database connection
try {
    require_once 'db.php';
    if (isset($con)) {
        echo "<p>✅ Database connection established</p>";
        
        // Test 6: Check if database has tables
        $result = mysqli_query($con, "SHOW TABLES");
        if ($result) {
            $table_count = mysqli_num_rows($result);
            echo "<p>✅ Database has {$table_count} tables</p>";
        } else {
            echo "<p>❌ Could not query database tables</p>";
        }
    } else {
        echo "<p>❌ Database connection variable not set</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error including db.php: " . $e->getMessage() . "</p>";
}

// Test 7: Check if hotel_settings.php can be included
try {
    require_once 'includes/common/hotel_settings.php';
    echo "<p>✅ hotel_settings.php included successfully</p>";
    
    // Test 8: Try to get hotel info
    if (function_exists('get_hotel_info')) {
        $hotel_name = get_hotel_info('name');
        if ($hotel_name) {
            echo "<p>✅ Hotel name retrieved: {$hotel_name}</p>";
        } else {
            echo "<p>⚠️ Hotel name function exists but returned no value</p>";
        }
    } else {
        echo "<p>❌ get_hotel_info function not found</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Error including hotel_settings.php: " . $e->getMessage() . "</p>";
}

// Test 9: Check web server info
echo "<h2>Server Information</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server Software:</strong> " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</p>";
echo "<p><strong>Document Root:</strong> " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</p>";
echo "<p><strong>Current Script:</strong> " . ($_SERVER['SCRIPT_NAME'] ?? 'Unknown') . "</p>";

// Test 10: Check for common issues
echo "<h2>Common Issues Check</h2>";

// Check if .htaccess is being read
if (function_exists('apache_get_modules')) {
    $modules = apache_get_modules();
    if (in_array('mod_rewrite', $modules)) {
        echo "<p>✅ mod_rewrite is enabled</p>";
    } else {
        echo "<p>❌ mod_rewrite is not enabled</p>";
    }
} else {
    echo "<p>⚠️ Cannot check Apache modules (not Apache server)</p>";
}

// Check file permissions
$test_files = ['index.php', 'db.php', 'includes/header.php'];
foreach ($test_files as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p>✅ {$file} exists (permissions: {$perms})</p>";
    } else {
        echo "<p>❌ {$file} not found</p>";
    }
}

echo "<hr>";
echo "<p><strong>Test completed. Check the results above for any issues.</strong></p>";
?>
