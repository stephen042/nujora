<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Verify user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// CSRF protection
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit;
}

// Validate input
$required = ['payment_method', 'delivery_method'];
if ($_POST['delivery_method'] === 'home_delivery') {
    $required[] = 'shipping_address';
}

foreach ($required as $field) {
    if (empty($_POST[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

$buyer_id = $_SESSION['user_id'];
$payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_SPECIAL_CHARS);
$delivery_method = filter_input(INPUT_POST, 'delivery_method', FILTER_SANITIZE_SPECIAL_CHARS);
$shipping_address = filter_input(INPUT_POST, 'shipping_address', FILTER_SANITIZE_SPECIAL_CHARS);
$promo_code = filter_input(INPUT_POST, 'promo_code', FILTER_SANITIZE_SPECIAL_CHARS);

try {
    $pdo->beginTransaction();

    // Fetch cart items
    $stmt = $pdo->prepare("
        SELECT ci.product_id, p.price, ci.quantity, p.name, p.seller_id, p.image_url, p.stock
        FROM cart_items ci
        JOIN products p ON ci.product_id = p.id
        WHERE ci.buyer_id = ?
    ");
    $stmt->execute([$buyer_id]);
    $cart_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($cart_items)) {
        throw new Exception("Your cart is empty");
    }

    // Validate stock and calculate totals
    $subtotal = 0;
    foreach ($cart_items as $item) {
        if ($item['stock'] < $item['quantity']) {
            throw new Exception("Insufficient stock for {$item['name']}");
        }
        $subtotal += $item['price'] * $item['quantity'];
    }

    // Apply promo code if valid
    $discount_amount = 0;
    if (!empty($promo_code)) {
        $stmt = $pdo->prepare("
            SELECT discount_type, discount_value, min_spend 
            FROM coupons 
            WHERE code = ? AND status = 'active' AND expiry_date > NOW()
        ");
        $stmt->execute([$promo_code]);
        $coupon = $stmt->fetch();

        if ($coupon && $subtotal >= $coupon['min_spend']) {
            if ($coupon['discount_type'] === 'percentage') {
                $discount_amount = ($coupon['discount_value'] / 100) * $subtotal;
            } else {
                $discount_amount = min($coupon['discount_value'], $subtotal);
            }
        }
    }

    // Calculate delivery fee
    $delivery_fee = ($delivery_method === 'home_delivery') ? 500 : 0;
    $total = $subtotal - $discount_amount + $delivery_fee;

    // Create order
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            buyer_id, order_date, payment_method, delivery_method, 
            shipping_address, status, subtotal, discount, delivery_fee, total, promo_code
        ) VALUES (?, NOW(), ?, ?, ?, 'pending', ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $buyer_id,
        $payment_method,
        $delivery_method,
        $shipping_address,
        $subtotal,
        $discount_amount,
        $delivery_fee,
        $total,
        $promo_code ?: null
    ]);
    $order_id = $pdo->lastInsertId();

    // Create order items and update product stock
    $stmt = $pdo->prepare("
        INSERT INTO order_items (
            order_id, product_id, seller_id, price, quantity, name, image_url
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $updateStmt = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");

    foreach ($cart_items as $item) {
        $stmt->execute([
            $order_id,
            $item['product_id'],
            $item['seller_id'],
            $item['price'],
            $item['quantity'],
            $item['name'],
            $item['image_url']
        ]);

        $updateStmt->execute([$item['quantity'], $item['product_id']]);
    }

    // Clear cart
    $pdo->prepare("DELETE FROM cart_items WHERE buyer_id = ?")->execute([$buyer_id]);

    // Mark promo code as used if applicable
    if (!empty($promo_code)) {
        $pdo->prepare("
            UPDATE coupons 
            SET max_redemptions = max_redemptions - 1 
            WHERE code = ?
        ")->execute([$promo_code]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'order_id' => $order_id,
        'redirect' => "order-confirmation.php?order_id=$order_id"
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}