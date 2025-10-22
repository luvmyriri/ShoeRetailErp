<?php
/**
 * System Test Script for Shoe Retail ERP System
 * Author: Generated for PHP/MySQL Implementation
 * Date: 2024
 */

require_once 'includes/core_functions.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Test - Shoe Retail ERP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4"><i class="bi bi-gear"></i> System Test Results</h1>
        
        <div class="row">
            <div class="col-12">
                
                <!-- Database Connection Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-database"></i> Database Connection Test</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            $db = getDB();
                            echo '<div class="alert alert-success"><i class="bi bi-check-circle"></i> Database connection successful!</div>';
                            
                            // Test basic query
                            $result = dbFetchOne("SELECT COUNT(*) as count FROM Stores");
                            echo '<p><strong>Stores in database:</strong> ' . $result['count'] . '</p>';
                            
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger"><i class="bi bi-x-circle"></i> Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- Tables Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-table"></i> Database Tables Test</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $tables = [
                            'Stores', 'Suppliers', 'Products', 'Inventory', 'Customers', 
                            'Sales', 'SaleDetails', 'PurchaseOrders', 'PurchaseOrderDetails',
                            'GeneralLedger', 'AccountsReceivable', 'AccountsPayable', 
                            'TaxRecords', 'SupportTickets', 'Users', 'StockMovements'
                        ];
                        
                        echo '<div class="row">';
                        foreach ($tables as $table) {
                            try {
                                $result = dbFetchOne("SELECT COUNT(*) as count FROM $table");
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> ' . $table . ': ' . $result['count'] . ' records';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> ' . $table . ': Error';
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                        ?>
                    </div>
                </div>

                <!-- Views Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-eye"></i> Database Views Test</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        $views = [
                            'v_inventory_summary',
                            'v_sales_summary', 
                            'v_financial_summary',
                            'v_outstanding_receivables'
                        ];
                        
                        echo '<div class="row">';
                        foreach ($views as $view) {
                            try {
                                $result = dbFetchOne("SELECT COUNT(*) as count FROM $view");
                                echo '<div class="col-md-6 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> ' . $view . ': ' . $result['count'] . ' records';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-6 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> ' . $view . ': Error - ' . htmlspecialchars($e->getMessage());
                                echo '</div>';
                                echo '</div>';
                            }
                        }
                        echo '</div>';
                        ?>
                    </div>
                </div>

                <!-- Core Functions Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-code-square"></i> Core Functions Test</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php
                            // Test utility functions
                            try {
                                $stores = getAllStores();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getAllStores(): ' . count($stores) . ' stores';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getAllStores(): Error';
                                echo '</div>';
                                echo '</div>';
                            }

                            try {
                                $suppliers = getAllSuppliers();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getAllSuppliers(): ' . count($suppliers) . ' suppliers';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getAllSuppliers(): Error';
                                echo '</div>';
                                echo '</div>';
                            }

                            try {
                                $stats = getDashboardStats();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getDashboardStats(): Working';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getDashboardStats(): Error';
                                echo '</div>';
                                echo '</div>';
                            }

                            try {
                                $products = getAllProducts();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getAllProducts(): ' . count($products) . ' products';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getAllProducts(): Error';
                                echo '</div>';
                                echo '</div>';
                            }

                            try {
                                $lowStock = getLowStockItems();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getLowStockItems(): ' . count($lowStock) . ' items';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getLowStockItems(): Error';
                                echo '</div>';
                                echo '</div>';
                            }

                            try {
                                $sales = getSalesSummary();
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-success py-2">';
                                echo '<i class="bi bi-check"></i> getSalesSummary(): ' . count($sales) . ' sales';
                                echo '</div>';
                                echo '</div>';
                            } catch (Exception $e) {
                                echo '<div class="col-md-4 mb-2">';
                                echo '<div class="alert alert-danger py-2">';
                                echo '<i class="bi bi-x"></i> getSalesSummary(): Error';
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Stored Procedures Test -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-gear-fill"></i> Stored Procedures Test</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        try {
                            // Test if stored procedures exist
                            $procedures = dbFetchAll("SHOW PROCEDURE STATUS WHERE Db = DATABASE()");
                            
                            if (!empty($procedures)) {
                                echo '<div class="alert alert-success">';
                                echo '<i class="bi bi-check-circle"></i> Found ' . count($procedures) . ' stored procedures:';
                                echo '<ul class="mb-0 mt-2">';
                                foreach ($procedures as $proc) {
                                    echo '<li>' . htmlspecialchars($proc['Name']) . '</li>';
                                }
                                echo '</ul>';
                                echo '</div>';
                            } else {
                                echo '<div class="alert alert-warning">';
                                echo '<i class="bi bi-exclamation-triangle"></i> No stored procedures found. You may need to run the complete SQL schema.';
                                echo '</div>';
                            }
                        } catch (Exception $e) {
                            echo '<div class="alert alert-danger">';
                            echo '<i class="bi bi-x-circle"></i> Error checking stored procedures: ' . htmlspecialchars($e->getMessage());
                            echo '</div>';
                        }
                        ?>
                    </div>
                </div>

                <!-- System Summary -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-info-circle"></i> System Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        echo '<div class="row">';
                        
                        // PHP Version
                        echo '<div class="col-md-6 mb-3">';
                        echo '<h6>PHP Information</h6>';
                        echo '<p><strong>Version:</strong> ' . PHP_VERSION . '</p>';
                        echo '<p><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</p>';
                        echo '</div>';
                        
                        // MySQL Version
                        echo '<div class="col-md-6 mb-3">';
                        echo '<h6>MySQL Information</h6>';
                        try {
                            $version = dbFetchOne("SELECT VERSION() as version");
                            echo '<p><strong>Version:</strong> ' . $version['version'] . '</p>';
                            
                            $charset = dbFetchOne("SELECT @@character_set_database as charset");
                            echo '<p><strong>Charset:</strong> ' . $charset['charset'] . '</p>';
                        } catch (Exception $e) {
                            echo '<p class="text-danger">Unable to retrieve MySQL info</p>';
                        }
                        echo '</div>';
                        
                        echo '</div>';
                        
                        // Quick Setup Guide
                        echo '<div class="alert alert-info">';
                        echo '<h6><i class="bi bi-lightbulb"></i> Quick Setup Steps:</h6>';
                        echo '<ol class="mb-0">';
                        echo '<li>Run the complete SQL schema file: <code>shoe_retail_erp_complete.sql</code></li>';
                        echo '<li>Update database credentials in <code>config/database.php</code></li>';
                        echo '<li>Create necessary directories: <code>logs/</code></li>';
                        echo '<li>Set proper file permissions for write access</li>';
                        echo '<li>Access the login page and use demo credentials</li>';
                        echo '</ol>';
                        echo '</div>';
                        ?>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="card">
                    <div class="card-body text-center">
                        <a href="login.php" class="btn btn-primary me-2">
                            <i class="bi bi-box-arrow-in-right"></i> Go to Login
                        </a>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-house-door"></i> Dashboard
                        </a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>