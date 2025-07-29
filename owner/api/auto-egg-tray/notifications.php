<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

$farmId = $_GET['farm_id'] ?? 0;

if (!$farmId) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    // Get notifications
    $stmt = $mysqli->prepare("
        SELECT * 
        FROM notifications 
        WHERE farm_id = ? 
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->bind_param("i", $farmId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = $result->fetch_all(MYSQLI_ASSOC);
    
    // Mark notifications as read if requested
    if (isset($_GET['mark_read']) && $_GET['mark_read'] == 'all') {
        $updateStmt = $mysqli->prepare("
            UPDATE notifications 
            SET is_read = 1 
            WHERE farm_id = ? AND is_read = 0
        ");
        $updateStmt->bind_param("i", $farmId);
        $updateStmt->execute();
    }
    
    echo json_encode($notifications);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}