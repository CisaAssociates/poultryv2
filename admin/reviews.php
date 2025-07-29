<?php
require_once __DIR__ . '/../config.php';

$title = 'Product Reviews';
$sub_title = 'Manage Customer Reviews';
ob_start();

$mysqli = db_connect();

// Handle review approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_review'])) {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid token';
    } else {
        $review_id = (int)$_POST['review_id'];
        $approved = isset($_POST['approved']) ? 1 : 0;
        
        $stmt = $mysqli->prepare("UPDATE consumer_reviews SET approved = ? WHERE review_id = ?");
        $stmt->bind_param("ii", $approved, $review_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Review status updated successfully';
        } else {
            $_SESSION['error'] = 'Failed to update review status';
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Build query based on filters
$query = "SELECT r.*, u.fullname, u.email, o.order_id, o.order_date, t.size, f.farm_name 
          FROM consumer_reviews r 
          JOIN users u ON r.user_id = u.id 
          JOIN consumer_orders o ON r.order_id = o.order_id 
          JOIN consumer_order_items i ON o.order_id = i.order_id 
          JOIN trays t ON i.tray_id = t.tray_id 
          JOIN farms f ON t.farm_id = f.farm_id";

$where_clauses = [];
$params = [];
$param_types = "";

if ($status_filter !== '') {
    $where_clauses[] = "r.approved = ?";
    $params[] = $status_filter;
    $param_types .= "i";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " GROUP BY r.review_id ORDER BY r.created_at DESC";

$stmt = $mysqli->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}

$stmt->execute();
$reviews = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!-- Filter Controls -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Reviews</option>
                    <option value="1" <?= $status_filter === '1' ? 'selected' : '' ?>>Approved</option>
                    <option value="0" <?= $status_filter === '0' ? 'selected' : '' ?>>Pending Approval</option>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="mdi mdi-filter-outline me-1"></i> Apply Filters
                </button>
                <a href="<?= view('admin.reviews') ?>" class="btn btn-secondary">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Reviews Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered table-striped dt-responsive nowrap w-100" id="reviews-datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Product</th>
                        <th>Customer</th>
                        <th>Rating</th>
                        <th>Review</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($reviews) > 0): ?>
                        <?php foreach ($reviews as $review): ?>
                            <tr>
                                <td>#<?= $review['review_id'] ?></td>
                                <td>
                                    <div><?= special_chars(ucfirst($review['size'])) ?> Eggs</div>
                                    <small class="text-muted">From <?= special_chars($review['farm_name']) ?></small>
                                </td>
                                <td>
                                    <div><?= special_chars($review['fullname']) ?></div>
                                    <small class="text-muted"><?= special_chars($review['email']) ?></small>
                                </td>
                                <td>
                                    <div class="rating-display">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="mdi mdi-star<?= ($i <= $review['rating']) ? '' : '-outline' ?> text-warning"></i>
                                        <?php endfor; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width: 200px;">
                                        <?= special_chars($review['comment']) ?>
                                    </div>
                                </td>
                                <td><?= !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : 'N/A' ?></td>
                                <td>
                                    <span class="badge bg-<?= $review['approved'] ? 'success' : 'warning' ?>">
                                        <?= $review['approved'] ? 'Approved' : 'Pending' ?>
                                    </span>
                                </td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary view-review" data-bs-toggle="modal" data-bs-target="#reviewModal"
                                        data-review-id="<?= $review['review_id'] ?>"
                                        data-product="<?= special_chars(ucfirst($review['size'])) ?> Eggs"
                                        data-farm="<?= special_chars($review['farm_name']) ?>"
                                        data-customer="<?= special_chars($review['fullname']) ?>"
                                        data-email="<?= special_chars($review['email']) ?>"
                                        data-rating="<?= $review['rating'] ?>"
                                        data-comment="<?= special_chars($review['comment']) ?>"
                                        data-date="<?= !empty($review['created_at']) ? date('M d, Y', strtotime($review['created_at'])) : 'N/A' ?>"
                                        data-approved="<?= $review['approved'] ?>">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No reviews found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-labelledby="reviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalLabel">Review Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="review-details mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 id="product-name"></h5>
                            <p class="mb-1"><strong>Farm:</strong> <span id="farm-name"></span></p>
                            <p class="mb-0"><strong>Date:</strong> <span id="review-date"></span></p>
                        </div>
                        <div class="col-md-6">
                            <h5>Customer Information</h5>
                            <p class="mb-1"><strong>Name:</strong> <span id="customer-name"></span></p>
                            <p class="mb-0"><strong>Email:</strong> <span id="customer-email"></span></p>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Rating</h5>
                    </div>
                    <div class="card-body">
                        <div class="rating-display" id="review-rating">
                            <!-- Rating stars will be inserted here by JavaScript -->
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">Review Comment</h5>
                    </div>
                    <div class="card-body">
                        <p id="review-comment"></p>
                    </div>
                </div>

                <form action="" method="POST" id="review-form">
                    <?= csrf_token() ?>
                    <input type="hidden" name="review_id" id="modal-review-id">
                    <input type="hidden" name="update_review" value="1">
                    
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="approved" name="approved">
                        <label class="form-check-label" for="approved">Approve this review</label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="review-form" class="btn btn-primary">Update Status</button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Messages for Feedback -->
<?php if (isset($_SESSION['success'])): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <div class="toast align-items-center text-white bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="mdi mdi-check-circle me-1"></i> <?= $_SESSION['success'] ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php unset($_SESSION['success']); endif; ?>

<?php if (isset($_SESSION['error'])): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index: 1050">
    <div class="toast align-items-center text-white bg-danger border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body">
                <i class="mdi mdi-alert-circle me-1"></i> <?= $_SESSION['error'] ?>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<?php unset($_SESSION['error']); endif; ?>

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
    'js/admin/reviews.js',
];

$content = ob_get_clean();
include layouts('admin.main');
?>