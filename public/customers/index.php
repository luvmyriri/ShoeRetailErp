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
    <title>Customers - Shoe Retail ERP</title>
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
                    <h1>Customer Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Customers</div>
                </div>
                <div class="page-header-actions">
                    <button class="btn btn-primary"><i class="fas fa-plus"></i> Add Customer</button>
                </div>
            </div>

            <div class="row" style="margin-bottom: 2rem;">
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">👥</div><div class="stat-value">458</div><div class="stat-label">Total Customers</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">⭐</div><div class="stat-value">312</div><div class="stat-label">Active Customers</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">💬</div><div class="stat-value">₱2.3M</div><div class="stat-label">Total Sales Value</div></div></div>
                <div class="col-md-3"><div class="stat-card"><div class="stat-icon">☎️</div><div class="stat-value">89</div><div class="stat-label">Pending Contacts</div></div></div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="listTab">Customer List</a></li>
                <li><a href="#" class="nav-link" data-tab="segmentTab">Segmentation</a></li>
                <li><a href="#" class="nav-link" data-tab="contactsTab">Contact History</a></li>
                <li><a href="#" class="nav-link" data-tab="analyticsTab">Analytics</a></li>
            </ul>

            <div id="listTab" class="tab-pane active">
                <div class="card">
                    <div class="card-header">
                        <h3>All Customers</h3>
                        <div style="margin-top: 1rem;">
                            <input type="text" placeholder="Filter customers..." style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; width: 300px;">
                        </div>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Total Orders</th>
                                    <th>Total Spent</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>John Smith</td>
                                    <td>john@example.com</td>
                                    <td>555-0101</td>
                                    <td>12</td>
                                    <td>₱5,450</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Sarah Johnson</td>
                                    <td>sarah@example.com</td>
                                    <td>555-0102</td>
                                    <td>8</td>
                                    <td>₱3,200</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                                <tr>
                                    <td>Mike Davis</td>
                                    <td>mike@example.com</td>
                                    <td>555-0103</td>
                                    <td>5</td>
                                    <td>₱2,100</td>
                                    <td><span class="badge badge-warning">Inactive</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="segmentTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Customer Segmentation</h3></div>
                    <div class="card-body"><p>Customer segments and categories will be displayed here.</p></div>
                </div>
            </div>

            <div id="contactsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Contact History</h3></div>
                    <div class="card-body"><p>Customer interaction history will be displayed here.</p></div>
                </div>
            </div>

            <div id="analyticsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header"><h3>Analytics</h3></div>
                    <div class="card-body"><p>Customer analytics and insights will be displayed here.</p></div>
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

