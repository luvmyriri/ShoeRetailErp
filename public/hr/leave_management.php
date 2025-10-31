<?php
session_start();
include('../../config/database.php'); // 1. INCLUDE DATABASE CONNECTION

// 2. HANDLE APPROVE/REJECT ACTIONS
$successMessage = null;
$errorMessage = null;

// Get the global $pdo variable from database.php
// This is needed for transactions.
global $pdo; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        if (isset($_POST['approve_request'])) {
            // --- APPROVE ---
            $leaveRequestId = $_POST['leave_request_id'];
            $employeeId = $_POST['employee_id'];
            $leaveTypeId = $_POST['leave_type_id'];
            $daysRequested = $_POST['days_requested'];

            // Start transaction
            getDB()->beginTransaction();

            // Step 1: Update the leave request status
            // This assumes dbExecute() is defined in your database.php
            dbExecute("UPDATE leaverequests SET Status = 'Approved', ApprovedBy = ? WHERE LeaveRequestID = ?", [$_SESSION['user_id'] ?? 1, $leaveRequestId]); 

            // Step 2: Update the employee's leave balance
            dbExecute("UPDATE leavebalances SET Taken = Taken + ?, Remaining = Entitlement - (Taken + ?) WHERE EmployeeID = ? AND LeaveTypeID = ?", [$daysRequested, $daysRequested, $employeeId, $leaveTypeId]);

            // Commit transaction
            getDB()->commit();
            $successMessage = "Leave request approved successfully.";

        } elseif (isset($_POST['reject_request'])) {
            // --- REJECT ---
            $leaveRequestId = $_POST['leave_request_id'];
            
            // Update the leave request status
            dbExecute("UPDATE leaverequests SET Status = 'Rejected', ApprovedBy = ? WHERE LeaveRequestID = ?", [$_SESSION['user_id'] ?? 1, $leaveRequestId]);
            $successMessage = "Leave request rejected.";
            
            // NEW: Re-fetch pending requests to update the display immediately
            $sql = "SELECT lr.LeaveRequestID, lr.EmployeeID, lr.LeaveTypeID, lr.DaysRequested, lr.RequestDate,
                           e.FirstName, e.LastName, e.Role,
                           d.DepartmentName,
                           lt.LeaveTypeName
                    FROM leaverequests lr
                    LEFT JOIN employees e ON lr.EmployeeID = e.EmployeeID
                    LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
                    LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
                    WHERE lr.Status = 'Pending'
                    ORDER BY lr.RequestDate ASC";
            $pendingRequests = dbFetchAll($sql);
        }
    } catch (Exception $e) {
        // Check if a transaction is active using your new class structure
        if (getDB()->getConnection()->inTransaction()) {
            getDB()->rollback();
        }
        $errorMessage = "Error processing request: " . $e->getMessage();
    }
}


// 3. FETCH PENDING LEAVE REQUESTS
$pendingRequests = [];
try {
    // UPDATED to use LEFT JOIN to prevent errors on missing employee/dept data
    $sql = "SELECT lr.LeaveRequestID, lr.EmployeeID, lr.LeaveTypeID, lr.DaysRequested, lr.RequestDate,
                   e.FirstName, e.LastName, e.Role,
                   d.DepartmentName,
                   lt.LeaveTypeName
            FROM leaverequests lr
            LEFT JOIN employees e ON lr.EmployeeID = e.EmployeeID
            LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
            LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
            WHERE lr.Status = 'Pending'
            ORDER BY lr.RequestDate ASC";
    $pendingRequests = dbFetchAll($sql);
} catch (Exception $e) {
    $errorMessage = "Error fetching pending requests: " . $e->getMessage();
}

// 4. FETCH LEAVE HISTORY (Grouped by Department)
$leaveHistory = [];
try {
    // UPDATED to use LEFT JOIN
    $sql = "SELECT e.FirstName, e.LastName, e.Role,
                   lt.LeaveTypeName,
                   d.DepartmentName,
                   lr.StartDate, lr.EndDate, lr.Status,
                   lb.Remaining
            FROM leaverequests lr
            LEFT JOIN employees e ON lr.EmployeeID = e.EmployeeID
            LEFT JOIN departments d ON e.DepartmentID = d.DepartmentID
            LEFT JOIN leavetypes lt ON lr.LeaveTypeID = lt.LeaveTypeID
            LEFT JOIN leavebalances lb ON e.EmployeeID = lb.EmployeeID AND lr.LeaveTypeID = lb.LeaveTypeID
            WHERE lr.Status IN ('Approved', 'Rejected')
            ORDER BY d.DepartmentName, e.FirstName";
            
    $results = dbFetchAll($sql);
    
    // Group results by department
    foreach ($results as $row) {
        $department = $row['DepartmentName'] ?? 'Other'; // Handle missing departments
        $leaveHistory[$department][] = $row;
    }
} catch (Exception $e) {
    $errorMessage = "Error fetching leave history: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Leave Management | Shoe Retail ERP</title>

  <link rel="stylesheet" href="../css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">


  <style>
    /* Custom styles to restore Bootstrap look using style.css variables */
    .table th,
    .table td {
        border: 1px solid var(--gray-200);
        vertical-align: middle; 
    }

    /* Style modal header to match your primary theme color */
    .modal-header {
        background-color: var(--primary-color);
        color: white;
    }
    .modal-header h5 {
        color: white;
        margin-bottom: 0;
    }
    .modal-header .modal-close {
        color: white;
        opacity: 0.7;
        font-size: 1.5rem; /* Make it a bit bigger */
    }
    .modal-header .modal-close:hover {
        opacity: 1;
    }

    /* Fix spacing for action buttons */
    .table .btn-success {
        margin-right: var(--spacing-sm);
    }
    .table .action-forms {
        display: flex;
        gap: var(--spacing-sm);
    }

    /* Make tab headings bold when active */
    .nav-tabs .nav-link.active {
        font-weight: 600;
    }
  </style>

</head>
<body>

<?php include '../includes/navbar.php'; ?>

<div class="main-wrapper">
  <main class="main-content">

    <div class="page-header">
        <div class="page-header-title">
            <p class="text-muted" style="font-size:14px; margin-bottom: 0;">Branch: <strong>Main Branch</strong></p>
        </div>
        <div class="page-header-actions">
            <a href="index.php" class="btn btn-outline btn-sm">← Back to HR Dashboard</a>
        </div>
    </div>
    
    <!-- Display Success/Error Messages -->
    <?php if ($successMessage): ?>
        <div class="alert alert-success">
            <i class="alert-icon fas fa-check-circle"></i>
            <?php echo $successMessage; ?>
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
        </div>
    <?php endif; ?>
    <?php if ($errorMessage): ?>
        <div class="alert alert-danger">
            <i class="alert-icon fas fa-times-circle"></i>
            <?php echo $errorMessage; ?>
            <button type="button" class="alert-close" onclick="this.parentElement.style.display='none';">&times;</button>
        </div>
    <?php endif; ?>


    <ul class="nav-tabs" id="leaveTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests" type="button" role="tab">Leave Requests</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="leaves-tab" data-bs-toggle="tab" data-bs-target="#leaves" type="button" role="tab">Leaves</button>
      </li>
    </ul>

    <div class="tab-content" id="leaveTabsContent">

      <!-- TAB 1: LEAVE REQUESTS -->
      <div class="tab-pane active show" id="requests" role="tabpanel">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Employee Name</th>
                <th>Department</th>
                <th>Position</th>
                <th>Type of Leave</th>
                <th>Date Requested</th>
                <th>Days</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($pendingRequests)): ?>
                  <tr>
                      <td colspan="7" class="text-center text-muted">No pending leave requests found.</td>
                  </tr>
              <?php else: ?>
                  <?php foreach ($pendingRequests as $request): ?>
                  <tr>
                    <td><?php echo htmlspecialchars(($request['FirstName'] ?? 'Unknown') . ' ' . ($request['LastName'] ?? 'Employee')); ?></td>
                    <td><?php echo htmlspecialchars($request['DepartmentName'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($request['Role'] ?? 'N/A'); ?></td>
                    <td><?php echo htmlspecialchars($request['LeaveTypeName'] ?? 'N/A'); ?></td>
                    <td><?php echo date('M d, Y', strtotime($request['RequestDate'])); ?></td>
                    <td><?php echo htmlspecialchars($request['DaysRequested']); ?></td>
                   <td>
                      <div class="action-forms">
                          <button type="button" class="btn btn-success btn-sm approve-btn" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#approveConfirmationModal" 
                                  data-leave-id="<?php echo $request['LeaveRequestID']; ?>"
                                  data-employee-id="<?php echo $request['EmployeeID']; ?>"
                                  data-leave-type-id="<?php echo $request['LeaveTypeID']; ?>"
                                  data-days-requested="<?php echo $request['DaysRequested']; ?>">
                              Approve
                          </button>
                          
                          <button type="button" class="btn btn-danger btn-sm reject-btn" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#rejectConfirmationModal" 
                                  data-leave-id="<?php echo $request['LeaveRequestID']; ?>">
                              Reject
                          </button>

                          <?php /*
                          <form method="POST">
                              <input type="hidden" name="leave_request_id" value="<?php echo $request['LeaveRequestID']; ?>">
                              <button type="submit" name="reject_request" class="btn btn-danger btn-sm">Reject</button>
                          </form>
                          */ ?>

                          </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="modal fade" id="approveConfirmationModal" tabindex="-1" aria-labelledby="approveConfirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content" style="border-radius: 12px; text-align: center;">
      <div class="modal-body" style="padding: 30px 20px;">
        <i class="fas fa-check-circle text-success" style="font-size: 2.5rem; margin-bottom: 15px;"></i>
        <h5 class="modal-title" id="approveConfirmationModalLabel" style="font-weight: 700;">Confirm Approval?</h5>
        <p class="text-muted" style="margin-top: 5px; margin-bottom: 20px; font-size: 0.9rem;">Approving this request will update the employee's leave balance.</p>
        
        <form method="POST" id="approveFormAction">
            <input type="hidden" name="leave_request_id" id="modalApproveLeaveId">
            <input type="hidden" name="employee_id" id="modalApproveEmployeeId">
            <input type="hidden" name="leave_type_id" id="modalApproveLeaveTypeId">
            <input type="hidden" name="days_requested" id="modalApproveDaysRequested">

            <button type="submit" name="approve_request" class="btn btn-success" style="font-weight: 600;">Confirm Approve</button>
            <button type="button" class="btn btn-outline" data-bs-dismiss="modal" style="font-weight: 600; margin-left: 10px;">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</div>

      <!-- TAB 2: LEAVES -->
      <div class="tab-pane" id="leaves" role="tabpanel">

        <?php if (empty($leaveHistory)): ?>
            <p class="text-muted mt-3">No leave history found.</p>
        <?php else: ?>
            <?php foreach ($leaveHistory as $departmentName => $leaves): ?>
                <h5 class="mt-3 mb-2 text-primary"><?php echo htmlspecialchars(strtoupper($departmentName)); ?></h5>
                <div class="table-responsive mb-4">
                  <table class="table">
                    <thead>
                      <tr>
                        <th>Employee Name</th>
                        <th>Position</th>
                        <th>Leave Taken</th>
                        <th>Available Leave</th>
                        <th>Status</th>
                        <th>Details</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($leaves as $leave): ?>
                      <tr>
                        <td><?php echo htmlspecialchars(($leave['FirstName'] ?? 'Unknown') . ' ' . ($leave['LastName'] ?? 'Employee')); ?></td>
                        <td><?php echo htmlspecialchars($leave['Role'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($leave['LeaveTypeName'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($leave['Remaining'] ?? 'N/A'); ?> Days</td>
                        <td>
                            <span class="badge <?php echo $leave['Status'] == 'Approved' ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo htmlspecialchars($leave['Status']); ?>
                            </span>
                        </td>
                        <td>
                          <!-- 6. MAKE "VIEW" BUTTON DYNAMIC FOR MODAL -->
                          <button class="btn btn-outline btn-sm" 
                                  data-bs-toggle="modal" 
                                  data-bs-target="#viewLeaveModal"
                                  data-employee-name="<?php echo htmlspecialchars(($leave['FirstName'] ?? 'Unknown') . ' ' . ($leave['LastName'] ?? 'Employee')); ?>"
                                  data-leave-type="<?php echo htmlspecialchars($leave['LeaveTypeName'] ?? 'N/A'); ?>"
                                  data-start-date="<?php echo $leave['StartDate'] ? date('M d, Y', strtotime($leave['StartDate'])) : 'N/A'; ?>"
                                  data-end-date="<?php echo $leave['EndDate'] ? date('M d, Y', strtotime($leave['EndDate'])) : 'N/A'; ?>"
                                  data-status="<?php echo htmlspecialchars($leave['Status']); ?>">
                              View
                          </button>
                        </td>
                      </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
      </div>
    </div>
  
  </main>
</div>

<div class="modal fade" id="rejectConfirmationModal" tabindex="-1" aria-labelledby="rejectConfirmationModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-sm">
    <div class="modal-content" style="border-radius: 12px; text-align: center;">
      <div class="modal-body" style="padding: 30px 20px;">
        <i class="fas fa-exclamation-triangle text-danger" style="font-size: 2.5rem; margin-bottom: 15px;"></i>
        <h5 class="modal-title" id="rejectConfirmationModalLabel" style="font-weight: 700;">Are you sure?</h5>
        <p class="text-muted" style="margin-top: 5px; margin-bottom: 20px; font-size: 0.9rem;">This action can't be undone. Please confirm if you want to proceed.</p>
        
        <form method="POST" id="rejectFormAction">
            <input type="hidden" name="leave_request_id" id="modalRejectLeaveId">
            <button type="submit" name="reject_request" class="btn btn-danger" style="font-weight: 600;">Confirm Reject</button>
            <button type="button" class="btn btn-outline" data-bs-dismiss="modal" style="font-weight: 600; margin-left: 10px;">Cancel</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- VIEW LEAVE DETAILS MODAL -->
<div class="modal" id="viewLeaveModal" tabindex="-1" aria-labelledby="viewLeaveModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="viewLeaveModalLabel">Leave Details</h5>
        <button type="button" class="modal-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
      </div>
      <!-- Modal body is now dynamic -->
      <div class="modal-body">
        <p><strong>Employee:</strong> <span id="modalEmployeeName"></span></p>
        <p><strong>Type of Leave:</strong> <span id="modalLeaveType"></span></p>
        <p><strong>From:</strong> <span id="modalStartDate"></span></p>
        <p><strong>To:</strong> <span id="modalEndDate"></span></p>
        <p><strong>Status:</strong> <span id="modalStatus"></span></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline btn-sm" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Keep Bootstrap's JS for tab and modal functionality -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- 7. JAVASCRIPT TO POPULATE MODAL -->
// Remove all the extra closing tags and merge all the code inside ONE SINGLE block:
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Logic for View Leave Modal
        var viewLeaveModal = document.getElementById('viewLeaveModal');
        if (viewLeaveModal) {
            // NOTE: FIXING THE EVENT NAME TO 'show.bs.modal'
            viewLeaveModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var employeeName = button.getAttribute('data-employee-name');
                var leaveType = button.getAttribute('data-leave-type');
                var startDate = button.getAttribute('data-start-date');
                var endDate = button.getAttribute('data-end-date');
                var status = button.getAttribute('data-status');
                
                var modalBody = viewLeaveModal.querySelector('.modal-body');
                modalBody.querySelector('#modalEmployeeName').textContent = employeeName;
                modalBody.querySelector('#modalLeaveType').textContent = leaveType;
                modalBody.querySelector('#modalStartDate').textContent = startDate;
                modalBody.querySelector('#modalEndDate').textContent = endDate;
                modalBody.querySelector('#modalStatus').textContent = status;
            });
        }
        
        // Logic for Reject Confirmation Modal
        var rejectConfirmationModal = document.getElementById('rejectConfirmationModal');
        if (rejectConfirmationModal) {
            rejectConfirmationModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                var leaveId = button.getAttribute('data-leave-id');
                var modalInput = rejectConfirmationModal.querySelector('#modalRejectLeaveId');
                modalInput.value = leaveId;
            });
        }
        
        // Logic for Approve Confirmation Modal
        var approveConfirmationModal = document.getElementById('approveConfirmationModal');
        if (approveConfirmationModal) {
            approveConfirmationModal.addEventListener('show.bs.modal', function (event) {
                var button = event.relatedTarget;
                
                var leaveId = button.getAttribute('data-leave-id');
                var employeeId = button.getAttribute('data-employee-id');
                var leaveTypeId = button.getAttribute('data-leave-type-id');
                var daysRequested = button.getAttribute('data-days-requested');

                approveConfirmationModal.querySelector('#modalApproveLeaveId').value = leaveId;
                approveConfirmationModal.querySelector('#modalApproveEmployeeId').value = employeeId;
                approveConfirmationModal.querySelector('#modalApproveLeaveTypeId').value = leaveTypeId;
                approveConfirmationModal.querySelector('#modalApproveDaysRequested').value = daysRequested;
            });
        }
    });
</script>

</body>
</html>



