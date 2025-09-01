# Menu Widget Migration Guide

## Migration Notice

**The enhanced menu widget has now replaced the basic menu widget as the default `menu_widget.php`.**

This file serves as a migration guide for updating your existing implementations. The new widget is backward compatible and provides all the functionality of the old widget plus many new features.

## Features

### 🔍 **Search & Filtering**

- **Live Search**: Real-time search through menu items by name and description
- **Category Filtering**: Filter by menu categories (appetizers, mains, drinks, etc.)
- **Dietary Options**: Filter by dietary preferences (vegetarian, gluten-free, spicy)
- **Sorting**: Sort by name, price (low to high, high to low), or category

### 🛒 **Cart Integration**

- **Quantity Selectors**: Interactive quantity controls for each item
- **Add to Cart**: Direct cart functionality with loading states
- **Cart Indicator**: Shows current cart count in widget header
- **Cart Actions**: Quick access to view cart and checkout

### 🎨 **Styling Options**

- **Compact Mode**: Space-efficient layout for sidebars
- **Full Mode**: Expanded layout with more details
- **Responsive Design**: Adapts to mobile and desktop screens
- **Animated Interactions**: Smooth transitions and hover effects

### 🏷️ **Product Features**

- **Dietary Badges**: Visual indicators for vegetarian, gluten-free, spicy items
- **Image Support**: Product images with fallback icons
- **Price Display**: Formatted pricing with currency
- **Category Labels**: Clear categorization of items

## Usage

### Basic Implementation

```php
<?php include('includes/menu_widget_enhanced.php'); ?>
```

### Customized Implementation

```php
<?php
// Configure widget parameters
$widget_title = "Featured Dishes";
$widget_items_limit = 8;
$widget_show_prices = true;
$widget_show_filters = true;
$widget_show_search = true;
$widget_show_cart_actions = true;
$widget_style = "compact"; // or "full"

// Include the enhanced widget
include('includes/menu_widget_enhanced.php');
?>
```

## Configuration Parameters

| Parameter                   | Type   | Default    | Description                        |
| --------------------------- | ------ | ---------- | ---------------------------------- |
| `$widget_title`             | string | "Our Menu" | Widget title displayed in header   |
| `$widget_items_limit`       | int    | 8          | Maximum number of items to display |
| `$widget_show_prices`       | bool   | true       | Show/hide item prices              |
| `$widget_show_filters`      | bool   | true       | Show/hide filter controls          |
| `$widget_show_search`       | bool   | true       | Show/hide search box               |
| `$widget_show_cart_actions` | bool   | true       | Show/hide cart functionality       |
| `$widget_style`             | string | "compact"  | Layout style: "compact" or "full"  |

## Widget Styles

### Compact Style

- Horizontal layout for each item
- Smaller images and condensed text
- Perfect for sidebars and narrow spaces
- Shows essential information only

### Full Style

- Vertical card layout for each item
- Larger images and detailed descriptions
- Better for main content areas
- Shows comprehensive item details

## URL Parameters

The widget responds to URL parameters for maintaining filter state:

- `widget_search`: Search query string
- `widget_category`: Category ID for filtering
- `widget_dietary`: Dietary filter (vegetarian, gluten_free, spicy)
- `widget_sort`: Sort order (name, price_low, price_high, category)

## JavaScript Functions

### Filter Management

```javascript
applyWidgetFilters(); // Apply current filter settings
clearWidgetFilters(); // Clear all filters
```

### Cart Operations

```javascript
changeQuantity(itemId, change); // Adjust item quantity
addToCartFromWidget(itemId, name, price, type); // Add item to cart
```

### Notifications

```javascript
showWidgetNotification(message, type); // Show success/error messages
```

## Examples

### Sidebar Menu Widget

```php
<?php
$widget_title = "Quick Menu";
$widget_items_limit = 6;
$widget_style = "compact";
$widget_show_filters = false;  // Hide filters for cleaner sidebar
include('includes/menu_widget_enhanced.php');
?>
```

### Featured Items Section

```php
<?php
$widget_title = "Today's Specials";
$widget_items_limit = 12;
$widget_style = "full";
$widget_show_search = true;
$widget_show_filters = true;
include('includes/menu_widget_enhanced.php');
?>
```

### Category-Specific Widget

```php
<?php
// Show only appetizers
$_GET['widget_category'] = 1; // Appetizers category ID
$widget_title = "Appetizers";
$widget_show_filters = false;
include('includes/menu_widget_enhanced.php');
?>
```

## Integration with Pages

### Homepage Integration

```php
<!-- In index.php -->
<div class="col-md-4">
    <?php
    $widget_title = "Featured Menu";
    $widget_style = "compact";
    $widget_items_limit = 6;
    include('includes/menu_widget_enhanced.php');
    ?>
</div>
```

### Admin Dashboard Integration

```php
<!-- In admin dashboards -->
<div class="dashboard-widget">
    <?php
    $widget_title = "Popular Items";
    $widget_show_cart_actions = false;
    $widget_show_filters = false;
    include('includes/menu_widget_enhanced.php');
    ?>
</div>
```

## Compatibility

- **PHP**: Requires PHP 7.0+
- **MySQL**: Works with existing menu_items and menu_categories tables
- **JavaScript**: Uses jQuery if available, graceful fallback without it
- **Bootstrap**: Compatible with Bootstrap 3.x and 4.x
- **Font Awesome**: Requires Font Awesome for icons

## Database Requirements

The widget uses the following database tables:

- `menu_items`: Menu item data
- `menu_categories`: Category information

Required fields in `menu_items`:

- `id`, `name`, `description`, `price`, `category_id`
- `is_available`, `is_vegetarian`, `is_gluten_free`, `is_spicy`
- `image`, `display_order`

## Performance Notes

- Uses efficient SQL queries with proper LIMIT clauses
- Implements lazy loading for images
- Minimal JavaScript footprint
- Responsive CSS with mobile-first approach
- Caches category data for multiple widget instances

## Troubleshooting

### Common Issues

1. **Cart not working**: Ensure CartManager class is available
2. **Images not showing**: Check image paths and file permissions
3. **Filters not applying**: Verify URL parameter handling
4. **Styling issues**: Check for CSS conflicts with theme

### Debug Tips

- Enable PHP error reporting to catch database issues
- Use browser developer tools to check JavaScript errors
- Verify database table structure matches requirements
- Test with different widget configurations

## Migration from Basic Widget

**No action required!** The new enhanced widget is backward compatible.

### Automatic Migration

- All existing `include('includes/menu_widget.php')` calls now use the enhanced widget
- Old configuration parameters still work
- Basic functionality is preserved

### To Enable New Features

Simply add the new configuration parameters:

```php
<?php
// Old parameters (still work)
$widget_title = "Our Menu";
$widget_items_limit = 6;
$widget_show_prices = true;
$widget_show_order_btn = true;  // Maps to $widget_show_cart_actions

// New enhanced parameters (optional)
$widget_style = "compact";           // or "full"
$widget_show_filters = true;         // Add filtering
$widget_show_search = true;          // Add search
$widget_show_cart_actions = true;    // Enhanced cart features

include('includes/menu_widget.php');
?>
```

### Parameter Mapping

- `$widget_show_order_btn` → `$widget_show_cart_actions` (backward compatible)
- All other old parameters work unchanged
- New parameters default to sensible values
