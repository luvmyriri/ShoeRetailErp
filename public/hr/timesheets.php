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
$query = "SELECT BranchID, BranchName, Location FROM Branches ORDER BY BranchName";
$branches = dbFetchAll($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Timesheets - Shoe Retail ERP</title>
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
        <h1>Timesheets - Select Branch</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          Timesheets
        </div>
      </div>
      <div class="page-header-actions">
        <a href="index.php" class="btn btn-outline btn-sm">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <div class="card">
      <div class="card-header">
        <h3>Select a Branch</h3>
      </div>
      <div class="card-body">
        <?php if (!empty($branches)): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Branch Name</th>
                  <th>Location</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($branches as $b): ?>
                  <tr>
                    <td><?= htmlspecialchars($b['BranchName']) ?></td>
                    <td><?= htmlspecialchars($b['Location']) ?></td>
                    <td>
                      <a class="btn btn-sm btn-primary" href="departments.php?branch_id=<?= $b['BranchID'] ?>">
                        <i class="fas fa-building"></i> View Departments
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p style="text-align: center; padding: 2rem; color: var(--gray-500);">
            <i class="fas fa-building" style="font-size: 2rem; display: block; margin-bottom: 1rem;"></i>
            No branches found.
          </p>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
</body>
</html>
