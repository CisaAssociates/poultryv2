<?php
require_once __DIR__ . '/../../../config.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in and is a consumer
if (!is_logged_in() || get_user_data()['role_id'] != 4) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data from request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate JSON parsing
if ($data === null) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
    exit;
}

// Validate required fields
if (!isset($data['item_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required field: item_id']);
    exit;
}

$item_id = intval($data['item_id']);

// Validate item_id
if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

try {
    // Connect to database
    $conn = get_database_connection();
    
    // Get current user ID
    $user_data = get_user_data();
    $user_id = $user_data['user_id'];

    // Begin transaction
    $conn->begin_transaction();

    // Get current cart
    $cart_query = $conn->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
    if (!$cart_query) {
        throw new Exception("Failed to prepare cart query: " . $conn->error);
    }
    
    $cart_query->bind_param("i", $user_id);
    $cart_query->execute();
    $cart_result = $cart_query->get_result();

    if ($cart_result->num_rows === 0) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'No active cart found']);
        exit;
    }

    $cart_id = $cart_result->fetch_assoc()['cart_id'];

    // Check if item exists in cart and get its details
    $item_query = $conn->prepare("
        SELECT ci.quantity, ci.tray_id, t.price, t.size, f.farm_name
        FROM consumer_cart_items ci
        JOIN trays t ON ci.tray_id = t.tray_id
        JOIN farms f ON t.farm_id = f.farm_id
        WHERE ci.item_id = ? AND ci.cart_id = ?
    ");
    
    if (!$item_query) {
        throw new Exception("Failed to prepare item query: " . $conn->error);
    }
    
    $item_query->bind_param("ii", $item_id, $cart_id);
    $item_query->execute();
    $item_result = $item_query->get_result();

    if ($item_result->num_rows === 0) {
        $conn->rollback();
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found in your cart']);
        exit;
    }

    $item_details = $item_result->fetch_assoc();

    // Delete the item from cart
    $delete_query = $conn->prepare("DELETE FROM consumer_cart_items WHERE item_id = ? AND cart_id = ?");
    if (!$delete_query) {
        throw new Exception("Failed to prepare delete query: " . $conn->error);
    }
    
    $delete_query->bind_param("ii", $item_id, $cart_id);
    $delete_result = $delete_query->execute();

    if (!$delete_result) {
        throw new Exception("Failed to remove item from cart: " . $conn->error);
    }

    // Check if any rows were actually deleted
    if ($conn->affected_rows === 0) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found in cart or already removed']);
        exit;
    }

    // Get remaining cart count
    $count_query = $conn->prepare("SELECT COUNT(*) as item_count FROM consumer_cart_items WHERE cart_id = ?");
    if (!$count_query) {
        throw new Exception("Failed to prepare count query: " . $conn->error);
    }
    
    $count_query->bind_param("i", $cart_id);
    $count_query->execute();
    $count_result = $count_query->get_result();
    $remaining_items = $count_result->fetch_assoc()['item_count'];

    // Commit transaction
    $conn->commit();
    
    // Calculate removed item total for reference
    $removed_total = floatval($item_details['price']) * intval($item_details['quantity']);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Item removed from cart successfully',
        'removed_item' => [
            'item_id' => $item_id,
            'quantity' => intval($item_details['quantity']),
            'price' => floatval($item_details['price']),
            'total' => $removed_total,
            'size' => $item_details['size'],
            'farm_name' => $item_details['farm_name']
        ],
        'remaining_items' => intval($remaining_items),
        'cart_empty' => $remaining_items == 0
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
    }
    
    // Log the error
    error_log("Cart remove item error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while removing the item. Please try again.'
    ]);
} finally {
    // Close database connection
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>