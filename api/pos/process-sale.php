<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || !verify_token($_POST['token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$farm_id = isset($_POST['farm_id']) ? (int)$_POST['farm_id'] : 0;
$cart = json_decode($_POST['cart'] ?? '', true);

if (!validate_farm_access($farm_id) || empty($cart)) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$mysqli = db_connect();
$mysqli->begin_transaction();

try {
    // Get farm name
    $farm_name = 'Main Farm';
    $stmt = $mysqli->prepare("SELECT farm_name FROM farms WHERE farm_id = ?");
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $farm_name = $row['farm_name'];
    }
    
    // Get active tax rate
    $tax_rate = 0;
    $stmt = $mysqli->prepare("
        SELECT tax_rate 
        FROM tax_settings 
        WHERE farm_id = ? AND is_active = 1
        ORDER BY is_default DESC, created_at DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $tax_rate = (float)$row['tax_rate'];
    }
    
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['price'] * $item['quantity'];
    }
    $tax = $subtotal * ($tax_rate / 100);
    $total = $subtotal + $tax;
    
    // Create transaction
    $stmt = $mysqli->prepare("
        INSERT INTO transactions (farm_id, subtotal, tax, total, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iddd", $farm_id, $subtotal, $tax, $total);
    $stmt->execute();
    $transaction_id = $mysqli->insert_id;
    
    $transaction_items = [];
    foreach ($cart as $item) {
        $tray_id = $item['id'];
        $quantity = $item['quantity'];
        $price = $item['price'];
        $item_total = $price * $quantity;
        
        // Add transaction item
        $stmt = $mysqli->prepare("
            INSERT INTO transaction_items (transaction_id, size, price, quantity, total)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("issid", $transaction_id, $item['size'], $price, $quantity, $item_total);
        $stmt->execute();
        
        // Update inventory
        $stmt = $mysqli->prepare("
            UPDATE trays 
            SET stock_count = GREATEST(0, stock_count - ?) 
            WHERE tray_id = ?
        ");
        $stmt->bind_param("ii", $quantity, $tray_id);
        $stmt->execute();
        
        // Add to items array for receipt
        $transaction_items[] = [
            'size' => $item['size'],
            'quantity' => $quantity,
            'price' => $price,
            'total' => $item_total
        ];
    }
    
    $mysqli->commit();
    
    // Return transaction data for receipt
    echo json_encode([
        'success' => true,
        'transaction' => [
            'transaction_id' => $transaction_id,
            'farm_name' => $farm_name,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'total' => $total,
            'items' => $transaction_items
        ]
    ]);
} catch (Exception $e) {
    try {
        $mysqli->rollback();
    } catch (Exception $rollbackException) {
        // Transaction wasn't active or rollback failed
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}