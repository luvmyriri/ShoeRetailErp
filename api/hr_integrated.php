<?php
/**
 * HR Module API - Fully Integrated
 * Handles employee management, attendance, payroll, and leave requests
 * Integrates with: Accounting (GL), Core Functions
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/core_functions.php';
require_once '../includes/hr_functions.php';

header('Content-Type: application/json');

// Authentication & Authorization
if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

if (!hasPermission('Manager')) {
    jsonResponse(['success' => false, 'message' => 'Insufficient permissions'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        // ===== EMPLOYEE MANAGEMENT =====
        case 'get_employees':
            getEmployeesHR();
            break;
        case 'add_employee':
            addEmployeeHR();
            break;
        case 'update_employee':
            updateEmployeeHR();
            break;
        case 'get_employee':
            getEmployeeHR();
            break;
        
        // ===== ATTENDANCE =====
        case 'get_attendance':
            getAttendanceHR();
            break;
        case 'log_attendance':
            logAttendanceHR();
            break;
        case 'get_attendance_report':
            getAttendanceReportHR();
            break;
        
        // ===== LEAVE MANAGEMENT =====
        case 'request_leave':
            requestLeaveHR();
            break;
        case 'get_leave_requests':
            getLeaveRequestsHR();
            break;
        case 'approve_leave':
            approveLeaveHR();
            break;
        case 'reject_leave':
            rejectLeaveHR();
            break;
        case 'get_leave_balance':
            getLeaveBalanceHR();
            break;
        
        // ===== PAYROLL =====
        case 'process_payroll':
            processPayrollHR();
            break;
        case 'pay_payroll':
            payPayrollHR();
            break;
        case 'get_payroll_records':
            getPayrollRecordsHR();
            break;
        case 'get_employee_payroll':
            getEmployeePayrollHR();
            break;
        case 'search_employees':
            searchEmployeesHR();
            break;

        // ===== PAYROLL CRUD (manual entries) =====
        case 'create_payroll':
            createPayrollHR();
            break;
        case 'update_payroll':
            updatePayrollHR();
            break;
        case 'delete_payroll':
            deletePayrollHR();
            break;
        
        default:
            throw new Exception("Invalid action: {$action}");
    }
} catch (Exception $e) {
    logError("HR API Error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}

// ============================================
// EMPLOYEE MANAGEMENT FUNCTIONS
// ============================================

function getEmployeesHR() {
    $storeId = $_GET['store_id'] ?? null;
    $departmentId = $_GET['department_id'] ?? null;
    $status = $_GET['status'] ?? 'Active';
    $limit = $_GET['limit'] ?? 100;
    $offset = $_GET['offset'] ?? 0;
    
    try {
        $query = "SELECT e.*, s.StoreName, d.DepartmentName, b.BranchName\r
                  FROM Employees e \r
                  LEFT JOIN Stores s ON e.StoreID = s.StoreID\r
                  LEFT JOIN Departments d ON e.DepartmentID = d.DepartmentID\r
                  LEFT JOIN Branches b ON d.BranchID = b.BranchID\r
                  WHERE 1=1";
        
        $params = [];
        
        if ($storeId) {
            $query .= " AND e.StoreID = ?";
            $params[] = $storeId;
        }
        if ($departmentId) {
            $query .= " AND e.DepartmentID = ?";
            $params[] = $departmentId;
        }
        if ($status) {
            $query .= " AND e.Status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY e.FirstName, e.LastName LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $employees = dbFetchAll($query, $params);
        jsonResponse(['success' => true, 'data' => $employees]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getEmployeeHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    if (!$employeeId) {
        throw new Exception('Employee ID required');
    }
    
    try {
        $employee = dbFetchOne(\r
            "SELECT e.*, s.StoreName, d.DepartmentName FROM Employees e\r
             LEFT JOIN Stores s ON e.StoreID = s.StoreID\r
             LEFT JOIN Departments d ON e.DepartmentID = d.DepartmentID\r
             WHERE e.EmployeeID = ?",\r
            [$employeeId]\r
        );
        
        if (!$employee) {
            throw new Exception('Employee not found');
        }
        
        jsonResponse(['success' => true, 'data' => $employee]);
    } catch (Exception $e) {
        throw $e;
    }
}

function addEmployeeHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $required = ['first_name', 'last_name', 'email', 'hire_date', 'hourly_rate'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        // Check uniqueness
        $existing = dbFetchOne(\r
            "SELECT EmployeeID FROM Employees WHERE Email = ?",\r
            [$data['email']]\r
        );
        if ($existing) {
            throw new Exception('Email already exists');
        }
        
        $query = "INSERT INTO employees 
                  (FirstName, LastName, Email, Phone, HireDate, HourlyRate, Salary, 
                   DepartmentID, StoreID, Gender, MaritalStatus, Status, BirthDate, 
                   StreetAddress, City, ZipCode)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?)";
        
        $employeeId = dbInsert($query, [\r
            $data['first_name'],\r
            $data['last_name'],\r
            $data['email'],\r
            $data['phone'] ?? null,\r
            $data['hire_date'],\r
            $data['hourly_rate'],\r
            $data['salary'] ?? 0,\r
            $data['department_id'] ?? null,\r
            $data['store_id'] ?? $_SESSION['store_id'] ?? null,\r
            $data['gender'] ?? null,\r
            $data['marital_status'] ?? null,\r
            $data['birth_date'] ?? null,\r
            $data['street_address'] ?? null,\r
            $data['city'] ?? null,\r
            $data['zip_code'] ?? null\r
        ]);
        
        logInfo("Employee added", ['employee_id' => $employeeId, 'email' => $data['email']]);
        jsonResponse(['success' => true, 'message' => 'Employee added', 'employee_id' => $employeeId]);
    } catch (Exception $e) {
        throw $e;
    }
}

function updateEmployeeHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $employeeId = $data['employee_id'] ?? null;
        
        if (!$employeeId) {
            throw new Exception('Employee ID required');
        }
        
        $updates = [];
        $params = [];
        
        $updatableFields = ['first_name', 'last_name', 'phone', 'salary', 'hourly_rate', 
                           'status', 'department_id', 'gender', 'marital_status'];
        
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $dbField = ucfirst(str_replace('_', '', ucwords($field, '_')));
                $updates[] = "{$dbField} = ?";
                $params[] = $data[$field];
            }
        }
        
        if (empty($updates)) {
            throw new Exception('No fields to update');
        }
        
        $params[] = $employeeId;
        $query = "UPDATE Employees SET " . implode(", ", $updates) . " WHERE EmployeeID = ?";\r
        \r
        dbUpdate($query, $params);
        logInfo("Employee updated", ['employee_id' => $employeeId]);
        jsonResponse(['success' => true, 'message' => 'Employee updated']);
    } catch (Exception $e) {
        throw $e;
    }
}

// ============================================
// ATTENDANCE FUNCTIONS
// ============================================

function getAttendanceHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    $date = $_GET['date'] ?? null;
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    try {
        $query = "SELECT a.*, e.FirstName, e.LastName FROM Attendance a\r
                  JOIN Employees e ON a.EmployeeID = e.EmployeeID\r
                  WHERE 1=1";
        
        $params = [];
        
        if ($employeeId) {
            $query .= " AND a.EmployeeID = ?";
            $params[] = $employeeId;
        }
        
        if ($date) {
            $query .= " AND DATE(a.AttendanceDate) = ?";
            $params[] = $date;
        } else {
            $query .= " AND DATE(a.AttendanceDate) BETWEEN ? AND ?";
            $params[] = $startDate;
            $params[] = $endDate;
        }
        
        $query .= " ORDER BY a.AttendanceDate DESC";
        
        $records = dbFetchAll($query, $params);
        jsonResponse(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        throw $e;
    }
}

function logAttendanceHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if (!$data['employee_id'] || !$data['date']) {
            throw new Exception('Employee ID and date required');
        }
        
        $query = "INSERT INTO Attendance \r
                  (EmployeeID, AttendanceDate, LogInTime, LogOutTime, Notes)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    LogInTime = VALUES(LogInTime),
                    LogOutTime = VALUES(LogOutTime),
                    Notes = VALUES(Notes)";
        
        $attendanceId = dbInsert($query, [
            $data['employee_id'],
            $data['date'],
            $data['log_in_time'] ?? null,
            $data['log_out_time'] ?? null,
            $data['notes'] ?? null
        ]);
        
        logInfo("Attendance logged", ['employee_id' => $data['employee_id'], 'date' => $data['date']]);
        jsonResponse(['success' => true, 'message' => 'Attendance logged', 'attendance_id' => $attendanceId]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getAttendanceReportHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    
    try {
        $query = "SELECT e.EmployeeID, e.FirstName, e.LastName,\r
                         COUNT(CASE WHEN LogInTime IS NOT NULL THEN 1 END) as days_present,\r
                         COUNT(CASE WHEN LogInTime IS NULL THEN 1 END) as days_absent,\r
                         SUM(HoursWorked) as total_hours\r
                  FROM Employees e\r
                  LEFT JOIN Attendance a ON e.EmployeeID = a.EmployeeID \r
                                          AND DATE(a.AttendanceDate) BETWEEN ? AND ?\r
                  WHERE 1=1";
        
        $params = [$startDate, $endDate];
        
        if ($employeeId) {
            $query .= " AND e.EmployeeID = ?";
            $params[] = $employeeId;
        }
        
        $query .= " GROUP BY e.EmployeeID";
        
        $report = dbFetchAll($query, $params);
        jsonResponse(['success' => true, 'data' => $report]);
    } catch (Exception $e) {
        throw $e;
    }
}

// ============================================
// LEAVE MANAGEMENT FUNCTIONS
// ============================================

function requestLeaveHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        $required = ['employee_id', 'leave_type_id', 'start_date', 'end_date'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }
        
        $daysRequested = (strtotime($data['end_date']) - strtotime($data['start_date'])) / 86400 + 1;
        
        // Check leave balance
        $year = date('Y', strtotime($data['start_date']));
        $balance = dbFetchOne(\r
            "SELECT Remaining FROM LeaveBalances \r
             WHERE EmployeeID = ? AND LeaveTypeID = ? AND Year = ?",\r
            [$data['employee_id'], $data['leave_type_id'], $year]\r
        );
        
        if ($balance && $balance['Remaining'] < $daysRequested) {
            throw new Exception('Insufficient leave balance');
        }
        
        $query = "INSERT INTO LeaveRequests \r
                  (EmployeeID, LeaveTypeID, StartDate, EndDate, DaysRequested, Status, Comments)
                  VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
        
        $leaveRequestId = dbInsert($query, [
            $data['employee_id'],
            $data['leave_type_id'],
            $data['start_date'],
            $data['end_date'],
            $daysRequested,
            $data['comments'] ?? null
        ]);
        
        logInfo("Leave requested", ['leave_request_id' => $leaveRequestId, 'days' => $daysRequested]);
        jsonResponse(['success' => true, 'message' => 'Leave request submitted', 'leave_request_id' => $leaveRequestId]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getLeaveRequestsHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    $status = $_GET['status'] ?? null;
    
    try {
        $query = "SELECT lr.*, e.FirstName, e.LastName, lt.LeaveTypeName\r
                  FROM LeaveRequests lr\r
                  JOIN Employees e ON lr.EmployeeID = e.EmployeeID\r
                  JOIN LeaveTypes lt ON lr.LeaveTypeID = lt.LeaveTypeID\r
                  WHERE 1=1";
        
        $params = [];
        
        if ($employeeId) {
            $query .= " AND lr.EmployeeID = ?";
            $params[] = $employeeId;
        }
        if ($status) {
            $query .= " AND lr.Status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY lr.RequestDate DESC";
        
        $requests = dbFetchAll($query, $params);
        jsonResponse(['success' => true, 'data' => $requests]);
    } catch (Exception $e) {
        throw $e;
    }
}

function approveLeaveHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $leaveRequestId = $data['leave_request_id'] ?? null;
        
        if (!$leaveRequestId) {
            throw new Exception('Leave request ID required');
        }
        
        getDB()->beginTransaction();
        
        // Get leave request details
        $leave = dbFetchOne(\r
            "SELECT * FROM LeaveRequests WHERE LeaveRequestID = ?",\r
            [$leaveRequestId]\r
        );
        
        if (!$leave) {
            throw new Exception('Leave request not found');
        }
        
        // Update leave request
        dbUpdate(\r
            "UPDATE LeaveRequests SET Status = 'Approved', ApprovedBy = ? WHERE LeaveRequestID = ?",\r
            [$_SESSION['user_id'] ?? null, $leaveRequestId]\r
        );
        
        // Update leave balance
        $year = date('Y', strtotime($leave['StartDate']));
        dbUpdate(\r
            "UPDATE LeaveBalances SET Taken = Taken + ?, Remaining = Remaining - ? \r
             WHERE EmployeeID = ? AND LeaveTypeID = ? AND Year = ?",\r
            [$leave['DaysRequested'], $leave['DaysRequested'], \r
             $leave['EmployeeID'], $leave['LeaveTypeID'], $year]\r
        );
        
        getDB()->commit();
        
        logInfo("Leave approved", ['leave_request_id' => $leaveRequestId]);
        jsonResponse(['success' => true, 'message' => 'Leave request approved']);
    } catch (Exception $e) {
        getDB()->rollback();
        throw $e;
    }
}

function rejectLeaveHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $leaveRequestId = $data['leave_request_id'] ?? null;
        
        if (!$leaveRequestId) {
            throw new Exception('Leave request ID required');
        }
        
        dbUpdate(\r
            "UPDATE LeaveRequests SET Status = 'Rejected' WHERE LeaveRequestID = ?",\r
            [$leaveRequestId]\r
        );
        
        logInfo("Leave rejected", ['leave_request_id' => $leaveRequestId]);
        jsonResponse(['success' => true, 'message' => 'Leave request rejected']);
    } catch (Exception $e) {
        throw $e;
    }
}

function getLeaveBalanceHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    $year = $_GET['year'] ?? date('Y');
    
    try {
        if (!$employeeId) {
            throw new Exception('Employee ID required');
        }
        
        $balances = dbFetchAll(\r
            "SELECT lb.*, lt.LeaveTypeName FROM LeaveBalances lb\r
             JOIN LeaveTypes lt ON lb.LeaveTypeID = lt.LeaveTypeID\r
             WHERE lb.EmployeeID = ? AND lb.Year = ?\r
             ORDER BY lt.LeaveTypeName",\r
            [$employeeId, $year]\r
        );
        
        jsonResponse(['success' => true, 'data' => $balances]);
    } catch (Exception $e) {
        throw $e;
    }
}

// ============================================
// PAYROLL FUNCTIONS
// ============================================

function processPayrollHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    
    try {
        $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        
        if (!$data['pay_period_start'] || !$data['pay_period_end']) {
            throw new Exception('Pay period dates required');
        }
        
        getDB()->beginTransaction();
        
        // Get all active employees
        $employees = dbFetchAll(\r
            "SELECT EmployeeID, HourlyRate FROM Employees WHERE Status = 'Active'"\r
        );
        
        $processedCount = 0;
        foreach ($employees as $emp) {
            // Call stored procedure: GeneratePayroll
            try {
                dbExecute("CALL GeneratePayroll(?, ?, ?, ?)", [\r
                    $emp['EmployeeID'],\r
                    $data['pay_period_start'],\r
                    $data['pay_period_end'],\r
                    $data['deductions'] ?? 0\r
                ]);
                $processedCount++;
            } catch (Exception $e) {
                logError("Payroll generation failed for employee", ['employee_id' => $emp['EmployeeID']]);
            }
        }
        
        getDB()->commit();
        
        // Post GL accruals for generated payroll rows in this period to align with Accounting
        $rows = dbFetchAll(
            "SELECT PayrollID FROM Payroll WHERE PayPeriodStart = ? AND PayPeriodEnd = ? AND Status IN ('Pending','Draft')",
            [$data['pay_period_start'], $data['pay_period_end']]
        );
        $accrued = 0;
        foreach ($rows as $r) {
            try { postPayrollAccrual($r['PayrollID']); $accrued++; } catch (Exception $e) { logError('Payroll accrual post failed', ['payroll_id' => $r['PayrollID'], 'error' => $e->getMessage()]); }
        }
        
        logInfo("Payroll processed", ['period_start' => $data['pay_period_start'], 'employees' => $processedCount, 'accruals_posted' => $accrued]);
        jsonResponse([
            'success' => true, 
            'message' => "Payroll processed for {$processedCount} employees; accruals posted: {$accrued}",
            'processed_count' => $processedCount,
            'accruals_posted' => $accrued
        ]);
    } catch (Exception $e) {
        getDB()->rollback();
        throw $e;
    }
}

function getPayrollRecordsHR() {
    $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    
    try {
        $query = "SELECT p.*, e.FirstName, e.LastName FROM Payroll p\r
                  JOIN Employees e ON p.EmployeeID = e.EmployeeID\r
                  WHERE 1=1";
        
        $params = [];
        
        if ($startDate) {
            $query .= " AND DATE(p.PayPeriodStart) >= ?";
            $params[] = $startDate;
        }
        if ($endDate) {
            $query .= " AND DATE(p.PayPeriodEnd) <= ?";
            $params[] = $endDate;
        }
        if ($status) {
            $query .= " AND p.Status = ?";
            $params[] = $status;
        }
        
        $query .= " ORDER BY p.PayPeriodEnd DESC";
        
        $records = dbFetchAll($query, $params);
        jsonResponse(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        throw $e;
    }
}

function getEmployeePayrollHR() {
    $employeeId = $_GET['employee_id'] ?? null;
    
    try {
        if (!$employeeId) {
            throw new Exception('Employee ID required');
        }
        
        $payroll = dbFetchAll(
            "SELECT * FROM Payroll WHERE EmployeeID = ? ORDER BY PayPeriodEnd DESC",
            [$employeeId]
        );
        
        jsonResponse(['success' => true, 'data' => $payroll]);
    } catch (Exception $e) {
        throw $e;
    }
}

// ===== Payroll CRUD endpoints =====
function createPayrollHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $required = ['employee_id','pay_date','hours_worked','gross_pay','deductions'];
    foreach ($required as $f) { if (!isset($data[$f])) throw new Exception("Missing field: {$f}"); }
    $employeeId = (int)$data['employee_id'];
    $payDate = $data['pay_date'];
    $start = $data['pay_period_start'] ?? $payDate;
    $end = $data['pay_period_end'] ?? $payDate;
    $hours = (float)$data['hours_worked'];
    $gross = (float)$data['gross_pay'];
    $ded = (float)$data['deductions'];
    $bonus = (float)($data['bonuses'] ?? 0);
    $net = $gross - $ded + $bonus;
    $pid = dbInsert(
        "INSERT INTO Payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, GrossPay, Deductions, Bonuses, NetPay, Status, PaymentDate)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NULL)",
        [$employeeId, $start, $end, $hours, $gross, $ded, $bonus, $net]
    );
    jsonResponse(['success'=>true,'message'=>'Payroll created','payroll_id'=>$pid]);
}

function updatePayrollHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $pid = (int)($data['payroll_id'] ?? 0);
    if (!$pid) throw new Exception('payroll_id required');
    $row = dbFetchOne("SELECT * FROM Payroll WHERE PayrollID = ?", [$pid]);
    if (!$row) throw new Exception('Payroll not found');
    $hours = isset($data['hours_worked']) ? (float)$data['hours_worked'] : (float)$row['HoursWorked'];
    $gross = isset($data['gross_pay']) ? (float)$data['gross_pay'] : (float)$row['GrossPay'];
    $ded = isset($data['deductions']) ? (float)$data['deductions'] : (float)$row['Deductions'];
    $bonus = isset($data['bonuses']) ? (float)$data['bonuses'] : (float)$row['Bonuses'];
    $net = $gross - $ded + $bonus;
    dbUpdate("UPDATE Payroll SET HoursWorked = ?, GrossPay = ?, Deductions = ?, Bonuses = ?, NetPay = ? WHERE PayrollID = ?",
        [$hours, $gross, $ded, $bonus, $net, $pid]);
    jsonResponse(['success'=>true,'message'=>'Payroll updated']);
}

function deletePayrollHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $pid = (int)($data['payroll_id'] ?? 0);
    if (!$pid) throw new Exception('payroll_id required');
    dbUpdate("UPDATE Payroll SET Status = 'Voided' WHERE PayrollID = ?", [$pid]);
    jsonResponse(['success'=>true,'message'=>'Payroll voided']);
}

function searchEmployeesHR() {
    $q = trim($_GET['q'] ?? '');
    $limit = (int)($_GET['limit'] ?? 10);
    if ($limit <= 0 || $limit > 50) { $limit = 10; }
    if ($q === '') { jsonResponse(['success'=>true,'data'=>[]]); return; }
    $like = '%' . $q . '%';
    $rows = dbFetchAll(
        "SELECT e.EmployeeID, e.FirstName, e.LastName, e.Email, d.DepartmentName, s.StoreName
         FROM Employees e
         LEFT JOIN Departments d ON e.DepartmentID = d.DepartmentID
         LEFT JOIN Stores s ON e.StoreID = s.StoreID
         WHERE e.FirstName LIKE ? OR e.LastName LIKE ? OR e.Email LIKE ?
         ORDER BY e.FirstName, e.LastName LIMIT ?",
        [$like, $like, $like, $limit]
    );
    jsonResponse(['success'=>true,'data'=>$rows]);
}

?>
// ============================================
// PAYROLL PAYMENT FUNCTION
// ============================================
function payPayrollHR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }
    $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
    $ids = $data['payroll_ids'] ?? null;
    if (!$ids) {
        $id = $data['payroll_id'] ?? null;
        if ($id) { $ids = [$id]; }
    }
    if (!$ids || !is_array($ids)) {
        throw new Exception('payroll_ids (array) or payroll_id required');
    }
    $paid = 0; $errors = [];
    foreach ($ids as $pid) {
        try { processPayroll($pid); $paid++; } catch (Exception $e) { $errors[] = ['payroll_id' => $pid, 'error' => $e->getMessage()]; }
    }
    jsonResponse(['success' => true, 'message' => "Payroll paid: {$paid}", 'paid' => $paid, 'errors' => $errors]);
}

?>
