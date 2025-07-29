<!-- ========== Horizontal Menu Start ========== -->
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


    <!--- Menu -->
    <div class="scrollbar">
        <!-- User box -->
        <div class="user-box text-center">
            <img src="<?= asset('images/users/user-1.jpg') ?>" alt="user-img" title="Mat Helme" class="rounded-circle avatar-md">
            <div class="dropdown">
                <a href="javascript: void(0);" class="text-capitalize h5 mb-1 d-block"><?= special_chars($user['fullname']) ?></a>
            </div>
            <p class="text-muted mb-0"><?= special_chars($user['role_name']) ?></p>
        </div>

        <ul class="menu">
            <li class="menu-item">
                <a href="<?= view('consumer.index') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="home"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('consumer.products') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="shopping-bag"></i></span>
                    <span class="menu-text">Shop Eggs</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="#menuOrders" data-bs-toggle="collapse" class="menu-link">
                    <span class="menu-icon"><i data-feather="shopping-cart"></i></span>
                    <span class="menu-text">My Orders</span>
                    <span class="menu-arrow"></span>
                </a>
                <div class="collapse" id="menuOrders">
                    <ul class="sub-menu">
                        <li class="menu-item">
                            <a href="<?= view('consumer.orders') ?>" class="menu-link">
                                <span class="menu-text">Order History</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="<?= view('consumer.cart') ?>" class="menu-link">
                                <span class="menu-text">Shopping Cart</span>
                                <span id="cart-count" class="badge bg-primary rounded-pill ms-auto">0</span>
                            </a>
                        </li>
                        <li class="menu-item">
                            <a href="<?= view('consumer.reviews') ?>" class="menu-link">
                                <span class="menu-text">My Reviews</span>
                            </a>
                        </li>
                    </ul>
                </div>
            </li>

            <li class="menu-item">
                <a href="<?= view('consumer.addresses') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="map-pin"></i></span>
                    <span class="menu-text">Delivery Addresses</span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('consumer.loyalty') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="award"></i></span>
                    <span class="menu-text">Loyalty Program</span>
                    <span class="badge bg-warning text-dark rounded-pill ms-2"></span>
                </a>
            </li>

            <li class="menu-item">
                <a href="<?= view('consumer.support') ?>" class="menu-link">
                    <span class="menu-icon"><i data-feather="help-circle"></i></span>
                    <span class="menu-text">Help & Support</span>
                </a>
            </li>
        </ul>
    </div>
    <!--- End Menu -->
</div>
<!-- ========== Horizontal Menu End ========== -->

<script>
    function updateCartCount() {
        fetch('<?= view('consumer.api.cart.count') ?>')
            .then(response => response.json())
            .then(data => {
                document.getElementById('cart-count').textContent = data.count || '0';
            });
    }

    document.addEventListener('DOMContentLoaded', updateCartCount);
</script>