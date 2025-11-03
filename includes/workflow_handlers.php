<?php
/**
 * Cross-Module Workflow Handlers
 * Orchestrates data flow between modules
 * 
 * Usage:
 *   WorkflowHandler::processSale($saleData);
 *   WorkflowHandler::processPurchaseOrder($poData);
 *   WorkflowHandler::processPayroll($payrollData);
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/core_functions.php';

class WorkflowHandler
{
    private static $db = null;

    public static function init()
    {
        self::$db = getDB();
    }

    /**
     * Process Sale Transaction
     * Flow: Sales → Inventory → Accounting
     */
    public static function processSale($saleData)
    {
        self::init();
        self::$db->beginTransaction();

        try {
            // 1. Validate sale data
            if (!isset($saleData['customer_id']) || !isset($saleData['items'])) {
                throw new Exception('Invalid sale data: missing customer_id or items');
            }

            // 2. Create sale record
            $saleId = self::createSaleRecord($saleData);
            logInfo('Sale created', ['sale_id' => $saleId, 'customer_id' => $saleData['customer_id']]);

            // 3. Deduct inventory for each item
            $totalAmount = 0;
            foreach ($saleData['items'] as $item) {
                self::decrementInventory($item['product_id'], $item['store_id'], $item['quantity']);
                $totalAmount += $item['quantity'] * $item['price'];
            }

            // 4. Create AR entry if sale is on credit
            if ($saleData['payment_method'] === 'Credit') {
                self::createAccountsReceivable($saleId, $saleData['customer_id'], $totalAmount);
            }

            // 5. Record GL entries
            self::recordSaleGLEntries($saleId, $totalAmount, $saleData['store_id']);

            // 6. Update customer loyalty points
            if (isset($saleData['customer_id'])) {
                self::updateCustomerLoyalty($saleData['customer_id'], $totalAmount);
            }

            self::$db->commit();

            logInfo('Sale workflow completed', ['sale_id' => $saleId, 'total' => $totalAmount]);

            return [
                'success' => true,
                'sale_id' => $saleId,
                'message' => 'Sale processed successfully'
            ];
        } catch (Exception $e) {
            self::$db->rollback();
            logError('Sale workflow failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process Purchase Order & Goods Receipt
     * Flow: Procurement → Inventory → Accounting
     */
    public static function processGoodsReceipt($receiptData)
    {
        self::init();
        self::$db->beginTransaction();

        try {
            if (!isset($receiptData['po_id'])) {
                throw new Exception('Purchase Order ID required');
            }

            // 1. Get PO details
            $po = self::$db->fetchOne(
                "SELECT * FROM purchaseorders WHERE PurchaseOrderID = ?",
                [$receiptData['po_id']]
            );

            if (!$po) {
                throw new Exception('Purchase order not found');
            }

            // 2. Get PO line items
            $poItems = self::$db->fetchAll(
                "SELECT * FROM purchaseorderdetails WHERE PurchaseOrderID = ?",
                [$receiptData['po_id']]
            );

            // 3. Create goods receipt record
            $receiptId = self::createGoodsReceipt($receiptData['po_id'], $receiptData);

            // 4. Increment inventory for each item
            $totalAmount = 0;
            foreach ($poItems as $item) {
                self::incrementInventory(
                    $item['ProductID'],
                    $po['StoreID'],
                    $item['Quantity']
                );
                $totalAmount += $item['Quantity'] * $item['UnitCost'];
            }

            // 5. Create AP entry
            self::createAccountsPayable($receiptId, $po['SupplierID'], $totalAmount);

            // 6. Record GL entries
            self::recordPurchaseGLEntries($po['PurchaseOrderID'], $totalAmount, $po['StoreID']);

            // 7. Update PO status to received
            self::$db->update(
                "UPDATE purchaseorders SET Status = 'Received', ReceivedDate = NOW() WHERE PurchaseOrderID = ?",
                [$receiptData['po_id']]
            );

            self::$db->commit();

            logInfo('Goods receipt workflow completed', ['po_id' => $receiptData['po_id'], 'receipt_id' => $receiptId]);

            return [
                'success' => true,
                'receipt_id' => $receiptId,
                'message' => 'Goods receipt processed successfully'
            ];
        } catch (Exception $e) {
            self::$db->rollback();
            logError('Goods receipt workflow failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Process Payroll & GL Entries
     * Flow: HR → Accounting
     */
    public static function processPayroll($payrollData)
    {
        self::init();
        self::$db->beginTransaction();

        try {
            if (!isset($payrollData['employee_id']) || !isset($payrollData['period'])) {
                throw new Exception('Employee ID and period required');
            }

            // 1. Calculate salary components
            $salaryComponents = self::calculateSalaryComponents($payrollData);

            // 2. Create payroll record
            $payrollId = self::createPayrollRecord($payrollData, $salaryComponents);

            // 3. Record GL entries for each component
            self::recordSalaryExpenseGL($payrollId, $salaryComponents, $payrollData['store_id']);
            self::recordTaxGL($payrollId, $salaryComponents);
            self::recordBenefitsGL($payrollId, $salaryComponents);

            // 4. Update employee payment status
            self::$db->update(
                "UPDATE employees SET LastPayrollDate = NOW() WHERE EmployeeID = ?",
                [$payrollData['employee_id']]
            );

            self::$db->commit();

            logInfo('Payroll workflow completed', ['payroll_id' => $payrollId, 'employee_id' => $payrollData['employee_id']]);

            return [
                'success' => true,
                'payroll_id' => $payrollId,
                'message' => 'Payroll processed successfully'
            ];
        } catch (Exception $e) {
            self::$db->rollback();
            logError('Payroll workflow failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update Customer Profile with Cross-Module Data
     */
    public static function updateCustomerProfile($customerId)
    {
        self::init();

        try {
            // 1. Get customer data
            $customer = self::$db->fetchOne("SELECT * FROM customers WHERE CustomerID = ?", [$customerId]);

            if (!$customer) {
                throw new Exception('Customer not found');
            }

            // 2. Get purchase history from Sales
            $purchases = self::$db->fetchAll(
                "SELECT SUM(TotalAmount) as lifetime_value, COUNT(*) as total_purchases FROM sales WHERE CustomerID = ?",
                [$customerId]
            );

            // 3. Get loyalty points
            $loyalty = self::$db->fetchOne(
                "SELECT LoyaltyPoints FROM customers WHERE CustomerID = ?",
                [$customerId]
            );

            // 4. Get AR balance
            $arBalance = self::$db->fetchOne(
                "SELECT SUM(AmountDue - PaidAmount) as outstanding FROM accountsreceivable WHERE CustomerID = ?",
                [$customerId]
            );

            // 5. Update customer record with aggregated data
            $updateData = [
                'customer_id' => $customerId,
                'lifetime_value' => $purchases[0]['lifetime_value'] ?? 0,
                'total_purchases' => $purchases[0]['total_purchases'] ?? 0,
                'loyalty_points' => $loyalty['LoyaltyPoints'] ?? 0,
                'outstanding_balance' => $arBalance['outstanding'] ?? 0,
            ];

            return [
                'success' => true,
                'data' => array_merge($customer, $updateData)
            ];
        } catch (Exception $e) {
            logError('Customer profile update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ==================== Helper Methods ====================

    private static function createSaleRecord($saleData)
    {
        return self::$db->insert(
            "INSERT INTO sales (CustomerID, StoreID, SaleDate, TotalAmount, PaymentMethod, PaymentStatus) 
             VALUES (?, ?, NOW(), ?, ?, 'Pending')",
            [
                $saleData['customer_id'],
                $saleData['store_id'] ?? $_SESSION['store_id'],
                $saleData['total_amount'] ?? 0,
                $saleData['payment_method'] ?? 'Cash'
            ]
        );
    }

    private static function decrementInventory($productId, $storeId, $quantity)
    {
        // Check available inventory
        $current = self::$db->fetchOne(
            "SELECT Quantity FROM inventory WHERE ProductID = ? AND StoreID = ?",
            [$productId, $storeId]
        );

        if (!$current || $current['Quantity'] < $quantity) {
            throw new Exception("Insufficient inventory for product {$productId}");
        }

        // Decrement inventory
        self::$db->update(
            "UPDATE inventory SET Quantity = Quantity - ? WHERE ProductID = ? AND StoreID = ?",
            [$quantity, $productId, $storeId]
        );

        // Record stock movement
        self::$db->insert(
            "INSERT INTO stockmovements (ProductID, StoreID, MovementType, Quantity, ReferenceType, Notes, CreatedBy)
             VALUES (?, ?, 'OUT', ?, 'Sale', 'Sale transaction', ?)",
            [$productId, $storeId, $quantity, $_SESSION['username'] ?? 'System']
        );
    }

    private static function incrementInventory($productId, $storeId, $quantity)
    {
        self::$db->execute(
            "INSERT INTO inventory (ProductID, StoreID, Quantity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE Quantity = Quantity + ?",
            [$productId, $storeId, $quantity, $quantity]
        );

        // Record stock movement
        self::$db->insert(
            "INSERT INTO stockmovements (ProductID, StoreID, MovementType, Quantity, ReferenceType, Notes, CreatedBy)
             VALUES (?, ?, 'IN', ?, 'Purchase', 'Goods received from purchase order', ?)",
            [$productId, $storeId, $quantity, $_SESSION['username'] ?? 'System']
        );
    }

    private static function createAccountsReceivable($saleId, $customerId, $amount)
    {
        return self::$db->insert(
            "INSERT INTO accountsreceivable (SaleID, CustomerID, AmountDue, PaidAmount, PaymentStatus, DueDate)
             VALUES (?, ?, ?, 0, 'Outstanding', DATE_ADD(NOW(), INTERVAL 30 DAY))",
            [$saleId, $customerId, $amount]
        );
    }

    private static function createAccountsPayable($receiptId, $supplierId, $amount)
    {
        return self::$db->insert(
            "INSERT INTO accountspayable (GoodsReceiptID, SupplierID, AmountDue, PaidAmount, PaymentStatus, DueDate)
             VALUES (?, ?, ?, 0, 'Outstanding', DATE_ADD(NOW(), INTERVAL 30 DAY))",
            [$receiptId, $supplierId, $amount]
        );
    }

    private static function recordSaleGLEntries($saleId, $amount, $storeId)
    {
        // Debit Accounts Receivable (or Cash)
        self::$db->insert(
            "INSERT INTO generalledger (StoreID, AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
             VALUES (?, 'Asset', 'Accounts Receivable', ?, 0, ?, 'Sale', 'Sale transaction')",
            [$storeId, $amount, $saleId]
        );

        // Credit Revenue
        self::$db->insert(
            "INSERT INTO generalledger (StoreID, AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
             VALUES (?, 'Revenue', 'Sales Revenue', 0, ?, ?, 'Sale', 'Sale transaction')",
            [$storeId, $amount, $saleId]
        );
    }

    private static function recordPurchaseGLEntries($poId, $amount, $storeId)
    {
        // Debit Inventory
        self::$db->insert(
            "INSERT INTO generalledger (StoreID, AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
             VALUES (?, 'Asset', 'Inventory', ?, 0, ?, 'Purchase', 'Goods received')",
            [$storeId, $amount, $poId]
        );

        // Credit Accounts Payable
        self::$db->insert(
            "INSERT INTO generalledger (StoreID, AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
             VALUES (?, 'Liability', 'Accounts Payable', 0, ?, ?, 'Purchase', 'Goods received')",
            [$storeId, $amount, $poId]
        );
    }

    private static function recordSalaryExpenseGL($payrollId, $components, $storeId)
    {
        $totalSalary = $components['base_salary'] + $components['allowances'];

        self::$db->insert(
            "INSERT INTO generalledger (StoreID, AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
             VALUES (?, 'Expense', 'Salary Expense', ?, 0, ?, 'Payroll', 'Employee salary')",
            [$storeId, $totalSalary, $payrollId]
        );
    }

    private static function recordTaxGL($payrollId, $components)
    {
        if ($components['taxes'] > 0) {
            self::$db->insert(
                "INSERT INTO generalledger (AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
                 VALUES ('Liability', 'Tax Payable', 0, ?, ?, 'Payroll', 'Employee tax withholding')",
                [$components['taxes'], $payrollId]
            );
        }
    }

    private static function recordBenefitsGL($payrollId, $components)
    {
        if ($components['benefits'] > 0) {
            self::$db->insert(
                "INSERT INTO generalledger (AccountType, AccountName, Debit, Credit, ReferenceID, ReferenceType, Description)
                 VALUES ('Expense', 'Benefits Expense', ?, 0, ?, 'Payroll', 'Employee benefits')",
                [$components['benefits'], $payrollId]
            );
        }
    }

    private static function updateCustomerLoyalty($customerId, $amount)
    {
        $points = floor($amount / 10); // 1 point per ₱10

        self::$db->execute(
            "UPDATE customers SET LoyaltyPoints = LoyaltyPoints + ? WHERE CustomerID = ?",
            [$points, $customerId]
        );
    }

    private static function calculateSalaryComponents($payrollData)
    {
        // Get employee salary details
        $employee = self::$db->fetchOne(
            "SELECT * FROM employees WHERE EmployeeID = ?",
            [$payrollData['employee_id']]
        );

        return [
            'base_salary' => $employee['BaseSalary'] ?? 0,
            'allowances' => $payrollData['allowances'] ?? 0,
            'taxes' => $payrollData['taxes'] ?? 0,
            'benefits' => $payrollData['benefits'] ?? 0,
            'deductions' => $payrollData['deductions'] ?? 0,
        ];
    }

    private static function createPayrollRecord($payrollData, $components)
    {
        $netSalary = $components['base_salary'] + $components['allowances'] - $components['taxes'] - $components['deductions'];

        return self::$db->insert(
            "INSERT INTO payroll (EmployeeID, Period, BaseSalary, Allowances, Taxes, Benefits, Deductions, NetSalary, Status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')",
            [
                $payrollData['employee_id'],
                $payrollData['period'],
                $components['base_salary'],
                $components['allowances'],
                $components['taxes'],
                $components['benefits'],
                $components['deductions'],
                $netSalary
            ]
        );
    }

    private static function createGoodsReceipt($poId, $receiptData)
    {
        return self::$db->insert(
            "INSERT INTO goodsreceipts (PurchaseOrderID, ReceiptDate, Notes, CreatedBy)
             VALUES (?, NOW(), ?, ?)",
            [
                $poId,
                $receiptData['notes'] ?? null,
                $_SESSION['username'] ?? 'System'
            ]
        );
    }
}

?>
