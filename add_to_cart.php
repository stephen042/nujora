<?php
require 'app/config.php';
header('Content-Type: application/json');

session_start();

// Get POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

if ($product_id <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// If user is logged in — store cart in DB
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    try {
        // Check if item exists in DB cart
        $stmt = $pdo->prepare("SELECT id, quantity FROM cart_items WHERE buyer_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $existing['id']]);
        } else {
            // Insert new item
            $stmt = $pdo->prepare("INSERT INTO cart_items (buyer_id, product_id, quantity) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $product_id, $quantity]);
        }

        // Get total cart count
        $stmt = $pdo->prepare("SELECT SUM(quantity) AS total FROM cart_items WHERE buyer_id = ?");
        $stmt->execute([$user_id]);
        $count = $stmt->fetchColumn() ?? 0;

        echo json_encode(['success' => true, 'count' => $count, 'message' => 'Item added to cart']);
        exit;
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
        exit;
    }
}

// If not logged in — use session-based cart
if (!isset($_SESSION['guest_cart'])) {
    $_SESSION['guest_cart'] = [];
}

// If product already exists in session cart
if (isset($_SESSION['guest_cart'][$product_id])) {
    $_SESSION['guest_cart'][$product_id] += $quantity;
} else {
    $_SESSION['guest_cart'][$product_id] = $quantity;
}

// Calculate total items in guest cart
$total = array_sum($_SESSION['guest_cart']);

echo json_encode(['success' => true, 'count' => $total, 'message' => 'Item added to cart']);
exit;
?>
