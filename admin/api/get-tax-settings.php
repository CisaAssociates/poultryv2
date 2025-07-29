<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
$response = ['success' => false, 'data' => []];

if (!is_logged_in() || !in_array($user['role_id'], [1, 2])) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
} 

$mysqli = db_connect();

$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

if (!validate_farm_access($farm_id)) {
    $response['message'] = 'Access denied to farm';
    echo json_encode($response);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM tax_settings WHERE farm_id = ?");
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();

$taxSettings = [];
while ($row = $result->fetch_assoc()) {
    $taxSettings[] = $row;
}

$response['success'] = true;
$response['data'] = $taxSettings;
echo json_encode($response);