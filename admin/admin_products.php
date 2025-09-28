<?php
require '../app/config.php';

// Check admin privileges
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

// Define categories (replace with actual categories from your database or array)
// Fetch categories from the database
try {
    $stmt = $pdo->query("SELECT name FROM categories ORDER BY name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    die("Error fetching categories: " . $e->getMessage());
}

// Get current category filter
$currentCategory = $_GET['category'] ?? '';

// Get search query
$searchQuery = $_GET['search'] ?? '';

// Query products based on category filter and search query
try {
    if ($currentCategory && in_array($currentCategory, $categories)) {
        $stmt = $pdo->prepare("SELECT p.*, u.shop_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.category = ? AND p.name LIKE ? ORDER BY p.created_at DESC");
        $stmt->execute([$currentCategory, '%' . $searchQuery . '%']);
    } else {
        $stmt = $pdo->prepare("SELECT p.*, u.shop_name FROM products p JOIN users u ON p.seller_id = u.id WHERE p.name LIKE ? ORDER BY p.created_at DESC");
        $stmt->execute(['%' . $searchQuery . '%']);
    }
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching products: " . $e->getMessage());
}
if (isset($_POST['product_action'])) {
    // Handle product actions (feature/delete)
    $product_id = (int)$_POST['product_id'];
    $action = $_POST['product_action'];

    try {
        if ($action === 'feature') {
            $stmt = $pdo->prepare("UPDATE products SET is_featured = NOT is_featured WHERE id = ?");
            $stmt->execute([$product_id]);
            $_SESSION['success'] = "Product feature status updated.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = " Error updating product: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Products</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>

<body>
    <div class="container mt-4">
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <!-- Back to Admin Dashboard -->
        <div class="mb-4">
            <a href="admin-dashboard.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>

        <h1 class="mb-4">Product Management</h1>

        <!-- Search Bar -->
        <form method="GET" action="admin_products.php" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search products by name" value="<?= htmlspecialchars($searchQuery) ?>">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-search"></i> Search
                </button>
            </div>
        </form>

        <!-- Category Filter -->
        <div class="mb-4">
            <h4>Browse Categories</h4>
            <div class="d-flex flex-wrap gap-2">
                <a href="admin_products.php" class="btn btn-outline-primary <?= empty($currentCategory) ? 'active' : '' ?>">All Products</a>
                <?php foreach ($categories as $cat): ?>
                    <a href="admin_products.php?category=<?= urlencode($cat) ?>"
                        class="btn btn-outline-secondary <?= $currentCategory === $cat ? 'active' : '' ?>">
                        <?= htmlspecialchars($cat) ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Products Table -->
        <?php if (!empty($products)): ?>
            <table class="table table-bordered dataTable">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Created At</th>
                        <th>Image</th>
                        <th>Shop Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $i = 1; // initialize counter
                    ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['category']) ?></td>
                            <td>₦<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['stock']) ?></td>
                            <td><?= htmlspecialchars($product['created_at']) ?></td>
                            <td>
                                <a href="<?= htmlspecialchars($product['image_url']) ?>" target="_blank">
                                    <img src="<?= htmlspecialchars($product['image_url']) ?>" class="img-thumbnail" style="width: 60px; height: 60px; object-fit: cover;" alt="">
                                </a>
                            </td>
                            <td><?= htmlspecialchars($product['shop_name']) ?></td>
                            <td class="d-flex gap-2">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                    <input type="hidden" name="product_action" value="feature">
                                    <button type="submit" class="btn btn-sm <?= $product['is_featured'] ? 'btn-warning' : 'btn-outline-primary' ?>">
                                        <?= $product['is_featured'] ? 'Unfeature' : 'Feature' ?>
                                    </button>
                                </form>
                                <a href="admin_edit_product.php?id=<?= urlencode($product['id']) ?>" class="btn btn-sm btn-warning">Edit</a>
                                <a href="delete_product.php?id=<?= urlencode($product['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this product?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No products found in this category.</div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>