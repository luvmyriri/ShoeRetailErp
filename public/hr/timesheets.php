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
?>
<?php
require_once __DIR__ . '/../../config/database.php';

// Fetch all branches
$query = "SELECT BranchID, BranchName, Location FROM branches ORDER BY BranchName";
$branches = dbFetchAll($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timesheets - Branches</title>
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
  max-width: 800px;
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
ul.branch-list {
  list-style: none;
  padding: 0;
}
.branch-list li {
  padding: 12px 15px;
  border-bottom: 1px solid #eee;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.branch-list li:hover {
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
</style>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<main>
  <h1>Select a Branch</h1>
  <ul class="branch-list">
    <?php if (!empty($branches)): ?>
      <?php foreach ($branches as $b): ?>
        <li>
          <span><?= htmlspecialchars($b['BranchName'] . ' (' . $b['Location'] . ')') ?></span>
          <a class="view-link" href="departments.php?branch_id=<?= $b['BranchID'] ?>">View Departments</a>
        </li>
      <?php endforeach; ?>
    <?php else: ?>
      <li>No branches found.</li>
    <?php endif; ?>
  </ul>
</main>
</body>
</html>
