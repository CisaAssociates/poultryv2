<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');
$response = ['success' => false];

if (!verify_token($_POST['token'])) {
    $response['message'] = 'Invalid token';
    echo json_encode($response);
    exit;
}

if (!is_logged_in()) {
    $response['message'] = 'Unauthorized access';
    echo json_encode($response);
    exit;
}

$farm_id = isset($_POST['farm_id']) ? (int)$_POST['farm_id'] : 0;
$tax_name = trim($_POST['tax_name']);
$tax_rate = (float)$_POST['tax_rate'];

// Validate farm ID
if ($farm_id <= 0) {
    $response['message'] = 'Invalid farm selection';
    echo json_encode($response);
    exit;
}

// Validate farm access
if (!validate_farm_access($farm_id)) {
    $response['message'] = 'Access denied to farm';
    echo json_encode($response);
    exit;
}

if (empty($tax_name)) {
    $response['message'] = 'Tax name is required';
    echo json_encode($response);
    exit;
}

if ($tax_rate <= 0 || $tax_rate > 100) {
    $response['message'] = 'Tax rate must be between 0.01 and 100';
    echo json_encode($response);
    exit;
}

$is_default = isset($_POST['is_default']) ? 1 : 0;
$mysqli = db_connect();

// Add transaction for safety
$mysqli->begin_transaction();

try {
    if ($is_default) {
        $stmt = $mysqli->prepare("UPDATE tax_settings SET is_default = 0 WHERE farm_id = ?");
        $stmt->bind_param("i", $farm_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to reset default tax");
        }
    }

    $stmt = $mysqli->prepare("INSERT INTO tax_settings (farm_id, tax_name, tax_rate, is_default) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isdi", $farm_id, $tax_name, $tax_rate, $is_default);
    
    if ($stmt->execute()) {
        $mysqli->commit();
        $response['success'] = true;
        $response['message'] = 'Tax setting saved successfully';
    } else {
        throw new Exception("Failed to save tax setting: " . $mysqli->error);
    }
} catch (Exception $e) {
    try {
        $mysqli->rollback();
    } catch (Exception $rollbackException) {
        // Transaction wasn't active or rollback failed
    }
    $response['message'] = $e->getMessage();
}

echo json_encode($response);