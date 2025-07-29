<?php
require_once __DIR__ . '/../config.php';

$title = 'Security & Audit Logs';
$sub_title = 'System Activity Logs';
ob_start();
?>
<div class="row">
    <div class="col">
        <h2>Security & Audit Logs</h2>
        <p>Track system activity and unauthorized access attempts.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('admin.main');
?>
