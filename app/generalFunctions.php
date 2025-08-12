<?php 

// Get cart count
if (isset($_SESSION['user_id'])) {
  $buyerId = $_SESSION['user_id'];

  $stmt = $pdo->prepare("SELECT SUM(quantity) AS total_items FROM cart_items WHERE buyer_id = ?");
  $stmt->execute([$buyerId]);
  $result = $stmt->fetch(PDO::FETCH_ASSOC);

  $cartCount = $result['total_items'] ?? 0;
}