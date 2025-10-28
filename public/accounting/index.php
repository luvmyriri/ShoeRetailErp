<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Accounting'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting - Shoe Retail ERP</title>
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
                    <h1>Accounting Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Accounting</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-download"></i> Export Report</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💰</div><div class="stat-value">₱425,890</div><div class="stat-label">Total Revenue</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📉</div><div class="stat-value">₱156,234</div><div class="stat-label">Total Expenses</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">📊</div><div class="stat-value">₱269,656</div><div class="stat-label">Net Income</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💳</div><div class="stat-value">₱45,230</div><div class="stat-label">Receivables</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="arTab">Accounts Receivable</a></li>
                <li><a href="#" class="nav-link" data-tab="apTab">Accounts Payable</a></li>
                <li><a href="#" class="nav-link" data-tab="ledgerTab">General Ledger</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Financial Reports</a></li>
            </ul>

            <div id="arTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header"><h3>Accounts Receivable</h3></div>
                    <div class="card-body">
                        <table class="table"><thead><tr><th>Invoice #</th><th>Customer</th><th>Amount</th><th>Due Date</th><th>Status</th></tr></thead>
                        <tbody><tr><td>INV-001</td><td>ABC Retail</td><td>₱5,000</td><td>Oct 15</td><td><span class="badge badge-danger">Overdue</span></td></tr></tbody></table>
                    </div>
                </div>
            </div>

            <div id="apTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Accounts Payable</h3></div>
                    <div class="card-body"><p>Payable records will be displayed here.</p></div>
                </div>
            </div>

            <div id="ledgerTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>General Ledger</h3></div>
                    <div class="card-body"><p>Ledger entries will be displayed here.</p></div>
                </div>
            </div>

            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Financial Reports</h3></div>
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

