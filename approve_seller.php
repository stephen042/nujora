<?php
require 'db.php';

if (isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("UPDATE users SET is_approved = TRUE, approved_at = NOW() WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: pending_sellers.php");
}
?>

