<?php
// Test file for InfinityFree connection
echo "<h1>🚀 Hotel Management System - InfinityFree Test</h1>";

// Test database connection
try {
    $con = mysqli_connect("sql300.infinityfree.com", "if0_39842749", "6LLK5O9akZAKiZi", "if0_39842749_XXX");
    
    if ($con) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";
        
        // Test query
        $result = mysqli_query($con, "SHOW TABLES");
        if ($result) {
            $table_count = mysqli_num_rows($result);
            echo "<p>📊 Found $table_count tables in database</p>";
            
            echo "<h3>Tables found:</h3><ul>";
            while ($row = mysqli_fetch_array($result)) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        }
        
        mysqli_close($con);
    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

// Test PHP info
echo "<h3>🔧 PHP Information:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Server: " . $_SERVER['SERVER_SOFTWARE'] . "</p>";

// Test file permissions
echo "<h3>📁 File Permissions Test:</h3>";
$test_file = "test_write.txt";
if (file_put_contents($test_file, "Test write access")) {
    echo "<p style='color: green;'>✅ File write access working</p>";
    unlink($test_file); // Clean up
} else {
    echo "<p style='color: red;'>❌ File write access failed</p>";
}

echo "<hr>";
echo "<p><strong>🎯 If you see this page, your Hotel Management System is ready to upload!</strong></p>";
echo "<p>Next steps:</p>";
echo "<ol>";
echo "<li>Upload all files via FileZilla</li>";
echo "<li>Import your database</li>";
echo "<li>Test the full system</li>";
echo "</ol>";
?>
