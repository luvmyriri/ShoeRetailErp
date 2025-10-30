<?php
session_start();
require_once('../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_GET['deal_id'])) {
        $stmt = $db->prepare("SELECT * FROM deals WHERE DealID = ?");
        $stmt->execute([$_GET['deal_id']]);
        $deal = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deal) {
            header('Content-Type: application/json');
            echo json_encode($deal);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Deal not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Deal ID required']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>