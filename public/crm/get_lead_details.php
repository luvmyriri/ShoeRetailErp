<?php
session_start();
require_once('../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_GET['lead_id'])) {
        $stmt = $db->prepare("SELECT * FROM leads WHERE LeadID = ?");
        $stmt->execute([$_GET['lead_id']]);
        $lead = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($lead) {
            header('Content-Type: application/json');
            echo json_encode($lead);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Lead not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Lead ID required']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>