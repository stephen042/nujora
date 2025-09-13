<?php
require '../app/config.php';

// Fallback page if referer not available
$redirectPage = $_SERVER['HTTP_REFERER'] ?? 'admin_products.php';

// Check if ID exists in query
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid product ID.";
    header("Location: " . $redirectPage);
    exit();
}

$productId = (int) $_GET['id'];

try {
    // Check if product exists
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product) {
        $_SESSION['error'] = "Product not found.";
        header("Location: " . $redirectPage);
        exit();
    }

    // Delete the product
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);

    $_SESSION['success'] = "Product deleted successfully.";
    header("Location: " . $redirectPage);
    exit();

} catch (PDOException $e) {
    $_SESSION['error'] = "Error deleting product: " . $e->getMessage();
    header("Location: " . $redirectPage);
    exit();
}
