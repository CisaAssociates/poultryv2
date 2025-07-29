/**
 * Consumer Order Management
 * Handles product loading, cart management, and order placement
 * for the consumer ordering system.
 */

$(document).ready(function() {
    // Get CSRF token from the page
    const csrfToken = $('#token').val();
    
    // Initialize cart as an empty object
    let cart = {};
    
    // Configure toastr notifications for a more professional look
    toastr.options = {
        closeButton: true,
        debug: false,
        newestOnTop: true,
        progressBar: true,
        positionClass: "toast-top-right",
        preventDuplicates: false,
        onclick: null,
        showDuration: "300",
        hideDuration: "1000",
        timeOut: "5000",
        extendedTimeOut: "1000",
        showEasing: "swing",
        hideEasing: "linear",
        showMethod: "fadeIn",
        hideMethod: "fadeOut"
    };
    
    /**
     * Show a professional notification with icon and structured content
     * @param {string} type - The type of notification: 'success', 'info', 'warning', 'error'
     * @param {string} title - Bold title for the notification
     * @param {string} message - Detailed message content
     */
    function showNotification(type, title, message) {
        let icon = '';
        
        // Set appropriate icon based on notification type
        switch(type) {
            case 'success':
                icon = '<i class="mdi mdi-check-circle me-2"></i>';
                break;
            case 'info':
                icon = '<i class="mdi mdi-information me-2"></i>';
                break;
            case 'warning':
                icon = '<i class="mdi mdi-alert me-2"></i>';
                break;
            case 'error':
                icon = '<i class="mdi mdi-alert-circle me-2"></i>';
                break;
        }
        
        // Create structured content with title and message
        const content = `
            <div class="d-flex align-items-start">
                <div class="notification-icon">${icon}</div>
                <div class="notification-content">
                    <div class="fw-bold">${title}</div>
                    <div class="small">${message}</div>
                </div>
            </div>
        `;
        
        // Show the notification using toastr
        toastr[type](content);
    }
    
    // Load products on page load
    loadProducts();
    
    // Event delegation for product-related actions
    $(document).on('click', '.add-to-cart-btn', function() {
        const productId = $(this).data('product-id');
        const unit = $(this).data('unit');
        addToCart(productId, unit);
    });
    
    $(document).on('click', '.remove-from-cart', function() {
        const cartKey = $(this).data('cart-key');
        decrementCartItem(cartKey);
    });
    
    // Place order event handler
    $('#place-order-btn').on('click', function() {
        placeOrder();
    });
    
    // Toggle delivery address form
    $('#new-address-toggle').on('change', function() {
        $('#existing-address-container').toggle(!this.checked);
        $('#new-address-container').toggle(this.checked);
    });
    
    /**
     * Load products from the backend
     */
    function loadProducts() {
        $('#product-catalog').html(
            `<div class="d-flex justify-content-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>`
        );
        
        // Make AJAX request to get products
        $.ajax({
            url: '../backend/ajax_handler.php',
            type: 'POST',
            data: {
                controller: 'ConsumerOrderController',
                action: 'get_products',
                token: csrfToken
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    // Debug log to see what's coming from the server
                    console.log('Server response:', data);
                    
                    if (data.status === 1 && data.data && data.data.products) {
                        // Log the first product to inspect farm data
                        if (data.data.products.length > 0) {
                            console.log('First product:', data.data.products[0]);
                        }
                        
                        displayProducts(data.data.products);
                    } else {
                        $('#product-catalog').html(
                            `<div class="alert alert-warning">
                                <i class="mdi mdi-alert-circle me-2"></i>
                                ${data.message || 'No products found'}
                            </div>`
                        );
                    }
                } catch (e) {
                    console.error('Error parsing response:', e);
                    $('#product-catalog').html(
                        `<div class="alert alert-danger">
                            <i class="mdi mdi-block-helper me-2"></i>
                            Error loading products. Please refresh the page and try again.
                        </div>`
                    );
                }
            },
            error: function(xhr) {
                console.error('AJAX error:', xhr);
                $('#product-catalog').html(
                    `<div class="alert alert-danger">
                        <i class="mdi mdi-block-helper me-2"></i>
                        Connection error. Please check your internet connection and try again.
                    </div>`
                );
            }
        });
    }
    
    /**
     * Display products in the product catalog
     * @param {Array} products List of products to display
     */
    function displayProducts(products) {
        if (!products || products.length === 0) {
            $('#product-catalog').html(
                `<div class="alert alert-info">
                    <i class="mdi mdi-information me-2"></i>
                    No products are available at this time.
                </div>`
            );
            return;
        }
        
        // Group products by category for better display
        const productsByCategory = {};
        products.forEach(product => {
            const category = product.category || 'Uncategorized';
            if (!productsByCategory[category]) {
                productsByCategory[category] = [];
            }
            productsByCategory[category].push(product);
        });
        
        let html = '';
        
        // For each category, create a section
        for (const category in productsByCategory) {
            html += `
            <div class="product-category mb-4">
                <h5 class="category-title bg-light p-2 rounded-top">${category}</h5>
                <div class="row">`;
            
            // Add products in this category
            productsByCategory[category].forEach(product => {
                const stockClass = product.stock_status === 'out_of_stock' ? 'text-danger' : 
                                (product.stock_status === 'low_stock' ? 'text-warning' : 'text-success');
                                
                const stockText = product.stock_status === 'out_of_stock' ? 'Out of Stock' : 
                               (product.stock_status === 'low_stock' ? 'Low Stock' : 'In Stock');
                
                // Disable buttons if out of stock
                const disabledAttr = product.stock_status === 'out_of_stock' ? 'disabled' : '';
                
                html += `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="card product-card">
                        <img src="${product.image}" class="card-img-top" alt="${product.name}" style="height: 180px; object-fit: cover;">
                        <div class="card-body">
                            <h5 class="card-title">${product.name}</h5>
                            <p class="card-text small">${product.description || 'No description available'}</p>
                            <p class="card-text small text-primary"><strong>Farm:</strong> ${product.farm_name || 'Unknown'}</p>
                            <p class="card-text small"><strong>Location:</strong> ${product.farm_location || 'N/A'}</p>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="${stockClass}"><i class="mdi mdi-circle-small"></i> ${stockText}</span>
                                <span class="text-muted small">Stock: ${product.stock_quantity}</span>
                            </div>
                            
                            <div class="price-box text-center p-2 border rounded mt-3">
                                <div class="small text-muted">Price Per Tray</div>
                                <div class="fw-bold">₱${product.price_per_tray.toFixed(2)}</div>
                                <button class="btn btn-sm btn-primary mt-1 add-to-cart-btn" data-product-id="${product.id}" data-unit="tray" ${disabledAttr}>
                                    <i class="mdi mdi-plus"></i> Add to Cart
                                </button>
                            </div>
                        </div>
                    </div>
                </div>`;
            });
            
            html += `
                </div>
            </div>`;
        }
        
        $('#product-catalog').html(html);
    }
    
    /**
     * Add a product to the cart
     * @param {number} productId ID of the product to add
     * @param {string} unit 'tray' (only option now)
     */
    function addToCart(productId, unit) {
        // Always use tray as the unit now (per egg option removed)
        unit = 'tray';
        
        // Find the product in the catalog
        $.ajax({
            url: '../backend/ajax_handler.php',
            type: 'POST',
            data: {
                controller: 'ConsumerOrderController',
                action: 'get_products',
                token: csrfToken
            },
            success: function(response) {
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.status === 1 && data.data && data.data.products) {
                        const products = data.data.products;
                        const product = products.find(p => parseInt(p.id) === parseInt(productId));
                        
                        if (product) {
                            // Create unique key for product
                            const cartKey = `${productId}-tray`;
                            
                            // Get available stock quantity
                            const availableStock = parseInt(product.stock_quantity) || 0;
                            
                            // Check if the item is already in cart
                            if (cart[cartKey]) {
                                // Calculate total quantity after increment
                                const newQuantity = cart[cartKey].quantity + 1;
                                
                                // Check if there's enough stock
                                if (newQuantity <= availableStock) {
                                    cart[cartKey].quantity++;
                                    
                                    // Update the cart display
                                    updateCartDisplay();
                                    
                                    // Show success notification
                                    showNotification('success', 'Added to Cart', `${product.name} from ${product.farm_name || 'Unknown'} has been added to your cart.`);
                                } else {
                                    // Show error if not enough stock
                                    showNotification('error', 'Stock Limit Reached', `Cannot add more ${product.name}. Only ${availableStock} available in stock.`);
                                }
                            } else {
                                // For new items, check if there's stock available
                                if (availableStock > 0) {
                                    // Add new item to cart
                                    cart[cartKey] = {
                                        id: productId,
                                        name: product.name,
                                        unit: 'tray',
                                        farm: product.farm_name || 'Unknown',
                                        price: product.price_per_tray,
                                        maxQuantity: availableStock, // Store max quantity for reference
                                        quantity: 1
                                    };
                                    
                                    // Update the cart display
                                    updateCartDisplay();
                                    
                                    // Show notification
                                    showNotification('success', 'Added to Cart', `${product.name} from ${product.farm_name || 'Unknown'} has been added to your cart.`);
                                } else {
                                    // Show error if out of stock
                                    showNotification('error', 'Out of Stock', `${product.name} is currently out of stock.`);
                                }
                            }
                        } else {
                            showNotification('error', 'Product Not Found', 'The requested product could not be found in our catalog.');
                        }
                    }
                } catch (error) {
                    console.error('Error processing product:', error);
                    showNotification('error', 'System Error', 'We encountered an error while adding the product to your cart. Please try again.');
                }
            },
            error: function() {
                showNotification('error', 'Connection Error', 'Connection error. Please try again.');
            }
        });
    }
    
    /**
     * Decrement a product quantity in the cart
     * @param {string} cartKey The key of the item to decrement
     */
    function decrementCartItem(cartKey) {
        if (cart[cartKey]) {
            const itemName = cart[cartKey].name;
            const itemFarm = cart[cartKey].farm || 'Unknown';
            
            // Decrease quantity by 1
            cart[cartKey].quantity--;
            
            // If quantity is now 0, remove the item from cart
            if (cart[cartKey].quantity <= 0) {
                delete cart[cartKey];
                showNotification('info', 'Item Removed', `${itemName} from ${itemFarm} has been removed from your cart.`);
            } else {
                showNotification('info', 'Quantity Updated', `${itemName} from ${itemFarm} quantity has been decreased.`);
            }
            
            // Update the display
            updateCartDisplay();
        }
    }
    
    /**
     * Remove a product completely from the cart
     * @param {string} cartKey The key of the item to remove
     */
    function removeFromCart(cartKey) {
        if (cart[cartKey]) {
            const itemName = cart[cartKey].name;
            const itemFarm = cart[cartKey].farm || 'Unknown';
            
            // Remove the item from the cart
            delete cart[cartKey];
            
            // Update the display
            updateCartDisplay();
            
            // Show notification
            showNotification('info', 'Item Removed', `${itemName} from ${itemFarm} has been removed from your cart.`);
        }
    }
    
    /**
     * Update the cart summary display
     */
    function updateCartDisplay() {
        const tbody = $('#order-summary-tbody');
        tbody.empty();
        
        let subtotal = 0;
        const deliveryFee = 50; // Default delivery fee
        let discount = 0;
        
        if (Object.keys(cart).length === 0) {
            tbody.html('<tr><td colspan="3" class="text-center">Your cart is empty</td></tr>');
        } else {
            for (const key in cart) {
                if (cart.hasOwnProperty(key)) {
                    const item = cart[key];
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    
                    // Check if we're at max stock for this item
                    const isAtMaxStock = item.maxQuantity && item.quantity >= item.maxQuantity;
                    const stockWarning = isAtMaxStock ? 
                        `<small class="text-danger d-block">Max stock reached</small>` : 
                        `<small class="text-muted d-block">Stock: ${item.maxQuantity || 'Limited'}</small>`;
                    
                    tbody.append(`
                        <tr>
                            <td>
                                <b>${item.name}</b><br>
                                <small>Farm: ${item.farm || 'Unknown'}</small>
                            </td>
                            <td>
                                ${item.quantity}
                                <div class="btn-group-vertical btn-group-sm ms-1">
                                    <button class="btn btn-xs btn-link p-0 add-to-cart-btn" 
                                        data-product-id="${item.id}" 
                                        data-unit="${item.unit}" 
                                        ${isAtMaxStock ? 'disabled' : ''}>
                                        <i class="mdi mdi-plus ${isAtMaxStock ? 'text-muted' : 'text-success'}"></i>
                                    </button>
                                    <button class="btn btn-xs btn-link p-0 remove-from-cart" data-cart-key="${key}">
                                        <i class="mdi mdi-minus text-danger"></i>
                                    </button>
                                </div>
                                ${stockWarning}
                            </td>
                            <td>₱${itemTotal.toFixed(2)}</td>
                        </tr>
                    `);
                }
            }
        }
        
        // Update the summary amounts
        $('#subtotal').text(`₱${subtotal.toFixed(2)}`);
        $('#delivery-fee').text(`₱${deliveryFee.toFixed(2)}`);
        $('#discount').text(`-₱${discount.toFixed(2)}`);
        
        const total = subtotal + deliveryFee - discount;
        $('#total').text(`₱${total.toFixed(2)}`);
        
        // Enable or disable the checkout button
        $('#place-order-btn').prop('disabled', Object.keys(cart).length === 0);
    }
    
    /**
     * Verify that all items in cart have sufficient stock
     * @returns {Promise} Promise that resolves with true if stock is available, false otherwise
     */
    function validateStockAvailability() {
        return new Promise((resolve) => {
            // Get latest product data from server
            $.ajax({
                url: '../backend/ajax_handler.php',
                type: 'POST',
                data: {
                    controller: 'ConsumerOrderController',
                    action: 'get_products',
                    token: csrfToken
                },
                success: function(response) {
                    try {
                        const data = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        if (data.status === 1 && data.data && data.data.products) {
                            const products = data.data.products;
                            let stockValid = true;
                            let errorMessages = [];
                            
                            // Check each cart item against latest stock data
                            for (const key in cart) {
                                if (cart.hasOwnProperty(key)) {
                                    const cartItem = cart[key];
                                    const productId = parseInt(cartItem.id);
                                    const quantity = cartItem.quantity;
                                    
                                    // Find product in latest data
                                    const product = products.find(p => parseInt(p.id) === productId);
                                    
                                    if (product) {
                                        const availableStock = parseInt(product.stock_quantity) || 0;
                                        
                                        // Update maxQuantity in cart item for reference
                                        cartItem.maxQuantity = availableStock;
                                        
                                        // Check if requested quantity exceeds available stock
                                        if (quantity > availableStock) {
                                            stockValid = false;
                                            errorMessages.push(`${product.name} - Only ${availableStock} available, you requested ${quantity}`);
                                        }
                                        
                                        // Check if product is out of stock
                                        if (product.stock_status === 'out_of_stock') {
                                            stockValid = false;
                                            errorMessages.push(`${product.name} is now out of stock`);
                                        }
                                    } else {
                                        // Product not found in latest data
                                        stockValid = false;
                                        errorMessages.push(`${cartItem.name} is no longer available`);
                                    }
                                }
                            }
                            
                            if (!stockValid) {
                                // Show error messages
                                showNotification('error', 'Stock Changes Detected', 'Some products in your cart have availability changes.');
                                errorMessages.forEach(msg => {
                                    showNotification('warning', 'Stock Issue', msg);
                                });
                                
                                // Update cart display with latest stock information
                                updateCartDisplay();
                            }
                            
                            resolve(stockValid);
                        } else {
                            showNotification('error', 'Verification Failed', 'Could not verify product availability. Please try again.');
                            resolve(false);
                        }
                    } catch (error) {
                        console.error('Error parsing response:', error);
                        showNotification('error', 'System Error', 'We encountered an error while verifying product availability. Please try again.');
                        resolve(false);
                    }
                },
                error: function() {
                    showNotification('error', 'Connection Error', 'Network error while verifying product availability. Please check your connection.');
                    resolve(false);
                }
            });
        });
    }
    
    /**
     * Place the order with the current cart items
     */
    async function placeOrder() {
        if (Object.keys(cart).length === 0) {
            showNotification('warning', 'Empty Cart', 'Your cart is empty. Please add some products before checkout.');
            return;
        }
        
        const termsAccepted = $('#terms-checkbox').prop('checked');
        if (!termsAccepted) {
            showNotification('warning', 'Terms Required', 'Please accept the terms and conditions to proceed with your order.');
            return;
        }
        
        // Verify stock availability before proceeding
        const stockAvailable = await validateStockAvailability();
        if (!stockAvailable) {
            return; // Stock validation failed, messages already shown
        }
        
        // Get address information
        let addressId = null;
        let newAddress = null;
        
        if ($('#new-address-toggle').prop('checked')) {
            // Using a new address
            newAddress = {
                street: $('#new-address-street').val().trim(),
                city: $('#new-address-city').val().trim(),
                state: $('#new-address-state').val().trim(),
                postal_code: $('#new-address-postal').val().trim(),
                phone: $('#new-address-phone').val().trim(),
                save_address: $('#save-address-checkbox').prop('checked')
            };
            
            // Basic validation
            if (!newAddress.street || !newAddress.city || !newAddress.postal_code || !newAddress.phone) {
                showNotification('error', 'Missing Information', 'Please fill in all required address fields to continue.');
                return;
            }
        } else {
            // Using an existing address
            addressId = $('#delivery-address').val(); // Fix ID to match the HTML
            if (!addressId) {
                showNotification('error', 'Address Required', 'Please select a delivery address for your order.');
                return;
            }
        }
        
        // Prepare order items from cart
        const items = [];
        for (const key in cart) {
            if (cart.hasOwnProperty(key)) {
                const item = cart[key];
                items.push({
                    product_id: parseInt(item.id),
                    quantity: item.quantity,
                    packaging_type: item.unit === 'tray' ? 'full_tray' : 'single'
                });
            }
        }
        
        // Show loading overlay
        $('#loading-overlay').show();
        
        // Submit the order via AJAX
        $.ajax({
            url: '../backend/ajax_handler.php',
            type: 'POST',
            data: {
                controller: 'ConsumerOrderController',
                action: 'place_order',
                token: csrfToken, // Add CSRF token for security validation
                items: JSON.stringify(items),
                address_id: addressId,
                new_address: newAddress ? JSON.stringify(newAddress) : null,
                notes: $('#delivery-notes').val() ? $('#delivery-notes').val().trim() : '',
                use_loyalty_points: $('#use-loyalty-points').prop('checked')
            },
            success: function(response) {
                $('#loading-overlay').hide();
                
                try {
                    const data = typeof response === 'string' ? JSON.parse(response) : response;
                    
                    if (data.status === 1) {
                        // Order placed successfully
                        // Clear the cart
                        cart = {};
                        updateCartDisplay();
                        
                        // Show success message with order details
                        showNotification(
                            'success', 
                            'Order Placed Successfully!', 
                            'Thank you for your order. You will be redirected to your orders page momentarily.'
                        );
                        
                        // Redirect to orders page after 2 seconds
                        setTimeout(function() {
                            window.location.href = '<?= view("consumer.orders") ?>';
                        }, 2000);
                    } else {
                        // Show error message
                        showNotification(
                            'error', 
                            'Order Failed', 
                            data.message || 'There was a problem placing your order. Please try again.'
                        );
                    }
                } catch (e) {
                    console.error('Error processing response:', e);
                    showNotification(
                        'error', 
                        'System Error', 
                        'There was a problem processing your order. Please try again.'
                    );
                }
            },
            error: function() {
                // Hide loading overlay
                $('#loading-overlay').hide();
                showNotification(
                    'error',
                    'Connection Error',
                    'There was a problem connecting to the server. Please check your internet connection and try again.'
                );
            }
        });
    }
});
