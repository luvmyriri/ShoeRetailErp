<?php
/**
 * Inventory Management API
 * Handles all inventory-related operations
 */

session_start();

require_once '../config/database.php';

// Set JSON header
header('Content-Type: application/json');

/**
 * Get low stock items for a store
 */
function getLowStockItems($storeId = null) {
    $query = "SELECT p.*, i.Quantity, i.StoreID FROM Products p 
              LEFT JOIN Inventory i ON p.ProductID = i.ProductID 
              WHERE i.Quantity <= p.MinStockLevel AND p.Status = 'Active'";
    $params = [];
    
    if ($storeId) {
        $query .= " AND i.StoreID = ?";
        $params[] = $storeId;
    }
    
    $query .= " ORDER BY i.Quantity ASC";
    return dbFetchAll($query, $params);
}

/**
 * Update inventory quantity for a product at a store
 */
function updateInventory($productId, $storeId, $quantityChange) {
    $query = "UPDATE Inventory SET Quantity = Quantity + ? WHERE ProductID = ? AND StoreID = ?";
    return dbExecute($query, [$quantityChange, $productId, $storeId]);
}

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
            
            $query = "SELECT p.ProductID, p.SKU, p.Brand, p.Model, p.Size, p.Color, 
                             p.CostPrice, p.SellingPrice, p.MinStockLevel, p.MaxStockLevel, 
                             p.Status, i.Quantity, i.StoreID
                     FROM products p 
                     LEFT JOIN inventory i ON p.ProductID = i.ProductID AND i.StoreID = ?
                     WHERE p.Status = 'Active'";
            $params = [$storeId];
            
            if ($searchTerm) {
                $query .= " AND (p.SKU LIKE ? OR p.Brand LIKE ? OR p.Model LIKE ?)";
                $searchTerm = "%{$searchTerm}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
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
            
            $query = "INSERT INTO products (SKU, Brand, Model, Size, Color, CostPrice, SellingPrice, MinStockLevel, MaxStockLevel, Status) 
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
            
            // Create initial inventory entry for all stores
            $stores = dbFetchAll("SELECT StoreID FROM stores WHERE Status = 'Active'");
            foreach ($stores as $store) {
                dbExecute("INSERT INTO inventory (ProductID, StoreID, Quantity) VALUES (?, ?, 0)", 
                         [$productId, $store['StoreID']]);
            }
            
            logInfo('Product added', ['product_id' => $productId, 'sku' => $data['sku']]);
            jsonResponse(['success' => true, 'message' => 'Product added successfully', 'product_id' => $productId]);
            break;

        // REMOVED: Manual stock entry - stock is managed through Sales and Procurement
        case 'stock_entry':
            jsonResponse(['success' => false, 'message' => 'Manual stock adjustments are disabled. Use Procurement to add stock or Sales to reduce stock.'], 403);
            break;

        case 'get_product':
            $productId = $_GET['product_id'] ?? null;
            if (!$productId) {
                throw new Exception('Product ID required');
            }
            
            // Get product details
            $product = dbFetchOne("SELECT * FROM products WHERE ProductID = ?", [$productId]);
            
            // Get stock levels across all stores
            $stockLevels = dbFetchAll(
                "SELECT s.StoreName, i.Quantity, i.LastUpdated 
                 FROM inventory i 
                 JOIN stores s ON i.StoreID = s.StoreID 
                 WHERE i.ProductID = ? 
                 ORDER BY s.StoreName",
                [$productId]
            );
            
            jsonResponse(['success' => true, 'data' => [
                'product' => $product,
                'stock_levels' => $stockLevels
            ]]);
            break;

        case 'get_low_stock':
            $storeId = $_GET['store_id'] ?? null;
            $items = getLowStockItems($storeId);
            jsonResponse(['success' => true, 'data' => $items]);
            break;
        
        case 'get_stock_view':
            // Get all products with their stock levels across all stores
            $query = "SELECT p.ProductID, p.SKU, p.Brand, p.Model, p.Size, p.Color, 
                             p.CostPrice, p.SellingPrice, p.MinStockLevel, p.MaxStockLevel, 
                             p.Status, i.Quantity, i.LastUpdated, s.StoreName, s.StoreID
                     FROM products p 
                     JOIN inventory i ON p.ProductID = i.ProductID
                     JOIN stores s ON i.StoreID = s.StoreID
                     WHERE p.Status = 'Active' AND s.Status = 'Active'
                     ORDER BY s.StoreName, p.Brand, p.Model";
            
            $stockView = dbFetchAll($query);
            jsonResponse(['success' => true, 'data' => $stockView]);
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

        case 'update_product':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['product_id'])) {
                throw new Exception('Product ID is required');
            }
            
            $query = "UPDATE products SET Brand = ?, Model = ?, Size = ?, Color = ?, 
                     CostPrice = ?, SellingPrice = ?, MinStockLevel = ?, MaxStockLevel = ?, Status = ? 
                     WHERE ProductID = ?";
            
            dbExecute($query, [
                $data['brand'] ?? null,
                $data['model'] ?? null,
                $data['size'] ?? null,
                $data['color'] ?? null,
                $data['cost_price'] ?? 0,
                $data['selling_price'] ?? 0,
                $data['min_stock'] ?? 10,
                $data['max_stock'] ?? 100,
                $data['status'] ?? 'Active',
                $data['product_id']
            ]);
            
            logInfo('Product updated', ['product_id' => $data['product_id']]);
            jsonResponse(['success' => true, 'message' => 'Product updated successfully']);
            break;

        case 'get_inventory_value':
            $query = "SELECT SUM(i.Quantity * p.CostPrice) as inventory_value FROM inventory i JOIN products p ON i.ProductID = p.ProductID";
            $result = dbFetchOne($query);
            jsonResponse(['success' => true, 'data' => $result]);
            break;
        
        case 'get_stores':
            $stores = dbFetchAll("SELECT StoreID, StoreName, Location FROM stores WHERE Status = 'Active' ORDER BY StoreName");
            jsonResponse(['success' => true, 'data' => $stores]);
            break;
        
        case 'request_restock':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!isset($data['product_id']) || !isset($data['store_id']) || !isset($data['quantity'])) {
                throw new Exception('Product ID, Store ID, and Quantity are required');
            }
            
            // Get product details
            $product = dbFetchOne("SELECT * FROM products WHERE ProductID = ?", [$data['product_id']]);
            if (!$product) {
                throw new Exception('Product not found');
            }
            
            // Get supplier for the product
            $supplierId = $product['SupplierID'] ?? null;
            
            // Create a purchase order request
            getDB()->beginTransaction();
            
            try {
                $poQuery = "INSERT INTO purchaseorders (SupplierID, StoreID, TotalAmount, Status, CreatedBy) 
                           VALUES (?, ?, ?, 'Pending', ?)";
                
                $totalAmount = $data['quantity'] * $product['CostPrice'];
                
                $poId = dbInsert($poQuery, [
                    $supplierId,
                    $data['store_id'],
                    $totalAmount,
                    $_SESSION['username'] ?? 'system'
                ]);
                
                // Add purchase order detail
                $detailQuery = "INSERT INTO purchaseorderdetails (PurchaseOrderID, ProductID, Quantity, UnitCost, Subtotal) 
                               VALUES (?, ?, ?, ?, ?)";
                
                dbExecute($detailQuery, [
                    $poId,
                    $data['product_id'],
                    $data['quantity'],
                    $product['CostPrice'],
                    $totalAmount
                ]);
                
                getDB()->commit();
                
                logInfo('Restock request created', [
                    'po_id' => $poId,
                    'product_id' => $data['product_id'],
                    'quantity' => $data['quantity']
                ]);
                
                jsonResponse([
                    'success' => true, 
                    'message' => 'Restock request created successfully',
                    'po_id' => $poId
                ]);
            } catch (Exception $e) {
                getDB()->rollBack();
                throw $e;
            }
            break;

        case 'export_inventory':
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment;filename="inventory_export.csv"');
            
            $products = dbFetchAll("SELECT p.*, i.Quantity FROM Products p LEFT JOIN Inventory i ON p.ProductID = i.ProductID ORDER BY p.Brand, p.Model");
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ProductID', 'SKU', 'Brand', 'Model', 'Size', 'Color', 'CostPrice', 'SellingPrice', 'Quantity', 'MinStockLevel', 'MaxStockLevel', 'Status']);
            
            foreach ($products as $product) {
                fputcsv($output, [
                    $product['ProductID'],
                    $product['SKU'],
                    $product['Brand'],
                    $product['Model'],
                    $product['Size'],
                    $product['Color'],
                    $product['CostPrice'],
                    $product['SellingPrice'],
                    $product['Quantity'],
                    $product['MinStockLevel'],
                    $product['MaxStockLevel'],
                    $product['Status']
                ]);
            }
            
            fclose($output);
            exit;

        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Inventory API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>