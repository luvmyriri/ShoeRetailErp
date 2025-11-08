<?php
/**
 * public/sales/receipt.php
 * Print-ready receipt for a completed sale.
 * Called from POS redirect → ?sale_id={id}
 */

session_start();
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/company.php';

/* ---------- helpers ---------- */
function get_db() {
    // Prefer global helper if available (mysqli or wrapper)
    if (function_exists('getDB')) return getDB();

    // Try common globals
    global $pdo, $db;
    if (isset($pdo) && $pdo) return $pdo;

    if (isset($db) && $db) {
        // If this is a wrapper with getConnection(), unwrap to PDO/mysqli
        if (is_object($db) && method_exists($db, 'getConnection')) {
            try { $conn = $db->getConnection(); if ($conn) return $conn; } catch (Exception $e) {}
        }
        return $db;
    }

    // If a Database singleton exists, unwrap its connection
    if (class_exists('Database')) {
        try {
            $inst = Database::getInstance();
            if ($inst && method_exists($inst, 'getConnection')) {
                $conn = $inst->getConnection();
                if ($conn) return $conn;
            }
        } catch (Exception $e) {}
    }

    // Fallback: build raw PDO from constants
    if (defined('DB_HOST')) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        return new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }
    throw new Exception("No database connection defined");
}
function fetch_all($sql, $params = []) {
    $db = get_db();
    // PDO path
    if ($db instanceof PDO) {
        $st = $db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }
    // Unwrap wrapper to underlying PDO/mysqli if possible
    if (is_object($db) && method_exists($db, 'getConnection')) {
        $conn = $db->getConnection();
        if ($conn instanceof PDO) {
            $st = $conn->prepare($sql);
            $st->execute($params);
            return $st->fetchAll(PDO::FETCH_ASSOC);
        }
    }
    // Wrapper with fetchAll method (custom db class)
    if (is_object($db) && method_exists($db, 'fetchAll')) {
        return $db->fetchAll($sql, $params);
    }
    throw new Exception('Unsupported DB adapter for fetch_all');
}
function fetch_one($sql, $params = []) {
    $db = get_db();
    if ($db instanceof PDO) {
        $st = $db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
    if (is_object($db) && method_exists($db, 'getConnection')) {
        $conn = $db->getConnection();
        if ($conn instanceof PDO) {
            $st = $conn->prepare($sql);
            $st->execute($params);
            $row = $st->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        }
    }
    if (is_object($db) && method_exists($db, 'fetchOne')) {
        return $db->fetchOne($sql, $params);
    }
    // fallback via fetch_all
    $r = fetch_all($sql, $params);
    return count($r) ? $r[0] : null;
}

/* ---------- validate ---------- */
$saleId = isset($_GET['sale_id']) ? (int)$_GET['sale_id'] : 0;
if ($saleId <= 0) {
    echo "<h3>Invalid Sale ID</h3>";
    exit;
}

/* ---------- fetch sale header ---------- */
$sale = fetch_one("
    SELECT s.*, 
           c.FirstName AS cust_first, c.LastName AS cust_last,
           st.StoreName AS store_name, st.Location AS store_location,
           u.Username AS cashier_user, e.FirstName AS emp_first, e.LastName AS emp_last
      FROM sales s
 LEFT JOIN customers c ON s.CustomerID = c.CustomerID
 LEFT JOIN stores st    ON s.StoreID = st.StoreID
 LEFT JOIN users u      ON s.SalespersonID = u.UserID
 LEFT JOIN employees e  ON s.SalespersonID = e.EmployeeID
     WHERE s.SaleID = ?
", [$saleId]);

/* ---------- fetch invoice ---------- */
$invoice = fetch_one("SELECT InvoiceNumber, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod FROM invoices WHERE SaleID = ? LIMIT 1", [$saleId]);

if (!$sale) {
    echo "<h3>Sale not found.</h3>";
    exit;
}

/* ---------- fetch line items ---------- */
$lines = fetch_all("
    SELECT sd.Quantity AS quantity,
           sd.UnitPrice AS unit_price,
           sd.Subtotal AS subtotal,
           p.Brand, p.Model, p.Color, p.Size, p.SKU
      FROM saledetails sd
 LEFT JOIN products p ON sd.ProductID = p.ProductID
     WHERE sd.SaleID = ?
", [$saleId]);

/* ---------- compute totals ---------- */
$subtotal = array_sum(array_column($lines, 'subtotal'));
$tax       = (float)$sale['TaxAmount'];
$discount  = (float)$sale['DiscountAmount'];
$total     = (float)$sale['TotalAmount'];

$customerName = trim(($sale['cust_first'] ?? '') . ' ' . ($sale['cust_last'] ?? '')) ?: 'Walk-in';
$storeName    = $sale['store_name'] ?? 'Store';
$storeLoc     = $sale['store_location'] ?? '';
$cashier      = trim(($sale['emp_first'] ?? '') . ' ' . ($sale['emp_last'] ?? '')) ?: ($sale['cashier_user'] ?? ($_SESSION['user_name'] ?? 'System'));
$paymentMethod = $sale['PaymentMethod'] ?? 'Cash';
$saleDate     = date('F j, Y g:i A', strtotime($sale['SaleDate'] ?? 'now'));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Receipt #<?= htmlspecialchars($saleId) ?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<style>
:root {
    --primary: #714B67;
    --gray: #6b7280;
}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Arial,sans-serif;
     background:#fff;color:#111;margin:0;padding:16px;}
.receipt{max-width:720px;margin:0 auto;border:1px solid #eee;
         padding:20px;border-radius:8px;}
.header{display:flex;justify-content:space-between;align-items:flex-start;
        border-bottom:1px solid #e5e7eb;margin-bottom:12px;padding-bottom:8px;}
h1{margin:0;color:var(--primary);font-size:1.3rem}
.muted{color:var(--gray);font-size:.9rem}
table{width:100%;border-collapse:collapse;margin-top:8px}
th,td{padding:8px;border-bottom:1px dashed #e5e7eb;text-align:left}
th{background:#fafafa;font-weight:600}
.totals{margin-top:12px}
.totals div{display:flex;justify-content:space-between;padding:4px 0}
.total{font-weight:700;font-size:1.2rem;border-top:1px solid #e5e7eb;padding-top:6px}
.print-actions{margin-top:14px;display:flex;gap:8px;justify-content:flex-end}
.btn{padding:8px 12px;border:none;border-radius:6px;cursor:pointer;font-weight:600}
.btn-print{background:var(--primary);color:#fff}
.btn-back{background:#f3f4f6}
@media print{.print-actions{display:none}.receipt{border:none;box-shadow:none}}
</style>
</head>
<body>
<div class="receipt" role="document" aria-label="Receipt">
  <div class="header">
    <div>
      <h1><?= htmlspecialchars($storeName) ?></h1>
      <?php if ($storeLoc): ?><div class="muted"><?= htmlspecialchars($storeLoc) ?></div><?php endif; ?>
      <div class="muted">VAT Reg TIN: <strong><?= htmlspecialchars(defined('COMPANY_TIN')?COMPANY_TIN:'') ?></strong></div>
      <div class="muted">Sale #: <strong>#S<?= str_pad($saleId,4,'0',STR_PAD_LEFT) ?></strong></div>
      <div class="muted">Date: <?= htmlspecialchars($saleDate) ?></div>
    </div>
    <div style="text-align:right">
      <div class="muted">Sales Invoice</div>
      <div class="muted">Invoice #: <strong><?= htmlspecialchars($invoice['InvoiceNumber'] ?? '-') ?></strong></div>
      <div class="muted">Cashier:</div>
      <div style="font-weight:700"><?= htmlspecialchars($cashier) ?></div>
      <div class="muted" style="margin-top:6px">Customer: <?= htmlspecialchars($customerName) ?></div>
      <div class="muted">Payment: <?= htmlspecialchars($invoice['PaymentMethod'] ?? $paymentMethod) ?></div>
    </div>
  </div>

  <table role="table">
    <thead><tr><th>Item</th><th>Qty</th><th>Unit</th><th>Total</th></tr></thead>
    <tbody>
      <?php foreach($lines as $ln): ?>
        <tr>
          <td>
            <div style="font-weight:600"><?= htmlspecialchars(($ln['Brand'] ?? '').' '.($ln['Model'] ?? '')) ?></div>
            <div class="muted" style="font-size:.85rem">
              <?= htmlspecialchars($ln['SKU'] ?? '') ?>
              <?= $ln['Size'] ? ' • '.$ln['Size'] : '' ?>
              <?= $ln['Color'] ? ' • '.$ln['Color'] : '' ?>
            </div>
          </td>
          <td><?= number_format($ln['quantity'],0) ?></td>
          <td>₱<?= number_format($ln['unit_price'],2) ?></td>
          <td>₱<?= number_format($ln['subtotal'],2) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="totals">
    <div><span>VAT Sales</span><span>₱<?= number_format(max(0, $total - $tax),2) ?></span></div>
    <div><span>VAT (<?= (int)round((defined('VAT_RATE')?VAT_RATE:0.12)*100) ?>%)</span><span>₱<?= number_format($tax,2) ?></span></div>
    <div><span>Discount</span><span>-₱<?= number_format($discount,2) ?></span></div>
    <div class="total"><span>Total Due</span><span>₱<?= number_format($total,2) ?></span></div>
    <div><span>Ref No.</span><span><?= htmlspecialchars($invoice['InvoiceNumber'] ?? ('S'.$saleId)) ?></span></div>
  </div>
  <div style="margin-top:10px; display:flex; justify-content:flex-end">
    <?php $qrData = urlencode('INV=' . ($invoice['InvoiceNumber'] ?? '') . '&Total=' . number_format($total,2) . '&Date=' . $saleDate); ?>
    <img alt="QR" src="<?= (defined('RECEIPT_QR_BASE')?RECEIPT_QR_BASE:'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=') . $qrData ?>" />
  </div>

  <div style="margin-top:12px;font-size:.9rem">
    <div>Salesperson: <?= htmlspecialchars($cashier) ?></div>
    <div style="margin-top:6px;">Thank you for your purchase!</div>
  </div>

  <div class="print-actions">
    <button class="btn btn-back" onclick="window.location.href='pos.php'">Back to POS</button>
    <button class="btn btn-print" onclick="window.print()">Print</button>
  </div>
</div>

<script>
window.addEventListener('load', () => {
  // give browser a moment to finish rendering before printing
  setTimeout(() => {
    try { window.print(); } catch(e){ console.warn(e); }
  }, 300);
});
</script>
</body>
</html>
