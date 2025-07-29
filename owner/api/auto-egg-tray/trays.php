<?php
require_once __DIR__ . '/../../../config.php';
header('Content-Type: application/json');

$farmId = $_GET['farm_id'] ?? 0;
$status = $_GET['status'] ?? '';

if (!$farmId) {
    echo json_encode(['error' => 'Invalid farm ID']);
    exit;
}

try {
    $mysqli = db_connect();
    
    $query = "SELECT * FROM trays WHERE farm_id = ?";
    $params = [$farmId];
    $types = "i";
    
    if ($status) {
        $query .= " AND status = ?";
        $params[] = $status;
        $types .= "s";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    $trays = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode($trays);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}