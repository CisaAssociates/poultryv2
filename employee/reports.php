<?php
// File: reports.php
require_once __DIR__ . '/../config.php';

$title = 'Egg Production Reports';
$sub_title = 'Generate production logs for review';

// Get current farm ID from employee data
$farm_id = $user['farm_id'] ?? 0;

// Initialize variables
$start_date = date('Y-m-01'); // First day of current month
$end_date = date('Y-m-d'); // Today
$report_data = [];
$summary = [
    'total_eggs' => 0,
    'avg_weight' => 0,
    'min_weight' => 0,
    'max_weight' => 0,
    'size_distribution' => []
];
$has_generated = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid security token';
        header('Location: ' . view('employee.reports'));
        exit;
    }

    // Get filter parameters
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $has_generated = true;

    // Validate dates
    if (strtotime($start_date) > strtotime($end_date)) {
        $_SESSION['error'] = 'Start date cannot be after end date';
    } else {
        // Fetch report data
        $report_data = get_egg_production_data($farm_id, $start_date, $end_date);

        // Calculate summary
        if (!empty($report_data)) {
            $weights = array_column($report_data, 'egg_weight');
            $summary['total_eggs'] = count($report_data);
            $summary['avg_weight'] = round(array_sum($weights) / count($weights), 2);
            $summary['min_weight'] = min($weights);
            $summary['max_weight'] = max($weights);

            // Size distribution
            $sizes = array_count_values(array_column($report_data, 'size'));
            $summary['size_distribution'] = $sizes;
        }
    }
}

ob_start();
?>

<div class="container-fluid">
    <div class="row">
        <!-- Filter Panel -->
        <div class="col-md-4 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0"><i class="fas fa-filter mr-2"></i>Report Filters</h3>
                </div>
                <div class="card-body">
                    <form method="post" id="report-form">
                        <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">

                        <div class="form-group">
                            <label for="start-date" class="font-weight-bold">Start Date</label>
                            <input type="date" id="start-date" name="start_date"
                                class="form-control" value="<?= special_chars($start_date) ?>"
                                max="<?= special_chars(date('Y-m-d')) ?>">
                        </div>

                        <div class="form-group">
                            <label for="end-date" class="font-weight-bold">End Date</label>
                            <input type="date" id="end-date" name="end_date"
                                class="form-control" value="<?= special_chars($end_date) ?>"
                                max="<?= special_chars(date('Y-m-d')) ?>">
                        </div>

                        <div class="form-group">
                            <label class="font-weight-bold">Data Type</label>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="daily" name="report_type"
                                    class="custom-control-input" value="daily" checked>
                                <label class="custom-control-label" for="daily">Daily Summary</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input type="radio" id="detailed" name="report_type"
                                    class="custom-control-input" value="detailed">
                                <label class="custom-control-label" for="detailed">Detailed Records</label>
                            </div>
                        </div>

                        <button type="submit" name="generate_report" class="btn btn-primary btn-block">
                            <i class="fas fa-chart-bar mr-2"></i>Generate Report
                        </button>
                    </form>

                    <?php if ($has_generated && !empty($report_data)): ?>
                        <div class="mt-4">
                            <h5 class="mb-3"><i class="fas fa-download mr-2"></i>Export Options</h5>
                            <form method="post" action="api/report/export.php">
                                <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                                <input type="hidden" name="start_date" value="<?= special_chars($start_date) ?>">
                                <input type="hidden" name="end_date" value="<?= special_chars($end_date) ?>">

                                <div class="form-group">
                                    <label class="font-weight-bold">Export Format</label>
                                    <select name="export_format" class="custom-select">
                                        <option value="csv">CSV (Comma Separated)</option>
                                        <option value="pdf">PDF (Portable Document)</option>
                                        <option value="excel">Excel Spreadsheet</option>
                                    </select>
                                </div>

                                <button type="submit" name="export_report" class="btn btn-success btn-block">
                                    <i class="fas fa-file-export mr-2"></i>Export Report
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Report Content -->
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="h5 mb-0">
                        <i class="fas fa-chart-line mr-2"></i>
                        Egg Production Report
                    </h3>
                    <?php if ($has_generated && !empty($report_data)): ?>
                        <span class="badge badge-light">
                            <?= !empty($start_date) ? date('M d, Y', strtotime($start_date)) : 'N/A' ?> -
                            <?= !empty($end_date) ? date('M d, Y', strtotime($end_date)) : 'N/A' ?>
                        </span>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <?php if ($has_generated && empty($report_data)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-chart-pie fa-4x text-muted"></i>
                            </div>
                            <h4 class="mb-3">No Production Data Found</h4>
                            <p class="text-muted">
                                No egg production records found for the selected date range.
                            </p>
                        </div>
                    <?php elseif (!$has_generated): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-egg fa-4x text-primary"></i>
                            </div>
                            <h4 class="mb-3">Generate Production Report</h4>
                            <p class="text-muted">
                                Select a date range and generate a report to view egg production data.
                            </p>
                        </div>
                    <?php else: ?>
                        <!-- Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3 mb-3">
                                <div class="card border-left-primary shadow-sm h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                                    Total Eggs
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= number_format($summary['total_eggs']) ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-egg fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card border-left-success shadow-sm h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                                    Avg. Weight
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= special_chars($summary['avg_weight']) ?> g
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-weight fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card border-left-warning shadow-sm h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                                    Weight Range
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= special_chars($summary['min_weight']) ?>-<?= special_chars($summary['max_weight']) ?> g
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-ruler fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-3 mb-3">
                                <div class="card border-left-info shadow-sm h-100 py-2">
                                    <div class="card-body">
                                        <div class="row no-gutters align-items-center">
                                            <div class="col mr-2">
                                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                                    Recorded Days
                                                </div>
                                                <div class="h5 mb-0 font-weight-bold text-gray-800">
                                                    <?= special_chars(count(array_unique(array_column($report_data, 'date')))) ?>
                                                </div>
                                            </div>
                                            <div class="col-auto">
                                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Charts -->
                        <div class="row mb-4">
                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-chart-bar mr-1"></i>
                                            Daily Egg Production
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="dailyProductionChart"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-4">
                                <div class="card shadow-sm h-100">
                                    <div class="card-header bg-white py-3">
                                        <h6 class="m-0 font-weight-bold text-primary">
                                            <i class="fas fa-chart-pie mr-1"></i>
                                            Egg Size Distribution
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div id="sizeDistributionChart"></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Data Table -->
                        <div class="card shadow-sm">
                            <div class="card-header bg-white py-3">
                                <h6 class="m-0 font-weight-bold text-primary d-flex justify-content-between align-items-center">
                                    <span>
                                        <i class="fas fa-table mr-1"></i>
                                        Production Details
                                    </span>
                                    <small class="badge badge-primary">
                                        Showing <?= special_chars(count($report_data)) ?> records
                                    </small>
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Time</th>
                                                <th>Egg ID</th>
                                                <th>Size</th>
                                                <th>Weight (g)</th>
                                                <th>Device</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($report_data as $record): ?>
                                                <tr>
                                                    <td><?= !empty($record['date']) ? date('M d, Y', strtotime($record['date'])) : 'N/A' ?></td>
                                                    <td><?= !empty($record['time']) ? date('h:i A', strtotime($record['time'])) : 'N/A' ?></td>
                                                    <td>EGG-<?= str_pad($record['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                                    <td>
                                                        <span class="badge 
                                                            <?= $record['size'] == 'Pullets' ? 'bg-info' : '' ?>
                                                            <?= $record['size'] == 'Pewee' ? 'bg-info' : '' ?>
                                                            <?= $record['size'] == 'Small' ? 'bg-info' : '' ?>
                                                            <?= $record['size'] == 'Medium' ? 'bg-primary' : '' ?>
                                                            <?= $record['size'] == 'Large' ? 'bg-primary' : '' ?>
                                                            <?= $record['size'] == 'Jumbo' ? 'bg-success' : '' ?>
                                                            <?= $record['size'] == 'Extra Large' ? 'bg-warning' : '' ?>">
                                                            <?= special_chars($record['size']) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= special_chars($record['egg_weight']) ?></td>
                                                    <td>
                                                        <span class="badge bg-secondary">
                                                            <?= special_chars($record['device_serial_no']) ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($has_generated && !empty($report_data)): ?>
    <script>
        $(document).ready(function() {
            // Prepare data for charts
            const dailyData = {};
            <?php
            // Aggregate daily production
            foreach ($report_data as $record) {
                $date = $record['date'];
                if (!isset($dailyData[$date])) {
                    $dailyData[$date] = 0;
                }
                $dailyData[$date]++;
            }
            ksort($dailyData);
            ?>

            const dailyLabels = [<?= '"' . implode('","', array_keys($dailyData)) . '"' ?>];
            const dailyValues = [<?= implode(',', array_values($dailyData)) ?>];

            // Size distribution data
            const sizeLabels = [<?= '"' . implode('","', array_keys($summary['size_distribution'])) . '"' ?>];
            const sizeValues = [<?= implode(',', array_values($summary['size_distribution'])) ?>];
            const sizeColors = [
                '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
            ];

            // Daily Production Chart (ApexCharts)
            const dailyOptions = {
                series: [{
                    name: "Eggs Collected",
                    data: dailyValues
                }],
                chart: {
                    type: 'bar',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: false,
                            zoom: false,
                            zoomin: false,
                            zoomout: false,
                            pan: false,
                            reset: false
                        }
                    }
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '55%',
                        endingShape: 'rounded'
                    },
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    show: true,
                    width: 2,
                    colors: ['transparent']
                },
                xaxis: {
                    categories: dailyLabels,
                    labels: {
                        rotate: -45,
                        style: {
                            fontSize: '12px'
                        }
                    }
                },
                yaxis: {
                    title: {
                        text: 'Number of Eggs'
                    },
                    min: 0,
                    tickAmount: 5
                },
                fill: {
                    opacity: 1
                },
                tooltip: {
                    y: {
                        formatter: function(val) {
                            return val + " eggs"
                        }
                    }
                },
                colors: ['#4e73df']
            };

            const dailyChart = new ApexCharts(document.querySelector("#dailyProductionChart"), dailyOptions);
            dailyChart.render();

            // Size Distribution Chart (ApexCharts)
            const sizeOptions = {
                series: sizeValues,
                chart: {
                    type: 'donut',
                    height: 350,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true
                        }
                    }
                },
                labels: sizeLabels,
                colors: sizeColors,
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
                                        return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                    }
                                }
                            }
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function(value, {
                            seriesIndex,
                            w
                        }) {
                            const total = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            const percent = Math.round(value / total * 100);
                            return value + ' eggs (' + percent + '%)';
                        }
                    }
                },
                legend: {
                    position: 'right',
                    offsetY: 0,
                    height: 230,
                }
            };

            const sizeChart = new ApexCharts(document.querySelector("#sizeDistributionChart"), sizeOptions);
            sizeChart.render();
        });
    </script>
<?php endif; ?>

<style>
    .card {
        border-radius: 0.5rem;
        overflow: hidden;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .card-header {
        border-bottom: 1px solid rgba(0, 0, 0, .05);
        padding: 0.75rem 1.25rem;
    }

    .table thead th {
        border-top: 0;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 0.5px;
        color: #6c757d;
        border-bottom: 1px solid #e3e6f0;
        vertical-align: middle;
    }

    .table-hover tbody tr:hover {
        background-color: rgba(78, 115, 223, 0.05);
    }

    .badge {
        font-weight: 500;
        padding: 0.4em 0.8em;
        border-radius: 10rem;
        font-size: 0.85em;
    }

    .form-control,
    .custom-select {
        border: 1px solid #d1d3e2;
        border-radius: 0.35rem;
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        transition: border-color 0.15s ease-in-out;
    }

    .form-control:focus,
    .custom-select:focus {
        border-color: #bac8f3;
        outline: 0;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .btn-primary {
        background-color: #4e73df;
        border-color: #4e73df;
        transition: all 0.15s ease;
    }

    .btn-primary:hover {
        background-color: #2e59d9;
        border-color: #2653d4;
        transform: translateY(-1px);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .btn-success {
        background-color: #1cc88a;
        border-color: #1cc88a;
        transition: all 0.15s ease;
    }

    .btn-success:hover {
        background-color: #17a673;
        border-color: #169b6b;
        transform: translateY(-1px);
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .badge-info {
        background-color: #36b9cc;
    }

    .badge-primary {
        background-color: #4e73df;
    }

    .badge-success {
        background-color: #1cc88a;
    }

    .badge-warning {
        background-color: #f6c23e;
        color: #000;
    }

    .badge-secondary {
        background-color: #858796;
    }

    .apexcharts-tooltip {
        box-shadow: 0 0.15rem 1rem rgba(0, 0, 0, 0.15) !important;
        border-radius: 0.35rem !important;
    }

    .apexcharts-menu-item {
        padding: 6px 10px;
        font-size: 14px;
    }
</style>

<?php
function get_egg_production_data($farm_id, $start_date, $end_date)
{
    $mysqli = db_connect();

    $stmt = $mysqli->prepare("
                SELECT 
                    ed.id,
                    DATE(ed.created_at) AS date,
                    TIME(ed.created_at) AS time,
                    ed.size,
                    ed.egg_weight,
                    d.device_serial_no
                FROM egg_data ed
                JOIN devices d ON ed.mac = d.device_mac
                WHERE d.device_owner_id = ?
                AND DATE(ed.created_at) BETWEEN ? AND ?
                ORDER BY ed.created_at DESC
            ");

    $stmt->bind_param("iss", $farm_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

$push_js = [
    'libs/apexcharts/apexcharts.min.js',
];

$push_css = [
    'libs/apexcharts/apexcharts.css',
];

$content = ob_get_clean();
include layouts('employee.main');
?>