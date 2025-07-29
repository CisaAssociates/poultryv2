<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

if (!validate_farm_access($farm_id)) {
    if ($user['role_id'] == 3) {
        $farm_id = $user['farm_id'];
    } else {
        echo json_encode(['error' => 'Access denied']);
        exit;
    }
}

$mysqli = db_connect();
$stmt = $mysqli->prepare("
    SELECT t.tray_id, t.size, t.price, t.stock_count 
    FROM trays t
    WHERE t.farm_id = ? 
    AND t.status = 'published' 
    AND t.stock_count > 0
");
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
