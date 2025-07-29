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

// Validate required parameters
if (!isset($_POST['tray_id']) || !isset($_POST['quantity']) || !isset($_POST['price'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Missing required parameters'
    ]);
    exit;
}

$tray_id = (int) $_POST['tray_id'];
$quantity = (int) $_POST['quantity'];
$price = (float) $_POST['price'];

// Validate data
if ($tray_id <= 0 || $quantity <= 0 || $price <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid parameters'
    ]);
    exit;
}

$mysqli = db_connect();

try {
    $stmt = $mysqli->prepare("SELECT stock_count FROM trays WHERE tray_id = ?");
    $stmt->bind_param("i", $tray_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Tray not found'
        ]);
        exit;
    }

    $tray = $result->fetch_assoc();

    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(cci.quantity), 0) AS cart_quantity
        FROM consumer_cart_items cci
        JOIN consumer_carts cc ON cci.cart_id = cc.cart_id
        WHERE cc.user_id = ? AND cci.tray_id = ?
    ");
    $stmt->bind_param("ii", $_SESSION['id'], $tray_id);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    $cartData = $cartResult->fetch_assoc();
    $cartQuantity = $cartData['cart_quantity'] ?? 0;

    $existingQuantity = 0;
    $stmt = $mysqli->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $cartResult = $stmt->get_result();
    $cartExists = $cartResult->num_rows > 0;

    if ($cartExists) {
        $cart_id = $cartResult->fetch_assoc()['cart_id'];
        $stmt = $mysqli->prepare("SELECT quantity FROM consumer_cart_items WHERE cart_id = ? AND tray_id = ?");
        $stmt->bind_param("ii", $cart_id, $tray_id);
        $stmt->execute();
        $existingItemResult = $stmt->get_result();
        if ($existingItemResult->num_rows > 0) {
            $existingQuantity = $existingItemResult->fetch_assoc()['quantity'];
        }
    }

    $availableStock = $tray['stock_count'] - ($cartQuantity - $existingQuantity);

    if ($availableStock < $quantity) {
        echo json_encode([
            'success' => false,
            'message' => 'Not enough stock available',
            'max_quantity' => $availableStock
        ]);
        exit;
    }

    // Get or create cart for the user
    if (!$cartExists) {
        $stmt = $mysqli->prepare("INSERT INTO consumer_carts (user_id) VALUES (?)");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $cart_id = $mysqli->insert_id;
    }

    // Check if item already exists in cart
    $stmt = $mysqli->prepare("SELECT item_id, quantity FROM consumer_cart_items WHERE cart_id = ? AND tray_id = ?");
    $stmt->bind_param("ii", $cart_id, $tray_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $item = $result->fetch_assoc();
        $new_quantity = $quantity;

        $stmt = $mysqli->prepare("UPDATE consumer_cart_items SET quantity = ? WHERE item_id = ?");
        $stmt->bind_param("ii", $new_quantity, $item['item_id']);
        $stmt->execute();
    } else {
        $stmt = $mysqli->prepare("INSERT INTO consumer_cart_items (cart_id, tray_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $cart_id, $tray_id, $quantity);
        $stmt->execute();
    }

    echo json_encode([
        'max_quantity' => $tray['stock_count'],
        'success' => true,
        'message' => 'Item added to cart successfully'
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while adding to cart: ' . $e->getMessage()
    ]);
} finally {
    $mysqli->close();
}