<?php
require_once __DIR__ . '/../../config.php';

// Verify user authentication and CSRF token
if (!is_logged_in() || !verify_token($_POST['token'])) {
    header('HTTP/1.1 403 Forbidden');
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Get filter parameters
$start_date = $_POST['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$end_date = $_POST['end_date'] ?? date('Y-m-d');
$farm_id = isset($_POST['farm_id']) && $_POST['farm_id'] !== '' ? intval($_POST['farm_id']) : null;

$conn = db_connect();

// Build query parameters
$params = [$start_date, $end_date];
$farm_condition = '';
if (!empty($farm_id)) {
    $farm_condition = " AND d.device_owner_id = ?";
    $params[] = $farm_id;
}

// Fetch analytics data with size mapping
$sql = "SELECT 
            DATE(e.created_at) AS date, 
            AVG(e.egg_weight) AS avg_weight,
            COUNT(e.id) AS total_eggs,
            f.farm_name,
            e.size  // Actual size category
        FROM egg_data e
        INNER JOIN devices d ON e.mac = d.device_mac
        LEFT JOIN farms f ON d.device_owner_id = f.farm_id
        WHERE DATE(e.created_at) BETWEEN ? AND ?
        $farm_condition
        GROUP BY DATE(e.created_at), f.farm_name
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

// Prepare data arrays
$dates = [];
$weights = [];
$counts = [];
$size_categories = [];  // For category counts

foreach ($analytics_data as $row) {
    $dates[] = $row['date'];
    $weights[] = (float)$row['avg_weight'];
    $counts[] = (int)$row['total_eggs'];
    
    // Collect size categories for distribution
    $size_categories[] = $row['size'];
}

// Calculate size distribution
$size_distribution = array_count_values($size_categories);
$size_labels = array_keys($size_distribution);
$size_counts = array_values($size_distribution);

// Fetch summary data
$summary_sql = "SELECT 
                COUNT(e.id) AS total_eggs,
                AVG(e.egg_weight) AS avg_weight,
                MIN(e.egg_weight) AS min_weight,
                MAX(e.egg_weight) AS max_weight
            FROM egg_data e
            INNER JOIN devices d ON e.mac = d.device_mac
            WHERE DATE(e.created_at) BETWEEN ? AND ?
            $farm_condition";

$summary_stmt = $conn->prepare($summary_sql);
if ($farm_condition) {
    $summary_stmt->bind_param($types, ...$params);
} else {
    $summary_stmt->bind_param('ss', ...$params);
}
$summary_stmt->execute();
$summary_result = $summary_stmt->get_result();
$summary = $summary_result->fetch_assoc();

// Close connection
$conn->close();

// Return all data as JSON
header('Content-Type: application/json');
echo json_encode([
    'analytics_data' => $analytics_data,
    'dates' => $dates,
    'weights' => $weights,
    'counts' => $counts,
    'summary' => $summary,
    'size_labels' => $size_labels,
    'size_counts' => $size_counts
]);
?>