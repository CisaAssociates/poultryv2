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
if (!isset($_POST['review_id']) || !isset($_POST['rating']) || !isset($_POST['comment'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$review_id = (int)$_POST['review_id'];
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

// Check if review exists and belongs to the user
$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT review_id FROM consumer_reviews WHERE review_id = ? AND user_id = ?");
$stmt->bind_param("ii", $review_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Review not found or does not belong to you']);
    exit;
}

// Update review
$stmt = $mysqli->prepare("UPDATE consumer_reviews SET rating = ?, comment = ? WHERE review_id = ? AND user_id = ?");
$stmt->bind_param("isii", $rating, $comment, $review_id, $_SESSION['id']);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Review updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update review']);
}