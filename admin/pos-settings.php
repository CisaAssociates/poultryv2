<?php
require_once __DIR__ . '/../config.php';

$mysqli = db_connect();
$farms = [];
$initial_farm_id = 0;

if ($user['role_id'] == 1) {
    $stmt = $mysqli->prepare("SELECT farm_id, farm_name FROM farms");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $farms[] = $row;
    }
    if (!empty($farms)) {
        $initial_farm_id = $farms[0]['farm_id'];
    }
} else if ($user['role_id'] == 2) {
    $stmt = $mysqli->prepare("SELECT farm_id, farm_name FROM farms WHERE owner_id = ?");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $farms[] = $row;
    }
    $initial_farm_id = $user['farm_id'] ?? 0;
}

$title = 'POS & Inventory Settings';
$sub_title = 'Configure POS Settings';
ob_start();
?>

<div class="container-fluid">
    <!-- Farm Selection -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="form-group">
                        <label for="farmSelect" class="form-label">Farm</label>
                        <select id="farmSelect" class="form-control">
                            <?php foreach ($farms as $farm): ?>
                                <option value="<?= $farm['farm_id'] ?>"
                                    <?= ($farm['farm_id'] == ($user['farm_id'] ?? 0)) ? 'selected' : '' ?>>
                                    <?= special_chars($farm['farm_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Tax Settings</h4>
                </div>
                <div class="card-body">
                    <form id="taxForm">
                        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                        <input type="hidden" id="taxFarmId" name="farm_id" value="<?= $initial_farm_id ?>">

                        <div class="form-group mb-3">
                            <label for="taxName">Tax Name</label>
                            <input type="text" class="form-control" id="taxName" name="tax_name"
                                placeholder="e.g., VAT, Sales Tax" required>
                        </div>
                        <div class="form-group mb-3">
                            <label for="taxRate">Tax Rate (%)</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control"
                                id="taxRate" name="tax_rate" required>
                        </div>
                        <div class="form-check mb-3">
                            <input type="checkbox" class="form-check-input" id="isDefault" name="is_default">
                            <label class="form-check-label" for="isDefault">Set as default tax rate</label>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Tax Setting</button>
                    </form>

                    <div class="mt-4">
                        <h5>Active Tax Rates</h5>
                        <div id="taxList" class="list-group">
                            <!-- Tax rates will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Transaction Logs</h4>
                </div>
                <div class="card-body">
                    <table id="transactionDataTable" class="table table-striped" style="width:100%">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Date</th>
                                <th>Items</th>
                                <th>Subtotal</th>
                                <th>Tax</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- DataTables will populate this automatically -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize farm selector
        const $farmSelect = $('#farmSelect').selectize({
            create: false,
            sortField: 'text'
        });

        const selectize = $farmSelect[0].selectize;
        let currentFarmId = <?= $initial_farm_id ?>; // Use PHP value

        // Initialize DataTable for transactions
        const transactionTable = $('#transactionDataTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: 'api/get-transactions.php',
                type: 'GET',
                dataType: 'json',
                data: function(d) {
                    d.farm_id = currentFarmId;
                }
            },
            columns: [{
                    data: 'transaction_id'
                },
                {
                    data: 'created_at',
                    render: function(data) {
                        return new Date(data).toLocaleString();
                    }
                },
                {
                    data: 'item_count',
                    render: function(data) {
                        return data + ' items';
                    }
                },
                {
                    data: 'subtotal',
                    render: function(data) {
                        return '₱' + parseFloat(data).toFixed(2);
                    }
                },
                {
                    data: 'tax',
                    render: function(data) {
                        return '₱' + parseFloat(data).toFixed(2);
                    }
                },
                {
                    data: 'total',
                    render: function(data) {
                        return '₱' + parseFloat(data).toFixed(2);
                    }
                }
            ],
            responsive: true,
            lengthChange: true,
            pageLength: 10,
            searching: true,
            ordering: true,
            autoWidth: true,
            language: {
                emptyTable: "No transactions found for this farm"
            }
        });

        // Update hidden farm field when selection changes
        selectize.on('change', function() {
            currentFarmId = this.getValue();
            $('#taxFarmId').val(currentFarmId);
            loadTaxSettings(currentFarmId);

            // Reload DataTable with new farm ID
            transactionTable.ajax.reload();
        });

        // Load tax settings
        function loadTaxSettings(farmId) {
            $.get('api/get-tax-settings.php?farm_id=' + farmId, function(response) {
                $('#taxList').empty();
                if (response.data.length > 0) {
                    response.data.forEach(tax => {
                        const taxItem = `
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <h6>${tax.tax_name} ${tax.is_default ? '<span class="badge bg-primary">Default</span>' : ''}</h6>
                                <small>${tax.tax_rate}%</small>
                            </div>
                            <div>
                                <button class="btn btn-sm ${tax.is_active ? 'btn-success' : 'btn-secondary'} toggle-tax" 
                                    data-id="${tax.id}" data-active="${tax.is_active}">
                                    ${tax.is_active ? 'Active' : 'Inactive'}
                                </button>
                                <button class="btn btn-sm btn-danger delete-tax" data-id="${tax.id}">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>`;
                        $('#taxList').append(taxItem);
                    });
                } else {
                    $('#taxList').html('<div class="alert alert-info">No tax settings found for this farm</div>');
                }
            });
        }

        // Save tax setting
        $('#taxForm').submit(function(e) {
            e.preventDefault();
            const formData = $(this).serialize();

            $.post('api/save-tax-setting.php', formData, function(response) {
                if (response.success) {
                    toastr.success('Tax setting saved successfully');
                    loadTaxSettings(currentFarmId);
                    $('#taxForm')[0].reset();
                    // Reset the default checkbox
                    $('#isDefault').prop('checked', false);
                } else {
                    toastr.error(response.message || 'Error saving tax setting');
                }
            }).fail(function(xhr) {
                toastr.error('Server error: ' + xhr.status + ' ' + xhr.statusText);
            });
        });

        // Toggle tax status
        $(document).on('click', '.toggle-tax', function() {
            const taxId = $(this).data('id');
            const isActive = $(this).data('active') ? 0 : 1;

            $.post('api/toggle-tax-status.php', {
                id: taxId,
                is_active: isActive,
                token: '<?= $_SESSION['token'] ?>'
            }, function(response) {
                if (response.success) {
                    toastr.success('Tax status updated');
                    loadTaxSettings(currentFarmId);
                } else {
                    toastr.error(response.message || 'Error updating tax status');
                }
            });
        });

        // Delete tax setting
        $(document).on('click', '.delete-tax', function() {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const taxId = $(this).data('id');
                    const $listItem = $(this).closest('.list-group-item');

                    $.post('api/delete-tax-setting.php', {
                        id: taxId,
                        token: '<?= $_SESSION['token'] ?>'
                    }, function(response) {
                        if (response.success) {
                            toastr.success('Tax setting deleted');
                            // Remove from UI immediately
                            $listItem.fadeOut(300, function() {
                                $(this).remove();
                                // Show empty message if no taxes left
                                if ($('#taxList .list-group-item').length === 0) {
                                    $('#taxList').html('<div class="alert alert-info">No tax settings found for this farm</div>');
                                }
                            });
                        } else {
                            toastr.error(response.message || 'Error deleting tax setting');
                        }
                    }).fail(function(xhr) {
                        toastr.error('Server error: ' + xhr.status + ' ' + xhr.statusText);
                    });
                }
            });
        });

        // Initialize with current farm
        loadTaxSettings(currentFarmId);
        transactionTable.ajax.reload(); // Initial load of transactions
    });
</script>

<?php

$push_css = [
    'libs/selectize/css/selectize.bootstrap3.css',
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
    'libs/toastr/build/toastr.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/datatables.net-responsive/js/dataTables.responsive.min.js',
    'libs/datatables.net-responsive-bs5/js/responsive.bootstrap5.min.js',
    'libs/selectize/js/standalone/selectize.min.js',
    'libs/toastr/build/toastr.min.js',
    'libs/sweetalert2/sweetalert2.all.min.js',
];

$content = ob_get_clean();
include layouts('admin.main');
?>