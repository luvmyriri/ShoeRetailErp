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
    <title>Inventory - Shoe Retail ERP</title>
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
                    <h1>Inventory Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Inventory</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📦</div><div class="stat-value">1,234</div><div class="stat-label">Total Products</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">⚠️</div><div class="stat-value">45</div><div class="stat-label">Low Stock Items</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">₱234,567</div><div class="stat-label">Inventory Value</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📊</div><div class="stat-value">92%</div><div class="stat-label">Stock Health</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="stockTab">Stock Levels</a></li>
                <li><a href="#" class="nav-link" data-tab="transferTab">Stock Transfers</a></li>
                <li><a href="#" class="nav-link" data-tab="alertsTab">Low Stock Alerts</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Reports</a></li>
            </ul>

            <div id="stockTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header"><h3>Current Stock Levels</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>Product Code</th><th>Brand</th><th>Model</th><th>Size</th><th>Current Stock</th><th>Min Level</th><th>Status</th><th>Actions</th></tr></thead>
                                <tbody>
                                    <tr><td>#PRD-001</td><td>Nike</td><td>Air Max 90</td><td>10</td><td><span class="badge badge-success">45</span></td><td>20</td><td><span class="badge badge-success">Optimal</span></td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                                    <tr><td>#PRD-002</td><td>Adidas</td><td>Stan Smith</td><td>9</td><td><span class="badge badge-warning">15</span></td><td>20</td><td><span class="badge badge-warning">Low Stock</span></td><td><button class="btn btn-sm btn-outline">Edit</button></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="transferTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Stock Transfer History</h3></div>
                    <div class="card-body">
                        <p>No transfers recorded yet.</p>
                    </div>
                </div>
            </div>

            <div id="alertsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Low Stock Alerts</h3></div>
                    <div class="card-body">
                        <p>You have 3 products with low stock levels.</p>
                    </div>
                </div>
            </div>

            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Inventory Reports</h3></div>
                    <div class="card-body">
                        <p>Reports will be generated here.</p>
                    </div>
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

