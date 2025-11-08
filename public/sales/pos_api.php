<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../../includes/core_functions.php';
require_once __DIR__ . '/../../includes/db_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

function respond($ok, $data = [], $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode(['success' => $ok, 'data' => $data]);
    exit;
}

try {
    if ($method === 'GET' && $action === 'products') {
        $storeId = $_SESSION['store_id'] ?? null;
        // Fetch active products with inventory quantity (if available)
        $sql = "SELECT p.ProductID AS id,
                       CONCAT(p.Brand, ' ', p.Model) AS name,
                       p.SellingPrice AS price,
                       COALESCE(i.Quantity, 0) AS stock,
                       p.Brand AS category
                FROM Products p
                LEFT JOIN Inventory i ON i.ProductID = p.ProductID";
        $params = [];
        if ($storeId) {
            $sql .= " AND i.StoreID = ?";
            $params[] = $storeId;
        }
        $sql .= " WHERE p.Status = 'Active' ORDER BY p.Brand, p.Model";

        // Adjust WHERE placement if store filter used
        if ($storeId) {
            // Previous line appended AND i.StoreID = ? before WHERE; fix by rewriting
            $sql = "SELECT p.ProductID AS id,
                           CONCAT(p.Brand, ' ', p.Model) AS name,
                           p.SellingPrice AS price,
                           COALESCE(i.Quantity, 0) AS stock,
                           p.Brand AS category
                    FROM Products p
                    LEFT JOIN Inventory i ON i.ProductID = p.ProductID AND i.StoreID = ?
                    WHERE p.Status = 'Active'
                    ORDER BY p.Brand, p.Model";
            $products = dbFetchAll($sql, [$storeId]);
        } else {
            $products = dbFetchAll($sql);
        }

        respond(true, ['products' => $products]);
    }

    if ($method === 'GET' && $action === 'customer') {
        $q = trim($_GET['q'] ?? '');
        $byId = intval($_GET['id'] ?? 0);
        if ($byId > 0) {
            $c = dbFetchOne("SELECT CustomerID AS id, MemberNumber, CONCAT(FirstName,' ',COALESCE(LastName,'')) AS name, LoyaltyPoints FROM customers WHERE CustomerID=?", [$byId]);
            respond(true, ['customer' => $c]);
        }
        if ($q === '') respond(false, ['message' => 'Missing q'], 400);
        // Search by member number or name fragment
        $c = dbFetchOne("SELECT CustomerID AS id, MemberNumber, CONCAT(FirstName,' ',COALESCE(LastName,'')) AS name, LoyaltyPoints FROM customers WHERE MemberNumber=?", [$q]);
        if (!$c) {
            $like = '%' . $q . '%';
            $row = dbFetchAll("SELECT CustomerID AS id, MemberNumber, CONCAT(FirstName,' ',COALESCE(LastName,'')) AS name, LoyaltyPoints FROM customers WHERE FirstName LIKE ? OR LastName LIKE ? LIMIT 5", [$like, $like]);
            respond(true, ['matches' => $row]);
        }
        respond(true, ['customer' => $c]);
    }

    if ($method === 'POST' && $action === 'checkout') {
        $body = file_get_contents('php://input');
        $payload = json_decode($body, true);
        if (!is_array($payload)) {
            respond(false, ['message' => 'Invalid JSON body'], 400);
        }

        $items = $payload['items'] ?? [];
        $paymentMethod = $payload['payment_method'] ?? 'Cash';
        $discount = floatval($payload['discount'] ?? 0);
        $pointsUsed = intval($payload['points_used'] ?? 0);
        $paymentStatus = $payload['payment_status'] ?? null;
        $amountPaid = floatval($payload['amount_paid'] ?? 0);
        $customerId = !empty($payload['customer_id']) ? intval($payload['customer_id']) : null;
        $storeId = !empty($payload['store_id']) ? intval($payload['store_id']) : ($_SESSION['store_id'] ?? null);

        if (!$storeId) {
            respond(false, ['message' => 'Missing store_id'], 400);
        }
        if (!is_array($items) || count($items) === 0) {
            respond(false, ['message' => 'Cart is empty'], 400);
        }

        // Normalize items to expected structure for processSale()
        $saleItems = [];
        foreach ($items as $it) {
            $pid = intval($it['id'] ?? $it['product_id'] ?? 0);
            $qty = floatval($it['quantity'] ?? 0);
            $price = floatval($it['price'] ?? 0);
            if ($pid <= 0 || $qty <= 0 || $price < 0) {
                continue;
            }
            $saleItems[] = [
                'product_id' => $pid,
                'quantity'   => $qty,
                'price'      => $price,
            ];
        }
        if (count($saleItems) === 0) {
            respond(false, ['message' => 'No valid items'], 400);
        }

        // Process via core function (handles inventory, GL, AR)
        $saleId = processSale($customerId, $storeId, $saleItems, $paymentMethod, $discount, $pointsUsed, $paymentStatus, $amountPaid);
        respond(true, ['sale_id' => $saleId]);
    }

    respond(false, ['message' => 'Unsupported route'], 404);
} catch (Exception $e) {
    logError('POS API error', ['error' => $e->getMessage()]);
    respond(false, ['message' => $e->getMessage()], 500);
}
