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

$branchID = isset($_GET['branch_id']) ? intval($_GET['branch_id']) : 0;
if ($branchID <= 0) die("Invalid branch ID.");

// Get branch name
$branch = dbFetchOne("SELECT BranchName FROM branches WHERE BranchID = ?", [$branchID]);

// Get departments in that branch
$query = "SELECT DepartmentID, DepartmentName FROM departments WHERE BranchID = ? ORDER BY DepartmentName";
$departments = dbFetchAll($query, [$branchID]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments - <?= htmlspecialchars($branch['BranchName']) ?></title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
h1 {
  color: #333;
  margin-bottom: 25px;
}
.dept-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 15px;
}
.dept-card {
  display: block;
  background: #f9f9ff;
  padding: 20px;
  border-radius: 10px;
  text-decoration: none;
  color: #333;
  box-shadow: 0 1px 4px rgba(0,0,0,0.05);
  transition: all 0.2s ease-in-out;
}
.dept-card:hover {
  background: #6b46c1;
  color: #fff;
  transform: translateY(-3px);
  box-shadow: 0 4px 10px rgba(107,70,193,0.3);
}
.dept-card i {
  font-size: 30px;
  margin-bottom: 10px;
  color: #6b46c1;
  transition: color 0.2s;
}
.dept-card:hover i {
  color: #fff;
}
.back-link {
  display: inline-block;
  margin-bottom: 20px;
  text-decoration: none;
  background: #eaeaea;
  color: #333;
  padding: 6px 12px;
  border-radius: 6px;
}
.back-link:hover {
  background: #ddd;
}
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>

<main>
  <a href="timesheets.php" class="back-link">&larr; Back to Branches</a>
  <h1><?= htmlspecialchars($branch['BranchName']) ?> - Departments</h1>

  <div class="dept-grid">
    <?php if (!empty($departments)): ?>
      <?php foreach ($departments as $d): ?>
        <a class="dept-card" href="employees.php?department_id=<?= $d['DepartmentID'] ?>">
          <h3><?= htmlspecialchars($d['DepartmentName']) ?></h3>
        </a>
      <?php endforeach; ?>
    <?php else: ?>
      <p>No departments found in this branch.</p>
    <?php endif; ?>
  </div>
</main>
</body>
</html>
