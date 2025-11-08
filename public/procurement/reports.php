<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /ShoeRetailErp/login.php'); exit; }

// Role-based access control
$userRole = $_SESSION['role'] ?? '';
$allowedRoles = ['Admin', 'Manager', 'Procurement'];

if (!in_array($userRole, $allowedRoles)) {
    header('Location: /ShoeRetailErp/public/index.php?error=access_denied');
    exit;
}

require_once '../../config/database.php';
require_once '../../includes/core_functions.php';

// ✅ Filter: Month & Year (default current month)
$report_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ✅ Filter: Payment Status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';


$sql = "SELECT 
            thp.BatchNo AS PONumber,
            CONCAT(thp.Brand, ' ', thp.Model) AS ProductName,
            thp.Passed AS PassedQty,
            thp.Failed AS QCFailedQty,
            thp.UnitCost AS UnitCost,
            thp.OrderedDate AS OrderedDate,
            thp.ArrivalDate AS ArrivalDate,
            pod.Quantity AS QtyOrdered
        FROM transaction_history_precurement thp
        LEFT JOIN purchaseorders po ON thp.PurchaseOrderID = po.PurchaseOrderID
        LEFT JOIN purchaseorderdetails pod ON pod.PurchaseOrderID = thp.PurchaseOrderID
        LEFT JOIN accountspayable ap ON ap.PurchaseOrderID = thp.PurchaseOrderID
        WHERE DATE_FORMAT(thp.ArrivalDate, '%Y-%m') = ?";

if ($status_filter !== 'All') {
    $sql .= " AND ap.PaymentStatus = ?";
}

$sql .= " ORDER BY PONumber, ProductName";

$params = [$report_month];
if ($status_filter !== 'All') {
    $params[] = $status_filter;
}

$rows = dbFetchAll($sql, $params);


// ✅ Data grouping & totals
$transactions = [];
$totReceived = $totPassed = $totFailed = $totAmount = 0;

foreach ($rows as $r) {
    $key = $r['PONumber'] . "_" . $r['ProductName'];

    if (!isset($transactions[$key])) {
        $transactions[$key] = [
            'Batch' => $r['PONumber'],
            'Brand' => explode(' ', $r['ProductName'])[0],
            'Model' => substr($r['ProductName'], strpos($r['ProductName'], ' ') + 1),
            'ReceivedQty' => 0,
            'PassedQty' => 0,
            'FailedQty' => 0,
            'UnitCost' => $r['UnitCost'],
            'OrderedDate' => $r['OrderedDate'],
            'ArrivalDate' => $r['ArrivalDate']
        ];
    }

    $transactions[$key]['ReceivedQty'] += ($r['PassedQty'] + $r['QCFailedQty']);
    $transactions[$key]['PassedQty'] += $r['PassedQty'];
    $transactions[$key]['FailedQty'] += $r['QCFailedQty'];

    $totReceived += ($r['PassedQty'] + $r['QCFailedQty']);
    $totPassed += $r['PassedQty'];
    $totFailed += $r['QCFailedQty'];
    $totAmount += $r['UnitCost'] * $r['PassedQty'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Receiving and QC Report - Shoe Retail ERP</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>

<body>
<?php include '../includes/navbar.php'; ?>

<div class="main-wrapper" style="margin-left: 0;">
    <main class="main-content">
        <!-- Page Header -->
        <div class="page-header no-print">
            <div class="page-header-title">
                <h1>Purchase Receiving & QC Report</h1>
                <div class="page-header-breadcrumb">
                    <a href="/ShoeRetailErp/public/index.php">Home</a> / 
                    <a href="./index.php">Procurement</a> / 
                    Reports
                </div>
            </div>
            <div class="page-header-actions">
                <button class="btn btn-secondary" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button class="btn btn-primary" onclick="handleDownload()">
                    <i class="fas fa-download"></i> Download PDF
                </button>
            </div>
        </div>

        <!-- Filter Form -->
        <form method="GET" class="card no-print" style="margin-bottom: 1rem;">
            <div class="card-header">
                <h3>Report Filters</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; align-items: end;">
                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600;">Month:</label>
                        <input type="month" name="month" value="<?= $report_month ?>" class="form-control">
                    </div>

                    <div>
                        <label style="display: block; margin-bottom: 0.25rem; font-weight: 600;">Payment Status:</label>
                        <select name="status" class="form-control">
                            <option <?= ($status_filter == 'All' ? 'selected' : '') ?>>All</option>
                            <option value="Pending" <?= ($status_filter == 'Pending' ? 'selected' : '') ?>>Pending</option>
                            <option value="Paid" <?= ($status_filter == 'Paid' ? 'selected' : '') ?>>Paid</option>
                            <option value="Overdue" <?= ($status_filter == 'Overdue' ? 'selected' : '') ?>>Overdue</option>
                            <option value="Partial" <?= ($status_filter == 'Partial' ? 'selected' : '') ?>>Partial</option>
                            <option value="Request to pay" <?= ($status_filter == 'Request to pay' ? 'selected' : '') ?>>Request To Pay</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>
            </div>
        </form>

        <!-- Report Content -->
        <div id="monthly-report-content" class="card">
            <div class="card-header" style="text-align: center; border-bottom: 2px solid var(--primary-color);">
                <h2 style="margin: 0 0 0.5rem 0; font-size: 24px;">Purchase Receiving & QC Report</h2>
                <p style="margin: 0; font-size: 16px; color: var(--gray-600);">Monthly Transaction Summary</p>
                <p style="margin: 0.5rem 0 0 0; font-size: 14px; color: var(--gray-700);">
                    <strong>Month: <?= date('F Y', strtotime($report_month . "-01")) ?></strong>
                </p>
            </div>

            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Batch #</th>
                            <th>Brand</th>
                            <th>Model</th>
                            <th style="text-align: center;">Received</th>
                            <th style="text-align: center;">QC Passed</th>
                            <th style="text-align: center;">QC Failed</th>
                            <th style="text-align: right;">Unit Cost</th>
                            <th style="text-align: right;">Line Total</th>
                            <th style="text-align: center;">Arrival Date</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="9" style="text-align: center; padding: 2rem; color: var(--gray-500);">
                                <i class="fas fa-inbox" style="font-size: 2rem; margin-bottom: 0.5rem; opacity: 0.5; display: block;"></i>
                                No transactions found for selected filters
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['Batch']) ?></td>
                                <td><?= htmlspecialchars($t['Brand']) ?></td>
                                <td><?= htmlspecialchars($t['Model']) ?></td>
                                <td style="text-align: center;"><?= number_format($t['ReceivedQty']) ?></td>
                                <td style="text-align: center; color: var(--success-color); font-weight: 600;"><?= number_format($t['PassedQty']) ?></td>
                                <td style="text-align: center; color: var(--danger-color); font-weight: 600;"><?= number_format($t['FailedQty']) ?></td>
                                <td style="text-align: right;">₱<?= number_format($t['UnitCost'], 2) ?></td>
                                <td style="text-align: right; font-weight: 600;">
                                    ₱<?= number_format($t['UnitCost'] * $t['PassedQty'], 2) ?>
                                </td>
                                <td style="text-align: center;"><?= date('M d, Y', strtotime($t['ArrivalDate'])) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                    <?php if (!empty($transactions)): ?>
                    <tfoot style="border-top: 2px solid var(--primary-color); background-color: var(--gray-50);">
                        <tr style="font-weight: 700;">
                            <td colspan="3" style="text-align: right; padding: 0.75rem;">TOTAL:</td>
                            <td style="text-align: center;"><?= number_format($totReceived) ?></td>
                            <td style="text-align: center; color: var(--success-color);"><?= number_format($totPassed) ?></td>
                            <td style="text-align: center; color: var(--danger-color);"><?= number_format($totFailed) ?></td>
                            <td></td>
                            <td style="text-align: right; font-size: 18px; color: var(--gray-900);">₱<?= number_format($totAmount, 2) ?></td>
                            <td></td>
                        </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

    </main>
</div>

    <script>
        function handlePrint() {
            window.print();
        }

        function handleDownload() {
            const element = document.getElementById("monthly-report-content");
            html2pdf().from(element).save("Monthly_QC_Report.pdf");
        }
    </script>

</body>
</html>
