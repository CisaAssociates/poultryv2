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
    
    $stmt = $mysqli->prepare("
        SELECT size, COUNT(egg_data.id) AS egg_count
        FROM egg_data
        JOIN devices ON egg_data.mac = devices.device_mac
        WHERE devices.device_owner_id = ? 
          AND egg_data.id NOT IN (SELECT egg_id FROM tray_eggs)
        GROUP BY size
    ");
    $stmt->bind_param("i", $farmId);
    $stmt->execute();
    $result = $stmt->get_result();
    $progressData = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($progressData);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}