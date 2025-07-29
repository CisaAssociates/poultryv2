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
$is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;

// Validate the status (should be 0 or 1)
if ($is_active !== 0 && $is_active !== 1) {
    $response['message'] = 'Invalid status value';
    echo json_encode($response);
    exit;
}

$mysqli = db_connect();

try {
    // First get the farm ID associated with this tax setting
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

    // Update the tax status
    $update_stmt = $mysqli->prepare("UPDATE tax_settings SET is_active = ? WHERE id = ?");
    $update_stmt->bind_param("ii", $is_active, $tax_id);

    if ($update_stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Tax status updated successfully';
        $response['new_status'] = $is_active;
    } else {
        $response['message'] = 'Failed to update tax status: ' . $mysqli->error;
    }
} catch (Exception $e) {
    $response['message'] = 'Error updating tax status: ' . $e->getMessage();
}

echo json_encode($response);
