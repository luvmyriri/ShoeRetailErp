<?php
include './Connection.php';

// ✅ Filter: Month & Year (default current month)
$report_month = isset($_GET['month']) ? $_GET['month'] : date('Y-m');

// ✅ Filter: Payment Status
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'All';


// ✅ Query using JOINs; View removed successfully ✅
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

$stmt = $conn->prepare($sql);

if ($status_filter !== 'All') {
    $stmt->bind_param("ss", $report_month, $status_filter);
} else {
    $stmt->bind_param("s", $report_month);
}

$stmt->execute();
$result = $stmt->get_result();
$rows = $result->fetch_all(MYSQLI_ASSOC);


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
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" href="./css/reports.css">
    <script src="./js/reports.js"></script>
    <title>Monthly Receiving and QC Report</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
</head>

<body class="bg-gray-50 font-sans p-4 min-h-screen flex flex-col">

    <!-- ✅ FILTER FORM -->
    <form method="GET" class="max-w-6xl mx-auto flex gap-4 mb-6">
        
        <!-- Month Filter -->
        <div>
            <label class="text-gray-700 font-medium">Month:</label>
            <input type="month" name="month" value="<?= $report_month ?>" class="border p-2 rounded-md">
        </div>

        <!-- Status Filter -->
        <div>
            <label class="text-gray-700 font-medium">Payment Status:</label>
            <select name="status" class="border p-2 rounded-md">
                <option <?= ($status_filter == 'All' ? 'selected' : '') ?>>All</option>
                <option value="Pending" <?= ($status_filter == 'Pending' ? 'selected' : '') ?>>Pending</option>
                <option value="Paid" <?= ($status_filter == 'Paid' ? 'selected' : '') ?>>Paid</option>
                <option value="Overdue" <?= ($status_filter == 'Overdue' ? 'selected' : '') ?>>Overdue</option>
                <option value="Partial" <?= ($status_filter == 'Partial' ? 'selected' : '') ?>>Partial</option>
                <option value="Request to pay" <?= ($status_filter == 'Partial' ? 'selected' : '') ?>>Request To Pay</option>
            </select>
        </div>

        <button type="submit" class="self-end bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-md shadow">
            FILTER
        </button>
    </form>


    <!-- ✅ REPORT CONTENT -->
    <div id="monthly-report-content" class="max-w-6xl mx-auto p-8 bg-white rounded-xl shadow-2xl border border-gray-200 flex-grow">

        <header class="text-center mb-8 pb-4 border-b-4 border-indigo-600">
            <h1 class="text-3xl font-extrabold text-gray-900">Purchase Receiving & QC Report</h1>
            <h2 class="text-xl font-semibold text-gray-700 mt-1">Monthly Transaction Summary</h2>
            <p class="text-lg text-gray-600 mt-2">
                Month: 
                <span class="font-bold text-gray-800">
                    <?= date('F Y', strtotime($report_month . "-01")) ?>
                </span>
            </p>
        </header>

        <!-- ✅ DATA TABLE -->
        <div class="overflow-x-auto shadow-lg rounded-lg border border-gray-200">
            <table class="report-table w-full border-collapse">
                <thead>
                    <tr class="bg-gray-700 text-white text-left uppercase tracking-wider">
                        <th class="p-3 rounded-tl-lg">Batch #</th>
                        <th class="p-3">Brand</th>
                        <th class="p-3">Model</th>
                        <th class="p-3 text-center">Received</th>
                        <th class="p-3 text-center bg-green-700">QC Passed</th>
                        <th class="p-3 text-center bg-red-700">QC Failed</th>
                        <th class="p-3 text-right">Unit Cost</th>
                        <th class="p-3 text-right">Line Total</th>
                        <th class="p-3 text-center rounded-tr-lg">Arrival Date</th>
                    </tr>
                </thead>

                <tbody class="text-gray-700 divide-y divide-gray-200">
                    <?php foreach ($transactions as $t): ?>
                    <tr class="hover:bg-indigo-50 transition duration-100">
                        <td class="p-3"><?= $t['Batch'] ?></td>
                        <td class="p-3"><?= $t['Brand'] ?></td>
                        <td class="p-3"><?= $t['Model'] ?></td>
                        <td class="p-3 text-center"><?= number_format($t['ReceivedQty']) ?></td>
                        <td class="p-3 text-center text-green-700 font-medium"><?= number_format($t['PassedQty']) ?></td>
                        <td class="p-3 text-center text-red-700 font-medium"><?= number_format($t['FailedQty']) ?></td>
                        <td class="p-3 text-right"><?= number_format($t['UnitCost'], 2) ?></td>
                        <td class="p-3 text-right font-semibold">
                            <?= number_format($t['UnitCost'] * $t['PassedQty'], 2) ?>
                        </td>
                        <td class="p-3 text-center"><?= $t['ArrivalDate'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- ✅ TOTALS FOOTER -->
                <tfoot>
                    <tr class="text-lg bg-gray-100 border-t-4 border-indigo-700">
                        <td colspan="3" class="p-3 text-right font-extrabold text-gray-900">TOTAL:</td>
                        <td class="p-3 text-center"><?= number_format($totReceived) ?></td>
                        <td class="p-3 text-center text-green-700"><?= number_format($totPassed) ?></td>
                        <td class="p-3 text-center text-red-700"><?= number_format($totFailed) ?></td>
                        <td></td>
                        <td class="p-3 text-right text-black text-xl font-extrabold">₱<?= number_format($totAmount, 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>

            </table>
        </div>

    </div>


    <!-- ✅ BUTTONS -->
    <div class="no-print max-w-6xl mx-auto flex justify-between mt-8 mb-4 px-8 py-5 bg-white rounded-xl shadow-lg">
        <a href="./index.php" 
           class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-xl shadow-md">
            DONE
        </a>

        <button onclick="handlePrint()" 
                class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl shadow-md">
            PRINT REPORT
        </button>

        <button onclick="handleDownload()" 
                class="js-download-btn bg-green-600 hover:bg-green-700 text-white font-bold py-3 px-6 rounded-xl shadow-md">
            DOWNLOAD (PDF)
        </button>
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
