<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];
    $rating = intval($_POST['rating']);
    $comment = $_POST['comment'] ?? '';

    try {
        $pdo->beginTransaction();
        
        // Insert review
        $stmt = $pdo->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $user_id, $rating, $comment]);
        
        // Update product rating summary
        $update_stmt = $pdo->prepare("UPDATE products SET 
                                    avg_rating = (SELECT AVG(rating) FROM reviews WHERE product_id = ?),
                                    review_count = (SELECT COUNT(*) FROM reviews WHERE product_id = ?)
                                    WHERE id = ?");
        $update_stmt->execute([$product_id, $product_id, $product_id]);
        
        $pdo->commit();
        
        header("Location: product_details.php?id=$product_id&review=success#reviews");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        header("Location: product_details.php?id=$product_id&review=error");
        exit;
    }
}