<?php
require 'app/config.php';
header('Content-Type: application/json');
session_start(); // <-- THIS IS REQUIRED for guest cart to persist

// ----------------------
// Collect POST values
// ----------------------
$product_id   = intval($_POST['product_id'] ?? 0);
$quantity     = max(1, intval($_POST['quantity'] ?? 1));
$variant_id   = !empty($_POST['variant_id']) ? $_POST['variant_id'] : null;

// Collect variant options
$variant_options = $_POST;
unset($variant_options['product_id'], $variant_options['quantity'], $variant_options['variant_id']);

// Validate
if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// Check if product has variants
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id=?");
    $stmt->execute([$product_id]);
    $has_variants = $stmt->fetchColumn() > 0;
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Require variant if product has variants
if ($has_variants && !$variant_id) {
    echo json_encode(['success' => false, 'message' => 'View product details and select product options.']);
    exit;
}

// ----------------------
// Logged-in user logic
// ----------------------
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT id FROM cart_items WHERE buyer_id=? AND product_id=? AND (variant_id <=> ?)");
        $stmt->execute([$user_id, $product_id, $variant_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity=quantity+? WHERE id=?");
            $stmt->execute([$quantity, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO cart_items (buyer_id, product_id, variant_id, variant_options, quantity) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $variant_id, !empty($variant_options) ? json_encode($variant_options) : null, $quantity]);
        }

        // Cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) FROM cart_items WHERE buyer_id=?");
        $stmt->execute([$user_id]);
        $count = $stmt->fetchColumn() ?? 0;

        echo json_encode(['success' => true, 'count' => $count, 'message' => 'Item added to cart']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}

// ----------------------
// Guest cart logic
// ----------------------
if (!isset($_SESSION['guest_cart'])) $_SESSION['guest_cart'] = [];

$cartKey = $product_id . '_' . ($variant_id ?: 'default');

if (isset($_SESSION['guest_cart'][$cartKey])) {
    $_SESSION['guest_cart'][$cartKey]['quantity'] += $quantity;
} else {
    $_SESSION['guest_cart'][$cartKey] = [
        'product_id' => $product_id,
        'variant_id' => $variant_id,
        'variant_options' => $variant_options,
        'quantity' => $quantity
    ];
}

// Calculate total items in guest cart
$total = 0;
foreach ($_SESSION['guest_cart'] as $item) {
    $total += intval($item['quantity']);
}

echo json_encode(['success' => true, 'count' => $total, 'message' => 'Item added to cart']);
exit;
