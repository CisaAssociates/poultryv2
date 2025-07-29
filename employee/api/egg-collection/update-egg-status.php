<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || $user['role_id'] != 3) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if (!verify_token($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid security token']);
    exit;
}

$egg_id = $_POST['egg_id'] ?? '';
$status = $_POST['status'] ?? '';

if (!in_array($status, ['approved', 'rejected'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    exit;
}

$mysqli = db_connect();
$farm_id = $user['farm_id'];

try {
    // Batch approval
    if ($egg_id === 'all') {
        $query = "UPDATE egg_data
                  INNER JOIN devices ON egg_data.mac = devices.device_mac
                  SET egg_data.validation_status = ?
                  WHERE devices.device_owner_id = ?
                  AND egg_data.validation_status = 'pending'";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("si", $status, $farm_id);
    } 
    // Single egg update
    else {
        $query = "UPDATE egg_data
                  INNER JOIN devices ON egg_data.mac = devices.device_mac
                  SET egg_data.validation_status = ?
                  WHERE egg_data.id = ?
                  AND devices.device_owner_id = ?";
        
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("sii", $status, $egg_id, $farm_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Database update failed']);
    }
} catch (Exception $e) {
    error_log("Egg validation error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error occurred']);
}