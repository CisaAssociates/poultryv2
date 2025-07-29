<?php
require_once __DIR__ . '/../../config.php';

if (!is_logged_in() || !verify_token($_GET['token'])) {
    die();
}

session_write_close();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
set_time_limit(0);

$device_mac = isset($_GET['device_mac']) ? $_GET['device_mac'] : null;
$last_id = isset($_GET['last_id']) ? intval($_GET['last_id']) : 0;

if (!$device_mac) die();

$conn = db_connect();

while (true) {
    if (connection_aborted()) break;
    
    $query = "SELECT * FROM egg_data 
              WHERE mac = ? AND id > ? 
              ORDER BY id DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('si', $device_mac, $last_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $newRows = [];
    $newLastId = $last_id;
    
    while ($row = $result->fetch_assoc()) {
        $newRows[] = $row;
        if ($row['id'] > $newLastId) {
            $newLastId = $row['id'];
        }
    }
    
    if (!empty($newRows)) {
        $data = [
            'rows' => $newRows,
            'last_id' => $newLastId
        ];
        echo "event: newData\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();
        $last_id = $newLastId;
    }
    
    $result->close();
    sleep(1);
}

$conn->close();
?>