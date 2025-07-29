<?php
// File: export.php
require_once __DIR__ . '/../../../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_token($_POST['token'])) {
    $farm_id = $user['farm_id'] ?? 0;
    $start_date = $_POST['start_date'] ?? date('Y-m-01');
    $end_date = $_POST['end_date'] ?? date('Y-m-d');
    $format = $_POST['export_format'] ?? 'csv';
    
    // Fetch data
    $data = get_egg_production_data($farm_id, $start_date, $end_date);
    
    if ($format === 'csv') {
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="egg_production_'.date('Ymd_His').'.csv"');
        
        // Create a file pointer
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, [
            'Date', 'Time', 'Egg ID', 'Size', 'Weight (g)', 'Device Serial'
        ]);
        
        // Add data rows
        foreach ($data as $row) {
            fputcsv($output, [
                $row['date'],
                date('h:i A', strtotime($row['time'])),
                'EGG-'.str_pad($row['id'], 6, '0', STR_PAD_LEFT),
                $row['size'],
                $row['egg_weight'],
                $row['device_serial_no']
            ]);
        }
        
        fclose($output);
        exit;
    }
    
    // For PDF/Excel, you would add similar functionality with appropriate libraries
    // This is a placeholder for future implementation
    $_SESSION['error'] = 'Selected export format is not yet implemented';
}

header('Location: ' . view('employee.reports'));
exit;