<?php
// Include configuration (once)
require_once __DIR__ . '/app/config.php';

// Fetch all categories with product counts
try {
  $stmt = $pdo->prepare("
        SELECT c.*, COUNT(p.id) AS product_count 
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id
        GROUP BY c.id
        ORDER BY c.name
    ");
  $stmt->execute();
  $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  // Fallback if categories table doesn't exist
  $categories = [
    ['id' => 1, 'name' => 'Electronics', 'image_url' => 'electronics.jpg', 'description' => 'Phones, laptops, gadgets', 'product_count' => 42],
    ['id' => 2, 'name' => 'Fashion', 'image_url' => 'fashion.jpg', 'description' => 'Clothing, shoes, accessories', 'product_count' => 36],
    ['id' => 3, 'name' => 'Home & Garden', 'image_url' => 'home.jpg', 'description' => 'Furniture, decor, appliances', 'product_count' => 28],
    ['id' => 4, 'name' => 'Groceries', 'image_url' => 'groceries.jpg', 'description' => 'Food items, beverages', 'product_count' => 53]
  ];
  error_log("Error fetching categories: " . $e->getMessage());
}

// Badge logic (reused from your homepage)
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
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categories | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="uploads/default-product.png">
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    /* All your existing styles from homepage */
    :root {
      --primary-color: #6a11cb;
      --secondary-color: #2575fc;
      --accent-color: #957156;
      --light-bg: #f8f9fa;
      --dark-text: #2B2A26;
    }

    body {
      font-family: 'Roboto', 'Segoe UI', sans-serif;
      background-color: var(--light-bg);
      color: var(--dark-text);
      /* padding-bottom: 60px; */
    }

    /* ... (include all your existing styles from homepage) ... */

    /* Categories page styles */
    .category-page-img {
      height: 200px;
      object-fit: cover;
      width: 100%;
      border-radius: 12px 12px 0 0;
    }

    .category-count-badge {
      position: absolute;
      top: 10px;
      right: 10px;
      background: rgba(255, 255, 255, 0.9);
      padding: 3px 8px;
      border-radius: 10px;
      font-weight: 600;
      font-size: 0.8rem;
    }

    .category-description {
      color: #6c757d;
      font-size: 0.9rem;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .featured-category {
      height: 300px;
      position: relative;
      border-radius: 12px;
      overflow: hidden;
    }

    .featured-category-content {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
      padding: 20px;
      color: white;
    }

    @media (max-width: 768px) {
      .category-page-img {
        height: 150px;
      }
    }
  </style>
</head>

<body>

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
      <a class="navbar-brand" href="home.php">
        <i class="fas fa-store me-2"></i><?= APP_NAME ?>
      </a>
      <div class="d-flex align-items-center">
        <a href="search.php" class="btn btn-outline-secondary me-2">
          <i class="fas fa-search"></i>
        </a>
        <a href="cart.php" class="btn btn-outline-secondary position-relative me-2">
          <i class="fas fa-shopping-cart"></i>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
            0
          </span>
        </a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="dropdown">
            <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown">
              <i class="fas fa-user-circle"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="orders.php"><i class="fas fa-box me-2"></i>My Orders</a></li>
              <li><a class="dropdown-item" href="buyer-dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>My Dashboard</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="auth/login.php" class="btn btn-outline-primary">
            <i class="fas fa-sign-in-alt me-1"></i> Login
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Hero Section -->

  <!-- Hero Section -->
  <section class="hero-section">
    <div class="container text-center">
      <h1 class="display-5 fw-bold mb-3">Browse Categories</h1>
      <p class="lead mb-4">Discover products organized by category</p>

      <form class="d-flex mx-auto" style="max-width: 600px;" action="search.php" method="GET">
        <input class="form-control form-control-lg me-2" type="search" name="query" placeholder="Search products or shops..." aria-label="Search">
        <button class="btn btn-light btn-lg" type="submit">
          <i class="fas fa-search"></i>
        </button>
      </form>
    </div>
  </section>

  <!-- Main Content -->
  <div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="my-4">
      <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="home.php">Home</a></li>
        <li class="breadcrumb-item active" aria-current="page">Categories</li>
      </ol>
    </nav>

    <!-- All Categories -->
    <h4 class="section-title">All Categories</h4>
    <div class="row g-4 mb-5">
      <?php foreach ($categories as $category): ?>
        <div class="col-6 col-md-4 col-lg-3">
          <div class="card h-100">
            <div class="position-relative">
              <img src="uploads<?= htmlspecialchars($category['image_url'] ?? 'uploads/default-product.png' . urlencode($category['name'])) ?>"
                class="category-page-img"
                alt="<?= htmlspecialchars($category['name']) ?>">
              <span class="category-count-badge"><?= $category['product_count'] ?> items</span>
            </div>
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($category['name']) ?></h5>
              <p class="category-description"><?= htmlspecialchars($category['description'] ?? 'Various products in this category') ?></p>
              <a href="category-products.php?id=<?= $category['id'] ?>" class="btn btn-outline-primary btn-sm w-100">
                View Products
              </a>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Categories Grid
       
    <! Featured Categories -->
    <h4 class="section-title">Featured Collections</h4>
    <div class="row g-4 mb-5">
      <div class="col-md-6">
        <div class="featured-category">
          <img src="uploads/default-product.png"
            class="w-100 h-100 object-fit-cover"
            alt="Summer Sale">
          <div class="featured-category-content">
            <h3>Summer Sale</h3>
            <p class="mb-3">Up to 50% off on selected items</p>
            <a href="summer-sale.php" class="btn btn-primary">Shop Now</a>
          </div>
        </div>
      </div>
      <div class="col-md-6">
        <div class="featured-category">
          <img src="uploads/default-product.png"
            class="w-100 h-100 object-fit-cover"
            alt="New Arrivals">
          <div class="featured-category-content">
            <h3>New Arrivals</h3>
            <p class="mb-3">Discover the latest products</p>
            <a href="new-arrivals.php" class="btn btn-outline-light">Explore</a>
          </div>
        </div>
      </div>
    </div>
  </div>


  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Script -->
  <?php include 'includes/script.php'; ?>
</body>

</html>