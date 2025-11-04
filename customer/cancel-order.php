<?php
require 'db.php';

// Security: Only buyers can cancel their own orders
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'buyer') {
    header("Location: login.php");
    exit;
}

$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$buyer_id = $_SESSION['user_id'];

if ($order_id <= 0) {
    die("Invalid order ID.");
}

// Fetch the order and check ownership and status
$stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND buyer_id = ?");
$stmt->execute([$order_id, $buyer_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die("Order not found or you do not have permission to cancel this order.");
}

if ($order['status'] !== 'pending') {
    die("Only pending orders can be cancelled.");
}

// Cancel the order
$stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
$stmt->execute([$order_id]);

// Optionally, add to order_status_history if you use it
if ($pdo->query("SHOW TABLES LIKE 'order_status_history'")->rowCount() > 0) {
    $stmtHist = $pdo->prepare("INSERT INTO order_status_history (order_id, status, changed_by) VALUES (?, 'cancelled', 'buyer')");
    $stmtHist->execute([$order_id]);
}

header("Location: orders.php?msg=Order cancelled successfully");