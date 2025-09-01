<?php
include 'db.php';

echo "<h2>Setting up User Preferences...</h2>";

// Create user_preferences table
$sql1 = "CREATE TABLE IF NOT EXISTS `user_preferences` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `theme` varchar(50) DEFAULT 'default',
  `language` varchar(10) DEFAULT 'en',
  `notifications_enabled` tinyint(1) DEFAULT 1,
  `email_alerts` tinyint(1) DEFAULT 1,
  `dashboard_layout` varchar(50) DEFAULT 'default',
  `auto_refresh` tinyint(1) DEFAULT 0,
  `refresh_interval` int(11) DEFAULT 30,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_id` (`user_id`),
  KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($con->query($sql1)) {
    echo "<p>✓ User preferences table created successfully</p>";
} else {
    echo "<p>✗ Error creating user preferences table: " . $con->error . "</p>";
}

// Insert default preferences for existing users
$sql2 = "INSERT IGNORE INTO `user_preferences` (`user_id`, `theme`, `language`, `notifications_enabled`, `email_alerts`, `dashboard_layout`, `auto_refresh`, `refresh_interval`)
SELECT
    u.id,
    'default',
    'en',
    1,
    1,
    'default',
    0,
    30
FROM `users` u
WHERE NOT EXISTS (
    SELECT 1 FROM `user_preferences` up WHERE up.user_id = u.id
)";

if ($con->query($sql2)) {
    echo "<p>✓ Default preferences inserted for existing users</p>";
} else {
    echo "<p>✗ Error inserting default preferences: " . $con->error . "</p>";
}

// Add user preferences columns to users table if they don't exist
$sql3 = "ALTER TABLE `users`
ADD COLUMN IF NOT EXISTS `last_login` datetime NULL,
ADD COLUMN IF NOT EXISTS `login_count` int(11) DEFAULT 0,
ADD COLUMN IF NOT EXISTS `preferences_updated` datetime NULL";

if ($con->query($sql3)) {
    echo "<p>✓ User table columns added successfully</p>";
} else {
    echo "<p>✗ Error adding user table columns: " . $con->error . "</p>";
}

// Update existing users with default values
$sql4 = "UPDATE `users` SET
    `last_login` = NOW(),
    `login_count` = 1,
    `preferences_updated` = NOW()
WHERE `last_login` IS NULL";

if ($con->query($sql4)) {
    echo "<p>✓ User table updated with default values</p>";
} else {
    echo "<p>✗ Error updating user table: " . $con->error . "</p>";
}

echo "<h3>Setup Complete!</h3>";
echo "<p><a href='admin/home.php'>Go to Admin Dashboard</a></p>";
?>
