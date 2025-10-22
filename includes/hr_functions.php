<?php
/**
 * HR Module Functions for Shoe Retail ERP System
 * Includes employee management, role assignment, role distribution, and payroll functions
 * Author: Generated for PHP/MySQL Implementation
 * Date: 2024
 */

require_once __DIR__ . '/../config/database.php';

// ==============================================
// EMPLOYEE MANAGEMENT FUNCTIONS
// ==============================================

/**
 * Add new employee
 */
function addEmployee($data) {
    try {
        $query = "
            INSERT INTO Employees (FirstName, LastName, Email, Phone, HireDate, Salary, HourlyRate, StoreID, Status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";
        
        $employeeId = dbInsert($query, [
            $data['first_name'], $data['last_name'], $data['email'], 
            $data['phone'], $data['hire_date'], $data['salary'], 
            $data['hourly_rate'] ?? null, $data['store_id'], 
            $data['status'] ?? 'Active'
        ]);
        
        logInfo("Employee added successfully", ['employee_id' => $employeeId, 'email' => $data['email']]);
        return $employeeId;
    } catch (Exception $e) {
        logError("Failed to add employee", ['error' => $e->getMessage(), 'data' => $data]);
        throw $e;
    }
}

/**
 * Get all employees
 */
function getAllEmployees($storeId = null, $status = 'Active', $searchTerm = null) {
    $query = "
        SELECT e.*, s.StoreName,
               GROUP_CONCAT(DISTINCT r.RoleName SEPARATOR ', ') AS Roles
        FROM Employees e
        LEFT JOIN Stores s ON e.StoreID = s.StoreID
        LEFT JOIN EmployeeRoles er ON e.EmployeeID = er.EmployeeID AND er.IsActive = 'Yes'
        LEFT JOIN Roles r ON er.RoleID = r.RoleID
        WHERE 1=1
    ";
    
    $params = [];
    
    if ($status) {
        $query .= " AND e.Status = ?";
        $params[] = $status;
    }
    
    if ($storeId) {
        $query .= " AND e.StoreID = ?";
        $params[] = $storeId;
    }
    
    if ($searchTerm) {
        $query .= " AND (e.FirstName LIKE ? OR e.LastName LIKE ? OR e.Email LIKE ?)";
        $searchTerm = "%{$searchTerm}%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $query .= " GROUP BY e.EmployeeID ORDER BY e.FirstName, e.LastName";
    
    return dbFetchAll($query, $params);
}

/**
 * Get single employee details
 */
function getEmployee($employeeId) {
    return dbFetchOne("SELECT * FROM Employees WHERE EmployeeID = ?", [$employeeId]);
}

/**
 * Update employee information
 */
function updateEmployee($employeeId, $data) {
    try {
        $query = "
            UPDATE Employees SET 
                FirstName = ?, LastName = ?, Phone = ?, Salary = ?, 
                HourlyRate = ?, StoreID = ?, Status = ?
            WHERE EmployeeID = ?
        ";
        
        $affected = dbUpdate($query, [
            $data['first_name'], $data['last_name'], $data['phone'], 
            $data['salary'], $data['hourly_rate'] ?? null, 
            $data['store_id'] ?? null, $data['status'] ?? 'Active',
            $employeeId
        ]);
        
        logInfo("Employee updated successfully", ['employee_id' => $employeeId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to update employee", ['error' => $e->getMessage(), 'employee_id' => $employeeId]);
        throw $e;
    }
}

/**
 * Get employee summary with roles
 */
function getEmployeeSummary() {
    return dbFetchAll("SELECT * FROM v_employee_summary ORDER BY EmployeeName");
}

// ==============================================
// ROLE MANAGEMENT FUNCTIONS
// ==============================================

/**
 * Create new role
 */
function createRole($data) {
    try {
        $permissions = isset($data['permissions']) ? json_encode($data['permissions']) : '{}';
        
        $query = "
            INSERT INTO Roles (RoleName, Description, Permissions, IsActive)
            VALUES (?, ?, ?, ?)
        ";
        
        $roleId = dbInsert($query, [
            $data['role_name'], $data['description'] ?? null, 
            $permissions, $data['is_active'] ?? 'Yes'
        ]);
        
        logInfo("Role created successfully", ['role_id' => $roleId, 'role_name' => $data['role_name']]);
        return $roleId;
    } catch (Exception $e) {
        logError("Failed to create role", ['error' => $e->getMessage(), 'data' => $data]);
        throw $e;
    }
}

/**
 * Get all roles
 */
function getAllRoles($isActive = 'Yes') {
    $query = "SELECT * FROM Roles";
    $params = [];
    
    if ($isActive) {
        $query .= " WHERE IsActive = ?";
        $params[] = $isActive;
    }
    
    $query .= " ORDER BY RoleName";
    
    return dbFetchAll($query, $params);
}

/**
 * Get role with permissions
 */
function getRole($roleId) {
    return dbFetchOne("SELECT * FROM Roles WHERE RoleID = ?", [$roleId]);
}

/**
 * Update role permissions
 */
function updateRolePermissions($roleId, $permissions) {
    try {
        $permissionsJson = json_encode($permissions);
        
        $query = "UPDATE Roles SET Permissions = ? WHERE RoleID = ?";
        $affected = dbUpdate($query, [$permissionsJson, $roleId]);
        
        logInfo("Role permissions updated", ['role_id' => $roleId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to update role permissions", ['error' => $e->getMessage(), 'role_id' => $roleId]);
        throw $e;
    }
}

// ==============================================
// ROLE ASSIGNMENT FUNCTIONS
// ==============================================

/**
 * Assign role to employee
 */
function assignRoleToEmployee($employeeId, $roleId) {
    try {
        // Check if role is already assigned
        $existing = dbFetchOne(
            "SELECT COUNT(*) as count FROM EmployeeRoles WHERE EmployeeID = ? AND RoleID = ? AND IsActive = 'Yes'",
            [$employeeId, $roleId]
        );
        
        if ($existing['count'] > 0) {
            throw new Exception("Role already assigned to employee");
        }
        
        $query = "
            INSERT INTO EmployeeRoles (EmployeeID, RoleID, IsActive)
            VALUES (?, ?, 'Yes')
        ";
        
        $employeeRoleId = dbInsert($query, [$employeeId, $roleId]);
        
        logInfo("Role assigned to employee", ['employee_id' => $employeeId, 'role_id' => $roleId]);
        return $employeeRoleId;
    } catch (Exception $e) {
        logError("Failed to assign role", ['error' => $e->getMessage(), 'employee_id' => $employeeId]);
        throw $e;
    }
}

/**
 * Revoke role from employee
 */
function revokeRoleFromEmployee($employeeId, $roleId) {
    try {
        $query = "UPDATE EmployeeRoles SET IsActive = 'No' WHERE EmployeeID = ? AND RoleID = ?";
        $affected = dbUpdate($query, [$employeeId, $roleId]);
        
        logInfo("Role revoked from employee", ['employee_id' => $employeeId, 'role_id' => $roleId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to revoke role", ['error' => $e->getMessage(), 'employee_id' => $employeeId]);
        throw $e;
    }
}

/**
 * Get employee roles
 */
function getEmployeeRoles($employeeId, $activeOnly = true) {
    $query = "
        SELECT er.EmployeeRoleID, r.* FROM EmployeeRoles er
        JOIN Roles r ON er.RoleID = r.RoleID
        WHERE er.EmployeeID = ?
    ";
    
    $params = [$employeeId];
    
    if ($activeOnly) {
        $query .= " AND er.IsActive = 'Yes'";
    }
    
    $query .= " ORDER BY r.RoleName";
    
    return dbFetchAll($query, $params);
}

/**
 * Check if employee has specific role
 */
function employeeHasRole($employeeId, $roleId) {
    $result = dbFetchOne(
        "SELECT COUNT(*) as count FROM EmployeeRoles WHERE EmployeeID = ? AND RoleID = ? AND IsActive = 'Yes'",
        [$employeeId, $roleId]
    );
    
    return $result['count'] > 0;
}

// ==============================================
// ROLE DISTRIBUTION FUNCTIONS
// ==============================================

/**
 * Distribute role to store (assign employee to store with specific role)
 */
function distributeRoleToStore($employeeId, $roleId, $storeId, $startDate, $endDate = null) {
    try {
        // Validate dates
        if ($endDate && strtotime($endDate) < strtotime($startDate)) {
            throw new Exception("End date cannot be before start date");
        }
        
        $query = "
            INSERT INTO EmployeeRoleAssignments (EmployeeID, RoleID, StoreID, StartDate, EndDate, IsActive)
            VALUES (?, ?, ?, ?, ?, 'Yes')
        ";
        
        $assignmentId = dbInsert($query, [
            $employeeId, $roleId, $storeId, $startDate, $endDate
        ]);
        
        logInfo("Role distributed to store", [
            'employee_id' => $employeeId,
            'role_id' => $roleId,
            'store_id' => $storeId
        ]);
        
        return $assignmentId;
    } catch (Exception $e) {
        logError("Failed to distribute role to store", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Get role distribution for a store
 */
function getRoleDistributionByStore($storeId, $activeOnly = true) {
    return dbFetchAll("SELECT * FROM v_role_distribution WHERE StoreName = (SELECT StoreName FROM Stores WHERE StoreID = ?)", [$storeId]);
}

/**
 * Get all active role distributions
 */
function getActiveRoleDistributions() {
    return dbFetchAll("SELECT * FROM v_role_distribution ORDER BY StoreName, RoleName");
}

/**
 * Get employee assignments for store
 */
function getEmployeeAssignmentsForStore($storeId, $roleId = null) {
    $query = "
        SELECT era.*, e.FirstName, e.LastName, e.Email, r.RoleName, s.StoreName
        FROM EmployeeRoleAssignments era
        JOIN Employees e ON era.EmployeeID = e.EmployeeID
        JOIN Roles r ON era.RoleID = r.RoleID
        JOIN Stores s ON era.StoreID = s.StoreID
        WHERE era.StoreID = ? AND era.IsActive = 'Yes'
        AND CURDATE() BETWEEN era.StartDate AND COALESCE(era.EndDate, CURDATE())
    ";
    
    $params = [$storeId];
    
    if ($roleId) {
        $query .= " AND era.RoleID = ?";
        $params[] = $roleId;
    }
    
    $query .= " ORDER BY r.RoleName, e.FirstName, e.LastName";
    
    return dbFetchAll($query, $params);
}

/**
 * End role assignment
 */
function endRoleAssignment($assignmentId, $endDate = null) {
    try {
        $endDate = $endDate ?? date('Y-m-d');
        
        $query = "UPDATE EmployeeRoleAssignments SET EndDate = ?, IsActive = 'No' WHERE AssignmentID = ?";
        $affected = dbUpdate($query, [$endDate, $assignmentId]);
        
        logInfo("Role assignment ended", ['assignment_id' => $assignmentId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to end role assignment", ['error' => $e->getMessage()]);
        throw $e;
    }
}

// ==============================================
// PAYROLL FUNCTIONS
// ==============================================

/**
 * Calculate and create payroll for employee
 */
function calculatePayroll($employeeId, $payPeriodStart, $payPeriodEnd) {
    try {
        getDB()->beginTransaction();
        
        // Get employee details
        $employee = dbFetchOne("SELECT * FROM Employees WHERE EmployeeID = ?", [$employeeId]);
        if (!$employee) {
            throw new Exception("Employee not found");
        }
        
        // Calculate total hours worked
        $timesheetResult = dbFetchOne(
            "SELECT COALESCE(SUM(HoursWorked), 0) as TotalHours FROM Timesheets 
             WHERE EmployeeID = ? AND WorkDate BETWEEN ? AND ? AND Status = 'Approved'",
            [$employeeId, $payPeriodStart, $payPeriodEnd]
        );
        
        $totalHours = $timesheetResult['TotalHours'] ?? 0;
        
        // Calculate gross pay
        if ($employee['HourlyRate'] && $employee['HourlyRate'] > 0) {
            $grossPay = $totalHours * $employee['HourlyRate'];
        } else {
            // Use fixed salary
            $grossPay = $employee['Salary'];
        }
        
        // Get deductions (default 10% for tax)
        $deductions = $grossPay * 0.10;
        $bonuses = 0;
        
        $netPay = $grossPay - $deductions + $bonuses;
        
        // Insert payroll record
        $query = "
            INSERT INTO Payroll (EmployeeID, PayPeriodStart, PayPeriodEnd, HoursWorked, GrossPay, Deductions, Bonuses, NetPay, Status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
        ";
        
        $payrollId = dbInsert($query, [
            $employeeId, $payPeriodStart, $payPeriodEnd, $totalHours, 
            $grossPay, $deductions, $bonuses, $netPay
        ]);
        
        // Record in general ledger
        $storeId = $employee['StoreID'];
        recordGeneralLedger('Expense', 'Payroll Expense', 
            "Payroll for employee {$employee['FirstName']} {$employee['LastName']}", 
            $netPay, 0, $payrollId, 'Payroll', $storeId);
        
        // Record in expenses
        dbExecute(
            "INSERT INTO Expenses (StoreID, Description, Amount, Category) VALUES (?, ?, ?, ?)",
            [$storeId, "Payroll - {$employee['FirstName']} {$employee['LastName']}", $netPay, 'Payroll']
        );
        
        getDB()->commit();
        
        logInfo("Payroll calculated successfully", [
            'payroll_id' => $payrollId,
            'employee_id' => $employeeId,
            'gross_pay' => $grossPay
        ]);
        
        return $payrollId;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to calculate payroll", ['error' => $e->getMessage(), 'employee_id' => $employeeId]);
        throw $e;
    }
}

/**
 * Get payroll records
 */
function getPayrollRecords($employeeId = null, $startDate = null, $endDate = null, $status = null) {
    $query = "SELECT * FROM v_payroll_summary WHERE 1=1";
    $params = [];
    
    if ($employeeId) {
        $query .= " AND EmployeeID = ?";
        $params[] = $employeeId;
    }
    
    if ($startDate) {
        $query .= " AND PayPeriodStart >= ?";
        $params[] = $startDate;
    }
    
    if ($endDate) {
        $query .= " AND PayPeriodEnd <= ?";
        $params[] = $endDate;
    }
    
    if ($status) {
        $query .= " AND Status = ?";
        $params[] = $status;
    }
    
    $query .= " ORDER BY PayPeriodEnd DESC";
    
    return dbFetchAll($query, $params);
}

/**
 * Get single payroll record
 */
function getPayrollRecord($payrollId) {
    return dbFetchOne("SELECT * FROM Payroll WHERE PayrollID = ?", [$payrollId]);
}

/**
 * Add deduction to payroll
 */
function addPayrollDeduction($payrollId, $deductionType, $amount, $description = null) {
    try {
        $query = "
            INSERT INTO PayrollDeductions (PayrollID, DeductionType, Amount, Description)
            VALUES (?, ?, ?, ?)
        ";
        
        $deductionId = dbInsert($query, [$payrollId, $deductionType, $amount, $description]);
        
        // Update payroll deductions
        dbExecute(
            "UPDATE Payroll SET Deductions = Deductions + ? WHERE PayrollID = ?",
            [$amount, $payrollId]
        );
        
        // Recalculate net pay
        $payroll = dbFetchOne("SELECT GrossPay, Deductions, Bonuses FROM Payroll WHERE PayrollID = ?", [$payrollId]);
        $netPay = $payroll['GrossPay'] - $payroll['Deductions'] + $payroll['Bonuses'];
        
        dbUpdate("UPDATE Payroll SET NetPay = ? WHERE PayrollID = ?", [$netPay, $payrollId]);
        
        logInfo("Deduction added to payroll", ['payroll_id' => $payrollId, 'amount' => $amount]);
        return $deductionId;
    } catch (Exception $e) {
        logError("Failed to add payroll deduction", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Add bonus to payroll
 */
function addPayrollBonus($payrollId, $bonusType, $amount, $description = null) {
    try {
        $query = "
            INSERT INTO PayrollBonuses (PayrollID, BonusType, Amount, Description)
            VALUES (?, ?, ?, ?)
        ";
        
        $bonusId = dbInsert($query, [$payrollId, $bonusType, $amount, $description]);
        
        // Update payroll bonuses
        dbExecute(
            "UPDATE Payroll SET Bonuses = Bonuses + ? WHERE PayrollID = ?",
            [$amount, $payrollId]
        );
        
        // Recalculate net pay
        $payroll = dbFetchOne("SELECT GrossPay, Deductions, Bonuses FROM Payroll WHERE PayrollID = ?", [$payrollId]);
        $netPay = $payroll['GrossPay'] - $payroll['Deductions'] + $payroll['Bonuses'];
        
        dbUpdate("UPDATE Payroll SET NetPay = ? WHERE PayrollID = ?", [$netPay, $payrollId]);
        
        logInfo("Bonus added to payroll", ['payroll_id' => $payrollId, 'amount' => $amount]);
        return $bonusId;
    } catch (Exception $e) {
        logError("Failed to add payroll bonus", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Process payroll (mark as paid and finalize)
 */
function processPayroll($payrollId) {
    try {
        getDB()->beginTransaction();
        
        $payroll = dbFetchOne("SELECT * FROM Payroll WHERE PayrollID = ?", [$payrollId]);
        
        if (!$payroll) {
            throw new Exception("Payroll record not found");
        }
        
        // Update payroll status
        dbUpdate(
            "UPDATE Payroll SET Status = 'Paid', PaymentDate = NOW() WHERE PayrollID = ?",
            [$payrollId]
        );
        
        // Record payment in general ledger (if not already recorded)
        $employee = dbFetchOne("SELECT * FROM Employees WHERE EmployeeID = ?", [$payroll['EmployeeID']]);
        
        recordGeneralLedger('Asset', 'Cash', 
            "Payroll payment to {$employee['FirstName']} {$employee['LastName']}", 
            0, $payroll['NetPay'], $payrollId, 'Payment', $employee['StoreID']);
        
        getDB()->commit();
        
        logInfo("Payroll processed", ['payroll_id' => $payrollId]);
        return true;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to process payroll", ['error' => $e->getMessage()]);
        throw $e;
    }
}

// ==============================================
// TIMESHEET FUNCTIONS
// ==============================================

/**
 * Record timesheet entry
 */
function recordTimesheet($employeeId, $storeId, $workDate, $hoursWorked, $clockIn = null, $clockOut = null, $notes = null) {
    try {
        $query = "
            INSERT INTO Timesheets (EmployeeID, StoreID, WorkDate, ClockIn, ClockOut, HoursWorked, Notes)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                ClockIn = ?, ClockOut = ?, HoursWorked = ?, Notes = ?, UpdatedAt = NOW()
        ";
        
        $timesheetId = dbInsert($query, [
            $employeeId, $storeId, $workDate, $clockIn, $clockOut, $hoursWorked, $notes,
            $clockIn, $clockOut, $hoursWorked, $notes
        ]);
        
        logInfo("Timesheet recorded", ['employee_id' => $employeeId, 'work_date' => $workDate]);
        return $timesheetId;
    } catch (Exception $e) {
        logError("Failed to record timesheet", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Approve timesheet
 */
function approveTimesheet($timesheetId) {
    try {
        $affected = dbUpdate(
            "UPDATE Timesheets SET Status = 'Approved' WHERE TimesheetID = ?",
            [$timesheetId]
        );
        
        logInfo("Timesheet approved", ['timesheet_id' => $timesheetId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to approve timesheet", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Reject timesheet
 */
function rejectTimesheet($timesheetId, $reason = null) {
    try {
        $query = "UPDATE Timesheets SET Status = 'Rejected', Notes = ? WHERE TimesheetID = ?";
        $affected = dbUpdate($query, [$reason, $timesheetId]);
        
        logInfo("Timesheet rejected", ['timesheet_id' => $timesheetId]);
        return $affected > 0;
    } catch (Exception $e) {
        logError("Failed to reject timesheet", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Get timesheets for review
 */
function getTimesheetsForReview($storeId = null, $status = 'Pending') {
    $query = "
        SELECT t.*, e.FirstName, e.LastName, s.StoreName
        FROM Timesheets t
        JOIN Employees e ON t.EmployeeID = e.EmployeeID
        JOIN Stores s ON t.StoreID = s.StoreID
        WHERE t.Status = ?
    ";
    
    $params = [$status];
    
    if ($storeId) {
        $query .= " AND t.StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY t.WorkDate DESC, e.FirstName";
    
    return dbFetchAll($query, $params);
}

// ==============================================
// ATTENDANCE AND LEAVE FUNCTIONS
// ==============================================

/**
 * Record attendance
 */
function recordAttendance($employeeId, $storeId, $attendanceDate, $status = 'Present', $remarks = null) {
    try {
        $query = "
            INSERT INTO Attendance (EmployeeID, StoreID, AttendanceDate, Status, Remarks)
            VALUES (?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE Status = ?, Remarks = ?, CreatedAt = NOW()
        ";
        
        $attendanceId = dbInsert($query, [
            $employeeId, $storeId, $attendanceDate, $status, $remarks,
            $status, $remarks
        ]);
        
        return $attendanceId;
    } catch (Exception $e) {
        logError("Failed to record attendance", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Submit leave request
 */
function submitLeaveRequest($employeeId, $leaveType, $startDate, $endDate, $reason = null) {
    try {
        // Validate dates
        if (strtotime($endDate) < strtotime($startDate)) {
            throw new Exception("End date cannot be before start date");
        }
        
        $query = "
            INSERT INTO LeaveRequests (EmployeeID, LeaveType, StartDate, EndDate, Reason, Status)
            VALUES (?, ?, ?, ?, ?, 'Pending')
        ";
        
        $leaveRequestId = dbInsert($query, [
            $employeeId, $leaveType, $startDate, $endDate, $reason
        ]);
        
        logInfo("Leave request submitted", ['employee_id' => $employeeId, 'leave_type' => $leaveType]);
        return $leaveRequestId;
    } catch (Exception $e) {
        logError("Failed to submit leave request", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Approve leave request
 */
function approveLeaveRequest($leaveRequestId, $approvedBy) {
    try {
        getDB()->beginTransaction();
        
        $leaveRequest = dbFetchOne(
            "SELECT * FROM LeaveRequests WHERE LeaveRequestID = ?",
            [$leaveRequestId]
        );
        
        if (!$leaveRequest) {
            throw new Exception("Leave request not found");
        }
        
        // Update leave request
        dbUpdate(
            "UPDATE LeaveRequests SET Status = 'Approved', ApprovedBy = ?, ApprovedDate = NOW() WHERE LeaveRequestID = ?",
            [$approvedBy, $leaveRequestId]
        );
        
        // Record attendance as 'On Leave' for each day
        $currentDate = $leaveRequest['StartDate'];
        $employee = dbFetchOne("SELECT StoreID FROM Employees WHERE EmployeeID = ?", [$leaveRequest['EmployeeID']]);
        
        while ($currentDate <= $leaveRequest['EndDate']) {
            recordAttendance(
                $leaveRequest['EmployeeID'],
                $employee['StoreID'],
                $currentDate,
                'On Leave',
                'Approved leave'
            );
            
            $currentDate = date('Y-m-d', strtotime($currentDate . ' +1 day'));
        }
        
        getDB()->commit();
        
        logInfo("Leave request approved", ['leave_request_id' => $leaveRequestId]);
        return true;
    } catch (Exception $e) {
        getDB()->rollback();
        logError("Failed to approve leave request", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Reject leave request
 */
function rejectLeaveRequest($leaveRequestId, $approvedBy) {
    try {
        dbUpdate(
            "UPDATE LeaveRequests SET Status = 'Rejected', ApprovedBy = ?, ApprovedDate = NOW() WHERE LeaveRequestID = ?",
            [$approvedBy, $leaveRequestId]
        );
        
        logInfo("Leave request rejected", ['leave_request_id' => $leaveRequestId]);
        return true;
    } catch (Exception $e) {
        logError("Failed to reject leave request", ['error' => $e->getMessage()]);
        throw $e;
    }
}

/**
 * Get pending leave requests
 */
function getPendingLeaveRequests($storeId = null) {
    $query = "
        SELECT lr.*, e.FirstName, e.LastName, e.Email, s.StoreName
        FROM LeaveRequests lr
        JOIN Employees e ON lr.EmployeeID = e.EmployeeID
        LEFT JOIN Stores s ON e.StoreID = s.StoreID
        WHERE lr.Status = 'Pending'
    ";
    
    $params = [];
    
    if ($storeId) {
        $query .= " AND e.StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY lr.CreatedAt DESC";
    
    return dbFetchAll($query, $params);
}

// ==============================================
// REPORTING FUNCTIONS
// ==============================================

/**
 * Get payroll summary
 */
function getPayrollSummary() {
    return dbFetchAll("SELECT * FROM v_payroll_summary ORDER BY PayPeriodEnd DESC");
}

/**
 * Get attendance summary
 */
function getAttendanceSummary($storeId = null, $date = null) {
    if (!$date) {
        $date = date('Y-m-d');
    }
    
    $query = "
        SELECT a.AttendanceDate, s.StoreName,
               COUNT(CASE WHEN a.Status = 'Present' THEN 1 END) AS Present,
               COUNT(CASE WHEN a.Status = 'Absent' THEN 1 END) AS Absent,
               COUNT(CASE WHEN a.Status = 'Late' THEN 1 END) AS Late,
               COUNT(CASE WHEN a.Status = 'On Leave' THEN 1 END) AS OnLeave,
               COUNT(*) AS Total
        FROM Attendance a
        JOIN Stores s ON a.StoreID = s.StoreID
        WHERE a.AttendanceDate = ?
    ";
    
    $params = [$date];
    
    if ($storeId) {
        $query .= " AND a.StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " GROUP BY a.AttendanceDate, s.StoreName";
    
    return dbFetchAll($query, $params);
}

/**
 * Get HR dashboard statistics
 */
function getHRDashboardStats($storeId = null) {
    $stats = [];
    
    // Total active employees
    $query = "SELECT COUNT(*) as total FROM Employees WHERE Status = 'Active'";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $stats['total_employees'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['total_employees'] = dbFetchOne($query)['total'];
    }
    
    // Employees on leave
    $query = "SELECT COUNT(*) as total FROM Employees WHERE Status = 'On Leave'";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $stats['employees_on_leave'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['employees_on_leave'] = dbFetchOne($query)['total'];
    }
    
    // Pending leave requests
    $query = "SELECT COUNT(*) as total FROM LeaveRequests WHERE Status = 'Pending'";
    if ($storeId) {
        $query .= " AND EmployeeID IN (SELECT EmployeeID FROM Employees WHERE StoreID = ?)";
        $stats['pending_leave_requests'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['pending_leave_requests'] = dbFetchOne($query)['total'];
    }
    
    // Pending timesheets
    $query = "SELECT COUNT(*) as total FROM Timesheets WHERE Status = 'Pending'";
    if ($storeId) {
        $query .= " AND StoreID = ?";
        $stats['pending_timesheets'] = dbFetchOne($query, [$storeId])['total'];
    } else {
        $stats['pending_timesheets'] = dbFetchOne($query)['total'];
    }
    
    // Pending payroll
    $query = "SELECT COUNT(*) as total FROM Payroll WHERE Status IN ('Draft', 'Pending')";
    $stats['pending_payroll'] = dbFetchOne($query)['total'];
    
    return $stats;
}

?>