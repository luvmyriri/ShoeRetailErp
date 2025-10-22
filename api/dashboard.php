<?php
/**
 * Dashboard API
 * Provides statistics and metrics for dashboard
 */

session_start();

require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
}

$action = $_GET['action'] ?? null;

try {
    switch ($action) {
        case 'get_stats':
            $stats = [
                'total_products' => dbFetchOne("SELECT COUNT(*) as count FROM Products WHERE Status = 'Active'")['count'] ?? 0,
                'low_stock_items' => dbFetchOne("SELECT COUNT(*) as count FROM Inventory i JOIN Products p ON i.ProductID = p.ProductID WHERE i.Quantity <= p.MinStockLevel")['count'] ?? 0,
                'todays_sales_count' => dbFetchOne("SELECT COUNT(*) as count FROM Sales WHERE DATE(SaleDate) = CURDATE()")['count'] ?? 0,
                'todays_sales_total' => dbFetchOne("SELECT COALESCE(SUM(TotalAmount), 0) as total FROM Sales WHERE DATE(SaleDate) = CURDATE()")['total'] ?? 0,
                'pending_tickets' => dbFetchOne("SELECT COUNT(*) as count FROM SupportTickets WHERE Status IN ('Open', 'In Progress')")['count'] ?? 0,
                'outstanding_receivables_count' => dbFetchOne("SELECT COUNT(*) as count FROM AccountsReceivable WHERE PaymentStatus != 'Paid'")['count'] ?? 0,
                'outstanding_receivables_total' => dbFetchOne("SELECT COALESCE(SUM(AmountDue - PaidAmount), 0) as total FROM AccountsReceivable WHERE PaymentStatus != 'Paid'")['total'] ?? 0
            ];
            
            jsonResponse(['success' => true, 'data' => $stats]);
            break;
        
        case 'get_low_stock':
            $lowStock = dbFetchAll("SELECT p.*, i.Quantity FROM Products p LEFT JOIN Inventory i ON p.ProductID = i.ProductID WHERE i.Quantity <= p.MinStockLevel ORDER BY i.Quantity ASC");
            
            jsonResponse(['success' => true, 'data' => $lowStock]);
            break;
        
        case 'get_recent_sales':
            $query = "
                SELECT s.SaleID, s.SaleDate, CONCAT(c.FirstName, ' ', COALESCE(c.LastName, '')) as CustomerName, 
                       s.TotalAmount, s.PaymentStatus
                FROM Sales s
                LEFT JOIN Customers c ON s.CustomerID = c.CustomerID
                ORDER BY s.SaleDate DESC
                LIMIT 10
            ";
            $sales = dbFetchAll($query);
            
            jsonResponse([
                'success' => true,
                'data' => $sales
            ]);
            break;
        
        case 'get_pending_orders':
            $query = "
                SELECT COUNT(*) as pending_count 
                FROM PurchaseOrders 
                WHERE Status = 'Pending'
            ";
            $result = dbFetchOne($query);
            
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        case 'get_outstanding_ar':
            $query = "
                SELECT COUNT(*) as count, COALESCE(SUM(AmountDue - PaidAmount), 0) as total
                FROM AccountsReceivable 
                WHERE PaymentStatus != 'Paid'
            ";
            $result = dbFetchOne($query);
            
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        case 'get_employees':
            $query = "SELECT COUNT(*) as count FROM Employees WHERE Status = 'Active'";
            $result = dbFetchOne($query);
            
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        case 'get_active_users':
            $query = "SELECT COUNT(*) as count FROM Users WHERE Status = 'Active'";
            $result = dbFetchOne($query);
            
            jsonResponse([
                'success' => true,
                'data' => $result
            ]);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    logError("Dashboard API error", ['action' => $action, 'error' => $e->getMessage()]);
    jsonResponse(['success' => false, 'message' => $e->getMessage()], 400);
}
?>
