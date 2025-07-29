<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$mysqli = new mysqli('localhost', 'root', '', 'poultryv2_db');
if ($mysqli->connect_errno) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to connect to MySQL: ' . $mysqli->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
$mac = $data['mac'] ?? '';
$egg_weight = floatval($data['egg_weight'] ?? 0.0);

if (empty($mac) || $egg_weight <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields or invalid weight.']);
    exit;
}

// Validate device
$stmt = $mysqli->prepare('SELECT id FROM devices WHERE device_mac = ?');
$stmt->bind_param('s', $mac);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows === 0) {
    echo json_encode(['status' => 'error', 'message' => 'Device not registered.']);
    exit;
}
$stmt->close();

// Calculate size based on weight
$size = 'No weight';
if ($egg_weight >= 70) {
    $size = 'Jumbo';
} elseif ($egg_weight >= 65) {
    $size = 'Extra Large';
} elseif ($egg_weight >= 60) {
    $size = 'Large';
} elseif ($egg_weight >= 55) {
    $size = 'Medium';
} elseif ($egg_weight >= 50) {
    $size = 'Small';
} elseif ($egg_weight >= 45) {
    $size = 'Pullets';
} elseif ($egg_weight >= 40) {
    $size = 'Pewee';
}

// Insert data
$insert = $mysqli->prepare('INSERT INTO egg_data (mac, size, egg_weight) VALUES (?, ?, ?)');
$insert->bind_param('ssd', $mac, $size, $egg_weight);

if ($insert->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Egg data recorded.', 'size' => $size]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Failed to insert egg data: ' . $mysqli->error]);
}

$insert->close();
$mysqli->close();
?>