<?php
/**
 * Accounting Management API
 * Handles AR, AP, GL, and financial reporting
 */

require_once '../config/database.php';
require_once '../includes/core_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn() || !hasPermission('Accountant')) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'get_accounts_receivable':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            $status = $_GET['status'] ?? null;
            
            $query = "SELECT ar.*, s.CustomerName, s.SaleDate FROM v_outstanding_receivables ar 
                     WHERE ar.StoreID = ?";
            $params = [$storeId];
            
            if ($status) {
                $query .= " AND ar.PaymentStatus = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY ar.DaysOverdue DESC";
            $ar = dbFetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $ar]);
            break;

        case 'get_accounts_payable':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            $status = $_GET['status'] ?? null;
            
            $query = "SELECT ap.*, sup.SupplierName FROM AccountsPayable ap
                     JOIN PurchaseOrders po ON ap.PurchaseOrderID = po.PurchaseOrderID
                     JOIN Suppliers sup ON ap.SupplierID = sup.SupplierID
                     WHERE po.StoreID = ?";
            $params = [$storeId];
            
            if ($status) {
                $query .= " AND ap.PaymentStatus = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY ap.DueDate";
            $ap = dbFetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $ap]);
            break;

        case 'process_ar_payment':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            processARPayment($data['ar_id'], $data['amount'], $data['payment_method'] ?? 'Cash');
            
            jsonResponse(['success' => true, 'message' => 'Payment processed']);
            break;

        case 'process_ap_payment':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            getDB()->beginTransaction();
            
            $ap = dbFetchOne("SELECT * FROM AccountsPayable WHERE APID = ?", [$data['ap_id']]);
            if (!$ap) {
                throw new Exception("AP record not found");
            }
            
            $newPaidAmount = $ap['PaidAmount'] + $data['amount'];
            $status = $newPaidAmount >= $ap['AmountDue'] ? 'Paid' : 'Partial';
            
            dbUpdate("UPDATE AccountsPayable SET PaidAmount = ?, PaymentStatus = ?, PaymentDate = NOW() WHERE APID = ?",
                    [$newPaidAmount, $status, $data['ap_id']]);
            
            // Record in general ledger
            $po = dbFetchOne("SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?", [$ap['PurchaseOrderID']]);
            recordGeneralLedger('Liability', 'Accounts Payable', 'Supplier payment', 0, $data['amount'], 
                              $ap['PurchaseOrderID'], 'Payment', $po['StoreID']);
            
            getDB()->commit();
            
            jsonResponse(['success' => true, 'message' => 'Payment processed']);
            break;

        case 'get_general_ledger':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            $accountType = $_GET['account_type'] ?? null;
            
            $query = "SELECT * FROM GeneralLedger WHERE StoreID = ? AND DATE(CreatedAt) BETWEEN ? AND ?";
            $params = [$storeId, $startDate, $endDate];
            
            if ($accountType) {
                $query .= " AND AccountType = ?";
                $params[] = $accountType;
            }
            
            $query .= " ORDER BY CreatedAt DESC";
            $ledger = dbFetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $ledger]);
            break;

        case 'get_financial_summary':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $summary = getFinancialSummary($startDate, $endDate, $storeId);
            
            jsonResponse(['success' => true, 'data' => $summary]);
            break;

        case 'get_income_statement':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $query = "SELECT 
                        SUM(CASE WHEN AccountType = 'Revenue' THEN Debit ELSE 0 END) as revenue,
                        SUM(CASE WHEN AccountName LIKE '%Cost%' THEN Credit ELSE 0 END) as cogs,
                        SUM(CASE WHEN AccountType = 'Expense' THEN Credit ELSE 0 END) as expenses
                     FROM GeneralLedger 
                     WHERE StoreID = ? AND DATE(CreatedAt) BETWEEN ? AND ?";
            
            $result = dbFetchOne($query, [$storeId, $startDate, $endDate]);
            
            if (!$result) {
                $result = ['revenue' => 0, 'cogs' => 0, 'expenses' => 0];
            }
            
            $result['gross_profit'] = $result['revenue'] - $result['cogs'];
            $result['net_income'] = $result['gross_profit'] - $result['expenses'];
            
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'get_balance_sheet':
            $db = getDB();
            $query = "SELECT 
                        SUM(CASE WHEN AccountType = 'Asset' THEN Debit ELSE 0 END) as assets,
                        SUM(CASE WHEN AccountType = 'Liability' THEN Credit ELSE 0 END) as liabilities,
                        SUM(CASE WHEN AccountType = 'Equity' THEN Credit ELSE 0 END) as equity
                     FROM generalledger";
            
            $result = $db->fetchOne($query);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        case 'get_ar_aging':
            $db = getDB();
            $query = "SELECT 
                        SUM(CASE WHEN DaysOverdue < 30 THEN AmountDue - PaidAmount ELSE 0 END) as current,
                        SUM(CASE WHEN DaysOverdue BETWEEN 30 AND 60 THEN AmountDue - PaidAmount ELSE 0 END) as days_30_60,
                        SUM(CASE WHEN DaysOverdue BETWEEN 61 AND 90 THEN AmountDue - PaidAmount ELSE 0 END) as days_61_90,
                        SUM(CASE WHEN DaysOverdue > 90 THEN AmountDue - PaidAmount ELSE 0 END) as over_90
                     FROM accountsreceivable
                     WHERE PaymentStatus != 'Paid'";
            
            $aging = $db->fetchOne($query);
            jsonResponse(['success' => true, 'data' => $aging]);
            break;

        case 'export_financial_data':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $data = getFinancialSummary($startDate, $endDate, $storeId);
            exportToCSV($data, 'financial_report_' . date('Y-m-d'));
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Accounting API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
