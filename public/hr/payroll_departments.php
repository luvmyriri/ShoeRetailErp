<?php
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

$branch = $_GET['branch'] ?? 'Main Branch';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
  <div>
    <h3 class="fw-bold mb-1"><?= htmlspecialchars($branch) ?> - Departments</h3>
    <p class="text-muted mb-0" style="font-size:14px;">Select a department to view employees</p>
  </div>
  <button class="btn btn-outline-secondary btn-sm load-page" data-page="payroll_branch_select.php">← Back to Branches</button>
</div>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <h5 class="fw-semibold mb-3 text-primary">🏢 Departments in <?= htmlspecialchars($branch) ?></h5>
    <div class="list-group">
      <?php
      $departments = ['Accounting', 'Human Resources', 'Inventory', 'Procurement', 'Sales'];
      foreach($departments as $d): ?>
      <div class="list-group-item d-flex justify-content-between align-items-center">
        <span><?= $d ?></span>
        <button class="btn btn-outline-success btn-sm load-page"
                data-page="payroll_records.php"
                data-branch="<?= htmlspecialchars($branch) ?>"
                data-dept="<?= $d ?>">
          View Employees
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
