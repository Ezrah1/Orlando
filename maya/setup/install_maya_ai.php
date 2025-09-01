<?php
/**
 * Maya AI System Installation Script
 * Orlando International Resorts
 */

require_once '../db.php';

echo "<h2>Installing Maya AI System...</h2>";

try {
    // Read and execute the Maya AI SQL file
    $sql_file = '../database/maya_ai_system.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: $sql_file");
    }
    
    $sql_content = file_get_contents($sql_file);
    
    // Split the SQL into individual queries
    $queries = array_filter(array_map('trim', explode(';', $sql_content)));
    
    $success_count = 0;
    $total_queries = count($queries);
    
    echo "<p>Executing $total_queries SQL statements...</p>";
    echo "<ul>";
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, '--') === 0) {
            continue; // Skip empty queries and comments
        }
        
        if (mysqli_query($con, $query)) {
            $success_count++;
            
            // Show what we're creating
            if (stripos($query, 'CREATE TABLE') !== false) {
                preg_match('/CREATE TABLE `?([^`\s]+)`?/i', $query, $matches);
                $table_name = $matches[1] ?? 'Unknown';
                echo "<li style='color: green;'>✅ Created table: <strong>$table_name</strong></li>";
            } elseif (stripos($query, 'INSERT INTO') !== false) {
                preg_match('/INSERT INTO `?([^`\s]+)`?/i', $query, $matches);
                $table_name = $matches[1] ?? 'Unknown';
                echo "<li style='color: blue;'>📝 Inserted data into: <strong>$table_name</strong></li>";
            } elseif (stripos($query, 'CREATE INDEX') !== false) {
                echo "<li style='color: purple;'>🔍 Created database index</li>";
            }
        } else {
            echo "<li style='color: red;'>❌ Error: " . mysqli_error($con) . "</li>";
            echo "<li style='color: orange;'>Query: " . htmlspecialchars(substr($query, 0, 100)) . "...</li>";
        }
    }
    
    echo "</ul>";
    
    if ($success_count > 0) {
        echo "<div style='background: #d4edda; padding: 20px; border-radius: 10px; border-left: 5px solid #28a745; margin: 20px 0;'>";
        echo "<h3 style='color: #155724; margin: 0 0 10px 0;'>🎉 Maya AI System Installed Successfully!</h3>";
        echo "<p style='color: #155724; margin: 0;'>Executed $success_count SQL statements successfully.</p>";
        echo "</div>";
        
        // Verify installation
        echo "<h3>Verifying Installation:</h3>";
        echo "<ul>";
        
        $tables_to_check = ['ai_agents', 'ai_knowledge_base', 'ai_conversations', 'ai_quick_actions'];
        
        foreach ($tables_to_check as $table) {
            $check_query = "SELECT COUNT(*) as count FROM $table";
            $result = mysqli_query($con, $check_query);
            
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                $count = $row['count'];
                echo "<li style='color: green;'>✅ Table <strong>$table</strong>: $count records</li>";
            } else {
                echo "<li style='color: red;'>❌ Table <strong>$table</strong>: Error checking</li>";
            }
        }
        
        echo "</ul>";
        
        // Show Maya's knowledge base
        echo "<h3>Maya's Knowledge Base:</h3>";
        $knowledge_query = "SELECT category, COUNT(*) as count FROM ai_knowledge_base GROUP BY category ORDER BY count DESC";
        $result = mysqli_query($con, $knowledge_query);
        
        if ($result) {
            echo "<ul>";
            while ($row = mysqli_fetch_assoc($result)) {
                echo "<li><strong>" . ucfirst($row['category']) . ":</strong> " . $row['count'] . " responses</li>";
            }
            echo "</ul>";
        }
        
        echo "<div style='background: #fff3cd; padding: 15px; border-radius: 10px; border-left: 5px solid #ffc107; margin: 20px 0;'>";
        echo "<h4 style='color: #856404; margin: 0 0 10px 0;'>🤖 Maya is now ready!</h4>";
        echo "<p style='color: #856404; margin: 0;'>Maya AI assistant is now available on your website with a floating chat button. Users can interact with Maya for booking assistance, room information, and general inquiries.</p>";
        echo "</div>";
        
        echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 10px; border-left: 5px solid #17a2b8; margin: 20px 0;'>";
        echo "<h4 style='color: #0c5460; margin: 0 0 10px 0;'>📊 What's Included:</h4>";
        echo "<ul style='color: #0c5460; margin: 0;'>";
        echo "<li><strong>AI Agent:</strong> Maya with personality and role definition</li>";
        echo "<li><strong>Knowledge Base:</strong> 12+ categories with smart responses</li>";
        echo "<li><strong>Quick Actions:</strong> Context-aware button suggestions</li>";
        echo "<li><strong>Conversation Logging:</strong> Track all interactions</li>";
        echo "<li><strong>Floating Widget:</strong> Available on all pages</li>";
        echo "<li><strong>Room-Specific Chat:</strong> Dedicated booking assistance</li>";
        echo "</ul>";
        echo "</div>";
        
    } else {
        echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545; margin: 20px 0;'>";
        echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Installation Failed</h3>";
        echo "<p style='color: #721c24; margin: 0;'>No queries were executed successfully. Please check the error messages above.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 20px; border-radius: 10px; border-left: 5px solid #dc3545; margin: 20px 0;'>";
    echo "<h3 style='color: #721c24; margin: 0 0 10px 0;'>❌ Installation Error</h3>";
    echo "<p style='color: #721c24; margin: 0;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='../index.php' style='background: #0f2453; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏠 Go to Homepage</a></p>";
echo "<p><a href='../rooms.php' style='background: #ffce14; color: #0f2453; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🏨 Try Maya on Rooms Page</a></p>";
?>
