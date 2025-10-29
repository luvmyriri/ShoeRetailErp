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
    <style>
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
        }
        .filter-row {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        .filter-group {
            flex: 1;
            min-width: 200px;
        }
        .filter-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        .filter-group input,
        .filter-group select {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        .table td:last-child {
            text-align: center;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
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
                    <button class="btn btn-primary" onclick="openNewSaleModal()"><i class="fas fa-plus"></i> New Sale</button>
                </div>
            </div>
            
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">💰</div>
                        <div class="stat-value">₱12,450</div>
                        <div class="stat-label">Today's Sales</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">🛒</div>
                        <div class="stat-value">45</div>
                        <div class="stat-label">Orders Today</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-value">32</div>
                        <div class="stat-label">Unique Customers</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">⭐</div>
                        <div class="stat-value">4.8</div>
                        <div class="stat-label">Avg Rating</div>
                    </div>
                </div>
            </div>

            <ul class="nav-tabs">
                <li><a href="#" class="nav-link active" data-tab="ordersTab">Orders</a></li>
                <li><a href="#" class="nav-link" data-tab="invoicesTab">Invoices</a></li>
                <li><a href="#" class="nav-link" data-tab="returnsTab">Returns</a></li>
                <li><a href="#" class="nav-link" data-tab="reportsTab">Reports</a></li>
                <li><a href="#" class="nav-link" data-tab="analyticsTab">Analytics</a></li>
            </ul>

            <!-- ORDERS TAB -->
            <div id="ordersTab" class="tab-pane active">
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Date Range</label>
                            <input type="date" id="orderDateFrom">
                        </div>
                        <div class="filter-group">
                            <label>To</label>
                            <input type="date" id="orderDateTo">
                        </div>
                        <div class="filter-group">
                            <label>Store</label>
                            <select id="orderStore">
                                <option value="">All Stores</option>
                                <option value="1">Downtown Store</option>
                                <option value="2">Mall Store</option>
                                <option value="3">Outlet Store</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Payment Status</label>
                            <select id="orderPaymentStatus">
                                <option value="">All Status</option>
                                <option value="Paid">Paid</option>
                                <option value="Credit">Credit</option>
                                <option value="Partial">Partial</option>
                                <option value="Refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn btn-primary" onclick="filterOrders()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Recent Orders</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportOrders()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="ordersTable">
                                <thead>
                                    <tr>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Store</th>
                                        <th>Date</th>
                                        <th>Total Amount</th>
                                        <th>Tax</th>
                                        <th>Discount</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>#S001</td>
                                        <td>Alice Johnson</td>
                                        <td>Downtown Store</td>
                                        <td>2025-10-28 10:30</td>
                                        <td>₱2,640.00</td>
                                        <td>₱240.00</td>
                                        <td>₱0.00</td>
                                        <td>Card</td>
                                        <td><span class="badge badge-success">Paid</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" onclick="viewOrder(1)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="printInvoice(1)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>#S002</td>
                                        <td>Bob Smith</td>
                                        <td>Mall Store</td>
                                        <td>2025-10-28 11:15</td>
                                        <td>₱1,540.00</td>
                                        <td>₱140.00</td>
                                        <td>₱75.00</td>
                                        <td>Cash</td>
                                        <td><span class="badge badge-warning">Credit</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" onclick="viewOrder(2)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="printInvoice(2)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- INVOICES TAB -->
            <div id="invoicesTab" class="tab-pane" style="display:none;">
                <div class="filter-section">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Invoice Number</label>
                            <input type="text" id="invoiceNumber" placeholder="Search by invoice #">
                        </div>
                        <div class="filter-group">
                            <label>Date From</label>
                            <input type="date" id="invoiceDateFrom">
                        </div>
                        <div class="filter-group">
                            <label>Date To</label>
                            <input type="date" id="invoiceDateTo">
                        </div>
                        <div class="filter-group">
                            <label>Payment Status</label>
                            <select id="invoiceStatus">
                                <option value="">All</option>
                                <option value="Paid">Paid</option>
                                <option value="Partial">Partial</option>
                                <option value="Credit">Credit</option>
                                <option value="Refunded">Refunded</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <button class="btn btn-primary" onclick="filterInvoices()">
                                <i class="fas fa-search"></i> Search
                            </button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Invoices</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportInvoices()">
                            <i class="fas fa-download"></i> Export
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="invoicesTable">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Store</th>
                                        <th>Invoice Date</th>
                                        <th>Total Amount</th>
                                        <th>Tax</th>
                                        <th>Payment Method</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>INV-2025-001</td>
                                        <td>#S001</td>
                                        <td>Alice Johnson</td>
                                        <td>Downtown Store</td>
                                        <td>2025-10-28 10:30</td>
                                        <td>₱2,640.00</td>
                                        <td>₱240.00</td>
                                        <td>Card</td>
                                        <td><span class="badge badge-success">Paid</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button onclick="openModal('viewOrderModal')">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="printInvoice(1)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                                <button class="btn btn-sm btn-secondary" onclick="downloadInvoice(1)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>INV-2025-002</td>
                                        <td>#S002</td>
                                        <td>Bob Smith</td>
                                        <td>Mall Store</td>
                                        <td>2025-10-28 11:15</td>
                                        <td>₱1,540.00</td>
                                        <td>₱140.00</td>
                                        <td>Cash</td>
                                        <td><span class="badge badge-warning">Credit</span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn btn-sm btn-primary" onclick="viewInvoice(2)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-info" onclick="printInvoice(2)">
                                                    <i class="fas fa-print"></i>
                                                </button>
                                                <button class="btn btn-sm btn-secondary" onclick="downloadInvoice(2)">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RETURNS TAB -->
            <div id="returnsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Returns & Refunds</h3>
                        <button class="btn btn-primary btn-sm" onclick="openReturnModal()">
                            <i class="fas fa-plus"></i> Process Return
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table" id="returnsTable">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Original Sale</th>
                                        <th>Customer</th>
                                        <th>Return Date</th>
                                        <th>Reason</th>
                                        <th>Items</th>
                                        <th>Refund Amount</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="9" style="text-align: center; padding: 2rem;">
                                            No returns recorded yet
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REPORTS TAB -->
            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="summary-grid">
                    <div class="summary-item">
                        <div class="summary-value">₱125,450</div>
                        <div class="summary-label">Monthly Revenue</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">342</div>
                        <div class="summary-label">Total Orders</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">₱367</div>
                        <div class="summary-label">Avg Order Value</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-value">15</div>
                        <div class="summary-label">Returns</div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Sales Reports</h3>
                        <div>
                            <select id="reportType" style="padding: 0.5rem; margin-right: 0.5rem;">
                                <option value="daily">Daily Report</option>
                                <option value="weekly">Weekly Report</option>
                                <option value="monthly">Monthly Report</option>
                                <option value="yearly">Yearly Report</option>
                            </select>
                            <button class="btn btn-primary btn-sm" onclick="generateReport()">
                                <i class="fas fa-chart-bar"></i> Generate
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Store</th>
                                        <th>Total Sales</th>
                                        <th>Orders</th>
                                        <th>Avg Order</th>
                                        <th>Tax Collected</th>
                                        <th>Discounts</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Oct 28, 2025</td>
                                        <td>Downtown Store</td>
                                        <td>₱15,420</td>
                                        <td>42</td>
                                        <td>₱367</td>
                                        <td>₱1,402</td>
                                        <td>₱520</td>
                                    </tr>
                                    <tr>
                                        <td>Oct 28, 2025</td>
                                        <td>Mall Store</td>
                                        <td>₱12,350</td>
                                        <td>35</td>
                                        <td>₱353</td>
                                        <td>₱1,123</td>
                                        <td>₱480</td>
                                    </tr>
                                    <tr>
                                        <td>Oct 28, 2025</td>
                                        <td>Outlet Store</td>
                                        <td>₱8,920</td>
                                        <td>28</td>
                                        <td>₱319</td>
                                        <td>₱811</td>
                                        <td>₱350</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ANALYTICS TAB -->
            <div id="analyticsTab" class="tab-pane" style="display:none;">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Sales Trend</h3></div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="salesTrendChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Payment Methods</h3></div>
                            <div class="card-body">
                                <div class="chart-container">
                                    <canvas id="paymentMethodsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row" style="margin-top: 1rem;">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Top Products</h3></div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Units Sold</th>
                                            <th>Revenue</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Nike Air Max 90</td>
                                            <td>45</td>
                                            <td>₱5,400</td>
                                        </tr>
                                        <tr>
                                            <td>Adidas Ultraboost 22</td>
                                            <td>38</td>
                                            <td>₱5,320</td>
                                        </tr>
                                        <tr>
                                            <td>Local Casual Sneaker</td>
                                            <td>52</td>
                                            <td>₱3,640</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header"><h3>Store Performance</h3></div>
                            <div class="card-body">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Store</th>
                                            <th>Orders</th>
                                            <th>Revenue</th>
                                            <th>Growth</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Downtown Store</td>
                                            <td>156</td>
                                            <td>₱52,340</td>
                                            <td><span class="badge badge-success">+12%</span></td>
                                        </tr>
                                        <tr>
                                            <td>Mall Store</td>
                                            <td>142</td>
                                            <td>₱48,920</td>
                                            <td><span class="badge badge-success">+8%</span></td>
                                        </tr>
                                        <tr>
                                            <td>Outlet Store</td>
                                            <td>98</td>
                                            <td>₱31,450</td>
                                            <td><span class="badge badge-danger">-3%</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- New Sale Modal -->
    <div id="newSaleModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>New Sale</h2>
                <span class="close" onclick="closeModal('newSaleModal')">&times;</span>
            </div>
            <form id="newSaleForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Customer</label>
                        <select id="customerId" required>
                            <option value="">Select Customer</option>
                            <option value="1">Alice Johnson (MEM-001)</option>
                            <option value="2">Bob Smith (MEM-002)</option>
                            <option value="3">Carol Davis (MEM-003)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Store</label>
                        <select id="storeId" required>
                            <option value="">Select Store</option>
                            <option value="1">Downtown Store</option>
                            <option value="2">Mall Store</option>
                            <option value="3">Outlet Store</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select id="paymentMethod" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Credit">Credit</option>
                            <option value="Loyalty">Loyalty Points</option>
                        </select>
                    </div>
                </div>

                <div class="product-items">
                    <h4>Products</h4>
                    <div id="productItemsContainer">
                        <div class="product-item">
                            <div class="form-group">
                                <select class="product-select" required>
                                    <option value="">Select Product</option>
                                    <option value="1">Nike Air Max 90 - Size 9.5 - Black</option>
                                    <option value="2">Nike Air Max 90 - Size 10 - White</option>
                                    <option value="3">Adidas Ultraboost 22 - Size 9 - Blue</option>
                                    <option value="4">Adidas Ultraboost 22 - Size 10 - Grey</option>
                                    <option value="5">Local Casual Sneaker - Size 8.5 - Brown</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="number" class="quantity-input" placeholder="Qty" min="1" value="1" required>
                            </div>
                            <div class="form-group">
                                <input type="number" class="price-input" placeholder="Price" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="subtotal-input" placeholder="Subtotal" readonly>
                            </div>
                            <button type="button" class="btn btn-danger btn-sm" onclick="removeProductItem(this)">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" onclick="addProductItem()">
                        <i class="fas fa-plus"></i> Add Product
                    </button>
                </div>

                <div class="form-row" style="margin-top: 1.5rem;">
                    <div class="form-group">
                        <label>Discount Amount</label>
                        <input type="number" id="discountAmount" step="0.01" value="0.00">
                    </div>
                    <div class="form-group">
                        <label>Use Loyalty Points</label>
                        <input type="number" id="pointsUsed" min="0" value="0">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Notes</label>
                        <textarea id="saleNotes" rows="3"></textarea>
                    </div>
                </div>

                <div class="summary-grid" style="margin-top: 1.5rem;">
                    <div class="summary-item">
                        <div class="summary-label">Subtotal</div>
                        <div class="summary-value" id="saleSubtotal">₱0.00</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Tax (10%)</div>
                        <div class="summary-value" id="saleTax">₱0.00</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Discount</div>
                        <div class="summary-value" id="saleDiscount">₱0.00</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Total</div>
                        <div class="summary-value" id="saleTotal">₱0.00</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('newSaleModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Sale</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Return Modal -->
    <div id="returnModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Process Return</h2>
                <span class="close" onclick="closeModal('returnModal')">&times;</span>
            </div>
            <form id="returnForm">
                <div class="form-row">
                    <div class="form-group">
                        <label>Original Sale ID</label>
                        <input type="text" id="returnSaleId" placeholder="Search sale ID" required>
                    </div>
                    <div class="form-group">
                        <label>Return Date</label>
                        <input type="date" id="returnDate" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Reason for Return</label>
                        <select id="returnReason" required>
                            <option value="">Select Reason</option>
                            <option value="Defective">Defective Product</option>
                            <option value="Wrong Size">Wrong Size</option>
                            <option value="Wrong Color">Wrong Color</option>
                            <option value="Customer Changed Mind">Customer Changed Mind</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Refund Method</label>
                        <select id="refundMethod" required>
                            <option value="Cash">Cash</option>
                            <option value="Card">Card Refund</option>
                            <option value="Store Credit">Store Credit</option>
                            <option value="Exchange">Exchange</option>
                        </select>
                    </div>
                </div>

                <div class="product-items">
                    <h4>Items to Return</h4>
                    <div id="returnItemsContainer">
                        <div class="product-item">
                            <div class="form-group">
                                <select class="return-product-select" required>
                                    <option value="">Select Product</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <input type="number" class="return-quantity" placeholder="Qty" min="1" value="1" required>
                            </div>
                            <div class="form-group">
                                <input type="text" class="return-reason-detail" placeholder="Additional details">
                            </div>
                            <div class="form-group">
                                <input type="number" class="return-amount" placeholder="Refund Amount" step="0.01" readonly>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 1rem;">
                    <label>Additional Notes</label>
                    <textarea id="returnNotes" rows="3"></textarea>
                </div>

                <div class="summary-grid" style="margin-top: 1.5rem;">
                    <div class="summary-item">
                        <div class="summary-label">Total Refund</div>
                        <div class="summary-value" id="totalRefund">₱0.00</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Restocking Fee</div>
                        <div class="summary-value" id="restockingFee">₱0.00</div>
                    </div>
                    <div class="summary-item">
                        <div class="summary-label">Net Refund</div>
                        <div class="summary-value" id="netRefund">₱0.00</div>
                    </div>
                </div>

                <div style="margin-top: 1.5rem; text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('returnModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary">Process Return</button>
                </div>
            </form>
        </div>
    </div>

    <script src="../js/app.js"></script>
    <script>
        // Tab Switching
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelectorAll('.tab-pane').forEach(pane => pane.style.display = 'none');
                document.getElementById(this.dataset.tab).style.display = 'block';
                document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            });
        });

        // Modal Functions
       function openNewSaleModal() {
    const modal = document.getElementById('newSaleModal');
    modal.style.display = 'flex';
    modal.classList.add('active');
}


        function openReturnModal() {
            const modal = document.getElementById('returnModal');
            modal.style.display = 'flex';
            modal.classList.add('active');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            modal.style.display = 'none';
            modal.classList.remove('active');
        }

        function viewOrder(orderId) {
            // Remove any existing modal
            const existingModal = document.getElementById('viewOrderModal');
            if (existingModal) existingModal.remove();

            // Create modal HTML
            const orderDetails = `
                <div id="viewOrderModal" class="vieworder-modal">
                    <div class="vieworder-modal-content">
                        <div class="vieworder-modal-header">
                            <h2>Order Details</h2>
                        </div>
                        <div id="orderDetailsContent" class="viewOrder-details">
                            <div style="padding: 1rem;">
                                <h3>Sale #S00${orderId}</h3>
                                <div class="form-row">
                                    <div><strong>Customer:</strong> Alice Johnson</div>
                                    <div><strong>Store:</strong> Downtown Store</div>
                                    <div><strong>Date:</strong> 2025-10-28 10:30</div>
                                </div>
                                <h4 style="margin-top: 1.5rem;">Items</h4>
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Product</th>
                                            <th>Quantity</th>
                                            <th>Unit Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Nike Air Max 90 - Black</td>
                                            <td>2</td>
                                            <td>₱1,200.00</td>
                                            <td>₱2,400.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                                <div style="text-align: right; margin-top: 1rem;">
                                    <p><strong>Subtotal:</strong> ₱2,400.00</p>
                                    <p><strong>Tax:</strong> ₱240.00</p>
                                    <p><strong>Total:</strong> ₱2,640.00</p>
                                </div>
                                <div style="margin-top: 1.5rem; text-align: right;">
                                    <button class="btn btn-secondary" onclick="closeModal('viewOrderModal')">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Append modal to body
            document.body.insertAdjacentHTML('beforeend', orderDetails);

            // Show modal
            const modal = document.getElementById('viewOrderModal');
            modal.style.display = 'flex';
            modal.classList.add('active');
        }

        // Product Item Management
        function addProductItem() {
            const container = document.getElementById('productItemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'product-item';
            newItem.innerHTML = `
                <div class="form-group">
                    <select class="product-select" required>
                        <option value="">Select Product</option>
                        <option value="1">Nike Air Max 90 - Size 9.5 - Black</option>
                        <option value="2">Nike Air Max 90 - Size 10 - White</option>
                        <option value="3">Adidas Ultraboost 22 - Size 9 - Blue</option>
                        <option value="4">Adidas Ultraboost 22 - Size 10 - Grey</option>
                        <option value="5">Local Casual Sneaker - Size 8.5 - Brown</option>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" class="quantity-input" placeholder="Qty" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <input type="number" class="price-input" placeholder="Price" step="0.01" required>
                </div>
                <div class="form-group">
                    <input type="text" class="subtotal-input" placeholder="Subtotal" readonly>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="removeProductItem(this)">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newItem);
            attachProductItemListeners(newItem);
        }

        function removeProductItem(btn) {
            btn.closest('.product-item').remove();
            calculateSaleTotal();
        }

        function attachProductItemListeners(item) {
            const quantityInput = item.querySelector('.quantity-input');
            const priceInput = item.querySelector('.price-input');
            const subtotalInput = item.querySelector('.subtotal-input');

            function updateSubtotal() {
                const qty = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const subtotal = qty * price;
                subtotalInput.value = '₱' + subtotal.toFixed(2);
                calculateSaleTotal();
            }

            quantityInput.addEventListener('input', updateSubtotal);
            priceInput.addEventListener('input', updateSubtotal);
        }

        function calculateSaleTotal() {
            let subtotal = 0;
            document.querySelectorAll('.product-item').forEach(item => {
                const qty = parseFloat(item.querySelector('.quantity-input').value) || 0;
                const price = parseFloat(item.querySelector('.price-input').value) || 0;
                subtotal += qty * price;
            });

            const discount = parseFloat(document.getElementById('discountAmount')?.value) || 0;
            const pointsUsed = parseFloat(document.getElementById('pointsUsed')?.value) || 0;
            const pointsDiscount = pointsUsed * 1.0; // 1 point = ₱1

            const totalDiscount = discount + pointsDiscount;
            const taxableAmount = subtotal - totalDiscount;
            const tax = taxableAmount * 0.10;
            const total = taxableAmount + tax;

            document.getElementById('saleSubtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('saleTax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('saleDiscount').textContent = '₱' + totalDiscount.toFixed(2);
            document.getElementById('saleTotal').textContent = '₱' + total.toFixed(2);
        }

        // Initialize event listeners for existing product items
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.product-item').forEach(item => {
                attachProductItemListeners(item);
            });

            // Discount and points listeners
            const discountInput = document.getElementById('discountAmount');
            const pointsInput = document.getElementById('pointsUsed');
            if (discountInput) discountInput.addEventListener('input', calculateSaleTotal);
            if (pointsInput) pointsInput.addEventListener('input', calculateSaleTotal);
        });

        // Filter Functions
        function filterOrders() {
            console.log('Filtering orders...');
            // Implementation for filtering orders
        }

        function filterInvoices() {
            console.log('Filtering invoices...');
            // Implementation for filtering invoices
        }

        // Export Functions
        function exportOrders() {
            console.log('Exporting orders...');
            alert('Export functionality will be implemented with backend connection');
        }

        function exportInvoices() {
            console.log('Exporting invoices...');
            alert('Export functionality will be implemented with backend connection');
        }

        // Invoice Functions
        function viewInvoice(invoiceId) {
            console.log('Viewing invoice:', invoiceId);
            alert('Invoice view will be implemented with backend connection');
        }

        function printInvoice(invoiceId) {
            console.log('Printing invoice:', invoiceId);
            alert('Print functionality will be implemented');
        }

        function downloadInvoice(invoiceId) {
            console.log('Downloading invoice:', invoiceId);
            alert('Download functionality will be implemented');
        }

        // Report Functions
        function generateReport() {
            const reportType = document.getElementById('reportType').value;
            console.log('Generating report:', reportType);
            alert(`Generating ${reportType} report...`);
        }

        // Form Submissions
        document.getElementById('newSaleForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Processing new sale...');
            alert('Sale will be processed when backend is connected');
            closeModal('newSaleModal');
        });

        document.getElementById('returnForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            console.log('Processing return...');
            alert('Return will be processed when backend is connected');
            closeModal('returnModal');
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
    </script>
</body>
</html>