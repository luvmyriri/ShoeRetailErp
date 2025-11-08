<?php
session_start();

require __DIR__ . '/../../config/database.php';

function add_alert($type, $msg) {
    $_SESSION['flash_alert'] = ['type' => $type, 'msg' => $msg];
}

// --- POST Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'process_sale') {
        $customer_id = !empty($_POST['customerId']) ? intval($_POST['customerId']) : null;
        $store_id = !empty($_POST['storeId']) ? intval($_POST['storeId']) : null;
        $payment_method = $_POST['paymentMethod'] ?? 'Cash';
        $discount = (float)($_POST['discountAmount'] ?? 0);
        $points_used = (int)($_POST['pointsUsed'] ?? 0);
        $notes = $_POST['saleNotes'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;
        $sale_items_json = $_POST['sale_items_json'] ?? '[]';

        $sale_items = json_decode($sale_items_json, true);
        if (!is_array($sale_items) || count($sale_items) === 0) {
            add_alert('danger', 'Sale must contain at least one product.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // compute totals
        $subtotal = 0.0;
        foreach ($sale_items as $it) {
            $qty = (float)($it['quantity'] ?? 0);
            $price = (float)($it['price'] ?? 0);
            $subtotal += $qty * $price;
        }
        $taxable = max(0.0, $subtotal - ($discount + $points_used));
        $tax = round($taxable * 0.10, 2);
        $total = round($taxable + $tax, 2);

        $db = getDB();
        $db->beginTransaction();

        try {
            // Insert sales (PascalCase columns)
            $sale_id = $db->insert(
                "INSERT INTO sales (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PointsUsed, PointsEarned, PaymentStatus, PaymentMethod, SalespersonID, SaleDate) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $customer_id, $store_id, $total, $tax, $discount, $points_used, 0,
                    ($payment_method === 'Credit' ? 'Credit' : 'Paid'), $payment_method, $user_id
                ]
            );

            // Insert line items and decrement inventory
            foreach ($sale_items as $it) {
                $product_id = (int)$it['product_id'];
                $quantity = (float)$it['quantity'];
                $unit_price = (float)$it['price'];
                $line_subtotal = round($quantity * $unit_price, 2);

                $db->execute(
                    "INSERT INTO saledetails (SaleID, ProductID, Quantity, SalesUnitID, QuantityBase, UnitPrice, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [$sale_id, $product_id, $quantity, 1, $quantity, $unit_price, $line_subtotal]
                );

                $db->execute(
                    "UPDATE inventory SET Quantity = GREATEST(0, Quantity - ?) WHERE ProductID = ? AND StoreID = ?",
                    [$quantity, $product_id, $store_id]
                );
            }

            // Create invoice (PascalCase columns)
            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
            $db->execute(
                "INSERT INTO invoices (InvoiceNumber, SaleID, CustomerID, StoreID, InvoiceDate, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus, CreatedBy) 
                 VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)",
                [
                    $invoiceNumber, $sale_id, $customer_id, $store_id, $total, $tax, $discount,
                    $payment_method, ($payment_method === 'Credit' ? 'Credit' : 'Paid'), (string)$user_id
                ]
            );

            $db->commit();
            add_alert('success', 'Sale processed successfully. Sale ID: #' . $sale_id);
        } catch (Exception $ex) {
            $db->rollback();
            add_alert('danger', 'Failed to process sale: ' . $ex->getMessage());
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;

    } elseif ($action === 'process_return') {
        $original_sale_id = (int)($_POST['returnSaleId'] ?? 0);
        $return_date = $_POST['returnDate'] ?? date('Y-m-d');
        $reason = $_POST['returnReason'] ?? '';
        $refund_method = $_POST['refundMethod'] ?? 'Cash';
        $return_items_json = $_POST['return_items_json'] ?? '[]';
        $notes = $_POST['returnNotes'] ?? '';
        $user_id = $_SESSION['user_id'] ?? null;
        $return_items = json_decode($return_items_json, true);

        if ($original_sale_id <= 0 || !is_array($return_items) || count($return_items) === 0) {
            add_alert('danger', 'Return must contain a valid Sale ID and at least one item.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        // Compute refund totals (5% restocking fee)
        $totalRefund = 0.0;
        foreach ($return_items as $it) { $totalRefund += (float)($it['refund_amount'] ?? 0); }
        $restockingFee = round($totalRefund * 0.05, 2);
        $netRefund = round($totalRefund - $restockingFee, 2);

        // Fetch sale header for customer/store
        $saleHdr = dbFetchOne("SELECT CustomerID, StoreID FROM sales WHERE SaleID = ?", [$original_sale_id]);
        $customer_id = $saleHdr['CustomerID'] ?? null;
        $store_id = $saleHdr['StoreID'] ?? null;

        $db = getDB();
        $db->beginTransaction();
        try {
            // Insert return summary (PascalCase columns)
            $return_id = $db->insert(
                "INSERT INTO returns (SaleID, CustomerID, StoreID, ReturnDate, Reason, RefundMethod, RefundAmount, RestockingFee, NetRefund, Status, ProcessedBy, Notes, CreatedAt)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [
                    $original_sale_id, $customer_id, $store_id, $return_date, $reason, $refund_method,
                    $totalRefund, $restockingFee, $netRefund, 'Completed', $user_id, substr($notes,0,1000)
                ]
            );

            // Update inventory for each returned item
            foreach ($return_items as $it) {
                $product_id = (int)$it['product_id'];
                $quantity = (float)$it['quantity'];
                $db->execute(
                    "UPDATE inventory SET Quantity = Quantity + ? WHERE ProductID = ? AND StoreID = ?",
                    [$quantity, $product_id, $store_id]
                );
            }

            $db->commit();
            add_alert('success', 'Return processed successfully. Return ID: #' . $return_id);
        } catch (Exception $ex) {
            $db->rollback();
            add_alert('danger', 'Failed to process return: ' . $ex->getMessage());
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// --- Safe Query Helper ---
function safe_query_assoc($sql, $params = []) {
    try {
        return dbFetchAll($sql, $params);
    } catch (Exception $e) {
        logError("Query failed", ['sql' => $sql, 'error' => $e->getMessage()]);
        return [];
    }
}

// --- Stats ---
$today = date('Y-m-d');
$stats = ['today_sales' => 0, 'orders_today' => 0, 'unique_customers_today' => 0, 'avg_rating' => 0];

try {
    $row = dbFetchOne("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['today_sales'] = $row['total'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(*) AS cnt FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['orders_today'] = $row['cnt'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(DISTINCT CustomerID) AS cnt FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['unique_customers_today'] = $row['cnt'] ?? 0;

    $row = @dbFetchOne("SELECT AVG(rating) AS avg_rating FROM product_reviews");
    $stats['avg_rating'] = $row && isset($row['avg_rating']) ? round((float)$row['avg_rating'], 2) : 0;
} catch (Exception $e) {
    logError("Stats failed", ['error' => $e->getMessage()]);
}

// --- Fetch Data ---
$customers = safe_query_assoc("SELECT CustomerID AS id, CONCAT(FirstName, ' ', COALESCE(LastName,'')) AS name, MemberNumber AS member_code FROM customers ORDER BY name");
$stores = safe_query_assoc("SELECT StoreID AS id, StoreName AS name FROM stores ORDER BY StoreName");
$products = safe_query_assoc("SELECT p.ProductID AS id, p.SKU AS sku, CONCAT(p.Brand,' ',p.Model,' ',p.Size) AS name, p.SellingPrice AS price, COALESCE(SUM(i.Quantity),0) AS stock FROM products p LEFT JOIN inventory i ON i.ProductID = p.ProductID GROUP BY p.ProductID ORDER BY name");

// --- Orders with Items ---
$orders = safe_query_assoc(" 
    SELECT s.SaleID AS sale_id, s.TotalAmount AS total_amount, s.TaxAmount AS tax, s.DiscountAmount AS discount, s.PaymentMethod AS payment_method, s.SaleDate AS created_at, s.PaymentStatus AS status,
           c.FirstName AS cust_first, c.LastName AS cust_last, st.StoreName AS store_name, st.StoreID AS store_id
    FROM sales s
    LEFT JOIN customers c ON s.CustomerID = c.CustomerID
    LEFT JOIN stores st ON s.StoreID = st.StoreID
    ORDER BY s.SaleDate DESC
    LIMIT 50
");

foreach ($orders as &$o) {
$o['items'] = safe_query_assoc("
        SELECT sd.Quantity AS quantity, sd.UnitPrice AS unit_price, CONCAT(p.Brand,' ',p.Model) AS product_name
        FROM saledetails sd
        JOIN products p ON sd.ProductID = p.ProductID
        WHERE sd.SaleID = ?
    ", [$o['sale_id']]);
}
unset($o);

// --- Invoices with Items ---
$invoices = safe_query_assoc(" 
    SELECT i.InvoiceID AS id, i.InvoiceNumber AS invoice_number, i.SaleID AS sale_id, i.TotalAmount AS total_amount, i.InvoiceDate AS invoice_date,
           i.PaymentMethod, i.PaymentStatus AS payment_status, c.FirstName AS cust_first, c.LastName AS cust_last, st.StoreName AS store_name
    FROM invoices i
    JOIN sales s ON i.SaleID = s.SaleID
    LEFT JOIN customers c ON s.CustomerID = c.CustomerID
    LEFT JOIN stores st ON s.StoreID = st.StoreID
    ORDER BY i.InvoiceDate DESC
    LIMIT 50
");

foreach ($invoices as &$inv) {
$inv['items'] = safe_query_assoc("
        SELECT sd.Quantity AS quantity, sd.UnitPrice AS unit_price, CONCAT(p.Brand,' ',p.Model) AS product_name
        FROM saledetails sd
        JOIN products p ON sd.ProductID = p.ProductID
        WHERE sd.SaleID = ?
    ", [$inv['sale_id']]);
}
unset($inv);

// --- Returns ---
$returns = safe_query_assoc("
    SELECT r.ReturnID AS id, r.SaleID AS sale_id, r.ReturnDate AS return_date, r.Reason AS reason, r.NetRefund AS refund_amount,
           c.FirstName AS cust_first, c.LastName AS cust_last
    FROM returns r
    JOIN sales s ON r.SaleID = s.SaleID
    LEFT JOIN customers c ON s.CustomerID = c.CustomerID
    ORDER BY r.ReturnDate DESC
    LIMIT 50
");
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
        .filter-section { background: #f8f9fa; padding: 1rem; margin-bottom: 1.5rem; border-radius: 8px; }
        .filter-row { display: flex; gap: 1rem; flex-wrap: wrap; align-items: flex-end; }
        .filter-group { flex: 1; min-width: 200px; }
        .filter-group label { display: block; margin-bottom: 0.5rem; font-weight: 500; color: #333; }
        .filter-group input, .filter-group select { width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; }
        .action-buttons { display: flex; gap: 0.5rem; justify-content: center; }
        .table td:last-child { text-align: center; }
        .btn-sm { padding: 0.25rem 0.75rem; font-size: 0.875rem; }

        /* ======= Modal fixes for Sales (New Sale, Process Return, View Details) ======= */
        body.modal-open { overflow: hidden; }

        /* Backdrop + centering */
        .modal { position: fixed; inset: 0; width: 100vw; height: 100vh; transform: none; display: none; background: rgba(0,0,0,0.45); z-index: 10000; }
        .modal.active { display: flex; align-items: center; justify-content: center; }
        /* Force ID-scoped centering to override any global modal rules */
        #newSaleModal, #returnModal, #detailModal { position: fixed !important; top:0 !important; left:0 !important; right:0 !important; bottom:0 !important; width:100vw !important; height:100vh !important; display:none; background: rgba(0,0,0,0.45); z-index:10000; }
        #newSaleModal.active, #returnModal.active, #detailModal.active { display:flex !important; align-items:center !important; justify-content:center !important; }

        /* Shared content container */
        #newSaleModal .modal-content, #returnModal .modal-content, #detailModal .modal-content { margin: 0 auto !important; }
        .modal .modal-content { width: 95%; max-height: 85vh; overflow: auto; border-radius: 10px; background: #fff; box-shadow: 0 10px 30px rgba(0,0,0,.15); }
        /* Size variants */
        #newSaleModal .modal-content, #returnModal .modal-content { max-width: 960px; }
        #detailModal .modal-content { max-width: 800px; }

        /* Sticky header inside modals for better UX while scrolling */
        .modal .modal-header { position: sticky; top: 0; background: #fff; z-index: 1; padding: 1rem 1.25rem; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .modal .modal-body { padding: 1rem 1.25rem; }
        .modal .modal-actions { padding: 1rem 1.25rem; border-top: 1px solid #eee; display: flex; gap: .5rem; justify-content: flex-end; }
        .modal .close { cursor: pointer; font-size: 1.5rem; line-height: 1; color: #666; padding: .25rem .5rem; }
        .modal .close:hover { color: #333; }

        /* Product/Return item grids: wrap neatly on small screens */
        .product-item { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: .5rem; align-items: end; }
        @media (max-width: 900px) { .product-item { grid-template-columns: 1fr 1fr; align-items: start; } .product-item .form-group { margin-bottom: .25rem; } }

        /* Inline alerts used on this page */
        .alert { padding: .75rem 1rem; border-radius: 6px; margin: 1rem; }
        .alert-success { background: #e6ffed; color: #1a7f37; border: 1px solid #b3f0c7; }
        .alert-danger { background: #ffe6e6; color: #a10d0d; border: 1px solid #ffb3b3; }

        .items-list { font-size: 0.85rem; line-height: 1.5; }
        .items-list div { padding: 2px 0; border-bottom: 1px dashed #eee; }
        .items-list div:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>

    <div class="alert-container">
        <?php if (!empty($_SESSION['flash_alert'])): 
            $a = $_SESSION['flash_alert'];
            $cls = $a['type'] === 'success' ? 'alert-success' : ($a['type'] === 'danger' ? 'alert-danger' : 'alert-info');
        ?>
            <div class="alert <?php echo $cls; ?>"><?php echo htmlspecialchars($a['msg']); ?></div>
        <?php unset($_SESSION['flash_alert']); endif; ?>
    </div>

    <div class="main-wrapper" style="margin-left: 0;">
        <main class="main-content">
            <div class="page-header">
                <div class="page-header-title">
                    <h1>Sales Management</h1>
                    <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Sales</div>
                </div>
                <div class="page-header-actions" style="display:flex; gap:.5rem; align-items:center;">
                    <button class="btn btn-primary" type="button" onclick="openNewSaleModal()">New Sale</button>
                    <a href="pos.php" style="text-decoration: none;">
                        <button class="btn btn-secondary" type="button">Point of Sale</button>
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Money</div>
                        <div class="stat-value">₱<?php echo number_format($stats['today_sales'], 2); ?></div>
                        <div class="stat-label">Today's Sales</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Cart</div>
                        <div class="stat-value"><?php echo intval($stats['orders_today']); ?></div>
                        <div class="stat-label">Orders Today</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">People</div>
                        <div class="stat-value"><?php echo intval($stats['unique_customers_today']); ?></div>
                        <div class="stat-label">Unique Customers</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Star</div>
                        <div class="stat-value"><?php echo number_format($stats['avg_rating'], 1); ?></div>
                        <div class="stat-label">Avg Rating</div>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
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
                                <?php foreach ($stores as $st): ?>
                                    <option value="<?php echo $st['id']; ?>"><?php echo htmlspecialchars($st['name']); ?></option>
                                <?php endforeach; ?>
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
                            <button class="btn btn-primary" onclick="filterOrders()">Search</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Recent Orders</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportOrders()">Export</button>
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
                                        <th>Items (Qty × Unit Cost)</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr><td colspan="9" style="text-align:center; padding:1.5rem;">No sales yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($orders as $o): 
                                            $custName = trim(($o['cust_first'] ?? '') . ' ' . ($o['cust_last'] ?? '')) ?: 'Walk-in';
                                            $status = $o['status'] ?? 'Paid';
                                        ?>
                                            <tr data-date="<?php echo htmlspecialchars(date('Y-m-d', strtotime($o['created_at']))); ?>" data-storeid="<?php echo (int)($o['store_id'] ?? 0); ?>" data-status="<?php echo htmlspecialchars($status); ?>" data-payment="<?php echo htmlspecialchars($o['payment_method'] ?? ''); ?>">
                                                <td>#S<?php echo str_pad($o['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?php echo htmlspecialchars($custName); ?></td>
                                                <td><?php echo htmlspecialchars($o['store_name'] ?? ''); ?></td>
                                                <td><?php echo date('M j, Y', strtotime($o['created_at'])); ?></td>
                                                <td>
                                                    <div class="items-list">
                                                        <?php foreach ($o['items'] as $it): ?>
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($it['product_name']); ?></strong><br>
                                                                Qty: <?php echo $it['quantity']; ?> × ₱<?php echo number_format($it['unit_price'], 2); ?>
                                                                = ₱<?php echo number_format($it['quantity'] * $it['unit_price'], 2); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td>₱<?php echo number_format($o['total_amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($o['payment_method'] ?? ($o['PaymentMethod'] ?? '')); ?></td>
                                                <td><span class="badge <?php echo ($status === 'Paid' ? 'badge-success' : 'badge-warning'); ?>"><?php echo $status; ?></span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-info" onclick="printInvoice(<?php echo $o['sale_id']; ?>)">Print</button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                            <button class="btn btn-primary" onclick="filterInvoices()">Search</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Invoices</h3>
                        <button class="btn btn-secondary btn-sm" onclick="exportInvoices()">Export</button>
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
                                        <th>Date</th>
                                        <th>Items (Qty × Unit Cost)</th>
                                        <th>Total</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoices)): ?>
                                        <tr><td colspan="9" style="text-align:center; padding:1.5rem;">No invoices yet</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($invoices as $inv): 
                                            $cust = trim(($inv['cust_first'] ?? '') . ' ' . ($inv['cust_last'] ?? '')) ?: 'Walk-in';
                                        ?>
                                        <tr data-invoice-number="<?php echo htmlspecialchars($inv['invoice_number']); ?>" data-date="<?php echo htmlspecialchars(date('Y-m-d', strtotime($inv['invoice_date']))); ?>" data-status="<?php echo htmlspecialchars($inv['payment_status'] ?? ''); ?>">
                                            <td><?php echo htmlspecialchars($inv['invoice_number']); ?></td>
                                            <td>#S<?php echo str_pad($inv['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars($cust); ?></td>
                                            <td><?php echo htmlspecialchars($inv['store_name'] ?? ''); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($inv['invoice_date'])); ?></td>
                                            <td>
                                                <div class="items-list">
                                                    <?php foreach ($inv['items'] as $it): ?>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($it['product_name']); ?></strong><br>
                                                            Qty: <?php echo $it['quantity']; ?> × ₱<?php echo number_format($it['unit_price'], 2); ?>
                                                            = ₱<?php echo number_format($it['quantity'] * $it['unit_price'], 2); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </td>
                                            <td>₱<?php echo number_format($inv['total_amount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($inv['payment_method'] ?? ($inv['PaymentMethod'] ?? '')); ?></td>
                                            <td>
                                            <div class="action-buttons">
                                                    <button class="btn btn-sm btn-info" onclick="printInvoice(<?php echo $inv['sale_id']; ?>)">Print</button>
                                                    <button class="btn btn-sm btn-secondary" onclick="downloadInvoice(<?php echo $inv['id']; ?>)">Download</button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
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
                        <button class="btn btn-primary btn-sm" onclick="openReturnModal()">Process Return</button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Return ID</th>
                                        <th>Sale ID</th>
                                        <th>Customer</th>
                                        <th>Date</th>
                                        <th>Reason</th>
                                        <th>Refund</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($returns)): ?>
                                        <tr><td colspan="6" style="text-align:center;">No returns</td></tr>
                                    <?php else: foreach ($returns as $r): ?>
                                        <tr>
                                            <td>#R<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>#S<?php echo str_pad($r['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($r['cust_first'] ?? '') . ' ' . ($r['cust_last'] ?? ''))); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($r['return_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($r['reason']); ?></td>
                                            <td>₱<?php echo number_format($r['refund_amount'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REPORTS TAB -->
            <div id="reportsTab" class="tab-pane" style="display:none;">
                <div class="card">
                    <div class="card-header">
                        <h3>Sales Reports</h3>
                        <div>
                            <select id="reportType" style="padding: 0.5rem; margin-right: 0.5rem;">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <button class="btn btn-primary btn-sm" onclick="generateReport()">Generate</button>
                        </div>
                    </div>
                    <div class="card-body">
                        <p><em>Select a report type and click "Generate" to view real data.</em></p>
                        <div id="reportOutput"></div>
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
            </div>

            <!-- MODALS -->
            <!-- View Details Modal -->
            <div id="detailModal" class="modal" aria-hidden="true">
                <div class="modal-content" style="max-width:800px; width:95%;">
                    <div class="modal-header">
                        <h2 id="detailModalTitle">Details</h2>
                        <span class="close" onclick="closeModal('detailModal')">×</span>
                    </div>
                    <div id="detailModalBody" class="modal-body" style="max-height:65vh; overflow:auto; padding:1rem;"></div>
                    <div class="modal-actions" style="padding:1rem; display:flex; gap:.5rem; justify-content:flex-end;">
                        <button class="btn btn-secondary" onclick="closeModal('detailModal')">Close</button>
                    </div>
                </div>
            </div>

            <!-- New Sale Modal -->
            <div id="newSaleModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>New Sale</h2>
                        <span class="close" onclick="closeModal('newSaleModal')">×</span>
                    </div>
                    <form id="newSaleForm" method="post" onsubmit="prepareSaleSubmit(event)">
                        <input type="hidden" name="action" value="process_sale">
                        <input type="hidden" id="sale_items_json" name="sale_items_json" value="[]">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Customer</label>
                                <select name="customerId" id="customerId" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?php echo $c['id']; ?>">
                                            <?php echo htmlspecialchars($c['name']); ?> (<?php echo htmlspecialchars($c['member_code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Store</label>
                                <select name="storeId" id="storeId" required>
                                    <option value="">Select Store</option>
                                    <?php foreach ($stores as $s): ?>
                                        <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="paymentMethod" id="paymentMethod">
                                    <option value="Cash">Cash</option>
                                    <option value="Card">Card</option>
                                    <option value="Gcash">GCash</option>
                                    <option value="Paymaya">PayMaya</option>
                                    <option value="Credit">Credit</option>
                                </select>
                            </div>
                        </div>

                        <h3>Products</h3>
                        <div id="productItemsContainer">
                            <div class="product-item">
                                <div class="form-group">
                                    <select class="product-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                                <?php echo htmlspecialchars($p['name']); ?> (₱<?php echo number_format($p['price'], 2); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input type="number" class="quantity-input" placeholder="Qty" min="1" value="1" required>
                                </div>
                                <div class="form-group">
                                    <input type="number" class="price-input" placeholder="Unit Price" step="0.01" required>
                                </div>
                                <div class="form-group">
                                    <input type="number" class="subtotal-input" placeholder="Subtotal" step="0.01" readonly>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-item').remove(); calculateSaleTotal();">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addProductItem()">+ Add Product</button>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Discount (₱)</label>
                                <input type="number" id="discountAmount" name="discountAmount" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label>Points Used (₱)</label>
                                <input type="number" id="pointsUsed" name="pointsUsed" step="0.01" value="0">
                            </div>
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="saleNotes" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="totals">
                            <p>Subtotal: <span id="saleSubtotal">₱0.00</span></p>
                            <p>Tax (10%): <span id="saleTax">₱0.00</span></p>
                            <p>Discount: <span id="saleDiscount">₱0.00</span></p>
                            <h3>Total: <span id="saleTotal">₱0.00</span></h3>
                        </div>

                        <div class="modal-actions">
                            <button type="submit" class="btn btn-primary">Process Sale</button>
                            <button type="button" class="btn btn-secondary" onclick="closeModal('newSaleModal')">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Process Return Modal -->
            <div id="returnModal" class="modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>Process Return</h2>
                        <span class="close" onclick="closeModal('returnModal')">×</span>
                    </div>
                    <form id="returnForm" method="post" onsubmit="prepareReturnSubmit(event)">
                        <input type="hidden" name="action" value="process_return">
                        <input type="hidden" id="return_items_json" name="return_items_json" value="[]">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Original Sale ID</label>
                                <input type="number" name="returnSaleId" required placeholder="e.g., 1023">
                            </div>
                            <div class="form-group">
                                <label>Return Date</label>
                                <input type="date" name="returnDate" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Reason</label>
                                <input type="text" name="returnReason" required>
                            </div>
                            <div class="form-group">
                                <label>Refund Method</label>
                                <select name="refundMethod">
                                    <option value="Cash">Cash</option>
                                    <option value="GCash">GCash</option>
                                    <option value="Store Credit">Store Credit</option>
                                </select>
                            </div>
                        </div>

                        <h3>Returned Items</h3>
                        <div id="returnItemsContainer">
                            <div class="product-item">
                                <div class="form-group">
                                    <select class="return-product-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                                <?php echo htmlspecialchars($p['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <input type="number" class="return-quantity" placeholder="Qty" min="1" value="1" required>
                                </div>
                                <div class="form-group">
                                    <input type="text" class="return-reason-detail" placeholder="Reason detail">
                                </div>
                                <div class="form-group">
                                    <input type="number" class="return-amount" placeholder="Refund Amount" step="0.01" readonly>
                                </div>
                                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-item').remove(); calculateReturnTotal();">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary" onclick="addReturnItem()">+ Add Item</button>

                        <div class="totals">
                            <p>Total Refund: <span id="totalRefund">₱0.00</span></p>
                            <p>Restocking Fee (5%): <span id="restockingFee">₱0.00</span></p>
                            <h3>Net Refund: <span id="netRefund">₱0.00</span></h3>
                        </div>

                        <div class="form-group">
                            <label>Notes</label>
                            <textarea name="returnNotes" rows="2"></textarea>
                        </div>

                        <div class="modal-actions">
                            <button type="submit" class="btn btn-primary">Process Return</button>
                            <button type="button" class="btn btn-secondary" onclick="closeModal('returnModal')">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Tab navigation
        document.querySelectorAll('.nav-link').forEach(tab => {
            tab.addEventListener('click', e => {
                e.preventDefault();
                document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
                document.querySelectorAll('.nav-link').forEach(t => t.classList.remove('active'));
                const id = tab.dataset.tab;
                document.getElementById(id).style.display = 'block';
                tab.classList.add('active');
            });
        });

        // Modal open/close (with body scroll lock)
        function openNewSaleModal() {
            document.getElementById('newSaleModal').classList.add('active');
            document.body.classList.add('modal-open');
        }
        function openReturnModal() {
            document.getElementById('returnModal').classList.add('active');
            document.body.classList.add('modal-open');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
            // If no more active modals, restore scroll
            if (!document.querySelector('.modal.active')) {
                document.body.classList.remove('modal-open');
            }
        }

        // Close when clicking backdrop (outside content)
        document.addEventListener('click', (e) => {
            const m = e.target.closest('.modal');
            if (m && e.target === m) { closeModal(m.id); }
        });

        // Add product item
        function addProductItem() {
            const container = document.getElementById('productItemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'product-item';
            newItem.innerHTML = `
                <div class="form-group">
                    <select class="product-select" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?> (₱<?php echo number_format($p['price'], 2); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" class="quantity-input" placeholder="Qty" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <input type="number" class="price-input" placeholder="Unit Price" step="0.01" required>
                </div>
                <div class="form-group">
                    <input type="number" class="subtotal-input" placeholder="Subtotal" step="0.01" readonly>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-item').remove(); calculateSaleTotal();">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newItem);
            attachProductItemListeners(newItem);
        }

        function attachProductItemListeners(item) {
            const select = item.querySelector('.product-select');
            const quantityInput = item.querySelector('.quantity-input');
            const priceInput = item.querySelector('.price-input');
            const subtotalInput = item.querySelector('.subtotal-input');

            select?.addEventListener('change', () => {
                const price = parseFloat(select.selectedOptions[0]?.dataset?.price || 0);
                priceInput.value = price.toFixed(2);
                updateSubtotal();
            });

            function updateSubtotal() {
                const qty = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const subtotal = qty * price;
                subtotalInput.value = subtotal.toFixed(2);
                calculateSaleTotal();
            }

            quantityInput?.addEventListener('input', updateSubtotal);
            priceInput?.addEventListener('input', updateSubtotal);
        }

        document.querySelectorAll('#productItemsContainer .product-item').forEach(attachProductItemListeners);

        // Add return item
        function addReturnItem() {
            const container = document.getElementById('returnItemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'product-item';
            newItem.innerHTML = `
                <div class="form-group">
                    <select class="return-product-select" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?php echo $p['id']; ?>" data-price="<?php echo $p['price']; ?>">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <input type="number" class="return-quantity" placeholder="Qty" min="1" value="1" required>
                </div>
                <div class="form-group">
                    <input type="text" class="return-reason-detail" placeholder="Reason detail">
                </div>
                <div class="form-group">
                    <input type="number" class="return-amount" placeholder="Refund Amount" step="0.01" readonly>
                </div>
                <button type="button" class="btn btn-danger btn-sm" onclick="this.closest('.product-item').remove(); calculateReturnTotal();">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(newItem);
            attachReturnItemListeners(newItem);
        }

        function attachReturnItemListeners(item) {
            const select = item.querySelector('.return-product-select');
            const quantityInput = item.querySelector('.return-quantity');
            const amountInput = item.querySelector('.return-amount');

            select?.addEventListener('change', updateReturnAmount);
            quantityInput?.addEventListener('input', updateReturnAmount);

            function updateReturnAmount() {
                const qty = parseFloat(quantityInput.value) || 0;
                const price = parseFloat(select.selectedOptions[0]?.dataset?.price || 0);
                const amount = qty * price;
                amountInput.value = amount.toFixed(2);
                calculateReturnTotal();
            }
        }

        document.querySelectorAll('#returnItemsContainer .product-item').forEach(attachReturnItemListeners);

        // Calculate totals
        function calculateSaleTotal() {
            let subtotal = 0;
            document.querySelectorAll('#productItemsContainer .product-item').forEach(item => {
                const st = parseFloat(item.querySelector('.subtotal-input').value) || 0;
                subtotal += st;
            });
            const discount = parseFloat(document.getElementById('discountAmount').value) || 0;
            const points = parseFloat(document.getElementById('pointsUsed').value) || 0;
            const taxable = Math.max(0, subtotal - discount - points);
            const tax = taxable * 0.10;
            const total = taxable + tax;

            document.getElementById('saleSubtotal').textContent = '₱' + subtotal.toFixed(2);
            document.getElementById('saleTax').textContent = '₱' + tax.toFixed(2);
            document.getElementById('saleDiscount').textContent = '₱' + (discount + points).toFixed(2);
            document.getElementById('saleTotal').textContent = '₱' + total.toFixed(2);
        }

        function calculateReturnTotal() {
            let totalRefund = 0;
            document.querySelectorAll('#returnItemsContainer .product-item').forEach(item => {
                const amount = parseFloat(item.querySelector('.return-amount').value) || 0;
                totalRefund += amount;
            });
            const restockingFee = totalRefund * 0.05;
            const netRefund = totalRefund - restockingFee;

            document.getElementById('totalRefund').textContent = '₱' + totalRefund.toFixed(2);
            document.getElementById('restockingFee').textContent = '₱' + restockingFee.toFixed(2);
            document.getElementById('netRefund').textContent = '₱' + netRefund.toFixed(2);
        }

        // Prepare submit
        function prepareSaleSubmit(e) {
            e.preventDefault();
            const items = [];
            document.querySelectorAll('#productItemsContainer .product-item').forEach(item => {
                const select = item.querySelector('.product-select');
                const qty = item.querySelector('.quantity-input').value;
                const price = item.querySelector('.price-input').value;
                if (select.value && qty > 0) {
                    items.push({
                        product_id: select.value,
                        quantity: qty,
                        price: price
                    });
                }
            });
            document.getElementById('sale_items_json').value = JSON.stringify(items);
            e.target.submit();
        }

        function prepareReturnSubmit(e) {
            e.preventDefault();
            const items = [];
            document.querySelectorAll('#returnItemsContainer .product-item').forEach(item => {
                const select = item.querySelector('.return-product-select');
                const qty = item.querySelector('.return-quantity').value;
                const amount = item.querySelector('.return-amount').value;
                const reason = item.querySelector('.return-reason-detail').value;
                if (select.value && qty > 0) {
                    items.push({
                        product_id: select.value,
                        quantity: qty,
                        refund_amount: amount,
                        reason_detail: reason
                    });
                }
            });
            document.getElementById('return_items_json').value = JSON.stringify(items);
            e.target.submit();
        }

        // Filtering
        function filterOrders(){
          const df = document.getElementById('orderDateFrom').value;
          const dt = document.getElementById('orderDateTo').value;
          const storeId = document.getElementById('orderStore').value;
          const status = document.getElementById('orderPaymentStatus').value;
          const rows = document.querySelectorAll('#ordersTable tbody tr');
          rows.forEach(tr => {
            const d = tr.getAttribute('data-date') || '';
            const s = tr.getAttribute('data-storeid') || '';
            const st = tr.getAttribute('data-status') || '';
            let ok = true;
            if (df && d < df) ok = false;
            if (ok && dt && d > dt) ok = false;
            if (ok && storeId && s !== storeId) ok = false;
            if (ok && status && st !== status) ok = false;
            tr.style.display = ok ? '' : 'none';
          });
        }
        
        // Export helpers (client-side)
        const ORDERS_DATA = <?php echo json_encode($orders); ?>;
        const INVOICES_DATA = <?php echo json_encode($invoices); ?>;
        function toCSV(rows){ if(!rows||!rows.length) return ''; const headers = Object.keys(rows[0]); const esc=v=>`"${String(v??'').replace(/"/g,'""')}"`; const lines=[headers.join(',')]; rows.forEach(r=>lines.push(headers.map(h=>esc(r[h])).join(','))); return lines.join('\n'); }
        function downloadCSV(name, csv){ const blob = new Blob([csv], {type:'text/csv;charset=utf-8;'}); const url = URL.createObjectURL(blob); const a=document.createElement('a'); a.href=url; a.download=name; document.body.appendChild(a); a.click(); URL.revokeObjectURL(url); a.remove(); }
        function exportOrders(){ const csv = toCSV(ORDERS_DATA); downloadCSV('orders.csv', csv); }
        function exportInvoices(){ const csv = toCSV(INVOICES_DATA); downloadCSV('invoices.csv', csv); }
        
        // Analytics charts
        async function loadAnalytics(){
          try{
            const res = await fetch('sales_report_data.php');
            const js = await res.json();
            // Trend
            const labels = (js.sales_trend||[]).map(r=>r.date).reverse();
            const totals = (js.sales_trend||[]).map(r=>parseFloat(r.total||0)).reverse();
            const ctx1 = document.getElementById('salesTrendChart');
            if(ctx1 && window.Chart){ new Chart(ctx1, { type:'line', data:{ labels, datasets:[{label:'Daily Sales', data: totals, borderColor:'#714B67', backgroundColor:'rgba(113,75,103,0.2)', tension:.25 }]}, options:{ plugins:{legend:{display:true}}, scales:{y:{beginAtZero:true}} } }); }
            // Payment methods
            const pm = js.payment_methods || [];
            const pmLabels = pm.map(x=>x.method||'Unknown');
            const pmTotals = pm.map(x=>parseFloat(x.total||0));
            const ctx2 = document.getElementById('paymentMethodsChart');
            if(ctx2 && window.Chart){ new Chart(ctx2, { type:'doughnut', data:{ labels: pmLabels, datasets:[{ data: pmTotals, backgroundColor:['#714B67','#3B82F6','#10B981','#F59E0B','#EF4444','#6B7280'] }]}, options:{ plugins:{legend:{position:'bottom'}} } }); }
          }catch(e){ console.warn('Analytics load failed', e); }
        }
        loadAnalytics();
        
async function generateReport(){
          const type = document.getElementById('reportType').value || 'daily';
          const out = document.getElementById('reportOutput');
          out.innerHTML = '<em>Loading...</em>';
          try{
            const res = await fetch('sales_report_data.php');
            const js = await res.json();
            let rows = js.daily_summary || [];
            // Aggregate for weekly/monthly/yearly
            if(type!=='daily'){
              const bucket = {};
              rows.forEach(r=>{
                const d = r.date||'';
                let key = d;
                if(type==='weekly'){
                  key = d.slice(0,4) + '-W' + Math.ceil((new Date(d).getDate())/7);
                } else if(type==='monthly'){
                  key = d.slice(0,7);
                } else if(type==='yearly'){
                  key = d.slice(0,4);
                }
                if(!bucket[key]) bucket[key] = {date:key, orders:0, items_sold:0, revenue:0, tax:0, discounts:0};
                bucket[key].orders += parseInt(r.orders||0);
                bucket[key].items_sold += parseFloat(r.items_sold||0);
                bucket[key].revenue += parseFloat(r.revenue||0);
                bucket[key].tax += parseFloat(r.tax||0);
                bucket[key].discounts += parseFloat(r.discounts||0);
              });
              rows = Object.values(bucket).sort((a,b)=>a.date.localeCompare(b.date));
            }
            const html = ['<table class="table"><thead><tr><th>Period</th><th>Orders</th><th>Items Sold</th><th>Revenue</th><th>Tax</th><th>Discounts</th><th>Avg Order</th></tr></thead><tbody>']
              .concat(rows.map(r=>{
                const avg = (r.orders? (r.revenue/r.orders):0);
                return `<tr><td>${r.date}</td><td>${r.orders||0}</td><td>${r.items_sold||0}</td><td>₱${Number(r.revenue||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td><td>₱${Number(r.tax||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td><td>₱${Number(r.discounts||0).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td><td>₱${Number(avg).toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}</td></tr>`;
              }))
              .concat(['</tbody></table>']).join('');
            out.innerHTML = html;
          }catch(e){ out.innerHTML = '<span style=\"color:#a10d0d\">Failed to generate report.</span>'; }
        }
        function filterInvoices(){
          const q = (document.getElementById('invoiceNumber').value || '').toLowerCase();
          const df = document.getElementById('invoiceDateFrom').value;
          const dt = document.getElementById('invoiceDateTo').value;
          const status = document.getElementById('invoiceStatus').value;
          const rows = document.querySelectorAll('#invoicesTable tbody tr');
          rows.forEach(tr => {
            const num = (tr.getAttribute('data-invoice-number') || '').toLowerCase();
            const d = tr.getAttribute('data-date') || '';
            const st = tr.getAttribute('data-status') || '';
            let ok = true;
            if (q && num.indexOf(q) === -1) ok = false;
            if (ok && df && d < df) ok = false;
            if (ok && dt && d > dt) ok = false;
            if (ok && status && st !== status) ok = false;
            tr.style.display = ok ? '' : 'none';
          });
        }
        async function viewOrder(id) {
          try{
            const r = await fetch('../../api/sales.php?action=get_sale_details&sale_id='+id);
            const js = await r.json();
            if(!js.success) throw new Error(js.message||'Failed');
            const s = js.data.sale||{}; const items = js.data.details||[];
            const html = ['<div style=\'margin-bottom:.5rem\'><strong>Sale #S'+String(id).padStart(4,'0')+'</strong></div>',
                          '<div>Date: '+(s.SaleDate||'')+'</div>',
                          '<div>Payment: '+(s.PaymentMethod||'')+' ('+(s.PaymentStatus||'')+')</div>',
                          '<div>Total: ₱'+Number(s.TotalAmount||0).toLocaleString(undefined,{minimumFractionDigits:2})+'</div>',
                          '<hr>',
                          '<table class="table"><thead><tr><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead><tbody>']
                          .concat(items.map(it=>`<tr><td>${it.ProductID}</td><td>${it.Quantity}</td><td>₱${Number(it.UnitPrice||0).toFixed(2)}</td><td>₱${Number(it.Subtotal||0).toFixed(2)}</td></tr>`))
                          .concat(['</tbody></table>']).join('');
            document.getElementById('detailModalTitle').textContent = 'Sale Details';
            document.getElementById('detailModalBody').innerHTML = html;
            document.getElementById('detailModal').classList.add('active');
          }catch(e){ alert('Failed to load sale: '+e.message); }
        }
        function viewInvoice(id) { document.getElementById('detailModalTitle').textContent='Invoice Details'; document.getElementById('detailModalBody').innerHTML = '<em>Open receipt from Orders to print.</em>'; document.getElementById('detailModal').classList.add('active'); }
        function viewReturn(id) { document.getElementById('detailModalTitle').textContent='Return Details'; document.getElementById('detailModalBody').innerHTML = '<em>Return view coming soon.</em>'; document.getElementById('detailModal').classList.add('active'); }
        function printInvoice(id) { window.open('receipt.php?sale_id=' + id, '_blank'); }
        function downloadInvoice(id) { window.open('receipt.php?sale_id=' + id, '_blank'); }

        // Initialize
        calculateSaleTotal();
        calculateReturnTotal();
    </script>
</body>
</html>
