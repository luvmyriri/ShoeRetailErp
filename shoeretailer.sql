-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 31, 2025 at 06:16 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `shoeretailer`
--

DELIMITER $$
--
-- Procedures
--
CREATE DEFINER=`root`@`localhost` PROCEDURE `GeneratePayroll` (IN `p_employee_id` INT, IN `p_start_date` DATE, IN `p_end_date` DATE, IN `p_deductions` DECIMAL(10,2))   BEGIN
    DECLARE v_hours_worked DECIMAL(5,2);
    DECLARE v_hourly_rate DECIMAL(10,2);
    DECLARE v_gross_pay DECIMAL(10,2);
    DECLARE v_leave_pay DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_net_pay DECIMAL(10,2);
    
    -- Calculate total hours from attendance
    SELECT SUM(HoursWorked) INTO v_hours_worked
    FROM Attendance
    WHERE EmployeeID = p_employee_id
    AND AttendanceDate BETWEEN p_start_date AND p_end_date;
    
    -- Get hourly rate
    SELECT HourlyRate INTO v_hourly_rate
    FROM Employees
    WHERE EmployeeID = p_employee_id;
    
    -- Calculate leave pay for approved paid leaves
    SELECT SUM(lr.DaysRequested * 8 * e.HourlyRate) INTO v_leave_pay
    FROM LeaveRequests lr
    JOIN LeaveTypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
    JOIN Employees e ON lr.EmployeeID = e.EmployeeID
    WHERE lr.EmployeeID = p_employee_id
    AND lr.Status = 'Approved'
    AND lt.IsPaid = 'Yes'
    AND lr.StartDate BETWEEN p_start_date AND p_end_date;
    
    SET v_gross_pay = (v_hours_worked * v_hourly_rate) + COALESCE(v_leave_pay, 0);
    SET v_net_pay = v_gross_pay - p_deductions;
    
    -- Insert into payroll
    INSERT INTO Payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, HourlyRate, GrossPay, LeavePay, Deductions, NetPay)
    VALUES (p_employee_id, p_start_date, p_end_date, v_hours_worked, v_hourly_rate, v_gross_pay, v_leave_pay, p_deductions, v_net_pay);
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcessSale` (IN `p_customer_id` INT, IN `p_store_id` INT, IN `p_products` JSON, IN `p_payment_method` VARCHAR(20), IN `p_discount_amount` DECIMAL(10,2), IN `p_points_used` INT, OUT `p_sale_id` INT)   BEGIN
    DECLARE v_total_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_tax_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_count INT;
    DECLARE v_loyalty_points INT;
    DECLARE v_points_discount DECIMAL(10,2);
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Calculate points discount (assuming 1 point = $1 discount)
    SET v_points_discount = p_points_used * 1.00;
    
    -- Get product count from JSON
    SET v_count = JSON_LENGTH(p_products);
    
    -- Create sale record
    INSERT INTO Sales (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus, PointsUsed)
    VALUES (p_customer_id, p_store_id, 0, 0, p_discount_amount + v_points_discount, p_payment_method, 'Paid', p_points_used);
    
    SET p_sale_id = LAST_INSERT_ID();
    
    -- Process each product
    WHILE v_i < v_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].productID')));
        SET v_quantity = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].quantity')));
        SET v_unit_price = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].unitPrice')));
        SET v_subtotal = v_quantity * v_unit_price;
        SET v_total_amount = v_total_amount + v_subtotal;
        
        -- Insert sale detail
        INSERT INTO SaleDetails (SaleID, ProductID, Quantity, UnitPrice, Subtotal)
        VALUES (p_sale_id, v_product_id, v_quantity, v_unit_price, v_subtotal);
        
        -- Update inventory
        UPDATE Inventory 
        SET Quantity = Quantity - v_quantity 
        WHERE ProductID = v_product_id AND StoreID = p_store_id;
        
        -- Record stock movement
        INSERT INTO StockMovements (ProductID, StoreID, MovementType, Quantity, ReferenceID, ReferenceType, CreatedBy)
        VALUES (v_product_id, p_store_id, 'OUT', v_quantity, p_sale_id, 'Sale', USER());
        
        SET v_i = v_i + 1;
    END WHILE;
    
    -- Calculate tax (10%)
    SET v_tax_amount = (v_total_amount - (p_discount_amount + v_points_discount)) * 0.10;
    
    -- Update sale totals
    UPDATE Sales 
    SET TotalAmount = v_total_amount, TaxAmount = v_tax_amount, PointsEarned = FLOOR((v_total_amount + v_tax_amount - (p_discount_amount + v_points_discount)) / 10)
    WHERE SaleID = p_sale_id;
    
    -- Update customer loyalty points
    IF p_customer_id IS NOT NULL THEN
        UPDATE Customers 
        SET LoyaltyPoints = LoyaltyPoints - p_points_used + FLOOR((v_total_amount + v_tax_amount - (p_discount_amount + v_points_discount)) / 10)
        WHERE CustomerID = p_customer_id;
    END IF;
    
    -- Record in General Ledger
    INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Revenue', 'Sales Revenue', 'Product Sales', v_total_amount, p_sale_id, 'Sale', p_store_id);
    
    IF v_tax_amount > 0 THEN
        INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, StoreID)
        VALUES (NOW(), 'Liability', 'Sales Tax Payable', 'Sales Tax', v_tax_amount, p_sale_id, 'Sale', p_store_id);
        
        INSERT INTO TaxRecords (TransactionID, TransactionType, TaxAmount, TaxType, TaxRate, StoreID)
        VALUES (p_sale_id, 'Sale', v_tax_amount, 'Sales Tax', 0.10, p_store_id);
    END IF;
    
    COMMIT;
END$$

CREATE DEFINER=`root`@`localhost` PROCEDURE `ReceivePurchaseOrder` (IN `p_purchase_order_id` INT, IN `p_received_products` JSON)   BEGIN
    DECLARE v_product_id INT;
    DECLARE v_received_qty INT;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_count INT;
    DECLARE v_store_id INT;
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get store ID
    SELECT StoreID INTO v_store_id FROM PurchaseOrders WHERE PurchaseOrderID = p_purchase_order_id;
    
    -- Get product count from JSON
    SET v_count = JSON_LENGTH(p_received_products);
    
    -- Process each received product
    WHILE v_i < v_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_received_products, CONCAT('$[', v_i, '].productID')));
        SET v_received_qty = JSON_UNQUOTE(JSON_EXTRACT(p_received_products, CONCAT('$[', v_i, '].receivedQuantity')));
        
        -- Update purchase order details
        UPDATE PurchaseOrderDetails 
        SET ReceivedQuantity = ReceivedQuantity + v_received_qty
        WHERE PurchaseOrderID = p_purchase_order_id AND ProductID = v_product_id;
        
        -- Update inventory
        INSERT INTO Inventory (ProductID, StoreID, Quantity) 
        VALUES (v_product_id, v_store_id, v_received_qty)
        ON DUPLICATE KEY UPDATE Quantity = Quantity + v_received_qty;
        
        -- Record stock movement
        INSERT INTO StockMovements (ProductID, StoreID, MovementType, Quantity, ReferenceID, ReferenceType, CreatedBy)
        VALUES (v_product_id, v_store_id, 'IN', v_received_qty, p_purchase_order_id, 'Purchase', USER());
        
        SET v_i = v_i + 1;
    END WHILE;
    
    -- Update purchase order status
    UPDATE PurchaseOrders SET Status = 'Received' WHERE PurchaseOrderID = p_purchase_order_id;
    
    COMMIT;
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accountspayable`
--

CREATE TABLE `accountspayable` (
  `APID` int(11) NOT NULL,
  `PurchaseOrderID` int(11) DEFAULT NULL,
  `SupplierID` int(11) DEFAULT NULL,
  `AmountDue` decimal(10,2) NOT NULL,
  `DueDate` date NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Overdue','Partial') DEFAULT 'Pending',
  `PaidAmount` decimal(10,2) DEFAULT 0.00,
  `PaymentDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `accountspayable`
--
DELIMITER $$
CREATE TRIGGER `tr_mark_overdue_ap` BEFORE UPDATE ON `accountspayable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `accountsreceivable`
--

CREATE TABLE `accountsreceivable` (
  `ARID` int(11) NOT NULL,
  `SaleID` int(11) DEFAULT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `AmountDue` decimal(10,2) NOT NULL,
  `DueDate` date NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Overdue','Partial') DEFAULT 'Pending',
  `PaidAmount` decimal(10,2) DEFAULT 0.00,
  `PaymentDate` datetime DEFAULT NULL,
  `DiscountFromPoints` decimal(10,2) DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `accountsreceivable`
--
DELIMITER $$
CREATE TRIGGER `tr_mark_overdue_ar` BEFORE UPDATE ON `accountsreceivable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `attendance`
--

CREATE TABLE `attendance` (
  `AttendanceID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `AttendanceDate` date NOT NULL,
  `LogInTime` datetime DEFAULT NULL,
  `LogOutTime` datetime DEFAULT NULL,
  `HoursWorked` decimal(5,2) DEFAULT NULL,
  `Notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `attendance`
--

INSERT INTO `attendance` (`AttendanceID`, `EmployeeID`, `AttendanceDate`, `LogInTime`, `LogOutTime`, `HoursWorked`, `Notes`) VALUES
(2, 23, '2025-11-10', NULL, NULL, NULL, 'On Sick Leave'),
(3, 23, '2025-11-11', NULL, NULL, NULL, 'On Sick Leave'),
(5, 31, '2025-11-15', NULL, NULL, NULL, 'On Birthday Leave'),
(6, 54, '2025-11-10', NULL, NULL, NULL, 'On Sick Leave'),
(7, 54, '2025-11-11', NULL, NULL, NULL, 'On Sick Leave'),
(9, 53, '2025-11-15', NULL, NULL, NULL, 'On Birthday Leave');

--
-- Triggers `attendance`
--
DELIMITER $$
CREATE TRIGGER `tr_calculate_hours` BEFORE UPDATE ON `attendance` FOR EACH ROW BEGIN
    IF NEW.LogOutTime IS NOT NULL AND NEW.LogInTime IS NOT NULL THEN
        SET NEW.HoursWorked = TIMESTAMPDIFF(MINUTE, NEW.LogInTime, NEW.LogOutTime) / 60.0;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `branches`
--

CREATE TABLE `branches` (
  `BranchID` int(11) NOT NULL,
  `BranchName` varchar(100) NOT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `branches`
--

INSERT INTO `branches` (`BranchID`, `BranchName`, `Location`, `CreatedAt`) VALUES
(1, 'SM Megamall Branch', 'Mandaluyong, Metro Manila', '2025-10-30 15:52:53'),
(2, 'Ayala Center Cebu', 'Cebu Business Park, Cebu City', '2025-10-30 15:52:53'),
(3, 'Davao Gateway', 'Ecoland, Davao City', '2025-10-30 15:52:53'),
(4, 'Greenbelt Main', 'Makati, Metro Manila', '2025-10-30 15:52:53');

-- --------------------------------------------------------

--
-- Table structure for table `budgets`
--

CREATE TABLE `budgets` (
  `BudgetID` int(11) NOT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `Department` varchar(50) DEFAULT NULL,
  `Month` int(11) NOT NULL,
  `Year` int(11) NOT NULL,
  `ProposedAmount` decimal(10,2) NOT NULL,
  `ApprovedAmount` decimal(10,2) DEFAULT 0.00,
  `Status` enum('Proposed','Approved','Rejected','Allocated') DEFAULT 'Proposed',
  `ApprovedBy` int(11) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `customers`
--

CREATE TABLE `customers` (
  `CustomerID` int(11) NOT NULL,
  `MemberNumber` varchar(20) DEFAULT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `LoyaltyPoints` int(11) DEFAULT 0,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `customers`
--

INSERT INTO `customers` (`CustomerID`, `MemberNumber`, `FirstName`, `LastName`, `Email`, `Phone`, `Address`, `LoyaltyPoints`, `Status`, `CreatedAt`) VALUES
(1, 'MEM-001', 'Alice', 'Johnson', 'alice@email.com', '555-2001', '111 Customer St', 150, 'Active', '2025-10-22 08:41:50'),
(2, 'MEM-002', 'Bob', 'Smith', 'bob@email.com', '555-2002', '222 Buyer Ave', 75, 'Active', '2025-10-22 08:41:50'),
(3, 'MEM-003', 'Carol', 'Davis', 'carol@email.com', '555-2003', '333 Shopper Rd', 200, 'Active', '2025-10-22 08:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `departments`
--

CREATE TABLE `departments` (
  `DepartmentID` int(11) NOT NULL,
  `BranchID` int(11) NOT NULL,
  `DepartmentName` varchar(100) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `employees`
--

CREATE TABLE `employees` (
  `EmployeeID` int(11) NOT NULL,
  `DepartmentID` int(11) DEFAULT NULL,
  `Department` varchar(50) DEFAULT NULL,
  `Role` varchar(50) DEFAULT NULL,
  `FirstName` varchar(50) NOT NULL,
  `MiddleName` varchar(50) DEFAULT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Landline` varchar(20) DEFAULT NULL,
  `EmergencyContactName` varchar(100) DEFAULT NULL,
  `EmergencyContactNumber` varchar(20) DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `HireDate` date NOT NULL,
  `BirthDate` date DEFAULT NULL,
  `Age` int(3) DEFAULT NULL,
  `PlaceOfBirth` varchar(100) DEFAULT NULL,
  `StreetAddress` varchar(255) DEFAULT NULL,
  `City` varchar(100) DEFAULT NULL,
  `ZipCode` varchar(20) DEFAULT NULL,
  `Gender` enum('Male','Female','Other') DEFAULT NULL,
  `MaritalStatus` enum('Single','Married','Divorced','Widowed') DEFAULT NULL,
  `Religion` varchar(50) DEFAULT NULL,
  `Salary` decimal(10,2) DEFAULT 0.00,
  `HourlyRate` decimal(10,2) NOT NULL DEFAULT 0.00,
  `BankAccountNumber` varchar(50) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `Status` enum('Active','Inactive','On Leave','Terminated') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `region` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `employees`
--

INSERT INTO `employees` (`EmployeeID`, `DepartmentID`, `Department`, `Role`, `FirstName`, `MiddleName`, `LastName`, `Email`, `Landline`, `EmergencyContactName`, `EmergencyContactNumber`, `image`, `Phone`, `HireDate`, `BirthDate`, `Age`, `PlaceOfBirth`, `StreetAddress`, `City`, `ZipCode`, `Gender`, `MaritalStatus`, `Religion`, `Salary`, `HourlyRate`, `BankAccountNumber`, `StoreID`, `Status`, `CreatedAt`, `UpdatedAt`, `region`) VALUES
(23, NULL, 'Inventory Management', 'Procurement Manager', 'Angeline', '', 'Crisostomo', 'angelinecrisostomo52@gmail.com', '09633639518', 'Angeline Crisostomo', '09633639518', NULL, '09532598051', '2025-10-29', '2004-12-10', 32, 'caloocan city', '16454 Rosal Street Bario San Lazaro Tala Caloocan City', 'Caloocan City', '1477', 'Male', 'Single', 'catholic', NULL, 0.00, '111111111111111111111111111', NULL, 'Active', '2025-10-29 07:51:56', '2025-10-29 07:51:56', NULL),
(27, NULL, 'Procurement', 'Procurement Manager', 'Angeline', '', 'Crisostomo', 'geline@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, NULL, 0.00, NULL, NULL, 'Active', '2025-10-31 00:03:01', '2025-10-31 06:34:34', NULL),
(31, NULL, 'Sales and Customer Relation Management', 'Cashier', 'Angeline', NULL, 'Crisostomo', 'angelinecrisostomo5211@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, 0.00, NULL, NULL, 'Active', '2025-10-31 01:11:53', '2025-10-31 01:11:53', NULL),
(48, NULL, 'Accounting', 'Accountant', 'Angelinecutie', '', 'Crisostomo', 'test1user@test.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 02:47:52', '2025-10-31 02:47:52', NULL),
(51, NULL, 'Human Resource', 'HR Manager', 'Angeline', '', 'Crisostomo', 'angeline12121crisostomo52@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 03:50:21', '2025-10-31 03:50:21', NULL),
(52, NULL, 'Sales and Customer Relation Management', 'Sales Manager', 'Angelinehehe', '', 'Crisostomo', 'testuser21211@test.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 03:50:40', '2025-10-31 03:50:40', NULL),
(53, NULL, 'Procurement', 'Procurement Manager', 'Emy', '', 'Ly', 'hehe@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 04:08:55', '2025-10-31 06:41:10', NULL),
(54, NULL, 'Accounting', 'Accountant', 'Zedrick', '', 'Abraham', 'zedmabaho@test.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 04:09:44', '2025-10-31 04:09:44', NULL),
(55, NULL, 'Sales and Customer Relation Management', 'Customer Service', 'Angelinehh', '', 'Chua', 'testuser11111@test.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 05:42:43', '2025-10-31 05:42:43', NULL),
(56, NULL, 'Accounting', 'Accountant', 'Johan', '', 'Recta', 'johanrecta@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 06:45:34', '2025-10-31 06:45:34', NULL),
(57, NULL, 'Inventory Management', 'Inventory Encoder', 'Leon', '', 'Recto', 'testest@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 06:46:06', '2025-10-31 06:51:49', NULL),
(58, NULL, 'Inventory', 'Inventory Encoder', 'Angelinerr', '', 'Crisostomorr', 'angelinecrisostomo5121212@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 06:46:31', '2025-10-31 06:46:31', NULL),
(59, NULL, 'Accounting', 'Accountant', 'Angel', '', 'Chua', 'angelchua@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 06:57:01', '2025-10-31 06:57:50', NULL),
(60, NULL, 'Procurement', 'Procurement Manager', 'Angelica', '', 'Chua', 'angelcrisostomo52@gmail.com', NULL, NULL, NULL, NULL, NULL, '2025-10-31', NULL, NULL, NULL, NULL, NULL, NULL, 'Male', 'Single', NULL, 0.00, 0.00, NULL, NULL, 'Active', '2025-10-31 12:54:37', '2025-10-31 12:56:10', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `expenses`
--

CREATE TABLE `expenses` (
  `ExpenseID` int(11) NOT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `ExpenseDate` datetime DEFAULT current_timestamp(),
  `Description` varchar(200) DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Category` enum('Rent','Utilities','Payroll','Supplies','Marketing','Maintenance','Other') NOT NULL,
  `ApprovedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `generalledger`
--

CREATE TABLE `generalledger` (
  `LedgerID` int(11) NOT NULL,
  `TransactionDate` datetime DEFAULT current_timestamp(),
  `AccountType` enum('Revenue','Expense','Asset','Liability','Equity') NOT NULL,
  `AccountName` varchar(100) NOT NULL,
  `Description` varchar(200) DEFAULT NULL,
  `Debit` decimal(10,2) DEFAULT 0.00,
  `Credit` decimal(10,2) DEFAULT 0.00,
  `ReferenceID` int(11) DEFAULT NULL,
  `ReferenceType` enum('Sale','Purchase','Expense','Payment','Adjustment','Other') NOT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `CreatedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `inventory`
--

CREATE TABLE `inventory` (
  `InventoryID` int(11) NOT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL DEFAULT 0,
  `LastUpdated` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Triggers `inventory`
--
DELIMITER $$
CREATE TRIGGER `tr_inventory_update` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
    DECLARE v_cost_price DECIMAL(10,2);
    DECLARE v_value_change DECIMAL(10,2);
    
    -- Get cost price
    SELECT CostPrice INTO v_cost_price FROM Products WHERE ProductID = NEW.ProductID;
    
    -- Calculate value change
    SET v_value_change = (NEW.Quantity - OLD.Quantity) * v_cost_price;
    
    -- Record in general ledger if there's a change
    IF v_value_change != 0 THEN
        INSERT INTO GeneralLedger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID, CreatedBy)
        VALUES (
            NOW(), 
            'Asset', 
            'Inventory', 
            CONCAT('Inventory adjustment for Product ID: ', NEW.ProductID),
            CASE WHEN v_value_change > 0 THEN v_value_change ELSE 0 END,
            CASE WHEN v_value_change < 0 THEN ABS(v_value_change) ELSE 0 END,
            NEW.ProductID,
            'Adjustment',
            NEW.StoreID,
            USER()
        );
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_low_stock_alert` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
    DECLARE v_min_stock INT;
    
    -- Get minimum stock level
    SELECT MinStockLevel INTO v_min_stock FROM Products WHERE ProductID = NEW.ProductID;
    
    -- Create support ticket for low stock (simplified notification)
    IF NEW.Quantity <= v_min_stock AND OLD.Quantity > v_min_stock THEN
        INSERT INTO SupportTickets (CustomerID, StoreID, Subject, Description, Status, Priority, AssignedTo)
        VALUES (
            NULL, 
            NEW.StoreID, 
            'Low Stock Alert',
            CONCAT('Product ID ', NEW.ProductID, ' is running low. Current stock: ', NEW.Quantity, ', Minimum: ', v_min_stock),
            'Open',
            'Medium',
            'Inventory Manager'
        );
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `leavebalances`
--

CREATE TABLE `leavebalances` (
  `BalanceID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `LeaveTypeID` int(11) DEFAULT NULL,
  `Year` int(11) NOT NULL,
  `Entitlement` int(11) NOT NULL DEFAULT 0,
  `Taken` int(11) NOT NULL DEFAULT 0,
  `Remaining` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `leaverequests`
--

CREATE TABLE `leaverequests` (
  `LeaveRequestID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `LeaveTypeID` int(11) DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `DaysRequested` int(11) NOT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `ApprovedBy` int(11) DEFAULT NULL,
  `RequestDate` datetime DEFAULT current_timestamp(),
  `Comments` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leaverequests`
--

INSERT INTO `leaverequests` (`LeaveRequestID`, `EmployeeID`, `LeaveTypeID`, `StartDate`, `EndDate`, `DaysRequested`, `Status`, `ApprovedBy`, `RequestDate`, `Comments`) VALUES
(24, 23, 1, '2025-11-10', '2025-11-11', 2, 'Approved', 1, '2025-11-05 00:00:00', 'Had to go to the hospital for a check-up.'),
(25, 31, 2, '2025-11-15', '2025-11-15', 1, 'Approved', 1, '2025-11-02 00:00:00', 'Birthday celebration.'),
(26, 48, 1, '2025-11-20', '2025-11-21', 2, 'Approved', 6, '2025-11-18 00:00:00', 'Feeling unwell, flu symptoms.'),
(27, 23, 2, '2025-12-01', '2025-12-01', 1, 'Rejected', 1, '2025-11-28 00:00:00', 'Already took birthday leave this year.'),
(28, 54, 1, '2025-11-10', '2025-11-11', 2, 'Approved', 1, '2025-11-05 00:00:00', 'Had to go to the hospital for a check-up.'),
(29, 53, 2, '2025-11-15', '2025-11-15', 1, 'Approved', 1, '2025-11-02 00:00:00', 'Birthday celebration.'),
(30, 52, 1, '2025-11-20', '2025-11-21', 2, 'Rejected', 6, '2025-11-18 00:00:00', 'Feeling unwell, flu symptoms.'),
(31, 48, 2, '2025-12-01', '2025-12-01', 1, 'Rejected', 1, '2025-11-28 00:00:00', 'Already took birthday leave this year.'),
(32, 55, 1, '2025-11-03', '2025-11-03', 1, 'Rejected', 6, '2025-11-02 00:00:00', 'Sudden illness, awaiting doctor\'s note.'),
(33, 58, 1, '2025-12-15', '2025-12-18', 4, 'Approved', 6, '2025-11-01 00:00:00', 'Scheduled year-end vacation.'),
(34, 57, 2, '2025-12-25', '2025-12-25', 1, 'Rejected', 6, '2025-11-20 00:00:00', 'Scheduled birthday leave.'),
(35, 56, 1, '2025-12-01', '2025-12-05', 5, 'Approved', 6, '2025-11-10 00:00:00', 'Request for extended sick leave.'),
(36, 23, 1, '2026-01-10', '2026-01-10', 1, 'Rejected', 6, '2025-12-01 00:00:00', 'Personal emergency.'),
(37, 58, 1, '2025-12-15', '2025-12-18', 4, 'Approved', 6, '2025-11-01 00:00:00', 'Scheduled year-end vacation.');

--
-- Triggers `leaverequests`
--
DELIMITER $$
CREATE TRIGGER `tr_leave_attendance` AFTER INSERT ON `leaverequests` FOR EACH ROW BEGIN
    IF NEW.Status = 'Approved' THEN
        INSERT INTO Attendance (EmployeeID, AttendanceDate, Notes)
        SELECT NEW.EmployeeID, d.Date, CONCAT('On ', lt.LeaveTypeName)
        FROM (
            SELECT NEW.StartDate + INTERVAL (n-1) DAY AS Date
            FROM (
                SELECT a.N + b.N * 10 + 1 AS n
                FROM (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
                CROSS JOIN (SELECT 0 AS N UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
            ) numbers
            WHERE n <= DATEDIFF(NEW.EndDate, NEW.StartDate) + 1
        ) d
        JOIN LeaveTypes lt ON NEW.LeaveTypeID = lt.LeaveTypeID
        ON DUPLICATE KEY UPDATE Notes = CONCAT('On ', lt.LeaveTypeName);
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `tr_update_leave_balance` AFTER UPDATE ON `leaverequests` FOR EACH ROW BEGIN
    IF NEW.Status = 'Approved' AND OLD.Status != 'Approved' THEN
        UPDATE LeaveBalances
        SET Taken = Taken + NEW.DaysRequested,
            Remaining = Remaining - NEW.DaysRequested
        WHERE EmployeeID = NEW.EmployeeID
        AND LeaveTypeID = NEW.LeaveTypeID
        AND Year = YEAR(NEW.StartDate);
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Table structure for table `leavetypes`
--

CREATE TABLE `leavetypes` (
  `LeaveTypeID` int(11) NOT NULL,
  `LeaveTypeName` varchar(50) NOT NULL,
  `Description` text DEFAULT NULL,
  `IsPaid` enum('Yes','No') DEFAULT 'Yes',
  `DefaultEntitlement` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leavetypes`
--

INSERT INTO `leavetypes` (`LeaveTypeID`, `LeaveTypeName`, `Description`, `IsPaid`, `DefaultEntitlement`) VALUES
(1, 'Sick Leave', 'Paid leave for medical reasons', 'Yes', 7),
(2, 'Birthday Leave', 'One day off for employee’s birthday', 'Yes', 1);

-- --------------------------------------------------------

--
-- Table structure for table `payroll`
--

CREATE TABLE `payroll` (
  `PayrollID` int(11) NOT NULL,
  `EmployeeID` int(11) DEFAULT NULL,
  `PayPeriodStart` date NOT NULL,
  `PayPeriodEnd` date NOT NULL,
  `HoursWorked` decimal(5,2) NOT NULL,
  `HourlyRate` decimal(10,2) NOT NULL,
  `GrossPay` decimal(10,2) NOT NULL,
  `LeavePay` decimal(10,2) DEFAULT 0.00,
  `Deductions` decimal(10,2) DEFAULT 0.00,
  `NetPay` decimal(10,2) NOT NULL,
  `PaymentDate` date DEFAULT NULL,
  `Status` enum('Pending','Paid','Processed') DEFAULT 'Pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `ProductID` int(11) NOT NULL,
  `SKU` varchar(50) NOT NULL,
  `Brand` varchar(50) NOT NULL,
  `Model` varchar(100) NOT NULL,
  `Size` decimal(4,1) NOT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `CostPrice` decimal(10,2) NOT NULL,
  `SellingPrice` decimal(10,2) NOT NULL,
  `MinStockLevel` int(11) DEFAULT 10,
  `MaxStockLevel` int(11) DEFAULT 100,
  `SupplierID` int(11) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`ProductID`, `SKU`, `Brand`, `Model`, `Size`, `Color`, `CostPrice`, `SellingPrice`, `MinStockLevel`, `MaxStockLevel`, `SupplierID`, `Status`, `CreatedAt`) VALUES
(1, 'NK-AM-001-9.5-BLK', 'Nike', 'Air Max 90', 9.5, 'Black', 65.00, 120.00, 5, 50, 1, 'Active', '2025-10-22 08:41:50'),
(2, 'NK-AM-001-10-WHT', 'Nike', 'Air Max 90', 10.0, 'White', 65.00, 120.00, 5, 50, 1, 'Active', '2025-10-22 08:41:50'),
(3, 'AD-UB-001-9-BLU', 'Adidas', 'Ultraboost 22', 9.0, 'Blue', 75.00, 140.00, 3, 30, 2, 'Active', '2025-10-22 08:41:50'),
(4, 'AD-UB-001-10-GRY', 'Adidas', 'Ultraboost 22', 10.0, 'Grey', 75.00, 140.00, 3, 30, 2, 'Active', '2025-10-22 08:41:50'),
(5, 'LC-CS-001-8.5-BRN', 'Local Brand', 'Casual Sneaker', 8.5, 'Brown', 35.00, 70.00, 10, 100, 3, 'Active', '2025-10-22 08:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `purchaseorderdetails`
--

CREATE TABLE `purchaseorderdetails` (
  `PurchaseOrderDetailID` int(11) NOT NULL,
  `PurchaseOrderID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitCost` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `ReceivedQuantity` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `purchaseorders`
--

CREATE TABLE `purchaseorders` (
  `PurchaseOrderID` int(11) NOT NULL,
  `SupplierID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `OrderDate` datetime DEFAULT current_timestamp(),
  `ExpectedDeliveryDate` date DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Status` enum('Pending','Received','Cancelled','Partial') DEFAULT 'Pending',
  `CreatedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE `roles` (
  `RoleID` int(11) NOT NULL,
  `RoleName` varchar(100) NOT NULL,
  `Description` text DEFAULT NULL,
  `Permissions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`Permissions`)),
  `IsActive` enum('Yes','No') DEFAULT 'Yes',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `saledetails`
--

CREATE TABLE `saledetails` (
  `SaleDetailID` int(11) NOT NULL,
  `SaleID` int(11) DEFAULT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `Quantity` int(11) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `sales`
--

CREATE TABLE `sales` (
  `SaleID` int(11) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `SaleDate` datetime DEFAULT current_timestamp(),
  `TotalAmount` decimal(10,2) NOT NULL,
  `TaxAmount` decimal(10,2) DEFAULT 0.00,
  `DiscountAmount` decimal(10,2) DEFAULT 0.00,
  `PointsUsed` int(11) DEFAULT 0,
  `PointsEarned` int(11) DEFAULT 0,
  `PaymentStatus` enum('Paid','Credit','Refunded','Partial') DEFAULT 'Paid',
  `PaymentMethod` enum('Cash','Card','Credit','Loyalty') DEFAULT 'Cash',
  `SalespersonID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stockmovements`
--

CREATE TABLE `stockmovements` (
  `MovementID` int(11) NOT NULL,
  `ProductID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `MovementType` enum('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
  `Quantity` int(11) NOT NULL,
  `ReferenceID` int(11) DEFAULT NULL,
  `ReferenceType` enum('Sale','Purchase','Transfer','Adjustment','Return') NOT NULL,
  `MovementDate` datetime DEFAULT current_timestamp(),
  `Notes` text DEFAULT NULL,
  `CreatedBy` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `stores`
--

CREATE TABLE `stores` (
  `StoreID` int(11) NOT NULL,
  `StoreName` varchar(100) NOT NULL,
  `Location` text DEFAULT NULL,
  `ManagerName` varchar(50) DEFAULT NULL,
  `ContactPhone` varchar(20) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `stores`
--

INSERT INTO `stores` (`StoreID`, `StoreName`, `Location`, `ManagerName`, `ContactPhone`, `Status`, `CreatedAt`) VALUES
(1, 'Maria Collections Bagong Silang', 'Bagong Silang, Caloocan City', 'Maria', '09XX-XXX-XXXX', 'Active', '2025-10-28 03:10:22'),
(2, 'Main Branch', 'Manila', 'Mr. Admin', '09170001111', 'Active', '2025-10-29 03:52:40'),
(3, 'Warehouse', 'Quezon City', 'Ms. Manager', '09180002222', 'Active', '2025-10-29 03:52:40');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `SupplierID` int(11) NOT NULL,
  `SupplierName` varchar(100) NOT NULL,
  `ContactName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text DEFAULT NULL,
  `PaymentTerms` varchar(50) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp(),
  `UpdatedAt` datetime DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`SupplierID`, `SupplierName`, `ContactName`, `Email`, `Phone`, `Address`, `PaymentTerms`, `Status`, `CreatedAt`, `UpdatedAt`) VALUES
(1, 'Nike Distribution', 'Sarah Wilson', 'sarah@nikedist.com', '555-1001', '100 Nike Way, Portland, OR', 'Net 30', 'Active', '2025-10-22 08:41:50', '2025-10-22 08:41:50'),
(2, 'Adidas Supply Co', 'Tom Brown', 'tom@adidas-supply.com', '555-1002', '200 Adidas St, Germany', 'Net 45', 'Active', '2025-10-22 08:41:50', '2025-10-22 08:41:50'),
(3, 'Local Shoe Warehouse', 'Lisa Garcia', 'lisa@localshoes.com', '555-1003', '300 Local Ave, Local City', 'Net 15', 'Active', '2025-10-22 08:41:50', '2025-10-22 08:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `supporttickets`
--

CREATE TABLE `supporttickets` (
  `TicketID` int(11) NOT NULL,
  `CustomerID` int(11) DEFAULT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `IssueDate` datetime DEFAULT current_timestamp(),
  `Subject` varchar(200) NOT NULL,
  `Description` text NOT NULL,
  `Status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `Priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `AssignedTo` varchar(50) DEFAULT NULL,
  `Resolution` text DEFAULT NULL,
  `ResolvedDate` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `taxrecords`
--

CREATE TABLE `taxrecords` (
  `TaxRecordID` int(11) NOT NULL,
  `TransactionID` int(11) DEFAULT NULL,
  `TransactionType` enum('Sale','Purchase') NOT NULL,
  `TaxAmount` decimal(10,2) NOT NULL,
  `TaxDate` datetime DEFAULT current_timestamp(),
  `TaxType` varchar(50) NOT NULL,
  `TaxRate` decimal(5,4) NOT NULL,
  `StoreID` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `UserID` int(11) NOT NULL,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Role` enum('Admin','Manager','Cashier','Accountant','Support','Inventory','Sales','Procurement','Accounting','Customers','HR') NOT NULL,
  `StoreID` int(11) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`UserID`, `Username`, `PasswordHash`, `FirstName`, `LastName`, `Email`, `Role`, `StoreID`, `Status`, `CreatedAt`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin@shoestore.com', 'Admin', NULL, 'Active', '2025-10-22 08:41:50'),
(2, 'manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'john@shoestore.com', 'Manager', NULL, 'Active', '2025-10-22 08:41:50'),
(3, 'cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Johnson', 'mary@shoestore.com', 'Cashier', NULL, 'Active', '2025-10-22 08:41:50'),
(4, 'inventory', '$2y$12$P49QiiXYhWameOP/nIC/VecOdajnrBfRRxZBrxg4/qXGKnsPv./06', 'Val Javez', 'Lamsen', 'vjlamsenlamsen28@gmail.com', 'Inventory', 1, 'Active', '2025-10-28 03:48:35'),
(5, 'inventory2', '$2y$12$mtZ.Xt.4wJhe9xwW3JstCuaim1kvBHC5OzgIpg77aIc4tHTFQBi8a', 'Gene', 'Tabibis', 'gegesgene@gmail.com', 'Inventory', NULL, 'Active', '2025-10-28 03:54:49'),
(6, 'humores', '$2y$12$D9iZI/f3T6rd/JUqjCiat.Dwq73tIErZAlArV2UotDncNaUg8R/TG', 'Daril', 'Pidil', 'darilpidil@gmail.com', 'HR', NULL, 'Active', '2025-10-28 04:08:11');

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_financial_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_financial_summary` (
`TransactionDate` date
,`StoreName` varchar(100)
,`AccountType` enum('Revenue','Expense','Asset','Liability','Equity')
,`TotalDebits` decimal(32,2)
,`TotalCredits` decimal(32,2)
,`NetAmount` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_inventory_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_inventory_summary` (
`ProductID` int(11)
,`SKU` varchar(50)
,`Brand` varchar(50)
,`Model` varchar(100)
,`Size` decimal(4,1)
,`Color` varchar(50)
,`StoreName` varchar(100)
,`Quantity` int(11)
,`MinStockLevel` int(11)
,`MaxStockLevel` int(11)
,`StockStatus` varchar(9)
,`InventoryValue` decimal(20,2)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_outstanding_receivables`
-- (See below for the actual view)
--
CREATE TABLE `v_outstanding_receivables` (
`ARID` int(11)
,`SaleID` int(11)
,`CustomerName` varchar(101)
,`Email` varchar(100)
,`Phone` varchar(20)
,`AmountDue` decimal(10,2)
,`PaidAmount` decimal(10,2)
,`Balance` decimal(11,2)
,`DueDate` date
,`Status` varchar(9)
,`DaysOverdue` int(7)
);

-- --------------------------------------------------------

--
-- Stand-in structure for view `v_sales_summary`
-- (See below for the actual view)
--
CREATE TABLE `v_sales_summary` (
`SaleID` int(11)
,`SaleDate` datetime
,`CustomerName` varchar(101)
,`StoreName` varchar(100)
,`TotalAmount` decimal(10,2)
,`TaxAmount` decimal(10,2)
,`DiscountAmount` decimal(10,2)
,`PaymentStatus` enum('Paid','Credit','Refunded','Partial')
,`PaymentMethod` enum('Cash','Card','Credit','Loyalty')
,`ItemCount` bigint(21)
);

-- --------------------------------------------------------

--
-- Structure for view `v_financial_summary`
--
DROP TABLE IF EXISTS `v_financial_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_financial_summary`  AS SELECT cast(`gl`.`TransactionDate` as date) AS `TransactionDate`, `s`.`StoreName` AS `StoreName`, `gl`.`AccountType` AS `AccountType`, sum(`gl`.`Debit`) AS `TotalDebits`, sum(`gl`.`Credit`) AS `TotalCredits`, sum(`gl`.`Credit` - `gl`.`Debit`) AS `NetAmount` FROM (`generalledger` `gl` join `stores` `s` on(`gl`.`StoreID` = `s`.`StoreID`)) GROUP BY cast(`gl`.`TransactionDate` as date), `gl`.`StoreID`, `gl`.`AccountType` ;

-- --------------------------------------------------------

--
-- Structure for view `v_inventory_summary`
--
DROP TABLE IF EXISTS `v_inventory_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_inventory_summary`  AS SELECT `p`.`ProductID` AS `ProductID`, `p`.`SKU` AS `SKU`, `p`.`Brand` AS `Brand`, `p`.`Model` AS `Model`, `p`.`Size` AS `Size`, `p`.`Color` AS `Color`, `s`.`StoreName` AS `StoreName`, `i`.`Quantity` AS `Quantity`, `p`.`MinStockLevel` AS `MinStockLevel`, `p`.`MaxStockLevel` AS `MaxStockLevel`, CASE WHEN `i`.`Quantity` <= `p`.`MinStockLevel` THEN 'Low Stock' WHEN `i`.`Quantity` >= `p`.`MaxStockLevel` THEN 'Overstock' ELSE 'Normal' END AS `StockStatus`, `i`.`Quantity`* `p`.`CostPrice` AS `InventoryValue` FROM ((`products` `p` join `inventory` `i` on(`p`.`ProductID` = `i`.`ProductID`)) join `stores` `s` on(`i`.`StoreID` = `s`.`StoreID`)) WHERE `p`.`Status` = 'Active' ;

-- --------------------------------------------------------

--
-- Structure for view `v_outstanding_receivables`
--
DROP TABLE IF EXISTS `v_outstanding_receivables`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_outstanding_receivables`  AS SELECT `ar`.`ARID` AS `ARID`, `ar`.`SaleID` AS `SaleID`, concat(`c`.`FirstName`,' ',coalesce(`c`.`LastName`,'')) AS `CustomerName`, `c`.`Email` AS `Email`, `c`.`Phone` AS `Phone`, `ar`.`AmountDue` AS `AmountDue`, `ar`.`PaidAmount` AS `PaidAmount`, `ar`.`AmountDue`- `ar`.`PaidAmount` AS `Balance`, `ar`.`DueDate` AS `DueDate`, CASE WHEN `ar`.`DueDate` < curdate() THEN 'Overdue' WHEN `ar`.`DueDate` = curdate() THEN 'Due Today' ELSE 'Pending' END AS `Status`, to_days(curdate()) - to_days(`ar`.`DueDate`) AS `DaysOverdue` FROM (`accountsreceivable` `ar` join `customers` `c` on(`ar`.`CustomerID` = `c`.`CustomerID`)) WHERE `ar`.`PaymentStatus` <> 'Paid' ;

-- --------------------------------------------------------

--
-- Structure for view `v_sales_summary`
--
DROP TABLE IF EXISTS `v_sales_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_sales_summary`  AS SELECT `s`.`SaleID` AS `SaleID`, `s`.`SaleDate` AS `SaleDate`, concat(`c`.`FirstName`,' ',coalesce(`c`.`LastName`,'')) AS `CustomerName`, `st`.`StoreName` AS `StoreName`, `s`.`TotalAmount` AS `TotalAmount`, `s`.`TaxAmount` AS `TaxAmount`, `s`.`DiscountAmount` AS `DiscountAmount`, `s`.`PaymentStatus` AS `PaymentStatus`, `s`.`PaymentMethod` AS `PaymentMethod`, count(`sd`.`SaleDetailID`) AS `ItemCount` FROM (((`sales` `s` left join `customers` `c` on(`s`.`CustomerID` = `c`.`CustomerID`)) join `stores` `st` on(`s`.`StoreID` = `st`.`StoreID`)) join `saledetails` `sd` on(`s`.`SaleID` = `sd`.`SaleID`)) GROUP BY `s`.`SaleID` ;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `accountspayable`
--
ALTER TABLE `accountspayable`
  ADD PRIMARY KEY (`APID`),
  ADD KEY `PurchaseOrderID` (`PurchaseOrderID`),
  ADD KEY `SupplierID` (`SupplierID`),
  ADD KEY `idx_ap_status` (`PaymentStatus`),
  ADD KEY `idx_ap_due_date` (`DueDate`);

--
-- Indexes for table `accountsreceivable`
--
ALTER TABLE `accountsreceivable`
  ADD PRIMARY KEY (`ARID`),
  ADD KEY `SaleID` (`SaleID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `idx_ar_status` (`PaymentStatus`),
  ADD KEY `idx_ar_due_date` (`DueDate`);

--
-- Indexes for table `attendance`
--
ALTER TABLE `attendance`
  ADD PRIMARY KEY (`AttendanceID`),
  ADD UNIQUE KEY `unique_employee_date` (`EmployeeID`,`AttendanceDate`),
  ADD KEY `idx_attendance_date` (`AttendanceDate`);

--
-- Indexes for table `branches`
--
ALTER TABLE `branches`
  ADD PRIMARY KEY (`BranchID`);

--
-- Indexes for table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`BudgetID`),
  ADD UNIQUE KEY `unique_store_dept_period` (`StoreID`,`Department`,`Month`,`Year`),
  ADD KEY `ApprovedBy` (`ApprovedBy`);

--
-- Indexes for table `customers`
--
ALTER TABLE `customers`
  ADD PRIMARY KEY (`CustomerID`),
  ADD UNIQUE KEY `MemberNumber` (`MemberNumber`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone` (`Phone`);

--
-- Indexes for table `departments`
--
ALTER TABLE `departments`
  ADD PRIMARY KEY (`DepartmentID`),
  ADD KEY `BranchID` (`BranchID`);

--
-- Indexes for table `employees`
--
ALTER TABLE `employees`
  ADD PRIMARY KEY (`EmployeeID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone` (`Phone`),
  ADD UNIQUE KEY `BankAccountNumber` (`BankAccountNumber`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `DepartmentID` (`DepartmentID`);

--
-- Indexes for table `expenses`
--
ALTER TABLE `expenses`
  ADD PRIMARY KEY (`ExpenseID`),
  ADD KEY `StoreID` (`StoreID`);

--
-- Indexes for table `generalledger`
--
ALTER TABLE `generalledger`
  ADD PRIMARY KEY (`LedgerID`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `idx_ledger_date` (`TransactionDate`),
  ADD KEY `idx_ledger_account` (`AccountType`);

--
-- Indexes for table `inventory`
--
ALTER TABLE `inventory`
  ADD PRIMARY KEY (`InventoryID`),
  ADD UNIQUE KEY `unique_product_store` (`ProductID`,`StoreID`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `idx_inventory_product_store` (`ProductID`,`StoreID`);

--
-- Indexes for table `leavebalances`
--
ALTER TABLE `leavebalances`
  ADD PRIMARY KEY (`BalanceID`),
  ADD UNIQUE KEY `unique_employee_leave_year` (`EmployeeID`,`LeaveTypeID`,`Year`),
  ADD KEY `leavebalances_ibfk_2` (`LeaveTypeID`);

--
-- Indexes for table `leaverequests`
--
ALTER TABLE `leaverequests`
  ADD PRIMARY KEY (`LeaveRequestID`),
  ADD KEY `EmployeeID` (`EmployeeID`),
  ADD KEY `LeaveTypeID` (`LeaveTypeID`),
  ADD KEY `ApprovedBy` (`ApprovedBy`);

--
-- Indexes for table `leavetypes`
--
ALTER TABLE `leavetypes`
  ADD PRIMARY KEY (`LeaveTypeID`),
  ADD UNIQUE KEY `LeaveTypeName` (`LeaveTypeName`);

--
-- Indexes for table `payroll`
--
ALTER TABLE `payroll`
  ADD PRIMARY KEY (`PayrollID`),
  ADD UNIQUE KEY `unique_employee_period` (`EmployeeID`,`PayPeriodStart`,`PayPeriodEnd`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`ProductID`),
  ADD UNIQUE KEY `SKU` (`SKU`),
  ADD KEY `SupplierID` (`SupplierID`),
  ADD KEY `idx_products_sku` (`SKU`),
  ADD KEY `idx_products_brand_model` (`Brand`,`Model`);

--
-- Indexes for table `purchaseorderdetails`
--
ALTER TABLE `purchaseorderdetails`
  ADD PRIMARY KEY (`PurchaseOrderDetailID`),
  ADD KEY `PurchaseOrderID` (`PurchaseOrderID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `purchaseorders`
--
ALTER TABLE `purchaseorders`
  ADD PRIMARY KEY (`PurchaseOrderID`),
  ADD KEY `SupplierID` (`SupplierID`),
  ADD KEY `StoreID` (`StoreID`);

--
-- Indexes for table `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`RoleID`),
  ADD UNIQUE KEY `RoleName` (`RoleName`);

--
-- Indexes for table `saledetails`
--
ALTER TABLE `saledetails`
  ADD PRIMARY KEY (`SaleDetailID`),
  ADD KEY `SaleID` (`SaleID`),
  ADD KEY `ProductID` (`ProductID`);

--
-- Indexes for table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`SaleID`),
  ADD KEY `idx_sales_date` (`SaleDate`),
  ADD KEY `idx_sales_customer` (`CustomerID`),
  ADD KEY `idx_sales_store` (`StoreID`);

--
-- Indexes for table `stockmovements`
--
ALTER TABLE `stockmovements`
  ADD PRIMARY KEY (`MovementID`),
  ADD KEY `ProductID` (`ProductID`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `idx_stock_movements_date` (`MovementDate`);

--
-- Indexes for table `stores`
--
ALTER TABLE `stores`
  ADD PRIMARY KEY (`StoreID`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`SupplierID`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD UNIQUE KEY `Phone` (`Phone`);

--
-- Indexes for table `supporttickets`
--
ALTER TABLE `supporttickets`
  ADD PRIMARY KEY (`TicketID`),
  ADD KEY `CustomerID` (`CustomerID`),
  ADD KEY `StoreID` (`StoreID`),
  ADD KEY `idx_support_tickets_status` (`Status`);

--
-- Indexes for table `taxrecords`
--
ALTER TABLE `taxrecords`
  ADD PRIMARY KEY (`TaxRecordID`),
  ADD KEY `StoreID` (`StoreID`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`UserID`),
  ADD UNIQUE KEY `Username` (`Username`),
  ADD UNIQUE KEY `Email` (`Email`),
  ADD KEY `StoreID` (`StoreID`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `accountspayable`
--
ALTER TABLE `accountspayable`
  MODIFY `APID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `accountsreceivable`
--
ALTER TABLE `accountsreceivable`
  MODIFY `ARID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `attendance`
--
ALTER TABLE `attendance`
  MODIFY `AttendanceID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `branches`
--
ALTER TABLE `branches`
  MODIFY `BranchID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `BudgetID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `customers`
--
ALTER TABLE `customers`
  MODIFY `CustomerID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `departments`
--
ALTER TABLE `departments`
  MODIFY `DepartmentID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `employees`
--
ALTER TABLE `employees`
  MODIFY `EmployeeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=61;

--
-- AUTO_INCREMENT for table `expenses`
--
ALTER TABLE `expenses`
  MODIFY `ExpenseID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `generalledger`
--
ALTER TABLE `generalledger`
  MODIFY `LedgerID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `inventory`
--
ALTER TABLE `inventory`
  MODIFY `InventoryID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT for table `leavebalances`
--
ALTER TABLE `leavebalances`
  MODIFY `BalanceID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `leaverequests`
--
ALTER TABLE `leaverequests`
  MODIFY `LeaveRequestID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=38;

--
-- AUTO_INCREMENT for table `leavetypes`
--
ALTER TABLE `leavetypes`
  MODIFY `LeaveTypeID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `payroll`
--
ALTER TABLE `payroll`
  MODIFY `PayrollID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `ProductID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `purchaseorderdetails`
--
ALTER TABLE `purchaseorderdetails`
  MODIFY `PurchaseOrderDetailID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `purchaseorders`
--
ALTER TABLE `purchaseorders`
  MODIFY `PurchaseOrderID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `roles`
--
ALTER TABLE `roles`
  MODIFY `RoleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `saledetails`
--
ALTER TABLE `saledetails`
  MODIFY `SaleDetailID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `sales`
--
ALTER TABLE `sales`
  MODIFY `SaleID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stockmovements`
--
ALTER TABLE `stockmovements`
  MODIFY `MovementID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `stores`
--
ALTER TABLE `stores`
  MODIFY `StoreID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `SupplierID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `supporttickets`
--
ALTER TABLE `supporttickets`
  MODIFY `TicketID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `taxrecords`
--
ALTER TABLE `taxrecords`
  MODIFY `TaxRecordID` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `UserID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `accountspayable`
--
ALTER TABLE `accountspayable`
  ADD CONSTRAINT `accountspayable_ibfk_1` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `accountspayable_ibfk_2` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL;

--
-- Constraints for table `accountsreceivable`
--
ALTER TABLE `accountsreceivable`
  ADD CONSTRAINT `accountsreceivable_ibfk_1` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  ADD CONSTRAINT `accountsreceivable_ibfk_2` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL;

--
-- Constraints for table `attendance`
--
ALTER TABLE `attendance`
  ADD CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE,
  ADD CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `departments`
--
ALTER TABLE `departments`
  ADD CONSTRAINT `departments_ibfk_1` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`);

--
-- Constraints for table `employees`
--
ALTER TABLE `employees`
  ADD CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  ADD CONSTRAINT `employees_ibfk_2` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`);

--
-- Constraints for table `expenses`
--
ALTER TABLE `expenses`
  ADD CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `generalledger`
--
ALTER TABLE `generalledger`
  ADD CONSTRAINT `generalledger_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `inventory`
--
ALTER TABLE `inventory`
  ADD CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  ADD CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE;

--
-- Constraints for table `leavebalances`
--
ALTER TABLE `leavebalances`
  ADD CONSTRAINT `leavebalances_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `leavebalances_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`) ON DELETE CASCADE;

--
-- Constraints for table `leaverequests`
--
ALTER TABLE `leaverequests`
  ADD CONSTRAINT `leaverequests_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaverequests_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`) ON DELETE CASCADE,
  ADD CONSTRAINT `leaverequests_ibfk_3` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL;

--
-- Constraints for table `payroll`
--
ALTER TABLE `payroll`
  ADD CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL;

--
-- Constraints for table `purchaseorderdetails`
--
ALTER TABLE `purchaseorderdetails`
  ADD CONSTRAINT `purchaseorderdetails_ibfk_1` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  ADD CONSTRAINT `purchaseorderdetails_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `purchaseorders`
--
ALTER TABLE `purchaseorders`
  ADD CONSTRAINT `purchaseorders_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL,
  ADD CONSTRAINT `purchaseorders_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `saledetails`
--
ALTER TABLE `saledetails`
  ADD CONSTRAINT `saledetails_ibfk_1` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  ADD CONSTRAINT `saledetails_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE;

--
-- Constraints for table `sales`
--
ALTER TABLE `sales`
  ADD CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `stockmovements`
--
ALTER TABLE `stockmovements`
  ADD CONSTRAINT `stockmovements_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  ADD CONSTRAINT `stockmovements_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE;

--
-- Constraints for table `supporttickets`
--
ALTER TABLE `supporttickets`
  ADD CONSTRAINT `supporttickets_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  ADD CONSTRAINT `supporttickets_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `taxrecords`
--
ALTER TABLE `taxrecords`
  ADD CONSTRAINT `taxrecords_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;

--
-- Constraints for table `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
