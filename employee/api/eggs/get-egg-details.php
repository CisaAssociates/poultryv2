<?php
require_once __DIR__ . '/../../../config.php';

// Verify token
if (!verify_token($_POST['token'])) {
    echo '<div class="alert alert-danger">Invalid token</div>';
    exit;
}

if (!isset($_POST['egg_id']) || !is_numeric($_POST['egg_id'])) {
    echo '<div class="alert alert-danger">Invalid egg ID</div>';
    exit;
}

$egg_id = (int)$_POST['egg_id'];
$mysqli = db_connect();

// Fetch egg details
$query = "SELECT * FROM egg_data WHERE id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $egg_id);
$stmt->execute();
$egg = $stmt->get_result()->fetch_assoc();

if (!$egg) {
    echo '<div class="alert alert-danger">Egg not found</div>';
    exit;
}

// Display egg details
?>
<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Egg ID</label>
            <p class="fw-bold"><?= $egg['id'] ?></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Size</label>
            <p class="fw-bold">
                <span class="badge bg-bg-<?= 
                    $egg['size'] == 'Pewee' ? 'primary' : 
                    ($egg['size'] == 'Pullets' ? 'primary' : 
                    ($egg['size'] == 'Small' ? 'info' : 
                    ($egg['size'] == 'Medium' ? 'info' : 
                    ($egg['size'] == 'Large' ? 'success' :
                    ($egg['size'] == 'Jumbo' ? 'success' : 
                    ($egg['size'] == 'Extra Large' ? 'warning' : 'danger'))))))
                ?>">
                    <?= $egg['size'] ?>
                </span>
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Weight (g)</label>
            <p class="fw-bold"><?= number_format($egg['egg_weight'], 2) ?></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Validation Status</label>
            <p class="fw-bold">
                <span class="badge bg-<?= 
                    $egg['validation_status'] == 'pending' ? 'warning' : 
                    ($egg['validation_status'] == 'approved' ? 'success' : 'danger') 
                ?>">
                    <?= ucfirst($egg['validation_status']) ?>
                </span>
            </p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">First Weight (g)</label>
            <p class="fw-bold"><?= number_format($egg['first_weight'], 2) ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Last Weight (g)</label>
            <p class="fw-bold"><?= number_format($egg['last_weight'], 2) ?></p>
        </div>
    </div>
    <div class="col-md-4">
        <div class="mb-3">
            <label class="form-label">Mode Weight (g)</label>
            <p class="fw-bold"><?= number_format($egg['mode_weight'], 2) ?></p>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Recorded At</label>
            <p class="fw-bold"><?= date('M d, Y H:i:s', strtotime($egg['created_at'])) ?></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="mb-3">
            <label class="form-label">Device MAC</label>
            <p class="fw-bold"><?= $egg['mac'] ?></p>
        </div>
    </div>
</div>