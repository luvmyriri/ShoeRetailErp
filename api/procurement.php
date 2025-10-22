<?php
/**
 * Procurement Management API
 * Handles purchase orders and supplier management
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
        case 'create_purchase_order':
            if ($method !== 'POST' || !hasPermission('Manager')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $poId = createPurchaseOrder($data['supplier_id'], $_SESSION['store_id'], 
                                      $data['products'], $data['expected_delivery_date'] ?? null);
            
            jsonResponse(['success' => true, 'message' => 'Purchase order created', 'po_id' => $poId]);
            break;

        case 'get_purchase_orders':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            $status = $_GET['status'] ?? null;
            
            $pos = getPurchaseOrders($storeId, $status);
            jsonResponse(['success' => true, 'data' => $pos]);
            break;

        case 'get_po_details':
            $poId = $_GET['po_id'] ?? null;
            if (!$poId) throw new Exception('PO ID required');
            
            $po = dbFetchOne("SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?", [$poId]);
            $details = dbFetchAll("SELECT * FROM PurchaseOrderDetails WHERE PurchaseOrderID = ?", [$poId]);
            
            jsonResponse(['success' => true, 'data' => ['po' => $po, 'details' => $details]]);
            break;

        case 'receive_purchase_order':
            if ($method !== 'POST' || !hasPermission('Manager')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            receivePurchaseOrder($data['po_id'], $data['received_products']);
            
            jsonResponse(['success' => true, 'message' => 'Purchase order received']);
            break;

        case 'get_suppliers':
            $suppliers = getAllSuppliers();
            jsonResponse(['success' => true, 'data' => $suppliers]);
            break;

        case 'add_supplier':
            if ($method !== 'POST' || !hasPermission('Manager')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "INSERT INTO Suppliers (SupplierName, ContactPerson, Email, Phone, Address) VALUES (?, ?, ?, ?, ?)";
            $supplierId = dbInsert($query, [
                $data['name'], $data['contact'], $data['email'], $data['phone'], $data['address']
            ]);
            
            jsonResponse(['success' => true, 'message' => 'Supplier added', 'supplier_id' => $supplierId]);
            break;

        case 'get_goods_receipts':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $query = "SELECT gr.*, po.PurchaseOrderID, s.SupplierName FROM GoodsReceipts gr 
                     JOIN PurchaseOrders po ON gr.PurchaseOrderID = po.PurchaseOrderID
                     JOIN Suppliers s ON po.SupplierID = s.SupplierID
                     WHERE po.StoreID = ? ORDER BY gr.ReceiptDate DESC";
            
            $receipts = dbFetchAll($query, [$storeId]);
            jsonResponse(['success' => true, 'data' => $receipts]);
            break;

        case 'create_goods_receipt':
            if ($method !== 'POST' || !hasPermission('Manager')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "INSERT INTO GoodsReceipts (PurchaseOrderID, ReceiptDate, Notes) VALUES (?, NOW(), ?)";
            $receiptId = dbInsert($query, [$data['po_id'], $data['notes'] ?? null]);
            
            jsonResponse(['success' => true, 'message' => 'Goods receipt created', 'receipt_id' => $receiptId]);
            break;

        case 'get_procurement_summary':
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'];
            
            $query = "SELECT COUNT(*) as total_pos, SUM(TotalAmount) as total_spend FROM PurchaseOrders 
                     WHERE StoreID = ? AND DATE(OrderDate) BETWEEN ? AND ?";
            
            $stats = dbFetchOne($query, [$storeId, $startDate, $endDate]);
            jsonResponse(['success' => true, 'data' => $stats]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Procurement API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
