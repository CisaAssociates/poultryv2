<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!is_logged_in() || $user['role_id'] != 4) {
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access'
    ]);
    exit;
}

// Validate request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// Get JSON data from request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Validate required parameters
if (!isset($data['address_id']) || !isset($data['delivery_method']) || !isset($data['payment_method'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$address_id = (int) $data['address_id'];
$delivery_method = $data['delivery_method'];
$payment_method = $data['payment_method'];
$order_notes = isset($data['order_notes']) ? $data['order_notes'] : '';
$use_loyalty_points = isset($data['use_loyalty_points']) && $data['use_loyalty_points'];
$pickup_date = isset($data['pickup_date']) ? $data['pickup_date'] : null;
$pickup_time = isset($data['pickup_time']) ? $data['pickup_time'] : null;

// Validate delivery method
if (!in_array($delivery_method, ['delivery', 'pickup'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid delivery method'
    ]);
    exit;
}

// If delivery method is delivery, validate address
if ($delivery_method === 'delivery' && $address_id <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid address ID'
    ]);
    exit;
}

// If delivery method is pickup, validate pickup date and time
if ($delivery_method === 'pickup' && (!$pickup_date || !$pickup_time)) {
    echo json_encode([
        'success' => false,
        'message' => 'Pickup date and time are required'
    ]);
    exit;
}

$mysqli = db_connect();

try {
    // Get user's cart
    $stmt = $mysqli->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cart not found'
        ]);
        exit;
    }

    $cart_id = $result->fetch_assoc()['cart_id'];

    // Get cart items
    $stmt = $mysqli->prepare("SELECT ci.item_id, ci.tray_id, ci.quantity, t.size, t.price, t.image_url, f.farm_name, f.farm_id 
                             FROM consumer_cart_items ci
                             JOIN trays t ON ci.tray_id = t.tray_id
                             JOIN farms f ON t.farm_id = f.farm_id
                             WHERE ci.cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $cart_items = $stmt->get_result();

    // If cart is empty, return error
    if ($cart_items->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Cart is empty'
        ]);
        exit;
    }

    // If delivery method is delivery, validate address
    if ($delivery_method === 'delivery') {
        $stmt = $mysqli->prepare("SELECT * FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $address_id, $_SESSION['id']);
        $stmt->execute();
        $address_result = $stmt->get_result();
        
        if ($address_result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid address'
            ]);
            exit;
        }
    }

    // Get user's loyalty points and tier
    $stmt = $mysqli->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $loyalty_result = $stmt->get_result();
    $loyalty = $loyalty_result->fetch_assoc();
    $loyalty_points = $loyalty ? $loyalty['points'] : 0;

    // Get user's loyalty tier based on points
    $stmt = $mysqli->prepare("SELECT * FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
    $stmt->bind_param("i", $loyalty_points);
    $stmt->execute();
    $loyalty_tier_result = $stmt->get_result();
    $loyalty_tier = $loyalty_tier_result->fetch_assoc();

    // Get tax rate
    $stmt = $mysqli->prepare("SELECT tax_rate FROM tax_settings WHERE is_active = 1 LIMIT 1");
    $stmt->execute();
    $tax_result = $stmt->get_result();
    $tax_rate = $tax_result->num_rows > 0 ? $tax_result->fetch_assoc()['tax_rate'] : 12; // Default to 12% if not set

    // Calculate order totals
    $subtotal = 0;
    $delivery_fee = $delivery_method === 'delivery' ? 50 : 0; // Default delivery fee

    // Calculate subtotal from cart items
    $cart_items_array = [];
    while ($item = $cart_items->fetch_assoc()) {
        $subtotal += $item['price'] * $item['quantity'];
        $cart_items_array[] = $item;
    }

    // Calculate loyalty discount
    $loyalty_discount = 0;
    $points_discount = 0;

    // Apply tier discount if available
    if ($loyalty_tier && isset($loyalty_tier['discount_percentage'])) {
        $loyalty_discount = $subtotal * ($loyalty_tier['discount_percentage'] / 100);
    }

    // Apply points discount if selected and enough points available
    if ($use_loyalty_points && $loyalty_points >= 50) {
        $points_discount = 50; // ₱50 discount for 50 points
    }

    // Calculate tax and total
    $tax_amount = ($subtotal - $loyalty_discount - $points_discount) * ($tax_rate / 100);
    $total = $subtotal - $loyalty_discount - $points_discount + $delivery_fee + $tax_amount;

    // Start transaction
    $mysqli->begin_transaction();

    // Create order record
    $stmt = $mysqli->prepare("INSERT INTO consumer_orders 
                            (user_id, address_id, total_amount, status, delivery_method, 
                             delivery_date, delivery_time, payment_method, payment_status, order_notes) 
                            VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, 'pending', ?)");
    
    // Set address_id to NULL for pickup orders
    $address_id_param = $delivery_method === 'pickup' ? null : $address_id;
    
    $stmt->bind_param("iidsssss", 
        $_SESSION['id'], 
        $address_id_param, 
        $total, 
        $delivery_method, 
        $pickup_date, 
        $pickup_time, 
        $payment_method, 
        $order_notes
    );
    
    $stmt->execute();
    $order_id = $mysqli->insert_id;
    
    // Add order items
    $stmt = $mysqli->prepare("INSERT INTO consumer_order_items 
                            (order_id, tray_id, quantity, unit_price, total_price) 
                            VALUES (?, ?, ?, ?, ?)");
    
    foreach ($cart_items_array as $item) {
        $item_total = $item['price'] * $item['quantity'];
        $stmt->bind_param("iiddd", 
            $order_id, 
            $item['tray_id'], 
            $item['quantity'], 
            $item['price'], 
            $item_total
        );
        $stmt->execute();
        
        // Update tray status to sold
        $update_tray = $mysqli->prepare("UPDATE trays SET status = 'sold', sold_at = NOW() WHERE tray_id = ?");
        $update_tray->bind_param("i", $item['tray_id']);
        $update_tray->execute();
    }
    
    // If using loyalty points, deduct them
    if ($use_loyalty_points && $loyalty_points >= 50) {
        $stmt = $mysqli->prepare("UPDATE consumer_loyalty SET points = points - 50 WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
    }
    
    // Clear the cart
    $stmt = $mysqli->prepare("DELETE FROM consumer_cart_items WHERE cart_id = ?");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    
    // Commit transaction
    $mysqli->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback transaction on error
    try {
        $mysqli->rollback();
    } catch (Exception $rollbackException) {
        // Transaction wasn't active or rollback failed
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while processing your order: ' . $e->getMessage()
    ]);
} finally {
    $mysqli->close();
}