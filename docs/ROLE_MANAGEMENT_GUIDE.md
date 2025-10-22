# Role Management Guide - Shoe Retail ERP

## Overview

This guide provides comprehensive documentation for the enhanced Role Management system in the Shoe Retail ERP. The system handles employee role assignments, role distribution across stores, permission management, and access control.

## Table of Contents

1. [Database Schema](#database-schema)
2. [Core Concepts](#core-concepts)
3. [API Functions](#api-functions)
4. [Usage Examples](#usage-examples)
5. [Role Definitions](#role-definitions)
6. [Permission Model](#permission-model)
7. [Best Practices](#best-practices)

---

## Database Schema

### Tables

#### Roles Table
Stores all available roles with their permissions.

```sql
CREATE TABLE Roles (
    RoleID INT PRIMARY KEY AUTO_INCREMENT,
    RoleName VARCHAR(50) UNIQUE NOT NULL,
    Description TEXT,
    Permissions JSON NOT NULL,
    IsActive ENUM('Yes', 'No') DEFAULT 'Yes',
    CreatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

#### EmployeeRoles Table
Links employees to roles with date ranges.

```sql
CREATE TABLE EmployeeRoles (
    AssignmentID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    RoleID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE,
    AssignedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID)
);
```

#### EmployeeRoleAssignments Table
Distributes employee roles across specific stores with date ranges.

```sql
CREATE TABLE EmployeeRoleAssignments (
    AssignmentID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    RoleID INT NOT NULL,
    StoreID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE,
    AssignedDate TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID),
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID),
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID)
);
```

---

## Core Concepts

### Role
A role is a set of permissions that define what actions a user can perform in the system. Each role has:
- **RoleName**: Unique identifier
- **Description**: Purpose of the role
- **Permissions**: JSON object containing specific permissions and module access
- **IsActive**: Whether the role is currently usable

### Role Assignment
Assigning a role to an employee grants them all permissions associated with that role. Assignments can have:
- **Start Date**: When the assignment becomes active
- **End Date**: When the assignment expires (null = permanent)
- **Status**: Computed based on current date (Permanent, Active, Expired, Pending)

### Role Distribution
Distributing a role to specific stores allows role-specific access control by location. This is useful for:
- Store-specific inventory management
- Regional sales reporting
- Multi-store employee assignments

---

## API Functions

### Role Management Functions

#### `createRole($roleName, $description, $permissions, $isActive = 'Yes')`

Creates or updates a role with comprehensive permissions.

**Parameters:**
- `$roleName` (string): Name of the role
- `$description` (string): Role description
- `$permissions` (array): Permission array as JSON-compatible array
- `$isActive` (string): 'Yes' or 'No'

**Returns:** Array with keys:
- `success` (bool): Operation success status
- `message` (string): Success or error message
- `roleID` (int|null): ID of created/updated role
- `action` (string): 'created' or 'updated'

**Example:**
```php
$permissions = [
    'can_process_sale' => true,
    'can_view_sales' => true,
    'can_apply_discount' => false,
    'module_access' => ['Sales Management']
];

$result = createRole('Cashier', 'Point of Sale operator', $permissions);
if ($result['success']) {
    echo "Role created with ID: " . $result['roleID'];
}
```

---

#### `getAllRoles($activeOnly = false)`

Retrieves all available roles.

**Parameters:**
- `$activeOnly` (bool): If true, returns only active roles

**Returns:** Array of role arrays with decoded Permissions

**Example:**
```php
$roles = getAllRoles(true); // Get only active roles
foreach ($roles as $role) {
    echo $role['RoleName'] . ': ' . $role['Description'];
    print_r($role['Permissions']);
}
```

---

#### `getRoleByID($roleID)`

Gets specific role details by ID.

**Parameters:**
- `$roleID` (int): Role ID

**Returns:** Role array or null if not found

**Example:**
```php
$role = getRoleByID(1);
if ($role) {
    echo "Role: " . $role['RoleName'];
}
```

---

#### `getRoleByName($roleName)`

Gets specific role details by name.

**Parameters:**
- `$roleName` (string): Role name

**Returns:** Role array or null if not found

**Example:**
```php
$role = getRoleByName('Sales Manager');
if ($role) {
    echo "Found role with ID: " . $role['RoleID'];
}
```

---

### Employee Role Assignment Functions

#### `assignRoleToEmployee($employeeID, $roleID, $startDate = null, $endDate = null)`

Assigns a role to an employee.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$roleID` (int): Role ID to assign
- `$startDate` (string|null): Assignment start date (YYYY-MM-DD), default: today
- `$endDate` (string|null): Assignment end date (null = permanent)

**Returns:** Array with success status and assignmentID

**Example:**
```php
$result = assignRoleToEmployee(
    1,                      // Employee ID
    3,                      // Role ID (Inventory Manager)
    '2024-01-01',          // Start date
    '2024-12-31'           // End date
);

if ($result['success']) {
    echo "Assignment ID: " . $result['assignmentID'];
}
```

---

#### `getEmployeeRoles($employeeID, $activeOnly = false)`

Gets all roles assigned to an employee.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$activeOnly` (bool): If true, returns only active assignments

**Returns:** Array of assigned roles with details and status

**Example:**
```php
$roles = getEmployeeRoles(5, true); // Get active roles
foreach ($roles as $role) {
    echo $role['RoleName'] . ' - Status: ' . $role['Status'];
    echo ' (Valid from ' . $role['StartDate'] . ' to ' . ($role['EndDate'] ?? 'Permanent') . ')';
}
```

---

#### `removeRoleAssignment($assignmentID)`

Removes a role assignment from an employee.

**Parameters:**
- `$assignmentID` (int): Assignment ID from EmployeeRoles table

**Returns:** Array with success status

**Example:**
```php
$result = removeRoleAssignment(42);
if ($result['success']) {
    echo "Role assignment removed";
}
```

---

### Role Distribution Functions

#### `distributeRoleToStores($employeeID, $roleID, $storeIDs, $startDate = null, $endDate = null)`

Distributes an employee's role across multiple stores.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$roleID` (int): Role ID to distribute
- `$storeIDs` (array): Array of store IDs
- `$startDate` (string|null): Distribution start date
- `$endDate` (string|null): Distribution end date

**Returns:** Array with success status and distribution results

**Example:**
```php
$stores = [1, 2, 3, 5]; // Store IDs

$result = distributeRoleToStores(
    7,                      // Employee ID
    2,                      // Role ID (Sales Manager)
    $stores,
    '2024-01-01',
    '2024-06-30'
);

if ($result['success']) {
    echo "Role distributed to " . count($result['distributions']) . " stores";
}
```

---

#### `getEmployeeRoleDistributions($employeeID, $storeID = null)`

Gets all role assignments for an employee across stores.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$storeID` (int|null): Optional - filter by specific store

**Returns:** Array of role assignments by store

**Example:**
```php
$distributions = getEmployeeRoleDistributions(7);
foreach ($distributions as $assignment) {
    echo $assignment['RoleName'] . ' at ' . $assignment['StoreName'];
    echo ' (Status: ' . $assignment['Status'] . ')';
}
```

---

### Permission Checking Functions

#### `hasPermission($employeeID, $permissionKey, $storeID = null)`

Checks if an employee has a specific permission.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$permissionKey` (string): Permission key to check
- `$storeID` (int|null): Optional - check for specific store

**Returns:** Boolean - true if employee has permission

**Example:**
```php
if (hasPermission(5, 'can_process_sale')) {
    // Allow user to process sales
    processSale();
}
```

---

#### `hasModuleAccess($employeeID, $moduleName)`

Checks if an employee has access to a specific module.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$moduleName` (string): Module name to check

**Returns:** Boolean - true if employee has module access

**Example:**
```php
if (hasModuleAccess(5, 'Inventory Management')) {
    // Show inventory management interface
}
```

---

#### `hasRole($employeeID, $roleName, $storeID = null)`

Checks if employee has a specific role.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$roleName` (string): Role name to check
- `$storeID` (int|null): Optional - check role for specific store

**Returns:** Boolean - true if employee has the role

**Example:**
```php
if (hasRole(5, 'Store Manager')) {
    // Show store management features
}
```

---

### History & Audit Functions

#### `getRoleAssignmentHistory($employeeID, $limit = null)`

Gets role assignment history for an employee.

**Parameters:**
- `$employeeID` (int): Employee ID
- `$limit` (int|null): Number of records to return (null = all)

**Returns:** Array of role assignments including expired ones

**Example:**
```php
$history = getRoleAssignmentHistory(5, 10);
foreach ($history as $record) {
    echo $record['RoleName'] . ' - ' . $record['Status'];
}
```

---

#### `getEmployeesByRole($roleID, $storeID = null)`

Gets all employees with a specific role.

**Parameters:**
- `$roleID` (int): Role ID
- `$storeID` (int|null): Optional - filter by store

**Returns:** Array of employees with the role

**Example:**
```php
$cashiers = getEmployeesByRole(1); // All Cashiers
foreach ($cashiers as $emp) {
    echo $emp['FirstName'] . ' ' . $emp['LastName'] . ' (' . $emp['Status'] . ')';
}
```

---

### Utility Functions

#### `validateRolePermissions($permissions)`

Validates if role permissions are correctly formatted.

**Parameters:**
- `$permissions` (array): Permissions array to validate

**Returns:** Array with 'valid' boolean and 'errors' array

**Example:**
```php
$validation = validateRolePermissions($permissions);
if (!$validation['valid']) {
    foreach ($validation['errors'] as $error) {
        echo "Error: $error";
    }
}
```

---

#### `getRoleManagementStats()`

Gets summary statistics for role management.

**Returns:** Array with statistics

**Example:**
```php
$stats = getRoleManagementStats();
echo "Total roles: " . $stats['total_roles'];
echo "Active assignments: " . $stats['active_assignments'];
echo "Employees with roles: " . $stats['employees_with_roles'];
```

---

## Usage Examples

### Complete Workflow Example

```php
<?php
require_once 'includes/role_management_functions.php';

// 1. Create a new role
$permissions = [
    'can_manage_inventory' => true,
    'can_process_stock_entry' => true,
    'can_view_stock_reports' => true,
    'can_handle_returns' => true,
    'module_access' => ['Inventory Management', 'Procurement']
];

$createResult = createRole(
    'Inventory Manager',
    'Manages stock entry, tracks inventory levels, handles returns',
    $permissions
);

if (!$createResult['success']) {
    die("Error creating role: " . $createResult['message']);
}

$inventoryRoleID = $createResult['roleID'];

// 2. Assign role to multiple employees
$employeeIDs = [10, 15, 20];
foreach ($employeeIDs as $empID) {
    assignRoleToEmployee($empID, $inventoryRoleID, '2024-01-01');
}

// 3. Distribute role to specific stores
$result = distributeRoleToStores(
    10,                     // Employee ID
    $inventoryRoleID,
    [1, 2, 3, 4],          // Store IDs
    '2024-01-01',
    '2024-12-31'
);

// 4. Check permissions
if (hasRole(10, 'Inventory Manager')) {
    if (hasPermission(10, 'can_manage_inventory')) {
        // Allow inventory management
    }
}

// 5. Get employee details
$roles = getEmployeeRoles(10, true);
foreach ($roles as $role) {
    echo "Role: " . $role['RoleName'] . " - " . $role['Status'];
}

// 6. Get statistics
$stats = getRoleManagementStats();
echo "System has " . $stats['total_roles'] . " total roles";
?>
```

---

## Role Definitions

### Core Roles

#### Cashier
- **Purpose**: Point of Sale operator
- **Key Permissions**: Process sales, view sales, process cash/card payments
- **Module Access**: Sales Management

#### Sales Manager
- **Purpose**: Manages sales operations
- **Key Permissions**: Process sales, refunds, generate reports, apply discounts
- **Module Access**: Sales Management, Customer Management

#### Inventory Manager
- **Purpose**: Manages stock operations
- **Key Permissions**: Manage inventory, process stock entry, handle returns
- **Module Access**: Inventory Management, Procurement

#### Procurement Manager
- **Purpose**: Manages purchase orders
- **Key Permissions**: Create POs, manage suppliers, process goods receipt
- **Module Access**: Procurement, Inventory Management

#### Customer Service
- **Purpose**: Manages customer relations
- **Key Permissions**: Manage customers, support tickets, loyalty points
- **Module Access**: Customer Management, Sales Management

#### Accountant
- **Purpose**: Financial management
- **Key Permissions**: Manage ledger, AR/AP, taxes, financial reports
- **Module Access**: Accounting, Sales Management, Procurement

#### HR Manager
- **Purpose**: Human resources management
- **Key Permissions**: Manage employees, assign roles, process payroll
- **Module Access**: HR, Accounting

#### Store Manager
- **Purpose**: Store operations oversight
- **Key Permissions**: Manage inventory, sales, staff, view reports
- **Module Access**: Inventory Management, Sales Management, Customer Management, HR

#### Admin
- **Purpose**: Full system access
- **Key Permissions**: Manage all, manage users, view audit logs
- **Module Access**: All

### Specialized Inventory Roles

#### Inventory Analyst
- Views stock reports, forecasts demand, analyzes trends

#### Inventory Clerk
- Performs stock counts, updates records, validates data

#### Inventory Counter
- Conducts physical stock checks, reports discrepancies

#### Inventory Encoder
- Enters stock data, ensures record accuracy

---

## Permission Model

Permissions are stored as JSON objects within the Roles table. Each permission is a key-value pair:

### Format
```json
{
  "permission_key": true/false,
  "another_permission": true/false,
  "module_access": ["Module1", "Module2", "All"]
}
```

### Permission Categories

**Sales Permissions:**
- can_process_sale
- can_view_sales
- can_process_refunds
- can_approve_refunds
- can_apply_discount
- can_view_loyalty_points
- can_manage_sales_staff

**Inventory Permissions:**
- can_manage_inventory
- can_process_stock_entry
- can_view_stock_reports
- can_handle_returns
- can_respond_stock_alerts
- can_create_stock_transfer
- can_perform_stock_count

**Customer Permissions:**
- can_manage_customers
- can_create_support_tickets
- can_create_customer
- can_update_customer
- can_view_customer_history

**Financial Permissions:**
- can_manage_ledger
- can_process_ar_ap
- can_generate_financial_reports
- can_manage_taxes
- can_reconcile_accounts

**HR Permissions:**
- can_manage_employees
- can_assign_roles
- can_process_payroll
- can_approve_leave
- can_manage_timesheets
- can_manage_attendance

**Admin Permissions:**
- can_manage_all
- can_manage_users
- can_manage_roles
- can_view_audit_logs

### Module Access
Module access controls which major system sections are visible:
- Sales Management
- Inventory Management
- Customer Management
- Procurement
- Accounting
- HR
- All (grants access to all modules)

---

## Best Practices

### 1. Role Design
- **Single Responsibility**: Each role should have a clear, focused purpose
- **Least Privilege**: Grant only necessary permissions
- **Consistent Naming**: Use clear, descriptive role names
- **Documentation**: Always include detailed descriptions

### 2. Assignment Management
- **Temporary Assignments**: Use start/end dates for temporary role changes
- **Regular Review**: Periodically review employee role assignments
- **Audit Trail**: Monitor role assignment changes through logs

### 3. Permission Checking
- **Early Validation**: Check permissions before showing UI elements
- **Fail Secure**: Deny access by default, grant explicitly
- **Context Awareness**: Consider store context when checking permissions

### 4. Store Distribution
- **Consistency**: Maintain consistent role definitions across stores
- **Scalability**: Use distribution for multi-store deployments
- **Transitions**: Use date ranges for temporary cross-store assignments

### 5. Testing
```php
// Test permission structure
function testRoleConfiguration($roleID) {
    $role = getRoleByID($roleID);
    $validation = validateRolePermissions($role['Permissions']);
    
    if (!$validation['valid']) {
        foreach ($validation['errors'] as $error) {
            error_log("Role $roleID config error: $error");
        }
        return false;
    }
    return true;
}
```

### 6. Performance Optimization
- Cache role/permission data when possible
- Use indexed queries for frequent lookups
- Batch operations when updating multiple assignments

### 7. Security Considerations
- Never trust client-side role validation
- Always verify permissions server-side
- Log all role-related changes
- Use prepared statements (already implemented)
- Validate all user input

---

## Common Tasks

### Task: Change Employee Role
```php
// Remove old role
removeRoleAssignment($oldAssignmentID);

// Assign new role with effective date
assignRoleToEmployee($employeeID, $newRoleID, date('Y-m-d'));
```

### Task: Temporary Role for Specific Project
```php
// Assign role for project duration
$startDate = '2024-03-01';
$endDate = '2024-03-31';

assignRoleToEmployee($employeeID, $projectRoleID, $startDate, $endDate);
```

### Task: Promote Employee
```php
// View current roles and assignment history
$currentRoles = getEmployeeRoles($employeeID, true);
$history = getRoleAssignmentHistory($employeeID, 5);

// Assign new role while keeping existing ones
assignRoleToEmployee($employeeID, $promotedRoleID, date('Y-m-d'));
```

### Task: Create Store-Specific Manager
```php
// Get all stores
$stores = [1, 2, 3]; // Store IDs

// Distribute role
distributeRoleToStores(
    $employeeID,
    $storeManagerRoleID,
    $stores,
    date('Y-m-d'),
    null  // Permanent
);
```

---

## Troubleshooting

### Issue: Permission Check Returns False
- Verify employee has active role assignment
- Check role assignment dates (start/end)
- Ensure permission key is spelled correctly
- Verify role permissions are properly formatted JSON

### Issue: Role Not Found
- Check RoleID/RoleName spelling and case sensitivity
- Verify role exists in database
- Check if role IsActive = 'Yes'

### Issue: Employee Not Found in Distribution
- Verify employee exists in Employees table
- Check if store IDs are valid
- Review assignment date ranges

---

## Support & Maintenance

- Review logs regularly for role-related errors
- Perform quarterly role audits
- Update role permissions as business requirements change
- Document any custom permissions or roles
- Test role changes before deploying to production

