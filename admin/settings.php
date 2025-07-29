<?php
require_once __DIR__ . '/../config.php';

$title = 'System Settings';
$sub_title = 'System Configuration';
ob_start();
?>
<div class="row">
    <div class="col">
        <h2>System Settings</h2>
        <p>Configure site-wide settings, permissions, and updates.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('admin.main');
?>
