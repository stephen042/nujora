<?php
// rate_product.php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit;
}

// Get product_id and order_id
if (!isset($_GET['product_id'], $_GET['order_id'])) {
    echo "Missing required information.";
    exit;
}

$product_id = (int) $_GET['product_id'];
$order_id = (int) $_GET['order_id'];

// Check if user purchased this product
$stmt = $pdo->prepare("SELECT oi.id FROM order_items oi JOIN orders o ON o.id = oi.order_id WHERE o.buyer_id = ? AND oi.product_id = ? AND o.id = ?");
$stmt->execute([$_SESSION['user_id'], $product_id, $order_id]);

if ($stmt->rowCount() === 0) {
    echo "You can only rate products you have purchased.";
    exit;
}

// Get product details
$stmt = $pdo->prepare("SELECT name FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();
if (!$product) {
    echo "Product not found.";
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Rate Product | TrendyMart</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-5">
    <h4 class="text-center text-primary">Rate Product</h4>

    <div class="card mb-4">
        <div class="card-body">
            <h5><?= htmlspecialchars($product['name']) ?></h5>
            <p>Order ID: <strong><?= htmlspecialchars($order_id) ?></strong></p>
        </div>
    </div>

    <form method="POST" action="submit_product_rating.php">
        <input type="hidden" name="product_id" value="<?= $product_id ?>">
        <input type="hidden" name="order_id" value="<?= htmlspecialchars($order_id) ?>">

        <div class="mb-3">
            <label for="stars" class="form-label">Your Rating</label>
            <select name="stars" id="stars" class="form-select" required>
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
</div>
</body>
</html>
