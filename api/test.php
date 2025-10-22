<?php
/**
 * Test API - Verify system is working
 */

session_start();

require_once '../config/database.php';

header('Content-Type: application/json');

try {
    $db = getDB();
    
    // Test 1: Database connection
    $test = dbFetchOne("SELECT 1 as test");
    if ($test) {
        echo json_encode(['success' => true, 'message' => 'Database connected!', 'test' => $test]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
