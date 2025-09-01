# 📋 **Orlando International Resorts - Include System Usage Guide**

## 🎯 **Overview**

This comprehensive include system eliminates code duplication and ensures consistency across your Orlando International Resorts application. All includes are organized by user type and functionality.

## 📁 **Directory Structure**

```
includes/
├── common/          # Shared across all user types
│   ├── config.php   # Application configuration & helpers
│   └── meta.php     # Common meta tags, CSS, JS
├── guest/           # Guest-facing includes
│   ├── header.php   # Modern guest header with navigation
│   └── footer.php   # Professional guest footer
├── admin/           # Admin dashboard includes
│   ├── header.php   # Admin dashboard header with sidebar
│   └── footer.php   # Admin dashboard footer with scripts
└── components/      # Reusable UI components
    ├── alerts.php           # Alert/notification components
    ├── forms.php            # Form generation helpers
    └── dashboard_widgets.php # Dashboard widget components
```

## 🚀 **Quick Start Examples**

### **1. Basic Guest Page**

```php
<?php
$page_title = 'Rooms & Suites - Orlando International Resorts';
$page_description = 'Luxury rooms and suites at Orlando International Resorts';
include 'includes/guest/header.php';
?>

<div class="container">
    <h1>Our Rooms</h1>
    <p>Welcome to our luxury accommodations...</p>
</div>

<?php include 'includes/guest/footer.php'; ?>
```

### **2. Basic Admin Page**

```php
<?php
$page_title = 'User Management';
include 'includes/admin/header.php';

// Include form components
include 'includes/components/forms.php';
include 'includes/components/alerts.php';
?>

<div class="page-header">
    <h1 class="page-title">User Management</h1>
    <p class="page-subtitle">Manage system users and permissions</p>
</div>

<?php
// Display any alerts
display_session_alerts();

// Render user form
echo render_input_field('username', 'Username', 'text', '', [], true);
echo render_select_field('role', 'Role', ['1' => 'Admin', '2' => 'Staff'], '', [], true);
echo render_form_buttons('Create User', 'user_list.php');
?>

<?php include 'includes/admin/footer.php'; ?>
```

### **3. Dashboard with Widgets**

```php
<?php
$page_title = 'Dashboard';
include 'includes/admin/header.php';
include 'includes/components/dashboard_widgets.php';

// Render dashboard styles
render_dashboard_styles();
?>

<div class="page-header">
    <h1 class="page-title">Dashboard Overview</h1>
</div>

<div class="row">
    <?php
    // Statistics cards
    echo render_stats_card('Total Bookings', '156', 'fa-bed', 'primary', '', 12.5);
    echo render_stats_card('Revenue Today', 'KSh 45,230', 'fa-dollar-sign', 'success', '', 8.2);
    echo render_stats_card('Occupancy Rate', '87%', 'fa-chart-pie', 'info');
    echo render_stats_card('Pending Orders', '23', 'fa-clock', 'warning');
    ?>
</div>

<?php
// Chart container
echo render_chart_container('revenueChart', 'Monthly Revenue', '400px');

// Recent activity
$activities = [
    [
        'title' => 'New Booking',
        'description' => 'Room 101 booked by John Doe',
        'created_at' => '2024-01-15 10:30:00',
        'icon' => 'fa-bed',
        'color' => 'success'
    ],
    [
        'title' => 'Payment Received',
        'description' => 'KSh 12,500 via M-Pesa',
        'created_at' => '2024-01-15 09:15:00',
        'icon' => 'fa-money-bill',
        'color' => 'success'
    ]
];

echo render_activity_list($activities, 'Recent Activity', 'activity.php');
?>

<?php include 'includes/admin/footer.php'; ?>
```

## 🔧 **Available Components**

### **Alert Components (`includes/components/alerts.php`)**

```php
// Display session-based alerts (automatic)
display_session_alerts();

// Manual alert rendering
echo render_alert('Success message', 'success');
echo render_alert('Error occurred', 'danger');

// Validation errors
$errors = ['Username is required', 'Email is invalid'];
echo display_validation_errors($errors);

// Success confirmation
echo display_success_confirmation(
    'Booking Confirmed!',
    'Your reservation has been successfully created.',
    'bookings.php',
    'View Bookings'
);
```

### **Form Components (`includes/components/forms.php`)**

```php
// Basic form fields
echo render_input_field('email', 'Email Address', 'email', '', ['placeholder' => 'Enter email'], true);
echo render_textarea_field('description', 'Description', '', 4, [], true);
echo render_select_field('status', 'Status', ['active' => 'Active', 'inactive' => 'Inactive']);
echo render_checkbox_field('newsletter', 'Subscribe to newsletter');
echo render_date_field('check_in', 'Check-in Date', '', date('Y-m-d'), '', true);

// Form buttons
echo render_form_buttons('Save Changes', 'cancel.php', [
    ['type' => 'button', 'class' => 'btn btn-info', 'text' => 'Preview']
]);

// Search form
echo render_search_form('search.php', 'Search bookings...', $_GET['search'] ?? '');

// Pagination
echo render_pagination(2, 10, 'bookings.php', ['status' => 'active']);
```

### **Dashboard Widgets (`includes/components/dashboard_widgets.php`)**

```php
// Statistics cards
echo render_stats_card('Revenue', 'KSh 156,000', 'fa-chart-line', 'success', 'This month', 15.3);

// Progress cards
echo render_progress_card('Room Occupancy', 87, 100, 'primary');

// Data tables
$headers = ['Name', 'Email', 'Role', 'Status'];
$data = [
    ['John Doe', 'john@example.com', 'Admin', 'Active'],
    ['Jane Smith', 'jane@example.com', 'Staff', 'Active']
];
$actions = [
    '<a href="edit.php?id=1" class="btn btn-sm btn-primary">Edit</a>',
    '<a href="delete.php?id=1" class="btn btn-sm btn-danger">Delete</a>'
];
echo render_data_table($headers, $data, $actions);

// Quick actions
$quick_actions = [
    [
        'title' => 'New Booking',
        'description' => 'Create a new room reservation',
        'url' => 'booking_form.php',
        'icon' => 'fa-plus',
        'color' => 'primary'
    ],
    [
        'title' => 'Check-in Guest',
        'description' => 'Process guest arrival',
        'url' => 'checkin.php',
        'icon' => 'fa-sign-in-alt',
        'color' => 'success'
    ]
];
echo render_quick_actions($quick_actions);
```

## 🎨 **Styling & Customization**

### **Custom Page Styles**

Add custom styles to individual pages:

```php
<?php
$page_scripts = "
<style>
.custom-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 0;
}
</style>
<script>
// Custom JavaScript
$(document).ready(function() {
    console.log('Page loaded');
});
</script>
";

include 'includes/guest/header.php';
?>
```

### **Path Prefix for Subdirectories**

The system automatically calculates correct paths:

```php
// These work from any directory depth
<img src="<?php echo $path_prefix; ?>images/logo.png">
<link href="<?php echo $path_prefix; ?>css/style.css">
<a href="<?php echo $path_prefix; ?>index.php">Home</a>
```

## 🔐 **User Type Detection & Permissions**

### **Admin Permission Checks**

```php
<?php
include 'includes/admin/header.php';

// Check specific permissions
if (user_has_permission($con, 'user.create')) {
    echo '<a href="create_user.php" class="btn btn-primary">Add User</a>';
}

// Conditional navigation (automatically handled in admin header)
?>
```

### **Guest vs Admin Detection**

```php
<?php
// Automatically detected in config.php
// Guest pages use: includes/guest/header.php
// Admin pages use: includes/admin/header.php
?>
```

## 📱 **Responsive Design**

All includes are fully responsive:

- **Mobile-first design** with Bootstrap 4
- **Collapsible navigation** for mobile devices
- **Touch-friendly interface** elements
- **Optimized layouts** for all screen sizes

## 🚀 **Performance Features**

- **Lazy loading** for images
- **Minified assets** loading
- **Caching headers** for static resources
- **Optimized database** queries in components
- **Progressive enhancement** with JavaScript

## 🔧 **Migration from Old System**

### **Step 1: Update Existing Pages**

Replace old includes:

```php
// OLD
include 'header.php';
include 'footer.php';

// NEW
include 'includes/guest/header.php';
include 'includes/guest/footer.php';
```

### **Step 2: Set Page Titles**

```php
// Before any includes
$page_title = 'Your Page Title - Orlando International Resorts';
$page_description = 'Page description for SEO';
```

### **Step 3: Use New Components**

Replace manual HTML with component functions:

```php
// OLD
echo '<div class="alert alert-success">Success!</div>';

// NEW
echo render_alert('Success!', 'success');
```

## 🎯 **Best Practices**

1. **Always set page title** before including headers
2. **Use components** instead of manual HTML when possible
3. **Follow naming conventions** for consistency
4. **Test responsive** layouts on mobile devices
5. **Validate forms** using built-in helpers
6. **Use session alerts** for user feedback
7. **Check permissions** before displaying admin content
8. **Include proper meta** descriptions for SEO

## 🆘 **Troubleshooting**

### **Common Issues**

**Path Prefix Not Working:**

```php
// Ensure config.php is loaded first
include 'includes/common/config.php';
```

**CSS/JS Not Loading:**

```php
// Check path prefix calculation
echo "Path prefix: " . $GLOBALS['path_prefix'];
```

**Permission Errors:**

```php
// Ensure auth.php is included in admin pages
include 'admin/auth.php';
```

**Missing Bootstrap Styles:**

```php
// Use correct CSS classes from Bootstrap 4
class="btn btn-primary"  // ✓ Correct
class="button primary"   // ✗ Wrong
```

## 📞 **Support**

For issues with the include system:

1. Check this guide first
2. Verify file paths and permissions
3. Test with a minimal example
4. Check browser console for errors
5. Validate HTML and PHP syntax

This include system provides a solid foundation for maintaining and scaling your Orlando International Resorts application! 🏨✨
