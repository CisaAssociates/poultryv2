<?php
require_once __DIR__ . '/../config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Order ID is required';
    header('Location: ' . view('consumer.orders'));
    exit;
}

$order_id = (int)$_GET['id'];

// Verify that the order belongs to the current user
$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM consumer_orders WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result['count'] == 0) {
    $_SESSION['error'] = 'Order not found or you do not have permission to view it';
    header('Location: ' . view('consumer.orders'));
    exit;
}

// Fetch order details
$stmt = $mysqli->prepare("SELECT o.*, a.* FROM consumer_orders o JOIN consumer_addresses a ON o.address_id = a.address_id WHERE o.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// Fetch order items
$stmt = $mysqli->prepare("SELECT i.*, t.size, f.farm_name FROM consumer_order_items i JOIN trays t ON i.tray_id = t.tray_id JOIN farms f ON t.farm_id = f.farm_id WHERE i.order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Check if user has already reviewed this order
$stmt = $mysqli->prepare("SELECT review_id, rating, comment FROM consumer_reviews WHERE order_id = ? AND user_id = ?");
$stmt->bind_param("ii", $order_id, $_SESSION['id']);
$stmt->execute();
$review = $stmt->get_result()->fetch_assoc();

$title = 'Order Details';
$sub_title = 'Order #' . $order_id;
ob_start();
?>

<div class="container">
    <div class="row mb-3">
        <div class="col-12">
            <a href="<?= view('consumer.orders') ?>" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Orders
            </a>
        </div>
    </div>
    
    <div class="row">
        <div class="col-lg-8">
            <!-- Order Information -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Order #<?= $order_id ?></h4>
                    <span class="badge <?= 
                        $order['status'] == 'pending' ? 'bg-secondary' : 
                        ($order['status'] == 'confirmed' ? 'bg-primary' : 
                        ($order['status'] == 'packing' ? 'bg-info' : 
                        ($order['status'] == 'shipped' ? 'bg-warning' : 
                        ($order['status'] == 'delivered' ? 'bg-success' : 'bg-danger')))) 
                    ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Order Information</h5>
                            <table class="table table-sm table-borderless">
                                <tr>
                                    <td class="text-muted">Order Date:</td>
                                    <td><?= !empty($order['order_date']) ? date('F d, Y h:i A', strtotime($order['order_date'])) : 'N/A' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Payment Method:</td>
                                    <td><?= $order['payment_method'] ?: 'Cash on Delivery' ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Payment Status:</td>
                                    <td><?= ucfirst($order['payment_status']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted">Delivery Method:</td>
                                    <td><?= ucfirst($order['delivery_method']) ?></td>
                                </tr>
                                <?php if ($order['delivery_method'] == 'delivery' && $order['delivery_date']): ?>
                                <tr>
                                    <td class="text-muted">Delivery Schedule:</td>
                                    <td>
                                        <?= date('F d, Y', strtotime($order['delivery_date'])) ?>
                                        <?= $order['delivery_time'] ? ' at ' . date('h:i A', strtotime($order['delivery_time'])) : '' ?>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Delivery Address</h5>
                            <address>
                                <strong><?= special_chars($order['recipient_name']) ?></strong><br>
                                <?= special_chars($order['street_address']) ?><br>
                                <?= special_chars($order['barangay']) ?>, <?= special_chars($order['city']) ?><br>
                                <?= special_chars($order['province']) ?>, <?= special_chars($order['zip_code']) ?><br>
                                <abbr title="Phone">P:</abbr> <?= special_chars($order['contact_number']) ?>
                            </address>
                        </div>
                    </div>
                    
                    <?php if ($order['status'] == 'shipped'): ?>
                    <div class="alert alert-info mt-3">
                        <i class="bi bi-truck"></i> Your order is on the way! Expected delivery on 
                        <?= !empty($order['delivery_date']) ? date('F d, Y', strtotime($order['delivery_date'])) : 'a date to be determined' ?>.
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($order['status'] == 'delivered'): ?>
                    <div class="alert alert-success mt-3">
                        <i class="bi bi-check-circle"></i> Your order was delivered on 
                        <?= !empty($order['delivery_date']) ? date('F d, Y', strtotime($order['delivery_date'])) : 'a date to be determined' ?>.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Order Items</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Farm</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="flex-shrink-0">
                                                <div class="bg-light rounded" style="width: 48px; height: 48px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-egg-fill text-warning"></i>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <h6 class="mb-0"><?= ucfirst($item['size']) ?> Eggs</h6>
                                                <small class="text-muted">Tray #<?= $item['tray_id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?= special_chars($item['farm_name']) ?></td>
                                    <td>₱<?= number_format($item['unit_price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td class="text-end">₱<?= number_format($item['total_price'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">₱<?= number_format($order['total_amount'], 2) ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Review Section -->
            <?php if ($order['status'] == 'delivered'): ?>
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0"><?= $review ? 'Your Review' : 'Leave a Review' ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($review): ?>
                    <div class="mb-3">
                        <div class="mb-2">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill' : '' ?> text-warning"></i>
                            <?php endfor; ?>
                            <span class="ms-2"><?= $review['rating'] ?>/5</span>
                        </div>
                        <p class="mb-0"><?= special_chars($review['comment']) ?></p>
                    </div>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editReviewModal">
                        Edit Review
                    </button>
                    <?php else: ?>
                    <form id="review-form" method="post" action="api/reviews/submit.php">
                        <input type="hidden" name="order_id" value="<?= $order_id ?>">
                        <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Rating</label>
                            <div class="rating">
                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" value="<?= $i ?>" id="rating-<?= $i ?>">
                                <label for="rating-<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                                <?php endfor; ?>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="comment" class="form-label">Your Review</label>
                            <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="Share your experience with this order..."></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card mb-4">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Order Summary</h4>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span>₱<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Shipping:</span>
                        <span>₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax:</span>
                        <span>Included</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-0">
                        <strong>Total:</strong>
                        <strong>₱<?= number_format($order['total_amount'], 2) ?></strong>
                    </div>
                </div>
            </div>
            
            <!-- Order Timeline -->
            <div class="card">
                <div class="card-header bg-white">
                    <h4 class="mb-0">Order Timeline</h4>
                </div>
                <div class="card-body">
                    <ul class="timeline">
                        <li class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Order Placed</h5>
                                <p class="timeline-text"><?= !empty($order['order_date']) ? date('M d, Y h:i A', strtotime($order['order_date'])) : 'N/A' ?></p>
                            </div>
                        </li>
                        
                        <li class="timeline-item <?= $order['status'] == 'pending' ? 'timeline-item-pending' : '' ?>">
                            <div class="timeline-marker <?= in_array($order['status'], ['confirmed', 'packing', 'shipped', 'delivered']) ? '' : 'timeline-marker-outline' ?>"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Order Confirmed</h5>
                                <?php if (in_array($order['status'], ['confirmed', 'packing', 'shipped', 'delivered'])): ?>
                                <p class="timeline-text">Your order has been confirmed</p>
                                <?php else: ?>
                                <p class="timeline-text text-muted">Waiting for confirmation</p>
                                <?php endif; ?>
                            </div>
                        </li>
                        
                        <li class="timeline-item <?= $order['status'] == 'confirmed' ? 'timeline-item-pending' : '' ?>">
                            <div class="timeline-marker <?= in_array($order['status'], ['packing', 'shipped', 'delivered']) ? '' : 'timeline-marker-outline' ?>"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Packing</h5>
                                <?php if (in_array($order['status'], ['packing', 'shipped', 'delivered'])): ?>
                                <p class="timeline-text">Your order is being packed</p>
                                <?php else: ?>
                                <p class="timeline-text text-muted">Not yet started</p>
                                <?php endif; ?>
                            </div>
                        </li>
                        
                        <li class="timeline-item <?= $order['status'] == 'packing' ? 'timeline-item-pending' : '' ?>">
                            <div class="timeline-marker <?= in_array($order['status'], ['shipped', 'delivered']) ? '' : 'timeline-marker-outline' ?>"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Shipped</h5>
                                <?php if (in_array($order['status'], ['shipped', 'delivered'])): ?>
                                <p class="timeline-text">Your order is on the way</p>
                                <?php else: ?>
                                <p class="timeline-text text-muted">Not yet shipped</p>
                                <?php endif; ?>
                            </div>
                        </li>
                        
                        <li class="timeline-item <?= $order['status'] == 'shipped' ? 'timeline-item-pending' : '' ?>">
                            <div class="timeline-marker <?= $order['status'] == 'delivered' ? '' : 'timeline-marker-outline' ?>"></div>
                            <div class="timeline-content">
                                <h5 class="timeline-title">Delivered</h5>
                                <?php if ($order['status'] == 'delivered'): ?>
                                <p class="timeline-text">Your order has been delivered</p>
                                <?php else: ?>
                                <p class="timeline-text text-muted">Not yet delivered</p>
                                <?php endif; ?>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Review Modal -->
<?php if ($review): ?>
<div class="modal fade" id="editReviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Your Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="edit-review-form" method="post" action="api/reviews/update.php">
                    <input type="hidden" name="review_id" value="<?= $review['review_id'] ?>">
                    <input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Rating</label>
                        <div class="rating edit-rating">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                            <input type="radio" name="rating" value="<?= $i ?>" id="edit-rating-<?= $i ?>" <?= $review['rating'] == $i ? 'checked' : '' ?>>
                            <label for="edit-rating-<?= $i ?>"><i class="bi bi-star-fill"></i></label>
                            <?php endfor; ?>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit-comment" class="form-label">Your Review</label>
                        <textarea class="form-control" id="edit-comment" name="comment" rows="3"><?= special_chars($review['comment']) ?></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" form="edit-review-form" class="btn btn-primary">Update Review</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
/* Timeline Styles */
.timeline {
    position: relative;
    padding-left: 30px;
    list-style: none;
    margin-bottom: 0;
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
    width: 15px;
    height: 15px;
    left: -30px;
    background-color: #3498db;
    border-radius: 50%;
    box-shadow: 0 0 0 3px #fff;
}

.timeline-marker-outline {
    background-color: #fff;
    border: 2px solid #3498db;
}

.timeline-item:not(:last-child) .timeline-marker:before {
    content: '';
    width: 2px;
    height: calc(100% + 5px);
    background-color: #e0e0e0;
    position: absolute;
    left: 6px;
    top: 15px;
}

.timeline-item-pending .timeline-marker {
    animation: pulse 1.5s infinite;
}

@keyframes pulse {
    0% {
        box-shadow: 0 0 0 0 rgba(52, 152, 219, 0.7);
    }
    70% {
        box-shadow: 0 0 0 10px rgba(52, 152, 219, 0);
    }
    100% {
        box-shadow: 0 0 0 0 rgba(52, 152, 219, 0);
    }
}

/* Rating Styles */
.rating {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
}

.rating input {
    display: none;
}

.rating label {
    cursor: pointer;
    font-size: 1.5rem;
    color: #ddd;
    margin-right: 5px;
}

.rating input:checked ~ label,
.rating label:hover,
.rating label:hover ~ label {
    color: #ffc107;
}
</style>

<?php
$push_js = ['js/consumer/order-details.js'];
$content = ob_get_clean();
include layouts('consumer.main');
?>