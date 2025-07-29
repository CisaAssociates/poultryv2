<?php
require_once __DIR__ . '/../../../config.php';

// Set headers for SSE
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Disable nginx buffering
header('Access-Control-Allow-Origin: *'); // Allow CORS

// Prevent buffering
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);

// Disable PHP time limit
set_time_limit(0);

// Clear any existing output buffers
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Start with a clean buffer
ob_start();

// Check if user is logged in and is a consumer
if (!is_logged_in() || $_SESSION['role'] != 4) {
    // Send empty cart data for non-logged in users
    $emptyCart = [
        'items' => [],
        'subtotal' => 0,
        'subtotal_formatted' => '0.00',
        'delivery_fee' => 0,
        'delivery_fee_formatted' => '0.00',
        'tax_rate' => 0,
        'tax_amount' => 0,
        'tax_amount_formatted' => '0.00',
        'loyalty_discount' => 0,
        'loyalty_discount_formatted' => '0.00',
        'total' => 0,
        'total_formatted' => '0.00',
        'loyalty_points' => 0,
        'loyalty_tier' => null,
        'message' => 'Please log in to view your cart'
    ];
    
    echo "event: cartUpdate\n";
    echo "data: " . json_encode($emptyCart) . "\n\n";
    ob_flush();
    flush();
    
    // For non-logged in users, just send the empty cart once and exit
    // This avoids keeping the connection open indefinitely
    exit;
}

// Get current user ID
$user_id = $_SESSION['id'];

// Connect to database
$conn = get_database_connection();

// Get or create cart for the user
$cart_query = $conn->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
$cart_query->bind_param("i", $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();

if ($cart_result->num_rows === 0) {
    // Create a new cart for the user
    $create_cart = $conn->prepare("INSERT INTO consumer_carts (user_id) VALUES (?)");
    $create_cart->bind_param("i", $user_id);
    $create_cart->execute();
    $cart_id = $conn->insert_id;
} else {
    $cart_id = $cart_result->fetch_assoc()['cart_id'];
}

// Define egg categories
$egg_categories = ['Pewee', 'Pullets', 'Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];

// Get cart items with product details
$stmt = $conn->prepare("SELECT ci.item_id, ci.tray_id, ci.quantity, t.size, t.price, t.image_url, f.farm_name, f.farm_id 
                       FROM consumer_cart_items ci
                       JOIN trays t ON ci.tray_id = t.tray_id
                       JOIN farms f ON t.farm_id = f.farm_id
                       WHERE ci.cart_id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Get tax rate
$tax_query = $conn->prepare("SELECT tax_rate FROM tax_settings WHERE is_active = 1 LIMIT 1");
$tax_query->execute();
$tax_result = $tax_query->get_result();
$tax_rate = $tax_result->num_rows > 0 ? $tax_result->fetch_assoc()['tax_rate'] : 12; // Default to 12% if not set

// Get user's loyalty points and tier
$loyalty_query = $conn->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
$loyalty_query->bind_param("i", $user_id);
$loyalty_query->execute();
$loyalty_result = $loyalty_query->get_result();
$loyalty = $loyalty_result->fetch_assoc();
$loyalty_points = $loyalty ? $loyalty['points'] : 0;

// Get user's loyalty tier based on points
$tier_query = $conn->prepare("SELECT * FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
$tier_query->bind_param("i", $loyalty_points);
$tier_query->execute();
$loyalty_tier_result = $tier_query->get_result();
$loyalty_tier = $loyalty_tier_result->fetch_assoc();

// Calculate cart totals
$subtotal = 0;
$delivery_fee = 50; // Default delivery fee

// Prepare cart items for JSON
$items = [];
while ($item = $cart_items->fetch_assoc()) {
    $item_total = $item['price'] * $item['quantity'];
    $subtotal += $item_total;
    
    // Format the size display
    $size = strtolower($item['size']);
    $display_size = '';
    
    // Match the size to one of our categories
    foreach ($egg_categories as $category) {
        if (strtolower($category) === $size) {
            $display_size = $category;
            break;
        }
    }
    
    // If no match found, just use the original size
    if (empty($display_size)) {
        $display_size = ucfirst($size);
    }
    
    $items[] = [
        'item_id' => $item['item_id'],
        'tray_id' => $item['tray_id'],
        'quantity' => $item['quantity'],
        'size' => $display_size,
        'price' => $item['price'],
        'price_formatted' => number_format($item['price'], 2),
        'total' => $item_total,
        'total_formatted' => number_format($item_total, 2),
        'image_url' => $item['image_url'],
        'farm_name' => $item['farm_name'],
        'farm_id' => $item['farm_id']
    ];
}

// Calculate loyalty discount if applicable
$loyalty_discount = 0;
if ($loyalty_tier && isset($loyalty_tier['discount_percentage'])) {
    $loyalty_discount = $subtotal * ($loyalty_tier['discount_percentage'] / 100);
}

$tax_amount = ($subtotal - $loyalty_discount) * ($tax_rate / 100);
$total = $subtotal - $loyalty_discount + $delivery_fee + $tax_amount;

// Prepare cart summary data
$cart_data = [
    'items' => $items,
    'item_count' => count($items),
    'subtotal' => $subtotal,
    'subtotal_formatted' => number_format($subtotal, 2),
    'delivery_fee' => $delivery_fee,
    'delivery_fee_formatted' => number_format($delivery_fee, 2),
    'tax_rate' => $tax_rate,
    'tax_amount' => $tax_amount,
    'tax_amount_formatted' => number_format($tax_amount, 2),
    'loyalty_discount' => $loyalty_discount,
    'loyalty_discount_formatted' => number_format($loyalty_discount, 2),
    'loyalty_points' => $loyalty_points,
    'total' => $total,
    'total_formatted' => number_format($total, 2)
];

// Add loyalty tier information if available
if ($loyalty_tier) {
    $cart_data['loyalty_tier'] = [
        'tier_name' => $loyalty_tier['tier_name'],
        'discount_percentage' => $loyalty_tier['discount_percentage']
    ];
    
    // Get next tier if available
    $next_tier_query = $conn->prepare("SELECT * FROM loyalty_tiers WHERE points_required > ? ORDER BY points_required ASC LIMIT 1");
    $next_tier_query->bind_param("i", $loyalty_tier['points_required']);
    $next_tier_query->execute();
    $next_tier_result = $next_tier_query->get_result();
    $next_tier = $next_tier_result->fetch_assoc();
    
    if ($next_tier) {
        $points_range = $next_tier['points_required'] - $loyalty_tier['points_required'];
        $points_earned = $loyalty_points - $loyalty_tier['points_required'];
        $progress_percentage = min(100, max(0, ($points_earned / $points_range) * 100));
        
        $cart_data['next_tier'] = [
            'tier_name' => $next_tier['tier_name'],
            'points_needed' => $next_tier['points_required'] - $loyalty_points,
            'progress_percentage' => $progress_percentage
        ];
    } else {
        $cart_data['next_tier'] = null;
        $cart_data['progress_percentage'] = 100; // Max tier reached
    }
} else {
    $cart_data['loyalty_tier'] = null;
    $cart_data['next_tier'] = null;
    $cart_data['progress_percentage'] = 0;
}

// Send the cart data as SSE
echo "retry: 3000\n"; // Tell client to retry connection after 3 seconds if disconnected
echo "event: cartUpdate\n";
echo "data: " . json_encode($cart_data) . "\n\n";

// Ensure output is sent immediately
ob_end_flush();
if (ob_get_level() > 0) {
    ob_flush();
}
flush();

// For simplicity in the development environment, just send one update and exit
// In a production environment, you might want to implement a proper SSE loop
// that checks for cart changes and sends updates when needed
connection_aborted() or exit(0);