<?php
/**
 * Inventory Management API
 * Handles all inventory-related operations
 */

session_start();

require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];
$db = getDB();

try {
    switch ($action) {
        case 'get_products':
            $storeId = $_GET['store_id'] ?? 1;
            $searchTerm = $_GET['search'] ?? null;
            
            $query = "SELECT p.*, i.Quantity FROM Products p 
                     LEFT JOIN Inventory i ON p.ProductID = i.ProductID 
                     WHERE p.Status = 'Active'";
            $params = [];
            
            if ($searchTerm) {
                $query .= " AND (p.SKU LIKE ? OR p.Brand LIKE ? OR p.Model LIKE ?)";
                $searchTerm = "%{$searchTerm}%";
                $params = [$searchTerm, $searchTerm, $searchTerm];
            }
            
            $query .= " ORDER BY p.Brand, p.Model";
            $products = dbFetchAll($query, $params);
            jsonResponse(['success' => true, 'data' => $products]);
            break;

        case 'add_product':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "INSERT INTO Products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, MinStockLevel, MaxStockLevel, Status) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active')";
            
            $productId = dbInsert($query, [
                $data['sku'] ?? null,
                $data['brand'] ?? null,
                $data['model'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                $data['cost_price'] ?? 0,
                $data['selling_price'] ?? 0,
                $data['min_stock'] ?? 10,
                $data['max_stock'] ?? 100
            ]);
            
            logInfo('Product added', ['product_id' => $productId, 'sku' => $data['sku']]);
            jsonResponse(['success' => true, 'message' => 'Product added successfully', 'product_id' => $productId]);
            break;

        case 'stock_entry':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            $query = "INSERT INTO Inventory (ProductID, StoreID, Quantity) 
                     VALUES (?, ?, ?) 
                     ON DUPLICATE KEY UPDATE Quantity = Quantity + ?";
            
            dbExecute($query, [
                $data['product_id'],
                $data['store_id'] ?? 1,
                $data['quantity'] ?? 0,
                $data['quantity'] ?? 0
            ]);
            
            logInfo('Stock entry created', ['product_id' => $data['product_id'], 'quantity' => $data['quantity']]);
            jsonResponse(['success' => true, 'message' => 'Stock updated successfully']);
            break;

        case 'get_low_stock':
            $storeId = $_GET['store_id'] ?? null;
            $items = getLowStockItems($storeId);
            jsonResponse(['success' => true, 'data' => $items]);
            break;

        case 'get_stock_movements':
            $productId = $_GET['product_id'] ?? null;
            $storeId = $_GET['store_id'] ?? null;
            
            $query = "SELECT * FROM StockMovements WHERE 1=1";
            $params = [];
            
            if ($productId) {
                $query .= " AND ProductID = ?";
                $params[] = $productId;
            }
            if ($storeId) {
                $query .= " AND StoreID = ?";
                $params[] = $storeId;
            }
            
            $query .= " ORDER BY MovementDate DESC";
            $movements = dbFetchAll($query, $params);
            jsonResponse(['success' => true, 'data' => $movements]);
            break;

        case 'transfer_stock':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            getDB()->beginTransaction();
            
            // Deduct from source
            updateInventory($data['product_id'], $data['from_store'], -$data['quantity']);
            
            // Add to destination
            updateInventory($data['product_id'], $data['to_store'], $data['quantity']);
            
            getDB()->commit();
            
            jsonResponse(['success' => true, 'message' => 'Stock transferred successfully']);
            break;

        case 'get_inventory_value':
            $query = "SELECT SUM(i.Quantity * p.CostPrice) as inventory_value FROM Inventory i JOIN Products p ON i.ProductID = p.ProductID";
            $result = dbFetchOne($query);
            jsonResponse(['success' => true, 'data' => $result]);
            break;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Inventory API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
