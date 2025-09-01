# 🏗️ Admin Navigation Structure

## 📁 File Organization

### **Main Include Files:**

```
includes/admin/
├── header.php      # Main header with all common elements
├── sidebar.php     # Separate sidebar navigation file
└── footer.php      # Footer file
```

## 🔧 How It Works

### **1. Header.php** (`includes/admin/header.php`)

**Purpose:** Main wrapper file that includes everything needed for admin pages

**Contains:**

- ✅ Session management and authentication
- ✅ User role detection and permissions
- ✅ CSS styling and Bootstrap includes
- ✅ HTML document structure
- ✅ Top header bar with search and user dropdown
- ✅ **Includes the sidebar file**

**Usage in every admin page:**

```php
<?php
$page_title = 'Your Page Title';
include '../includes/admin/header.php';
?>
```

### **2. Sidebar.php** (`includes/admin/sidebar.php`)

**Purpose:** Standalone sidebar navigation that can be reused anywhere

**Contains:**

- ✅ Complete sidebar HTML structure
- ✅ Role-based navigation modules
- ✅ Active page highlighting
- ✅ All navigation links organized by modules

**Can be used independently:**

```php
<?php
// If you need just the sidebar somewhere else
include '../includes/admin/sidebar.php';
?>
```

## 🎯 Benefits of This Structure

### **Modular Design:**

- 📁 **Separation of Concerns** - Sidebar logic is separate from header
- 🔧 **Easy Maintenance** - Update sidebar without touching header
- 🔄 **Reusability** - Sidebar can be used in other contexts
- 📋 **Better Organization** - Clear file structure

### **How Pages Use It:**

Every admin page now follows this simple pattern:

```php
<?php
$page_title = 'Page Name';
include '../includes/admin/header.php';  // This automatically includes sidebar.php
include '../includes/components/alerts.php';
?>

<!-- Your page content here -->

        </div>
    </main>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
```

## 📊 Navigation Modules

The sidebar is organized into logical modules:

### **🏆 Executive Center**

- Director Dashboard
- Revenue Analytics
- Marketing Campaigns

### **⚙️ Operations**

- Operations Dashboard
- Housekeeping
- Maintenance
- Inventory Management

### **📅 Reservations**

- All Reservations
- New Booking
- Room Management

### **🍽️ Restaurant & Bar**

- Point of Sale
- Orders
- Menu Management
- Kitchen & Bar Inventory

### **💰 Finance & Accounting**

- Finance Dashboard
- Accounting Center
- Financial Reports
- Transactions
- Journal Entries

### **📈 Reports & Analytics**

- Bar Sales Reports
- Food Cost Reports
- Room Revenue

### **🔧 Administration**

- User Management
- Settings
- Help Center

### **🏠 General**

- Overview
- Logout

## 🔐 Role-Based Access

Each module checks user permissions using `hasModuleAccess()`:

```php
<?php if (hasModuleAccess($user_role, ['Admin', 'Director', 'CEO'])): ?>
    <!-- Executive module content -->
<?php endif; ?>
```

**Role Hierarchy:**

- **Admin & Director** → Full access to all modules
- **Operations Manager** → Operations, Reservations, Reports
- **Finance Staff** → Finance, Accounting, Reports
- **Staff** → Basic operations and reservations

## ✅ Consistency Achieved

**Before:** Multiple different navigation systems causing confusion
**After:** Single, consistent navigation across ALL admin pages

**Result:**

- 🎯 **Same sidebar everywhere** - No more navigation confusion
- 🔒 **Role-based access** - Users see only what they can access
- 📱 **Mobile responsive** - Works on all devices
- 🎨 **Professional design** - Consistent, modern interface

## 🚀 Usage Examples

### **Standard Page:**

```php
<?php
$page_title = 'Finance Dashboard';
include '../includes/admin/header.php';
?>
<!-- Page content -->
```

### **If You Need Custom Navigation:**

```php
<!-- Include only the sidebar in a custom layout -->
<?php include '../includes/admin/sidebar.php'; ?>
```

This structure ensures every admin page has the exact same navigation experience while maintaining clean, modular code! 🎉
