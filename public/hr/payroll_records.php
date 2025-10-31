<?php
$branch = $_GET['branch'] ?? 'Main Branch';
$dept = $_GET['dept'] ?? 'All Departments';
?>
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
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h3 class="fw-bold mb-1"><?= htmlspecialchars($dept) ?> Department - Payroll</h3>
      <p class="text-muted mb-0" style="font-size:14px;">Branch: <strong><?= htmlspecialchars($branch) ?></strong></p>
    </div>
    <button class="btn btn-outline-secondary btn-sm load-page" data-page="payroll_departments.php" data-branch="<?= htmlspecialchars($branch) ?>">← Back to Departments</button>
  </div>

  <!-- ACTION BAR -->
  <div class="card mb-4 shadow-sm border-0">
    <div class="card-body d-flex justify-content-between align-items-center py-2">
      <h6 class="mb-0 fw-semibold text-secondary">Payroll Records</h6>
      <button id="create_new" class="btn btn-primary btn-sm px-3">➕ Add New Payroll</button>
    </div>
  </div>

  <!-- PAYROLL TABLE -->
  <div class="card shadow-sm border-0">
    <div class="card-body">
      <table class="table table-hover table-striped table-bordered align-middle mb-0">
        <thead class="table-light text-center">
          <tr>
            <th>#</th>
            <th>Employee</th>
            <th>Department</th>
            <th>Store</th>
            <th>Hours</th>
            <th>Gross</th>
            <th>Deductions</th>
            <th>Net Pay</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td class="text-center">1</td>
            <td>Juan Dela Cruz</td>
            <td>Sales</td>
            <td>Ayala Center Cebu</td>
            <td class="text-center">80</td>
            <td>20000</td>
            <td>4500</td>
            <td>15500</td>
            <td class="text-center">
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary view_btn">View</button>
                <button class="btn btn-sm btn-outline-success edit_btn">Edit</button>
                <button class="btn btn-sm btn-outline-danger delete_btn">Delete</button>
              </div>
            </td>
          </tr>
          <tr>
            <td class="text-center">2</td>
            <td>Maria Santos</td>
            <td>HR</td>
            <td>Greenbelt Main</td>
            <td class="text-center">75</td>
            <td>18000</td>
            <td>3000</td>
            <td>15000</td>
            <td class="text-center">
              <div class="btn-group">
                <button class="btn btn-sm btn-outline-primary view_btn">View</button>
                <button class="btn btn-sm btn-outline-success edit_btn">Edit</button>
                <button class="btn btn-sm btn-outline-danger delete_btn">Delete</button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
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
          <div class="row mb-3">
            <div class="col-md-6"><label class="form-label">Employee ID</label><input type="text" id="empID" class="form-control form-control-sm"></div>
            <div class="col-md-6"><label class="form-label">Employee Name</label><input type="text" id="empName" class="form-control form-control-sm"></div>
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

  function updateRowNumbers(){
    $('table tbody tr').each(function(i){ $(this).find('td:first').text(i+1); });
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

  // Delete
  $('.delete_btn').on('click', function(){
    if(confirm('Are you sure you want to delete this payroll record?')){
      $(this).closest('tr').remove();
      updateRowNumbers();
      alert('Record deleted successfully!');
    }
  });

  // Save (Submit)
  $('#detailedPayrollForm').on('submit', function(e){
    e.preventDefault();
    const name = $('#empName').val();
    const dept = $('#departmentDropdown').val();
    const hours = $('#workHours').val();
    const gross = parseFloat($('#basicSalary').val());
    const totalDed = parseFloat($('#totalDeductionsSummary').text().replace(/₱|,/g,''))||0;
    const net = gross - totalDed;

    if(editRow){
      editRow.find('td:eq(1)').text(name);
      editRow.find('td:eq(2)').text(dept);
      editRow.find('td:eq(4)').text(hours);
      editRow.find('td:eq(5)').text(gross);
      editRow.find('td:eq(6)').text(totalDed);
      editRow.find('td:eq(7)').text(net);
      alert('Payroll updated successfully!');
    } else {
      const newRow = `<tr>
        <td class="text-center">#</td>
        <td>${name}</td>
        <td>${dept}</td>
        <td>Main Branch</td>
        <td class="text-center">${hours}</td>
        <td>${gross}</td>
        <td>${totalDed}</td>
        <td>${net}</td>
        <td class="text-center">
          <div class="btn-group">
            <button class="btn btn-sm btn-outline-primary view_btn">View</button>
            <button class="btn btn-sm btn-outline-success edit_btn">Edit</button>
            <button class="btn btn-sm btn-outline-danger delete_btn">Delete</button>
          </div>
        </td>
      </tr>`;
      $('table tbody').append(newRow);
      updateRowNumbers();
      alert('Payroll added successfully!');
    }
    $('#detailedPayrollModal').modal('hide');
  });

  $('#detailedPayrollModal').on('hidden.bs.modal', function () {
    $('#detailedPayrollModal :input').prop('disabled', false);
  });

});
</script>
</body>
</html>
