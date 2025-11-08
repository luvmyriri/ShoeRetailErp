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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Payroll Management - Shoe Retail ERP</title>
  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>
<div class="main-wrapper" style="margin-left: 0;">
  <main class="main-content">
    <!-- Page Header -->
    <div class="page-header">
      <div class="page-header-title">
        <h1>Payroll Management</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          Payroll Management
        </div>
      </div>
      <div class="page-header-actions">
        <button id="btnSettlePayroll" class="btn btn-success">
          <i class="fas fa-money-check"></i> Settle Payroll (Selected)
        </button>
        <a href="index.php" class="btn btn-outline btn-sm">
          <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
      </div>
    </div>

    <!-- Dynamic Content Area -->
    <div id="contentArea"></div>
  </main>
</div>

<!-- Dynamic content loaded via AJAX -->
<script>
$(document).ready(function() {
  // Load branch selection first
  $('#contentArea').load('payroll_branch_select.php');

  // Dynamic navigation loader (branch → departments → records)
  $(document).on('click', '.load-page', function(e) {
    e.preventDefault();
    const page = $(this).data('page');
    const branch = $(this).data('branch');
    const dept = $(this).data('dept');

    let url = page;
    if (branch) url += '?branch=' + encodeURIComponent(branch);
    if (dept) url += (url.includes('?') ? '&' : '?') + 'dept=' + encodeURIComponent(dept);

    $('#contentArea').fadeOut(150, function() {
      $('#contentArea').load(url, function() {
        $('#contentArea').fadeIn(150);
      });
    });
  });

  // Helper functions for display formatting
  window.formatDisplay = function(str) {
    const d = new Date(str);
    return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
  };

  window.formatDate = function(str) {
    const d = new Date(str + ' 00:00:00');
    return d.toISOString().split('T')[0];
  };
  // Settle Payroll handler
  $(document).on('click', '#btnSettlePayroll', async function() {
    // Collect selected payroll IDs from loaded content (supports checkbox or data attributes)
    const ids = [];
    // Checkbox pattern: <input type="checkbox" name="payroll_id" value="123" checked>
    $('#contentArea input[type=checkbox][name=payroll_id]:checked').each(function(){ ids.push($(this).val()); });
    // Data attribute pattern on selected rows: <tr class="selected" data-payroll-id="123">
    $('#contentArea .selected[data-payroll-id]').each(function(){ ids.push($(this).data('payroll-id')); });

    let toSettle = ids.filter(Boolean);
    if (toSettle.length === 0) {
      const entered = prompt('Enter Payroll IDs to settle (comma-separated):');
      if (!entered) return;
      toSettle = entered.split(',').map(s => s.trim()).filter(Boolean);
      if (toSettle.length === 0) return;
    }

    try {
      const res = await fetch('/ShoeRetailErp/api/hr_integrated.php?action=pay_payroll', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ payroll_ids: toSettle })
      });
      const json = await res.json();
      alert((json.success ? 'Success: ' : 'Error: ') + (json.message || ''));
      // Optionally reload current view
      $('.load-page').first().trigger('click');
    } catch (e) {
      showModal('Error', 'Failed to settle payroll: ' + e, 'error');
    }
  });

  // Replace all alert() with showModal() in dynamically loaded content
  $(document).on('click', 'button, a', function() {
    // Override window.alert for dynamic content
    window.alert = function(msg) {
      const isSuccess = msg.toLowerCase().includes('success');
      showModal(isSuccess ? 'Success' : 'Alert', msg, isSuccess ? 'success' : 'info');
    };
  });
});
</script>
</body>
</html>
