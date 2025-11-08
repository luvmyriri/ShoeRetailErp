<?php
session_start();
require_once __DIR__ . '/../../includes/db_helper.php';

$saleId = isset($_GET['sale_id']) ? intval($_GET['sale_id']) : 0;
if ($saleId <= 0) {
    http_response_code(400);
    echo 'Missing sale_id';
    exit;
}

// Fetch sale and related data
$sale = dbFetchOne("SELECT s.*, CONCAT(c.FirstName,' ',COALESCE(c.LastName,'')) AS CustomerName, st.StoreName
                    FROM sales s
                    LEFT JOIN customers c ON s.CustomerID = c.CustomerID
                    LEFT JOIN stores st ON s.StoreID = st.StoreID
                    WHERE s.SaleID = ?", [$saleId]);
if (!$sale) {
    http_response_code(404);
    echo 'Sale not found';
    exit;
}

$items = dbFetchAll("SELECT sd.*, p.SKU, p.Brand, p.Model
                     FROM saledetails sd
                     JOIN products p ON sd.ProductID = p.ProductID
                     WHERE sd.SaleID = ?", [$saleId]);

// Ensure invoice exists
$invoice = dbFetchOne("SELECT * FROM invoices WHERE SaleID = ?", [$saleId]);
if (!$invoice) {
    $invNo = 'INV-' . date('Ymd') . '-' . str_pad($saleId, 5, '0', STR_PAD_LEFT);
    $invoiceId = dbInsert("INSERT INTO invoices (InvoiceNumber, SaleID, CustomerID, StoreID, InvoiceDate, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus, CreatedBy)
                           VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)", [
        $invNo, $saleId, $sale['CustomerID'], $sale['StoreID'], $sale['TotalAmount'], $sale['TaxAmount'], $sale['DiscountAmount'], $sale['PaymentMethod'], $sale['PaymentStatus'], $_SESSION['username'] ?? 'System'
    ]);
    foreach ($items as $ln) {
        dbInsert("INSERT INTO invoiceitems (InvoiceID, ProductID, Quantity, UnitID, QuantityBase, UnitPrice, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)", [
            $invoiceId, $ln['ProductID'], $ln['Quantity'], $ln['SalesUnitID'], $ln['QuantityBase'], $ln['UnitPrice'], $ln['Subtotal']
        ]);
    }
    $invoice = dbFetchOne("SELECT * FROM invoices WHERE InvoiceID = ?", [$invoiceId]);
}

$download = isset($_GET['download']);
if ($download) {
    header('Content-Disposition: attachment; filename="invoice_' . htmlspecialchars($invoice['InvoiceNumber']) . '.html"');
}
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($invoice['InvoiceNumber']) ?></title>
<style>
body { font-family: Arial, sans-serif; margin: 40px; }
.header { display: flex; justify-content: space-between; align-items: center; }
.table { width:100%; border-collapse: collapse; margin-top: 20px; }
.table th, .table td { border:1px solid #ddd; padding:8px; font-size: 14px; }
.totals { text-align: right; margin-top: 10px; }
</style>
</head>
<body>
<div class="header">
  <div>
    <h2>Invoice</h2>
    <div>No: <?= htmlspecialchars($invoice['InvoiceNumber']) ?></div>
    <div>Date: <?= htmlspecialchars(date('Y-m-d H:i', strtotime($invoice['InvoiceDate']))) ?></div>
  </div>
  <div>
    <strong><?= htmlspecialchars($sale['StoreName'] ?? 'Store') ?></strong>
  </div>
</div>
<hr>
<div>
  <strong>Billed To:</strong> <?= htmlspecialchars($sale['CustomerName'] ?? 'Walk-in Customer') ?>
</div>
<table class="table">
  <thead>
    <tr>
      <th>SKU</th><th>Product</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($items as $it): ?>
      <tr>
        <td><?= htmlspecialchars($it['SKU']) ?></td>
        <td><?= htmlspecialchars($it['Brand'] . ' ' . $it['Model']) ?></td>
        <td><?= htmlspecialchars($it['Quantity']) ?></td>
        <td>₱<?= number_format($it['UnitPrice'], 2) ?></td>
        <td>₱<?= number_format($it['Subtotal'], 2) ?></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<div class="totals">
  <div>Discount: ₱<?= number_format($invoice['DiscountAmount'], 2) ?></div>
  <div>Tax: ₱<?= number_format($invoice['TaxAmount'], 2) ?></div>
  <h3>Total: ₱<?= number_format($invoice['TotalAmount'], 2) ?></h3>
</div>
</body>
</html>
