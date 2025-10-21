<script src="../../assets/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/jquery.min.js"></script>
<script src="../../assets/js/owl.carousel.min.js"></script>

<!-- for product details -->
<script>
    function changeImage(thumbnail) {
        document.querySelectorAll('.thumbnail').forEach(img => img.classList.remove('active'));
        thumbnail.classList.add('active');
        document.getElementById('mainImage').src = thumbnail.src;
    }

    function updateQuantity(change) {
        const quantityInput = document.getElementById('quantity');
        let currentQuantity = parseInt(quantityInput.value);
        const maxQuantity = parseInt(quantityInput.max);
        currentQuantity += change;
        if (currentQuantity < 1) currentQuantity = 1;
        if (currentQuantity > maxQuantity) currentQuantity = maxQuantity;
        quantityInput.value = currentQuantity;
        document.getElementById('cartQuantity').value = currentQuantity;
        document.getElementById('buyNowQuantity').value = currentQuantity;
    }
</script>

<!-- for checkout -->
<script>
    // Handle method selection
    document.addEventListener('DOMContentLoaded', function() {
        // Handle method card clicks
        document.querySelectorAll('.method-card').forEach(card => {
            card.addEventListener('click', function() {
                // Find the radio input inside this card
                const radio = this.querySelector('input[type="radio"]');
                if (radio) {
                    radio.checked = true;
                    updateMethodCards();

                    // Handle shipping address visibility
                    if (radio.name === 'delivery_method') {
                        const shippingAddressField = document.getElementById('shippingAddressField');
                        shippingAddressField.style.display = radio.value === 'home_delivery' ? 'block' : 'none';

                        // Update delivery fee display
                        const deliveryFeeElement = document.getElementById('deliveryFee');
                        deliveryFeeElement.textContent = radio.value === 'home_delivery' ? '₦500.00' : '₦0.00';
                        updateOrderTotal(radio.value === 'home_delivery' ? 500 : 0);
                    }
                }
            });
        });

        // Update visual state of method cards
        function updateMethodCards() {
            document.querySelectorAll('.method-card').forEach(card => {
                const radio = card.querySelector('input[type="radio"]');
                if (radio && radio.checked) {
                    card.classList.add('active');
                } else {
                    card.classList.remove('active');
                }
            });
        }

        // Initialize first options as selected
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            if (radio.checked) {
                radio.closest('.method-card').classList.add('active');
                if (radio.name === 'delivery_method') {
                    const shippingAddressField = document.getElementById('shippingAddressField');
                    shippingAddressField.style.display = radio.value === 'home_delivery' ? 'block' : 'none';
                }
            }
        });

        // Update order total with delivery fee
        function updateOrderTotal(deliveryFee) {
            const subtotal = <?= $subtotal ?>;
            const discount = <?= $discount_amount ?>;
            const total = subtotal - discount + deliveryFee;
            document.getElementById('orderTotal').textContent = '₦' + total.toFixed(2);
        }

        // Form validation before submission
        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            const deliveryMethod = document.querySelector('input[name="delivery_method"]:checked').value;
            const shippingAddress = document.getElementById('shipping_address').value;

            if (deliveryMethod === 'home_delivery' && shippingAddress.trim() === '') {
                e.preventDefault();
                alert('Please enter your shipping address for home delivery');
                document.getElementById('shipping_address').focus();
            }
        });
    });
</script>

<!-- for products -->
<script>
    // Quantity selector functionality
    document.addEventListener('DOMContentLoaded', function() {
        const minusBtn = document.querySelector('.quantity-btn.minus');
        const plusBtn = document.querySelector('.quantity-btn.plus');
        const quantityInput = document.querySelector('.quantity-input');

        if (minusBtn && plusBtn && quantityInput) {
            minusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                if (value > 1) {
                    quantityInput.value = value - 1;
                }
            });

            plusBtn.addEventListener('click', function() {
                let value = parseInt(quantityInput.value);
                let max = parseInt(quantityInput.getAttribute('max'));
                if (value < max) {
                    quantityInput.value = value + 1;
                }
            });
        }

        // Add to cart functionality
        const addToCartBtn = document.querySelector('.btn-cart');
        if (addToCartBtn) {
            addToCartBtn.addEventListener('click', function() {
                const productId = <?= $product['id'] ?>;
                const quantity = document.querySelector('.quantity-input')?.value || 1;

                // Here you would typically make an AJAX call to add to cart
                alert(`Added ${quantity} item(s) to cart!`);
            });
        }

        // Buy now functionality
        const buyNowBtn = document.querySelector('.btn-buy');
        if (buyNowBtn) {
            buyNowBtn.addEventListener('click', function() {
                const productId = <?= $product['id'] ?>;
                const quantity = document.querySelector('.quantity-input')?.value || 1;

                // Redirect to checkout with this product
                window.location.href = `checkout.php?product_id=${productId}&quantity=${quantity}`;
            });
        }
    });
</script>

<!-- for orders -->
<script>
    // Simple script to highlight the current tracking step
    document.addEventListener('DOMContentLoaded', function() {
        const orderCards = document.querySelectorAll('.order-card');

        orderCards.forEach(card => {
            const status = card.querySelector('.status-badge').textContent.toLowerCase();
            const steps = card.querySelectorAll('.tracking-step');

            steps.forEach((step, index) => {
                if (index < steps.length - 1) {
                    if (status === 'completed') {
                        step.classList.add('step-complete');
                    } else if (index === 0 && status === 'pending') {
                        step.classList.add('step-active');
                    } else if (index === 1 && status === 'processing') {
                        step.classList.add('step-active');
                    } else if (index === 2 && status === 'shipped') {
                        step.classList.add('step-active');
                    }
                }
            });
        });
    });
</script>