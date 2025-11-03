<?php
session_start();

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    header('Location: /ShoeRetailErp/login.php');
    exit;
}

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
        $discount = floatval($_POST['discountAmount'] ?? 0);
        $points_used = floatval($_POST['pointsUsed'] ?? 0);
        $notes = $_POST['saleNotes'] ?? '';
        $user_id = $_SESSION['user_id'];
        $sale_items_json = $_POST['sale_items_json'] ?? '[]';

        $sale_items = json_decode($sale_items_json, true);
        if (!is_array($sale_items) || count($sale_items) === 0) {
            add_alert('danger', 'Sale must contain at least one product.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $subtotal = 0.0;
        foreach ($sale_items as $it) {
            $qty = floatval($it['quantity'] ?? 0);
            $price = floatval($it['price'] ?? 0);
            $subtotal += $qty * $price;
        }
        $total_discount = $discount + $points_used;
        $taxable = max(0.0, $subtotal - $total_discount);
        $tax = $taxable * 0.10;
        $total = $taxable + $tax;

        $db = getDB();
        $db->beginTransaction();

        try {
            $sale_id = $db->insert("
                INSERT INTO sales
                    (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, SaleDate)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ", [$customer_id, $store_id, $total, $tax, $discount, $payment_method]);

            foreach ($sale_items as $it) {
                $product_id = intval($it['product_id']);
                $quantity = floatval($it['quantity']);
                $unit_price = floatval($it['price']);
                $line_subtotal = $quantity * $unit_price;

                $db->execute("INSERT INTO saledetails (SaleID, ProductID, Quantity, SalesUnitID, QuantityBase, UnitPrice, Subtotal) VALUES (?, ?, ?, 1, ?, ?, ?)",
                    [$sale_id, $product_id, $quantity, $quantity, $unit_price, $line_subtotal]);
            }

        
// Create invoice if needed
    if ($sale_id) {
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
        try {
            $db->execute(
                "INSERT INTO invoices 
                    (SaleID, InvoiceNumber, InvoiceDate, TotalAmount, CreatedAt) 
                 VALUES 
                    (?, ?, NOW(), ?, NOW())",
                [$sale_id, $invoiceNumber, $total]
            );
        } catch (Exception $invoiceEx) {
            // Invoice table may not exist, continue with sale
            logError("Invoice creation failed", ['error' => $invoiceEx->getMessage()]);
        }
    }


 $db->commit();
    add_alert('success', 'Sale processed successfully. Sale ID: #' . $sale_id);
} catch (Exception $ex) {
    $db->rollback();
    add_alert('danger', 'Failed to process sale: ' . $ex->getMessage());
}

header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
exit;

    } elseif ($action === 'process_return') {
        $original_sale_id = intval($_POST['returnSaleId']);
        $return_date = $_POST['returnDate'] ?? date('Y-m-d');
        $reason = $_POST['returnReason'] ?? '';
        $refund_method = $_POST['refundMethod'] ?? 'Cash';
        $return_items_json = $_POST['return_items_json'] ?? '[]';
        $return_items = json_decode($return_items_json, true);
        $notes = $_POST['returnNotes'] ?? '';
        $user_id = $_SESSION['user_id'];

        if (!is_array($return_items) || count($return_items) === 0) {
            add_alert('danger', 'Return must contain items.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $db = getDB();
        $db->beginTransaction();
        try {
            $return_id = $db->insert("
                INSERT INTO returns (SaleID, ReturnDate, Reason, RefundMethod, notes, CreatedAt)
                VALUES (?, ?, ?, ?, ?, NOW())
            ", [$original_sale_id, $return_date, $reason, $refund_method, $notes]);

            foreach ($return_items as $it) {
                $product_id = intval($it['product_id']);
                $quantity = floatval($it['quantity']);
                $refund_amount = floatval($it['refund_amount'] ?? 0);
                $reason_detail = $it['reason_detail'] ?? '';

                $db->execute("INSERT INTO return_items (ReturnID, ProductID, Quantity, RefundAmount, ReasonDetail) VALUES (?, ?, ?, ?, ?)",
                    [$return_id, $product_id, $quantity, $refund_amount, $reason_detail]);
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
    // Check if sales table exists and has correct columns
    $row = dbFetchOne("SELECT IFNULL(SUM(TotalAmount),0) AS total FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['today_sales'] = $row['total'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(*) AS cnt FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['orders_today'] = $row['cnt'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(DISTINCT CustomerID) AS cnt FROM sales WHERE DATE(SaleDate) = ?", [$today]);
    $stats['unique_customers_today'] = $row['cnt'] ?? 0;
} catch (Exception $e) {
    logError("Stats failed", ['error' => $e->getMessage()]);
}

// --- Fetch Data ---
$customers = safe_query_assoc("SELECT CustomerID AS id, CONCAT(FirstName, ' ', LastName) AS name, MemberNumber AS member_code FROM customers ORDER BY FirstName, LastName");
$stores = safe_query_assoc("SELECT StoreID AS id, StoreName AS name FROM stores ORDER BY StoreName");
$products = safe_query_assoc("SELECT ProductID AS id, SKU AS sku, Model AS name, SellingPrice AS price FROM products ORDER BY Model");

// --- Orders with Items (if sales table exists) ---
$orders = [];
try {
    $orders = safe_query_assoc("
        SELECT s.SaleID AS sale_id, s.TotalAmount, s.TaxAmount AS tax, s.DiscountAmount AS discount, s.PaymentMethod AS payment_method, s.SaleDate AS created_at,
               c.FirstName AS cust_first, c.LastName AS cust_last, st.StoreName AS store_name
        FROM sales s
        LEFT JOIN customers c ON s.CustomerID = c.CustomerID
        LEFT JOIN stores st ON s.StoreID = st.StoreID
        ORDER BY s.SaleDate DESC
        LIMIT 50
    ");
} catch (Exception $e) {
    logError("Orders query failed", ['error' => $e->getMessage()]);
}

foreach ($orders as &$o) {
    try {
        $o['items'] = safe_query_assoc("
            SELECT sd.Quantity AS quantity, sd.UnitPrice AS unit_price, p.Model AS product_name
            FROM saledetails sd
            JOIN products p ON sd.ProductID = p.ProductID
            WHERE sd.SaleID = ?
        ", [$o['sale_id']]);
    } catch (Exception $e) {
        $o['items'] = [];
    }
}
unset($o);

// --- Invoices with Items (if invoices table exists) ---
$invoices = [];
try {
    $invoices = safe_query_assoc("
        SELECT i.InvoiceID AS id, i.InvoiceNumber AS invoice_number, i.SaleID AS sale_id, i.TotalAmount AS total_amount, i.InvoiceDate AS invoice_date,
               s.PaymentMethod AS payment_method, c.FirstName AS cust_first, c.LastName AS cust_last, st.StoreName AS store_name
        FROM invoices i
        JOIN sales s ON i.SaleID = s.SaleID
        LEFT JOIN customers c ON s.CustomerID = c.CustomerID
        LEFT JOIN stores st ON s.StoreID = st.StoreID
        ORDER BY i.InvoiceDate DESC
        LIMIT 50
    ");
} catch (Exception $e) {
    logError("Invoices query failed", ['error' => $e->getMessage()]);
}

foreach ($invoices as &$inv) {
    try {
        $inv['items'] = safe_query_assoc("
            SELECT ii.Quantity AS quantity, ii.UnitPrice AS unit_price, p.Model AS product_name
            FROM invoiceitems ii
            JOIN products p ON ii.ProductID = p.ProductID
            WHERE ii.InvoiceID = ?
        ", [$inv['id']]);
    } catch (Exception $e) {
        $inv['items'] = [];
    }
}
unset($inv);

// --- Returns (if returns table exists) ---
$returns = [];
try {
    $returns = safe_query_assoc("
        SELECT r.ReturnID AS id, r.SaleID AS sale_id, r.ReturnDate AS return_date, r.Reason AS reason,
               c.FirstName AS cust_first, c.LastName AS cust_last
        FROM returns r
        JOIN sales s ON r.SaleID = s.SaleID
        LEFT JOIN customers c ON s.CustomerID = c.CustomerID
        ORDER BY r.ReturnDate DESC
        LIMIT 50
    ");
} catch (Exception $e) {
    logError("Returns query failed", ['error' => $e->getMessage()]);
}
// --- Navbar Active Link Logic --- 
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Map directories to nav items
$nav_map = [
    'index' => 'index.php' === $current_page && dirname($_SERVER['PHP_SELF']) === dirname(__DIR__),
    'inventory' => $current_dir === 'inventory',
    'sales' => $current_dir === 'sales',
    'procurement' => $current_dir === 'procurement',
    'accounting' => $current_dir === 'accounting',
    'customers' => $current_dir === 'customers',
    'hr' => $current_dir === 'hr',
];
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
        .modal { display: none; align-items: center; justify-content: center; position: fixed; inset:0; background: rgba(0,0,0,0.4); z-index: 9999; }
        .modal.active { display: flex; }
        .alert { padding: .75rem 1rem; border-radius: 6px; margin: 1rem; }
        .alert-success { background: #e6ffed; color: #1a7f37; border: 1px solid #b3f0c7; }
        .alert-danger { background: #ffe6e6; color: #a10d0d; border: 1px solid #ffb3b3; }
        .items-list { font-size: 0.85rem; line-height: 1.5; }
        .items-list div { padding: 2px 0; border-bottom: 1px dashed #eee; }
        .items-list div:last-child { border-bottom: none; }
    </style>
</head>
<body>
    <div class="alert-container"></div>
    <?php include '../includes/navbar.php'; ?>

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
                <div class="page-header-actions">
                     <a href="pos.php" style="text-decoration: none;">
                    <button class="btn btn-primary">Point of Sale</button>
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
                                            $status = $o['PaymentStatus'] ?? 'Paid';
                                        ?>
                                            <tr>
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
                                                <td>₱<?php echo number_format($o['TotalAmount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($o['payment_method']); ?></td>
                                                <td><span class="badge <?php echo ($status === 'Paid' ? 'badge-success' : 'badge-warning'); ?>"><?php echo $status; ?></span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-primary" onclick="viewOrder(<?php echo $o['sale_id']; ?>)">View</button>
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
                                        <tr>
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
                                            <td>₱<?php echo number_format($inv['TotalAmount'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($inv['payment_method']); ?></td>
                                            <td>
                                                <div class="action-buttons">
                                                    <button class="btn btn-sm btn-primary" onclick="viewInvoice(<?php echo $inv['id']; ?>)">View</button>
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
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($returns)): ?>
                                        <tr><td colspan="7" style="text-align:center;">No returns</td></tr>
                                    <?php else: foreach ($returns as $r): ?>
                                        <tr>
                                            <td>#R<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>#S<?php echo str_pad($r['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?php echo htmlspecialchars(trim(($r['cust_first'] ?? '') . ' ' . ($r['cust_last'] ?? ''))); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($r['return_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($r['reason']); ?></td>
                                            <td>₱<?php echo number_format($r['refund_amount'], 2); ?></td>
                                            <td><button class="btn btn-sm btn-primary" onclick="viewReturn(<?php echo $r['id']; ?>)">View</button></td>
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

        // Modal open/close
        function openNewSaleModal() { document.getElementById('newSaleModal').classList.add('active'); }
        function openReturnModal() { document.getElementById('returnModal').classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }

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

        // Placeholder actions
        function filterOrders() { console.log('Filtering orders...'); }
        function filterInvoices() { console.log('Filtering invoices...'); }
        function exportOrders() { console.log('Exporting orders...'); }
        function exportInvoices() { console.log('Exporting invoices...'); }
        function viewOrder(id) { alert('View Sale #' + id); }
        function viewInvoice(id) { alert('View Invoice #' + id); }
        function viewReturn(id) { alert('View Return #' + id); }
        function printInvoice(id) { alert('Print Invoice for Sale #' + id); }
        function downloadInvoice(id) { alert('Download Invoice #' + id); }
        function generateReport() {
            const type = document.getElementById('reportType').value;
            document.getElementById('reportOutput').innerHTML = `<p><em>Loading ${type} report...</em></p>`;
        }

        // Initialize
        calculateSaleTotal();
        calculateReturnTotal();
    </script>
</body>
</html>