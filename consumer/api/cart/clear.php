<?php
require_once __DIR__ . '/../../../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Get database connection
$mysqli = db_connect();

// Get user's cart ID
$stmt = $mysqli->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No cart exists, nothing to clear
    echo json_encode(['success' => true, 'message' => 'Cart already empty']);
    exit;
}

$cart_id = $result->fetch_assoc()['cart_id'];

// Begin transaction
$mysqli->begin_transaction();

try {
    // Delete all items from the cart
    $stmt = $mysqli->prepare("DELETE FROM consumer_cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    
    // Commit transaction
    $mysqli->commit();
    
    echo json_encode(['success' => true, 'message' => 'Cart cleared successfully']);
} catch (Exception $e) {
    // Rollback transaction on error
    try {
        $mysqli->rollback();
    } catch (Exception $rollbackException) {
        // Transaction wasn't active or rollback failed
    }
    echo json_encode(['success' => false, 'message' => 'Failed to clear cart: ' . $e->getMessage()]);
}