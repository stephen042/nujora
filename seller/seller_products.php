<?php
require '../app/config.php';

// ✅ Check if seller is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'seller') {
    header('Location: ../auth/login.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// ✅ Get seller shop info
try {
    // Fetch shop details
    $shop_stmt = $pdo->prepare("SELECT shop_name, shop_description, shop_logo FROM users WHERE id = ?");
    $shop_stmt->execute([$seller_id]);
    $shop_info = $shop_stmt->fetch(PDO::FETCH_ASSOC);

    // ✅ Pagination Setup
    $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 12;
    $offset = ($page - 1) * $per_page;

    // ✅ Fetch products with review stats
    $product_stmt = $pdo->prepare("
        SELECT 
            p.*, 
            COUNT(r.id) AS review_count, 
            ROUND(AVG(r.rating), 1) AS avg_rating
        FROM products p
        LEFT JOIN reviews r ON p.id = r.product_id
        WHERE p.seller_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
        LIMIT ? OFFSET ?
    ");

    // Use bindParam for pagination values to ensure correct integer binding
    $product_stmt->bindValue(1, $seller_id, PDO::PARAM_INT);
    $product_stmt->bindValue(2, $per_page, PDO::PARAM_INT);
    $product_stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $product_stmt->execute();

    $products = $product_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle database error gracefully
    die("Database error: " . $e->getMessage());
}

// Get total products count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE seller_id = ?");
$stmt->execute([$seller_id]);
$total_products = $stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta property="og:image" content="uploads/default-product.png" />
    <title><?= htmlspecialchars($shop_info['shop_name'] ?? 'My Shop') ?> | <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #6a11cb;
            --secondary: #2575fc;
            --accent: #957156;
            --light-bg: #f8f9fa;
            --dark-text: #2B2A26;
            --success: #28a745;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f8f9fa;
            color: var(--dark-text);
        }

        .shop-header {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .shop-logo {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid white;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }

        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .product-img-container {
            height: 200px;
            overflow: hidden;
            position: relative;
        }

        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.05);
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--success);
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .rating-stars {
            color: #FFD700;
            font-size: 0.9rem;
        }

        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
            padding: 8px 20px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #5a0db4;
            border-color: #5a0db4;
        }

        .nav-pills .nav-link.active {
            background-color: var(--primary);
        }

        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .stats-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            border-radius: 8px;
            color: var(--dark-text);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: rgba(106, 17, 203, 0.1);
            color: var(--primary);
        }

        .sidebar-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary);
            border-color: var(--primary);
        }

        .pagination .page-link {
            color: var(--primary);
        }

        @media (max-width: 768px) {
            .shop-header {
                padding: 2rem 0;
                text-align: center;
            }

            .shop-logo {
                width: 80px;
                height: 80px;
                margin-bottom: 1rem;
            }

            .product-img-container {
                height: 150px;
            }
        }
    </style>
</head>

<body>
    <!-- Shop Header -->
    <header class="shop-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-2 text-center text-md-start">
                    <img src="<?= htmlspecialchars($shop_info['shop_logo'] ?? 'https://via.placeholder.com/120') ?>"
                        alt="<?= htmlspecialchars($shop_info['shop_name'] ?? 'Shop Logo') ?>"
                        class="shop-logo mb-3 mb-md-0">
                </div>
                <div class="col-md-7 text-center text-md-start">
                    <h1 class="mb-2"><?= htmlspecialchars($shop_info['shop_name'] ?? 'My Shop') ?></h1>
                    <p class="mb-0"><?= htmlspecialchars($shop_info['shop_description'] ?? 'Quality products for you') ?></p>
                </div>
                <div class="col-md-3 text-center text-md-end mt-3 mt-md-0">
                    <a href="add_product.php" class="btn btn-light">
                        <i class="fas fa-plus me-2"></i> Add Product
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container py-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="stats-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>Total Products</span>
                        <span class="stats-number"><?= $total_products ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span>Total Sales</span>
                        <span class="stats-number">₦0</span> <!-- Replace with actual sales data -->
                    </div>
                </div>

                <nav class="nav flex-column">
                    <a href="seller_products.php" class="sidebar-link active">
                        <i class="fas fa-box"></i> My Products
                    </a>
                    <a href="seller_orders.php" class="sidebar-link">
                        <i class="fas fa-shopping-bag"></i> Orders
                    </a>
                    <a href="seller_reviews.php" class="sidebar-link">
                        <i class="fas fa-star"></i> Reviews
                    </a>
                    <a href="seller_analytics.php" class="sidebar-link">
                        <i class="fas fa-chart-line"></i> Analytics
                    </a>
                    <a href="seller_settings.php" class="sidebar-link">
                        <i class="fas fa-cog"></i> Shop Settings
                    </a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">My Products</h2>
                    <div class="d-flex">
                        <div class="dropdown me-2">
                            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown">
                                <i class="fas fa-sort me-1"></i> Sort By
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="?sort=newest">Newest First</a></li>
                                <li><a class="dropdown-item" href="?sort=oldest">Oldest First</a></li>
                                <li><a class="dropdown-item" href="?sort=price_high">Price: High to Low</a></li>
                                <li><a class="dropdown-item" href="?sort=price_low">Price: Low to High</a></li>
                            </ul>
                        </div>
                        <div class="input-group" style="width: 200px;">
                            <input type="text" class="form-control" placeholder="Search products...">
                            <button class="btn btn-outline-secondary" type="button">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($products)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-4"></i>
                        <h3>No Products Found</h3>
                        <p class="text-muted">You haven't added any products yet. Get started by adding your first product.</p>
                        <a href="add_product.php" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i> Add Product
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                        <?php foreach ($products as $product): ?>
                            <div class="col">
                                <div class="product-card">
                                    <div class="product-img-container">
                                        <img src="<?= htmlspecialchars($product['image_url'] ?? 'https://via.placeholder.com/300') ?>"
                                            alt="<?= htmlspecialchars($product['name']) ?>"
                                            class="product-img"
                                            onerror="this.src='https://via.placeholder.com/300?text=Product+Image'">
                                        <?php if ($product['stock'] > 0): ?>
                                            <span class="product-badge">In Stock</span>
                                        <?php else: ?>
                                            <span class="product-badge bg-danger">Out of Stock</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="p-3 d-flex flex-column flex-grow-1">
                                        <h5 class="mb-1"><?= htmlspecialchars($product['name']) ?></h5>
                                        <div class="d-flex align-items-center mb-2">
                                            <div class="rating-stars">
                                                <?php
                                                $rating = round($product['avg_rating'] ?? 0);
                                                for ($i = 1; $i <= 5; $i++) {
                                                    echo $i <= $rating ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
                                                }
                                                ?>
                                            </div>
                                            <small class="text-muted ms-2">(<?= $product['review_count'] ?? 0 ?>)</small>
                                        </div>
                                        <div class="mt-auto">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <span class="fw-bold fs-5">₦<?= number_format($product['price'], 2) ?></span>
                                                <small class="text-muted"><?= $product['stock'] ?> in stock</small>
                                            </div>
                                            <div class="d-grid gap-2">
                                                <a href="edit_product.php?id=<?= $product['id'] ?>" class="btn btn-outline-primary">
                                                    <i class="fas fa-edit me-2"></i> Edit
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-5">
                            <ul class="pagination justify-content-center">
                                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page - 1 ?>">Previous</a>
                                </li>

                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                        <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>

                                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                                    <a class="page-link" href="?page=<?= $page + 1 ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Product search functionality
        document.querySelector('.input-group button').addEventListener('click', function() {
            const searchTerm = document.querySelector('.input-group input').value.trim();
            if (searchTerm) {
                window.location.href = `seller_products.php?search=${encodeURIComponent(searchTerm)}`;
            }
        });

        // Enable pressing Enter in search input
        document.querySelector('.input-group input').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                document.querySelector('.input-group button').click();
            }
        });
    </script>
</body>

</html>