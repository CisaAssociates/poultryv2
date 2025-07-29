<?php
require_once __DIR__ . '/../config.php';

$title = 'Inventory Management';
$sub_title = 'Daily Egg Collection & Stock Movement';

// Get current employee's farm ID
$farm_id = $user['farm_id'];

ob_start();
?>

<div class="container-fluid">
    <!-- Dashboard Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white shadow">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-egg fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white" id="total-eggs">0</h5>
                            <p class="small mb-0 text-white">Total Eggs</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white shadow">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-boxes fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white" id="published-trays">0</h5>
                            <p class="small mb-0 text-white">Published Trays</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark shadow">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fa fa-shopping-cart fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white" id="sold-trays">0</h5>
                            <p class="small mb-0 text-white">Sold This Week</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white shadow">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <div>
                            <h5 class="mb-0 text-white" id="expiring-trays">0</h5>
                            <p class="small mb-0 text-white">Expiring Soon</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Inventory Card -->
    <div class="card shadow border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
            <h5 class="mb-0">Published Trays Inventory</h5>
            <div>
                <div class="btn-group">
                    <button class="btn btn-outline-primary" id="refresh-inventory">
                        <i class="fas fa-sync-alt"></i>
                    </button>
                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addTrayModal">
                        <i class="fas fa-plus me-1"></i> Add Tray
                    </button>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="inventoryTable" class="table table-hover align-middle" style="width:100%">
                    <thead>
                        <tr>
                            <th>Tray ID</th>
                            <th>Size</th>
                            <th>Egg Count</th>
                            <th>Price</th>
                            <th>Published</th>
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
    </div>

    <!-- Charts Row -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">Inventory by Egg Size</h6>
                </div>
                <div class="card-body">
                    <div id="sizeChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow border-0">
                <div class="card-header bg-white py-3">
                    <h6 class="mb-0">Weekly Inventory Movement</h6>
                </div>
                <div class="card-body">
                    <div id="movementChart" style="height: 300px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Tray Modal -->
<div class="modal fade" id="addTrayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Tray</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTrayForm">
                <div class="modal-body">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <input type="hidden" name="farm_id" value="<?= $farm_id ?>">
                    
                    <div class="mb-3">
                        <label class="form-label">Size</label>
                        <select name="size" class="form-select" required>
                            <option value="">Select size</option>
                            <option value="Pullets">Pullets</option>
                            <option value="Pewee">Pewee</option>
                            <option value="Small">Small</option>
                            <option value="Medium">Medium</option>
                            <option value="Large">Large</option>
                            <option value="Extra Large">Extra Large</option>
                            <option value="Jumbo">Jumbo</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Stock Count</label>
                        <input type="number" name="stock_count" class="form-control" min="1" max="100" value="30" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Device MAC</label>
                        <select name="device_mac" class="form-select" required>
                            <option value="">Select device</option>
                            <?php
                            $mysqli = db_connect();
                            $query = "SELECT device_mac, device_serial_no FROM devices WHERE device_owner_id = ?";
                            $stmt = $mysqli->prepare($query);
                            $stmt->bind_param("i", $farm_id);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($device = $result->fetch_assoc()):
                            ?>
                            <option value="<?= special_chars($device['device_mac']) ?>">
                                <?= special_chars($device['device_serial_no']) ?>
                            </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Tray</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tray Actions Modal -->
<div class="modal fade" id="trayActionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tray Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-3">
                    <button class="btn btn-success action-btn" data-action="sold">
                        <i class="fas fa-cash-register me-2"></i> Mark as Sold
                    </button>
                    <button class="btn btn-warning action-btn" data-action="expired">
                        <i class="fas fa-exclamation-triangle me-2"></i> Mark as Expired
                    </button>
                    <button class="btn btn-danger action-btn" data-action="delete">
                        <i class="fas fa-trash-alt me-2"></i> Delete Tray
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    let currentTrayId = null;
    
    // Initialize DataTable
    const table = $('#inventoryTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: 'api/inventory/get-trays.php',
            type: 'POST',
            data: function(d) {
                d.farm_id = '<?= $farm_id ?>';
                d.token = '<?= special_chars($_SESSION['token']) ?>';
                d.status = 'published'; // Only show published trays
            }
        },
        columns: [
            { data: 'tray_id' },
            { 
                data: 'size',
                render: function(data) {
                    const colors = {
                        'Pullets': 'primary',
                        'Pewee': 'primary',
                        'Small': 'primary',
                        'Medium': 'info',
                        'Large': 'success',
                        'Extra Large': 'warning',
                        'Jumbo': 'danger'
                    };
                    return `<span class="badge bg-${colors[data] || 'secondary'}">${data}</span>`;
                }
            },
            { 
                data: 'stock_count',
                className: 'text-center fw-bold',
                render: function(data) {
                    return `${data} tray(s)`;
                }
            },
            {   
                data: 'price',
                render: function(data) {
                    return `₱${parseFloat(data).toFixed(2)}`;
                },
                className: 'text-end'
            },
            { 
                data: 'published_at',
                render: function(data) {
                    return new Date(data).toLocaleDateString();
                }
            },
            { 
                data: 'status',
                render: function(data) {
                    const statusMap = {
                        'pending': ['warning', 'Pending'],
                        'published': ['success', 'Published'],
                        'sold': ['info', 'Sold'],
                        'expired': ['danger', 'Expired']
                    };
                    const [color, text] = statusMap[data] || ['secondary', data];
                    return `<span class="badge bg-${color}">${text}</span>`;
                }
            },
            { 
                data: null,
                render: function(data, type, row) {
                    return `
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-outline-primary view-tray" data-id="${row.tray_id}">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-info actions-btn" data-id="${row.tray_id}">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                    `;
                },
                orderable: false,
                className: 'text-center'
            }
        ],
        order: [[0, 'desc']],
        responsive: true,
        pageLength: 10,
        lengthMenu: [[5, 10, 25, 50], [5, 10, 25, 50]],
        language: {
            emptyTable: "No published trays found",
            processing: '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>'
        },
        drawCallback: function(settings) {
            // Re-attach event handlers after table redraw
            $('.actions-btn').off('click').on('click', function() {
                currentTrayId = $(this).data('id');
                $('#trayActionsModal').modal('show');
            });
            
            $('.view-tray').off('click').on('click', function() {
                const trayId = $(this).data('id');
                window.location.href = `<?= view('employee.tray-details') ?>?id=${trayId}`;
            });
        }
    });
    
    // Load dashboard stats
    function loadDashboardStats() {
        $.ajax({
            url: 'api/inventory/get-stats.php',
            type: 'POST',
            data: {
                token: '<?= special_chars($_SESSION['token']) ?>',
                farm_id: '<?= $farm_id ?>'
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    $('#total-eggs').text(data.total_eggs.toLocaleString());
                    $('#published-trays').text(data.published_trays.toLocaleString());
                    $('#sold-trays').text(data.sold_this_week.toLocaleString());
                    $('#expiring-trays').text(data.expiring_soon.toLocaleString());
                    
                    // Update size chart
                    updateSizeChart(data.size_distribution);
                    
                    // Update movement chart
                    updateMovementChart(data.weekly_movement);
                }
            }
        });
    }
    
    // Update size distribution chart
    function updateSizeChart(sizeData) {
        const options = {
            series: Object.values(sizeData),
            chart: {
                type: 'donut',
                height: 300
            },
            labels: Object.keys(sizeData),
            colors: ['#4361ee', '#3a0ca3', '#4cc9f0', '#f72585', '#7209b7'],
            responsive: [{
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200
                    },
                    legend: {
                        position: 'bottom'
                    }
                }
            }],
            plotOptions: {
                pie: {
                    donut: {
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total Eggs',
                                formatter: function(w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0)
                                }
                            }
                        }
                    }
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#sizeChart"), options);
        chart.render();
    }
    
    // Update weekly movement chart
    function updateMovementChart(movementData) {
        const options = {
            series: [{
                name: 'Published',
                data: movementData.published
            }, {
                name: 'Sold',
                data: movementData.sold
            }],
            chart: {
                height: 300,
                type: 'area',
                toolbar: {
                    show: false
                }
            },
            colors: ['#4361ee', '#4cc9f0'],
            dataLabels: {
                enabled: false
            },
            stroke: {
                curve: 'smooth'
            },
            xaxis: {
                type: 'category',
                categories: movementData.dates
            },
            tooltip: {
                x: {
                    format: 'dd MMM'
                }
            }
        };

        const chart = new ApexCharts(document.querySelector("#movementChart"), options);
        chart.render();
    }
    
    // Tray actions handler
    $('.action-btn').on('click', function() {
        const action = $(this).data('action');
        const trayId = currentTrayId;
        
        if (!trayId) return;
        
        $.ajax({
            url: 'api/inventory/update-tray.php',
            type: 'POST',
            data: {
                token: '<?= special_chars($_SESSION['token']) ?>',
                tray_id: trayId,
                action: action
            },
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showToast(`Tray ${action === 'delete' ? 'deleted' : 'updated'} successfully`, 'success');
                    table.ajax.reload();
                    loadDashboardStats();
                    $('#trayActionsModal').modal('hide');
                } else {
                    showToast(data.message || 'Operation failed', 'danger');
                }
            },
            error: function() {
                showToast('Network error occurred', 'danger');
            }
        });
    });
    
    // Add new tray form
    $('#addTrayForm').on('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        $.ajax({
            url: 'api/inventory/add-tray.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(data) {
                if (data.success) {
                    showToast('Tray added successfully', 'success');
                    table.ajax.reload();
                    loadDashboardStats();
                    $('#addTrayModal').modal('hide');
                    $('#addTrayForm')[0].reset();
                } else {
                    showToast(data.message || 'Failed to add tray', 'danger');
                }
            },
            error: function() {
                showToast('Network error occurred', 'danger');
            }
        });
    });
    
    // Refresh inventory
    $('#refresh-inventory').on('click', function() {
        table.ajax.reload(null, false);
        loadDashboardStats();
        showToast('Inventory refreshed', 'info');
    });
    
    // Initial load
    loadDashboardStats();
    
    // Toast notification function
    function showToast(message, type) {
        const toast = $(`
            <div class="toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        `);
        
        $('body').append(toast);
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
.card {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
}

.card-header {
    border-bottom: 1px solid rgba(0, 0, 0, 0.05);
}

.table thead th {
    font-weight: 600;
    background-color: #f8f9fa;
}

.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.badge {
    font-weight: 500;
    letter-spacing: 0.5px;
}

#refresh-inventory {
    border-top-right-radius: 0;
    border-bottom-right-radius: 0;
}

.action-btn {
    text-align: left;
    padding: 0.75rem 1rem;
    border-radius: 8px;
    transition: all 0.2s;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}
</style>

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
    'libs/apexcharts/apexcharts.css'
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
    'libs/apexcharts/apexcharts.min.js'
];

$content = ob_get_clean();
include layouts('employee.main');
?>