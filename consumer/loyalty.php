<?php
require_once __DIR__ . '/../config.php';

$title = 'Loyalty Program';
$sub_title = 'Earn Points and Rewards';
ob_start();

// Get user's loyalty information
$mysqli = db_connect();
$stmt = $mysqli->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$loyalty_result = $stmt->get_result();
$loyalty = $loyalty_result->fetch_assoc();
$loyalty_points = $loyalty ? $loyalty['points'] : 0;

// Get all loyalty tiers from the database
$tiers_query = $mysqli->query("SELECT * FROM loyalty_tiers ORDER BY points_required ASC");
$all_tiers = $tiers_query->fetch_all(MYSQLI_ASSOC);

// Get user's current tier based on points
$stmt = $mysqli->prepare("SELECT * FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
$stmt->bind_param("i", $loyalty_points);
$stmt->execute();
$current_tier_result = $stmt->get_result();
$current_tier = $current_tier_result->fetch_assoc();

// Find next tier if exists
$next_tier = null;
$points_to_next = 0;
$progress_percentage = 100;

foreach ($all_tiers as $tier) {
    if ($tier['points_required'] > $loyalty_points) {
        $next_tier = $tier;
        $points_to_next = $tier['points_required'] - $loyalty_points;
        $previous_tier_points = $current_tier ? $current_tier['points_required'] : 0;
        $tier_range = $tier['points_required'] - $previous_tier_points;
        $progress_percentage = min(100, (($loyalty_points - $previous_tier_points) / $tier_range) * 100);
        break;
    }
}

// Get points history
$stmt = $mysqli->prepare("SELECT * FROM consumer_loyalty_history WHERE user_id = ? ORDER BY date_added DESC LIMIT 5");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$points_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>


<div class="row">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h2 class="card-title">Your Tier</h2>
                <div class="my-4">
                    <div class="tier-badge <?= strtolower($current_tier['tier_name'] ?? 'bronze') ?> mx-auto">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <h3 class="mt-3"><?= $current_tier ? $current_tier['tier_name'] : 'Bronze' ?> Member</h3>
                    <p class="text-muted">
                        <i class="bi bi-tag-fill me-1"></i> <?= $current_tier ? $current_tier['discount_percentage'] : 0 ?>% discount on all purchases
                    </p>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar" style="width: <?= $progress_percentage ?>%"><?= round($progress_percentage) ?>%</div>
                </div>
                <?php if ($next_tier): ?>
                    <p class="text-muted"><?= $points_to_next ?> points to <?= $next_tier['tier_name'] ?></p>
                    <p class="text-primary small">
                        <i class="bi bi-arrow-up-circle-fill me-1"></i> Unlock <?= $next_tier['discount_percentage'] ?>% discount at <?= $next_tier['tier_name'] ?> tier
                    </p>
                <?php else: ?>
                    <p class="text-success"><i class="bi bi-trophy-fill me-1"></i> You've reached our highest tier!</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4 class="mb-0">Points History</h4>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php if (empty($points_history)): ?>
                        <li class="list-group-item text-center py-4">
                            <i class="bi bi-clock-history text-muted display-4"></i>
                            <p class="mt-3 mb-0">No points history yet. Start shopping to earn points!</p>
                        </li>
                    <?php else: ?>
                        <?php foreach ($points_history as $history): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h6 class="mb-0"><?= htmlspecialchars($history['description']) ?></h6>
                                        <small class="text-muted"><?= !empty($history['date_added']) ? date('M d, Y', strtotime($history['date_added'])) : 'N/A' ?></small>
                                    </div>
                                    <div class="text-end">
                                        <span class="<?= $history['points'] > 0 ? 'text-success' : 'text-danger' ?>">
                                            <?= $history['points'] > 0 ? '+' : '' ?><?= $history['points'] ?> points
                                        </span>
                                        <?php if (!empty($history['order_id'])): ?>
                                            <div class="text-muted">Order #<?= $history['order_id'] ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>

                <div class="alert alert-info mt-4">
                    <h5>How to Earn Points</h5>
                    <ul class="mb-0">
                        <li>₱10 spent = 1 point</li>
                        <li>Monthly loyalty bonus: 10 points</li>
                        <li>Refer a friend: 50 points</li>
                        <li>Product review: 5 points</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>