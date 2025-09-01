# Orlando International Resorts - RBAC Enhancement Summary

## Overview

This document outlines the comprehensive Role-Based Access Control (RBAC) enhancement for Orlando International Resorts, based on the detailed "2. User Roles & Permissions" blueprint.

## Current RBAC System Analysis

### What's Currently Implemented ✅

- Basic RBAC structure with `users`, `roles`, `departments`, and `role_permissions` tables
- 6 basic roles: Admin, Staff, DeptManager, Finance, HR, SalesMarketing
- 9 departments: Admin, Bar, Events, Finance, HR, Kitchen, Laundry, Rooms, SalesMarketing
- 1 admin user with full permissions
- Basic permission system with 13 different permissions

### Current Permissions

- `booking.create` - Create bookings
- `txn.create` - Create transactions
- `payment.capture` - Capture payments
- `petty.create` - Create petty cash requests
- `petty.approve` - Approve petty cash
- `inventory.update` - Update inventory
- `expense.create` - Create expenses
- `report.pl.dept.view` - View department P&L
- `report.pl.all.view` - View all P&L reports
- `reconcile.run` - Run reconciliations
- `campaign.manage` - Manage campaigns
- `payroll.run` - Run payroll
- `audit.read` - Read audit logs

## Proposed RBAC Enhancement

### 1. Enhanced Role Structure

#### Executive Level

- **Director** (Role ID 1)
  - Highest authority
  - Full system visibility (finance, operations, reports)
  - Approve budgets & large expenses
  - Permissions: `system.full_access`, `finance.full_access`, `operations.full_access`, `reports.full_access`, `budget.approve`, `expense.approve_large`, `user.manage_all`, `audit.full_access`

#### Management Level

- **IT/Admin** (Role ID 2)

  - System administrator
  - Creates/updates user accounts & permissions
  - Maintains data backups and system settings
  - Permissions: `user.create`, `user.update`, `user.delete`, `role.manage`, `permission.manage`, `system.backup`, `system.settings`, `audit.read`, `system.maintenance`

- **Finance Manager** (Role ID 3)

  - Manages financial transactions, invoices, payroll
  - Oversees procurement approvals
  - Generates financial reports for directors
  - Permissions: `finance.transactions`, `finance.invoices`, `finance.payroll`, `finance.procurement`, `finance.reports`, `finance.reconcile`, `expense.approve`, `budget.view`, `audit.finance`

- **Operations Manager** (Role ID 4)
  - Oversees day-to-day hotel operations
  - Coordinates between departments
  - Approves operational requests
  - Permissions: `operations.oversight`, `department.coordinate`, `operational.approve`, `reports.operations`, `staff.manage`, `schedule.manage`, `quality.control`

#### Department Head Level

- **Head of Accommodation** (Role ID 7)

  - Manages reservations & staff
  - Permissions: `accommodation.manage`, `reservations.manage`, `accommodation.staff`, `room.status.manage`, `accommodation.reports`, `guest.services`, `accommodation.revenue.view`, `accommodation.occupancy.view`

- **Head of Kitchen** (Role ID 8)

  - Manages chefs, menu, stock
  - Permissions: `kitchen.manage`, `menu.manage`, `kitchen.stock`, `kitchen.staff`, `food.orders`, `kitchen.reports`, `food.cost.manage`, `kitchen.cost.analysis`, `kitchen.profit.margin`

- **Head of Bar** (Role ID 9)

  - Manages bartenders, inventory
  - Permissions: `bar.manage`, `bar.inventory`, `bar.staff`, `bar.orders`, `bar.sales`, `bar.reports`, `bar.stock.alerts`, `bar.profit.analysis`

- **Head of Housekeeping** (Role ID 10)

  - Manages cleaning schedules & staff
  - Permissions: `housekeeping.manage`, `cleaning.schedules`, `housekeeping.staff`, `room.cleaning.status`, `laundry.services`, `housekeeping.reports`, `housekeeping.efficiency`, `laundry.revenue`

- **Head of Maintenance** (Role ID 11)
  - Oversees repairs & service requests
  - Permissions: `maintenance.manage`, `repairs.oversee`, `maintenance.staff`, `service.requests`, `maintenance.schedules`, `maintenance.reports`, `parts.inventory`, `maintenance.cost.analysis`, `preventive.maintenance`

#### Staff Level

- **Accommodation Staff** (Role ID 12)

  - Book rooms, check-in/out guests
  - Permissions: `booking.create`, `booking.view`, `checkin.process`, `checkout.process`, `guest.info.view`, `room.status.view`, `payment.capture`, `accommodation.revenue.view`

- **Kitchen Staff** (Role ID 13)

  - Log orders & update stock
  - Permissions: `food.orders.view`, `food.orders.update`, `kitchen.stock.view`, `kitchen.stock.update`, `menu.view`, `food.cost.view`, `kitchen.cost.view`

- **Bar Staff** (Role ID 14)

  - Serve drinks, update sales
  - Permissions: `bar.orders.create`, `bar.orders.view`, `bar.sales.update`, `bar.inventory.view`, `bar.stock.update`, `payment.capture`, `bar.profit.view`

- **Housekeeping Staff** (Role ID 15)

  - Update room status
  - Permissions: `room.status.update`, `cleaning.tasks.view`, `cleaning.tasks.update`, `laundry.orders.create`, `housekeeping.reports.view`, `housekeeping.efficiency.view`

- **Maintenance Staff** (Role ID 16)
  - Log repair jobs
  - Permissions: `maintenance.requests.create`, `maintenance.requests.view`, `repair.jobs.log`, `maintenance.schedules.view`, `parts.inventory.view`, `maintenance.reports.view`, `maintenance.cost.view`

### 2. Sample Users Created

#### Executive Users

- `director1` - Director role (password: 1234)
- `director_admin` - Updated from original Admin user

#### Management Users

- `itadmin` - IT/Admin role (password: 1234)
- `finance_mgr` - Finance Manager role (password: 1234)
- `ops_mgr` - Operations Manager role (password: 1234)

#### Department Heads

- `head_accommodation` - Head of Accommodation (password: 1234)
- `head_kitchen` - Head of Kitchen (password: 1234)
- `head_bar` - Head of Bar (password: 1234)
- `head_housekeeping` - Head of Housekeeping (password: 1234)
- `head_maintenance` - Head of Maintenance (password: 1234)

#### Staff Users

- `receptionist1` - Accommodation Staff (password: 1234)
- `chef1` - Kitchen Staff (password: 1234)
- `bartender1` - Bar Staff (password: 1234)
- `housekeeper1` - Housekeeping Staff (password: 1234)
- `technician1` - Maintenance Staff (password: 1234)

## Implementation Files

### 1. Database Enhancement

- **`rbac_enhancement_plan.sql`** - Complete SQL script to enhance the RBAC system
  - Updates existing roles to match blueprint
  - Adds new department-specific roles
  - Creates comprehensive permission system
  - Adds sample users for each role

### 2. User Management Interface

- **`admin/user_management.php`** - Comprehensive user management interface

  - Create, edit, delete users
  - Assign roles and departments
  - Reset passwords
  - View all users with their roles and permissions

- **`admin/get_user_data.php`** - Helper file for AJAX requests
  - Fetches user data for editing

### 3. Updated Navigation

- **`admin/home.php`** - Updated to include User Management link in Settings section

## Permission Categories

### System Permissions

- `system.full_access` - Full system access
- `system.backup` - System backup access
- `system.settings` - System settings access
- `system.maintenance` - System maintenance access

### Finance Permissions

- `finance.full_access` - Full finance access
- `finance.transactions` - Financial transactions
- `finance.invoices` - Invoice management
- `finance.payroll` - Payroll management
- `finance.procurement` - Procurement management
- `finance.reports` - Financial reports
- `finance.reconcile` - Reconciliation access

### Operations Permissions

- `operations.full_access` - Full operations access
- `operations.oversight` - Operations oversight
- `department.coordinate` - Department coordination
- `operational.approve` - Operational approvals
- `reports.operations` - Operations reports

### Department-Specific Permissions

- **Accommodation**: `accommodation.manage`, `reservations.manage`, `accommodation.staff`, `room.status.manage`, `accommodation.reports`, `guest.services`
- **Kitchen**: `kitchen.manage`, `menu.manage`, `kitchen.stock`, `kitchen.staff`, `food.orders`, `kitchen.reports`, `food.cost.manage`
- **Bar**: `bar.manage`, `bar.inventory`, `bar.staff`, `bar.orders`, `bar.sales`, `bar.reports`, `bar.stock.alerts`
- **Housekeeping**: `housekeeping.manage`, `cleaning.schedules`, `housekeeping.staff`, `room.cleaning.status`, `laundry.services`, `housekeeping.reports`
- **Maintenance**: `maintenance.manage`, `repairs.oversee`, `maintenance.staff`, `service.requests`, `maintenance.schedules`, `maintenance.reports`, `parts.inventory`

## Next Steps

### 1. Execute the Enhancement

Run the `rbac_enhancement_plan.sql` script in phpMyAdmin to:

- Update existing roles
- Add new department-specific roles
- Create comprehensive permissions
- Add sample users

### 2. Test the System

- Login with different user accounts to test role-based access
- Verify that users can only access features appropriate to their role
- Test the user management interface

### 3. Customize Permissions

- Review and adjust permissions based on specific business requirements
- Add or modify permissions as needed for specific workflows
- Ensure proper segregation of duties

### 4. Training and Documentation

- Train department heads on user management
- Document role responsibilities and permissions
- Create user guides for different roles

## Security Considerations

### Password Policy

- All sample users have password "1234" - change immediately in production
- Implement strong password policy
- Enable password expiration

### Access Control

- Regular review of user permissions
- Implement least privilege principle
- Audit user access regularly

### Data Protection

- Encrypt sensitive data
- Implement session timeout
- Log all user actions for audit purposes

## Benefits of Enhanced RBAC

1. **Improved Security** - Granular access control based on job responsibilities
2. **Better Compliance** - Clear separation of duties and audit trails
3. **Operational Efficiency** - Users see only relevant information and functions
4. **Scalability** - Easy to add new roles and permissions as business grows
5. **Risk Management** - Reduced risk of unauthorized access and data breaches

## Conclusion

This enhanced RBAC system provides Orlando International Resorts with a comprehensive, scalable, and secure access control framework that aligns with the detailed blueprint specifications. The system supports the resort's operational needs while maintaining security and compliance standards.
