<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || $user['role_id'] != 4) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

if (!verify_token($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

// Validate required fields
if (!isset($_POST['order_id']) || !isset($_POST['rating']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$order_id = (int)$_POST['order_id'];
$rating = (int)$_POST['rating'];
$comment = trim($_POST['comment']);

// Validate rating
if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Rating must be between 1 and 5']);
    exit;
}

// Validate comment
if (empty($comment)) {
    echo json_encode(['success' => false, 'message' => 'Comment cannot be empty']);
    exit;
}

// Check if order exists and belongs to the user
$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT status FROM consumer_orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Order not found or does not belong to you']);
    exit;
}

$order = $result->fetch_assoc();

// Check if order is delivered
if ($order['status'] !== 'delivered') {
    echo json_encode(['success' => false, 'message' => 'You can only review delivered orders']);
    exit;
}

// Check if user has already reviewed this order
$stmt = $mysqli->prepare("SELECT review_id FROM consumer_reviews WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You have already reviewed this order']);
    exit;
}

// Insert review
$stmt = $mysqli->prepare("INSERT INTO consumer_reviews (user_id, order_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->bind_param("iiis", $_SESSION['id'], $order_id, $rating, $comment);

if ($stmt->execute()) {
    // Add loyalty points for review
    $stmt = $mysqli->prepare("UPDATE consumer_loyalty SET points = points + 5, last_activity = CURRENT_TIMESTAMP WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Review submitted successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to submit review']);
}