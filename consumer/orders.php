<?php
require_once __DIR__ . '/../config.php';

$mysqli = db_connect();

$title = 'Your Orders';
$sub_title = 'Review Your Previous Purchases';
ob_start();
?>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Date</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $mysqli->prepare("
                            SELECT o.order_id, o.order_date, o.total_amount, o.status,
                                COUNT(i.item_id) AS item_count
                            FROM consumer_orders o
                            JOIN consumer_order_items i ON o.order_id = i.order_id
                            WHERE o.user_id = ?
                            GROUP BY o.order_id
                            ORDER BY o.order_date DESC
                        ");
                    $stmt->bind_param("i", $_SESSION['id']);
                    $stmt->execute();
                    $result = $stmt->get_result();

                    while ($order = $result->fetch_assoc()):
                        $statusClass = [
                            'pending' => 'text-bg-secondary',
                            'confirmed' => 'text-bg-primary',
                            'packing' => 'text-bg-info',
                            'shipped' => 'text-bg-warning',
                            'delivered' => 'text-bg-success',
                            'cancelled' => 'text-bg-danger'
                        ][$order['status']] ?? 'text-bg-secondary';
                    ?>
                        <tr>
                            <td>TX-<?= $order['order_id'] ?></td>
                            <td><?= !empty($order['order_date']) ? date('M d, Y', strtotime($order['order_date'])) : 'N/A' ?></td>
                            <td><?= $order['item_count'] ?> items</td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge <?= $statusClass ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?= view('consumer.order-details') ?>?id=<?= $order['order_id'] ?>"
                                    class="btn btn-sm btn-outline-primary">
                                    View Details
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <?php if ($result->num_rows === 0): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted"></i>
                <h4 class="mt-3">No Orders Found</h4>
                <p class="text-muted">You haven't placed any orders yet</p>
                <a href="<?= view('consumer.products') ?>" class="btn btn-primary mt-2">
                    Browse Products
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>