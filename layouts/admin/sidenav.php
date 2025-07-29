<!-- ========== Menu ========== -->
<div class="app-menu">

    <!-- Brand Logo -->
    <div class="logo-box">
        <!-- Brand Logo Light -->
        <a href="javascript:void(0);" class="logo-light mt-2">
            <img src="<?= asset('images/logo-light.png') ?>" alt="logo" class="logo-lg" style="height: 75px;">
            <img src="<?= asset('images/logo-light.png') ?>" alt="small logo" class="logo-sm" style="height: 40px;">
        </a>

        <!-- Brand Logo Dark -->
        <a href="javascript:void(0);" class="logo-dark mt-2">
            <img src="<?= asset('images/logo-dark.png') ?>" alt="dark logo" class="logo-lg" style="height: 75px;">
            <img src="<?= asset('images/logo-dark.png') ?>" alt="small logo" class="logo-sm" style="height: 40px;">
        </a>
    </div>

    <!-- menu-left -->
    <div class="scrollbar">

        <!-- User box -->
        <div class="user-box text-center">
            <img src="<?= asset('images/users/user-1.jpg') ?>" alt="user-img" title="Mat Helme" class="rounded-circle avatar-md">
            <div class="dropdown">
                <a href="javascript: void(0);" class="text-capitalize h5 mb-1 d-block"><?= special_chars($user['fullname']) ?></a>
            </div>
            <p class="text-muted mb-0"><?= special_chars($user['role_name']) ?></p>
        </div>

        <!--- Menu -->
        <ul class="menu">

            <li class="menu-title">Home</li>

            <li class="menu-item">
                <a href="<?= view('admin.index') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-view-dashboard"></i></span>
                    <span class="menu-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-title">Management</li>

            <li class="menu-item">
                <a href="<?= view('admin.farms') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-warehouse"></i></span>
                    <span class="menu-text"> Farm </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('admin.users') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-account-group"></i></span>
                    <span class="menu-text"> Users </span>
                </a>
            </li>

            <li class="menu-title">POS Configuration</li>

            <li class="menu-item">
                <a href="<?= view('admin.pos-settings') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-cart"></i></span>
                    <span class="menu-text"> POS Settings </span>
                </a>
            </li>

            <li class="menu-title">Devices & Egg Production</li>

            <li class="menu-item">
                <a href="<?= view('admin.device.analytics') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-chart-bar"></i></span>
                    <span class="menu-text"> AI-Powered Egg Production Analytics </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('admin.device.monitoring') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-monitor-dashboard"></i></span>
                    <span class="menu-text"> Monitoring </span>
                </a>
            </li>

            <li class="menu-title">Database</li>

            <li class="menu-item">
                <a href="<?= view('admin.database') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-database-cog"></i></span>
                    <span class="menu-text"> Database Management </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('admin.backup') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-database-export"></i></span>
                    <span class="menu-text"> Backup & Restore </span>
                </a>
            </li>

            <li class="menu-title">Customer Support</li>

            <li class="menu-item">
                <a href="<?= view('admin.support-tickets') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-ticket-account"></i></span>
                    <span class="menu-text"> Support Tickets </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('admin.reviews') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-star"></i></span>
                    <span class="menu-text"> Product Reviews </span>
                </a>
            </li>

            <li class="menu-title">System Tools</li>

            <li class="menu-item">
                <a href="<?= view('admin.roles') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-lock"></i></span>
                    <span class="menu-text"> RBAC </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('admin.settings') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-cog"></i></span>
                    <span class="menu-text"> Settings </span>
                </a>
            </li>


            <li class="menu-item">
                <a href="<?= view('admin.audit_logs') ?>" class="menu-link">
                    <span class="menu-icon"><i class="mdi mdi-file-document-multiple"></i></span>
                    <span class="menu-text"> Audit Logs </span>
                </a>
            </li>

        </ul>
        <!--- End Menu -->
        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left menu End ========== -->