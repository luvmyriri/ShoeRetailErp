<?php
/**
 * Sales Management API
 * Handles orders, invoices, and returns
 */

require_once '../config/database.php';
require_once '../includes/core_functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'get_orders':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $query = "SELECT s.SaleID, s.CustomerID, s.StoreID, s.SaleDate, s.TotalAmount, s.TaxAmount, s.DiscountAmount, s.PaymentStatus, s.PaymentMethod,
                             CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) AS CustomerName
                      FROM sales s
                      LEFT JOIN customers c ON s.CustomerID = c.CustomerID
                      WHERE 1=1";
            $params = [];
            if ($storeId) { $query .= " AND s.StoreID = ?"; $params[] = $storeId; }
            if ($status) { $query .= " AND s.PaymentStatus = ?"; $params[] = $status; }
            $query .= " ORDER BY s.SaleDate DESC LIMIT ? OFFSET ?";
            $params[] = $limit; $params[] = $offset;
            $orders = dbFetchAll($query, $params);
            jsonResponse(['success' => true, 'data' => $orders]);
            break;

        case 'create_sale':
            if ($method !== 'POST' || !hasPermission('Cashier')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $saleId = processSale($data['customer_id'], $_SESSION['store_id'], $data['products'], 
                                 $data['payment_method'], $data['discount'] ?? 0);
            
            // Add loyalty points if customer made payment
            if ($data['payment_method'] !== 'Credit' && $data['customer_id']) {
                $points = floor($data['total'] / 10); // 1 point per $10
                updateLoyaltyPoints($data['customer_id'], $points);
            }
            
            jsonResponse(['success' => true, 'message' => 'Sale created', 'sale_id' => $saleId]);
            break;

        case 'get_sale_details':
            $saleId = $_GET['sale_id'] ?? null;
            if (!$saleId) throw new Exception('Sale ID required');
            
            $sale = dbFetchOne("SELECT * FROM sales WHERE SaleID = ?", [$saleId]);
            $details = dbFetchAll("SELECT * FROM saledetails WHERE SaleID = ?", [$saleId]);
            
            jsonResponse(['success' => true, 'data' => ['sale' => $sale, 'details' => $details]]);
            break;

        case 'get_invoices':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            $status = $_GET['status'] ?? null;
            $limit = $_GET['limit'] ?? 100;
            
            $db = getDB();
            $query = "SELECT * FROM sales WHERE StoreID = ?";
            $params = [$storeId];
            
            if ($status) {
                $query .= " AND PaymentStatus = ?";
                $params[] = $status;
            }
            
            $query .= " ORDER BY SaleDate DESC LIMIT ?";
            $params[] = $limit;
            
            $invoices = $db->fetchAll($query, $params);
            
            jsonResponse(['success' => true, 'data' => $invoices]);
            break;

        case 'process_return':
            if ($method !== 'POST' || !hasPermission('Manager')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $refundAmount = processReturn($data['sale_id'], $data['items'], $data['reason'] ?? null);
            
            jsonResponse(['success' => true, 'message' => 'Return processed', 'refund_amount' => $refundAmount]);
            break;

        case 'get_sales_summary':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? null;
            
            $summary = getSalesSummary($startDate, $endDate, $storeId);
            
            $stats = [];
            $stats['total_sales'] = count($summary);
            $stats['total_revenue'] = array_sum(array_column($summary, 'TotalAmount'));
            $stats['average_order'] = $stats['total_sales'] > 0 ? $stats['total_revenue'] / $stats['total_sales'] : 0;
            
            jsonResponse(['success' => true, 'data' => ['summary' => $summary, 'stats' => $stats]]);
            break;

        case 'get_daily_sales':
            $date = $_GET['date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $result = dbFetchOne("SELECT DATE(SaleDate) as date, COUNT(*) as count, SUM(TotalAmount) as total FROM sales WHERE (? IS NULL OR StoreID = ?) AND DATE(SaleDate) = ? GROUP BY DATE(SaleDate)", [$storeId, $storeId, $date]);
            jsonResponse(['success' => true, 'data' => $result ?? ['count' => 0, 'total' => 0, 'date' => $date]]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Sales API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
