

<?php
include './Connection.php';
// ✅ FETCH RETURNS (Status = not yet)
$returns_orders = [];
$sqlReturns = "SELECT * FROM returns WHERE Status = 'not yet' ORDER BY ReturnID DESC";
$resultReturns = $conn->query($sqlReturns);

if ($resultReturns && $resultReturns->num_rows > 0) {
    while ($row = $resultReturns->fetch_assoc()) {
        $returns_orders[] = $row;
    }
}

// --- PAGINATION SETUP ---
$records_per_page = 10; // Ilan ang gustong ipakita per page
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;

$count_sql = "SELECT COUNT(DISTINCT `Batch#`) AS total_records 
              FROM v_PurchaseOrderDetails 
              WHERE `Ordered Date` IS NULL 
              AND Status = 'Pending'";
$count_result = $conn->query($count_sql);
$total_records = 0;
if ($count_result) {
    $total_records = $count_result->fetch_assoc()['total_records'];
}

// ✅ Calculate pagination
$total_pages = ceil($total_records / $records_per_page);
$offset = ($current_page - 1) * $records_per_page;
if ($offset < 0) $offset = 0;

// ✅ Fetch only Pending purchase orders with NO OrderedDate
$sql = "SELECT 
            `Batch#`,
            SupplierName,
            MIN(Brand) AS Brand,
            GROUP_CONCAT(DISTINCT Model SEPARATOR ', ') AS Model,
            SUM(Qty) AS Qty,
            AVG(Cost) AS Cost,
            SUM(Total) AS Total
        FROM v_PurchaseOrderDetails 
        WHERE `Ordered Date` IS NULL 
        AND Status = 'Pending'
        GROUP BY `Batch#`, SupplierName
        ORDER BY MIN(`Order Date`) DESC
        LIMIT $records_per_page OFFSET $offset";

$purchase_orders = [];
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $purchase_orders[] = $row;
    }
}


// Calculate statistics (These will only count orders where Ordered Date is NULL)
$total_orders = $total_records; // Gamitin ang na-count na total records
$total_spend = 0;
// Kailangan mong i-calculate ang Total Spend sa lahat ng records (hindi lang sa current page)
$spend_sql = "SELECT SUM(Total) AS total_spend FROM v_PurchaseOrderDetails WHERE `Ordered Date` IS NULL";
$spend_result = $conn->query($spend_sql);
if ($spend_result) {
    $total_spend_row = $spend_result->fetch_assoc();
    $total_spend = $total_spend_row['total_spend'] ?? 0;
}
// Fetch unique suppliers count (from all orders, regardless of OrderedDate)
$suppliers_sql = "SELECT COUNT(DISTINCT SupplierName) as supplier_count FROM v_PurchaseOrderDetails";
$suppliers_result = $conn->query($suppliers_sql);
$supplier_count = 0;
if ($suppliers_result && $suppliers_result->num_rows > 0) {
    $supplier_row = $suppliers_result->fetch_assoc();
    $supplier_count = $supplier_row['supplier_count'] ?? 0;
}


// --- NEW: Fetch summarized transaction history by BatchNo ---
$history_transactions = [];

$history_sql = "
    SELECT 
        BatchNo,
        '' AS Brand,
        GROUP_CONCAT(DISTINCT Model SEPARATOR ', ') AS Model,
        
        SUM(Received) AS Received,
        SUM(Passed) AS Passed,
        SUM(Failed) AS Failed,

        AVG(UnitCost) AS UnitCost,
        SUM(PassedCost) AS PassedCost,
        SUM(FailedCost) AS FailedCost,
        SUM(Total) AS Total,

        MIN(OrderedDate) AS OrderedDate,
        MAX(ArrivalDate) AS ArrivalDate
    FROM transaction_history_precurement
    GROUP BY BatchNo
    ORDER BY OrderedDate DESC
    LIMIT 10
";


$history_result = $conn->query($history_sql);

if ($history_result && $history_result->num_rows > 0) {
    while ($row = $history_result->fetch_assoc()) {
        $history_transactions[] = $row;
    }
}

// ✅ Fetch Suppliers Data
$suppliers_data = [];
$supplier_sql = "SELECT * FROM suppliers 
                 ORDER BY 
                 CASE 
                     WHEN Status = 'Active' THEN 0
                     ELSE 1
                 END,
                 SupplierName ASC";

$supplier_result = $conn->query($supplier_sql);

if ($supplier_result && $supplier_result->num_rows > 0) {
    while ($row = $supplier_result->fetch_assoc()) {
        $suppliers_data[] = $row;
    }
}


$goods_receipts = [];

// ✅ Fetch ALL fields for later drilldown use
$sql = "
 SELECT 
    thp.BatchNo AS PONumber,
    CONCAT(thp.Brand, ' ', thp.Model) AS ProductName,
    thp.ArrivalDate AS ReceiptDate,
    s.SupplierName AS Supplier,
    ap.PaymentStatus AS PaymentStatus,
    ap.PaymentDate AS PaymentDate,
    (thp.UnitCost * thp.Passed) AS Total
FROM transaction_history_precurement thp
LEFT JOIN purchaseorders po ON thp.PurchaseOrderID = po.PurchaseOrderID
LEFT JOIN purchaseorderdetails pod ON po.PurchaseOrderID = pod.PurchaseOrderID
LEFT JOIN suppliers s ON s.SupplierID = thp.SupplierID
LEFT JOIN accountspayable ap ON ap.PurchaseOrderID = thp.PurchaseOrderID
WHERE thp.ArrivalDate IS NOT NULL
ORDER BY thp.ArrivalDate DESC
";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $goods_receipts[] = $row;
    }
}

// ✅ Unique BatchNo grouping for display
$display_batches = [];
foreach ($goods_receipts as $item) {
if (!empty($item['ReceiptDate']) && $item['PaymentStatus'] === 'Pending') {
    $display_batches[$item['PONumber']] = $item; 
}
}

// ✅ HANDLE RETURN ACTION
if (isset($_GET['return_id'])) {
    $return_id = intval($_GET['return_id']);

    // ✅ Get return item details
    $sql = "SELECT * FROM returns WHERE ReturnID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        exit("Return record not found!");
    }

    // ✅ Update returns table
    $update_return = "UPDATE returns SET Status = 'returned' WHERE ReturnID = ?";
    $stmtUR = $conn->prepare($update_return);
    $stmtUR->bind_param("i", $return_id);
    $stmtUR->execute();

    // ✅ Get Purchase Order ID
    $sqlPO = "SELECT PurchaseOrderID FROM purchaseorders WHERE BatchNo = ? LIMIT 1";
    $stmtPO = $conn->prepare($sqlPO);
    $stmtPO->bind_param("s", $item['BatchNo']);
    $stmtPO->execute();
    $po = $stmtPO->get_result()->fetch_assoc();

    if ($po) {
        $poid = $po['PurchaseOrderID'];

        // ✅ Set PO to PARTIAL (back to Receiving Orders queue)
        $update_status = "UPDATE purchaseorders SET Status = 'Pending' WHERE PurchaseOrderID = ?";
        $stmtUP = $conn->prepare($update_status);
        $stmtUP->bind_param("i", $poid);
        $stmtUP->execute();

        // ✅ Reduce ReceivedQuantity in purchaseorderdetails
        $sqlReduce = "UPDATE purchaseorderdetails pd 
                      JOIN products p ON p.ProductID = pd.ProductID
                      SET pd.ReceivedQuantity = pd.ReceivedQuantity - ?, 
                          pd.ReceivedStatus = 'Pending'
                      WHERE pd.PurchaseOrderID = ?
                        AND p.Brand = ?
                        AND p.Model = ?";
        $stmtReduce = $conn->prepare($sqlReduce);
        $stmtReduce->bind_param("iiss", 
            $item['Qty'], 
            $poid, 
            $item['Brand'], 
            $item['Model']
        );
        $stmtReduce->execute();

        // ✅ Insert into transaction history again = back to QC
        $insertTH = "INSERT INTO transaction_history_precurement
                    (BatchNo, Brand, Model, Received, Passed, Failed, UnitCost, OrderedDate, ArrivalDate, Description)
                    VALUES (?, ?, ?, ?, 0, ?, ?, CURDATE(), CURDATE(), 'Returned: Pending QC')";
        $stmtTH = $conn->prepare($insertTH);
        $stmtTH->bind_param("sssidd",
            $item['BatchNo'],
            $item['Brand'],
            $item['Model'],
            $item['Qty'],
            $item['Qty'],
            $item['Cost']
        );
        $stmtTH->execute();
    }

    echo "<script>
            alert('✅ Returned item successfully moved back to Receiving for QC!');
            window.location.href='index.php?tab=receivingTab';
         </script>";
    exit;
}


// ✅ RECEIVING ORDERS - Approved & Awaiting Arrival
$receiving_orders = [];

$sqlReceiving = "
    SELECT DISTINCT 
        `Batch#`,
        SupplierName,
        `Order Date` AS OrderedDate
    FROM v_PurchaseOrderDetails
    WHERE 
        `Ordered Date` IS NOT NULL
        AND Status = 'Pending'
    ORDER BY `Order Date` DESC
";

$resultReceiving = $conn->query($sqlReceiving);

if ($resultReceiving && $resultReceiving->num_rows > 0) {
    while ($row = $resultReceiving->fetch_assoc()) {
        $receiving_orders[] = $row;
    }
}


// ✅ Payment History - Only PAID
$payment_history = [];
$sql_payments = "
    SELECT 
        thp.BatchNo AS PONumber,
        GROUP_CONCAT(DISTINCT COALESCE(s.SupplierName, s2.SupplierName) SEPARATOR ', ') AS Supplier,
        MAX(thp.ArrivalDate) AS ReceiptDate,
        SUM(ap.PaidAmount) AS Total,
        MAX(ap.PaymentDate) AS PaymentDate
    FROM transaction_history_precurement thp
    LEFT JOIN purchaseorders po ON thp.PurchaseOrderID = po.PurchaseOrderID
    LEFT JOIN suppliers s ON s.SupplierID = thp.SupplierID
    LEFT JOIN suppliers s2 ON s2.SupplierID = po.SupplierID
    LEFT JOIN accountspayable ap ON ap.PurchaseOrderID = thp.PurchaseOrderID
    WHERE ap.PaymentStatus = 'Paid'
    GROUP BY thp.BatchNo
    ORDER BY MAX(ap.PaymentDate) DESC
";

$result_payments = $conn->query($sql_payments);
if ($result_payments && $result_payments->num_rows > 0) {
    while ($row = $result_payments->fetch_assoc()) {
        $payment_history[] = $row;
    }
}


// ✅ PROCESS RETURN
if (isset($_GET['return_id'])) {
    $return_id = intval($_GET['return_id']);

    // Fetch return details
    $sql = "SELECT * FROM returns WHERE ReturnID = ? AND Status = 'not yet' ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $item = $stmt->get_result()->fetch_assoc();

    if (!$item) {
        exit("❌ Return item not found!");
    }

    // ✅ Update return status
    $stmtUR = $conn->prepare("UPDATE returns SET Status='returned' WHERE ReturnID=?");
    $stmtUR->bind_param("i", $return_id);
    $stmtUR->execute();

    // ✅ Create new BatchNo
    $newBatch = "BATCH-" . strtoupper(uniqid());

    // ✅ Get SupplierID from previous PO
    $stmtPO = $conn->prepare("SELECT SupplierID FROM purchaseorders WHERE BatchNo=? LIMIT 1");
    $stmtPO->bind_param("s", $item['BatchNo']);
    $stmtPO->execute();
    $poData = $stmtPO->get_result()->fetch_assoc();
    $supplierID = $poData['SupplierID'];

    // ✅ Insert NEW PO (Pending Receiving)
    $insertPO = "INSERT INTO purchaseorders (BatchNo, SupplierID, Status, OrderDate, OrderedDate)
                 VALUES (?, ?, 'Pending', NOW(), NOW())";
    $stmtNewPO = $conn->prepare($insertPO);
    $stmtNewPO->bind_param("si", $newBatch, $supplierID);
    $stmtNewPO->execute();
    $newPOID = $conn->insert_id;

    // ✅ Identify ProductID via Brand + Model
    $stmtProd = $conn->prepare("SELECT ProductID FROM products WHERE Brand=? AND Model=? LIMIT 1");
    $stmtProd->bind_param("ss", $item['Brand'], $item['Model']);
    $stmtProd->execute();
    $productData = $stmtProd->get_result()->fetch_assoc();
    $productID = $productData['ProductID'];

    // ✅ Insert PO Details Line
    $insertPOD = "INSERT INTO purchaseorderdetails 
                  (PurchaseOrderID, ProductID, Quantity, UnitCost, Subtotal)
                  VALUES (?, ?, ?, ?, (? * ?))";
    $stmtPOD = $conn->prepare($insertPOD);
    $stmtPOD->bind_param("iidddd",
        $newPOID,
        $productID,
        $item['Qty'],
        $item['Cost'],
        $item['Qty'],
        $item['Cost']
    );
    $stmtPOD->execute();

    echo "<script>
        alert('✅ Item re-ordered & moved to Receiving Orders!');
        window.location.href='index.php?tab=receivingTab';
    </script>";
    exit;
}


$payment_status_filter = isset($_GET['pay_status']) ? $_GET['pay_status'] : 'All';

$sql_payments = "
    SELECT 
        thp.BatchNo AS PONumber,
        thp.Brand,
        thp.Model,

        SUM(thp.Passed + thp.Failed) AS ReceivedQty,
        SUM(thp.Passed) AS PassedQty,
        SUM(thp.Failed) AS FailedQty,

        MAX(thp.UnitCost) AS UnitCost,
        SUM(thp.Passed * thp.UnitCost) AS LineTotal,

        MAX(thp.ArrivalDate) AS ArrivalDate,
        MAX(thp.PurchaseOrderID) AS PurchaseOrderID,

        (
            SELECT ap2.PaymentStatus
            FROM accountspayable ap2
            WHERE ap2.PurchaseOrderID = MAX(thp.PurchaseOrderID)
            ORDER BY ap2.APID DESC
            LIMIT 1
        ) AS PaymentStatus

    FROM transaction_history_precurement thp
    LEFT JOIN accountspayable ap ON ap.PurchaseOrderID = thp.PurchaseOrderID
";

if ($payment_status_filter !== 'All') {
    $sql_payments .= "
        WHERE ap.PaymentStatus = ?
    ";
} else {
    $sql_payments .= "
        WHERE ap.PaymentStatus IN ('Paid', 'Request To Pay')
    ";
}

$sql_payments .= "
    GROUP BY thp.BatchNo, thp.Brand, thp.Model
    ORDER BY ArrivalDate DESC
";

// ✅ safe execution
$stmt = $conn->prepare($sql_payments);
if ($payment_status_filter !== 'All') {
    $stmt->bind_param("s", $payment_status_filter);
}
$stmt->execute();
$result_payments = $stmt->get_result();
$payment_history = $result_payments->fetch_all(MYSQLI_ASSOC);

// ✅ Open POs = Laman ng Order Fulfillment Tab
$sqlOpenPOs = "
    SELECT COUNT(DISTINCT `Batch#`) AS total 
    FROM v_purchaseorderdetails
    WHERE `Ordered Date` IS NULL
    AND Status = 'Pending'
";
$total_open_pos = $conn->query($sqlOpenPOs)->fetch_assoc()['total'] ?? 0;


// ✅ Active Suppliers = Status is Active only
$sqlActiveSuppliers = "
    SELECT COUNT(*) AS total 
    FROM suppliers 
    WHERE Status = 'Active'
";
$active_suppliers = $conn->query($sqlActiveSuppliers)->fetch_assoc()['total'] ?? 0;


// ✅ Receiving Orders = Already approved & Waiting for arrival
$sqlReceivingOrders = "
    SELECT COUNT(DISTINCT BatchNo) AS total 
    FROM transaction_history_precurement
    WHERE ArrivalDate IS NULL
";
$total_receiving_orders = 
    $conn->query($sqlReceivingOrders)->fetch_assoc()['total'] ?? 0;


// ✅ Total Spend = FROM REPORTS — Passed only + PaymentStatus = Pending
$sqlTotalSpend = "
    SELECT SUM(thp.Passed * thp.UnitCost) AS total
    FROM transaction_history_precurement thp
    LEFT JOIN accountspayable ap ON ap.PurchaseOrderID = thp.PurchaseOrderID
    WHERE ap.PaymentStatus = 'Pending'
";
$total_spend = $conn->query($sqlTotalSpend)->fetch_assoc()['total'] ?? 0;



?>


<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Procurement - Shoe Retail ERP</title>
    <link rel="stylesheet" href="./css/index.css">
    <script src="./js/index.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>

<div class="alert-container"></div>
<?php include '../includes/navbar.php'; ?>

<div class="main-wrapper" style="margin-left: 0;">
    <main class="main-content">

        <!-- ✅ PAGE HEADER -->
        <div class="page-header">
            <div class="page-header-title">
                <h1>Procurement Management</h1>
                <div class="page-header-breadcrumb"><a href="/ShoeRetailErp/public/index.php">Home</a> / Procurement</div>
            </div>
        </div>

      <div class="row" style="margin-bottom: 2rem;">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-value"><?= $total_open_pos ?></div>
            <div class="stat-label">Open POs</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-value">₱<?= number_format($total_spend, 2) ?></div>
            <div class="stat-label">Total Pending Spend</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">📥</div>
            <div class="stat-value"><?= $total_receiving_orders ?></div>
            <div class="stat-label">Receiving Orders</div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon">🏭</div>
            <div class="stat-value"><?= $active_suppliers ?></div>
            <div class="stat-label">Active Suppliers</div>
        </div>
    </div>
</div>



        <BR><BR>
        <!-- ✅ SEGMENTATION TABS -->
        <ul class="nav-tabs">
            <li>
                <a href="#" class="nav-link active" data-tab="fulfillmentTab">
                    Order Fulfillment
                </a>
            </li>
            
            <li>
                <a href="#" class="nav-link" data-tab="receivingTab">
                    Receiving Orders
                </a>
            </li>
            <li>
                <a href="#" class="nav-link" data-tab="returnsTab">
                    Returns
                </a>
            </li>
            <li>
                <a href="#" class="nav-link" data-tab="suppliersTab">
                    Suppliers
                </a>
            </li>
            <li>
                <a href="#" class="nav-link" data-tab="history">
                    Receiving History
                </a>
            </li>
            <li>
                <a href="#" class="nav-link" data-tab="goodsTab">
                    Goods Receipt
                </a>
            </li>
            <li>
    <a href="#" class="nav-link" data-tab="paymentsTab">
        Payment History
    </a>
</li>
        </ul>
        

        <!-- ✅ (A) ORDER FULFILLMENT MODULE -->
        <div id="fulfillmentTab" class="tab-pane active">
            <div class="card">
                <div class="card-header">
                    <h3>Approved Requests</h3>
                    <div class="header-actions d-flex gap-2">
                    </div>
                </div>
                <div class="card-body table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Batch #</th>
                                <th>Brand</th>
                                <th>Model</th>
                                <th>Qty</th>
                                <th>Cost</th>
                                <th>Total</th>

                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($purchase_orders)): ?>
                                <?php foreach ($purchase_orders as $order): ?>
                                <tr>
                                    <td><?= htmlspecialchars($order['Batch#'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($order['Brand'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($order['Model'] ?? '') ?></td>
                                    <td><?= htmlspecialchars($order['Qty'] ?? 0) ?></td>
                                    <td>₱<?= number_format($order['Cost'] ?? 0, 2) ?></td>
                                    <td>₱<?= number_format($order['Total'] ?? 0, 2) ?></td>
                                    
                                    <td>  
                                     <a href="./orderfulfillment.php?batch=<?= urlencode($order['Batch#'] ?? '') ?>" class="btn btn-sm btn-primary">
    View Details
</a>


                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="11" style="text-align: center; padding: 20px;">
                                        No purchase orders found.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- ✅ (B) SUPPLIER MANAGEMENT MODULE -->
        <div id="suppliersTab" class="tab-pane" style="display:none;">
            <div class="card-header d-flex justify-space-between align-center">
                <h3>Supplier Management</h3>
                <div class="header-actions d-flex gap-2">
                    <select id="supplierFilter" class="form-control" style="width:150px;">
    <option value="All">All</option>
    <option value="Active">Active</option>
    <option value="Inactive">Inactive</option>
</select>

                    <a href="./addsupplier.php" class="btn btn-sm btn-primary">
                        Add Supplier
                    </a>
                </div>
            </div>

            <div class="card-body table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Email</th>
                            <th>Contact No.</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                 <tbody id="supplierBody">
<?php if (!empty($suppliers_data)): ?>
    <?php foreach ($suppliers_data as $supplier): ?>
        <tr>
            <td><?= htmlspecialchars($supplier['SupplierName']) ?></td>
            <td><?= htmlspecialchars($supplier['ContactName'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($supplier['Email'] ?? 'N/A') ?></td>
            <td><?= htmlspecialchars($supplier['Phone'] ?? 'N/A') ?></td>
            <td>
                <?php if ($supplier['Status'] === "Active"): ?>
                    <span class="badge badge-success">Active</span>
                <?php else: ?>
                    <span class="badge badge-danger">Inactive</span>
                <?php endif; ?>
            </td>
            <td>
                <a href="./editsupplier.php?id=<?= $supplier['SupplierID'] ?>" class="btn btn-sm btn-primary">
                    Edit
                </a>
            </td>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6" style="text-align:center; padding:15px;">No suppliers found.</td>
    </tr>
<?php endif; ?>
</tbody>

                </table>
            </div>
        </div>

<!-- ✅ (C) GOODS RECEIPT MODULE -->
<div id="goodsTab" class="tab-pane" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3>Goods Receipt Note</h3>
            <div class="header-actions d-flex gap-2">
                <a href="./reports.php" class="btn btn-sm btn-primary">
                    Generate Report
                </a>
            </div>
        </div>

        <div class="card-body table-responsive">
            <?php if (!empty($display_batches)): ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Batch #</th>
                            <th>Supplier</th>
                            <th>Date Received</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($display_batches as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['PONumber']) ?></td>
                                <td><?= htmlspecialchars($item['Supplier']) ?></td>
                                <td><?= htmlspecialchars($item['ReceiptDate']) ?></td>
                                <td>
                                    <a href="./goodreceipts.php?batch=<?= urlencode($item['PONumber']) ?>" 
                                       class="btn btn-sm btn-primary">
                                       View Receipt
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="text-align:center; padding:20px;">No Goods Receipts Found.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

        <!-- ✅ (D) RECEIVING ORDERS MODULE + QC SECTION -->
       <div id="receivingTab" class="tab-pane" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3>Receiving Orders (Pending Receipt)</h3>
            <div class="header-actions d-flex gap-2">
                </div>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
<thead>
    <tr>
        <th>Batch #</th>
        <th>Supplier</th>
        <th>Order Date</th>
        <th>Order Time</th> <!-- ✅ New Column -->
        <th>Action</th>
    </tr>
</thead>

                <tbody>
                    <?php if (!empty($receiving_orders)): ?>
                        <?php foreach ($receiving_orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['Batch#'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($order['SupplierName'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($order['OrderedDate'] ?? 'N/A') ?></td> 
                            <td>
    <?php 
        $orderDT = $order['OrderedDate'] ?? '';
        echo $orderDT ? date('Y-m-d', strtotime($orderDT)) : 'N/A';
    ?>
</td>

<td>
    <?php 
        echo $orderDT ? date('g:i A', strtotime($orderDT)) : 'N/A';
    ?>
</td>

                            <td> 
                                <a href="./qualitychecking.php?batch=<?= urlencode($order['Batch#'] ?? '') ?>" class="btn btn-sm btn-primary">
                                    Mark As Received
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 20px;">
                                🎉 No Pending Receiving Orders
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

        <!-- ✅ (E) RETURNS MODULE -->
        <!-- ✅ (E) RETURNS MODULE -->
<div id="returnsTab" class="tab-pane" style="display:none;">
    <div class="card">
        <div class="card-header">
            <h3>Returns (Pending for Re-Order)</h3>
        </div>

        <div class="card-body table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Total</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>

                <tbody>
                    <?php if (!empty($returns_orders)): ?>
                        <?php foreach ($returns_orders as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['BatchNo']) ?></td>
                            <td><?= htmlspecialchars($row['Brand']) ?></td>
                            <td><?= htmlspecialchars($row['Model']) ?></td>
                            <td><?= htmlspecialchars($row['Qty']) ?></td>
                            <td>₱<?= number_format($row['Cost'], 2) ?></td>
                            <td>₱<?= number_format($row['Qty'] * $row['Cost'], 2) ?></td>

                            <td style="text-align:center;">
                                <a href="index.php?return_id=<?= $row['ReturnID'] ?>"
                                   onclick="return confirm('Are you sure you want to return & re-order this item?');"
                                   class="btn btn-sm btn-primary">
                                   Return
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding:16px;">
                                ✅ No pending returns.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

        
<div id="history" class="tab-pane">
  <div class="card">
    <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
      <h3 style="margin:0;">Receiving Transactions</h3>
      <input type="text" id="searchInput" placeholder="Search transactions..." 
             style="padding:8px 12px; width:250px; border:1px solid #ccc; border-radius:4px;">
    </div>

    <div class="card-body table-responsive" id="transactionContainer">
      <?php
     if (!empty($history_transactions)) {
    echo "
    <table class='table transactionTable' 
           style='border-collapse:collapse; width:100%; font-size:14px;'>
        <thead style='background:#e9ecef;'>
            <tr>
                <th>Batch #</th>
                <th>Brand & Model</th>

                <th style='text-align:center;'>Received</th>
                <th style='text-align:center;'>Passed</th>
                <th style='text-align:center;'>Failed</th>

                <th colspan='4' style='text-align:center; background:#dfe6f1;'>Cost Breakdown (₱)</th>

                <th style='text-align:center;'>Order Date</th>
                <th style='text-align:center;'>Arrival Date</th>
            </tr>
            <tr style='background:#dfe6f1; font-size:13px;'>
                <th colspan='3'></th><th colspan='2'></th>
                 <th style='text-align:right;'>Per Item</th>
                <th style='text-align:right;'>Passed</th>
                <th style='text-align:right;'>Failed</th>
                <th style='text-align:right;'>Total</th>
                <th colspan='2'></th>
            </tr>
        </thead>
        <tbody>
    ";

    foreach ($history_transactions as $transaction) {
        echo "
        <tr class='transaction-row' style='border-bottom: 1px solid #ddd;'>
            <td>" . htmlspecialchars($transaction['BatchNo']) . "</td>
            <td>" . htmlspecialchars($transaction['Brand']) . " - " . htmlspecialchars($transaction['Model']) . "</td>

            <td style='text-align:center;'>" . number_format($transaction['Received']) . "</td>
            <td style='text-align:center; color:#28a745; font-weight:bold;'>" . number_format($transaction['Passed']) . "</td>
            <td style='text-align:center; color:#dc3545; font-weight:bold;'>" . number_format($transaction['Failed']) . "</td>
             <td style='text-align:right;'>₱" . number_format($transaction['UnitCost'], 2) . "</td>
            <td style='text-align:right;'>₱" . number_format($transaction['PassedCost'], 2) . "</td>
            <td style='text-align:right; color:#dc3545;'>₱" . number_format($transaction['FailedCost'], 2) . "</td>
           <td style='text-align:right; font-weight:bold;'>₱" . number_format($transaction['Total'], 2) . "</td>

            <td style='text-align:center;'>" . htmlspecialchars($transaction['OrderedDate']) . "</td>
            <td style='text-align:center;'>" . htmlspecialchars($transaction['ArrivalDate']) . "</td>
        </tr>
        ";
    }

    echo "
        </tbody>
    </table>";
} else {
    echo "<p style='text-align:center; padding: 20px;'>No transaction history found.</p>";
}

      ?>
    </div>
  </div>
</div>

 <div id="paymentsTab" class="tab-pane" style="display:none;">
    <div class="card">
        <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0;">Payment Transactions</h3>

            <div class="d-flex" style="gap:10px;">
                <!-- ✅ Status Filter -->
                <form method="GET" id="payFilterForm">
    <input type="hidden" name="tab" value="paymentsTab">

                    <select name="pay_status" class="form-control" onchange="document.getElementById('payFilterForm').submit()">
                        <option value="All" <?= ($payment_status_filter == 'All') ? 'selected' : '' ?>>All</option>
                        <option value="Paid" <?= ($payment_status_filter == 'Paid') ? 'selected' : '' ?>>Paid</option>
                        <option value="Request to Pay" <?= ($payment_status_filter == 'Request to Pay') ? 'selected' : '' ?>>Request to Pay</option>
                    </select>
                </form>

                <!-- ✅ Searchbar -->
                <input type="text" id="paymentSearch"
                       placeholder="Search transactions..."
                       style="padding:8px 12px; width:230px; border:1px solid #ccc; border-radius:4px;">
            </div>
        </div>

        <div class="card-body table-responsive">
            <?php if (!empty($payment_history)): ?>
            <table class="table table-hover" id="paymentTable">
                <thead>
                    <tr>
                        <th>Batch #</th>
                        <th>Brand</th>
                        <th>Model</th>
                        <th class="text-center">Received</th>
                        <th class="text-center">Passed</th>
                        <th class="text-center">Failed</th>
                        <th class="text-right">Unit Cost</th>
                        <th class="text-right">Line Total</th>
                        <th>Arrival Date</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payment_history as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['PONumber']) ?></td>
                        <td><?= htmlspecialchars($p['Brand']) ?></td>
                        <td><?= htmlspecialchars($p['Model']) ?></td>
                        <td class="text-center"><?= number_format($p['ReceivedQty']) ?></td>
                        <td class="text-center" style="color:green;font-weight:bold;">
                            <?= number_format($p['PassedQty']) ?>
                        </td>
                        <td class="text-center" style="color:red;font-weight:bold;">
                            <?= number_format($p['FailedQty']) ?>
                        </td>
                        <td class="text-right">₱<?= number_format($p['UnitCost'], 2) ?></td>
                        <td class="text-right font-bold">₱<?= number_format($p['LineTotal'], 2) ?></td>
                        <td><?= htmlspecialchars($p['ArrivalDate']) ?></td>

                        <td>
                            <?php if ($p['PaymentStatus'] == 'Paid'): ?>
                                <span class="badge badge-success">Paid</span>
                            <?php else: ?>
                                <span class="badge badge-warning">Request To Pay</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <p style="text-align:center; padding:20px;">
                No payment transactions found.
            </p>
            <?php endif; ?>
        </div>
    </div>
</div>



<script>
document.addEventListener('DOMContentLoaded', () => {
    // 🔍 Search Functionality
    const searchInput = document.getElementById('searchInput');
    const transactionTables = document.querySelectorAll('.transactionTable');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const filter = this.value.toLowerCase();

            document.querySelectorAll('.transaction-row').forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(filter) ? '' : 'none';
            });

            // Hide section header if all rows are hidden
            transactionTables.forEach(table => {
                const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
                table.previousElementSibling.style.display = visibleRows.length > 0 ? 'block' : 'none';
                table.style.display = visibleRows.length > 0 ? 'table' : 'none';
            });
        });
    }
});
</script>
<script>
document.getElementById('paymentSearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#paymentsTab table tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});
</script>
<script>
    document.getElementById('paymentSearch').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#paymentTable tbody tr').forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(filter) ? '' : 'none';
    });
});

</script>
<script>
document.querySelectorAll('.nav-link').forEach(tab => {
    tab.addEventListener('click', function(e) {
        e.preventDefault();
        const tabName = this.getAttribute('data-tab');
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabName);
        window.location.href = url.toString();
    });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const url = new URL(window.location.href);
    const activeTab = url.searchParams.get('tab') || 'fulfillmentTab';

    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.getElementById(activeTab).style.display = 'block';

    document.querySelectorAll('.nav-link').forEach(n => n.classList.remove('active'));
    document.querySelector(`[data-tab="${activeTab}"]`).classList.add('active');
});
</script>

</div>




    </main>
</div>

</body>
</html>
<?php
// Close database connection
$conn->close();
?>