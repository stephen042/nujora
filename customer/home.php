<?php
require '../app/config.php';

// Fetch products for the home page
try {
  $stmt = $pdo->prepare("
        SELECT p.*, u.shop_name 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE u.approval_status = 'approved'
        ORDER BY p.created_at DESC 
        LIMIT 12
    ");
  $stmt->execute();
  $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error: " . $e->getMessage());
}

// Fetch top-rated sellers
try {
  $stmt = $pdo->prepare("
        SELECT 
            u.id, 
            u.shop_name,
            u.shop_logo,
            ROUND(AVG(r.stars), 2) AS avg_rating,
            COUNT(r.id) AS rating_count
        FROM users u
        LEFT JOIN ratings r ON u.id = r.seller_id 
        WHERE u.role = 'seller' AND u.approval_status = 'approved'
        GROUP BY u.id
        HAVING COUNT(r.id) > 0
        ORDER BY avg_rating DESC
        LIMIT 8
    ");
  $stmt->execute();
  $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  $sellers = [];
  error_log("Error fetching sellers: " . $e->getMessage());
}

// Badge logic with enhancements
function getSellerBadge($avg_rating)
{
  if ($avg_rating === null) {
    return ['label' => 'New Seller', 'color' => '#808080', 'icon' => 'fa-star'];
  } elseif ($avg_rating >= 4.5) {
    return ['label' => 'Platinum Seller', 'color' => '#e5e4e2', 'icon' => 'fa-award'];
  } elseif ($avg_rating >= 4.0) {
    return ['label' => 'Gold Seller', 'color' => '#ffd700', 'icon' => 'fa-trophy'];
  } elseif ($avg_rating >= 3.0) {
    return ['label' => 'Silver Seller', 'color' => '#c0c0c0', 'icon' => 'fa-medal'];
  } else {
    return ['label' => 'Rising Seller', 'color' => '#cd7f32', 'icon' => 'fa-arrow-up'];
  }
}

// Fetch reviews for products
$stmt = $pdo->prepare("SELECT product_id, review_text, rating FROM reviews");
$stmt->execute();
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
error_log("SQL Query: SELECT product_id, review_text, rating FROM reviews");

//  for search
$search = $_GET['search'] ?? '';
$suggestions = [];

if (!empty($search)) {
  $stmt = $pdo->prepare("SELECT name FROM products WHERE name LIKE :search LIMIT 5");
  $stmt->execute(['search' => "%$search%"]);
  $suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Home | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
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
      /* font-family: 'Roboto', 'Segoe UI', sans-serif; */
      font-family: 'Open Sans', sans-serif;
      background-color: var(--light-bg);
      color: var(--dark-text);
      padding-bottom: 60px;
    }
    h1, h2, h3, h4 {
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

    .category-scroll-wrapper {
      overflow-x: auto;
      white-space: nowrap;
      padding: 10px 0;
      position: relative;
    }

    .category-scroll-wrapper::-webkit-scrollbar {
      display: none;
    }

    .category-card {
      display: inline-block;
      width: 140px;
      margin-right: 16px;
      text-align: center;
      border-radius: 10px;
      padding: 16px;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease;
    }

    .category-card:hover {
      transform: translateY(-3px);
    }

    .category-icon {
      font-size: 30px;
      margin-bottom: 10px;
      color: #f57c00;
    }

    .scroll-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: white;
      border: none;
      font-size: 20px;
      z-index: 10;
      cursor: pointer;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
      border-radius: 50%;
      width: 32px;
      height: 32px;
    }

    .scroll-left {
      left: -12px;
    }

    .scroll-right {
      right: -12px;
    }

    @media (min-width: 768px) {
      .category-scroll-wrapper {
        padding: 0 40px;
      }
    }

    .category-card {
      transition: transform 0.2s ease-in-out;
    }

    .category-card:hover {
      transform: scale(1.05);
    }

    @media (min-width: 768px) {
      .category-card {
        width: 140px;
      }
    }

    @media (min-width: 992px) {
      .category-card {
        width: 160px;
      }
    }

    @media (min-width: 768px) {
      #categoryScroll {
        max-width: 1000px;
        margin-left: auto;
        margin-right: auto;
      }
    }


    #searchInput:focus+div {
      display: block;
    }

    #suggestionsBox {
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      max-height: 200px;
      overflow-y: auto;
    }

    @media (min-width: 992px) {
      .category-wrapper-responsive {
        max-width: 1000px;
        width: fit-content;
      }
    }
  </style>

</head>

<body>
  <!-- Navigation -->
  <?php include 'includes/nav.php'; ?>

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container text-center">
      <?php if (isset($_SESSION['user_name'])): ?>
        <h1 class="display-5 fw-bold mb-3">Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?>!</h1>
      <?php else: ?>
        <h1 class="display-5 fw-bold mb-3">Discover Amazing Products</h1>
      <?php endif; ?>
      <p class="lead mb-4">Shop from trusted sellers in your community</p>

      <form class="d-flex mx-auto" style="max-width: 600px;" action="products.php" method="GET" id="searchForm">
        <div class="position-relative w-100">
          <input type="text" name="q" id="searchInput" class="form-control" placeholder="Search products..." autocomplete="off">
          <div id="suggestionsBox" class="position-absolute bg-white border w-100 mt-2 rounded d-none" style="z-index: 1000;"></div>
        </div>

        <button class="btn btn-light ms-2" type="submit">
          <i class="fas fa-search"></i>
        </button>
      </form>
    </div>
  </section>


  <!-- Main Content -->
  <div class="container">
    <!-- Categories -->
    <?php
    try {
      $stmt = $pdo->prepare("SELECT * FROM categories ORDER BY name ASC");
      $stmt->execute();
      $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
      die("Database error: " . $e->getMessage());
    }
    ?>


    <h4 class="section-title">Shop by Category</h4>
    <div class="position-relative mb-5">
      <button class="scroll-btn scroll-left" onclick="scrollCategory(-300)">
        <i class="fas fa-chevron-left"></i>
      </button>

      <!-- Wrapper with max-width and auto margin -->
      <div id="categoryScroll" class="category-scroll-wrapper bg-light rounded px-3 py-3 overflow-auto category-wrapper-responsive">
        <div class="d-flex flex-row flex-nowrap gap-3">
          <?php foreach ($categories as $row): ?>
            <a href="products.php?category=<?= rawurlencode($row['name']) ?>" class="text-decoration-none flex-shrink-0">
              <div class="category-card bg-white text-center px-3 py-2" style="width: 120px;">
                <div class="category-icon mb-2">
                  <?php if (!empty($row['image_url'])): ?>
                    <img src="<?= htmlspecialchars($row['image_url']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="img-fluid" style="width: 70px; height: 70px;">
                  <?php else: ?>
                    <i class="fas fa-tags fa-2x"></i>
                  <?php endif; ?>
                </div>
                <h6 class="mb-0 text-break" style="word-wrap: break-word; white-space: normal;font-size: 0.8rem">
                  <?= htmlspecialchars($row['name']) ?>
                </h6>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
      </div>

      <button class="scroll-btn scroll-right" onclick="scrollCategory(300)">
        <i class="fas fa-chevron-right"></i>
      </button>
    </div>



    <!-- Top Sellers -->
    <h4 class="section-title">Top Rated Sellers</h4>
    <div class="row g-4 mb-5">
      <?php if (empty($sellers)): ?>
        <div class="col-12 text-center py-4">
          <div class="alert alert-info">No top sellers available at the moment.</div>
        </div>
      <?php else: ?>
        <?php foreach ($sellers as $seller):
          $badge = getSellerBadge($seller['avg_rating']);
          $badgeClass = strtolower(str_replace(' ', '-', $badge['label']));
        ?>
          <div class="col-6 col-md-4 col-lg-3">
            <div class="card h-100">
              <div class="card-body text-center">
                <img src="<?= htmlspecialchars($seller['shop_logo'] ?? 'assets/default-shop.png') ?>"
                  class="seller-logo mb-3"
                  alt="<?= htmlspecialchars($seller['shop_name']) ?>">
                <h5 class="card-title mb-1"><?= htmlspecialchars($seller['shop_name']) ?></h5>

                <span class="badge badge-tier badge-<?= $badgeClass ?> mb-2">
                  <i class="fas <?= $badge['icon'] ?> me-1"></i> <?= $badge['label'] ?>
                </span>

                <div class="rating-stars mb-2">
                  <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fas <?= $i <= round($seller['avg_rating']) ? 'fa-star' : 'fa-star-half-alt' ?>"></i>
                  <?php endfor; ?>
                  <span class="ms-1"><?= number_format($seller['avg_rating'], 1) ?></span>
                </div>

                <p class="small text-muted mb-3">
                  <i class="fas fa-comment-alt me-1"></i> <?= $seller['rating_count'] ?> reviews
                </p>

                <a href="seller.php?id=<?= $seller['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                  Visit Shop
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Featured Products -->
    <h4 class="section-title">
      <a href="products.php" class="text-decoration-none">View All Products <i class="fas fa-chevron-right"></i><i class="fas fa-chevron-right"></i>
      </a>
    </h4>
    <div class="row g-6 mb-2 mx-auto">
      <?php if (empty($products)): ?>
        <div class="col-12 text-center py-4">
          <div class="alert alert-info">No featured products available at the moment.</div>
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
                <h5 class="card-title fs-6"><?= htmlspecialchars($product['name']) ?></h5>
                <p class="card-text mb-2">Price: ₦<?= number_format($product['price'], 2) ?></p>
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
                  <!-- <a href="save_item" class="btn btn-transparent btn-sm fs-5">
                    <i class="fas fa-heart text-danger"></i>
                  </a> -->
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <div>
      <a href="products.php" class="btn btn-outline-primary w-100 text-decoration-none my-5">View All Products <i class="fas fa-chevron-right"></i><i class="fas fa-chevron-right"></i>
      </a>
    </div>
  </div>
  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Bottom Navigation -->
  <?php include 'includes/bottomNav.php'; ?>

  <!-- Script -->
  <?php include 'includes/script.php'; ?>
  <script>
    function scrollCategory(val) {
      document.getElementById('categoryScroll').scrollBy({
        left: val,
        behavior: 'smooth'
      });
    }
  </script>
</body>

</html>