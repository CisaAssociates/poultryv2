<?php 
require_once __DIR__ . '/../../config.php'; 

if (!is_logged_in() || $user['role_id'] != 1) {
    $_SESSION['error-page'] = true;
    header('Location: ' . view('auth.error.403'));
    exit;
}
?>

<?php include partial('main'); ?>

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

        <?php include layouts('admin.sidenav'); ?>

        <div class="content-page">

            <?php include layouts('admin.topbar'); ?>

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