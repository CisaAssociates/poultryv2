<?php
require_once __DIR__ . '/config.php';

if (!is_logged_in() || !in_array($user['role_id'], [2, 3])) {
    header('Location: ' . view('auth.error.403'));
    exit;
}

$title = 'Manage Orders';
$sub_title = 'View and Process Customer Orders';

// Determine layout based on role
$layout = ($user['role_id'] == 2) ? 'owner.main' : 'employee.main';

// Get farm IDs accessible by user
$farm_ids = [];
if ($user['role_id'] == 2) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("SELECT farm_id FROM farms WHERE owner_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $farm_ids[] = $row['farm_id'];
    }
} else {
    $farm_ids[] = $user['farm_id'];
}

// Get orders
$orders = [];
if (!empty($farm_ids)) {
    $farm_ids_str = implode(',', $farm_ids);
    $query = "
        SELECT o.order_id, o.order_date, o.total_amount, o.status, 
               a.recipient_name, a.contact_number, 
               GROUP_CONCAT(DISTINCT f.farm_name ORDER BY f.farm_name SEPARATOR ', ') AS farm_names
        FROM consumer_orders o
        INNER JOIN consumer_order_items i ON o.order_id = i.order_id
        INNER JOIN trays t ON i.tray_id = t.tray_id
        INNER JOIN farms f ON t.farm_id = f.farm_id
        INNER JOIN consumer_addresses a ON o.address_id = a.address_id
        WHERE f.farm_id IN ($farm_ids_str)
        GROUP BY o.order_id
        ORDER BY o.order_date DESC
    ";
    $mysqli = db_connect();
    $result = $mysqli->query($query);
    $orders = $result->fetch_all(MYSQLI_ASSOC);
}

ob_start();
?>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Customer Orders</h5>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-outline-primary" id="refresh-btn">
                <i class="fas fa-sync"></i> Refresh
            </button>
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                    id="filterDropdown" data-bs-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <ul class="dropdown-menu" aria-labelledby="filterDropdown">
                    <li><a class="dropdown-item filter-status" data-status="all" href="#">All Orders</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item filter-status" data-status="pending" href="#">Pending</a></li>
                    <li><a class="dropdown-item filter-status" data-status="confirmed" href="#">Confirmed</a></li>
                    <li><a class="dropdown-item filter-status" data-status="packing" href="#">Packing</a></li>
                    <li><a class="dropdown-item filter-status" data-status="shipped" href="#">Shipped</a></li>
                    <li><a class="dropdown-item filter-status" data-status="delivered" href="#">Delivered</a></li>
                    <li><a class="dropdown-item filter-status" data-status="cancelled" href="#">Cancelled</a></li>
                </ul>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table id="orders-table" class="table table-striped table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Contact</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Farms</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr data-status="<?= strtolower($order['status']) ?>">
                            <td><?= $order['order_id'] ?></td>
                            <td><?= date('M d, Y h:i A', strtotime($order['order_date'])) ?></td>
                            <td><?= special_chars($order['recipient_name']) ?></td>
                            <td><?= special_chars($order['contact_number']) ?></td>
                            <td>₱<?= number_format($order['total_amount'], 2) ?></td>
                            <td>
                                <span class="badge bg-<?=
                                                        $order['status'] == 'pending' ? 'warning' : ($order['status'] == 'confirmed' ? 'primary' : ($order['status'] == 'packing' ? 'info' : ($order['status'] == 'shipped' ? 'secondary' : ($order['status'] == 'delivered' ? 'success' : 'danger'))))
                                                        ?>">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                            <td><?= special_chars($order['farm_names']) ?></td>
                            <td>
                                <a href="<?= view('order-details') ?>?order_id=<?= $order['order_id'] ?>"
                                    class="btn btn-sm btn-primary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize DataTable
        const table = $('#orders-table').DataTable({
            responsive: true,
            columnDefs: [{
                    targets: [0, 1, 2, 3, 4, 5, 6],
                    orderable: true
                },
                {
                    targets: [7],
                    orderable: false
                }
            ]
        });

        // Refresh button
        $('#refresh-btn').click(function() {
            location.reload();
        });

        // Status filtering
        $('.filter-status').click(function(e) {
            e.preventDefault();
            const status = $(this).data('status');

            if (status === 'all') {
                table.rows().search('').draw();
            } else {
                table.rows().search(status).draw();
            }
        });
    });
</script>

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
];

$content = ob_get_clean();
include layouts($layout);
?>