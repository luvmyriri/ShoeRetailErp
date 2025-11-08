
<?php

session_start();

// Prevent caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control - Only Admin can access main dashboard
$userRole = $_SESSION['role'] ?? '';

if ($userRole !== 'Admin') {
    // Redirect non-admin users to their respective module dashboards
    switch ($userRole) {
        case 'Manager':
        case 'Inventory':
            header('Location: /ShoeRetailErp/public/inventory/index.php');
            exit;
        case 'HR':
            header('Location: /ShoeRetailErp/public/hr/index.php');
            exit;
        case 'Sales':
        case 'Cashier':
            header('Location: /ShoeRetailErp/public/sales/SalesDashboard.php');
            exit;
        case 'Procurement':
            header('Location: /ShoeRetailErp/public/procurement/index.php');
            exit;
        case 'Accountant':
            header('Location: /ShoeRetailErp/public/accounting/index.php');
            exit;
        case 'Customer Service':
            header('Location: /ShoeRetailErp/public/crm/index.php');
            exit;
        default:
            // If role not recognized, show access denied
            header('Location: /ShoeRetailErp/login.php?error=access_denied');
            exit;
    }
}

// Load database configuration
require __DIR__ . '/../config/database.php';

// Dashboard data aggregation
function getDashboardData() {
    $defaults = [
        'inventory' => ['total_products' => 0, 'total_stock' => 0],
        'low_stock' => ['low_stock_items' => 0],
        'today_sales' => ['total' => 0, 'orders' => 0],
        'month_sales' => ['total' => 0],
        'procurement' => ['pending' => 0],
        'ar' => ['outstanding' => 0],
        'ap' => ['outstanding' => 0],
        'employees' => ['total' => 0],
        'pending_leaves' => ['pending' => 0],
        'customers' => ['total' => 0],
        'active_deals' => ['total' => 0],
        'recent_sales' => []
    ];
    
    try {
        $result = $defaults;
        
        // Try fetching each stat, fallback to default if table doesn't exist
        try {
            $result['inventory'] = dbFetchOne("SELECT COUNT(*) as total_products, IFNULL(SUM(Quantity), 0) as total_stock FROM inventory") ?: $defaults['inventory'];
        } catch (Exception $e) { }
        
        try {
            $result['low_stock'] = dbFetchOne("SELECT COUNT(*) as low_stock_items FROM products WHERE (SELECT IFNULL(SUM(Quantity),0) FROM inventory WHERE inventory.ProductID = products.ProductID) <= MinStockLevel") ?: $defaults['low_stock'];
        } catch (Exception $e) { }
        
        try {
            $today = date('Y-m-d');
            $result['today_sales'] = dbFetchOne("SELECT IFNULL(SUM(TotalAmount), 0) as total, COUNT(*) as orders FROM sales WHERE DATE(SaleDate) = ?", [$today]) ?: $defaults['today_sales'];
        } catch (Exception $e) { }
        
        try {
            $result['month_sales'] = dbFetchOne("SELECT IFNULL(SUM(TotalAmount), 0) as total FROM sales WHERE YEAR(SaleDate) = YEAR(NOW()) AND MONTH(SaleDate) = MONTH(NOW())") ?: $defaults['month_sales'];
        } catch (Exception $e) { }
        
        try {
            $result['procurement'] = dbFetchOne("SELECT COUNT(*) as pending FROM purchaseorders WHERE Status = 'Pending'") ?: $defaults['procurement'];
        } catch (Exception $e) { }
        
        try {
            $result['ar'] = dbFetchOne("SELECT IFNULL(SUM(AmountDue - PaidAmount), 0) as outstanding FROM accountsreceivable WHERE PaymentStatus IN ('Pending', 'Overdue', 'Partial')") ?: $defaults['ar'];
        } catch (Exception $e) { }
        
        try {
            $result['ap'] = dbFetchOne("SELECT IFNULL(SUM(AmountDue - PaidAmount), 0) as outstanding FROM accountspayable WHERE PaymentStatus IN ('Pending', 'Overdue', 'Partial')") ?: $defaults['ap'];
        } catch (Exception $e) { }
        
        try {
            $result['employees'] = dbFetchOne("SELECT COUNT(*) as total FROM employees WHERE Status = 'Active'") ?: $defaults['employees'];
        } catch (Exception $e) { }
        
        try {
            $result['pending_leaves'] = dbFetchOne("SELECT COUNT(*) as pending FROM leaverequests WHERE Status = 'Pending'") ?: $defaults['pending_leaves'];
        } catch (Exception $e) { }
        
        try {
            $result['customers'] = dbFetchOne("SELECT COUNT(*) as total FROM customers WHERE Status = 'Active'") ?: $defaults['customers'];
        } catch (Exception $e) { }
        
        try {
            $result['active_deals'] = dbFetchOne("SELECT COUNT(*) as total FROM supporttickets WHERE Status IN ('Open', 'In Progress')") ?: $defaults['active_deals'];
        } catch (Exception $e) { }
        
        return $result;
    } catch (Exception $e) {
        logError('Dashboard data fetch failed', ['error' => $e->getMessage()]);
        return $defaults;
    }
}

$dashboard = getDashboardData();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Shoe Retail ERP</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include 'includes/navbar.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Dashboard</h1>
                    <div class="page-header-breadcrumb"><a href="index.php">Home</a></div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary" onclick="ERP.refreshDashboard()"><i class="fas fa-sync-alt"></i> Refresh</button>
                </div>
            </div>

            <!-- Key Statistics Cards -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Total Products</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;"><?php echo number_format($dashboard['inventory']['total_products'] ?? 0); ?></div>
                                    <div style="font-size: 11px; color: #666;">Stock: <?php echo number_format($dashboard['inventory']['total_stock'] ?? 0); ?> units</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">📦</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Low Stock Items</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #E74C3C; margin-bottom: 0.25rem;"><?php echo $dashboard['low_stock']['low_stock_items'] ?? 0; ?></div>
                                    <div style="font-size: 11px; color: #666;">⚠️ Needs reordering</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">⚠️</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Today's Sales</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #27AE60; margin-bottom: 0.25rem;">₱<?php echo number_format($dashboard['today_sales']['total'] ?? 0, 2); ?></div>
                                    <div style="font-size: 11px; color: #666;"><?php echo $dashboard['today_sales']['orders'] ?? 0; ?> orders today</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">💳</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <div style="flex: 1;">
                                    <div style="font-size: 12px; color: #999; margin-bottom: 0.75rem; text-transform: uppercase; letter-spacing: 0.5px; font-weight: 600;">Employees</div>
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;"><?php echo $dashboard['employees']['total'] ?? 0; ?></div>
                                    <div style="font-size: 11px; color: #666;"><?php echo $dashboard['pending_leaves']['pending'] ?? 0; ?> pending leave</div>
                                </div>
                                <div style="font-size: 32px; text-align: center;">👥</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Secondary Statistics -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Total Customers</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo $dashboard['customers']['total'] ?? 0; ?></div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Active customer base</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📦 Pending Purchases</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo $dashboard['procurement']['pending'] ?? 0; ?></div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Purchase orders</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">🎫 Support Tickets</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;"><?php echo $dashboard['active_deals']['total'] ?? 0; ?></div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Open & In Progress</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Financial Overview -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">💰 Outstanding Receivables</h3></div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px solid #eee;">
                                <div style="font-size: 11px; color: #999; margin-bottom: 0.25rem; font-weight: 600;">TOTAL AMOUNT</div>
                                <div style="font-size: 22px; font-weight: bold; color: #E74C3C;">₱<?php echo number_format($dashboard['ar']['outstanding'] ?? 0, 2); ?></div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #999; margin-bottom: 0.25rem; font-weight: 600;">ACCOUNTS PAYABLE</div>
                                <div style="font-size: 22px; font-weight: bold; color: #3498DB;">₱<?php echo number_format($dashboard['ap']['outstanding'] ?? 0, 2); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📈 Month Revenue</h3></div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="font-size: 11px; color: #999; margin-bottom: 0.5rem; font-weight: 600;">CURRENT MONTH TOTAL</div>
                            <div style="font-size: 22px; font-weight: bold; color: #27AE60; margin-bottom: 0.75rem;">₱<?php echo number_format($dashboard['month_sales']['total'] ?? 0, 2); ?></div>
                            <div style="background-color: #f0f0f0; border-radius: 8px; height: 8px; overflow: hidden;">
                                <div style="height: 100%; width: 100%; background: linear-gradient(to right, #667eea, #764ba2); border-radius: 8px;"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">⚙️ System Status</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 11px; color: #999; margin-bottom: 0.75rem; font-weight: 600;">All Modules</div>
                            <div style="font-size: 32px; font-weight: bold; color: #27AE60; margin-bottom: 0.5rem;">✓</div>
                            <div style="font-size: 11px; color: #666; background-color: #f5f5f5; padding: 0.5rem; border-radius: 4px; font-weight: 600;">Online</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Module Navigation Section -->
            <div style="margin-bottom: 1rem;">
                <h2 style="margin: 0 0 0.75rem 0; font-size: 16px; font-weight: 600; color: #333;">Modules & Tools</h2>
            </div>
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="inventory/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">📦</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Inventory</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Stock: <?php echo number_format($dashboard['inventory']['total_stock'] ?? 0); ?> units</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="sales/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">💰</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Sales</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">₱<?php echo number_format($dashboard['month_sales']['total'] ?? 0, 2); ?> this month</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="procurement/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">🏭</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Procurement</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;"><?php echo $dashboard['procurement']['pending'] ?? 0; ?> pending POs</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="accounting/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">📊</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Accounting</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">AR: ₱<?php echo number_format($dashboard['ar']['outstanding'] ?? 0, 0); ?></p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="crm/CrmDashboard.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">👥</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">CRM</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;"><?php echo $dashboard['customers']['total'] ?? 0; ?> customers</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="hr/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">👔</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">HR</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;"><?php echo $dashboard['employees']['total'] ?? 0; ?> employees</p>
                            </div>
                        </div>
                    </a>
                </div>
            </div>
        </main>
    </div>
    <script src="js/app.js"></script>
    <script src="js/app.js"></script>
    <script src="js/erp-app.js"></script>
    <script>
        // Initialize dashboard on load
        document.addEventListener('DOMContentLoaded', function() {
            ERP.loadDashboard();
            
            // Auto-refresh every 30 seconds
            setInterval(() => {
                if (ERP.state.currentModule === 'dashboard') {
                    ERP.loadDashboard();
                }
            }, 30000);
        });
    </script>
</body>
</html>

