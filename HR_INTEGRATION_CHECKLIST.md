# HR Module Integration Checklist
## Quick Reference & Implementation Guide

---

## PRIORITY 1: CRITICAL FOUNDATION (Do First)

### 1.1 Session Management & Security
- [ ] Add to ALL HR pages (top of file):
```php
<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}
require_once __DIR__ . '/../../config/database.php';
?>
```

- [ ] Add role-based access check to sensitive pages:
```php
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, ['Admin', 'Manager', 'HR'])) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}
```

### 1.2 Theme & Navigation Links
**Add to HEAD of ALL pages:**
```html
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
```

**Add to BODY of ALL pages (at top):**
```html
<div class="alert-container"></div>
<?php include '../includes/navbar.php'; ?>

<div class="main-wrapper">
    <main class="main-content">
        <!-- Page content here -->
    </main>
</div>
```

### 1.3 Files Requiring IMMEDIATE Updates
```
CRITICAL (P1):
├── index.php ..................... HR Dashboard
├── employees.php ................. Employee Directory  
├── leave_management.php .......... Leave Requests
└── payroll_management.php ........ Payroll System

HIGH (P2):
├── timesheets.php ................ Attendance
├── departments.php ............... Org Structure
├── assign_roles.php .............. Role Management
└── reports.php ................... HR Reports

MEDIUM (P3):
├── details.php ................... Employee Profile
└── employee-timesheet.php ........ Timesheet View
```

---

## PRIORITY 2: ENHANCED MODALS

### 2.1 Copy Modal Stylesheet
```bash
cp /public/crm/enhanced-modal-styles.css /public/hr/
```

### 2.2 Link in HEAD
```html
<link rel="stylesheet" href="enhanced-modal-styles.css">
```

### 2.3 Modal Template for All Forms
```html
<!-- Template: Replace ALL inline modals -->
<div id="myModal" class="modal-enhanced modal-lg">
    <div class="modal-enhanced-content">
        <div class="modal-enhanced-header">
            <div class="modal-enhanced-header-content">
                <h2 class="modal-enhanced-title">Modal Title</h2>
                <p class="modal-enhanced-subtitle">Subtitle text here</p>
            </div>
            <button class="modal-enhanced-close" onclick="closeModal('myModal')">×</button>
        </div>
        <div class="modal-enhanced-body">
            <!-- Form content -->
        </div>
        <div class="modal-enhanced-footer">
            <button class="modal-btn modal-btn-secondary" onclick="closeModal('myModal')">Cancel</button>
            <button class="modal-btn modal-btn-primary">Submit</button>
        </div>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
</script>
```

---

## PRIORITY 3: FORM HANDLING

### 3.1 Create `js/hr-common.js`
```javascript
// Unified AJAX form submission
function submitFormAjax(formId, endpoint, callback) {
    const form = document.getElementById(formId);
    const data = new FormData(form);
    
    fetch(endpoint, {method: 'POST', body: data})
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showAlert(res.message, 'success');
                if (callback) callback(res);
            } else {
                showAlert(res.message || 'Error', 'error');
            }
        })
        .catch(e => showAlert('Network error: ' + e, 'error'));
}

// Alert display system
function showAlert(msg, type) {
    const container = document.querySelector('.alert-container');
    if (!container) return;
    const alert = document.createElement('div');
    alert.className = `alert alert-${type}`;
    alert.innerHTML = `<i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}-circle"></i> ${msg}`;
    container.appendChild(alert);
    setTimeout(() => alert.remove(), 5000);
}
```

### 3.2 Add to HEAD of Pages with Forms
```html
<script src="js/hr-common.js"></script>
```

### 3.3 PHP Endpoint Response Format
**ALL endpoints must return:**
```php
header('Content-Type: application/json');
echo json_encode([
    'success' => true|false,
    'message' => 'User-friendly message',
    'data' => $optional_data,
    'redirectUrl' => 'optional-redirect-path'
]);
exit;
```

---

## PRIORITY 4: DATABASE QUERIES

### 4.1 Verify Column Names (CRITICAL)

**Employees Table:**
```sql
✅ EmployeeID (NOT id, emp_id)
✅ FirstName, LastName (NOT first_name, last_name)
✅ DepartmentID (NOT dept_id)
✅ HireDate (NOT hire_date)
✅ Email, Phone (NOT email_address)
```

**Departments Table:**
```sql
✅ DepartmentID (NOT id)
✅ DepartmentName (NOT name)
✅ BranchID (NOT branch_id)
```

**Leave Tables:**
```sql
✅ LeaveRequestID (NOT request_id, leave_id)
✅ EmployeeID (NOT emp_id)
✅ LeaveTypeID (NOT type_id)
✅ StartDate, EndDate (NOT start_date)
✅ Status (values: 'Pending', 'Approved', 'Rejected')
```

**Attendance Table:**
```sql
✅ AttendanceID (NOT id)
✅ EmployeeID (NOT emp_id)
✅ AttendanceDate (NOT date)
✅ LogInTime, LogOutTime (NOT time_in, time_out)
```

**Payroll Table:**
```sql
✅ PayrollID (NOT id)
✅ EmployeeID (NOT emp_id)
✅ PayPeriodStart, PayPeriodEnd
✅ GrossPay, NetPay
```

### 4.2 Query Error Handling Template
```php
try {
    $result = dbFetchAll("SELECT * FROM employees WHERE EmployeeID = ?", [$id]);
    if (empty($result)) {
        throw new Exception('Employee not found');
    }
} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = 'Failed to load data. Please try again.';
    $result = [];
}
```

---

## PRIORITY 5: SPECIFIC PAGE UPDATES

### index.php (Dashboard)
- [ ] Add navbar & theme
- [ ] Replace inline modal with enhanced-modal
- [ ] Add alert container
- [ ] Verify all DB queries use correct column names
- [ ] Add error handling for queries

### employees.php (Directory)
- [ ] Add navbar & theme
- [ ] Verify column: DepartmentID, FirstName, LastName
- [ ] Add responsive table styling
- [ ] Test pagination if exists

### leave_management.php
- [ ] Add navbar & theme
- [ ] Replace approval modal with enhanced-modal
- [ ] Create AJAX approve/reject handlers
- [ ] Response format: `{success, message, data}`

### payroll_management.php
- [ ] Complete rewrite with navbar
- [ ] Add session check
- [ ] Replace inline modals
- [ ] Verify all queries: PayrollID, EmployeeID, GrossPay, NetPay

### timesheets.php & employee-timesheet.php
- [ ] Add navbar & theme
- [ ] Verify: AttendanceID, EmployeeID, AttendanceDate
- [ ] Add responsive table layout

### departments.php
- [ ] Add navbar & theme
- [ ] Verify: DepartmentID, DepartmentName
- [ ] Add breadcrumb navigation

### assign_roles.php
- [ ] Add navbar & theme
- [ ] Create enhanced modal for role assignment
- [ ] Add form validation

### reports.php
- [ ] Add navbar & theme
- [ ] Add date range filters
- [ ] Verify all report queries

### details.php
- [ ] Add navbar & theme
- [ ] Add back button/breadcrumb
- [ ] Make responsive

---

## TESTING CHECKLIST

### Functionality Tests
- [ ] Employee creation works
- [ ] Employee update works
- [ ] Employee deletion works (cascades correctly)
- [ ] Leave request submission works
- [ ] Leave approval/rejection works
- [ ] Payroll generation works
- [ ] Attendance records save
- [ ] Reports generate correctly

### UI/UX Tests
- [ ] Navbar displays on all pages
- [ ] Modals open and close
- [ ] Forms validate before submission
- [ ] Success messages display
- [ ] Error messages display
- [ ] Alerts auto-dismiss after 5s
- [ ] Mobile responsive (test 320px, 768px, 1024px)

### Security Tests
- [ ] Non-logged-in users redirected to login
- [ ] Non-HR users cannot access HR pages
- [ ] Session persists across pages
- [ ] Database queries safe from SQL injection

### Database Tests
- [ ] No "column not found" errors
- [ ] All queries return expected data
- [ ] Foreign key relationships work
- [ ] Cascading deletes work

---

## COMMON ISSUES & SOLUTIONS

### Issue: "Unknown column 'EmployeeID'"
**Solution:** Query is using wrong column name. Check if using `id` instead of `EmployeeID`

### Issue: Modal not opening
**Solution:** Ensure:
1. Modal HTML has correct class: `class="modal-enhanced"`
2. Function called: `openModal('modalId')`
3. enhanced-modal-styles.css is linked
4. JavaScript function exists

### Issue: Form not submitting
**Solution:** Ensure:
1. Form has `id` attribute
2. AJAX endpoint exists
3. Endpoint returns valid JSON
4. Form has proper event handler

### Issue: Navbar not showing
**Solution:** Ensure:
1. Navbar include path: `<?php include '../includes/navbar.php'; ?>`
2. User is in `$_SESSION['role']`
3. No PHP errors before navbar inclusion

---

## QUICK REFERENCE: Files to Copy/Create

```bash
# Copy enhanced modal styles
cp /public/crm/enhanced-modal-styles.css /public/hr/

# Create common JS file
touch /public/hr/js/hr-common.js

# Files to CREATE (if don't exist):
/public/hr/includes/hr-validators.php
/public/hr/js/hr-common.js
/public/hr/css/hr-overrides.css (if needed)
```

---

## DATABASE COLUMN QUICK REFERENCE

### All PascalCase (IMPORTANT!)
```
❌ Wrong:
SELECT id, first_name, last_name FROM employees
SELECT dept_id FROM departments
SELECT request_id FROM leaverequests

✅ Correct:
SELECT EmployeeID, FirstName, LastName FROM employees
SELECT DepartmentID FROM departments
SELECT LeaveRequestID FROM leaverequests
```

---

## IMPLEMENTATION TIME ESTIMATE

| Phase | Tasks | Est. Time | Pages |
|-------|-------|-----------|-------|
| P1 | Security, navbar, alerts | 2-3 hrs | 4 files |
| P2 | Enhanced modals | 1-2 hrs | 4 files |
| P3 | Form handlers | 2-3 hrs | All files |
| P4 | DB query fixes | 1-2 hrs | All files |
| P5 | Testing | 2-3 hrs | All files |
| **TOTAL** | | **8-13 hrs** | 10 files |

---

## SUCCESS INDICATORS ✅

After completion, verify:
- [ ] All 10 HR pages load with navbar visible
- [ ] No database column errors in logs
- [ ] All forms submit and show success/error messages
- [ ] Modals open/close smoothly with animations
- [ ] Mobile responsive (< 768px width)
- [ ] Session persists across page navigation
- [ ] Non-HR users cannot access HR pages
- [ ] Employee CRUD operations complete without errors

---

**Version:** 1.0
**Last Updated:** 2025-11-02
**Status:** Ready to Implement
