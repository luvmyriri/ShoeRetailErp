<?php
session_start();
require_once __DIR__ . '/../../includes/db_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
header('Content-Type: application/json');

function respond($ok, $data = [], $status = 200) {
    http_response_code($status);
    echo json_encode(['success' => $ok, 'data' => $data]);
    exit;
}

try {
    if ($method !== 'POST') respond(false, ['message' => 'Method not allowed'], 405);

    $body = file_get_contents('php://input');
    $payload = json_decode($body, true);
    if (!is_array($payload)) respond(false, ['message' => 'Invalid JSON'], 400);

    $saleId = intval($payload['sale_id'] ?? 0);
    $amount = floatval($payload['amount'] ?? 0);
    $methodName = $payload['method'] ?? 'Cash';
    $storeId = $_SESSION['store_id'] ?? null;
    $userId = $_SESSION['user_id'] ?? null;

    if ($saleId <= 0 || $amount <= 0) respond(false, ['message' => 'Missing sale_id or amount'], 400);
    if (!$storeId) respond(false, ['message' => 'Missing store in session'], 400);

    // Call stored procedure RecordCustomerPayment(sale_id, amount, method, store_id, received_by)
    $stmt1 = getDB()->prepare("CALL RecordCustomerPayment(?, ?, ?, ?, ?)");
    if (!$stmt1) throw new Exception('Cannot prepare statement');
    $stmt1->bind_param('idssi', $saleId, $amount, $methodName, $storeId, $userId);
    $stmt1->execute();
    $stmt1->close();

    respond(true, ['message' => 'Payment recorded']);
} catch (Exception $e) {
    logError('AR payment error', ['error' => $e->getMessage()]);
    respond(false, ['message' => $e->getMessage()], 500);
}
