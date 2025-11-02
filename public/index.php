
<?php

session_start();

// Prevent caching of authenticated pages
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    // Debug output before redirect
}

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
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">1,245</div>
                                    <div style="font-size: 11px; color: #666;">↑ 12% from last month</div>
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
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">23</div>
                                    <div style="font-size: 11px; color: #666;">⚠️ Needs attention</div>
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
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">₱45,230.50</div>
                                    <div style="font-size: 11px; color: #666;">↑ 8.5% today</div>
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
                                    <div style="font-size: 28px; font-weight: bold; color: #333; margin-bottom: 0.25rem;">28</div>
                                    <div style="font-size: 11px; color: #666;">4 pending leave</div>
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
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">👥 Active Users</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;">12</div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Users currently online</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📋 Pending Orders</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;">34</div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Awaiting fulfillment</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">✓ Completed Orders</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 32px; font-weight: bold; color: #333;">156</div>
                            <div style="font-size: 11px; color: #999; margin-top: 0.5rem;">Processed today</div>
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
                                <div style="font-size: 22px; font-weight: bold; color: #333;">₱125,400.00</div>
                            </div>
                            <div>
                                <div style="font-size: 11px; color: #999; margin-bottom: 0.25rem; font-weight: 600;">FROM ACCOUNTS</div>
                                <div style="font-size: 22px; font-weight: bold; color: #333;">7 customers</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">📈 Revenue Progress</h3></div>
                        <div class="card-body" style="padding: 1rem 0.75rem;">
                            <div style="font-size: 11px; color: #999; margin-bottom: 0.5rem; font-weight: 600;">CURRENT REVENUE THIS MONTH</div>
                            <div style="font-size: 22px; font-weight: bold; color: #333; margin-bottom: 0.75rem;">₱325,650</div>
                            <div style="background-color: #f0f0f0; border-radius: 8px; height: 8px; overflow: hidden; margin-bottom: 0.5rem;">
                                <div style="height: 100%; width: 65%; background: linear-gradient(to right, #667eea, #764ba2); border-radius: 8px;"></div>
                            </div>
                            <div style="font-size: 10px; color: #999;"><strong>65%</strong> of ₱500,000 target</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <div class="card">
                        <div class="card-header" style="padding: 0.75rem;"><h3 style="margin: 0; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">✓ System Health</h3></div>
                        <div class="card-body" style="text-align: center; padding: 1rem 0.75rem;">
                            <div style="font-size: 11px; color: #999; margin-bottom: 0.75rem; font-weight: 600;">SERVER UPTIME</div>
                            <div style="font-size: 32px; font-weight: bold; color: #333; margin-bottom: 0.5rem;">99.9%</div>
                            <div style="font-size: 11px; color: #666; background-color: #f5f5f5; padding: 0.5rem; border-radius: 4px; font-weight: 600;">✓ All systems operational</div>
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
                                <p style="margin: 0; font-size: 12px; color: #666;">Manage stock & products</p>
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
                                <p style="margin: 0; font-size: 12px; color: #666;">Track orders & revenue</p>
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
                                <p style="margin: 0; font-size: 12px; color: #666;">Purchase & suppliers</p>
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
                                <p style="margin: 0; font-size: 12px; color: #666;">Financial reports</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="customers/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">👥</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Customers</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Customer management</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4" style="margin-bottom: 0.75rem;">
                    <a href="hr/index.php" style="text-decoration: none; color: inherit;">
                        <div class="card" style="cursor: pointer; transition: all 0.3s;" onmouseover="this.style.boxShadow='0 8px 20px rgba(0,0,0,0.15)'" onmouseout="this.style.boxShadow='0 1px 3px rgba(0,0,0,0.08)'">
                            <div class="card-body" style="text-align: center; padding: 1.5rem 1rem;">
                                <div style="font-size: 40px; margin-bottom: 0.75rem;">👔</div>
                                <h3 style="margin: 0 0 0.5rem 0; font-size: 15px; font-weight: 600; color: #333;">Human Resources</h3>
                                <p style="margin: 0; font-size: 12px; color: #666;">Employee management</p>
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

