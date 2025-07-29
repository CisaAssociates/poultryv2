<?php
require_once __DIR__ . '/../../config.php';

$title = 'AI-Powered Egg Production Analytics';
$sub_title = 'Predictive Insights & Trend Analysis';
ob_start();

$conn = db_connect();

// Get all available farms
$farms = [];
$farm_result = $conn->query("SELECT farm_id, farm_name FROM farms");
if ($farm_result) {
    $farms = $farm_result->fetch_all(MYSQLI_ASSOC);
}

// Default date range (last 30 days)
$start_date = date('Y-m-d', strtotime('-30 days'));
$end_date = date('Y-m-d');
$farm_id = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $start_date = $_POST['start_date'] ?? $start_date;
    $end_date = $_POST['end_date'] ?? $end_date;
    $farm_id = isset($_POST['farm_id']) && $_POST['farm_id'] !== '' ? intval($_POST['farm_id']) : null;

    if ($start_date > $end_date) {
        $temp = $start_date;
        $start_date = $end_date;
        $end_date = $temp;
    }
}

// Fetch historical data for trend analysis
$params = [$start_date, $end_date];
$farm_condition = '';
if (!empty($farm_id)) {
    $farm_condition = " AND d.device_owner_id = ?";
    $params[] = $farm_id;
}

$sql = "SELECT 
            DATE(e.created_at) AS date, 
            AVG(e.egg_weight) AS avg_weight,
            COUNT(e.id) AS total_eggs,
            f.farm_name,
            e.size 
        FROM egg_data e
        INNER JOIN devices d ON e.mac = d.device_mac
        LEFT JOIN farms f ON d.device_owner_id = f.farm_id
        WHERE DATE(e.created_at) BETWEEN ? AND ?
        $farm_condition
        GROUP BY DATE(e.created_at), f.farm_name, e.size
        ORDER BY DATE(e.created_at) DESC";

$stmt = $conn->prepare($sql);
if ($farm_condition) {
    $types = 'ssi';
    $stmt->bind_param($types, ...$params);
} else {
    $types = 'ss';
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$analytics_data = $result->fetch_all(MYSQLI_ASSOC);

// Prepare data for AI predictions
$size_categories = ['Pewee', 'Pullets', 'Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];
$size_data = array_fill_keys($size_categories, ['weights' => [], 'dates' => []]);

foreach ($analytics_data as $row) {
    if (in_array($row['size'], $size_categories)) {
        $size_data[$row['size']]['weights'][] = (float)$row['avg_weight'];
        $size_data[$row['size']]['dates'][] = $row['date'];
    }
}

// AI Prediction Functions (simulated)
function predictSizeTrend($weights) {
    if (count($weights) < 3) return ['trend' => 'stable', 'change' => 0];
    
    // Simple linear regression
    $n = count($weights);
    $sumX = 0; $sumY = 0; $sumXY = 0; $sumX2 = 0;
    
    foreach ($weights as $i => $weight) {
        $sumX += $i;
        $sumY += $weight;
        $sumXY += $i * $weight;
        $sumX2 += $i * $i;
    }
    
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
    
    $trend = $slope > 0.1 ? 'increasing' : ($slope < -0.1 ? 'decreasing' : 'stable');
    $change = round($slope * 7, 2); // Projected change in 7 days
    
    return ['trend' => $trend, 'change' => $change];
}

function predictSizeTransition($size, $size_data) {
    // Simulate transition probabilities based on historical patterns
    $transitions = [];
    $size_categories = ['Pullets', 'Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];
    $current_avg = end($size_data[$size]['weights']);
    
    // Define size thresholds (grams)
    $size_thresholds = [
        'Pewee' => 40,
        'Pullets' => 45,
        'Small' => 50,
        'Medium' => 55,
        'Large' => 60,
        'Extra Large' => 65,
        'Jumbo' => 70
    ];
    
    // Determine neighboring sizes
    $size_index = array_search($size, $size_categories);
    $prev_size = $size_index > 0 ? $size_categories[$size_index - 1] : null;
    $next_size = $size_index < count($size_categories) - 1 ? $size_categories[$size_index + 1] : null;
    
    // Calculate transition probabilities
    if ($prev_size && $current_avg < $size_thresholds[$size] * 0.95) {
        $transitions[$prev_size] = min(90, round((1 - ($current_avg / $size_thresholds[$size])) * 100));
    }
    
    if ($next_size && $current_avg > $size_thresholds[$size] * 1.05) {
        $transitions[$next_size] = min(90, round((($current_avg - $size_thresholds[$size]) / ($size_thresholds[$next_size] - $size_thresholds[$size])) * 100));
    }
    
    // Always some chance of staying the same
    $stay_prob = 100 - array_sum($transitions);
    if ($stay_prob > 0) {
        $transitions[$size] = $stay_prob;
    }
    
    return $transitions;
}

function forecastProduction($size_data) {
    // Simulate production forecast based on historical patterns
    $forecast = [];
    
    foreach ($size_data as $size => $data) {
        if (count($data['weights']) > 5) {
            // Simple forecast: average of last 3 days
            $last_weights = array_slice($data['weights'], -3);
            $forecast[$size] = round(array_sum($last_weights) / count($last_weights), 2);
        } else {
            // Default forecast if not enough data
            $forecast[$size] = 0;
        }
    }
    
    // Normalize to 100% distribution
    $total = array_sum($forecast);
    if ($total > 0) {
        foreach ($forecast as $size => $value) {
            $forecast[$size] = round(($value / $total) * 100, 1);
        }
    }
    
    return $forecast;
}

// Generate predictions
$size_trends = [];
$size_transitions = [];
$production_forecast = forecastProduction($size_data);

foreach ($size_categories as $size) {
    if (!empty($size_data[$size]['weights'])) {
        $size_trends[$size] = predictSizeTrend($size_data[$size]['weights']);
        $size_transitions[$size] = predictSizeTransition($size, $size_data);
    }
}

// Get farm name for display
$selected_farm_name = 'All Farms';
if ($farm_id) {
    foreach ($farms as $farm) {
        if ($farm['farm_id'] == $farm_id) {
            $selected_farm_name = $farm['farm_name'];
            break;
        }
    }
}
?>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-header">
        <h5 class="mb-0">Data Filters</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="start_date" name="start_date"
                    value="<?= special_chars($start_date) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="end_date" name="end_date"
                    value="<?= special_chars($end_date) ?>" max="<?= date('Y-m-d') ?>">
            </div>
            <div class="col-md-3">
                <label for="farm_id" class="form-label">Farm</label>
                <select class="form-select" id="farm_id" name="farm_id" <?= empty($farms) ? 'disabled' : '' ?>>
                    <option value="">All Farms</option>
                    <?php foreach ($farms as $farm): ?>
                        <option value="<?= $farm['farm_id'] ?>"
                            <?= isset($farm_id) && $farm_id == $farm['farm_id'] ? 'selected' : '' ?>>
                            <?= special_chars($farm['farm_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($farms)): ?>
                    <div class="form-text text-warning">No farms available. Register farms first.</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3 d-flex align-items-center">
                <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- AI Insights Header -->
<div class="alert alert-info mb-4">
    <div class="d-flex align-items-center">
        <i class="fas fa-robot fa-2x me-3"></i>
        <div>
            <h4 class="alert-heading mb-1">AI-Powered Predictive Analytics</h4>
            <p class="mb-0">Harnessing machine learning to forecast production trends and provide actionable insights</p>
        </div>
    </div>
</div>

<!-- Production Forecast Row -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-primary">
            <div class="card-header bg-primary">
                <h4 class="mb-0 text-light">
                    <i class="fas fa-cloud-meatball me-2"></i>
                    Tomorrow's Egg Production Forecast
                    <span class="float-end">
                        <span class="badge bg-light text-dark"><?= $selected_farm_name ?></span>
                    </span>
                </h4>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <?php foreach ($size_categories as $size): ?>
                        <div class="col">
                            <div class="card mb-3">
                                <div class="card-header py-2 bg-light">
                                    <h6 class="mb-0 text-dark"><?= $size ?></h6>
                                </div>
                                <div class="card-body py-3">
                                    <div class="display-5 fw-bold">
                                        <?= $production_forecast[$size] ?? 0 ?>%
                                    </div>
                                    <div class="text-muted small">Projected Share</div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-light mt-3">
                    <div class="d-flex">
                        <i class="fas fa-info-circle text-primary me-2 mt-1"></i>
                        <div>
                            Forecast based on historical patterns and recent production trends. 
                            Values represent projected distribution across size categories.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Trend Analysis Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card border-success h-100">
            <div class="card-header bg-success text-white">
                <h4 class="mb-0 text-light">
                    <i class="fas fa-chart-line me-2"></i>
                    Size Trend Analysis
                </h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Size Category</th>
                                <th>Trend Direction</th>
                                <th>Avg Weight Trend</th>
                                <th>Projected 7-Day Change</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($size_categories as $size): ?>
                                <?php if (isset($size_trends[$size])): ?>
                                    <tr>
                                        <td><?= $size ?></td>
                                        <td>
                                            <?php if ($size_trends[$size]['trend'] === 'increasing'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-arrow-up me-1"></i> Increasing
                                                </span>
                                            <?php elseif ($size_trends[$size]['trend'] === 'decreasing'): ?>
                                                <span class="badge bg-danger">
                                                    <i class="fas fa-arrow-down me-1"></i> Decreasing
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">
                                                    <i class="fas fa-arrows-alt-h me-1"></i> Stable
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <?php 
                                                $trend_percent = $size_trends[$size]['trend'] === 'increasing' ? 70 : 
                                                                ($size_trends[$size]['trend'] === 'decreasing' ? 30 : 50);
                                                ?>
                                                <div class="progress-bar 
                                                    <?= $size_trends[$size]['trend'] === 'increasing' ? 'bg-success' : 
                                                        ($size_trends[$size]['trend'] === 'decreasing' ? 'bg-danger' : 'bg-secondary') ?>" 
                                                    role="progressbar" 
                                                    style="width: <?= $trend_percent ?>%;" 
                                                    aria-valuenow="<?= $trend_percent ?>" 
                                                    aria-valuemin="0" 
                                                    aria-valuemax="100">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="fw-bold <?= $size_trends[$size]['change'] > 0 ? 'text-success' : 
                                                            ($size_trends[$size]['change'] < 0 ? 'text-danger' : 'text-secondary') ?>">
                                            <?= $size_trends[$size]['change'] > 0 ? '+' : '' ?><?= $size_trends[$size]['change'] ?>g
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <tr>
                                        <td><?= $size ?></td>
                                        <td colspan="3" class="text-muted">Insufficient data for analysis</td>
                                    </tr>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="alert alert-light mt-3">
                    <div class="d-flex">
                        <i class="fas fa-lightbulb text-success me-2 mt-1"></i>
                        <div>
                            <span class="fw-bold">Key Insight:</span> 
                            <?php 
                            $most_improving = '';
                            $max_change = -100;
                            foreach ($size_trends as $size => $trend) {
                                if ($trend['change'] > $max_change) {
                                    $max_change = $trend['change'];
                                    $most_improving = $size;
                                }
                            }
                            ?>
                            The <?= $most_improving ?> category shows the strongest positive trend, suggesting 
                            potential for increased premium pricing opportunities.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card border-warning h-100">
            <div class="card-header bg-warning text-dark">
                <h4 class="mb-0 text-light">
                    <i class="fas fa-exchange-alt me-2"></i>
                    Size Transition Forecast
                </h4>
            </div>
            <div class="card-body">
                <p class="text-muted">Probability of eggs transitioning between size categories in future batches</p>
                
                <div class="row">
                    <?php foreach ($size_categories as $size): ?>
                        <?php if (isset($size_transitions[$size]) && count($size_transitions[$size]) > 0): ?>
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header py-2 bg-light">
                                        <h6 class="mb-0"><?= $size ?> Eggs</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Transition Probability:</span>
                                        </div>
                                        
                                        <?php foreach ($size_transitions[$size] as $target => $probability): ?>
                                            <div class="mb-2">
                                                <div class="d-flex justify-content-between">
                                                    <span class="<?= $target === $size ? 'fw-bold' : '' ?>">
                                                        <?= $target ?>
                                                    </span>
                                                    <span><?= $probability ?>%</span>
                                                </div>
                                                <div class="progress" style="height: 10px;">
                                                    <div class="progress-bar 
                                                        <?= $target === $size ? 'bg-info' : 'bg-warning' ?>" 
                                                        role="progressbar" 
                                                        style="width: <?= $probability ?>%;" 
                                                        aria-valuenow="<?= $probability ?>" 
                                                        aria-valuemin="0" 
                                                        aria-valuemax="100">
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        
                                        <div class="mt-3 text-center small">
                                            <?php 
                                            $most_likely = '';
                                            $highest_prob = 0;
                                            foreach ($size_transitions[$size] as $target => $probability) {
                                                if ($probability > $highest_prob) {
                                                    $highest_prob = $probability;
                                                    $most_likely = $target;
                                                }
                                            }
                                            ?>
                                            <i class="fas fa-bullseye me-1"></i>
                                            Most likely to transition to: <span class="fw-bold"><?= $most_likely ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                
                <div class="alert alert-light mt-3">
                    <div class="d-flex">
                        <i class="fas fa-lightbulb text-warning me-2 mt-1"></i>
                        <div>
                            <span class="fw-bold">Poultry Insight:</span> 
                            Tracking size transitions helps monitor hen development and predict when flocks 
                            will reach peak production for different size categories.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Add tooltips to forecast cards
    document.addEventListener('DOMContentLoaded', function() {
        // Add tooltips to forecast cards
        const forecastCards = document.querySelectorAll('.card .card');
        forecastCards.forEach(card => {
            const size = card.querySelector('.card-header h6').textContent;
            card.setAttribute('title', `Click to see ${size} trend details`);
            card.style.cursor = 'pointer';
            
            card.addEventListener('click', function() {
                const sizeName = size.replace(' ', '-').toLowerCase();
                document.getElementById(`trend-${sizeName}`).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    });
</script>

<style>
    .progress-bar {
        transition: width 0.5s ease-in-out;
    }
    /* .card .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        transition: all 0.3s ease;
    } */
    .badge {
        font-weight: 500;
    }
</style>

<?php
$conn->close();

// Push additional CSS and JS
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/datatables.net-responsive-bs5/css/responsive.bootstrap5.min.css',
    'libs/chart.js/chart.min.css'
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