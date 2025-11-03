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
        case 'get_payroll_records':
            getPayrollRecordsHR();
            break;
        case 'get_employee_payroll':
            getEmployeePayrollHR();
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
        $query = "SELECT e.*, s.StoreName, d.DepartmentName, b.BranchName
                  FROM employees e 
                  LEFT JOIN stores s ON e.StoreID = s.StoreID
                  LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                  LEFT JOIN branches b ON d.BranchID = b.BranchID
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
        
        $employees = getDB()->fetchAll($query, $params);
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
        $employee = getDB()->fetchOne(
            "SELECT e.*, s.StoreName, d.DepartmentName FROM employees e
             LEFT JOIN stores s ON e.StoreID = s.StoreID
             LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
             WHERE e.EmployeeID = ?",
            [$employeeId]
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
        $existing = getDB()->fetchOne(
            "SELECT EmployeeID FROM employees WHERE Email = ?",
            [$data['email']]
        );
        if ($existing) {
            throw new Exception('Email already exists');
        }
        
        $query = "INSERT INTO employees 
                  (FirstName, LastName, Email, Phone, HireDate, HourlyRate, Salary, 
                   DepartmentID, StoreID, Gender, MaritalStatus, Status, BirthDate, 
                   StreetAddress, City, ZipCode)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, ?, ?, ?)";
        
        $employeeId = getDB()->insert($query, [
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'] ?? null,
            $data['hire_date'],
            $data['hourly_rate'],
            $data['salary'] ?? 0,
            $data['department_id'] ?? null,
            $data['store_id'] ?? $_SESSION['store_id'] ?? null,
            $data['gender'] ?? null,
            $data['marital_status'] ?? null,
            $data['birth_date'] ?? null,
            $data['street_address'] ?? null,
            $data['city'] ?? null,
            $data['zip_code'] ?? null
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
        $query = "UPDATE employees SET " . implode(", ", $updates) . " WHERE EmployeeID = ?";
        
        getDB()->update($query, $params);
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
        $query = "SELECT a.*, e.FirstName, e.LastName FROM attendance a
                  JOIN employees e ON a.EmployeeID = e.EmployeeID
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
        
        $records = getDB()->fetchAll($query, $params);
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
        
        $query = "INSERT INTO attendance 
                  (EmployeeID, AttendanceDate, LogInTime, LogOutTime, Notes)
                  VALUES (?, ?, ?, ?, ?)
                  ON DUPLICATE KEY UPDATE 
                    LogInTime = VALUES(LogInTime),
                    LogOutTime = VALUES(LogOutTime),
                    Notes = VALUES(Notes)";
        
        $attendanceId = getDB()->insert($query, [
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
        $query = "SELECT e.EmployeeID, e.FirstName, e.LastName,
                         COUNT(CASE WHEN LogInTime IS NOT NULL THEN 1 END) as days_present,
                         COUNT(CASE WHEN LogInTime IS NULL THEN 1 END) as days_absent,
                         SUM(HoursWorked) as total_hours
                  FROM employees e
                  LEFT JOIN attendance a ON e.EmployeeID = a.EmployeeID 
                                          AND DATE(a.AttendanceDate) BETWEEN ? AND ?
                  WHERE 1=1";
        
        $params = [$startDate, $endDate];
        
        if ($employeeId) {
            $query .= " AND e.EmployeeID = ?";
            $params[] = $employeeId;
        }
        
        $query .= " GROUP BY e.EmployeeID";
        
        $report = getDB()->fetchAll($query, $params);
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
        $balance = getDB()->fetchOne(
            "SELECT Remaining FROM leavebalances 
             WHERE EmployeeID = ? AND LeaveTypeID = ? AND Year = ?",
            [$data['employee_id'], $data['leave_type_id'], $year]
        );
        
        if ($balance && $balance['Remaining'] < $daysRequested) {
            throw new Exception('Insufficient leave balance');
        }
        
        $query = "INSERT INTO leaverequests 
                  (EmployeeID, LeaveTypeID, StartDate, EndDate, DaysRequested, Status, Comments)
                  VALUES (?, ?, ?, ?, ?, 'Pending', ?)";
        
        $leaveRequestId = getDB()->insert($query, [
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
        $query = "SELECT lr.*, e.FirstName, e.LastName, lt.LeaveTypeName
                  FROM leaverequests lr
                  JOIN employees e ON lr.EmployeeID = e.EmployeeID
                  JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
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
        
        $requests = getDB()->fetchAll($query, $params);
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
        $leave = getDB()->fetchOne(
            "SELECT * FROM leaverequests WHERE LeaveRequestID = ?",
            [$leaveRequestId]
        );
        
        if (!$leave) {
            throw new Exception('Leave request not found');
        }
        
        // Update leave request
        getDB()->update(
            "UPDATE leaverequests SET Status = 'Approved', ApprovedBy = ? WHERE LeaveRequestID = ?",
            [$_SESSION['user_id'] ?? null, $leaveRequestId]
        );
        
        // Update leave balance
        $year = date('Y', strtotime($leave['StartDate']));
        getDB()->update(
            "UPDATE leavebalances SET Taken = Taken + ?, Remaining = Remaining - ? 
             WHERE EmployeeID = ? AND LeaveTypeID = ? AND Year = ?",
            [$leave['DaysRequested'], $leave['DaysRequested'], 
             $leave['EmployeeID'], $leave['LeaveTypeID'], $year]
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
        
        getDB()->update(
            "UPDATE leaverequests SET Status = 'Rejected' WHERE LeaveRequestID = ?",
            [$leaveRequestId]
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
        
        $balances = getDB()->fetchAll(
            "SELECT lb.*, lt.LeaveTypeName FROM leavebalances lb
             JOIN leavetypes lt ON lb.LeaveTypeID = lt.LeaveTypeID
             WHERE lb.EmployeeID = ? AND lb.Year = ?
             ORDER BY lt.LeaveTypeName",
            [$employeeId, $year]
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
        $employees = getDB()->fetchAll(
            "SELECT EmployeeID, HourlyRate FROM employees WHERE Status = 'Active'"
        );
        
        $processedCount = 0;
        foreach ($employees as $emp) {
            // Call stored procedure: GeneratePayroll
            try {
                getDB()->callProcedure('GeneratePayroll', [
                    $emp['EmployeeID'],
                    $data['pay_period_start'],
                    $data['pay_period_end'],
                    $data['deductions'] ?? 0
                ]);
                $processedCount++;
            } catch (Exception $e) {
                logError("Payroll generation failed for employee", ['employee_id' => $emp['EmployeeID']]);
            }
        }
        
        getDB()->commit();
        
        logInfo("Payroll processed", ['period_start' => $data['pay_period_start'], 'employees' => $processedCount]);
        jsonResponse([
            'success' => true, 
            'message' => "Payroll processed for {$processedCount} employees",
            'processed_count' => $processedCount
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
        $query = "SELECT p.*, e.FirstName, e.LastName FROM payroll p
                  JOIN employees e ON p.EmployeeID = e.EmployeeID
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
        
        $records = getDB()->fetchAll($query, $params);
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
        
        $payroll = getDB()->fetchAll(
            "SELECT * FROM payroll WHERE EmployeeID = ? ORDER BY PayPeriodEnd DESC",
            [$employeeId]
        );
        
        jsonResponse(['success' => true, 'data' => $payroll]);
    } catch (Exception $e) {
        throw $e;
    }
}

?>
