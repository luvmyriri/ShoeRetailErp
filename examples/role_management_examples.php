<?php
/**
 * Role Management Implementation Examples
 * Shoe Retail ERP System
 * 
 * This file provides practical examples of using the role management system
 * for common tasks in the ERP application.
 */

require_once '../includes/role_management_functions.php';

// =====================================================
// EXAMPLE 1: Initialize System Roles
// =====================================================

function initializeSystemRoles() {
    echo "\n=== Initializing System Roles ===\n";
    
    // Define all core roles
    $rolesData = [
        // Core roles
        [
            'name' => 'Cashier',
            'description' => 'Point of Sale operator who processes customer purchases',
            'permissions' => [
                'can_process_sale' => true,
                'can_view_sales' => true,
                'can_process_cash' => true,
                'can_process_card' => true,
                'can_view_inventory_limited' => true,
                'can_apply_discount' => false,
                'can_process_refund' => false,
                'module_access' => ['Sales Management']
            ]
        ],
        [
            'name' => 'Sales Manager',
            'description' => 'Manages sales operations, approves returns and generates sales reports',
            'permissions' => [
                'can_process_sale' => true,
                'can_view_sales' => true,
                'can_process_refunds' => true,
                'can_approve_refunds' => true,
                'can_generate_sales_reports' => true,
                'can_apply_discount' => true,
                'can_view_loyalty_points' => true,
                'can_manage_sales_staff' => true,
                'module_access' => ['Sales Management', 'Customer Management']
            ]
        ],
        [
            'name' => 'Inventory Manager',
            'description' => 'Manages stock entry, tracks inventory levels, handles returns',
            'permissions' => [
                'can_manage_inventory' => true,
                'can_process_stock_entry' => true,
                'can_view_stock_reports' => true,
                'can_handle_returns' => true,
                'can_respond_stock_alerts' => true,
                'can_create_stock_transfer' => true,
                'can_view_purchase_orders' => true,
                'can_manage_inventory_staff' => true,
                'module_access' => ['Inventory Management', 'Procurement']
            ]
        ],
        [
            'name' => 'Store Manager',
            'description' => 'Oversees store operations including inventory, sales, and staff',
            'permissions' => [
                'can_manage_inventory' => true,
                'can_view_inventory' => true,
                'can_process_sale' => true,
                'can_process_refunds' => true,
                'can_view_store_reports' => true,
                'can_manage_store_staff' => true,
                'can_view_store_sales' => true,
                'can_view_employee_assignments' => true,
                'module_access' => ['Inventory Management', 'Sales Management', 'Customer Management', 'HR']
            ]
        ],
        [
            'name' => 'Admin',
            'description' => 'Full system access for oversight, user management, configurations',
            'permissions' => [
                'can_manage_all' => true,
                'can_manage_users' => true,
                'can_manage_roles' => true,
                'can_manage_systems' => true,
                'can_generate_reports' => true,
                'can_manage_employees' => true,
                'can_view_audit_logs' => true,
                'module_access' => ['All']
            ]
        ]
    ];
    
    foreach ($rolesData as $roleData) {
        $result = createRole(
            $roleData['name'],
            $roleData['description'],
            $roleData['permissions']
        );
        
        if ($result['success']) {
            echo "✓ Created role: {$roleData['name']} (ID: {$result['roleID']})\n";
        } else {
            echo "✗ Error creating {$roleData['name']}: {$result['message']}\n";
        }
    }
}

// =====================================================
// EXAMPLE 2: Assign Roles to New Employee
// =====================================================

function assignRolesToNewEmployee($employeeID, $employeeName, $roleName) {
    echo "\n=== Assigning Role to New Employee ===\n";
    echo "Employee: $employeeName (ID: $employeeID)\n";
    echo "Role: $roleName\n";
    
    // Get role by name
    $role = getRoleByName($roleName);
    if (!$role) {
        echo "✗ Role '$roleName' not found\n";
        return false;
    }
    
    // Assign role (starting today, permanent)
    $result = assignRoleToEmployee(
        $employeeID,
        $role['RoleID'],
        date('Y-m-d'),
        null  // null = permanent
    );
    
    if ($result['success']) {
        echo "✓ Role assigned successfully (Assignment ID: {$result['assignmentID']})\n";
        
        // Get and display assigned roles
        $roles = getEmployeeRoles($employeeID, true);
        echo "\nEmployee's Active Roles:\n";
        foreach ($roles as $r) {
            echo "  - {$r['RoleName']}: {$r['Description']}\n";
            echo "    Valid from {$r['StartDate']} to " . ($r['EndDate'] ?? 'Permanent') . "\n";
        }
        return true;
    } else {
        echo "✗ Error assigning role: {$result['message']}\n";
        return false;
    }
}

// =====================================================
// EXAMPLE 3: Distribute Role Across Multiple Stores
// =====================================================

function distributeSalesManagerToStores($employeeID, $storeIDs, $startDate, $endDate = null) {
    echo "\n=== Distributing Sales Manager Role to Stores ===\n";
    
    // Get Sales Manager role
    $role = getRoleByName('Sales Manager');
    if (!$role) {
        echo "✗ Sales Manager role not found\n";
        return false;
    }
    
    // Distribute to stores
    $result = distributeRoleToStores(
        $employeeID,
        $role['RoleID'],
        $storeIDs,
        $startDate,
        $endDate
    );
    
    if ($result['success']) {
        echo "✓ Role distributed successfully\n";
        echo "Distributions created:\n";
        foreach ($result['distributions'] as $dist) {
            echo "  - Store ID {$dist['storeID']}: Assignment ID {$dist['assignmentID']}\n";
        }
        
        // Display role distributions
        $distributions = getEmployeeRoleDistributions($employeeID);
        echo "\nEmployee's Store Distributions:\n";
        foreach ($distributions as $dist) {
            echo "  - {$dist['StoreName']}: {$dist['RoleName']} ({$dist['Status']})\n";
        }
        return true;
    } else {
        echo "✗ Error distributing role: {$result['message']}\n";
        return false;
    }
}

// =====================================================
// EXAMPLE 4: Check Employee Permissions
// =====================================================

function checkEmployeePermissions($employeeID, $employeeName) {
    echo "\n=== Checking Employee Permissions ===\n";
    echo "Employee: $employeeName (ID: $employeeID)\n";
    
    // Get all roles
    $roles = getEmployeeRoles($employeeID, true);
    if (empty($roles)) {
        echo "✗ Employee has no active roles\n";
        return false;
    }
    
    echo "\nActive Roles and Permissions:\n";
    foreach ($roles as $role) {
        echo "\n► {$role['RoleName']} (Status: {$role['Status']})\n";
        echo "  Description: {$role['Description']}\n";
        
        $permissions = $role['Permissions'];
        if (isset($permissions['module_access'])) {
            echo "  Module Access: " . implode(', ', $permissions['module_access']) . "\n";
        }
        
        echo "  Specific Permissions:\n";
        foreach ($permissions as $key => $value) {
            if ($key !== 'module_access' && $value === true) {
                echo "    ✓ $key\n";
            }
        }
    }
    
    // Check specific permissions
    echo "\nPermission Checks:\n";
    
    $permissionsToCheck = [
        'can_process_sale',
        'can_manage_inventory',
        'can_generate_sales_reports',
        'can_approve_refunds',
        'can_manage_all'
    ];
    
    foreach ($permissionsToCheck as $permission) {
        $has = hasPermission($employeeID, $permission);
        $status = $has ? '✓' : '✗';
        echo "  $status $permission\n";
    }
    
    // Check module access
    echo "\nModule Access Checks:\n";
    $modules = [
        'Sales Management',
        'Inventory Management',
        'Customer Management',
        'Procurement',
        'Accounting',
        'HR'
    ];
    
    foreach ($modules as $module) {
        $has = hasModuleAccess($employeeID, $module);
        $status = $has ? '✓' : '✗';
        echo "  $status $module\n";
    }
    
    return true;
}

// =====================================================
// EXAMPLE 5: Get Employees by Role
// =====================================================

function getEmployeesByRoleExample($roleName) {
    echo "\n=== Employees with Role: $roleName ===\n";
    
    $role = getRoleByName($roleName);
    if (!$role) {
        echo "✗ Role '$roleName' not found\n";
        return false;
    }
    
    $employees = getEmployeesByRole($role['RoleID']);
    
    if (empty($employees)) {
        echo "No employees found with this role\n";
        return true;
    }
    
    echo "Found " . count($employees) . " employee(s):\n";
    foreach ($employees as $emp) {
        echo "\n► {$emp['FirstName']} {$emp['LastName']}\n";
        echo "  Email: {$emp['Email']}\n";
        echo "  Status: {$emp['Status']}\n";
        echo "  Valid from {$emp['StartDate']} to " . ($emp['EndDate'] ?? 'Permanent') . "\n";
    }
    
    return true;
}

// =====================================================
// EXAMPLE 6: Employee Role History
// =====================================================

function viewEmployeeRoleHistory($employeeID, $employeeName, $limit = 10) {
    echo "\n=== Role Assignment History for $employeeName ===\n";
    
    $history = getRoleAssignmentHistory($employeeID, $limit);
    
    if (empty($history)) {
        echo "No role assignment history found\n";
        return false;
    }
    
    echo "Total assignments: " . count($history) . "\n\n";
    
    foreach ($history as $record) {
        echo "► {$record['RoleName']}\n";
        echo "  Status: {$record['Status']}\n";
        echo "  Assigned: {$record['AssignedDate']}\n";
        echo "  Period: {$record['StartDate']} to " . ($record['EndDate'] ?? 'Ongoing') . "\n";
        echo "---\n";
    }
    
    return true;
}

// =====================================================
// EXAMPLE 7: Promote Employee
// =====================================================

function promoteEmployee($employeeID, $employeeName, $newRoleName) {
    echo "\n=== Promoting Employee ===\n";
    echo "Employee: $employeeName\n";
    echo "New Role: $newRoleName\n";
    
    // Get current roles
    $currentRoles = getEmployeeRoles($employeeID, true);
    echo "\nCurrent Roles: " . count($currentRoles) . "\n";
    foreach ($currentRoles as $role) {
        echo "  - {$role['RoleName']}\n";
    }
    
    // Get new role
    $newRole = getRoleByName($newRoleName);
    if (!$newRole) {
        echo "✗ Role '$newRoleName' not found\n";
        return false;
    }
    
    // Assign new role
    $result = assignRoleToEmployee(
        $employeeID,
        $newRole['RoleID'],
        date('Y-m-d'),
        null
    );
    
    if ($result['success']) {
        echo "\n✓ Promotion successful!\n";
        
        // Display updated roles
        $updatedRoles = getEmployeeRoles($employeeID, true);
        echo "\nUpdated Roles: " . count($updatedRoles) . "\n";
        foreach ($updatedRoles as $role) {
            echo "  - {$role['RoleName']}: {$role['Status']}\n";
        }
        return true;
    } else {
        echo "✗ Error assigning new role: {$result['message']}\n";
        return false;
    }
}

// =====================================================
// EXAMPLE 8: Temporary Assignment
// =====================================================

function assignTemporaryRole($employeeID, $employeeName, $roleName, $startDate, $endDate) {
    echo "\n=== Temporary Role Assignment ===\n";
    echo "Employee: $employeeName\n";
    echo "Role: $roleName\n";
    echo "Period: $startDate to $endDate\n";
    
    $role = getRoleByName($roleName);
    if (!$role) {
        echo "✗ Role '$roleName' not found\n";
        return false;
    }
    
    $result = assignRoleToEmployee(
        $employeeID,
        $role['RoleID'],
        $startDate,
        $endDate
    );
    
    if ($result['success']) {
        echo "\n✓ Temporary role assigned successfully (Assignment ID: {$result['assignmentID']})\n";
        return true;
    } else {
        echo "✗ Error: {$result['message']}\n";
        return false;
    }
}

// =====================================================
// EXAMPLE 9: System Statistics
// =====================================================

function displayRoleManagementStats() {
    echo "\n=== Role Management Statistics ===\n";
    
    $stats = getRoleManagementStats();
    
    echo "Total Roles: {$stats['total_roles']}\n";
    echo "Active Roles: {$stats['active_roles']}\n";
    echo "Total Assignments: {$stats['total_assignments']}\n";
    echo "Active Assignments: {$stats['active_assignments']}\n";
    echo "Total Store Distributions: {$stats['total_distributions']}\n";
    echo "Employees with Roles: {$stats['employees_with_roles']}\n";
    
    // Calculate percentages
    if ($stats['total_assignments'] > 0) {
        $activePercentage = ($stats['active_assignments'] / $stats['total_assignments']) * 100;
        echo "Active/Total Assignments: " . number_format($activePercentage, 2) . "%\n";
    }
}

// =====================================================
// EXAMPLE 10: List All Roles
// =====================================================

function displayAllRoles() {
    echo "\n=== All System Roles ===\n";
    
    $roles = getAllRoles(true);
    
    echo "Total Active Roles: " . count($roles) . "\n\n";
    
    foreach ($roles as $role) {
        echo "► {$role['RoleName']} (ID: {$role['RoleID']})\n";
        echo "  Description: {$role['Description']}\n";
        
        $permissions = $role['Permissions'];
        if (isset($permissions['module_access'])) {
            echo "  Modules: " . implode(', ', $permissions['module_access']) . "\n";
        }
        
        // Count enabled permissions
        $enabledCount = 0;
        foreach ($permissions as $key => $value) {
            if ($key !== 'module_access' && $value === true) {
                $enabledCount++;
            }
        }
        echo "  Enabled Permissions: $enabledCount\n";
        echo "---\n";
    }
}

// =====================================================
// MAIN EXECUTION (Examples)
// =====================================================

// Uncomment below to run examples:

/*
// Initialize system roles
initializeSystemRoles();

// Assign roles to new employee
assignRolesToNewEmployee(1, 'John Doe', 'Cashier');

// Distribute role across stores
distributeSalesManagerToStores(2, [1, 2, 3], date('Y-m-d'), date('Y-m-d', strtotime('+6 months')));

// Check permissions
checkEmployeePermissions(1, 'John Doe');

// Get employees by role
getEmployeesByRoleExample('Cashier');

// View history
viewEmployeeRoleHistory(1, 'John Doe');

// Promote employee
promoteEmployee(1, 'John Doe', 'Sales Manager');

// Temporary assignment
assignTemporaryRole(3, 'Jane Smith', 'Sales Manager', date('Y-m-d'), date('Y-m-d', strtotime('+1 month')));

// Display statistics
displayRoleManagementStats();

// List all roles
displayAllRoles();
*/

?>
