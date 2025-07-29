<?php
require_once __DIR__ . '/../../config.php';

$title = '500';
$sub_title = 'Internal Server Error';

if (!isset($_SESSION['error-page'])) {
    header('Location: ' . view('auth.index'));
    exit;
}

unset($_SESSION['error-page']);

ob_start();
?>
<div class="card bg-pattern">

    <div class="card-body p-4">

        <div class="auth-brand">
            <a href="index.php" class="logo logo-dark text-center">
                <span class="logo-lg">
                    <img src="assets/images/logo-dark.png" alt="" height="22">
                </span>
            </a>

            <a href="index.php" class="logo logo-light text-center">
                <span class="logo-lg">
                    <img src="assets/images/logo-light.png" alt="" height="22">
                </span>
            </a>
        </div>

        <div class="text-center mt-4">
            <h1 class="text-error">500</h1>
            <h3 class="mt-3 mb-2">Internal Server Error</h3>
            <p class="text-muted mb-3">Why not try refreshing your page? or you can contact <a href="" class="text-dark"><b>Support</b></a></p>

            <a href="auth/login" class="btn btn-success waves-effect waves-light">Back to Home</a>
        </div>

    </div> <!-- end card-body -->
</div>
<!-- end card -->


<?php ob_end_flush();
include layouts('auth.main') ?>