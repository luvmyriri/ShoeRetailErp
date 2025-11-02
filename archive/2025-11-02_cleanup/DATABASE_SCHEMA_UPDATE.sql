-- ============================================================================
-- Shoe Retail ERP - Database Schema Update
-- Purpose: Add proper relationship between Users and Roles tables
-- Date: 2025-10-22
-- ============================================================================

-- Step 1: Backup existing user data (optional, for reference)
-- Run this before making changes if you want to preserve role mappings

-- Step 2: Add RoleID column to Users table (before dropping Role enum)
ALTER TABLE `users` ADD COLUMN `RoleID` INT DEFAULT NULL AFTER `Email`;

-- Step 3: Create the foreign key relationship
ALTER TABLE `users` ADD CONSTRAINT `users_ibfk_3` 
FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE SET NULL;

-- Step 4: Migrate existing role data from enum to foreign key
-- First, ensure Roles table has all the roles from the enum
INSERT INTO `roles` (`RoleName`, `Description`, `Permissions`, `IsActive`) VALUES
('Admin', 'Full system access with all permissions', JSON_OBJECT(
    'can_manage_users', true,
    'can_manage_roles', true,
    'can_manage_inventory', true,
    'can_manage_sales', true,
    'can_manage_procurement', true,
    'can_manage_accounting', true,
    'can_manage_customers', true,
    'can_manage_employees', true,
    'can_view_all_reports', true,
    'can_generate_financial_reports', true
), 'Yes')
ON DUPLICATE KEY UPDATE RoleID=RoleID;

INSERT INTO `roles` (`RoleName`, `Description`, `Permissions`, `IsActive`) VALUES
('Manager', 'Manager-level access for store operations', JSON_OBJECT(
    'can_manage_inventory', true,
    'can_manage_sales', true,
    'can_manage_procurement', true,
    'can_manage_customers', true,
    'can_process_refunds', true,
    'can_view_reports', true,
    'can_process_sale', true
), 'Yes')
ON DUPLICATE KEY UPDATE RoleID=RoleID;

INSERT INTO `roles` (`RoleName`, `Description`, `Permissions`, `IsActive`) VALUES
('Cashier', 'Cashier access for POS operations', JSON_OBJECT(
    'can_process_sale', true,
    'can_view_inventory', true,
    'can_process_returns', true,
    'can_view_customer_info', true
), 'Yes')
ON DUPLICATE KEY UPDATE RoleID=RoleID;

INSERT INTO `roles` (`RoleName`, `Description`, `Permissions`, `IsActive`) VALUES
('Accountant', 'Accounting and financial management access', JSON_OBJECT(
    'can_manage_ledger', true,
    'can_process_ar_ap', true,
    'can_manage_tax', true,
    'can_generate_financial_reports', true,
    'can_view_all_transactions', true
), 'Yes')
ON DUPLICATE KEY UPDATE RoleID=RoleID;

INSERT INTO `roles` (`RoleName`, `Description`, `Permissions`, `IsActive`) VALUES
('Support', 'Customer support and ticket management', JSON_OBJECT(
    'can_create_support_tickets', true,
    'can_view_customer_info', true,
    'can_process_refunds', true,
    'can_view_orders', true
), 'Yes')
ON DUPLICATE KEY UPDATE RoleID=RoleID;

-- Step 5: Migrate user roles from enum to RoleID
-- Update Admin user
UPDATE `users` u
SET u.RoleID = (SELECT RoleID FROM `roles` WHERE RoleName = 'Admin')
WHERE u.Username = 'admin';

-- Update Manager users
UPDATE `users` u
SET u.RoleID = (SELECT RoleID FROM `roles` WHERE RoleName = 'Manager')
WHERE u.Username LIKE 'manager%';

-- Update Cashier users
UPDATE `users` u
SET u.RoleID = (SELECT RoleID FROM `roles` WHERE RoleName = 'Cashier')
WHERE u.Username LIKE 'cashier%';

-- Step 6: Drop the old Role enum column (after migration is complete)
-- Uncomment the line below ONLY after verifying RoleID is properly populated
-- ALTER TABLE `users` DROP COLUMN `Role`;

-- ============================================================================
-- NEW TABLES FOR EXTENDED ROLE MANAGEMENT
-- ============================================================================

-- Table for mapping employees to roles (supports multiple roles per employee)
DROP TABLE IF EXISTS `employeeroles`;
CREATE TABLE `employeeroles` (
  `EmployeeRoleID` int NOT NULL AUTO_INCREMENT,
  `EmployeeID` int NOT NULL,
  `RoleID` int NOT NULL,
  `StoreID` int DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date DEFAULT NULL,
  `IsActive` enum('Yes','No') DEFAULT 'Yes',
  `AssignedBy` varchar(50) DEFAULT NULL,
  `AssignedDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`EmployeeRoleID`),
  UNIQUE KEY `unique_employee_role_store` (`EmployeeID`, `RoleID`, `StoreID`),
  KEY `EmployeeID` (`EmployeeID`),
  KEY `RoleID` (`RoleID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `employeeroles_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `employeeroles_ibfk_2` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE,
  CONSTRAINT `employeeroles_ibfk_3` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table for role permissions matrix (more granular control)
DROP TABLE IF EXISTS `rolepermissions`;
CREATE TABLE `rolepermissions` (
  `RolePermissionID` int NOT NULL AUTO_INCREMENT,
  `RoleID` int NOT NULL,
  `PermissionName` varchar(100) NOT NULL,
  `PermissionCode` varchar(50) NOT NULL,
  `Description` text,
  `IsGranted` enum('Yes','No') DEFAULT 'Yes',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`RolePermissionID`),
  UNIQUE KEY `unique_role_permission` (`RoleID`, `PermissionCode`),
  KEY `RoleID` (`RoleID`),
  CONSTRAINT `rolepermissions_ibfk_1` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Table for user-role assignments (alternative to Users.RoleID for multiple roles)
DROP TABLE IF EXISTS `userroles`;
CREATE TABLE `userroles` (
  `UserRoleID` int NOT NULL AUTO_INCREMENT,
  `UserID` int NOT NULL,
  `RoleID` int NOT NULL,
  `StoreID` int DEFAULT NULL,
  `AssignedDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `IsActive` enum('Yes','No') DEFAULT 'Yes',
  `AssignedBy` varchar(50) DEFAULT NULL,
  `ExpireDate` date DEFAULT NULL,
  PRIMARY KEY (`UserRoleID`),
  UNIQUE KEY `unique_user_role_store` (`UserID`, `RoleID`, `StoreID`),
  KEY `UserID` (`UserID`),
  KEY `RoleID` (`RoleID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `userroles_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users` (`UserID`) ON DELETE CASCADE,
  CONSTRAINT `userroles_ibfk_2` FOREIGN KEY (`RoleID`) REFERENCES `roles` (`RoleID`) ON DELETE CASCADE,
  CONSTRAINT `userroles_ibfk_3` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- ============================================================================
-- VIEWS FOR ROLE & PERMISSION MANAGEMENT
-- ============================================================================

-- View: User permissions (flattened from JSON permissions)
DROP VIEW IF EXISTS `v_user_permissions`;
CREATE VIEW `v_user_permissions` AS
SELECT 
    u.UserID,
    u.Username,
    u.FirstName,
    u.LastName,
    r.RoleID,
    r.RoleName,
    r.Permissions,
    s.StoreID,
    s.StoreName,
    u.Status
FROM users u
LEFT JOIN roles r ON u.RoleID = r.RoleID
LEFT JOIN stores s ON u.StoreID = s.StoreID
WHERE r.IsActive = 'Yes' AND u.Status = 'Active'
ORDER BY u.UserID, r.RoleID;

-- View: Employee roles with details
DROP VIEW IF EXISTS `v_employee_roles_detail`;
CREATE VIEW `v_employee_roles_detail` AS
SELECT 
    er.EmployeeRoleID,
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
    r.RoleID,
    r.RoleName,
    s.StoreID,
    s.StoreName,
    er.StartDate,
    er.EndDate,
    er.IsActive,
    er.AssignedBy,
    er.AssignedDate,
    (CASE 
        WHEN er.EndDate IS NULL OR er.EndDate >= CURDATE() THEN 'Active'
        ELSE 'Expired'
    END) AS RoleStatus
FROM employeeroles er
JOIN employees e ON er.EmployeeID = e.EmployeeID
JOIN roles r ON er.RoleID = r.RoleID
LEFT JOIN stores s ON er.StoreID = s.StoreID
ORDER BY e.EmployeeID, er.StartDate DESC;

-- View: Role permissions matrix
DROP VIEW IF EXISTS `v_role_permissions`;
CREATE VIEW `v_role_permissions` AS
SELECT 
    r.RoleID,
    r.RoleName,
    rp.PermissionCode,
    rp.PermissionName,
    rp.Description,
    rp.IsGranted
FROM roles r
LEFT JOIN rolepermissions rp ON r.RoleID = rp.RoleID
ORDER BY r.RoleID, rp.PermissionCode;

-- ============================================================================
-- STORED PROCEDURES FOR ROLE MANAGEMENT
-- ============================================================================

-- Procedure: Assign role to user
DROP PROCEDURE IF EXISTS `AssignRoleToUser`;
DELIMITER ;;
CREATE PROCEDURE `AssignRoleToUser`(
    IN p_user_id INT,
    IN p_role_id INT,
    IN p_store_id INT,
    IN p_assigned_by VARCHAR(50)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Remove existing active roles for user at this store
    UPDATE userroles 
    SET IsActive = 'No'
    WHERE UserID = p_user_id AND (p_store_id IS NULL OR StoreID = p_store_id) AND IsActive = 'Yes';
    
    -- Assign new role
    INSERT INTO userroles (UserID, RoleID, StoreID, AssignedBy, AssignedDate, IsActive)
    VALUES (p_user_id, p_role_id, p_store_id, p_assigned_by, NOW(), 'Yes')
    ON DUPLICATE KEY UPDATE 
        IsActive = 'Yes',
        AssignedBy = p_assigned_by,
        AssignedDate = NOW();
    
    -- Update primary role in users table
    UPDATE users 
    SET RoleID = p_role_id
    WHERE UserID = p_user_id;
    
    COMMIT;
END ;;
DELIMITER ;

-- Procedure: Assign role to employee
DROP PROCEDURE IF EXISTS `AssignRoleToEmployee`;
DELIMITER ;;
CREATE PROCEDURE `AssignRoleToEmployee`(
    IN p_employee_id INT,
    IN p_role_id INT,
    IN p_store_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_assigned_by VARCHAR(50)
)
BEGIN
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Insert or update employee role
    INSERT INTO employeeroles (EmployeeID, RoleID, StoreID, StartDate, EndDate, IsActive, AssignedBy, AssignedDate)
    VALUES (p_employee_id, p_role_id, p_store_id, p_start_date, p_end_date, 'Yes', p_assigned_by, NOW())
    ON DUPLICATE KEY UPDATE 
        StartDate = p_start_date,
        EndDate = p_end_date,
        IsActive = 'Yes',
        AssignedDate = NOW();
    
    COMMIT;
END ;;
DELIMITER ;

-- Procedure: Check user permissions
DROP PROCEDURE IF EXISTS `CheckUserPermission`;
DELIMITER ;;
CREATE PROCEDURE `CheckUserPermission`(
    IN p_user_id INT,
    IN p_permission_code VARCHAR(50),
    OUT p_has_permission INT
)
BEGIN
    DECLARE v_permissions JSON;
    
    SELECT r.Permissions INTO v_permissions
    FROM users u
    JOIN roles r ON u.RoleID = r.RoleID
    WHERE u.UserID = p_user_id AND u.Status = 'Active' AND r.IsActive = 'Yes';
    
    SET p_has_permission = CASE 
        WHEN JSON_CONTAINS(v_permissions, 'true', CONCAT('$.', p_permission_code)) THEN 1
        ELSE 0
    END;
END ;;
DELIMITER ;

-- ============================================================================
-- MIGRATION NOTES
-- ============================================================================
/*
STEPS TO EXECUTE THIS SCHEMA UPDATE:

1. BACKUP DATABASE:
   mysqldump -u root -p shoeretailerp > shoeretailerp_backup.sql

2. RUN THIS SCRIPT:
   mysql -u root -p shoeretailerp < DATABASE_SCHEMA_UPDATE.sql

3. VERIFY MIGRATION:
   SELECT * FROM v_user_permissions;
   SELECT * FROM v_employee_roles_detail;

4. TEST PHP PERMISSION CHECK:
   In your PHP code, call: CheckUserPermission(UserID, 'permission_code')

5. (OPTIONAL) DROP OLD ROLE COLUMN:
   Only after confirming RoleID is properly populated:
   ALTER TABLE users DROP COLUMN Role;

IMPORTANT NOTES:
- The Users.RoleID now references Roles table
- Employees can have multiple roles via EmployeeRoles table
- Users can have multiple roles via UserRoles table
- All permissions are stored in roles.Permissions JSON column
- Role-based access is now fully relational and scalable
- Existing users will be migrated based on their old enum Role values
*/

-- ============================================================================
-- SAMPLE PERMISSION SETUP FOR ROLES
-- ============================================================================

-- Admin permissions
INSERT INTO rolepermissions (RoleID, PermissionName, PermissionCode, Description, IsGranted)
SELECT RoleID, 'Manage Users', 'can_manage_users', 'Can create, edit, delete users', 'Yes' FROM roles WHERE RoleName = 'Admin'
UNION ALL
SELECT RoleID, 'Manage Roles', 'can_manage_roles', 'Can create and assign roles', 'Yes' FROM roles WHERE RoleName = 'Admin'
UNION ALL
SELECT RoleID, 'View All Reports', 'can_view_all_reports', 'Can access all system reports', 'Yes' FROM roles WHERE RoleName = 'Admin'
ON DUPLICATE KEY UPDATE IsGranted = 'Yes';

-- Manager permissions
INSERT INTO rolepermissions (RoleID, PermissionName, PermissionCode, Description, IsGranted)
SELECT RoleID, 'Manage Sales', 'can_manage_sales', 'Can process and manage sales', 'Yes' FROM roles WHERE RoleName = 'Manager'
UNION ALL
SELECT RoleID, 'Manage Inventory', 'can_manage_inventory', 'Can manage inventory and stock', 'Yes' FROM roles WHERE RoleName = 'Manager'
UNION ALL
SELECT RoleID, 'Process Refunds', 'can_process_refunds', 'Can process customer refunds', 'Yes' FROM roles WHERE RoleName = 'Manager'
ON DUPLICATE KEY UPDATE IsGranted = 'Yes';

-- ============================================================================
-- END OF SCHEMA UPDATE
-- ============================================================================
