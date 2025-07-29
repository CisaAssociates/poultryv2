<?php
require_once __DIR__ . '/../../config.php';

// Verify user authentication
if (!is_logged_in()) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
set_time_limit(0);

$params = [
    'start_date' => $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days')),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d'),
    'farm_id' => isset($_GET['farm_id']) && $_GET['farm_id'] !== '' ? intval($_GET['farm_id']) : null
];

$conn = db_connect();
$last_id = 0;

// Get initial max ID
$query = "SELECT MAX(id) AS max_id FROM egg_data";
$result = $conn->query($query);
if ($result && $row = $result->fetch_assoc()) {
    $last_id = $row['max_id'] ?? 0;
}

while (true) {
    if (connection_aborted()) break;
    
    // Check for new egg data
    $query = "SELECT MAX(id) AS max_id FROM egg_data";
    $result = $conn->query($query);
    $current_max = $result->fetch_assoc()['max_id'] ?? 0;
    
    if ($current_max > $last_id) {
        // Notify client to refresh data
        echo "event: update\n";
        echo "data: {}\n\n";
        ob_flush();
        flush();
        $last_id = $current_max;
    }
    
    sleep(5); // Check every 5 seconds
}

$conn->close();
?>