CREATE DATABASE  IF NOT EXISTS `shoeretailerp` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */;
USE `shoeretailerp`;
-- MySQL dump 10.13  Distrib 8.0.43, for Win64 (x86_64)
--
-- Host: localhost    Database: shoeretailerp
-- ------------------------------------------------------
-- Server version	8.0.44

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!50503 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `accountspayable`
--

DROP TABLE IF EXISTS `accountspayable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `accountspayable` (
  `APID` int NOT NULL AUTO_INCREMENT,
  `PurchaseOrderID` int DEFAULT NULL,
  `SupplierID` int DEFAULT NULL,
  `AmountDue` decimal(10,2) NOT NULL,
  `DueDate` date NOT NULL,
  `PaymentStatus` enum('Pending','Paid','Overdue','Partial') DEFAULT 'Pending',
  `PaidAmount` decimal(10,2) DEFAULT '0.00',
  `PaymentDate` datetime DEFAULT NULL,
  PRIMARY KEY (`APID`),
  KEY `PurchaseOrderID` (`PurchaseOrderID`),
  KEY `SupplierID` (`SupplierID`),
  KEY `idx_ap_status` (`PaymentStatus`),
  KEY `idx_ap_due_date` (`DueDate`),
  CONSTRAINT `accountspayable_ibfk_1` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  CONSTRAINT `accountspayable_ibfk_2` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accountspayable`
--

LOCK TABLES `accountspayable` WRITE;
/*!40000 ALTER TABLE `accountspayable` DISABLE KEYS */;
/*!40000 ALTER TABLE `accountspayable` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_mark_overdue_ap` BEFORE UPDATE ON `accountspayable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `accountsreceivable`
--

DROP TABLE IF EXISTS `accountsreceivable`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `accountsreceivable_ibfk_1` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  CONSTRAINT `accountsreceivable_ibfk_2` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `accountsreceivable`
--

LOCK TABLES `accountsreceivable` WRITE;
/*!40000 ALTER TABLE `accountsreceivable` DISABLE KEYS */;
/*!40000 ALTER TABLE `accountsreceivable` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_mark_overdue_ar` BEFORE UPDATE ON `accountsreceivable` FOR EACH ROW BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `attendance`
--

DROP TABLE IF EXISTS `attendance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `attendance_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `attendance`
--

LOCK TABLES `attendance` WRITE;
/*!40000 ALTER TABLE `attendance` DISABLE KEYS */;
/*!40000 ALTER TABLE `attendance` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_calculate_hours` BEFORE UPDATE ON `attendance` FOR EACH ROW BEGIN
    IF NEW.LogOutTime IS NOT NULL AND NEW.LogInTime IS NOT NULL THEN
        SET NEW.HoursWorked = TIMESTAMPDIFF(MINUTE, NEW.LogInTime, NEW.LogOutTime) / 60.0;
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `budgets`
--

DROP TABLE IF EXISTS `budgets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE,
  CONSTRAINT `budgets_ibfk_2` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `budgets`
--

LOCK TABLES `budgets` WRITE;
/*!40000 ALTER TABLE `budgets` DISABLE KEYS */;
/*!40000 ALTER TABLE `budgets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `customers`
--

DROP TABLE IF EXISTS `customers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `customers`
--

LOCK TABLES `customers` WRITE;
/*!40000 ALTER TABLE `customers` DISABLE KEYS */;
INSERT INTO `customers` VALUES (1,'MEM-001','Alice','Johnson','alice@email.com','555-2001','111 Customer St',150,'Active','2025-10-22 08:41:50'),(2,'MEM-002','Bob','Smith','bob@email.com','555-2002','222 Buyer Ave',75,'Active','2025-10-22 08:41:50'),(3,'MEM-003','Carol','Davis','carol@email.com','555-2003','333 Shopper Rd',200,'Active','2025-10-22 08:41:50');
/*!40000 ALTER TABLE `customers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `employees`
--

DROP TABLE IF EXISTS `employees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `employees` (
  `EmployeeID` int NOT NULL AUTO_INCREMENT,
  `FirstName` varchar(50) NOT NULL,
  `LastName` varchar(50) NOT NULL,
  `Email` varchar(100) NOT NULL,
  `Phone` varchar(20) DEFAULT NULL,
  `HireDate` date NOT NULL,
  `Salary` decimal(10,2) DEFAULT NULL,
  `HourlyRate` decimal(10,2) NOT NULL,
  `BankAccountNumber` varchar(50) DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `Status` enum('Active','Inactive','On Leave','Terminated') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  `UpdatedAt` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`EmployeeID`),
  UNIQUE KEY `Email` (`Email`),
  UNIQUE KEY `Phone` (`Phone`),
  UNIQUE KEY `BankAccountNumber` (`BankAccountNumber`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `employees_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `employees`
--

LOCK TABLES `employees` WRITE;
/*!40000 ALTER TABLE `employees` DISABLE KEYS */;
/*!40000 ALTER TABLE `employees` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `expenses`
--

DROP TABLE IF EXISTS `expenses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `expenses_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `expenses`
--

LOCK TABLES `expenses` WRITE;
/*!40000 ALTER TABLE `expenses` DISABLE KEYS */;
/*!40000 ALTER TABLE `expenses` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `generalledger`
--

DROP TABLE IF EXISTS `generalledger`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `generalledger_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `generalledger`
--

LOCK TABLES `generalledger` WRITE;
/*!40000 ALTER TABLE `generalledger` DISABLE KEYS */;
/*!40000 ALTER TABLE `generalledger` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inventory`
--

DROP TABLE IF EXISTS `inventory`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `inventory` (
  `InventoryID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `Quantity` int NOT NULL DEFAULT '0',
  `LastUpdated` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`InventoryID`),
  UNIQUE KEY `unique_product_store` (`ProductID`,`StoreID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_inventory_product_store` (`ProductID`,`StoreID`),
  CONSTRAINT `inventory_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `inventory_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inventory`
--

LOCK TABLES `inventory` WRITE;
/*!40000 ALTER TABLE `inventory` DISABLE KEYS */;
/*!40000 ALTER TABLE `inventory` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_inventory_update` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_low_stock_alert` AFTER UPDATE ON `inventory` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `leavebalances`
--

DROP TABLE IF EXISTS `leavebalances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leavebalances`
--

LOCK TABLES `leavebalances` WRITE;
/*!40000 ALTER TABLE `leavebalances` DISABLE KEYS */;
/*!40000 ALTER TABLE `leavebalances` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `leaverequests`
--

DROP TABLE IF EXISTS `leaverequests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `leaverequests_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_2` FOREIGN KEY (`LeaveTypeID`) REFERENCES `leavetypes` (`LeaveTypeID`) ON DELETE CASCADE,
  CONSTRAINT `leaverequests_ibfk_3` FOREIGN KEY (`ApprovedBy`) REFERENCES `users` (`UserID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leaverequests`
--

LOCK TABLES `leaverequests` WRITE;
/*!40000 ALTER TABLE `leaverequests` DISABLE KEYS */;
/*!40000 ALTER TABLE `leaverequests` ENABLE KEYS */;
UNLOCK TABLES;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_leave_attendance` AFTER INSERT ON `leaverequests` FOR EACH ROW BEGIN
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
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `tr_update_leave_balance` AFTER UPDATE ON `leaverequests` FOR EACH ROW BEGIN
    IF NEW.Status = 'Approved' AND OLD.Status != 'Approved' THEN
        UPDATE LeaveBalances
        SET Taken = Taken + NEW.DaysRequested,
            Remaining = Remaining - NEW.DaysRequested
        WHERE EmployeeID = NEW.EmployeeID
        AND LeaveTypeID = NEW.LeaveTypeID
        AND Year = YEAR(NEW.StartDate);
    END IF;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `leavetypes`
--

DROP TABLE IF EXISTS `leavetypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `leavetypes` (
  `LeaveTypeID` int NOT NULL AUTO_INCREMENT,
  `LeaveTypeName` varchar(50) NOT NULL,
  `Description` text,
  `IsPaid` enum('Yes','No') DEFAULT 'Yes',
  `DefaultEntitlement` int DEFAULT '0',
  PRIMARY KEY (`LeaveTypeID`),
  UNIQUE KEY `LeaveTypeName` (`LeaveTypeName`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `leavetypes`
--

LOCK TABLES `leavetypes` WRITE;
/*!40000 ALTER TABLE `leavetypes` DISABLE KEYS */;
INSERT INTO `leavetypes` VALUES (1,'Sick Leave','Paid leave for medical reasons','Yes',7),(2,'Birthday Leave','One day off for employee’s birthday','Yes',1);
/*!40000 ALTER TABLE `leavetypes` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `payroll`
--

DROP TABLE IF EXISTS `payroll`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `payroll_ibfk_1` FOREIGN KEY (`EmployeeID`) REFERENCES `employees` (`EmployeeID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `payroll`
--

LOCK TABLES `payroll` WRITE;
/*!40000 ALTER TABLE `payroll` DISABLE KEYS */;
/*!40000 ALTER TABLE `payroll` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `products`
--

DROP TABLE IF EXISTS `products`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  PRIMARY KEY (`ProductID`),
  UNIQUE KEY `SKU` (`SKU`),
  KEY `SupplierID` (`SupplierID`),
  KEY `idx_products_sku` (`SKU`),
  KEY `idx_products_brand_model` (`Brand`,`Model`),
  CONSTRAINT `products_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `products`
--

LOCK TABLES `products` WRITE;
/*!40000 ALTER TABLE `products` DISABLE KEYS */;
INSERT INTO `products` VALUES (1,'NK-AM-001-9.5-BLK','Nike','Air Max 90',9.5,'Black',65.00,120.00,5,50,1,'Active','2025-10-22 08:41:50'),(2,'NK-AM-001-10-WHT','Nike','Air Max 90',10.0,'White',65.00,120.00,5,50,1,'Active','2025-10-22 08:41:50'),(3,'AD-UB-001-9-BLU','Adidas','Ultraboost 22',9.0,'Blue',75.00,140.00,3,30,2,'Active','2025-10-22 08:41:50'),(4,'AD-UB-001-10-GRY','Adidas','Ultraboost 22',10.0,'Grey',75.00,140.00,3,30,2,'Active','2025-10-22 08:41:50'),(5,'LC-CS-001-8.5-BRN','Local Brand','Casual Sneaker',8.5,'Brown',35.00,70.00,10,100,3,'Active','2025-10-22 08:41:50');
/*!40000 ALTER TABLE `products` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchaseorderdetails`
--

DROP TABLE IF EXISTS `purchaseorderdetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseorderdetails` (
  `PurchaseOrderDetailID` int NOT NULL AUTO_INCREMENT,
  `PurchaseOrderID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Quantity` int NOT NULL,
  `UnitCost` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  `ReceivedQuantity` int DEFAULT '0',
  PRIMARY KEY (`PurchaseOrderDetailID`),
  KEY `PurchaseOrderID` (`PurchaseOrderID`),
  KEY `ProductID` (`ProductID`),
  CONSTRAINT `purchaseorderdetails_ibfk_1` FOREIGN KEY (`PurchaseOrderID`) REFERENCES `purchaseorders` (`PurchaseOrderID`) ON DELETE CASCADE,
  CONSTRAINT `purchaseorderdetails_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchaseorderdetails`
--

LOCK TABLES `purchaseorderdetails` WRITE;
/*!40000 ALTER TABLE `purchaseorderdetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchaseorderdetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `purchaseorders`
--

DROP TABLE IF EXISTS `purchaseorders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `purchaseorders` (
  `PurchaseOrderID` int NOT NULL AUTO_INCREMENT,
  `SupplierID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `OrderDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `ExpectedDeliveryDate` date DEFAULT NULL,
  `TotalAmount` decimal(10,2) DEFAULT NULL,
  `Status` enum('Pending','Received','Cancelled','Partial') DEFAULT 'Pending',
  `CreatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`PurchaseOrderID`),
  KEY `SupplierID` (`SupplierID`),
  KEY `StoreID` (`StoreID`),
  CONSTRAINT `purchaseorders_ibfk_1` FOREIGN KEY (`SupplierID`) REFERENCES `suppliers` (`SupplierID`) ON DELETE SET NULL,
  CONSTRAINT `purchaseorders_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `purchaseorders`
--

LOCK TABLES `purchaseorders` WRITE;
/*!40000 ALTER TABLE `purchaseorders` DISABLE KEYS */;
/*!40000 ALTER TABLE `purchaseorders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `roles`
--

DROP TABLE IF EXISTS `roles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `roles`
--

LOCK TABLES `roles` WRITE;
/*!40000 ALTER TABLE `roles` DISABLE KEYS */;
/*!40000 ALTER TABLE `roles` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `saledetails`
--

DROP TABLE IF EXISTS `saledetails`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `saledetails` (
  `SaleDetailID` int NOT NULL AUTO_INCREMENT,
  `SaleID` int DEFAULT NULL,
  `ProductID` int DEFAULT NULL,
  `Quantity` int NOT NULL,
  `UnitPrice` decimal(10,2) NOT NULL,
  `Subtotal` decimal(10,2) NOT NULL,
  PRIMARY KEY (`SaleDetailID`),
  KEY `SaleID` (`SaleID`),
  KEY `ProductID` (`ProductID`),
  CONSTRAINT `saledetails_ibfk_1` FOREIGN KEY (`SaleID`) REFERENCES `sales` (`SaleID`) ON DELETE CASCADE,
  CONSTRAINT `saledetails_ibfk_2` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `saledetails`
--

LOCK TABLES `saledetails` WRITE;
/*!40000 ALTER TABLE `saledetails` DISABLE KEYS */;
/*!40000 ALTER TABLE `saledetails` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `sales`
--

DROP TABLE IF EXISTS `sales`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `sales_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `sales_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `sales`
--

LOCK TABLES `sales` WRITE;
/*!40000 ALTER TABLE `sales` DISABLE KEYS */;
/*!40000 ALTER TABLE `sales` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stockmovements`
--

DROP TABLE IF EXISTS `stockmovements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stockmovements` (
  `MovementID` int NOT NULL AUTO_INCREMENT,
  `ProductID` int DEFAULT NULL,
  `StoreID` int DEFAULT NULL,
  `MovementType` enum('IN','OUT','TRANSFER','ADJUSTMENT') NOT NULL,
  `Quantity` int NOT NULL,
  `ReferenceID` int DEFAULT NULL,
  `ReferenceType` enum('Sale','Purchase','Transfer','Adjustment','Return') NOT NULL,
  `MovementDate` datetime DEFAULT CURRENT_TIMESTAMP,
  `Notes` text,
  `CreatedBy` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`MovementID`),
  KEY `ProductID` (`ProductID`),
  KEY `StoreID` (`StoreID`),
  KEY `idx_stock_movements_date` (`MovementDate`),
  CONSTRAINT `stockmovements_ibfk_1` FOREIGN KEY (`ProductID`) REFERENCES `products` (`ProductID`) ON DELETE CASCADE,
  CONSTRAINT `stockmovements_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stockmovements`
--

LOCK TABLES `stockmovements` WRITE;
/*!40000 ALTER TABLE `stockmovements` DISABLE KEYS */;
/*!40000 ALTER TABLE `stockmovements` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `stores`
--

DROP TABLE IF EXISTS `stores`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
CREATE TABLE `stores` (
  `StoreID` int NOT NULL AUTO_INCREMENT,
  `StoreName` varchar(100) NOT NULL,
  `Location` text,
  `ManagerName` varchar(50) DEFAULT NULL,
  `ContactPhone` varchar(20) DEFAULT NULL,
  `Status` enum('Active','Inactive') DEFAULT 'Active',
  `CreatedAt` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`StoreID`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `stores`
--

LOCK TABLES `stores` WRITE;
/*!40000 ALTER TABLE `stores` DISABLE KEYS */;
INSERT INTO `stores` VALUES (1,'Maria Collections Bagong Silang','Bagong Silang, Caloocan City','Maria','09XX-XXX-XXXX','Active','2025-10-28 03:10:22');
/*!40000 ALTER TABLE `stores` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `suppliers`
--

DROP TABLE IF EXISTS `suppliers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `suppliers`
--

LOCK TABLES `suppliers` WRITE;
/*!40000 ALTER TABLE `suppliers` DISABLE KEYS */;
INSERT INTO `suppliers` VALUES (1,'Nike Distribution','Sarah Wilson','sarah@nikedist.com','555-1001','100 Nike Way, Portland, OR','Net 30','Active','2025-10-22 08:41:50','2025-10-22 08:41:50'),(2,'Adidas Supply Co','Tom Brown','tom@adidas-supply.com','555-1002','200 Adidas St, Germany','Net 45','Active','2025-10-22 08:41:50','2025-10-22 08:41:50'),(3,'Local Shoe Warehouse','Lisa Garcia','lisa@localshoes.com','555-1003','300 Local Ave, Local City','Net 15','Active','2025-10-22 08:41:50','2025-10-22 08:41:50');
/*!40000 ALTER TABLE `suppliers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `supporttickets`
--

DROP TABLE IF EXISTS `supporttickets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `supporttickets_ibfk_1` FOREIGN KEY (`CustomerID`) REFERENCES `customers` (`CustomerID`) ON DELETE SET NULL,
  CONSTRAINT `supporttickets_ibfk_2` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `supporttickets`
--

LOCK TABLES `supporttickets` WRITE;
/*!40000 ALTER TABLE `supporttickets` DISABLE KEYS */;
/*!40000 ALTER TABLE `supporttickets` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `taxrecords`
--

DROP TABLE IF EXISTS `taxrecords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `taxrecords_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `taxrecords`
--

LOCK TABLES `taxrecords` WRITE;
/*!40000 ALTER TABLE `taxrecords` DISABLE KEYS */;
/*!40000 ALTER TABLE `taxrecords` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!50503 SET character_set_client = utf8mb4 */;
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
  CONSTRAINT `users_ibfk_1` FOREIGN KEY (`StoreID`) REFERENCES `stores` (`StoreID`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `users`
--

LOCK TABLES `users` WRITE;
/*!40000 ALTER TABLE `users` DISABLE KEYS */;
INSERT INTO `users` VALUES (1,'admin','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Admin','User','admin@shoestore.com','Admin',NULL,'Active','2025-10-22 08:41:50'),(2,'manager1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','John','Smith','john@shoestore.com','Manager',NULL,'Active','2025-10-22 08:41:50'),(3,'cashier1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi','Mary','Johnson','mary@shoestore.com','Cashier',NULL,'Active','2025-10-22 08:41:50'),(4,'inventory','$2y$12$P49QiiXYhWameOP/nIC/VecOdajnrBfRRxZBrxg4/qXGKnsPv./06','Val Javez','Lamsen','vjlamsenlamsen28@gmail.com','Inventory',1,'Active','2025-10-28 03:48:35'),(5,'inventory2','$2y$12$mtZ.Xt.4wJhe9xwW3JstCuaim1kvBHC5OzgIpg77aIc4tHTFQBi8a','Gene','Tabibis','gegesgene@gmail.com','Inventory',NULL,'Active','2025-10-28 03:54:49'),(6,'humores','$2y$12$D9iZI/f3T6rd/JUqjCiat.Dwq73tIErZAlArV2UotDncNaUg8R/TG','Daril','Pidil','darilpidil@gmail.com','HR',NULL,'Active','2025-10-28 04:08:11');
/*!40000 ALTER TABLE `users` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Temporary view structure for view `v_financial_summary`
--

DROP TABLE IF EXISTS `v_financial_summary`;
/*!50001 DROP VIEW IF EXISTS `v_financial_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_financial_summary` AS SELECT 
 1 AS `TransactionDate`,
 1 AS `StoreName`,
 1 AS `AccountType`,
 1 AS `TotalDebits`,
 1 AS `TotalCredits`,
 1 AS `NetAmount`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_inventory_summary`
--

DROP TABLE IF EXISTS `v_inventory_summary`;
/*!50001 DROP VIEW IF EXISTS `v_inventory_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_inventory_summary` AS SELECT 
 1 AS `ProductID`,
 1 AS `SKU`,
 1 AS `Brand`,
 1 AS `Model`,
 1 AS `Size`,
 1 AS `Color`,
 1 AS `StoreName`,
 1 AS `Quantity`,
 1 AS `MinStockLevel`,
 1 AS `MaxStockLevel`,
 1 AS `StockStatus`,
 1 AS `InventoryValue`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_outstanding_receivables`
--

DROP TABLE IF EXISTS `v_outstanding_receivables`;
/*!50001 DROP VIEW IF EXISTS `v_outstanding_receivables`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_outstanding_receivables` AS SELECT 
 1 AS `ARID`,
 1 AS `SaleID`,
 1 AS `CustomerName`,
 1 AS `Email`,
 1 AS `Phone`,
 1 AS `AmountDue`,
 1 AS `PaidAmount`,
 1 AS `Balance`,
 1 AS `DueDate`,
 1 AS `Status`,
 1 AS `DaysOverdue`*/;
SET character_set_client = @saved_cs_client;

--
-- Temporary view structure for view `v_sales_summary`
--

DROP TABLE IF EXISTS `v_sales_summary`;
/*!50001 DROP VIEW IF EXISTS `v_sales_summary`*/;
SET @saved_cs_client     = @@character_set_client;
/*!50503 SET character_set_client = utf8mb4 */;
/*!50001 CREATE VIEW `v_sales_summary` AS SELECT 
 1 AS `SaleID`,
 1 AS `SaleDate`,
 1 AS `CustomerName`,
 1 AS `StoreName`,
 1 AS `TotalAmount`,
 1 AS `TaxAmount`,
 1 AS `DiscountAmount`,
 1 AS `PaymentStatus`,
 1 AS `PaymentMethod`,
 1 AS `ItemCount`*/;
SET character_set_client = @saved_cs_client;

--
-- Dumping events for database 'shoeretailerp'
--

--
-- Dumping routines for database 'shoeretailerp'
--
/*!50003 DROP PROCEDURE IF EXISTS `GeneratePayroll` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb3 */ ;
/*!50003 SET character_set_results = utf8mb3 */ ;
/*!50003 SET collation_connection  = utf8mb3_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'NO_AUTO_VALUE_ON_ZERO' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `GeneratePayroll`(
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ProcessSale` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `ProcessSale`(
    IN p_customer_id INT,
    IN p_store_id INT,
    IN p_products JSON,
    IN p_payment_method VARCHAR(20),
    IN p_discount_amount DECIMAL(10,2),
    IN p_points_used INT,
    OUT p_sale_id INT
)
BEGIN
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 DROP PROCEDURE IF EXISTS `ReceivePurchaseOrder` */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8mb4 */ ;
/*!50003 SET character_set_results = utf8mb4 */ ;
/*!50003 SET collation_connection  = utf8mb4_0900_ai_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
CREATE DEFINER=`root`@`localhost` PROCEDURE `ReceivePurchaseOrder`(
    IN p_purchase_order_id INT,
    IN p_received_products JSON
)
BEGIN
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
END ;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Final view structure for view `v_financial_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_financial_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_financial_summary` AS select cast(`gl`.`TransactionDate` as date) AS `TransactionDate`,`s`.`StoreName` AS `StoreName`,`gl`.`AccountType` AS `AccountType`,sum(`gl`.`Debit`) AS `TotalDebits`,sum(`gl`.`Credit`) AS `TotalCredits`,sum((`gl`.`Credit` - `gl`.`Debit`)) AS `NetAmount` from (`generalledger` `gl` join `stores` `s` on((`gl`.`StoreID` = `s`.`StoreID`))) group by cast(`gl`.`TransactionDate` as date),`gl`.`StoreID`,`gl`.`AccountType` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_inventory_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_inventory_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_inventory_summary` AS select `p`.`ProductID` AS `ProductID`,`p`.`SKU` AS `SKU`,`p`.`Brand` AS `Brand`,`p`.`Model` AS `Model`,`p`.`Size` AS `Size`,`p`.`Color` AS `Color`,`s`.`StoreName` AS `StoreName`,`i`.`Quantity` AS `Quantity`,`p`.`MinStockLevel` AS `MinStockLevel`,`p`.`MaxStockLevel` AS `MaxStockLevel`,(case when (`i`.`Quantity` <= `p`.`MinStockLevel`) then 'Low Stock' when (`i`.`Quantity` >= `p`.`MaxStockLevel`) then 'Overstock' else 'Normal' end) AS `StockStatus`,(`i`.`Quantity` * `p`.`CostPrice`) AS `InventoryValue` from ((`products` `p` join `inventory` `i` on((`p`.`ProductID` = `i`.`ProductID`))) join `stores` `s` on((`i`.`StoreID` = `s`.`StoreID`))) where (`p`.`Status` = 'Active') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_outstanding_receivables`
--

/*!50001 DROP VIEW IF EXISTS `v_outstanding_receivables`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_outstanding_receivables` AS select `ar`.`ARID` AS `ARID`,`ar`.`SaleID` AS `SaleID`,concat(`c`.`FirstName`,' ',coalesce(`c`.`LastName`,'')) AS `CustomerName`,`c`.`Email` AS `Email`,`c`.`Phone` AS `Phone`,`ar`.`AmountDue` AS `AmountDue`,`ar`.`PaidAmount` AS `PaidAmount`,(`ar`.`AmountDue` - `ar`.`PaidAmount`) AS `Balance`,`ar`.`DueDate` AS `DueDate`,(case when (`ar`.`DueDate` < curdate()) then 'Overdue' when (`ar`.`DueDate` = curdate()) then 'Due Today' else 'Pending' end) AS `Status`,(to_days(curdate()) - to_days(`ar`.`DueDate`)) AS `DaysOverdue` from (`accountsreceivable` `ar` join `customers` `c` on((`ar`.`CustomerID` = `c`.`CustomerID`))) where (`ar`.`PaymentStatus` <> 'Paid') */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;

--
-- Final view structure for view `v_sales_summary`
--

/*!50001 DROP VIEW IF EXISTS `v_sales_summary`*/;
/*!50001 SET @saved_cs_client          = @@character_set_client */;
/*!50001 SET @saved_cs_results         = @@character_set_results */;
/*!50001 SET @saved_col_connection     = @@collation_connection */;
/*!50001 SET character_set_client      = utf8mb4 */;
/*!50001 SET character_set_results     = utf8mb4 */;
/*!50001 SET collation_connection      = utf8mb4_0900_ai_ci */;
/*!50001 CREATE ALGORITHM=UNDEFINED */
/*!50013 DEFINER=`root`@`localhost` SQL SECURITY DEFINER */
/*!50001 VIEW `v_sales_summary` AS select `s`.`SaleID` AS `SaleID`,`s`.`SaleDate` AS `SaleDate`,concat(`c`.`FirstName`,' ',coalesce(`c`.`LastName`,'')) AS `CustomerName`,`st`.`StoreName` AS `StoreName`,`s`.`TotalAmount` AS `TotalAmount`,`s`.`TaxAmount` AS `TaxAmount`,`s`.`DiscountAmount` AS `DiscountAmount`,`s`.`PaymentStatus` AS `PaymentStatus`,`s`.`PaymentMethod` AS `PaymentMethod`,count(`sd`.`SaleDetailID`) AS `ItemCount` from (((`sales` `s` left join `customers` `c` on((`s`.`CustomerID` = `c`.`CustomerID`))) join `stores` `st` on((`s`.`StoreID` = `st`.`StoreID`))) join `saledetails` `sd` on((`s`.`SaleID` = `sd`.`SaleID`))) group by `s`.`SaleID` */;
/*!50001 SET character_set_client      = @saved_cs_client */;
/*!50001 SET character_set_results     = @saved_cs_results */;
/*!50001 SET collation_connection      = @saved_col_connection */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2025-10-28 16:53:17
