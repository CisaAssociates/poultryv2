<?php
require_once __DIR__ . '/../../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . view('manage-orders'));
    exit;
}

if (!isset($_POST['order_id'], $_POST['status'], $_POST['token'])) {
    $_SESSION['error'] = 'Invalid request';
    header('Location: ' . view('manage-orders'));
    exit;
}

if (!verify_token($_POST['token'])) {
    $_SESSION['error'] = 'Invalid token';
    header('Location: ' . view('manage-orders'));
    exit;
}

$order_id = (int)$_POST['order_id'];
$status = $_POST['status'];

if (!is_logged_in() || !in_array($user['role_id'], [2, 3])) {
    header('Location: ' . view('auth.error.403'));
    exit;
}

$mysqli = db_connect();
$stmt = $mysqli->prepare("UPDATE consumer_orders SET status = ? WHERE order_id = ?");
$stmt->bind_param("si", $status, $order_id);

if ($stmt->execute()) {
    // Add notification
    $note = $_POST['notes'] ?? '';
    $message = "Order #$order_id status updated to " . ucfirst($status);
    if (!empty($note)) $message .= ". Note: $note";
    
    $notif_stmt = $mysqli->prepare("
        INSERT INTO notifications (farm_id, title, message, type)
        SELECT t.farm_id, 'Order Updated', ?, 'info'
        FROM consumer_order_items i
        INNER JOIN trays t ON i.tray_id = t.tray_id
        WHERE i.order_id = ?
        LIMIT 1
    ");
    $notif_stmt->bind_param("si", $message, $order_id);
    $notif_stmt->execute();
    
    $_SESSION['success'] = 'Order status updated successfully';
} else {
    $_SESSION['error'] = 'Failed to update order status: ' . $mysqli->error;
}

header('Location: ' . view('order-details') . '?order_id=' . $order_id);
exit;