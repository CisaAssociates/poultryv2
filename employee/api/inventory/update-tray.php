<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

// Verify token
if (!verify_token($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$tray_id = (int)$_POST['tray_id'];
$action = $_POST['action'] ?? '';
$mysqli = db_connect();

switch ($action) {
    case 'sold':
        $query = "UPDATE trays 
                  SET status = 'sold', sold_at = NOW() 
                  WHERE tray_id = ?";
        $message = "Tray marked as sold";
        break;
        
    case 'expired':
        $query = "UPDATE trays 
                  SET status = 'expired', expired_at = NOW() 
                  WHERE tray_id = ?";
        $message = "Tray marked as expired";
        break;
        
    case 'delete':
        $query = "DELETE FROM trays WHERE tray_id = ?";
        $message = "Tray deleted successfully";
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $tray_id);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => $message]);
} else {
    echo json_encode(['success' => false, 'message' => 'Operation failed: ' . $mysqli->error]);
}