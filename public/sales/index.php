<?php
// session_start();
// if (!isset($_SESSION['user_id'])) {
//     header('Location: /ShoeRetailErp/login.php');
//     exit;
// }

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
        $user_id = $_SESSION['user_id'] ?? 1; // fallback for testing
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
            $sale_id = $db->insert(
                "INSERT INTO sales (customer_id, store_id, subtotal, tax, discount, points_used, total_amount, payment_method, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())",
                [$customer_id, $store_id, $subtotal, $tax, $discount, $points_used, $total, $payment_method, $notes, $user_id]
            );

            foreach ($sale_items as $it) {
                $product_id = intval($it['product_id']);
                $quantity = floatval($it['quantity']);
                $unit_price = floatval($it['price']);
                $line_subtotal = $quantity * $unit_price;

                $db->execute(
                    "INSERT INTO sales_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)",
                    [$sale_id, $product_id, $quantity, $unit_price, $line_subtotal]
                );

                $db->execute("UPDATE products SET stock = stock - ? WHERE id = ?", [$quantity, $product_id]);
            }

            $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($sale_id, 4, '0', STR_PAD_LEFT);
            $db->execute(
                "INSERT INTO invoices (sale_id, invoice_number, invoice_date, total_amount, created_at)
                 VALUES (?, ?, NOW(), ?, NOW())",
                [$sale_id, $invoiceNumber, $total]
            );

            $db->commit();
            add_alert('success', 'Sale processed successfully. Sale ID: #' . $sale_id);
        } catch (Exception $ex) {
            $db->rollback();
            add_alert('danger', 'Failed to process sale: ' . $ex->getMessage());
        }

        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }

    elseif ($action === 'process_return') {
        $original_sale_id = intval($_POST['returnSaleId']);
        $return_date = $_POST['returnDate'] ?? date('Y-m-d');
        $reason = $_POST['returnReason'] ?? '';
        $refund_method = $_POST['refundMethod'] ?? 'Cash';
        $return_items_json = $_POST['return_items_json'] ?? '[]';
        $return_items = json_decode($return_items_json, true);
        $notes = $_POST['returnNotes'] ?? '';
        $user_id = $_SESSION['user_id'] ?? 1;

        if (!is_array($return_items) || count($return_items) === 0) {
            add_alert('danger', 'Return must contain items.');
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        }

        $db = getDB();
        $db->beginTransaction();

        try {
            $return_id = $db->insert(
                "INSERT INTO returns (sale_id, return_date, reason, refund_method, notes, processed_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, NOW())",
                [$original_sale_id, $return_date, $reason, $refund_method, $notes, $user_id]
            );

            foreach ($return_items as $it) {
                $product_id = intval($it['product_id']);
                $quantity = floatval($it['quantity']);
                $refund_amount = floatval($it['refund_amount'] ?? 0);
                $reason_detail = $it['reason_detail'] ?? '';

                $db->execute(
                    "INSERT INTO return_items (return_id, product_id, quantity, refund_amount, reason_detail)
                     VALUES (?, ?, ?, ?, ?)",
                    [$return_id, $product_id, $quantity, $refund_amount, $reason_detail]
                );

                $db->execute("UPDATE products SET stock = stock + ? WHERE id = ?", [$quantity, $product_id]);
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
        error_log("Query failed: " . $e->getMessage());
        return [];
    }
}

// --- Stats ---
$today = date('Y-m-d');
$stats = ['today_sales' => 0, 'orders_today' => 0, 'unique_customers_today' => 0, 'avg_rating' => 0];

try {
    $row = dbFetchOne("SELECT IFNULL(SUM(total_amount),0) AS total FROM sales WHERE DATE(created_at) = ?", [$today]);
    $stats['today_sales'] = $row['total'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(*) AS cnt FROM sales WHERE DATE(created_at) = ?", [$today]);
    $stats['orders_today'] = $row['cnt'] ?? 0;

    $row = dbFetchOne("SELECT COUNT(DISTINCT customer_id) AS cnt FROM sales WHERE DATE(created_at) = ?", [$today]);
    $stats['unique_customers_today'] = $row['cnt'] ?? 0;

    $row = dbFetchOne("SELECT AVG(rating) AS avg_rating FROM product_reviews");
    $stats['avg_rating'] = $row['avg_rating'] ? round((float)$row['avg_rating'], 2) : 0;
} catch (Exception $e) {
    error_log("Stats failed: " . $e->getMessage());
}

// --- Fetch Data ---
$customers = safe_query_assoc("SELECT id, CONCAT(first_name, ' ', last_name) AS name, member_code FROM customers ORDER BY name");
$stores = safe_query_assoc("SELECT id, name FROM stores ORDER BY name");
$products = safe_query_assoc("SELECT id, sku, name, price, stock FROM products ORDER BY name");

// --- Orders with Items ---
$orders = safe_query_assoc("
    SELECT s.id AS sale_id, s.total_amount, s.tax, s.discount, s.payment_method, s.created_at, s.status,
           c.first_name AS cust_first, c.last_name AS cust_last, st.name AS store_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN stores st ON s.store_id = st.id
    ORDER BY s.created_at DESC LIMIT 50
");

foreach ($orders as &$o) {
    $o['items'] = safe_query_assoc("
        SELECT si.quantity, si.unit_price, p.name AS product_name
        FROM sales_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ", [$o['sale_id']]);
}
unset($o);

// --- Invoices with Items ---
$invoices = safe_query_assoc("
    SELECT i.id, i.invoice_number, i.sale_id, i.total_amount, i.invoice_date,
           s.payment_method, c.first_name AS cust_first, c.last_name AS cust_last, st.name AS store_name
    FROM invoices i
    JOIN sales s ON i.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    LEFT JOIN stores st ON s.store_id = st.id
    ORDER BY i.invoice_date DESC LIMIT 50
");

foreach ($invoices as &$inv) {
    $inv['items'] = safe_query_assoc("
        SELECT si.quantity, si.unit_price, p.name AS product_name
        FROM sales_items si
        JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
    ", [$inv['sale_id']]);
}
unset($inv);

// --- Returns ---
$returns = safe_query_assoc("
    SELECT r.id, r.sale_id, r.return_date, r.reason, r.refund_amount,
           c.first_name AS cust_first, c.last_name AS cust_last
    FROM returns r
    JOIN sales s ON r.sale_id = s.id
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY r.return_date DESC LIMIT 50
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
   

    <div class="alert-container">
            <?php include '../includes/navbar.php'; ?>
        <?php if (!empty($_SESSION['flash_alert'])): 
            $a = $_SESSION['flash_alert'];
            $cls = $a['type'] === 'success' ? 'alert-success' : ($a['type'] === 'danger' ? 'alert-danger' : 'alert-info');
        ?>
            <div class="alert <?= $cls; ?>"><?= htmlspecialchars($a['msg']); ?></div>
            <?php unset($_SESSION['flash_alert']); ?>
        <?php endif; ?>
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
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="row" style="margin-bottom: 1rem;">
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Money</div>
                        <div class="stat-value">₱<?= number_format($stats['today_sales'], 2); ?></div>
                        <div class="stat-label">Today's Sales</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Cart</div>
                        <div class="stat-value"><?= intval($stats['orders_today']); ?></div>
                        <div class="stat-label">Orders Today</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">People</div>
                        <div class="stat-value"><?= intval($stats['unique_customers_today']); ?></div>
                        <div class="stat-label">Unique Customers</div>
                    </div>
                </div>
                <div class="col-md-3" style="margin-bottom: 0.75rem;">
                    <div class="stat-card">
                        <div class="stat-icon">Star</div>
                        <div class="stat-value"><?= number_format($stats['avg_rating'], 1); ?></div>
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
                                    <option value="<?= $st['id']; ?>"><?= htmlspecialchars($st['name']); ?></option>
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
                                            <tr>
                                                <td>#S<?= str_pad($o['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                                <td><?= htmlspecialchars($custName); ?></td>
                                                <td><?= htmlspecialchars($o['store_name'] ?? ''); ?></td>
                                                <td><?= date('M j, Y', strtotime($o['created_at'])); ?></td>
                                                <td>
                                                    <div class="items-list">
                                                        <?php foreach ($o['items'] as $it): ?>
                                                            <div>
                                                                <strong><?= htmlspecialchars($it['product_name']); ?></strong><br>
                                                                Qty: <?= $it['quantity']; ?> × ₱<?= number_format($it['unit_price'], 2); ?> = ₱<?= number_format($it['quantity'] * $it['unit_price'], 2); ?>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </td>
                                                <td>₱<?= number_format($o['total_amount'], 2); ?></td>
                                                <td><?= htmlspecialchars($o['payment_method']); ?></td>
                                                <td><span class="badge <?= ($status === 'Paid' ? 'badge-success' : 'badge-warning'); ?>"><?= $status; ?></span></td>
                                                <td>
                                                    <div class="action-buttons">
                                                        <button class="btn btn-sm btn-primary" onclick="viewOrder(<?= $o['sale_id']; ?>)">View</button>
                                                        <button class="btn btn-sm btn-info" onclick="printInvoice(<?= $o['sale_id']; ?>)">Print</button>
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
                <!-- Similar structure, omitted for brevity -->
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
                                            <td>#R<?= str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td>#S<?= str_pad($r['sale_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                            <td><?= htmlspecialchars(trim(($r['cust_first'] ?? '') . ' ' . ($r['cust_last'] ?? ''))); ?></td>
                                            <td><?= date('M j, Y', strtotime($r['return_date'])); ?></td>
                                            <td><?= htmlspecialchars($r['reason']); ?></td>
                                            <td>₱<?= number_format($r['refund_amount'], 2); ?></td>
                                            <td><button class="btn btn-sm btn-primary" onclick="viewReturn(<?= $r['id']; ?>)">View</button></td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- REPORTS & ANALYTICS TABS (unchanged) -->
            <!-- ... -->

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
                        <!-- Form fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Customer</label>
                                <select name="customerId" id="customerId" required>
                                    <option value="">Select Customer</option>
                                    <?php foreach ($customers as $c): ?>
                                        <option value="<?= $c['id']; ?>">
                                            <?= htmlspecialchars($c['name']); ?> (<?= htmlspecialchars($c['member_code']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Other fields -->
                        </div>
                        <!-- Product items container -->
                        <div id="productItemsContainer">
                            <!-- Initial item -->
                            <div class="product-item">
                                <div class="form-group">
                                    <select class="product-select" required>
                                        <option value="">Select Product</option>
                                        <?php foreach ($products as $p): ?>
                                            <option value="<?= $p['id']; ?>" data-price="<?= $p['price']; ?>">
                                                <?= htmlspecialchars($p['name']); ?> (₱<?= number_format($p['price'], 2); ?>)
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
                        <!-- Totals and submit -->
                    </form>
                </div>
            </div>

            <!-- Return Modal (similar) -->
            <!-- ... -->

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

        // Modal functions
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
                            <option value="<?= $p['id']; ?>" data-price="<?= $p['price']; ?>">
                                <?= htmlspecialchars($p['name']); ?> (₱<?= number_format($p['price'], 2); ?>)
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

        function addReturnItem() {
            const container = document.getElementById('returnItemsContainer');
            const newItem = document.createElement('div');
            newItem.className = 'product-item';
            newItem.innerHTML = `
                <div class="form-group">
                    <select class="return-product-select" required>
                        <option value="">Select Product</option>
                        <?php foreach ($products as $p): ?>
                            <option value="<?= $p['id']; ?>" data-price="<?= $p['price']; ?>">
                                <?= htmlspecialchars($p['name']); ?>
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

        // Attach listeners (unchanged)
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

        // Initialize
        document.querySelectorAll('#productItemsContainer .product-item').forEach(attachProductItemListeners);

        // calculateSaleTotal(), prepareSaleSubmit(), etc. (unchanged)
        // ... (rest of your JS functions)

        calculateSaleTotal();
        calculateReturnTotal();
    </script>
</body>
</html>