<?php
require 'config/database.php';

try {
    $db = getDB();
    
    // Get all tables
    $tables = $db->fetchAll("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'shoeretailerp'");
    
    echo "<h2>Database Tables</h2>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>" . htmlspecialchars($table['TABLE_NAME']) . "</li>";
    }
    echo "</ul>";
    
    // Check key tables for data
    echo "<h2>Data Summary</h2>";
    
    $stats = [
        'products' => 'SELECT COUNT(*) as cnt FROM products',
        'customers' => 'SELECT COUNT(*) as cnt FROM customers',
        'employees' => 'SELECT COUNT(*) as cnt FROM employees',
        'sales' => 'SELECT COUNT(*) as cnt FROM sales',
        'purchase_orders' => 'SELECT COUNT(*) as cnt FROM purchase_orders'
    ];
    
    foreach ($stats as $table => $query) {
        try {
            $result = $db->fetchOne($query);
            echo "<p><strong>" . ucfirst($table) . ":</strong> " . ($result['cnt'] ?? 0) . " records</p>";
        } catch (Exception $e) {
            echo "<p><strong>" . ucfirst($table) . ":</strong> (table error)</p>";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
