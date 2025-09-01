<?php
/**
 * Maya Emoji Encoding Fix
 * Run this script to fix emoji display issues in Maya AI
 */

// Include database connection
require_once '../../db.php';

echo "Starting Maya emoji encoding fix...\n\n";

// Array of SQL commands to execute
$sql_commands = [
    "ALTER TABLE `ai_agents` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE `ai_knowledge_base` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci", 
    "ALTER TABLE `ai_conversations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "ALTER TABLE `ai_quick_actions` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
    "UPDATE `ai_agents` SET `avatar_emoji` = '🤖' WHERE `name` = 'Maya'"
];

$success_count = 0;
$error_count = 0;

foreach ($sql_commands as $sql) {
    echo "Executing: " . substr($sql, 0, 50) . "...\n";
    
    if (mysqli_query($con, $sql)) {
        echo "✅ Success\n";
        $success_count++;
    } else {
        echo "❌ Error: " . mysqli_error($con) . "\n";
        $error_count++;
    }
    echo "\n";
}

echo "Fix completed!\n";
echo "Successful operations: $success_count\n";
echo "Failed operations: $error_count\n\n";

if ($error_count === 0) {
    echo "🎉 All operations completed successfully!\n";
    echo "Maya's emoji should now display properly.\n";
} else {
    echo "⚠️  Some operations failed. Check the errors above.\n";
}

mysqli_close($con);
?>
