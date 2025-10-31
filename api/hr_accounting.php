<?php
/**
 * HR and Accounting Management API
 * Handles employees, roles, payroll, and financial transactions
 */

require_once '../includes/db_helper.php';
require_once '../includes/core_functions.php';
require_once '../includes/role_management_functions.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        // ==================== HR OPERATIONS ====================
        
        case 'add_employee':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_manage_employees')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $required = ['first_name', 'last_name', 'email', 'phone', 'salary'];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check duplicate email/phone
            $existing = dbFetchOne(
                "SELECT EmployeeID FROM Employees WHERE Email = ? OR Phone = ?",
                [$data['email'], $data['phone']]
            );
            
            if ($existing) {
                throw new Exception('Employee with this email or phone already exists');
            }
            
            $employeeId = dbInsert(
                "INSERT INTO Employees (FirstName, LastName, Email, Phone, Salary, StoreID, Status) 
                 VALUES (?, ?, ?, ?, ?, ?, 'Active')",
                [$data['first_name'], $data['last_name'], $data['email'], $data['phone'], 
                 $data['salary'], $data['store_id'] ?? 1]
            );
            
            logInfo("Employee added", ['employee_id' => $employeeId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Employee added successfully',
                'employee_id' => $employeeId
            ]);
            break;
        
        case 'get_employees':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $query = "SELECT * FROM Employees WHERE Status = 'Active'";
            $params = [];
            
            if ($storeId) {
                $query .= " AND StoreID = ?";
                $params[] = $storeId;
            }
            
            $query .= " ORDER BY FirstName, LastName";
            $employees = dbFetchAll($query, $params);
            $paginated = array_slice($employees, $offset, $limit);
            
            jsonResponse([
                'success' => true,
                'data' => $paginated,
                'total' => count($employees)
            ]);
            break;
        
        case 'assign_role':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_assign_roles')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $result = assignRoleToEmployee($data['employee_id'], $data['role_id'], 
                                         $data['start_date'] ?? null, 
                                         $data['end_date'] ?? null);
            
            if ($result['success']) {
                jsonResponse([
                    'success' => true,
                    'message' => $result['message'],
                    'assignment_id' => $result['assignmentID']
                ]);
            } else {
                throw new Exception($result['message']);
            }
            break;
        
        case 'get_employee_roles':
            $employeeId = $_GET['employee_id'] ?? null;
            if (!$employeeId) {
                throw new Exception('Employee ID required');
            }
            
            $roles = getEmployeeRoles($employeeId, true);
            
            jsonResponse([
                'success' => true,
                'data' => $roles
            ]);
            break;
        
        case 'get_roles':
            $roles = getAllRoles(true);
            
            jsonResponse([
                'success' => true,
                'data' => $roles
            ]);
            break;
        
        case 'record_timesheet':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_manage_employees')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $required = ['employee_id', 'work_date', 'hours_worked'];
            
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            $timesheetId = dbInsert(
                "INSERT INTO Timesheets (EmployeeID, WorkDate, HoursWorked) VALUES (?, ?, ?)",
                [$data['employee_id'], $data['work_date'], $data['hours_worked']]
            );
            
            logInfo("Timesheet recorded", ['timesheet_id' => $timesheetId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Timesheet recorded successfully',
                'timesheet_id' => $timesheetId
            ]);
            break;
        
        case 'get_timesheets':
            $employeeId = $_GET['employee_id'] ?? null;
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $query = "SELECT * FROM Timesheets WHERE WorkDate BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($employeeId) {
                $query .= " AND EmployeeID = ?";
                $params[] = $employeeId;
            }
            
            $query .= " ORDER BY WorkDate DESC";
            $timesheets = dbFetchAll($query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $timesheets
            ]);
            break;
        
        // ==================== ACCOUNTING OPERATIONS ====================
        
        case 'record_ledger_entry':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_manage_ledger')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $required = ['account_type', 'account_name', 'description', 'debit', 'credit'];
            
            foreach ($required as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            $ledgerId = recordGeneralLedger(
                $data['account_type'],
                $data['account_name'],
                $data['description'],
                $data['debit'],
                $data['credit'],
                $data['reference_id'] ?? null,
                $data['reference_type'] ?? null,
                $data['store_id'] ?? $_SESSION['store_id']
            );
            
            logInfo("Ledger entry recorded", ['ledger_id' => $ledgerId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Ledger entry recorded successfully',
                'ledger_id' => $ledgerId
            ]);
            break;
        
        case 'get_general_ledger':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $accountType = $_GET['account_type'] ?? null;
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $query = "SELECT * FROM GeneralLedger WHERE TransactionDate BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
            
            if ($accountType) {
                $query .= " AND AccountType = ?";
                $params[] = $accountType;
            }
            
            if ($storeId) {
                $query .= " AND StoreID = ?";
                $params[] = $storeId;
            }
            
            $query .= " ORDER BY TransactionDate DESC";
            $entries = dbFetchAll($query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $entries
            ]);
            break;
        
        case 'get_accounts_receivable':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $query = "SELECT ar.*, c.FirstName, c.LastName, s.StoreID 
                      FROM AccountsReceivable ar 
                      JOIN Customers c ON ar.CustomerID = c.CustomerID 
                      JOIN Sales s ON ar.SaleID = s.SaleID 
                      WHERE 1=1";
            $params = [];
            
            if ($storeId) {
                $query .= " AND s.StoreID = ?";
                $params[] = $storeId;
            }
            
            $query .= " ORDER BY ar.DueDate ASC";
            $receivables = dbFetchAll($query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $receivables
            ]);
            break;
        
        case 'process_ar_payment':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_process_ar_ap')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $arId = $data['ar_id'] ?? null;
            
            if (!$arId) {
                throw new Exception('AR ID required');
            }
            
            processARPayment($arId, $data['amount'], $data['payment_method'] ?? 'Cash');
            
            logInfo("AR payment processed", ['ar_id' => $arId, 'amount' => $data['amount']]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Payment processed successfully'
            ]);
            break;
        
        case 'get_accounts_payable':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $query = "SELECT ap.*, sup.SupplierName 
                      FROM AccountsPayable ap 
                      JOIN Suppliers sup ON ap.SupplierID = sup.SupplierID 
                      JOIN PurchaseOrders po ON ap.PurchaseOrderID = po.PurchaseOrderID 
                      WHERE 1=1";
            $params = [];
            
            if ($storeId) {
                $query .= " AND po.StoreID = ?";
                $params[] = $storeId;
            }
            
            $query .= " ORDER BY ap.DueDate ASC";
            $payables = dbFetchAll($query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $payables
            ]);
            break;
        
        case 'get_financial_summary':
            if (!hasPermission($_SESSION['user_id'], 'can_generate_financial_reports')) {
                throw new Exception('Unauthorized');
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $summary = getFinancialSummary($startDate, $endDate, $storeId);
            
            jsonResponse([
                'success' => true,
                'data' => $summary
            ]);
            break;
        
        case 'export_ledger':
            if (!hasPermission($_SESSION['user_id'], 'can_generate_financial_reports')) {
                throw new Exception('Unauthorized');
            }
            
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            
            $data = getFinancialSummary($startDate, $endDate);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="general_ledger_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Date', 'Account Type', 'Account Name', 'Description', 'Debit', 'Credit']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['TransactionDate'],
                    $row['AccountType'],
                    $row['AccountName'],
                    $row['Description'],
                    $row['Debit'],
                    $row['Credit']
                ]);
            }
            
            fclose($output);
            exit;
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("HR/Accounting API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
