<?php
require 'app/config.php';

// Authentication check - built directly into this file
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit;
}

// Verify user is a buyer
try {
    $stmt = $pdo->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user || $user['role'] !== 'buyer') {
        header("Location: unauthorized.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database error during authentication: " . $e->getMessage());
}
?>

<?php

// Fetch sellers with average rating and product count
try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.name,
            u.shop_name,
            u.shop_logo,
            ROUND(AVG(r.stars), 2) AS avg_rating,
            COUNT(DISTINCT p.id) AS product_count,
            COUNT(DISTINCT r.id) AS review_count
        FROM users u
        LEFT JOIN ratings r ON u.id = r.seller_id 
        LEFT JOIN products p ON u.id = p.seller_id
        WHERE u.role = 'seller' AND u.approval_status = 'approved'
        GROUP BY u.id
        ORDER BY avg_rating DESC, review_count DESC
    ");
    $stmt->execute();
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $sellers = [];
    $error_message = "Unable to load seller information. Please try again later.";
}

// Badge logic with improved tier system
function getSellerBadge($avg_rating)
{
    if ($avg_rating === null) {
        return [
            'label' => 'New Seller',
            'color' => '#808080',
            'icon' => 'bi-star'
        ];
    }

    if ($avg_rating >= 4.5) {
        return [
            'label' => 'Platinum Seller',
            'color' => '#e5e4e2',
            'icon' => 'bi-award'
        ];
    } elseif ($avg_rating >= 4.0) {
        return [
            'label' => 'Gold Seller',
            'color' => '#ffd700',
            'icon' => 'bi-trophy'
        ];
    } elseif ($avg_rating >= 3.0) {
        return [
            'label' => 'Silver Seller',
            'color' => '#c0c0c0',
            'icon' => 'bi-medal'
        ];
    } else {
        return [
            'label' => 'Rising Seller',
            'color' => '#cd7f32', // Bronze
            'icon' => 'bi-arrow-up-circle'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Marketplace | <?= APP_NAME ?></title>
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #f57c00;
            --secondary-color: #ef6c00;
            --platinum: #e5e4e2;
            --gold: #ffd700;
            --silver: #c0c0c0;
            --bronze: #cd7f32;
        }

        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
            color: #333;
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

        .seller-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            margin-bottom: 1.5rem;
            border: none;
        }

        .seller-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.12);
        }

        .seller-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
        }

        .badge-platinum {
            background-color: var(--platinum);
            color: #333;
        }

        .badge-gold {
            background-color: var(--gold);
            color: #333;
        }

        .badge-silver {
            background-color: var(--silver);
            color: #333;
        }

        .badge-bronze {
            background-color: var(--bronze);
            color: white;
        }

        .badge-new {
            background-color: #808080;
            color: white;
        }

        .rating-stars {
            color: #ffc107;
            font-size: 1.1rem;
        }

        .seller-meta {
            font-size: 0.9rem;
            color: #6c757d;
        }

        .search-box {
            max-width: 500px;
            margin: 0 auto 2rem;
        }

        .filter-section {
            background-color: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .pagination .page-item.active .page-link {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .page-link {
            color: var(--primary-color);
        }

        footer {
            background-color: #343a40;
            color: white;
            padding: 2rem 0;
            margin-top: 3rem;
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container text-center">
            <h1 class="display-4 fw-bold mb-3">Discover Trusted Sellers</h1>
            <p class="lead mb-4">Shop from our community of verified sellers with customer ratings</p>
            <div class="search-box">
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search sellers..." aria-label="Search">
                    <button class="btn btn-light" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="categoryFilter" class="form-label">Category</label>
                    <select class="form-select" id="categoryFilter">
                        <option selected>All Categories</option>
                        <option>Electronics</option>
                        <option>Fashion</option>
                        <option>Home & Garden</option>
                        <option>Groceries</option>
                    </select>
                </div>
                <div class="col-md-4 mb-3 mb-md-0">
                    <label for="ratingFilter" class="form-label">Minimum Rating</label>
                    <select class="form-select" id="ratingFilter">
                        <option selected>Any Rating</option>
                        <option>4+ Stars</option>
                        <option>3+ Stars</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="sortBy" class="form-label">Sort By</label>
                    <select class="form-select" id="sortBy">
                        <option selected>Highest Rated</option>
                        <option>Most Products</option>
                        <option>Most Reviews</option>
                        <option>Newest</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Sellers Grid -->
        <div class="row">
            <?php if (!empty($error_message)): ?>
                <div class="col-12">
                    <div class="alert alert-danger"><?= $error_message ?></div>
                </div>
            <?php elseif (empty($sellers)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-emoji-frown" style="font-size: 3rem;"></i>
                    <h4 class="mt-3">No sellers found</h4>
                    <p>Please check back later or try different filters</p>
                </div>
            <?php else: ?>
                <?php foreach ($sellers as $seller):
                    $badge = getSellerBadge($seller['avg_rating']);
                    $rating = $seller['avg_rating'] ?? 0;
                    $review_count = $seller['review_count'] ?? 0;
                ?>
                    <div class="col-md-6 col-lg-4 py-3">
                        <div class="seller-card card h-100">
                            <div class="card-body text-center">
                                <img src="<?= htmlspecialchars($seller['shop_logo'] ?? '../uploads/default-product.png') ?>"
                                    alt="<?= htmlspecialchars($seller['shop_name']) ?>"
                                    class="seller-logo mb-3">
                                <h5 class="card-title mb-1"><?= htmlspecialchars($seller['shop_name']) ?></h5>
                                <p class="text-muted mb-2"><?= htmlspecialchars($seller['name']) ?></p>

                                <span class="badge mb-3 <?=
                                                        strtolower(str_replace(' ', '-', $badge['label']))
                                                        ?>">
                                    <i class="bi <?= $badge['icon'] ?>"></i> <?= $badge['label'] ?>
                                </span>

                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="bi <?= $i <= round($rating) ? 'bi-star-fill' : 'bi-star' ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1"><?= number_format($rating, 1) ?></span>
                                </div>

                                <div class="seller-meta mb-3">
                                    <span class="me-2">
                                        <i class="bi bi-box-seam"></i> <?= $seller['product_count'] ?> products
                                    </span>
                                    <span>
                                        <i class="bi bi-chat-square-text"></i> <?= $review_count ?> reviews
                                    </span>
                                </div>

                                <a href="seller-products.php?id=<?= $seller['id'] ?>" class="btn btn-primary">
                                    <i class="bi bi-basket"></i> View Products
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Pagination -->
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <li class="page-item disabled">
                    <a class="page-link" href="#" tabindex="-1">Previous</a>
                </li>
                <li class="page-item active"><a class="page-link" href="#">1</a></li>
                <li class="page-item"><a class="page-link" href="#">2</a></li>
                <li class="page-item"><a class="page-link" href="#">3</a></li>
                <li class="page-item">
                    <a class="page-link" href="#">Next</a>
                </li>
            </ul>
        </nav>
    </div>

    <!-- Footer -->
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Script -->
    <?php include 'includes/script.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simple filter functionality
        document.getElementById('ratingFilter').addEventListener('change', function() {
            // In a real implementation, this would filter the sellers
            console.log("Filter by rating: ", this.value);
        });

        // Add to favorites functionality
        document.querySelectorAll('.btn-favorite').forEach(btn => {
            btn.addEventListener('click', function() {
                const sellerId = this.dataset.sellerId;
                console.log("Added seller to favorites: ", sellerId);
                // AJAX call to save favorite
            });
        });
    </script>
</body>

</html>