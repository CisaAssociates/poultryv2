<?php
require_once '../config.php';

// Check if user is logged in and is a consumer
if (!is_logged_in() || $user['role_id'] != 4) {
    redirect('error.php');
}

// Check if order_id is provided
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    redirect('orders.php');
}

$order_id = $_GET['order_id'];
$mysqli = db_connect();

// Get order details
$stmt = $mysqli->prepare("SELECT o.*, 
                         DATE_FORMAT(o.created_at, '%M %d, %Y %h:%i %p') as formatted_date,
                         u.name as customer_name, u.email as customer_email,
                         a.address_line1, a.address_line2, a.city, a.province, a.postal_code,
                         ds.day_of_week, ds.time_slot
                         FROM consumer_orders o 
                         LEFT JOIN users u ON o.user_id = u.id
                         LEFT JOIN consumer_addresses a ON o.address_id = a.address_id
                         LEFT JOIN delivery_schedules ds ON o.schedule_id = ds.schedule_id
                         WHERE o.order_id = ? AND o.user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('orders.php');
}

$order = $result->fetch_assoc();

// Get order items
$stmt = $mysqli->prepare("SELECT oi.*, t.size_id, t.tray_count, t.image, 
                         f.farm_name, es.size_name 
                         FROM consumer_order_items oi 
                         JOIN trays t ON oi.tray_id = t.tray_id 
                         JOIN farms f ON oi.farm_id = f.farm_id 
                         JOIN egg_sizes es ON t.size_id = es.size_id 
                         WHERE oi.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_result = $stmt->get_result();
$order_items = [];

while ($row = $items_result->fetch_assoc()) {
    $order_items[] = $row;
}

// Get loyalty points earned from this order
$points_earned = floor($order['total'] / 10);

// Set page title
$page_title = "Order Confirmation";

// Include header
include_once "../layouts/consumer/main.php";

// Helper function to get day name
function getDayName($dayNum) {
    $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    return $days[$dayNum];
}
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Order Confirmation</h1>
        <a href="<?= view('consumer/orders.php') ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-list fa-sm text-white-50"></i> View All Orders
        </a>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="mb-3">Thank You for Your Order!</h2>
                    <p class="lead mb-1">Your order has been successfully placed.</p>
                    <p class="mb-4">Order #<?= $order_id ?> was placed on <?= $order['formatted_date'] ?></p>
                    
                    <?php if ($points_earned > 0): ?>
                    <div class="alert alert-success d-inline-block">
                        <i class="fas fa-star mr-1"></i> You earned <strong><?= $points_earned ?> loyalty points</strong> with this purchase!
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-4">
                        <a href="<?= view('consumer/products.php') ?>" class="btn btn-primary mr-2">
                            <i class="fas fa-shopping-basket mr-1"></i> Continue Shopping
                        </a>
                        <a href="<?= view('consumer/order-details.php') ?>?order_id=<?= $order_id ?>" class="btn btn-info">
                            <i class="fas fa-file-alt mr-1"></i> View Order Details
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <!-- Order Details Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Order Summary</h6>
                    <span class="badge badge-<?= getStatusBadgeClass($order['status']) ?>"><?= ucfirst($order['status']) ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="font-weight-bold">Order Information</h5>
                            <p class="mb-1"><strong>Order ID:</strong> #<?= $order_id ?></p>
                            <p class="mb-1"><strong>Date:</strong> <?= $order['formatted_date'] ?></p>
                            <p class="mb-1"><strong>Payment Method:</strong> <?= getPaymentMethodName($order['payment_method']) ?></p>
                            <?php if ($order['payment_method'] != 'cod'): ?>
                            <div class="alert alert-info mt-2">
                                <small>
                                    <strong>Payment Instructions:</strong><br>
                                    <?php if ($order['payment_method'] == 'gcash'): ?>
                                    Send payment to GCash number: 09123456789<br>
                                    Use your Order ID #<?= $order_id ?> as reference<br>
                                    Email screenshot to payments@poultryv2.com
                                    <?php elseif ($order['payment_method'] == 'bank'): ?>
                                    Bank: Sample Bank<br>
                                    Account Name: Poultry Farm<br>
                                    Account Number: 1234567890<br>
                                    Use your Order ID #<?= $order_id ?> as reference
                                    <?php endif; ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <h5 class="font-weight-bold"><?= $order['delivery_method'] == 'delivery' ? 'Delivery' : 'Pickup' ?> Information</h5>
                            <?php if ($order['delivery_method'] == 'delivery'): ?>
                            <p class="mb-1"><strong>Address:</strong></p>
                            <p class="mb-1"><?= $order['address_line1'] ?></p>
                            <?php if (!empty($order['address_line2'])): ?>
                            <p class="mb-1"><?= $order['address_line2'] ?></p>
                            <?php endif; ?>
                            <p class="mb-1"><?= $order['city'] ?>, <?= $order['province'] ?> <?= $order['postal_code'] ?></p>
                            <p class="mb-3"><strong>Delivery Schedule:</strong> <?= getDayName($order['day_of_week']) ?>, <?= $order['time_slot'] ?></p>
                            <?php else: ?>
                            <p class="mb-1"><strong>Pickup Location:</strong></p>
                            <p class="mb-1">123 Farm Road, Barangay Poultry</p>
                            <p class="mb-1">Quezon City, Metro Manila</p>
                            <p class="mb-3"><a href="https://maps.google.com" target="_blank"><i class="fas fa-directions mr-1"></i>Get Directions</a></p>
                            <?php if (!empty($order['pickup_notes'])): ?>
                            <p class="mb-1"><strong>Pickup Notes:</strong></p>
                            <p class="mb-1"><?= nl2br(htmlspecialchars($order['pickup_notes'])) ?></p>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (!empty($order['order_notes'])): ?>
                    <div class="row mt-3">
                        <div class="col-12">
                            <h5 class="font-weight-bold">Order Notes</h5>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($order['order_notes'])) ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Items Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Items</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Farm</th>
                                    <th class="text-right">Price</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-right">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?= asset($item['image']) ?>" alt="<?= $item['size_name'] ?> Eggs" class="mr-3 rounded" style="width: 50px; height: 50px; object-fit: cover;">
                                            <div>
                                                <h6 class="mb-0"><?= $item['size_name'] ?> Eggs</h6>
                                                <small class="text-muted"><?= $item['tray_count'] ?> pcs per tray</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= $item['farm_name'] ?></td>
                                    <td class="text-right">₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td class="text-center"><?= $item['quantity'] ?></td>
                                    <td class="text-right">₱<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Order Total Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Order Total</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-borderless">
                            <tbody>
                                <tr>
                                    <td>Subtotal</td>
                                    <td class="text-right">₱<?= number_format($order['subtotal'], 2) ?></td>
                                </tr>
                                <?php if ($order['bulk_discount'] > 0): ?>
                                <tr>
                                    <td>Bulk Discount</td>
                                    <td class="text-right text-danger">-₱<?= number_format($order['bulk_discount'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <?php if ($order['loyalty_discount'] > 0): ?>
                                <tr>
                                    <td>Loyalty Discount</td>
                                    <td class="text-right text-danger">-₱<?= number_format($order['loyalty_discount'], 2) ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td>Tax</td>
                                    <td class="text-right">₱<?= number_format($order['tax'], 2) ?></td>
                                </tr>
                                <?php if ($order['delivery_method'] == 'delivery'): ?>
                                <tr>
                                    <td>Delivery Fee</td>
                                    <td class="text-right">
                                        <?php if ($order['delivery_fee'] > 0): ?>
                                        ₱<?= number_format($order['delivery_fee'], 2) ?>
                                        <?php else: ?>
                                        <span class="text-success">FREE</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td class="font-weight-bold">Total</td>
                                    <td class="text-right font-weight-bold">₱<?= number_format($order['total'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- What's Next Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">What's Next?</h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"><i class="fas fa-check"></i></div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Order Placed</h6>
                                <p class="small text-muted mb-3"><?= $order['formatted_date'] ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker <?= in_array($order['status'], ['processing', 'ready', 'shipped', 'delivered']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= in_array($order['status'], ['processing', 'ready', 'shipped', 'delivered']) ? '<i class="fas fa-check"></i>' : '' ?>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Processing</h6>
                                <p class="small mb-3">We're preparing your order</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker <?= in_array($order['status'], ['ready', 'shipped', 'delivered']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= in_array($order['status'], ['ready', 'shipped', 'delivered']) ? '<i class="fas fa-check"></i>' : '' ?>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1"><?= $order['delivery_method'] == 'delivery' ? 'Ready for Delivery' : 'Ready for Pickup' ?></h6>
                                <p class="small mb-3">Your order is packed and ready</p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker <?= in_array($order['status'], ['shipped', 'delivered']) ? 'bg-success' : 'bg-secondary' ?>">
                                <?= in_array($order['status'], ['shipped', 'delivered']) ? '<i class="fas fa-check"></i>' : '' ?>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1"><?= $order['delivery_method'] == 'delivery' ? 'Out for Delivery' : 'Picked Up' ?></h6>
                                <p class="small mb-3"><?= $order['delivery_method'] == 'delivery' ? 'Your order is on its way' : 'You\'ve collected your order' ?></p>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker <?= $order['status'] == 'delivered' ? 'bg-success' : 'bg-secondary' ?>">
                                <?= $order['status'] == 'delivered' ? '<i class="fas fa-check"></i>' : '' ?>
                            </div>
                            <div class="timeline-content">
                                <h6 class="mb-1">Completed</h6>
                                <p class="small mb-0">Enjoy your fresh eggs!</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Help Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Need Help?</h6>
                </div>
                <div class="card-body">
                    <p>If you have any questions about your order, please contact our customer support:</p>
                    <p class="mb-1"><i class="fas fa-phone-alt mr-2"></i> (02) 8123-4567</p>
                    <p class="mb-1"><i class="fas fa-envelope mr-2"></i> support@poultryv2.com</p>
                    <p class="mb-0"><i class="fas fa-clock mr-2"></i> Mon-Sat, 8:00 AM - 5:00 PM</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-marker {
    position: absolute;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    left: -30px;
    top: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-item:not(:last-child):before {
    content: '';
    position: absolute;
    left: -19px;
    top: 24px;
    height: calc(100% - 24px);
    width: 2px;
    background-color: #e3e6f0;
}
</style>

<?php
// Helper function to get payment method name
function getPaymentMethodName($method) {
    switch ($method) {
        case 'cod':
            return 'Cash on Delivery/Pickup';
        case 'gcash':
            return 'GCash';
        case 'bank':
            return 'Bank Transfer';
        default:
            return ucfirst($method);
    }
}

// Helper function to get status badge class
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending':
            return 'warning';
        case 'processing':
            return 'info';
        case 'ready':
            return 'primary';
        case 'shipped':
            return 'info';
        case 'delivered':
            return 'success';
        case 'cancelled':
            return 'danger';
        default:
            return 'secondary';
    }
}
?>

<?php include_once "../layouts/consumer/footer.php"; ?>