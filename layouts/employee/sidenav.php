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
            <p class="text-muted mb-0"><?= special_chars($user['type_name']) ?></p>
        </div>

        <!--- Menu -->
        <ul class="menu">

            <li class="menu-title">Home</li>

            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.index') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-tachometer-alt"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <li class="menu-title">Management</li>

            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.tasks') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-tasks"></i></span>
                    <span class="menu-text">Daily Tasks</span>
                </a>
            </li>

            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.pos') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-cash-register"></i></span>
                    <span class="menu-text">Sales & POS</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?= view('manage-orders') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="shopping-cart"></i></span>
                    <span class="menu-text"> Manage Orders </span>
                </a>
            </li>
            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.egg-collection') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-egg"></i></span>
                    <span class="menu-text">Egg Collection & Sorting</span>
                </a>
            </li>
            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.inventory') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-warehouse"></i></span>
                    <span class="menu-text">Inventory Management</span>
                </a>
            </li>
            <li class="menu-item">
                <a class="menu-link" href="<?= view('employee.reports') ?>">
                    <span class="menu-icon"><i class="fas fa-fw fa-chart-bar"></i></span>
                    <span class="menu-text">Reports</span>
                </a>
            </li>
        </ul>
        <!--- End Menu -->
        <div class="clearfix"></div>
    </div>
</div>
<!-- ========== Left menu End ========== -->