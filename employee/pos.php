<?php
require_once __DIR__ . '/../config.php';

$title = 'POS System';
$sub_title = 'Point of Sale';
ob_start();
?>

<div class="row g-4">
    <!-- Products Column (70%) -->
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Available Products</h5>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control" placeholder="Search products..." id="productSearch">
                    </div>
                </div>

                <div class="product-grid" id="productGrid">
                    <!-- Products will be loaded via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Column (30%) -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">Shopping Cart</h5>
            </div>
            <div class="card-body d-flex flex-column">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div class="fw-medium">Current Sale</div>
                    <div>
                        <span class="badge bg-light text-dark">Farm: <?= $user['farm_name'] ?? 'Main Farm' ?></span>
                    </div>
                </div>

                <div id="cartItems" class="flex-grow-1" style="overflow-y: auto; max-height: 300px;">
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                        <p class="mt-3">Your cart is empty</p>
                        <p class="small">Add products from the left panel</p>
                    </div>
                </div>

                <div class="mt-auto pt-3 border-top">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal:</span>
                        <span id="subtotal">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span id="taxLabel">Tax (0%):</span>
                        <span id="tax">₱0.00</span>
                    </div>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="fw-bold">Total:</span>
                        <span class="fw-bold cart-total" id="total">₱0.00</span>
                    </div>

                    <div class="d-grid gap-2">
                        <button class="btn btn-primary btn-lg" id="checkoutBtn" disabled>
                            <i class="bi bi-credit-card me-2"></i>Process Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Receipt Modal -->
<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Receipt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="receiptContent" class="p-2 bg-light" style="font-family: monospace;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printReceiptBtn">
                    <i class="bi bi-printer me-2"></i>Print Receipt
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .card {
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        border: none;
        margin-bottom: 25px;
    }

    .card-header {
        background-color: white;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        padding: 20px;
        border-radius: 12px 12px 0 0 !important;
    }
    
    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
        gap: 20px;
    }

    .product-card {
        border: 1px solid rgba(0, 0, 0, 0.05);
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.3s, box-shadow 0.3s;
        background: white;
        cursor: pointer;
    }

    .product-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .product-card .img-placeholder {
        height: 120px;
        background-color: #f8f9fa;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
    }

    .product-card .info {
        padding: 15px;
    }

    .product-card .title {
        font-weight: 500;
        margin-bottom: 5px;
        font-size: 1rem;
    }

    .product-card .price {
        color: var(--primary);
        font-weight: 600;
        font-size: 1.1rem;
    }

    .product-card .stock {
        font-size: 0.85rem;
        color: #6c757d;
    }

    .cart-item {
        padding: 15px 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .cart-total {
        font-size: 1.2rem;
        font-weight: 600;
        color: var(--dark);
    }

    .product-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 15px;
    }

    #cartItems {
        max-height: 300px;
        overflow-y: auto;
    }

    .cart-item {
        padding: 12px 0;
        border-bottom: 1px dashed #dee2e6;
    }

    .printable-receipt {
        font-family: monospace;
        font-size: 12px;
        line-height: 1.2;
        white-space: pre;
    }

    @media print {
        body * {
            visibility: hidden;
        }

        .printable-receipt,
        .printable-receipt * {
            visibility: visible;
        }

        .printable-receipt {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
        }
    }
</style>
<script>
    $(document).ready(function() {
        const farmId = <?= $user['farm_id'] ?? 0 ?>;
        let cart = [];
        let taxRate = 0;

        function fetchTaxRate() {
            $.ajax({
                url: '<?= view("api.pos.get-tax") ?>',
                method: 'GET',
                data: {
                    farm_id: farmId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        taxRate = parseFloat(response.tax_rate);
                        $('#taxLabel').text(`Tax (${taxRate}%):`);
                    } else {
                        console.error('Failed to fetch tax rate:', response.message);
                    }
                },
                error: function() {
                    console.error('Failed to fetch tax rate');
                }
            });
        }

        function loadProducts() {
            $.ajax({
                url: '<?= view("api.pos.get-products") ?>',
                method: 'GET',
                data: {
                    farm_id: farmId
                },
                dataType: 'json',
                success: function(response) {
                    // Check if response is valid array
                    if (Array.isArray(response)) {
                        renderProducts(response);
                    } else {
                        console.error('Invalid response format:', response);
                        showProductError('Invalid product data format');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', error);
                    showProductError('Failed to load products');
                }
            });
        }

        function renderProducts(products) {
            let html = '';

            if (products.length === 0) {
                html = `
                <div class="col-12 text-center py-5 text-muted">
                    <i class="mdi mdi-egg-off-outline" style="font-size: 3rem;"></i>
                    <p class="mt-3">No products available</p>
                </div>
            `;
                $('#productGrid').html(html);
            } else {
                products.forEach(product => {
                    html += `
                <div class="product-card" data-id="${product.tray_id}" data-size="${product.size}" data-price="${product.price}" data-stock="${product.stock_count}">
                    <div class="img-placeholder">
                        <i class="bi bi-egg" style="font-size: 2.5rem;"></i>
                    </div>
                    <div class="info">
                        <div class="title">${product.size} Eggs</div>
                        <div class="price">₱${parseFloat(product.price).toFixed(2)}</div>
                        <div class="stock">${product.stock_count} available</div>
                    </div>
                </div>
                `;
                });
                $('#productGrid').html(html);

                // Add click handlers
                $('.product-card').click(function() {
                    const id = $(this).data('id');
                    const size = $(this).data('size');
                    const price = $(this).data('price');
                    const stock = $(this).data('stock');

                    // Check if already in cart
                    const existingItem = cart.find(item => item.id === id);

                    if (existingItem) {
                        if (existingItem.quantity < stock) {
                            existingItem.quantity++;
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Not enough stock available',
                                showConfirmButton: false,
                                showCloseButton: false,
                                timer: 2000,
                                timerProgressBar: true,
                            });
                            return;
                        }
                    } else {
                        cart.push({
                            id: id,
                            size: size,
                            price: price,
                            quantity: 1,
                            stock: stock
                        });
                    }

                    updateCartDisplay();
                });
            }
        }

        function showProductError(message) {
            $('#productGrid').html(`
                <div class="col-12 text-center py-5 text-danger">
                    <i class="bi bi-exclamation-circle" style="font-size: 3rem;"></i>
                    <p class="mt-3">${message}</p>
                </div>
            `);
        }

        // Update cart display
        function updateCartDisplay() {
            let html = '';
            let subtotal = 0;

            if (cart.length === 0) {
                html = `
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-cart-x" style="font-size: 3rem;"></i>
                    <p class="mt-3">Your cart is empty</p>
                    <p class="small">Add products from the left panel</p>
                </div>
            `;
            } else {
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;

                    html += `
                    <div class="cart-item">
                        <div class="d-flex justify-content-between">
                            <div>
                                <div class="fw-medium">${item.size} Eggs</div>
                                <div>₱${parseFloat(item.price).toFixed(2)} × ${item.quantity}</div>
                            </div>
                            <div class="d-flex align-items-center">
                                <div class="fw-medium me-3">₱${itemTotal.toFixed(2)}</div>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-secondary decrement" data-id="${item.id}">
                                        <i class="bi bi-dash"></i>
                                    </button>
                                    <button class="btn btn-outline-danger remove" data-id="${item.id}">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    <button class="btn btn-outline-secondary increment" data-id="${item.id}">
                                        <i class="bi bi-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                });
            }

            $('#cartItems').html(html);
            const tax = subtotal * (taxRate / 100);
            const total = subtotal + tax;

            $('#subtotal').text('₱' + subtotal.toFixed(2));
            $('#tax').text('₱' + tax.toFixed(2));
            $('#total').text('₱' + total.toFixed(2));

            // Enable/disable checkout button
            $('#checkoutBtn').prop('disabled', cart.length === 0);

            // Add event listeners to cart buttons
            $('.decrement').click(function() {
                const id = $(this).data('id');
                const item = cart.find(item => item.id === id);

                if (item.quantity > 1) {
                    item.quantity--;
                    updateCartDisplay();
                }
            });

            $('.increment').click(function() {
                const id = $(this).data('id');
                const item = cart.find(item => item.id === id);

                if (item.quantity < item.stock) {
                    item.quantity++;
                    updateCartDisplay();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Not enough stock available',
                        showConfirmButton: false,
                        showCloseButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                    });
                }
            });

            $('.remove').click(function() {
                const id = $(this).data('id');
                cart = cart.filter(item => item.id !== id);
                updateCartDisplay();
            });
        }

        // Generate thermal receipt content
        function generateReceipt(transaction) {
            const now = new Date();
            const dateStr = now.toLocaleDateString('en-PH', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit'
            });
            const timeStr = now.toLocaleTimeString('en-PH', {
                hour: '2-digit',
                minute: '2-digit'
            });

            let receipt = `
                ==============================
                        POULTRY POS
                ==============================
                Farm: ${transaction.farm_name}
                Date: ${dateStr} ${timeStr}
                Txn ID: ${transaction.transaction_id}
                ==============================
                Item            Qty   Amount
                ------------------------------
            `;

            transaction.items.forEach(item => {
                receipt += `    ${item.size.padEnd(15)} ${item.quantity.toString().padStart(3)}   ₱${item.total.toFixed(2).padStart(8)}\n`;
            });

            receipt += `
                ==============================
                Subtotal:        ₱${transaction.subtotal.toFixed(2).padStart(8)}
                Tax (${taxRate}%):        ₱${transaction.tax.toFixed(2).padStart(8)}
                Total:           ₱${transaction.total.toFixed(2).padStart(8)}
                ==============================
                    THANK YOU FOR YOUR
                        PURCHASE!
                ==============================
            `;

            return receipt;
        }

        // Checkout functionality
        $('#checkoutBtn').click(function() {
            if (cart.length === 0) return;

            $.ajax({
                url: '<?= view("api.pos.process-sale") ?>',
                method: 'POST',
                data: {
                    cart: JSON.stringify(cart),
                    farm_id: farmId,
                    token: '<?= $_SESSION['token'] ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Generate receipt
                        const receiptContent = generateReceipt(response.transaction);

                        // Display in modal
                        $('#receiptContent').text(receiptContent);
                        $('#receiptModal').modal('show');

                        // Reset cart
                        cart = [];
                        updateCartDisplay();

                        // Refresh products
                        loadProducts();
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                            showConfirmButton: true
                        });
                    }
                }
            });
        });

        // Print receipt functionality
        $('#printReceiptBtn').click(function() {
            const receiptContent = $('#receiptContent').text();
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Receipt</title>
                    <style>
                        body { 
                            font-family: monospace; 
                            font-size: 12px;
                            margin: 0;
                            padding: 10px;
                        }
                        pre { white-space: pre-wrap; }
                    </style>
                </head>
                <body>
                    <pre class="printable-receipt">${receiptContent}</pre>
                    <script>
                        window.onload = function() {
                            window.print();
                            setTimeout(function() { window.close(); }, 500);
                        }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        });

        // Search functionality
        $('#productSearch').on('input', function() {
            const searchTerm = $(this).val().toLowerCase();
            $('.product-card').each(function() {
                const size = $(this).data('size').toLowerCase();
                $(this).toggle(size.includes(searchTerm));
            });
        });

        // Initial load
        fetchTaxRate();
        loadProducts();
    });
</script>

<?php
$push_css = [
    'libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css',
    'libs/chart.js/chart.min.css',
];

$push_js = [
    'libs/datatables.net/js/jquery.dataTables.min.js',
    'libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js',
    'libs/sweetalert2/sweetalert2.all.min.js',
];

$content = ob_get_clean();
include layouts('employee.main');
