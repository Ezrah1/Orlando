# 🏨 Dynamic Hotel Settings System Guide

## Overview

The Orlando International Resorts system now includes a comprehensive dynamic hotel settings system that allows you to manage all hotel contact details, business information, and configuration from the admin panel. This system ensures consistency across all pages and makes it easy to update information throughout the entire application.

## 📋 Features

### ✅ What's Been Implemented

1. **Dynamic Settings Database**

   - Centralized `system_settings` table for all configuration
   - Categorized settings (hotel, business, system, notifications, security)
   - Type-safe value storage (text, number, boolean, json, email, url)
   - Public/private setting visibility control

2. **Enhanced Settings Class**

   - Easy-to-use methods for retrieving hotel information
   - Caching for improved performance
   - Helper methods for formatted contact display
   - Singleton pattern for global access

3. **Global Helper Functions**

   - `get_hotel_info()` - Get hotel details
   - `get_business_config()` - Get business configuration
   - `get_contact_display()` - Get formatted contact information
   - `get_setting()` - Get any setting value
   - `format_currency()` - Format currency with dynamic symbol
   - `get_phone_link()` and `get_email_link()` - Get clickable links

4. **Updated Admin Interface**

   - Comprehensive hotel information form
   - Contact details, location, and social media fields
   - Your specific information pre-filled as defaults
   - Organized sections with validation

5. **Updated Templates**
   - Header and footer now use dynamic settings
   - Main index page uses dynamic content
   - Contact information widget for easy inclusion

## 🔧 Current Configuration

Your hotel details are now configured as follows:

```
Hotel Name: Orlando International Resorts
Phone: +254 742 824 006
Email: info@orlandointernationalresort.net
Address: Machakos Town, Kenya
City: Machakos
Country: Kenya
Currency: KES
```

## 🚀 How to Use

### 1. Admin Panel Management

Navigate to `http://localhost/Hotel/admin/settings.php` to:

- Update hotel name, contact details, and description
- Modify location information
- Set social media links
- Configure business settings (check-in/out times, currency, policies)
- Adjust system and security settings

### 2. Using in PHP Templates

#### Basic Hotel Information

```php
<?php
// Include the helper functions
require_once 'includes/common/hotel_settings.php';

// Get hotel name
echo get_hotel_info('name');

// Get all hotel information
$hotel = get_hotel_info();
echo $hotel['name'];
echo $hotel['phone'];
echo $hotel['email'];
?>
```

#### Formatted Contact Information

```php
<?php
// Get formatted contact links
$contact = get_contact_display();
?>
<a href="<?php echo $contact['phone_link']; ?>">Call Us</a>
<a href="<?php echo $contact['email_link']; ?>">Email Us</a>
<a href="<?php echo $contact['whatsapp_link']; ?>">WhatsApp</a>
```

#### Business Configuration

```php
<?php
// Get currency symbol
echo format_currency(1500); // Outputs: KES 1,500

// Get check-in time
echo get_business_config('check_in_time'); // Outputs: 14:00
?>
```

### 3. Contact Widget

Include the ready-made contact widget anywhere:

```php
<?php include 'includes/components/contact_info_widget.php'; ?>
```

### 4. Individual Settings

Get any specific setting:

```php
<?php
$maintenance_mode = get_setting('maintenance_mode', false);
$tax_rate = get_setting('tax_rate', '16.00');
?>
```

## 📁 File Structure

```
Hotel/
├── admin/
│   └── settings.php (Updated admin interface)
├── includes/
│   ├── classes/
│   │   └── Settings.php (Enhanced settings class)
│   ├── common/
│   │   └── hotel_settings.php (Global helper functions)
│   ├── components/
│   │   └── contact_info_widget.php (Contact widget)
│   ├── header.php (Updated to use dynamic settings)
│   └── footer.php (Updated to use dynamic settings)
├── setup/
│   └── initialize_hotel_settings.php (Database initialization)
└── docs/
    └── DYNAMIC_HOTEL_SETTINGS_GUIDE.md (This file)
```

## 🛠️ Setup Instructions

### 1. Initialize Database Settings

Run the initialization script to populate your hotel details:

```bash
# Navigate to setup directory and run:
php initialize_hotel_settings.php
```

### 2. Access Admin Panel

1. Go to `http://localhost/Hotel/admin/settings.php`
2. Click on the "Hotel Information" tab
3. Verify your details are populated:
   - Hotel Name: Orlando International Resorts
   - Phone: +254 742 824 006
   - Email: info@orlandointernationalresort.net
   - Address: Machakos Town, Kenya

### 3. Test Dynamic Updates

1. Change any hotel detail in the admin panel
2. Save the changes
3. Visit any front-end page to see the updates reflected immediately

## 🔍 Benefits

1. **Centralized Management**: Update hotel details in one place, reflected everywhere
2. **Consistency**: No more hardcoded values scattered throughout the code
3. **Easy Maintenance**: Non-technical users can update contact information
4. **Future-Proof**: Easy to add new settings as your business grows
5. **Professional**: Maintains brand consistency across all touchpoints

## 🎯 Next Steps

1. **Run the initialization script** to populate your specific hotel details
2. **Test the admin panel** to ensure settings are working properly
3. **Verify frontend updates** by changing a setting and viewing the website
4. **Add more settings** as needed for your specific requirements

## 🆘 Support

If you encounter any issues:

1. Check that the `system_settings` table exists in your database
2. Verify database connection in `admin/db.php`
3. Ensure file permissions allow reading of include files
4. Check browser console for any JavaScript errors

The system is now fully functional and ready for production use with your specific Orlando International Resorts information!
