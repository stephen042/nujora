<?php
require 'app/config.php';

// Detect if user is logged in
$user_id = $_SESSION['user_id'] ?? null;

// Initialize
$cart_items = [];
$subtotal = 0;
$total_items = 0;

try {
    if ($user_id) {
        // Logged-in user cart from database
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
    } else {
        // Guest user – load from session (fixed)
        if (!empty($_SESSION['guest_cart'])) {
            foreach ($_SESSION['guest_cart'] as $product_id => $quantity) {
                $stmt = $pdo->prepare("SELECT id, name, price, image_url, stock FROM products WHERE id = ?");
                $stmt->execute([$product_id]);
                $product = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($product) {
                    $product['quantity'] = $quantity;
                    $product['item_total'] = $product['price'] * $quantity;
                    $cart_items[] = $product;
                }
            }
        }
    }

    // Totals
    foreach ($cart_items as $item) {
        $subtotal += $item['item_total'];
        $total_items += $item['quantity'];
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
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }

        .cart-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .product-card {
            border: 1px solid #e0e0e0;
            border-radius: 5px;
        }

        .product-card .row {
            flex-wrap: nowrap;
        }

        @media (max-width: 576px) {
            .product-card .row {
                flex-wrap: wrap;
                /* allow proper wrapping on phones */
            }

            .product-card .col-3.col-md-3 {
                margin-top: 10px;
                margin-bottom: 10px;
            }

            .product-card .d-flex.align-items-center {
                justify-content: space-between;
                width: 100%;
            }

            .product-card button.btn.btn-sm.btn-outline-secondary {
                padding: 4px 8px !important;
            }

            .product-card input.form-control {
                width: 45px !important;
            }
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
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            padding: 20px;
            position: sticky;
            top: 20px;
        }

        .btn-primary {
            background-color: #FF6B6B;
            border: none;
        }

        .btn-primary:hover {
            background-color: #e85c5c;
        }

        .empty-cart {
            text-align: center;
            padding: 70px 0;
        }

        .empty-cart i {
            font-size: 4rem;
            color: #ccc;
        }

        @media (max-width: 768px) {
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
                        <?php foreach ($cart_items as $index => $item): ?>
                            <div class="product-card p-3">
                                <div class="row align-items-center">
                                    <div class="col-3 col-md-2">
                                        <img src="<?= htmlspecialchars($item['image_url'] ?? 'uploads/default-product.png') ?>"
                                            class="img-fluid product-img" alt="Product">
                                    </div>
                                    <div class="col-6 col-md-5">
                                        <h6 class="mb-1"><?= htmlspecialchars($item['name']) ?></h6>
                                        <small class="text-muted">₦<?= number_format($item['price'], 2) ?></small>
                                    </div>
                                    <div class="col-12 col-md-3 mt-2 mt-md-0">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <button class="btn btn-sm btn-outline-secondary"
                                                onclick="updateQuantity('<?= $user_id ? $item['cart_id'] : $item['id'] ?>', -1)">
                                                <i class="fas fa-minus"></i>
                                            </button>

                                            <input type="text" readonly value="<?= $item['quantity'] ?>"
                                                class="form-control text-center mx-1" style="width: 50px;">

                                            <button class="btn btn-sm btn-outline-secondary"
                                                onclick="updateQuantity('<?= $user_id ? $item['cart_id'] : $item['id'] ?>', 1)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="col-12 col-md-2 mt-2 mt-md-0 text-end">
                                        <strong>₦<?= number_format($item['item_total'], 2) ?></strong><br>
                                        <button class="btn btn-sm text-danger mt-1"
                                            onclick="removeFromCart('<?= $user_id ? $item['cart_id'] : $item['id'] ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div class="mt-3">
                            <a href="index.php" class="btn btn-outline-primary">← Continue Shopping</a>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="summary-card">
                            <h6 class="mb-3 fw-semibold">Order Summary</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal (<?= $total_items ?> items)</span>
                                <span>₦<?= number_format($subtotal, 2) ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Delivery Fee</span>
                                <span>₦<?= number_format($delivery_fee, 2) ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between fw-bold fs-5">
                                <span>Total</span>
                                <span>₦<?= number_format($total, 2) ?></span>
                            </div>
                            <a href="checkout.php" class="btn btn-primary w-100 mt-3">
                                Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart mb-3"></i>
                    <h4>Your cart is empty</h4>
                    <p class="text-muted">Browse our products and add items to your cart</p>
                    <a href="index.php" class="btn btn-primary">Start Shopping</a>
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