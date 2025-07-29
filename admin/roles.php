<?php
require_once __DIR__ . '/../config.php';

$title = 'Roles Management';
$sub_title = 'Role-Based Access Control';
ob_start();
?>
<div class="row">
    <div class="col">
        <h2>Roles Management</h2>
        <p>Assign and configure role-based permissions.</p>
    </div>
</div>
<?php
$content = ob_get_clean();
include layouts('admin.main');
?>
