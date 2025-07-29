<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');


if (!is_logged_in() || get_user_data()['role_id'] != 4) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Get current user ID
$user_id = get_user_data()['user_id'];

// Get current cart ID
$conn = get_database_connection();
$stmt = $conn->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart = $stmt->get_result()->fetch_assoc();
$cart_id = $cart ? $cart['cart_id'] : 0;

$last_update = time();

while (true) {
    // Check for cart updates
    $stmt = $conn->prepare("
        SELECT ci.item_id, ci.quantity, t.size, t.price, t.image_url, f.farm_name 
        FROM consumer_cart_items ci
        JOIN trays t ON ci.tray_id = t.tray_id
        JOIN farms f ON t.farm_id = f.farm_id
        WHERE ci.cart_id = ?
    ");
    $stmt->bind_param("i", $cart_id);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Calculate totals
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    
    $loyalty_points = 0; // Calculate loyalty points as needed
    $tax_rate = 12; // Default tax rate
    $tax_amount = $subtotal * ($tax_rate / 100);
    $delivery_fee = 50;
    $total = $subtotal + $delivery_fee + $tax_amount;

    // Prepare data
    $data = [
        'items' => $items,
        'summary' => [
            'subtotal' => number_format($subtotal, 2),
            'tax' => number_format($tax_amount, 2),
            'delivery_fee' => number_format($delivery_fee, 2),
            'total' => number_format($total, 2),
            'loyalty_points' => $loyalty_points
        ]
    ];

    echo "data: " . json_encode($data) . "\n\n";
    ob_flush();
    flush();

    // Check client connection
    if (connection_aborted()) break;

    sleep(5); // Update every 5 seconds
    $last_update = time();
}