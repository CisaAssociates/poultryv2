<?php
require_once __DIR__ . '/../config.php';

$owner_id = $_SESSION['id'];
$title = 'Egg Weighing & Sorting';
$sub_title = 'Automated Egg Data Monitoring';

// Default date range (last 7 days)
$endDate = date('Y-m-d 23:59:59');
$startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
$dateRange = 'last7days';
$selectedDevices = [];
$selectedSizes = [];

// Handle filter submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['filter'])) {
    $dateRange = $_POST['date_range'] ?? 'last7days';
    $selectedDevices = $_POST['devices'] ?? [];
    $selectedSizes = $_POST['sizes'] ?? [];
    
    switch ($dateRange) {
        case 'today':
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'last30days':
            $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'custom':
            $startDate = $_POST['start_date'] . ' 00:00:00';
            $endDate = $_POST['end_date'] . ' 23:59:59';
            break;
        case 'last7days':
        default:
            $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $endDate = date('Y-m-d 23:59:59');
            break;
    }
}

ob_start();

// Get devices and sizes
$conn = db_connect();
$devices = [];
$sizes = ['Small', 'Medium', 'Large', 'Extra Large', 'Jumbo', 'Pewee', 'Pullets'];

// Get farm_id from session
$farm_id = $_SESSION['selected_farm_id'] ?? null;
if (!$farm_id) {
    // Show error message if no farm is selected
    echo '<div class="alert alert-danger">No farm selected. Please select a farm first.</div>';
    $content = ob_get_clean();
    require_once layouts('owner.main');
    exit;
}

// Get all devices for current farm
$deviceStmt = $conn->prepare(
    "SELECT device_mac, device_serial_no 
     FROM devices 
     WHERE device_owner_id = ?"
);
$deviceStmt->bind_param('i', $farm_id);
$deviceStmt->execute();
$deviceResult = $deviceStmt->get_result();
while ($row = $deviceResult->fetch_assoc()) {
    $devices[$row['device_mac']] = $row['device_serial_no'];
}

// Check if farm has any devices
if (empty($devices)) {
    // Redirect to no devices error page
    header('Location: ' . view('owner.error.no-devices'));
    exit;
}

// Get egg size distribution data
$sizeData = [];
$sizeWhereClause = "";
$sizeParams = [$farm_id, $startDate, $endDate];
$sizeTypes = "iss";

if (!empty($selectedDevices)) {
    $placeholders = implode(',', array_fill(0, count($selectedDevices), '?'));
    $sizeWhereClause .= " AND e.mac IN ($placeholders)";
    $sizeTypes .= str_repeat('s', count($selectedDevices));
    $sizeParams = array_merge($sizeParams, $selectedDevices);
}

if (!empty($selectedSizes)) {
    $placeholders = implode(',', array_fill(0, count($selectedSizes), '?'));
    $sizeWhereClause .= " AND e.size IN ($placeholders)";
    $sizeTypes .= str_repeat('s', count($selectedSizes));
    $sizeParams = array_merge($sizeParams, $selectedSizes);
}

$sizeStmt = $conn->prepare(
    "SELECT size, COUNT(*) AS count, AVG(egg_weight) AS avg_weight 
     FROM egg_data e
     WHERE mac IN (
         SELECT device_mac FROM devices 
         WHERE device_owner_id = ?
     )
     AND created_at BETWEEN ? AND ?
     $sizeWhereClause
     GROUP BY size"
);
$sizeStmt->bind_param($sizeTypes, ...$sizeParams);
$sizeStmt->execute();
$sizeResult = $sizeStmt->get_result();

while ($row = $sizeResult->fetch_assoc()) {
    $sizeData[$row['size']] = $row;
}

// Fill missing sizes with zero values
foreach ($sizes as $size) {
    if (!isset($sizeData[$size])) {
        $sizeData[$size] = [
            'size' => $size,
            'count' => 0,
            'avg_weight' => 0
        ];
    }
}

// Get weight distribution data
$weightRanges = [
    ['min' => 0, 'max' => 49.99, 'label' => '<50g'],
    ['min' => 50, 'max' => 54.99, 'label' => '50-55g'],
    ['min' => 55, 'max' => 59.99, 'label' => '55-60g'],
    ['min' => 60, 'max' => 64.99, 'label' => '60-65g'],
    ['min' => 65, 'max' => 69.99, 'label' => '65-70g'],
    ['min' => 70, 'max' => 100, 'label' => '>70g']
];

$weightData = [];
$weightWhereClause = "";
$weightParams = [$farm_id, $startDate, $endDate];
$weightTypes = "iss";

if (!empty($selectedDevices)) {
    $placeholders = implode(',', array_fill(0, count($selectedDevices), '?'));
    $weightWhereClause .= " AND e.mac IN ($placeholders)";
    $weightTypes .= str_repeat('s', count($selectedDevices));
    $weightParams = array_merge($weightParams, $selectedDevices);
}

if (!empty($selectedSizes)) {
    $placeholders = implode(',', array_fill(0, count($selectedSizes), '?'));
    $weightWhereClause .= " AND e.size IN ($placeholders)";
    $weightTypes .= str_repeat('s', count($selectedSizes));
    $weightParams = array_merge($weightParams, $selectedSizes);
}

$weightStmt = $conn->prepare(
    "SELECT 
        CASE
            WHEN egg_weight < 50 THEN '<50g'
            WHEN egg_weight BETWEEN 50 AND 54.99 THEN '50-55g'
            WHEN egg_weight BETWEEN 55 AND 59.99 THEN '55-60g'
            WHEN egg_weight BETWEEN 60 AND 64.99 THEN '60-65g'
            WHEN egg_weight BETWEEN 65 AND 69.99 THEN '65-70g'
            ELSE '>70g'
        END AS weight_range,
        COUNT(*) AS count
     FROM egg_data e
     WHERE mac IN (
         SELECT device_mac FROM devices 
         WHERE device_owner_id = ?
     )
     AND created_at BETWEEN ? AND ?
     $weightWhereClause
     GROUP BY weight_range"
);
$weightStmt->bind_param($weightTypes, ...$weightParams);
$weightStmt->execute();
$weightResult = $weightStmt->get_result();

while ($row = $weightResult->fetch_assoc()) {
    $weightData[$row['weight_range']] = $row['count'];
}

// Fill missing ranges with zero values
foreach ($weightRanges as $range) {
    if (!isset($weightData[$range['label']])) {
        $weightData[$range['label']] = 0;
    }
}

// Get recent measurements
$recentWhereClause = "";
$recentParams = [$farm_id, $startDate, $endDate];
$recentTypes = "iss";

if (!empty($selectedDevices)) {
    $placeholders = implode(',', array_fill(0, count($selectedDevices), '?'));
    $recentWhereClause .= " AND e.mac IN ($placeholders)";
    $recentTypes .= str_repeat('s', count($selectedDevices));
    $recentParams = array_merge($recentParams, $selectedDevices);
}

if (!empty($selectedSizes)) {
    $placeholders = implode(',', array_fill(0, count($selectedSizes), '?'));
    $recentWhereClause .= " AND e.size IN ($placeholders)";
    $recentTypes .= str_repeat('s', count($selectedSizes));
    $recentParams = array_merge($recentParams, $selectedSizes);
}

$recentStmt = $conn->prepare(
    "SELECT e.created_at, e.size, e.egg_weight, d.device_serial_no
     FROM egg_data e
     JOIN devices d ON e.mac = d.device_mac
     WHERE d.device_owner_id = ?
     AND e.created_at BETWEEN ? AND ?
     $recentWhereClause
     ORDER BY e.created_at DESC
     LIMIT 10"
);
$recentStmt->bind_param($recentTypes, ...$recentParams);
$recentStmt->execute();
$recentResult = $recentStmt->get_result();
$recentMeasurements = $recentResult->fetch_all(MYSQLI_ASSOC);

$conn->close();

// Prepare data for ApexCharts
$sizeChartData = [];
$weightChartData = [];

foreach ($sizes as $size) {
    $sizeChartData[] = $sizeData[$size]['count'];
}

foreach ($weightRanges as $range) {
    $weightChartData[] = [
        'x' => $range['label'],
        'y' => $weightData[$range['label']]
    ];
}
?>

<div class="card shadow-sm mb-4">
    <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
        <h6 class="m-0 fw-bold text-primary">Egg Distribution by Size</h6>
        <button class="btn btn-outline-primary" type="button" data-bs-toggle="collapse" data-bs-target="#filterCollapse">
            <i class="fas fa-filter me-1"></i> Filter Options
        </button>
    </div>
    <div class="collapse show" id="filterCollapse">
        <div class="card-body border-bottom">
            <form method="POST" class="row g-3">
                <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                
                <div class="col-md-3">
                    <label class="form-label">Date Range</label>
                    <select class="form-select" name="date_range">
                        <option value="today" <?= $dateRange === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="last7days" <?= $dateRange === 'last7days' ? 'selected' : '' ?>>Last 7 Days</option>
                        <option value="last30days" <?= $dateRange === 'last30days' ? 'selected' : '' ?>>Last 30 Days</option>
                        <option value="custom" <?= $dateRange === 'custom' ? 'selected' : '' ?>>Custom Range</option>
                    </select>
                </div>
                
                <div class="col-md-3 custom-range" style="display:<?= $dateRange === 'custom' ? 'block' : 'none' ?>;">
                    <label class="form-label">Start Date</label>
                    <input type="date" class="form-control" name="start_date" value="<?= !empty($startDate) ? date('Y-m-d', strtotime($startDate)) : date('Y-m-d') ?>">
                </div>
                
                <div class="col-md-3 custom-range" style="display:<?= $dateRange === 'custom' ? 'block' : 'none' ?>;">
                    <label class="form-label">End Date</label>
                    <input type="date" class="form-control" name="end_date" value="<?= !empty($endDate) ? date('Y-m-d', strtotime($endDate)) : date('Y-m-d') ?>">
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Devices</label>
                    <select name="devices[]" class="form-select" multiple size="1">
                        <option value="">All Devices</option>
                        <?php foreach ($devices as $mac => $serial): ?>
                            <option value="<?= $mac ?>" <?= in_array($mac, $selectedDevices) ? 'selected' : '' ?>>
                                <?= special_chars($serial) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label class="form-label">Sizes</label>
                    <select name="sizes[]" class="form-select" multiple size="1">
                        <option value="">All Sizes</option>
                        <?php foreach ($sizes as $size): ?>
                            <option value="<?= $size ?>" <?= in_array($size, $selectedSizes) ? 'selected' : '' ?>>
                                <?= special_chars($size) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" name="filter" class="btn btn-primary w-100">
                        <i class="fas fa-sync me-2"></i> Apply Filters
                    </button>
                </div>
                
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="resetFilters()">
                        <i class="fas fa-times me-2"></i> Reset Filters
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card-body">
        <div class="row">
            <div class="col-lg-8">
                <div id="eggSizeChart"></div>
            </div>
            <div class="col-lg-4">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Size Category</th>
                                <th class="text-end">Egg Count</th>
                                <th class="text-end">Avg Weight (g)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($sizes as $size): ?>
                            <tr>
                                <td><?= special_chars($size) ?></td>
                                <td class="text-end fw-bold"><?= number_format($sizeData[$size]['count']) ?></td>
                                <td class="text-end"><?= number_format($sizeData[$size]['avg_weight'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 bg-white">
                <h6 class="m-0 fw-bold text-primary">Weight Distribution</h6>
            </div>
            <div class="card-body">
                <div id="weightDistributionChart" class="pt-3"></div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card shadow-sm mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center bg-white">
                <h6 class="m-0 fw-bold text-primary">Recent Measurements</h6>
                <span class="badge bg-primary"><?= count($recentMeasurements) ?> records</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Time</th>
                                <th>Device</th>
                                <th>Size</th>
                                <th class="text-end">Weight (g)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentMeasurements as $measurement): ?>
                            <tr>
                                <td><?= !empty($measurement['created_at']) ? date('H:i:s', strtotime($measurement['created_at'])) : 'N/A' ?></td>
                                <td><span class="badge bg-light text-dark"><?= special_chars($measurement['device_serial_no'] ?? 'N/A') ?></span></td>
                                <td><span class="badge bg-info"><?= special_chars($measurement['size']) ?></span></td>
                                <td class="text-end fw-bold"><?= number_format($measurement['egg_weight'], 1) ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentMeasurements)): ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">
                                    <i class="fas fa-egg fa-2x mb-3"></i>
                                    <p class="mb-0">No measurements found</p>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date range toggle
    document.querySelector('[name="date_range"]').addEventListener('change', function() {
        const customRange = document.querySelectorAll('.custom-range');
        if (this.value === 'custom') {
            customRange.forEach(el => el.style.display = 'block');
        } else {
            customRange.forEach(el => el.style.display = 'none');
        }
    });
    
    // Initialize multi-selects
    $('select[multiple]').select2({
        placeholder: "Select options",
        allowClear: true,
        width: '100%'
    });
    
    // Egg Size Chart (Bar)
    var sizeOptions = {
        series: [{
            name: 'Egg Count',
            data: <?= json_encode($sizeChartData) ?>
        }],
        chart: {
            type: 'bar',
            height: 350,
            toolbar: {
                show: true
            }
        },
        colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1', '#fd7e14'],
        plotOptions: {
            bar: {
                borderRadius: 4,
                horizontal: false,
                distributed: true,
                columnWidth: '60%'
            }
        },
        dataLabels: {
            enabled: false
        },
        xaxis: {
            categories: <?= json_encode($sizes) ?>,
            labels: {
                style: {
                    fontSize: '12px'
                }
            }
        },
        yaxis: {
            title: {
                text: 'Egg Count',
                style: {
                    fontSize: '14px'
                }
            },
            tickAmount: 5,
            min: 0
        },
        grid: {
            row: {
                colors: ['#f8f9fc', 'transparent'],
                opacity: 0.5
            }
        },
        tooltip: {
            y: {
                formatter: function(val) {
                    return val + " eggs"
                }
            }
        }
    };

    var sizeChart = new ApexCharts(document.querySelector("#eggSizeChart"), sizeOptions);
    sizeChart.render();

    // Weight Distribution Chart (Donut)
    var weightOptions = {
        series: <?= json_encode(array_values($weightData)) ?>,
        chart: {
            type: 'donut',
            height: 350
        },
        colors: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#6f42c1'],
        labels: <?= json_encode(array_column($weightRanges, 'label')) ?>,
        plotOptions: {
            pie: {
                donut: {
                    size: '60%',
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
        },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts) {
                return opts.w.config.series[opts.seriesIndex] + " (" + val.toFixed(1) + "%)";
            },
            dropShadow: {
                enabled: false
            },
            style: {
                fontSize: '12px',
                fontWeight: 'normal'
            }
        },
        legend: {
            position: 'bottom',
            horizontalAlign: 'center',
            fontSize: '14px'
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    width: 300
                },
                legend: {
                    position: 'bottom'
                }
            }
        }]
    };

    var weightChart = new ApexCharts(document.querySelector("#weightDistributionChart"), weightOptions);
    weightChart.render();
});

function resetFilters() {
    $('select').val(null).trigger('change');
    $('select[name="date_range"]').val('last7days').trigger('change');
    $('.custom-range').hide();
}
</script>

<style>
.card {
    border-radius: 0.75rem;
    box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
    border: none;
    margin-bottom: 1.5rem;
    transition: transform 0.3s, box-shadow 0.3s;
}

.card-header {
    background-color: #fff;
    border-bottom: 1px solid #e3e6f0;
    padding: 1.25rem 1.5rem;
    border-radius: 0.75rem 0.75rem 0 0 !important;
    font-weight: 600;
}

.badge {
    border-radius: 0.5rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
}

.btn-outline-primary {
    border-color: #4e73df;
    color: #4e73df;
    transition: all 0.3s;
}

.btn-outline-primary:hover {
    background-color: #4e73df;
    color: white;
}

.select2-container .select2-selection--multiple {
    min-height: 44px;
    border: 1px solid #d1d3e2 !important;
    border-radius: 0.5rem !important;
    padding: 0.5rem 1rem;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice {
    background-color: #4e73df;
    border: 1px solid #4e73df;
    border-radius: 0.5rem;
    color: white;
    padding: 0 0.5rem;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove {
    color: rgba(255,255,255,0.7);
    margin-right: 5px;
}

.select2-container--default .select2-selection--multiple .select2-selection__choice__remove:hover {
    color: white;
}

/* ApexCharts tooltip styling */
.apexcharts-tooltip {
    border-radius: 0.5rem !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
    border: none !important;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .card-header h6 {
        font-size: 1rem;
    }
    
    .table th, .table td {
        padding: 0.75rem;
    }
    
    .col-md-3 {
        margin-bottom: 1rem;
    }
}
</style>

<?php
$push_js = [
    'libs/apexcharts/apexcharts.min.js',
    'libs/select2/js/select2.min.js'
];

$push_css = [
    'libs/apexcharts/apexcharts.css',
    'libs/select2/css/select2.min.css'
];

$content = ob_get_clean();
include layouts('owner.main');
?>