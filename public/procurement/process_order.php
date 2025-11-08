<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: /ShoeRetailErp/login.php'); exit; }
require_once '../../config/database.php';
require_once '../../includes/core_functions.php';
require_once '../../includes/workflow_handlers.php';

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

// Fetch PurchaseOrderID and SupplierID
$id_result = dbFetchOne("SELECT PurchaseOrderID, SupplierID FROM PurchaseOrders WHERE BatchNo = ?", [$batch]);
if (!$id_result) {
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    include '../includes/modal.php';
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Error', 'Purchase Order not found!', 'error', function() { history.back(); }); });</script></body></html>";
    exit;
}
$poid = $id_result['PurchaseOrderID'];
$sid = $id_result['SupplierID'];

// Quantity validation
if ($qty_received > $quantity || $qty_passed > $qty_received || $qty_failed < 0) {
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    include '../includes/modal.php';
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Error', 'Invalid Quantity Input!', 'error', function() { history.back(); }); });</script></body></html>";
    exit;
}

$status = ($qty_failed > 0) ? "Partial" : "Received";

// Handle Image Proof Upload
$image_path = null;
if (!empty($_FILES['image_proof']['name'])) {
    $allowed = ['jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['image_proof']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
        include '../includes/modal.php';
        echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Error', 'Invalid file type! Allowed: JPG, JPEG, PNG', 'error', function() { history.back(); }); });</script></body></html>";
        exit;
    }
    $upload_dir = __DIR__ . "/uploads/";
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
    $unique_name = uniqid('proof_') . "." . $ext;
    $image_path = "uploads/" . $unique_name;
    move_uploaded_file($_FILES['image_proof']['tmp_name'], $upload_dir . $unique_name);
}

try {
    getDB()->beginTransaction();
    
    // Update purchase order status
    dbUpdate("UPDATE PurchaseOrders SET Status = ? WHERE BatchNo = ?", [$status, $batch]);
    
    // Insert into transaction history
    dbInsert(
        "INSERT INTO transaction_history_precurement 
         (PurchaseOrderID, SupplierID, BatchNo, Brand, Model, Received, Passed, Failed, UnitCost, OrderedDate, ArrivalDate, Description, ImageProof)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
        [$poid, $sid, $batch, $brand, $model, $qty_received, $qty_passed, $qty_failed, $price_per_item, $order_date, $arrival_date, $description, $image_path]
    );
    
    // Create AP entry
    $amount_due = $qty_passed * $price_per_item;
    $due_date = date('Y-m-d', strtotime($arrival_date . ' + 30 days'));
    dbInsert(
        "INSERT INTO AccountsPayable (PurchaseOrderID, SupplierID, AmountDue, DueDate, PaymentStatus, PaidAmount) 
         VALUES (?, ?, ?, ?, 'Pending', 0.00)",
        [$poid, $sid, $amount_due, $due_date]
    );
    
    // Update inventory - Get ProductID from Brand/Model
    $product = dbFetchOne("SELECT ProductID FROM Products WHERE Brand = ? AND Model = ? LIMIT 1", [$brand, $model]);
    if ($product) {
        $store_id = $_SESSION['store_id'] ?? 1;
        dbExecute(
            "INSERT INTO Inventory (ProductID, StoreID, Quantity) VALUES (?, ?, ?) 
             ON DUPLICATE KEY UPDATE Quantity = Quantity + VALUES(Quantity)",
            [$product['ProductID'], $store_id, $qty_passed]
        );
        // Record stock movement
        dbInsert(
            "INSERT INTO StockMovements (ProductID, StoreID, MovementType, Quantity, ReferenceType, Notes, CreatedBy)
             VALUES (?, ?, 'IN', ?, 'Purchase Receipt', ?, ?)",
            [$product['ProductID'], $store_id, $qty_passed, "Batch {$batch} QC Passed", $_SESSION['username'] ?? 'System']
        );
    }
    
    // Insert failed items into returns table
    if ($qty_failed > 0) {
        dbInsert(
            "INSERT INTO returns (BatchNo, Brand, Model, Qty, Cost) VALUES (?, ?, ?, ?, ?)",
            [$batch, $brand, $model, $qty_failed, $price_per_item]
        );
    }
    
    getDB()->commit();
    logInfo('Purchase order processed', ['batch' => $batch, 'po_id' => $poid, 'received' => $qty_received, 'passed' => $qty_passed, 'failed' => $qty_failed]);
} catch (Exception $e) {
    getDB()->rollback();
    logError('Failed to process purchase order', ['error' => $e->getMessage(), 'batch' => $batch]);
    $errorMsg = addslashes($e->getMessage());
    echo "<!DOCTYPE html><html><head><title>Error</title></head><body>";
    include '../includes/modal.php';
    echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Error', 'Error processing order: {$errorMsg}', 'error', function() { history.back(); }); });</script></body></html>";
    exit;
}

// ✅ Redirect Success
echo "<!DOCTYPE html><html><head><title>Success</title></head><body>";
include '../includes/modal.php';
echo "<script>document.addEventListener('DOMContentLoaded', function() { showModal('Success', 'Order successfully recorded! Status: {$status}', 'success', function() { window.location.href = './index.php'; }); });</script></body></html>";
?>
