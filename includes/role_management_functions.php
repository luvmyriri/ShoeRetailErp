<?php
/**
 * Role Management Functions
 * Enhanced HR Module - Role Assignment, Distribution, and Permission Management
 * Shoe Retail ERP System
 * 
 * This module provides comprehensive role management including:
 * - Role creation and updates with permissions
 * - Employee role assignments
 * - Role distribution across stores
 * - Permission checking and validation
 * - Role history and audit trail
 */

require_once 'db_connection.php';
require_once 'logging_functions.php';

// =====================================================
// ROLE CREATION & MANAGEMENT
// =====================================================

/**
 * Creates or updates a role with comprehensive permissions
 * 
 * @param string $roleName Name of the role
 * @param string $description Role description
 * @param array $permissions Array of permissions as JSON-compatible array
 * @param string $isActive 'Yes' or 'No'
 * @return array Result array with success status and roleID or error message
 */
function createRole($roleName, $description, $permissions, $isActive = 'Yes') {
    global $conn;
    
    try {
        // Validate input
        if (empty($roleName) || empty($description)) {
            return [
                'success' => false,
                'message' => 'Role name and description are required',
                'roleID' => null
            ];
        }
        
        // Convert permissions array to JSON
        $permissionsJSON = json_encode($permissions);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'message' => 'Invalid permissions format: ' . json_last_error_msg(),
                'roleID' => null
            ];
        }
        
        // Check if role already exists
        $checkQuery = "SELECT RoleID FROM Roles WHERE RoleName = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            // Update existing role
            $row = $result->fetch_assoc();
            $roleID = $row['RoleID'];
            
            $updateQuery = "UPDATE Roles SET Description = ?, Permissions = ?, IsActive = ? WHERE RoleID = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssi", $description, $permissionsJSON, $isActive, $roleID);
            
            if (!$stmt->execute()) {
                throw new Exception("Error updating role: " . $stmt->error);
            }
            
            logAction('ROLE_UPDATE', $roleID, "Updated role: $roleName");
            
            return [
                'success' => true,
                'message' => "Role '$roleName' updated successfully",
                'roleID' => $roleID,
                'action' => 'updated'
            ];
        } else {
            // Insert new role
            $insertQuery = "INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("ssss", $roleName, $description, $permissionsJSON, $isActive);
            
            if (!$stmt->execute()) {
                throw new Exception("Error creating role: " . $stmt->error);
            }
            
            $roleID = $stmt->insert_id;
            logAction('ROLE_CREATE', $roleID, "Created role: $roleName");
            
            return [
                'success' => true,
                'message' => "Role '$roleName' created successfully",
                'roleID' => $roleID,
                'action' => 'created'
            ];
        }
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in createRole: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'roleID' => null
        ];
    }
}

/**
 * Get all available roles
 * 
 * @param bool $activeOnly If true, returns only active roles
 * @return array Array of roles with details
 */
function getAllRoles($activeOnly = false) {
    global $conn;
    
    try {
        $query = "SELECT RoleID, RoleName, Description, Permissions, IsActive FROM Roles";
        
        if ($activeOnly) {
            $query .= " WHERE IsActive = 'Yes'";
        }
        
        $query .= " ORDER BY RoleName";
        
        $result = $conn->query($query);
        if (!$result) {
            throw new Exception("Error fetching roles: " . $conn->error);
        }
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['Permissions'] = json_decode($row['Permissions'], true);
            $roles[] = $row;
        }
        
        return $roles;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in getAllRoles: " . $e->getMessage());
        return [];
    }
}

/**
 * Get specific role details by ID
 * 
 * @param int $roleID Role ID
 * @return array|null Role details or null if not found
 */
function getRoleByID($roleID) {
    global $conn;
    
    try {
        $query = "SELECT RoleID, RoleName, Description, Permissions, IsActive FROM Roles WHERE RoleID = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $roleID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $role = $result->fetch_assoc();
        $role['Permissions'] = json_decode($role['Permissions'], true);
        
        return $role;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $roleID, "Error in getRoleByID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get role by name
 * 
 * @param string $roleName Role name
 * @return array|null Role details or null if not found
 */
function getRoleByName($roleName) {
    global $conn;
    
    try {
        $query = "SELECT RoleID, RoleName, Description, Permissions, IsActive FROM Roles WHERE RoleName = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return null;
        }
        
        $role = $result->fetch_assoc();
        $role['Permissions'] = json_decode($role['Permissions'], true);
        
        return $role;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in getRoleByName: " . $e->getMessage());
        return null;
    }
}

// =====================================================
// EMPLOYEE ROLE ASSIGNMENT
// =====================================================

/**
 * Assign a role to an employee
 * 
 * @param int $employeeID Employee ID
 * @param int $roleID Role ID to assign
 * @param datetime|null $startDate Assignment start date (default: today)
 * @param datetime|null $endDate Assignment end date (null for permanent)
 * @return array Result array with success status
 */
function assignRoleToEmployee($employeeID, $roleID, $startDate = null, $endDate = null) {
    global $conn;
    
    try {
        // Validate employee and role exist
        $empQuery = "SELECT EmployeeID FROM Employees WHERE EmployeeID = ?";
        $stmt = $conn->prepare($empQuery);
        $stmt->bind_param("i", $employeeID);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Employee not found'];
        }
        
        $roleQuery = "SELECT RoleID FROM Roles WHERE RoleID = ? AND IsActive = 'Yes'";
        $stmt = $conn->prepare($roleQuery);
        $stmt->bind_param("i", $roleID);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            return ['success' => false, 'message' => 'Role not found or inactive'];
        }
        
        // Set default dates
        if ($startDate === null) {
            $startDate = date('Y-m-d');
        }
        
        // Insert into EmployeeRoles table
        $insertQuery = "INSERT INTO EmployeeRoles (EmployeeID, RoleID, StartDate, EndDate, AssignedDate) 
                       VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($insertQuery);
        $stmt->bind_param("iiss", $employeeID, $roleID, $startDate, $endDate);
        
        if (!$stmt->execute()) {
            throw new Exception("Error assigning role: " . $stmt->error);
        }
        
        $assignmentID = $stmt->insert_id;
        logAction('ROLE_ASSIGN', $employeeID, "Assigned RoleID $roleID to employee");
        
        return [
            'success' => true,
            'message' => 'Role assigned successfully',
            'assignmentID' => $assignmentID
        ];
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $employeeID, "Error in assignRoleToEmployee: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get all roles assigned to an employee
 * 
 * @param int $employeeID Employee ID
 * @param bool $activeOnly If true, returns only active assignments
 * @return array Array of assigned roles with details
 */
function getEmployeeRoles($employeeID, $activeOnly = false) {
    global $conn;
    
    try {
        $query = "SELECT 
                    er.AssignmentID,
                    er.EmployeeID,
                    er.RoleID,
                    r.RoleName,
                    r.Description,
                    r.Permissions,
                    er.StartDate,
                    er.EndDate,
                    er.AssignedDate,
                    CASE 
                        WHEN er.EndDate IS NULL THEN 'Permanent'
                        WHEN CURDATE() BETWEEN er.StartDate AND er.EndDate THEN 'Active'
                        WHEN CURDATE() > er.EndDate THEN 'Expired'
                        ELSE 'Pending'
                    END AS Status
                  FROM EmployeeRoles er
                  JOIN Roles r ON er.RoleID = r.RoleID
                  WHERE er.EmployeeID = ?";
        
        if ($activeOnly) {
            $query .= " AND (er.EndDate IS NULL OR CURDATE() BETWEEN er.StartDate AND er.EndDate)";
        }
        
        $query .= " ORDER BY er.StartDate DESC";
        
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $employeeID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $row['Permissions'] = json_decode($row['Permissions'], true);
            $roles[] = $row;
        }
        
        return $roles;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $employeeID, "Error in getEmployeeRoles: " . $e->getMessage());
        return [];
    }
}

/**
 * Remove a role assignment from an employee
 * 
 * @param int $assignmentID Assignment ID from EmployeeRoles table
 * @return array Result array with success status
 */
function removeRoleAssignment($assignmentID) {
    global $conn;
    
    try {
        // Get assignment details before deleting
        $getQuery = "SELECT EmployeeID, RoleID FROM EmployeeRoles WHERE AssignmentID = ?";
        $stmt = $conn->prepare($getQuery);
        $stmt->bind_param("i", $assignmentID);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Assignment not found'];
        }
        
        $row = $result->fetch_assoc();
        
        // Delete the assignment
        $deleteQuery = "DELETE FROM EmployeeRoles WHERE AssignmentID = ?";
        $stmt = $conn->prepare($deleteQuery);
        $stmt->bind_param("i", $assignmentID);
        
        if (!$stmt->execute()) {
            throw new Exception("Error removing role assignment: " . $stmt->error);
        }
        
        logAction('ROLE_UNASSIGN', $row['EmployeeID'], "Removed RoleID {$row['RoleID']}");
        
        return [
            'success' => true,
            'message' => 'Role assignment removed successfully'
        ];
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in removeRoleAssignment: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// =====================================================
// ROLE DISTRIBUTION ACROSS STORES
// =====================================================

/**
 * Distribute an employee's role across multiple stores
 * 
 * @param int $employeeID Employee ID
 * @param int $roleID Role ID to distribute
 * @param array $storeIDs Array of store IDs
 * @param datetime|null $startDate Distribution start date
 * @param datetime|null $endDate Distribution end date
 * @return array Result array with success status
 */
function distributeRoleToStores($employeeID, $roleID, $storeIDs, $startDate = null, $endDate = null) {
    global $conn;
    
    try {
        if (!is_array($storeIDs) || empty($storeIDs)) {
            return ['success' => false, 'message' => 'Store IDs array is required'];
        }
        
        // Set default dates
        if ($startDate === null) {
            $startDate = date('Y-m-d');
        }
        
        $distributionResults = [];
        $conn->begin_transaction();
        
        foreach ($storeIDs as $storeID) {
            // Validate store exists
            $storeQuery = "SELECT StoreID FROM Stores WHERE StoreID = ?";
            $stmt = $conn->prepare($storeQuery);
            $stmt->bind_param("i", $storeID);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                continue; // Skip invalid store
            }
            
            // Insert into EmployeeRoleAssignments table
            $insertQuery = "INSERT INTO EmployeeRoleAssignments (EmployeeID, RoleID, StoreID, StartDate, EndDate, AssignedDate) 
                           VALUES (?, ?, ?, ?, ?, NOW())";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("iiiss", $employeeID, $roleID, $storeID, $startDate, $endDate);
            
            if (!$stmt->execute()) {
                throw new Exception("Error assigning role to store $storeID: " . $stmt->error);
            }
            
            $distributionResults[] = [
                'storeID' => $storeID,
                'success' => true,
                'assignmentID' => $stmt->insert_id
            ];
        }
        
        $conn->commit();
        logAction('ROLE_DISTRIBUTE', $employeeID, "Distributed RoleID $roleID to " . count($distributionResults) . " stores");
        
        return [
            'success' => true,
            'message' => 'Role distributed to stores successfully',
            'distributions' => $distributionResults
        ];
    } catch (Exception $e) {
        $conn->rollback();
        logAction('ROLE_ERROR', $employeeID, "Error in distributeRoleToStores: " . $e->getMessage());
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Get all role assignments for an employee across stores
 * 
 * @param int $employeeID Employee ID
 * @param int|null $storeID Optional: filter by specific store
 * @return array Array of role assignments by store
 */
function getEmployeeRoleDistributions($employeeID, $storeID = null) {
    global $conn;
    
    try {
        $query = "SELECT 
                    era.AssignmentID,
                    era.EmployeeID,
                    era.RoleID,
                    era.StoreID,
                    s.StoreName,
                    r.RoleName,
                    r.Description,
                    r.Permissions,
                    era.StartDate,
                    era.EndDate,
                    era.AssignedDate,
                    CASE 
                        WHEN era.EndDate IS NULL THEN 'Permanent'
                        WHEN CURDATE() BETWEEN era.StartDate AND era.EndDate THEN 'Active'
                        WHEN CURDATE() > era.EndDate THEN 'Expired'
                        ELSE 'Pending'
                    END AS Status
                  FROM EmployeeRoleAssignments era
                  JOIN Stores s ON era.StoreID = s.StoreID
                  JOIN Roles r ON era.RoleID = r.RoleID
                  WHERE era.EmployeeID = ?";
        
        if ($storeID !== null) {
            $query .= " AND era.StoreID = ?";
        }
        
        $query .= " ORDER BY s.StoreName, era.StartDate DESC";
        
        $stmt = $conn->prepare($query);
        
        if ($storeID !== null) {
            $stmt->bind_param("ii", $employeeID, $storeID);
        } else {
            $stmt->bind_param("i", $employeeID);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $distributions = [];
        while ($row = $result->fetch_assoc()) {
            $row['Permissions'] = json_decode($row['Permissions'], true);
            $distributions[] = $row;
        }
        
        return $distributions;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $employeeID, "Error in getEmployeeRoleDistributions: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// PERMISSION CHECKING & VALIDATION
// =====================================================

/**
 * Check if an employee has a specific permission
 * 
 * @param int $employeeID Employee ID
 * @param string $permissionKey Permission key to check
 * @param int|null $storeID Optional: check permission for specific store
 * @return bool True if employee has permission, false otherwise
 */
function hasPermission($employeeID, $permissionKey, $storeID = null) {
    global $conn;
    
    try {
        $roles = getEmployeeRoles($employeeID, true); // Get active roles only
        
        foreach ($roles as $role) {
            $permissions = $role['Permissions'];
            
            // Check if permission exists and is true
            if (isset($permissions[$permissionKey]) && $permissions[$permissionKey] === true) {
                return true;
            }
        }
        
        return false;
    } catch (Exception $e) {
        logAction('PERMISSION_ERROR', $employeeID, "Error in hasPermission: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if an employee has access to a specific module
 * 
 * @param int $employeeID Employee ID
 * @param string $moduleName Module name to check
 * @return bool True if employee has module access, false otherwise
 */
function hasModuleAccess($employeeID, $moduleName) {
    global $conn;
    
    try {
        $roles = getEmployeeRoles($employeeID, true); // Get active roles only
        
        foreach ($roles as $role) {
            $permissions = $role['Permissions'];
            
            if (isset($permissions['module_access']) && is_array($permissions['module_access'])) {
                if (in_array('All', $permissions['module_access']) || 
                    in_array($moduleName, $permissions['module_access'])) {
                    return true;
                }
            }
        }
        
        return false;
    } catch (Exception $e) {
        logAction('PERMISSION_ERROR', $employeeID, "Error in hasModuleAccess: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if employee has a specific role
 * 
 * @param int $employeeID Employee ID
 * @param string $roleName Role name to check
 * @param int|null $storeID Optional: check role for specific store
 * @return bool True if employee has the role, false otherwise
 */
function hasRole($employeeID, $roleName, $storeID = null) {
    global $conn;
    
    try {
        // Get the role ID
        $roleQuery = "SELECT RoleID FROM Roles WHERE RoleName = ?";
        $stmt = $conn->prepare($roleQuery);
        $stmt->bind_param("s", $roleName);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return false;
        }
        
        $roleRow = $result->fetch_assoc();
        $roleID = $roleRow['RoleID'];
        
        // Check if employee has active role assignment
        $query = "SELECT EmployeeID FROM EmployeeRoles 
                  WHERE EmployeeID = ? AND RoleID = ? 
                  AND (EndDate IS NULL OR CURDATE() BETWEEN StartDate AND EndDate)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ii", $employeeID, $roleID);
        $stmt->execute();
        
        return $stmt->get_result()->num_rows > 0;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $employeeID, "Error in hasRole: " . $e->getMessage());
        return false;
    }
}

// =====================================================
// ROLE HISTORY & AUDIT
// =====================================================

/**
 * Get role assignment history for an employee
 * 
 * @param int $employeeID Employee ID
 * @param int|null $limit Number of records to return (null for all)
 * @return array Array of role assignments including expired ones
 */
function getRoleAssignmentHistory($employeeID, $limit = null) {
    global $conn;
    
    try {
        $query = "SELECT 
                    er.AssignmentID,
                    er.EmployeeID,
                    er.RoleID,
                    r.RoleName,
                    er.StartDate,
                    er.EndDate,
                    er.AssignedDate,
                    CASE 
                        WHEN er.EndDate IS NULL THEN 'Permanent'
                        WHEN CURDATE() BETWEEN er.StartDate AND er.EndDate THEN 'Active'
                        WHEN CURDATE() > er.EndDate THEN 'Expired'
                        ELSE 'Pending'
                    END AS Status
                  FROM EmployeeRoles er
                  JOIN Roles r ON er.RoleID = r.RoleID
                  WHERE er.EmployeeID = ?
                  ORDER BY er.AssignedDate DESC";
        
        if ($limit !== null) {
            $query .= " LIMIT ?";
        }
        
        $stmt = $conn->prepare($query);
        
        if ($limit !== null) {
            $stmt->bind_param("ii", $employeeID, $limit);
        } else {
            $stmt->bind_param("i", $employeeID);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = $row;
        }
        
        return $history;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', $employeeID, "Error in getRoleAssignmentHistory: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all employees with a specific role
 * 
 * @param int $roleID Role ID
 * @param int|null $storeID Optional: filter by store
 * @return array Array of employees with the role
 */
function getEmployeesByRole($roleID, $storeID = null) {
    global $conn;
    
    try {
        if ($storeID === null) {
            // Get from general employee roles
            $query = "SELECT 
                        e.EmployeeID,
                        e.FirstName,
                        e.LastName,
                        e.Email,
                        er.StartDate,
                        er.EndDate,
                        CASE 
                            WHEN er.EndDate IS NULL THEN 'Permanent'
                            WHEN CURDATE() BETWEEN er.StartDate AND er.EndDate THEN 'Active'
                            ELSE 'Inactive'
                        END AS Status
                      FROM Employees e
                      JOIN EmployeeRoles er ON e.EmployeeID = er.EmployeeID
                      WHERE er.RoleID = ? 
                      AND (er.EndDate IS NULL OR CURDATE() BETWEEN er.StartDate AND er.EndDate)
                      ORDER BY e.FirstName, e.LastName";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $roleID);
        } else {
            // Get from store-specific assignments
            $query = "SELECT 
                        e.EmployeeID,
                        e.FirstName,
                        e.LastName,
                        e.Email,
                        s.StoreName,
                        era.StartDate,
                        era.EndDate,
                        CASE 
                            WHEN era.EndDate IS NULL THEN 'Permanent'
                            WHEN CURDATE() BETWEEN era.StartDate AND era.EndDate THEN 'Active'
                            ELSE 'Inactive'
                        END AS Status
                      FROM Employees e
                      JOIN EmployeeRoleAssignments era ON e.EmployeeID = era.EmployeeID
                      JOIN Stores s ON era.StoreID = s.StoreID
                      WHERE era.RoleID = ? AND era.StoreID = ?
                      AND (era.EndDate IS NULL OR CURDATE() BETWEEN era.StartDate AND era.EndDate)
                      ORDER BY e.FirstName, e.LastName";
            
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $roleID, $storeID);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
        
        return $employees;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in getEmployeesByRole: " . $e->getMessage());
        return [];
    }
}

// =====================================================
// UTILITY FUNCTIONS
// =====================================================

/**
 * Validate if role permissions are correctly formatted
 * 
 * @param array $permissions Permissions array
 * @return array Array with 'valid' boolean and 'errors' array
 */
function validateRolePermissions($permissions) {
    $errors = [];
    
    if (!is_array($permissions)) {
        $errors[] = 'Permissions must be an array';
        return ['valid' => false, 'errors' => $errors];
    }
    
    // Check for required fields
    if (!isset($permissions['module_access'])) {
        $errors[] = 'module_access field is required';
    }
    
    if (!empty($errors)) {
        return ['valid' => false, 'errors' => $errors];
    }
    
    return ['valid' => true, 'errors' => []];
}

/**
 * Get summary statistics for role management
 * 
 * @return array Statistics including total roles, active assignments, etc.
 */
function getRoleManagementStats() {
    global $conn;
    
    try {
        $stats = [];
        
        // Total roles
        $result = $conn->query("SELECT COUNT(*) as count FROM Roles");
        $stats['total_roles'] = $result->fetch_assoc()['count'];
        
        // Active roles
        $result = $conn->query("SELECT COUNT(*) as count FROM Roles WHERE IsActive = 'Yes'");
        $stats['active_roles'] = $result->fetch_assoc()['count'];
        
        // Total employee role assignments
        $result = $conn->query("SELECT COUNT(*) as count FROM EmployeeRoles");
        $stats['total_assignments'] = $result->fetch_assoc()['count'];
        
        // Active assignments
        $result = $conn->query("SELECT COUNT(*) as count FROM EmployeeRoles 
                              WHERE (EndDate IS NULL OR CURDATE() BETWEEN StartDate AND EndDate)");
        $stats['active_assignments'] = $result->fetch_assoc()['count'];
        
        // Store distributions
        $result = $conn->query("SELECT COUNT(*) as count FROM EmployeeRoleAssignments");
        $stats['total_distributions'] = $result->fetch_assoc()['count'];
        
        // Employees with roles
        $result = $conn->query("SELECT COUNT(DISTINCT EmployeeID) as count FROM EmployeeRoles");
        $stats['employees_with_roles'] = $result->fetch_assoc()['count'];
        
        return $stats;
    } catch (Exception $e) {
        logAction('ROLE_ERROR', 0, "Error in getRoleManagementStats: " . $e->getMessage());
        return [];
    }
}

?>
