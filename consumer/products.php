<?php
require_once __DIR__ . '/../config.php';

$mysqli = db_connect();

$farm_stmt = $mysqli->prepare("SELECT DISTINCT farm_name, farm_id FROM farms ORDER BY farm_name");
$farm_stmt->execute();
$farm_result = $farm_stmt->get_result();
$farms = [];

while ($farm = $farm_result->fetch_assoc()) {
    $farms[] = $farm;
}

$title = 'Available Products';
$sub_title = 'Fresh Eggs Selection';
ob_start();
?>

<div class="row mb-4">
    <div class="col-12 d-flex justify-content-end">
        <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas" aria-controls="filterOffcanvas">
            <i class="mdi mdi-filter-outline me-1"></i> Filter Products
        </button>
    </div>
</div>

<!-- Filter Offcanvas -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="filterOffcanvas" aria-labelledby="filterOffcanvasLabel">
    <div class="offcanvas-header">
        <h5 id="filterOffcanvasLabel">Filter Products</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            <label for="sizeFilter" class="form-label">Egg Size</label>
            <div class="d-flex flex-wrap gap-2 size-filter">
                <button class="btn btn-sm btn-outline-primary active" data-size="all">All Sizes</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Pewee">Pewee</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Pullets">Pullets</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Small">Small</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Medium">Medium</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Large">Large</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Extra Large">Extra Large</button>
                <button class="btn btn-sm btn-outline-primary" data-size="Jumbo">Jumbo</button>
            </div>
        </div>

        <div class="mb-4">
            <label for="priceRange" class="form-label">Price Range</label>
            <div class="d-flex align-items-center gap-2">
                <input type="range" class="form-range" id="priceRange" min="0" max="500" step="10" value="500">
                <span class="badge bg-primary" id="priceRangeValue">₱500</span>
            </div>
        </div>

        <div class="mb-4">
            <label for="farmFilter" class="form-label">Farm</label>
            <select class="form-select" id="farmFilter">
                <option value="all" selected>All Farms</option>
                <?php foreach ($farms as $farm): ?>
                    <option value="<?= $farm['farm_id'] ?>"><?= special_chars($farm['farm_name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-4">
            <label for="sortOrder" class="form-label">Sort By</label>
            <select class="form-select" id="sortOrder">
                <option value="price-asc">Price: Low to High</option>
                <option value="price-desc">Price: High to Low</option>
                <option value="size-asc">Size: Small to Large</option>
                <option value="size-desc">Size: Large to Small</option>
            </select>
        </div>

        <div class="d-grid gap-2 mt-4">
            <button id="applyFilters" class="btn btn-primary" data-bs-dismiss="offcanvas">Apply Filters</button>
            <button id="resetFilters" class="btn btn-outline-secondary">Reset Filters</button>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <?php
    // Get products
    $stmt = $mysqli->prepare("
            SELECT t.tray_id, t.size, t.price, t.image_url, t.stock_count, f.farm_name, f.farm_id 
            FROM trays t
            JOIN farms f ON t.farm_id = f.farm_id
            WHERE t.status = 'published'
        ");
    $stmt->execute();
    $result = $stmt->get_result();

    while ($tray = $result->fetch_assoc()):
        $badgeClass = $tray['stock_count'] > 10 ? 'bg-success' : 'bg-warning';
    ?>
        <div class="col-md-4 col-lg-3">
            <div class="card h-100 product-card" data-farm-id="<?= $tray['farm_id'] ?>" data-price="<?= $tray['price'] ?>" data-size="<?= $tray['size'] ?>">
                <?php if ($tray['image_url']): ?>
                    <img src="<?= special_chars($tray['image_url']) ?>" class="card-img-top" alt="<?= special_chars($tray['size']) ?> eggs">
                <?php else: ?>
                    <div class="bg-light border" style="height: 180px; display: grid; place-items: center;">
                        <i class="bi bi-egg-fill text-warning" style="font-size: 3rem;"></i>
                    </div>
                <?php endif; ?>

                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h5 class="card-title"><?= special_chars(ucfirst($tray['size'])) ?> Eggs</h5>
                        <span class="align-items-center d-flex badge <?= $badgeClass ?>"><?= $tray['stock_count'] ?> left</span>
                    </div>
                    <p class="card-text text-muted mb-1">From <?= special_chars($tray['farm_name']) ?></p>
                    <h4 class="text-primary my-2">₱<?= number_format($tray['price'], 2) ?></h4>

                    <div class="d-flex justify-content-between mt-3">
                        <a href="#" class="btn btn-outline-primary btn-sm">Details</a>
                        <button class="btn btn-primary btn-sm add-to-cart" data-tray-id="<?= $tray['tray_id'] ?>">
                            <i class="bi bi-cart-plus"></i> Add to Cart
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endwhile; ?>
    <?php if ($result->num_rows === 0): ?>
        <div class="col-12">
            <p class="text-center">No trays available at the moment.</p>
        </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>



<!-- Cart Preview Modal -->
<div class="modal fade" id="cartPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Added to Cart</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-4" id="cartItemImage">
                        <!-- Image will be inserted here -->
                    </div>
                    <div class="col-8">
                        <h5 id="cartItemName">Egg Tray</h5>
                        <p class="text-muted" id="cartItemFarm">Farm name</p>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="text-primary" id="cartItemPrice">₱0.00</h5>
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <button class="btn btn-outline-secondary" id="decreaseQty" type="button">-</button>
                                <input type="number" class="form-control text-center p-1" id="cartItemQty" value="1" min="1">
                                <button class="btn btn-outline-secondary" id="increaseQty" type="button">+</button>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total:</span>
                            <h5 class="text-primary" id="cartTotalPreview">₱0.00</h5>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="<?= view('consumer.cart') ?>" class="btn btn-primary">View Cart</a>
                <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Continue Shopping</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.addToCartInProgress = false;
        // Get all products
        const productCards = document.querySelectorAll('.product-card');
        const productContainers = document.querySelectorAll('.product-card').forEach(card => card.closest('.col-md-4'));

        // Size filter functionality
        const sizeButtons = document.querySelectorAll('.size-filter button');
        let selectedSize = 'all';

        sizeButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Remove active class from all buttons
                sizeButtons.forEach(btn => btn.classList.remove('active'));

                // Add active class to clicked button
                this.classList.add('active');

                selectedSize = this.dataset.size;
                applyFilters();
            });
        });

        // Price range filter
        const priceRange = document.getElementById('priceRange');
        const priceRangeValue = document.getElementById('priceRangeValue');
        let maxPrice = 500;

        priceRange.addEventListener('input', function() {
            maxPrice = this.value;
            priceRangeValue.textContent = `₱${maxPrice}`;
        });

        // Farm filter
        const farmFilter = document.getElementById('farmFilter');
        let selectedFarm = 'all';

        farmFilter.addEventListener('change', function() {
            selectedFarm = this.value;
        });

        // Sort order
        const sortOrder = document.getElementById('sortOrder');
        let currentSortOrder = 'price-asc';

        sortOrder.addEventListener('change', function() {
            currentSortOrder = this.value;
        });

        // Apply filters button
        const applyFiltersBtn = document.getElementById('applyFilters');
        applyFiltersBtn.addEventListener('click', function() {
            applyFilters();
        });

        // Reset filters button
        const resetFiltersBtn = document.getElementById('resetFilters');
        resetFiltersBtn.addEventListener('click', function() {
            // Reset size filter
            sizeButtons.forEach(btn => btn.classList.remove('active'));
            document.querySelector('.size-filter button[data-size="all"]').classList.add('active');
            selectedSize = 'all';

            // Reset price range
            priceRange.value = 500;
            priceRangeValue.textContent = '₱500';
            maxPrice = 500;

            // Reset farm filter
            farmFilter.value = 'all';
            selectedFarm = 'all';

            // Reset sort order
            sortOrder.value = 'price-asc';
            currentSortOrder = 'price-asc';

            // Apply reset filters
            applyFilters();
        });

        // Function to apply all filters
        function applyFilters() {
            // Get all products again in case DOM has changed
            const productCards = document.querySelectorAll('.product-card');

            // Apply filters to each product
            productCards.forEach(card => {
                const container = card.closest('.col-md-4');
                const cardTitle = card.querySelector('.card-title').textContent.trim();
                const price = parseFloat(card.querySelector('.text-primary').textContent.replace('₱', '').replace(',', ''));
                const farmName = card.querySelector('.card-text').textContent.replace('From ', '');
                const farmId = card.dataset.farmId || 'unknown'; // Add data-farm-id attribute to cards

                // Check size filter
                const sizeMatch = selectedSize === 'all' || cardTitle.toLowerCase().includes(selectedSize.toLowerCase());

                // Check price filter
                const priceMatch = price <= maxPrice;

                // Check farm filter
                const farmMatch = selectedFarm === 'all' || farmId === selectedFarm;

                // Show/hide based on all filters
                if (sizeMatch && priceMatch && farmMatch) {
                    container.style.display = '';
                } else {
                    container.style.display = 'none';
                }
            });

            // Sort visible products
            sortProducts(currentSortOrder);
        }

        // Function to sort products
        function sortProducts(sortType) {
            const productsContainer = document.querySelector('.row.g-4');
            const products = Array.from(productsContainer.querySelectorAll('.col-md-4')).filter(el => el.style.display !== 'none');

            products.sort((a, b) => {
                const priceA = parseFloat(a.querySelector('.text-primary').textContent.replace('₱', '').replace(',', ''));
                const priceB = parseFloat(b.querySelector('.text-primary').textContent.replace('₱', '').replace(',', ''));
                const sizeA = a.querySelector('.card-title').textContent.trim();
                const sizeB = b.querySelector('.card-title').textContent.trim();

                // Size order mapping (smallest to largest)
                const sizeOrder = {
                    'Pewee': 1,
                    'Pullets': 2,
                    'Small': 3,
                    'Medium': 4,
                    'Large': 5,
                    'Extra Large': 6,
                    'Jumbo': 7
                };

                // Get size value or default to highest if not found
                const getSizeValue = (size) => {
                    for (const [key, value] of Object.entries(sizeOrder)) {
                        if (size.includes(key)) return value;
                    }
                    return 999; // Default for unknown sizes
                };

                const sizeValueA = getSizeValue(sizeA);
                const sizeValueB = getSizeValue(sizeB);

                switch (sortType) {
                    case 'price-asc':
                        return priceA - priceB;
                    case 'price-desc':
                        return priceB - priceA;
                    case 'size-asc':
                        return sizeValueA - sizeValueB;
                    case 'size-desc':
                        return sizeValueB - sizeValueA;
                    default:
                        return 0;
                }
            });

            // Reorder DOM elements
            products.forEach(product => {
                productsContainer.appendChild(product);
            });
        }

        // Add to cart functionality
        const addToCartButtons = document.querySelectorAll('.add-to-cart');
        const cartItemQty = document.getElementById('cartItemQty');
        const decreaseQtyBtn = document.getElementById('decreaseQty');
        const increaseQtyBtn = document.getElementById('increaseQty');
        let currentTrayId = null;
        let currentPrice = 0;

        addToCartButtons.forEach(button => {
            button.addEventListener('click', function() {
                const trayId = this.dataset.trayId;
                currentTrayId = trayId;
                const card = this.closest('.product-card');
                const productName = card.querySelector('.card-title').textContent;
                const farmName = card.querySelector('.card-text').textContent.replace('From ', '');
                const price = parseFloat(card.querySelector('.text-primary').textContent.replace('₱', '').replace(',', ''));
                currentPrice = price;

                // Set modal content
                document.getElementById('cartItemName').textContent = productName;
                document.getElementById('cartItemFarm').textContent = farmName;
                document.getElementById('cartItemPrice').textContent = `₱${price.toFixed(2)}`;

                // Set image
                let imageHtml = '';
                const img = card.querySelector('.card-img-top');
                if (img) {
                    imageHtml = `<img src="${img.src}" class="img-fluid rounded" alt="${productName}">`;
                } else {
                    imageHtml = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:100%;height:100%;"><i class="bi bi-egg-fill text-warning" style="font-size: 2rem;"></i></div>`;
                }
                document.getElementById('cartItemImage').innerHTML = imageHtml;

                // Reset quantity
                cartItemQty.value = 1;
                updateCartTotal(price);

                // Show modal
                const modal = new bootstrap.Modal(document.getElementById('cartPreviewModal'));
                modal.show();

                // Add to cart
                addToCart(trayId, 1, price);
            });
        });

        // Quantity change handlers using event delegation
        document.addEventListener('click', function(e) {
            if (e.target.matches('#decreaseQty')) {
                let qty = parseInt(cartItemQty.value);
                if (qty > 1) {
                    cartItemQty.value = qty - 1;
                    cartItemQty.dispatchEvent(new Event('change'));
                }
            } else if (e.target.matches('#increaseQty')) {
                let qty = parseInt(cartItemQty.value);
                cartItemQty.value = qty + 1;
                cartItemQty.dispatchEvent(new Event('change'));
            }
        });

        cartItemQty.addEventListener('change', function() {
            const qty = parseInt(this.value);
            if (qty < 1) this.value = 1;
            updateCartTotal(currentPrice);

            if (currentTrayId) {
                addToCart(currentTrayId, parseInt(this.value), currentPrice);
            }
        });

        function updateCartTotal(price) {
            const qty = parseInt(cartItemQty.value);
            const total = price * qty;
            document.getElementById('cartTotalPreview').textContent = `₱${total.toFixed(2)}`;
        }

        function addToCart(trayId, quantity, price) {
            // Prevent multiple requests
            if (window.addToCartInProgress) return;
            window.addToCartInProgress = true;

            const buttons = document.querySelectorAll('#decreaseQty, #increaseQty');
            buttons.forEach(btn => btn.disabled = true);

            const formData = new FormData();
            formData.append('tray_id', trayId);
            formData.append('quantity', quantity);
            formData.append('price', price);

            fetch('<?= view('consumer.api.order.add-to-cart') ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartCount();
                    } else {
                        if (data.max_quantity !== undefined) {
                            cartItemQty.value = data.max_quantity;
                            updateCartTotal(currentPrice);
                            if (data.max_quantity <= 0) {
                                increaseQtyBtn.disabled = true;
                            }
                        }

                        console.error('Error adding to cart:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while adding to cart');
                })
                .finally(() => {
                    buttons.forEach(btn => btn.disabled = false);
                    window.addToCartInProgress = false;
                });
        }

        function updateCartCount() {
            fetch('<?= view('consumer.api.cart.count') ?>')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || '0';
                });
        }

        // Initialize filters on page load
        applyFilters();
    });
</script>