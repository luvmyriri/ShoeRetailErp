<?php
/**
 * public/sales/pos.php
 *
 * Integrated POS with:
 * - includes/check_stock.php (inventory per store)
 * - embedded CSS (user-provided)
 * - in-page receipt modal auto-prints after successful checkout
 *
 * Save to: public/sales/pos.php
 */

session_start();
require __DIR__ . '/../../config/database.php';
require __DIR__ . '/../../config/company.php';

// Allow branch switch via GET param
if (isset($_GET['store_id'])) {
    $_SESSION['store_id'] = max(1, (int)$_GET['store_id']);
}

// -----------------------------------------------------------------------------
// DB helper functions (support both PDO and common wrapper)
function get_db_conn() {
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
            error_log("[POS] PDO connect failed: " . $e->getMessage());
        }
    }
    throw new Exception("No DB connection available. Ensure config/database.php provides PDO or getDB().");
}

function db_fetch_all($sql, $params = []) {
    $db = get_db_conn();
    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        if (method_exists($db, 'fetchAll')) return $db->fetchAll($sql, $params);
        if (method_exists($db, 'select')) return $db->select($sql, $params);
        if (method_exists($db, 'query')) return $db->query($sql, $params) ?: [];
        throw new Exception("DB wrapper has no fetchAll/select/query");
    }
}

function db_fetch_one($sql, $params = []) {
    $rows = db_fetch_all($sql, $params);
    return count($rows) ? $rows[0] : null;
}

function db_execute($sql, $params = []) {
    $db = get_db_conn();
    if ($db instanceof PDO) {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } else {
        if (method_exists($db, 'execute')) return $db->execute($sql, $params);
        if (method_exists($db, 'query')) return $db->query($sql, $params);
        if (method_exists($db, 'run')) return $db->run($sql, $params);
        throw new Exception("DB wrapper missing execute/query/run");
    }
}

function db_begin() {
    $db = get_db_conn();
    if ($db instanceof PDO) return $db->beginTransaction();
    if (method_exists($db, 'beginTransaction')) return $db->beginTransaction();
    if (method_exists($db, 'startTransaction')) return $db->startTransaction();
    return false;
}
function db_commit() {
    $db = get_db_conn();
    if ($db instanceof PDO) return $db->commit();
    if (method_exists($db, 'commit')) return $db->commit();
    if (method_exists($db, 'endTransaction')) return $db->endTransaction();
    return false;
}
function db_rollback() {
    $db = get_db_conn();
    if ($db instanceof PDO) return $db->rollBack();
    if (method_exists($db, 'rollBack')) return $db->rollBack();
    if (method_exists($db, 'cancelTransaction')) return $db->cancelTransaction();
    return false;
}

// include check_stock helper
require_once __DIR__ . '/../../includes/check_stock.php';

// -----------------------------------------------------------------------------
// Process sale (POST)
$openReceipt = false;
$receiptSale = null;
$receiptLines = [];
$invoiceNumber = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'process_sale') {
    try {
        $storeId = intval($_POST['storeId'] ?? 0);
        $customerId = intval($_POST['customerId'] ?? 0) ?: null;
        $paymentMethod = $_POST['paymentMethod'] ?? 'Cash';
        $discountAmount = floatval($_POST['discountAmount'] ?? 0);
        $pointsUsed = intval($_POST['pointsUsed'] ?? 0);
        $salespersonId = $_SESSION['employee']['EmployeeID'] ?? $_SESSION['user_id'] ?? null;
        $employeeName = trim(($_SESSION['employee']['FirstName'] ?? '') . ' ' . ($_SESSION['employee']['LastName'] ?? ''));

        $items_json = $_POST['sale_items_json'] ?? '[]';
        $items = json_decode($items_json, true);
        if (!is_array($items) || count($items) === 0) {
            throw new Exception("Cart is empty.");
        }

        // normalize items
        $line_items = [];
        foreach ($items as $it) {
            $pid = intval($it['productId'] ?? 0);
            $qty = floatval($it['quantity'] ?? 0);
            $unitPrice = floatval($it['unitPrice'] ?? $it['price'] ?? 0);
            if ($pid <= 0 || $qty <= 0) throw new Exception("Invalid cart item data.");
            $line_items[] = ['productId' => $pid, 'quantity' => $qty, 'unitPrice' => $unitPrice, 'subtotal' => round($qty * $unitPrice, 2)];
        }

        // 1) check stock via includes/check_stock.php
        $stockCheck = checkStockAvailability($line_items, $storeId);
        if (! $stockCheck['success']) {
            $_SESSION['flash_alert'] = ['type' => 'danger', 'msg' => $stockCheck['message']];
            header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
            exit;
        }

        // 2) compute totals (VAT on net after discount)
        $subtotal = 0.0;
        foreach ($line_items as $li) $subtotal += $li['subtotal'];
        $taxable = max(0, $subtotal - $discountAmount);
        $taxRate = defined('VAT_RATE') ? VAT_RATE : 0.12;
        $taxAmount = round($taxable * $taxRate, 2);
        $totalAmount = round($taxable + $taxAmount, 2);

        // 3) Insert transactionally (sales + saledetails + decrement inventory)
        db_begin();
        $db = get_db_conn();
        // Insert into sales
        $insertSaleSql = "INSERT INTO sales (CustomerID, StoreID, TotalAmount, TaxAmount, DiscountAmount, PointsUsed, PointsEarned, PaymentStatus, PaymentMethod, SalespersonID, SaleDate)
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $pointsEarned = 0;
        if ($db instanceof PDO) {
            $stmt = $db->prepare($insertSaleSql);
            $stmt->execute([$customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $pointsUsed, $pointsEarned, ($paymentMethod === 'Credit' ? 'Credit' : 'Paid'), $paymentMethod, $salespersonId]);
            $saleId = $db->lastInsertId();
        } else {
            if (method_exists($db, 'insert')) {
                $saleId = $db->insert($insertSaleSql, [$customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $pointsUsed, $pointsEarned, ($paymentMethod === 'Credit' ? 'Credit' : 'Paid'), $paymentMethod, $salespersonId]);
            } else {
                db_execute($insertSaleSql, [$customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $pointsUsed, $pointsEarned, ($paymentMethod === 'Credit' ? 'Credit' : 'Paid'), $paymentMethod, $salespersonId]);
                $saleId = null;
                if (method_exists($db, 'lastInsertId')) $saleId = $db->lastInsertId();
            }
        }
        if (! $saleId) throw new Exception("Failed to insert sale.");

        // Insert saledetails
        $insertDetailSql = "INSERT INTO saledetails (SaleID, ProductID, Quantity, SalesUnitID, QuantityBase, UnitPrice, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($db instanceof PDO) $stmtDetail = $db->prepare($insertDetailSql);

        foreach ($line_items as $li) {
            $qty = $li['quantity'];
            $unitPrice = $li['unitPrice'];
            $lineSubtotal = $li['subtotal'];
            if ($db instanceof PDO) {
                $stmtDetail->execute([$saleId, $li['productId'], $qty, 1, $qty, $unitPrice, $lineSubtotal]);
            } else {
                db_execute($insertDetailSql, [$saleId, $li['productId'], $qty, 1, $qty, $unitPrice, $lineSubtotal]);
            }
            // decrement inventory per store
            $updInv = "UPDATE inventory SET Quantity = GREATEST(0, Quantity - ?) WHERE ProductID = ? AND StoreID = ?";
            if ($db instanceof PDO) {
                $stmtUpd = $db->prepare($updInv);
                $stmtUpd->execute([$qty, $li['productId'], $storeId]);
            } else {
                db_execute($updInv, [$qty, $li['productId'], $storeId]);
            }
        }

        // 3.5) Create invoice (always) and AR when Credit
        $invoiceNumber = 'INV-' . date('Ymd') . '-' . str_pad($saleId, 5, '0', STR_PAD_LEFT);
        $invSql = "INSERT INTO invoices (InvoiceNumber, SaleID, CustomerID, StoreID, InvoiceDate, TotalAmount, TaxAmount, DiscountAmount, PaymentMethod, PaymentStatus, CreatedBy)
                   VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?, ?, ?)";
        $invoicePaymentStatus = ($paymentMethod === 'Credit') ? 'Credit' : 'Paid';
        $createdBy = $employeeName ?: 'System';
        if ($db instanceof PDO) {
            $stmtInv = $db->prepare($invSql);
            $stmtInv->execute([$invoiceNumber, $saleId, $customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $paymentMethod, $invoicePaymentStatus, $createdBy]);
            $invoiceId = $db->lastInsertId();
        } else {
            if (method_exists($db, 'insert')) {
                $invoiceId = $db->insert($invSql, [$invoiceNumber, $saleId, $customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $paymentMethod, $invoicePaymentStatus, $createdBy]);
            } else {
                db_execute($invSql, [$invoiceNumber, $saleId, $customerId, $storeId, $totalAmount, $taxAmount, $discountAmount, $paymentMethod, $invoicePaymentStatus, $createdBy]);
                $invoiceId = method_exists($db, 'lastInsertId') ? $db->lastInsertId() : null;
            }
        }
        // Invoice items from sale details payload
        $invItemSql = "INSERT INTO invoiceitems (InvoiceID, ProductID, Quantity, UnitID, QuantityBase, UnitPrice, Subtotal) VALUES (?, ?, ?, ?, ?, ?, ?)";
        if ($db instanceof PDO) $stmtInvItem = $db->prepare($invItemSql);
        foreach ($line_items as $li) {
            $qty = $li['quantity'];
            $unitPrice = $li['unitPrice'];
            $lineSubtotal = $li['subtotal'];
            if ($db instanceof PDO) {
                $stmtInvItem->execute([$invoiceId, $li['productId'], $qty, 1, $qty, $unitPrice, $lineSubtotal]);
            } else {
                db_execute($invItemSql, [$invoiceId, $li['productId'], $qty, 1, $qty, $unitPrice, $lineSubtotal]);
            }
        }
        if ($paymentMethod === 'Credit') {
            $dueDate = $_POST['creditDueDate'] ?? null;
            $creditNotes = $_POST['creditNotes'] ?? null;
            $arSql = "INSERT INTO accountsreceivable (SaleID, CustomerID, AmountDue, DueDate, PaymentStatus, PaidAmount, DiscountFromPoints)
                      VALUES (?, ?, ?, ?, 'Pending', 0, ?)";
            if ($db instanceof PDO) {
                $stmtAr = $db->prepare($arSql);
                $stmtAr->execute([$saleId, $customerId, $totalAmount, $dueDate, $pointsUsed]);
            } else {
                db_execute($arSql, [$saleId, $customerId, $totalAmount, $dueDate, $pointsUsed]);
            }
        }

        db_commit();

        // Prepare receipt data to show in modal (fetch sale + lines)
        $receiptSale = db_fetch_one("SELECT s.*, st.StoreName AS store_name, u.FirstName AS cashier_first, u.LastName AS cashier_last
                                    FROM sales s
                                    LEFT JOIN stores st ON s.StoreID = st.StoreID
                                    LEFT JOIN employees u ON s.SalespersonID = u.EmployeeID
                                    WHERE s.SaleID = ?", [$saleId]);

        $receiptLines = db_fetch_all("SELECT sd.*, p.Brand, p.Model, p.Size, p.Color, p.SKU
                                     FROM saledetails sd
                                     LEFT JOIN products p ON sd.ProductID = p.ProductID
                                     WHERE sd.SaleID = ?", [$saleId]);

        $openReceipt = true;
        $_SESSION['flash_alert'] = ['type' => 'success', 'msg' => "Sale processed successfully (Sale ID: #{$saleId})."];

    } catch (Exception $ex) {
        try { db_rollback(); } catch (Exception $e) {}
        $_SESSION['flash_alert'] = ['type' => 'danger', 'msg' => 'Failed to process sale: ' . $ex->getMessage()];
        header('Location: ' . strtok($_SERVER['REQUEST_URI'], '?'));
        exit;
    }
}

// -----------------------------------------------------------------------------
// Load stores first, then products for current store (limit to active products)
$stores = db_fetch_all("SELECT StoreID AS id, StoreName AS name FROM stores ORDER BY StoreName");
$currentStoreId = (int)($_SESSION['store_id'] ?? ($stores[0]['id'] ?? 1));
$storeParam = $currentStoreId;
// Some wrappers ignore bound params; inline the store id to guarantee results
$products = db_fetch_all("SELECT p.ProductID AS id, p.SKU AS sku, p.Brand AS brand, p.Model AS model, p.Size AS size, p.Color AS color, p.CostPrice AS cost_price, p.SellingPrice AS price, IFNULL(i.Quantity,0) AS stock
                          FROM products p
                          LEFT JOIN inventory i ON i.ProductID = p.ProductID AND i.StoreID = {$storeParam}
                          WHERE (p.Status IS NULL OR p.Status = 'Active')
                          ORDER BY p.Brand, p.Model, p.Size");
if (!$products || count($products) === 0) {
    $products = [
        [
            'id' => 999001,
            'sku' => 'NIKE-AIR-TEST01',
            'brand' => 'Nike',
            'model' => 'Air Test',
            'size' => 42.0,
            'color' => 'Black/White',
            'cost_price' => 2000.00,
            'price' => 2500.00,
            'stock' => 10
        ]
    ];
}
// load customers for credit sales
$customers = db_fetch_all("SELECT CustomerID AS id, CONCAT(FirstName,' ',COALESCE(LastName,'')) AS name FROM customers ORDER BY name");

// UI values
$products_json = json_encode($products, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP);
$stores_json = json_encode($stores);
$customers_json = json_encode($customers);

$cashierName = trim(($_SESSION['employee']['FirstName'] ?? '') . ' ' . ($_SESSION['employee']['LastName'] ?? ''));
if (! $cashierName) $cashierName = $_SESSION['user_name'] ?? $_SESSION['firstname'] ?? 'System';

$terminalNumber = 1;
$storeLocation = 'Main Branch';
foreach ($stores as $s) { if ((int)$s['id'] === $currentStoreId) { $storeLocation = $s['name']; break; } }

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Shoe Retail POS</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* ---------------------------
           User-provided CSS (embedded)
           --------------------------- */
        :root {
            --primary-color: #714B67;
            --primary-light: #8B5E7F;
            --primary-dark: #5A3B54;
            --secondary-color: #F5B041;
            --success-color: #27AE60;
            --danger-color: #E74C3C;
            --gray-50: #F9FAFB;
            --gray-100: #F3F4F6;
            --gray-200: #E5E7EB;
            --gray-300: #D1D5DB;
            --gray-500: #6B7280;
            --gray-700: #374151;
            --gray-800: #1F2937;
            --gray-900: #111827;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.08);
            --shadow-lg: 0 8px 12px -2px rgba(0, 0, 0, 0.1);
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: var(--gray-50);
            overflow: hidden;
        }

        .pos-wrapper {
            display: flex;
            height: 100vh;
        }

        .products-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: white;
        }

        .top-bar {
            background: white;
            color: var(--gray-900);
            padding: 1.5rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid var(--gray-200);
            box-shadow: var(--shadow-sm);
        }

        .store-info h1 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--primary-color);
        }

        .store-info p {
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .cashier-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .cashier-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 1.125rem;
            border: 2px solid var(--secondary-color);
        }

        .cashier-details {
            text-align: right;
        }

        .cashier-details p {
            font-size: 0.75rem;
            color: var(--gray-500);
            margin-bottom: 0.125rem;
        }

        .cashier-name {
            font-weight: 600;
            font-size: 0.9375rem;
            color: var(--gray-900);
        }

        .filter-bar {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--gray-200);
            background: var(--gray-50);
        }

        .search-container {
            margin-bottom: 1rem;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 3rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            transition: all var(--transition-fast);
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
            font-size: 1.125rem;
        }

        .category-tabs {
            display: flex;
            gap: 0.5rem;
            overflow-x: auto;
            padding-bottom: 0.25rem;
        }

        .category-tabs::-webkit-scrollbar {
            height: 4px;
        }

        .category-tabs::-webkit-scrollbar-thumb {
            background: var(--gray-300);
            border-radius: 2px;
        }

        .category-tab {
            padding: 0.5rem 1rem;
            border: none;
            background: white;
            border-radius: var(--radius-md);
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all var(--transition-fast);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
        }

        .category-tab:hover {
            background: var(--gray-100);
            border-color: var(--primary-color);
        }

        .category-tab.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .products-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1rem;
        }

        .product-item {
            background: white;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-lg);
            padding: 1rem;
            cursor: pointer;
            transition: all var(--transition-fast);
            display: flex;
            gap: 1rem;
        }

        .product-item:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

.product-icon {
            font-size: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .product-thumb { width:72px; height:72px; border-radius:10px; object-fit:cover; background:#f3f4f6; border:1px solid var(--gray-200); }

        .product-details {
            flex: 1;
        }

        .product-name {
            font-weight: 600;
            color: var(--gray-900);
            margin-bottom: 0.5rem;
            font-size: 0.9375rem;
        }

        .product-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .product-price {
            color: var(--primary-color);
            font-weight: 700;
            font-size: 1.125rem;
        }

        .product-stock {
            background: var(--gray-100);
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8125rem;
            color: var(--gray-700);
        }

        .cart-panel {
            width: 420px;
            background: var(--gray-50);
            border-left: 2px solid var(--gray-200);
            display: flex;
            flex-direction: column;
        }

        .cart-header {
            padding: 1.5rem;
            border-bottom: 2px solid var(--gray-200);
            background: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cart-header-left {
            flex: 1;
        }

        .cart-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--gray-900);
            margin-bottom: 0.25rem;
        }

        .order-info {
            display: flex;
            gap: 1rem;
            font-size: 0.875rem;
            color: var(--gray-500);
        }

        .order-info span {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .cart-items-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
        }
        .cart-item-cost {
            font-size: 0.8125rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        .empty-cart {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #94a3b8;
        }

        .empty-cart-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .cart-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 0.75rem;
        }

        .cart-item-top {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
        }

        .item-name {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 0.25rem;
        }

        .item-size {
            font-size: 0.8125rem;
            color: #64748b;
        }

        .remove-btn {
            background: none;
            border: none;
            color: #ef4444;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .cart-item-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            border: none;
            background: #e2e8f0;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #cbd5e1;
        }

        .qty-value {
            font-weight: 600;
            min-width: 30px;
            text-align: center;
        }

        .item-total {
            font-weight: 700;
            color: #667eea;
        }

        .cart-summary {
            padding: 1.5rem;
            border-top: 2px solid var(--gray-200);
            background: white;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            color: var(--gray-700);
        }

        .summary-row.total {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--gray-900);
            padding-top: 0.75rem;
            border-top: 2px solid var(--gray-200);
            margin-top: 0.5rem;
        }

        .cart-actions {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.75rem;
            margin-top: 1rem;
        }

        .dropdown {
            grid-column: 1 / -1;
            padding: 0.875rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.9375rem;
            cursor: pointer;
            background: white;
            color: var(--gray-700);
            font-weight: 500;
            transition: all var(--transition-fast);
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20' fill='%23714B67'%3E%3Cpath fill-rule='evenodd' d='M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z' clip-rule='evenodd' /%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 20px;
            padding-right: 2.5rem;
        }

        .dropdown:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(113, 75, 103, 0.1);
        }

        .dropdown:disabled {
            background-color: var(--gray-100);
            color: var(--gray-500);
            cursor: not-allowed;
        }

        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.9375rem;
        }

        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-outline {
            background: white;
            color: #64748b;
            border: 2px solid #e2e8f0;
        }

        .btn-outline:hover:not(:disabled) {
            background: #f8fafc;
        }

        .btn-primary {
            grid-column: 1 / -1;
            background: #714B67;
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .btn-success {
            background: #10b981;
            color: white;
        }

        .btn-success:hover:not(:disabled) {
            background: #059669;
        }

        .modal-overlay, .modal-backdrop {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }

        .modal-overlay.show, .modal-backdrop.show {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content, .modal {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 1000;
        }

        .modal.show {
            display: block;
        }

        .modal-header {
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 {
            font-size: 1.5rem;
            color: #1e293b;
        }

        .modal-close {
            background: none;
            border: none;
            font-size: 2rem;
            cursor: pointer;
            color: #94a3b8;
            line-height: 1;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .modal-subtitle {
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .size-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .size-option {
            padding: 1rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            background: white;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }

        .size-option:hover {
            border-color: #667eea;
        }

        .size-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }

        .modal-actions, .modal-footer {
            display: flex;
            gap: 0.75rem;
            justify-content: flex-end;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #1e293b;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9375rem;
        }

        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }

        .form-help {
            margin-top: 0.5rem;
            font-size: 0.875rem;
            color: #64748b;
        }

        .calc-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.75rem;
            margin: 1rem 0;
        }

        .calc-btn {
            padding: 1.25rem;
            border: 2px solid #e2e8f0;
            background: white;
            border-radius: 8px;
            font-size: 1.25rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .calc-btn:hover {
            background: #f8fafc;
            border-color: #667eea;
        }

        .calc-btn.clear {
            grid-column: 2 / -1;
            background: #fee2e2;
            color: #ef4444;
            border-color: #fecaca;
        }

        .calc-btn.clear:hover {
            background: #fecaca;
        }

        .discount-indicator {
            color: #10b981;
            font-weight: 600;
        }

        .btn-secondary {
            background: #64748b;
            color: white;
        }

        .btn-secondary:hover:not(:disabled) {
            background: #475569;
        }
        /* Receipt modal styling */
        .receipt-modal .modal-content { max-width: 800px; width: 95%; padding: 1.25rem; max-height: 85vh; overflow-y: auto; }
        .receipt-wrapper { max-width: 720px; margin: 0 auto; max-height: none; overflow: visible; }
        .receipt-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
        html.no-scroll, body.no-scroll { overflow: hidden !important; height: 100vh !important; }
        .receipt-modal { max-height: none !important; overflow: visible !important; }
        .receipt-header h2 { margin:0; color:var(--primary-color); }
        .receipt-items table { width:100%; border-collapse:collapse; margin-top:0.5rem; }
        .receipt-items th, .receipt-items td { padding:8px 6px; border-bottom:1px dashed #e5e7eb; text-align:left; }
        .receipt-totals { margin-top:10px; }
        .receipt-totals div { display:flex; justify-content:space-between; padding:6px 0; }
@media print {
  html, body { overflow: visible !important; height: auto !important; }
  .modal-backdrop, .btn, .filter-bar, .top-bar, .cart-panel, .products-panel { display:none !important; }
  .pos-wrapper { display:none !important; }
  .modal { position: static !important; transform:none !important; display:block !important; }
  .receipt-modal .modal-content { max-height: none !important; overflow: visible !important; box-shadow:none !important; border:none !important; padding: 0 !important; }
  .receipt-wrapper { margin: 0 !important; }
}
    </style>
</head>
<body>
    <!-- Flash via Swal -->
    <?php if (!empty($_SESSION['flash_alert'])): $a = $_SESSION['flash_alert']; ?>
        <script>
            window.addEventListener('DOMContentLoaded', function(){
                Swal.fire({ icon: '<?php echo $a['type']==='danger'?'error':($a['type']==='success'?'success':'info'); ?>', title: <?php echo json_encode($a['msg']); ?> });
            });
        </script>
    <?php unset($_SESSION['flash_alert']); endif; ?>

    <div class="pos-wrapper">
        <div class="products-panel">
            <div class="top-bar">
                <div class="store-info">
                    <h1>Shoe Retail POS</h1>
                    <div style="margin:.5rem 0 0 0; display:flex; gap:.5rem; align-items:center;">
                        <label for="storeSelect" style="font-size:.875rem;color:var(--gray-700);">Branch</label>
                        <select id="storeSelect" class="dropdown" style="max-width:260px;">
                            <?php foreach ($stores as $s) { $sel = (isset($_SESSION['store_id']) && intval($_SESSION['store_id'])===intval($s['id'])) ? ' selected' : ''; echo '<option value="'.intval($s['id']).'"'.$sel.'>'.htmlspecialchars($s['name']).'</option>'; } ?>
                        </select>
                    </div>
                    <p>Terminal #<span id="terminalNumber"><?php echo htmlspecialchars($terminalNumber); ?></span> • <span id="storeLocation"><?php echo htmlspecialchars($storeLocation); ?></span> • <span id="nowClock"></span></p>
                </div>
                <div class="cashier-info">
                    <div class="cashier-details">
                        <p>Cashier</p>
                        <div class="cashier-name" id="cashierName"><?php echo htmlspecialchars($cashierName); ?></div>
                    </div>
                    <div class="cashier-avatar" id="cashierAvatar"><?php echo htmlspecialchars(substr($cashierName,0,1)); ?></div>
                </div>
            </div>

            <div class="filter-bar">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input id="searchInput" class="search-input" placeholder="Search by SKU, brand, or model..." onkeyup="renderProducts()">
                </div>
                <div class="category-tabs" id="categoryTabs"></div>
            </div>

            <div class="products-container">
                <div class="products-grid" id="productsGrid"></div>
            </div>
        </div>

        <div class="cart-panel">
            <div class="cart-header">
                <div class="cart-header-left">
                    <div class="cart-title">Current Order</div>
                    <div class="order-info">
                        <span><i class="fas fa-receipt"></i> Order #<span id="orderNumber">-</span></span>
                        <span><i class="fas fa-percentage"></i> VAT: <span id="vatDisplay"><?php echo (int)round((defined('VAT_RATE')?VAT_RATE:0.12)*100); ?>%</span></span>
                        <span><i class="fas fa-shopping-cart"></i> <span id="cartItemCount">0</span> items</span>
                    </div>
                </div>
            </div>

            <div class="cart-items-container" id="cartItems">
                <div class="empty-cart">
                    <div class="empty-cart-icon">🛒</div>
                    <p>No items in cart</p>
                    <p style="font-size: 0.875rem; margin-top: 0.5rem;">Select products to add to order</p>
                </div>
            </div>

            <div class="cart-summary">
                <div class="summary-row"><span>Subtotal</span><span id="subtotal">₱0.00</span></div>
                <div class="summary-row"><span>VAT (<span id="vatRate"><?php echo (int)round((defined('VAT_RATE')?VAT_RATE:0.12)*100); ?></span>%)</span><span id="tax">₱0.00</span></div>
                <div class="summary-row"><span>Discount <span id="discountLabel" class="discount-indicator"></span></span><span id="discount">₱0.00</span></div>
                <div class="summary-row total"><span>Total</span><span id="total">₱0.00</span></div>

                <div class="cart-actions">
                    <select id="customerSelect" class="dropdown" onchange="onCustomerChange()">
                        <option value="">Walk-in Customer</option>
                        <?php foreach ($customers as $c) { echo '<option value="'.intval($c['id']).'">'.htmlspecialchars($c['name']).'</option>'; } ?>
                    </select>
                    <div id="pointsRow" style="grid-column:1 / -1; display:none; gap:.5rem; align-items:center; flex-wrap:wrap;">
                        <label style="font-size:.9rem;color:#64748b; display:flex; align-items:center; gap:.5rem;">
                            <input type="checkbox" id="usePointsChk" onchange="recomputePoints()" />
                            Use points (<span id="pointsAvailable">0</span> available)
                        </label>
                        <div id="pointsQuick" style="display:flex; gap:.5rem;">
                            <button class="btn btn-outline" onclick="setMaxPoints()">Max</button>
                            <button class="btn btn-outline" onclick="adjustPoints(100)">+100</button>
                            <button class="btn btn-outline" onclick="adjustPoints(500)">+500</button>
                            <button class="btn btn-outline" onclick="clearPoints()">Clear</button>
                        </div>
                        <span id="appliedPointsInfo" style="font-size:.9rem;color:#10b981"></span>
                    </div>
                    <select id="paymentMode" class="dropdown" onchange="updateCartButtons()">
                        <option value="">Select Payment Mode</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Credit">Credit (On Account)</option>
                    </select>

                    <button class="btn btn-outline" onclick="openVoucherModal()" id="voucherBtn" disabled>Add Voucher</button>
                    <button class="btn btn-outline" id="myVouchersBtn" style="display:none" onclick="openVoucherModal()">My Vouchers</button>
                    <button class="btn btn-outline" id="bestVoucherBtn" style="display:none" onclick="applyBestVoucher()">Best Voucher</button>
                    <button class="btn btn-outline" onclick="clearCart()">Clear</button>
                    <button class="btn btn-primary" onclick="prepareAndSubmitSale()" id="checkoutBtn" disabled>Checkout</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Size modal -->
    <div class="modal-overlay" id="sizeModal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Select Size</div>
                <div class="modal-subtitle" id="modalProductName"></div>
            </div>
            <div class="size-grid" id="sizeOptions"></div>
            <div class="modal-actions">
                <button class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                <button class="btn btn-primary" onclick="addToCart()">Add to Order</button>
            </div>
        </div>
    </div>

    <!-- Voucher modal -->
    <div class="modal-backdrop" id="voucherModalBackdrop"></div>
    <div class="modal" id="voucherModal">
        <div class="modal-header">
            <h3>Apply Voucher</h3>
            <button class="modal-close" onclick="closeVoucherModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label for="voucherCode" class="form-label">Voucher Code</label>
                <input type="text" id="voucherCode" class="form-control" placeholder="Enter voucher code">
            </div>
            <div class="form-help">Available codes: <code>SAVE10</code>, <code>WELCOME20</code></div>
            <div id="voucherMessage" class="form-help"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeVoucherModal()">Cancel</button>
            <button class="btn btn-success" onclick="applyVoucher()">Apply</button>
        </div>
    </div>

    <!-- Payment modal -->
    <div class="modal-backdrop" id="paymentModalBackdrop"></div>
    <div class="modal" id="paymentModal">
        <div class="modal-header">
            <h3>Cash Payment</h3>
            <button class="modal-close" onclick="closePaymentModal()">×</button>
        </div>
        <div class="modal-body">
            <p style="font-size: 1rem; margin-bottom: 1rem;">Total Due: <strong id="paymentTotal">₱0.00</strong></p>
            <input type="text" id="paymentInput" class="form-control" placeholder="Enter amount" readonly>
            <div class="calc-grid">
                <button class="calc-btn">7</button>
                <button class="calc-btn">8</button>
                <button class="calc-btn">9</button>
                <button class="calc-btn">4</button>
                <button class="calc-btn">5</button>
                <button class="calc-btn">6</button>
                <button class="calc-btn">1</button>
                <button class="calc-btn">2</button>
                <button class="calc-btn">3</button>
                <button class="calc-btn">0</button>
                <button class="calc-btn clear">Clear</button>
            </div>
            <p id="changeOutput" style="margin-top: 10px; font-weight: bold;"></p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closePaymentModal()">Back</button>
            <button class="btn btn-primary" id="confirmPaymentBtn" disabled onclick="confirmPayment()">Confirm</button>
        </div>
    </div>

    <!-- Credit modal -->
    <div class="modal-backdrop" id="creditModalBackdrop"></div>
    <div class="modal" id="creditModal">
        <div class="modal-header">
            <h3>Credit Sale Details</h3>
            <button class="modal-close" onclick="closeCreditModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Customer</label>
                <select id="creditCustomer" class="form-control"></select>
                <div class="form-help">Required for credit transactions</div>
            </div>
            <div class="form-group">
                <label class="form-label">Due Date</label>
                <input type="date" id="creditDueDate" class="form-control" />
            </div>
            <div class="form-group">
                <label class="form-label">Reference</label>
                <input type="text" id="creditReference" class="form-control" placeholder="PO/Agreement # (optional)" />
            </div>
            <div class="form-group">
                <label class="form-label">Notes</label>
                <textarea id="creditNotes" class="form-control" rows="2" placeholder="Notes (optional)"></textarea>
            </div>
            <div id="creditError" class="form-help" style="color:#ef4444"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeCreditModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmCredit()">Confirm Credit</button>
        </div>
    </div>

    <!-- Card modal -->
    <div class="modal-backdrop" id="cardModalBackdrop"></div>
    <div class="modal" id="cardModal">
        <div class="modal-header">
            <h3>Card Payment</h3>
            <button class="modal-close" onclick="closeCardModal()">×</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label class="form-label">Cardholder Name</label>
                <input type="text" id="cardHolder" class="form-control" placeholder="e.g., Juan Dela Cruz">
            </div>
            <div class="form-group">
                <label class="form-label">Card Number</label>
                <input type="text" id="cardNumber" class="form-control" placeholder="XXXX XXXX XXXX XXXX" maxlength="19">
                <div class="form-help">We'll only store the last 4 digits and a reference code.</div>
            </div>
            <div class="form-group" style="display:flex; gap:.75rem;">
                <div style="flex:1">
                    <label class="form-label">Expiry (MM/YY)</label>
                    <input type="text" id="cardExpiry" class="form-control" placeholder="MM/YY" maxlength="5">
                </div>
                <div style="flex:1">
                    <label class="form-label">CVV</label>
                    <input type="password" id="cardCvv" class="form-control" placeholder="CVV" maxlength="4">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reference / Auth Code</label>
                <input type="text" id="cardRef" class="form-control" placeholder="Approval/Trace/Ref #">
            </div>
            <div id="cardError" class="form-help" style="color:#ef4444"></div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline" onclick="closeCardModal()">Cancel</button>
            <button class="btn btn-primary" onclick="confirmCardPayment()">Confirm Card</button>
        </div>
    </div>

    <!-- Receipt backdrop -->
    <div class="modal-backdrop" id="receiptModalBackdrop"></div>
    <!-- Receipt modal (auto opens after successful sale) -->
    <div id="receiptModal" class="modal receipt-modal" aria-hidden="true">
        <div class="modal-content">
            <div class="receipt-wrapper">
                <div class="receipt-header">
                    <div>
                        <h2><?php echo htmlspecialchars($storeLocation); ?></h2>
                        <div class="muted">Cashier: <strong><?php echo htmlspecialchars($cashierName); ?></strong> <?php if(!empty($receiptSale['SalespersonID'])) echo '(#'.intval($receiptSale['SalespersonID']).')'; ?></div>
                        <div class="muted">VAT Reg TIN: <strong><?php echo htmlspecialchars(defined('COMPANY_TIN')?COMPANY_TIN:''); ?></strong></div>
                    </div>
                    <div style="text-align:right">
                        <div>Sales Invoice</div>
                        <div>Sale #: <strong><?php echo isset($receiptSale['SaleID']) ? 'S' . str_pad($receiptSale['SaleID'],4,'0',STR_PAD_LEFT) : '-'; ?></strong></div>
                        <div>Invoice #: <strong><?php echo htmlspecialchars($invoiceNumber ?? '-'); ?></strong></div>
                        <div><?php echo isset($receiptSale['SaleDate']) ? date('F j, Y g:i A', strtotime($receiptSale['SaleDate'])) : date('F j, Y g:i A'); ?></div>
                    </div>
                </div>

                <div class="receipt-items">
                    <table>
                        <thead>
                            <tr><th>Item</th><th style="width:70px">Qty</th><th style="width:120px">Unit</th><th style="width:120px">Total</th></tr>
                        </thead>
                        <tbody id="receiptItemsBody">
                        <?php if (!empty($receiptLines)) :
                            foreach ($receiptLines as $ln) : ?>
                                <tr>
                                    <td>
                                        <div style="font-weight:600;"><?php echo htmlspecialchars(($ln['Brand'] ?? '') . ' ' . ($ln['Model'] ?? '')); ?></div>
                                        <div class="muted" style="font-size:.9rem;"><?php echo htmlspecialchars(($ln['SKU'] ?? '') . (isset($ln['Size']) ? ' • ' . $ln['Size'] : '') ); ?></div>
                                    </td>
                                    <td><?php echo (float)$ln['Quantity']; ?></td>
                                    <td>₱<?php echo number_format((float)$ln['UnitPrice'],2); ?></td>
                                    <td>₱<?php echo number_format((float)$ln['Subtotal'],2); ?></td>
                                </tr>
                            <?php endforeach;
                        endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="receipt-totals">
                    <?php
                        $r_sub = $receiptSale['TotalAmount'] - ($receiptSale['TaxAmount'] ?? 0);
                        $r_tax = $receiptSale['TaxAmount'] ?? 0;
                        $r_discount = $receiptSale['DiscountAmount'] ?? 0;
                        $r_total = $receiptSale['TotalAmount'] ?? 0;
                        $vat_sales = max(0, $r_total - $r_tax);
                        $tendered = isset($_POST['cashTendered']) ? (float)$_POST['cashTendered'] : null;
                        $change = ($tendered !== null) ? max(0, $tendered - $r_total) : null;
                        $ref = $_POST['cardRef'] ?? ($_POST['creditReference'] ?? null);
                    ?>
                    <div><div>VAT Sales</div><div>₱<?php echo number_format((float)$vat_sales,2); ?></div></div>
                    <div><div>VAT</div><div>₱<?php echo number_format((float)$r_tax,2); ?></div></div>
                    <div><div>Discount</div><div>- ₱<?php echo number_format((float)$r_discount,2); ?></div></div>
                    <div style="border-top:1px solid #e5e7eb; padding-top:10px; font-weight:700;"><div>Total Due</div><div>₱<?php echo number_format((float)$r_total,2); ?></div></div>
                    <?php if ($tendered !== null): ?>
                        <div><div>Amount Tendered</div><div>₱<?php echo number_format($tendered,2); ?></div></div>
                        <div><div>Change</div><div>₱<?php echo number_format($change,2); ?></div></div>
                    <?php endif; ?>
                    <?php if (!empty($ref)): ?>
                        <div><div>Ref No.</div><div><?php echo htmlspecialchars($ref); ?></div></div>
                    <?php endif; ?>
                    <div style="margin-top:8px;">Payment: <?php echo htmlspecialchars($receiptSale['PaymentMethod'] ?? '-'); ?></div>
                </div>
                <div style="margin-top:8px; display:flex; justify-content:flex-end">
                    <?php if (!empty($invoiceNumber)): $qrData = urlencode('INV=' . $invoiceNumber . '&Total=' . number_format((float)$r_total,2) . '&Date=' . (isset($receiptSale['SaleDate']) ? date('Y-m-d H:i', strtotime($receiptSale['SaleDate'])) : date('Y-m-d H:i'))); ?>
                        <img alt="QR" src="<?php echo (defined('RECEIPT_QR_BASE')?RECEIPT_QR_BASE:'https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=') . $qrData; ?>" />
                    <?php endif; ?>
                </div>

                <div style="margin-top:12px; font-size:.95rem;">
                    <div>Thank you for your purchase!</div>
                </div>

                <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px;">
                    <button class="btn btn-outline" onclick="openReceiptWindow(<?php echo intval($receiptSale['SaleID'] ?? 0); ?>)">View Full Receipt</button>
                    <button class="btn btn-primary" onclick="printReceipt()">Print</button>
                    <button class="btn btn-outline" onclick="closeReceiptModal()">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden traditional POST form -->
    <form id="posForm" method="post" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" style="display:none;">
        <input type="hidden" name="action" value="process_sale">
<input type="hidden" name="storeId" id="form_storeId" value="<?php echo htmlspecialchars($currentStoreId ?? 1); ?>">
        <input type="hidden" name="customerId" id="form_customerId" value="">
        <input type="hidden" name="paymentMethod" id="form_paymentMethod" value="">
        <input type="hidden" name="discountAmount" id="form_discountAmount" value="0">
        <input type="hidden" name="pointsUsed" id="form_pointsUsed" value="0">
        <input type="hidden" name="customerId" id="form_customerId_cart" value="">
        <input type="hidden" name="sale_items_json" id="form_sale_items_json" value="[]">
        <input type="hidden" name="creditDueDate" id="form_creditDueDate" value="">
        <input type="hidden" name="creditNotes" id="form_creditNotes" value="">
        <input type="hidden" name="creditReference" id="form_creditReference" value="">
        <input type="hidden" name="cashTendered" id="form_cashTendered" value="">
        <input type="hidden" name="cardLast4" id="form_cardLast4" value="">
        <input type="hidden" name="cardRef" id="form_cardRef" value="">
    </form>

<script>
/* ---------- Frontend JS ---------- */
const PRODUCTS = <?php echo $products_json ?: '[]'; ?>; console.debug('POS PRODUCTS', PRODUCTS);
// Runtime fallback if no products came from DB (ensures visible test item)
try {
  if (!Array.isArray(PRODUCTS) || PRODUCTS.length === 0) {
    (Array.isArray(PRODUCTS) ? PRODUCTS : (window.PRODUCTS = [])).push({
      id: 999001,
      sku: 'NIKE-AIR-TEST01',
      brand: 'Nike',
      model: 'Air Test',
      Size: 42.0,
      size: 42.0,
      Color: 'Black/White',
      color: 'Black/White',
      cost_price: 2000.00,
      price: 2500.00,
      stock: 10
    });
  }
} catch(e) { console.warn(e); }
const CUSTOMERS = <?php echo $customers_json ?: '[]'; ?>;
let cart = [];
let appliedDiscount = 0; // percent (0..1) from voucher
let discountCode = '';
let appliedPoints = 0; // integer points applied
let customerPoints = 0; // fetched from CRM/customers list when selected
let selectedCustomerId = '';
const VAT_RATE = <?php echo defined('VAT_RATE') ? VAT_RATE : 0.12; ?>;
const API_CUSTOMERS = '/ShoeRetailErp/api/customers.php';

// render category tabs and products grid
function buildCategoryTabs() {
    const tabs = [...new Set(PRODUCTS.map(p => p.brand).filter(Boolean))];
    const container = document.getElementById('categoryTabs');
container.innerHTML = `<button class=\"category-tab active\" onclick=\"filterByBrand(event, '')\">All Products</button>` +
        tabs.map(b => `<button class=\"category-tab\" onclick=\"filterByBrand(event, '${escapeJs(b)}')\">${escapeHtml(b)}</button>`).join('');
}

function renderProducts() {
    const grid = document.getElementById('productsGrid');
    const term = (document.getElementById('searchInput').value || '').toLowerCase();
    const filtered = PRODUCTS.filter(p => {
        const matchSearch = (p.sku || '').toLowerCase().includes(term) ||
                            (p.brand || '').toLowerCase().includes(term) ||
                            (p.model || '').toLowerCase().includes(term);
        return matchSearch;
    });
    grid.innerHTML = filtered.map(prod => {
        const price = parseFloat(prod.price || prod.SellingPrice || 0).toFixed(2);
        const sku = prod.sku || prod.SKU || '';
        const size = prod.size || prod.Size || '';
        const color = prod.color || prod.Color || '';
        const imgSrc = `../uploads/products/${encodeURIComponent(sku || prod.id)}.jpg`;
        return `<div class="product-item" data-id="${prod.id}" onclick="openSizeModal(${prod.id})">
            <div class="product-icon"><img class="product-thumb" src="${imgSrc}" onerror="this.onerror=null;this.replaceWith(document.createTextNode('👟'));" alt="${escapeHtml(prod.brand)}"/></div>
            <div class="product-details">
                <div class="product-name">${escapeHtml(prod.brand)} ${escapeHtml(prod.model)}${size ? ' - ' + escapeHtml(size) : ''}</div>
                <div style="font-size:0.85rem;color:#64748b;margin-top:6px">${escapeHtml(sku)} ${color ? '• ' + escapeHtml(color) : ''}</div>
                <div class="product-meta" style="margin-top:8px">
                    <div class="product-price">₱${price}</div>
                    <div class="product-stock">${(prod.stock !== undefined) ? prod.stock + ' pairs in stock' : '—'}</div>
                </div>
            </div>
        </div>`;
    }).join('');
}

// size modal
let currentProduct = null;
function openSizeModal(id) {
    currentProduct = PRODUCTS.find(p => Number(p.id) === Number(id));
if (!currentProduct) { Swal.fire({icon:'error', title:'Product not found'}); return; }
    document.getElementById('modalProductName').textContent = `${currentProduct.brand} ${currentProduct.model} ${currentProduct.color ? '• ' + currentProduct.color : ''}`;
    // show size options based on variants
    const sizes = PRODUCTS.filter(p => p.brand === currentProduct.brand && p.model === currentProduct.model);
    const sizeOptions = document.getElementById('sizeOptions');
sizeOptions.innerHTML = sizes.map(s => `<button class=\"size-option\" onclick=\"selectSize(event, ${s.id})\">${escapeHtml(s.size || s.Size)}</button>`).join('');
    document.getElementById('sizeModal').classList.add('show');
}
function closeModal() { document.getElementById('sizeModal').classList.remove('show'); }
let selectedSizeProductId = null;
function selectSize(ev, pid) {
    selectedSizeProductId = pid;
    document.querySelectorAll('.size-option').forEach(b => b.classList.remove('selected'));
    if (ev && ev.target) ev.target.classList.add('selected');
}

function addToCart() { if (cart.length===0){ document.getElementById('orderNumber').textContent = 'POS-' + Date.now().toString().slice(-6); }
    if (!selectedSizeProductId) { Swal.fire({icon:'info', title:'Please select a size'}); return; }
    const prod = PRODUCTS.find(p => Number(p.id) === Number(selectedSizeProductId));
    if (!prod) { Swal.fire({icon:'error', title:'Variant missing'}); return; }
    const existing = cart.find(c => Number(c.productId) === Number(prod.id));
    const currentQty = existing ? existing.quantity : 0;
    const max = Number(prod.stock ?? Infinity);
    if (currentQty + 1 > max) { Swal.fire({icon:'warning', title:'Not enough stock'}); return; }
    if (existing) existing.quantity += 1; else cart.push({ productId: prod.id, sku: prod.sku || prod.SKU, name: prod.brand + ' ' + prod.model, size: prod.size, color: prod.color, unitPrice: parseFloat(prod.price || prod.SellingPrice || 0), quantity: 1 });
    const remaining = max - (currentQty + 1);
    if (max !== Infinity && remaining <= 10) { Swal.fire({icon:'info', title:'Low stock', text:`Only ${remaining} pairs left`}); }
    selectedSizeProductId = null;
    closeModal();
    updateCart();
}

// cart UI functions
function updateCart() {
    const container = document.getElementById('cartItems');
    const countEl = document.getElementById('cartItemCount');
    if (cart.length === 0) {
        container.innerHTML = `<div class="empty-cart"><div class="empty-cart-icon">🛒</div><p>No items in cart</p><p style="font-size:.875rem;margin-top:.5rem">Select products to add to order</p></div>`;
    } else {
        container.innerHTML = cart.map((it, idx) => `
            <div class="cart-item">
                <div class="cart-item-top">
                    <div>
                        <div class="item-name">${escapeHtml(it.name)} <span class="item-size">(${escapeHtml(it.size)})</span></div>
                        <div class="cart-item-cost">${escapeHtml(it.sku)} ${it.color ? '• ' + escapeHtml(it.color) : ''}</div>
                    </div>
                    <button class="remove-btn" onclick="removeFromCart(${idx})">×</button>
                </div>
                <div class="cart-item-bottom">
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="changeQty(${idx}, -1)">−</button>
                        <div class="qty-value">${it.quantity}</div>
                        <button class="qty-btn" onclick="changeQty(${idx}, 1)">+</button>
                    </div>
                    <div class="item-total">₱${(it.unitPrice * it.quantity).toFixed(2)}</div>
                </div>
            </div>
        `).join('');
    }
    countEl.textContent = cart.reduce((s,i)=>s+i.quantity,0);
    calculateSummary();
    updateCartButtons();
    // Re-evaluate points application after any cart change
    recomputePoints(false);
}

function changeQty(i, delta) {
    if (!cart[i]) return;
    const prod = PRODUCTS.find(p=>Number(p.id)===Number(cart[i].productId));
    const max = Number(prod && prod.stock !== undefined ? prod.stock : Infinity);
    const newQty = cart[i].quantity + delta;
    if (newQty <= 0) { cart.splice(i,1); updateCart(); return; }
    if (newQty > max) { Swal.fire({icon:'warning', title:'Cannot exceed stock'}); return; }
    cart[i].quantity = newQty;
    const remaining = max - newQty;
    if (max !== Infinity && remaining <= 10) { Swal.fire({icon:'info', title:'Low stock', text:`Only ${remaining} pairs left`}); }
    updateCart();
}
function removeFromCart(i) { cart.splice(i,1); updateCart(); }
function clearCart() { Swal.fire({icon:'warning', title:'Clear cart?', showCancelButton:true, confirmButtonText:'Yes, clear', cancelButtonText:'No'}).then(res=>{ if(res.isConfirmed){ cart=[]; appliedDiscount=0; discountCode=''; updateCart(); Swal.fire({icon:'success', title:'Cart cleared', timer:1000, showConfirmButton:false}); } }); }

function calculateSummary() {
    const subtotal = cart.reduce((s,i)=>s + (i.unitPrice * i.quantity), 0);
    const voucherDiscount = subtotal * appliedDiscount;
    const pointsDiscount = appliedPoints; // 1 point = ₱1.00 (schema-compatible)
    const totalDiscount = Math.min(subtotal, voucherDiscount + pointsDiscount);
    const taxable = Math.max(0, subtotal - totalDiscount);
    const tax = taxable * VAT_RATE;
    const total = taxable + tax;
    document.getElementById('subtotal').textContent = `₱${subtotal.toFixed(2)}`;
    document.getElementById('tax').textContent = `₱${tax.toFixed(2)}`;
    document.getElementById('discount').textContent = totalDiscount > 0 ? `-₱${totalDiscount.toFixed(2)}` : '₱0.00';
    document.getElementById('total').textContent = `₱${total.toFixed(2)}`;
    document.getElementById('discountLabel').textContent = [discountCode?`(${discountCode})`:'' , appliedPoints?`(+${appliedPoints} pts)`:'' ].filter(Boolean).join(' ');
}

function getCartTotals() {
    const subtotal = cart.reduce((s,i)=>s + (i.unitPrice * i.quantity), 0);
    const voucherDiscount = subtotal * appliedDiscount;
    const pointsDiscount = appliedPoints; // ₱1 per point
    const totalDiscount = Math.min(subtotal, voucherDiscount + pointsDiscount);
    const taxable = Math.max(0, subtotal - totalDiscount);
    const tax = taxable * VAT_RATE;
    const total = taxable + tax;
    return {subtotal, voucherDiscount, pointsDiscount, totalDiscount, taxable, tax, total};
}

function updateCartButtons() {
    const has = cart.length > 0;
    const mode = document.getElementById('paymentMode').value;
    const acc = !!selectedCustomerId;
    document.getElementById('voucherBtn').disabled = !has || mode !== 'Credit';
    document.getElementById('checkoutBtn').disabled = !has || !mode;
    const myV = document.getElementById('myVouchersBtn'); const bestV = document.getElementById('bestVoucherBtn');
    if (myV) { myV.style.display = acc ? 'inline-block' : 'none'; myV.disabled = !has || mode !== 'Credit'; }
    if (bestV) { bestV.style.display = acc ? 'inline-block' : 'none'; bestV.disabled = !has || mode !== 'Credit'; }
}

// voucher
const VOUCHER_CODES = { 'SAVE10':0.10, 'WELCOME20':0.20, 'CLEARANCE15':0.15 };
async function onCustomerChange(){
  const sel = document.getElementById('customerSelect');
  selectedCustomerId = sel.value;
  document.getElementById('form_customerId_cart').value = selectedCustomerId || '';
  const row = document.getElementById('pointsRow');
  const chk = document.getElementById('usePointsChk');
  customerPoints = 0;
  if (selectedCustomerId) {
    try {
      const res = await fetch(`${API_CUSTOMERS}?action=get_customer&customer_id=${encodeURIComponent(selectedCustomerId)}`, {credentials:'include'});
      const js = await res.json();
      if (js && js.success && js.data) {
        customerPoints = parseInt(js.data.LoyaltyPoints || 0, 10) || 0;
      }
    } catch (e) { console.warn('Loyalty fetch failed', e); }
  }
  document.getElementById('pointsAvailable').textContent = String(customerPoints||0);
  if (selectedCustomerId && (customerPoints||0) > 0) { row.style.display='flex'; chk.disabled = false; }
  else { row.style.display='none'; chk.checked = false; }
  recomputePoints();
}
function recomputePoints(showToast=true){
  const chk = document.getElementById('usePointsChk');
  const info = document.getElementById('appliedPointsInfo');
  if (!chk) return;
  if (!selectedCustomerId || !chk.checked){ appliedPoints = 0; document.getElementById('form_pointsUsed').value = '0'; info.textContent=''; calculateSummary(); return; }
  const subtotal = cart.reduce((s,i)=>s + (i.unitPrice * i.quantity), 0);
  const voucherDiscount = subtotal * appliedDiscount;
  const maxUsable = Math.max(0, Math.floor(subtotal - voucherDiscount));
  appliedPoints = Math.min(appliedPoints || 0, Math.min(customerPoints||0, maxUsable));
  document.getElementById('form_pointsUsed').value = String(appliedPoints);
  info.textContent = appliedPoints>0 ? `Applied: ₱${appliedPoints.toFixed(0)}` : '';
  calculateSummary();
  if (showToast && appliedPoints>0) Swal.fire({icon:'success', title:`Applied ${appliedPoints} points`});
}
function setMaxPoints(){ const chk=document.getElementById('usePointsChk'); chk.checked=true; const subtotal = cart.reduce((s,i)=>s + (i.unitPrice * i.quantity), 0); const voucherDiscount = subtotal * appliedDiscount; const maxUsable = Math.max(0, Math.floor(subtotal - voucherDiscount)); appliedPoints = Math.min(customerPoints||0, maxUsable); recomputePoints(false); }
function adjustPoints(delta){ const chk=document.getElementById('usePointsChk'); chk.checked=true; appliedPoints = Math.min((appliedPoints||0)+delta, customerPoints||0); recomputePoints(false); }
function clearPoints(){ appliedPoints = 0; const chk=document.getElementById('usePointsChk'); chk.checked=false; recomputePoints(false); }
function openVoucherModal(){ const mode=document.getElementById('paymentMode').value; if(mode!=='Credit'){ Swal.fire({icon:'info', title:'Voucher available only for Credit sales'}); return; } document.getElementById('voucherModal').classList.add('show'); document.getElementById('voucherModalBackdrop').classList.add('show'); document.getElementById('voucherCode').value=''; const avail = Object.keys(VOUCHER_CODES).map(c=>`<span class='badge' style='border:1px solid #ddd;padding:.25rem .5rem;border-radius:6px;cursor:pointer' onclick=\"document.getElementById('voucherCode').value='${c}'\">${c}</span>`).join(' '); document.getElementById('voucherMessage').innerHTML = `Available: ${avail}`; }
function closeVoucherModal(){ document.getElementById('voucherModal').classList.remove('show'); document.getElementById('voucherModalBackdrop').classList.remove('show'); }
function applyVoucher(){ const mode=document.getElementById('paymentMode').value; if(mode!=='Credit'){ Swal.fire({icon:'info', title:'Voucher only for Credit'}); return; } const code = (document.getElementById('voucherCode').value||'').trim().toUpperCase(); const msg = document.getElementById('voucherMessage'); if(!code){ msg.textContent='Enter voucher code'; return; } if(VOUCHER_CODES[code]){ appliedDiscount=VOUCHER_CODES[code]; discountCode=code; msg.textContent='Voucher applied'; setTimeout(()=>{ closeVoucherModal(); Swal.fire({icon:'success', title:'Voucher applied'}); },400); recomputePoints(false); } else { msg.textContent='Invalid code'; Swal.fire({icon:'error', title:'Invalid voucher'});} }
function applyBestVoucher(){ const mode=document.getElementById('paymentMode').value; if(mode!=='Credit'){ Swal.fire({icon:'info', title:'Voucher only for Credit'}); return; } let best=null, bestRate=0; Object.entries(VOUCHER_CODES).forEach(([k,v])=>{ if(v>bestRate){ bestRate=v; best=k; } }); if(best){ appliedDiscount=bestRate; discountCode=best; Swal.fire({icon:'success', title:`Applied ${best}`}); recomputePoints(false);} }

// payment modal (cash)
function openPaymentModal(total){ document.getElementById('paymentTotal').textContent = `₱${total.toFixed(2)}`; document.getElementById('paymentInput').value=''; document.getElementById('changeOutput').textContent=''; document.getElementById('confirmPaymentBtn').disabled=true; document.getElementById('paymentModal').classList.add('show'); document.getElementById('paymentModalBackdrop').classList.add('show'); }
function closePaymentModal(){ document.getElementById('paymentModal').classList.remove('show'); document.getElementById('paymentModalBackdrop').classList.remove('show'); }
function confirmPayment(){
    const input = document.getElementById('paymentInput');
const tendered = parseFloat(input.value || '0');
    const totals = getCartTotals();
    if (!isNaN(tendered)) { const fld = document.getElementById('form_cashTendered'); if (fld) fld.value = tendered.toFixed(2); }
    completeSale('Cash', totals.total);
    closePaymentModal();
}

// credit modal
function openCreditModal(){
  const sel = document.getElementById('creditCustomer');
  sel.innerHTML = '<option value="">Select customer</option>' + CUSTOMERS.map(c=>`<option value="${c.id}">${escapeHtml(c.name)}</option>`).join('');
  document.getElementById('creditError').textContent='';
  document.getElementById('creditDueDate').value = new Date(Date.now()+1000*60*60*24*30).toISOString().slice(0,10);
  document.getElementById('creditModal').classList.add('show');
  document.getElementById('creditModalBackdrop').classList.add('show');
}
function closeCreditModal(){ document.getElementById('creditModal').classList.remove('show'); document.getElementById('creditModalBackdrop').classList.remove('show'); }
function confirmCredit(){
  const cust = document.getElementById('creditCustomer').value;
  if(!cust){ document.getElementById('creditError').textContent = 'Customer is required for credit.'; return; }
  document.getElementById('form_customerId').value = cust;
  document.getElementById('form_creditDueDate').value = document.getElementById('creditDueDate').value;
  document.getElementById('form_creditNotes').value = document.getElementById('creditNotes').value;
  document.getElementById('form_creditReference').value = document.getElementById('creditReference').value;
  document.getElementById('posForm').submit();
}
document.querySelectorAll('.calc-btn').forEach(btn=>btn.addEventListener('click', ()=> {
    const input = document.getElementById('paymentInput');
    const changeOutput = document.getElementById('changeOutput');
    if (btn.classList.contains('clear')) { input.value=''; changeOutput.textContent=''; document.getElementById('confirmPaymentBtn').disabled=true; return; }
input.value += btn.textContent;
    const entered = parseFloat(input.value || 0);
    const totals = getCartTotals();
    if (!isNaN(entered) && entered >= totals.total) { changeOutput.textContent = `Change: ₱${(entered - totals.total).toFixed(2)}`; changeOutput.style.color='#10b981'; document.getElementById('confirmPaymentBtn').disabled=false; } else { changeOutput.textContent = 'Insufficient amount'; changeOutput.style.color='#ef4444'; document.getElementById('confirmPaymentBtn').disabled=true; }
}));

// Prepare and submit sale via hidden form (traditional POST)
function prepareAndSubmitSale() {
if (cart.length === 0) { Swal.fire({icon:'info', title:'Cart is empty'}); return; }
    const paymentMode = document.getElementById('paymentMode').value;
    if (!paymentMode) { Swal.fire({icon:'info', title:'Select payment mode'}); return; }
    const storeId = document.getElementById('storeSelect').value;
    const itemsPayload = cart.map(i => ({ productId: i.productId, quantity: i.quantity, unitPrice: i.unitPrice }));
    document.getElementById('form_storeId').value = storeId;
    document.getElementById('form_paymentMethod').value = paymentMode;
    const subtotal = cart.reduce((s,i)=>s+(i.unitPrice * i.quantity),0);
    const discountAmount = +(subtotal * appliedDiscount + appliedPoints).toFixed(2);
    document.getElementById('form_discountAmount').value = discountAmount;
    document.getElementById('form_pointsUsed').value = String(appliedPoints);
    document.getElementById('form_sale_items_json').value = JSON.stringify(itemsPayload);
    if (selectedCustomerId) document.getElementById('form_customerId').value = selectedCustomerId;
    // Cash -> payment modal; Credit -> credit modal; Card -> direct submit
if (paymentMode === 'Cash') {
        const totals = getCartTotals();
        openPaymentModal(totals.total);
        return;
    }
    if (paymentMode === 'Credit') {
        openCreditModal();
        return;
    }
    if (paymentMode === 'Card') {
        openCardModal();
        return;
    }
    document.getElementById('posForm').submit();
}

function completeSale(method, total) {
    // For Card or Credit we just submit form directly
    if (method !== 'Cash') {
        document.getElementById('posForm').submit();
        return;
    }
    // For Cash confirm -> submit hidden form
    document.getElementById('posForm').submit();
}

// Receipt modal functions
function openReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.add('show');
    const rb = document.getElementById('receiptModalBackdrop'); if (rb) rb.classList.add('show');
    document.body.classList.add('no-scroll'); document.documentElement.classList.add('no-scroll');
    // no auto-print; user can click Print button
}
function closeReceiptModal() {
    const modal = document.getElementById('receiptModal');
    modal.classList.remove('show');
    const rb = document.getElementById('receiptModalBackdrop'); if (rb) rb.classList.remove('show');
    document.body.classList.remove('no-scroll'); document.documentElement.classList.remove('no-scroll');
    // Reset cart on close (so cashier can start new order)
    cart = [];
    appliedDiscount = 0;
    discountCode = '';
    updateCart();
    // refresh interface for next transaction
    window.location.href = window.location.pathname;
}
function printReceipt(){ try { window.print(); } catch(e){ console.warn('Print failed', e); } }
function openReceiptWindow(id){ id = Number(id||0); if(!id){ Swal.fire({icon:'error', title:'No receipt available'}); return; } try { window.open('receipt.php?sale_id='+id, '_blank'); } catch(e){ console.warn('Popup blocked', e); } }


// helpers
function escapeHtml(s){ if(s===undefined||s===null) return ''; return String(s).replace(/[&<>\"'\/]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;',"'":'&#39;','/':'&#x2F;'})[c]); }
function escapeJs(s){ return (s||'').replace(/'/g,"\\'"); }
function filterByBrand(evt, b){ document.querySelectorAll('.category-tab').forEach(t=>t.classList.remove('active')); if (evt && evt.target) evt.target.classList.add('active'); document.getElementById('searchInput').value = (b||''); renderProducts(); }

// card modal
function openCardModal(){ document.getElementById('cardError').textContent=''; document.getElementById('cardModal').classList.add('show'); document.getElementById('cardModalBackdrop').classList.add('show'); }
function closeCardModal(){ document.getElementById('cardModal').classList.remove('show'); document.getElementById('cardModalBackdrop').classList.remove('show'); }
function confirmCardPayment(){
  const num = (document.getElementById('cardNumber').value||'').replace(/\s+/g,'');
  const holder = (document.getElementById('cardHolder').value||'').trim();
  const ref = (document.getElementById('cardRef').value||'').trim();
  const err = document.getElementById('cardError');
  if (!holder || num.length < 12 || !ref) { err.textContent='Please enter cardholder, a valid card number, and a reference code.'; return; }
  document.getElementById('form_cardLast4').value = num.slice(-4);
  document.getElementById('form_cardRef').value = ref;
  closeCardModal();
  document.getElementById('posForm').submit();
}

// init UI
buildCategoryTabs();
renderProducts();
updateCart();
document.getElementById('orderNumber').textContent = '-';
document.getElementById('paymentMode').addEventListener('change', updateCartButtons);
const ss = document.getElementById('storeSelect');
if (ss) { ss.addEventListener('change', e => { const id = e.target.value; const url = new URL(window.location.href); url.searchParams.set('store_id', id); window.location.href = url.toString(); }); }
// realtime clock
function tick(){ const el=document.getElementById('nowClock'); if(!el) return; const d=new Date(); el.textContent=d.toLocaleString(); }
setInterval(tick, 1000); tick();

/// If server-side processed sale and prepared receipt, allow user to choose
<?php if (!empty($openReceipt) && !empty($receiptSale)): ?>
    window.addEventListener('DOMContentLoaded', function() {
        // Offer to view receipt or start a new sale; printing available from the receipt view
        Swal.fire({ icon:'success', title:'Sale complete', showDenyButton:true, confirmButtonText:'View Receipt', denyButtonText:'New Sale' }).then(res=>{ if(res.isConfirmed){ openReceiptModal(); } else { window.location.href = window.location.pathname; } });
    });
<?php endif; ?>
</script>
</body>
</html>
