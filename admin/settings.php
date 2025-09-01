<?php
$page_title = 'System Settings';
require_once '../db.php';
require_once '../includes/classes/Settings.php';

// Initialize settings manager
$settings = new Settings($con);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_hotel_info':
                $updates = [
                    'hotel_name' => $_POST['hotel_name'],
                    'hotel_address' => $_POST['hotel_address'],
                    'hotel_phone' => $_POST['hotel_phone'],
                    'hotel_email' => $_POST['hotel_email'],
                    'hotel_website' => $_POST['hotel_website'] ?? '',
                    'hotel_description' => $_POST['hotel_description'] ?? '',
                    'hotel_city' => $_POST['hotel_city'] ?? '',
                    'hotel_state' => $_POST['hotel_state'] ?? '',
                    'hotel_country' => $_POST['hotel_country'] ?? '',
                    'hotel_postal_code' => $_POST['hotel_postal_code'] ?? '',
                    'hotel_whatsapp' => $_POST['hotel_whatsapp'] ?? '',
                    'hotel_facebook' => $_POST['hotel_facebook'] ?? '',
                    'hotel_instagram' => $_POST['hotel_instagram'] ?? '',
                    'hotel_twitter' => $_POST['hotel_twitter'] ?? ''
                ];
                
                if ($settings->updateMultiple($updates)) {
                    $_SESSION['success_message'] = 'Hotel information updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update hotel information.';
                }
                break;
                
            case 'update_business_settings':
                $updates = [
                    'check_in_time' => $_POST['check_in_time'],
                    'check_out_time' => $_POST['check_out_time'],
                    'currency_symbol' => $_POST['currency_symbol'],
                    'tax_rate' => $_POST['tax_rate'],
                    'cancellation_policy' => $_POST['cancellation_policy']
                ];
                
                if ($settings->updateMultiple($updates)) {
                    $_SESSION['success_message'] = 'Business settings updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update business settings.';
                }
                break;
                
            case 'update_system_settings':
                $updates = [
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                    'allow_guest_registration' => isset($_POST['allow_guest_registration']) ? '1' : '0',
                    'session_timeout' => $_POST['session_timeout'],
                    'max_login_attempts' => $_POST['max_login_attempts'],
                    'backup_frequency' => $_POST['backup_frequency']
                ];
                
                if ($settings->updateMultiple($updates)) {
                    $_SESSION['success_message'] = 'System settings updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update system settings.';
                }
                break;
                
            case 'update_notification_settings':
                $updates = [
                    'email_notifications' => isset($_POST['email_notifications']) ? '1' : '0',
                    'sms_notifications' => isset($_POST['sms_notifications']) ? '1' : '0',
                    'admin_email' => $_POST['admin_email']
                ];
                
                if ($settings->updateMultiple($updates)) {
                    $_SESSION['success_message'] = 'Notification settings updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update notification settings.';
                }
                break;
                
            case 'update_security_settings':
                $updates = [
                    'password_min_length' => $_POST['password_min_length'],
                    'require_password_change' => $_POST['require_password_change'],
                    'enable_two_factor' => isset($_POST['enable_two_factor']) ? '1' : '0'
                ];
                
                if ($settings->updateMultiple($updates)) {
                    $_SESSION['success_message'] = 'Security settings updated successfully!';
                } else {
                    $_SESSION['error_message'] = 'Failed to update security settings.';
                }
                break;
        }
        
        // Redirect to prevent form resubmission
        header("Location: settings.php");
        exit();
    }
}

// Get all settings grouped by category
$allSettings = $settings->getAllGrouped();

include '../includes/admin/header.php';
include '../includes/components/alerts.php';
include '../includes/components/forms.php';
include '../includes/components/dashboard_widgets.php';
?>

<!-- Page Header -->
<div class="page-header">
    <h1 class="page-title">System Settings</h1>
    <p class="page-subtitle">Configure hotel management system settings</p>
</div>

<?php
// Display session alerts
display_session_alerts();
?>

<?php  
// Session is already started by header.php
if(!isset($_SESSION["user"]) && !isset($_SESSION["user_id"]))
{
 header("Location: index.php");
 exit();
}
?> 

<!-- Settings Navigation Tabs -->
<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active" id="hotel-tab" data-bs-toggle="tab" data-bs-target="#hotel" type="button" role="tab">
            <i class="fas fa-hotel"></i> Hotel Information
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="business-tab" data-bs-toggle="tab" data-bs-target="#business" type="button" role="tab">
            <i class="fas fa-briefcase"></i> Business Settings
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab">
            <i class="fas fa-cogs"></i> System Configuration
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="notifications-tab" data-bs-toggle="tab" data-bs-target="#notifications" type="button" role="tab">
            <i class="fas fa-bell"></i> Notifications
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
            <i class="fas fa-shield-alt"></i> Security
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link" id="stats-tab" data-bs-toggle="tab" data-bs-target="#stats" type="button" role="tab">
            <i class="fas fa-chart-bar"></i> System Statistics
        </button>
    </li>
</ul>

<div class="tab-content" id="settingsTabContent">
    <!-- Hotel Information Tab -->
    <div class="tab-pane fade show active" id="hotel" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-hotel"></i> Hotel Information</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_hotel_info">
                    <div class="row">
                        <!-- Basic Information -->
                        <div class="col-md-12">
                            <h6 class="form-section-title"><i class="fas fa-info-circle"></i> Basic Information</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Hotel Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="hotel_name" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_name']['value'] ?? 'Orlando International Resorts'); ?>" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Website URL</label>
                                <input type="url" class="form-control" name="hotel_website" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_website']['value'] ?? ''); ?>" 
                                       placeholder="https://www.orlandointernationalresort.net">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Hotel Description</label>
                                <textarea class="form-control" name="hotel_description" rows="3" 
                                          placeholder="Brief description of your hotel"><?php echo htmlspecialchars($allSettings['hotel']['hotel_description']['value'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Contact Information -->
                        <div class="col-md-12">
                            <h6 class="form-section-title"><i class="fas fa-phone"></i> Contact Information</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Primary Phone <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="hotel_phone" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_phone']['value'] ?? '+254 742 824 006'); ?>" 
                                       placeholder="+254 742 824 006" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>WhatsApp Number</label>
                                <input type="text" class="form-control" name="hotel_whatsapp" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_whatsapp']['value'] ?? '+254742824006'); ?>" 
                                       placeholder="+254742824006">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Contact Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="hotel_email" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_email']['value'] ?? 'info@orlandointernationalresort.net'); ?>" 
                                       placeholder="info@orlandointernationalresort.net" required>
                            </div>
                        </div>
                        
                        <!-- Location Information -->
                        <div class="col-md-12">
                            <h6 class="form-section-title"><i class="fas fa-map-marker-alt"></i> Location Information</h6>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Street Address</label>
                                <textarea class="form-control" name="hotel_address" rows="2" 
                                          placeholder="Street address or main location"><?php echo htmlspecialchars($allSettings['hotel']['hotel_address']['value'] ?? 'Machakos Town, Kenya'); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" class="form-control" name="hotel_city" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_city']['value'] ?? 'Machakos'); ?>" 
                                       placeholder="Machakos">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>State/County</label>
                                <input type="text" class="form-control" name="hotel_state" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_state']['value'] ?? 'Machakos County'); ?>" 
                                       placeholder="Machakos County">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Country</label>
                                <input type="text" class="form-control" name="hotel_country" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_country']['value'] ?? 'Kenya'); ?>" 
                                       placeholder="Kenya">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Postal Code</label>
                                <input type="text" class="form-control" name="hotel_postal_code" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_postal_code']['value'] ?? '90100'); ?>" 
                                       placeholder="90100">
                            </div>
                        </div>
                        
                        <!-- Social Media -->
                        <div class="col-md-12">
                            <h6 class="form-section-title"><i class="fas fa-share-alt"></i> Social Media (Optional)</h6>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Facebook URL</label>
                                <input type="url" class="form-control" name="hotel_facebook" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_facebook']['value'] ?? ''); ?>" 
                                       placeholder="https://facebook.com/yourhotel">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Instagram URL</label>
                                <input type="url" class="form-control" name="hotel_instagram" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_instagram']['value'] ?? ''); ?>" 
                                       placeholder="https://instagram.com/yourhotel">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Twitter URL</label>
                                <input type="url" class="form-control" name="hotel_twitter" 
                                       value="<?php echo htmlspecialchars($allSettings['hotel']['hotel_twitter']['value'] ?? ''); ?>" 
                                       placeholder="https://twitter.com/yourhotel">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Hotel Information
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Business Settings Tab -->
    <div class="tab-pane fade" id="business" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-briefcase"></i> Business Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_business_settings">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check-in Time</label>
                                <input type="time" class="form-control" name="check_in_time" 
                                       value="<?php echo htmlspecialchars($allSettings['business']['check_in_time']['value'] ?? '15:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Check-out Time</label>
                                <input type="time" class="form-control" name="check_out_time" 
                                       value="<?php echo htmlspecialchars($allSettings['business']['check_out_time']['value'] ?? '11:00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Currency Symbol</label>
                                <input type="text" class="form-control" name="currency_symbol" 
                                       value="<?php echo htmlspecialchars($allSettings['business']['currency_symbol']['value'] ?? 'KES'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tax Rate (%)</label>
                                <input type="number" step="0.01" class="form-control" name="tax_rate" 
                                       value="<?php echo htmlspecialchars($allSettings['business']['tax_rate']['value'] ?? '16.00'); ?>">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label>Cancellation Policy</label>
                                <textarea class="form-control" name="cancellation_policy" rows="3"><?php echo htmlspecialchars($allSettings['business']['cancellation_policy']['value'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Business Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- System Configuration Tab -->
    <div class="tab-pane fade" id="system" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-cogs"></i> System Configuration</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_system_settings">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="maintenance_mode" id="maintenance_mode"
                                       <?php echo (!empty($allSettings['system']['maintenance_mode']['value'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="maintenance_mode">
                                    <strong>Maintenance Mode</strong> - Temporarily disable public access
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="allow_guest_registration" id="allow_guest_registration"
                                       <?php echo (!empty($allSettings['system']['allow_guest_registration']['value'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="allow_guest_registration">
                                    <strong>Allow Guest Registration</strong> - Enable new guest sign-ups
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Session Timeout (seconds)</label>
                                <input type="number" class="form-control" name="session_timeout" 
                                       value="<?php echo htmlspecialchars($allSettings['system']['session_timeout']['value'] ?? '3600'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Max Login Attempts</label>
                                <input type="number" class="form-control" name="max_login_attempts" 
                                       value="<?php echo htmlspecialchars($allSettings['system']['max_login_attempts']['value'] ?? '5'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Backup Frequency</label>
                                <select class="form-control" name="backup_frequency">
                                    <option value="daily" <?php echo ($allSettings['system']['backup_frequency']['value'] ?? '') === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo ($allSettings['system']['backup_frequency']['value'] ?? '') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo ($allSettings['system']['backup_frequency']['value'] ?? '') === 'monthly' ? 'selected' : ''; ?>>Monthly</option>
                                    <option value="manual" <?php echo ($allSettings['system']['backup_frequency']['value'] ?? '') === 'manual' ? 'selected' : ''; ?>>Manual Only</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update System Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Notifications Tab -->
    <div class="tab-pane fade" id="notifications" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-bell"></i> Notification Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_notification_settings">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="email_notifications" id="email_notifications"
                                       <?php echo (!empty($allSettings['notifications']['email_notifications']['value'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="email_notifications">
                                    <strong>Email Notifications</strong> - Send notifications via email
                                </label>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="sms_notifications" id="sms_notifications"
                                       <?php echo (!empty($allSettings['notifications']['sms_notifications']['value'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="sms_notifications">
                                    <strong>SMS Notifications</strong> - Send notifications via SMS
                                </label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Admin Email</label>
                                <input type="email" class="form-control" name="admin_email" 
                                       value="<?php echo htmlspecialchars($allSettings['notifications']['admin_email']['value'] ?? ''); ?>">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Notification Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Security Tab -->
    <div class="tab-pane fade" id="security" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-shield-alt"></i> Security Settings</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="update_security_settings">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Minimum Password Length</label>
                                <input type="number" min="6" max="20" class="form-control" name="password_min_length" 
                                       value="<?php echo htmlspecialchars($allSettings['security']['password_min_length']['value'] ?? '8'); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Password Change Required (days)</label>
                                <input type="number" min="0" class="form-control" name="require_password_change" 
                                       value="<?php echo htmlspecialchars($allSettings['security']['require_password_change']['value'] ?? '90'); ?>">
                                <small class="form-text text-muted">Set to 0 to disable forced password changes</small>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="enable_two_factor" id="enable_two_factor"
                                       <?php echo (!empty($allSettings['security']['enable_two_factor']['value'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="enable_two_factor">
                                    <strong>Enable Two-Factor Authentication</strong> - Require 2FA for admin users
                                </label>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Security Settings
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- System Statistics Tab -->
    <div class="tab-pane fade" id="stats" role="tabpanel">
        <div class="card mt-3">
            <div class="card-header">
                <h5><i class="fas fa-chart-bar"></i> System Statistics</h5>
            </div>
            <div class="card-body">
                <?php
                // Get system statistics
                $total_rooms = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM named_rooms WHERE is_active = 1"))['count'];
                $total_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook"))['count'];
                $active_users = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM users WHERE status = 'active'"))['count'];
                $total_revenue = mysqli_fetch_assoc(mysqli_query($con, "SELECT SUM(ttot) as total FROM payment"))['total'] ?? 0;
                
                // Additional statistics
                $pending_bookings = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cin > NOW()"))['count'];
                $current_guests = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM roombook WHERE cin <= NOW() AND cout >= NOW()"))['count'];
                $total_messages = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM messages"))['count'];
                $inventory_items = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as count FROM kitchen_inventory WHERE is_active = 1"))['count'];
                ?>
                <div class="row text-center">
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-primary text-white">
                            <div class="stat-icon"><i class="fas fa-bed"></i></div>
                            <div class="stat-number"><?php echo $total_rooms; ?></div>
                            <div class="stat-label">Active Rooms</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-success text-white">
                            <div class="stat-icon"><i class="fas fa-calendar-check"></i></div>
                            <div class="stat-number"><?php echo $total_bookings; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-info text-white">
                            <div class="stat-icon"><i class="fas fa-users"></i></div>
                            <div class="stat-number"><?php echo $active_users; ?></div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-warning text-white">
                            <div class="stat-icon"><i class="fas fa-money-bill-wave"></i></div>
                            <div class="stat-number">KES <?php echo number_format($total_revenue, 0); ?></div>
                            <div class="stat-label">Total Revenue</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-secondary text-white">
                            <div class="stat-icon"><i class="fas fa-clock"></i></div>
                            <div class="stat-number"><?php echo $pending_bookings; ?></div>
                            <div class="stat-label">Pending Bookings</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-dark text-white">
                            <div class="stat-icon"><i class="fas fa-user-check"></i></div>
                            <div class="stat-number"><?php echo $current_guests; ?></div>
                            <div class="stat-label">Current Guests</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-danger text-white">
                            <div class="stat-icon"><i class="fas fa-envelope"></i></div>
                            <div class="stat-number"><?php echo $total_messages; ?></div>
                            <div class="stat-label">Messages</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="stat-card bg-purple text-white">
                            <div class="stat-icon"><i class="fas fa-boxes"></i></div>
                            <div class="stat-number"><?php echo $inventory_items; ?></div>
                            <div class="stat-label">Inventory Items</div>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <h6>System Information</h6>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <tbody>
                                <tr>
                                    <td><strong>PHP Version:</strong></td>
                                    <td><?php echo PHP_VERSION; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>MySQL Version:</strong></td>
                                    <td><?php echo mysqli_get_server_info($con); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Server Time:</strong></td>
                                    <td><?php echo date('Y-m-d H:i:s'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Database Name:</strong></td>
                                    <td><?php echo mysqli_fetch_assoc(mysqli_query($con, "SELECT DATABASE() as db"))['db']; ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.stat-card {
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 20px;
    text-align: center;
    position: relative;
    overflow: hidden;
}

.stat-card .stat-icon {
    font-size: 2.5rem;
    opacity: 0.8;
    margin-bottom: 10px;
}

.stat-card .stat-number {
    font-size: 2rem;
    font-weight: bold;
    margin-bottom: 5px;
}

.stat-card .stat-label {
    font-size: 0.9rem;
    opacity: 0.9;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.bg-purple {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
}

.form-group {
    margin-bottom: 1rem;
}

.nav-tabs {
    border-bottom: 2px solid #dee2e6;
}

.nav-tabs .nav-link {
    border: none;
    color: #6c757d;
    font-weight: 500;
    padding: 15px 20px;
    margin-bottom: -2px;
    border-bottom: 2px solid transparent;
}

.nav-tabs .nav-link:hover {
    border-color: transparent;
    background-color: rgba(0,123,255,0.1);
    color: #007bff;
}

.nav-tabs .nav-link.active {
    color: #007bff;
    background-color: transparent;
    border-bottom-color: #007bff;
}

.card {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    border: 1px solid rgba(0,0,0,.125);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid rgba(0,0,0,.125);
    font-weight: 600;
}

.form-check-label strong {
    color: #495057;
}

.btn-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border: none;
    padding: 10px 25px;
    font-weight: 500;
}

.btn-primary:hover {
    background: linear-gradient(135deg, #0056b3 0%, #004085 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,123,255,0.3);
}

.page-header {
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #dee2e6;
}

.page-title {
    color: #2c3e50;
    font-weight: 600;
    margin-bottom: 5px;
}

.page-subtitle {
    color: #6c757d;
    margin-bottom: 0;
}

.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.form-section-title {
    color: #495057;
    font-weight: 600;
    margin: 20px 0 15px 0;
    padding-bottom: 8px;
    border-bottom: 2px solid #e9ecef;
}

.form-section-title:first-of-type {
    margin-top: 5px;
}

.form-section-title i {
    color: #007bff;
    margin-right: 8px;
}

@media (max-width: 768px) {
    .nav-tabs .nav-link {
        padding: 10px 15px;
        font-size: 0.9rem;
    }
    
    .stat-card .stat-number {
        font-size: 1.5rem;
    }
    
    .stat-card .stat-icon {
        font-size: 2rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-save functionality (optional)
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            input.addEventListener('change', function() {
                // Add a small indicator that changes have been made
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn && !submitBtn.classList.contains('btn-warning')) {
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-warning');
                    submitBtn.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Save Changes';
                }
            });
        });
        
        form.addEventListener('submit', function() {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
                submitBtn.disabled = true;
            }
        });
    });
    
    // Tab persistence
    const activeTab = localStorage.getItem('settingsActiveTab');
    if (activeTab) {
        const tabTrigger = document.querySelector(`[data-bs-target="${activeTab}"]`);
        if (tabTrigger) {
            const tab = new bootstrap.Tab(tabTrigger);
            tab.show();
        }
    }
    
    // Save active tab
    const tabButtons = document.querySelectorAll('[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(e) {
            localStorage.setItem('settingsActiveTab', e.target.getAttribute('data-bs-target'));
        });
    });
    
    // Form validation feedback
    const requiredInputs = document.querySelectorAll('input[required]');
    requiredInputs.forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value.trim() === '') {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    });
});
</script>

<?php include '../includes/admin/footer.php'; ?>