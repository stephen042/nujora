<?php
require '../app/config.php';

$user_id = $_SESSION['user_id'] ?? null;
$totalCount = 0;

if ($user_id) {
    // Logged-in user → fetch from DB
    $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE buyer_id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $totalCount = $result['total_items'] ?? 0;
} else {
    // Guest → fetch from session cart
    if (isset($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            $totalCount += $item['quantity'];
        }
    }
}

echo json_encode(['status' => 'success', 'count' => $totalCount]);
exit;
