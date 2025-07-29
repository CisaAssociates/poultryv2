<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
$mysqli = db_connect();

// Only allow POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

// Get POST data
$device_type     = $_POST['device_type'] ?? '';
$device_mac      = $_POST['device_mac'] ?? '';
$device_wifi     = $_POST['device_wifi'] ?? '';
$device_wifi_pass= $_POST['device_wifi_pass'] ?? '';
$device_wifi_ip  = $_POST['device_wifi_ip'] ?? '';
$device_serial_no= $_POST['device_serial_no'] ?? '';

// Validate required fields
if (!$device_type || !$device_mac || !$device_wifi || !$device_wifi_pass || !$device_wifi_ip || !$device_serial_no) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

// Check if device exists
$stmt = $mysqli->prepare('SELECT id FROM devices WHERE device_mac = ?');
$stmt->bind_param('s', $device_mac);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->close();
    // Update existing
    $upd = $mysqli->prepare(
        'UPDATE devices SET device_type=?, device_wifi=?, device_wifi_pass=?, device_wifi_ip=?, device_serial_no=?, registration_date=CURDATE(), registration_time=CURTIME(), is_registered=1 WHERE device_mac=?'
    );
    $upd->bind_param('ssssss', $device_type, $device_wifi, $device_wifi_pass, $device_wifi_ip, $device_serial_no, $device_mac);
    if ($upd->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Device updated.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to update device.']);
    }
    $upd->close();
} else {
    $stmt->close();
    // Insert new device
    $ins = $mysqli->prepare(
        'INSERT INTO devices (device_type, device_mac, device_wifi, device_wifi_pass, device_wifi_ip, registration_date, registration_time, device_serial_no, is_registered) VALUES (?, ?, ?, ?, ?, CURDATE(), CURTIME(), ?, 1)'
    );
    $ins->bind_param('ssssss', $device_type, $device_mac, $device_wifi, $device_wifi_pass, $device_wifi_ip, $device_serial_no);
    if ($ins->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Device registered.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to register device.']);
    }
    $ins->close();
}

$mysqli->close();
