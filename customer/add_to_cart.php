<?php
require '../app/config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    $msg = '<div class="alert alert-warning text-center">You need to login or create account to complete your order</div>';
    $_SESSION['statusMessage'] = $msg;
    exit;
}

$user_id = $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id > 0 && $quantity > 0) {
    try {
        // Check if the product is already in the cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE buyer_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $cart_item = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($cart_item) {
            // If the product is already in the cart, update the quantity
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $cart_item['id']]);
        } else {
            // If the product is not in the cart, insert it
            $stmt = $pdo->prepare("INSERT INTO cart_items (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        // Redirect back to the cart page
        header('Location: cart.php');
        exit;
    } catch (PDOException $e) {
        die("Error: " . $e->getMessage());
    }
} else {
    // Redirect back if no product ID or invalid quantity is provided
    header('Location: home.php');
    exit;
}