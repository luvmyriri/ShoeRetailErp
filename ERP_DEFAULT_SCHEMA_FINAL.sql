-- ShoeRetailERP - Finalized Merged Schema
-- Base: ERP DEFAULT SCHEMA + HR.sql + PROCURMENT.sql + sales.sql + integrity enhancements (UoM, AR/AP automation, payments, GL, alerts)
-- Target: MySQL 8.0+

DROP DATABASE IF EXISTS `shoeretailerp`;
CREATE DATABASE `shoeretailerp` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */;
USE `shoeretailerp`;
SET FOREIGN_KEY_CHECKS=0;

-- Core reference tables
CREATE TABLE `stores` (
  `StoreID` int NOT NULL AUTO_INCREMENT,
  `StoreName` varchar(100) NOT NULL,
  `Location` text,
  `ManagerName` varchar(50) DEFAULT NULL,
  `ContactPhone` varchar(20) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`StoreID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `users` (
  `UserID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Role` enum('Admin','Manager','Cashier','Accountant','Support','Inventory','Sales','Procurement','Accounting','Customers','HR') NOT NULL,
  `StoreID` int DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`),
  UNIQUE KEY `Username` (`Username`),
  UNIQUE KEY `Email` (`Email`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `users_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `suppliers` (
  `SupplierID` int NOT NULL AUTO_INCREMENT,
  `SupplierName` varchar(100) NOT NULL,
  `ContactName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text,
  `PaymentTerms` varchar(50) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`SupplierID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone` (`Phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Roles (from downloads)
CREATE TABLE `roles` (
  `RoleID` int NOT NULL AUTO_INCREMENT,
  `RoleName` varchar(100) NOT NULL,
  `Description` text,
  `Permissions` json DEFAULT NULL,
  `IsActive` enum('Yes','No') DEFAULT 'Yes',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`RoleID`),
  UNIQUE KEY `RoleName` (`RoleName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Units of Measure (enhancement)
CREATE TABLE `units` (
  `UnitID` int NOT NULL AUTO_INCREMENT,
  `UnitCode` varchar(16) NOT NULL,
  `UnitName` varchar(64) NOT NULL,
  PRIMARY KEY (`UnitID`),
  UNIQUE KEY `UnitCode` (`UnitCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
INSERT INTO `units` (`UnitCode`,`UnitName`) VALUES
('PC','Piece'),('PAIR','Pair'),('BOX','Box')
ON DUPLICATE KEY UPDATE UnitName=VALUES(UnitName);

-- Products
CREATE TABLE `products` (
  `ProductID` int NOT NULL AUTO_INCREMENT,
  `SKU` varchar(50) NOT NULL,
  `Brand` varchar(50) NOT NULL,
  `Model` varchar(100) NOT NULL,
  `Size` decimal(4,1) NOT NULL,
  `Color` varchar(50) DEFAULT NULL,
  `CostPrice` decimal(10,2) NOT NULL,
  `SellingPrice` decimal(10,2) NOT NULL,
  `MinStockLevel` int DEFAULT '10',
  `MaxStockLevel` int DEFAULT '100',
  `SupplierID` int DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `BaseUnitID` int NOT NULL DEFAULT 1,
  `DefaultSalesUnitID` int DEFAULT NULL,
  `DefaultPurchaseUnitID` int DEFAULT NULL,
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `SKU` (`SKU`),
  KEY `SupplierID` (`SupplierID`),
  KEY `BaseUnitID` (`BaseUnitID`),
  KEY `DefaultSalesUnitID` (`DefaultSalesUnitID`),
  KEY `DefaultPurchaseUnitID` (`DefaultPurchaseUnitID`),
  CONSTRAINT `products_ibfk_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL,
  CONSTRAINT `products_ibfk_baseunit` FOREIGN KEY (`BaseUnitID`) REFERENCES `units` (`UnitID`),
  CONSTRAINT `products_ibfk_salesunit` FOREIGN KEY (`DefaultSalesUnitID`) REFERENCES `units` (`UnitID`),
  CONSTRAINT `products_ibfk_purchaseunit` FOREIGN KEY (`DefaultPurchaseUnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Per-product allowed units
CREATE TABLE `product_units` (
  `ProductUnitID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int NOT NULL,
  `UnitID` int NOT NULL,
  `ConversionToBase` decimal(10,4) NOT NULL,
  PRIMARY KEY (`ProductUnitID`),
  UNIQUE KEY `unique_product_unit` (`ProductID`,`UnitID`),
  KEY `UnitID` (`UnitID`),
  CONSTRAINT `product_units_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `product_units_ibfk_unit` FOREIGN KEY (`UnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Customers
CREATE TABLE `customers` (
  `CustomerID` int NOT NULL AUTO_INCREMENT,
  `MemberNumber` varchar(20) DEFAULT NULL,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) DEFAULT NULL,
  `Email` varchar(100) DEFAULT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `Address` text,
  `LoyaltyPoints` int DEFAULT '0',
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CustomerID`),
  UNIQUE KEY `MemberNumber` (`MemberNumber`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone` (`Phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- HR: branches/departments/employees (merged)
CREATE TABLE `branches` (
  `BranchID` int NOT NULL AUTO_INCREMENT,
  `BranchName` varchar(100) NOT NULL,
  `Location` varchar(150) DEFAULT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`BranchID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `departments` (
  `DepartmentID` int NOT NULL AUTO_INCREMENT,
  `BranchID` int NOT NULL,
  `DepartmentName` varchar(100) NOT NULL,
  `CreatedAt` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`DepartmentID`),
  KEY `BranchID` (`BranchID`),
  CONSTRAINT `departments_ibfk_branch` FOREIGN KEY (`BranchID`) REFERENCES `branches` (`BranchID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `employees` (
  `EmployeeID` int NOT NULL AUTO_INCREMENT,
  `DepartmentID` int DEFAULT NULL,
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
  `Age` int DEFAULT NULL,
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
  `StoreID` int DEFAULT NULL,
  `Status` enum('Active','Inactive','On Leave','Terminated') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `region` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`EmployeeID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone` (`Phone`),
  UNIQUE KEY `BankAccountNumber` (`BankAccountNumber`),
  KEY `StoreID` (`StoreID`),
  KEY `DepartmentID` (`DepartmentID`),
  CONSTRAINT `employees_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  CONSTRAINT `employees_ibfk_department` FOREIGN KEY (`DepartmentID`) REFERENCES `departments` (`DepartmentID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- HR leave tables (merged)
CREATE TABLE `leavetypes` (
  `LeaveTypeID` int NOT NULL AUTO_INCREMENT,
  `LeaveTypeName` varchar(50) NOT NULL,
  `Description` text,
  `IsPaid` enum('Yes','No') DEFAULT 'Yes',
  `DefaultEntitlement` int DEFAULT '0',
  PRIMARY KEY (`LeaveTypeID`),
  UNIQUE KEY `LeaveTypeName` (`LeaveTypeName`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `leavebalances` (
  `BalanceID` int NOT NULL AUTO_INCREMENT,
  `EmployeeID` int DEFAULT NULL,
  `LeaveTypeID` int DEFAULT NULL,
  `Year` int NOT NULL,
  `Entitlement` int NOT NULL DEFAULT '0',
  `Taken` int NOT NULL DEFAULT '0',
  `Remaining` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`BalanceID`),
  UNIQUE KEY `unique_employee_leave_year` (`EmployeeID`,`LeaveTypeID`,`Year`),
  KEY `leavebalances_ibfk_2` (`LeaveTypeID`),
  CONSTRAINT `leavebalances_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `leavebalances_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `leaverequests` (
  `LeaveRequestID` int NOT NULL AUTO_INCREMENT,
  `EmployeeID` int DEFAULT NULL,
  `LeaveTypeID` int DEFAULT NULL,
  `StartDate` date NOT NULL,
  `EndDate` date NOT NULL,
  `DaysRequested` int NOT NULL,
  `Status` enum('Pending','Approved','Rejected') DEFAULT 'Pending',
  `ApprovedBy` int DEFAULT NULL,
  `RequestDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Comments` text,
  PRIMARY KEY (`LeaveRequestID`),
  KEY `EmployeeID` (`EmployeeID`),
  KEY `LeaveTypeID` (`LeaveTypeID`),
  KEY `ApprovedBy` (`ApprovedBy`),
  CONSTRAINT `leaverequests_ibfk_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_type` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_user` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `attendance` (
  `AttendanceID` int NOT NULL AUTO_INCREMENT,
  `EmployeeID` int DEFAULT NULL,
  `AttendanceDate` date NOT NULL,
  `LogInTime` datetime DEFAULT NULL,
  `LogOutTime` datetime DEFAULT NULL,
  `HoursWorked` decimal(5,2) DEFAULT NULL,
  `Notes` text,
  PRIMARY KEY (`AttendanceID`),
  UNIQUE KEY `unique_employee_date` (`EmployeeID`,`AttendanceDate`),
  KEY `idx_attendance_date` (`AttendanceDate`),
  CONSTRAINT `attendance_ibfk_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `payroll` (
  `PayrollID` int NOT NULL AUTO_INCREMENT,
  `EmployeeID` int DEFAULT NULL,
  `PayPeriodStart` date NOT NULL,
  `PayPeriodEnd` date NOT NULL,
  `HoursWorked` decimal(5,2) NOT NULL,
  `HourlyRate` decimal(10,2) NOT NULL,
  `GrossPay` decimal(10,2) NOT NULL,
  `LeavePay` decimal(10,2) DEFAULT '0.00',
  `Deductions` decimal(10,2) DEFAULT '0.00',
  `NetPay` decimal(10,2) NOT NULL,
  `PaymentDate` date DEFAULT NULL,
  `Status` enum('Pending','Paid','Processed') DEFAULT 'Pending',
  PRIMARY KEY (`PayrollID`),
  UNIQUE KEY `unique_employee_period` (`EmployeeID`,`PayPeriodStart`,`PayPeriodEnd`),
  CONSTRAINT `payroll_ibfk_employee` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Budgets (from downloads)
CREATE TABLE `budgets` (
  `BudgetID` int NOT NULL AUTO_INCREMENT,
  `StoreID` int DEFAULT NULL,
  `Department` varchar(50) DEFAULT NULL,
  `Month` int NOT NULL,
  `Year` int NOT NULL,
  `ProposedAmount` decimal(10,2) NOT NULL,
  `ApprovedAmount` decimal(10,2) DEFAULT '0.00',
  `Status` enum('Proposed','Approved','Rejected','Allocated') DEFAULT 'Proposed',
  `ApprovedBy` int DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`BudgetID`),
  UNIQUE KEY `unique_store_dept_period` (`StoreID`,`Department`,`Month`,`Year`),
  KEY `ApprovedBy` (`ApprovedBy`),
  CONSTRAINT `budgets_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE,
  CONSTRAINT `budgets_ibfk_user` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Financials
CREATE TABLE `generalledger` (
  `LedgerID` int NOT NULL AUTO_INCREMENT,
  `TransactionDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `AccountType` enum('Revenue','Expense','Asset','Liability','Equity') NOT NULL,
  `AccountName` varchar(100) NOT NULL,
  `Description` varchar(200) DEFAULT NULL,
  `Debit` decimal(10,2) DEFAULT '0.00',
  `Credit` decimal(10,2) DEFAULT '0.00',
  `ReferenceID` int DEFAULT NULL,
  `ReferenceType` enum('Sale','Purchase','Expense','Payment','Adjustment','Other') NOT NULL,
  `StoreID` int DEFAULT NULL,
  `CreatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`LedgerID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_ledger_date` (`TransactionDate`),
  KEY `idx_ledger_account` (`AccountType`),
  CONSTRAINT `generalledger_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `expenses` (
  `ExpenseID` int NOT NULL AUTO_INCREMENT,
  `StoreID` int DEFAULT NULL,
  `ExpenseDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Description` varchar(200) DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Category` enum('Rent','Utilities','Payroll','Supplies','Marketing','Maintenance','Other') NOT NULL,
  `ApprovedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`ExpenseID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `expenses_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Inventory and movements (base units)
CREATE TABLE `inventory` (
  `InventoryID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0' COMMENT 'Quantity in base units',
  `LastUpdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`InventoryID`),
  UNIQUE KEY `unique_product_store` (`ProductID`,`StoreID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_inventory_product_store` (`ProductID`,`StoreID`),
  CONSTRAINT `inventory_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `stockmovements` (
  `MovementID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `MovementType` enum('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
  `Quantity` decimal(12,4) NOT NULL COMMENT 'Quantity in transacted unit',
  `UnitID` int NOT NULL,
  `QuantityBase` decimal(12,4) NOT NULL COMMENT 'Converted to base unit',
  `ReferenceID` int DEFAULT NULL,
  `ReferenceType` enum('Sale','Purchase','Transfer','Adjustment','Return') NOT NULL,
  `MovementDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Notes` text,
  `CreatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`MovementID`),
  KEY `ProductID` (`ProductID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_stock_movements_date` (`MovementDate`),
  KEY `UnitID` (`UnitID`),
  CONSTRAINT `stockmovements_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `stockmovements_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE,
  CONSTRAINT `stockmovements_ibfk_unit` FOREIGN KEY (`UnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Sales & docs
CREATE TABLE `sales` (
  `SaleID` int NOT NULL AUTO_INCREMENT,
  `CustomerID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `SaleDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `TotalAmount` decimal(10,2) NOT NULL,
  `TaxAmount` decimal(10,2) DEFAULT '0.00',
  `DiscountAmount` decimal(10,2) DEFAULT '0.00',
  `PointsUsed` int DEFAULT '0',
  `PointsEarned` int DEFAULT '0',
  `PaymentStatus` enum('Paid','Credit','Refunded','Partial') DEFAULT 'Paid',
  `PaymentMethod` enum('Cash','Card','Credit','Loyalty') DEFAULT 'Cash',
  `SalespersonID` int DEFAULT NULL,
  PRIMARY KEY (`SaleID`),
  KEY `idx_sales_date` (`SaleDate`),
  KEY `idx_sales_customer` (`CustomerID`),
  KEY `idx_sales_store` (`StoreID`),
  KEY `idx_sales_salesperson` (`SalespersonID`),
  CONSTRAINT `sales_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  CONSTRAINT `fk_sales_salesperson` FOREIGN KEY (`SalespersonID`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `saledetails` (
  `SaleDetailID` int NOT NULL AUTO_INCREMENT,
  `SaleID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Quantity` decimal(12,4) NOT NULL,
  `SalesUnitID` int NOT NULL,
  `QuantityBase` decimal(12,4) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`SaleDetailID`),
  KEY `SaleID` (`SaleID`),
  KEY `ProductID` (`ProductID`),
  KEY `SalesUnitID` (`SalesUnitID`),
  CONSTRAINT `saledetails_ibfk_sale` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  CONSTRAINT `saledetails_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `saledetails_ibfk_unit` FOREIGN KEY (`SalesUnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `invoices` (
  `InvoiceID` int NOT NULL AUTO_INCREMENT,
  `InvoiceNumber` varchar(20) NOT NULL,
  `SaleID` int DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `InvoiceDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `TotalAmount` decimal(10,2) NOT NULL,
  `TaxAmount` decimal(10,2) DEFAULT '0.00',
  `DiscountAmount` decimal(10,2) DEFAULT '0.00',
  `PaymentMethod` enum('Cash','Card','Credit','Loyalty') DEFAULT 'Cash',
  `PaymentStatus` enum('Paid','Partial','Credit','Refunded') DEFAULT 'Paid',
  `CreatedBy` varchar(50) DEFAULT NULL,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`InvoiceID`),
  UNIQUE KEY `InvoiceNumber` (`InvoiceNumber`),
  KEY `SaleID` (`SaleID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `invoices_ibfk_sale` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  CONSTRAINT `invoices_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `invoices_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `invoiceitems` (
  `InvoiceItemID` int NOT NULL AUTO_INCREMENT,
  `InvoiceID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Quantity` decimal(12,4) NOT NULL,
  `UnitID` int NOT NULL,
  `QuantityBase` decimal(12,4) NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`InvoiceItemID`),
  KEY `InvoiceID` (`InvoiceID`),
  KEY `ProductID` (`ProductID`),
  KEY `UnitID` (`UnitID`),
  CONSTRAINT `invoiceitems_ibfk_invoice` FOREIGN KEY (`InvoiceID`) REFERENCES `invoices` (`InvoiceID`) ON DELETE CASCADE,
  CONSTRAINT `invoiceitems_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE SET NULL,
  CONSTRAINT `invoiceitems_ibfk_unit` FOREIGN KEY (`UnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Procurement
CREATE TABLE `purchaseorders` (
  `PurchaseOrderID` int NOT NULL AUTO_INCREMENT,
  `BatchNo` varchar(50) DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `OrderDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `OrderedDate` datetime DEFAULT NULL,
  `ExpectedDeliveryDate` date DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Status` enum('Pending','Received','Cancelled','Partial') DEFAULT 'Pending',
  `CreatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`PurchaseOrderID`),
  KEY `SupplierID` (`SupplierID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `purchaseorders_ibfk_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL,
  CONSTRAINT `purchaseorders_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `purchaseorderdetails` (
  `PurchaseOrderDetailID` int NOT NULL AUTO_INCREMENT,
  `PurchaseOrderID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Quantity` decimal(12,4) NOT NULL,
  `PurchaseUnitID` int NOT NULL,
  `QuantityBase` decimal(12,4) NOT NULL,
  `UnitCost` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `ReceivedQuantity` decimal(12,4) DEFAULT '0',
  `ReceivedStatus` enum('Pending','Partially Received','Received') DEFAULT 'Pending',
  PRIMARY KEY (`PurchaseOrderDetailID`),
  KEY `PurchaseOrderID` (`PurchaseOrderID`),
  KEY `ProductID` (`ProductID`),
  KEY `PurchaseUnitID` (`PurchaseUnitID`),
  CONSTRAINT `purchaseorderdetails_ibfk_po` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  CONSTRAINT `purchaseorderdetails_ibfk_product` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `purchaseorderdetails_ibfk_unit` FOREIGN KEY (`PurchaseUnitID`) REFERENCES `units` (`UnitID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `accountspayable` (
  `APID` int NOT NULL AUTO_INCREMENT,
  `PurchaseOrderID` int DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `AmountDue` decimal(10,2) NOT NULL,
  `DueDate` date NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Overdue','Partial','Request to Pay') DEFAULT 'Pending',
  `PaidAmount` decimal(10,2) DEFAULT '0.00',
  `PaymentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`APID`),
  KEY `PurchaseOrderID` (`PurchaseOrderID`),
  KEY `SupplierID` (`SupplierID`),
  KEY `idx_ap_status` (`PaymentStatus`),
  KEY `idx_ap_due_date` (`DueDate`),
  CONSTRAINT `accountspayable_ibfk_po` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  CONSTRAINT `accountspayable_ibfk_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `accountsreceivable` (
  `ARID` int NOT NULL AUTO_INCREMENT,
  `SaleID` int DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  `AmountDue` decimal(10,2) NOT NULL,
  `DueDate` date NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Overdue','Partial') DEFAULT 'Pending',
  `PaidAmount` decimal(10,2) DEFAULT '0.00',
  `PaymentDate` datetime DEFAULT NULL,
  `DiscountFromPoints` decimal(10,2) DEFAULT '0.00',
  PRIMARY KEY (`ARID`),
  KEY `SaleID` (`SaleID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `idx_ar_status` (`PaymentStatus`),
  KEY `idx_ar_due_date` (`DueDate`),
  CONSTRAINT `accountsreceivable_ibfk_sale` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  CONSTRAINT `accountsreceivable_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `taxrecords` (
  `TaxRecordID` int NOT NULL AUTO_INCREMENT,
  `TransactionID` int DEFAULT NULL,
  `TransactionType` enum('Sale','Purchase') NOT NULL,
  `TaxAmount` decimal(10,2) NOT NULL,
  `TaxDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `TaxType` varchar(50) NOT NULL,
  `TaxRate` decimal(5,4) NOT NULL,
  `StoreID` int DEFAULT NULL,
  PRIMARY KEY (`TaxRecordID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `taxrecords_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `supporttickets` (
  `TicketID` int NOT NULL AUTO_INCREMENT,
  `CustomerID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `IssueDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Subject` varchar(200) NOT NULL,
  `Description` text NOT NULL,
  `Status` enum('Open','In Progress','Resolved','Closed') DEFAULT 'Open',
  `Priority` enum('Low','Medium','High','Critical') DEFAULT 'Medium',
  `AssignedTo` varchar(50) DEFAULT NULL,
  `Resolution` text,
  `ResolvedDate` datetime DEFAULT NULL,
  PRIMARY KEY (`TicketID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_support_tickets_status` (`Status`),
  CONSTRAINT `supporttickets_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `supporttickets_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
-- Procurement history and returns
CREATE TABLE `transaction_history_precurement` (
  `TransactionID` int NOT NULL AUTO_INCREMENT,
  `PurchaseOrderID` int DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `BatchNo` varchar(50) NOT NULL,
  `Brand` varchar(100) NOT NULL,
  `Model` varchar(100) NOT NULL,
  `Received` int NOT NULL DEFAULT '0',
  `Passed` int NOT NULL DEFAULT '0',
  `PassedCost` decimal(12,2) GENERATED ALWAYS AS ((`UnitCost` * `Passed`)) STORED,
  `Failed` int NOT NULL DEFAULT '0',
  `FailedCost` decimal(12,2) GENERATED ALWAYS AS ((`UnitCost` * `Failed`)) STORED,
  `UnitCost` decimal(10,2) NOT NULL,
  `Total` decimal(12,2) GENERATED ALWAYS AS ((`UnitCost` * `Received`)) STORED,
  `OrderedDate` date NOT NULL,
  `ArrivalDate` date NOT NULL,
  `Description` text,
  `ImageProof` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`TransactionID`),
  KEY `fk_thp_po` (`PurchaseOrderID`),
  KEY `fk_thp_supplier` (`SupplierID`),
  CONSTRAINT `fk_thp_po` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE SET NULL,
  CONSTRAINT `fk_thp_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `procurement_returns` (
  `ReturnID` int NOT NULL AUTO_INCREMENT,
  `BatchNo` varchar(50) NOT NULL,
  `Brand` varchar(100) NOT NULL,
  `Model` varchar(100) NOT NULL,
  `Qty` int NOT NULL,
  `Cost` decimal(10,2) NOT NULL,
  `Total` decimal(12,2) GENERATED ALWAYS AS ((`Cost` * `Qty`)) STORED,
  `Status` enum('returned','not yet') DEFAULT 'not yet',
  PRIMARY KEY (`ReturnID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Customer returns
CREATE TABLE `returns` (
  `ReturnID` int NOT NULL AUTO_INCREMENT,
  `SaleID` int DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `ReturnDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Reason` varchar(200) NOT NULL,
  `RefundMethod` enum('Cash','Card','Store Credit','Exchange') DEFAULT 'Cash',
  `RefundAmount` decimal(10,2) NOT NULL,
  `RestockingFee` decimal(10,2) DEFAULT '0.00',
  `NetRefund` decimal(10,2) NOT NULL,
  `Status` enum('Pending','Approved','Rejected','Completed') DEFAULT 'Pending',
  `ProcessedBy` int DEFAULT NULL,
  `Notes` text,
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReturnID`),
  KEY `SaleID` (`SaleID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `StoreID` (`StoreID`),
  KEY `ProcessedBy` (`ProcessedBy`),
  CONSTRAINT `returns_ibfk_sale` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  CONSTRAINT `returns_ibfk_user` FOREIGN KEY (`ProcessedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Payments
CREATE TABLE `customerpayments` (
  `PaymentID` int NOT NULL AUTO_INCREMENT,
  `ARID` int DEFAULT NULL,
  `SaleID` int DEFAULT NULL,
  `CustomerID` int DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Method` enum('Cash','Card','Transfer','Other') DEFAULT 'Cash',
  `PaymentDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `StoreID` int DEFAULT NULL,
  `ReceivedBy` int DEFAULT NULL,
  `Notes` text,
  PRIMARY KEY (`PaymentID`),
  KEY `ARID` (`ARID`),
  KEY `SaleID` (`SaleID`),
  KEY `CustomerID` (`CustomerID`),
  KEY `StoreID` (`StoreID`),
  KEY `ReceivedBy` (`ReceivedBy`),
  CONSTRAINT `customerpayments_ibfk_ar` FOREIGN KEY (`ARID`) REFERENCES `accountsreceivable` (`ARID`) ON DELETE SET NULL,
  CONSTRAINT `customerpayments_ibfk_sale` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE SET NULL,
  CONSTRAINT `customerpayments_ibfk_customer` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `customerpayments_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  CONSTRAINT `customerpayments_ibfk_user` FOREIGN KEY (`ReceivedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `supplierpayments` (
  `PaymentID` int NOT NULL AUTO_INCREMENT,
  `APID` int DEFAULT NULL,
  `PurchaseOrderID` int DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `Amount` decimal(10,2) NOT NULL,
  `Method` enum('Cash','Card','Transfer','Other') DEFAULT 'Cash',
  `PaymentDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `StoreID` int DEFAULT NULL,
  `PaidBy` int DEFAULT NULL,
  `Notes` text,
  PRIMARY KEY (`PaymentID`),
  KEY `APID` (`APID`),
  KEY `POID` (`PurchaseOrderID`),
  KEY `SupplierID` (`SupplierID`),
  KEY `StoreID` (`StoreID`),
  KEY `PaidBy` (`PaidBy`),
  CONSTRAINT `supplierpayments_ibfk_ap` FOREIGN KEY (`APID`) REFERENCES `accountspayable` (`APID`) ON DELETE SET NULL,
  CONSTRAINT `supplierpayments_ibfk_po` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE SET NULL,
  CONSTRAINT `supplierpayments_ibfk_supplier` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL,
  CONSTRAINT `supplierpayments_ibfk_store` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL,
  CONSTRAINT `supplierpayments_ibfk_user` FOREIGN KEY (`PaidBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Triggers
DELIMITER //
CREATE TRIGGER `tr_calculate_hours` BEFORE UPDATE ON `attendance` FOR EACH ROW BEGIN
    IF NEW.LogOutTime IS NOT NULL AND NEW.LogInTime IS NOT NULL THEN
        SET NEW.HoursWorked = TIMESTAMPDIFF(MINUTE, NEW.LogInTime, NEW.LogOutTime) / 60.0;
    END IF;
END//

CREATE TRIGGER `tr_mark_overdue_ap` BEFORE UPDATE ON `accountspayable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END//

CREATE TRIGGER `tr_mark_overdue_ar` BEFORE UPDATE ON `accountsreceivable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END//

-- HR leave automation triggers
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
END//

CREATE TRIGGER `tr_update_leave_balance` AFTER UPDATE ON `leaverequests` FOR EACH ROW BEGIN
    IF NEW.Status = 'Approved' AND OLD.Status != 'Approved' THEN
        UPDATE LeaveBalances
        SET Taken = Taken + NEW.DaysRequested,
            Remaining = Remaining - NEW.DaysRequested
        WHERE EmployeeID = NEW.EmployeeID
        AND LeaveTypeID = NEW.LeaveTypeID
        AND Year = YEAR(NEW.StartDate);
    END IF;
END//

-- Inventory valuation + Low stock alerts
CREATE TRIGGER `tr_inventory_update` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
    DECLARE v_cost_price DECIMAL(10,2);
    DECLARE v_value_change DECIMAL(10,2);
    SELECT CostPrice INTO v_cost_price FROM products WHERE ProductID = NEW.ProductID;
    SET v_value_change = (NEW.Quantity - OLD.Quantity) * v_cost_price;
    IF v_value_change <> 0 THEN
        INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID, CreatedBy)
        VALUES (
            NOW(), 'Asset', 'Inventory', CONCAT('Inventory adjustment for Product ID: ', NEW.ProductID),
            CASE WHEN v_value_change > 0 THEN v_value_change ELSE 0 END,
            CASE WHEN v_value_change < 0 THEN ABS(v_value_change) ELSE 0 END,
            NEW.ProductID, 'Adjustment', NEW.StoreID, USER()
        );
    END IF;
END//

CREATE TRIGGER `tr_low_stock_alert` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
    DECLARE v_min_stock INT;
    SELECT MinStockLevel INTO v_min_stock FROM products WHERE ProductID = NEW.ProductID;
    IF NEW.Quantity <= v_min_stock AND OLD.Quantity > v_min_stock THEN
        INSERT INTO supporttickets (CustomerID, StoreID, Subject, Description, Status, Priority, AssignedTo)
        VALUES (NULL, NEW.StoreID, 'Low Stock Alert', CONCAT('Product ID ', NEW.ProductID, ' is low. Qty: ', NEW.Quantity, ', Min: ', v_min_stock), 'Open', 'Medium', 'Inventory Manager');
    END IF;
END//
DELIMITER ;

-- Procedures (enhanced automation, UoM-aware)
DELIMITER //
CREATE PROCEDURE `GeneratePayroll`(
    IN p_employee_id INT,
    IN p_start_date DATE,
    IN p_end_date DATE,
    IN p_deductions DECIMAL(10,2)
)
BEGIN
    DECLARE v_hours_worked DECIMAL(5,2);
    DECLARE v_hourly_rate DECIMAL(10,2);
    DECLARE v_gross_pay DECIMAL(10,2);
    DECLARE v_leave_pay DECIMAL(10,2) DEFAULT 0.00;
    DECLARE v_net_pay DECIMAL(10,2);

    SELECT SUM(HoursWorked) INTO v_hours_worked
    FROM attendance
    WHERE EmployeeID = p_employee_id
      AND AttendanceDate BETWEEN p_start_date AND p_end_date;

    SELECT HourlyRate INTO v_hourly_rate FROM employees WHERE EmployeeID = p_employee_id;

    SELECT SUM(lr.DaysRequested * 8 * e.HourlyRate) INTO v_leave_pay
    FROM leaverequests lr
    JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
    JOIN employees e ON lr.EmployeeID = e.EmployeeID
    WHERE lr.EmployeeID = p_employee_id AND lr.Status = 'Approved' AND lt.IsPaid = 'Yes'
      AND lr.StartDate BETWEEN p_start_date AND p_end_date;

    SET v_gross_pay = (COALESCE(v_hours_worked,0) * COALESCE(v_hourly_rate,0)) + COALESCE(v_leave_pay, 0);
    SET v_net_pay = v_gross_pay - p_deductions;

    INSERT INTO payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, HourlyRate, GrossPay, LeavePay, Deductions, NetPay)
    VALUES (p_employee_id, p_start_date, p_end_date, COALESCE(v_hours_worked,0), COALESCE(v_hourly_rate,0), v_gross_pay, v_leave_pay, p_deductions, v_net_pay);
END//

CREATE PROCEDURE `ProcessSale`(
    IN p_customer_id INT,
    IN p_store_id INT,
    IN p_products JSON,      -- array of {productID, quantity, unitID, unitPrice}
    IN p_payment_method VARCHAR(20),
    IN p_discount_amount DECIMAL(10,2),
    IN p_points_used INT,
    IN p_payment_status ENUM('Paid','Credit','Refunded','Partial'),
    IN p_amount_paid DECIMAL(10,2),
    OUT p_sale_id INT
)
BEGIN
    DECLARE v_total_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_tax_amount DECIMAL(10,2) DEFAULT 0;
    DECLARE v_product_id INT;
    DECLARE v_quantity DECIMAL(12,4);
    DECLARE v_unit_id INT;
    DECLARE v_unit_price DECIMAL(10,2);
    DECLARE v_subtotal DECIMAL(10,2);
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_count INT;
    DECLARE v_conv DECIMAL(10,4);
    DECLARE v_points_discount DECIMAL(10,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    START TRANSACTION;

    SET v_points_discount = p_points_used * 1.00;
    SET v_count = JSON_LENGTH(p_products);

    INSERT INTO sales (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus, PointsUsed)
    VALUES (p_customer_id, p_store_id, 0, 0, p_discount_amount + v_points_discount, p_payment_method, p_payment_status, p_points_used);

    SET p_sale_id = LAST_INSERT_ID();

    WHILE v_i < v_count DO
        SET v_product_id = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].productID')));
        SET v_quantity   = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].quantity')));
        SET v_unit_id    = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].unitID')));
        SET v_unit_price = JSON_UNQUOTE(JSON_EXTRACT(p_products, CONCAT('$[', v_i, '].unitPrice')));
        IF v_unit_id IS NULL THEN SELECT COALESCE(DefaultSalesUnitID, BaseUnitID) INTO v_unit_id FROM products WHERE ProductID = v_product_id; END IF;
        SELECT pu.ConversionToBase INTO v_conv FROM product_units pu WHERE pu.ProductID = v_product_id AND pu.UnitID = v_unit_id;
        IF v_conv IS NULL THEN SET v_conv = 1.0; END IF;
        SET v_subtotal = v_quantity * v_unit_price;
        SET v_total_amount = v_total_amount + v_subtotal;
        INSERT INTO saledetails (SaleID, ProductID, Quantity, SalesUnitID, QuantityBase, UnitPrice, Subtotal)
        VALUES (p_sale_id, v_product_id, v_quantity, v_unit_id, v_quantity * v_conv, v_unit_price, v_subtotal);
        UPDATE inventory SET Quantity = Quantity - (v_quantity * v_conv) WHERE ProductID = v_product_id AND StoreID = p_store_id;
        INSERT INTO stockmovements (ProductID, StoreID, MovementType, Quantity, UnitID, QuantityBase, ReferenceID, ReferenceType, CreatedBy)
        VALUES (v_product_id, p_store_id, 'OUT', v_quantity, v_unit_id, v_quantity * v_conv, p_sale_id, 'Sale', USER());
        SET v_i = v_i + 1;
    END WHILE;

    SET v_tax_amount = (v_total_amount - (p_discount_amount + v_points_discount)) * 0.10;
    UPDATE sales SET TotalAmount = v_total_amount, TaxAmount = v_tax_amount, PointsEarned = FLOOR((v_total_amount + v_tax_amount - (p_discount_amount + v_points_discount)) / 10)
    WHERE SaleID = p_sale_id;

    IF p_customer_id IS NOT NULL THEN
        UPDATE customers SET LoyaltyPoints = LoyaltyPoints - p_points_used + FLOOR((v_total_amount + v_tax_amount - (p_discount_amount + v_points_discount)) / 10)
        WHERE CustomerID = p_customer_id;
    END IF;

    INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Revenue', 'Sales Revenue', 'Product Sales', v_total_amount, p_sale_id, 'Sale', p_store_id);
    IF v_tax_amount > 0 THEN
        INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Credit, ReferenceID, ReferenceType, StoreID)
        VALUES (NOW(), 'Liability', 'Sales Tax Payable', 'Sales Tax', v_tax_amount, p_sale_id, 'Sale', p_store_id);
        INSERT INTO taxrecords (TransactionID, TransactionType, TaxAmount, TaxType, TaxRate, StoreID)
        VALUES (p_sale_id, 'Sale', v_tax_amount, 'Sales Tax', 0.10, p_store_id);
    END IF;

    -- AR handling
    IF p_payment_status IN ('Credit','Partial') THEN
        INSERT INTO accountsreceivable (SaleID, CustomerID, AmountDue, DueDate, PaymentStatus, PaidAmount, DiscountFromPoints)
        VALUES (p_sale_id, p_customer_id, v_total_amount + v_tax_amount - (p_discount_amount + v_points_discount), DATE_ADD(CURDATE(), INTERVAL 30 DAY), CASE WHEN p_payment_status='Credit' THEN 'Pending' ELSE 'Partial' END, COALESCE(p_amount_paid,0), v_points_discount);
    END IF;

    COMMIT;
END//

CREATE PROCEDURE `ReceivePurchaseOrder`(
    IN p_purchase_order_id INT,
    IN p_received_products JSON -- array of {productID, receivedQuantity, unitID}
)
BEGIN
    DECLARE v_product_id INT;
    DECLARE v_received_qty DECIMAL(12,4);
    DECLARE v_unit_id INT;
    DECLARE v_i INT DEFAULT 0;
    DECLARE v_count INT;
    DECLARE v_store_id INT;
    DECLARE v_supplier_id INT;
    DECLARE v_conv DECIMAL(10,4);
    DECLARE v_total DECIMAL(12,2);

    DECLARE EXIT HANDLER FOR SQLEXCEPTION BEGIN ROLLBACK; RESIGNAL; END;
    START TRANSACTION;

    SELECT StoreID, SupplierID INTO v_store_id, v_supplier_id FROM purchaseorders WHERE PurchaseOrderID = p_purchase_order_id;
    SET v_count = JSON_LENGTH(p_received_products);

    WHILE v_i < v_count DO
        SET v_product_id   = JSON_UNQUOTE(JSON_EXTRACT(p_received_products, CONCAT('$[', v_i, '].productID')));
        SET v_received_qty = JSON_UNQUOTE(JSON_EXTRACT(p_received_products, CONCAT('$[', v_i, '].receivedQuantity')));
        SET v_unit_id      = JSON_UNQUOTE(JSON_EXTRACT(p_received_products, CONCAT('$[', v_i, '].unitID')));
        IF v_unit_id IS NULL THEN SELECT COALESCE(DefaultPurchaseUnitID, BaseUnitID) INTO v_unit_id FROM products WHERE ProductID = v_product_id; END IF;
        SELECT pu.ConversionToBase INTO v_conv FROM product_units pu WHERE pu.ProductID = v_product_id AND pu.UnitID = v_unit_id;
        IF v_conv IS NULL THEN SET v_conv = 1.0; END IF;

        UPDATE purchaseorderdetails SET ReceivedQuantity = ReceivedQuantity + v_received_qty WHERE PurchaseOrderID = p_purchase_order_id AND ProductID = v_product_id;
        INSERT INTO inventory (ProductID, StoreID, Quantity) VALUES (v_product_id, v_store_id, v_received_qty * v_conv)
            ON DUPLICATE KEY UPDATE Quantity = Quantity + (v_received_qty * v_conv);
        INSERT INTO stockmovements (ProductID, StoreID, MovementType, Quantity, UnitID, QuantityBase, ReferenceID, ReferenceType, CreatedBy)
        VALUES (v_product_id, v_store_id, 'IN', v_received_qty, v_unit_id, v_received_qty * v_conv, p_purchase_order_id, 'Purchase', USER());
        SET v_i = v_i + 1;
    END WHILE;

    SELECT SUM(Subtotal) INTO v_total FROM purchaseorderdetails WHERE PurchaseOrderID = p_purchase_order_id;
    UPDATE purchaseorders SET TotalAmount = v_total, Status = 'Received' WHERE PurchaseOrderID = p_purchase_order_id;

    IF EXISTS (SELECT 1 FROM accountspayable WHERE PurchaseOrderID = p_purchase_order_id) THEN
        UPDATE accountspayable SET AmountDue = v_total WHERE PurchaseOrderID = p_purchase_order_id;
    ELSE
        INSERT INTO accountspayable (PurchaseOrderID, SupplierID, AmountDue, DueDate, PaymentStatus, PaidAmount)
        VALUES (p_purchase_order_id, v_supplier_id, v_total, DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'Pending', 0);
    END IF;

    COMMIT;
END//

CREATE PROCEDURE `RecordCustomerPayment`(
    IN p_sale_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_method ENUM('Cash','Card','Transfer','Other'),
    IN p_store_id INT,
    IN p_received_by INT
)
BEGIN
    DECLARE v_arid INT; DECLARE v_customer_id INT;
    START TRANSACTION;
    SELECT ARID, CustomerID INTO v_arid, v_customer_id FROM accountsreceivable WHERE SaleID = p_sale_id;
    INSERT INTO customerpayments (ARID, SaleID, CustomerID, Amount, Method, StoreID, ReceivedBy)
    VALUES (v_arid, p_sale_id, v_customer_id, p_amount, p_method, p_store_id, p_received_by);
    UPDATE accountsreceivable SET PaidAmount = PaidAmount + p_amount,
        PaymentStatus = CASE WHEN (PaidAmount + p_amount) >= AmountDue THEN 'Paid' ELSE 'Partial' END
    WHERE ARID = v_arid;
    INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Asset', 'Cash/Bank', 'Customer payment', p_amount, 0, v_arid, 'Payment', p_store_id);
    INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Asset', 'Accounts Receivable', 'Reduce AR', 0, p_amount, v_arid, 'Payment', p_store_id);
    COMMIT;
END//

CREATE PROCEDURE `RecordSupplierPayment`(
    IN p_purchase_order_id INT,
    IN p_amount DECIMAL(10,2),
    IN p_method ENUM('Cash','Card','Transfer','Other'),
    IN p_store_id INT,
    IN p_paid_by INT
)
BEGIN
    DECLARE v_apid INT; DECLARE v_supplier_id INT;
    START TRANSACTION;
    SELECT APID, SupplierID INTO v_apid, v_supplier_id FROM accountspayable WHERE PurchaseOrderID = p_purchase_order_id;
    INSERT INTO supplierpayments (APID, PurchaseOrderID, SupplierID, Amount, Method, StoreID, PaidBy)
    VALUES (v_apid, p_purchase_order_id, v_supplier_id, p_amount, p_method, p_store_id, p_paid_by);
    UPDATE accountspayable SET PaidAmount = PaidAmount + p_amount,
        PaymentStatus = CASE WHEN (PaidAmount + p_amount) >= AmountDue THEN 'Paid' ELSE 'Partial' END
    WHERE APID = v_apid;
    INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Liability', 'Accounts Payable', 'Reduce AP', p_amount, 0, v_apid, 'Payment', p_store_id);
    INSERT INTO generalledger (TransactionDate, AccountType, AccountName, Description, Debit, Credit, ReferenceID, ReferenceType, StoreID)
    VALUES (NOW(), 'Asset', 'Cash/Bank', 'Cash out to supplier', 0, p_amount, v_apid, 'Payment', p_store_id);
    COMMIT;
END//
DELIMITER ;

-- Views (merged)
DROP VIEW IF EXISTS `v_financial_summary`;
CREATE ALGORITHM=UNDEFINED VIEW `v_financial_summary` AS 
select cast(`gl`.`TransactionDate` as date) AS `TransactionDate`,`s`.`StoreName` AS `StoreName`,`gl`.`AccountType` AS `AccountType`,
       sum(`gl`.`Debit`) AS `TotalDebits`,sum(`gl`.`Credit`) AS `TotalCredits`,sum((`gl`.`Credit` - `gl`.`Debit`)) AS `NetAmount`
from (`generalledger` `gl` join `stores` `s` on((`gl`.`StoreID` = `s`.`StoreID`)))
GROUP BY cast(`gl`.`TransactionDate` as date),`gl`.`StoreID`,`gl`.`AccountType`;

DROP VIEW IF EXISTS `v_inventory_summary`;
CREATE ALGORITHM=UNDEFINED VIEW `v_inventory_summary` AS 
select p.ProductID, p.SKU, p.Brand, p.Model, p.Size, p.Color, s.StoreName, i.Quantity as Quantity,
       p.MinStockLevel, p.MaxStockLevel,
       (case when (i.Quantity <= p.MinStockLevel) then 'Low Stock' when (i.Quantity >= p.MaxStockLevel) then 'Overstock' else 'Normal' end) as StockStatus,
       (i.Quantity * p.CostPrice) as InventoryValue
from products p
join inventory i on p.ProductID = i.ProductID
join stores s on i.StoreID = s.StoreID
where p.Status = 'Active';

DROP VIEW IF EXISTS `v_outstanding_receivables`;
CREATE ALGORITHM=UNDEFINED VIEW `v_outstanding_receivables` AS 
select ar.ARID, ar.SaleID, concat(c.FirstName,' ',coalesce(c.LastName,'')) as CustomerName, c.Email, c.Phone,
       ar.AmountDue, ar.PaidAmount, (ar.AmountDue - ar.PaidAmount) as Balance, ar.DueDate,
       (case when (ar.DueDate < curdate()) then 'Overdue' when (ar.DueDate = curdate()) then 'Due Today' else 'Pending' end) as Status,
       (to_days(curdate()) - to_days(ar.DueDate)) as DaysOverdue
from accountsreceivable ar
join customers c on ar.CustomerID = c.CustomerID
where ar.PaymentStatus <> 'Paid';

DROP VIEW IF EXISTS `v_sales_summary`;
CREATE ALGORITHM=UNDEFINED VIEW `v_sales_summary` AS 
select s.SaleID, s.SaleDate, concat(c.FirstName,' ',coalesce(c.LastName,'')) as CustomerName, st.StoreName,
       s.TotalAmount, s.TaxAmount, s.DiscountAmount, s.PaymentStatus, s.PaymentMethod, count(sd.SaleDetailID) as ItemCount
from sales s
left join customers c on s.CustomerID = c.CustomerID
join stores st on s.StoreID = st.StoreID
join saledetails sd on s.SaleID = sd.SaleID
group by s.SaleID;

DROP VIEW IF EXISTS `v_purchaseorderdetails`;
CREATE ALGORITHM=UNDEFINED VIEW `v_purchaseorderdetails` AS 
select po.BatchNo as `Batch#`, po.PurchaseOrderID, p.Brand, p.Model,
       pod.Quantity as Qty, pod.UnitCost as Cost, p.SellingPrice as `Price Per Item`, pod.Subtotal as Total,
       s.SupplierName, s.Phone as `Supplier Contact Number`, s.Email as `Supplier Email`,
       po.OrderDate as `Order Date`, po.OrderedDate as `Ordered Date`, po.ExpectedDeliveryDate as `Target Arrival Date`, po.Status
from purchaseorders po
join purchaseorderdetails pod on po.PurchaseOrderID = pod.PurchaseOrderID
join products p on pod.ProductID = p.ProductID
left join suppliers s on po.SupplierID = s.SupplierID;

SET FOREIGN_KEY_CHECKS=1;
