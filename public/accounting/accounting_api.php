<?php
ob_clean();
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once '../../config/database.php';
require_once __DIR__ . '/accounting_functions.php';

$database = Database::getInstance();
$db = $database->getConnection();

$accountingManager = new AccountingManager($db);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    // ===== FINANCIAL SUMMARY =====
    case 'get_summary':
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $response = $accountingManager->getFinancialSummary($startDate, $endDate);
        echo json_encode($response);
        break;

    // ===== ACCOUNTS RECEIVABLE =====
    case 'get_receivables':
        $status = $_GET['status'] ?? null;
        $response = $accountingManager->getAccountsReceivable($status);
        echo json_encode($response);
        break;

    case 'record_receivable_payment':
        $arid = $_POST['arid'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
        $response = $accountingManager->recordReceivablePayment($arid, $amount, $paymentDate);
        echo json_encode($response);
        break;

    // ===== ACCOUNTS PAYABLE =====
    case 'get_payables':
        $status = $_GET['status'] ?? null;
        $response = $accountingManager->getAccountsPayable($status);
        echo json_encode($response);
        break;

    case 'record_payable_payment':
        $apid = $_POST['apid'] ?? 0;
        $amount = $_POST['amount'] ?? 0;
        $paymentDate = $_POST['payment_date'] ?? date('Y-m-d H:i:s');
        $response = $accountingManager->recordPayablePayment($apid, $amount, $paymentDate);
        echo json_encode($response);
        break;

    // ===== GENERAL LEDGER =====
    case 'get_ledger':
        $accountType = $_GET['account_type'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $response = $accountingManager->getGeneralLedger($accountType, $startDate, $endDate);
        echo json_encode($response);
        break;

    // ===== FINANCIAL REPORTS =====
    case 'get_income_statement':
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $response = $accountingManager->getIncomeStatement($startDate, $endDate);
        echo json_encode($response);
        break;

    case 'get_balance_sheet':
        $asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
        $response = $accountingManager->getBalanceSheet($asOfDate);
        echo json_encode($response);
        break;

    case 'get_tax_records':
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $response = $accountingManager->getTaxRecords($startDate, $endDate);
        echo json_encode($response);
        break;

    // ===== BUDGETS =====
    case 'get_budgets':
        $status = $_GET['status'] ?? null;
        $storeId = $_GET['store_id'] ?? null;
        $response = $accountingManager->getBudgets($status, $storeId);
        echo json_encode($response);
        break;

    case 'approve_budget':
        $budgetId = $_POST['budget_id'] ?? 0;
        $approvedAmount = $_POST['approved_amount'] ?? 0;
        $response = $accountingManager->approveBudget($budgetId, $approvedAmount);
        echo json_encode($response);
        break;

    case 'allocate_budget':
        $budgetId = $_POST['budget_id'] ?? 0;
        $response = $accountingManager->allocateBudget($budgetId);
        echo json_encode($response);
        break;

    // ===== PAYROLL =====
    case 'get_payroll_summary':
        $storeId = $_GET['store_id'] ?? null;
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $response = $accountingManager->getPayrollSummary($storeId, $month, $year);
        echo json_encode($response);
        break;

    case 'generate_payroll':
        $storeId = $_POST['store_id'] ?? null;
        $month = $_POST['month'] ?? date('m');
        $year = $_POST['year'] ?? date('Y');
        $response = $accountingManager->generatePayroll($storeId, $month, $year);
        echo json_encode($response);
        break;

    case 'process_payroll_payment':
        $payrollId = $_POST['payroll_id'] ?? null;
        $paymentDate = $_POST['payment_date'] ?? null;
        if ($payrollId) {
            echo json_encode($accountingManager->processPayrollPayment($payrollId, $paymentDate));
        } else {
            echo json_encode(['success' => false, 'message' => 'Payroll ID required']);
        }
        break;

    case 'get_department_payroll_summary':
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $response = $accountingManager->getDepartmentPayrollSummary($month, $year);
        echo json_encode($response);
        break;

    // ===== EMPLOYEE SALARIES & DEPARTMENTS =====
    case 'get_employee_salaries':
        $response = $accountingManager->getEmployeeSalaries();
        echo json_encode($response);
        break;

    case 'get_departments':
        $response = $accountingManager->getDepartments();
        echo json_encode($response);
        break;

    case 'get_salary_grades':
        $departmentId = $_GET['department_id'] ?? 0;
        if (!$departmentId) {
            echo json_encode(['success' => false, 'message' => 'Department ID required']);
            break;
        }
        $response = $accountingManager->getSalaryGradesByDepartment($departmentId);
        echo json_encode($response);
        break;

    case 'update_employee_salary':
        $employeeId = $_POST['employee_id'] ?? 0;
        $hourlyRate = $_POST['hourly_rate'] ?? 0;
        $gradeId = $_POST['grade_id'] ?? 0;
        $effectiveDate = $_POST['effective_date'] ?? date('Y-m-d');
        $notes = $_POST['notes'] ?? '';
        
        if (!$employeeId || !$hourlyRate || !$gradeId) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        $response = $accountingManager->updateEmployeeSalary($employeeId, $hourlyRate, $gradeId, $effectiveDate, $notes);
        echo json_encode($response);
        break;

    case 'get_salary_audit_log':
        $employeeId = $_GET['employee_id'] ?? null;
        $startDate = $_GET['start_date'] ?? null;
        $endDate = $_GET['end_date'] ?? null;
        $response = $accountingManager->getSalaryAuditLog($employeeId, $startDate, $endDate);
        echo json_encode($response);
        break;

    case 'add_department':
        $name = $_POST['name'] ?? '';
        $baseRate = $_POST['base_rate'] ?? 0;
        $description = $_POST['description'] ?? '';
        
        if (!$name || !$baseRate) {
            echo json_encode(['success' => false, 'message' => 'Name and base rate required']);
            break;
        }
        
        $response = $accountingManager->addDepartment($name, $baseRate, $description);
        echo json_encode($response);
        break;

    case 'get_stores':
        $stmt = $db->prepare("SELECT StoreID, StoreName FROM stores ORDER BY StoreName");
        $stmt->execute();
        $res = $stmt->get_result();
        $stores = [];
        while ($row = $res->fetch_assoc()) $stores[] = $row;
        echo json_encode(['success' => true, 'data' => $stores]);
        break;
        
    case 'add_salary_grade':
        $departmentId = $_POST['department_id'] ?? 0;
        $gradeName = $_POST['grade_name'] ?? '';
        $minRate = $_POST['min_rate'] ?? 0;
        $maxRate = $_POST['max_rate'] ?? 0;
        $description = $_POST['description'] ?? '';
        
        if (!$departmentId || !$gradeName || !$minRate || !$maxRate) {
            echo json_encode(['success' => false, 'message' => 'Missing required fields']);
            break;
        }
        
        if ($minRate >= $maxRate) {
            echo json_encode(['success' => false, 'message' => 'Min rate must be less than max rate']);
            break;
        }
        
        $response = $accountingManager->addSalaryGrade($departmentId, $gradeName, $minRate, $maxRate, $description);
        echo json_encode($response);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        break;
}
?>