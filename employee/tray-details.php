<?php
require_once __DIR__ . '/../config.php';

// Check if tray ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error-page'] = true;
    header('Location: ' . view('auth.error.404'));
    exit;
}

$tray_id = (int)$_GET['id'];
$farm_id = $user['farm_id'];

// Fetch tray details
$mysqli = db_connect();
$query = "SELECT * FROM trays WHERE tray_id = ? AND farm_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("ii", $tray_id, $farm_id);
$stmt->execute();
$tray = $stmt->get_result()->fetch_assoc();

if (!$tray) {
    $_SESSION['error-page'] = true;
    header('Location: ' . view('auth.error.404'));
    exit;
}

// Fetch eggs in the tray
$query = "SELECT e.* 
          FROM egg_data e
          INNER JOIN tray_eggs te ON e.id = te.egg_id
          WHERE te.tray_id = ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $tray_id);
$stmt->execute();
$eggs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$title = 'Tray Details';
$sub_title = 'Detailed view of tray #' . $tray_id;

ob_start();
?>

<div class="container-fluid">
    <!-- Back button -->
    <div class="mb-4">
        <a href="<?= view('employee.inventory') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="fas fa-arrow-left me-1"></i> Back to Inventory
        </a>
    </div>

    <!-- Tray Information Card -->
    <div class="card shadow border-0 mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Tray Information</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Tray ID</label>
                        <p class="fw-bold"><?= special_chars($tray['tray_id']) ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Size</label>
                        <p class="fw-bold">
                            <span class="badge bg-<?= 
                                $tray['size'] == 'Pewee' ? 'primary' : 
                                ($tray['size'] == 'Pullets' ? 'primary' : 
                                ($tray['size'] == 'Small' ? 'info' : 
                                ($tray['size'] == 'Medium' ? 'info' : 
                                ($tray['size'] == 'Large' ? 'success' :
                                ($tray['size'] == 'Jumbo' ? 'success' : 
                                ($tray['size'] == 'Extra Large' ? 'warning' : 'danger'))))))
                            ?>">
                                <?= special_chars($tray['size']) ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Egg Count</label>
                        <p class="fw-bold"><?= special_chars($tray['egg_count']) ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Stock Count</label>
                        <p class="fw-bold"><?= special_chars($tray['stock_count']) ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Price</label>
                        <p class="fw-bold">₱<?= number_format($tray['price'], 2) ?></p>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Published</label>
                        <p class="fw-bold"><?= !empty($tray['published_at']) ? date('M d, Y', strtotime($tray['published_at'])) : 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Status</label>
                        <p class="fw-bold">
                            <span class="badge bg-<?= 
                                $tray['status'] == 'pending' ? 'warning' : 
                                ($tray['status'] == 'published' ? 'success' : 
                                ($tray['status'] == 'sold' ? 'info' : 'danger')) 
                            ?>">
                                <?= ucfirst(special_chars($tray['status'])) ?>
                            </span>
                        </p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Expiration Date</label>
                        <p class="fw-bold"><?= $tray['expired_at'] ? date('M d, Y', strtotime($tray['expired_at'])) : 'N/A' ?></p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="mb-3">
                        <label class="form-label text-muted small">Device MAC</label>
                        <p class="fw-bold"><?= special_chars($tray['device_mac']) ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Eggs Table -->
    <div class="card shadow border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">Eggs in Tray</h5>
            <div>
                <span class="badge bg-light text-dark">
                    Showing <?= count($eggs) ?> eggs
                </span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Weight (g)</th>
                            <th>First Weight (g)</th>
                            <th>Last Weight (g)</th>
                            <th>Mode Weight (g)</th>
                            <th>Size</th>
                            <th>Validation</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($eggs) > 0): ?>
                            <?php foreach ($eggs as $egg): ?>
                                <tr>
                                    <td><?= special_chars($egg['id']) ?></td>
                                    <td><?= number_format($egg['egg_weight'], 2) ?></td>
                                    <td><?= number_format($egg['first_weight'], 2) ?></td>
                                    <td><?= number_format($egg['last_weight'], 2) ?></td>
                                    <td><?= number_format($egg['mode_weight'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-bg-<?= 
                                            $egg['size'] == 'Pewee' ? 'primary' : 
                                            ($egg['size'] == 'Pullets' ? 'primary' : 
                                            ($egg['size'] == 'Small' ? 'info' : 
                                            ($egg['size'] == 'Medium' ? 'info' : 
                                            ($egg['size'] == 'Large' ? 'success' :
                                            ($egg['size'] == 'Jumbo' ? 'success' : 
                                            ($egg['size'] == 'Extra Large' ? 'warning' : 'danger'))))))
                                        ?>">
                                            <?= special_chars($egg['size']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $egg['validation_status'] == 'pending' ? 'warning' : 
                                            ($egg['validation_status'] == 'approved' ? 'success' : 'danger') 
                                        ?>">
                                            <?= ucfirst(special_chars($egg['validation_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary view-egg" data-id="<?= $egg['id'] ?>">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">No eggs found in this tray.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Egg Details Modal -->
<div class="modal fade" id="eggModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Egg Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="eggDetails">
                <!-- Content will be loaded by AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // View egg details
    $('.view-egg').on('click', function() {
        const eggId = $(this).data('id');
        $.ajax({
            url: '<?= base_url('api/eggs/get-egg-details.php') ?>',
            type: 'POST',
            data: {
                token: '<?= special_chars($_SESSION['token']) ?>',
                egg_id: eggId
            },
            dataType: 'html',
            success: function(data) {
                $('#eggDetails').html(data);
                $('#eggModal').modal('show');
            },
            error: function() {
                alert('Failed to load egg details.');
            }
        });
    });
});
</script>

<?php
$push_css = [];
$push_js = [];

$content = ob_get_clean();
include layouts('employee.main');
?>