<?php


session_start();
require_once 'includes/core_functions.php';
require_once 'includes/hr_functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has HR Manager role
if (!hasPermission('Admin') && !hasPermission('Manager')) {
    header('Location: index.php');
    exit;
}

// Get HR Dashboard Statistics
try {
    $hrStats = getHRDashboardStats($_SESSION['store_id'] ?? null);
    $employees = getAllEmployees($_SESSION['store_id'] ?? null, 'Active');
    $roles = getAllRoles();
} catch (Exception $e) {
    $error = "Unable to load HR data: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Module - Shoe Retail ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        main {
            margin-left: 240px;
        }
        .nav-tabs .nav-link {
            color: #495057;
        }
        .nav-tabs .nav-link.active {
            color: #0d6efd;
            border-color: #dee2e6 #dee2e6 #ffffff;
        }
        .stat-card {
            border-left: 4px solid #007bff;
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <!-- Top Navigation -->
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="index.php"><i class="bi bi-shop"></i> Shoe Retail ERP</a>
        <div class="navbar-nav">
            <span class="nav-link px-3 text-white"><i class="bi bi-person-fill"></i> HR Module</span>
        </div>
        <div class="navbar-nav">
            <a class="nav-link px-3" href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="index.php"><i class="bi bi-house-door"></i> Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="hr.php"><i class="bi bi-people-fill"></i> HR Management</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inventory.php"><i class="bi bi-box-seam"></i> Inventory</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="sales.php"><i class="bi bi-cart-check"></i> Sales</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="accounting.php"><i class="bi bi-calculator"></i> Accounting</a>
                        </li>
                    </ul>
                </div>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-people-fill"></i> HR Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                            <i class="bi bi-plus-circle"></i> Add Employee
                        </button>
                    </div>
                </div>

                <?php if (isset($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <!-- HR Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Total Employees</small>
                                        <h5 class="mb-0"><?php echo number_format($hrStats['total_employees'] ?? 0); ?></h5>
                                    </div>
                                    <i class="bi bi-people text-primary" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">On Leave</small>
                                        <h5 class="mb-0"><?php echo number_format($hrStats['employees_on_leave'] ?? 0); ?></h5>
                                    </div>
                                    <i class="bi bi-calendar-check text-warning" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Pending Leave Requests</small>
                                        <h5 class="mb-0"><?php echo number_format($hrStats['pending_leave_requests'] ?? 0); ?></h5>
                                    </div>
                                    <i class="bi bi-clock-history text-info" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-3 col-md-6 mb-4">
                        <div class="card stat-card shadow">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-muted">Pending Payroll</small>
                                        <h5 class="mb-0"><?php echo number_format($hrStats['pending_payroll'] ?? 0); ?></h5>
                                    </div>
                                    <i class="bi bi-cash-coin text-success" style="font-size: 2rem;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HR Tabs -->
                <div class="card shadow">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" data-bs-toggle="tab" href="#employees" role="tab">
                                    <i class="bi bi-people"></i> Employees
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#roles" role="tab">
                                    <i class="bi bi-shield-check"></i> Roles & Permissions
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#timesheets" role="tab">
                                    <i class="bi bi-calendar-range"></i> Timesheets
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#payroll" role="tab">
                                    <i class="bi bi-wallet2"></i> Payroll
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#leave" role="tab">
                                    <i class="bi bi-calendar-check"></i> Leave Management
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Employees Tab -->
                            <div class="tab-pane fade show active" id="employees">
                                <h5 class="mb-3">Active Employees</h5>
                                <?php if (!empty($employees)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Store</th>
                                                <th>Salary</th>
                                                <th>Roles</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($employees as $employee): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($employee['Email']); ?></td>
                                                <td><?php echo htmlspecialchars($employee['StoreName'] ?? 'N/A'); ?></td>
                                                <td>$<?php echo number_format($employee['Salary'], 2); ?></td>
                                                <td>
                                                    <?php if (!empty($employee['Roles'])): ?>
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($employee['Roles']); ?></span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">No Roles</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo $employee['Status'] == 'Active' ? 'success' : 'danger'; ?>">
                                                        <?php echo $employee['Status']; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#assignRoleModal"
                                                            onclick="setEmployeeId(<?php echo $employee['EmployeeID']; ?>)">
                                                        <i class="bi bi-link"></i> Assign Role
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No active employees found.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Roles Tab -->
                            <div class="tab-pane fade" id="roles">
                                <h5 class="mb-3">System Roles</h5>
                                <?php if (!empty($roles)): ?>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Role Name</th>
                                                <th>Description</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($roles as $role): ?>
                                            <tr>
                                                <td><strong><?php echo htmlspecialchars($role['RoleName']); ?></strong></td>
                                                <td><?php echo htmlspecialchars($role['Description'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $role['IsActive'] == 'Yes' ? 'success' : 'danger'; ?>">
                                                        <?php echo $role['IsActive'] == 'Yes' ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-warning">
                                                        <i class="bi bi-pencil"></i> Edit
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-muted">No roles found.</p>
                                <?php endif; ?>
                            </div>

                            <!-- Timesheets Tab -->
                            <div class="tab-pane fade" id="timesheets">
                                <h5 class="mb-3">Pending Timesheets for Approval</h5>
                                <?php
                                try {
                                    $pendingTimesheets = getTimesheetsForReview($_SESSION['store_id'] ?? null);
                                    if (!empty($pendingTimesheets)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Work Date</th>
                                                    <th>Hours Worked</th>
                                                    <th>Clock In</th>
                                                    <th>Clock Out</th>
                                                    <th>Store</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingTimesheets as $timesheet): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($timesheet['FirstName'] . ' ' . $timesheet['LastName']); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($timesheet['WorkDate'])); ?></td>
                                                    <td><?php echo $timesheet['HoursWorked']; ?> hrs</td>
                                                    <td><?php echo $timesheet['ClockIn'] ?? 'N/A'; ?></td>
                                                    <td><?php echo $timesheet['ClockOut'] ?? 'N/A'; ?></td>
                                                    <td><?php echo htmlspecialchars($timesheet['StoreName']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">No pending timesheets for approval.</p>
                                    <?php endif;
                                } catch (Exception $e) {
                                    echo '<p class="text-danger">Unable to load timesheets: ' . htmlspecialchars($e->getMessage()) . '</p>';
                                }
                                ?>
                            </div>

                            <!-- Payroll Tab -->
                            <div class="tab-pane fade" id="payroll">
                                <h5 class="mb-3">Payroll Processing</h5>
                                <form method="POST" class="mb-4">
                                    <div class="row">
                                        <div class="col-md-4 mb-2">
                                            <label for="payPeriodStart" class="form-label">Pay Period Start</label>
                                            <input type="date" class="form-control" id="payPeriodStart" name="payPeriodStart" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label for="payPeriodEnd" class="form-label">Pay Period End</label>
                                            <input type="date" class="form-control" id="payPeriodEnd" name="payPeriodEnd" required>
                                        </div>
                                        <div class="col-md-4 mb-2">
                                            <label>&nbsp;</label>
                                            <button type="submit" name="action" value="calculatePayroll" class="btn btn-primary w-100">
                                                <i class="bi bi-calculator"></i> Calculate Payroll
                                            </button>
                                        </div>
                                    </div>
                                </form>

                                <?php
                                try {
                                    $payrollRecords = getPayrollRecords(null, null, null, 'Pending');
                                    if (!empty($payrollRecords)): ?>
                                    <h6 class="mt-4">Pending Payroll Records</h6>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Period</th>
                                                    <th>Gross Pay</th>
                                                    <th>Deductions</th>
                                                    <th>Bonuses</th>
                                                    <th>Net Pay</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($payrollRecords as $record): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($record['EmployeeName']); ?></td>
                                                    <td><?php echo date('M d', strtotime($record['PayPeriodStart'])) . ' - ' . date('M d, Y', strtotime($record['PayPeriodEnd'])); ?></td>
                                                    <td>$<?php echo number_format($record['GrossPay'], 2); ?></td>
                                                    <td>$<?php echo number_format($record['Deductions'], 2); ?></td>
                                                    <td>$<?php echo number_format($record['Bonuses'], 2); ?></td>
                                                    <td><strong>$<?php echo number_format($record['NetPay'], 2); ?></strong></td>
                                                    <td><span class="badge bg-warning"><?php echo $record['Status']; ?></span></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary">
                                                            <i class="bi bi-check-circle"></i> Process
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">No pending payroll records.</p>
                                    <?php endif;
                                } catch (Exception $e) {
                                    echo '<p class="text-danger">Unable to load payroll records.</p>';
                                }
                                ?>
                            </div>

                            <!-- Leave Management Tab -->
                            <div class="tab-pane fade" id="leave">
                                <h5 class="mb-3">Leave Requests Pending Approval</h5>
                                <?php
                                try {
                                    $pendingLeaveRequests = getPendingLeaveRequests($_SESSION['store_id'] ?? null);
                                    if (!empty($pendingLeaveRequests)): ?>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Employee</th>
                                                    <th>Leave Type</th>
                                                    <th>From - To</th>
                                                    <th>Reason</th>
                                                    <th>Requested On</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($pendingLeaveRequests as $leave): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($leave['FirstName'] . ' ' . $leave['LastName']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $leave['LeaveType']; ?></span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($leave['StartDate'])); ?> - 
                                                        <?php echo date('M d, Y', strtotime($leave['EndDate'])); ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($leave['Reason'] ?? 'N/A'); ?></td>
                                                    <td><?php echo date('M d, Y', strtotime($leave['CreatedAt'])); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success" onclick="approveLeave(<?php echo $leave['LeaveRequestID']; ?>)">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" onclick="rejectLeave(<?php echo $leave['LeaveRequestID']; ?>)">
                                                            <i class="bi bi-x-circle"></i> Reject
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php else: ?>
                                    <p class="text-muted">No pending leave requests.</p>
                                    <?php endif;
                                } catch (Exception $e) {
                                    echo '<p class="text-danger">Unable to load leave requests.</p>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div class="modal fade" id="addEmployeeModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="api/hr/add_employee.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="firstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="lastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="phone" name="phone">
                        </div>
                        <div class="mb-3">
                            <label for="hireDate" class="form-label">Hire Date</label>
                            <input type="date" class="form-control" id="hireDate" name="hire_date" required>
                        </div>
                        <div class="mb-3">
                            <label for="salary" class="form-label">Salary</label>
                            <input type="number" step="0.01" class="form-control" id="salary" name="salary" required>
                        </div>
                        <div class="mb-3">
                            <label for="hourlyRate" class="form-label">Hourly Rate (Optional)</label>
                            <input type="number" step="0.01" class="form-control" id="hourlyRate" name="hourly_rate">
                        </div>
                        <div class="mb-3">
                            <label for="storeId" class="form-label">Store</label>
                            <select class="form-control" id="storeId" name="store_id" required>
                                <option value="">Select Store</option>
                                <?php
                                $stores = getAllStores();
                                foreach ($stores as $store): ?>
                                <option value="<?php echo $store['StoreID']; ?>"><?php echo htmlspecialchars($store['StoreName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Employee</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Role Modal -->
    <div class="modal fade" id="assignRoleModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Assign Role to Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="api/hr/assign_role.php">
                    <div class="modal-body">
                        <input type="hidden" id="employeeId" name="employee_id">
                        <div class="mb-3">
                            <label for="roleId" class="form-label">Select Role</label>
                            <select class="form-control" id="roleId" name="role_id" required>
                                <option value="">Select a Role</option>
                                <?php foreach ($roles as $role): ?>
                                <option value="<?php echo $role['RoleID']; ?>"><?php echo htmlspecialchars($role['RoleName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="storeAssignment" class="form-label">Assign to Store (for store-specific role)</label>
                            <select class="form-control" id="storeAssignment" name="store_assignment">
                                <option value="">Optional - Select Store</option>
                                <?php foreach ($stores as $store): ?>
                                <option value="<?php echo $store['StoreID']; ?>"><?php echo htmlspecialchars($store['StoreName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="startDate" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="startDate" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="endDate" class="form-label">End Date (Optional)</label>
                            <input type="date" class="form-control" id="endDate" name="end_date">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Assign Role</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setEmployeeId(employeeId) {
            document.getElementById('employeeId').value = employeeId;
        }

        function approveLeave(leaveRequestId) {
            if (confirm('Approve this leave request?')) {
                // Handle approval via API
                console.log('Approving leave request:', leaveRequestId);
            }
        }

        function rejectLeave(leaveRequestId) {
            if (confirm('Reject this leave request?')) {
                // Handle rejection via API
                console.log('Rejecting leave request:', leaveRequestId);
            }
        }
    </script>
</body>
</html>