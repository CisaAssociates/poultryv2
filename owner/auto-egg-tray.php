<?php
require_once __DIR__ . '/../config.php';

// Get the selected farm
$farm = [];
$farm_id = isset($_SESSION['selected_farm_id']) ? $_SESSION['selected_farm_id'] : $user['owner_farm_id'];
if ($farm_id) {
    $stmt = db_connect()->prepare("SELECT * FROM farms WHERE farm_id = ?");
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $farm = $result->fetch_assoc();
    }
}
// Get tray progress data
$progressData = [];
if (!empty($farm)) {
    // Check if farm has any devices
    $device_check = db_connect()->prepare("SELECT COUNT(*) as device_count FROM devices WHERE device_owner_id = ?");
    $device_check->bind_param("i", $farm['farm_id']);
    $device_check->execute();
    $device_result = $device_check->get_result()->fetch_assoc();
    
    if ($device_result['device_count'] == 0) {
        // Redirect to no devices error page
        header('Location: ' . view('owner.error.no-devices'));
        exit;
    }
    $stmt = db_connect()->prepare("
        SELECT size, COUNT(egg_data.id) AS egg_count
        FROM egg_data
        JOIN devices ON egg_data.mac = devices.device_mac
        WHERE devices.device_owner_id = ? 
          AND egg_data.id NOT IN (SELECT egg_id FROM tray_eggs)
        GROUP BY size
    ");
    $stmt->bind_param("i", $farm['farm_id']);
    $stmt->execute();
    $progressResult = $stmt->get_result();
    while ($row = $progressResult->fetch_assoc()) {
        $progressData[$row['size']] = $row['egg_count'];
    }
}

// Get tray statistics
$stats = [
    'pending' => 0,
    'published' => 0,
    'sold' => 0,
    'expiring' => 0
];
if (!empty($farm)) {
    $stmt = db_connect()->prepare("
        SELECT 
            SUM(IF(status = 'pending', 1, 0)) AS pending,
            SUM(IF(status = 'published', 1, 0)) AS published,
            SUM(IF(status = 'sold', 1, 0)) AS sold,
            SUM(IF(expired_at <= DATE_ADD(NOW(), INTERVAL 3 DAY), 1, 0)) AS expiring
        FROM trays
        WHERE farm_id = ?
    ");
    $stmt->bind_param("i", $farm['farm_id']);
    $stmt->execute();
    $statsResult = $stmt->get_result();
    if ($statsResult->num_rows > 0) {
        $stats = $statsResult->fetch_assoc();
    }
}

$title = 'Automatic Egg Tray';
$sub_title = 'Management';
ob_start();
?>


<!-- Dashboard Stats -->
<div class="row mb-3">
    <div class="col-12">
        <button type="button" class="btn btn-primary" id="checkNewTraysBtn">
            <i class="mdi mdi-refresh me-1"></i> Check for New Trays
        </button>
        <button type="button" class="btn btn-info ms-2" data-bs-toggle="modal" data-bs-target="#settingsModal">
            <i class="mdi mdi-cog me-1"></i> Settings
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="row">
    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm rounded bg-soft-primary me-3">
                        <i class="avatar-title text-primary rounded mdi mdi-egg font-20"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="mt-0 mb-1 font-20" id="pendingTraysCount"><?= $stats['pending'] ?></h4>
                        <p class="mb-0">Pending Trays</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm rounded bg-soft-success me-3">
                        <i class="avatar-title text-success rounded mdi mdi-package-variant font-20"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="mt-0 mb-1 font-20" id="listedTraysCount"><?= $stats['published'] ?></h4>
                        <p class="mb-0">Active Listings</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm rounded bg-soft-info me-3">
                        <i class="avatar-title text-info rounded mdi mdi-cart-outline font-20"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="mt-0 mb-1 font-20" id="soldTraysCount"><?= $stats['sold'] ?></h4>
                        <p class="mb-0">Sold This Week</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start">
                    <div class="avatar-sm rounded bg-soft-warning me-3">
                        <i class="avatar-title text-warning rounded mdi mdi-alert-circle-outline font-20"></i>
                    </div>
                    <div class="flex-1">
                        <h4 class="mt-0 mb-1 font-20" id="expiringTraysCount"><?= $stats['expiring'] ?></h4>
                        <p class="mb-0">Expiring Soon</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Egg Progress and Trays -->
<div class="row">
    <div class="col-xl-4">
        <!-- Tray Progress by Size -->
        <div class="card">
            <div class="card-body">
                <h4 class="header-title">Tray Progress by Size</h4>
                <p class="text-muted">Progress towards completing a full tray (30 eggs)</p>

                <div id="trayProgressContainer">
                    <?php if (!empty($progressData)): ?>
                        <?php foreach ($progressData as $size => $count): ?>
                            <?php $progressPercent = min(100, ($count / 30) * 100); ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium"><?= htmlspecialchars($size) ?> Eggs</span>
                                    <span><?= $count ?>/30 (<?= round($progressPercent) ?>%)</span>
                                </div>
                                <div class="progress tray-progress">
                                    <div class="progress-bar" role="progressbar"
                                        style="width: <?= $progressPercent ?>%"
                                        aria-valuenow="<?= $progressPercent ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">No eggs detected. Scan some eggs to get started.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notifications -->
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="header-title mb-0">Notifications</h4>
                    <button type="button" class="btn btn-sm btn-link text-muted" id="markAllReadBtn">
                        Mark All Read
                    </button>
                </div>

                <div id="notificationsContainer" style="max-height: 300px; overflow-y: auto;">
                    <!-- Notifications will be loaded dynamically -->
                    <div class="d-flex justify-content-center my-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <ul class="nav nav-tabs nav-bordered" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#pending-trays" role="tab">
                            Pending <span class="badge bg-primary rounded-pill ms-1" id="pendingTabCount"><?= $stats['pending'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#active-listings" role="tab">
                            Active <span class="badge bg-success rounded-pill ms-1" id="activeTabCount"><?= $stats['published'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sold-trays" role="tab">
                            Sold <span class="badge bg-info rounded-pill ms-1" id="soldTabCount"><?= $stats['sold'] ?></span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#expired-trays" role="tab">
                            Expired <span class="badge bg-danger rounded-pill ms-1" id="expiredTabCount">0</span>
                        </a>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="pending-trays">
                        <div class="mt-3" id="pendingTraysContainer">
                            <?php
                            // Get pending trays
                            $pendingTrays = [];
                            if (!empty($farm)) {
                                $stmt = db_connect()->prepare("
                                                    SELECT * FROM trays
                                                    WHERE farm_id = ? AND status = 'pending'
                                                    ORDER BY created_at DESC
                                                    LIMIT 10
                                                ");
                                $stmt->bind_param("i", $farm['farm_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $pendingTrays = $result->fetch_all(MYSQLI_ASSOC);
                            }
                            ?>

                            <?php if (!empty($pendingTrays)): ?>
                                <div class="row">
                                    <?php foreach ($pendingTrays as $tray): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card tray-card h-100">
                                                <div class="card-body">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <h5 class="card-title"><?= htmlspecialchars($tray['size']) ?> Eggs</h5>
                                                            <p class="card-text">
                                                                <span class="badge bg-primary"><?= $tray['egg_count'] ?>/30 eggs</span>
                                                                <span class="badge bg-info"><?= $tray['stock_count'] ?> in stock</span>
                                                            </p>
                                                        </div>
                                                        <span class="egg-size-badge badge bg-<?= getSizeColor($tray['size']) ?>">
                                                            <?= htmlspecialchars($tray['size']) ?>
                                                        </span>
                                                    </div>

                                                    <div class="progress mt-2 tray-progress">
                                                        <div class="progress-bar"
                                                            style="width: <?= min(100, ($tray['egg_count'] / 30) * 100) ?>%"></div>
                                                    </div>

                                                    <div class="mt-3 d-flex justify-content-end">
                                                        <button class="btn btn-sm btn-primary edit-tray-btn me-2"
                                                            data-tray-id="<?= $tray['tray_id'] ?>">
                                                            <i class="mdi mdi-pencil"></i> Edit
                                                        </button>
                                                        <button class="btn btn-sm btn-success publish-tray-btn"
                                                            data-tray-id="<?= $tray['tray_id'] ?>"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#publishTrayModal">
                                                            <i class="mdi mdi-cloud-upload"></i> Publish
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info mt-3">No pending trays. Scan more eggs to create new trays.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane" id="active-listings">
                        <div class="mt-3 row" id="activeTraysContainer">
                            <!-- Active trays will be loaded dynamically -->
                            <div class="col-lg-12 d-flex justify-content-center my-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="sold-trays">
                        <div class="mt-3" id="soldTraysContainer">
                            <!-- Sold trays will be loaded dynamically -->
                            <div class="d-flex justify-content-center my-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="expired-trays">
                        <div class="mt-3" id="expiredTraysContainer">
                            <!-- Expired trays will be loaded dynamically -->
                            <div class="d-flex justify-content-center my-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Tray Modal -->
<div class="modal fade" id="editTrayModal" tabindex="-1" aria-labelledby="editTrayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTrayModalLabel">Edit Tray</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editTrayForm">
                    <input type="hidden" id="edit-tray-id">
                    <div class="mb-3">
                        <label for="edit-tray-size" class="form-label">Size</label>
                        <select class="form-select" id="edit-tray-size">
                            <option value="small">Small</option>
                            <option value="medium">Medium</option>
                            <option value="large">Large</option>
                            <option value="xlarge">Extra Large</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit-tray-stock" class="form-label">Stock Count</label>
                        <input type="number" class="form-control" id="edit-tray-stock" min="1" value="1">
                        <small class="text-muted">Number of trays available in stock</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveTrayChangesBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-labelledby="settingsModalLabel" aria-hidden="hidden">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Auto-Listing Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs nav-bordered" role="tablist" id="settingsTabList">
                    <?php
                    $eggSizes = ['Jumbo', 'Extra Large', 'Large', 'Medium', 'Small', 'Pullets', 'Pewee'];
                    $settings = [];

                    if (!empty($farm)) {
                        $stmt = db_connect()->prepare("SELECT * FROM tray_settings WHERE farm_id = ?");
                        $stmt->bind_param("i", $farm['farm_id']);
                        $stmt->execute();
                        $result = $stmt->get_result();

                        while ($row = $result->fetch_assoc()) {
                            $settings[$row['size']] = $row;
                        }
                    }

                    foreach ($eggSizes as $index => $size):
                        $active = $index === 0 ? 'active' : '';
                    ?>
                        <li class="nav-item">
                            <a class="nav-link <?= $active ?>" data-bs-toggle="tab" href="#size-<?= str_replace(' ', '-', $size) ?>" role="tab">
                                <?= $size ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <div class="tab-content" id="settingsTabContent">
                    <?php foreach ($eggSizes as $index => $size):
                        $active = $index === 0 ? 'show active' : '';
                        $setting = $settings[$size] ?? [
                            'default_price' => 0.00,
                            'auto_publish' => 0
                        ];
                    ?>
                        <div class="tab-pane fade <?= $active ?>" id="size-<?= str_replace(' ', '-', $size) ?>" role="tabpanel">
                            <div class="p-3">
                                <div class="mb-3">
                                    <label for="price-<?= str_replace(' ', '-', $size) ?>" class="form-label">Default Price (₱)</label>
                                    <input type="number" class="form-control" id="price-<?= str_replace(' ', '-', $size) ?>"
                                        name="price[<?= $size ?>]" step="0.01" min="0"
                                        value="<?= $setting['default_price'] ?>">
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox"
                                            id="auto-publish-<?= str_replace(' ', '-', $size) ?>" name="auto_publish[<?= $size ?>]"
                                            <?= $setting['auto_publish'] ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="auto-publish-<?= str_replace(' ', '-', $size) ?>">
                                            Automatically publish when tray is full
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveSettingsBtn">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Publish Tray Modal -->
<div class="modal fade" id="publishTrayModal" tabindex="-1" aria-labelledby="publishTrayModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="publishTrayModalLabel">Publish Egg Tray</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="publishTrayForm">
                    <input type="hidden" id="publishTrayId" name="tray_id">

                    <div class="mb-3">
                        <label for="trayPrice" class="form-label">Price (₱)</label>
                        <input type="number" class="form-control" id="trayPrice" name="price" min="0" step="0.01">
                        <div class="form-text">
                            Leave blank to use default pricing based on your settings.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Tray Image</label>
                        <div class="dropzone" id="trayImageDropzone"></div>
                        <div class="form-text">
                            Drag & drop an image or click to select. Leave blank to use default image.
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmPublishBtn">Publish Tray</button>
            </div>
        </div>
    </div>
</div>

<script>
    const farmId = <?= $farm['farm_id'] ?? 0 ?>;
    const defaultImage = '<?= asset("images/default-egg-tray.png") ?>';
</script>

<?php
$push_css = [
    'libs/sweetalert2/sweetalert2.min.css',
    'libs/toastr/build/toastr.min.css',
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/dropzone/min/dropzone.min.css',
];

$push_js = [
    'libs/jquery/jquery.min.js',
    'libs/bootstrap/js/bootstrap.bundle.min.js',
    'libs/sweetalert2/sweetalert2.all.min.js',
    'libs/toastr/build/toastr.min.js',
    'libs/dropzone/min/dropzone.min.js',
    'js/auto-egg-tray.js'
];

// Add script to disable Dropzone auto-discovery before DOM loads
$push_head_js = [
    '<script>window.Dropzone = { autoDiscover: false };</script>'
];

$content = ob_get_clean();
include layouts('owner.main');

// Helper function to get color based on egg size
function getSizeColor($size)
{
    $colors = [
        'Jumbo' => 'danger',
        'Extra Large' => 'warning',
        'Large' => 'primary',
        'Medium' => 'success',
        'Small' => 'info',
        'Pullets' => 'secondary',
        'Pewee' => 'dark'
    ];
    return $colors[$size] ?? 'primary';
}
?>