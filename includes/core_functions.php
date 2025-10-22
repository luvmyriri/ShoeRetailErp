<?php
/**
 * Core Functions for Shoe Retail ERP System
 * Contains all business logic functions for inventory, sales, procurement, customers, and accounting
 * Author: Generated for PHP/MySQL Implementation
 * Date: 2024
 */

require_once __DIR__ . '/../config/database.php';

// ==============================================
// INVENTORY MANAGEMENT FUNCTIONS
// ==============================================

/**
 * Get all products with inventory information
 */
function getAllProducts($storeId = null, $searchTerm = null) {
    $query = "
        SELECT p.*, i.Quantity, s.StoreName,
               CASE 
                   WHEN i.Quantity <= p.MinStockLevel THEN 'Low Stock'
                   WHEN i.Quantity >= p.MaxStockLevel THEN 'Overstock'
                   ELSE 'Normal'
               END AS StockStatus
        FROM Products p
        LEFT JOIN Inventory i ON p.ProductID = i.ProductID
        LEFT JOIN Stores s ON i.StoreID = s.StoreID
        WHERE p.Status = 'Active'
    ";
    
    $params = [];
    
    if ($storeId) {
        $query .= " AND i.StoreID = ?";
        $params[] = $storeId;
    }
    
    if ($searchTerm) {
        $query .= " AND (p.SKU LIKE ? OR p.Brand LIKE ? OR p.Model LIKE ?)";
        $searchTerm = "%{$searchTerm}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " ORDER BY p.Brand, p.Model, p.Size";
    
    return dbFetchAll($query, $params);
}

/**
 * Add new product
 */
function addProduct($data) {
    try {
        $query = "
            INSERT INTO Products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, 
                                MinStockLevel, MaxStockLevel, SupplierID) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $productId = dbInsert($query, [
            $data['sku'], $data['brand'], $data['model'], $data['size'], $data['color'],
            $data['cost_price'], $data['selling_price'], $data['min_stock'], $data['max_stock'],
            $data['supplier_id']
        ]);
        
        logInfo("Product added successfully", ['product_id' => $productId, 'sku' => $data['sku']]);
        return $productId;
    } catch (Exception $e) {
        logError("Failed to add product", ['error' => $e->getMessage(), 'data' => $data]);
        throw $e;
    }
}

/**
 * Update inventory quantity
 */
function updateInventory($productId, $storeId, $quantity, $movementType = 'ADJUSTMENT') {
    try {
        getDB()->beginTransaction();
        
        // Update inventory
        $query = "
            INSERT INTO Inventory (ProductID, StoreID, Quantity) 
            VALUES (?, ?, ?) 
            ON DUPLICATE KEY UPDATE Quantity = ?
        ";
        dbExecute($query, [$productId, $storeId, $quantity, $quantity]);
        
        // Record stock movement
        $query = "
            INSERT INTO StockMovements (ProductID, StoreID, MovementType, Quantity, 
                                      ReferenceType, Notes, CreatedBy) 
            VALUES (?, ?, ?, ?, 'Adjustment', 'Manual inventory adjustment', ?)
        ";
        dbExecute($query, [$productId, $storeId, $movementType, $quantity, $_SESSION['username'] ?? 'System']);
        
        getDB()->commit();
        
        logInfo("Inventory updated", [
            'product_id' => $productId, 
            'store_id' => $storeId, 
            'quantity' => $quantity
        ]);
        
        return true;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to update inventory", [
            'error' => $e->getMessage(), 
            'product_id' => $productId, 
            'store_id' => $storeId
        ]);
        throw $e;
    }
}

/**
 * Get low stock items
 */
function getLowStockItems($storeId = null) {
    $query = "SELECT * FROM v_inventory_summary WHERE StockStatus = 'Low Stock'";
    $params = [];
    
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $params[] = $storeId;
    }
    
    return dbFetchAll($query, $params);
}

// ==============================================
// SALES MANAGEMENT FUNCTIONS
// ==============================================

/**
 * Process a complete sale using stored procedure
 */
function processSale($customerId, $storeId, $products, $paymentMethod = 'Cash', $discountAmount = 0) {
    try {
        // Prepare products JSON
        $productsJson = json_encode($products);
        
        // Call stored procedure
        $query = "CALL ProcessSale(?, ?, ?, ?, ?, @sale_id)";
        dbExecute($query, [$customerId, $storeId, $productsJson, $paymentMethod, $discountAmount]);
        
        // Get the sale ID
        $result = dbFetchOne("SELECT @sale_id as sale_id");
        $saleId = $result['sale_id'];
        
        // If credit sale, create accounts receivable
        if ($paymentMethod === 'Credit') {
            createAccountsReceivable($saleId, $customerId);
        }
        
        logInfo("Sale processed successfully", [
            'sale_id' => $saleId, 
            'customer_id' => $customerId, 
            'store_id' => $storeId
        ]);
        
        return $saleId;
    } catch (Exception $e) {
        logError("Failed to process sale", [
            'error' => $e->getMessage(), 
            'customer_id' => $customerId, 
            'store_id' => $storeId
        ]);
        throw $e;
    }
}

/**
 * Get sales summary
 */
function getSalesSummary($startDate = null, $endDate = null, $storeId = null) {
    $query = "SELECT * FROM v_sales_summary WHERE 1=1";
    $params = [];
    
    if ($startDate) {
        $query .= " AND DATE(SaleDate) >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND DATE(SaleDate) <= ?";
        $params[] = $endDate;
    }
    
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY SaleDate DESC";
    
    return dbFetchAll($query, $params);
}

/**
 * Process return/refund
 */
function processReturn($saleId, $returnItems, $reason = 'Customer Request') {
    try {
        getDB()->beginTransaction();
        
        // Get original sale data
        $sale = dbFetchOne("SELECT * FROM Sales WHERE SaleID = ?", [$saleId]);
        if (!$sale) {
            throw new Exception("Sale not found");
        }
        
        $totalRefund = 0;
        
        foreach ($returnItems as $item) {
            // Update inventory (return stock)
            $query = "
                UPDATE Inventory 
                SET Quantity = Quantity + ? 
                WHERE ProductID = ? AND StoreID = ?
            ";
            dbExecute($query, [$item['quantity'], $item['product_id'], $sale['StoreID']]);
            
            // Record stock movement
            $query = "
                INSERT INTO StockMovements (ProductID, StoreID, MovementType, Quantity, 
                                          ReferenceID, ReferenceType, Notes, CreatedBy) 
                VALUES (?, ?, 'IN', ?, ?, 'Return', ?, ?)
            ";
            dbExecute($query, [
                $item['product_id'], $sale['StoreID'], $item['quantity'], 
                $saleId, $reason, $_SESSION['username'] ?? 'System'
            ]);
            
            $totalRefund += $item['quantity'] * $item['unit_price'];
        }
        
        // Update sale status
        dbUpdate("UPDATE Sales SET PaymentStatus = 'Refunded' WHERE SaleID = ?", [$saleId]);
        
        // Record refund in general ledger
        recordGeneralLedger('Expense', 'Sales Returns', 'Product return refund', $totalRefund, 0, $saleId, 'Sale', $sale['StoreID']);
        
        getDB()->commit();
        
        logInfo("Return processed successfully", [
            'sale_id' => $saleId, 
            'refund_amount' => $totalRefund
        ]);
        
        return $totalRefund;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to process return", ['error' => $e->getMessage(), 'sale_id' => $saleId]);
        throw $e;
    }
}

// ==============================================
// PROCUREMENT FUNCTIONS
// ==============================================

/**
 * Create purchase order
 */
function createPurchaseOrder($supplierId, $storeId, $products, $expectedDeliveryDate = null) {
    try {
        getDB()->beginTransaction();
        
        $totalAmount = array_sum(array_column($products, 'subtotal'));
        
        // Create purchase order
        $query = "
            INSERT INTO PurchaseOrders (SupplierID, StoreID, ExpectedDeliveryDate, 
                                      TotalAmount, CreatedBy) 
            VALUES (?, ?, ?, ?, ?)
        ";
        $purchaseOrderId = dbInsert($query, [
            $supplierId, $storeId, $expectedDeliveryDate, $totalAmount, $_SESSION['username'] ?? 'System'
        ]);
        
        // Add purchase order details
        foreach ($products as $product) {
            $query = "
                INSERT INTO PurchaseOrderDetails (PurchaseOrderID, ProductID, Quantity, UnitCost, Subtotal) 
                VALUES (?, ?, ?, ?, ?)
            ";
            dbExecute($query, [
                $purchaseOrderId, $product['product_id'], $product['quantity'], 
                $product['unit_cost'], $product['subtotal']
            ]);
        }
        
        // Create accounts payable entry
        createAccountsPayable($purchaseOrderId, $supplierId, $totalAmount, $expectedDeliveryDate);
        
        getDB()->commit();
        
        logInfo("Purchase order created", [
            'po_id' => $purchaseOrderId, 
            'supplier_id' => $supplierId, 
            'total_amount' => $totalAmount
        ]);
        
        return $purchaseOrderId;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to create purchase order", [
            'error' => $e->getMessage(), 
            'supplier_id' => $supplierId
        ]);
        throw $e;
    }
}

/**
 * Receive purchase order
 */
function receivePurchaseOrder($purchaseOrderId, $receivedProducts) {
    try {
        // Prepare received products JSON
        $receivedJson = json_encode($receivedProducts);
        
        // Call stored procedure
        $query = "CALL ReceivePurchaseOrder(?, ?)";
        dbExecute($query, [$purchaseOrderId, $receivedJson]);
        
        // Update accounts payable
        $po = dbFetchOne("SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?", [$purchaseOrderId]);
        recordGeneralLedger('Asset', 'Inventory', 'Purchase order received', $po['TotalAmount'], 0, $purchaseOrderId, 'Purchase', $po['StoreID']);
        
        logInfo("Purchase order received", ['po_id' => $purchaseOrderId]);
        
        return true;
    } catch (Exception $e) {
        logError("Failed to receive purchase order", [
            'error' => $e->getMessage(), 
            'po_id' => $purchaseOrderId
        ]);
        throw $e;
    }
}

/**
 * Get purchase orders
 */
function getPurchaseOrders($storeId = null, $status = null) {
    $query = "
        SELECT po.*, s.SupplierName, st.StoreName 
        FROM PurchaseOrders po
        JOIN Suppliers s ON po.SupplierID = s.SupplierID
        JOIN Stores st ON po.StoreID = st.StoreID
        WHERE 1=1
    ";
    $params = [];
    
    if ($storeId) {
        $query .= " AND po.StoreID = ?";
        $params[] = $storeId;
    }
    
    if ($status) {
        $query .= " AND po.Status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY po.OrderDate DESC";
    
    return dbFetchAll($query, $params);
}

// ==============================================
// CUSTOMER MANAGEMENT FUNCTIONS
// ==============================================

/**
 * Add new customer
 */
function addCustomer($data) {
    try {
        $query = "
            INSERT INTO Customers (FirstName, LastName, Email, Phone, Address) 
            VALUES (?, ?, ?, ?, ?)
        ";
        
        $customerId = dbInsert($query, [
            $data['first_name'], $data['last_name'], $data['email'], 
            $data['phone'], $data['address']
        ]);
        
        logInfo("Customer added successfully", ['customer_id' => $customerId]);
        return $customerId;
    } catch (Exception $e) {
        logError("Failed to add customer", ['error' => $e->getMessage(), 'data' => $data]);
        throw $e;
    }
}

/**
 * Update customer loyalty points
 */
function updateLoyaltyPoints($customerId, $points, $operation = 'add') {
    try {
        if ($operation === 'add') {
            $query = "UPDATE Customers SET LoyaltyPoints = LoyaltyPoints + ? WHERE CustomerID = ?";
        } else {
            $query = "UPDATE Customers SET LoyaltyPoints = LoyaltyPoints - ? WHERE CustomerID = ?";
        }
        
        $affected = dbUpdate($query, [$points, $customerId]);
        
        logInfo("Loyalty points updated", [
            'customer_id' => $customerId, 
            'points' => $points, 
            'operation' => $operation
        ]);
        
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to update loyalty points", [
            'error' => $e->getMessage(), 
            'customer_id' => $customerId
        ]);
        throw $e;
    }
}

/**
 * Get customer information
 */
function getCustomer($customerId = null, $email = null, $phone = null) {
    if ($customerId) {
        return dbFetchOne("SELECT * FROM Customers WHERE CustomerID = ?", [$customerId]);
    } elseif ($email) {
        return dbFetchOne("SELECT * FROM Customers WHERE Email = ?", [$email]);
    } elseif ($phone) {
        return dbFetchOne("SELECT * FROM Customers WHERE Phone = ?", [$phone]);
    }
    return null;
}

/**
 * Search customers
 */
function searchCustomers($searchTerm) {
    $query = "
        SELECT * FROM Customers 
        WHERE FirstName LIKE ? OR LastName LIKE ? OR Email LIKE ? OR Phone LIKE ?
        ORDER BY FirstName, LastName
    ";
    $searchTerm = "%{$searchTerm}%";
    return dbFetchAll($query, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

// ==============================================
// SUPPORT TICKET FUNCTIONS
// ==============================================

/**
 * Create support ticket
 */
function createSupportTicket($customerId, $storeId, $subject, $description, $priority = 'Medium') {
    try {
        $query = "
            INSERT INTO SupportTickets (CustomerID, StoreID, Subject, Description, Priority, AssignedTo) 
            VALUES (?, ?, ?, ?, ?, ?)
        ";
        
        $ticketId = dbInsert($query, [
            $customerId, $storeId, $subject, $description, $priority, 'Support Team'
        ]);
        
        logInfo("Support ticket created", ['ticket_id' => $ticketId]);
        return $ticketId;
    } catch (Exception $e) {
        logError("Failed to create support ticket", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Update support ticket status
 */
function updateSupportTicket($ticketId, $status, $resolution = null) {
    try {
        $query = "UPDATE SupportTickets SET Status = ?, Resolution = ?";
        $params = [$status, $resolution];
        
        if ($status === 'Resolved' || $status === 'Closed') {
            $query .= ", ResolvedDate = NOW()";
        }
        
        $query .= " WHERE TicketID = ?";
        $params[] = $ticketId;
        
        $affected = dbUpdate($query, $params);
        
        logInfo("Support ticket updated", ['ticket_id' => $ticketId, 'status' => $status]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to update support ticket", ['error' => $e->getMessage(), 'ticket_id' => $ticketId]);
        throw $e;
    }
}

// ==============================================
// ACCOUNTING FUNCTIONS
// ==============================================

/**
 * Record entry in general ledger
 */
function recordGeneralLedger($accountType, $accountName, $description, $debit, $credit, $referenceId, $referenceType, $storeId) {
    try {
        $query = "
            INSERT INTO GeneralLedger (AccountType, AccountName, Description, Debit, Credit, 
                                     ReferenceID, ReferenceType, StoreID, CreatedBy) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $ledgerId = dbInsert($query, [
            $accountType, $accountName, $description, $debit, $credit, 
            $referenceId, $referenceType, $storeId, $_SESSION['username'] ?? 'System'
        ]);
        
        return $ledgerId;
    } catch (Exception $e) {
        logError("Failed to record general ledger entry", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Create accounts receivable entry
 */
function createAccountsReceivable($saleId, $customerId) {
    try {
        $sale = dbFetchOne("SELECT * FROM Sales WHERE SaleID = ?", [$saleId]);
        $dueDate = date('Y-m-d', strtotime('+30 days'));
        $amount = $sale['TotalAmount'] + $sale['TaxAmount'] - $sale['DiscountAmount'];
        
        $query = "
            INSERT INTO AccountsReceivable (SaleID, CustomerID, AmountDue, DueDate) 
            VALUES (?, ?, ?, ?)
        ";
        
        $arId = dbInsert($query, [$saleId, $customerId, $amount, $dueDate]);
        
        logInfo("Accounts receivable created", ['ar_id' => $arId, 'sale_id' => $saleId]);
        return $arId;
    } catch (Exception $e) {
        logError("Failed to create accounts receivable", ['error' => $e->getMessage(), 'sale_id' => $saleId]);
        throw $e;
    }
}

/**
 * Create accounts payable entry
 */
function createAccountsPayable($purchaseOrderId, $supplierId, $amount, $dueDate = null) {
    try {
        if (!$dueDate) {
            $dueDate = date('Y-m-d', strtotime('+30 days'));
        }
        
        $query = "
            INSERT INTO AccountsPayable (PurchaseOrderID, SupplierID, AmountDue, DueDate) 
            VALUES (?, ?, ?, ?)
        ";
        
        $apId = dbInsert($query, [$purchaseOrderId, $supplierId, $amount, $dueDate]);
        
        logInfo("Accounts payable created", ['ap_id' => $apId, 'po_id' => $purchaseOrderId]);
        return $apId;
    } catch (Exception $e) {
        logError("Failed to create accounts payable", ['error' => $e->getMessage(), 'po_id' => $purchaseOrderId]);
        throw $e;
    }
}

/**
 * Process payment for accounts receivable
 */
function processARPayment($arId, $amount, $paymentMethod = 'Cash') {
    try {
        getDB()->beginTransaction();
        
        $ar = dbFetchOne("SELECT * FROM AccountsReceivable WHERE ARID = ?", [$arId]);
        if (!$ar) {
            throw new Exception("Accounts receivable record not found");
        }
        
        $newPaidAmount = $ar['PaidAmount'] + $amount;
        $status = $newPaidAmount >= $ar['AmountDue'] ? 'Paid' : 'Partial';
        
        // Update accounts receivable
        $query = "
            UPDATE AccountsReceivable 
            SET PaidAmount = ?, PaymentStatus = ?, PaymentDate = NOW() 
            WHERE ARID = ?
        ";
        dbUpdate($query, [$newPaidAmount, $status, $arId]);
        
        // Record in general ledger
        $sale = dbFetchOne("SELECT StoreID FROM Sales WHERE SaleID = ?", [$ar['SaleID']]);
        recordGeneralLedger('Asset', 'Cash', 'Customer payment received', $amount, 0, $ar['SaleID'], 'Payment', $sale['StoreID']);
        recordGeneralLedger('Asset', 'Accounts Receivable', 'Customer payment received', 0, $amount, $ar['SaleID'], 'Payment', $sale['StoreID']);
        
        getDB()->commit();
        
        logInfo("AR payment processed", ['ar_id' => $arId, 'amount' => $amount]);
        return true;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to process AR payment", ['error' => $e->getMessage(), 'ar_id' => $arId]);
        throw $e;
    }
}

/**
 * Get financial summary
 */
function getFinancialSummary($startDate = null, $endDate = null, $storeId = null) {
    $query = "SELECT * FROM v_financial_summary WHERE 1=1";
    $params = [];
    
    if ($startDate) {
        $query .= " AND TransactionDate >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND TransactionDate <= ?";
        $params[] = $endDate;
    }
    
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $params[] = $storeId;
    }
    
    return dbFetchAll($query, $params);
}

/**
 * Get outstanding receivables
 */
function getOutstandingReceivables($storeId = null) {
    $query = "SELECT * FROM v_outstanding_receivables WHERE 1=1";
    $params = [];
    
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY DaysOverdue DESC, AmountDue DESC";
    
    return dbFetchAll($query, $params);
}

// ==============================================
// UTILITY FUNCTIONS
// ==============================================

/**
 * Get all stores
 */
function getAllStores() {
    return dbFetchAll("SELECT * FROM Stores WHERE Status = 'Active' ORDER BY StoreName");
}

/**
 * Get all suppliers
 */
function getAllSuppliers() {
    return dbFetchAll("SELECT * FROM Suppliers WHERE Status = 'Active' ORDER BY SupplierName");
}

/**
 * Get dashboard statistics
 */
function getDashboardStats($storeId = null) {
    $stats = [];
    
    // Total products
    $query = "SELECT COUNT(*) as total FROM Products WHERE Status = 'Active'";
    $stats['total_products'] = dbFetchOne($query)['total'];
    
    // Low stock items
    $query = "SELECT COUNT(*) as total FROM v_inventory_summary WHERE StockStatus = 'Low Stock'";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $stats['low_stock_items'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['low_stock_items'] = dbFetchOne($query)['total'];
    }
    
    // Today's sales
    $query = "SELECT COUNT(*) as count, COALESCE(SUM(TotalAmount), 0) as total FROM Sales WHERE DATE(SaleDate) = CURDATE()";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $result = dbFetchOne($query, [$storeId]);
    } else {
        $result = dbFetchOne($query);
    }
    $stats['todays_sales_count'] = $result['count'];
    $stats['todays_sales_total'] = $result['total'];
    
    // Pending support tickets
    $query = "SELECT COUNT(*) as total FROM SupportTickets WHERE Status IN ('Open', 'In Progress')";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $stats['pending_tickets'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['pending_tickets'] = dbFetchOne($query)['total'];
    }
    
    // Outstanding receivables
    $query = "SELECT COUNT(*) as count, COALESCE(SUM(AmountDue - PaidAmount), 0) as total FROM AccountsReceivable WHERE PaymentStatus != 'Paid'";
    $result = dbFetchOne($query);
    $stats['outstanding_receivables_count'] = $result['count'];
    $stats['outstanding_receivables_total'] = $result['total'];
    
    return $stats;
}

/**
 * Export data to CSV
 */
function exportToCSV($data, $filename, $headers = null) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if ($headers) {
        fputcsv($output, $headers);
    } elseif (!empty($data)) {
        fputcsv($output, array_keys($data[0]));
    }
    
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Generate report data
 */
function generateSalesReport($startDate, $endDate, $storeId = null) {
    $query = "
        SELECT 
            s.SaleDate,
            s.SaleID,
            CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName,
            st.StoreName,
            s.TotalAmount,
            s.TaxAmount,
            s.PaymentMethod,
            s.PaymentStatus
        FROM Sales s
        LEFT JOIN Customers c ON s.CustomerID = c.CustomerID
        JOIN Stores st ON s.StoreID = st.StoreID
        WHERE DATE(s.SaleDate) BETWEEN ? AND ?
    ";
    
    $params = [$startDate, $endDate];
    
    if ($storeId) {
        $query .= " AND s.StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY s.SaleDate DESC";
    
    return dbFetchAll($query, $params);
}

/**
 * User authentication and session management
 */
function authenticateUser($username, $password) {
    $user = dbFetchOne("SELECT * FROM Users WHERE Username = ? AND Status = 'Active'", [$username]);
    
    if ($user && verifyPassword($password, $user['PasswordHash'])) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['Role'];
        $_SESSION['store_id'] = $user['StoreID'];
        $_SESSION['full_name'] = $user['FirstName'] . ' ' . $user['LastName'];
        
        logInfo("User logged in successfully", ['username' => $username, 'role' => $user['Role']]);
        return true;
    }
    
    logError("Failed login attempt", ['username' => $username]);
    return false;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']);
}

/**
 * Check user permission
 */
function hasPermission($requiredRole) {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'];
    $roleHierarchy = ['Cashier' => 1, 'Support' => 2, 'Accountant' => 3, 'Manager' => 4, 'Admin' => 5];
    
    return $roleHierarchy[$userRole] >= $roleHierarchy[$requiredRole];
}

/**
 * Logout user
 */
function logoutUser() {
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    logInfo("User logged out", ['username' => $_SESSION['username'] ?? 'unknown']);
    
    session_unset();
    session_destroy();
}
?>