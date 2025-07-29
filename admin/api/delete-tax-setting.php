<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
$response = ['success' => false];

if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
    $response['message'] = 'Invalid token';
    echo json_encode($response);
    exit;
}

if (!is_logged_in()) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    $response['message'] = 'Invalid tax ID';
    echo json_encode($response);
    exit;
}

$tax_id = (int)$_POST['id'];

$mysqli = db_connect();

// Get tax setting and farm ID
$stmt = $mysqli->prepare("SELECT farm_id FROM tax_settings WHERE id = ?");
$stmt->bind_param("i", $tax_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $response['message'] = 'Tax setting not found';
    echo json_encode($response);
    exit;
}

$tax_data = $result->fetch_assoc();
$farm_id = $tax_data['farm_id'];

// Validate farm access
if (!validate_farm_access($farm_id)) {
    $response['message'] = 'Access denied to farm';
    echo json_encode($response);
    exit;
}

// Check if this tax setting is used in any transactions
$check_stmt = $mysqli->prepare("SELECT COUNT(*) AS usage_count FROM transactions WHERE tax_id = ?");
$check_stmt->bind_param("i", $tax_id);
$check_stmt->execute();
$usage_result = $check_stmt->get_result()->fetch_assoc();

if ($usage_result['usage_count'] > 0) {
    $response['message'] = 'Cannot delete tax setting used in transactions';
    echo json_encode($response);
    exit;
}

// Delete the tax setting
$delete_stmt = $mysqli->prepare("DELETE FROM tax_settings WHERE id = ?");
$delete_stmt->bind_param("i", $tax_id);

if ($delete_stmt->execute()) {
    if ($delete_stmt->affected_rows > 0) {
        $response['success'] = true;
        $response['message'] = 'Tax setting deleted successfully';
    } else {
        $response['message'] = 'No tax setting was deleted';
    }
} else {
    $response['message'] = 'Failed to delete tax setting: ' . $mysqli->error;
}

echo json_encode($response);