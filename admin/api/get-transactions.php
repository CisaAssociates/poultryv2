<?php
require_once __DIR__ . '/../../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

$farm_id = isset($_GET['farm_id']) ? (int)$_GET['farm_id'] : 0;

if (!validate_farm_access($farm_id)) {
    echo json_encode(['error' => 'Access denied to farm']);
    exit;
}

$mysqli = db_connect();

// DataTables parameters
$draw = isset($_GET['draw']) ? (int)$_GET['draw'] : 0;
$start = isset($_GET['start']) ? (int)$_GET['start'] : 0;
$length = isset($_GET['length']) ? (int)$_GET['length'] : 10;
$searchValue = isset($_GET['search']['value']) ? trim($_GET['search']['value']) : '';
$orderColumn = isset($_GET['order'][0]['column']) ? (int)$_GET['order'][0]['column'] : 1;
$orderDir = isset($_GET['order'][0]['dir']) ? $_GET['order'][0]['dir'] : 'desc';

// Map DataTables columns to database columns
$columns = [
    't.transaction_id',
    't.created_at',
    'item_count',
    't.subtotal',
    't.tax',
    't.total'
];
$orderBy = $columns[$orderColumn] ?? 't.created_at';
$orderDir = in_array(strtolower($orderDir), ['asc', 'desc']) ? $orderDir : 'desc';

// Base query
$query = "
    SELECT t.*, COUNT(ti.id) AS item_count 
    FROM transactions t
    LEFT JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
    WHERE t.farm_id = ?
";

$params = [$farm_id];
$types = 'i';
$where = '';

// Search filter
if (!empty($searchValue)) {
    $where = " AND (";
    $where .= "t.transaction_id LIKE ?";
    $where .= " OR DATE_FORMAT(t.created_at, '%Y-%m-%d %H:%i:%s') LIKE ?";
    $where .= " OR t.subtotal LIKE ?";
    $where .= " OR t.tax LIKE ?";
    $where .= " OR t.total LIKE ?";
    $where .= ")";
    
    $searchPattern = "%$searchValue%";
    $params = array_merge($params, array_fill(0, 5, $searchPattern));
    $types .= str_repeat('s', 5);
}

// Total records count
$countQuery = "SELECT COUNT(DISTINCT t.transaction_id) AS total FROM transactions t WHERE t.farm_id = ?";
$countStmt = $mysqli->prepare($countQuery);
$countStmt->bind_param("i", $farm_id);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$recordsTotal = $countResult['total'] ?? 0;

// Filtered records count
$filteredQuery = "SELECT COUNT(DISTINCT t.transaction_id) AS total 
    FROM transactions t
    LEFT JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
    WHERE t.farm_id = ? $where";
    
$filteredStmt = $mysqli->prepare($filteredQuery);
if (!empty($searchValue)) {
    $filteredStmt->bind_param($types, ...$params);
} else {
    $filteredStmt->bind_param("i", $farm_id);
}
$filteredStmt->execute();
$filteredResult = $filteredStmt->get_result()->fetch_assoc();
$recordsFiltered = $filteredResult['total'] ?? 0;

// Data query with ordering and pagination
$dataQuery = "$query $where GROUP BY t.transaction_id ORDER BY $orderBy $orderDir LIMIT ? OFFSET ?";

$params[] = $length;
$params[] = $start;
$types .= 'ii';

$dataStmt = $mysqli->prepare($dataQuery);
$dataStmt->bind_param($types, ...$params);
$dataStmt->execute();
$result = $dataStmt->get_result();

$transactions = [];
while ($row = $result->fetch_assoc()) {
    $transactions[] = $row;
}

// Build DataTables response
$response = [
    'draw' => $draw,
    'recordsTotal' => $recordsTotal,
    'recordsFiltered' => $recordsFiltered,
    'data' => $transactions
];

echo json_encode($response);