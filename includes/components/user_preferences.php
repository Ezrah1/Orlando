<?php
/**
 * User Preferences Component
 * Handles user-specific settings and theme preferences
 */

// Ensure we have user session
if (!isset($_SESSION['user_id'])) {
    return;
}

/**
 * Get user preference value
 */
function getUserPreference($key, $default = null) {
    global $con;
    
    if (!isset($_SESSION['user_id'])) {
        return $default;
    }
    
    $stmt = mysqli_prepare($con, "SELECT preference_value FROM user_preferences WHERE user_id = ? AND preference_key = ?");
    mysqli_stmt_bind_param($stmt, "is", $_SESSION['user_id'], $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['preference_value'];
    }
    
    return $default;
}

/**
 * Set user preference value
 */
function setUserPreference($key, $value) {
    global $con;
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    $stmt = mysqli_prepare($con, "
        INSERT INTO user_preferences (user_id, preference_key, preference_value) 
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE 
        preference_value = VALUES(preference_value),
        updated_at = CURRENT_TIMESTAMP
    ");
    
    mysqli_stmt_bind_param($stmt, "iss", $_SESSION['user_id'], $key, $value);
    return mysqli_stmt_execute($stmt);
}

/**
 * Get all user preferences
 */
function getAllUserPreferences() {
    global $con;
    
    if (!isset($_SESSION['user_id'])) {
        return [];
    }
    
    $stmt = mysqli_prepare($con, "SELECT preference_key, preference_value FROM user_preferences WHERE user_id = ?");
    mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $preferences = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $preferences[$row['preference_key']] = $row['preference_value'];
    }
    
    return $preferences;
}

/**
 * Render user preferences form
 */
function renderUserPreferencesForm() {
    $preferences = getAllUserPreferences();
    
    // Default preferences
    $defaults = [
        'theme' => 'light',
        'sidebar_collapsed' => '0',
        'dashboard_layout' => 'default',
        'notifications_enabled' => '1',
        'timezone' => 'Africa/Nairobi',
        'date_format' => 'Y-m-d',
        'time_format' => '24',
        'items_per_page' => '25',
        'show_help_tips' => '1'
    ];
    
    $current = array_merge($defaults, $preferences);
    ?>
    
    <div class="card">
        <div class="card-header">
            <h5><i class="fas fa-user-cog"></i> User Preferences</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="userPreferencesForm">
                <input type="hidden" name="action" value="update_user_preferences">
                
                <!-- Theme Settings -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Theme</label>
                            <select class="form-control" name="theme" id="themeSelect">
                                <option value="light" <?php echo $current['theme'] === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                                <option value="dark" <?php echo $current['theme'] === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                                <option value="auto" <?php echo $current['theme'] === 'auto' ? 'selected' : ''; ?>>Auto (System)</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Dashboard Layout</label>
                            <select class="form-control" name="dashboard_layout">
                                <option value="default" <?php echo $current['dashboard_layout'] === 'default' ? 'selected' : ''; ?>>Default</option>
                                <option value="compact" <?php echo $current['dashboard_layout'] === 'compact' ? 'selected' : ''; ?>>Compact</option>
                                <option value="expanded" <?php echo $current['dashboard_layout'] === 'expanded' ? 'selected' : ''; ?>>Expanded</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Display Settings -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Items Per Page</label>
                            <select class="form-control" name="items_per_page">
                                <option value="10" <?php echo $current['items_per_page'] === '10' ? 'selected' : ''; ?>>10</option>
                                <option value="25" <?php echo $current['items_per_page'] === '25' ? 'selected' : ''; ?>>25</option>
                                <option value="50" <?php echo $current['items_per_page'] === '50' ? 'selected' : ''; ?>>50</option>
                                <option value="100" <?php echo $current['items_per_page'] === '100' ? 'selected' : ''; ?>>100</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Timezone</label>
                            <select class="form-control" name="timezone">
                                <option value="Africa/Nairobi" <?php echo $current['timezone'] === 'Africa/Nairobi' ? 'selected' : ''; ?>>East Africa Time (EAT)</option>
                                <option value="UTC" <?php echo $current['timezone'] === 'UTC' ? 'selected' : ''; ?>>UTC</option>
                                <option value="America/New_York" <?php echo $current['timezone'] === 'America/New_York' ? 'selected' : ''; ?>>Eastern Time</option>
                                <option value="Europe/London" <?php echo $current['timezone'] === 'Europe/London' ? 'selected' : ''; ?>>London Time</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Format Settings -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Date Format</label>
                            <select class="form-control" name="date_format">
                                <option value="Y-m-d" <?php echo $current['date_format'] === 'Y-m-d' ? 'selected' : ''; ?>>YYYY-MM-DD</option>
                                <option value="d/m/Y" <?php echo $current['date_format'] === 'd/m/Y' ? 'selected' : ''; ?>>DD/MM/YYYY</option>
                                <option value="m/d/Y" <?php echo $current['date_format'] === 'm/d/Y' ? 'selected' : ''; ?>>MM/DD/YYYY</option>
                                <option value="d-M-Y" <?php echo $current['date_format'] === 'd-M-Y' ? 'selected' : ''; ?>>DD-MMM-YYYY</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Time Format</label>
                            <select class="form-control" name="time_format">
                                <option value="24" <?php echo $current['time_format'] === '24' ? 'selected' : ''; ?>>24 Hour (13:45)</option>
                                <option value="12" <?php echo $current['time_format'] === '12' ? 'selected' : ''; ?>>12 Hour (1:45 PM)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- UI Preferences -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="sidebar_collapsed" id="sidebar_collapsed"
                                   <?php echo $current['sidebar_collapsed'] === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="sidebar_collapsed">
                                <strong>Collapse Sidebar by Default</strong>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="notifications_enabled" id="notifications_enabled"
                                   <?php echo $current['notifications_enabled'] === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="notifications_enabled">
                                <strong>Enable Desktop Notifications</strong>
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="show_help_tips" id="show_help_tips"
                                   <?php echo $current['show_help_tips'] === '1' ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="show_help_tips">
                                <strong>Show Help Tips</strong>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Preferences
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="resetPreferences()">
                        <i class="fas fa-undo"></i> Reset to Defaults
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Theme preview functionality
    document.getElementById('themeSelect').addEventListener('change', function() {
        const theme = this.value;
        previewTheme(theme);
    });

    function previewTheme(theme) {
        const body = document.body;
        
        // Remove existing theme classes
        body.classList.remove('theme-light', 'theme-dark', 'theme-auto');
        
        if (theme === 'auto') {
            // Detect system preference
            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                body.classList.add('theme-dark');
            } else {
                body.classList.add('theme-light');
            }
        } else {
            body.classList.add('theme-' + theme);
        }
    }

    function resetPreferences() {
        if (confirm('Are you sure you want to reset all preferences to defaults?')) {
            // Reset form to defaults
            document.getElementById('userPreferencesForm').reset();
            
            // Reset theme preview
            previewTheme('light');
            
            // Submit form with reset action
            const form = document.getElementById('userPreferencesForm');
            const resetInput = document.createElement('input');
            resetInput.type = 'hidden';
            resetInput.name = 'reset_preferences';
            resetInput.value = '1';
            form.appendChild(resetInput);
            form.submit();
        }
    }

    // Apply current theme on load
    document.addEventListener('DOMContentLoaded', function() {
        const currentTheme = '<?php echo $current['theme']; ?>';
        previewTheme(currentTheme);
    });
    </script>
    
    <?php
}

/**
 * Handle user preferences form submission
 */
function handleUserPreferencesSubmission() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_user_preferences') {
        
        // Handle reset preferences
        if (isset($_POST['reset_preferences'])) {
            // Delete all user preferences to reset to defaults
            global $con;
            $stmt = mysqli_prepare($con, "DELETE FROM user_preferences WHERE user_id = ?");
            mysqli_stmt_bind_param($stmt, "i", $_SESSION['user_id']);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = 'Preferences reset to defaults successfully!';
            } else {
                $_SESSION['error_message'] = 'Failed to reset preferences.';
            }
            
            header("Location: " . $_SERVER['PHP_SELF']);
            exit();
        }
        
        // Update preferences
        $preferences = [
            'theme' => $_POST['theme'] ?? 'light',
            'sidebar_collapsed' => isset($_POST['sidebar_collapsed']) ? '1' : '0',
            'dashboard_layout' => $_POST['dashboard_layout'] ?? 'default',
            'notifications_enabled' => isset($_POST['notifications_enabled']) ? '1' : '0',
            'timezone' => $_POST['timezone'] ?? 'Africa/Nairobi',
            'date_format' => $_POST['date_format'] ?? 'Y-m-d',
            'time_format' => $_POST['time_format'] ?? '24',
            'items_per_page' => $_POST['items_per_page'] ?? '25',
            'show_help_tips' => isset($_POST['show_help_tips']) ? '1' : '0'
        ];
        
        $success = true;
        foreach ($preferences as $key => $value) {
            if (!setUserPreference($key, $value)) {
                $success = false;
                break;
            }
        }
        
        if ($success) {
            $_SESSION['success_message'] = 'User preferences updated successfully!';
        } else {
            $_SESSION['error_message'] = 'Failed to update user preferences.';
        }
        
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }
}
?>
