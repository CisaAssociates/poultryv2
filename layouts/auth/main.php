<?php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline'; script-src 'self'");

if (is_logged_in()) {
    redirect_based_on_role($_SESSION['role']);
}

require_once partial('main');
?>

<head>
    <?php
    include partial('title-meta');
    include partial('head-css');
    ?>

    <?php if (isset($push_css)) : ?>
        <?php foreach ($push_css as $css) : ?>
            <link rel="stylesheet" href="<?= asset($css) ?>">
        <?php endforeach; ?>
    <?php endif; ?>
</head>

<body class="authentication-bg authentication-bg-pattern">

    <div class="account-pages mt-5 mb-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6 col-xl-4">
                    <?= isset($content) ? $content : '' ?>
                </div> <!-- end col -->
            </div>
            <!-- end row -->
        </div>
        <!-- end container -->
    </div>
    <!-- end page -->

    <footer class="footer footer-alt">
        <script>
            document.write(new Date().getFullYear())
        </script> &copy; Powered by CISA
    </footer>

    <?php include partial('footer-scripts'); ?>

    <?php if (isset($push_js)) : ?>
        <?php foreach ($push_js as $js) : ?>
            <script src="<?= asset($js) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>

</html>