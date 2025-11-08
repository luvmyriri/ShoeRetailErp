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
$branch = dbFetchOne("SELECT BranchName FROM Branches WHERE BranchID = ?", [$branchID]);

// Get departments in that branch
$query = "SELECT DepartmentID, DepartmentName FROM Departments WHERE BranchID = ? ORDER BY DepartmentName";
$departments = dbFetchAll($query, [$branchID]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Departments - <?= htmlspecialchars($branch['BranchName']) ?> - Shoe Retail ERP</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
.dept-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
  gap: 1.5rem;
  margin-top: 2rem;
}
.dept-card {
  background: white;
  border-radius: var(--radius-lg);
  padding: 2rem;
  text-align: center;
  transition: all var(--transition-base);
  cursor: pointer;
  text-decoration: none;
  color: var(--gray-800);
  box-shadow: var(--shadow-md);
}
.dept-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-xl);
  background: var(--primary-color);
  color: white;
}
.dept-card i {
  font-size: 3rem;
  margin-bottom: 1rem;
  color: var(--primary-color);
  transition: color var(--transition-base);
}
.dept-card:hover i {
  color: white;
}
.dept-card h3 {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 600;
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
        <h1><?= htmlspecialchars($branch['BranchName']) ?> - Departments</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          <a href="timesheets.php">Branches</a> / 
          Departments
        </div>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.location.href='timesheets.php'"><i class="fas fa-arrow-left"></i> Back to Branches</button>
      </div>
    </div>

    <div class="dept-grid">
      <?php if (!empty($departments)): ?>
        <?php foreach ($departments as $d): ?>
          <a class="dept-card" href="employees.php?department_id=<?= $d['DepartmentID'] ?>">
            <i class="fas fa-building"></i>
            <h3><?= htmlspecialchars($d['DepartmentName']) ?></h3>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="card" style="text-align: center; padding: 3rem;">
          <i class="fas fa-folder-open" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 1rem;"></i>
          <p style="color: var(--gray-500);">No departments found in this branch.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>
</body>
</html>
