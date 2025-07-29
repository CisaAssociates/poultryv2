<?php
// File: dashboard.php
require_once __DIR__ . '/../config.php';

$title = 'Employee Dashboard';
$sub_title = 'Farm Management Overview';

// Get employee data
$employee_id = $user['employee_id'] ?? 0;
$farm_id = $user['farm_id'] ?? 0;

// Fetch dashboard data
$upcoming_tasks = get_employee_upcoming_tasks($employee_id, 5);
$todays_production = get_todays_egg_production($farm_id);
$weekly_trends = get_weekly_production_trend($farm_id);
$notifications = get_employee_notifications($employee_id, 5);

// Calculate task completion rate
$total_tasks = get_employee_task_count($employee_id);
$completed_tasks = get_employee_completed_task_count($employee_id);
$completion_rate = $total_tasks > 0 ? round(($completed_tasks / $total_tasks) * 100) : 0;

ob_start();
?>

<div class="dashboard-container">
    <div class="row">
        <!-- Left Column -->
        <div class="col-lg-8">
            <!-- Welcome Card -->
            <div class="card welcome-card mb-4">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h2 class="mb-1 text-white">Good <?= get_time_greeting() ?>, <?= special_chars($user['fullname']) ?>!</h2>
                            <p class="text-white mb-0">Here's what's happening on your farm today</p>
                        </div>
                        <div class="col-md-4 text-right">
                            <div class="date-display">
                                <div class="day"><?= date('l') ?></div>
                                <div class="date"><?= date('F j, Y') ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Today's Eggs</h6>
                                    <h3 class="mb-0"><?= $todays_production['total_eggs'] ?? 0 ?></h3>
                                </div>
                                <div class="icon-circle bg-primary">
                                    <i class="fas fa-egg"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="<?= ($todays_production['avg_weight'] ?? 0) > 60 ? 'text-success' : 'text-warning' ?>">
                                    <i class="fas fa-weight"></i> <?= $todays_production['avg_weight'] ?? '0.0' ?>g avg
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Tasks Completed</h6>
                                    <h3 class="mb-0"><?= $completion_rate ?>%</h3>
                                </div>
                                <div class="icon-circle bg-success">
                                    <i class="fas fa-tasks"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">
                                    <?= $completed_tasks ?> of <?= $total_tasks ?> tasks
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Active Devices</h6>
                                    <h3 class="mb-0"><?= get_active_device_count($farm_id) ?></h3>
                                </div>
                                <div class="icon-circle bg-info">
                                    <i class="fas fa-microchip"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">
                                    <?= get_total_device_count($farm_id) ?> total
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6 mb-3">
                    <div class="card stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase text-muted small mb-1">Notifications</h6>
                                    <h3 class="mb-0"><?= count($notifications) ?></h3>
                                </div>
                                <div class="icon-circle bg-warning">
                                    <i class="fas fa-bell"></i>
                                </div>
                            </div>
                            <div class="mt-3">
                                <span class="text-muted small">
                                    <?= get_unread_notification_count($employee_id) ?> unread
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Production Chart -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Egg Production Trend</h5>
                    <p class="text-muted mb-0 small">Last 7 days production overview</p>
                </div>
                <div class="card-body p-3">
                    <div id="productionChart" style="height: 300px;"></div>
                </div>
            </div>
            
            <!-- Task Management -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Upcoming Tasks</h5>
                    <a href="<?= view('employee.tasks') ?>" class="btn btn-sm btn-outline-primary">View All</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($upcoming_tasks)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-check-circle fa-2x mb-3"></i>
                            <p>No upcoming tasks. Great job!</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($upcoming_tasks as $task): ?>
                                <?php 
                                $priority_class = [
                                    'low' => 'info',
                                    'medium' => 'warning',
                                    'high' => 'danger'
                                ][$task['priority']];
                                
                                $due_date = strtotime($task['due_date']);
                                $is_today = date('Y-m-d') == date('Y-m-d', $due_date);
                                $is_tomorrow = date('Y-m-d', strtotime('+1 day')) == date('Y-m-d', $due_date);
                                ?>
                                
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <div class="custom-control custom-checkbox mr-3">
                                                <input type="checkbox" class="custom-control-input" id="task-<?= $task['task_id'] ?>">
                                                <label class="custom-control-label" for="task-<?= $task['task_id'] ?>"></label>
                                            </div>
                                            <div>
                                                <h6 class="mb-0"><?= special_chars($task['title']) ?></h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar mr-1"></i>
                                                    <?php if ($is_today): ?>
                                                        <span class="text-danger">Today</span>
                                                    <?php elseif ($is_tomorrow): ?>
                                                        <span class="text-warning">Tomorrow</span>
                                                    <?php else: ?>
                                                        <?= date('M d', $due_date) ?>
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="badge badge-<?= $priority_class ?> badge-pill">
                                                <?= ucfirst($task['priority']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Right Column -->
        <div class="col-lg-4">
            <!-- Notifications -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Notifications</h5>
                    <a href="#" class="btn btn-sm btn-outline-secondary">Mark All Read</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="p-4 text-center text-muted">
                            <i class="fas fa-bell-slash fa-2x mb-3"></i>
                            <p>No new notifications</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item">
                                    <div class="d-flex">
                                        <div class="mr-3">
                                            <?php 
                                            $icon_class = [
                                                'success' => 'check-circle text-success',
                                                'error' => 'exclamation-circle text-danger',
                                                'warning' => 'exclamation-triangle text-warning',
                                                'info' => 'info-circle text-info'
                                            ][$notification['type']];
                                            ?>
                                            <i class="fas fa-<?= $icon_class ?> fa-2x"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= special_chars($notification['title']) ?></h6>
                                            <p class="mb-0 small"><?= special_chars($notification['message']) ?></p>
                                            <small class="text-muted">
                                                <?= time_elapsed_string($notification['created_at']) ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Egg Size Distribution -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Egg Size Distribution</h5>
                    <p class="text-muted mb-0 small">Today's collected eggs</p>
                </div>
                <div class="card-body">
                    <div id="sizeDistributionChart" style="height: 250px;"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Production trend chart
    const productionOptions = {
        series: [{
            name: "Eggs Collected",
            data: [<?= implode(',', $weekly_trends['values']) ?>]
        }],
        chart: {
            height: 300,
            type: 'area',
            toolbar: {
                show: true,
                tools: {
                    download: true
                }
            }
        },
        colors: ['#4e73df'],
        dataLabels: {
            enabled: false
        },
        stroke: {
            curve: 'smooth',
            width: 2
        },
        xaxis: {
            categories: [<?= '"' . implode('","', $weekly_trends['labels']) . '"' ?>],
            labels: {
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
        tooltip: {
            y: {
                formatter: function (val) {
                    return val + " eggs"
                }
            }
        }
    };

    const productionChart = new ApexCharts(document.querySelector("#productionChart"), productionOptions);
    productionChart.render();
    
    // Size distribution chart
    const sizeOptions = {
        series: [<?= implode(',', $todays_production['size_values']) ?>],
        chart: {
            height: 250,
            type: 'donut',
            toolbar: {
                show: true,
                tools: {
                    download: true
                }
            }
        },
        labels: [<?= '"' . implode('","', $todays_production['size_labels']) . '"' ?>],
        colors: ['#36b9cc', '#1cc88a', '#4e73df', '#f6c23e'],
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
                            formatter: function (w) {
                                return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                            }
                        }
                    }
                }
            }
        }
    };

    const sizeChart = new ApexCharts(document.querySelector("#sizeDistributionChart"), sizeOptions);
    sizeChart.render();
});
</script>

<style>
.dashboard-container {
    padding: 20px;
}

.welcome-card {
    background: linear-gradient(120deg, #4e73df, #224abe);
    color: white;
    border: none;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(78, 115, 223, 0.3);
}

.welcome-card h2 {
    font-weight: 600;
}

.date-display {
    background: rgba(255, 255, 255, 0.15);
    padding: 12px 20px;
    border-radius: 8px;
    text-align: center;
    backdrop-filter: blur(5px);
}

.date-display .day {
    font-size: 1.2rem;
    font-weight: 500;
}

.date-display .date {
    font-size: 0.9rem;
    opacity: 0.9;
}

.stat-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.icon-circle {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 1.2rem;
}

.card-header {
    background: white;
    border-bottom: 1px solid #e3e6f0;
    padding: 1rem 1.5rem;
    border-radius: 10px 10px 0 0 !important;
}

.list-group-item {
    border: none;
    border-bottom: 1px solid #e3e6f0;
    padding: 1.25rem;
}

.list-group-item:last-child {
    border-bottom: none;
}

.badge {
    font-weight: 500;
    padding: 0.5em 0.8em;
    border-radius: 10rem;
    font-size: 0.8rem;
}

.badge-info { background-color: #36b9cc; }
.badge-primary { background-color: #4e73df; }
.badge-success { background-color: #1cc88a; }
.badge-warning { background-color: #f6c23e; color: #000; }
.badge-danger { background-color: #e74a3b; }

.custom-control-input:checked ~ .custom-control-label::before {
    background-color: #4e73df;
    border-color: #4e73df;
}

.btn-light {
    background: white;
    border: 1px solid #e3e6f0;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.btn-light:hover {
    background: #f8f9fc;
    border-color: #d1d3e2;
    transform: translateY(-3px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
}

.btn-light i {
    transition: transform 0.3s ease;
}

.btn-light:hover i {
    transform: scale(1.1);
}
</style>

<?php
function get_time_greeting() {
    $hour = date('G');
    if ($hour >= 5 && $hour < 12) return 'Morning';
    if ($hour >= 12 && $hour < 18) return 'Afternoon';
    return 'Evening';
}

function get_employee_upcoming_tasks($employee_id, $limit = 5) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT t.*, f.farm_name 
        FROM tasks t
        JOIN farms f ON t.farm_id = f.farm_id
        WHERE t.assigned_to = ?
        AND t.status IN ('pending', 'in_progress')
        ORDER BY t.due_date ASC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $employee_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_todays_egg_production($farm_id) {
    $today = date('Y-m-d');
    $mysqli = db_connect();
    
    // Get basic production stats
    $stmt = $mysqli->prepare("
        SELECT 
            COUNT(id) AS total_eggs,
            AVG(egg_weight) AS avg_weight,
            MIN(egg_weight) AS min_weight,
            MAX(egg_weight) AS max_weight
        FROM egg_data
        WHERE DATE(created_at) = ?
        AND mac IN (SELECT device_mac FROM devices WHERE device_owner_id = ?)
    ");
    $stmt->bind_param("si", $today, $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $production = $result->fetch_assoc();
    
    // Get size distribution
    $size_stmt = $mysqli->prepare("
        SELECT size, COUNT(*) AS count
        FROM egg_data
        WHERE DATE(created_at) = ?
        AND mac IN (SELECT device_mac FROM devices WHERE device_owner_id = ?)
        GROUP BY size
    ");
    $size_stmt->bind_param("si", $today, $farm_id);
    $size_stmt->execute();
    $size_result = $size_stmt->get_result();
    
    $size_labels = [];
    $size_values = [];
    while ($row = $size_result->fetch_assoc()) {
        $size_labels[] = $row['size'];
        $size_values[] = $row['count'];
    }
    
    $production['size_labels'] = $size_labels;
    $production['size_values'] = $size_values;
    
    return $production;
}

function get_weekly_production_trend($farm_id) {
    $mysqli = db_connect();
    $end_date = date('Y-m-d');
    $start_date = date('Y-m-d', strtotime('-6 days'));
    
    $stmt = $mysqli->prepare("
        SELECT 
            DATE(created_at) AS date,
            COUNT(id) AS count
        FROM egg_data
        WHERE DATE(created_at) BETWEEN ? AND ?
        AND mac IN (SELECT device_mac FROM devices WHERE device_owner_id = ?)
        GROUP BY DATE(created_at)
        ORDER BY DATE(created_at) ASC
    ");
    $stmt->bind_param("ssi", $start_date, $end_date, $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Create array for all 7 days
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("-{$i} days"));
        $dates[$date] = 0;
    }
    
    // Fill with actual data
    while ($row = $result->fetch_assoc()) {
        $dates[$row['date']] = (int)$row['count'];
    }
    
    // Sort dates in chronological order
    ksort($dates);
    
    return [
        'labels' => array_map(function($date) { 
            return date('D', strtotime($date)); 
        }, array_keys($dates)),
        'values' => array_values($dates)
    ];
}

function get_employee_notifications($employee_id, $limit = 5) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT n.* 
        FROM notifications n
        JOIN employees e ON n.farm_id = e.farm_id
        WHERE e.employee_id = ?
        AND n.is_read = 0
        ORDER BY n.created_at DESC
        LIMIT ?
    ");
    $stmt->bind_param("ii", $employee_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function get_employee_task_count($employee_id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS count
        FROM tasks
        WHERE assigned_to = ?
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function get_employee_completed_task_count($employee_id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS count
        FROM tasks
        WHERE assigned_to = ?
        AND status = 'completed'
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function get_active_device_count($farm_id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS count
        FROM devices
        WHERE device_owner_id = ?
        AND is_registered = 1
    ");
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function get_total_device_count($farm_id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS count
        FROM devices
        WHERE device_owner_id = ?
    ");
    $stmt->bind_param("i", $farm_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function get_unread_notification_count($employee_id) {
    $mysqli = db_connect();
    $stmt = $mysqli->prepare("
        SELECT COUNT(*) AS count
        FROM notifications n
        JOIN employees e ON n.farm_id = e.farm_id
        WHERE e.employee_id = ?
        AND n.is_read = 0
    ");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'] ?? 0;
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);

    $weeks = floor($diff->d / 7);
    $days = $diff->d - $weeks * 7;

    $string = [
        'y' => 'year',
        'm' => 'month',
        'w' => $weeks,
        'd' => $days,
        'h' => $diff->h,
        'i' => $diff->i,
        's' => $diff->s,
    ];
    
    foreach ($string as $k => &$v) {
        if ($v) {
            $label = match ($k) {
                'y' => 'year',
                'm' => 'month',
                'w' => 'week',
                'd' => 'day',
                'h' => 'hour',
                'i' => 'minute',
                's' => 'second',
            };
            $v = $v . ' ' . $label . ($v > 1 ? 's' : '');
        } else {
            unset($string[$k]);
        }
    }

    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? implode(', ', $string) . ' ago' : 'just now';
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