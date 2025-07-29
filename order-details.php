<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in() || !in_array($user['role_id'], [2, 3])) {
    header('Location: ' . view('auth.error.403'));
    exit;
}

if (!isset($_GET['order_id'])) {
    $_SESSION['error'] = 'Order ID is required';
    header('Location: ' . view('manage-orders'));
    exit;
}

$order_id = (int)$_GET['order_id'];

$farm_ids = [];
if ($user['role_id'] == 2) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT farm_id FROM farms WHERE owner_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $farm_ids[] = $row['farm_id'];
    }
} else {
    $farm_ids[] = $user['farm_id'];
}

if (empty($farm_ids)) {
    $_SESSION['error'] = 'No farms found';
    header('Location: ' . view('manage-orders'));
    exit;
}

$farm_ids_str = implode(',', $farm_ids);

// Check if the order has items from user's farms
$mysqli = db_connect();
$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS cnt
    FROM consumer_order_items i
    INNER JOIN trays t ON i.tray_id = t.tray_id
    WHERE i.order_id = ? AND t.farm_id IN ($farm_ids_str)
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if ($row['cnt'] == 0) {
    $_SESSION['error'] = 'You do not have permission to view this order';
    header('Location: ' . view('manage-orders'));
    exit;
}

// Fetch order details
$stmt = $mysqli->prepare("
    SELECT o.*, a.* 
    FROM consumer_orders o
    INNER JOIN consumer_addresses a ON o.address_id = a.address_id
    WHERE o.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Fetch order items
$stmt = $mysqli->prepare("
    SELECT i.*, t.size, t.price as unit_price, f.farm_name
    FROM consumer_order_items i
    INNER JOIN trays t ON i.tray_id = t.tray_id
    INNER JOIN farms f ON t.farm_id = f.farm_id
    WHERE i.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Set title and layout
$title = 'Order Details';
$sub_title = 'Order #' . $order_id;
$layout = ($user['role_id'] == 2) ? 'owner.main' : 'employee.main';

ob_start();
?>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0">Order Information</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Order Summary</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Order ID:</dt>
                        <dd class="col-sm-7">#<?= $order['order_id'] ?></dd>
                        
                        <dt class="col-sm-5">Order Date:</dt>
                        <dd class="col-sm-7"><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></dd>
                        
                        <dt class="col-sm-5">Status:</dt>
                        <dd class="col-sm-7">
                            <span class="badge bg-<?= 
                                $order['status'] == 'pending' ? 'warning' : 
                                ($order['status'] == 'confirmed' ? 'primary' : 
                                ($order['status'] == 'packing' ? 'info' : 
                                ($order['status'] == 'shipped' ? 'secondary' : 
                                ($order['status'] == 'delivered' ? 'success' : 'danger')))) 
                            ?>">
                                <?= ucfirst($order['status']) ?>
                            </span>
                        </dd>
                        
                        <dt class="col-sm-5">Total Amount:</dt>
                        <dd class="col-sm-7">₱<?= number_format($order['total_amount'], 2) ?></dd>
                        
                        <dt class="col-sm-5">Payment Method:</dt>
                        <dd class="col-sm-7"><?= $order['payment_method'] ?: 'COD' ?></dd>
                        
                        <dt class="col-sm-5">Payment Status:</dt>
                        <dd class="col-sm-7"><?= ucfirst($order['payment_status'] ?: 'pending') ?></dd>
                    </dl>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="mb-3">
                    <h6>Delivery Information</h6>
                    <dl class="row mb-0">
                        <dt class="col-sm-5">Recipient:</dt>
                        <dd class="col-sm-7"><?= special_chars($order['recipient_name']) ?></dd>
                        
                        <dt class="col-sm-5">Contact:</dt>
                        <dd class="col-sm-7"><?= special_chars($order['contact_number']) ?></dd>
                        
                        <dt class="col-sm-5">Address:</dt>
                        <dd class="col-sm-7">
                            <?= special_chars($order['street_address']) ?>, 
                            <?= special_chars($order['barangay']) ?>, 
                            <?= special_chars($order['city']) ?>, 
                            <?= special_chars($order['province']) ?> 
                            <?= special_chars($order['zip_code']) ?>
                        </dd>
                        
                        <dt class="col-sm-5">Delivery Method:</dt>
                        <dd class="col-sm-7"><?= ucfirst($order['delivery_method']) ?></dd>
                        
                        <?php if ($order['delivery_method'] == 'delivery'): ?>
                        <dt class="col-sm-5">Delivery Date:</dt>
                        <dd class="col-sm-7">
                            <?= $order['delivery_date'] ? date('M d, Y', strtotime($order['delivery_date'])) : 'Not set' ?>
                            <?= $order['delivery_time'] ? 'at ' . date('h:i A', strtotime($order['delivery_time'])) : '' ?>
                        </dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
        
        <hr>
        
        <h5 class="mb-3">Order Items</h5>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Farm</th>
                        <th>Egg Size</th>
                        <th>Unit Price</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= special_chars($item['farm_name']) ?></td>
                        <td><?= special_chars($item['size']) ?></td>
                        <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>₱<?= number_format($item['total_price'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal:</th>
                        <th>₱<?= number_format($order['total_amount'], 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        <?php if (in_array($order['status'], ['pending', 'confirmed', 'packing'])): ?>
        <hr>
        
        <h5 class="mb-3">Update Order Status</h5>
        <form method="post" action="<?= view('api/manage-orders/update-order-status') ?>">
            <input type="hidden" name="order_id" value="<?= $order_id ?>">
            <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="status" class="form-label">New Status</label>
                        <select class="form-select" id="status" name="status" required>
                            <?php if ($order['status'] == 'pending'): ?>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancel Order</option>
                            <?php elseif ($order['status'] == 'confirmed'): ?>
                                <option value="packing">Mark as Packing</option>
                                <option value="cancelled">Cancel Order</option>
                            <?php elseif ($order['status'] == 'packing'): ?>
                                <option value="shipped">Mark as Shipped</option>
                                <option value="cancelled">Cancel Order</option>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                    </div>
                </div>
            </div>
            
            <div class="d-flex justify-content-end gap-2">
                <a href="<?= view('manage-orders') ?>" class="btn btn-secondary">Back to Orders</a>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
        <?php else: ?>
        <div class="d-flex justify-content-end">
            <a href="<?= view('manage-orders') ?>" class="btn btn-secondary">Back to Orders</a>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php
$content = ob_get_clean();
include layouts($layout);
?>
