<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Get seller_id and order_id from URL
if (!isset($_GET['seller_id'], $_GET['order_id'])) {
    echo "Missing required information.";
    exit;
}

$seller_id = (int) $_GET['seller_id'];
$order_id = htmlspecialchars($_GET['order_id']);

// Fetch seller info
$stmt = $pdo->prepare("SELECT name, shop_name FROM users WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

if (!$seller) {
    echo "Seller not found.";
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Rate Seller | TrendyMart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .star-select {
            font-size: 24px;
        }
        .form-section {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container mt-5 form-section">
        <h4 class="mb-4 text-center text-primary">Rate Seller</h4>

        <div class="card mb-4">
            <div class="card-body">
                <h5><?= htmlspecialchars($seller['shop_name'] ?? $seller['name']) ?></h5>
                <p>Order ID: <strong><?= htmlspecialchars($order_id) ?></strong></p>
            </div>
        </div>

        <form method="POST" action="submit_rating.php">
            <input type="hidden" name="seller_id" value="<?= $seller_id ?>">
            <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">

            <div class="mb-3">
                <label for="stars" class="form-label">Your Rating</label>
                <select name="stars" id="stars" class="form-select star-select" required>
                    <option value="">Select stars</option>
                    <option value="1">★☆☆☆☆</option>
                    <option value="2">★★☆☆☆</option>
                    <option value="3">★★★☆☆</option>
                    <option value="4">★★★★☆</option>
                    <option value="5">★★★★★</option>
                </select>
            </div>

            <div class="mb-3">
                <label for="review" class="form-label">Your Review (optional)</label>
                <textarea name="review" id="review" class="form-control" placeholder="Write a brief review..." rows="3"></textarea>
            </div>

            <button type="submit" class="btn btn-success w-100">Submit Rating</button>
        </form>

        <div class="text-center mt-3">
            <a href="orders.php" class="btn btn-link">← Back to Orders</a>
        </div>

        <div class="d-grid gap-2 mt-4">
            <!-- Add to Cart Button -->
            <form method="POST" action="cart.php">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" id="cartQuantity" value="1">
                <button type="submit" class="btn btn-add-to-cart btn-lg" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-shopping-cart me-2"></i> Add to Cart
                </button>
            </form>

            <!-- Buy Now Button -->
            <form method="POST" action="checkout.php">
                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                <input type="hidden" name="quantity" id="buyNowQuantity" value="1">
                <button type="submit" class="btn btn-buy-now btn-lg" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                    <i class="fas fa-bolt me-2"></i> Buy Now
                </button>
            </form>
        </div>
    </div>
</body>
</html>
