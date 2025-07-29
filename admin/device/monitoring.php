<?php
require_once __DIR__ . '/../../config.php';

$title = 'Monitoring';
$sub_title = 'Devices';
ob_start();

$conn = db_connect();
// Handle device registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register_device'])) {
    if (!isset($_POST['token']) || !verify_token($_POST['token'])) {
        die('Invalid CSRF token');
    }
    $mac = $_POST['device_mac'];
    $farm_id = intval($_POST['farm_id']);

    $sql = 'UPDATE devices SET device_owner_id = ?, is_registered = 1 WHERE device_mac = ?';
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('is', $farm_id, $mac);

    if (!$stmt->execute()) {
        die('Registration failed: ' . $conn->error);
    }
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$farmsArray = [];
$farms = $conn->query('SELECT farm_id, farm_name FROM farms');
if ($farms) {
    $farmsArray = $farms->fetch_all(MYSQLI_ASSOC);
    $farmMap = array_column($farmsArray, 'farm_name', 'farm_id');
} else {
    die('Query failed: ' . $conn->error);
}

$devices = $conn->query("SELECT * FROM devices");
if (!$devices) {
    die("Query failed: " . $conn->error);
}

$selected_mac = isset($_GET['device_mac']) ? $_GET['device_mac'] : null;

$query = "SELECT e.*, d.device_type 
          FROM egg_data e 
          INNER JOIN devices d ON e.mac = d.device_mac";

if ($selected_mac) {
    $query .= " WHERE e.mac = '$selected_mac'";
}

$query .= " ORDER BY e.created_at DESC LIMIT 50";

$result = $conn->query($query);
if (!$result) {
    die("Query failed: " . $conn->error);
}

$first_row = null;
$last_id = 0;
if ($result->num_rows > 0) {
    $result->data_seek(0);
    $first_row = $result->fetch_assoc();
    $last_id = $first_row['id'];
    $result->data_seek(0); // Reset pointer for table
}
?>

<!-- Devices List -->
<div class="row">
    <div class="card">
        <div class="card-body">
            <div class="col">
                <table class="table table-bordered" id="devicesTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>MAC</th>
                            <th>IP</th>
                            <th>Status</th>
                            <th>Owner</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($dev = $devices->fetch_assoc()): ?>
                            <tr>
                                <td><?= special_chars($dev['id']); ?></td>
                                <td><?= special_chars($dev['device_type']); ?></td>
                                <td><?= special_chars($dev['device_mac']); ?></td>
                                <td><?= special_chars($dev['device_wifi_ip']); ?></td>
                                <td><?= $dev['is_registered'] == 1 ? '<span class="badge bg-success">Registered</span>' : '<span class="badge bg-danger">Not Registered</span>'; ?></td>
                                <td>
                                    <?php if ($dev['is_registered'] == 1 && isset($farmMap[$dev['device_owner_id']])): ?>
                                        <?= special_chars($farmMap[$dev['device_owner_id']]); ?>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <?php if ($dev['is_registered'] == 0): ?>
                                    <td><button type='button' class='btn btn-outline-success btn-sm register-btn' data-bs-toggle='modal' data-bs-target='#registerDeviceModal' data-mac='<?= special_chars($dev['device_mac']); ?>'>Register</button></td>
                                <?php else: ?>
                                    <td><a href='?device_mac=<?= urlencode($dev['device_mac']); ?>' class='btn btn-outline-primary btn-sm'>View Data</a></td>
                                <?php endif; ?>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Register Device Modal -->
<div class='modal fade' id='registerDeviceModal' tabindex='-1' aria-labelledby='registerDeviceModalLabel' aria-hidden='true'>
    <div class='modal-dialog'>
        <div class='modal-content'>
            <form method='post' action='<?= $_SERVER['PHP_SELF']; ?>'>
                <input type='hidden' name='token' value='<?= special_chars($_SESSION['token']); ?>'>
                <div class='modal-header'>
                    <h5 class='modal-title' id='registerDeviceModalLabel'>Register Device</h5>
                    <button type='button' class='btn-close' data-bs-dismiss='modal' aria-label='Close'></button>
                </div>
                <div class='modal-body'>
                    <input type='hidden' name='device_mac' id='device_mac_input' value=''>
                    <div class='mb-3'>
                        <label for='farm_id_select' class='form-label'>Select Owner (Farm)</label>
                        <select class='form-select' id='farm_id_select' name='farm_id' required>
                            <option value=''>-- Choose Farm --</option>
                            <?php foreach ($farmsArray as $farm): ?>
                                <option value='<?= $farm['farm_id']; ?>'><?= special_chars($farm['farm_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-secondary' data-bs-dismiss='modal'>Cancel</button>
                    <button type='submit' name='register_device' class='btn btn-primary'>Register Device</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Egg Data Modal -->
<?php if ($selected_mac): ?>
    <div class="modal fade" id="eggDataModal" tabindex="-1" aria-labelledby="eggDataModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eggDataModalLabel">Egg Data for <?= special_chars($selected_mac); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" data-last-id="<?= $last_id ?>">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>MAC</th>
                                    <th>Size</th>
                                    <th>Egg Weight</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody id="eggDataBody">
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= special_chars($row['created_at']) ?></td>
                                        <td><?= special_chars($row['mac']) ?></td>
                                        <td><?= special_chars($row['size']) ?></td>
                                        <td><?= special_chars($row['egg_weight']) ?></td>
                                        <td>
                                            <?php
                                            switch ($row['validation_status']) {
                                                case 'approved':
                                                    echo '<span class="badge bg-success">Approved</span>';
                                                    break;
                                                case 'rejected':
                                                    echo '<span class="badge bg-danger">Rejected</span>';
                                                    break;
                                                case 'pending':
                                                    echo '<span class="badge bg-warning">Pending</span>';
                                                    break;
                                                default:
                                                    echo '<span class="badge bg-secondary">N/A</span>';
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    function getStatusBadge(status) {
        switch (status) {
            case 'approved':
                return '<span class="badge bg-success">Approved</span>';
            case 'rejected':
                return '<span class="badge bg-danger">Rejected</span>';
            case 'pending':
                return '<span class="badge bg-warning">Pending</span>';
            default:
                return '<span class="badge bg-secondary">N/A</span>';
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Registration modal setup
        var registerModal = document.getElementById('registerDeviceModal');
        if (registerModal) {
            registerModal.addEventListener('show.bs.modal', function(event) {
                var button = event.relatedTarget;
                var mac = button.getAttribute('data-mac');
                document.getElementById('device_mac_input').value = mac;
            });
        }

        // Initialize DataTable for devices table
        $('#devicesTable').DataTable({
            responsive: true
        });

        // Show egg data modal if exists
        <?php if ($selected_mac): ?>
            var eggModal = new bootstrap.Modal(document.getElementById('eggDataModal'));
            eggModal.show();
        <?php endif; ?>

        // Real-time updates
        var eventSource;

        function escapeHtml(unsafe) {
            return unsafe?.toString()
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;") || '';
        }

        $('#eggDataModal').on('shown.bs.modal', function() {
            var modalBody = $(this).find('.modal-body');
            var lastId = modalBody.data('lastId') || 0;
            var deviceMac = '<?= $selected_mac ?>';

            if (eventSource) eventSource.close();

            eventSource = new EventSource('../api/egg-data.php?device_mac=' +
                encodeURIComponent(deviceMac) + '&last_id=' + lastId + '&token=' + '<?= $_SESSION['token'] ?>');

            eventSource.addEventListener('newData', function(e) {
                var data = JSON.parse(e.data);
                modalBody.data('lastId', data.last_id);

                // Add new rows in descending order
                var rows = data.rows;
                for (var i = rows.length - 1; i >= 0; i--) {
                    var row = rows[i];
                    var newRow = '<tr>' +
                        '<td>' + escapeHtml(row.created_at) + '</td>' +
                        '<td>' + escapeHtml(row.mac) + '</td>' +
                        '<td>' + escapeHtml(row.size) + '</td>' +
                        '<td>' + escapeHtml(row.egg_weight) + '</td>' +
                        '<td>' + getStatusBadge(row.validation_status) + '</td>' +
                        '</tr>';
                    $('#eggDataBody').prepend(newRow);
                }
            });
        });

        $('#eggDataModal').on('hidden.bs.modal', function() {
            if (eventSource) {
                eventSource.close();
                eventSource = null;
            }
        });
    });
</script>

<?php
$conn->close();

$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
];

$content = ob_get_clean();
include layouts('admin.main');
