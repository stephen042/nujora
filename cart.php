<?php
require 'app/config.php';

// Detect user login
$user_id = $_SESSION['user_id'] ?? null;

// Initialize cart summary
$cart_items = [];
$subtotal = 0;
$total_items = 0;

try {

    if ($user_id) {
        // Logged-in user's cart from database
        $stmt = $pdo->prepare("
            SELECT 
                ci.id AS cart_id,
                p.id AS product_id,
                p.name,
                p.price,
                p.image_url,
                p.stock,
                ci.quantity,
                ci.variant_id,
                ci.variant_options,
                (p.price * ci.quantity) AS item_total
            FROM cart_items ci
            JOIN products p ON ci.product_id = p.id
            WHERE ci.buyer_id = ?
        ");
        $stmt->execute([$user_id]);
        $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {

        // Guest cart from session
        if (!$user_id && !empty($_SESSION['guest_cart'])) {

            foreach ($_SESSION['guest_cart'] as $key => $item) {

                $product_id = $item['product_id'];
                $quantity = intval($item['quantity']);
                $variant_id = $item['variant_id'] ?? null;

                // Make sure variant_options is ALWAYS a JSON string
                $variant_options = $item['variant_options'] ?? '{}';
                if (is_array($variant_options)) {
                    $variant_options = json_encode($variant_options);
                }

                // Fetch product details
                $stmt = $pdo->prepare("
                    SELECT id, name, price, image_url, stock
                    FROM products 
                    WHERE id = ?
                ");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($product) {

                    $product['quantity'] = $quantity;
                    $product['price'] = floatval($product['price']);
                    $product['item_total'] = $product['price'] * $quantity;

                    // keep variant info
                    $product['variant_id'] = $variant_id;
                    $product['variant_options'] = $variant_options;
                    $product['cart_key'] = $key; // very important

                    $cart_items[] = $product;
                }
            }
        }
    }

    // Calculate totals
    foreach ($cart_items as $item) {
        $subtotal += floatval($item['item_total']);
        $total_items += intval($item['quantity']);
    }

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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --primary-color: #f57c00;
            /* Bright Orange */
            --secondary-color: #ef6c00;
            /* Deep Orange */
            --accent-color: #ffb74d;
            /* Soft Yellow-Orange */
            --light-bg: #fff8f0;
            /* Warm Light Background */
            --dark-text: #1e1e1e;
            /* Darker Text for Better Contrast */
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        /* ================= BUTTONS ================= */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #e65100;
            border-color: #e65100;
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .product-img {
            width: 90px;
            height: 90px;
            object-fit: contain;
            border-radius: 6px;
        }

        .summary-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        @media (max-width: 576px) {
            .product-img {
                width: 70px;
                height: 70px;
            }
        }
    </style>
</head>

<body>

    <?php include 'includes/nav.php'; ?>

    <div class="cart-container py-4 mb-5">
        <div class="container">
            <h3 class="mb-4 fw-semibold">My Cart</h3>

            <?php if (!empty($cart_items)): ?>
                <div class="row">
                    <div class="col-lg-8">

                        <?php foreach ($cart_items as $item): ?>
                            <div class="product-card p-3 mb-3">
                                <div class="row align-items-center">

                                    <div class="col-3 col-md-2">
                                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'uploads/default-product.png') ?>"
                                            class="img-fluid product-img">
                                    </div>

                                    <div class="col-6 col-md-5">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>

                                        <small class="text-muted">₦<?= number_format($item['price'], 2) ?></small>

                                        <!-- VARIANTS DISPLAY -->
                                        <?php if (!empty($item['variant_options'])): ?>
                                            <?php
                                            $opts = is_string($item['variant_options']) ? json_decode($item['variant_options'], true) : $item['variant_options'];
                                            ?>
                                            <?php if (!empty($opts)): ?>
                                                <div class="mt-1 text-muted small">
                                                    <?php foreach ($opts as $key => $value): ?>
                                                        <div>
                                                            <?= ucfirst($key) ?>: <strong><?= htmlspecialchars($value) ?></strong>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-12 col-md-3 mt-2 mt-md-0">
                                        <div class="d-flex align-items-center justify-content-center">

                                            <button class="btn btn-sm btn-outline-secondary"
                                                onclick="updateQuantity('<?= $user_id ? $item['cart_id'] : $item['cart_key'] ?>', -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>

                                            <input type="text" readonly value="<?= $item['quantity'] ?>"
                                                class="form-control text-center mx-1" style="width: 50px;">

                                            <button class="btn btn-sm btn-outline-secondary"
                                                onclick="updateQuantity('<?= $user_id ? $item['cart_id'] : $item['cart_key'] ?>', 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>

                                        </div>
                                    </div>

                                    <div class="col-12 col-md-2 mt-2 mt-md-0 text-end">
                                        <strong>₦<?= number_format($item['item_total'], 2) ?></strong>
                                        <br>
                                        <button class="btn btn-sm text-danger mt-1"
                                            onclick="removeFromCart('<?= $user_id ? $item['cart_id'] : $item['cart_key'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <a href="products.php" class="btn btn-outline-primary">← Continue Shopping</a>
                    </div>

                    <div class="col-lg-4">
                        <div class="summary-card">
                            <h6 class="fw-semibold mb-3">Order Summary</h6>

                            <div class="d-flex justify-content-between">
                                <span>Subtotal (<?= $total_items ?> items)</span>
                                <strong>₦<?= number_format($subtotal, 2) ?></strong>
                            </div>

                            <div class="d-flex justify-content-between mt-2">
                                <span>Delivery Fee</span>
                                <strong>₦<?= number_format($delivery_fee, 2) ?></strong>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between fs-5 fw-bold">
                                <span>Total</span>
                                <span>₦<?= number_format($total, 2) ?></span>
                            </div>

                            <a href="checkout.php" class="btn btn-primary w-100 mt-3">Proceed to Checkout</a>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <div class="text-center py-5">
                    <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Browse products and add items to your cart</p>
                    <a href="products.php" class="btn btn-primary">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'includes/bottomNav.php'; ?>
    <?php include 'includes/script.php'; ?>

    <script>
        function updateQuantity(id, change) {
            fetch('update_cart_quantity.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}&change=${change}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message);
                });
        }

        function removeFromCart(id) {
            if (!confirm("Remove this item?")) return;
            fetch('remove_cart_item.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `id=${id}`
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) location.reload();
                    else alert(data.message);
                });
        }
    </script>
</body>

</html>