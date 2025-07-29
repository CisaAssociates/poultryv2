document.addEventListener('DOMContentLoaded', function() {
    // Farm filtering
    document.querySelectorAll('[data-farm]').forEach(btn => {
        btn.addEventListener('click', function() {
            const farmId = this.dataset.farm;
            document.querySelectorAll('#product-grid > div').forEach(card => {
                if (farmId === 'all' || card.dataset.farm === farmId) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Update active state
            document.querySelectorAll('[data-farm]').forEach(b => {
                b.classList.toggle('active', b === this);
            });
        });
    });
    
    // Add to cart functionality
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', function() {
            const trayId = this.dataset.tray;
            const price = parseFloat(this.dataset.price);
            const card = this.closest('.product-card');
            
            // Get product details
            const productName = card.querySelector('.card-title').textContent;
            const farmName = card.querySelector('.farm-info h6').textContent;
            const farmLocation = card.querySelector('.farm-info small').textContent;
            
            // Get image
            let imageHtml = '';
            const img = card.querySelector('.product-image');
            if (img) {
                imageHtml = `<img src="${img.src}" class="img-fluid rounded" alt="${productName}">`;
            } else {
                imageHtml = `<div class="bg-light rounded d-flex align-items-center justify-content-center" style="width:100%;height:100%;">
                                <i class="fas fa-egg fa-2x text-muted"></i>
                            </div>`;
            }
            
            // Update modal content
            document.getElementById('cartItemImage').innerHTML = imageHtml;
            document.getElementById('cartItemName').textContent = productName;
            document.getElementById('cartItemFarm').textContent = `${farmName} · ${farmLocation}`;
            document.getElementById('cartItemPrice').textContent = `₱${price.toFixed(2)}`;
            
            // Calculate total
            updateCartPreviewTotal(price);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('cartPreviewModal'));
            modal.show();
            
            // Quantity change handler
            document.getElementById('cartItemQty').addEventListener('change', function() {
                const qty = parseInt(this.value);
                updateCartPreviewTotal(price * qty);
            });
            
            // Add to cart (server-side)
            const qtyInput = document.getElementById('cartItemQty');
            qtyInput.addEventListener('change', function() {
                addToCart(trayId, parseInt(this.value), price);
            });
            
            // Initial add
            addToCart(trayId, 1, price);
        });
    });
    
    function addToCart(trayId, quantity, price) {
        const formData = new FormData();
        formData.append('tray_id', trayId);
        formData.append('quantity', quantity);
        formData.append('price', price);
        formData.append('token', token);
        
        fetch('api/order/add_to_cart.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                console.error('Error adding to cart:', data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    function updateCartPreviewTotal(total) {
        document.getElementById('cartTotalPreview').textContent = `₱${total.toFixed(2)}`;
    }
});