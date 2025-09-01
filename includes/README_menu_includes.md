# Menu Include Files Documentation

This document explains how to use the reusable menu include files throughout the Orlando International Resorts system.

## Files Created

### 1. `includes/menu_section.php`

**Purpose**: Full menu section for displaying menu items with filtering and search capabilities.

**Usage**: Include this file on any page where you want to display a comprehensive menu section.

**Configuration Variables** (set before including):

```php
<?php
// Configure menu section
$menu_title = "Our Menu";                    // Section title
$menu_subtitle = "Description text";         // Section subtitle
$show_search = false;                        // Show search bar (true/false)
$items_limit = 12;                          // Number of items to display
$show_all_link = true;                      // Show "View Full Menu" link
$menu_style = "simple";                     // Style: "simple" or "advanced"

// Include the menu section
include('includes/menu_section.php');
?>
```

**Example Implementation**:

```php
<?php
// Homepage featured menu
$menu_title = "Featured Menu";
$menu_subtitle = "Try our most popular dishes";
$show_search = true;
$items_limit = 8;
$show_all_link = true;
include('includes/menu_section.php');
?>
```

### 2. `includes/menu_widget.php` (Enhanced Widget)

**Purpose**: Feature-rich menu widget with advanced filtering, search, and cart functionality. Supports both simple and advanced modes for all use cases.

**Usage**: Include this file anywhere you need a menu widget - it automatically adapts to your configuration.

**Configuration Variables** (set before including):

```php
<?php
// Configure menu widget
$widget_title = "Featured Menu";            // Widget title
$widget_items_limit = 8;                    // Number of items to show
$widget_style = "compact";                  // Layout: "compact" or "full"
$widget_show_prices = true;                 // Show prices (true/false)
$widget_show_filters = true;                // Show filter controls
$widget_show_search = true;                 // Show search box
$widget_show_cart_actions = true;           // Show cart functionality

// Include the menu widget
include('includes/menu_widget.php');
?>
```

**Widget Features**:

- 🔍 Live search functionality
- 🏷️ Category and dietary filtering
- 🛒 Cart integration with quantity selectors
- 🎨 Two layout modes (compact/full)
- 📱 Fully responsive design
- ✨ Smooth animations and interactions
- 🔧 Backward compatibility with simple configuration

**Simple Usage (Backward Compatible)**:

```php
<?php
// Simple configuration for basic use
$widget_title = "Quick Menu";
$widget_items_limit = 6;
$widget_show_filters = false;    // Disable advanced features
$widget_show_search = false;     // Keep it simple
include('includes/menu_widget.php');
?>
```

**Advanced Usage**:

```php
<?php
// Full-featured interactive widget
$widget_title = "Interactive Menu";
$widget_items_limit = 12;
$widget_style = "full";
$widget_show_filters = true;
$widget_show_search = true;
$widget_show_cart_actions = true;
include('includes/menu_widget.php');
?>
```

## Features

### Menu Section Features:

- ✅ Category filtering tabs
- ✅ Search functionality (optional)
- ✅ Responsive grid layout
- ✅ Menu item cards with images
- ✅ Price display
- ✅ Category tags
- ✅ Dietary tags (Vegetarian, Spicy, Gluten-Free)
- ✅ Order buttons
- ✅ "View Full Menu" link
- ✅ Mobile responsive

### Menu Widget Features:

- ✅ Adaptive layout (compact/full modes)
- ✅ Live search functionality (optional)
- ✅ Category filtering dropdown (optional)
- ✅ Dietary options filtering (vegetarian, gluten-free, spicy)
- ✅ Multiple sorting options
- ✅ Interactive quantity selectors
- ✅ AJAX cart integration
- ✅ Product badges and images
- ✅ Price display (configurable)
- ✅ Animated interactions
- ✅ Cart count indicator
- ✅ Loading states and notifications
- ✅ Backward compatibility mode
- ✅ Mobile responsive design
- ✅ "View Full Menu" link

## Current Implementation

### Homepage (`index.php`)

```php
// Featured Menu Section
$menu_title = "Featured Menu";
$menu_subtitle = "Try our most popular dishes and beverages";
$show_search = true;
$items_limit = 8;
$show_all_link = true;
$menu_style = "simple";
include('includes/menu_section.php');
```

## Adding Menu to Other Pages

### Example 1: Booking Confirmation Page

```php
<?php
// Show menu widget on booking confirmation
$widget_title = "Order Food to Your Room";
$widget_items_limit = 6;
$widget_style = "compact";
$widget_show_prices = true;
$widget_show_cart_actions = true;
$widget_show_filters = false;  // Keep it simple
include('../../../includes/menu_widget.php');
?>
```

### Example 2: About Page

```php
<?php
// Show enhanced menu widget on about page
$widget_title = "Taste Our Cuisine";
$widget_items_limit = 8;
$widget_style = "full";
$widget_show_filters = true;
$widget_show_search = true;
$widget_show_cart_actions = true;
include('includes/menu_widget.php');
?>
```

### Example 3: Sidebar Widget

```php
<?php
// Compact sidebar menu
$widget_title = "Quick Bites";
$widget_items_limit = 4;
$widget_style = "compact";
$widget_show_prices = true;
$widget_show_filters = false;
$widget_show_search = false;
$widget_show_cart_actions = true;
include('includes/menu_widget.php');
?>
```

## Benefits of This Approach

### ✅ **Consistency**

- Same styling and functionality across all pages
- Unified user experience
- Brand consistency maintained

### ✅ **Maintainability**

- Update menu design in one place
- Changes apply across entire site
- Reduced code duplication

### ✅ **Flexibility**

- Configurable options for different use cases
- Easy to customize per page
- Multiple display styles available

### ✅ **Performance**

- Optimized database queries
- Cached menu data where possible
- Responsive design

## Database Requirements

The menu includes require these database tables:

- `menu_items` - Menu item details
- `menu_categories` - Menu categories
- Database connection `$con` must be available

## Styling

All styles are included within the files. The CSS is scoped to avoid conflicts with existing styles. Key classes:

### Menu Section Classes:

- `.menu-section` - Main container
- `.menu-items-grid` - Grid layout
- `.menu-item-card` - Individual item cards
- `.menu-categories-tabs` - Category navigation

### Menu Widget Classes:

- `.menu-widget` - Widget container
- `.menu-widget-item` - Individual item rows
- `.menu-widget-icon` - Item icons
- `.menu-widget-content` - Item details

## Recent Updates

### ✅ **Enhanced Menu Widget (Latest Version)**

The menu widget has been completely upgraded with enhanced features:

- **Unified Widget**: Single `menu_widget.php` file now handles all use cases
- **Backward Compatibility**: All existing code continues to work unchanged
- **Enhanced Features**: Search, filtering, cart integration, and more
- **Flexible Layouts**: Compact and full layout modes
- **Better UX**: Smooth animations, loading states, and notifications

### Migration Status

- ✅ Old basic widget removed and replaced with enhanced version
- ✅ All menu links updated to use enhanced menu system
- ✅ Backward compatibility maintained for existing implementations
- ✅ Documentation updated with new features and examples

## Future Enhancements

Possible improvements:

- Menu item ratings and reviews
- Nutritional information display
- Multi-language support
- Print-friendly versions
- Advanced analytics integration

## Support

For questions or issues with the menu includes:

1. Check this documentation first
2. Verify database connection and tables
3. Ensure configuration variables are set correctly
4. Test PHP syntax with `php -l filename.php`
