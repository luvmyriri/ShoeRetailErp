<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payroll Management | Shoe Retail ERP</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <style>
    body { background-color:#f9fafb; }
  </style>
</head>
<body>
<div class="container-fluid py-4 px-5">
  <div id="contentArea"></div>
</div>

<!-- ⚠️ REMOVED OLD DUPLICATE MODALS -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
});
</script>
</body>
</html>
