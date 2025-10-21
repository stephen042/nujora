<?php
require '../app/config.php';

// Get product details
$product_id = $_GET['id'] ?? null;



// if (!$product_id) {
//     header("Location: home.php");
//     exit();
// }

try {
  // Fetch product details
  $stmt = $pdo->prepare("
        SELECT p.*, u.shop_name, u.shop_logo, u.id as seller_id
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.id = ? AND u.approval_status = 'approved'
    ");
  $stmt->execute([$product_id]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$product) {
    header("Location: home.php");
    exit();
  }

  // Fetch seller rating
  $stmt = $pdo->prepare("
        SELECT 
            ROUND(AVG(stars), 2) AS avg_rating,
            COUNT(id) AS rating_count
        FROM ratings
        WHERE seller_id = ?
    ");
  $stmt->execute([$product['seller_id']]);
  $seller_rating = $stmt->fetch(PDO::FETCH_ASSOC);

  // Fetch related products
  $stmt = $pdo->prepare("
        SELECT p.*, u.shop_name 
        FROM products p
        JOIN users u ON p.seller_id = u.id
        WHERE p.category = ? AND p.id != ? AND u.approval_status = 'approved'
        ORDER BY RAND()
        LIMIT 8
    ");
  $stmt->execute([$product['category'], $product_id]);
  $related_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error: " . $e->getMessage());
}

// Badge logic
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

$seller_badge = getSellerBadge($seller_rating['avg_rating'] ?? null);
$badge_class = strtolower(str_replace(' ', '-', $seller_badge['label']));
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($product['name']) ?> | <?= APP_NAME ?></title>
  <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css">
  <style>
    :root {
      --primary-color: #6a11cb;
      --secondary-color: #2575fc;
      --accent-color: #957156;
      --light-bg: #f8f9fa;
      --dark-text: #2B2A26;
      --discount-color: #f44336;
      --stock-in: #4CAF50;
      --stock-out: #9E9E9E;
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

    .product-gallery {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
    }

    .main-image {
      width: 100%;
      height: 400px;
      object-fit: contain;
      background: #fff;
      border-bottom: 1px solid #eee;
    }

    .thumbnail-container {
      display: flex;
      padding: 10px;
      overflow-x: auto;
    }

    .thumbnail {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border: 1px solid #ddd;
      border-radius: 6px;
      margin-right: 10px;
      cursor: pointer;
      transition: all 0.3s;
    }

    .thumbnail:hover {
      border-color: var(--primary-color);
    }

    .product-info-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 20px;
    }

    .product-title {
      font-size: 1.8rem;
      font-weight: 600;
      margin-bottom: 10px;
    }

    .product-seller {
      display: flex;
      align-items: center;
      margin-bottom: 15px;
    }

    .seller-logo {
      width: 40px;
      height: 40px;
      object-fit: cover;
      border-radius: 50%;
      margin-right: 10px;
    }

    .seller-name {
      font-weight: 500;
      color: var(--primary-color);
    }

    .rating-stars {
      color: #ffc107;
      font-size: 0.9rem;
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

    .price-section {
      margin: 20px 0;
    }

    .current-price {
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    .original-price {
      font-size: 1.2rem;
      text-decoration: line-through;
      color: var(--stock-out);
      margin-left: 10px;
    }

    .discount-badge {
      background-color: var(--discount-color);
      color: white;
      padding: 3px 8px;
      border-radius: 4px;
      font-size: 0.9rem;
      font-weight: 600;
      margin-left: 10px;
    }

    .stock-status {
      font-weight: 500;
      margin: 15px 0;
    }

    .in-stock {
      color: var(--stock-in);
    }

    .out-of-stock {
      color: var(--stock-out);
    }

    .quantity-selector {
      display: flex;
      align-items: center;
      margin: 20px 0;
    }

    .quantity-btn {
      width: 40px;
      height: 40px;
      border: 1px solid #ddd;
      background: #f8f9fa;
      font-size: 1.2rem;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
    }

    .quantity-input {
      width: 60px;
      height: 40px;
      text-align: center;
      border-top: 1px solid #ddd;
      border-bottom: 1px solid #ddd;
      border-left: none;
      border-right: none;
    }

    .action-buttons {
      display: flex;
      gap: 10px;
      margin-top: 20px;
    }

    .btn-cart {
      background-color: #FF6B6B;
      color: white;
      border: none;
      flex: 1;
      padding: 12px;
      font-weight: 600;
    }

    .btn-buy {
      background-color: var(--primary-color);
      color: white;
      border: none;
      flex: 1;
      padding: 12px;
      font-weight: 600;
    }

    .product-details {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      padding: 20px;
      margin-top: 30px;
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

    .related-products {
      margin-top: 40px;
    }

    .related-product-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      overflow: hidden;
      transition: transform 0.3s ease;
      margin-bottom: 20px;
    }

    .related-product-card:hover {
      transform: translateY(-5px);
    }

    .related-product-img {
      width: 100%;
      height: 180px;
      object-fit: contain;
      background: #f8f9fa;
      border-bottom: 1px solid #eee;
    }

    .related-product-info {
      padding: 15px;
    }

    .related-product-title {
      font-weight: 500;
      font-size: 0.95rem;
      margin-bottom: 5px;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }

    .related-product-price {
      font-weight: 600;
      color: var(--primary-color);
    }

    .related-product-original-price {
      font-size: 0.8rem;
      text-decoration: line-through;
      color: var(--stock-out);
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

    /* Responsive adjustments */
    @media (max-width: 768px) {
      .main-image {
        height: 300px;
      }

      .product-title {
        font-size: 1.5rem;
      }

      .current-price {
        font-size: 1.5rem;
      }

      .original-price {
        font-size: 1rem;
      }

      .action-buttons {
        flex-direction: column;
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
          <a href="../auth/login.php" class="btn btn-outline-primary">
            <i class="fas fa-sign-in-alt me-1"></i> Login
          </a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <!-- Product Section -->
  <div class="container mt-4">
    <div class="row">
      <!-- Product Gallery -->
      <div class="col-lg-6">
        <div class="product-gallery">
          <img src="../<?= htmlspecialchars($product['image_url']) ?? "../uploads/default-product.png" ?>"
            class="main-image"
            alt="<?= htmlspecialchars($product['name']) ?>"
            id="mainImage">
          <div class="thumbnail-container">
            <?php
            // Assuming product has multiple images in a JSON array
            $product_images = json_decode($product['images'] ?? '[]', true);
            if (empty($product_images)) {
              $product_images = [$product['image_url']];
            }
            foreach ($product_images as $image): ?>
              <img src="../<?= htmlspecialchars($image) ?? "../uploads/default-product.png" ?>"
                class="thumbnail"
                alt="Thumbnail"
                onclick="document.getElementById('mainImage').src = this.src"
                onerror="this.src='https://via.placeholder.com/150'">
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <!-- Product Info -->
      <div class="col-lg-6">
        <div class="product-info-card">
          <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

          <div class="product-seller">
            <img src="<?= htmlspecialchars($product['shop_logo'] ?? "../uploads/default-product.png") ?>"
              class="seller-logo"
              alt="<?= htmlspecialchars($product['shop_name']) ?>">
            <span class="seller-name me-2"><?= htmlspecialchars($product['shop_name']) ?></span>

            <span class="badge badge-tier badge-<?= $badge_class ?> me-2">
              <i class="fas <?= $seller_badge['icon'] ?> me-1"></i> <?= $seller_badge['label'] ?>
            </span>

            <?php if ($seller_rating['avg_rating']): ?>
              <div class="rating-stars">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fas <?= $i <= round($seller_rating['avg_rating']) ? 'fa-star' : 'fa-star-half-alt' ?>"></i>
                <?php endfor; ?>
                <span class="ms-1"><?= number_format($seller_rating['avg_rating'], 1) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="price-section">
            <span class="current-price">₦<?= number_format($product['price'], 2) ?></span>

            <?php if (!empty($product['original_price']) && $product['original_price'] > $product['price']): ?>
              <span class="original-price">₦<?= number_format($product['original_price'], 2) ?></span>
              <?php
              $discount = round((($product['original_price'] - $product['price']) / $product['original_price']) * 100);
              ?>
              <span class="discount-badge"><?= $discount ?>% OFF</span>
            <?php endif; ?>
          </div>

          <div class="stock-status <?= $product['stock'] > 0 ? 'in stock' : 'out of stock' ?>">
            <i class="fas <?= $product['stock'] > 0 ? 'fa-check-circle' : 'fa-times-circle' ?> me-1"></i>
            <?= $product['stock'] > 0 ?
              "In Stock ({$product['stock']} available)" :
              "Out of Stock" ?>
          </div>

          <div class="product-description">
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
          </div>

          <?php if ($product['stock'] > 0): ?>
            <div class="quantity-selector">
              <label class="me-3">Quantity:</label>
              <button class="quantity-btn minus">-</button>
              <input type="number" class="quantity-input" value="1" min="1" max="<?= $product['stock'] ?>">
              <button class="quantity-btn plus">+</button>
            </div>

            <div class="action-buttons">
              <button class="btn btn-cart">
                <i class="fas fa-cart-plus me-2"></i> Add to Cart
              </button>
              <button class="btn btn-buy">
                <i class="fas fa-bolt me-2"></i> Buy Now
              </button>
            </div>
          <?php else: ?>
            <div class="alert alert-warning mt-3">
              This product is currently out of stock. Check back later!
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Product Details -->
    <div class="product-details">
      <h3 class="section-title">Product Details</h3>
      <div class="row">
        <div class="col-md-6">
          <ul class="list-unstyled">
            <li class="mb-2"><strong>Category:</strong> <?= htmlspecialchars($product['category']) ?></li>
            <li class="mb-2"><strong>Brand:</strong> <?= htmlspecialchars($product['brand'] ?? 'Generic') ?></li>
            <li class="mb-2"><strong>Weight:</strong> <?= htmlspecialchars($product['weight'] ?? 'N/A') ?></li>
          </ul>
        </div>
        <div class="col-md-6">
          <ul class="list-unstyled">
            <li class="mb-2"><strong>SKU:</strong> <?= htmlspecialchars($product['sku'] ?? 'N/A') ?></li>
            <li class="mb-2"><strong>Shipping:</strong> <?= htmlspecialchars($product['shipping_info'] ?? 'Standard shipping') ?></li>
            <li class="mb-2"><strong>Return Policy:</strong> <?= htmlspecialchars($product['return_policy'] ?? '7 days return policy') ?></li>
          </ul>
        </div>
      </div>

      <?php if (!empty($product['specifications'])): ?>
        <h4 class="mt-4 mb-3">Specifications</h4>
        <div class="table-responsive">
          <table class="table table-bordered">
            <tbody>
              <?php
              $specs = json_decode($product['specifications'], true);
              foreach ($specs as $key => $value): ?>
                <tr>
                  <th width="30%"><?= htmlspecialchars($key) ?></th>
                  <td><?= htmlspecialchars($value) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <!-- Related Products -->
    <div class="related-products">
      <h3 class="section-title">You May Also Like</h3>
      <div class="row">
        <?php if (empty($related_products)): ?>
          <div class="col-12 text-center py-4">
            <div class="alert alert-info">No related products found.</div>
          </div>
        <?php else: ?>
          <?php foreach ($related_products as $related): ?>
            <div class="col-6 col-md-4 col-lg-3">
              <a href="product_details.php?id=<?= $related['id'] ?>" class="text-decoration-none">
                <div class="related-product-card">
                  <img src="../<?= htmlspecialchars($related['image_url']) ?? "../uploads/default-product.png" ?>"
                    class="related-product-img"
                    alt="<?= htmlspecialchars($related['name']) ?>">
                  <div class="related-product-info">
                    <h5 class="related-product-title"><?= htmlspecialchars($related['name']) ?></h5>
                    <p class="related-product-price mb-1">₦<?= number_format($related['price'], 2) ?></p>
                    <?php if ($related['original_price'] > $related['price']): ?>
                      <p class="related-product-original-price">₦<?= number_format($related['original_price'], 2) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>


  <!-- Footer -->
  <?php include 'includes/footer.php'; ?>

  <!-- Bottom Navigation -->
  <?php include 'includes/bottomNav.php'; ?>

  <!-- Script -->
  <?php include 'includes/script.php'; ?>
</body>

</html>
<?php
error_log("Product ID: " . $product_id);
error_log("SQL Query: SELECT id, name, price, stock, quantity, description FROM products WHERE id = " . $product_id);
?>