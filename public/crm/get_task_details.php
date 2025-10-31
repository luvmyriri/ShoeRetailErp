<?php
session_start();
require_once('../config/database.php');

try {
    $db = Database::getInstance()->getConnection();
    
    if (isset($_GET['task_id'])) {
        $stmt = $db->prepare("SELECT * FROM tasks WHERE TaskID = ?");
        $stmt->execute([$_GET['task_id']]);
        $task = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($task) {
            header('Content-Type: application/json');
            echo json_encode($task);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Task not found']);
        }
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Task ID required']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?>