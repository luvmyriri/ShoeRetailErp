-- =====================================================
-- Shoe Retail ERP Database - Complete Schema with HR Module
-- Author: Generated for PHP/MySQL Implementation with HR Integration
-- Date: 2024
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS ShoeRetailERP;
USE ShoeRetailERP;

-- Set foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE TABLES (EXISTING)
-- =====================================================

-- Suppliers Table: Stores supplier information
CREATE TABLE IF NOT EXISTS Suppliers (
    SupplierID INT PRIMARY KEY AUTO_INCREMENT,
    SupplierName VARCHAR(100) NOT NULL,
    ContactName VARCHAR(50),
    Email VARCHAR(100) UNIQUE,
    Phone VARCHAR(20) UNIQUE,
    Address TEXT,
    PaymentTerms VARCHAR(50),
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Stores Table: Manages store locations
CREATE TABLE IF NOT EXISTS Stores (
    StoreID INT PRIMARY KEY AUTO_INCREMENT,
    StoreName VARCHAR(100) NOT NULL,
    Location TEXT,
    ManagerName VARCHAR(50),
    ContactPhone VARCHAR(20),
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products Table: Stores shoe details
CREATE TABLE IF NOT EXISTS Products (
    ProductID INT PRIMARY KEY AUTO_INCREMENT,
    SKU VARCHAR(50) UNIQUE NOT NULL,
    Brand VARCHAR(50) NOT NULL,
    Model VARCHAR(100) NOT NULL,
    Size DECIMAL(4,1) NOT NULL,
    Color VARCHAR(50),
    CostPrice DECIMAL(10,2) NOT NULL,
    SellingPrice DECIMAL(10,2) NOT NULL,
    MinStockLevel INT DEFAULT 10,
    MaxStockLevel INT DEFAULT 100,
    SupplierID INT,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Inventory Table: Tracks stock per store
CREATE TABLE IF NOT EXISTS Inventory (
    InventoryID INT PRIMARY KEY AUTO_INCREMENT,
    ProductID INT,
    StoreID INT,
    Quantity INT NOT NULL DEFAULT 0,
    LastUpdated DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE CASCADE,
    UNIQUE KEY unique_product_store (ProductID, StoreID)
) ENGINE=InnoDB;

-- Customers Table: Stores customer information
CREATE TABLE IF NOT EXISTS Customers (
    CustomerID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50),
    Email VARCHAR(100) UNIQUE,
    Phone VARCHAR(20) UNIQUE,
    Address TEXT,
    LoyaltyPoints INT DEFAULT 0,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sales Table: Records sales transactions
CREATE TABLE IF NOT EXISTS Sales (
    SaleID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerID INT,
    StoreID INT,
    SaleDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TotalAmount DECIMAL(10,2) NOT NULL,
    TaxAmount DECIMAL(10,2) DEFAULT 0,
    DiscountAmount DECIMAL(10,2) DEFAULT 0,
    PaymentStatus ENUM('Paid', 'Credit', 'Refunded', 'Partial') DEFAULT 'Paid',
    PaymentMethod ENUM('Cash', 'Card', 'Credit', 'Loyalty') DEFAULT 'Cash',
    SalespersonID INT,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE SET NULL,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SaleDetails Table: Details products in each sale
CREATE TABLE IF NOT EXISTS SaleDetails (
    SaleDetailID INT PRIMARY KEY AUTO_INCREMENT,
    SaleID INT,
    ProductID INT,
    Quantity INT NOT NULL,
    UnitPrice DECIMAL(10,2) NOT NULL,
    Subtotal DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (SaleID) REFERENCES Sales(SaleID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- PurchaseOrders Table: Manages supplier orders
CREATE TABLE IF NOT EXISTS PurchaseOrders (
    PurchaseOrderID INT PRIMARY KEY AUTO_INCREMENT,
    SupplierID INT,
    StoreID INT,
    OrderDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpectedDeliveryDate DATE,
    TotalAmount DECIMAL(10,2),
    Status ENUM('Pending', 'Received', 'Cancelled', 'Partial') DEFAULT 'Pending',
    CreatedBy VARCHAR(50),
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID) ON DELETE SET NULL,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- PurchaseOrderDetails Table: Details products in purchase orders
CREATE TABLE IF NOT EXISTS PurchaseOrderDetails (
    PurchaseOrderDetailID INT PRIMARY KEY AUTO_INCREMENT,
    PurchaseOrderID INT,
    ProductID INT,
    Quantity INT NOT NULL,
    UnitCost DECIMAL(10,2) NOT NULL,
    Subtotal DECIMAL(10,2) NOT NULL,
    ReceivedQuantity INT DEFAULT 0,
    FOREIGN KEY (PurchaseOrderID) REFERENCES PurchaseOrders(PurchaseOrderID) ON DELETE CASCADE,
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Expenses Table: Tracks operational expenses
CREATE TABLE IF NOT EXISTS Expenses (
    ExpenseID INT PRIMARY KEY AUTO_INCREMENT,
    StoreID INT,
    ExpenseDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Description VARCHAR(200),
    Amount DECIMAL(10,2) NOT NULL,
    Category ENUM('Rent', 'Utilities', 'Payroll', 'Supplies', 'Marketing', 'Maintenance', 'Other') NOT NULL,
    ApprovedBy VARCHAR(50),
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- ACCOUNTING TABLES (EXISTING)
-- =====================================================

-- GeneralLedger Table: Centralized financial transactions
CREATE TABLE IF NOT EXISTS GeneralLedger (
    LedgerID INT PRIMARY KEY AUTO_INCREMENT,
    TransactionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    AccountType ENUM('Revenue', 'Expense', 'Asset', 'Liability', 'Equity') NOT NULL,
    AccountName VARCHAR(100) NOT NULL,
    Description VARCHAR(200),
    Debit DECIMAL(10,2) DEFAULT 0,
    Credit DECIMAL(10,2) DEFAULT 0,
    ReferenceID INT,
    ReferenceType ENUM('Sale', 'Purchase', 'Expense', 'Payment', 'Payroll', 'Adjustment', 'Other') NOT NULL,
    StoreID INT,
    CreatedBy VARCHAR(50),
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- AccountsReceivable Table: Tracks customer credit payments
CREATE TABLE IF NOT EXISTS AccountsReceivable (
    ARID INT PRIMARY KEY AUTO_INCREMENT,
    SaleID INT,
    CustomerID INT,
    AmountDue DECIMAL(10,2) NOT NULL,
    DueDate DATE NOT NULL,
    PaymentStatus ENUM('Pending', 'Paid', 'Overdue', 'Partial') DEFAULT 'Pending',
    PaidAmount DECIMAL(10,2) DEFAULT 0,
    PaymentDate DATETIME NULL,
    FOREIGN KEY (SaleID) REFERENCES Sales(SaleID) ON DELETE CASCADE,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- AccountsPayable Table: Tracks supplier payments
CREATE TABLE IF NOT EXISTS AccountsPayable (
    APID INT PRIMARY KEY AUTO_INCREMENT,
    PurchaseOrderID INT,
    SupplierID INT,
    AmountDue DECIMAL(10,2) NOT NULL,
    DueDate DATE NOT NULL,
    PaymentStatus ENUM('Pending', 'Paid', 'Overdue', 'Partial') DEFAULT 'Pending',
    PaidAmount DECIMAL(10,2) DEFAULT 0,
    PaymentDate DATETIME NULL,
    FOREIGN KEY (PurchaseOrderID) REFERENCES PurchaseOrders(PurchaseOrderID) ON DELETE CASCADE,
    FOREIGN KEY (SupplierID) REFERENCES Suppliers(SupplierID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- TaxRecords Table: Logs tax details
CREATE TABLE IF NOT EXISTS TaxRecords (
    TaxRecordID INT PRIMARY KEY AUTO_INCREMENT,
    TransactionID INT,
    TransactionType ENUM('Sale', 'Purchase') NOT NULL,
    TaxAmount DECIMAL(10,2) NOT NULL,
    TaxDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    TaxType VARCHAR(50) NOT NULL,
    TaxRate DECIMAL(5,4) NOT NULL,
    StoreID INT,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- SupportTickets Table: Manages customer support issues
CREATE TABLE IF NOT EXISTS SupportTickets (
    TicketID INT PRIMARY KEY AUTO_INCREMENT,
    CustomerID INT,
    StoreID INT,
    IssueDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Subject VARCHAR(200) NOT NULL,
    Description TEXT NOT NULL,
    Status ENUM('Open', 'In Progress', 'Resolved', 'Closed') DEFAULT 'Open',
    Priority ENUM('Low', 'Medium', 'High', 'Critical') DEFAULT 'Medium',
    AssignedTo VARCHAR(50),
    Resolution TEXT,
    ResolvedDate DATETIME NULL,
    FOREIGN KEY (CustomerID) REFERENCES Customers(CustomerID) ON DELETE SET NULL,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- HR TABLES (NEW)
-- =====================================================

-- Employees Table: Stores employee details
CREATE TABLE IF NOT EXISTS Employees (
    EmployeeID INT PRIMARY KEY AUTO_INCREMENT,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Phone VARCHAR(20) UNIQUE,
    HireDate DATE NOT NULL,
    Salary DECIMAL(10,2) NOT NULL,
    HourlyRate DECIMAL(10,2),
    StoreID INT,
    Status ENUM('Active', 'Inactive', 'On Leave', 'Terminated') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Roles Table: Defines available roles with permissions
CREATE TABLE IF NOT EXISTS Roles (
    RoleID INT PRIMARY KEY AUTO_INCREMENT,
    RoleName VARCHAR(100) NOT NULL UNIQUE,
    Description TEXT,
    Permissions JSON DEFAULT '{}',
    IsActive ENUM('Yes', 'No') DEFAULT 'Yes',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- EmployeeRoles Table: Assigns roles to employees (many-to-many)
CREATE TABLE IF NOT EXISTS EmployeeRoles (
    EmployeeRoleID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    RoleID INT NOT NULL,
    AssignmentDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    IsActive ENUM('Yes', 'No') DEFAULT 'Yes',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_employee_role (EmployeeID, RoleID),
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- EmployeeRoleAssignments Table: Distributes roles across stores/periods
CREATE TABLE IF NOT EXISTS EmployeeRoleAssignments (
    AssignmentID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    RoleID INT NOT NULL,
    StoreID INT NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE,
    IsActive ENUM('Yes', 'No') DEFAULT 'Yes',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (RoleID) REFERENCES Roles(RoleID) ON DELETE CASCADE,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Timesheets Table: Tracks employee work hours
CREATE TABLE IF NOT EXISTS Timesheets (
    TimesheetID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    StoreID INT NOT NULL,
    WorkDate DATE NOT NULL,
    ClockIn TIME,
    ClockOut TIME,
    HoursWorked DECIMAL(5,2) NOT NULL,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    Notes TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_employee_date (EmployeeID, WorkDate),
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Payroll Table: Manages payroll calculations and records
CREATE TABLE IF NOT EXISTS Payroll (
    PayrollID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    PayPeriodStart DATE NOT NULL,
    PayPeriodEnd DATE NOT NULL,
    HoursWorked DECIMAL(8,2),
    GrossPay DECIMAL(12,2) NOT NULL,
    Deductions DECIMAL(10,2) DEFAULT 0,
    Bonuses DECIMAL(10,2) DEFAULT 0,
    NetPay DECIMAL(12,2) NOT NULL,
    PaymentDate DATETIME,
    Status ENUM('Draft', 'Pending', 'Processed', 'Paid', 'Rejected') DEFAULT 'Draft',
    Notes TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- PayrollDeductions Table: Details deductions for payroll
CREATE TABLE IF NOT EXISTS PayrollDeductions (
    DeductionID INT PRIMARY KEY AUTO_INCREMENT,
    PayrollID INT NOT NULL,
    DeductionType VARCHAR(50) NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Description TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- PayrollBonuses Table: Details bonuses for payroll
CREATE TABLE IF NOT EXISTS PayrollBonuses (
    BonusID INT PRIMARY KEY AUTO_INCREMENT,
    PayrollID INT NOT NULL,
    BonusType VARCHAR(50) NOT NULL,
    Amount DECIMAL(10,2) NOT NULL,
    Description TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PayrollID) REFERENCES Payroll(PayrollID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Attendance Table: Track attendance records
CREATE TABLE IF NOT EXISTS Attendance (
    AttendanceID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    StoreID INT NOT NULL,
    AttendanceDate DATE NOT NULL,
    Status ENUM('Present', 'Absent', 'Late', 'On Leave') DEFAULT 'Present',
    Remarks TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_attendance (EmployeeID, AttendanceDate),
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE CASCADE
) ENGINE=InnoDB;

-- LeaveRequests Table: Manage employee leave requests
CREATE TABLE IF NOT EXISTS LeaveRequests (
    LeaveRequestID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    LeaveType ENUM('Sick', 'Vacation', 'Personal', 'Other') NOT NULL,
    StartDate DATE NOT NULL,
    EndDate DATE NOT NULL,
    Reason TEXT,
    Status ENUM('Pending', 'Approved', 'Rejected') DEFAULT 'Pending',
    ApprovedBy INT,
    ApprovedDate DATETIME,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (ApprovedBy) REFERENCES Employees(EmployeeID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- EmployeePerformance Table: Track employee performance metrics
CREATE TABLE IF NOT EXISTS EmployeePerformance (
    PerformanceID INT PRIMARY KEY AUTO_INCREMENT,
    EmployeeID INT NOT NULL,
    EvaluationDate DATE NOT NULL,
    EvaluatedBy INT,
    RatingCategory VARCHAR(50),
    Rating INT CHECK (Rating >= 1 AND Rating <= 5),
    Comments TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES Employees(EmployeeID) ON DELETE CASCADE,
    FOREIGN KEY (EvaluatedBy) REFERENCES Employees(EmployeeID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

CREATE INDEX idx_products_sku ON Products(SKU);
CREATE INDEX idx_products_brand_model ON Products(Brand, Model);
CREATE INDEX idx_inventory_product_store ON Inventory(ProductID, StoreID);
CREATE INDEX idx_sales_date ON Sales(SaleDate);
CREATE INDEX idx_sales_customer ON Sales(CustomerID);
CREATE INDEX idx_sales_store ON Sales(StoreID);
CREATE INDEX idx_ledger_date ON GeneralLedger(TransactionDate);
CREATE INDEX idx_ledger_account ON GeneralLedger(AccountType);
CREATE INDEX idx_ar_status ON AccountsReceivable(PaymentStatus);
CREATE INDEX idx_ap_status ON AccountsPayable(PaymentStatus);
CREATE INDEX idx_employee_email ON Employees(Email);
CREATE INDEX idx_employee_store ON Employees(StoreID);
CREATE INDEX idx_employee_status ON Employees(Status);
CREATE INDEX idx_timesheet_date ON Timesheets(WorkDate);
CREATE INDEX idx_timesheet_employee ON Timesheets(EmployeeID);
CREATE INDEX idx_payroll_period ON Payroll(PayPeriodStart, PayPeriodEnd);
CREATE INDEX idx_payroll_status ON Payroll(Status);
CREATE INDEX idx_attendance_date ON Attendance(AttendanceDate);
CREATE INDEX idx_leave_request_status ON LeaveRequests(Status);
CREATE INDEX idx_employee_role_active ON EmployeeRoles(IsActive);
CREATE INDEX idx_role_assignment_store ON EmployeeRoleAssignments(StoreID);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Employee Summary View with Roles
CREATE OR REPLACE VIEW v_employee_summary AS
SELECT 
    e.EmployeeID,
    CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
    e.Email,
    e.Phone,
    e.HireDate,
    e.Salary,
    s.StoreName,
    GROUP_CONCAT(DISTINCT r.RoleName SEPARATOR ', ') AS Roles,
    e.Status
FROM Employees e
LEFT JOIN Stores s ON e.StoreID = s.StoreID
LEFT JOIN EmployeeRoles er ON e.EmployeeID = er.EmployeeID AND er.IsActive = 'Yes'
LEFT JOIN Roles r ON er.RoleID = r.RoleID
WHERE e.Status != 'Terminated'
GROUP BY e.EmployeeID, e.FirstName, e.LastName, e.Email, e.Phone, e.HireDate, e.Salary, s.StoreName, e.Status;

-- Payroll Summary View
CREATE OR REPLACE VIEW v_payroll_summary AS
SELECT 
    p.PayrollID,
    CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
    s.StoreName,
    p.PayPeriodStart,
    p.PayPeriodEnd,
    p.HoursWorked,
    p.GrossPay,
    p.Deductions,
    p.Bonuses,
    p.NetPay,
    p.Status
FROM Payroll p
JOIN Employees e ON p.EmployeeID = e.EmployeeID
LEFT JOIN Stores s ON e.StoreID = s.StoreID
ORDER BY p.PayPeriodEnd DESC;

-- Role Distribution View
CREATE OR REPLACE VIEW v_role_distribution AS
SELECT 
    s.StoreName,
    r.RoleName,
    COUNT(DISTINCT era.EmployeeID) AS EmployeeCount,
    GROUP_CONCAT(DISTINCT CONCAT(e.FirstName, ' ', e.LastName) SEPARATOR ', ') AS Employees
FROM EmployeeRoleAssignments era
JOIN Employees e ON era.EmployeeID = e.EmployeeID
JOIN Roles r ON era.RoleID = r.RoleID
JOIN Stores s ON era.StoreID = s.StoreID
WHERE era.IsActive = 'Yes' AND CURDATE() BETWEEN era.StartDate AND COALESCE(era.EndDate, CURDATE())
GROUP BY s.StoreName, r.RoleName;

-- Attendance Summary View
CREATE OR REPLACE VIEW v_attendance_summary AS
SELECT 
    a.AttendanceDate,
    s.StoreName,
    COUNT(CASE WHEN a.Status = 'Present' THEN 1 END) AS Present,
    COUNT(CASE WHEN a.Status = 'Absent' THEN 1 END) AS Absent,
    COUNT(CASE WHEN a.Status = 'Late' THEN 1 END) AS Late,
    COUNT(CASE WHEN a.Status = 'On Leave' THEN 1 END) AS OnLeave,
    COUNT(*) AS Total
FROM Attendance a
JOIN Stores s ON a.StoreID = s.StoreID
GROUP BY a.AttendanceDate, s.StoreName;

-- =====================================================
-- STORED PROCEDURES FOR HR OPERATIONS
-- =====================================================

DELIMITER $$

-- Procedure to calculate payroll for a pay period
CREATE PROCEDURE CalculatePayroll(
    IN p_employee_id INT,
    IN p_pay_period_start DATE,
    IN p_pay_period_end DATE,
    OUT p_payroll_id INT
)
BEGIN
    DECLARE v_total_hours DECIMAL(8,2);
    DECLARE v_hourly_rate DECIMAL(10,2);
    DECLARE v_gross_pay DECIMAL(12,2);
    DECLARE v_salary DECIMAL(12,2);
    DECLARE v_total_deductions DECIMAL(10,2) DEFAULT 0;
    DECLARE v_total_bonuses DECIMAL(10,2) DEFAULT 0;
    DECLARE v_net_pay DECIMAL(12,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Calculate total hours worked
    SELECT COALESCE(SUM(HoursWorked), 0) INTO v_total_hours
    FROM Timesheets
    WHERE EmployeeID = p_employee_id 
        AND WorkDate BETWEEN p_pay_period_start AND p_pay_period_end
        AND Status = 'Approved';
    
    -- Get employee's hourly rate or calculate from salary
    SELECT HourlyRate, Salary INTO v_hourly_rate, v_salary
    FROM Employees
    WHERE EmployeeID = p_employee_id;
    
    -- Calculate gross pay
    IF v_hourly_rate IS NOT NULL AND v_hourly_rate > 0 THEN
        SET v_gross_pay = v_total_hours * v_hourly_rate;
    ELSE
        -- Use salary divided by estimated hours per month
        SET v_gross_pay = v_salary;
    END IF;
    
    -- Insert payroll record
    INSERT INTO Payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, GrossPay, Deductions, Bonuses, NetPay, Status)
    VALUES (p_employee_id, p_pay_period_start, p_pay_period_end, v_total_hours, v_gross_pay, v_total_deductions, v_total_bonuses, v_gross_pay - v_total_deductions + v_total_bonuses, 'Pending');
    
    SET p_payroll_id = LAST_INSERT_ID();
    
    COMMIT;
END$$

-- Procedure to assign role to employee
CREATE PROCEDURE AssignRoleToEmployee(
    IN p_employee_id INT,
    IN p_role_id INT,
    OUT p_employee_role_id INT
)
BEGIN
    DECLARE v_existing INT;
    
    -- Check if role is already assigned
    SELECT COUNT(*) INTO v_existing FROM EmployeeRoles
    WHERE EmployeeID = p_employee_id AND RoleID = p_role_id AND IsActive = 'Yes';
    
    IF v_existing > 0 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Role already assigned to employee';
    END IF;
    
    INSERT INTO EmployeeRoles (EmployeeID, RoleID, IsActive)
    VALUES (p_employee_id, p_role_id, 'Yes');
    
    SET p_employee_role_id = LAST_INSERT_ID();
END$$

-- Procedure to distribute role to store with assignment
CREATE PROCEDURE DistributeRoleToStore(
    IN p_employee_id INT,
    IN p_role_id INT,
    IN p_store_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    OUT p_assignment_id INT
)
BEGIN
    INSERT INTO EmployeeRoleAssignments (EmployeeID, RoleID, StoreID, StartDate, EndDate, IsActive)
    VALUES (p_employee_id, p_role_id, p_store_id, p_start_date, p_end_date, 'Yes');
    
    SET p_assignment_id = LAST_INSERT_ID();
END$$

-- Procedure to process leave request
CREATE PROCEDURE ProcessLeaveRequest(
    IN p_leave_request_id INT,
    IN p_status ENUM('Approved', 'Rejected'),
    IN p_approved_by INT
)
BEGIN
    DECLARE v_employee_id INT;
    DECLARE v_start_date DATE;
    DECLARE v_end_date DATE;
    DECLARE v_current_date DATE;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get leave request details
    SELECT EmployeeID, StartDate, EndDate INTO v_employee_id, v_start_date, v_end_date
    FROM LeaveRequests
    WHERE LeaveRequestID = p_leave_request_id;
    
    -- Update leave request status
    UPDATE LeaveRequests
    SET Status = p_status, ApprovedBy = p_approved_by, ApprovedDate = NOW()
    WHERE LeaveRequestID = p_leave_request_id;
    
    -- If approved, update attendance records
    IF p_status = 'Approved' THEN
        SET v_current_date = v_start_date;
        
        WHILE v_current_date <= v_end_date DO
            -- Check if attendance record exists
            INSERT IGNORE INTO Attendance (EmployeeID, StoreID, AttendanceDate, Status)
            SELECT v_employee_id, StoreID, v_current_date, 'On Leave'
            FROM Employees
            WHERE EmployeeID = v_employee_id;
            
            SET v_current_date = DATE_ADD(v_current_date, INTERVAL 1 DAY);
        END WHILE;
    END IF;
    
    COMMIT;
END$$

DELIMITER ;

-- =====================================================
-- TRIGGERS FOR AUTOMATION
-- =====================================================

DELIMITER $$

-- Trigger to update employee status on termination date
CREATE TRIGGER tr_employee_termination_check
BEFORE INSERT ON Payroll
FOR EACH ROW
BEGIN
    DECLARE v_status VARCHAR(20);
    SELECT Status INTO v_status FROM Employees WHERE EmployeeID = NEW.EmployeeID;
    
    IF v_status = 'Terminated' THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'Cannot process payroll for terminated employee';
    END IF;
END$$

-- Trigger to record payroll in general ledger
CREATE TRIGGER tr_payroll_to_ledger
AFTER UPDATE ON Payroll
FOR EACH ROW
BEGIN
    DECLARE v_store_id INT;
    
    IF NEW.Status = 'Paid' AND OLD.Status != 'Paid' THEN
        -- Get employee's store
        SELECT StoreID INTO v_store_id FROM Employees WHERE EmployeeID = NEW.EmployeeID;
        
        -- Record in general ledger
        INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Debit, ReferenceID, ReferenceType, StoreID)
        VALUES (NOW(), 'Expense', 'Payroll Expense', CONCAT('Payroll payment for employee ', NEW.EmployeeID), NEW.NetPay, NEW.PayrollID, 'Payroll', v_store_id);
        
        -- Record in expenses
        INSERT INTO Expenses (StoreID, Description, Amount, Category)
        VALUES (v_store_id, CONCAT('Payroll - Employee ', NEW.EmployeeID), NEW.NetPay, 'Payroll');
    END IF;
END$$

-- Trigger to validate role assignment dates
CREATE TRIGGER tr_validate_role_assignment_dates
BEFORE INSERT ON EmployeeRoleAssignments
FOR EACH ROW
BEGIN
    IF NEW.EndDate IS NOT NULL AND NEW.EndDate < NEW.StartDate THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'End date cannot be before start date';
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Sample Roles (if not exists)
INSERT INTO Roles (RoleName, Description, Permissions, IsActive) VALUES
('Admin', 'System administrator with full access', '{"all": true}', 'Yes'),
('Cashier', 'Point of sale operator', '{"can_process_sale": true, "can_process_refund": true}', 'Yes'),
('Store Manager', 'Store manager with operational control', '{"can_manage_staff": true, "can_view_reports": true, "can_process_sale": true}', 'Yes'),
('Inventory Manager', 'Manages inventory and stock', '{"can_update_inventory": true, "can_create_purchase_order": true}', 'Yes'),
('Procurement Manager', 'Handles supplier orders', '{"can_create_purchase_order": true, "can_manage_suppliers": true}', 'Yes'),
('Accountant', 'Manages financial records', '{"can_view_ledger": true, "can_process_payments": true, "can_generate_reports": true}', 'Yes'),
('HR Manager', 'Manages HR functions and payroll', '{"can_manage_employees": true, "can_process_payroll": true, "can_approve_leave": true}', 'Yes'),
('Customer Service', 'Handles customer support', '{"can_manage_customers": true, "can_create_support_ticket": true}', 'Yes')
ON DUPLICATE KEY UPDATE RoleName = RoleName;

-- Sample Employees
INSERT INTO Employees (FirstName, LastName, Email, Phone, HireDate, Salary, HourlyRate, StoreID, Status) VALUES
('John', 'Smith', 'john.smith@shoe-retail.com', '555-0101', '2023-01-15', 3500.00, 17.50, 1, 'Active'),
('Jane', 'Doe', 'jane.doe@shoe-retail.com', '555-0102', '2023-02-20', 4000.00, 20.00, 1, 'Active'),
('Mike', 'Johnson', 'mike.johnson@shoe-retail.com', '555-0103', '2023-03-10', 3200.00, 16.00, 2, 'Active'),
('Sarah', 'Williams', 'sarah.williams@shoe-retail.com', '555-0104', '2023-04-05', 3800.00, 19.00, 2, 'Active'),
('Robert', 'Brown', 'robert.brown@shoe-retail.com', '555-0105', '2023-05-12', 3300.00, 16.50, 3, 'Active')
ON DUPLICATE KEY UPDATE FirstName = FirstName;

-- Reset foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- END OF SCHEMA
-- =====================================================