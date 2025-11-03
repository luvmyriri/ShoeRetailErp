<?php
require 'config/database.php';

try {
    $db = getDB();
    
    echo "<h2>Products Table Columns</h2>";
    $cols = $db->fetchAll("DESCRIBE products");
    echo "<pre>";
    foreach ($cols as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    echo "<h2>Sales Table Columns</h2>";
    $cols = $db->fetchAll("DESCRIBE sales");
    echo "<pre>";
    foreach ($cols as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    echo "<h2>All Tables in Database</h2>";
    $tables = $db->fetchAll("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'shoeretailerp' ORDER BY TABLE_NAME");
    echo "<ul>";
    foreach ($tables as $t) {
        echo "<li>" . $t['TABLE_NAME'] . "</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "Error: " . htmlspecialchars($e->getMessage());
}
?>
