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
            <li class="menu-title">Navigation</li>

            <li class="menu-item">
                <a href="<?= view('owner.index') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="airplay"></i></span>
                    <span class="menu-text"> Dashboard </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('owner.egg-monitoring') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="monitor"></i></span>
                    <span class="menu-text"> Egg Monitoring </span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="<?= view('owner.farms') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="home"></i></span>
                    <span class="menu-text"> Manage Farms </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('owner.auto-egg-tray') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="shopping-bag"></i></span>
                    <span class="menu-text"> Auto Egg Tray </span>
                </a>
            </li>
            

            <li class="menu-item">
                <a href="<?= view('owner.pos') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="printer"></i></span>
                    <span class="menu-text"> POS Management </span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('manage-orders') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="shopping-cart"></i></span>
                    <span class="menu-text"> Manage Orders </span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="<?= view('owner.sales-revenue') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="dollar-sign"></i></span>
                    <span class="menu-text"> Sales & Revenue </span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="<?= view('owner.predictive-analysis') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="trending-up"></i></span>
                    <span class="menu-text"> Predictive Analysis </span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="<?= view('owner.employees') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="users"></i></span>
                    <span class="menu-text"> Employee Management </span>
                </a>
            </li>
            
            <li class="menu-item">
                <a href="<?= view('owner.reports') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="file-text"></i></span>
                    <span class="menu-text"> Reports & Analytics </span>
                </a>
            </li>
        </ul>
        <!--- End Menu -->
        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left menu End ========== -->