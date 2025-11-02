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

// --- Employee ID ---
$employeeID = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;
if ($employeeID <= 0) die("Invalid employee ID.");

// --- Fetch Employee Info (include DepartmentID for back link) ---
$empQuery = "SELECT FirstName, LastName, DepartmentID FROM employees WHERE EmployeeID = ?";
$employee = dbFetchOne($empQuery, [$employeeID]);

if (!$employee) die("Employee not found.");

$departmentID = $employee['DepartmentID']; // ✅ for back link

// --- Filter Type ---
$filterType = $_GET['filter'] ?? 'weekly';

$dateStart = $dateEnd = '';
$label = '';
$navPrev = $navNext = [];

// --- Handle each filter type ---
if ($filterType === 'daily') {
    $selectedDate = $_GET['date'] ?? date('Y-m-d');
    $dateStart = $dateEnd = $selectedDate;
    $label = "Date: $selectedDate";

    $prevDate = date('Y-m-d', strtotime("$selectedDate -1 day"));
    $nextDate = date('Y-m-d', strtotime("$selectedDate +1 day"));
    $navPrev = ['date' => $prevDate];
    $navNext = ['date' => $nextDate];
} 
elseif ($filterType === 'monthly') {
    $selectedMonth = $_GET['month'] ?? date('Y-m');
    $dateStart = $selectedMonth . '-01';
    $dateEnd = date('Y-m-t', strtotime($dateStart));
    $label = date('F Y', strtotime($dateStart));

    $prevMonth = date('Y-m', strtotime("$dateStart -1 month"));
    $nextMonth = date('Y-m', strtotime("$dateStart +1 month"));
    $navPrev = ['month' => $prevMonth];
    $navNext = ['month' => $nextMonth];
} 
else { // weekly
    $selectedWeek = isset($_GET['week']) ? intval($_GET['week']) : date('W');
    $selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
    $dto = new DateTime();
    $dto->setISODate($selectedYear, $selectedWeek);
    $dateStart = $dto->format('Y-m-d');
    $dto->modify('+6 days');
    $dateEnd = $dto->format('Y-m-d');
    $label = "Week $selectedWeek ($dateStart → $dateEnd)";

    $prevWeek = $selectedWeek - 1;
    $nextWeek = $selectedWeek + 1;
    $prevYear = $selectedYear;
    $nextYear = $selectedYear;
    if ($prevWeek < 1) { $prevWeek = 52; $prevYear--; }
    if ($nextWeek > 52) { $nextWeek = 1; $nextYear++; }
    $navPrev = ['year' => $prevYear, 'week' => $prevWeek];
    $navNext = ['year' => $nextYear, 'week' => $nextWeek];
}

// --- Fetch Attendance ---
$attQuery = "
    SELECT AttendanceDate, LogInTime, LogOutTime, HoursWorked, Notes
    FROM attendance
    WHERE EmployeeID = ?
      AND AttendanceDate BETWEEN ? AND ?
    ORDER BY AttendanceDate ASC
";
$attendance = dbFetchAll($attQuery, [$employeeID, $dateStart, $dateEnd]);

// --- Total Hours ---
$totalHours = 0;
foreach ($attendance as $a) {
    $totalHours += floatval($a['HoursWorked']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?> - Timesheet</title>
<link rel="stylesheet" href="../css/style.css">
<style>
body {
  font-family: "Segoe UI", Arial, sans-serif;
  background: #f4f4f8;
  margin: 0;
  color: #333;
}
main {
  max-width: 900px;
  margin: 40px auto;
  background: #fff;
  padding: 30px;
  border-radius: 10px;
  box-shadow: 0 1px 6px rgba(0,0,0,0.05);
}
h1 { margin-bottom: 10px; }
.breadcrumb { margin-bottom: 20px; font-size: 14px; color: #777; }
form { margin-bottom: 20px; }
table {
  width: 100%; border-collapse: collapse; margin-top: 10px;
}
th, td {
  padding: 10px; border-bottom: 1px solid #eee; text-align: left;
}
th { background: #f9f9f9; }
tr:hover td { background: #faf7ff; }
.back-link {
  display: inline-block; margin-bottom: 15px;
  text-decoration: none; background: #eaeaea; color: #333;
  padding: 6px 12px; border-radius: 6px;
}
.back-link:hover { background: #ddd; }
.summary {
  margin-bottom: 10px; background: #f9f9ff;
  padding: 10px 15px; border-radius: 6px;
}
.filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
select, input {
  padding: 6px; border-radius: 4px; border: 1px solid #ccc;
}
button {
  padding: 6px 10px; background: #6b46c1; color: #fff;
  border: none; border-radius: 4px; cursor: pointer;
}
button:hover { background: #553c9a; }
.nav-buttons {
  display: flex; justify-content: space-between; margin: 10px 0 20px;
}
.nav-buttons a {
  background: #efefef; padding: 6px 12px; border-radius: 6px;
  text-decoration: none; color: #333;
}
.nav-buttons a:hover { background: #ddd; }
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<main>
  <!-- ✅ Fixed back link -->
  <div class="breadcrumb">
    <a href="employees.php?department_id=<?= $departmentID ?>" class="back-link">&larr; Back to Employees</a>
  </div>

  <h1><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>’s Timesheet</h1>

  <!-- Filter Form -->
  <form method="get" class="filter-group">
    <input type="hidden" name="employee_id" value="<?= $employeeID ?>">
    <label>View:</label>
    <select name="filter" id="filter" onchange="toggleInputs(this.value)">
      <option value="daily" <?= $filterType === 'daily' ? 'selected' : '' ?>>Daily</option>
      <option value="weekly" <?= $filterType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
      <option value="monthly" <?= $filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
    </select>

    <input type="date" name="date" id="date" value="<?= $_GET['date'] ?? date('Y-m-d') ?>" <?= $filterType === 'daily' ? '' : 'style="display:none"' ?>>
    <input type="week" name="weekyear" id="weekyear" value="<?= $filterType === 'weekly' ? date('Y-\WW', strtotime($dateStart)) : '' ?>" <?= $filterType === 'weekly' ? '' : 'style="display:none"' ?>>
    <input type="month" name="month" id="month" value="<?= $_GET['month'] ?? date('Y-m') ?>" <?= $filterType === 'monthly' ? '' : 'style="display:none"' ?>>

    <button type="submit">Filter</button>
  </form>

  <!-- Navigation -->
  <div class="nav-buttons">
    <?php if ($filterType === 'daily'): ?>
      <a href="?employee_id=<?= $employeeID ?>&filter=daily&date=<?= $navPrev['date'] ?>">← Previous Day</a>
      <a href="?employee_id=<?= $employeeID ?>&filter=daily&date=<?= $navNext['date'] ?>">Next Day →</a>
    <?php elseif ($filterType === 'weekly'): ?>
      <a href="?employee_id=<?= $employeeID ?>&filter=weekly&year=<?= $navPrev['year'] ?>&week=<?= $navPrev['week'] ?>">← Previous Week</a>
      <a href="?employee_id=<?= $employeeID ?>&filter=weekly&year=<?= $navNext['year'] ?>&week=<?= $navNext['week'] ?>">Next Week →</a>
    <?php else: ?>
      <a href="?employee_id=<?= $employeeID ?>&filter=monthly&month=<?= $navPrev['month'] ?>">← Previous Month</a>
      <a href="?employee_id=<?= $employeeID ?>&filter=monthly&month=<?= $navNext['month'] ?>">Next Month →</a>
    <?php endif; ?>
  </div>

  <!-- Summary -->
  <div class="summary">
    <strong><?= ucfirst($filterType) ?> Timesheet</strong><br>
    <?= htmlspecialchars($label) ?><br>
    Total Hours: <strong><?= number_format($totalHours, 2) ?></strong>
  </div>

  <!-- Attendance Table -->
  <table>
    <thead>
      <tr>
        <th>Date</th>
        <th>Log In</th>
        <th>Log Out</th>
        <th>Hours Worked</th>
        <th>Notes</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($attendance)): ?>
        <?php foreach ($attendance as $row): ?>
          <tr>
            <td><?= htmlspecialchars($row['AttendanceDate']) ?></td>
            <td><?= htmlspecialchars($row['LogInTime']) ?></td>
            <td><?= htmlspecialchars($row['LogOutTime']) ?></td>
            <td><?= htmlspecialchars($row['HoursWorked']) ?></td>
            <td><?= htmlspecialchars($row['Notes']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5">No attendance records found.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</main>

<script>
function toggleInputs(value) {
  document.getElementById('date').style.display = (value === 'daily') ? '' : 'none';
  document.getElementById('weekyear').style.display = (value === 'weekly') ? '' : 'none';
  document.getElementById('month').style.display = (value === 'monthly') ? '' : 'none';
}
</script>

</body>
</html>