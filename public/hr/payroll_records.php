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

$branch = $_GET['branch'] ?? null;
$dept = $_GET['dept'] ?? null;
$status = $_GET['status'] ?? null;
require_once '../../config/database.php';

$query = "SELECT 
    p.PayrollID,
    CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
    d.DepartmentName,
    s.StoreName,
    p.HoursWorked,
    p.GrossPay,
    p.Deductions,
    p.NetPay,
    p.Status
  FROM Payroll p
  JOIN Employees e ON p.EmployeeID = e.EmployeeID
  LEFT JOIN Departments d ON e.DepartmentID = d.DepartmentID
  LEFT JOIN Branches b ON d.BranchID = b.BranchID
  LEFT JOIN Stores s ON e.StoreID = s.StoreID
  WHERE 1=1";
$params = [];
if ($branch) {
  $query .= " AND (b.BranchName = ? OR s.StoreName = ?)";
  $params[] = $branch;
  $params[] = $branch;
}
if ($dept && $dept !== 'All Departments') {
  $query .= " AND d.DepartmentName = ?";
  $params[] = $dept;
}
if ($status) {
  $query .= " AND p.Status = ?";
  $params[] = $status;
}
$query .= " ORDER BY p.PayPeriodEnd DESC, EmployeeName";
$records = dbFetchAll($query, $params);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payroll Records - Shoe Retail ERP</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
</head>
<body>
<?php include '../includes/navbar.php'; ?>
<?php include '../includes/modal.php'; ?>
<div class="main-wrapper" style="margin-left: 0;">
  <main class="main-content">
    <div class="page-header">
      <div class="page-header-title">
        <h1><?= htmlspecialchars($dept) ?> Department - Payroll</h1>
        <div class="page-header-breadcrumb">
          <a href="/ShoeRetailErp/public/index.php">Home</a> / 
          <a href="index.php">HR</a> / 
          <a href="payroll_management.php">Payroll</a> / 
          Records
        </div>
        <p class="text-muted" style="font-size:14px; margin-top:0.5rem;">Branch: <strong><?= htmlspecialchars($branch) ?></strong></p>
      </div>
      <div class="page-header-actions">
        <button class="btn btn-outline btn-sm load-page" data-page="payroll_departments.php" data-branch="<?= htmlspecialchars($branch) ?>">
          <i class="fas fa-arrow-left"></i> Back to Departments
        </button>
      </div>
    </div>

    <!-- ACTION BAR -->
    <div class="card" style="margin-bottom: 1rem;">
      <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
        <h3>Payroll Records</h3>
        <button id="create_new" class="btn btn-primary btn-sm">
          <i class="fas fa-plus"></i> Add New Payroll
        </button>
      </div>
    </div>

    <!-- PAYROLL TABLE -->
    <div class="card">
      <div class="card-body table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th style="text-align: center;"><input type="checkbox" id="check_all"></th>
              <th>#</th>
              <th>Employee</th>
              <th>Department</th>
              <th>Store</th>
              <th style="text-align: center;">Hours</th>
              <th>Gross</th>
              <th>Deductions</th>
              <th>Net Pay</th>
              <th>Status</th>
              <th style="text-align: center;">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($records)): ?>
              <?php $i=0; foreach ($records as $row): $i++; ?>
                <tr data-payroll-id="<?= htmlspecialchars($row['PayrollID']) ?>">
                  <td style="text-align: center;"><input type="checkbox" name="payroll_id" value="<?= htmlspecialchars($row['PayrollID']) ?>"></td>
                  <td style="text-align: center;"><?= $i ?></td>
                  <td><?= htmlspecialchars($row['EmployeeName']) ?></td>
                  <td><?= htmlspecialchars($row['DepartmentName'] ?? '') ?></td>
                  <td><?= htmlspecialchars($row['StoreName'] ?? '') ?></td>
                  <td style="text-align: center;"><?= htmlspecialchars($row['HoursWorked']) ?></td>
                  <td>₱<?= number_format((float)$row['GrossPay'], 2) ?></td>
                  <td>₱<?= number_format((float)$row['Deductions'], 2) ?></td>
                  <td>₱<?= number_format((float)$row['NetPay'], 2) ?></td>
                  <td><?= htmlspecialchars($row['Status'] ?? '') ?></td>
                  <td style="text-align: center;">
                    <button class="btn btn-sm btn-primary view_btn" style="margin-right: 0.25rem;">View</button>
                    <button class="btn btn-sm btn-success edit_btn" style="margin-right: 0.25rem;">Edit</button>
                    <button class="btn btn-sm btn-danger delete_btn">Delete</button>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="11" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                  <i class="fas fa-receipt" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5; display: block;"></i>
                  No payroll records found.
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

<!-- ✅ SINGLE MODAL ONLY -->
<div class="modal fade" id="detailedPayrollModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h6 class="modal-title" id="detailedPayrollModalLabel">Payroll Sheet</h6>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form id="detailedPayrollForm">
        <div class="modal-body">
          <div class="d-flex justify-content-between mb-3">
            <div><strong>COMPANY:</strong> Shoe Retail Inc.<br><strong>BRANCH:</strong>
              <select id="branchDropdown" class="form-select form-select-sm d-inline-block w-auto">
                <option>Main Branch</option>
                <option>Branch 1</option>
                <option>Branch 2</option>
              </select>
            </div>
            <div><strong>PAY DATE:</strong> <input type="date" id="payDate" class="form-control form-control-sm w-auto d-inline-block"></div>
          </div>

          <h6>EMPLOYEE DETAILS</h6>
          <div class="row mb-3 position-relative">
            <div class="col-md-4"><label class="form-label">Employee ID</label><input type="number" id="empID" class="form-control form-control-sm" placeholder="e.g. 123"></div>
            <div class="col-md-8"><label class="form-label">Employee Name</label>
              <input type="text" id="empName" class="form-control form-control-sm" placeholder="Type to search...">
              <div id="empSuggest" class="border bg-white position-absolute w-100" style="z-index:1050; display:none; max-height:180px; overflow:auto;"></div>
            </div>
            <div class="col-md-6"><label class="form-label">Department</label>
              <select id="departmentDropdown" class="form-select form-select-sm"><option>Sales</option><option>HR</option><option>IT</option></select>
            </div>
            <div class="col-md-6"><label class="form-label">Position</label>
              <select id="positionDropdown" class="form-select form-select-sm"><option>Cashier</option><option>Manager</option><option>Staff</option></select>
            </div>
          </div>

          <h6>DEDUCTIONS</h6>
          <div class="row mb-3">
            <div class="col-md-3"><input type="number" class="form-control form-control-sm deductionInput" id="sss" placeholder="SSS"></div>
            <div class="col-md-3"><input type="number" class="form-control form-control-sm deductionInput" id="philhealth" placeholder="PhilHealth"></div>
            <div class="col-md-3"><input type="number" class="form-control form-control-sm deductionInput" id="pagibig" placeholder="Pag-IBIG"></div>
            <div class="col-md-3"><input type="number" class="form-control form-control-sm deductionInput" id="absenceDeduction" placeholder="Absence Deduction"></div>
          </div>
          <div class="mb-3"><strong>Total Deductions: </strong> <span id="totalDeductions">₱0.00</span></div>

          <div class="row mb-3">
            <div class="col-md-4"><label>Basic Salary</label><input type="number" id="basicSalary" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label>Work Hours</label><input type="number" id="workHours" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label>Type of Leave</label>
              <select id="leaveType" class="form-select form-select-sm"><option>None</option><option>Sick Leave</option><option>Vacation Leave</option></select>
            </div>
          </div>

          <h6>SUMMARY</h6>
          <div class="row mb-3">
            <div class="col-md-4"><strong>Total Gross Pay: </strong> <span id="totalGrossPay">₱0.00</span></div>
            <div class="col-md-4"><strong>Total Deductions: </strong> <span id="totalDeductionsSummary">₱0.00</span></div>
            <div class="col-md-4"><strong>Total Net Pay: </strong> <span id="totalNetPay">₱0.00</span></div>
          </div>

          <h6>BANK DETAILS</h6>
          <div class="row">
            <div class="col-md-4"><label>Bank Name</label><input type="text" id="bankName" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label>Account Name</label><input type="text" id="accountName" class="form-control form-control-sm"></div>
            <div class="col-md-4"><label>Account Number</label><input type="text" id="accountNumber" class="form-control form-control-sm"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success btn-sm">💾 Save</button>
          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function(){

  let editRow = null;

  function recalcTotals(){
    const sss = parseFloat($('#sss').val()||0);
    const philhealth = parseFloat($('#philhealth').val()||0);
    const pagibig = parseFloat($('#pagibig').val()||0);
    const absence = parseFloat($('#absenceDeduction').val()||0);
    const basic = parseFloat($('#basicSalary').val()||0);
    const totalDed = sss+philhealth+pagibig+absence;
    $('#totalDeductions').text('₱'+totalDed.toLocaleString());
    $('#totalDeductionsSummary').text('₱'+totalDed.toLocaleString());
    $('#totalGrossPay').text('₱'+basic.toLocaleString());
    $('#totalNetPay').text('₱'+(basic-totalDed).toLocaleString());
  }

  $('.deductionInput, #basicSalary').on('input', recalcTotals);

  // Employee search/autocomplete
  let empTimer = null;
  function renderEmpSuggestions(list){
    const box = $('#empSuggest');
    if (!list || list.length === 0) { box.hide().empty(); return; }
    let html = '';
    list.forEach(it => {
      const name = `${it.FirstName} ${it.LastName}`.trim();
      const meta = [it.Email, it.DepartmentName, it.StoreName].filter(Boolean).join(' • ');
      html += `<div class="p-2 border-bottom emp-sugg-item" data-id="${it.EmployeeID}" data-name="${name}">
                 <div class="fw-semibold">${name}</div>
                 <div class="text-muted" style="font-size:12px;">${meta}</div>
               </div>`;
    });
    box.html(html).show();
  }
  $('#empName').on('input focus', function(){
    const q = $(this).val().trim();
    if (empTimer) clearTimeout(empTimer);
    empTimer = setTimeout(async ()=>{
      if (q.length < 2) { renderEmpSuggestions([]); return; }
      try {
        const res = await fetch(`/ShoeRetailErp/api/hr_integrated.php?action=search_employees&q=${encodeURIComponent(q)}&limit=10`);
        const json = await res.json();
        if (json.success) renderEmpSuggestions(json.data); else renderEmpSuggestions([]);
      } catch { renderEmpSuggestions([]); }
    }, 200);
  });
  $(document).on('click', '.emp-sugg-item', function(){
    const id = $(this).data('id');
    const name = $(this).data('name');
    $('#empID').val(id);
    $('#empName').val(name);
    $('#empSuggest').hide().empty();
  });
  $(document).on('click', function(e){ if (!$(e.target).closest('#empName, #empSuggest').length) { $('#empSuggest').hide(); } });

  function updateRowNumbers(){
    $('table tbody tr').each(function(i){ $(this).find('td').eq(1).text(i+1); });
  }

  // Add new
  $('#create_new').on('click', function(){
    editRow = null;
    $('#detailedPayrollForm')[0].reset();
    $('#detailedPayrollModalLabel').text('Add New Payroll');
    $('#detailedPayrollModal :input').prop('disabled', false);
    $('#detailedPayrollModal').modal('show');
    recalcTotals();
  });

  // Edit
  $('.edit_btn').on('click', function(){
    editRow = $(this).closest('tr');
    const data = editRow.children('td').map(function(){ return $(this).text(); }).get();
    $('#empName').val(data[1]);
    $('#departmentDropdown').val(data[2]);
    $('#basicSalary').val(parseFloat(data[5]));
    $('#workHours').val(parseFloat(data[4]));
    recalcTotals();
    $('#detailedPayrollModalLabel').text('Edit Payroll');
    $('#detailedPayrollModal :input').prop('disabled', false);
    $('#detailedPayrollModal').modal('show');
  });

  // View
  $('.view_btn').on('click', function(){
    const row = $(this).closest('tr');
    const data = row.children('td').map(function(){ return $(this).text(); }).get();
    $('#empName').val(data[1]);
    $('#departmentDropdown').val(data[2]);
    $('#basicSalary').val(parseFloat(data[5]));
    $('#workHours').val(parseFloat(data[4]));
    recalcTotals();
    $('#detailedPayrollModalLabel').text('View Payroll');
    $('#detailedPayrollModal :input').prop('disabled', true);
    $('#detailedPayrollModal').modal('show');
  });

  // Delete (void)
  $('.delete_btn').on('click', async function(){
    const row = $(this).closest('tr');
    const pid = $(row).data('payroll-id');
    if (!pid) return;
    if(!confirm('Are you sure you want to void this payroll record?')) return;
    try {
      const res = await fetch('/ShoeRetailErp/api/hr_integrated.php?action=delete_payroll', {
        method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ payroll_id: pid })
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.message||'Failed');
      $(row).remove();
      updateRowNumbers();
      showModal('Success', 'Record voided', 'success');
    } catch (e) {
      showModal('Error', 'Error: ' + e, 'error');
    }
  });

  // Save (Submit)
  $('#detailedPayrollForm').on('submit', async function(e){
    e.preventDefault();
    const employeeId = parseInt($('#empID').val() || '0', 10);
    const payDate = $('#payDate').val();
    const hours = parseFloat($('#workHours').val() || '0');
    const gross = parseFloat($('#basicSalary').val() || '0');
    const totalDed = parseFloat($('#totalDeductionsSummary').text().replace(/₱|,/g,''))||0;
    const payload = { employee_id: employeeId, pay_date: payDate, hours_worked: hours, gross_pay: gross, deductions: totalDed };

    try {
      if (editRow) {
        const payrollId = $(editRow).data('payroll-id');
        const res = await fetch('/ShoeRetailErp/api/hr_integrated.php?action=update_payroll', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ payroll_id: payrollId, hours_worked: hours, gross_pay: gross, deductions: totalDed })
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message||'Failed');
        showModal('Success', 'Payroll updated successfully', 'success', function() {
          window.location.reload();
        });
      } else {
        const res = await fetch('/ShoeRetailErp/api/hr_integrated.php?action=create_payroll', {
          method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload)
        });
        const json = await res.json();
        if (!json.success) throw new Error(json.message||'Failed');
        showModal('Success', 'Payroll added successfully', 'success', function() {
          window.location.reload();
        });
      }
      $('#detailedPayrollModal').modal('hide');
    } catch (err) {
      showModal('Error', 'Error: ' + err, 'error');
    }
  });

  $('#detailedPayrollModal').on('hidden.bs.modal', function () {
    $('#detailedPayrollModal :input').prop('disabled', false);
  });

  // Selection helpers for Settle Payroll integration
  $(document).on('change', '#check_all', function(){
    const c = this.checked;
    $('input[type=checkbox][name=payroll_id]').prop('checked', c).trigger('change');
  });
  $(document).on('change', 'input[type=checkbox][name=payroll_id]', function(){
    $(this).closest('tr').toggleClass('selected', this.checked);
  });
  $('table.table tbody').on('click', 'tr', function(e){
    if ($(e.target).is('input,button,.btn')) return;
    const cb = $(this).find('input[type=checkbox][name=payroll_id]')[0];
    if (!cb) return;
    cb.checked = !cb.checked;
    $(cb).trigger('change');
  });

});
</script>
  </main>
</div>
</body>
</html>
