<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || $user['role_id'] != 4) {
    echo json_encode(['count' => 0]);
    exit;
}

$mysqli = db_connect();
$stmt = $mysqli->prepare("
    SELECT SUM(quantity) AS count 
    FROM consumer_cart_items 
    WHERE cart_id = (SELECT cart_id FROM consumer_carts WHERE user_id = ?)
");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
$count = $result->fetch_assoc()['count'] ?? 0;

echo json_encode(['count' => $count ?: 0]);
