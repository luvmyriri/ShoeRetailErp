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
$dept = dbFetchOne("
    SELECT d.DepartmentName, b.BranchID, b.BranchName 
    FROM departments d 
    JOIN branches b ON d.BranchID = b.BranchID 
    WHERE d.DepartmentID = ?
", [$departmentID]);

// Fetch employees
$employees = dbFetchAll("
    SELECT EmployeeID, FirstName, LastName 
    FROM employees 
    WHERE DepartmentID = ? 
    ORDER BY LastName, FirstName
", [$departmentID]);

$today = date('Y-m-d');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Employees - <?= htmlspecialchars($dept['DepartmentName']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f4f4f8;
  margin: 0;
  color: #333;
}
main {
  max-width: 1000px;
  margin: 40px auto;
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}
h1 {
  color: #333;
  margin-bottom: 20px;
}
.employee-table {
  width: 100%;
  border-collapse: collapse;
}
.employee-table th, .employee-table td {
  padding: 12px 10px;
  border-bottom: 1px solid #eee;
  text-align: left;
}
.employee-table th {
  background: #f9f9f9;
}
.employee-table tr:hover td {
  background: #f9f9ff;
}
a.view-link {
  text-decoration: none;
  background: #6b46c1;
  color: white;
  padding: 6px 10px;
  border-radius: 6px;
}
a.view-link:hover {
  background: #5930a6;
}
.back-link {
  display: inline-block;
  margin-bottom: 15px;
  text-decoration: none;
  background: #eaeaea;
  color: #333;
  padding: 6px 12px;
  border-radius: 6px;
}
.back-link:hover {
  background: #ddd;
}
.note {
  color: #666;
  font-size: 13px;
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<main>
  <a href="departments.php?branch_id=<?= $dept['BranchID'] ?>" class="back-link">&larr; Back to Departments</a>
  <h1><?= htmlspecialchars($dept['DepartmentName']) ?> - Employees</h1>

  <table class="employee-table">
    <thead>
      <tr>
        <th>Employee Name</th>
        <th>Present Date</th>
        <th>Time In</th>
        <th>Time Out</th>
        <th>Notes</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($employees)): ?>
        <?php foreach ($employees as $emp): ?>
          <?php
            // Fetch today's attendance for this employee
            $att = dbFetchOne("
                SELECT LogInTime, LogOutTime, Notes 
                FROM attendance 
                WHERE EmployeeID = ? AND AttendanceDate = ?
                LIMIT 1
            ", [$emp['EmployeeID'], $today]);
          ?>
          <tr>
            <td><?= htmlspecialchars($emp['FirstName'] . ' ' . $emp['LastName']) ?></td>
            <td><?= htmlspecialchars($today) ?></td>
            <td><?= htmlspecialchars($att['LogInTime'] ?? '—') ?></td>
            <td><?= htmlspecialchars($att['LogOutTime'] ?? '—') ?></td>
            <td class="note"><?= htmlspecialchars($att['Notes'] ?? 'No notes') ?></td>
            <td><a class="view-link" href="employee-timesheet.php?employee_id=<?= $emp['EmployeeID'] ?>">View Timesheet</a></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="6">No employees found in this department.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>
</body>
</html>
