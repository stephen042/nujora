<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $allowed = ['pending','processing','shipped','completed','cancelled'];
    if (!in_array($status, $allowed)) {
        header("Location: seller-dashboard.php?tab=orders&error=Invalid status");
        exit;
    }
    // Optional: Check if the order belongs to the logged-in seller
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $order_id]);
}
header("Location: seller-dashboard.php?tab=orders");
exit;