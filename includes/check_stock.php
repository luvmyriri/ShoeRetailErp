<?php
// includes/check_stock.php
// Reusable helper that validates stock in `inventory` table per store.
// Usage: $res = checkStockAvailability($line_items, $storeId);
// line_items = array of ['productId'=>int, 'quantity'=>float]

if (! defined('CHECK_STOCK_INCLUDED')) {
    define('CHECK_STOCK_INCLUDED', true);

    // load DB config
    require_once __DIR__ . '/../config/database.php';

    function get_db_conn_cs() {
        if (function_exists('getDB')) {
            try { return getDB(); } catch (Exception $e) {}
        }
        global $pdo, $db;
        if (isset($pdo) && $pdo instanceof PDO) return $pdo;
        if (isset($db) && $db) return $db;

        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASS')) {
            $dsn = "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4";
            try {
                $pdo_local = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                return $pdo_local;
            } catch (Exception $e) {
                error_log("[check_stock] PDO connect failed: " . $e->getMessage());
            }
        }
        throw new Exception("No DB connection available in check_stock helper.");
    }

    function db_fetch_one_cs($sql, $params = []) {
        $db = get_db_conn_cs();
        if ($db instanceof PDO) {
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } else {
            if (method_exists($db, 'fetchOne')) return $db->fetchOne($sql, $params);
            if (method_exists($db, 'fetch')) return $db->fetch($sql, $params);
            throw new Exception("DB wrapper does not provide fetch functionality.");
        }
    }

    /**
     * Check stock availability for given items in a store.
     *
     * @param array $line_items  Each item: ['productId'=>int, 'quantity'=>float]
     * @param int $storeId
     * @return array ['success'=>bool, 'message'=>string, 'detail'=>array|null]
     */
    function checkStockAvailability(array $line_items, int $storeId): array {
        if (empty($line_items)) {
            return ['success' => false, 'message' => 'No items provided for stock check.'];
        }
        try {
            foreach ($line_items as $it) {
                $productId = intval($it['productId'] ?? 0);
                $quantity = floatval($it['quantity'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) {
                    return ['success' => false, 'message' => "Invalid product or quantity provided.", 'detail' => $it];
                }

                // Query inventory for the store
                $sql = "SELECT i.Quantity AS qty, p.Brand, p.Model, p.Size, p.SKU
                        FROM inventory i
                        LEFT JOIN products p ON p.ProductID = i.ProductID
                        WHERE i.ProductID = ? AND i.StoreID = ?
                        LIMIT 1";
                $row = db_fetch_one_cs($sql, [$productId, $storeId]);

                if (! $row) {
                    // No inventory record for product in this store -> treat as zero available
                    return [
                        'success' => false,
                        'message' => "Product (ID: {$productId}) does not have inventory record for store #{$storeId}.",
                        'detail' => ['productId' => $productId, 'available' => 0, 'requested' => $quantity]
                    ];
                }

                $available = floatval($row['qty']);
                if ($quantity > $available) {
                    $label = trim(($row['Brand'] ?? '') . ' ' . ($row['Model'] ?? ''));
                    $sizeText = (isset($row['Size']) ? " Size {$row['Size']}" : '');
                    $skuText = isset($row['SKU']) ? " ({$row['SKU']})" : '';
                    $msg = "Not enough stock for {$label}{$sizeText}{$skuText} — Available: {$available}, Ordered: {$quantity}.";
                    return [
                        'success' => false,
                        'message' => $msg,
                        'detail' => ['productId' => $productId, 'available' => $available, 'requested' => $quantity]
                    ];
                }
            }

            return ['success' => true, 'message' => 'All items available'];
        } catch (Exception $e) {
            error_log("[check_stock] exception: " . $e->getMessage());
            return ['success' => false, 'message' => 'Stock check failed: ' . $e->getMessage()];
        }
    }
}
