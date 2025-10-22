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
    <title>Procurement - Shoe Retail ERP</title>
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
                    <h1>Procurement Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Procurement</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> New Purchase Order</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📋</div><div class="stat-value">15</div><div class="stat-label">Open POs</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">🏭</div><div class="stat-value">8</div><div class="stat-label">Active Suppliers</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📦</div><div class="stat-value">2,345</div><div class="stat-label">Items Ordered</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">₱89,250</div><div class="stat-label">Total Spend</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="posTab">Purchase Orders</a></li>
                <li><a href="#" class="nav-link" data-tab="suppliersTab">Suppliers</a></li>
                <li><a href="#" class="nav-link" data-tab="goodsTab">Goods Receipt</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Reports</a></li>
            </ul>

            <div id="posTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header"><h3>Purchase Orders</h3></div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead><tr><th>PO #</th><th>Supplier</th><th>Items</th><th>Total</th><th>Status</th></tr></thead>
                                <tbody>
                                    <tr><td>#PO-001</td><td>Nike Wholesale</td><td>50</td><td>₱5,000</td><td><span class="badge badge-success">Delivered</span></td></tr>
                                    <tr><td>#PO-002</td><td>Adidas Distributor</td><td>30</td><td>₱3,200</td><td><span class="badge badge-info">In Transit</span></td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div id="suppliersTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Suppliers</h3></div>
                    <div class="card-body"><p>Supplier list will be displayed here.</p></div>
                </div>
            </div>

            <div id="goodsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Goods Receipt</h3></div>
                    <div class="card-body"><p>Receipts will be recorded here.</p></div>
                </div>
            </div>

            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Procurement Reports</h3></div>
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

