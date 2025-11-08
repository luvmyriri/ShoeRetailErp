<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Sales', 'Customer Service'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require __DIR__ . '/../../config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - CRM - Shoe Retail ERP</title>
    <?php include '../includes/head.php'; ?>
    <link rel="stylesheet" href="crm-integration.css">
    <link rel="stylesheet" href="enhanced-modal-styles.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Reports & Analytics</h1>
                    <div class="page-header-breadcrumb">
                        <a href="/ShoeRetailErp/public/index.php">Home</a> / CRM / Reports
                    </div>
                </div>
                <div class="page-header-actions">
                    <!-- Actions here -->
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h3 style="margin: 0;">CRM Analytics</h3>
                        <div>
                            <select class="form-control" style="display: inline-block; width: auto; padding: 0.5rem;">
                                <option>Last 30 Days</option>
                                <option>Last 90 Days</option>
                                <option>This Year</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row" style="margin-bottom: 2rem;">
                        <div class="col-md-3">
                            <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 24px; font-weight: 600; color: #714B67; margin-bottom: 0.5rem;" id="newCustomers">0</div>
                                <div style="font-size: 12px; color: #666;">New Customers</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 24px; font-weight: 600; color: #27AE60; margin-bottom: 0.5rem;" id="conversionRate">0%</div>
                                <div style="font-size: 12px; color: #666;">Conversion Rate</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 24px; font-weight: 600; color: #F39C12; margin-bottom: 0.5rem;" id="avgValue">₱0</div>
                                <div style="font-size: 12px; color: #666;">Avg Customer Value</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div style="text-align: center; padding: 1.5rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 24px; font-weight: 600; color: #3498DB; margin-bottom: 0.5rem;" id="retention">0%</div>
                                <div style="font-size: 12px; color: #666;">Retention Rate</div>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <div style="text-align: center; color: #999; padding: 2rem;">
                        <i class="fas fa-chart-line" style="font-size: 32px; margin-bottom: 1rem; display: block;"></i>
                        <p>Analytics charts will be displayed here</p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../js/app.js"></script>
</body>
</html>
