<?php
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth.index');
}

// Get user's cart
$mysqli = db_connect();

// Define egg categories
$egg_categories = ['Pewee', 'Pullets', 'Small', 'Medium', 'Large', 'Extra Large', 'Jumbo'];

// Get or create cart for the user
$stmt = $mysqli->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Create a new cart for the user
    $stmt = $mysqli->prepare("INSERT INTO consumer_carts (user_id) VALUES (?)");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $cart_id = $mysqli->insert_id;
} else {
    $cart_id = $result->fetch_assoc()['cart_id'];
}

// Get cart items with product details
$stmt = $mysqli->prepare("SELECT ci.item_id, ci.tray_id, ci.quantity, t.size, t.price, t.image_url, t.stock_count, f.farm_name, f.farm_id 
                         FROM consumer_cart_items ci
                         JOIN trays t ON ci.tray_id = t.tray_id
                         JOIN farms f ON t.farm_id = f.farm_id
                         WHERE ci.cart_id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Get user's addresses
$stmt = $mysqli->prepare("SELECT * FROM consumer_addresses WHERE user_id = ? ORDER BY is_default DESC");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$addresses = $stmt->get_result();

// Get user's loyalty points and tier
$stmt = $mysqli->prepare("SELECT points FROM consumer_loyalty WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$loyalty_result = $stmt->get_result();
$loyalty = $loyalty_result->fetch_assoc();
$loyalty_points = $loyalty ? $loyalty['points'] : 0;

// Get user's loyalty tier based on points
$stmt = $mysqli->prepare("SELECT * FROM loyalty_tiers WHERE points_required <= ? ORDER BY points_required DESC LIMIT 1");
$stmt->bind_param("i", $loyalty_points);
$stmt->execute();
$loyalty_tier_result = $stmt->get_result();
$loyalty_tier = $loyalty_tier_result->fetch_assoc();

// Get tax rate
$stmt = $mysqli->prepare("SELECT tax_rate FROM tax_settings WHERE is_active = 1 LIMIT 1");
$stmt->execute();
$tax_result = $stmt->get_result();
$tax_rate = $tax_result->num_rows > 0 ? $tax_result->fetch_assoc()['tax_rate'] : 12; // Default to 12% if not set

// Calculate cart totals
$subtotal = 0;
$delivery_fee = 50; // Default delivery fee

$title = 'Your Cart';
$sub_title = 'Review Your Selected Products';
ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0">Your Cart</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Size</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cart-items">
                            <?php if ($cart_items->num_rows === 0): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="mb-3">
                                            <i class="bi bi-cart text-muted" style="font-size: 2.5rem;"></i>
                                        </div>
                                        <h5>Your cart is empty</h5>
                                        <p class="text-muted">Browse our selection of fresh eggs and add some to your cart.</p>
                                        <a href="<?= view('consumer.products') ?>" class="btn btn-primary">
                                            <i class="bi bi-egg"></i> Browse Products
                                        </a>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                while ($item = $cart_items->fetch_assoc()):
                                    $item_total = $item['price'] * $item['quantity'];
                                    $subtotal += $item_total;
                                ?>
                                    <tr data-item-id="<?= $item['item_id'] ?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if ($item['image_url']): ?>
                                                    <img src="<?= special_chars($item['image_url']) ?>" alt="<?= special_chars($item['size']) ?> eggs" class="me-3" style="width: 60px; height: 60px; object-fit: cover;">
                                                <?php else: ?>
                                                    <div class="bg-light rounded me-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                                        <i class="bi bi-egg-fill text-warning" style="font-size: 1.5rem;"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0">Egg Tray</h6>
                                                    <small class="text-muted">From <?= special_chars($item['farm_name']) ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            // Display the egg size category
                                            $size = strtolower($item['size']);
                                            $display_size = '';

                                            // Match the size to one of our categories
                                            foreach ($egg_categories as $category) {
                                                if (strtolower($category) === $size) {
                                                    $display_size = $category;
                                                    break;
                                                }
                                            }

                                            // If no match found, just use the original size
                                            if (empty($display_size)) {
                                                $display_size = ucfirst($size);
                                            }

                                            echo special_chars($display_size);
                                            ?>
                                        </td>
                                        <td>₱<?= number_format($item['price'], 2) ?></td>
                                        <td>
                                            <div class="input-group input-group-sm" style="width:100px;">
                                                <button class="btn btn-outline-secondary decrease-quantity" type="button" data-item-id="<?= $item['item_id'] ?>">-</button>
                                                <input type="text" class="form-control text-center item-quantity" value="<?= $item['quantity'] ?>" readonly>
                                                <button class="btn btn-outline-secondary increase-quantity" type="button" data-item-id="<?= $item['item_id'] ?>">+</button>
                                            </div>
                                        </td>
                                        <td>₱<?= number_format($item_total, 2) ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-danger remove-item" data-item-id="<?= $item['item_id'] ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between mt-4">
                    <a href="<?= view('consumer.products') ?>" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-left"></i> Continue Shopping
                    </a>
                    <button class="btn btn-danger" id="clear-cart" <?= $cart_items->num_rows === 0 ? 'disabled' : '' ?>>
                        <i class="bi bi-trash"></i> Clear Cart
                    </button>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Delivery Information</h5>
            </div>
            <div class="card-body">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="deliveryMethod" id="delivery" checked>
                    <label class="form-check-label" for="delivery">
                        Home Delivery
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="deliveryMethod" id="pickup">
                    <label class="form-check-label" for="pickup">
                        Farm Pickup
                    </label>
                </div>

                <div id="address-section">
                    <h6>Delivery Address</h6>
                    <div class="mb-3">
                        <select class="form-select" id="delivery-address" name="address_id">
                            <?php if ($addresses->num_rows === 0): ?>
                                <option value="">No saved addresses</option>
                            <?php else: ?>
                                <?php while ($address = $addresses->fetch_assoc()): ?>
                                    <option value="<?= $address['address_id'] ?>" <?= $address['is_default'] ? 'selected' : '' ?>>
                                        <?= special_chars($address['label']) ?> - <?= special_chars($address['recipient_name']) ?>
                                        (<?= special_chars($address['street_address']) ?>, <?= special_chars($address['barangay']) ?>,
                                        <?= special_chars($address['city']) ?>, <?= special_chars($address['province']) ?>)<?= $address['is_default'] ? ' - default' : '' ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    <a href="<?= view('consumer.addresses') ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-plus"></i> Manage Addresses
                    </a>
                </div>

                <div class="mt-3" id="pickup-section" style="display:none;">
                    <h6>Pickup Information</h6>
                    <p>Pickup available at our main farm location:<br>
                        <strong>123 Poultry Lane, Barangay Manok, Egg City</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Preferred Pickup Date</label>
                        <input type="date" class="form-control" min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preferred Pickup Time</label>
                        <input type="time" class="form-control">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card sticky-top" style="top: 130px;">
            <div class="card-header bg-white">
                <h5 class="mb-0">Order Summary</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between mb-2">
                    <span>Subtotal:</span>
                    <span id="subtotal">₱<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <span id="delivery-fee">₱<?= number_format($delivery_fee, 2) ?></span>
                </div>
                <?php
                // Calculate loyalty discount if applicable
                $loyalty_discount = 0;
                if ($loyalty_tier && isset($loyalty_tier['discount_percentage'])) {
                    $loyalty_discount = $subtotal * ($loyalty_tier['discount_percentage'] / 100);
                }

                $tax_amount = ($subtotal - $loyalty_discount) * ($tax_rate / 100);
                $total = $subtotal - $loyalty_discount + $delivery_fee + $tax_amount;
                ?>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (<?= $tax_rate ?>%):</span>
                    <span id="tax">₱<?= number_format($tax_amount, 2) ?></span>
                </div>

                <?php if ($loyalty_discount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Loyalty Discount (<?= $loyalty_tier['discount_percentage'] ?>%):</span>
                        <span id="loyalty-discount">-₱<?= number_format($loyalty_discount, 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-3 fw-bold fs-5">
                    <span>Total:</span>
                    <span id="total">₱<?= number_format($total, 2) ?></span>
                </div>

                <div class="mt-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <?php if ($loyalty_tier):
                                // Calculate progress percentage for progress bar
                                $progress_percentage = 0;

                                // Get next tier if available
                                $next_tier_query = $mysqli->prepare("SELECT * FROM loyalty_tiers WHERE points_required > ? ORDER BY points_required ASC LIMIT 1");
                                $next_tier_query->bind_param("i", $loyalty_tier['points_required']);
                                $next_tier_query->execute();
                                $next_tier = $next_tier_query->get_result()->fetch_assoc();

                                if ($next_tier) {
                                    $points_range = $next_tier['points_required'] - $loyalty_tier['points_required'];
                                    $points_earned = $loyalty_points - $loyalty_tier['points_required'];
                                    $progress_percentage = min(100, max(0, ($points_earned / $points_range) * 100));
                                } else {
                                    $progress_percentage = 100; // Max tier reached
                                }
                            ?>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-warning" style="width: <?= $progress_percentage ?>%"></div>
                                </div>
                                <div class="fw-bold"><?= special_chars($loyalty_tier['tier_name']) ?> Tier</div>
                                <?php if ($next_tier):
                                    $points_needed = $next_tier['points_required'] - $loyalty_points;
                                ?>
                                    <small class="text-muted">Earn <?= $points_needed ?> more points for <?= special_chars($next_tier['tier_name']) ?> tier</small>
                                <?php else: ?>
                                    <small class="text-muted">Highest tier reached</small>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="progress" style="height: 10px;">
                                    <div class="progress-bar bg-warning" style="width: 0%"></div>
                                </div>
                                <div class="fw-bold">No Tier</div>
                                <small class="text-muted">Start earning points with your purchases</small>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-info ms-2"><?= $loyalty_points ?> pts</span>
                    </div>

                    <?php if ($loyalty_points >= 50): ?>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="use-points" name="use_loyalty_points" value="1">
                            <label class="form-check-label" for="use-points">
                                Use 50 points for ₱50 discount
                            </label>
                        </div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary w-100 mt-2" id="checkout-btn" <?= $cart_items->num_rows === 0 ? 'disabled' : '' ?>>
                    Proceed to Checkout
                </button>
            </div>
        </div>
    </div>
</div>

<?php
$push_js = ['libs/sweetalert2/sweetalert2.all.min.js'];

$content = ob_get_clean();
include layouts('consumer.main');
?>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        let isUpdating = false;

        // Helper function to update order summary
        function updateOrderSummary() {
            let subtotal = 0;

            // Calculate subtotal from all cart items
            document.querySelectorAll('tr[data-item-id]').forEach(row => {
                const quantityInput = row.querySelector('.item-quantity');
                const priceCell = row.querySelector('td:nth-child(3)');

                if (quantityInput && priceCell) {
                    const quantity = parseInt(quantityInput.value) || 0;
                    const priceText = priceCell.textContent.replace('₱', '').replace(',', '').trim();
                    const price = parseFloat(priceText) || 0;
                    subtotal += price * quantity;
                }
            });

            // Update subtotal display
            const subtotalElement = document.getElementById('subtotal');
            if (subtotalElement) {
                subtotalElement.textContent = `₱${subtotal.toFixed(2)}`;
            }

            // Get current values
            const deliveryFeeElement = document.getElementById('delivery-fee');
            const taxElement = document.getElementById('tax');
            const totalElement = document.getElementById('total');
            const loyaltyDiscountElement = document.getElementById('loyalty-discount');

            if (deliveryFeeElement && taxElement && totalElement) {
                const deliveryFee = parseFloat(deliveryFeeElement.textContent.replace('₱', '').replace(',', '').trim()) || 0;

                // Calculate loyalty discount
                let loyaltyDiscount = 0;
                if (loyaltyDiscountElement) {
                    loyaltyDiscount = parseFloat(loyaltyDiscountElement.textContent.replace('-₱', '').replace(',', '').trim()) || 0;
                }

                // Calculate tax and total
                const taxRate = 12; // Default tax rate
                const taxAmount = Math.max(0, (subtotal - loyaltyDiscount) * (taxRate / 100));
                const total = Math.max(0, subtotal - loyaltyDiscount + deliveryFee + taxAmount);

                // Update displays
                taxElement.textContent = `₱${taxAmount.toFixed(2)}`;
                totalElement.textContent = `₱${total.toFixed(2)}`;
            }
        }

        // Function to update cart item quantity
        function updateCartItemQuantity(itemId, change) {
            if (isUpdating) return;
            isUpdating = true;

            const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
            if (!row) {
                isUpdating = false;
                return;
            }

            const quantityInput = row.querySelector('.item-quantity');
            const currentQuantity = parseInt(quantityInput.value) || 0;

            if (change === 0 || (currentQuantity === 1 && change === -1)) {
                isUpdating = false;
                return;
            }

            fetch('<?= view('consumer.api.cart.update_quantity') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        item_id: itemId,
                        change: change
                    }),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! Status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        quantityInput.value = data.new_quantity;

                        // Update item total
                        const priceCell = row.querySelector('td:nth-child(3)');
                        const totalCell = row.querySelector('td:nth-child(5)');
                        if (priceCell && totalCell) {
                            const price = parseFloat(priceCell.textContent.replace('₱', '').replace(',', '').trim()) || 0;
                            const newTotal = price * data.new_quantity;
                            totalCell.textContent = `₱${newTotal.toFixed(2)}`;
                        }

                        // Update order summary
                        updateOrderSummary();

                    } else {
                        console.error(data.message || 'Failed to update quantity', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                })
                .finally(() => {
                    isUpdating = false;
                });
        }

        // Function to remove cart item
        function removeCartItem(itemId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    const row = document.querySelector(`tr[data-item-id="${itemId}"]`);
                    if (row) {
                        // Remove row immediately for better UX
                        row.remove();

                        // Update order summary
                        updateOrderSummary();

                        // Check if cart is empty
                        const remainingItems = document.querySelectorAll('tr[data-item-id]');
                        if (remainingItems.length === 0) {
                            const cartItems = document.getElementById('cart-items');
                            cartItems.innerHTML = `
                                            <tr>
                                                <td colspan="6" class="text-center py-4">
                                                    <div class="mb-3"><i class="bi bi-cart text-muted" style="font-size: 2.5rem;"></i></div>
                                                    <h5>Your cart is empty</h5>
                                                    <p class="text-muted">Browse our selection of fresh eggs and add some to your cart.</p>
                                                    <a href="<?= view('consumer.products') ?>" class="btn btn-primary">
                                                        <i class="bi bi-egg"></i> Browse Products
                                                    </a>
                                                </td>
                                            </tr>
                                        `;

                            // Disable checkout button
                            document.getElementById('checkout-btn').disabled = true;
                        }
                    }

                    // Send request to server
                    fetch('<?= view('consumer.api.cart.remove_item') ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                item_id: itemId
                            }),
                            credentials: 'same-origin'
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! Status: ${response.status}`);
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {} else {
                                console.error(data.message || 'Failed to remove item', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });
        }

        // Attach event listeners for quantity buttons
        function attachEventListeners() {
            // Increase quantity buttons
            document.querySelectorAll('.increase-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    updateCartItemQuantity(itemId, 1);
                });
            });

            // Decrease quantity buttons
            document.querySelectorAll('.decrease-quantity').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    updateCartItemQuantity(itemId, -1);
                });
            });

            // Remove item buttons
            document.querySelectorAll('.remove-item').forEach(button => {
                button.addEventListener('click', function() {
                    const itemId = this.dataset.itemId;
                    removeCartItem(itemId);
                });
            });
        }

        // Initial attachment of event listeners
        attachEventListeners();

        // Clear cart functionality
        document.getElementById('clear-cart').addEventListener('click', function() {
            const cartItems = document.getElementById('cart-items');

            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    cartItems.innerHTML = `
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="mb-3"><i class="bi bi-cart text-muted" style="font-size: 2.5rem;"></i></div>
                                        <h5>Your cart is empty</h5>
                                        <p class="text-muted">Browse our selection of fresh eggs and add some to your cart.</p>
                                        <a href="<?= view('consumer.products') ?>" class="btn btn-primary">
                                            <i class="bi bi-egg"></i> Browse Products
                                        </a>
                                    </td>
                                </tr>
                            `;

                    // Update order summary
                    document.getElementById('subtotal').textContent = '₱0.00';
                    document.getElementById('tax').textContent = '₱0.00';
                    document.getElementById('total').textContent = '₱50.00'; // Just delivery fee
                    document.getElementById('checkout-btn').disabled = true;

                    // Send clear request to server
                    fetch('<?= view('consumer.api.cart.clear') ?>', {
                            method: 'POST',
                            credentials: 'same-origin'
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {} else {
                                console.error('Failed to clear cart', 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                }
            });

        });

        // Checkout button functionality
        document.getElementById('checkout-btn').addEventListener('click', function() {
            const deliveryMethod = document.querySelector('input[name="deliveryMethod"]:checked').id;
            let addressId = '';

            if (deliveryMethod === 'delivery') {
                addressId = document.getElementById('delivery-address').value;
                if (!addressId) {
                    alert('Please select a delivery address');
                    return;
                }
            }

            const useLoyaltyPoints = document.getElementById('use-points')?.checked || false;

            // Build checkout URL with parameters
            let checkoutUrl = 'checkout.php?delivery_method=' + deliveryMethod;
            if (addressId) {
                checkoutUrl += '&address_id=' + addressId;
            }
            checkoutUrl += '&use_loyalty_points=' + (useLoyaltyPoints ? '1' : '0');

            window.location.href = checkoutUrl;
        });

        // Delivery method toggle
        document.querySelectorAll('input[name="deliveryMethod"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const addressSection = document.getElementById('address-section');
                const pickupSection = document.getElementById('pickup-section');
                const deliveryFeeElement = document.getElementById('delivery-fee');

                if (this.id === 'delivery') {
                    addressSection.style.display = 'block';
                    pickupSection.style.display = 'none';
                    deliveryFeeElement.textContent = '₱50.00';
                } else {
                    addressSection.style.display = 'none';
                    pickupSection.style.display = 'block';
                    deliveryFeeElement.textContent = '₱0.00';
                }

                // Update total
                updateOrderSummary();
            });
        });
    });
</script>