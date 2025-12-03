<script src="../assets/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/jquery.min.js"></script>
<script src="../assets/js/owl.carousel.min.js"></script>

<!-- livechat JIvoChat -->
<script src="//code.jivosite.com/widget/VuNSxCgqcu" async></script>

<!-- for carting items  -->
<script>
    document.addEventListener("DOMContentLoaded", () => {

        const variants = <?= json_encode($variants) ?>;
        // Example structure:
        // {
        //    "12": {"color":"red","size":"M"},
        //    "13": {"color":"red","size":"L"},
        //    "14": {"color":"blue","size":"M"}
        // }

        const selects = document.querySelectorAll(".variant-select");
        const variantInput = document.getElementById("selected-variant-id");

        function detectVariant() {
            let chosen = {};

            selects.forEach(sel => {
                const name = sel.dataset.attr;
                const value = sel.value;
                if (value) chosen[name] = value;
            });

            // Find matching variant
            variantInput.value = "";
            for (let variantId in variants) {
                const variant = variants[variantId];
                let match = true;

                for (let attr in chosen) {
                    if (variant[attr] !== chosen[attr]) {
                        match = false;
                        break;
                    }
                }

                if (match && Object.keys(chosen).length === Object.keys(variant).length) {
                    variantInput.value = variantId;
                    break;
                }
            }
        }

        selects.forEach(sel => {
            sel.addEventListener("change", detectVariant);
        });


        // Override "Add to Cart" button behavior
        document.querySelectorAll(".add-to-cart").forEach(btn => {
            btn.addEventListener("click", function(e) {
                e.preventDefault();

                const productId = this.dataset.id;
                const variantId = variantInput ? variantInput.value : "";
                let body = `product_id=${productId}&quantity=1`;

                // If there are variants, collect options
                if (selects.length > 0) {
                    if (!variantId) {
                        showToast("Please choose product options", "error");
                        return;
                    }

                    body += `&variant_id=${variantId}`;

                    selects.forEach(sel => {
                        const attr = sel.dataset.attr;
                        const value = sel.value;
                        body += `&${attr}=${value}`;
                    });
                }

                fetch("add_to_cart.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: body
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {

                            // Update top navbar cart count
                            const topBadge = document.getElementById("cart-count");
                            if (topBadge) topBadge.textContent = data.count;

                            // Update bottom navbar cart count
                            const bottomBadge = document.getElementById("bottom-cart-count");
                            if (bottomBadge) bottomBadge.textContent = data.count;

                            showToast(data.message, "success");
                        } else {
                            showToast(data.message, "error");
                        }
                    })
                    .catch(() => showToast("Network error", "error"));
            });
        });

    });
</script>

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

<!-- for toast -->
<script>
    function closeToast(toast) {
        toast.classList.remove("show");
        setTimeout(() => toast.remove(), 400);
    }

    function showToast(message, type = "info", duration = 3000) {
        const container = document.getElementById("toast-container");
        const toast = document.createElement("div");

        toast.className = `custom-toast ${type}`;
        toast.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <span>${message}</span>
                <button class="toast-close-btn"><i class="fas fa-times"></i></button>
            </div>
        `;

        container.appendChild(toast);

        // Close button click
        toast.querySelector(".toast-close-btn").addEventListener("click", () => {
            closeToast(toast);
        });

        // Slide in
        setTimeout(() => toast.classList.add("show"), 50);

        // Auto close
        setTimeout(() => closeToast(toast), duration);
    }
</script>


<!-- Toast Container -->
<style>
    #toast-container {
        display: flex;
        flex-direction: column;
        align-items: flex-end;
        gap: 10px;
    }

    .custom-toast {
        min-width: 260px;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        opacity: 0;
        transform: translateX(120%);
        transition: all 0.4s ease;
        padding: 0.75rem 1rem;
        color: #fff;
        font-size: 0.9rem;
    }

    .custom-toast.show {
        opacity: 1;
        transform: translateX(0);
    }

    .custom-toast.success { background-color: #04a459ff; }
    .custom-toast.error { background-color: #dc3545; }
    .custom-toast.info { background-color: #0d6efd; }
    .custom-toast.warning { 
        background-color: #ffc107; 
        color: #000; 
    }

    /* NEW: Ensure close button works everywhere */
    .toast-close-btn {
        background: none;
        border: none;
        color: white;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        padding: 0 6px;
    }
</style>
<div id="toast-container" class="position-fixed top-0 end-0 p-3" style="z-index:1080;"></div>
