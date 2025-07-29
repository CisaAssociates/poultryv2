<?php
require_once __DIR__ . '/../config.php';

$title = 'Alerts & Notifications';
$sub_title = 'System Alerts Settings';
ob_start();
?>
<div class="row">
    <div class="col">
        <h2>Alerts & Notifications</h2>
        <p>Set up system alerts for device errors, low inventory, or security breaches.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('admin.main');
?>
