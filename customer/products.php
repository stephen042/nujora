<?php
// Database connection
require '../app/config.php';

// Input
$category = isset($_GET['category']) ? trim($_GET['category']) : null;
$search = isset($_GET['q']) ? trim($_GET['q']) : null;
$min_price = isset($_GET['min_price']) && is_numeric($_GET['min_price']) ? (float) $_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) && is_numeric($_GET['max_price']) ? (float) $_GET['max_price'] : null;

// Base SQL
$sql = "SELECT * FROM products WHERE 1";
$countSql = "SELECT COUNT(*) FROM products WHERE 1";
$params = [];

// Apply search (q)
if ($search) {
    $sql .= " AND name LIKE :search";
    $countSql .= " AND name LIKE :search";
    $params['search'] = "%$search%";
}

// Apply category
if ($category) {
    $sql .= " AND category = :category";
    $countSql .= " AND category = :category";
    $params['category'] = $category;
}

// Price filter
if ($min_price !== null) {
    $sql .= " AND price >= :min_price";
    $countSql .= " AND price >= :min_price";
    $params['min_price'] = $min_price;
}
if ($max_price !== null) {
    $sql .= " AND price <= :max_price";
    $countSql .= " AND price <= :max_price";
    $params['max_price'] = $max_price;
}

// Pagination
$page = max((int) ($_GET['page'] ?? 1), 1);
$limit = 12;
$offset = ($page - 1) * $limit;
$sql .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";

// Fetch filtered products
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count total for pagination
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Optional: Fetch reviews if needed for ratings (dummy logic)
$grouped_reviews = []; // Assuming you'll populate this elsewhere

// for search 
$searchQuery = isset($_GET['q']) ? trim($_GET['q']) : '';

if ($searchQuery !== '') {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE name LIKE ?");
    $stmt->execute(["%$searchQuery%"]);
} else {
    $stmt = $pdo->query("SELECT * FROM products");
}

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products | Nujora</title>
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f57c00;
            /* Bright Orange */
            --secondary-color: #ef6c00;
            /* Deep Orange */
            --accent-color: #ffb74d;
            /* Soft Yellow-Orange */
            --light-bg: #fff8f0;
            /* Warm Light Background */
            --dark-text: #1e1e1e;
            /* Darker Text for Better Contrast */
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
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .product-img {
            width: 100%;
            height: 320px;
            object-fit: cover;
            border-radius: 12px;
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-img:hover {
            transform: scale(1.1);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .seller-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .badge-tier {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
            font-weight: 600;
        }

        .badge-platinum {
            background-color: #e5e4e2;
            color: #333;
        }

        .badge-gold {
            background-color: #ffd700;
            color: #333;
        }

        .badge-silver {
            background-color: #c0c0c0;
            color: #333;
        }

        .badge-bronze {
            background-color: #cd7f32;
            color: white;
        }

        .badge-new {
            background-color: #808080;
            color: white;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 0.9rem;
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

        .category-card {
            text-align: center;
            padding: 20px;
            border-radius: 10px;
            transition: all 0.3s;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .category-icon {
            font-size: 2rem;
            margin-bottom: 10px;
            color: var(--accent-color);
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
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-danger {
            background-color: #dc3545 !important;
        }
    </style>

</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <div class="container">
        <!-- add search bar here -->
        <form class="d-flex mx-auto mt-4 mb-3" style="max-width: 600px;" action="products.php" method="GET">
            <input
                class="form-control form-control-lg me-2"
                type="search"
                name="name"
                id="live-search"
                placeholder="Search products..."
                aria-label="Search"
                autocomplete="off" />

            <button class="btn btn-light btn-lg" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
        <div id="search-results" class="row g-4 mt-4"></div>



        <!--  Products -->
        <div class="row g-6 mb-5 mx-auto my-3">

            <!-- Filter Form -->
            <form method="GET" class="row mb-4 g-3 align-items-end">
                <div class="col-md-4">
                    <label for="category" class="form-label">Filter by Category</label>
                    <select name="category" id="category" class="form-select">
                        <option value="">All Categories</option>
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
                        $stmt->execute();
                        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($categories as $cat):
                            $catName = $cat['name']; // Assuming your DB column is `name`
                        ?>
                            <option value="<?= $catName ?>" <?= ($category === $catName) ? 'selected' : '' ?>>
                                <?= $catName ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                </div>

                <div class="col-md-3">
                    <label for="min_price" class="form-label">Min Price</label>
                    <input type="number" class="form-control" name="min_price" placeholder="₦0" value="<?= htmlspecialchars($_GET['min_price'] ?? '') ?>">
                </div>

                <div class="col-md-3">
                    <label for="max_price" class="form-label">Max Price</label>
                    <input type="number" class="form-control" name="max_price" placeholder="₦10,000" value="<?= htmlspecialchars($_GET['max_price'] ?? '') ?>">
                </div>

                <div class="col-md-2">
                    <button class="btn btn-primary w-100" type="submit">Filter</button>
                </div>
            </form>
            <h4>All Products</h4>
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-4">
                    <div class="alert alert-info">No products available at the moment.</div>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-6 col-md-4 col-lg-3 my-2 col-sm-6" style="max-height: 450px; max-width: 300px;">
                        <div class="card h-100">
                            <img src="<?php echo isset($product['image_url']) && $product['image_url']
                                            ? "../" . htmlspecialchars($product['image_url'])
                                            : "../uploads/default-product.png"; ?>"
                                class="product-img"
                                alt="<?= htmlspecialchars($product['name']) ?>">

                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
                                <p class="card-text">Price: ₦<?= htmlspecialchars($product['price']) ?></p>
                                <!-- Stock badge -->
                                <?php if ($product['stock'] > 0): ?>
                                    <span class="product-badge bg-success">In Stock</span>
                                <?php else: ?>
                                    <span class="product-badge bg-danger">Out of Stock</span>
                                <?php endif; ?>
                                <!-- Rating stars -->
                                <div>
                                    <?php
                                    $avg_rating = 0;
                                    if (!empty($grouped_reviews[$product['id']])) {
                                        $ratings = array_column($grouped_reviews[$product['id']], 'rating');
                                        $avg_rating = round(array_sum($ratings) / count($ratings), 1);
                                    }
                                    ?>
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <?php if ($i <= floor($avg_rating)): ?>
                                            <span style="color: gold;">&#9733;</span>
                                        <?php else: ?>
                                            <span style="color: #ccc;">&#9733;</span>
                                        <?php endif; ?>
                                    <?php endfor; ?>
                                    <small>(<?= $avg_rating ?>)</small>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="product_details.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i> View
                                    </a>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php
                        $queryParams = $_GET;
                        $queryParams['page'] = $i;
                        $queryStr = http_build_query($queryParams);
                        ?>
                        <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                            <a class="page-link" href="products.php?<?= $queryStr ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
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
    <script>
        const searchInput = document.getElementById('live-search');
        const resultContainer = document.getElementById('search-results');

        let delayTimer;

        searchInput.addEventListener('input', function() {
            clearTimeout(delayTimer);
            const query = this.value.trim();

            if (query.length < 2) {
                resultContainer.innerHTML = '';
                return;
            }

            delayTimer = setTimeout(() => {
                fetch(`live_search.php?name=${encodeURIComponent(query)}`)
                    .then(response => response.text())
                    .then(data => {
                        resultContainer.innerHTML = data;
                    });
            }, 300); // wait 300ms after typing stops
        });
    </script>

</body>

</html>