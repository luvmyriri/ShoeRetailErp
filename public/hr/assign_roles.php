<?php
// 1. Start session
session_start();

// 2. Include database configuration and helper functions
// NOTE: Adjust the path if necessary for your file structure
require_once '../../config/database.php'; 

// Auth guard
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}
$allowedRoles = ['Admin', 'Manager', 'HR'];
if (!in_array(($_SESSION['role'] ?? ''), $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}
// Initialize variables for the form
$employees = [];
$rolesList = [];
$flashSuccess = null;
$flashError = null;

// ==========================================================
//  BLOCK 1: HANDLE ROLE ASSIGNMENT FORM SUBMISSION (POST)
// ==========================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'assign_role') {
    
    $employeeID = $_POST['EmployeeID'] ?? null;
    $newRole = $_POST['NewRole'] ?? null;
    $newDepartment = $_POST['NewDepartment'] ?? null;

    if (empty($employeeID) || empty($newRole) || empty($newDepartment)) {
        $_SESSION['flash_error'] = 'Error: All fields are required to assign a new position/role.';
    } else {
        try {
            // Prepare and execute the SQL update query
$sql = "UPDATE Employees SET Role = ?, Department = ? WHERE EmployeeID = ?";
            dbExecute($sql, [$newRole, $newDepartment, $employeeID]);

            $_SESSION['flash_success'] = "Successfully updated Employee #{$employeeID}'s position to '{$newRole}' in '{$newDepartment}' department.";

        } catch (Exception $e) {
            $_SESSION['flash_error'] = "Database Error: Failed to update role. " . $e->getMessage();
            error_log('Role Assignment Error: ' . $e->getMessage());
        }
    }
    
    // Redirect to self (GET request) to prevent form resubmission and show flash message
    header('Location: assign_roles.php');
    exit;
}

// ==========================================================
//  BLOCK 2: FETCH DATA FOR FORM DROPDOWNS & TABLE (GET)
// ==========================================================

// Define all available roles organized by department (Used for both PHP logic and JS data)
$rolesList = [
    'Inventory Management' => ['Inventory Manager', 'Inventory Encoder'],
    'Sales and Customer Management' => ['Cashier', 'Sales Manager', 'Customer Service'],
    'Procurement' => ['Procurement Manager'],
    'Accounting' => ['Accountant'],
    'Human Resource (HR)' => ['HR Manager'],
    'GeneralAdmin' => ['Admin']
];

$departments = array_keys($rolesList); 
$rolesJson = json_encode($rolesList); // Convert to JSON for dynamic JS use

try {
    // 1. Fetch ALL Employees (for the main dropdown and the roster table)
    $employees = dbFetchAll("
        SELECT 
            EmployeeID, 
            CONCAT(FirstName, ' ', LastName) AS EmployeeName, 
            Role, 
            Department 
FROM Employees 
        ORDER BY EmployeeName ASC
    ");
    
} catch (Exception $e) {
    $flashError = "Error loading employee and position data. Please check logs.";
    error_log("Assign Roles Page Error: " . $e->getMessage());
}

// Check for flash messages (to display after redirect)
if (isset($_SESSION['flash_success'])) {
    $flashSuccess = $_SESSION['flash_success'];
    unset($_SESSION['flash_success']);
}
if (isset($_SESSION['flash_error'])) {
    $flashError = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Roles - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .current-info {
            background-color: var(--gray-50);
            border: 1px solid var(--gray-200);
            padding: 1rem;
            border-radius: var(--radius-md);
            margin-bottom: 1.5rem;
            font-size: 14px;
        }
        .current-info strong {
            display: block;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/modal.php'; ?>
    
    <?php if ($flashSuccess): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showModal('Success', '<?php echo addslashes($flashSuccess); ?>', 'success');
        });
    </script>
    <?php endif; ?>
    
    <?php if ($flashError): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            showModal('Error', '<?php echo addslashes($flashError); ?>', 'error');
        });
    </script>
    <?php endif; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">

        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Assign Roles & Permissions</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / 
                        <a href="index.php">HR</a> / 
                        Assign Roles
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="index.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="card" style="max-width: 600px; margin: 0 auto 2rem;">
                <div class="card-header">
                    <h3>Update Employee Position & Department</h3>
                </div>
                <div class="card-body" style="display: flex; flex-direction: column; gap: 1rem;">
                    <form id="assignRoleForm" method="POST" action="assign_roles.php" style="display: flex; flex-direction: column; gap: 1rem;">
                        <input type="hidden" name="action" value="assign_role">
                        
                        <div>
                            <label for="EmployeeID" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.5rem;">Employee Name</label>
                            <select id="EmployeeID" name="EmployeeID" required onchange="displayCurrentInfo()" class="form-control">
                                <option value="">-- Select an Employee --</option>
                                <?php foreach ($employees as $emp): ?>
                                    <option 
                                        value="<?php echo htmlspecialchars($emp['EmployeeID']); ?>"
                                        data-role="<?php echo htmlspecialchars($emp['Role']); ?>"
                                        data-dept="<?php echo htmlspecialchars($emp['Department']); ?>"
                                    >
                                        <?php echo htmlspecialchars($emp['EmployeeName']) . ' (ID: ' . htmlspecialchars($emp['EmployeeID']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div id="currentInfoBox" class="current-info" style="display: none;">
                            <strong>Current Position:</strong> <span id="currentRole">N/A</span>
                            <strong>Current Department:</strong> <span id="currentDepartment">N/A</span>
                        </div>

                        <div>
                            <label for="NewDepartment" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.5rem;">New Department</label>
                            <select id="NewDepartment" name="NewDepartment" required onchange="updateRolesDropdown()" class="form-control">
                                <option value="">-- Select New Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo htmlspecialchars($dept); ?>">
                                        <?php echo htmlspecialchars($dept); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label for="NewRole" style="display: block; font-size: 14px; font-weight: 600; margin-bottom: 0.5rem;">Change Position (Dropdown)</label>
                            <select id="NewRole" name="NewRole" required disabled class="form-control">
                                <option value="">-- Select a Department first --</option>
                            </select>
                        </div>

                        <div style="text-align: right;">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Assign New Position</button>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card" style="max-width: 900px; margin: 0 auto 4rem;">
                <div class="card-header">
                    <h3>Employee Position Roster</h3>
                </div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Current Position (Role)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($employees)): ?>
                                <?php foreach ($employees as $emp): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($emp['EmployeeID']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['EmployeeName']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['Department']); ?></td>
                                        <td><?php echo htmlspecialchars($emp['Role']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" style="text-align: center; padding: 2rem;">No employee records found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>
    
    <script>
    // Data passed from PHP to JavaScript (The master list of roles)
    const ALL_ROLES = <?php echo $rolesJson; ?>;

    /**
     * Updates the "Current Position/Department" box based on the selected employee.
     */
    function displayCurrentInfo() {
        const select = document.getElementById('EmployeeID');
        const infoBox = document.getElementById('currentInfoBox');
        const currentRole = document.getElementById('currentRole');
        const currentDepartment = document.getElementById('currentDepartment');
        
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption.value) {
            currentRole.textContent = selectedOption.getAttribute('data-role') || 'N/A';
            currentDepartment.textContent = selectedOption.getAttribute('data-dept') || 'N/A';
            infoBox.style.display = 'block';
        } else {
            infoBox.style.display = 'none';
        }
    }

    /**
     * Dynamically updates the New Role dropdown based on the selected Department.
     */
    function updateRolesDropdown() {
        const departmentSelect = document.getElementById('NewDepartment');
        const roleSelect = document.getElementById('NewRole');
        const selectedDepartment = departmentSelect.value;
        
        // Clear existing options
        roleSelect.innerHTML = ''; // Start with an empty list

        if (selectedDepartment) {
            roleSelect.disabled = false;
            const rolesForDept = ALL_ROLES[selectedDepartment];
            
            if (rolesForDept && rolesForDept.length > 0) {
                // Add default option
                roleSelect.innerHTML += '<option value="">-- Select New Role --</option>';
                
                // Add roles specific to the selected department
                rolesForDept.forEach(role => {
                    const option = document.createElement('option');
                    option.value = role;
                    option.textContent = role;
                    roleSelect.appendChild(option);
                });
            } else {
                 roleSelect.innerHTML = '<option value="">No roles defined for this department</option>';
            }
        } else {
            // Reset state if no department is selected
            roleSelect.disabled = true;
            roleSelect.innerHTML = '<option value="">-- Select a Department first --</option>';
        }
    }

    // Initialize JavaScript functionality on page load
    document.addEventListener('DOMContentLoaded', () => {
        displayCurrentInfo();
        updateRolesDropdown();
    });
    // ... existing JavaScript code for displayCurrentInfo() and updateRolesDropdown() ...

// --- CUSTOM MODAL FUNCTIONS ---

/**
 * Hides and cleans up the alert modal.
 */
function closeAlert() {
    const container = document.querySelector('.alert-container');
    if (container) {
        // Start fade/transform transition out
        container.classList.remove('show'); 
        // Clean up the content after transition finishes (0.3s defined in CSS)
        setTimeout(() => {
            container.innerHTML = '';
        }, 300); 
    }
}

/**
 * Displays a custom alert modal (Success or Error).
 * @param {string} message - The main message to display.
 * @param {string} type - 'success' or 'error'.
 */
function showAlert(message, type) {
    const container = document.querySelector('.alert-container');
    if (!container) {
        console.error("Alert container not found.");
        return;
    }

    // 1. Determine content and styling based on type
    let iconClass, headerText, buttonText, buttonClass, iconHTML;

    if (type === 'success') {
        iconClass = 'success-icon';
        headerText = 'Woohoo, Success!';
        buttonText = 'Continue';
        buttonClass = 'success-btn';
        iconHTML = '<i class="fas fa-check"></i>'; // FontAwesome Check icon
    } else { // 'error'
        iconClass = 'error-icon';
        headerText = 'Uh, oh!';
        buttonText = 'Retry';
        buttonClass = 'error-btn';
        iconHTML = '<i class="fas fa-times"></i>'; // FontAwesome Times icon (X)
    }

    // 2. Construct the modal HTML
    const modalHTML = `
        <div class="alert-modal">
            <div class="alert-icon ${iconClass}">
                ${iconHTML}
            </div>
            <h2 class="alert-header">${headerText}</h2>
            <p class="alert-message">${message}</p>
            <button class="alert-button ${buttonClass}" onclick="closeAlert()">
                ${buttonText}
            </button>
        </div>
    `;

    // 3. Insert and display the modal
    container.innerHTML = modalHTML;
    // Use a small delay to ensure the content is rendered before applying 'show' class for the transition
    setTimeout(() => {
        container.classList.add('show');
    }, 10);
}
// --- END CUSTOM MODAL FUNCTIONS ---
    </script>
    <script src="../js/app.js"></script>
</body>
</html>