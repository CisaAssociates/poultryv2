<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$title = 'Farm Analytics Dashboard';
$sub_title = 'Advanced Reporting & Insights';
ob_start();

// Get farm_id from session
$conn = db_connect();
$farm_id = $_SESSION['selected_farm_id'] ?? null;
if (!$farm_id) {
    // Show error message if no farm is selected
    echo '<div class="alert alert-danger">No farm selected. Please select a farm first.</div>';
    $content = ob_get_clean();
    require_once layouts('owner.main');
    exit;
}

// Get current farm name
$farm_result = $conn->prepare("SELECT farm_name FROM farms WHERE farm_id = ?");
$farm_result->bind_param("i", $farm_id);
$farm_result->execute();
$result = $farm_result->get_result();
$current_farm = $result->fetch_assoc();
$current_farm_name = $current_farm ? $current_farm['farm_name'] : 'Unknown Farm';

// Get all farms for reference
$farms = [];
$owner_id = $user['id'];
$all_farms_result = $conn->prepare("SELECT farm_id, farm_name FROM farms WHERE owner_id = ?");
$all_farms_result->bind_param("i", $owner_id);
$all_farms_result->execute();
$result = $all_farms_result->get_result();
if ($result) {
    $farms = $result->fetch_all(MYSQLI_ASSOC);
}

// Egg size categories
$size_categories = ['Pewee', 'Pullets', 'Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];

// Fetch production data for preview
$preview_data = [];
$preview_summary = [
    'total_eggs' => 0,
    'avg_weight' => 0,
    'size_distribution' => [],
    'daily_avg' => 0,
    'days' => []
];

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

$query = "SELECT 
        DATE(e.created_at) AS date, 
        e.size,
        COUNT(*) AS quantity,
        AVG(e.egg_weight) AS avg_weight
      FROM egg_data e
      INNER JOIN devices d ON e.mac = d.device_mac
      WHERE d.device_owner_id = ? 
        AND e.created_at BETWEEN ? AND ?
      GROUP BY DATE(e.created_at), e.size
      ORDER BY DATE(e.created_at) DESC";

$stmt = $conn->prepare($query);
$start_date = date('Y-m-01') . ' 00:00:00';
$end_date = date('Y-m-t') . ' 23:59:59';

$stmt->bind_param('iss', $farm_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $preview_data[] = $row;
    }

    // Calculate summary
    if (!empty($preview_data)) {
        $days = [];
        $size_totals = array_fill_keys($size_categories, 0);
        $total_weight = 0;
        $total_eggs = 0;

        foreach ($preview_data as $row) {
            $preview_summary['total_eggs'] += $row['quantity'];
            $total_weight += $row['avg_weight'] * $row['quantity'];

            if (isset($size_totals[$row['size']])) {
                $size_totals[$row['size']] += $row['quantity'];
            }

            if (!in_array($row['date'], $days)) {
                $days[] = $row['date'];
            }
        }

        // Calculate size distribution
        foreach ($size_totals as $size => $total) {
            $preview_summary['size_distribution'][$size] = [
                'count' => $total,
                'percentage' => round(($total / $preview_summary['total_eggs']) * 100, 1)
            ];
        }

        // Calculate averages
        $day_count = count($days);
        $preview_summary['daily_avg'] = $day_count > 0 ? round($preview_summary['total_eggs'] / $day_count, 0) : 0;
        $preview_summary['avg_weight'] = $preview_summary['total_eggs'] > 0 ? round($total_weight / $preview_summary['total_eggs'], 2) : 0;
    }


// Prepare data for charts
$size_labels = [];
$size_data = [];
$trend_labels = [];
$trend_data = [];

if (!empty($preview_summary['size_distribution'])) {
    foreach ($preview_summary['size_distribution'] as $size => $data) {
        $size_labels[] = $size;
        $size_data[] = $data['percentage'];
    }
}

// Prepare trend data (last 7 days)
if (!empty($preview_data)) {
    $daily_totals = [];
    foreach ($preview_data as $row) {
        if (!isset($daily_totals[$row['date']])) {
            $daily_totals[$row['date']] = 0;
        }
        $daily_totals[$row['date']] += $row['quantity'];
    }

    // Sort by date
    ksort($daily_totals);

    // Get last 7 days
    $trend_data = array_slice(array_values($daily_totals), -7, 7, true);
    $trend_labels = array_slice(array_keys($daily_totals), -7, 7, true);

    // Format dates for display
    foreach ($trend_labels as &$label) {
        $label = !empty($label) ? date('M d', strtotime($label)) : 'N/A';
    }
}

// Find top size
$top_size = 'N/A';
$top_percentage = 0;
if (!empty($preview_summary['size_distribution'])) {
    foreach ($preview_summary['size_distribution'] as $size => $data) {
        if ($data['percentage'] > $top_percentage) {
            $top_size = $size;
            $top_percentage = $data['percentage'];
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    if (!verify_token($_POST['token'])) {
        $_SESSION['error'] = 'Invalid token. Please try again.';
    }

    // Get form data
    $report_type = $_POST['report_type'] ?? 'production';
    $date_range = $_POST['date_range'] ?? 'this_month';
    $output_format = $_POST['output_format'] ?? 'pdf';
    $include_charts = isset($_POST['include_charts']);

    // Calculate date range
    $today = new DateTime();
    $start_date = null;
    $end_date = null;

    switch ($date_range) {
        case 'today':
            $start_date = $today->format('Y-m-d');
            $end_date = $today->format('Y-m-d');
            break;
        case 'yesterday':
            $today->modify('-1 day');
            $start_date = $today->format('Y-m-d');
            $end_date = $today->format('Y-m-d');
            break;
        case 'this_week':
            $start_date = $today->modify('monday this week')->format('Y-m-d');
            $end_date = $today->modify('sunday this week')->format('Y-m-d');
            break;
        case 'last_week':
            $start_date = $today->modify('monday last week')->format('Y-m-d');
            $end_date = $today->modify('sunday last week')->format('Y-m-d');
            break;
        case 'this_month':
            $start_date = $today->format('Y-m-01');
            $end_date = $today->format('Y-m-t');
            break;
        case 'last_month':
            $start_date = $today->modify('first day of last month')->format('Y-m-d');
            $end_date = $today->modify('last day of last month')->format('Y-m-d');
            break;
        case 'this_quarter':
            $month = $today->format('n');
            if ($month < 4) {
                $start_date = $today->format('Y-01-01');
                $end_date = $today->format('Y-03-31');
            } elseif ($month < 7) {
                $start_date = $today->format('Y-04-01');
                $end_date = $today->format('Y-06-30');
            } elseif ($month < 10) {
                $start_date = $today->format('Y-07-01');
                $end_date = $today->format('Y-09-30');
            } else {
                $start_date = $today->format('Y-10-01');
                $end_date = $today->format('Y-12-31');
            }
            break;
        case 'last_quarter':
            $today->modify('-3 months');
            $month = $today->format('n');
            if ($month < 4) {
                $start_date = $today->format('Y-01-01');
                $end_date = $today->format('Y-03-31');
            } elseif ($month < 7) {
                $start_date = $today->format('Y-04-01');
                $end_date = $today->format('Y-06-30');
            } elseif ($month < 10) {
                $start_date = $today->format('Y-07-01');
                $end_date = $today->format('Y-09-30');
            } else {
                $start_date = $today->format('Y-10-01');
                $end_date = $today->format('Y-12-31');
            }
            break;
        case 'this_year':
            $start_date = $today->format('Y-01-01');
            $end_date = $today->format('Y-12-31');
            break;
        case 'last_year':
            $year = $today->format('Y') - 1;
            $start_date = $year . '-01-01';
            $end_date = $year . '-12-31';
            break;
        case 'custom':
            $start_date = $_POST['start_date'] ?? date('Y-m-d');
            $end_date = $_POST['end_date'] ?? date('Y-m-d');

            // Validate dates
            if (!strtotime($start_date) || !strtotime($end_date)) {
                $_SESSION['error'] = 'Invalid date format';
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            }
            break;
        default:
            $start_date = $today->format('Y-m-01');
            $end_date = $today->format('Y-m-t');
    }

    // Fetch data based on report type
    $owner_id = $_SESSION['id'];
    $report_data = [];
    $summary_data = [];

    // Get selected farm ID if available
    $selected_farm_id = isset($_SESSION['selected_farm_id']) ? $_SESSION['selected_farm_id'] : null;
    
    switch ($report_type) {
        case 'production':
            $report_data = fetchProductionReport($owner_id, $start_date, $end_date, $selected_farm_id);
            $summary_data = calculateProductionSummary($report_data);
            break;
        case 'sales':
            $report_data = fetchSalesReport($owner_id, $start_date, $end_date, $selected_farm_id);
            $summary_data = calculateSalesSummary($report_data);
            break;
        case 'inventory':
            $report_data = fetchInventoryReport($owner_id, $selected_farm_id);
            $summary_data = calculateInventorySummary($report_data);
            break;
        case 'financial':
            $report_data = fetchFinancialReport($owner_id, $start_date, $end_date, $selected_farm_id);
            $summary_data = calculateFinancialSummary($report_data);
            break;
        default:
            $report_data = fetchProductionReport($owner_id, $start_date, $end_date, $selected_farm_id);
            $summary_data = calculateProductionSummary($report_data);
    }

    // Generate report based on format
    if ($output_format === 'pdf') {
        generatePDFReport($report_type, $report_data, $summary_data, $start_date, $end_date, $include_charts);
    } else {
        generateExcelReport($report_type, $report_data, $summary_data, $start_date, $end_date);
    }
    exit;
}

// Fetch production data from database
function fetchProductionReport($owner_id, $start_date, $end_date, $selected_farm_id = null)
{
    $mysqli = db_connect();

    if ($selected_farm_id) {
        // Filter by specific farm
        $query = "SELECT 
                DATE(e.created_at) AS date, 
                e.size,
                COUNT(*) AS quantity,
                AVG(e.egg_weight) AS avg_weight
              FROM egg_data e
              INNER JOIN devices d ON e.mac = d.device_mac
              WHERE d.device_owner_id = ? 
                AND e.created_at BETWEEN ? AND ?
              GROUP BY DATE(e.created_at), e.size
              ORDER BY DATE(e.created_at) DESC, e.size";
              
        $stmt = $mysqli->prepare($query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $selected_farm_id, $start_datetime, $end_datetime);
    } else {
        // Filter by all farms owned by the user
        $query = "SELECT 
                DATE(e.created_at) AS date, 
                e.size,
                COUNT(*) AS quantity,
                AVG(e.egg_weight) AS avg_weight
              FROM egg_data e
              INNER JOIN devices d ON e.mac = d.device_mac
              INNER JOIN farms f ON d.device_owner_id = f.farm_id 
              WHERE f.owner_id = ? 
                AND e.created_at BETWEEN ? AND ?
              GROUP BY DATE(e.created_at), e.size
              ORDER BY DATE(e.created_at) DESC, e.size";
              
        $stmt = $mysqli->prepare($query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $owner_id, $start_datetime, $end_datetime);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

// Calculate production summary
function calculateProductionSummary($data)
{
    $summary = [
        'total_eggs' => 0,
        'avg_weight' => 0,
        'size_distribution' => [],
        'daily_avg' => 0,
        'days' => []
    ];

    $days = [];
    $size_totals = [];
    $total_weight = 0;

    foreach ($data as $row) {
        $summary['total_eggs'] += $row['quantity'];
        $total_weight += $row['avg_weight'] * $row['quantity'];

        if (!isset($size_totals[$row['size']])) {
            $size_totals[$row['size']] = 0;
        }
        $size_totals[$row['size']] += $row['quantity'];

        if (!in_array($row['date'], $summary['days'])) {
            $summary['days'][] = $row['date'];
        }
    }

    foreach ($size_totals as $size => $total) {
        $summary['size_distribution'][$size] = [
            'count' => $total,
            'percentage' => round(($total / $summary['total_eggs']) * 100, 1)
        ];
    }

    $day_count = count($summary['days']);
    $summary['daily_avg'] = $day_count > 0 ? round($summary['total_eggs'] / $day_count, 0) : 0;
    
    $summary['avg_weight'] = $summary['total_eggs'] > 0 
        ? round($total_weight / $summary['total_eggs'], 2) 
        : 0;

    return $summary;
}

// Fetch sales data from database
function fetchSalesReport($owner_id, $start_date, $end_date, $selected_farm_id = null)
{
    $mysqli = db_connect();

    if ($selected_farm_id) {
        // Filter by specific farm
        $query = "SELECT 
                t.transaction_id,
                t.created_at AS sale_date,
                ti.size,
                ti.price,
                ti.quantity,
                ti.total AS subtotal,
                t.tax,
                t.total AS grand_total
              FROM transactions t
              INNER JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
              INNER JOIN farms f ON t.farm_id = f.farm_id
              WHERE t.farm_id = ?
                AND t.created_at BETWEEN ? AND ?
              ORDER BY t.created_at DESC";

        $stmt = $mysqli->prepare($query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $selected_farm_id, $start_datetime, $end_datetime);
    } else {
        // Filter by all farms owned by the user
        $query = "SELECT 
                t.transaction_id,
                t.created_at AS sale_date,
                ti.size,
                ti.price,
                ti.quantity,
                ti.total AS subtotal,
                t.tax,
                t.total AS grand_total
              FROM transactions t
              INNER JOIN transaction_items ti ON t.transaction_id = ti.transaction_id
              INNER JOIN farms f ON t.farm_id = f.farm_id
              WHERE f.owner_id = ?
                AND t.created_at BETWEEN ? AND ?
              ORDER BY t.created_at DESC";

        $stmt = $mysqli->prepare($query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $owner_id, $start_datetime, $end_datetime);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

// Calculate sales summary
function calculateSalesSummary($data)
{
    $summary = [
        'total_sales' => 0,
        'total_quantity' => 0,
        'avg_price' => 0,
        'size_sales' => [],
        'daily_sales' => []
    ];

    foreach ($data as $row) {
        $summary['total_sales'] += $row['grand_total'];
        $summary['total_quantity'] += $row['quantity'];

        $date = !empty($row['sale_date']) ? date('Y-m-d', strtotime($row['sale_date'])) : date('Y-m-d');
        if (!isset($summary['daily_sales'][$date])) {
            $summary['daily_sales'][$date] = 0;
        }
        $summary['daily_sales'][$date] += $row['grand_total'];

        if (!isset($summary['size_sales'][$row['size']])) {
            $summary['size_sales'][$row['size']] = [
                'quantity' => 0,
                'revenue' => 0
            ];
        }
        $summary['size_sales'][$row['size']]['quantity'] += $row['quantity'];
        $summary['size_sales'][$row['size']]['revenue'] += $row['subtotal'];
    }

    // Calculate average price
    $summary['avg_price'] = $summary['total_quantity'] > 0
        ? round($summary['total_sales'] / $summary['total_quantity'], 2)
        : 0;

    return $summary;
}

// Fetch inventory data
function fetchInventoryReport($owner_id, $selected_farm_id = null)
{
    $mysqli = db_connect();

    if ($selected_farm_id) {
        // Filter by specific farm
        $query = "SELECT 
                t.size,
                COUNT(*) AS tray_count,
                SUM(t.stock_count) AS egg_count,
                ts.default_price
              FROM trays t
              INNER JOIN tray_settings ts ON t.farm_id = ts.farm_id AND t.size = ts.size
              WHERE t.farm_id = ?
                AND t.status IN ('pending', 'published')
              GROUP BY t.size";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $selected_farm_id);
    } else {
        // Filter by all farms owned by the user
        $query = "SELECT 
                t.size,
                COUNT(*) AS tray_count,
                SUM(t.stock_count) AS egg_count,
                ts.default_price
              FROM trays t
              INNER JOIN tray_settings ts ON t.farm_id = ts.farm_id AND t.size = ts.size
              INNER JOIN farms f ON t.farm_id = f.farm_id
              WHERE f.owner_id = ?
                AND t.status IN ('pending', 'published')
              GROUP BY t.size";

        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $owner_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }

    return $data;
}

// Calculate inventory summary
function calculateInventorySummary($data)
{
    $summary = [
        'total_eggs' => 0,
        'total_value' => 0,
        'size_distribution' => []
    ];

    foreach ($data as $row) {
        $summary['total_eggs'] += $row['egg_count'];
        $value = $row['egg_count'] * $row['default_price'];
        $summary['total_value'] += $value;

        $summary['size_distribution'][$row['size']] = [
            'egg_count' => $row['egg_count'],
            'tray_count' => $row['tray_count'],
            'value' => $value
        ];
    }

    return $summary;
}

// Fetch financial data
function fetchFinancialReport($owner_id, $start_date, $end_date, $selected_farm_id = null)
{
    $mysqli = db_connect();

    if ($selected_farm_id) {
        // Filter by specific farm
        // Get revenue data
        $revenue_query = "SELECT 
                            SUM(t.total) AS total_revenue
                          FROM transactions t
                          WHERE t.farm_id = ?
                            AND t.created_at BETWEEN ? AND ?";

        $stmt = $mysqli->prepare($revenue_query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $selected_farm_id, $start_datetime, $end_datetime);
        $stmt->execute();
        $revenue_result = $stmt->get_result();
        $revenue = $revenue_result->fetch_assoc();

        $cost_query = "SELECT 
                        SUM(e.salary) AS labor_costs,
                        SUM(feed.cost) AS feed_costs
                      FROM employees e
                      LEFT JOIN (
                        SELECT 
                            farm_id,
                            SUM(quantity * unit_price) AS cost
                        FROM feed_purchases
                        WHERE purchase_date BETWEEN ? AND ?
                            AND farm_id = ?
                        GROUP BY farm_id
                      ) feed ON e.farm_id = feed.farm_id
                      WHERE e.farm_id = ?";

        $stmt = $mysqli->prepare($cost_query);
        $stmt->bind_param("ssii", $start_date, $end_date, $selected_farm_id, $selected_farm_id);
        $stmt->execute();
        $cost_result = $stmt->get_result();
        $costs = $cost_result->fetch_assoc();
    } else {
        // Filter by all farms owned by the user
        // Get revenue data
        $revenue_query = "SELECT 
                            SUM(t.total) AS total_revenue
                          FROM transactions t
                          INNER JOIN farms f ON t.farm_id = f.farm_id
                          WHERE f.owner_id = ?
                            AND t.created_at BETWEEN ? AND ?";

        $stmt = $mysqli->prepare($revenue_query);
        $start_datetime = $start_date . ' 00:00:00';
        $end_datetime = $end_date . ' 23:59:59';
        $stmt->bind_param("iss", $owner_id, $start_datetime, $end_datetime);
        $stmt->execute();
        $revenue_result = $stmt->get_result();
        $revenue = $revenue_result->fetch_assoc();

        $cost_query = "SELECT 
                        SUM(e.salary) AS labor_costs,
                        SUM(feed.cost) AS feed_costs
                      FROM employees e
                      INNER JOIN farms f ON e.farm_id = f.farm_id
                      LEFT JOIN (
                        SELECT 
                            farm_id,
                            SUM(quantity * unit_price) AS cost
                        FROM feed_purchases
                        WHERE purchase_date BETWEEN ? AND ?
                        GROUP BY farm_id
                      ) feed ON f.farm_id = feed.farm_id
                      WHERE f.owner_id = ?";

        $stmt = $mysqli->prepare($cost_query);
        $stmt->bind_param("ssi", $start_date, $end_date, $owner_id);
        $stmt->execute();
        $cost_result = $stmt->get_result();
        $costs = $cost_result->fetch_assoc();
    }

    return [
        'revenue' => $revenue['total_revenue'] ?? 0,
        'labor_costs' => $costs['labor_costs'] ?? 0,
        'feed_costs' => $costs['feed_costs'] ?? 0,
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
}

// Calculate financial summary
function calculateFinancialSummary($data)
{
    $total_costs = $data['labor_costs'] + $data['feed_costs'];
    $net_profit = $data['revenue'] - $total_costs;
    $profit_margin = $data['revenue'] > 0 ? ($net_profit / $data['revenue']) * 100 : 0;

    return [
        'total_revenue' => $data['revenue'],
        'total_costs' => $total_costs,
        'net_profit' => $net_profit,
        'profit_margin' => round($profit_margin, 2),
        'labor_percentage' => $data['revenue'] > 0 ? round(($data['labor_costs'] / $data['revenue']) * 100, 2) : 0,
        'feed_percentage' => $data['revenue'] > 0 ? round(($data['feed_costs'] / $data['revenue']) * 100, 2) : 0
    ];
}

function generatePDFReport($type, $data, $summary, $start_date, $end_date, $include_charts)
{
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetCreator('Poultry Farm System');
    $pdf->SetAuthor('Farm Analytics');
    $pdf->SetTitle(ucfirst($type) . ' Report');
    $pdf->AddPage();

    // Header
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->Cell(0, 10, 'Poultry Farm ' . ucfirst($type) . ' Report', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Date Range: ' . $start_date . ' to ' . $end_date, 0, 1, 'C');
    $pdf->Ln(10);

    // Report content
    $pdf->SetFont('helvetica', 'B', 14);
    $pdf->Cell(0, 10, 'Summary', 0, 1);
    $pdf->SetFont('helvetica', '', 12);

    switch ($type) {
        case 'production':
            $html = '<table border="1" cellpadding="4">
                    <tr>
                        <th>Total Eggs</th>
                        <th>Daily Average</th>
                        <th>Avg. Weight</th>
                        <th>Top Size</th>
                    </tr>
                    <tr>
                        <td>' . number_format($summary['total_eggs']) . '</td>
                        <td>' . number_format($summary['daily_avg']) . '</td>
                        <td>' . number_format($summary['avg_weight'], 2) . 'g</td>
                        <td>' . array_search(max($summary['size_distribution']), $summary['size_distribution']) . '</td>
                    </tr>
                    </table>';
            $pdf->writeHTML($html, true, false, true, false, '');
            break;
    }

    $filename = "{$type}_report_{$start_date}_to_{$end_date}.pdf";
    $pdf->Output($filename, 'D');
    exit;
}

function generateExcelReport($type, $data, $summary, $start_date, $end_date)
{
    // Create new Spreadsheet object
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Set report header
    $sheet->setCellValue('A1', 'Poultry Farm ' . ucfirst($type) . ' Report');
    $sheet->mergeCells('A1:E1');
    $sheet->getStyle('A1')->getFont()->setBold(true);

    $sheet->setCellValue('A2', 'Date Range: ' . $start_date . ' to ' . $end_date);
    $sheet->mergeCells('A2:E2');

    // Add space
    $sheet->setCellValue('A3', '');

    // Start row counter
    $row = 4;

    switch ($type) {
        case 'production':
            // Set headers
            $sheet->setCellValue('A' . $row, 'Date');
            $sheet->setCellValue('B' . $row, 'Size');
            $sheet->setCellValue('C' . $row, 'Quantity');
            $sheet->setCellValue('D' . $row, 'Avg Weight (g)');
            $row++;

            // Add data
            foreach ($data as $record) {
                $sheet->setCellValue('A' . $row, $record['date']);
                $sheet->setCellValue('B' . $row, $record['size']);
                $sheet->setCellValue('C' . $row, $record['quantity']);
                $sheet->setCellValue('D' . $row, $record['avg_weight']);
                $row++;
            }

            // Add summary
            $row++;
            $sheet->setCellValue('A' . $row, 'Total Eggs:');
            $sheet->setCellValue('B' . $row, $summary['total_eggs']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Daily Average:');
            $sheet->setCellValue('B' . $row, $summary['daily_avg']);
            $row++;
            $sheet->setCellValue('A' . $row, 'Average Weight:');
            $sheet->setCellValue('B' . $row, $summary['avg_weight'] . 'g');
            break;

            // Add cases for other report types...
    }

    // Auto-size columns
    foreach (range('A', 'D') as $columnID) {
        $sheet->getColumnDimension($columnID)->setAutoSize(true);
    }

    // Create writer and output
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

    $filename = "{$type}_report_{$start_date}_to_{$end_date}.xlsx";

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Cache-Control: max-age=0');

    $writer->save('php://output');
    exit;
}
?>

<div class="row">
    <!-- Summary Cards -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-5 border-primary h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-primary text-uppercase mb-1">
                            Total Eggs
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?= number_format($preview_summary['total_eggs'] ?? 0) ?>
                        </div>
                        <div class="mt-2 text-xs text-muted">
                            <i class="fas fa-arrow-up text-success me-1"></i>
                            <span class="fw-semibold">12%</span> from last month
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-egg fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-5 border-success h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-success text-uppercase mb-1">
                            Daily Average
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?= number_format($preview_summary['daily_avg'] ?? 0) ?>
                        </div>
                        <div class="mt-2 text-xs text-muted">
                            <i class="fas fa-arrow-up text-success me-1"></i>
                            <span class="fw-semibold">8%</span> from last month
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-5 border-info h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-info text-uppercase mb-1">
                            Avg. Egg Weight
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?= $preview_summary['avg_weight'] ?? '0.0' ?>g
                        </div>
                        <div class="mt-2 text-xs text-muted">
                            <i class="fas fa-arrow-down text-danger me-1"></i>
                            <span class="fw-semibold">1.2%</span> from last month
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-weight fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-start border-5 border-warning h-100 py-2">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs fw-bold text-warning text-uppercase mb-1">
                            Top Size Category
                        </div>
                        <div class="h5 mb-0 fw-bold text-gray-800">
                            <?= $top_size ?>
                        </div>
                        <div class="mt-2 text-xs text-muted">
                            <?= $top_percentage ?>% of total production
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-star fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Charts -->
    <div class="col-lg-8">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold">Production Overview</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-7 mb-4 mb-lg-0">
                        <canvas id="productionTrendChart" height="200"></canvas>
                    </div>
                    <div class="col-lg-5">
                        <canvas id="sizeDistributionChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold">Detailed Production Data</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Size Category</th>
                                <th class="text-end">Quantity</th>
                                <th class="text-end">Avg Weight (g)</th>
                                <th class="text-end">Percentage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($preview_data)): ?>
                                <?php foreach ($preview_data as $row): ?>
                                    <tr>
                                        <td><?= $row['date'] ?></td>
                                        <td>
                                            <span class="badge bg-light text-dark p-2">
                                                <?= $row['size'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end"><?= number_format($row['quantity']) ?></td>
                                        <td class="text-end"><?= $row['avg_weight'] ?></td>
                                        <td class="text-end">
                                            <?= round(($row['quantity'] / $preview_summary['total_eggs']) * 100, 1) ?>%
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="py-4">
                                            <i class="fas fa-egg fa-3x text-gray-300 mb-3"></i>
                                            <p class="text-muted">No production data available</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                        <?php if (!empty($preview_data)): ?>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="2">Total</th>
                                    <th class="text-end"><?= number_format($preview_summary['total_eggs']) ?></th>
                                    <th class="text-end"><?= $preview_summary['avg_weight'] ?>g</th>
                                    <th class="text-end">100%</th>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Reports Sidebar -->
    <div class="col-lg-4">
        <div class="card shadow-sm mb-4">
            <div class="card-header text-white py-3">
                <h6 class="m-0 fw-bold">Generate Report</h6>
            </div>
            <div class="card-body">
                <form id="reportForm" method="post">
                    <input type="hidden" name="token" value="<?= special_chars($_SESSION['token']) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-medium">Report Type</label>
                        <select class="form-select form-select-lg" id="reportType" name="report_type" required>
                            <option value="production">Egg Production</option>
                            <option value="sales">Sales Report</option>
                            <option value="inventory">Inventory Summary</option>
                            <option value="financial">Financial Overview</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Date Range</label>
                        <select class="form-select form-select-lg" id="dateRange" name="date_range" required>
                            <option value="today">Today</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month" selected>This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="this_quarter">This Quarter</option>
                            <option value="last_quarter">Last Quarter</option>
                            <option value="this_year">This Year</option>
                            <option value="last_year">Last Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>

                    <div class="row mb-3" id="customDateRange" style="display: none;">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="date" class="form-control" name="start_date" id="startDate">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">End Date</label>
                            <input type="date" class="form-control" name="end_date" id="endDate">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-medium">Output Format</label>
                        <select class="form-select form-select-lg" id="outputFormat" name="output_format" required>
                            <option value="pdf">PDF Document</option>
                            <option value="excel">Excel Spreadsheet</option>
                        </select>
                    </div>

                    <div class="form-check form-switch mb-4">
                        <input class="form-check-input" type="checkbox" role="switch" id="includeCharts" name="include_charts" checked>
                        <label class="form-check-label fw-medium" for="includeCharts">Include Charts & Graphs</label>
                    </div>

                    <button type="submit" class="btn btn-success w-100 py-3 fw-bold" name="generate_report">
                        <i class="fas fa-file-download me-2"></i>Generate Report
                    </button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold">Size Distribution</h6>
            </div>
            <div class="card-body">
                <?php if (!empty($preview_summary['size_distribution'])): ?>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-2">
                            <div class="fw-medium">Egg Size</div>
                            <div class="fw-medium">Percentage</div>
                        </div>

                        <?php foreach ($preview_summary['size_distribution'] as $size => $data): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <span class="fw-medium"><?= $size ?></span>
                                    <span><?= $data['percentage'] ?>%</span>
                                </div>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar 
                                    <?= $size === $top_size ? 'bg-primary' : 'bg-info' ?>"
                                        role="progressbar"
                                        style="width: <?= $data['percentage'] ?>%;"
                                        aria-valuenow="<?= $data['percentage'] ?>"
                                        aria-valuemin="0"
                                        aria-valuemax="100">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-egg fa-2x mb-3"></i>
                        <p>No size distribution data available</p>
                    </div>
                <?php endif; ?>

                <div class="alert alert-light border">
                    <div class="d-flex">
                        <i class="fas fa-lightbulb text-primary me-2 mt-1"></i>
                        <div>
                            <span class="fw-bold">Insight:</span>
                            <?= $top_size ?> eggs are your most productive size category.
                            Consider adjusting feed formulas to optimize production.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Handle custom date range visibility
        document.getElementById('dateRange').addEventListener('change', function() {
            const customRangeDiv = document.getElementById('customDateRange');
            customRangeDiv.style.display = this.value === 'custom' ? 'flex' : 'none';
        });

        // Initialize charts
        const sizeCtx = document.getElementById('sizeDistributionChart').getContext('2d');
        const sizeChart = new Chart(sizeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($size_labels) ?>,
                datasets: [{
                    data: <?= json_encode($size_data) ?>,
                    backgroundColor: [
                        '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e',
                        '#e74a3b', '#858796', '#5a5c69'
                    ],
                    borderWidth: 1,
                    hoverOffset: 15
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 15,
                            padding: 20,
                            font: {
                                size: 11
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.parsed}%`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        const trendCtx = document.getElementById('productionTrendChart').getContext('2d');
        const trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($trend_labels) ?>,
                datasets: [{
                    label: 'Daily Egg Production',
                    data: <?= json_encode($trend_data) ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: '#4e73df',
                    borderWidth: 3,
                    pointBackgroundColor: '#4e73df',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: false,
                        title: {
                            display: true,
                            text: 'Eggs Produced',
                            font: {
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>

<style>
    .card {
        border-radius: 0.75rem;
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        border: none;
        margin-bottom: 1.5rem;
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .card-header {
        background-color: #fff;
        border-bottom: 1px solid #e3e6f0;
        padding: 1.25rem 1.5rem;
        border-radius: 0.75rem 0.75rem 0 0 !important;
        font-weight: 600;
    }

    .table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        font-weight: 600;
        color: #4e73df;
        background-color: #f8f9fc;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.5px;
        padding: 1rem 1.5rem;
    }

    .table td {
        padding: 0.75rem 1.5rem;
        vertical-align: middle;
        border-top: 1px solid #e3e6f0;
    }

    .table tbody tr {
        transition: background-color 0.2s;
    }

    .table tbody tr:hover {
        background-color: #f8f9fc;
    }

    .table-hover tbody tr:hover {
        background-color: #f6f9ff;
    }

    .btn-success {
        background: linear-gradient(135deg, #1cc88a, #17a673);
        border: none;
        transition: all 0.3s;
    }

    .btn-success:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(28, 200, 138, 0.3);
    }

    .form-control,
    .form-select {
        border: 1px solid #d1d3e2;
        border-radius: 0.5rem;
        padding: 0.75rem 1rem;
        transition: border-color 0.3s;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #4e73df;
        box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
    }

    .badge {
        border-radius: 0.5rem;
        font-weight: 500;
        padding: 0.5rem 0.75rem;
    }

    .progress {
        border-radius: 1rem;
        height: 0.75rem;
    }

    .progress-bar {
        border-radius: 1rem;
    }

    .text-xs {
        font-size: 0.8rem;
    }

    .text-gray-800 {
        color: #5a5c69;
    }

    .border-start {
        border-left: 0.25rem solid !important;
    }

    .fw-semibold {
        font-weight: 600;
    }

    .alert-light {
        background-color: #f8f9fc;
        border-color: #e3e6f0;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .card-header h6 {
            font-size: 1rem;
        }

        .table th,
        .table td {
            padding: 0.75rem;
        }
    }
</style>

<?php
$push_js = [
    'libs/chart.js/chart.min.js',
];

$push_css = [
    'libs/chart.js/chart.min.css',
];

$content = ob_get_clean();
include layouts('owner.main');
?>