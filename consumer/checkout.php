<?php
require_once __DIR__ . '/../config.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('auth.index');
}

// Check if cart is empty
$mysqli = db_connect();

// Get user's cart
$stmt = $mysqli->prepare("SELECT cart_id FROM consumer_carts WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // No cart found, redirect to products page
    redirect('consumer.products');
}

$cart_id = $result->fetch_assoc()['cart_id'];

// Get cart items
$stmt = $mysqli->prepare("SELECT ci.item_id, ci.tray_id, ci.quantity, t.size, t.price, t.image_url, f.farm_name, f.farm_id 
                         FROM consumer_cart_items ci
                         JOIN trays t ON ci.tray_id = t.tray_id
                         JOIN farms f ON t.farm_id = f.farm_id
                         WHERE ci.cart_id = ?");
$stmt->bind_param("i", $cart_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// If cart is empty, redirect to cart page
if ($cart_items->num_rows === 0) {
    redirect('consumer.cart');
}

// Get delivery method and address from URL parameters
$delivery_method = isset($_GET['delivery_method']) ? $_GET['delivery_method'] : 'delivery';
$address_id = isset($_GET['address_id']) ? intval($_GET['address_id']) : 0;
$use_loyalty_points = isset($_GET['use_loyalty_points']) && $_GET['use_loyalty_points'] == '1';

// If delivery method is delivery, make sure we have a valid address
if ($delivery_method === 'delivery' && $address_id === 0) {
    redirect('consumer.cart');
}

// Get address details if delivery method is delivery
$address = null;
if ($delivery_method === 'delivery') {
    $stmt = $mysqli->prepare("SELECT * FROM consumer_addresses WHERE address_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $address_id, $_SESSION['id']);
    $stmt->execute();
    $address_result = $stmt->get_result();
    
    if ($address_result->num_rows === 0) {
        redirect('consumer.cart');
    }
    
    $address = $address_result->fetch_assoc();
}

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

// Calculate order totals
$subtotal = 0;
$delivery_fee = $delivery_method === 'delivery' ? 50 : 0; // Default delivery fee

// Recalculate subtotal from cart items
while ($item = $cart_items->fetch_assoc()) {
    $subtotal += $item['price'] * $item['quantity'];
}

// Reset the result set pointer
$cart_items->data_seek(0);

// Calculate loyalty discount
$loyalty_discount = 0;
$points_discount = 0;

// Apply tier discount if available
if ($loyalty_tier && isset($loyalty_tier['discount_percentage'])) {
    $loyalty_discount = $subtotal * ($loyalty_tier['discount_percentage'] / 100);
}

// Apply points discount if selected and enough points available
if ($use_loyalty_points && $loyalty_points >= 50) {
    $points_discount = 50; // ₱50 discount for 50 points
}

// Calculate tax and total
$tax_amount = ($subtotal - $loyalty_discount - $points_discount) * ($tax_rate / 100);
$total = $subtotal - $loyalty_discount - $points_discount + $delivery_fee + $tax_amount;

// Process order submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'COD';
    $order_notes = isset($_POST['order_notes']) ? $_POST['order_notes'] : '';
    
    // For pickup method, get date and time
    $pickup_date = null;
    $pickup_time = null;
    if ($delivery_method === 'pickup') {
        $pickup_date = isset($_POST['pickup_date']) ? $_POST['pickup_date'] : null;
        $pickup_time = isset($_POST['pickup_time']) ? $_POST['pickup_time'] : null;
        
        if (!$pickup_date || !$pickup_time) {
            $error = "Please select a pickup date and time.";
        }
    }
    
    // If no errors, create the order
    if (!isset($error)) {
        // Start transaction
        $mysqli->begin_transaction();
        
        try {
            // Create order record
            $stmt = $mysqli->prepare("INSERT INTO consumer_orders 
                                    (user_id, address_id, total_amount, status, delivery_method, 
                                     delivery_date, delivery_time, payment_method, payment_status, order_notes) 
                                    VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, 'pending', ?)");
            
            // Set address_id to NULL for pickup orders
            $address_id_param = $delivery_method === 'pickup' ? null : $address_id;
            
            $stmt->bind_param("iidssss", 
                $_SESSION['id'], 
                $address_id_param, 
                $total, 
                $delivery_method, 
                $pickup_date, 
                $pickup_time, 
                $payment_method, 
                $order_notes
            );
            
            $stmt->execute();
            $order_id = $mysqli->insert_id;
            
            // Add order items
            $stmt = $mysqli->prepare("INSERT INTO consumer_order_items 
                                    (order_id, tray_id, quantity, unit_price, total_price) 
                                    VALUES (?, ?, ?, ?, ?)");
            
            // Reset the result set pointer
            $cart_items->data_seek(0);
            
            while ($item = $cart_items->fetch_assoc()) {
                $item_total = $item['price'] * $item['quantity'];
                $stmt->bind_param("iidd", 
                    $order_id, 
                    $item['tray_id'], 
                    $item['quantity'], 
                    $item['price'], 
                    $item_total
                );
                $stmt->execute();
                
                // Update tray status to sold
                $update_tray = $mysqli->prepare("UPDATE trays SET status = 'sold', sold_at = NOW() WHERE tray_id = ?");
                $update_tray->bind_param("i", $item['tray_id']);
                $update_tray->execute();
            }
            
            // If using loyalty points, deduct them
            if ($use_loyalty_points && $loyalty_points >= 50) {
                $stmt = $mysqli->prepare("UPDATE consumer_loyalty SET points = points - 50 WHERE user_id = ?");
                $stmt->bind_param("i", $_SESSION['id']);
                $stmt->execute();
            }
            
            // Clear the cart
            $stmt = $mysqli->prepare("DELETE FROM consumer_cart_items WHERE cart_id = ?");
            $stmt->bind_param("i", $cart_id);
            $stmt->execute();
            
            // Commit transaction
            $mysqli->commit();
            
            // Redirect to order confirmation page
            redirect('consumer.orders', ['order_id' => $order_id, 'status' => 'success']);
            
        } catch (Exception $e) {
            // Rollback transaction on error
            try {
                $mysqli->rollback();
            } catch (Exception $rollbackException) {
                // Transaction wasn't active or rollback failed
            }
            $error = "An error occurred while processing your order. Please try again.";
        }
    }
}

$title = 'Checkout';
$sub_title = 'Complete Your Order';
ob_start();
?>

<div class="row">
    <div class="col-lg-8">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <?= special_chars($error) ?>
            </div>
        <?php endif; ?>
        
        <div class="card">
            <div class="card-header bg-white">
                <h4 class="mb-0">Order Details</h4>
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
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($item = $cart_items->fetch_assoc()):
                                $item_total = $item['price'] * $item['quantity'];
                            ?>
                                <tr>
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
                                    <td><?= special_chars(ucfirst($item['size'])) ?></td>
                                    <td>₱<?= number_format($item['price'], 2) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>₱<?= number_format($item_total, 2) ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0"><?= $delivery_method === 'delivery' ? 'Delivery' : 'Pickup' ?> Information</h5>
            </div>
            <div class="card-body">
                <form method="post" id="checkout-form">
                <?php if ($delivery_method === 'delivery'): ?>
                    <h6>Delivery Address</h6>
                    <div class="mb-3">
                        <div class="border rounded p-3">
                            <p class="mb-1"><strong><?= special_chars($address['recipient_name']) ?></strong></p>
                            <p class="mb-1"><?= special_chars($address['street_address']) ?></p>
                            <p class="mb-1"><?= special_chars($address['barangay']) ?>, <?= special_chars($address['city']) ?></p>
                            <p class="mb-1"><?= special_chars($address['province']) ?>, <?= special_chars($address['zip_code']) ?></p>
                            <p class="mb-0"><?= special_chars($address['contact_number']) ?></p>
                        </div>
                    </div>
                    <p class="text-muted">Estimated delivery: 2-3 business days</p>
                <?php else: ?>
                    <h6>Pickup Information</h6>
                    <p>Pickup available at our main farm location:<br>
                        <strong>123 Poultry Lane, Barangay Manok, Egg City</strong>
                    </p>
                    <div class="mb-3">
                        <label class="form-label">Preferred Pickup Date</label>
                        <input type="date" name="pickup_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Preferred Pickup Time</label>
                        <input type="time" name="pickup_time" class="form-control" required>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Payment Method</h5>
            </div>
            <div class="card-body">
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="cod" value="COD" checked>
                    <label class="form-check-label" for="cod">
                        Cash on Delivery/Pickup
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="gcash" value="GCash">
                    <label class="form-check-label" for="gcash">
                        GCash
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="radio" name="payment_method" id="bank" value="Bank Transfer">
                    <label class="form-check-label" for="bank">
                        Bank Transfer
                    </label>
                </div>
                
                <div class="mb-3">
                    <label for="order_notes" class="form-label">Order Notes (Optional)</label>
                    <textarea class="form-control" id="order_notes" name="order_notes" rows="3" placeholder="Special instructions for your order"></textarea>
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
                    <span>₱<?= number_format($subtotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Delivery Fee:</span>
                    <span>₱<?= number_format($delivery_fee, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span>Tax (<?= $tax_rate ?>%):</span>
                    <span>₱<?= number_format($tax_amount, 2) ?></span>
                </div>

                <?php if ($loyalty_discount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Loyalty Discount (<?= $loyalty_tier['discount_percentage'] ?>%):</span>
                        <span>-₱<?= number_format($loyalty_discount, 2) ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($points_discount > 0): ?>
                    <div class="d-flex justify-content-between mb-2 text-success">
                        <span>Points Discount (50 points):</span>
                        <span>-₱<?= number_format($points_discount, 2) ?></span>
                    </div>
                <?php endif; ?>

                <div class="d-flex justify-content-between mt-3 fw-bold fs-5">
                    <span>Total:</span>
                    <span>₱<?= number_format($total, 2) ?></span>
                </div>

                <div class="mt-4">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div class="flex-grow-1">
                            <?php if ($loyalty_tier): ?>
                                <div class="fw-bold"><?= special_chars($loyalty_tier['tier_name']) ?> Tier</div>
                                <small class="text-muted">You'll earn approximately <?= floor($total / 10) ?> points with this purchase</small>
                            <?php else: ?>
                                <div class="fw-bold">No Tier</div>
                                <small class="text-muted">Start earning points with your purchases</small>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-info ms-2"><?= $loyalty_points - ($use_loyalty_points ? 50 : 0) ?> pts</span>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 mt-2">
                    Place Order
                </button>
                </form>
                
                <a href="<?= view('consumer.cart') ?>" class="btn btn-outline-secondary w-100 mt-2">
                    <i class="bi bi-arrow-left"></i> Back to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include layouts('consumer.main');
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Payment method toggle functionality
    const paymentMethods = document.querySelectorAll('input[name="payment_method"]');
    
    paymentMethods.forEach(method => {
        method.addEventListener('change', function() {
            // Here you can add logic to show/hide payment-specific fields
            // For example, if GCash is selected, show a QR code or number field
        });
    });
    
    // Handle form submission via AJAX
    const checkoutForm = document.getElementById('checkout-form');
    
    if (checkoutForm) {
        checkoutForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Show loading state
            const submitBtn = checkoutForm.querySelector('button[type="submit"]');
            let originalBtnText = '';
            if (submitBtn) {
                originalBtnText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
            }
            
            // Get form data
            const formData = new FormData(checkoutForm);
            const deliveryMethod = '<?= $delivery_method ?>';
            const addressId = <?= $delivery_method === 'delivery' ? $address_id : 'null' ?>;
            const useLoyaltyPoints = <?= $use_loyalty_points ? 'true' : 'false' ?>;
            
            // Create request payload
            const payload = {
                delivery_method: deliveryMethod,
                address_id: addressId,
                payment_method: formData.get('payment_method'),
                order_notes: formData.get('order_notes') || '',
                use_loyalty_points: useLoyaltyPoints
            };
            
            // Add pickup details if applicable
            if (deliveryMethod === 'pickup') {
                payload.pickup_date = formData.get('pickup_date');
                payload.pickup_time = formData.get('pickup_time');
            }
            
            // Send AJAX request
            fetch('<?= view('consumer.api.order.process-order') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
                credentials: 'same-origin'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                // Try to parse the response as JSON, but handle potential parsing errors
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Error parsing JSON:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                });
            })
            .then(data => {
                if (data.success) {
                    // Redirect to order confirmation page
                    window.location.href = '<?= view('consumer.orders') ?>?order_id=' + data.order_id + '&status=success';
                } else {
                    // Show error message
                    alert(data.message || 'An error occurred while processing your order. Please try again.');
                    
                    // Reset button state
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.innerHTML = originalBtnText;
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while processing your order. Please try again.');
                
                // Reset button state
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            });
        });
    }
});
</script>