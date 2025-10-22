<?php
/**
 * HR Module API
 * Handles employee management, timesheets, payroll, and leave requests
 */

session_start();
require_once '../config/database.php';
require_once '../includes/core_functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Check request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? null;

// Route to appropriate handler
switch ($action) {
    // Employee Management
    case 'get_employees':
        getEmployees();
        break;
    case 'add_employee':
        addEmployee();
        break;
    case 'update_employee':
        updateEmployee();
        break;
    case 'delete_employee':
        deleteEmployee();
        break;
    
    // Timesheet Management
    case 'get_timesheets':
        getTimesheets();
        break;
    case 'submit_timesheet':
        submitTimesheet();
        break;
    case 'approve_timesheet':
        approveTimesheet();
        break;
    
    // Payroll Management
    case 'process_payroll':
        processPayroll();
        break;
    case 'get_payroll_records':
        getPayrollRecords();
        break;
    
    // Leave Management
    case 'request_leave':
        requestLeave();
        break;
    case 'approve_leave':
        approveLeave();
        break;
    case 'reject_leave':
        rejectLeave();
        break;
    
    // Role Assignment
    case 'assign_role':
        assignRole();
        break;
    
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

// ===== Employee Management Functions =====

function getEmployees() {
    global $pdo;
    
    try {
        $storeId = $_GET['store_id'] ?? null;
        $status = $_GET['status'] ?? 'Active';
        
        $query = "SELECT e.*, s.StoreName 
                  FROM Employees e 
                  LEFT JOIN Stores s ON e.StoreID = s.StoreID";
        
        $conditions = [];
        if ($storeId) {
            $conditions[] = "e.StoreID = " . intval($storeId);
        }
        if ($status) {
            $conditions[] = "e.Status = '" . $pdo->quote($status) . "'";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $stmt = $pdo->query($query);
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $employees]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addEmployee() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = [
            'first_name' => $_POST['first_name'] ?? null,
            'last_name' => $_POST['last_name'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'hire_date' => $_POST['hire_date'] ?? null,
            'salary' => $_POST['salary'] ?? 0,
            'store_id' => $_POST['store_id'] ?? null,
        ];
        
        // Validate required fields
        if (!$data['first_name'] || !$data['last_name'] || !$data['email'] || !$data['hire_date']) {
            throw new Exception('Missing required fields');
        }
        
        // Check email uniqueness
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM Employees WHERE Email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email already exists');
        }
        
        // Insert employee
        $stmt = $pdo->prepare("
            INSERT INTO Employees (FirstName, LastName, Email, Phone, HireDate, Salary, StoreID, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'Active')
        ");
        
        $result = $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['email'],
            $data['phone'],
            $data['hire_date'],
            $data['salary'],
            $data['store_id']
        ]);
        
        if ($result) {
            $employeeId = $pdo->lastInsertId();
            echo json_encode([
                'success' => true,
                'message' => 'Employee added successfully',
                'employee_id' => $employeeId
            ]);
        } else {
            throw new Exception('Failed to add employee');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateEmployee() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $employeeId = $_POST['employee_id'] ?? null;
        if (!$employeeId) {
            throw new Exception('Employee ID required');
        }
        
        $updates = [];
        $values = [];
        
        if (isset($_POST['first_name'])) {
            $updates[] = "FirstName = ?";
            $values[] = $_POST['first_name'];
        }
        if (isset($_POST['last_name'])) {
            $updates[] = "LastName = ?";
            $values[] = $_POST['last_name'];
        }
        if (isset($_POST['phone'])) {
            $updates[] = "Phone = ?";
            $values[] = $_POST['phone'];
        }
        if (isset($_POST['salary'])) {
            $updates[] = "Salary = ?";
            $values[] = $_POST['salary'];
        }
        if (isset($_POST['status'])) {
            $updates[] = "Status = ?";
            $values[] = $_POST['status'];
        }
        
        if (empty($updates)) {
            throw new Exception('No fields to update');
        }
        
        $values[] = $employeeId;
        $query = "UPDATE Employees SET " . implode(", ", $updates) . " WHERE EmployeeID = ?";
        
        $stmt = $pdo->prepare($query);
        $result = $stmt->execute($values);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Employee updated successfully']);
        } else {
            throw new Exception('Failed to update employee');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteEmployee() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $employeeId = $_POST['employee_id'] ?? null;
        if (!$employeeId) {
            throw new Exception('Employee ID required');
        }
        
        // Soft delete - set status to Inactive
        $stmt = $pdo->prepare("UPDATE Employees SET Status = 'Inactive' WHERE EmployeeID = ?");
        $result = $stmt->execute([$employeeId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Employee deactivated successfully']);
        } else {
            throw new Exception('Failed to deactivate employee');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ===== Timesheet Functions =====

function getTimesheets() {
    global $pdo;
    
    try {
        $employeeId = $_GET['employee_id'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT t.*, e.FirstName, e.LastName, s.StoreName 
                  FROM Timesheets t 
                  JOIN Employees e ON t.EmployeeID = e.EmployeeID 
                  LEFT JOIN Stores s ON e.StoreID = s.StoreID";
        
        $conditions = [];
        if ($employeeId) {
            $conditions[] = "t.EmployeeID = " . intval($employeeId);
        }
        if ($status) {
            $conditions[] = "t.Status = '" . $pdo->quote($status) . "'";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY t.WorkDate DESC";
        
        $stmt = $pdo->query($query);
        $timesheets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $timesheets]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function submitTimesheet() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'work_date' => $_POST['work_date'] ?? null,
            'hours_worked' => $_POST['hours_worked'] ?? 0,
            'clock_in' => $_POST['clock_in'] ?? null,
            'clock_out' => $_POST['clock_out'] ?? null,
        ];
        
        if (!$data['employee_id'] || !$data['work_date']) {
            throw new Exception('Missing required fields');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO Timesheets (EmployeeID, WorkDate, HoursWorked, ClockIn, ClockOut, Status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ");
        
        $result = $stmt->execute([
            $data['employee_id'],
            $data['work_date'],
            $data['hours_worked'],
            $data['clock_in'],
            $data['clock_out']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Timesheet submitted successfully']);
        } else {
            throw new Exception('Failed to submit timesheet');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function approveTimesheet() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $timesheetId = $_POST['timesheet_id'] ?? null;
        if (!$timesheetId) {
            throw new Exception('Timesheet ID required');
        }
        
        $stmt = $pdo->prepare("UPDATE Timesheets SET Status = 'Approved' WHERE TimesheetID = ?");
        $result = $stmt->execute([$timesheetId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Timesheet approved successfully']);
        } else {
            throw new Exception('Failed to approve timesheet');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ===== Payroll Functions =====

function processPayroll() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $payPeriodStart = $_POST['pay_period_start'] ?? null;
        $payPeriodEnd = $_POST['pay_period_end'] ?? null;
        
        if (!$payPeriodStart || !$payPeriodEnd) {
            throw new Exception('Pay period dates required');
        }
        
        // Get all active employees
        $stmt = $pdo->query("SELECT EmployeeID, Salary FROM Employees WHERE Status = 'Active'");
        $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $processed = 0;
        foreach ($employees as $emp) {
            // Calculate gross pay, deductions, etc.
            $grossPay = $emp['Salary'];
            $deductions = $grossPay * 0.12; // Example: 12% deductions
            $netPay = $grossPay - $deductions;
            
            // Create payroll record
            $insertStmt = $pdo->prepare("
                INSERT INTO Payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, GrossPay, Deductions, NetPay, Status)
                VALUES (?, ?, ?, ?, ?, ?, 'Pending')
            ");
            
            $result = $insertStmt->execute([
                $emp['EmployeeID'],
                $payPeriodStart,
                $payPeriodEnd,
                $grossPay,
                $deductions,
                $netPay
            ]);
            
            if ($result) $processed++;
        }
        
        echo json_encode([
            'success' => true,
            'message' => "Payroll processed for {$processed} employees",
            'processed_count' => $processed
        ]);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getPayrollRecords() {
    global $pdo;
    
    try {
        $employeeId = $_GET['employee_id'] ?? null;
        $status = $_GET['status'] ?? null;
        
        $query = "SELECT p.*, e.FirstName, e.LastName 
                  FROM Payroll p 
                  JOIN Employees e ON p.EmployeeID = e.EmployeeID";
        
        $conditions = [];
        if ($employeeId) {
            $conditions[] = "p.EmployeeID = " . intval($employeeId);
        }
        if ($status) {
            $conditions[] = "p.Status = '" . $pdo->quote($status) . "'";
        }
        
        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }
        
        $query .= " ORDER BY p.PayPeriodEnd DESC";
        
        $stmt = $pdo->query($query);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $records]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ===== Leave Management Functions =====

function requestLeave() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'leave_type' => $_POST['leave_type'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'reason' => $_POST['reason'] ?? null,
        ];
        
        if (!$data['employee_id'] || !$data['leave_type'] || !$data['start_date'] || !$data['end_date']) {
            throw new Exception('Missing required fields');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO LeaveRequests (EmployeeID, LeaveType, StartDate, EndDate, Reason, Status, CreatedAt)
            VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        
        $result = $stmt->execute([
            $data['employee_id'],
            $data['leave_type'],
            $data['start_date'],
            $data['end_date'],
            $data['reason']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Leave request submitted successfully']);
        } else {
            throw new Exception('Failed to submit leave request');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function approveLeave() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $leaveRequestId = $_POST['leave_request_id'] ?? null;
        if (!$leaveRequestId) {
            throw new Exception('Leave request ID required');
        }
        
        $stmt = $pdo->prepare("UPDATE LeaveRequests SET Status = 'Approved' WHERE LeaveRequestID = ?");
        $result = $stmt->execute([$leaveRequestId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Leave request approved successfully']);
        } else {
            throw new Exception('Failed to approve leave request');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function rejectLeave() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $leaveRequestId = $_POST['leave_request_id'] ?? null;
        if (!$leaveRequestId) {
            throw new Exception('Leave request ID required');
        }
        
        $stmt = $pdo->prepare("UPDATE LeaveRequests SET Status = 'Rejected' WHERE LeaveRequestID = ?");
        $result = $stmt->execute([$leaveRequestId]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Leave request rejected']);
        } else {
            throw new Exception('Failed to reject leave request');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// ===== Role Assignment Function =====

function assignRole() {
    global $pdo;
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
        return;
    }
    
    try {
        $data = [
            'employee_id' => $_POST['employee_id'] ?? null,
            'role_id' => $_POST['role_id'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
        ];
        
        if (!$data['employee_id'] || !$data['role_id'] || !$data['start_date']) {
            throw new Exception('Missing required fields');
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO EmployeeRoles (EmployeeID, RoleID, StartDate, EndDate, IsActive)
            VALUES (?, ?, ?, ?, 'Yes')
        ");
        
        $result = $stmt->execute([
            $data['employee_id'],
            $data['role_id'],
            $data['start_date'],
            $data['end_date']
        ]);
        
        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Role assigned successfully']);
        } else {
            throw new Exception('Failed to assign role');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
?>
