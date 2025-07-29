<?php
require_once __DIR__ . '/../config.php';

$title = 'Support Tickets';
$sub_title = 'Manage Consumer Support Requests';
ob_start();

$mysqli = db_connect();

// Handle ticket status updates and responses
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ticket'])) {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid token';
    } else {
        $ticket_id = (int)$_POST['ticket_id'];
        $status = $_POST['status'];
        $admin_response = trim($_POST['admin_response']);

        // Validate status
        $valid_statuses = ['open', 'in_progress', 'resolved', 'closed'];
        if (!in_array($status, $valid_statuses)) {
            $_SESSION['error'] = 'Invalid status';
        } else {
            // Update ticket
            $stmt = $mysqli->prepare("UPDATE consumer_support_tickets SET status = ?, admin_response = ?, updated_at = NOW() WHERE ticket_id = ?");
            $stmt->bind_param("ssi", $status, $admin_response, $ticket_id);

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Ticket has been updated successfully';
                // Redirect to avoid form resubmission
                header('Location: ' . view('admin.support-tickets'));
                exit;
            } else {
                $_SESSION['error'] = 'Failed to update ticket. Please try again.';
            }
        }
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) ? $_GET['category'] : '';

// Build query based on filters
$query = "SELECT t.*, u.fullname, u.email FROM consumer_support_tickets t 
          JOIN users u ON t.user_id = u.id";
$where_clauses = [];
$params = [];
$param_types = "";

if (!empty($status_filter)) {
    $where_clauses[] = "t.status = ?";
    $params[] = $status_filter;
    $param_types .= "s";
}

if (!empty($category_filter)) {
    $where_clauses[] = "t.category = ?";
    $params[] = $category_filter;
    $param_types .= "s";
}

if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

$query .= " ORDER BY t.created_at DESC";

// Prepare and execute the query
$stmt = $mysqli->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$tickets_result = $stmt->get_result();
$tickets = $tickets_result->fetch_all(MYSQLI_ASSOC);

// Get unique categories for filter dropdown
$categories_result = $mysqli->query("SELECT DISTINCT category FROM consumer_support_tickets ORDER BY category");
$categories = $categories_result->fetch_all(MYSQLI_ASSOC);
?>

<!-- Display success/error messages -->
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['success']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['error']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Filter Controls -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label for="status" class="form-label">Filter by Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="open" <?= $status_filter === 'open' ? 'selected' : '' ?>>Open</option>
                    <option value="in_progress" <?= $status_filter === 'in_progress' ? 'selected' : '' ?>>In Progress</option>
                    <option value="resolved" <?= $status_filter === 'resolved' ? 'selected' : '' ?>>Resolved</option>
                    <option value="closed" <?= $status_filter === 'closed' ? 'selected' : '' ?>>Closed</option>
                </select>
            </div>

            <div class="col-md-4">
                <label for="category" class="form-label">Filter by Category</label>
                <select class="form-select" id="category" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= htmlspecialchars($cat['category']) ?>" <?= $category_filter === $cat['category'] ? 'selected' : '' ?>>
                            <?= ucfirst(htmlspecialchars($cat['category'])) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="mdi mdi-filter-outline me-1"></i> Apply Filters
                </button>
                <a href="<?= view('admin.support-tickets') ?>" class="btn btn-secondary">
                    <i class="mdi mdi-refresh me-1"></i> Reset
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tickets Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-centered table-striped dt-responsive nowrap w-100" id="tickets-datatable">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Subject</th>
                        <th>Customer</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Last Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($tickets) > 0): ?>
                        <?php foreach ($tickets as $ticket): ?>
                            <tr>
                                <td>#<?= $ticket['ticket_id'] ?></td>
                                <td>
                                    <a href="#" class="text-body fw-bold view-ticket" data-bs-toggle="modal" data-bs-target="#ticketModal"
                                        data-ticket-id="<?= $ticket['ticket_id'] ?>"
                                        data-subject="<?= htmlspecialchars($ticket['subject']) ?>"
                                        data-message="<?= htmlspecialchars($ticket['message']) ?>"
                                        data-category="<?= htmlspecialchars($ticket['category']) ?>"
                                        data-status="<?= htmlspecialchars($ticket['status']) ?>"
                                        data-created="<?= !empty($ticket['created_at']) ? date('M d, Y H:i', strtotime($ticket['created_at'])) : 'N/A' ?>"
                                        data-customer-name="<?= htmlspecialchars($ticket['fullname']) ?>"
                                        data-customer-email="<?= htmlspecialchars($ticket['email']) ?>"
                                        data-admin-response="<?= htmlspecialchars($ticket['admin_response'] ?? '') ?>">
                                        <?= htmlspecialchars($ticket['subject']) ?>
                                    </a>
                                </td>
                                <td>
                                    <div><?= htmlspecialchars($ticket['fullname']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($ticket['email']) ?></small>
                                </td>
                                <td><span class="badge bg-info"><?= ucfirst(htmlspecialchars($ticket['category'])) ?></span></td>
                                <td>
                                    <span class="badge bg-<?= $ticket['status'] === 'open' ? 'danger' : ($ticket['status'] === 'in_progress' ? 'warning' : ($ticket['status'] === 'resolved' ? 'success' : 'secondary')) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $ticket['status'])) ?>
                                    </span>
                                </td>
                                <td><?= !empty($ticket['created_at']) ? date('M d, Y', strtotime($ticket['created_at'])) : 'N/A' ?></td>
                                <td><?= $ticket['updated_at'] ? date('M d, Y', strtotime($ticket['updated_at'])) : 'N/A' ?></td>
                                <td>
                                    <button type="button" class="btn btn-sm btn-primary view-ticket" data-bs-toggle="modal" data-bs-target="#ticketModal"
                                        data-ticket-id="<?= $ticket['ticket_id'] ?>"
                                        data-subject="<?= htmlspecialchars($ticket['subject']) ?>"
                                        data-message="<?= htmlspecialchars($ticket['message']) ?>"
                                        data-category="<?= htmlspecialchars($ticket['category']) ?>"
                                        data-status="<?= htmlspecialchars($ticket['status']) ?>"
                                        data-created="<?= !empty($ticket['created_at']) ? date('M d, Y H:i', strtotime($ticket['created_at'])) : 'N/A' ?>"
                                        data-customer-name="<?= htmlspecialchars($ticket['fullname']) ?>"
                                        data-customer-email="<?= htmlspecialchars($ticket['email']) ?>"
                                        data-admin-response="<?= htmlspecialchars($ticket['admin_response'] ?? '') ?>">
                                        <i class="mdi mdi-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No support tickets found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Ticket Modal -->
<div class="modal fade" id="ticketModal" tabindex="-1" aria-labelledby="ticketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ticketModalLabel">Support Ticket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="ticket-details mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 id="ticket-subject"></h5>
                            <p class="mb-1"><strong>Category:</strong> <span id="ticket-category"></span></p>
                            <p class="mb-1"><strong>Status:</strong> <span id="ticket-status"></span></p>
                            <p class="mb-0"><strong>Created:</strong> <span id="ticket-created"></span></p>
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
                        <h5 class="mb-0">Customer Message</h5>
                    </div>
                    <div class="card-body">
                        <p id="ticket-message"></p>
                    </div>
                </div>

                <form action="" method="POST">
                    <?= csrf_token() ?>
                    <input type="hidden" name="ticket_id" id="modal-ticket-id">

                    <div class="mb-3">
                        <label for="status" class="form-label">Update Status</label>
                        <select class="form-select" id="modal-status" name="status" required>
                            <option value="open">Open</option>
                            <option value="in_progress">In Progress</option>
                            <option value="resolved">Resolved</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="admin_response" class="form-label">Response to Customer</label>
                        <textarea class="form-control" id="modal-admin-response" name="admin_response" rows="5" placeholder="Enter your response to the customer..."></textarea>
                    </div>

                    <div class="text-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" name="update_ticket" class="btn btn-primary">Update Ticket</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Check if there are any tickets before initializing DataTable
        if ($('#tickets-datatable tbody tr').length > 0 && !$('#tickets-datatable tbody tr td[colspan]').length) {
            // Initialize DataTable only if there are actual data rows
            $('#tickets-datatable').DataTable({
                responsive: true,
                order: [
                    [5, 'desc']
                ],
                columnDefs: [
                    { targets: [7], orderable: false }
                ],
                language: {
                    paginate: {
                        previous: "<i class='mdi mdi-chevron-left'>",
                        next: "<i class='mdi mdi-chevron-right'>"
                    }
                },
                drawCallback: function() {
                    $('.dataTables_paginate > .pagination').addClass('pagination-rounded');
                }
            });
        }

        // Handle ticket modal data
        $('.view-ticket').on('click', function() {
            const ticketId = $(this).data('ticket-id');
            const subject = $(this).data('subject');
            const message = $(this).data('message');
            const category = $(this).data('category');
            const status = $(this).data('status');
            const created = $(this).data('created');
            const customerName = $(this).data('customer-name');
            const customerEmail = $(this).data('customer-email');
            const adminResponse = $(this).data('admin-response');

            // Set modal values
            $('#modal-ticket-id').val(ticketId);
            $('#ticket-subject').text(subject);
            $('#ticket-category').text(category.charAt(0).toUpperCase() + category.slice(1));
            $('#ticket-status').text(status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase()));
            $('#ticket-created').text(created);
            $('#customer-name').text(customerName);
            $('#customer-email').text(customerEmail);
            $('#ticket-message').text(message);
            $('#modal-status').val(status);
            $('#modal-admin-response').val(adminResponse);
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
include layouts('admin.main');
?>