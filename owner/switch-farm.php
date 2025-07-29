<?php
require_once __DIR__ . '/../config.php';

// Verify user is logged in and is an owner
if (!is_logged_in() || $user['role_id'] != 2) {
    $_SESSION['error-page'] = true;
    header('Location: ' . view('auth.error.403'));
    exit;
}

// Get farm_id from query string
$farm_id = isset($_GET['farm_id']) ? intval($_GET['farm_id']) : 0;

// Verify farm belongs to this owner
if ($farm_id > 0) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT farm_id FROM farms WHERE farm_id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $farm_id, $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Farm belongs to this owner, set it as selected farm
        $_SESSION['selected_farm_id'] = $farm_id;
    }
}

// Redirect back to referring page or dashboard
$redirect = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : view('owner.index');
header("Location: $redirect");
exit;