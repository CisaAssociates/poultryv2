<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

// Verify token
if (!verify_token($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Get form data
$farm_id = (int)$_POST['farm_id'];
$size = $_POST['size'] ?? '';
$stock_count = (int)$_POST['stock_count'] ?? 0;
$price = (float)$_POST['price'] ?? 0;
$device_mac = $_POST['device_mac'] ?? '';

// Validate input
if (empty($size) || $stock_count <= 0 || $price <= 0 || empty($device_mac)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit;
}

$mysqli = db_connect();

// Insert tray
$query = "INSERT INTO trays (farm_id, device_mac, size, egg_count, stock_count, price, status, published_at) 
          VALUES (?, ?, ?, 30, ?, ?, 'published', NOW())";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("issid", $farm_id, $device_mac, $size, $stock_count, $price);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Tray added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add tray: ' . $mysqli->error]);
}