<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>
    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Sales Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Sales</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> New Sale</button>
                </div>
            </div>
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">₱12,450</div><div class="stat-label">Today's Sales</div></div></div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;"><div class="stat-card"><div class="stat-icon">🛒</div><div class="stat-value">45</div><div class="stat-label">Orders Today</div></div></div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;"><div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value">32</div><div class="stat-label">Unique Customers</div></div></div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;"><div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value">4.8</div><div class="stat-label">Avg Rating</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="ordersTab">Orders</a></li>
                <li><a href="#" class="nav-link" data-tab="invoicesTab">Invoices</a></li>
                <li><a href="#" class="nav-link" data-tab="returnsTab">Returns</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Reports</a></li>
            </ul>

            <div id="ordersTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header"><h3>Recent Orders</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Date</th><th>Status</th></tr></thead>
                                <tbody>
                                    <tr><td>#ORD-001</td><td>John Doe</td><td>₱299.99</td><td>Today</td><td><span class="badge badge-success">Completed</span></td></tr>
                                    <tr><td>#ORD-002</td><td>Jane Smith</td><td>₱149.99</td><td>Today</td><td><span class="badge badge-info">Processing</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="invoicesTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Invoices</h3></div>
                    <div class="card-body"><p>No invoices available.</p></div>
                </div>
            </div>

            <div id="returnsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Returns & Refunds</h3></div>
                    <div class="card-body"><p>No returns recorded.</p></div>
                </div>
            </div>

            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Sales Reports</h3></div>
                    <div class="card-body"><p>Reports will be generated here.</p></div>
                </div>
            </div>
        </main>
    </div>
    <script src="../js/app.js"></script>
    <script>
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
                document.getElementById(this.dataset.tab).style.display = 'block';
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });
    </script>
</body>
</html>

