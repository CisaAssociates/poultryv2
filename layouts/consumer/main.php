<?php 
require_once __DIR__ . '/../../config.php'; 

if (!is_logged_in() || $user['role_id'] != 4) {
    $_SESSION['error-page'] = true;
    header('Location: ' . view('auth.error.403'));
    exit;
}
?>

<!DOCTYPE html>
<html lang="en" data-layout="horizontal" data-topbar-color="light" loading="eager" data-sidenav-user="true">

<head>
    <?php
    include partial('title-meta'); ?>

    <?php include partial('head-css'); ?>
    <!-- Custom CSS -->
    <?php if (isset($push_css)) : ?>
        <?php foreach ($push_css as $css) : ?>
            <link rel="stylesheet" href="<?= asset($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <?php include layouts('consumer.menu'); ?>

        <div class="content-page">

            <?php include layouts('consumer.topbar'); ?>

            <div class="content">
                <?php include partial('page-title'); ?>

                <!-- Start Content-->
                <div class="container-fluid">
                    <?= isset($content) ? $content : '' ?>
                </div> <!-- container -->

            </div> <!-- content -->

            <?php include partial('footer'); ?>
        </div>

    </div>
    <!-- END wrapper -->

    <?php include partial('right-sidebar'); ?>

    <?php include partial('footer-scripts'); ?>
    <?php if (isset($push_js)) : ?>
        <?php foreach ($push_js as $js) : ?>
            <script src="<?= asset($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>