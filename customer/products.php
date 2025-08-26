<?php
// Database connection
require '../app/config.php';

// Input parameters
$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : (isset($_GET['name']) ? trim($_GET['name']) : null);
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float) $_GET['max_price'] : null;

// Base SQL with seller approval check
$sql = "SELECT p.*, u.shop_name 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE u.approval_status = 'approved'";
$countSql = "SELECT COUNT(*) 
             FROM products p
             JOIN users u ON p.seller_id = u.id
             WHERE u.approval_status = 'approved'";
$params = [];

// Apply search
if ($search) {
    $sql .= " AND p.name LIKE :search";
    $countSql .= " AND p.name LIKE :search";
    $params['search'] = "%$search%";
}

// Apply category filter
if ($category) {
    $sql .= " AND p.category = :category";
    $countSql .= " AND p.category = :category";
    $params['category'] = $category;
}

// Apply price filters
if ($min_price !== null) {
    $sql .= " AND p.price >= :min_price";
    $countSql .= " AND p.price >= :min_price";
    $params['min_price'] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND p.price <= :max_price";
    $countSql .= " AND p.price <= :max_price";
    $params['max_price'] = $max_price;
}

// Pagination
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 12;
$offset = ($page - 1) * $limit;
$sql .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";

// Fetch filtered products
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count total for pagination
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);
} catch (PDOException $e) {
    $products = [];
    $totalProducts = 0;
    $totalPages = 0;
    error_log("Database error: " . $e->getMessage());
}

// Fetch reviews for ratings
try {
    $stmt = $pdo->prepare("SELECT product_id, rating FROM reviews");
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Group reviews by product_id
    $grouped_reviews = [];
    foreach ($reviews as $review) {
        $grouped_reviews[$review['product_id']][] = $review;
    }
} catch (PDOException $e) {
    $grouped_reviews = [];
    error_log("Error fetching reviews: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f57c00;
            --secondary-color: #ef6c00;
            --accent-color: #ffb74d;
            --light-bg: #fff8f0;
            --dark-text: #1e1e1e;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
            padding-bottom: 60px;
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: 'Poppins', sans-serif;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .card-body {
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .card-body .btn-container {
            margin-top: auto;
            padding-top: 1rem;
        }

        .product-img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 12px 12px 0 0;
            transition: transform 0.3s ease;
        }

        .product-img:hover {
            transform: scale(1.05);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #e65100;
            border-color: #e65100;
        }

        .btn-outline-primary {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-outline-primary:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .section-title {
            position: relative;
            padding-bottom: 10px;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .section-title:after {
            content: '';
            position: absolute;
            left: 0;
            bottom: 0;
            width: 50px;
            height: 3px;
            background: var(--accent-color);
        }

        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: white;
            border-top: 1px solid #dee2e6;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .nav-bottom .nav-link {
            padding: 12px 0;
            font-size: 0.8rem;
            color: #6c757d;
        }

        .nav-bottom .nav-link.active {
            color: var(--accent-color);
        }

        .nav-bottom .nav-link i {
            display: block;
            font-size: 1.2rem;
            margin-bottom: 5px;
        }

        .product-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 600;
            color: white;
            border-radius: 10px;
            text-transform: uppercase;
            z-index: 10;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 576px) {
            .container {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }

            .row.g-6 {
                --bs-gutter-x: 0.5rem;
                --bs-gutter-y: 0.5rem;
            }

            .col-6 {
                padding-left: 4px !important;
                padding-right: 4px !important;
                max-width: 50% !important;
            }

            .card {
                margin-bottom: 0.5rem;
                border-radius: 8px;
            }

            .card-body {
                padding: 0.75rem 0.5rem;
            }

            .card-title {
                font-size: 0.8rem !important;
                line-height: 1.2;
                margin-bottom: 0.5rem;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .card-text {
                font-size: 0.85rem;
                margin-bottom: 0.5rem !important;
                font-weight: 600;
                color: var(--primary-color);
            }

            .product-img {
                height: 140px !important;
                border-radius: 8px 8px 0 0;
            }

            .product-badge {
                top: 5px;
                right: 5px;
                padding: 0.15rem 0.3rem;
                font-size: 0.6rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.7rem;
                border-radius: 4px;
            }

            .card-body .btn-container {
                padding-top: 0.5rem;
            }

            /* Filter form mobile optimization */
            .filter-form-mobile {
                background: white;
                padding: 1rem;
                border-radius: 10px;
                margin-bottom: 1rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            }

            .filter-form-mobile .form-label {
                font-size: 0.8rem;
                font-weight: 600;
                margin-bottom: 0.3rem;
            }

            .filter-form-mobile .form-control,
            .filter-form-mobile .form-select {
                font-size: 0.85rem;
                padding: 0.5rem;
            }

            .section-title {
                font-size: 1.1rem;
                margin-bottom: 15px;
            }

            /* Search form mobile */
            .search-form-mobile {
                max-width: 100% !important;
                margin: 1rem 0;
            }

            .search-form-mobile input {
                font-size: 16px;
                /* Prevents zoom on iOS */
            }

            /* Rating stars mobile */
            .rating-mobile {
                font-size: 0.7rem;
                margin: 0.3rem 0;
            }

            .rating-mobile span {
                font-size: 0.8rem !important;
            }
        }

        /* Small mobile devices */
        @media (max-width: 400px) {
            .container {
                padding-left: 5px !important;
                padding-right: 5px !important;
            }

            .col-6 {
                padding-left: 2px !important;
                padding-right: 2px !important;
            }

            .card-body {
                padding: 0.5rem 0.3rem;
            }

            .card-title {
                font-size: 0.75rem !important;
            }

            .product-img {
                height: 120px !important;
            }
        }

        /* Tablet optimization */
        @media (min-width: 577px) and (max-width: 768px) {
            .col-6 {
                max-width: 33.333333% !important;
            }

            .product-img {
                height: 200px !important;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- Search Bar -->
        <form class="d-flex mx-auto search-form-mobile" style="max-width: 600px;" action="products.php" method="GET">
            <input
                class="form-control form-control-lg me-2"
                type="search"
                name="q"
                value="<?= htmlspecialchars($search ?? '') ?>"
                placeholder="Search products..."
                aria-label="Search"
                autocomplete="off" />
            <button class="btn btn-primary btn-lg" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>

        <!-- Filter Form -->
        <form method="GET" class="filter-form-mobile">
            <div class="row g-3 align-items-end">
                <!-- Preserve search query -->
                <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                <?php endif; ?>

                <div class="col-md-4 col-6">
                    <label for="category" class="form-label">Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT DISTINCT category FROM products WHERE category IS NOT NULL ORDER BY category ASC");
                            $stmt->execute();
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $cat):
                                $catName = $cat['category'];
                        ?>
                                <option value="<?= htmlspecialchars($catName) ?>" <?= ($category === $catName) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($catName) ?>
                                </option>
                        <?php
                            endforeach;
                        } catch (PDOException $e) {
                            echo "<option value=''>Error loading categories</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="col-md-3 col-6">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" class="form-control" name="min_price" placeholder="₦0" value="<?= htmlspecialchars($min_price ?? '') ?>">
                </div>

                <div class="col-md-3 col-6">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" class="form-control" name="max_price" placeholder="₦10,000" value="<?= htmlspecialchars($max_price ?? '') ?>">
                </div>

                <div class="col-md-2 col-6">
                    <button class="btn btn-primary w-100" type="submit">
                        <span class="d-md-none">Filter</span>
                        <span class="d-none d-md-inline">Apply Filter</span>
                    </button>
                </div>
            </div>

            <!-- Clear filters button -->
            <?php if ($category || $min_price || $max_price || $search): ?>
                <div class="text-center mt-3">
                    <a href="products.php" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-times me-1"></i> Clear All Filters
                    </a>
                </div>
            <?php endif; ?>
        </form>

        <!-- Results Info -->
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="section-title mb-0">
                <?php if ($search): ?>
                    Search Results for "<?= htmlspecialchars($search) ?>"
                <?php elseif ($category): ?>
                    <?= htmlspecialchars($category) ?> Products
                <?php else: ?>
                    All Products
                <?php endif; ?>
            </h4>
            <small class="text-muted">
                <?= $totalProducts ?> product<?= $totalProducts !== 1 ? 's' : '' ?> found
            </small>
        </div>

        <!-- Products Grid -->
        <div class="row g-2 g-md-3 g-lg-4 mb-5">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <div class="alert alert-info">
                        <i class="fas fa-search fa-3x mb-3 text-muted"></i>
                        <h5>No products found</h5>
                        <p class="mb-0">Try adjusting your search criteria or browse all categories.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                        <div class="card h-100">
                            <div class="position-relative">
                                <img src="<?php echo isset($product['image_url']) && $product['image_url']
                                                ? "../" . htmlspecialchars($product['image_url'])
                                                : "../uploads/default-product.png"; ?>"
                                    class="product-img"
                                    alt="<?= htmlspecialchars($product['name']) ?>">

                                <!-- Stock badge -->
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="product-badge bg-success">In Stock</span>
                                <?php else: ?>
                                    <span class="product-badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body">
                                <div class="card-content">
                                    <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    <p class="card-text">₦<?= number_format($product['price'], 2) ?></p>

                                    <!-- Rating stars -->
                                    <div class="rating-mobile">
                                        <?php
                                        $avg_rating = 0;
                                        if (!empty($grouped_reviews[$product['id']])) {
                                            $ratings = array_column($grouped_reviews[$product['id']], 'rating');
                                            $avg_rating = round(array_sum($ratings) / count($ratings), 1);
                                        }
                                        ?>
                                        <div class="d-flex align-items-center">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <?php if ($i <= floor($avg_rating)): ?>
                                                    <span style="color: gold;">&#9733;</span>
                                                <?php else: ?>
                                                    <span style="color: #ccc;">&#9733;</span>
                                                <?php endif; ?>
                                            <?php endfor; ?>
                                            <small class="ms-1">(<?= $avg_rating ?>)</small>
                                        </div>
                                    </div>

                                    <!-- Shop name -->
                                    <small class="text-muted">
                                        <i class="fas fa-store me-1"></i>
                                        <?= htmlspecialchars($product['shop_name']) ?>
                                    </small>
                                </div>

                                <!-- Button container -->
                                <div class="btn-container">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="product_details.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                                            <i class="fas fa-eye me-1 d-none d-md-inline"></i>
                                            <span class="d-md-none">View</span>
                                            <span class="d-none d-md-inline">View Details</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php
                    // Previous page
                    if ($page > 1):
                        $prevParams = $_GET;
                        $prevParams['page'] = $page - 1;
                        $prevQuery = http_build_query($prevParams);
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="products.php?<?= $prevQuery ?>">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Show page numbers with ellipsis for large page counts
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);

                    if ($start > 1):
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="products.php?page=1">1</a>
                        </li>
                        <?php if ($start > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php for ($i = $start; $i <= $end; $i++): ?>
                        <?php
                        $pageParams = $_GET;
                        $pageParams['page'] = $i;
                        $pageQuery = http_build_query($pageParams);
                        ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="products.php?<?= $pageQuery ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($end < $totalPages): ?>
                        <?php if ($end < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="products.php?page=<?= $totalPages ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>

                    <?php
                    // Next page
                    if ($page < $totalPages):
                        $nextParams = $_GET;
                        $nextParams['page'] = $page + 1;
                        $nextQuery = http_build_query($nextParams);
                    ?>
                        <li class="page-item">
                            <a class="page-link" href="products.php?<?= $nextQuery ?>">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Script -->
    <?php include 'includes/script.php'; ?>
</body>

</html>