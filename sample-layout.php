<?php
require_once __DIR__ . '/../config.php';

$title = 'POS & Inventory Settings';
$sub_title = 'Configure POS Settings';
ob_start();
?>

<!-- Content -->

<!-- end Content -->

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/chart.js/chart.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
];

$content = ob_get_clean();
include layouts('admin.main');
?>