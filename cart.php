<?php
// Include configuration (once)
require_once __DIR__ . '/app/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    $msg = '<div class="alert alert-warning text-center">You need to login or create account to complete your order</div>';
    $_SESSION['statusMessage'] = $msg;
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // Fetch cart items with product details
    $stmt = $pdo->prepare("
        SELECT 
            ci.id AS cart_id, 
            p.id AS product_id,
            p.name, 
            p.price, 
            p.image_url, 
            p.stock,
            ci.quantity,
            (p.price * ci.quantity) AS item_total
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.buyer_id = ?
    ");
    $stmt->execute([$user_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate cart totals
    $subtotal = 0;
    $total_items = 0;
    foreach ($cart_items as $item) {
        $subtotal += $item['item_total'];
        $total_items += $item['quantity'];
    }

    // Delivery fee (example: free for orders over ₦5000)
    $delivery_fee = ($subtotal > 5000) ? 0 : 500;
    $total = $subtotal + $delivery_fee;
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #FF6B6B;
            --secondary: #4ECDC4;
            --dark: #292F36;
            --light: #F7FFF7;
            --accent: #FFE66D;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .product-card:hover {
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .product-img {
            width: 120px;
            height: 120px;
            object-fit: contain;
            border-radius: 8px;
        }

        .quantity-control {
            width: 120px;
        }

        .quantity-control .btn {
            width: 36px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: white;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .nav-bottom .nav-link {
            padding: 12px 0;
            font-size: 0.8rem;
            color: #666;
        }

        .nav-bottom .nav-link.active {
            color: var(--primary);
            font-weight: 600;
        }

        .nav-bottom .nav-link i {
            font-size: 1.2rem;
            display: block;
            margin-bottom: 5px;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .btn-primary:hover {
            background-color: #e05555;
            border-color: #e05555;
        }

        .btn-outline-primary {
            color: var(--primary);
            border-color: var(--primary);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .text-primary {
            color: var(--primary) !important;
        }

        .animate-bounce {
            animation: bounce 0.5s;
        }

        @keyframes bounce {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-5px);
            }
        }

        .empty-cart {
            text-align: center;
            padding: 60px 0;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>
    <div class="cart-container py-4 mb-5">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">My Cart</h2>
            </div>

            <?php if (!empty($cart_items)): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="product-card p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2 mb-3 mb-md-0">
                                        <img src="<?= htmlspecialchars($item['image_url']) ?? "uploads/default-product.png" ?>"
                                            alt="<?= htmlspecialchars($item['name']) ?>"
                                            class="img-fluid product-img">
                                    </div>
                                    <div class="col-md-5">
                                        <h5 class="mb-1"><?= htmlspecialchars($item['name']) ?></h5>
                                        <p class="mb-1 text-muted">In Stock: <?= $item['stock'] ?></p>
                                        <p class="mb-0 text-primary fw-bold">₦<?= number_format($item['price'], 2) ?></p>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group quantity-control mx-auto">
                                            <button class="btn btn-outline-secondary"
                                                type="button"
                                                onclick="updateQuantity(<?= $item['cart_id'] ?>, -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="text"
                                                class="form-control text-center"
                                                value="<?= $item['quantity'] ?>"
                                                id="qty-<?= $item['cart_id'] ?>"
                                                readonly>
                                            <button class="btn btn-outline-secondary"
                                                type="button"
                                                onclick="updateQuantity(<?= $item['cart_id'] ?>, 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <p class="fw-bold mb-1">₦<?= number_format($item['item_total'], 2) ?></p>
                                        <button class="btn btn-sm btn-outline-danger"
                                            onclick="removeFromCart(<?= $item['cart_id'] ?>)">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="product.php" class="btn btn-outline-primary">
                                <i class="fas fa-An error occurred. Please try again.arrow-left me-2"></i> Continue Shopping
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="summary-card">
                            <h5 class="mb-3">Order Summary</h5>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?= $total_items ?> items)</span>
                                <span>₦<?= number_format($subtotal, 2) ?></span>
                            </div>

                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <span>₦<?= number_format($delivery_fee, 2) ?></span>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between mb-3 fw-bold fs-5">
                                <span>Total</span>
                                <span class="text-primary">₦<?= number_format($total, 2) ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-primary w-100 py-2">
                                Proceed to Checkout <i class="fas fa-arrow-right ms-2"></i>
                            </a>

                            <div class="mt-3 text-center text-muted small">
                                By placing your order, you agree to our <a href="#">Terms of Service</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart"></i>
                    <h3 class="mb-3">Your cart is empty</h3>
                    <p class="text-muted mb-4">Browse our products and add items to your cart</p>
                    <a href="home.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Script -->
    <?php include 'includes/script.php'; ?>
    <script>
        function updateQuantity(cartId, change) {
            fetch('update_cart_quantity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `cart_id=${cartId}&change=${change}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Update quantity
                        document.getElementById('qty-' + cartId).value = data.newQuantity;

                        // Optionally reload total, or update DOM
                        location.reload(); // Or update only price if you want
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error updating quantity:', error);
                });
        }

        function removeFromCart(cartId) {
            if (!confirm("Are you sure you want to remove this item?")) return;

            fetch('remove_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `cart_id=${cartId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error removing item:', error);
                });
        }
    </script>
</body>

</html>