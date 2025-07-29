<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$trayId = $data['tray_id'] ?? 0;
$price = $data['price'] ?? null;
$imageUrl = $data['image_url'] ?? null;
$farmId = $data['farm_id'] ?? 0;

if (!$trayId || !$farmId) {
    echo json_encode(['success' => false, 'message' => 'Invalid tray or farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    // Get default price if not provided
    if ($price === null) {
        $stmt = $mysqli->prepare("
            SELECT default_price 
            FROM tray_settings 
            WHERE farm_id = ? 
              AND size = (SELECT size FROM trays WHERE tray_id = ?)
        ");
        $stmt->bind_param("ii", $farmId, $trayId);
        $stmt->execute();
        $result = $stmt->get_result();
        $price = $result->fetch_assoc()['default_price'] ?? 0;
    }
    
    // Update tray status
    $updateStmt = $mysqli->prepare("
        UPDATE trays 
        SET status = 'published', 
            price = ?, 
            image_url = ?, 
            published_at = NOW() 
        WHERE tray_id = ? AND farm_id = ?
    ");
    $updateStmt->bind_param("dsii", $price, $imageUrl, $trayId, $farmId);
    $updateStmt->execute();
    
    if ($updateStmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => 'Tray published successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Tray not found or update failed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}