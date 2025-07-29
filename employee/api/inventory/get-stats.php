<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

// Verify token
if (!verify_token($_POST['token'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid token']);
    exit;
}

$farm_id = (int)$_POST['farm_id'];
$mysqli = db_connect();

// Get total eggs count
$query = "SELECT SUM(stock_count) AS total_eggs FROM trays WHERE farm_id = ? AND status = 'published'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$total_eggs = $result->fetch_assoc()['total_eggs'] ?? 0;

// Get published trays count
$query = "SELECT COUNT(*) AS published_trays FROM trays WHERE farm_id = ? AND status = 'published'";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$published_trays = $result->fetch_assoc()['published_trays'] ?? 0;

// Get sold this week count
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$endOfWeek = date('Y-m-d', strtotime('sunday this week'));
$query = "SELECT COUNT(*) AS sold_this_week FROM trays 
          WHERE farm_id = ? 
          AND status = 'sold' 
          AND sold_at BETWEEN ? AND ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("iss", $farm_id, $startOfWeek, $endOfWeek);
$stmt->execute();
$result = $stmt->get_result();
$sold_this_week = $result->fetch_assoc()['sold_this_week'] ?? 0;

// Get expiring soon count
$threeDaysLater = date('Y-m-d', strtotime('+3 days'));
$query = "SELECT COUNT(*) AS expiring_soon FROM trays 
          WHERE farm_id = ? 
          AND status = 'published' 
          AND expired_at <= ?";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("is", $farm_id, $threeDaysLater);
$stmt->execute();
$result = $stmt->get_result();
$expiring_soon = $result->fetch_assoc()['expiring_soon'] ?? 0;

// Get size distribution
$query = "SELECT size, SUM(stock_count) AS count 
          FROM trays 
          WHERE farm_id = ? AND status = 'published' 
          GROUP BY size";
$stmt = $mysqli->prepare($query);
$stmt->bind_param("i", $farm_id);
$stmt->execute();
$result = $stmt->get_result();
$size_distribution = [];
while ($row = $result->fetch_assoc()) {
    $size_distribution[$row['size']] = (int)$row['count'];
}

// Get weekly movement data
$weekly_movement = [
    'published' => [],
    'sold' => [],
    'dates' => []
];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $weekly_movement['dates'][] = $date;
    
    // Published count
    $query = "SELECT COUNT(*) AS count 
              FROM trays 
              WHERE farm_id = ? 
              AND DATE(published_at) = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("is", $farm_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $weekly_movement['published'][] = (int)$result->fetch_assoc()['count'] ?? 0;
    
    // Sold count
    $query = "SELECT COUNT(*) AS count 
              FROM trays 
              WHERE farm_id = ? 
              AND status = 'sold' 
              AND DATE(sold_at) = ?";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("is", $farm_id, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $weekly_movement['sold'][] = (int)$result->fetch_assoc()['count'] ?? 0;
}

echo json_encode([
    'success' => true,
    'total_eggs' => (int)$total_eggs,
    'published_trays' => (int)$published_trays,
    'sold_this_week' => (int)$sold_this_week,
    'expiring_soon' => (int)$expiring_soon,
    'size_distribution' => $size_distribution,
    'weekly_movement' => $weekly_movement
]);