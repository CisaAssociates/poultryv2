<?php
// dashboard.php
require_once __DIR__ . '/../config.php';

$title = 'Dashboard';
$sub_title = 'Farm Overview';

$isOwner = ($user['role_id'] === 2); // Assuming role_id 2 is owner
ob_start();

$farm = [];
$stats = [
    'egg_production' => [],
    'sales' => [],
    'inventory' => [],
    'size_distribution' => [],
    'revenue' => []
];

if ($isOwner && isset($_SESSION['id'])) {
    $mysqli = db_connect();
    
    // Get selected farm information
    $farm_id = isset($_SESSION['selected_farm_id']) ? $_SESSION['selected_farm_id'] : $user['owner_farm_id'];
    if ($farm_id) {
        $stmt = $mysqli->prepare("SELECT * FROM farms WHERE farm_id = ?");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $farm = $result->fetch_assoc();
            
            // Check if farm has any devices
            $device_check = $mysqli->prepare("SELECT COUNT(*) as device_count FROM devices WHERE device_owner_id = ?");
            $device_check->bind_param("i", $farm_id);
            $device_check->execute();
            $device_result = $device_check->get_result()->fetch_assoc();
            
            if ($device_result['device_count'] == 0) {
                redirect('owner.error.no-devices');
            }
        }
    
        // Egg production stats (last 7 days)
        $stmt = $mysqli->prepare("
            SELECT DATE(created_at) AS date, COUNT(*) AS count
            FROM egg_data
            JOIN devices ON egg_data.mac = devices.device_mac
            WHERE devices.device_owner_id = ? 
            AND created_at >= CURDATE() - INTERVAL 7 DAY
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $productionResult = $stmt->get_result();
        while ($row = $productionResult->fetch_assoc()) {
            $stats['egg_production'][] = $row;
        }
        
        // Sales reports (last 30 days)
        $stmt = $mysqli->prepare("
            SELECT DATE(created_at) AS date, SUM(total) AS amount
            FROM transactions
            WHERE farm_id = ? 
            AND created_at >= CURDATE() - INTERVAL 30 DAY
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $salesResult = $stmt->get_result();
        while ($row = $salesResult->fetch_assoc()) {
            $stats['sales'][] = $row;
        }
        
        // Inventory levels
        $stmt = $mysqli->prepare("
            SELECT status, COUNT(*) AS count 
            FROM trays 
            WHERE farm_id = ?
            GROUP BY status
        ");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $inventoryResult = $stmt->get_result();
        while ($row = $inventoryResult->fetch_assoc()) {
            $stats['inventory'][] = $row;
        }
        
        // Egg size distribution
        $stmt = $mysqli->prepare("
            SELECT size, COUNT(*) AS count
            FROM egg_data
            JOIN devices ON egg_data.mac = devices.device_mac
            WHERE devices.device_owner_id = ?
            GROUP BY size
        ");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $sizeResult = $stmt->get_result();
        while ($row = $sizeResult->fetch_assoc()) {
            $stats['size_distribution'][] = $row;
        }
        
        // Revenue tracking (last 6 months)
        $stmt = $mysqli->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') AS month,
                SUM(total) AS revenue
            FROM transactions
            WHERE farm_id = ?
            AND created_at >= CURDATE() - INTERVAL 6 MONTH
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->bind_param("i", $farm_id);
        $stmt->execute();
        $revenueResult = $stmt->get_result();
        while ($row = $revenueResult->fetch_assoc()) {
            $stats['revenue'][] = $row;
        }
    }
}
?>

<style>
    .dashboard-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        margin-bottom: 24px;
        height: 100%;
    }
    
    .stat-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        text-align: center;
        transition: transform 0.3s;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-value {
        font-size: 2rem;
        font-weight: 700;
        margin: 10px 0;
    }
    
    .stat-label {
        color: #6c757d;
        font-size: 1rem;
    }
    
    .card-header {
        padding-bottom: 15px;
        margin-bottom: 15px;
        border-bottom: 1px solid #eaeaea;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        width: 100%;
    }
    
    .size-badge {
        display: inline-block;
        padding: 5px 12px;
        border-radius: 20px;
        font-weight: 600;
        margin-right: 8px;
        margin-bottom: 8px;
    }
    
    .size-pewee { background-color: #d1ecf1; color:rgb(51, 136, 151); }
    .size-pullets { background-color: #d1ecf1; color:rgb(98, 198, 216); }
    .size-small { background-color: #d1ecf1; color: #0c5460; }
    .size-medium { background-color: #d4edda; color: #155724; }
    .size-large { background-color: #fff3cd; color: #856404; }
    .size-jumbo { background-color: #fff3cd; color:rgb(163, 138, 63); }
    .size-xl { background-color: #f8d7da; color: #721c24; }
</style>

<!-- Stats Overview -->
<div class="row">
    <!-- Total Eggs -->
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Eggs</div>
            <div class="stat-value text-primary">
                <span data-plugin="counterup"><?= number_format(array_sum(array_column($stats['size_distribution'], 'count'))) ?></span>
            </div>
            <i class="mdi mdi-egg" style="font-size: 2.5rem; color: #727cf5;"></i>
        </div>
    </div>
    
    <!-- Available Inventory -->
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Available Inventory</div>
            <div class="stat-value text-success">
                <span data-plugin="counterup"><?php 
                    $available = 0;
                    foreach ($stats['inventory'] as $item) {
                        if ($item['status'] === 'published') {
                            $available = $item['count'];
                        }
                    }
                    echo number_format($available);
                ?></span>
            </div>
            <i class="mdi mdi-package-variant" style="font-size: 2.5rem; color: #0acf97;"></i>
        </div>
    </div>
    
    <!-- Monthly Revenue -->
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Monthly Revenue</div>
            <div class="stat-value text-warning">
                ₱<span data-plugin="counterup"><?= number_format(end($stats['revenue'])['revenue'] ?? 0, 2) ?></span>
            </div>
            <i class="mdi mdi-currency-php" style="font-size: 2.5rem; color: #ffbc00;"></i>
        </div>
    </div>
    
    <!-- Total Sales -->
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-label">Total Sales</div>
            <div class="stat-value text-danger">
                <span data-plugin="counterup"><?= number_format(array_sum(array_column($stats['sales'], 'amount') ?? 0)) ?></span>
            </div>
            <i class="mdi mdi-cart" style="font-size: 2.5rem; color: #fa5c7c;"></i>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Egg Production Chart -->
    <div class="col-lg-6">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0">Egg Production (Last 7 Days)</h5>
            </div>
            <div class="chart-container">
                <canvas id="productionChart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Sales Report -->
    <div class="col-lg-6">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0">Sales Report (Last 30 Days)</h5>
            </div>
            <div class="chart-container">
                <canvas id="salesChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Egg Size Distribution -->
    <div class="col-lg-5">
        <div class="dashboard-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Egg Size Distribution</h5>
            </div>
            <div class="chart-container">
                <canvas id="sizeChart"></canvas>
            </div>
            
            <div class="mt-4">
                <?php foreach ($stats['size_distribution'] as $size): ?>
                    <?php 
                        $badgeClass = 'size-medium';
                        if ($size['size'] === 'Small') $badgeClass = 'size-small';
                        elseif ($size['size'] === 'Large') $badgeClass = 'size-large';
                        elseif ($size['size'] === 'XL') $badgeClass = 'size-xl';
                        elseif ($size['size'] === 'Jumbo') $badgeClass = 'size-jumbo';
                        elseif ($size['size'] === 'Pewee') $badgeClass = 'size-pewee';
                        elseif ($size['size'] === 'Pullets') $badgeClass = 'size-pullets';
                    ?>
                    <span class="size-badge <?= $badgeClass ?>">
                        <?= $size['size'] ?>: <?= $size['count'] ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    
    <!-- Revenue Tracking -->
    <div class="col-lg-7">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0">Revenue Tracking (Last 6 Months)</h5>
            </div>
            <div class="chart-container">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <!-- Inventory Status -->
    <div class="col-lg-12">
        <div class="dashboard-card">
            <div class="card-header">
                <h5 class="mb-0">Inventory Status</h5>
            </div>
            <div class="row">
                <?php foreach ($stats['inventory'] as $status): ?>
                    <div class="col-md-3 mb-3">
                        <div class="stat-card">
                            <div class="stat-label text-capitalize"><?= $status['status'] ?></div>
                            <div class="stat-value"><?= number_format($status['count']) ?></div>
                            <i class="mdi mdi-pallet" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Egg Production Chart
        const productionCtx = document.getElementById('productionChart').getContext('2d');
        const productionChart = new Chart(productionCtx, {
            type: 'line',
            data: {
                labels: [<?= '"' . implode('","', array_column($stats['egg_production'], 'date')) . '"' ?>],
                datasets: [{
                    label: 'Eggs Produced',
                    data: [<?= implode(',', array_column($stats['egg_production'], 'count')) ?>],
                    backgroundColor: 'rgba(114, 124, 245, 0.1)',
                    borderColor: '#727cf5',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
        
        // Sales Chart
        const salesCtx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: [<?= '"' . implode('","', array_column($stats['sales'], 'date')) . '"' ?>],
                datasets: [{
                    label: 'Daily Sales (₱)',
                    data: [<?= implode(',', array_column($stats['sales'], 'amount')) ?>],
                    backgroundColor: 'rgba(10, 207, 151, 0.7)',
                    borderColor: '#0acf97',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
        
        // Size Distribution Chart
        const sizeCtx = document.getElementById('sizeChart').getContext('2d');
        const sizeChart = new Chart(sizeCtx, {
            type: 'doughnut',
            data: {
                labels: [<?= '"' . implode('","', array_column($stats['size_distribution'], 'size')) . '"' ?>],
                datasets: [{
                    data: [<?= implode(',', array_column($stats['size_distribution'], 'count')) ?>],
                    backgroundColor: [
                        '#d1ecf1', 
                        '#d4edda', 
                        '#fff3cd', 
                        '#f8d7da'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
        
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [<?= '"' . implode('","', array_column($stats['revenue'], 'month')) . '"' ?>],
                datasets: [{
                    label: 'Monthly Revenue (₱)',
                    data: [<?= implode(',', array_column($stats['revenue'], 'revenue')) ?>],
                    backgroundColor: 'rgba(255, 188, 0, 0.1)',
                    borderColor: '#ffbc00',
                    borderWidth: 3,
                    tension: 0.2,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '₱' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });
    });
</script>

<?php
$push_css = ['libs/chart.js/chart.min.css'];
$push_js = ['libs/chart.js/chart.min.js'];

$content = ob_get_clean();
include layouts('owner.main');
?>