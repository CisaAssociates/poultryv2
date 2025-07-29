<?php
require_once __DIR__ . '/../../config.php';
?>

<?php include partial('main'); ?>

<head>
    <?php
    include partial('title-meta'); ?>

    <?php include partial('head-css'); ?>
</head>

<body>

    <!-- Begin page -->
    <div id="wrapper">

        <div class="content-page">

            <?php include layouts('owner.topbar'); ?>

            <div class="content">
                <?php include partial('page-title'); ?>

                <!-- Start Content-->
                <div class="container-fluid">
                    <div class="row justify-content-center">
                        <div class="col-lg-6 col-xl-4 mb-4">
                            <div class="error-text-box">
                                <svg viewBox="0 0 600 200">
                                    <!-- Symbol-->
                                    <symbol id="s-text">
                                        <text text-anchor="middle" x="50%" y="50%" dy=".35em">Oops!</text>
                                    </symbol>
                                    <!-- Duplicate symbols-->
                                    <use class="text" xlink:href="#s-text"></use>
                                    <use class="text" xlink:href="#s-text"></use>
                                    <use class="text" xlink:href="#s-text"></use>
                                    <use class="text" xlink:href="#s-text"></use>
                                    <use class="text" xlink:href="#s-text"></use>
                                </svg>
                            </div>
                            <div class="text-center">
                                <h3 class="mt-0 mb-2">No Devices Found</h3>
                                <p class="text-muted mb-3">This farm doesn't have any devices configured yet. Please contact the administrator to add devices to your farm before you can access this feature.</p>

                                <a href="<?= view('owner.index') ?>" class="btn btn-success waves-effect waves-light">Back to Dashboard</a>
                            </div>
                            <!-- end row -->

                        </div> <!-- end col -->
                    </div>
                    <!-- end row -->
                </div> <!-- container -->

            </div> <!-- content -->

            <?php include partial('footer'); ?>
        </div>

    </div>
    <!-- END wrapper -->

    <?php include partial('right-sidebar'); ?>

    <?php include partial('footer-scripts'); ?>
</body>

</html>