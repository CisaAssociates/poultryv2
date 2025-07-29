<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

if (!validate_farm_access($farm_id)) {
    // For employees, automatically use their assigned farm
    if ($user['role_id'] == 3) {
        $farm_id = $user['farm_id'];
    } else {
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit;
    }
}

$mysqli = db_connect();
$tax_rate = 0;

// Get active tax rate for farm
$stmt = $mysqli->prepare("
    SELECT tax_rate 
    FROM tax_settings 
    WHERE farm_id = ? AND is_active = 1
    ORDER BY is_default DESC, created_at DESC
    LIMIT 1
");
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $tax_rate = (float)$row['tax_rate'];
}

echo json_encode(['success' => true, 'tax_rate' => $tax_rate]);