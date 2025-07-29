<?php
require_once __DIR__ . '/../config.php';

$title = 'Dashboard';
$sub_title = 'System Administrator';
ob_start();

$conn = db_connect();

// Fetch system statistics
$stats = [];
$queries = [
    'farms' => "SELECT COUNT(*) AS count FROM farms",
    'devices' => "SELECT COUNT(*) AS count FROM devices",
    'users' => "SELECT COUNT(*) AS count FROM users",
    'registered_devices' => "SELECT COUNT(*) AS count FROM devices WHERE is_registered = 1",
    'eggs_today' => "SELECT COUNT(*) AS count FROM egg_data WHERE DATE(created_at) = CURDATE()",
    'total_eggs' => "SELECT COUNT(*) AS count FROM egg_data",
    'active_devices' => "SELECT COUNT(DISTINCT mac) AS count FROM egg_data WHERE created_at >= NOW() - INTERVAL 1 HOUR"
];

foreach ($queries as $key => $sql) {
    $result = $conn->query($sql);
    if ($result) {
        $stats[$key] = $result->fetch_assoc()['count'];
    } else {
        $stats[$key] = 0;
    }
}

// Fetch recent production data (last 7 days)
$production_data = [];
$prod_sql = "SELECT 
                DATE(created_at) AS date, 
                COUNT(*) AS stock_count,
                AVG(egg_weight) AS avg_weight
            FROM egg_data 
            WHERE created_at >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)";

$prod_result = $conn->query($prod_sql);
if ($prod_result) {
    while ($row = $prod_result->fetch_assoc()) {
        $production_data[] = $row;
    }
}

// FIXED: Device status query - now correctly counts devices
$device_status = [];
$status_sql = "SELECT 
                d.device_mac,
                d.device_type,
                d.is_registered,
                CASE
                    WHEN d.is_registered = 0 THEN 'Not Registered'
                    WHEN MAX(e.created_at) >= DATE_SUB(NOW(), INTERVAL 1 HOUR) THEN 'Online'
                    ELSE 'Offline'
                END AS device_status,
                MAX(e.created_at) AS last_active
            FROM devices d
            LEFT JOIN egg_data e ON d.device_mac = e.mac
            GROUP BY d.device_mac, d.device_type, d.is_registered";

$status_result = $conn->query($status_sql);
if ($status_result) {
    while ($row = $status_result->fetch_assoc()) {
        $device_status[] = $row;
    }
}

// Prepare production chart data
$prod_dates = [];
$prod_counts = [];
$prod_weights = [];

foreach ($production_data as $row) {
    $prod_dates[] = !empty($row['date']) ? date('M j', strtotime($row['date'])) : 'N/A';
    $prod_counts[] = $row['stock_count'];
    $prod_weights[] = $row['avg_weight'];
}

// FIXED: Correct device status counting
$status_counts = [
    'Online' => 0,
    'Offline' => 0,
    'Not Registered' => 0
];

$device_type_counts = [];
foreach ($device_status as $device) {
    $status_counts[$device['device_status']]++;

    // Count by type for the table
    $type = $device['device_type'];
    $status = $device['device_status'];

    if (!isset($device_type_counts[$type])) {
        $device_type_counts[$type] = [
            'Online' => 0,
            'Offline' => 0,
            'Not Registered' => 0
        ];
    }

    $device_type_counts[$type][$status]++;
}

// Prepare device status for chart
$status_labels = ['Online', 'Offline', 'Not Registered'];
$status_series = [
    $status_counts['Online'],
    $status_counts['Offline'],
    $status_counts['Not Registered']
];

$conn->close();
?>
<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                            Total Farms
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $stats['farms'] ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tractor fa-2x text-primary"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                            Active Devices
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $stats['active_devices'] ?></div>
                        <div class="mt-2 small text-muted">
                            <span class="text-success">
                                <i class="fas fa-arrow-up"></i> <?= $stats['registered_devices'] ?> registered
                            </span>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-microchip fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                            Today's Production
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $stats['eggs_today'] ?> eggs</div>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-database"></i> <?= $stats['total_eggs'] ?> total eggs
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-egg fa-2x text-info"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                            System Users
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800"><?= $stats['users'] ?></div>
                        <div class="mt-2 small text-muted">
                            <i class="fas fa-user-clock"></i> Last login: Today
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-users fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <!-- Production Chart -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Egg Production (Last 7 Days)</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in"
                        aria-labelledby="dropdownMenuLink">
                        <div class="dropdown-header">Chart Options:</div>
                        <a class="dropdown-item" href="#" id="toggleWeights">Toggle Weight Display</a>
                        <a class="dropdown-item" href="#" id="exportChart">Export as PNG</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($production_data)): ?>
                    <div id="productionChart"></div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-chart-bar fa-3x mb-3"></i>
                        <h5>No production data available</h5>
                        <p class="mb-0">Egg production data will appear here once available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Device Status -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Device Status</h6>
            </div>
            <div class="card-body">
                <?php if (array_sum($status_counts) > 0): ?>
                    <div id="deviceStatusChart"></div>
                    <div class="mt-4 text-center small">
                        <div class="row">
                            <div class="col">
                                <span class="d-block text-success">
                                    <i class="fas fa-circle"></i> Online: <?= $status_counts['Online'] ?>
                                </span>
                            </div>
                            <div class="col">
                                <span class="d-block text-danger">
                                    <i class="fas fa-circle"></i> Offline: <?= $status_counts['Offline'] ?>
                                </span>
                            </div>
                            <div class="col">
                                <span class="d-block text-secondary">
                                    <i class="fas fa-circle"></i> Not Registered: <?= $status_counts['Not Registered'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-microchip fa-3x mb-3"></i>
                        <h5>No devices found</h5>
                        <p class="mb-0">Register devices to see status information</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Device Summary -->
<div class="row mb-4">
    <div class="col">
        <div class="card shadow">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary">Device Summary</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="deviceDropdown"
                        data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end shadow animated--fade-in"
                        aria-labelledby="deviceDropdown">
                        <div class="dropdown-header">Export Options:</div>
                        <a class="dropdown-item" href="#" id="exportCSV">CSV</a>
                        <a class="dropdown-item" href="#" id="exportExcel">Excel</a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($device_status)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered" id="deviceTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Device Type</th>
                                    <th>Online</th>
                                    <th>Offline</th>
                                    <th>Not Registered</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($device_type_counts as $type => $counts): ?>
                                    <?php
                                    $total = $counts['Online'] + $counts['Offline'] + $counts['Not Registered'];
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($type) ?></td>
                                        <td class="text-success"><?= $counts['Online'] ?></td>
                                        <td class="text-danger"><?= $counts['Offline'] ?></td>
                                        <td class="text-muted"><?= $counts['Not Registered'] ?></td>
                                        <td class="fw-bold"><?= $total ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-active fw-bold">
                                    <td>Totals</td>
                                    <td class="text-success"><?= $status_counts['Online'] ?></td>
                                    <td class="text-danger"><?= $status_counts['Offline'] ?></td>
                                    <td class="text-muted"><?= $status_counts['Not Registered'] ?></td>
                                    <td><?= count($device_status) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info text-center py-4">
                        <i class="fas fa-microchip fa-3x mb-3"></i>
                        <h5>No devices registered</h5>
                        <p class="mb-0">Register devices to see them listed here</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Load ApexCharts -->
<script src="<?= asset('libs/apexcharts/apexcharts.min.js') ?>"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Production Chart
        <?php if (!empty($production_data)): ?>
            const prodOptions = {
                series: [{
                        name: "Egg Count",
                        type: "column",
                        data: <?= json_encode($prod_counts) ?>
                    },
                    {
                        name: "Avg Weight",
                        type: "line",
                        data: <?= json_encode($prod_weights) ?>
                    }
                ],
                chart: {
                    height: 350,
                    type: 'line',
                    stacked: false,
                    toolbar: {
                        show: true,
                        tools: {
                            download: true,
                            selection: true,
                            zoom: true,
                            zoomin: true,
                            zoomout: true,
                            pan: true,
                            reset: true
                        }
                    },
                    zoom: {
                        enabled: true
                    }
                },
                dataLabels: {
                    enabled: false
                },
                stroke: {
                    width: [2, 3],
                    curve: 'smooth'
                },
                plotOptions: {
                    bar: {
                        columnWidth: '50%'
                    }
                },
                colors: ['#3a7afe', '#ff5252'],
                xaxis: {
                    categories: <?= json_encode($prod_dates) ?>,
                    title: {
                        text: 'Date'
                    }
                },
                yaxis: [{
                        seriesName: 'Egg Count',
                        axisTicks: {
                            show: true
                        },
                        axisBorder: {
                            show: true,
                            color: '#3a7afe'
                        },
                        labels: {
                            style: {
                                colors: '#3a7afe'
                            }
                        },
                        title: {
                            text: "Egg Count",
                            style: {
                                color: '#3a7afe'
                            }
                        }
                    },
                    {
                        seriesName: 'Avg Weight',
                        opposite: true,
                        axisTicks: {
                            show: true
                        },
                        axisBorder: {
                            show: true,
                            color: '#ff5252'
                        },
                        labels: {
                            style: {
                                colors: '#ff5252'
                            }
                        },
                        title: {
                            text: "Avg Weight (g)",
                            style: {
                                color: '#ff5252'
                            }
                        }
                    }
                ],
                tooltip: {
                    shared: true,
                    intersect: false,
                    y: {
                        formatter: function(y) {
                            if (typeof y !== "undefined") {
                                return y.toFixed(0) + (y > 1 ? " eggs" : " g");
                            }
                            return y;
                        }
                    }
                },
                legend: {
                    position: 'top',
                    horizontalAlign: 'left',
                    offsetY: 0
                }
            };

            const prodChart = new ApexCharts(document.querySelector("#productionChart"), prodOptions);
            prodChart.render();
        <?php endif; ?>

        // Device Status Chart
        <?php if (count($device_status) > 0): ?>
            const statusOptions = {
                series: <?= json_encode($status_series) ?>,
                chart: {
                    height: 300,
                    type: 'donut',
                },
                labels: <?= json_encode($status_labels) ?>,
                colors: ['#28a745', '#dc3545', '#6c757d'],
                legend: {
                    position: 'bottom'
                },
                plotOptions: {
                    pie: {
                        donut: {
                            size: '65%',
                            labels: {
                                show: true,
                                total: {
                                    show: true,
                                    label: 'Total Devices',
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
                    }
                },
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
                }]
            };

            const statusChart = new ApexCharts(document.querySelector("#deviceStatusChart"), statusOptions);
            statusChart.render();
        <?php endif; ?>

        // Initialize DataTable for devices
        if ($('#deviceTable').length && <?= !empty($device_status) ? 'true' : 'false' ?>) {
            $('#deviceTable').DataTable({
                ordering: true,
                order: [
                    [4, 'desc']
                ],
                searching: false,
                paging: false,
                info: false,
                dom: '<"top"f>rt<"bottom"lip><"clear">'
            });
        }
        
        // Chart toggle functionality
        $('#toggleWeights').click(function(e) {
            e.preventDefault();
            prodChart.toggleSeries('Avg Weight');
        });

        // Export chart as PNG
        $('#exportChart').click(function(e) {
            e.preventDefault();
            prodChart.dataURI().then((uri) => {
                const link = document.createElement('a');
                link.href = uri;
                link.download = 'egg-production-chart.png';
                link.click();
            });
        });

        // Export device data
        $('#exportCSV').click(function(e) {
            e.preventDefault();
            // Implement CSV export functionality here
            alert('CSV export functionality would be implemented here');
        });
    });
</script>

<style>
    .card {
        border-radius: 0.5rem;
        transition: transform 0.3s ease;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }

    .apexcharts-tooltip {
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border-radius: 0.5rem !important;
    }

    .apexcharts-menu {
        border-radius: 0.5rem !important;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
        border: none !important;
    }

    .dropdown-menu {
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        border: none;
    }
</style>

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/apexcharts/apexcharts.css'
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
];

$content = ob_get_clean();
include layouts('admin.main');
?>