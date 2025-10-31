<?php
include './Connection.php';

/**
 * ✅ REQUEST TO PAY — Update Payment Status
 */
if (isset($_GET['action']) && $_GET['action'] === 'requestPay' && isset($_GET['batch'])) {
    $batchNo = $_GET['batch'];

    $sqlFindPO = "SELECT PurchaseOrderID FROM purchaseorders WHERE BatchNo = ? LIMIT 1";
    $stmtPO = $conn->prepare($sqlFindPO);
    $stmtPO->bind_param("s", $batchNo);
    $stmtPO->execute();
    $poData = $stmtPO->get_result()->fetch_assoc();
    $stmtPO->close();

    if ($poData) {
        $poid = intval($poData['PurchaseOrderID']);

        $sqlUpdate = "UPDATE accountspayable 
                      SET PaymentStatus = 'Request to Pay'
                      WHERE PurchaseOrderID = ?";
        $stmtUP = $conn->prepare($sqlUpdate);
        $stmtUP->bind_param("i", $poid);
        $stmtUP->execute();
        $stmtUP->close();

        echo "<script>
                alert('📌 Request to Pay sent to Accountant!');
                window.location.href='./index.php';
             </script>";
        exit;
    }
}

// ✅ Validate Batch Input
if (!isset($_GET['batch']) || empty($_GET['batch'])) {
    die("Invalid Batch Number");
}
$batchNo = $_GET['batch'];

/**
 * ✅ Fetch Header Details (Supplier, Dates, Payment)
 */
$sqlHeader = "
    SELECT 
        po.PurchaseOrderID,
        po.BatchNo,
        s.SupplierName,
        s.PaymentTerms,
        MIN(po.OrderDate) AS OrderDate,
        MAX(thp.ArrivalDate) AS ArrivalDate,

        -- ✅ Latest Payment Status
        COALESCE(
            (SELECT ap2.PaymentStatus 
             FROM accountspayable ap2
             WHERE ap2.PurchaseOrderID = po.PurchaseOrderID
             ORDER BY ap2.APID DESC LIMIT 1),
            'Pending'
        ) AS PaymentStatus,

        -- ✅ Latest Payment Date
        (SELECT ap3.PaymentDate 
         FROM accountspayable ap3
         WHERE ap3.PurchaseOrderID = po.PurchaseOrderID
         ORDER BY ap3.APID DESC LIMIT 1) AS PaymentDate,

        -- ✅ Latest Amount Due
        COALESCE(
            (SELECT ap4.AmountDue 
             FROM accountspayable ap4
             WHERE ap4.PurchaseOrderID = po.PurchaseOrderID
             ORDER BY ap4.APID DESC LIMIT 1),
            0
        ) AS AmountDue

    FROM purchaseorders po
    LEFT JOIN suppliers s ON s.SupplierID = po.SupplierID
    LEFT JOIN transaction_history_precurement thp ON thp.BatchNo = po.BatchNo
    WHERE po.BatchNo = ?
    GROUP BY po.PurchaseOrderID, po.BatchNo, s.SupplierName, s.PaymentTerms
";


$stmtH = $conn->prepare($sqlHeader);
$stmtH->bind_param("s", $batchNo);
$stmtH->execute();
$header = $stmtH->get_result()->fetch_assoc();
$stmtH->close();

if (!$header) {
    die("No GRN data found.");
}

/**
 * ✅ Fetch Item Lines
 */
$sqlLines = "
    SELECT 
        CONCAT(thp.Brand, ' ', thp.Model) AS ProductName,
        COALESCE(SUM(DISTINCT pod.Quantity), 0) AS QtyOrdered,
        SUM(thp.Passed) AS PassedQty,
        SUM(thp.Failed) AS FailedQty,
        AVG(thp.UnitCost) AS UnitCost
    FROM transaction_history_precurement thp
    LEFT JOIN purchaseorders po ON po.BatchNo = thp.BatchNo
    LEFT JOIN products p ON p.Brand = thp.Brand AND p.Model = thp.Model
    LEFT JOIN purchaseorderdetails pod ON pod.PurchaseOrderID = po.PurchaseOrderID
        AND pod.ProductID = p.ProductID
    WHERE thp.BatchNo = ?
    GROUP BY ProductName
    ORDER BY ProductName
";

$stmtL = $conn->prepare($sqlLines);
$stmtL->bind_param("s", $batchNo);
$stmtL->execute();
$resultLines = $stmtL->get_result();
$stmtL->close();

$products = [];
$totalSubtotal = 0;

while ($row = $resultLines->fetch_assoc()) {
    $row['LineTotal'] = $row['PassedQty'] * $row['UnitCost'];
    $totalSubtotal += $row['LineTotal'];
    $products[] = $row;
}

$amountDue = $header['AmountDue'] > 0 ? $header['AmountDue'] : $totalSubtotal;

// Helper Functions
function h($v) { return htmlspecialchars((string)$v); }
function nf($n) { return number_format((float)$n,2); }

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Goods Receipt Note - <?= h($batchNo) ?></title>
<style>
body{font-family:Arial, sans-serif;background:#efefef;padding:20px;margin:0;}
.container{max-width:900px;margin:0 auto;background:#fff;border:1px solid #ccc;
padding:20px;border-radius:8px;}
h1{margin:0 0 10px;}
.sub{color:#555;margin-bottom:18px;}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;padding:10px;border:1px solid #ddd;
background:#fafafa;border-radius:6px;font-size:14px;}
.label{font-weight:bold;}
table{width:100%;border-collapse:collapse;margin-top:15px;font-size:14px;}
th,td{border:1px solid #ccc;padding:8px;}
th{background:#e1e1e1;}
tfoot td{font-weight:bold;}
.actions{display:flex;justify-content:space-between;margin-top:18px;}
.btn{padding:7px 12px;border:1px solid #333;background:#fff;border-radius:5px;font-weight:bold;
text-decoration:none;color:#000;}
.btn:hover{background:#e8e8e8;cursor:pointer;}
.note{text-align:right;margin-top:10px;font-size:14px;font-weight:bold;}
</style>
</head>

<body>

<div class="container">
    <h1>Goods Receipt Note</h1>
    <div class="sub">Final confirmation for Accounts Payable</div>

    <div class="grid">
        <div><span class="label">Supplier:</span> <?= h($header['SupplierName']) ?></div>
        <div><span class="label">PO Number:</span> <?= h($batchNo) ?></div>
        <div><span class="label">Ordered Date:</span> <?= h($header['OrderDate']) ?></div>
        <div><span class="label">Arrival Date:</span> <?= h($header['ArrivalDate']) ?></div>
        <div><span class="label">Payment Terms:</span> <?= h($header['PaymentTerms']) ?></div>
        <div><span class="label">Payment Status:</span> <?= h($header['PaymentStatus']) ?></div>

        <?php if (!empty($header['PaymentDate'])): ?>
        <div><span class="label">Payment Date:</span> <?= h($header['PaymentDate']) ?></div>
        <?php endif; ?>
    </div>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th style="text-align:center;">Ordered</th>
                <th style="text-align:center;">QC Passed</th>
                <th style="text-align:center;">Unit Cost</th>
                <th style="text-align:right;">Line Total</th>
                <th style="text-align:center;">QC Failed</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= h($p['ProductName']) ?></td>
                <td style="text-align:center;"><?= nf($p['QtyOrdered']) ?></td>
                <td style="text-align:center;"><?= nf($p['PassedQty']) ?></td>
                <td style="text-align:center;"><?= nf($p['UnitCost']) ?></td>
                <td style="text-align:right;"><?= nf($p['LineTotal']) ?></td>
                <td style="text-align:center;"><?= nf($p['FailedQty']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>

        <tfoot>
            <tr>
                <td colspan="4" align="right">Subtotal (QC Passed):</td>
                <td align="right"><?= nf($totalSubtotal) ?></td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="note">Amount Due: ₱<?= nf($amountDue) ?></div>

    <div class="actions">
        <a href="./index.php" class="btn">BACK</a>

        <?php if (strtoupper($header['PaymentStatus']) === 'PENDING'): ?>
            <a href="goodreceipts.php?action=requestPay&batch=<?= h($batchNo) ?>"
               class="btn"
               onclick="return confirm('Send Request to Pay?');">
               REQUEST TO PAY
            </a>

        <?php elseif (strtoupper($header['PaymentStatus']) === 'REQUEST TO PAY'): ?>
            <span>📌 Waiting for Accountant Approval...</span>

        <?php else: ?>
            <span>✅ PAID<?= !empty($header['PaymentDate']) ? ' ('.h($header['PaymentDate']).')' : '' ?></span>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php $conn->close(); ?>
