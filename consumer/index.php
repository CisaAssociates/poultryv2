<?php
require_once __DIR__ . '/../config.php';

$title = 'Consumer Dashboard';
$sub_title = 'Welcome to Your Egg Shopping Dashboard';
ob_start();

// Get user's loyalty information
$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$loyalty_result = $stmt->get_result();
$loyalty = $loyalty_result->fetch_assoc();
$loyalty_points = $loyalty ? $loyalty['points'] : 0;

// Get user's loyalty tier based on points
$stmt = $mysqli->prepare("SELECT * FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
$stmt->bind_param("i", $loyalty_points);
$stmt->execute();
$loyalty_tier_result = $stmt->get_result();
$loyalty_tier = $loyalty_tier_result->fetch_assoc();

// Create loyalty array with points and tier name for backward compatibility
$loyalty = [
    'points' => $loyalty_points,
    'tier' => $loyalty_tier ? strtolower($loyalty_tier['tier_name']) : 'bronze'
];

// Get recent orders
$stmt = $mysqli->prepare("SELECT order_id, order_date, total_amount, status FROM consumer_orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$recent_orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available products count
$stmt = $mysqli->query("SELECT COUNT(*) as count FROM trays WHERE status = 'published'");
$available_products = $stmt->fetch_assoc()['count'];

// Get cart items count
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM consumer_cart_items ci JOIN consumer_carts c ON ci.cart_id = c.cart_id WHERE c.user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_assoc()['count'];
?>

<!-- Welcome Banner -->
<div class="row">
    <div class="col-12">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h2 class="card-title text-white">Welcome, <?= $user['fullname'] ?>!</h2>
                        <p class="card-text mb-md-0">
                            <?php $tier = $loyalty['tier'] ?? 'bronze'; ?>
                            You're currently a <strong><?= ucfirst($tier) ?></strong> member with <strong><?= $loyalty['points'] ?? 0 ?></strong> loyalty points.
                            <?php if (isset($tierInfo[$tier]['discount']) && $tierInfo[$tier]['discount'] > 0): ?>
                                You receive a <strong><?= $tierInfo[$tier]['discount'] ?>%</strong> discount on all purchases!
                            <?php endif; ?>
                            <?php if ($loyalty && isset($currentTier['next'])): ?>
                                Keep shopping to reach the next tier!
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end">
                        <a href="<?= view('consumer.products') ?>" class="btn btn-light">Shop Now</a>
                        <a href="<?= view('consumer.loyalty') ?>" class="btn btn-outline-light ms-2 text-white">View Rewards</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-light text-primary rounded-circle">
                            <i class="bi bi-egg-fill font-24"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mt-0 mb-1 font-20"><?= $available_products ?></h4>
                        <p class="mb-0">Available Products</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-light text-success rounded-circle">
                            <i class="bi bi-cart-fill font-24"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mt-0 mb-1 font-20"><?= $cart_items ?></h4>
                        <p class="mb-0">Items in Cart</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="avatar-sm flex-shrink-0">
                        <span class="avatar-title bg-light text-warning rounded-circle">
                            <i class="bi bi-award-fill font-24"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1 ms-3">
                        <h4 class="mt-0 mb-1 font-20"><?= $loyalty['points'] ?? 0 ?></h4>
                        <p class="mb-0">Loyalty Points</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders & Featured Products -->
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white d-flex align-items-center justify-content-between">
                <h4 class="card-title mb-0">Recent Orders</h4>
                <a href="<?= view('consumer.orders') ?>" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body">
                <?php if (empty($recent_orders)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-cart-x display-4 text-muted"></i>
                        <p class="mt-3">You haven't placed any orders yet.</p>
                        <a href="<?= view('consumer.products') ?>" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-centered mb-0">
                            <thead>
                                <tr>
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order):
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
                                        <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                                        <td><span class="badge <?= $statusClass ?>"><?= ucfirst($order['status']) ?></span></td>
                                        <td>
                                            <a href="<?= view('consumer.order-details') ?>?id=<?= $order['order_id'] ?>" class="btn btn-sm btn-outline-primary">View</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="card-title mb-0">Loyalty Program</h4>
            </div>
            <div class="card-body">
                <?php
                $tier = $loyalty['tier'] ?? 'bronze';
                $points = $loyalty['points'] ?? 0;
                
                // Get all loyalty tiers from the database
                $tiers_query = $mysqli->query("SELECT * FROM loyalty_tiers ORDER BY points_required ASC");
                $all_tiers = $tiers_query->fetch_all(MYSQLI_ASSOC);
                
                // Create tier info array from database
                $tierInfo = [];
                $tierColors = ['bronze' => 'warning', 'silver' => 'secondary', 'gold' => 'warning', 'platinum' => 'info'];
                
                foreach ($all_tiers as $index => $tier_data) {
                    $tier_name = strtolower($tier_data['tier_name']);
                    $next_tier = isset($all_tiers[$index + 1]) ? strtolower($all_tiers[$index + 1]['tier_name']) : null;
                    $next_required = isset($all_tiers[$index + 1]) ? $all_tiers[$index + 1]['points_required'] : null;
                    
                    $tierInfo[$tier_name] = [
                        'next' => $next_tier,
                        'required' => $next_required,
                        'color' => $tierColors[$tier_name] ?? 'primary',
                        'discount' => $tier_data['discount_percentage']
                    ];
                }
                
                $currentTier = $tierInfo[$tier] ?? $tierInfo[array_key_first($tierInfo)];
                $progress = $currentTier['next'] && $currentTier['required'] ? min(100, ($points / $currentTier['required']) * 100) : 100;
                ?>

                <div class="text-center mb-4">
                    <div class="tier-badge <?= $tier ?> mx-auto">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <h3 class="mt-3"><?= ucfirst($tier) ?> Member</h3>
                    <p class="text-muted">
                        <i class="bi bi-tag-fill me-1"></i> <?= $currentTier['discount'] ?>% discount on all purchases
                    </p>
                </div>

                <?php if ($currentTier['next']): ?>
                    <div class="progress mb-2" style="height: 10px;">
                        <div class="progress-bar bg-<?= $currentTier['color'] ?>" style="width: <?= $progress ?>%"></div>
                    </div>
                    <p class="text-center text-muted mb-1">
                        <?= $currentTier['required'] - $points ?> more points to <?= ucfirst($currentTier['next']) ?>
                    </p>
                    <?php if (isset($tierInfo[$currentTier['next']]['discount'])): ?>
                    <p class="text-center text-primary small mb-3">
                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Unlock <?= $tierInfo[$currentTier['next']]['discount'] ?>% discount at <?= ucfirst($currentTier['next']) ?> tier
                    </p>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-success text-center">
                        <i class="bi bi-trophy-fill me-1"></i> You've reached our highest tier!
                    </div>
                <?php endif; ?>

                <a href="<?= view('consumer.loyalty') ?>" class="btn btn-outline-primary w-100">View Benefits</a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>