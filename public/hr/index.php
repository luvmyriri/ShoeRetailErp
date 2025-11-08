<?php
// 1. Start session to store flash messages
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'HR'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

// 2. Include database configuration
require_once '../../config/database.php';
// Check if this is a Leave Request action
$action = $_POST['action'] ?? '';
$leaveRequestId = $_POST['leaveRequestId'] ?? null;

if (($action === 'approve' || $action === 'reject') && is_numeric($leaveRequestId)) {
    
    // Determine the new status
    $status = ($action === 'approve') ? 'Approved' : 'Rejected';
    
    // Get the logged-in user's ID from session
    $approvedBy = $_SESSION['user_id'] ?? $_SESSION['employee_id'] ?? null;
    
    if (!$approvedBy) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You must be logged in to perform this action']);
        exit;
    }
    
    // Prepare SQL query to update the leave request status
    $sql = "UPDATE leaverequests SET Status = ?, ApprovedBy = ? WHERE LeaveRequestID = ?";
    
    // Execute query
    dbExecute($sql, [$status, $approvedBy, $leaveRequestId]);
    
    // Set success response
    $_SESSION['flash_success'] = "Leave Request ID {$leaveRequestId} has been {$status}.";
    $response['status'] = 'success';
    $response['message'] = "Request {$status} successfully.";
    $response['redirectUrl'] = 'index.php'; // Force reload to refresh data
    
    // Send JSON response and exit
    echo json_encode($response);
    exit;
}
// ==========================================================
//  BLOCK 1: HANDLE 'ADD EMPLOYEE' FORM SUBMISSION (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Set response header to JSON
    header('Content-Type: application/json');

    // Initialize response array
    $response = [
        'status' => 'error',
        'message' => 'An unknown error occurred.',
        'redirectUrl' => 'index.php' // Page will reload to itself
    ];

    try {
        // Get and sanitize form data
        $firstName = trim($_POST['FirstName'] ?? '');
        $middleName = trim($_POST['MiddleName'] ?? ''); // This might be empty for the simple form
        $lastName = trim($_POST['LastName'] ?? '');
        $email = trim($_POST['Email'] ?? '');
        $department = trim($_POST['Department'] ?? '');
        $role = trim($_POST['Role'] ?? '');
        
        // --- Get all other fields ---
        // These will be null if coming from the simple form, which is fine for the DB
        $gender = $_POST['Gender'] ?? 'Male';
        $maritalStatus = $_POST['MaritalStatus'] ?? 'Single';
        $religion = $_POST['Religion'] ?? null;
        $birthDate = $_POST['BirthDate'] ?? null;
        $placeOfBirth = $_POST['PlaceOfBirth'] ?? null;
        $age = $_POST['Age'] ?? null;
        $streetAddress = $_POST['StreetAddress'] ?? null;
        $city = $_POST['City'] ?? null;
        $zipCode = $_POST['ZipCode'] ?? null;
        $phone = $_POST['Phone'] ?? null;
        $landline = $_POST['Landline'] ?? null;
        $emergencyContactName = $_POST['EmergencyContactName'] ?? null;
        $emergencyContactNumber = $_POST['EmergencyContactNumber'] ?? null;
        $bankAccountNumber = $_POST['BankAccountNumber'] ?? null;
        
        // Server-side validation
        if (empty($firstName) || empty($lastName) || empty($email) || empty($department) || empty($role)) {
            throw new Exception('Please fill in all required fields: First Name, Last Name, Email, Department, and Role.');
        }

        // Prepare SQL query
        $sql = "INSERT INTO employees (
                    FirstName, MiddleName, LastName, Gender, MaritalStatus, Religion, 
                    BirthDate, PlaceOfBirth, Age, StreetAddress, City, ZipCode, 
                    Phone, Landline, Email, EmergencyContactName, EmergencyContactNumber, 
                    Department, Role, BankAccountNumber, HireDate
                ) VALUES (
                    ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE()
                )";
        
        // Prepare parameters
        $params = [
            $firstName, $middleName, $lastName, $gender, $maritalStatus, $religion,
            empty($birthDate) ? null : $birthDate,
            $placeOfBirth,
            empty($age) ? null : $age,
            $streetAddress, $city, $zipCode,
            $phone, $landline, $email, $emergencyContactName, $emergencyContactNumber,
            $department, $role, $bankAccountNumber
        ];

        // Execute query
        dbExecute($sql, $params);

        // ✅ *** CHANGED ***
        // We no longer set a session message. We send the message directly in the JSON.
        // $_SESSION['flash_success'] = 'You successfully added a new employee.'; // No longer needed
        $response['status'] = 'success';
        $response['message'] = 'You successfully added a new employee.'; // Send message directly

    } catch (Exception $e) {
        // Handle errors
        $response['message'] = $e->getMessage();
        error_log('Add Employee Error: ' . $e->getMessage());
    }

    // Send JSON response back to the JavaScript
    echo json_encode($response);
    
    // CRITICAL: Stop the script here so it doesn't render the HTML
    exit; 
}

// ==========================================================
//  BLOCK 2: HANDLE DASHBOARD PAGE LOAD (GET)
// ==========================================================

// ✅ *** CHANGED ***
// We still check for session messages from *other* actions (like leave approval),
// but the 'Add Employee' success message is no longer handled here.
$flashSuccess = null;
if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}

// Initialize variables for the dashboard
$totalEmployees = 0;
$newHires = 0;
$dashboardError = null;

try {
    // 3. Fetch data using the dbFetchOne helper function from your config
    
    // Query for Total Employees
    $totalResult = dbFetchOne("SELECT COUNT(EmployeeID) AS total FROM employees");
    if ($totalResult) {
        $totalEmployees = $totalResult['total'];
    }

    // ... (rest of your original dashboard queries) ...
    
    // Query for New Hires This Month
    $newHiresResult = dbFetchOne("
        SELECT COUNT(EmployeeID) AS newHires 
        FROM employees 
        WHERE HireDate >= DATE_FORMAT(CURDATE(), '%Y-%m-01') 
          AND HireDate <= CURDATE()
    ");
    
    if ($newHiresResult) {
        $newHires = $newHiresResult['newHires'];
    }
    
    // Query for Department Breakdown
    $departmentData = dbFetchAll("
        SELECT Department, COUNT(EmployeeID) AS employeeCount
        FROM employees
        GROUP BY Department
        ORDER BY Department ASC
    ");
    
    // Query for "On Leave" Count (Approved)
    $onLeaveResult = dbFetchOne("
        SELECT COUNT(LeaveRequestID) AS onLeaveCount
        FROM leaverequests
        WHERE Status = 'Approved'
    ");
    $onLeaveCount = $onLeaveResult ? $onLeaveResult['onLeaveCount'] : 0;

    // Query for "On Leave" Modal List (Approved)
    $onLeaveData = dbFetchAll("
        SELECT 
            CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
            e.Department,
            e.Role,
            lt.LeaveTypeName,
            lr.StartDate,
            lr.EndDate
        FROM leaverequests AS lr
        JOIN employees AS e ON lr.EmployeeID = e.EmployeeID
        JOIN leavetypes AS lt ON lr.LeaveTypeID = lt.LeaveTypeID
        WHERE lr.Status = 'Approved'
        ORDER BY lr.StartDate DESC
    ");

    // Query for Pending Leave Count
    $pendingLeaveResult = dbFetchOne("
        SELECT COUNT(LeaveRequestID) AS pendingCount
        FROM leaverequests
        WHERE Status = 'Pending'
    ");
    $pendingLeaveCount = $pendingLeaveResult ? $pendingLeaveResult['pendingCount'] : 0;

    // Query for Pending Leave List (for the modal)
    $pendingLeaveData = dbFetchAll("
        SELECT 
            lr.LeaveRequestID,
            lr.StartDate,
            lr.EndDate,
            lr.RequestDate,
            CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
            e.Department,  /* <-- ADDED */
            e.Role,        /* <-- ADDED */
            lt.LeaveTypeName
        FROM leaverequests AS lr
        JOIN employees AS e ON lr.EmployeeID = e.EmployeeID
        JOIN leavetypes AS lt ON lr.LeaveTypeID = lt.LeaveTypeID
        WHERE lr.Status = 'Pending'
        ORDER BY lr.RequestDate ASC
    ");
    
} catch (Exception $e) {
    // This will catch any errors if the database connection fails
    $dashboardError = "Error loading dashboard data. Please check logs.";
    error_log("HR Dashboard Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HR Management - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="enhanced-modal-styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="js/hr-common.js"></script>
        <style>
        /* HR-specific styles */
        .clickable-card {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .clickable-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* HR Dashboard tables */
        .hr-table-container {
            max-height: 400px;
            overflow-y: auto;
            margin-bottom: 1rem;
        }

        .hr-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .hr-table th, .hr-table td {
            padding: 0.75rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
        }

        .hr-table th {
            background-color: var(--gray-50);
            font-weight: 600;
            color: var(--gray-700);
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .hr-table tr:last-child td {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="alert-container"></div>
    
    <?php if ($flashSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showModal('Success', '<?php echo addslashes($flashSuccess); ?>', 'success');
        });
    </script>
    <?php endif; ?>
    
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/modal.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Human Resources</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / HR Management</div>
                </div>
                <div class="page-header-actions">
<button class="btn btn-primary" onclick="openModal('CreateEmployeeModal')"><i class="fas fa-plus"></i> Add Employee</button>
<button class="btn btn-secondary" onclick="window.location.href='reports.php'"><i class="fas fa-download"></i> Export</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card clickable-card" onclick="openModal('employeeDirectoryModal')" title="Click to view department breakdown">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Total Employees</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;"><?php echo $totalEmployees; ?></div>
                                    <div style="font-size: 11px; color: #666;">↑ <?php echo $newHires; ?> new this month</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">👥</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card clickable-card" onclick="openModal('leaveModal')" title="Click to view employees on leave">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">On Leave</div>
                                        <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;"><?php echo $onLeaveCount; ?></div>                                    <div style="font-size: 11px; color: #666;">Currently away</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">🏖️</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card clickable-card" onclick="openModal('pendingLeaveModal')" title="Click to view pending leave requests">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Pending Leave</div>
                                <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;"><?php echo $pendingLeaveCount; ?></div>                                    <div style="font-size: 11px; color: #666;">⚠️ Needs approval</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">⏳</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card clickable-card" onclick="openModal('payrollModal')" title="Click to view monthly payroll breakdown">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Monthly Payroll</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">₱156,400</div>
                                    <div style="font-size: 11px; color: #666;">Processed 25-Oct</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">💰</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                  <div class="card">
                      <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                        <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Employee Directory</h3>
            </div>

            <div class="card-body" style="padding: 1rem 0.75rem;">
                <div style="font-size: 12px; color: #666; line-height: 1.8;">
                    <?php
                    // Query for Department Breakdown
                    $departmentData = dbFetchAll("
                        SELECT Department, COUNT(EmployeeID) AS employeeCount
                        FROM employees
                        GROUP BY Department
                        ORDER BY Department ASC
                    ");

                    // Loop through results
                    if (!empty($departmentData)) {
                        $total = count($departmentData);
                        $i = 0;
                        foreach ($departmentData as $row) {
                            $i++;
                            $border = ($i < $total) ? 'border-bottom: 1px solid #f0f0f0;' : ''; // no border on last item
                            ?>
                            <div style="display: flex; justify-content: space-between; padding: 0.75rem 0; <?= $border ?>">
                                <span><?= htmlspecialchars($row['Department']) ?></span>
                                <span style="font-weight: 600; color: #333;"><?= htmlspecialchars($row['employeeCount']) ?></span>
                            </div>
                            <?php
                        }
                    } else {
                        echo "<p style='color:#999; font-size:12px;'>No data available.</p>";
                    }
                    ?>
                        </div>
                    </div>
                </div>
            </div>


                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📋 Quick Actions</h3>
                        </div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
                                <button onclick="openModal('CreateEmployeeModal')" 
                                        style="background-color: #7C3AED; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;" 
                                        onmouseover="this.style.backgroundColor='#6D28D9'" 
                                        onmouseout="this.style.backgroundColor='#7C3AED'">
                                    <i class="fas fa-user-plus"></i> Add Employee
                                </button>
                                <button onclick="window.location.href='timesheets.php'" 
                                        style="background-color: #F59E0B; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;" 
                                        onmouseover="this.style.backgroundColor='#D97706'" 
                                        onmouseout="this.style.backgroundColor='#F59E0B'">
                                    <i class="fas fa-clock"></i> Timesheets
                                </button>
                                <button onclick="window.location.href='payroll_management.php'" 
                                        style="background-color: #F59E0B; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;" 
                                        onmouseover="this.style.backgroundColor='#D97706'" 
                                        onmouseout="this.style.backgroundColor='#F59E0B'">
                                    <i class="fas fa-money-bill"></i> Process Payroll
                                </button>
                                <button onclick="window.location.href='leave_management.php'" 
                                        style="background-color: #F59E0B; color: white; border: none; padding: 0.75rem; border-radius: 6px; cursor: pointer; font-size: 14px; font-weight: 600; display: flex; align-items: center; justify-content: center; gap: 0.5rem; transition: all 0.2s;" 
                                        onmouseover="this.style.backgroundColor='#D97706'" 
                                        onmouseout="this.style.backgroundColor='#F59E0B'">
                                    <i class="fas fa-tasks"></i> Leave Requests
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

                   <div id="CreateEmployeeModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
  <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 500px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
      <h3 style="margin: 0; font-size: 18px;">Add New Employee</h3>
      <button onclick="closeModal('CreateEmployeeModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
    </div>

    <form id="addEmployeeFormSimple" action="index.php" method="POST" style="display: flex; flex-direction: column; gap: 0.75rem;">
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.75rem;">
        <div>
          <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.25rem;">First Name</label>
          <input type="text" name="FirstName" placeholder="First Name" required class="form-control" style="padding: 0.5rem; font-size: 14px;"
                 oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').replace(/\b\w/g, c => c.toUpperCase())">
        </div>
        <div>
          <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.25rem;">Last Name</label>
          <input type="text" name="LastName" placeholder="Last Name" required class="form-control" style="padding: 0.5rem; font-size: 14px;"
                 oninput="this.value = this.value.replace(/[^A-Za-z\s]/g, '').replace(/\b\w/g, c => c.toUpperCase())">
        </div>
      </div>



      <div>
        <label for="Department_simple" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.25rem;">Department</label>
        <select id="Department_simple" name="Department" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
          <option value="">Select Department...</option>
          <option value="Human Resource">Human Resource</option>
          <option value="Sales and Customer Relation Management">Sales and Customer Relation Management</option>
          <option value="Inventory">Inventory</option>
          <option value="Procurement">Procurement</option>
          <option value="Accounting">Accounting</option>
        </select>
      </div>

      <div>
        <label for="Role_simple" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.25rem;">Assigned Role</label>
        <select id="Role_simple" name="Role" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
          <option value="">Select Role...</option>
          <optgroup label="Inventory Management">
            <option value="Inventory Manager">Inventory Manager</option>
            <option value="Inventory Encoder">Inventory Encoder</option>
          </optgroup>
          <optgroup label="Sales and Customer Management">
            <option value="Cashier">Cashier</option>
            <option value="Sales Manager">Sales Manager</option>
            <option value="Customer Service">Customer Service</option>
          </optgroup>
          <optgroup label="Procurement">
            <option value="Procurement Manager">Procurement Manager</option>
          </optgroup>
          <optgroup label="Accounting">
            <option value="Accountant">Accountant</option>
          </optgroup>
          <optgroup label="Human Resource (HR)">
            <option value="HR Manager">HR Manager</option>
          </optgroup>
          <optgroup label="General Admin">
            <option value="Admin">Admin</option>
          </optgroup>
        </select>
      </div>

      <div>
        <label style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.25rem;">Email</label>
        <input type="email" name="Email" placeholder="Enter email address" required class="form-control" style="padding: 0.5rem; font-size: 14px;">
      </div>

      <div style="display: flex; gap: 0.75rem; margin-top: 1rem;">
        <button type="button" class="btn btn-outline" onclick="closeModal('CreateEmployeeModal')" style="flex: 1; padding: 0.5rem; font-size: 14px;">Cancel</button>
        <button type="submit" class="btn btn-primary" style="flex: 1; padding: 0.5rem; font-size: 14px;">Add Employee</button>
      </div>
    </form>
  </div>
</div>

<script>
// These are intentionally simple and global for now
function openModal(id) {
  document.getElementById(id).style.display = 'flex';
}
function closeModal(id) {
  document.getElementById(id).style.display = 'none';
}
</script>

            <!-- Pending Approvals Section -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-12" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem; border-bottom: 1px solid #eee;">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">⏳ Pending Approvals</h3>
                        </div>
                        <div class="card-body table-responsive">
                            <?php if (empty($pendingLeaveData)): ?>
                                <div style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                    <i class="fas fa-check-circle" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5;"></i>
                                    <p style="margin: 0;">No pending approvals</p>
                                </div>
                            <?php else: ?>
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Request Type</th>
                                        <th>Employee</th>
                                        <th>Department</th>
                                        <th>Submitted</th>
                                        <th style="text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingLeaveData as $leave): ?>
                                    <tr>
                                        <td><span class="badge" style="background-color: var(--info-bg); color: var(--primary-color); padding: 0.25rem 0.5rem;"><?php echo htmlspecialchars($leave['LeaveTypeName']); ?></span></td>
                                        <td><?php echo htmlspecialchars($leave['EmployeeName']); ?></td>
                                        <td style="color: var(--gray-500);"><?php echo htmlspecialchars($leave['Department']); ?></td>
                                        <td style="color: var(--gray-500);"><?php echo date('M d, Y', strtotime($leave['RequestDate'])); ?></td>
                                        <td style="text-align: right;">
                                            <button class="btn btn-sm btn-success" style="margin-right: 0.25rem;" 
                                                    onclick="handleLeaveAction('approve', <?php echo $leave['LeaveRequestID']; ?>)">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn btn-sm btn-danger" 
                                                    onclick="handleLeaveAction('reject', <?php echo $leave['LeaveRequestID']; ?>)">
                                                <i class="fas fa-times"></i> Reject
                                            </button>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

    <div style="margin-bottom: 1rem;">
                <h2 style="margin: 0 0 0.75rem 0; font-size: 16px; font-weight: 600; color: #333;">HR Modules</h2>
            </div>
            <div class="row">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                   <a href="employee_directory.php" style="text-decoration: none; color: inherit;">
                   <div class="card" style="cursor: pointer; transition: all 0.3s;"
                    onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'"
                    onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                    <div style="font-size: 40px; margin-bottom: 0.75rem;">👤</div>
                    <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Employee Directory</h3>
                    <p style="margin: 0; font-size: 12px; color: #666;">Manage employee records & profiles</p>
            </div>
    </div>
    </a>

                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="timesheets.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" 
                            onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" 
                            onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">📋</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Timesheets</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Track hours & attendance</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="payroll_management.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">💰</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Payroll</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Process salaries & benefits</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
           <div class="row">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="leave_management.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">🏖️</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Leave Management</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Manage leave requests</p>
                            </div>
                        </div>
                    </a>
                </div>
                 <div class="col-md-4" style="margin-bottom: 0.75rem;">
    <a href="assign_roles.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                <div style="font-size: 40px; margin-bottom: 0.75rem;">🔐</div>
                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Roles & Permissions</h3>
                <p style="margin: 0; font-size: 12px; color: #666;">Assign roles & access control</p>
            </div>
        </div>
    </a>
</div>
                 <div class="col-md-4" style="margin-bottom: 0.75rem;">
    <a href="reports.php" style="text-decoration: none; color: inherit;">
        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                <div style="font-size: 40px; margin-bottom: 0.75rem;">📊</div>
                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Reports</h3>
                <p style="margin: 0; font-size: 12px; color: #666;">Generate reports and analytics</p>
            </div>
        </div>
    </a>
</div>

    <div id="employeeDirectoryModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Employee Department Breakdown</h3>
                <button onclick="closeModal('employeeDirectoryModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="margin: 0; font-size: 14px;">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th style="text-align: right;">Number of Employees</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($departmentData)): ?>
                            <?php foreach ($departmentData as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['Department'] ?? 'Unassigned'); ?></td>
                                    <td style="text-align: right;"><?php echo $row['employeeCount']; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" style="text-align: center; padding: 2rem;">No department data found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button class="btn btn-outline" onclick="closeModal('employeeDirectoryModal')" style="padding: 0.5rem 1rem; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>
    
    <div id="leaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 800px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Employees Currently On Leave</h3>
                <button onclick="closeModal('leaveModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="margin: 0; font-size: 14px;">
                    <thead>
                        <tr>
                            <th>Name of Employee</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Type of Leave</th>
                            <th>Date(s)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($onLeaveData)): ?>
                            <?php foreach ($onLeaveData as $leave): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($leave['EmployeeName']); ?></td>
                                    <td><?php echo htmlspecialchars($leave['Department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($leave['Role'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($leave['LeaveTypeName']); ?></td>
                                    <td>
                                        <?php 
                                            // Format date range
                                            echo date("M j, Y", strtotime($leave['StartDate'])); 
                                            if ($leave['StartDate'] != $leave['EndDate']) {
                                                echo " - " . date("M j, Y", strtotime($leave['EndDate']));
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center; padding: 2rem;">No employees are on approved leave.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button class="btn btn-outline" onclick="closeModal('leaveModal')" style="padding: 0.5rem 1rem; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>

   <div id="pendingLeaveModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 900px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Pending Leave Requests</h3>
                <button onclick="closeModal('pendingLeaveModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="margin: 0; font-size: 14px;">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Department</th>
                            <th>Role</th>
                            <th>Leave Type</th>
                            <th>Date(s)</th>
                            <th>Submitted</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($pendingLeaveData)): ?>
                            <?php foreach ($pendingLeaveData as $request): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['EmployeeName']); ?></td>
                                    <td><?php echo htmlspecialchars($request['Department'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($request['Role'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($request['LeaveTypeName']); ?></td>
                                    <td>
                                        <?php 
                                            echo date("M j, Y", strtotime($request['StartDate'])); 
                                            if ($request['StartDate'] != $request['EndDate']) {
                                                echo " - " . date("M j, Y", strtotime($request['EndDate']));
                                            }
                                        ?>
                                    </td>
                                    <td><?php echo date("M j, Y", strtotime($request['RequestDate'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" style="text-align: center; padding: 2rem;">No pending leave requests.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button class="btn btn-outline" onclick="closeModal('pendingLeaveModal')" style="padding: 0.5rem 1rem; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>

    <div id="payrollModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 10000; align-items: center; justify-content: center; overflow: auto;">
        <div style="background: white; padding: 1.5rem; border-radius: 8px; width: 90%; max-width: 600px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); margin: auto; max-height: 90vh; overflow-y: auto;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0; font-size: 18px;">Monthly Payroll Breakdown</h3>
                <button onclick="closeModal('payrollModal')" style="background: none; border: none; font-size: 20px; cursor: pointer; padding: 0; width: 24px; height: 24px;">&times;</button>
            </div>
            <div style="max-height: 400px; overflow-y: auto;">
                <table class="table" style="margin: 0; font-size: 14px;">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th style="text-align: right;">Monthly Payroll</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Human Resource</td>
                            <td style="text-align: right;">₱0.00</td>
                        </tr>
                        <tr>
                            <td>Sales and Customer Relation Management</td>
                            <td style="text-align: right;">₱0.00</td>
                        </tr>
                        <tr>
                            <td>Inventory</td>
                            <td style="text-align: right;">₱0.00</td>
                        </tr>
                        <tr>
                            <td>Procurement</td>
                            <td style="text-align: right;">₱0.00</td>
                        </tr>
                        <tr>
                            <td>Accounting</td>
                            <td style="text-align: right;">₱0.00</td>
                        </tr>
                        <tr style="border-top: 2px solid #333;">
                            <td><strong>GRAND TOTAL</strong></td>
                            <td style="text-align: right;"><strong>₱156,400</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 1rem; text-align: right;">
                <button class="btn btn-outline" onclick="closeModal('payrollModal')" style="padding: 0.5rem 1rem; font-size: 14px;">Close</button>
            </div>
        </div>
    </div>
    
    <div id="alertModal" class="modal-overlay" style="display: none;">
        <div class="modal-content alert-modal-content">
            <div id="alertModalIcon" class="alert-modal-icon">
                <i class="fas fa-check-circle"></i> 
            </div>
            <h2 id="alertModalTitle" style="margin: 0 0 0.5rem 0;">Success</h2>
            <p id="alertModalMessage" style="margin-bottom: 1.5rem;">Your action was completed.</p>
            <button id="alertModalButton" class="btn btn-primary">Continue</button>
        </div>
    </div>
    
    <script>
        // ==============================================
        //  MODAL VISIBILITY FUNCTIONS
        // ==============================================
        
        // JS for handling modal visibility
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                modal.style.display = 'flex';
                // Optional: add a slight delay for the transform effect (see CSS)
                setTimeout(() => {
                    modal.querySelector('.modal-content').style.transform = 'scale(1)';
                }, 10);
            }
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) {
                // Optional: reverse the transform effect before hiding
                modal.querySelector('.modal-content').style.transform = 'scale(0.95)';
                setTimeout(() => {
                    modal.style.display = 'none';
                    
                    // ✅ If closing the alert modal, remove its specific click listener to prevent bugs
                    if (modalId === 'alertModal') {
                         const alertBtn = document.getElementById('alertModalButton');
                         // Clone and replace to remove all event listeners safely
                         alertBtn.parentNode.replaceChild(alertBtn.cloneNode(true), alertBtn);
                    }
                }, 300); // Wait for transition to complete
            }
        }
        
        // ==============================================
        //  ✅ NEW: CUSTOM ALERT FUNCTION
        // ==============================================
        function showCustomAlert(message, type = 'success') {
            const modal = document.getElementById('alertModal');
            const iconEl = document.getElementById('alertModalIcon');
            const titleEl = document.getElementById('alertModalTitle');
            const messageEl = document.getElementById('alertModalMessage');
            const buttonEl = document.getElementById('alertModalButton');
            
            // Clear old classes
            iconEl.className = 'alert-modal-icon';
            buttonEl.className = 'btn';
            
            if (type === 'success') {
                iconEl.innerHTML = '<i class="fas fa-check-circle"></i>';
                iconEl.classList.add('success');
                titleEl.textContent = 'Success';
                buttonEl.classList.add('btn-success');
                buttonEl.textContent = 'Continue';
            } else { // 'error'
                iconEl.innerHTML = '<i class="fas fa-times-circle"></i>';
                iconEl.classList.add('error');
                titleEl.textContent = 'Error';
                buttonEl.classList.add('btn-danger');
                buttonEl.textContent = 'Try Again';
            }
            
            messageEl.textContent = message;
            
            // Default button behavior: just close the modal
            buttonEl.onclick = () => closeModal('alertModal');
            
            openModal('alertModal');
        }
        
        // ==============================================
        //  MODAL EVENT LISTENERS
        // ==============================================
        
        // Listener for Employee Directory Modal
        document.getElementById('employeeDirectoryModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal('employeeDirectoryModal');
            }
        });

        // Listener for On Leave Modal
        document.getElementById('leaveModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal('leaveModal');
            }
        });

        // Listener for Pending Leave Modal
        document.getElementById('pendingLeaveModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal('pendingLeaveModal');
            }
        });

        // Listener for Payroll Modal
        document.getElementById('payrollModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal('payrollModal');
            }
        });
        
        // ✅ Listener for new Alert Modal
        document.getElementById('alertModal').addEventListener('click', function(event) {
            if (event.target === this) {
                // We only want the button to close this modal
                // closeModal('alertModal');
            }
        });

        // Close modal when escape key is pressed (for any open modal)
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                // ✅ Added 'alertModal' to the list
                const modals = ['leaveModal', 'employeeDirectoryModal', 'pendingLeaveModal', 'payrollModal', 'CreateEmployeeModal', 'alertModal'];
                modals.forEach(id => {
                    const modal = document.getElementById(id);
                    if (modal && modal.style.display === 'flex') {
                        // Only close if it's not the alert modal (which requires button click)
                        if (id !== 'alertModal') {
                            closeModal(id);
                        }
                    }
                });
            }
        });

        // ==============================================
        //  ✅ START: CASCADING DROPDOWN LOGIC
        // ==============================================

        // 1. Define the mapping from Department <select> value to Role <optgroup> label
        const departmentToRoleGroupMap = {
            // --- Simple Modal Values ('CreateEmployeeModal') ---
            "Inventory": "Inventory Management",
            "Sales and Customer Relation Management": "Sales and Customer Management",
            "Procurement": "Procurement",
            "Accounting": "Accounting",
            "Human Resource": "Human Resource (HR)",
        };

        /**
         * Filters the Role dropdown based on the selected Department.
         * @param {HTMLSelectElement} departmentSelect - The department dropdown element.
         * @param {HTMLSelectElement} roleSelect - The role dropdown element.
         */
        function filterRoleDropdown(departmentSelect, roleSelect) {
            // Ensure elements exist before proceeding
            if (!departmentSelect || !roleSelect) return; 
            
            const selectedDeptValue = departmentSelect.value;
            const targetOptgroupLabel = departmentToRoleGroupMap[selectedDeptValue];
            
            const allOptgroups = roleSelect.querySelectorAll('optgroup');
            let hasVisibleOptions = false;
            
            allOptgroups.forEach(optgroup => {
                // Check if the current optgroup's label matches our target
                if (optgroup.label === targetOptgroupLabel) {
                    optgroup.hidden = false; // Show this group
                    hasVisibleOptions = true;
                } else {
                    optgroup.hidden = true; // Hide this group
                }
            });

            // Reset the role dropdown's selection
            roleSelect.value = '';
            
            // If no department was selected (value is ""), show all and disable
            if (!selectedDeptValue) {
                allOptgroups.forEach(optgroup => {
                    optgroup.hidden = false; // Show all
                });
                roleSelect.disabled = true; // Disable role dropdown
            } else {
                // Enable the role dropdown only if we found a matching group
                roleSelect.disabled = !hasVisibleOptions; 
            }
        }

        // 2. Get references to all four dropdowns
        const simpleDeptSelect = document.getElementById('Department_simple');
        const simpleRoleSelect = document.getElementById('Role_simple');

        // 3. Attach event listeners
        if (simpleDeptSelect && simpleRoleSelect) {
            // Add listener for the simple modal
            simpleDeptSelect.addEventListener('change', function() {
                filterRoleDropdown(simpleDeptSelect, simpleRoleSelect);
            });
            
            // Initial state for simple modal (on page load)
            filterRoleDropdown(simpleDeptSelect, simpleRoleSelect);
        }

        // ==============================================
        //  ✅ END: NEW CASCADING DROPDOWN LOGIC
        // ==============================================


        // Override openModal and closeModal to reset the form
        const originalOpenModal = openModal;
        openModal = function(modalId) {
            if (modalId === 'CreateEmployeeModal') { 
                document.getElementById('addEmployeeFormSimple').reset();
                filterRoleDropdown(simpleDeptSelect, simpleRoleSelect);
            }
            originalOpenModal(modalId); // Call the original function
        }

        const originalCloseModal = closeModal;
        closeModal = function(modalId) {
            if (modalId === 'CreateEmployeeModal') { 
                setTimeout(() => {
                    document.getElementById('addEmployeeFormSimple').reset();
                    filterRoleDropdown(simpleDeptSelect, simpleRoleSelect);
                }, 300); // Wait for closing animation
            }
            originalCloseModal(modalId); // Call the original function
        }
        
        // ==============================================
        //  HANDLER FOR THE SIMPLE ADD EMPLOYEE FORM
        // ==============================================
        document.getElementById('addEmployeeFormSimple').addEventListener('submit', function(event) {
            event.preventDefault(); // Stop the default form submission
            
            // Get the submit button and disable it
            const submitButton = this.querySelector('button[type="submit"]');
            const originalButtonText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.textContent = 'Saving...';
            
            const formData = new FormData(this);
            
            // 'this.action' will be "index.php"
            const formAction = this.action; 
            
            fetch(formAction, {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok. Status: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // ✅ *** CHANGED ***
                if (data.status === 'success') {
                    // SUCCESS: Close form modal, show success alert
                    closeModal('CreateEmployeeModal');
                    showCustomAlert(data.message, 'success');
                    
                    // Add click listener to the new alert's button to reload the page
                    document.getElementById('alertModalButton').onclick = () => {
                        window.location.href = 'index.php'; // Reload to see updated employee count
                    };
                    
                } else {
                    // SERVER-SIDE ERROR: Show error alert
                    showCustomAlert(data.message || 'An error occurred.', 'error');
                    // Re-enable the form button
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                }
            })
            .catch(error => {
                // ✅ *** CHANGED ***
                // FETCH/NETWORK ERROR: Show error alert
                console.error('Fetch Error:', error);
                showCustomAlert('A connection error occurred. Please try again.', 'error');
                // Re-enable the form button
                submitButton.disabled = false;
                submitButton.textContent = originalButtonText;
            });
        });
        
        // ==============================================
        //  HANDLE LEAVE REQUEST APPROVE/REJECT
        // ==============================================
        function handleLeaveAction(action, leaveRequestId) {
            if (!confirm(`Are you sure you want to ${action} this leave request?`)) {
                return;
            }
            
            const formData = new FormData();
            formData.append('action', action);
            formData.append('leaveRequestId', leaveRequestId);
            
            fetch('index.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    showModal('Success', data.message, 'success');
                    // Reload page after 1.5 seconds to refresh the pending approvals table
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    showModal('Error', data.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showModal('Error', 'A connection error occurred. Please try again.', 'error');
            });
        }
        
    </script>
    <script src="../js/app.js"></script>
</body>
</html>
