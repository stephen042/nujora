<?php
session_start();
require 'db.php';

// Check if the seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit;
}

// Get product ID from URL
$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

try {
    // Fetch product details
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $_SESSION['user_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        die("Product not found or you don't have permission to edit this product.");
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = htmlspecialchars($_POST['name']);
        $price = floatval($_POST['price']);
        $description = htmlspecialchars($_POST['description']);
        $stock = intval($_POST['stock']);
        $status = $stock > 0 ? 'In Stock' : 'Out of Stock'; // Automatically set status based on stock

        try {
            $update_stmt = $pdo->prepare("
                UPDATE products 
                SET name = ?, price = ?, description = ?, stock = ?, status = ? 
                WHERE id = ? AND seller_id = ?
            ");
            $update_stmt->execute([$name, $price, $description, $stock, $status, $product_id, $_SESSION['user_id']]);

            header("Location: seller-products.php?success=Product updated successfully");
            exit;
        } catch (PDOException $e) {
            die("Error updating product: " . $e->getMessage());
        }
    }
} catch (PDOException $e) {
    die("Error fetching product: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product | Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Edit Product</h1>

        <form method="POST" action="edit_product.php?id=<?= $product_id ?>">
            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" name="name" id="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price (₦)</label>
                <input type="number" name="price" id="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" step="0.01" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" id="description" class="form-control" rows="5" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="stock" class="form-label">Stock Quantity</label>
                <input type="number" name="stock" id="stock" class="form-control" value="<?= htmlspecialchars($product['stock']) ?>" min="0" required>
            </div>

            <div class="mb-3">
                <label for="status" class="form-label">Status</label>
                <select name="status" id="status" class="form-select" disabled>
                    <option value="In Stock" <?= $product['stock'] > 0 ? 'selected' : '' ?>>In Stock</option>
                    <option value="Out of Stock" <?= $product['stock'] == 0 ? 'selected' : '' ?>>Out of Stock</option>
                </select>
                <small class="text-muted">Status is automatically set based on stock quantity.</small>
            </div>

            <button type="submit" class="btn btn-primary">Update Product</button>
            <a href="seller-products.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>