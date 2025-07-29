<?php
require_once __DIR__ . '/../config.php';

if (!is_logged_in() || $_SESSION['role'] != 2) {
    header('Location: ' . view('auth.login'));
    exit;
}

$owner_id = $_SESSION['id'];
$title = 'Sales & Revenue';
$sub_title = 'Financial Performance Tracking';

// Default timeframe (current month)
$timeframe = 'monthly';
$startDate = date('Y-m-01 00:00:00');
$endDate = date('Y-m-t 23:59:59');

// Handle timeframe selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['timeframe'])) {
    $timeframe = $_POST['timeframe'];
    
    switch ($timeframe) {
        case 'daily':
            $startDate = date('Y-m-d 00:00:00');
            $endDate = date('Y-m-d 23:59:59');
            break;
        case 'weekly':
            $startDate = date('Y-m-d 00:00:00', strtotime('monday this week'));
            $endDate = date('Y-m-d 23:59:59', strtotime('sunday this week'));
            break;
        case 'quarterly':
            $quarter = ceil(date('n') / 3);
            $startDate = date('Y-m-01 00:00:00', mktime(0, 0, 0, ($quarter * 3) - 2, 1));
            $endDate = date('Y-m-t 23:59:59', mktime(0, 0, 0, $quarter * 3, 1));
            break;
        case 'yearly':
            $startDate = date('Y-01-01 00:00:00');
            $endDate = date('Y-12-31 23:59:59');
            break;
        case 'monthly':
        default:
            $startDate = date('Y-m-01 00:00:00');
            $endDate = date('Y-m-t 23:59:59');
            break;
    }
}

ob_start();
$conn = db_connect();

// Get farm_id from session
$farm_id = $_SESSION['selected_farm_id'] ?? null;
if (!$farm_id) {
    // Show error message if no farm is selected
    echo '<div class="alert alert-danger">No farm selected. Please select a farm first.</div>';
    $content = ob_get_clean();
    require_once layouts('owner.main');
    exit;
}

// Check if farm has any devices
$device_check = $conn->prepare("SELECT COUNT(*) as device_count FROM devices WHERE device_owner_id = ?");
$device_check->bind_param("i", $farm_id);
$device_check->execute();
$device_result = $device_check->get_result()->fetch_assoc();

if ($device_result['device_count'] == 0) {
    // Redirect to no devices error page
    header('Location: ' . view('owner.error.no-devices'));
    exit;
}

// Get financial summary
$summaryStmt = $conn->prepare(
    "SELECT 
        SUM(t.total) AS total_revenue,
        SUM(t.tax) AS total_tax,
        SUM(i.quantity) AS total_eggs_sold,
        SUM(i.total) AS total_sales
     FROM transactions t
     JOIN transaction_items i ON t.transaction_id = i.transaction_id
     WHERE t.farm_id = ?
        AND t.created_at BETWEEN ? AND ?"
);
$summaryStmt->bind_param('iss', $farm_id, $startDate, $endDate);
$summaryStmt->execute();
$summaryResult = $summaryStmt->get_result();
$summary = $summaryResult->fetch_assoc();

$totalRevenue = $summary['total_revenue'] ?? 0;
$totalEggsSold = $summary['total_eggs_sold'] ?? 0;

// Placeholder values (in a real system these would come from expense tracking)
$totalExpenses = $totalRevenue * 0.25; // 25% of revenue as expenses
$netProfit = $totalRevenue - $totalExpenses;
$profitMargin = $totalRevenue > 0 ? ($netProfit / $totalRevenue) * 100 : 0;

// Get revenue trend data
$trendStmt = $conn->prepare(
    "SELECT 
        DATE_FORMAT(t.created_at, '%Y-%m') AS month,
        SUM(t.total) AS revenue
     FROM transactions t
     WHERE t.farm_id = ?
        AND t.created_at BETWEEN DATE_SUB(NOW(), INTERVAL 1 YEAR) AND NOW()
     GROUP BY DATE_FORMAT(t.created_at, '%Y-%m')
     ORDER BY t.created_at"
);
$trendStmt->bind_param('i', $farm_id);
$trendStmt->execute();
$trendResult = $trendStmt->get_result();

$trendLabels = [];
$trendData = [];
$currentYear = date('Y');

while ($row = $trendResult->fetch_assoc()) {
    $month = !empty($row['month']) ? date('M', strtotime($row['month'] . '-01')) : 'N/A';
    $trendLabels[] = $month;
    $trendData[] = $row['revenue'];
}

// Get revenue by size
$sizeRevenueStmt = $conn->prepare(
    "SELECT 
        i.size,
        SUM(i.total) AS revenue,
        SUM(i.quantity) AS quantity
     FROM transaction_items i
     JOIN transactions t ON i.transaction_id = t.transaction_id
     WHERE t.farm_id IN (SELECT farm_id FROM farms WHERE owner_id = ?)
        AND t.created_at BETWEEN ? AND ?
     GROUP BY i.size
     ORDER BY revenue DESC"
);
$sizeRevenueStmt->bind_param('iss', $owner_id, $startDate, $endDate);
$sizeRevenueStmt->execute();
$sizeRevenueResult = $sizeRevenueStmt->get_result();

$sizeLabels = [];
$sizeData = [];
$sizeQuantities = [];

while ($row = $sizeRevenueResult->fetch_assoc()) {
    $sizeLabels[] = $row['size'];
    $sizeData[] = $row['revenue'];
    $sizeQuantities[] = $row['quantity'];
}

// Get recent transactions
$transactionsStmt = $conn->prepare(
    "SELECT t.transaction_id, t.created_at, t.total, 
            GROUP_CONCAT(CONCAT(i.size, '(', i.quantity, ')')) AS items
     FROM transactions t
     JOIN transaction_items i ON t.transaction_id = i.transaction_id
     WHERE t.farm_id IN (SELECT farm_id FROM farms WHERE owner_id = ?)
        AND t.created_at BETWEEN ? AND ?
     GROUP BY t.transaction_id
     ORDER BY t.created_at DESC
     LIMIT 10"
);
$transactionsStmt->bind_param('iss', $owner_id, $startDate, $endDate);
$transactionsStmt->execute();
$transactionsResult = $transactionsStmt->get_result();
$recentTransactions = $transactionsResult->fetch_all(MYSQLI_ASSOC);

$conn->close();
?>

<div class="row">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total Revenue</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<span data-plugin="counterup"><?= number_format($totalRevenue, 2) ?></span></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Net Profit</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">₱<span data-plugin="counterup"><?= number_format($netProfit, 2) ?></span></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Profit Margin</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><span data-plugin="counterup"><?= number_format($profitMargin, 1) ?>%</span></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-percent fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Eggs Sold</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><span data-plugin="counterup"><?= number_format($totalEggsSold) ?></span></div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-egg fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Revenue Overview</h6>
                <form method="POST" class="form-inline">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">
                    <select class="form-control form-control-sm" name="timeframe" onchange="this.form.submit()">
                        <option value="daily" <?= $timeframe === 'daily' ? 'selected' : '' ?>>Daily</option>
                        <option value="weekly" <?= $timeframe === 'weekly' ? 'selected' : '' ?>>Weekly</option>
                        <option value="monthly" <?= $timeframe === 'monthly' ? 'selected' : '' ?>>Monthly</option>
                        <option value="quarterly" <?= $timeframe === 'quarterly' ? 'selected' : '' ?>>Quarterly</option>
                        <option value="yearly" <?= $timeframe === 'yearly' ? 'selected' : '' ?>>Yearly</option>
                    </select>
                </form>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Revenue by Size</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4">
                    <canvas id="revenuePieChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <?php foreach ($sizeLabels as $index => $label): ?>
                    <span class="mr-2">
                        <i class="fas fa-circle" style="color: <?= getChartColor($index) ?>"></i> <?= special_chars($label) ?>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary">Recent Transactions</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered" id="salesTable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Transaction ID</th>
                        <th>Items</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentTransactions as $transaction): ?>
                    <tr>
                        <td><?= !empty($transaction['created_at']) ? date('M d, Y', strtotime($transaction['created_at'])) : 'N/A' ?></td>
                        <td>#TX-<?= $transaction['transaction_id'] ?></td>
                        <td><?= special_chars($transaction['items']) ?></td>
                        <td>$<?= number_format($transaction['total'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentTransactions)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No transactions found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= asset('vendor/chart.js/Chart.min.js') ?>"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    var ctx = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($trendLabels) ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= json_encode($trendData) ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                borderColor: 'rgba(78, 115, 223, 1)',
                pointRadius: 4,
                pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                pointBorderColor: '#fff',
                pointHoverRadius: 5,
                tension: 0.3
            }]
        },
        options: {
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });

    // Revenue Pie Chart
    var ctx2 = document.getElementById('revenuePieChart').getContext('2d');
    var revenuePieChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($sizeLabels) ?>,
            datasets: [{
                data: <?= json_encode($sizeData) ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
                    '#e74a3b', '#6f42c1', '#fd7e14'
                ],
            }]
        },
        options: {
            maintainAspectRatio: false,
            cutout: '70%',
            plugins: {
                legend: {
                    display: false
                }
            }
        }
    });
});

<?php
// Helper function for consistent chart colors
function getChartColor($index) {
    $colors = [
        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', 
        '#e74a3b', '#6f42c1', '#fd7e14', '#20c997'
    ];
    return $colors[$index % count($colors)];
}
?>
</script>

<?php
$push_js = [
    'libs/chart.js/Chart.min.js'
];

$push_css = [
    'libs/chart.js/Chart.min.css'
];

$content = ob_get_clean();
include layouts('owner.main');
?>