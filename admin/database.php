<?php
require_once __DIR__ . '/../config.php';

$title = 'Database Management';
$sub_title = 'Database Backup & Restore';
ob_start();
?>
<div class="row">
    <div class="col">
        <h2>Database Management</h2>
        <p>Backup and restore system data.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('admin.main');
?>
