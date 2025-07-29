<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

$farmId = $_GET['farm_id'] ?? 0;

if (!$farmId) {
    echo json_encode(['error' => 'Invalid farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    $stats = [
        'pending' => 0,
        'published' => 0,
        'sold' => 0,
        'expired' => 0,
        'expiring' => 0
    ];
    
    // Get tray counts by status
    $stmt = $mysqli->prepare("
        SELECT 
            SUM(IF(status = 'pending', 1, 0)) AS pending,
            SUM(IF(status = 'published', 1, 0)) AS published,
            SUM(IF(status = 'sold', 1, 0)) AS sold,
            SUM(IF(status = 'expired', 1, 0)) AS expired,
            SUM(IF(expired_at <= DATE_ADD(NOW(), INTERVAL 3 DAY) AND status = 'published', 1, 0)) AS expiring
        FROM trays
        WHERE farm_id = ?
    ");
    $stmt->bind_param("i", $farmId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stats = array_merge($stats, $row);
    }
    
    echo json_encode($stats);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}