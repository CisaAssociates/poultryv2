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
if (!isset($data['item_id']) || !isset($data['change'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields: item_id and change']);
    exit;
}

$item_id = intval($data['item_id']);
$change = intval($data['change']);

// Validate item_id
if ($item_id <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Validate change value (should be -1 or +1)
if ($change !== -1 && $change !== 1) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid change value. Must be -1 or 1']);
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

    // Get current item details
    $item_query = $conn->prepare("
        SELECT ci.quantity, ci.tray_id, t.stock_count, t.price, t.size
        FROM consumer_cart_items ci
        JOIN trays t ON ci.tray_id = t.tray_id
        WHERE ci.item_id = ? AND ci.cart_id = ?
    ");

    if (!$item_query) {
        throw new Exception("Failed to prepare item query: " . $conn->error);
    }

    $item_query->bind_param("ii", $item_id, $cart_id);
    $item_query->execute();
    $item_result = $item_query->get_result();

    if ($item_result->num_rows === 0) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found in your cart']);
        exit;
    }

    $item = $item_result->fetch_assoc();
    $current_quantity = intval($item['quantity']);
    $tray_id = intval($item['tray_id']);
    $available_stock = intval($item['stock_count']);


    $new_quantity = $current_quantity + $change;

    if ($new_quantity <= 0) {
        // Remove the item if quantity becomes 0 or negative
        $delete_query = $conn->prepare("DELETE FROM consumer_cart_items WHERE item_id = ? AND cart_id = ?");
        if (!$delete_query) {
            throw new Exception("Failed to prepare delete query: " . $conn->error);
        }

        $delete_query->bind_param("ii", $item_id, $cart_id);
        $delete_result = $delete_query->execute();

        if (!$delete_result) {
            throw new Exception("Failed to remove item from cart: " . $conn->error);
        }

        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Item removed from cart',
            'action' => 'removed',
            'new_quantity' => 0
        ]);
        exit;
    }

    // Check stock availability
    if ($new_quantity > $available_stock) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => "Only {$available_stock} items available in stock",
            'available_stock' => $available_stock
        ]);
        exit;
    }

    // Update quantity
    $update_query = $conn->prepare("
        UPDATE consumer_cart_items 
        SET quantity = ?
        WHERE item_id = ? AND cart_id = ?
    ");

    if (!$update_query) {
        throw new Exception("Failed to prepare update query: " . $conn->error);
    }

    $update_query->bind_param("iii", $new_quantity, $item_id, $cart_id);
    $update_result = $update_query->execute();

    if (!$update_result) {
        throw new Exception("Failed to update item quantity: " . $conn->error);
    }

    // Check if any rows were actually updated
    if ($conn->affected_rows === 0) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Transaction wasn't active or rollback failed
        }
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Item not found or no changes made']);
        exit;
    }

    // Commit transaction
    $conn->commit();

    // Calculate new item total
    $item_total = floatval($item['price']) * $new_quantity;

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Quantity updated successfully',
        'action' => 'updated',
        'new_quantity' => $new_quantity,
        'item_total' => number_format($item_total, 2),
        'item_details' => [
            'item_id' => $item_id,
            'tray_id' => $tray_id,
            'price' => floatval($item['price']),
            'size' => $item['size']
        ]
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
    error_log("Cart update error: " . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while updating the cart. Please try again.'
    ]);
} finally {
    // Close database connection
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
