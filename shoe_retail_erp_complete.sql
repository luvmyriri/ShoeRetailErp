-- =====================================================
-- Shoe Retail ERP Database - Complete Schema
-- Author: Generated for PHP/MySQL Implementation
-- Date: 2024
-- =====================================================

-- Create Database
CREATE DATABASE IF NOT EXISTS ShoeRetailERP;
USE ShoeRetailERP;

-- Set foreign key checks
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- CORE TABLES
-- =====================================================

-- Suppliers Table: Stores supplier information
CREATE TABLE Suppliers (
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
CREATE TABLE Stores (
    StoreID INT PRIMARY KEY AUTO_INCREMENT,
    StoreName VARCHAR(100) NOT NULL,
    Location TEXT,
    ManagerName VARCHAR(50),
    ContactPhone VARCHAR(20),
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products Table: Stores shoe details
CREATE TABLE Products (
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
CREATE TABLE Inventory (
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
CREATE TABLE Customers (
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
CREATE TABLE Sales (
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
CREATE TABLE SaleDetails (
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
CREATE TABLE PurchaseOrders (
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
CREATE TABLE PurchaseOrderDetails (
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
CREATE TABLE Expenses (
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
-- ACCOUNTING TABLES
-- =====================================================

-- GeneralLedger Table: Centralized financial transactions
CREATE TABLE GeneralLedger (
    LedgerID INT PRIMARY KEY AUTO_INCREMENT,
    TransactionDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    AccountType ENUM('Revenue', 'Expense', 'Asset', 'Liability', 'Equity') NOT NULL,
    AccountName VARCHAR(100) NOT NULL,
    Description VARCHAR(200),
    Debit DECIMAL(10,2) DEFAULT 0,
    Credit DECIMAL(10,2) DEFAULT 0,
    ReferenceID INT,
    ReferenceType ENUM('Sale', 'Purchase', 'Expense', 'Payment', 'Adjustment', 'Other') NOT NULL,
    StoreID INT,
    CreatedBy VARCHAR(50),
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- AccountsReceivable Table: Tracks customer credit payments
CREATE TABLE AccountsReceivable (
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
CREATE TABLE AccountsPayable (
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
CREATE TABLE TaxRecords (
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
CREATE TABLE SupportTickets (
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
-- ADDITIONAL TABLES
-- =====================================================

-- Users Table: System users
CREATE TABLE Users (
    UserID INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(50) UNIQUE NOT NULL,
    PasswordHash VARCHAR(255) NOT NULL,
    FirstName VARCHAR(50) NOT NULL,
    LastName VARCHAR(50) NOT NULL,
    Email VARCHAR(100) UNIQUE NOT NULL,
    Role ENUM('Admin', 'Manager', 'Cashier', 'Accountant', 'Support') NOT NULL,
    StoreID INT,
    Status ENUM('Active', 'Inactive') DEFAULT 'Active',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE SET NULL
) ENGINE=InnoDB;

-- StockMovements Table: Track all stock movements
CREATE TABLE StockMovements (
    MovementID INT PRIMARY KEY AUTO_INCREMENT,
    ProductID INT,
    StoreID INT,
    MovementType ENUM('IN', 'OUT', 'TRANSFER', 'ADJUSTMENT') NOT NULL,
    Quantity INT NOT NULL,
    ReferenceID INT,
    ReferenceType ENUM('Sale', 'Purchase', 'Transfer', 'Adjustment', 'Return') NOT NULL,
    MovementDate DATETIME DEFAULT CURRENT_TIMESTAMP,
    Notes TEXT,
    CreatedBy VARCHAR(50),
    FOREIGN KEY (ProductID) REFERENCES Products(ProductID) ON DELETE CASCADE,
    FOREIGN KEY (StoreID) REFERENCES Stores(StoreID) ON DELETE CASCADE
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
CREATE INDEX idx_ar_due_date ON AccountsReceivable(DueDate);
CREATE INDEX idx_ap_due_date ON AccountsPayable(DueDate);
CREATE INDEX idx_stock_movements_date ON StockMovements(MovementDate);
CREATE INDEX idx_support_tickets_status ON SupportTickets(Status);

-- =====================================================
-- VIEWS FOR REPORTING
-- =====================================================

-- Inventory Summary View
CREATE VIEW v_inventory_summary AS
SELECT 
    p.ProductID,
    p.SKU,
    p.Brand,
    p.Model,
    p.Size,
    p.Color,
    s.StoreName,
    i.Quantity,
    p.MinStockLevel,
    p.MaxStockLevel,
    CASE 
        WHEN i.Quantity <= p.MinStockLevel THEN 'Low Stock'
        WHEN i.Quantity >= p.MaxStockLevel THEN 'Overstock'
        ELSE 'Normal'
    END AS StockStatus,
    (i.Quantity * p.CostPrice) AS InventoryValue
FROM Products p
JOIN Inventory i ON p.ProductID = i.ProductID
JOIN Stores s ON i.StoreID = s.StoreID
WHERE p.Status = 'Active';

-- Sales Summary View
CREATE VIEW v_sales_summary AS
SELECT 
    s.SaleID,
    s.SaleDate,
    CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) AS CustomerName,
    st.StoreName,
    s.TotalAmount,
    s.TaxAmount,
    s.DiscountAmount,
    s.PaymentStatus,
    s.PaymentMethod,
    COUNT(sd.SaleDetailID) AS ItemCount
FROM Sales s
LEFT JOIN Customers c ON s.CustomerID = c.CustomerID
JOIN Stores st ON s.StoreID = st.StoreID
JOIN SaleDetails sd ON s.SaleID = sd.SaleID
GROUP BY s.SaleID;

-- Financial Summary View
CREATE VIEW v_financial_summary AS
SELECT 
    DATE(gl.TransactionDate) AS TransactionDate,
    s.StoreName,
    gl.AccountType,
    SUM(gl.Debit) AS TotalDebits,
    SUM(gl.Credit) AS TotalCredits,
    SUM(gl.Credit - gl.Debit) AS NetAmount
FROM GeneralLedger gl
JOIN Stores s ON gl.StoreID = s.StoreID
GROUP BY DATE(gl.TransactionDate), gl.StoreID, gl.AccountType;

-- Outstanding Receivables View
CREATE VIEW v_outstanding_receivables AS
SELECT 
    ar.ARID,
    ar.SaleID,
    CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) AS CustomerName,
    c.Email,
    c.Phone,
    ar.AmountDue,
    ar.PaidAmount,
    (ar.AmountDue - ar.PaidAmount) AS Balance,
    ar.DueDate,
    CASE 
        WHEN ar.DueDate < CURDATE() THEN 'Overdue'
        WHEN ar.DueDate = CURDATE() THEN 'Due Today'
        ELSE 'Pending'
    END AS Status,
    DATEDIFF(CURDATE(), ar.DueDate) AS DaysOverdue
FROM AccountsReceivable ar
JOIN Customers c ON ar.CustomerID = c.CustomerID
WHERE ar.PaymentStatus != 'Paid';

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

DELIMITER $$

-- Procedure to process a complete sale
CREATE PROCEDURE ProcessSale(
    IN p_customer_id INT,
    IN p_store_id INT,
    IN p_products JSON,
    IN p_payment_method VARCHAR(20),
    IN p_discount_amount DECIMAL(10,2),
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
    
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        RESIGNAL;
    END;
    
    START TRANSACTION;
    
    -- Get product count from JSON
    SET v_count = JSON_LENGTH(p_products);
    
    -- Create sale record
    INSERT INTO Sales (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus)
    VALUES (p_customer_id, p_store_id, 0, 0, p_discount_amount, p_payment_method, 'Paid');
    
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
    SET v_tax_amount = (v_total_amount - p_discount_amount) * 0.10;
    
    -- Update sale totals
    UPDATE Sales 
    SET TotalAmount = v_total_amount, TaxAmount = v_tax_amount
    WHERE SaleID = p_sale_id;
    
    -- Update customer loyalty points (1 point per $10)
    IF p_customer_id IS NOT NULL THEN
        SET v_loyalty_points = FLOOR((v_total_amount + v_tax_amount - p_discount_amount) / 10);
        UPDATE Customers 
        SET LoyaltyPoints = LoyaltyPoints + v_loyalty_points
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

-- Procedure to receive purchase order
CREATE PROCEDURE ReceivePurchaseOrder(
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
END$$

DELIMITER ;

-- =====================================================
-- TRIGGERS
-- =====================================================

DELIMITER $$

-- Trigger to update inventory value in general ledger when stock changes
CREATE TRIGGER tr_inventory_update AFTER UPDATE ON Inventory
FOR EACH ROW
BEGIN
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
END$$

-- Trigger to check for low stock alerts
CREATE TRIGGER tr_low_stock_alert AFTER UPDATE ON Inventory
FOR EACH ROW
BEGIN
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
END$$

-- Trigger to automatically mark overdue accounts
CREATE TRIGGER tr_mark_overdue_ar BEFORE UPDATE ON AccountsReceivable
FOR EACH ROW
BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END$$

CREATE TRIGGER tr_mark_overdue_ap BEFORE UPDATE ON AccountsPayable
FOR EACH ROW
BEGIN
    IF NEW.DueDate < CURDATE() AND NEW.PaymentStatus = 'Pending' THEN
        SET NEW.PaymentStatus = 'Overdue';
    END IF;
END$$

DELIMITER ;

-- =====================================================
-- SAMPLE DATA
-- =====================================================

-- Sample Stores
INSERT INTO Stores (StoreName, Location, ManagerName, ContactPhone) VALUES
('Downtown Store', '123 Main St, City Center', 'John Smith', '555-0101'),
('Mall Store', 'Shopping Mall, North Side', 'Jane Doe', '555-0102'),
('Outlet Store', '456 Outlet Rd, Suburbs', 'Mike Johnson', '555-0103');

-- Sample Suppliers
INSERT INTO Suppliers (SupplierName, ContactName, Email, Phone, Address, PaymentTerms) VALUES
('Nike Distribution', 'Sarah Wilson', 'sarah@nikedist.com', '555-1001', '100 Nike Way, Portland, OR', 'Net 30'),
('Adidas Supply Co', 'Tom Brown', 'tom@adidas-supply.com', '555-1002', '200 Adidas St, Germany', 'Net 45'),
('Local Shoe Warehouse', 'Lisa Garcia', 'lisa@localshoes.com', '555-1003', '300 Local Ave, Local City', 'Net 15');

-- Sample Customers
INSERT INTO Customers (FirstName, LastName, Email, Phone, Address, LoyaltyPoints) VALUES
('Alice', 'Johnson', 'alice@email.com', '555-2001', '111 Customer St', 150),
('Bob', 'Smith', 'bob@email.com', '555-2002', '222 Buyer Ave', 75),
('Carol', 'Davis', 'carol@email.com', '555-2003', '333 Shopper Rd', 200);

-- Sample Products
INSERT INTO Products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, MinStockLevel, MaxStockLevel, SupplierID) VALUES
('NK-AM-001-9.5-BLK', 'Nike', 'Air Max 90', 9.5, 'Black', 65.00, 120.00, 5, 50, 1),
('NK-AM-001-10-WHT', 'Nike', 'Air Max 90', 10.0, 'White', 65.00, 120.00, 5, 50, 1),
('AD-UB-001-9-BLU', 'Adidas', 'Ultraboost 22', 9.0, 'Blue', 75.00, 140.00, 3, 30, 2),
('AD-UB-001-10-GRY', 'Adidas', 'Ultraboost 22', 10.0, 'Grey', 75.00, 140.00, 3, 30, 2),
('LC-CS-001-8.5-BRN', 'Local Brand', 'Casual Sneaker', 8.5, 'Brown', 35.00, 70.00, 10, 100, 3);

-- Sample Inventory
INSERT INTO Inventory (ProductID, StoreID, Quantity) VALUES
(1, 1, 25), (1, 2, 15), (1, 3, 10),
(2, 1, 20), (2, 2, 18), (2, 3, 12),
(3, 1, 12), (3, 2, 8), (3, 3, 5),
(4, 1, 15), (4, 2, 10), (4, 3, 7),
(5, 1, 50), (5, 2, 40), (5, 3, 30);

-- Sample Users
INSERT INTO Users (Username, PasswordHash, FirstName, LastName, Email, Role, StoreID) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin@shoestore.com', 'Admin', NULL),
('manager1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Smith', 'john@shoestore.com', 'Manager', 1),
('cashier1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mary', 'Johnson', 'mary@shoestore.com', 'Cashier', 1);

-- Reset foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- END OF SCHEMA
-- =====================================================