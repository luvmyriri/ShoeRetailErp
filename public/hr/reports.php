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
// Initialize data variable
$departmentData = [];
$reportError = null;

// Payroll GL filter defaults
$glStart = $_GET['gl_start'] ?? date('Y-m-01');
$glEnd = $_GET['gl_end'] ?? date('Y-m-d');
$payrollGL = [];

try {
    // Query for Department Breakdown (Employee Directory Report)
    $departmentData = dbFetchAll("
        SELECT Department, COUNT(EmployeeID) AS employeeCount
FROM Employees
        GROUP BY Department
        ORDER BY Department ASC
    ");
    // Payroll GL summary for period
    $payrollGL = dbFetchAll(
        "SELECT AccountName, SUM(Debit) as Debit, SUM(Credit) as Credit
         FROM GeneralLedger
         WHERE TransactionDate BETWEEN ? AND ?
           AND AccountName IN ('Payroll Expense','Wages Payable','Payroll Taxes Payable')
         GROUP BY AccountName
         ORDER BY FIELD(AccountName,'Payroll Expense','Wages Payable','Payroll Taxes Payable')",
        [$glStart, $glEnd]
    );
} catch (Exception $e) {
    $reportError = "Error loading report data. Check database connection and table status.";
    error_log("Reports Page Error: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-card {
            background-color: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
            transition: transform var(--transition-base);
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .report-card-header {
            padding: 1rem;
            border-bottom: 1px solid var(--gray-200);
            background-color: var(--gray-50);
            border-top-left-radius: var(--radius-md);
            border-top-right-radius: var(--radius-md);
        }
        .report-card-body {
            padding: 0.5rem 1rem;
        }
        .report-item {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--gray-100);
            font-size: 14px;
            transition: background-color var(--transition-base);
        }
        .report-item:hover {
            background-color: var(--gray-50);
            cursor: default;
        }
        .report-item:last-child {
            border-bottom: none; 
        }
        .report-item span:first-child {
            color: var(--primary-color);
            font-weight: 600;
        }
        .report-item span:last-child {
            font-weight: 700;
            color: var(--gray-900);
            padding: 0.2rem 0.6rem;
            border-radius: var(--radius-sm);
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <?php include '../includes/modal.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>HR Reports & Analytics</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / 
                        <a href="index.php">HR</a> / 
                        Reports
                    </div>
                </div>
                <div class="page-header-actions">
                    <a href="index.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <?php if ($reportError): ?>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        showModal('Error', '<?php echo addslashes($reportError); ?>', 'error');
                    });
                </script>
            <?php endif; ?>

            <div class="row" style="margin-bottom: 1rem;">
                
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="report-card">
                        <div class="report-card-header">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-users"></i> Employee Directory Breakdown
                            </h3>
                        </div>

                        <div class="report-card-body">
                            <div>
                                <?php
                                if (!empty($departmentData)) {
                                    foreach ($departmentData as $row) {
                                        ?>
                                        <div class="report-item">
                                            <span><?= htmlspecialchars($row['Department']) ?></span>
                                            <span><?= htmlspecialchars($row['employeeCount']) ?></span>
                                        </div>
                                        <?php
                                    }
                                } else {
                                    echo "<p style='color:#999; font-size:12px; padding: 0.75rem 0;'>No employee data found in the database.</p>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="report-card" style="min-height: 180px; text-align: center; padding: 2rem; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                        <i class="fas fa-chart-line" style="font-size: 24px; color: var(--gray-400); margin-bottom: 0.5rem;"></i>
                        <p style="color: var(--gray-500); font-size: 14px; margin: 0;">Payroll Summary Report coming soon...</p>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-12" style="margin-bottom: 0.75rem;">
                    <div class="report-card">
                        <div class="report-card-header d-flex justify-content-between align-items-center">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                <i class="fas fa-file-invoice-dollar"></i> Payroll GL Summary
                            </h3>
                            <form method="get" class="d-flex align-items-center gap-2" style="margin:0;">
                                <label class="text-muted" style="font-size:12px;">From</label>
                                <input type="date" name="gl_start" value="<?= htmlspecialchars($glStart) ?>" class="form-control form-control-sm" />
                                <label class="text-muted" style="font-size:12px;">To</label>
                                <input type="date" name="gl_end" value="<?= htmlspecialchars($glEnd) ?>" class="form-control form-control-sm" />
                                <button type="submit" class="btn btn-primary btn-sm">Filter</button>
                            </form>
                        </div>
                        <div class="report-card-body">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Account</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                        <th class="text-end">Net (Dr-Cr)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totDr = 0; $totCr = 0;
                                    foreach ($payrollGL as $row) {
                                        $dr = floatval($row['Debit']);
                                        $cr = floatval($row['Credit']);
                                        $totDr += $dr; $totCr += $cr;
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($row['AccountName']) ?></td>
                                            <td class="text-end"><?= number_format($dr, 2) ?></td>
                                            <td class="text-end"><?= number_format($cr, 2) ?></td>
                                            <td class="text-end"><?= number_format($dr - $cr, 2) ?></td>
                                        </tr>
                                        <?php
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th>Total</th>
                                        <th class="text-end"><?= number_format($totDr, 2) ?></th>
                                        <th class="text-end"><?= number_format($totCr, 2) ?></th>
                                        <th class="text-end"><?= number_format($totDr - $totCr, 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                            <?php if (empty($payrollGL)): ?>
                                <p class="text-muted" style="font-size:12px;">No payroll GL entries in the selected period.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
