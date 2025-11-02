<?php
// 1. Start session
session_start();

// 2. Include database configuration and helper functions
// NOTE: Adjust the path if necessary for your file structure
require_once '../../config/database.php'; 

// Initialize data variable
$departmentData = [];
$reportError = null;

try {
    // Query for Department Breakdown (Employee Directory Report)
    $departmentData = dbFetchAll("
        SELECT Department, COUNT(EmployeeID) AS employeeCount
        FROM employees
        GROUP BY Department
        ORDER BY Department ASC
    ");
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
    <title>Reports - HR Management</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* General Styles */
        .report-card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); 
            transition: transform 0.2s;
        }
        .report-card:hover {
            transform: translateY(-2px);
        }
        .report-card-header {
            padding: 1rem 1rem;
            border-bottom: 1px solid #eee;
            background-color: #f7f7f7;
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .report-card-body {
            padding: 0.5rem 1rem;
        }
        
        /* ENHANCED Directory Table Styles - Simple, Hover Effect */
        .report-item {
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
            font-size: 14px;
            transition: background-color 0.2s; /* Added transition for hover effect */
        }
        .report-item:hover {
            background-color: #f5f0f8; /* Light background on hover */
            cursor: default;
        }
        .report-item:last-child {
            border-bottom: none; 
        }
        .report-item span:first-child {
            color: #714B67; /* Theme color for department name */
            font-weight: 600;
        }
        .report-item span:last-child {
            font-weight: 700; /* Bold, but no special background */
            color: #333;
            padding: 0.2rem 0.6rem;
            border-radius: 4px;
        }
        
        /* Back Button Icon Style */
        .back-button {
            position: absolute;
            top: 1rem; 
            left: 2rem;
            font-size: 1.5rem;
            color: #714B67;
            transition: color 0.2s, transform 0.2s;
            z-index: 20; 
        }
        .back-button:hover {
            color: #5d3c53;
            transform: translateX(-3px);
        }
    </style>
</head>
<body>
    <div class="alert-container"></div>
    
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
                    <h1>HR Reports & Analytics</h1>
                    <div class="page-header-breadcrumb"><a href="index.php">HR Management</a> / Reports</div>
                </div>
            </div>

            <div class="row" style="margin-bottom: 1rem;">
                <?php if ($reportError): ?>
                    <div class="col-md-12">
                        <div class="alert alert-danger" style="padding: 1rem; color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; border-radius: 5px;">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($reportError); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div class="col-md-6" style="margin-bottom: 0.75rem;">
                    <div class="report-card">
                        <div class="report-card-header">
                            <h3 style="margin: 0; font-size: 14px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Employee Directory Breakdown</h3>
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
                        <i class="fas fa-chart-line" style="font-size: 24px; color: #ccc; margin-bottom: 0.5rem;"></i>
                        <p style="color: #999; font-size: 14px; margin: 0;">Payroll Summary Report coming soon...</p>
                    </div>
                </div>
            </div>

        </main>
    </div>
    <script src="../js/app.js"></script>
</body>
</html>