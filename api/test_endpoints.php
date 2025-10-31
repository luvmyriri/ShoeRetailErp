<?php
session_start();
$_SESSION['user_id'] = 1;

require_once '../config/database.php';

header('Content-Type: application/json');

// Test inventory endpoints
$tests = [];

// Test 1: Get products
try {
    $products = dbFetchAll("SELECT COUNT(*) as count FROM Products");
    $tests[] = ['test' => 'get_products_count', 'status' => 'OK', 'data' => $products];
} catch (Exception $e) {
    $tests[] = ['test' => 'get_products_count', 'status' => 'ERROR', 'error' => $e->getMessage()];
}

// Test 2: Dashboard query
try {
    $stats = dbFetchOne("SELECT COUNT(*) as count FROM Products WHERE Status = 'Active'");
    $tests[] = ['test' => 'dashboard_stats', 'status' => 'OK', 'data' => $stats];
} catch (Exception $e) {
    $tests[] = ['test' => 'dashboard_stats', 'status' => 'ERROR', 'error' => $e->getMessage()];
}

// Test 3: Inventory value
try {
    $value = dbFetchOne("SELECT SUM(i.Quantity * p.CostPrice) as inventory_value FROM Inventory i JOIN Products p ON i.ProductID = p.ProductID");
    $tests[] = ['test' => 'inventory_value', 'status' => 'OK', 'data' => $value];
} catch (Exception $e) {
    $tests[] = ['test' => 'inventory_value', 'status' => 'ERROR', 'error' => $e->getMessage()];
}

echo json_encode(['success' => true, 'tests' => $tests]);
?>
