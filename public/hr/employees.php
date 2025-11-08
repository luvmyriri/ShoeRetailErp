<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'HR'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$departmentID = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
if ($departmentID <= 0) die("Invalid department ID.");

// Get department info
$dept = dbFetchOne("\r
    SELECT d.DepartmentName, b.BranchID, b.BranchName \r
    FROM Departments d \r
    JOIN Branches b ON d.BranchID = b.BranchID \r
    WHERE d.DepartmentID = ?\r
", [$departmentID]);

// Fetch employees
$employees = dbFetchAll("\r
    SELECT EmployeeID, FirstName, LastName \r
    FROM Employees \r
    WHERE DepartmentID = ? \r
    ORDER BY LastName, FirstName\r
", [$departmentID]);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employees - <?= htmlspecialchars($dept['DepartmentName']) ?> - Shoe Retail ERP</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>

<div class="main-wrapper" style="margin-left: 0;">
  <main class="main-content">
    <div class="page-header">
      <div class="page-header-title">
        <h1><?= htmlspecialchars($dept['DepartmentName']) ?> - Employees</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          <a href="departments.php?branch_id=<?= $dept['BranchID'] ?>">Departments</a> / 
          Employees
        </div>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.location.href='departments.php?branch_id=<?= $dept['BranchID'] ?>'"><i class="fas fa-arrow-left"></i> Back</button>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Employee Attendance - <?= htmlspecialchars($today) ?></h3>
      </div>
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Employee Name</th>
              <th>Date</th>
              <th>Time In</th>
              <th>Time Out</th>
              <th>Notes</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($employees)): ?>
              <?php foreach ($employees as $emp): ?>
                <?php
                  // Fetch today's attendance for this employee
                  $att = dbFetchOne("
                      SELECT LogInTime, LogOutTime, Notes 
                      FROM Attendance 
                      WHERE EmployeeID = ? AND AttendanceDate = ?
                      LIMIT 1
                  ", [$emp['EmployeeID'], $today]);
                ?>
                <tr>
                  <td><?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?></td>
                  <td><?= htmlspecialchars($today) ?></td>
                  <td><?= htmlspecialchars($att['LogInTime'] ?? '—') ?></td>
                  <td><?= htmlspecialchars($att['LogOutTime'] ?? '—') ?></td>
                  <td style="color: var(--gray-600); font-size: 13px;"><?= htmlspecialchars($att['Notes'] ?? 'No notes') ?></td>
                  <td>
                    <a class="btn btn-sm btn-primary" href="employee-timesheet.php?employee_id=<?= $emp['EmployeeID'] ?>">
                      <i class="fas fa-clock"></i> Timesheet
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                  <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 1rem; display: block;"></i>
                  No employees found in this department.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
</body>
</html>
