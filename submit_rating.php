<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['users'])) {
    header("Location: login.php");
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Validate inputs
if (!isset($_POST['seller_id'], $_POST['stars']) || empty($_POST['seller_id']) || empty($_POST['stars'])) {
    header("Location: orders.php?error=Missing+required+fields");
    exit;
}

$seller_id = (int) $_POST['seller_id'];
$stars = (int) $_POST['stars'];
$review = trim($_POST['review'] ?? '');
$order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : null;

// Ensure valid star rating
if ($stars < 1 || $stars > 5) {
    header("Location: orders.php?error=Invalid+rating+value");
    exit;
}

// Optional: Prevent duplicate ratings per buyer per order
if ($order_id) {
    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM ratings WHERE buyer_id = ? AND order_id = ?");
    $checkStmt->execute([$buyer_id, $order_id]);
    $alreadyRated = $checkStmt->fetchColumn();

    if ($alreadyRated > 0) {
        header("Location: orders.php?error=You+have+already+rated+this+order");
        exit;
    }
}

try {
    $stmt = $pdo->prepare("INSERT INTO ratings (buyer_id, seller_id, stars, review, order_id, created_at)
                           VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$buyer_id, $seller_id, $stars, $review, $order_id]);

    header("Location: orders.php?success=Thanks+for+your+rating");
    exit;
} catch (PDOException $e) {
    // Log or debug $e->getMessage()
    header("Location: orders.php?error=Could+not+submit+rating");
    exit;
}
?>

