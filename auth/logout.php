<?php
require_once __DIR__ . '/../config.php';

$title = "Logout";
$sub_title = "Authentication";

if (!isset($_SESSION['logout'])) {
    header('Location: ' . view('auth.index'));
    exit;
}

unset($_SESSION['logout']);

ob_start();
?>
<div class="card bg-pattern">

    <div class="card-body p-4">

        <div class="text-center w-75 m-auto">
            <div class="auth-brand">
                <a href="index.php" class="logo logo-dark text-center">
                    <span class="logo-lg">
                        <img src="assets/images/logo-dark.png" alt="" height="22">
                    </span>
                </a>

                <a href="index.php" class="logo logo-light text-center">
                    <span class="logo-lg">
                        <img src="<?= asset('images/logo-light.png') ?>" alt="" height="22">
                    </span>
                </a>
            </div>
        </div>

        <div class="text-center">
            <div class="mt-4">
                <div class="logout-checkmark">
                    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 130.2 130.2">
                        <circle class="path circle" fill="none" stroke="#4bd396" stroke-width="6" stroke-miterlimit="10" cx="65.1" cy="65.1" r="62.1" />
                        <polyline class="path check" fill="none" stroke="#4bd396" stroke-width="6" stroke-linecap="round" stroke-miterlimit="10" points="100.2,40.2 51.5,88.8 29.8,67.5 " />
                    </svg>
                </div>
            </div>

            <h3>See you again !</h3>

            <p class="text-muted"> You are now successfully sign out. </p>
        </div>

    </div> <!-- end card-body -->
</div>
<!-- end card -->

<div class="row mt-3">
    <div class="col-12 text-center">
        <p class="text-white-50">Back to <a href="<?= view('auth.login') ?>" class="text-white ms-1"><b>Sign In</b></a></p>
    </div> <!-- end col -->
</div>
<!-- end row -->
<?php
$content = ob_get_clean();
include layouts('auth.main');
