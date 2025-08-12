<?php
session_start();
require 'db.php';

// Check if the seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header("Location: login.php");
    exit;
}

// Initialize variables
$statusMessage = '';
$uploadDir = 'uploads/'; // Directory to store uploaded images

// Define categories from your image
$categories = [
    'Home Furnishings',
    'Kitchen and Dining',
    'Small Appliances',
    'Housekeeping & Pet Supplies',
    'Furniture',
    'Large Appliances',
    'Arts, Crafts & Sewing',
    'Home & Kitchen Bundles',
    'Seasonal Decor',
    'Garden',
    'Floor Care & Vacuum Accessories',
    'Kids\' Home Store',
    'Heaters',
    'Appliances With Special Offers'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = htmlspecialchars($_POST['name']);
    $price = floatval($_POST['price']);
    $description = htmlspecialchars($_POST['description']);
    $stock = intval($_POST['stock']);
    $status = htmlspecialchars($_POST['status']); // In Stock or Out of Stock
    $category = $_POST['category'] ?? '';
    $sellerId = $_SESSION['user_id'];

    // Validate category
    if (!in_array($category, $categories)) {
        $statusMessage = '<div class="alert alert-danger">Please select a valid category.</div>';
    } else {
        // Handle file upload
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath = $_FILES['product_image']['tmp_name'];
            $fileName = $_FILES['product_image']['name'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExtension;
            $destPath = $uploadDir . $newFileName;

            if (move_uploaded_file($fileTmpPath, $destPath)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO products (name, price, description, stock, status, seller_id, category, image_url) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $price, $description, $stock, $status, $sellerId, $category, $destPath]);

                    $statusMessage = '<div class="alert alert-success">Product uploaded successfully!</div>';
                } catch (PDOException $e) {
                    $statusMessage = '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
                }
            } else {
                $statusMessage = '<div class="alert alert-danger">Failed to upload the image. Please try again.</div>';
            }
        } else {
            $statusMessage = '<div class="alert alert-danger">Please upload a valid image file.</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product | Seller Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0,0,0,0.05);
            z-index: 1000;
        }
        .nav-bottom .nav-link {
            padding: 0.75rem;
            font-size: 0.8rem;
            color: #495057;
        }
        .nav-bottom .nav-link.active {
            color: #6a11cb;
            font-weight: 600;
        }
        body {
            padding-bottom: 60px;
        }
    </style>
</head>
<body>
<div class="container mt-4 mb-5">
    <h4>Upload New Product</h4>
    <?= $statusMessage; ?>
    <form method="POST" action="add_product.php" enctype="multipart/form-data">
        <div class="mb-3">
            <label class="form-label">Product Name</label>
            <input type="text" name="name" class="form-control" placeholder="e.g. Bluetooth Speaker" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Price (₦)</label>
            <input type="number" name="price" class="form-control" placeholder="e.g. 8500" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3" placeholder="Enter product description" required></textarea>
        </div>
        <div class="mb-3">
            <label class="form-label">Stock Quantity</label>
            <input type="number" name="stock" class="form-control" min="0" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Status</label>
            <select name="status" class="form-select" required>
                <option value="In Stock">In Stock</option>
                <option value="Out of Stock">Out of Stock</option>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Category</label>
            <select name="category" class="form-select" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Upload Image</label>
            <input type="file" name="product_image" class="form-control" accept="image/*" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Submit</button>
    </form>
</div>

<!-- Seller navigation -->
<nav class="nav nav-pills nav-fill nav-bottom">
    <a class="nav-link" href="seller-dashboard.php">
        <i class="bi bi-house"></i> Dashboard
    </a>
    <a class="nav-link" href="seller-products.php">
        <i class="bi bi-box-seam"></i> My Products
    </a>
    <a class="nav-link" href="seller-orders.php">
        <i class="bi bi-receipt"></i> Orders
    </a>
    <a class="nav-link active" href="add_product.php">
        <i class="bi bi-plus-circle"></i> Add Product
    </a>
</nav>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>