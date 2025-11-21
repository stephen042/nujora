<?php
require 'app/config.php';

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

// Fetch products for the home page
try {
  $stmt = $pdo->prepare("
        SELECT p.*, u.shop_name 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE u.approval_status = 'approved'
        AND p.is_featured = 1
        ORDER BY p.created_at DESC 
        LIMIT 12
    ");
  $stmt->execute();
  $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reliable Online Shopping in <?= APP_NAME ?> | Shop Quality Deals | Nujora</title>
  <meta name="description" content="Nujora offers the best online shopping in <?= APP_NAME ?>. Enjoy secure payments, trusted sellers, and unbeatable deals on fashion, gadgets, and daily essentials. Contact now!">
  <link rel="icon" type="image/png" href="uploads/default-product.png">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="canonical" href="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" />
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <meta property="og:title" content="Reliable Online Shopping in <?= APP_NAME ?> | Shop Quality Deals | Nujora" />
  <meta property="og:description" content="Nujora offers the best online shopping in <?= APP_NAME ?>. Enjoy secure payments, trusted sellers, and unbeatable deals on fashion, gadgets, and daily essentials. Contact now!" />
  <meta property="og:url" content="<?php echo 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']; ?>" />
  <meta property="og:type" content="website" />
  <meta property="og:image" content="uploads/default-product.png" />
  <meta property="og:site_name" content="<?= APP_NAME ?>" />

  <meta name="robots" content="index, follow">
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

    /* ================= NAVBAR ================= */
    .navbar-brand {
      font-weight: 700;
      color: var(--primary-color);
    }

    .navbar-nav .nav-link.active {
      border-bottom: 2px solid var(--primary-color);
      color: var(--primary-color) !important;
      font-weight: 600;
    }

    /* ================= HERO CAROUSEL ================= */
    .hero-section {
      margin-bottom: 2rem;
      border-radius: 0 0 20px 20px;
      overflow: hidden;
    }

    .hero-section .carousel-item {
      height: 500px;
      /* Full height for desktop */
      background-size: cover;
      background-position: center;
      position: relative;
    }

    .hero-section .carousel-item .overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.5);
      display: flex;
      justify-content: center;
      align-items: center;
      text-align: center;
      color: #fff;
      padding: 0 1rem;
    }

    /* ================= CARDS ================= */
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

    .product-img {
      width: 100%;
      height: 320px;
      object-fit: contain;
      border-radius: 12px;
      box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .product-img:hover {
      transform: scale(1.1);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    /* ================= CATEGORIES ================= */
    .category-card {
      width: 140px;
      min-height: 150px;
      text-align: center;
      padding: 16px 10px;
      border-radius: 10px;
      background: #fff;
      display: flex;
      flex-direction: column;
      align-items: center;
      transition: transform 0.3s;
      box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
    }

    .category-card:hover {
      transform: translateY(-5px);
    }

    .category-icon img {
      width: 70px;
      height: 70px;
      object-fit: contain;
    }

    .category-card h6 {
      font-size: 0.8rem;
      line-height: 1.2;
      height: 2.4em;
      /* 2 lines */
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    /* ================= SCROLL BUTTONS ================= */
    .scroll-btn {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      background: white;
      border: none;
      font-size: 18px;
      z-index: 10;
      cursor: pointer;
      box-shadow: 0 0 6px rgba(0, 0, 0, 0.2);
      border-radius: 50%;
      width: 36px;
      height: 36px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .scroll-left {
      left: -14px;
    }

    .scroll-right {
      right: -14px;
    }

    /* ================= SECTION TITLES ================= */
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



    /* ================= MOBILE ================= */
    @media (max-width: 576px) {
      .hero-section .carousel-item {
        height: 300px;
        /* smaller height for mobile */
      }

      #suggestionsBox {
        max-width: 600px;
        position: absolute;
        width: 100%;
        z-index: 1000;
        display: none;
      }

      .category-card {
        width: 100px !important;
        padding: 12px 8px;
        margin-right: 12px;
      }

      .category-card h6 {
        font-size: 0.7rem;
      }

      .scroll-btn {
        width: 28px;
        height: 28px;
        font-size: 14px;
      }

      /* ==== PRODUCT CARD MOBILE FIX ==== */
      .product-img {
        height: 180px !important;
        /* smaller image for mobile */
      }

      .card-body {
        padding: 0.5rem !important;
      }

      .card-title {
        font-size: 0.85rem !important;
        line-height: 1.2;
        height: 2.4em;
        /* limit to 2 lines */
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }

      .card-text {
        font-size: 0.85rem !important;
      }

      .btn-sm {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
      }

      .card {
        margin-bottom: 0.75rem;
      }
    }

    /* ================= DESKTOP ================= */
    @media (min-width: 768px) {
      .product-img {
        height: 200px !important;
        /* was 320px */
        object-fit: contain;
      }

      #suggestionsBox {
        max-width: 600px;
        position: absolute;
        left: 300px;
        width: 100%;
        z-index: 1000;
        display: none;
      }

      .card-title {
        font-size: 1rem !important;
        line-height: 1.3;
        height: 2.6em;
        /* ~2 lines */
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
      }

      .card-text {
        font-size: 0.9rem !important;
      }

      .btn-sm {
        font-size: 0.85rem;
        padding: 0.35rem 0.75rem;
      }
    }

    /* ================= LARGE DESKTOP ================= */
    @media (min-width: 1200px) {
      .product-img {
        height: 220px !important;
        /* slightly larger for wide screens */
      }
    }
  </style>

</head>

<body>
  <!-- Navigation -->
  <?php include 'includes/nav.php'; ?>

  <!-- Hero Section with Carousel + BG Images -->
  <section class="hero-section mb-5">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner">

        <!-- Slide 1 -->
        <div class="carousel-item active" style="height: 450px; background: url('images/bg3.png') center/cover no-repeat;">
          <div class="d-flex h-100 justify-content-center align-items-center bg-dark bg-opacity-50 text-center text-white">
            <div>
              <h1 class="display-4 fw-bold mb-3">Discover Amazing Products</h1>
              <p class="lead mb-4">Shop from trusted sellers in your community</p>
              <a href="products.php" class="btn btn-light">Shop Now</a>
            </div>
          </div>
        </div>

        <!-- Slide 2 -->
        <div class="carousel-item" style="height: 450px; background: url('images/bg1.png') center/cover no-repeat;">
          <div class="d-flex h-100 justify-content-center align-items-center bg-dark bg-opacity-50 text-center text-white">
            <div>
              <h1 class="display-4 fw-bold mb-3">Big Discount Week</h1>
              <p class="lead mb-4">Up to 50% off selected items</p>
              <a href="products.php?discounts=1" class="btn btn-light">Grab Deals</a>
            </div>
          </div>
        </div>

        <!-- Slide 3 -->
        <div class="carousel-item" style="height: 450px; background: url('images/bg2.png') center/cover no-repeat;">
          <div class="d-flex h-100 justify-content-center align-items-center bg-dark bg-opacity-50 text-center text-white">
            <div>
              <h1 class="display-4 fw-bold mb-3">Support Local Shops</h1>
              <p class="lead mb-4">Buy from sellers in your area</p>
              <a href="sellers.php" class="btn btn-light">Find Sellers</a>
            </div>
          </div>
        </div>
      </div>
      <!-- Controls -->
      <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </section>

  <!-- Main Content -->
  <div class="container mb-3">
    <!-- search bar -->
    <div style="margin: 30px 10px; position: relative;">
      <form class="d-flex mx-auto search-form-mobile my-3" style="max-width: 600px;" action="products.php" method="GET">
        <input
          id="searchInput"
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

      <!-- LIVE SUGGESTIONS -->
      <ul id="suggestionsBox" class="list-group shadow">
      </ul>
    </div>


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
      <div id="categoryScroll" class="category-scroll-wrapper rounded px-3 py-3 overflow-auto category-wrapper-responsive">
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


    <?php if (count($featured_products) > 0): ?>
      <!-- Featured Goods -->
      <section class="mb-5 position-relative">
        <h4 class="section-title mb-3">Featured Goods</h4>
        <div class="category-scroll-wrapper overflow-hidden position-relative">
          <div id="featuredGoods" class="d-flex flex-row flex-nowrap gap-3 px-4 py-2 overflow-auto" style="scroll-behavior: smooth;">
            <?php foreach ($featured_products as $product): ?>
              <a href="product_details.php?id=<?= $product['id'] ?>" style="text-decoration: none;">
                <div class="card" style="min-width: 180px; max-width: 200px;">
                  <img src="<?php echo isset($product['image_url']) && $product['image_url']
                              ? "" . htmlspecialchars($product['image_url'])
                              : "uploads/default-product.png"; ?>"
                    class="card-img-top" alt="<?= htmlspecialchars($product['name']) ?>"
                    style="height: 150px; object-fit: contain;">
                  <div class="card-body p-2">
                    <h6 class="card-title text-truncate"><?= htmlspecialchars($product['name']) ?></h6>
                    <p class="card-text text-primary fw-bold mb-2">₦<?= number_format($product['price'], 2) ?></p>
                    <a href="product_details.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-primary w-100">View</a>
                  </div>
                </div>
              </a>
            <?php endforeach; ?>
          </div>
          <!-- Arrows with Font Awesome -->
          <button class="scroll-btn left position-absolute top-50 start-0 translate-middle-y d-flex align-items-center justify-content-center rounded-circle bg-white shadow" style="width:40px; height:40px; border:0;">
            <i class="fas fa-chevron-left"></i>
          </button>

          <button class="scroll-btn right position-absolute top-50 end-0 translate-middle-y d-flex align-items-center justify-content-center rounded-circle bg-white shadow" style="width:40px; height:40px; border:0;">
            <i class="fas fa-chevron-right"></i>
          </button>

        </div>
      </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <h4 class="section-title">
      <a href="products.php" class="text-decoration-none">
        <span class="section-title mb-3">See All Products</span>
      </a>
    </h4>

    <!-- Mobile-optimized product grid -->
    <div class="row g-2 g-md-3 g-lg-4 mb-2 mx-auto">
      <?php if (empty($products)): ?>
        <div class="col-12 text-center py-4">
          <div class="alert alert-info mobile-p-2">No featured products available at the moment.</div>
        </div>
      <?php else: ?>
        <?php foreach ($products as $product): ?>
          <div class="col-6 col-sm-6 col-md-4 col-lg-3 product-card-mobile">
            <a href="product_details.php?id=<?= $product['id'] ?>" style="text-decoration: none;">
              <div class="card h-100 mobile-rounded">
                <div class="position-relative">
                  <img src="<?php echo isset($product['image_url']) && $product['image_url']
                              ? "" . htmlspecialchars($product['image_url'])
                              : "uploads/default-product.png"; ?>"
                    class="product-img"
                    alt="<?= htmlspecialchars($product['name']) ?>">

                  <!-- Stock badge -->
                  <?php if ($product['stock'] > 0): ?>
                    <span style="position: absolute; top: 10px; left: 10px; background: #28a745; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                      In Stock
                    </span>
                  <?php else: ?>
                    <span style="position: absolute; top: 10px; left: 10px; background: #dc3545; color: #fff; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; z-index: 2; box-shadow: 0 2px 6px rgba(0,0,0,0.08);">
                      Out of Stock
                    </span>
                  <?php endif; ?>
                </div>

                <div class="card-body">
                  <h5 class="card-title mobile-text-bold"><?= htmlspecialchars($product['name']) ?></h5>
                  <p class="card-text mobile-text-bold">₦<?= number_format($product['price'], 2) ?></p>

                  <!-- Rating stars -->
                  <div class="mobile-spacing-sm">
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
                          <span style="color: gold; font-size: 0.8rem;">&#9733;</span>
                        <?php else: ?>
                          <span style="color: #ccc; font-size: 0.8rem;">&#9733;</span>
                        <?php endif; ?>
                      <?php endfor; ?>
                      <small class="ms-1 mobile-text-small">(<?= $avg_rating ?>)</small>
                    </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <a href="product_details.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm flex-grow-1">
                      <i class="fas fa-eye me-1 d-none d-md-inline"></i>
                      <span class="d-md-none">View</span>
                      <span class="d-none d-md-inline">View Details</span>
                    </a>
                  </div>
                </div>
              </div>
            </a>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <!-- Mobile-optimized view all button -->
    <div class="text-center">
      <a href="products.php" class="btn btn-outline-primary mobile-p-2" style="width: 95%; max-width: 400px;">
        See All Products
      </a>
    </div>

    <div class="container my-5">
      <div class="p-4 rounded" style="background:#ffffff; color:#000; line-height:1.7;">
        <h2 class="fw-bold mb-3" style="font-size:1.4rem;">
          Shop Everything You Need on <?= APP_NAME ?> Nigeria — The Best Online Shopping Site in Nigeria
        </h2>

        <p>
          Welcome to <strong><?= APP_NAME ?>.ng</strong>, your trusted destination for online shopping in Nigeria.
          Whether you’re searching for
          <a href="#" class="fw-bold text-decoration-none">the best online shopping sites in Nigeria</a>
          or simply looking for great deals near you, <?= APP_NAME ?> makes buying and selling easier, faster, and more local.
        </p>

        <p>
          Find the latest
          <strong>home appliances products</strong>,
          <strong>cheap Android phones in Nigeria</strong>,
          tablets and their prices in Nigeria, and
          <strong>the best Android phones in Nigeria</strong> — all in one place.
          You can also explore a wide range of
          <strong>beauty and personal care products</strong>,
          <strong>skin care products in Nigeria</strong>, and
          <a href="#" class="fw-bold text-decoration-none">top-quality Nigerian beauty products</a>
          from trusted sellers.
        </p>

        <p>
          At <?= APP_NAME ?>, we connect real buyers and sellers across Nigeria.
          From
          <a href="#" class="fw-bold text-decoration-none">online beauty stores in Nigeria</a>
          to
          <strong>online shopping in Nigeria for clothes</strong>,
          our platform helps you compare prices, connect directly with sellers, and shop smartly.
        </p>

        <p>
          For parents and families, discover quality
          <strong>educational products for kids</strong>,
          <strong>baby toys in Nigeria</strong>, and
          <strong>educational toys in Nigeria</strong>
          that help your children learn and play better.
        </p>

        <p>
          With <?= APP_NAME ?>, shopping is convenient, safe, and truly local.
          Buy from verified sellers, explore trending items, or list your own products for free.
          Whatever you need — electronics, fashion, home essentials, or beauty —
          <strong><?= APP_NAME ?> is your go-to online shopping platform in Nigeria</strong>
          for quality, affordability, and trust.
        </p>

        <p class="fw-bold mb-0">
          Start exploring today and experience why <?= APP_NAME ?> is fast becoming Nigeria’s best online shopping destination for everything you need!
        </p>

        <h2 class="fw-bold mb-3 mt-4" style="font-size:1.4rem;">
          About Nujora - Your Trusted Online Shopping Partner
        </h2>

        <p>
          Nujora stands as a leading platform for the
          <strong>Best Online Shopping in Nigeria</strong>,offering a secure and diverse marketplace for millions of products. We are committed to elevating the standard of
          <strong>Online Shopping Nigeria</strong>, by providing fast delivery, easy returns, and exceptional customer care. As one of the premier
          <strong>Best Online Shopping Sites Nigeria</strong> trusts, we connect buyers and sellers across the country, making us a top destination among <strong>Nigeria Online Shopping Platforms.</strong>
        </p>
      </div>
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

  <!-- JS for Featured Goods Scroll -->
  <script>
    const scrollContainer = document.getElementById('featuredGoods');
    document.querySelector('.scroll-btn.left').addEventListener('click', () => {
      scrollContainer.scrollBy({
        left: -220,
        behavior: 'smooth'
      });
    });
    document.querySelector('.scroll-btn.right').addEventListener('click', () => {
      scrollContainer.scrollBy({
        left: 220,
        behavior: 'smooth'
      });
    });
  </script>

  <!-- JS for Live Search Suggestions -->
  <script>
    document.getElementById("searchInput").addEventListener("keyup", function() {
      let query = this.value.trim();

      if (query.length < 1) {
        document.getElementById("suggestionsBox").style.display = "none";
        return;
      }

      fetch("search_suggestions.php?search=" + encodeURIComponent(query))
        .then(response => response.json())
        .then(data => {
          let box = document.getElementById("suggestionsBox");
          box.innerHTML = "";

          if (data.length === 0) {
            box.style.display = "none";
            return;
          }

          data.forEach(item => {
            let li = document.createElement("li");
            li.className = "list-group-item list-group-item-action";
            li.textContent = item;

            // Click to select suggestion
            li.onclick = function() {
              document.getElementById("searchInput").value = this.textContent;
              box.style.display = "none";
            };

            box.appendChild(li);
          });

          box.style.display = "block";
        });
    });
  </script>

</body>

</html>