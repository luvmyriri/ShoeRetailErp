<?php
/**
 * Procurement Management API
 * Handles purchase orders, suppliers, and goods receipt
 */

require_once '../includes/db_helper.php';
require_once '../includes/core_functions.php';
require_once '../includes/role_management_functions.php';

header('Content-Type: application/json');

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        case 'create_purchase_order':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_create_purchase_order')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $required = ['supplier_id', 'store_id', 'products', 'expected_delivery_date'];
            
            foreach ($required as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            $poId = createPurchaseOrder($data['supplier_id'], $data['store_id'], 
                                       $data['products'], $data['expected_delivery_date']);
            
            logInfo("Purchase order created", ['po_id' => $poId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Purchase order created successfully',
                'purchase_order_id' => $poId
            ]);
            break;
        
        case 'get_purchase_orders':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            $status = $_GET['status'] ?? null;
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $pos = getPurchaseOrders($storeId, $status);
            $paginated = array_slice($pos, $offset, $limit);
            
            jsonResponse([
                'success' => true,
                'data' => $paginated,
                'total' => count($pos)
            ]);
            break;
        
        case 'get_purchase_order':
            $poId = $_GET['po_id'] ?? null;
            if (!$poId) {
                throw new Exception('Purchase Order ID required');
            }
            
            $po = dbFetchOne("SELECT * FROM PurchaseOrders WHERE PurchaseOrderID = ?", [$poId]);
            $details = dbFetchAll("SELECT * FROM PurchaseOrderDetails WHERE PurchaseOrderID = ?", [$poId]);
            
            jsonResponse([
                'success' => true,
                'data' => ['po' => $po, 'details' => $details]
            ]);
            break;
        
        case 'receive_purchase_order':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_process_goods_receipt')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $poId = $data['purchase_order_id'] ?? null;
            
            if (!$poId) {
                throw new Exception('Purchase Order ID required');
            }
            
            receivePurchaseOrder($poId, $data['received_products'] ?? []);
            
            logInfo("Purchase order received", ['po_id' => $poId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Purchase order received successfully'
            ]);
            break;
        
        case 'get_suppliers':
            $suppliers = getAllSuppliers();
            
            jsonResponse([
                'success' => true,
                'data' => $suppliers
            ]);
            break;
        
        case 'add_supplier':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_manage_suppliers')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $required = ['supplier_name', 'contact_person', 'email', 'phone', 'address'];
            
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception("Field '{$field}' is required");
                }
            }
            
            // Check for duplicate email/phone
            $existing = dbFetchOne(
                "SELECT SupplierID FROM Suppliers WHERE Email = ? OR Phone = ?",
                [$data['email'], $data['phone']]
            );
            
            if ($existing) {
                throw new Exception('Supplier with this email or phone already exists');
            }
            
            $supplierId = dbInsert(
                "INSERT INTO Suppliers (SupplierName, ContactPerson, Email, Phone, Address, Status) 
                 VALUES (?, ?, ?, ?, ?, 'Active')",
                [$data['supplier_name'], $data['contact_person'], $data['email'], $data['phone'], $data['address']]
            );
            
            logInfo("Supplier added", ['supplier_id' => $supplierId]);
            
            jsonResponse([
                'success' => true,
                'message' => 'Supplier added successfully',
                'supplier_id' => $supplierId
            ]);
            break;
        
        case 'update_supplier':
            if ($method !== 'POST' || !hasPermission($_SESSION['user_id'], 'can_manage_suppliers')) {
                throw new Exception('Unauthorized');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            $supplierId = $data['supplier_id'] ?? null;
            
            if (!$supplierId) {
                throw new Exception('Supplier ID required');
            }
            
            $query = "UPDATE Suppliers SET SupplierName = ?, ContactPerson = ?, Email = ?, Phone = ?, Address = ? WHERE SupplierID = ?";
            dbUpdate($query, [
                $data['supplier_name'],
                $data['contact_person'],
                $data['email'],
                $data['phone'],
                $data['address'],
                $supplierId
            ]);
            
            logInfo("Supplier updated", ['supplier_id' => $supplierId]);
            
            jsonResponse(['success' => true, 'message' => 'Supplier updated successfully']);
            break;
        
        case 'get_pending_orders':
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $query = "SELECT COUNT(*) as count FROM PurchaseOrders WHERE Status = 'Pending'";
            $params = [];
            
            if ($storeId) {
                $query .= " AND StoreID = ?";
                $params[] = $storeId;
            }
            
            $result = dbFetchOne($query, $params);
            
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        case 'export_purchase_orders':
            if (!hasPermission($_SESSION['user_id'], 'can_view_stock_reports')) {
                throw new Exception('Unauthorized');
            }
            
            $storeId = $_GET['store_id'] ?? $_SESSION['store_id'] ?? null;
            
            $query = "
                SELECT po.PurchaseOrderID, s.SupplierName, st.StoreName, po.TotalAmount, 
                       po.OrderDate, po.ExpectedDeliveryDate, po.Status
                FROM PurchaseOrders po
                JOIN Suppliers s ON po.SupplierID = s.SupplierID
                JOIN Stores st ON po.StoreID = st.StoreID
                WHERE 1=1
            ";
            
            $params = [];
            if ($storeId) {
                $query .= " AND po.StoreID = ?";
                $params[] = $storeId;
            }
            
            $data = dbFetchAll($query, $params);
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="purchase_orders_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['PO ID', 'Supplier', 'Store', 'Amount', 'Order Date', 'Expected Delivery', 'Status']);
            
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
            
            fclose($output);
            exit;
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Procurement API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
