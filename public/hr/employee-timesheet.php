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
$empQuery = "SELECT FirstName, LastName, DepartmentID FROM Employees WHERE EmployeeID = ?";
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
    FROM Attendance
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
<title><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?> - Timesheet - Shoe Retail ERP</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.filter-group { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.nav-buttons { display: flex; justify-content: space-between; margin: 1rem 0; }
.summary-card {
  background: var(--gray-50);
  padding: 1rem;
  border-radius: var(--radius-md);
  margin-bottom: 1rem;
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>
<div class="main-wrapper" style="margin-left: 0;">
  <main class="main-content">
    <div class="page-header">
      <div class="page-header-title">
        <h1><?= htmlspecialchars($employee['FirstName'] . ' ' . $employee['LastName']) ?>'s Timesheet</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          <a href="timesheets.php">Timesheets</a> / 
          Employee Timesheet
        </div>
      </div>
      <div class="page-header-actions">
        <a href="employees.php?department_id=<?= $departmentID ?>" class="btn btn-outline btn-sm">
          <i class="fas fa-arrow-left"></i> Back to Employees
        </a>
      </div>
    </div>

    <!-- Filter Form -->
    <div class="card" style="margin-bottom: 1rem;">
      <div class="card-body">
        <form method="get" class="filter-group">
          <input type="hidden" name="employee_id" value="<?= $employeeID ?>">
          <label>View:</label>
          <select name="filter" id="filter" class="form-control" onchange="toggleInputs(this.value)" style="width: auto;">
            <option value="daily" <?= $filterType === 'daily' ? 'selected' : '' ?>>Daily</option>
            <option value="weekly" <?= $filterType === 'weekly' ? 'selected' : '' ?>>Weekly</option>
            <option value="monthly" <?= $filterType === 'monthly' ? 'selected' : '' ?>>Monthly</option>
          </select>

          <input type="date" name="date" id="date" class="form-control" value="<?= $_GET['date'] ?? date('Y-m-d') ?>" <?= $filterType === 'daily' ? '' : 'style="display:none"' ?> style="width: auto;">
          <input type="week" name="weekyear" id="weekyear" class="form-control" value="<?= $filterType === 'weekly' ? date('Y-\WW', strtotime($dateStart)) : '' ?>" <?= $filterType === 'weekly' ? '' : 'style="display:none"' ?> style="width: auto;">
          <input type="month" name="month" id="month" class="form-control" value="<?= $_GET['month'] ?? date('Y-m') ?>" <?= $filterType === 'monthly' ? '' : 'style="display:none"' ?> style="width: auto;">

          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        </form>
      </div>
    </div>

    <!-- Navigation -->
    <div class="nav-buttons">
      <?php if ($filterType === 'daily'): ?>
        <a href="?employee_id=<?= $employeeID ?>&filter=daily&date=<?= $navPrev['date'] ?>" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Previous Day
        </a>
        <a href="?employee_id=<?= $employeeID ?>&filter=daily&date=<?= $navNext['date'] ?>" class="btn btn-outline">
          Next Day <i class="fas fa-arrow-right"></i>
        </a>
      <?php elseif ($filterType === 'weekly'): ?>
        <a href="?employee_id=<?= $employeeID ?>&filter=weekly&year=<?= $navPrev['year'] ?>&week=<?= $navPrev['week'] ?>" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Previous Week
        </a>
        <a href="?employee_id=<?= $employeeID ?>&filter=weekly&year=<?= $navNext['year'] ?>&week=<?= $navNext['week'] ?>" class="btn btn-outline">
          Next Week <i class="fas fa-arrow-right"></i>
        </a>
      <?php else: ?>
        <a href="?employee_id=<?= $employeeID ?>&filter=monthly&month=<?= $navPrev['month'] ?>" class="btn btn-outline">
          <i class="fas fa-arrow-left"></i> Previous Month
        </a>
        <a href="?employee_id=<?= $employeeID ?>&filter=monthly&month=<?= $navNext['month'] ?>" class="btn btn-outline">
          Next Month <i class="fas fa-arrow-right"></i>
        </a>
      <?php endif; ?>
    </div>

    <!-- Summary -->
    <div class="summary-card">
      <strong><?= ucfirst($filterType) ?> Timesheet</strong><br>
      <?= htmlspecialchars($label) ?><br>
      Total Hours: <strong><?= number_format($totalHours, 2) ?></strong>
    </div>

    <!-- Attendance Table -->
    <div class="card">
      <div class="card-header">
        <h3>Attendance Records</h3>
      </div>
      <div class="card-body table-responsive">
        <table class="table">
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
              <tr>
                <td colspan="5" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                  <i class="fas fa-calendar-times" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
                  No attendance records found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>
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