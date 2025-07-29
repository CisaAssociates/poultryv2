<?php
require_once __DIR__ . '/../config.php';

$title = 'Egg Collection & Sorting';
$sub_title = 'Monitor and validate automated egg weighing results';

// Get current employee's farm ID
$farm_id = $user['farm_id'];

$status_filter = isset($_GET['status']) ? $_GET['status'] : 'pending';
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status_filter, $allowed_statuses)) {
    $status_filter = 'pending';
}

ob_start();
?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Egg Validation</h3>
                    <div class="d-flex align-items-center gap-2">
                        <div class="btn-group">
                            <a href="?status=pending" class="btn btn-sm <?= $status_filter === 'pending' ? 'btn-warning' : 'btn-outline-warning' ?>">Pending</a>
                            <a href="?status=approved" class="btn btn-sm <?= $status_filter === 'approved' ? 'btn-success' : 'btn-outline-success' ?>">Approved</a>
                            <a href="?status=rejected" class="btn btn-sm <?= $status_filter === 'rejected' ? 'btn-danger' : 'btn-outline-danger' ?>">Rejected</a>
                        </div>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="liveUpdateToggle" checked>
                            <label class="form-check-label" for="liveUpdateToggle">Live Updates</label>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="eggTable" class="table table-striped table-hover w-100">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Device</th>
                                    <th>Size</th>
                                    <th>Weight (g)</th>
                                    <th>Created At</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Data will be populated by DataTables -->
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex justify-content-end">
                    <button id="batch-approve" class="btn btn-success">
                        <i class="fas fa-check-double me-1"></i> Approve All Pending
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize DataTable
        const table = $('#eggTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/egg-collection/get-eggs.php',
                type: 'POST',
                data: function(d) {
                    d.status = '<?= $status_filter ?>';
                    d.farm_id = '<?= $farm_id ?>';
                    d.token = '<?= special_chars($_SESSION['token']) ?>';
                }
            },
            columns: [
                { data: 'id' },
                { data: 'device_serial_no' },
                { data: 'size' },
                { data: 'egg_weight', className: 'text-end' },
                { 
                    data: 'created_at',
                    render: function(data) {
                        const date = new Date(data);
                        return date.toLocaleString('en-US', {
                            month: 'short',
                            day: 'numeric',
                            year: 'numeric',
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        });
                    }
                },
                { 
                    data: 'validation_status',
                    render: function(data) {
                        const badgeClass = data === 'approved' ? 'success' : 
                                          data === 'rejected' ? 'danger' : 'warning';
                        return `<span class="badge bg-${badgeClass}">${data.charAt(0).toUpperCase() + data.slice(1)}</span>`;
                    }
                },
                { 
                    data: null,
                    render: function(data, type, row) {
                        if (row.validation_status === 'pending') {
                            return `
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-success approve-btn" data-id="${row.id}">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="btn btn-danger reject-btn" data-id="${row.id}">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            `;
                        } else {
                            return `<button class="btn btn-sm btn-outline-secondary" disabled>Validated</button>`;
                        }
                    },
                    orderable: false
                }
            ],
            order: [[0, 'desc']],
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            language: {
                emptyTable: "No eggs found for validation",
            },
            drawCallback: function(settings) {
                // Re-attach event handlers after table redraw
                $('.approve-btn').off('click').on('click', function() {
                    const eggId = $(this).data('id');
                    updateEggStatus(eggId, 'approved');
                });

                $('.reject-btn').off('click').on('click', function() {
                    const eggId = $(this).data('id');
                    updateEggStatus(eggId, 'rejected');
                });
            }
        });

        // Live update variables
        let liveUpdateInterval = null;
        const liveUpdateToggle = $('#liveUpdateToggle');
        
        // Toggle live updates
        function toggleLiveUpdates() {
            if (liveUpdateToggle.is(':checked')) {
                startLiveUpdates();
            } else {
                stopLiveUpdates();
            }
        }

        // Start live updates
        function startLiveUpdates() {
            if (liveUpdateInterval) return;
            
            liveUpdateInterval = setInterval(() => {
                // Only refresh if not currently processing
                if (!table.data().any() || !$.fn.dataTable.isDataTable('#eggTable')) return;
                
                table.ajax.reload(null, false); // Don't reset paging
                
                // Show update indicator
                const indicator = $('#update-indicator');
                if (indicator.length === 0) {
                    $('.card-title').append(' <span id="update-indicator" class="badge bg-info blink">Live</span>');
                }
            }, 5000); // Refresh every 5 seconds
        }

        // Stop live updates
        function stopLiveUpdates() {
            if (liveUpdateInterval) {
                clearInterval(liveUpdateInterval);
                liveUpdateInterval = null;
            }
            $('#update-indicator').remove();
        }

        // Initialize live updates
        liveUpdateToggle.on('change', toggleLiveUpdates);
        toggleLiveUpdates();

        // Update egg status function
        function updateEggStatus(eggId, status) {
            const formData = new FormData();
            formData.append('token', '<?= special_chars($_SESSION['token']) ?>');
            formData.append('egg_id', eggId);
            formData.append('status', status);

            $.ajax({
                url: 'api/egg-collection/update-egg-status.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    if (data.success) {
                        showToast(`Egg ${status} successfully`, 'success');
                        table.ajax.reload(null, false);
                    } else {
                        showToast(data.message || 'Operation failed', 'danger');
                    }
                },
                error: function() {
                    showToast('Network error occurred', 'danger');
                }
            });
        }

        // Batch approval
        $('#batch-approve').on('click', function() {
            if (confirm('Are you sure you want to approve ALL pending eggs?')) {
                const formData = new FormData();
                formData.append('token', '<?= special_chars($_SESSION['token']) ?>');
                formData.append('egg_id', 'all');
                formData.append('status', 'approved');
                
                $.ajax({
                    url: 'api/egg-collection/update-egg-status.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function(data) {
                        if (data.success) {
                            showToast('All pending eggs approved successfully', 'success');
                            table.ajax.reload();
                        } else {
                            showToast(data.message || 'Batch approval failed', 'danger');
                        }
                    },
                    error: function() {
                        showToast('Network error occurred', 'danger');
                    }
                });
            }
        });

        // Toast notification function
        function showToast(message, type) {
            const toastContainer = $('#toast-container');
            if (toastContainer.length === 0) {
                $('body').append('<div id="toast-container" class="toast-container position-fixed bottom-0 end-0 p-3"></div>');
            }
            
            const toastId = 'toast-' + Date.now();
            const toast = $(`
                <div id="${toastId}" class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body">${message}</div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `);
            
            $('#toast-container').append(toast);
            const bsToast = new bootstrap.Toast(toast[0]);
            bsToast.show();
            
            // Auto remove after 5 seconds
            setTimeout(() => {
                bsToast.hide();
                setTimeout(() => toast.remove(), 500);
            }, 5000);
        }
    });
</script>

<style>
    .blink {
        animation: blink-animation 1.5s steps(2, start) infinite;
    }
    
    @keyframes blink-animation {
        to { visibility: hidden; }
    }
</style>

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
    'libs/datatables.net-buttons-bs5/css/buttons.bootstrap5.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
    'libs/datatables.net-buttons/js/dataTables.buttons.min.js',
    'libs/datatables.net-buttons-bs5/js/buttons.bootstrap5.min.js',
];
$content = ob_get_clean();
include layouts('employee.main');
?>