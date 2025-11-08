<?php
/**
 * Procurement API (Integrated)
 * Endpoints aligned to MODULE_ENDPOINTS.md and INTEGRATION_GUIDE.md
 * Integrates with Inventory (stock) and Accounting (AP/GL) via core functions and stored procedures
 */

if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once '../config/database.php';
require_once '../includes/core_functions.php';
require_once '../includes/workflow_handlers.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($action) {
        // Purchase Orders
        case 'create_purchase_order': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_create_purchase_order')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $supplierId = (int)($data['supplier_id'] ?? 0);
            $storeId = (int)($data['store_id'] ?? ($_SESSION['store_id'] ?? 0));
            $expected = $data['expected_delivery_date'] ?? null;
            $items = $data['products'] ?? $data['items'] ?? [];
            if (!$supplierId || !$storeId || empty($items)) {
                throw new Exception('supplier_id, store_id and products/items are required');
            }
            // Normalize items to core function shape
            $products = [];
            foreach ($items as $it) {
                $products[] = [
                    'product_id' => (int)($it['product_id'] ?? 0),
                    'quantity' => (float)($it['quantity'] ?? 0),
                    'unit_cost' => (float)($it['unit_cost'] ?? 0),
                    'subtotal' => (float)($it['subtotal'] ?? ((float)($it['quantity'] ?? 0) * (float)($it['unit_cost'] ?? 0))),
                ];
            }
            $poId = createPurchaseOrder($supplierId, $storeId, $products, $expected);
            jsonResponse(['success' => true, 'message' => 'Purchase order created successfully', 'purchase_order_id' => $poId]);
            break;
        }

        case 'get_purchase_orders': // alias for list_pos
        case 'list_pos': {
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $status = $_GET['status'] ?? null; // Pending, Ordered, Received, etc.
            $limit = (int)($_GET['limit'] ?? 100);
            $offset = (int)($_GET['offset'] ?? 0);
            $rows = getPurchaseOrders($storeId, $status);
            $data = array_slice($rows, $offset, $limit);
            jsonResponse(['success' => true, 'data' => $data, 'total' => count($rows)]);
            break;
        }

        case 'get_purchase_order': // details
        case 'get_po_details': {
            $poId = (int)($_GET['po_id'] ?? $_GET['purchase_order_id'] ?? 0);
            if (!$poId) throw new Exception('Purchase Order ID required');
            $po = dbFetchOne("SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?", [$poId]);
            $details = dbFetchAll("SELECT * FROM PurchaseOrderDetails WHERE PurchaseOrderID = ?", [$poId]);
            jsonResponse(['success' => true, 'data' => ['po' => $po, 'details' => $details]]);
            break;
        }

        case 'receive_purchase_order': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_process_goods_receipt')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $poId = (int)($data['purchase_order_id'] ?? $data['po_id'] ?? 0);
            $received = $data['received_products'] ?? [];
            if (!$poId || empty($received)) {
                throw new Exception('purchase_order_id and received_products are required');
            }
            // Normalize received products
            $receivedProducts = [];
            foreach ($received as $it) {
                $receivedProducts[] = [
                    'product_id' => (int)($it['product_id'] ?? 0),
                    'quantity' => (float)($it['quantity'] ?? 0),
                    'unit_cost' => (float)($it['unit_cost'] ?? 0),
                    'subtotal' => (float)($it['subtotal'] ?? 0),
                ];
            }
            receivePurchaseOrder($poId, $receivedProducts);
            jsonResponse(['success' => true, 'message' => 'Purchase order received']);
            break;
        }

        case 'create_goods_receipt': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_process_goods_receipt')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            if (!isset($data['po_id'])) throw new Exception('Purchase Order ID required');
            $result = WorkflowHandler::processGoodsReceipt([
                'po_id' => (int)$data['po_id'],
                'notes' => $data['notes'] ?? null,
            ]);
            jsonResponse($result);
            break;
        }

        // Suppliers
        case 'get_suppliers': {
            $suppliers = getAllSuppliers();
            jsonResponse(['success' => true, 'data' => $suppliers]);
            break;
        }
        case 'add_supplier': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_manage_suppliers')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $required = ['supplier_name', 'contact_person', 'email', 'phone', 'address'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            // Prevent duplicates
            $existing = dbFetchOne("SELECT SupplierID FROM Suppliers WHERE Email = ? OR Phone = ?", [$data['email'], $data['phone']]);
            if ($existing) throw new Exception('Supplier with this email or phone already exists');
            $supplierId = dbInsert(
                "INSERT INTO Suppliers (SupplierName, ContactName, Email, Phone, Address, Status) VALUES (?, ?, ?, ?, ?, 'Active')",
                [$data['supplier_name'], $data['contact_person'], $data['email'], $data['phone'], $data['address']]
            );
            jsonResponse(['success' => true, 'message' => 'Supplier added successfully', 'supplier_id' => $supplierId]);
            break;
        }
        case 'update_supplier': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_manage_suppliers')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $supplierId = (int)($data['supplier_id'] ?? 0);
            if (!$supplierId) throw new Exception('supplier_id required');
            dbUpdate(
                "UPDATE Suppliers SET SupplierName = ?, ContactName = ?, Email = ?, Phone = ?, Address = ? WHERE SupplierID = ?",
                [$data['supplier_name'] ?? '', $data['contact_person'] ?? '', $data['email'] ?? null, $data['phone'] ?? null, $data['address'] ?? null, $supplierId]
            );
            jsonResponse(['success' => true, 'message' => 'Supplier updated']);
            break;
        }

        // Accounting/AP workflow convenience
        case 'request_to_pay': {
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'] ?? null, 'can_manage_suppliers')) {
                throw new Exception('Unauthorized');
            }
            $data = json_decode(file_get_contents('php://input'), true) ?? $_POST;
            $poId = (int)($data['purchase_order_id'] ?? 0);
            if (!$poId) throw new Exception('purchase_order_id required');
            dbUpdate("UPDATE AccountsPayable SET PaymentStatus = 'Request to Pay' WHERE PurchaseOrderID = ?", [$poId]);
            jsonResponse(['success' => true, 'message' => 'Request to Pay sent']);
            break;
        }

        // Reporting
        case 'get_goods_receipts': {
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $query = "SELECT gr.*, po.PurchaseOrderID, s.SupplierName FROM GoodsReceipts gr 
                     JOIN PurchaseOrders po ON gr.PurchaseOrderID = po.PurchaseOrderID
                     JOIN Suppliers s ON po.SupplierID = s.SupplierID
                     WHERE (? IS NULL OR po.StoreID = ?) ORDER BY gr.ReceiptDate DESC";
            $receipts = dbFetchAll($query, [$storeId, $storeId]);
            jsonResponse(['success' => true, 'data' => $receipts]);
            break;
        }
        case 'get_procurement_summary': {
            $startDate = $_GET['start_date'] ?? date('Y-m-01');
            $endDate = $_GET['end_date'] ?? date('Y-m-d');
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $query = "SELECT COUNT(*) as total_pos, COALESCE(SUM(TotalAmount),0) as total_spend FROM PurchaseOrders 
                     WHERE (? IS NULL OR StoreID = ?) AND DATE(OrderDate) BETWEEN ? AND ?";
            $stats = dbFetchOne($query, [$storeId, $storeId, $startDate, $endDate]);
            jsonResponse(['success' => true, 'data' => $stats]);
            break;
        }
        case 'export_purchase_orders': {
            if (!hasPermission($_SESSION['user_id'] ?? null, 'can_view_stock_reports')) {
                throw new Exception('Unauthorized');
            }
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $rows = dbFetchAll(
                "SELECT po.PurchaseOrderID, s.SupplierName, st.StoreName, po.TotalAmount, po.OrderDate, po.ExpectedDeliveryDate, po.Status
                 FROM PurchaseOrders po
                 JOIN Suppliers s ON po.SupplierID = s.SupplierID
                 JOIN Stores st ON po.StoreID = st.StoreID
                 WHERE (? IS NULL OR po.StoreID = ?) ORDER BY po.OrderDate DESC",
                [$storeId, $storeId]
            );
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="purchase_orders_' . date('Y-m-d') . '.csv"');
            $out = fopen('php://output', 'w');
            fputcsv($out, ['PO ID', 'Supplier', 'Store', 'Amount', 'Order Date', 'Expected Delivery', 'Status']);
            foreach ($rows as $r) { fputcsv($out, $r); }
            fclose($out);
            exit;
        }

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError('Procurement API error', ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
