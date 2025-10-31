<?php
// 1. Start session
session_start();

// 2. Include database configuration and helper functions
// NOTE: Adjust the path if necessary for your file structure
require_once '../../config/database.php'; 

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
            $sql = "UPDATE employees SET Role = ?, Department = ? WHERE EmployeeID = ?";
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
        FROM employees 
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
    <title>Assign Roles - HR Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* General Layout & Form Styles */
        .card-form {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            padding: 2rem;
            max-width: 600px;
            margin: 2rem auto;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            font-size: 14px;
            font-weight: 600;
            color: #555;
            margin-bottom: 0.5rem;
        }
        .form-group select,
        .form-group input[type="text"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 0.5rem;
            font-size: 14px;
            box-sizing: border-box;
        }
        .btn-submit {
            background-color: #714B67;
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .btn-submit:hover {
            background-color: #5d3c53;
        }
        .current-info {
            background-color: #f8f8f8;
            border: 1px solid #eee;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
            font-size: 13px;
        }
        .current-info strong {
            display: block;
            color: #333;
            margin-bottom: 0.25rem;
        }
        
        /* Back Button Icon Style - ADJUSTED TOP VALUE */
        .back-button {
            position: absolute;
            top: 1rem; /* Changed from 1.5rem to 1rem */
            left: 2rem;
            font-size: 1.5rem;
            color: #714B67; /* Theme color */
            transition: color 0.2s, transform 0.2s;
            z-index: 20; 
        }
        .back-button:hover {
            color: #5d3c53;
            transform: translateX(-3px);
        }

        /* Enhanced Roster Table Styles */
        .roster-card {
            max-width: 900px;
            margin: 2rem auto 4rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            border-radius: 8px;
            overflow: hidden; 
            background-color: #fff;
        }

        .modal-table-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .modal-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }

        .modal-table th, .modal-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #f0f0f0;
            white-space: nowrap; 
        }

        .modal-table th {
            background-color: #714B67; 
            color: white; 
            font-weight: 700;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        /* Stripes for better readability */
        .modal-table tbody tr:nth-child(even) {
            background-color: #f7f7f7;
        }
        .modal-table tbody tr:hover {
            background-color: #f0e6f5; 
        }
        
        /* Styling specific columns */
        .modal-table td:nth-child(3) {
            font-weight: 600; 
        }
        .modal-table td:nth-child(4) {
            color: #2a7a49; 
            font-weight: 500;
        }
/* ... existing styles ... */

/* --- Custom Modal/Notification Styles --- */

/* The main container for the alert messages (Modal Backdrop) */
.alert-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6); /* Dim background */
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 1000; /* Ensure it's above everything else */
    opacity: 0; /* Start hidden */
    visibility: hidden;
    transition: opacity 0.3s ease, visibility 0.3s ease;
}

.alert-container.show {
    opacity: 1;
    visibility: visible;
}

/* The actual modal card */
.alert-modal {
    background-color: #fff;
    padding: 2.5rem;
    border-radius: 12px;
    width: 90%;
    max-width: 400px;
    text-align: center;
    box-shadow: 0 0 30px rgba(0, 0, 0, 0.3);
    transform: scale(0.8); /* Start smaller */
    transition: transform 0.3s ease;
}

.alert-container.show .alert-modal {
    transform: scale(1); /* Zoom into position */
}

/* Icon Styles */
.alert-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0 auto 1.5rem;
    font-size: 30px;
    color: white;
}

.alert-icon.success-icon {
    background-color: #28a745; /* Green for success */
}

.alert-icon.error-icon {
    background-color: #dc3545; /* Red for error */
}

/* Header and Message */
.alert-header {
    font-size: 1.8rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
}

.alert-message {
    font-size: 1rem;
    color: #555;
    margin-bottom: 2rem;
}

/* Button Styles */
.alert-button {
    padding: 0.75rem 2rem;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    cursor: pointer;
    transition: background-color 0.2s;
    width: 100%;
}

.alert-button.success-btn {
    background-color: #28a745;
    color: white;
}

.alert-button.success-btn:hover {
    background-color: #1e7e34;
}

.alert-button.error-btn {
    background-color: #dc3545;
    color: white;
}

.alert-button.error-btn:hover {
    background-color: #c82333;
}
    
    </style>
</head>
<body>
    <div class="alert-container"></div>
    
   <?php if ($flashSuccess): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Assume showAlert is available to show the custom modal
        if (typeof showAlert === 'function') {
            showAlert('<?php echo addslashes($flashSuccess); ?>', 'success');
        }
    });
</script>
    <?php endif; ?>
    
   <?php if ($flashError): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Assume showAlert is available to show the custom modal
        if (typeof showAlert === 'function') {
            showAlert('<?php echo addslashes($flashError); ?>', 'error');
        }
    });
</script>
    <?php endif; ?>
    
    <?php 
    // NOTE: This assumes you have a navbar included at this path
    include '../includes/navbar.php'; 
    ?>
    
    <div class="main-wrapper" style="margin-left: 0; position: relative;">
        <a href="index.php" class="back-button" title="Go back to Dashboard">
            <i class="fas fa-arrow-circle-left"></i> 
        </a>

        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Assign Roles & Permissions</h1>
                    <div class="page-header-breadcrumb"><a href="index.php">HR Management</a> / Assign Roles</div>
                </div>
            </div>

            <div class="card-form">
                <h2 style="font-size: 1.5rem; color: #714B67; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 1rem;">Update Employee Position & Department</h2>
                
                <form id="assignRoleForm" method="POST" action="assign_roles.php">
                    <input type="hidden" name="action" value="assign_role">
                    
                    <div class="form-group">
                        <label for="EmployeeID">Employee Name</label>
                        <select id="EmployeeID" name="EmployeeID" required onchange="displayCurrentInfo()">
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

                    <div class="form-group">
                        <label for="NewDepartment">New Department</label>
                        <select id="NewDepartment" name="NewDepartment" required onchange="updateRolesDropdown()">
                            <option value="">-- Select New Department --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo htmlspecialchars($dept); ?>">
                                    <?php echo htmlspecialchars($dept); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="NewRole">Change Position (Dropdown)</label>
                        <select id="NewRole" name="NewRole" required disabled>
                            <option value="">-- Select a Department first --</option>
                        </select>
                    </div>

                    <div style="text-align: right; margin-top: 2rem;">
                        <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Assign New Position</button>
                    </div>
                </form>

            </div>
            
            <div class="roster-card">
                <h3 style="padding: 1rem; margin: 0; font-size: 1.2rem; color: #714B67; border-bottom: 1px solid #eee;">Employee Position Roster</h3>
                <div class="modal-table-container">
                    <table class="modal-table">
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
                                    <td colspan="4" style="text-align: center; padding: 1.5rem;">No employee records found.</td>
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