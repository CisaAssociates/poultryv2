<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$farmId = $data['farm_id'] ?? 0;
$settings = $data['settings'] ?? [];

if (!$farmId || empty($settings)) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $mysqli = db_connect();
    $mysqli->begin_transaction();
    
    // Delete existing settings
    $deleteStmt = $mysqli->prepare("DELETE FROM tray_settings WHERE farm_id = ?");
    $deleteStmt->bind_param("i", $farmId);
    $deleteStmt->execute();
    
    // Insert new settings
    foreach ($settings as $size => $config) {
        $insertStmt = $mysqli->prepare("
            INSERT INTO tray_settings (farm_id, size, default_price, auto_publish)
            VALUES (?, ?, ?, ?)
        ");
        $autoPublish = $config['auto_publish'] ? 1 : 0;
        $insertStmt->bind_param("isdi", $farmId, $size, $config['price'], $autoPublish);
        $insertStmt->execute();
    }
    
    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully']);
} catch (Exception $e) {
    try {
        $mysqli->rollback();
    } catch (Exception $rollbackException) {
        // Transaction wasn't active or rollback failed
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}