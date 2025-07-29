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

// DataTables parameters
$draw = (int)$_POST['draw'];
$start = (int)$_POST['start'];
$length = (int)$_POST['length'];
$search = $_POST['search']['value'] ?? '';
$orderColumn = $_POST['order'][0]['column'] ?? 0;
$orderDir = $_POST['order'][0]['dir'] ?? 'asc';

// Columns mapping
$columns = [
    'tray_id',
    'size',
    'stock_count',
    'price',
    'published_at',
    'status'
];
$orderBy = $columns[$orderColumn] ?? 'tray_id';

// Build query
$query = "SELECT SQL_CALC_FOUND_ROWS tray_id, size, stock_count, price, published_at, status 
          FROM trays 
          WHERE farm_id = ? 
          AND status = 'published'";

$params = [$farm_id];
$types = "i";

// Add search filter
if (!empty($search)) {
    $query .= " AND (tray_id LIKE ? OR size LIKE ? OR stock_count LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Add sorting
$query .= " ORDER BY $orderBy $orderDir LIMIT ?, ?";
$params[] = $start;
$params[] = $length;
$types .= "ii";

// Prepare and execute
$stmt = $mysqli->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get total records
$totalResult = $mysqli->query("SELECT FOUND_ROWS()");
$totalRecords = $totalResult->fetch_row()[0];

// Format response
$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = [
        'tray_id' => $row['tray_id'],
        'size' => $row['size'],
        'stock_count' => (int)$row['stock_count'],
        'price' => (float)$row['price'],
        'published_at' => $row['published_at'],
        'status' => $row['status']
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $totalRecords,
    'data' => $data
]);