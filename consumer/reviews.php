<?php
require_once __DIR__ . '/../config.php';

$mysqli = db_connect();

$title = 'Product Reviews';
$sub_title = 'View and Manage Your Reviews';
ob_start();

// Get user's reviews
$user_reviews_stmt = $mysqli->prepare("
    SELECT r.*, o.order_id, o.order_date, i.tray_id, t.size, f.farm_name
    FROM consumer_reviews r
    JOIN consumer_orders o ON r.order_id = o.order_id
    JOIN consumer_order_items i ON o.order_id = i.order_id
    JOIN trays t ON i.tray_id = t.tray_id
    JOIN farms f ON t.farm_id = f.farm_id
    WHERE r.user_id = ?
    GROUP BY r.review_id
    ORDER BY r.created_at DESC
");
$user_reviews_stmt->bind_param("i", $_SESSION['id']);
$user_reviews_stmt->execute();
$user_reviews = $user_reviews_stmt->get_result();

// Get all approved reviews
$all_reviews_stmt = $mysqli->prepare("
    SELECT r.*, u.fullname, o.order_date, i.tray_id, t.size, f.farm_name
    FROM consumer_reviews r
    JOIN users u ON r.user_id = u.id
    JOIN consumer_orders o ON r.order_id = o.order_id
    JOIN consumer_order_items i ON o.order_id = i.order_id
    JOIN trays t ON i.tray_id = t.tray_id
    JOIN farms f ON t.farm_id = f.farm_id
    WHERE r.approved = 1
    GROUP BY r.review_id
    ORDER BY r.created_at DESC
    LIMIT 50
");
$all_reviews_stmt->execute();
$all_reviews = $all_reviews_stmt->get_result();
?>

<div class="container">
    <!-- Tabs -->
    <ul class="nav nav-tabs mb-4" id="reviewsTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="my-reviews-tab" data-bs-toggle="tab" data-bs-target="#my-reviews" type="button" role="tab" aria-controls="my-reviews" aria-selected="true">
                My Reviews
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="all-reviews-tab" data-bs-toggle="tab" data-bs-target="#all-reviews" type="button" role="tab" aria-controls="all-reviews" aria-selected="false">
                All Reviews
            </button>
        </li>
    </ul>
    
    <!-- Tab Content -->
    <div class="tab-content" id="reviewsTabsContent">
        <!-- My Reviews Tab -->
        <div class="tab-pane fade show active" id="my-reviews" role="tabpanel" aria-labelledby="my-reviews-tab">
            <div class="row">
                <?php if ($user_reviews->num_rows > 0): ?>
                    <?php while ($review = $user_reviews->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?= special_chars(ucfirst($review['size'])) ?> Eggs</h5>
                                        <small class="text-muted">From <?= special_chars($review['farm_name']) ?></small>
                                    </div>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= ($i <= $review['rating']) ? '-fill' : '' ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= special_chars($review['comment']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">Reviewed on <?= !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : 'N/A' ?></small>
                                        <div>
                                            <a href="<?= view('consumer.order-details') ?>?id=<?= $review['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-white">
                                    <small class="text-muted">Order #<?= $review['order_id'] ?> - <?= !empty($review['order_date']) ? date('M d, Y', strtotime($review['order_date'])) : 'N/A' ?></small>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-star display-1 text-muted"></i>
                            <h4 class="mt-3">No Reviews Yet</h4>
                            <p class="text-muted">You haven't reviewed any products yet</p>
                            <a href="<?= view('consumer.orders') ?>" class="btn btn-primary mt-2">
                                View Your Orders
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- All Reviews Tab -->
        <div class="tab-pane fade" id="all-reviews" role="tabpanel" aria-labelledby="all-reviews-tab">
            <div class="row">
                <?php if ($all_reviews->num_rows > 0): ?>
                    <?php while ($review = $all_reviews->fetch_assoc()): ?>
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                                    <div>
                                        <h5 class="mb-0"><?= special_chars(ucfirst($review['size'])) ?> Eggs</h5>
                                        <small class="text-muted">From <?= special_chars($review['farm_name']) ?></small>
                                    </div>
                                    <div>
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="bi bi-star<?= ($i <= $review['rating']) ? '-fill' : '' ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <p class="card-text"><?= special_chars($review['comment']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <small class="text-muted">Reviewed by <?= special_chars($review['fullname']) ?></small>
                                        <small class="text-muted"><?= !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : 'N/A' ?></small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="text-center py-5">
                            <i class="bi bi-star display-1 text-muted"></i>
                            <h4 class="mt-3">No Reviews Yet</h4>
                            <p class="text-muted">There are no product reviews yet</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Toast for notifications -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 11">
    <div id="liveToast" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header bg-primary text-white">
            <strong class="me-auto">Notification</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body"></div>
    </div>
</div>

<?php
$push_js = ['js/consumer/reviews.js'];
$content = ob_get_clean();
include layouts('consumer.main');
?>