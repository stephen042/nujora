<?php
// Database connection
require 'app/config.php';

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




<?php
// Define meta title and description for each category
$category_meta = [
    "1" => [
        "title" => "Category 1 Exclusive Products",
        "description" => "Discover our exclusive collection of Category 1 products tailored for you."
    ],
    "9" => [
        "title" => "Category 9 Bestsellers",
        "description" => "Check out the top-selling Category 9 products in our store."
    ],
    "APPLIANCES" => [
        "title" => "Shop Home Appliances Online | Stylish, Modern, and Affordable",
        "description" => "Shop Nigerian home appliances online at Nujora. Stylish, modern, and affordable products designed to simplify your life and elevate your home’s comfort and style."
    ],
    "BEAUTY & PERSONAL CARE" => [
        "title" => "Buy Nigerian Beauty and Personal Care Products Online | Nujora",
        "description" => "Discover premium Nigerian beauty and personal care products online at Nujora. Shop skincare, haircare, and wellness essentials with fast delivery across Nigeria."
    ],
    "EDUCATIONAL PRODUCTS" => [
        "title" => "Educational Products for Kids – Smart Learning with Fun | Nujora",
        "description" => "Nujora offers educational products for kids that combine fun and learning. Explore toys, books, and tools designed to spark curiosity, creativity, and growth."
    ],
    "ELECTRONICS" => [
        "title" => "Latest Electronics & Gadgets",
        "description" => "Find the newest electronics and gadgets to keep you ahead of the tech curve."
    ],
    "FASHION" => [
        "title" => "Shop Clothes Online in Nigeria – Latest Fashion at Best Prices",
        "description" => "Latest fashion trends online in Nigeria with Nujora. Shop stylish clothes, shoes, bags, jewelry, and accessories at unbeatable prices for men, women, and kids."
    ],
    "HOME & OFFICES" => [
        "title" => "Home & Office Essentials",
        "description" => "Everything you need for your home and office in one place."
    ],
    "PHONES AND TABLETS" => [
        "title" => "Affordable Android Phones and Tablets Deals in Nigeria - Nujora",
        "description" => "Get affordable Android phones and tablets in Nigeria at Nujora. Shop top brands, compare deals, and enjoy fast delivery and a trusted online shopping experience."
    ],
    "TOYS, BABY & KIDS PRODUCTS" => [
        "title" => "Shop Baby and Educational Toys in Nigeria – Safe and Fun Choices",
        "description" => "Shop a diverse selection of baby and educational toys in Nigeria. Safe, fun, and stimulating toys designed to inspire learning, creativity, and endless playtime."
    ]
];

// Get selected category
$selected_category = isset($_GET['category']) ? $_GET['category'] : '';

// Default meta
$meta_title = "All Products | " . APP_NAME;
$meta_description = "Browse all products available in our store.";

// Use category-specific meta if available
if (!empty($selected_category) && isset($category_meta[$selected_category])) {
    $meta_title = $category_meta[$selected_category]['title'];
    $meta_description = $category_meta[$selected_category]['description'];
}

// Optional: search-specific meta
if (!empty($search)) {
    $search_term = htmlspecialchars($search);
    $meta_title = "Search results for '" . $search_term . "' | " . APP_NAME;
    $meta_description = "Find products matching '" . $search_term . "' in our store.";
}
?>


<?php
// Base URL
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$request_uri = $_SERVER['REQUEST_URI'];
$canonical_url = $protocol . $host . strtok($request_uri, '?');

// OG Title & Description
$og_title = $meta_title; // Use the same as meta title
$og_description = $meta_description; // Same as meta description

// Default OG image (can be product category image or store logo)
$og_image = $protocol . $host . "/uploads/default-product.png";

// If a category is selected, you could assign a category-specific image
if (!empty($selected_category) && isset($category_meta[$selected_category]['image'])) {
    $og_image = $protocol . $host . $category_meta[$selected_category]['image'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?= $meta_title ?></title>
    <meta name="description" content="<?= $meta_description ?>">
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="canonical" href="<?= htmlspecialchars($canonical_url) ?>">
    <meta name="robots" content="index, follow">
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($og_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($og_description) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonical_url) ?>">
    <meta property="og:image" content="<?= htmlspecialchars($og_image) ?>">
    <meta property="og:site_name" content="<?= APP_NAME ?>">

    <meta name="google-site-verification" content="ZlNr6S6JXMI9jO0JTdQHvBJc0V1aYZfiMDkNhziPCP4" />

    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-1RW7L87K4D"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }
        gtag('js', new Date());

        gtag('config', 'G-1RW7L87K4D');
    </script>

    <script type='application/ld+json'>
        {
            "@context": "https://www.schema.org",
            "@type": "WebSite",
            "name": "Nujora",
            "alternateName": "nujora",
            "url": "https://nujora.ng/"
        }
    </script>


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

        /* ================= NAVBAR ================= */
        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color);
        }

        /* ================= HERO ================= */
        .hero-section {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 3rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
        }

        /* ================= PRODUCT CARD ================= */
        .card {
            border: none;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.2rem;
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
            padding: 1rem;
        }

        .card-body .btn-container {
            margin-top: auto;
            padding-top: 0.8rem;
        }

        .product-img {
            width: 100%;
            height: 320px;
            object-fit: contain;
            border-radius: 12px 12px 0 0;
            transition: transform 0.3s ease;
        }

        .product-img:hover {
            transform: scale(1.05);
        }

        .card-title {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            line-height: 1.3;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .card-text {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.6rem;
        }

        /* ================= BUTTONS ================= */
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #e65100;
            border-color: #e65100;
        }

        a {
            text-decoration: none;
            color: inherit;
        }


        /* ================= BADGES ================= */
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

        /* ================= MOBILE OPTIMIZATION ================= */
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
                padding: 0.6rem;
            }

            .card-title {
                font-size: 0.8rem !important;
                line-height: 1.2;
                margin-bottom: 0.3rem;
                -webkit-line-clamp: 2;
            }

            .card-text {
                font-size: 0.8rem !important;
                margin-bottom: 0.4rem !important;
            }

            .product-img {
                height: 160px !important;
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

            .rating-mobile {
                font-size: 0.7rem;
                margin: 0.3rem 0;
            }

            .rating-mobile span {
                font-size: 0.75rem !important;
            }
        }

        /* ================= EXTRA SMALL MOBILE ================= */
        @media (max-width: 400px) {
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
                height: 130px !important;
            }
        }

        /* ================= TABLET OPTIMIZATION ================= */
        @media (min-width: 577px) and (max-width: 768px) {
            .col-6 {
                max-width: 33.333333% !important;
            }

            .product-img {
                height: 200px !important;
            }
        }

        /* ================= DESKTOP OPTIMIZATION ================= */
        @media (min-width: 769px) {
            .product-img {
                height: 220px !important;
                object-fit: contain;
            }

            .card-title {
                font-size: 1rem !important;
                line-height: 1.3;
                height: 2.6em;
                overflow: hidden;
                text-overflow: ellipsis;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
            }

            .card-text {
                font-size: 0.9rem !important;
                margin-bottom: 0.5rem;
            }

            .rating-mobile {
                font-size: 0.8rem;
                margin-bottom: 0.4rem;
            }

            .btn-sm {
                font-size: 0.85rem;
                padding: 0.35rem 0.75rem;
            }
        }

        /* ================= EXTRA LARGE SCREENS ================= */
        @media (min-width: 1200px) {
            .product-img {
                height: 240px !important;
            }

            .card-title {
                font-size: 1.05rem !important;
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
        <form class="d-flex mx-auto search-form-mobile my-3" style="max-width: 600px;" action="products.php" method="GET">
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
        <form method="GET" class="filter-form-mobile px-2">
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
                            $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
                            $stmt->execute();
                            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($categories as $cat):
                                $catName = $cat['name'];
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
        <div class="d-flex justify-content-between align-items-center mb-3 my-4">
            <h1 style="font-size:1.5rem;" class="section-title mb-0">
                <?php if ($search): ?>
                    Search Results for "<?= htmlspecialchars($search) ?>"
                <?php elseif ($category): ?>
                    <?= htmlspecialchars($category) ?> Products
                <?php else: ?>
                    All Products in Store
                <?php endif; ?>
            </h1>
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
                                <a href="product_details.php?id=<?= $product['id'] ?>">
                                    <img src="<?php echo isset($product['image_url']) && $product['image_url']
                                                    ? htmlspecialchars($product['image_url'])
                                                    : 'uploads/default-product.png'; ?>"
                                        class="product-img"
                                        alt="Product Image">
                                </a>

                                <!-- Stock badge -->
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="product-badge bg-success">In Stock</span>
                                <?php else: ?>
                                    <span class="product-badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                            </div>

                            <div class="card-body d-flex flex-column">
                                <!-- Product details -->
                                <div class="card-content">
                                    <a href="product_details.php?id=<?= $product['id'] ?>" style="text-decoration:none; color:inherit;">
                                        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                    </a>
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
                                                <span style="color: <?= $i <= floor($avg_rating) ? 'gold' : '#ccc' ?>;">&#9733;</span>
                                            <?php endfor; ?>
                                            <small class="ms-1">(<?= $avg_rating ?>)</small>
                                        </div>
                                    </div>

                                    <!-- Shop name -->
                                    <small class="text-muted d-block">
                                        <i class="fas fa-store me-1"></i>
                                        <?= htmlspecialchars($product['shop_name']) ?>
                                    </small>
                                </div>

                                <!-- Button container -->
                                <div class="btn-container mt-auto">
                                    <?php if ($product['stock'] > 0): ?>
                                        <button
                                            class="btn btn-primary btn-sm w-100 add-to-cart"
                                            data-id="<?= $product['id'] ?>"
                                            <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
                                            <i class="fas fa-shopping-cart d-none d-md-inline"></i>
                                            <span class="d-md-none">Add to Cart</span>
                                            <span class="d-none d-md-inline">Add to Cart</span>
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm w-100" disabled>
                                            Out of Stock
                                        </button>
                                    <?php endif; ?>
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

        <?php
        // -------------------------------------------
        // CATEGORY-SPECIFIC CONTENT ABOVE FOOTER
        // -------------------------------------------

        // Define custom footer content per category
        $category_footer_content = [
            "APPLIANCES" => '
                        <div class="container my-5">
                            <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                                <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                                    About Our Home Appliances
                                </h2>
                                <p>
                                    This category is dedicated to the Latest Home Appliances Products from renowned global and local brands. Nujora provides a reliable destination to Buy Home Appliances Products Online
                                    in Nigeria. We offer a comprehensive range of quality Home Appliances Products in Nigeria, ensuring that your Shopping Home Appliances Products experience is convenient, secure, and satisfying.
                                </p>
                            </div>
                        </div>
            ',
            "FASHION" => '
                <div class="container my-5">
                    <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                            Explore the Latest Fashion Trends in Nigeria
                        </h2>
                        <p>
                            Stay stylish with <strong>Nujora Fashion</strong>. Shop premium clothing, shoes, and accessories 
                            for men, women, and kids — all at unbeatable prices.
                        </p>
                    </div>
                </div>
            ',
            "ELECTRONICS" => '
                <div class="container my-5">
                    <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                            Buy the Latest Electronics and Gadgets Online
                        </h2>
                        <p>
                            Upgrade your tech with the latest electronics available at Nujora. 
                            Explore smartphones, TVs, and gadgets with great deals and fast nationwide delivery.
                        </p>
                    </div>
                </div>
            ',
            "BEAUTY & PERSONAL CARE" => '
                <div class="container my-5">
                    <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                            Discover Your Glow: Premium Beauty and Personal Care Products
                        </h2>
                    
                    <p>
                            Unleash your inner beauty with Nujora&#39;s exquisite collection of Beauty &#38; Personal Care Products.
                            We are a leading destination for high-quality skincare products in Nigeria, featuring serums,
                            moisturizers, and cleansers for every skin type. As one of the top online beauty stores in Nigeria,
                            we proudly showcase authentic Nigerian beauty products alongside international favorites.
                            It has never been easier to buy beauty care products online in Nigeria. Many consider us the best
                            skincare store in Nigeria because of our genuine products and great prices. Start shopping beauty
                            care products with us today and explore our range of effective Nigerian skincare products.
                        </p>

                    
                    </div>
                </div>
            ',
            "EDUCATIONAL PRODUCTS" => '
                <div class="container my-5">
                    <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                            Learning Through Play
                        </h2>
                    <p>
                            Nujora&#39;s Educational category features a wide array of Educational Products for Kids designed to inspire young minds. 
                            We offer a convenient hub to Shop Education Products Online and Buy Educational Products For Kids Online safely. 
                            Our selection represents some of the best Learning Educational Products in Nigeria, supporting cognitive development and creative thinking.
                        </p>

                    </div>
                </div>
            
            ',
            "FASHION" => '
                <div class="container my-5">
                    <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                            Express Your Style: The Best Online Shopping in Nigeria for Clothes
                        </h2>
                    
                    <p>
                            Refresh your wardrobe with the latest styles from the comfort of your home. Nujora offers the ultimate experience for online shopping in Nigeria for clothes. 
                            Discover a vast array of trendy and classic apparel for men, women, and children. 
                            We simplify the way you online buy clothes in Nigeria, offering a user-friendly platform and secure payment options. 
                            Whether you&#39;re shopping for clothes in Nigeria for a special event or everyday wear, Nujora has you covered. 
                            It&#39;s the best place to buy clothes in Nigeria, combining style, quality, and convenience.
                        </p>


                    
                    </div>
                </div>
            ',
            "PHONES AND TABLETS" => '
                        <div class="container my-5">
                            <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                                <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                                    Stay Connected: Find Cheap Android Phones & Tablets in Nigeria
                                </h2>
                            
                            <p>
                                    Get the latest technology without breaking the bank. Nujora is your go-to source for cheap Android phones in Nigeria and high-performance tablets. 
                                    We carefully select the best Android phones in Nigeria, offering a range of models from budget-friendly options to premium flagship devices. 
                                    You can easily compare tablets and their prices in Nigeria on our site. 
                                    We make it simple and secure to buy Android phones online. 
                                    Explore our wide selection of online Android phones in Nigeria and enjoy a great shopping for Android phones in Nigeria experience with guaranteed quality and reliable service.
                                </p>               
                            </div>
                        </div>
            ',
            "TOYS, BABY & KIDS PRODUCTS" => '
                        <div class="container my-5">
                            <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
                                <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
                                    Joyful Learning & Play: The Best Baby Toys in Nigeria
                                </h2>
                            
                            <p>
                                Bring smiles and support development with our wonderful collection of baby toys in Nigeria. 
                                Nujora offers a huge variety of toys in Nigeria for all ages, from soft rattles for infants to action figures and dolls for older children. 
                                We are particularly proud of our range of educational toys in Nigeria that combine fun with learning. 
                                It&#39;s easy and safe to buy children toys in Nigeria through our platform. 
                                Parents can shop toddler toys in Nigeria with confidence, knowing they are getting quality products. 
                                Discover our fantastic selection of online learning toys for kids and give your child the gift of playful learning.
                            </p>


                            
                            </div>
                        </div>
            ',

        ];

        // Detect selected category from URL (example: ?category=ELECTRONICS)
        $selected_category = isset($_GET['category']) ? strtoupper(trim($_GET['category'])) : '';

        // Display category-specific content or default block
        if (!empty($selected_category) && isset($category_footer_content[$selected_category])) {
            echo $category_footer_content[$selected_category];
        }
        ?>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Script -->
    <?php include 'includes/script.php'; ?>
</body>

</html>