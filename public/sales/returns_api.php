<?php
session_start();
require_once __DIR__ . '/../../includes/db_helper.php';
require_once __DIR__ . '/../../includes/core_functions.php';

$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

function respond($ok, $data = [], $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => $ok, 'data' => $data]);
    exit;
}

try {
    if ($method !== 'POST') {
        respond(false, ['message' => 'Method not allowed'], 405);
    }

    $body = file_get_contents('php://input');
    $payload = json_decode($body, true);
    if (!is_array($payload)) respond(false, ['message' => 'Invalid JSON'], 400);

    $saleId = intval($payload['sale_id'] ?? 0);
    $reason = trim($payload['reason'] ?? 'Customer Return');
    $refundMethod = $payload['refund_method'] ?? 'Cash';
    $restockingRate = floatval($payload['restocking_rate'] ?? 0.05);

    if ($saleId <= 0) respond(false, ['message' => 'Missing sale_id'], 400);

    // Load sale + items
    $sale = dbFetchOne("SELECT * FROM sales WHERE SaleID = ?", [$saleId]);
    if (!$sale) respond(false, ['message' => 'Sale not found'], 404);
    $storeId = $sale['StoreID'];
    $customerId = $sale['CustomerID'];

    $saleItems = dbFetchAll("SELECT ProductID, Quantity, SalesUnitID, QuantityBase, UnitPrice, Subtotal FROM saledetails WHERE SaleID = ?", [$saleId]);
    if (empty($saleItems)) respond(false, ['message' => 'No sale items to return'], 400);

    $payloadItems = $payload['items'] ?? null; // optional [{product_id, quantity}]
    $itemsToReturn = [];
    if (is_array($payloadItems) && count($payloadItems) > 0) {
        // Build map by product for quick lookup of unit price and unit ids
        $byProd = [];
        foreach ($saleItems as $it) { $byProd[$it['ProductID']] = $it; }
        foreach ($payloadItems as $req) {
            $pid = intval($req['product_id'] ?? 0);
            $qty = floatval($req['quantity'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || !isset($byProd[$pid])) continue;
            $orig = $byProd[$pid];
            // Cap by original sold quantity
            $qty = min($qty, floatval($orig['Quantity']));
            $qtyBase = ($orig['Quantity'] > 0) ? ($orig['QuantityBase'] * ($qty / $orig['Quantity'])) : 0;
            $itemsToReturn[] = [
                'ProductID' => $pid,
                'Quantity' => $qty,
                'SalesUnitID' => $orig['SalesUnitID'],
                'QuantityBase' => $qtyBase,
                'UnitPrice' => $orig['UnitPrice'],
                'Subtotal' => $orig['UnitPrice'] * $qty,
            ];
        }
    } else {
        // Full return of all items
        $itemsToReturn = $saleItems;
    }

    // Compute totals
    $refundAmount = 0.0;
    foreach ($itemsToReturn as $it) { $refundAmount += floatval($it['Subtotal']); }
    $restockingFee = $refundAmount * $restockingRate;
    $netRefund = $refundAmount - $restockingFee;

    // Update inventory back and record stock movements
    foreach ($itemsToReturn as $it) {
        dbExecute("INSERT INTO inventory (ProductID, StoreID, Quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE Quantity = Quantity + ?",
            [$it['ProductID'], $storeId, $it['QuantityBase'], $it['QuantityBase']]);
        dbExecute("INSERT INTO stockmovements (ProductID, StoreID, MovementType, Quantity, UnitID, QuantityBase, ReferenceID, ReferenceType, Notes, CreatedBy) VALUES (?, ?, 'IN', ?, ?, ?, ?, 'Return', ?, ?)",
            [$it['ProductID'], $storeId, $it['Quantity'], $it['SalesUnitID'], $it['QuantityBase'], $saleId, $reason, $_SESSION['username'] ?? 'System']);
    }

    // Insert returns row (aggregated)
    $returnId = dbInsert("INSERT INTO returns (SaleID, CustomerID, StoreID, Reason, RefundMethod, RefundAmount, RestockingFee, NetRefund, Status, ProcessedBy, Notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Completed', ?, ?)",
        [$saleId, $customerId, $storeId, $reason, $refundMethod, $refundAmount, $restockingFee, $netRefund, $_SESSION['user_id'] ?? null, $reason]);

    // Mark sale status and GL entries
    dbUpdate("UPDATE sales SET PaymentStatus = 'Refunded' WHERE SaleID = ?", [$saleId]);
    recordGeneralLedger('Expense', 'Sales Returns', 'Customer refund', $netRefund, 0, $returnId, 'Sale', $storeId);

    respond(true, ['return_id' => $returnId, 'refund' => $netRefund]);
} catch (Exception $e) {
    logError('Return processing error', ['error' => $e->getMessage()]);
    respond(false, ['message' => $e->getMessage()], 500);
}
