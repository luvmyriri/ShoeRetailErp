<?php
require 'config/database.php';

try {
    $db = getDB();
    $result = $db->fetchOne("SELECT 1 as test");
    echo "✓ Database connection successful!<br>";
    echo "Test result: " . json_encode($result);
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage();
}
?>
