<?php
include './Connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Invalid request.");
}

// Get POST data safely
$batch = $_POST['batch'] ?? '';
$brand = $_POST['brand'] ?? '';
$model = $_POST['model'] ?? '';
$quantity = floatval($_POST['quantity'] ?? 0);
$price_per_item = floatval($_POST['price_per_item'] ?? 0);
$order_date = !empty($_POST['order_date']) ? $_POST['order_date'] : date('Y-m-d');
$arrival_date = !empty($_POST['arrival_date']) ? $_POST['arrival_date'] : date('Y-m-d');
$qty_received = intval($_POST['quantity_received'] ?? 0);
$qty_passed = intval($_POST['quantity_passed'] ?? 0);
$description = htmlspecialchars($_POST['description'] ?? '');

$qty_failed = $qty_received - $qty_passed;

// ✅ Fetch PurchaseOrderID and SupplierID from purchaseorders
$id_sql = "SELECT PurchaseOrderID, SupplierID FROM purchaseorders WHERE BatchNo = ?";
$id_stmt = $conn->prepare($id_sql);
$id_stmt->bind_param("s", $batch);
$id_stmt->execute();
$id_result = $id_stmt->get_result()->fetch_assoc();
$id_stmt->close();

$poid = $id_result['PurchaseOrderID'];
$sid = $id_result['SupplierID'];

// ✅ Quantity validation
if ($qty_received > $quantity || $qty_passed > $qty_received || $qty_failed < 0) {
    echo "<script>alert('❌ Invalid Quantity Input!'); history.back();</script>";
    exit();
}

// ✅ STATUS logic
$status = ($qty_failed > 0) ? "Partial" : "Received";

// ✅ Handle Image Proof Upload
$image_path = NULL;
if (!empty($_FILES['image_proof']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['image_proof']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        echo "<script>alert('❌ Invalid file type! Allowed: JPG, JPEG, PNG'); history.back();</script>";
        exit;
    }

    $upload_dir = "uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

    $unique_name = uniqid('proof_') . "." . $ext;
    $image_path = $upload_dir . $unique_name;
    move_uploaded_file($_FILES['image_proof']['tmp_name'], $image_path);
}

// ✅ Update purchaseorders status
$update_sql = "UPDATE purchaseorders SET Status=? WHERE BatchNo=?";
$update_stmt = $conn->prepare($update_sql);
$update_stmt->bind_param("ss", $status, $batch);
$update_stmt->execute();
$update_stmt->close();

// ✅ Insert into transaction_history_precurement table
$history_sql = "INSERT INTO transaction_history_precurement
(PurchaseOrderID, SupplierID, BatchNo, Brand, Model, Received, Passed, Failed, UnitCost, OrderedDate, ArrivalDate, Description, ImageProof)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$history_stmt = $conn->prepare($history_sql);
$history_stmt->bind_param(
    "iisssiiidssss",
    $poid, $sid,
    $batch, $brand, $model,
    $qty_received, $qty_passed, $qty_failed,
    $price_per_item, $order_date, $arrival_date,
    $description, $image_path
);
$history_stmt->execute();
$history_stmt->close();

// ✅ Insert Payment Record into Accounts Payable
$amount_due = $qty_passed * $price_per_item;
$due_date = date('Y-m-d', strtotime($arrival_date . ' + 30 days')); // Default 30 days NET

$ap_sql = "INSERT INTO accountspayable 
           (PurchaseOrderID, SupplierID, AmountDue, DueDate, PaymentStatus, PaidAmount) 
           VALUES (?, ?, ?, ?, 'Pending', 0.00)";
$ap_stmt = $conn->prepare($ap_sql);
$ap_stmt->bind_param("iids", $poid, $sid, $amount_due, $due_date);
$ap_stmt->execute();
$ap_stmt->close();


// ✅ Add Passed qty into inventory
$product_id = 1; 
$store_id = 1;
$inventory_sql = "INSERT INTO inventory (ProductID, StoreID, Quantity)
                  VALUES (?, ?, ?) 
                  ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity)";
$inventory_stmt = $conn->prepare($inventory_sql);
$inventory_stmt->bind_param("iii", $product_id, $store_id, $qty_passed);
$inventory_stmt->execute();
$inventory_stmt->close();

// ✅ Insert Failed items into returns table
if ($qty_failed > 0) {
    $returns_sql = "INSERT INTO returns (BatchNo, Brand, Model, Qty, Cost)
                    VALUES (?, ?, ?, ?, ?)";
    $returns_stmt = $conn->prepare($returns_sql);
    $returns_stmt->bind_param("sssii", $batch, $brand, $model, $qty_failed, $price_per_item);
    $returns_stmt->execute();
    $returns_stmt->close();
}

$conn->close();

// ✅ Redirect Success
echo "<script>
alert('✅ Order successfully recorded! Status: $status');
window.location.href = './index.php';
</script>";
?>
