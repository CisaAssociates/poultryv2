<?php
require_once __DIR__ . '/../../../config.php';

header('Content-Type: application/json');

if (!is_logged_in() || $user['role_id'] != 3) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Verify token
if (!verify_token($_POST['token'] ?? '')) {
    echo json_encode(['error' => 'Invalid security token']);
    exit;
}

$farm_id = $_POST['farm_id'] ?? $user['farm_id'];
$status = $_POST['status'] ?? 'pending';

// Validate status
$allowed_statuses = ['pending', 'approved', 'rejected'];
if (!in_array($status, $allowed_statuses)) {
    $status = 'pending';
}

$mysqli = db_connect();

// Get request parameters for DataTables
$start = $_POST['start'] ?? 0;
$length = $_POST['length'] ?? 25;
$search = $_POST['search']['value'] ?? '';
$order_column = $_POST['order'][0]['column'] ?? 0;
$order_dir = $_POST['order'][0]['dir'] ?? 'desc';

// Column mapping for ordering
$columns = [
    0 => 'id',
    1 => 'device_serial_no',
    2 => 'size',
    3 => 'egg_weight',
    4 => 'created_at',
    5 => 'validation_status'
];

$order_by = isset($columns[$order_column]) ? $columns[$order_column] : 'id';
$order_dir = in_array(strtolower($order_dir), ['asc', 'desc']) ? $order_dir : 'desc';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS egg_data.*, devices.device_serial_no
          FROM egg_data
          INNER JOIN devices ON egg_data.mac = devices.device_mac
          WHERE devices.device_owner_id = ?
          AND egg_data.validation_status = ?";

$count_query = "SELECT COUNT(*) AS total 
                FROM egg_data
                INNER JOIN devices ON egg_data.mac = devices.device_mac
                WHERE devices.device_owner_id = ?
                AND egg_data.validation_status = ?";

// Add search filter
if (!empty($search)) {
    $search = "%$search%";
    $query .= " AND (egg_data.id LIKE ? OR devices.device_serial_no LIKE ? OR egg_data.size LIKE ?)";
    $count_query .= " AND (egg_data.id LIKE ? OR devices.device_serial_no LIKE ? OR egg_data.size LIKE ?)";
}

// Add ordering
$query .= " ORDER BY $order_by $order_dir LIMIT ?, ?";

// Prepare and execute main query
$stmt = $mysqli->prepare($query);
if (!$stmt) {
    error_log("Prepare failed: " . $mysqli->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}

// Bind parameters
$param_types = "is";
$params = [$farm_id, $status];

if (!empty($search)) {
    $param_types .= "sss";
    array_push($params, $search, $search, $search);
}

$param_types .= "ii";
array_push($params, $start, $length);

$stmt->bind_param($param_types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$eggs = $result->fetch_all(MYSQLI_ASSOC);

// Get total filtered count
$count_stmt = $mysqli->prepare($count_query);
if (!$count_stmt) {
    error_log("Count prepare failed: " . $mysqli->error);
    echo json_encode(['error' => 'Database error']);
    exit;
}

$count_params = [$farm_id, $status];
$count_types = "is";

if (!empty($search)) {
    $count_types .= "sss";
    array_push($count_params, $search, $search, $search);
}

$count_stmt->bind_param($count_types, ...$count_params);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$filtered_count = $count_result['total'] ?? 0;

// Get total records count
$total_stmt = $mysqli->prepare("SELECT COUNT(*) AS total FROM egg_data");
$total_stmt->execute();
$total_result = $total_stmt->get_result()->fetch_assoc();
$total_count = $total_result['total'] ?? 0;

// Return JSON response for DataTables
echo json_encode([
    'draw' => intval($_POST['draw'] ?? 1),
    'recordsTotal' => intval($total_count),
    'recordsFiltered' => intval($filtered_count),
    'data' => $eggs
]);