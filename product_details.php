<?php
require 'app/config.php';

// ✅ GET PRODUCT SLUG (as string)
$slug = $_GET['slug'] ?? '';

if (!$slug) {
  header("Location: 404.php");
  exit;
}

try {
  // ✅ Fetch product by slug
  $stmt = $pdo->prepare("
      SELECT p.*, u.name AS seller_name, u.shop_name, u.role AS seller_role 
      FROM products p 
      JOIN users u ON p.seller_id = u.id 
      WHERE p.slug = ?
  ");
  $stmt->execute([$slug]);
  $product = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$product) {
    header("Location: 404.php");
    exit;
  }

  // ✅ Fetch product variants using product ID
  $stmt = $pdo->prepare("
    SELECT v.id AS variant_id,
           o.option_name,
           o.option_value
    FROM product_variants v
    JOIN product_variant_options o ON o.variant_id = v.id
    WHERE v.product_id = ?
");
  $stmt->execute([$product['id']]);
  $raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Convert to a structured format
  $variants = [];
  $attributes = [];

  foreach ($raw as $row) {
    $variants[$row['variant_id']][$row['option_name']] = $row['option_value'];
    $attributes[$row['option_name']][] = $row['option_value'];
  }

  // Remove duplicates
  foreach ($attributes as $key => $values) {
    $attributes[$key] = array_unique($values);
  }

  $has_variants = count($variants) > 0;




  // ✅ Fetch related products using product ID
  $related_stmt = $pdo->prepare("
      SELECT * FROM products 
      WHERE category = ? AND id != ?
      LIMIT 4
  ");
  $related_stmt->execute([$product['category'], $product['id']]);
  $related_products = $related_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  die("Error: " . $e->getMessage());
}

//
// ---------------- REVIEWS SECTION ----------------
//

// ✅ FETCH FIRST 5 REVIEWS using product ID
try {
  $review_stmt = $pdo->prepare("
      SELECT r.*, u.name AS user_name
      FROM product_reviews r
      JOIN users u ON r.buyer_id = u.id
      WHERE r.product_id = ?
      ORDER BY r.created_at DESC
      LIMIT 5
  ");
  $review_stmt->execute([$product['id']]);
  $reviews = $review_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
  error_log('Review fetch error: ' . $e->getMessage());
  $reviews = [];
}

// ✅ AVERAGE RATING + TOTAL COUNT using product ID
try {
  $rating_stmt = $pdo->prepare("
      SELECT 
          ROUND(AVG(rating), 1) AS avg_rating,
          COUNT(*) AS review_count
      FROM product_reviews
      WHERE product_id = ?
  ");
  $rating_stmt->execute([$product['id']]);
  $rating_data = $rating_stmt->fetch(PDO::FETCH_ASSOC);

  $avg_rating = $rating_data['avg_rating'] ?? 0;
  $review_count = $rating_data['review_count'] ?? 0;
} catch (PDOException $e) {
  error_log('Rating summary error: ' . $e->getMessage());
  $avg_rating = 0;
  $review_count = 0;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title><?= htmlspecialchars($product['name']) ?> | <?= APP_NAME ?></title>

  <!-- Canonical URL -->
  <link rel="canonical" href="https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>">

  <!-- Favicon -->
  <link rel="icon" type="image/png" href="<?= htmlspecialchars($product['image_url'] ?? 'uploads/default-product.png') ?>">

  <!-- SEO -->
  <meta name="robots" content="index, follow">
  <meta name="description" content="<?= htmlspecialchars(substr($product['description'] ?? $product['name'], 0, 160)) ?>">

  <!-- ✅ OPEN GRAPH (for Facebook, WhatsApp, Instagram) -->
  <meta property="og:type" content="product" />
  <meta property="og:title" content="<?= htmlspecialchars($product['name']) ?> | <?= APP_NAME ?>" />
  <meta property="og:description" content="<?= htmlspecialchars(substr($product['description'] ?? $product['name'], 0, 160)) ?>" />
  <meta property="og:url" content="https://<?= $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] ?>" />
  <meta property="og:image" content="https://<?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($product['image_url'] ?? 'uploads/default-product.png') ?>" />
  <meta property="og:image:alt" content="<?= htmlspecialchars($product['name']) ?>" />

  <!-- ✅ TWITTER CARD -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="<?= htmlspecialchars($product['name']) ?> | <?= APP_NAME ?>">
  <meta name="twitter:description" content="<?= htmlspecialchars(substr($product['description'] ?? $product['name'], 0, 160)) ?>">
  <meta name="twitter:image" content="https://<?= $_SERVER['HTTP_HOST'] ?>/<?= htmlspecialchars($product['image_url'] ?? 'uploads/default-product.png') ?>">

  <!-- Google Verification -->
  <meta name="google-site-verification" content="ZlNr6S6JXMI9jO0JTdQHvBJc0V1aYZfiMDkNhziPCP4" />

  <!-- Styles -->
  <link href="assets/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
      background-color: #f8f9fa;
      color: #333;
    }

    h1,
    h2,
    h3,
    h4 {
      font-family: 'Poppins', sans-serif;
    }

    .product-header {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }

    .product-gallery {
      position: relative;
    }

    .main-image {
      height: 400px;
      object-fit: contain;
      border-radius: 8px;
      background: #f5f5f5;
    }

    .thumbnail-container {
      display: flex;
      gap: 10px;
      margin-top: 15px;
    }

    .thumbnail {
      width: 70px;
      height: 70px;
      object-fit: cover;
      border-radius: 6px;
      cursor: pointer;
      border: 2px solid transparent;
      transition: all 0.3s ease;
    }

    .thumbnail:hover,
    .thumbnail.active {
      border-color: var(--primary-color);
    }

    .product-title {
      font-size: 1.8rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
    }

    .price-container {
      background: #f8f9fa;
      padding: 1rem;
      border-radius: 8px;
    }

    .current-price {
      font-size: 2rem;
      font-weight: 700;
      color: var(--primary-color);
    }

    .original-price {
      text-decoration: line-through;
      color: #6c757d;
    }

    .discount-badge {
      background: var(--primary-color);
      color: white;
      font-weight: bold;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-size: 0.9rem;
    }

    .rating-badge {
      background: var(--accent-color);
      color: var(--dark-color);
      padding: 0.3rem 0.6rem;
      border-radius: 20px;
      font-weight: 600;
    }

    .btn-add-to-cart {
      background: var(--primary-color);
      border: none;
      padding: 12px 0;
      /* font-weight: 600; */
      transition: all 0.3s ease;
    }

    .btn-add-to-cart:hover {
      /* background: #e05555; */
      transform: translateY(-2px);
    }

    .btn-buy-now {
      background: var(--secondary-color);
      border: none;
      padding: 12px 0;
      /* font-weight: 600; */
      transition: all 0.3s ease;
    }

    .btn-buy-now:hover {
      background: #3dbeb6;
      transform: translateY(-2px);
    }

    .seller-card {
      border-radius: 8px;
      border: 1px solid #e0e0e0;
      transition: all 0.3s ease;
    }

    .seller-card:hover {
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }

    .section-title {
      font-weight: 700;
      position: relative;
      padding-bottom: 10px;
      margin-bottom: 20px;
    }

    .section-title:after {
      content: '';
      position: absolute;
      left: 0;
      bottom: 0;
      width: 50px;
      height: 3px;
      background: var(--primary-color);
    }

    .review-card {
      border-radius: 8px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .star-filled {
      color: var(--accent-color);
    }

    .star-empty {
      color: #ddd;
    }

    .nav-bottom {
      position: fixed;
      bottom: 0;
      left: 0;
      width: 100%;
      background: white;
      box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
      z-index: 1000;
    }

    .nav-bottom .nav-link {
      color: #666;
      font-size: 0.8rem;
      padding: 0.75rem;
    }

    .nav-bottom .nav-link.active {
      /* color: var(--primary-color); */
      font-weight: 600;
    }

    .nav-bottom .nav-link i {
      display: block;
      font-size: 1.2rem;
      margin-bottom: 5px;
    }

    .product-badge {
      position: absolute;
      top: 10px;
      left: 10px;
      background: var(--primary-color);
      color: white;
      padding: 0.2rem 0.5rem;
      border-radius: 4px;
      font-size: 0.8rem;
      font-weight: 600;
    }

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
    }
  </style>
</head>

<body>
  <?php include 'includes/nav.php'; ?>
  <div class="container py-4 mb-5">
    <!-- Product Gallery and Basic Info -->
    <div class="product-header p-3 mb-4">
      <div class="row">
        <div class="col-lg-6">
          <div class="product-gallery">
            <?php if ($product['stock'] < 10 && $product['stock'] > 0): ?>
              <span class="product-badge">Only <?= $product['stock'] ?> left!</span>
            <?php elseif ($product['stock'] == 0): ?>
              <span class="product-badge bg-danger">Out of Stock</span>
            <?php endif; ?>

            <img id="mainImage" src="<?= htmlspecialchars($product['image_url']) ?? "uploads/default-product.png" ?>" class="main-image w-100" alt="<?= htmlspecialchars($product['name']) ?>">

            <div class="thumbnail-container">
              <?php
              // Decode JSON from DB
              $images = json_decode($product['photos'], true);

              if (!empty($images)) {
                foreach ($images as $index => $image) {
                  $activeClass = $index === 0 ? 'active' : ''; // Make first image active
              ?>
                  <img src="<?= htmlspecialchars($image) ?>"
                    class="thumbnail <?= $activeClass ?>"
                    onclick="changeImage(this)">
                <?php
                }
              } else {
                // Fallback if no images
                ?>
                <img src="<?= htmlspecialchars($product['image_url']) ?? "uploads/default-product.png" ?>"
                  class="thumbnail active"
                  onclick="changeImage(this)">
              <?php
              }
              ?>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <h1 class="product-title"><?= htmlspecialchars($product['name']) ?></h1>

          <div class="d-flex align-items-center mb-3">
            <div class="rating-badge me-2">
              <i class="fas fa-star"></i> <?= $avg_rating ?> (<?= $review_count ?> reviews)
            </div>
            <span class="text-muted"><?= $product['sold'] ?? 0 ?> sold</span>
          </div>

          <div class="price-container mb-4">
            <div class="d-flex align-items-center">
              <span class="current-price me-3">₦<?= number_format($product['price'], 2) ?></span>
              <?php if (isset($product['original_price']) && $product['original_price'] > $product['price']): ?>
                <span class="original-price me-2">₦<?= number_format($product['original_price'], 2) ?></span>
                <span class="discount-badge">
                  <?= round(100 - ($product['price'] / $product['original_price'] * 100)) ?>% OFF
                </span>
              <?php endif; ?>
            </div>
            <?php if (isset($product['free_delivery']) && $product['free_delivery'] == 1): ?>
              <small class="text-muted">+ ₦1,200 shipping fee</small>
            <?php else: ?>
              <small class="text-success">Free Shipping</small>
            <?php endif; ?>
          </div>

          <div class="mb-4">
            <h5 class="fw-bold mb-3">Key Features</h5>
            <ul class="list-unstyled">
              <?php
              $features = explode("\n", $product['description']);
              foreach (array_slice($features, 0, 5) as $feature):
                if (!empty(trim($feature))): ?>
                  <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i><?= htmlspecialchars(trim($feature)) ?></li>
              <?php endif;
              endforeach; ?>
            </ul>
          </div>

          <?php if ($has_variants): ?>

            <?php foreach ($attributes as $attrName => $values): ?>
              <div class="mb-3">
                <label class="form-label fw-bold">Select <?= ucfirst($attrName) ?></label>
                <select class="form-select variant-select" data-attr="<?= $attrName ?>">
                  <option value="">Choose <?= $attrName ?></option>

                  <?php foreach ($values as $v): ?>
                    <option value="<?= $v ?>"><?= ucfirst($v) ?></option>
                  <?php endforeach; ?>

                </select>
              </div>
            <?php endforeach; ?>

            <!-- Hidden variant_id -->
            <input type="hidden" id="selected-variant-id">

          <?php endif; ?>




          <div class="d-grid gap-2">
            <!-- Add to Cart Button -->
            <button class="btn btn-add-to-cart btn-lg p-1 text-white add-to-cart w-100" data-id="<?= $product['id'] ?>" <?= $product['stock'] == 0 ? 'disabled' : '' ?>>
              <i class="fas fa-shopping-cart float-start m-2"></i>
              Add to Cart
            </button>
          </div>
          <!-- Notify When Available Button -->
          <?php if ($product['stock'] == 0): ?>
            <form method="POST" action="notify.php">
              <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
              <button class="btn btn-outline-secondary btn-lg">
                <i class="fas fa-bell me-2"></i> Notify When Available
              </button>
            </form>
          <?php endif; ?>
        </div>

        <div class="mt-3 d-flex justify-content-between">
          <div class="form-check">
            <!-- <input class="form-check-input" type="checkbox" id="addWarranty">
            <label class="form-check-label" for="addWarranty">
              Add 1-Year Warranty (+₦1,500)
            </label> -->
          </div>
          <a href="#" class="text-decoration-none"><i class="far fa-heart"></i> Add to Wishlist</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Seller Information -->
  <div class="card mb-4 p-3 seller-card">
    <div class="row align-items-center">
      <div class="col-md-2 text-center">
        <img src="uploads/default-product.png" class="rounded-circle" width="80" height="80">
      </div>
      <div class="col-md-6">
        <h5 class="mb-1"><?= htmlspecialchars($product['shop_name']) ?></h5>
        <div class="mb-2">
          <span class="rating-badge small me-2">
            <i class="fas fa-star"></i> 4.8
          </span>
          <span class="text-muted">98% Positive Feedback</span>
        </div>
        <p class="mb-1"><i class="fas fa-map-marker-alt text-danger me-2"></i> Lagos, Nigeria</p>
        <p class="mb-0"><i class="fas fa-user-tag me-2"></i> <?= ucfirst($product['seller_role']) ?> Seller</p>
      </div>
      <div class="col-md-4 text-end">
        <!-- <a href="chat.php?with=<?= $product['seller_id'] ?>" class="btn btn-outline-primary me-2">
            <i class="fas fa-comment-dots me-1"></i> Chat Now
          </a> -->
        <!-- <a href="seller_products.php?id=<?= $product['seller_id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-store me-1"></i> View Shop
          </a> -->
      </div>
    </div>
  </div>

  <!-- Product Details Tabs -->
  <div class="card mb-4">
    <div class="card-header bg-white">
      <ul class="nav nav-tabs card-header-tabs" id="productTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="details-tab" data-bs-toggle="tab" data-bs-target="#details" type="button" role="tab">Product Details</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="specs-tab" data-bs-toggle="tab" data-bs-target="#specs" type="button" role="tab">Specifications</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="reviews-tab" data-bs-toggle="tab" data-bs-target="#reviews" type="button" role="tab">Reviews (<?= $review_count ?>)</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="shipping-tab" data-bs-toggle="tab" data-bs-target="#shipping" type="button" role="tab">Shipping & Returns</button>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <div class="tab-content" id="productTabsContent">
        <div class="tab-pane fade show active" id="details" role="tabpanel">
          <h5 class="section-title">About This Product</h5>
          <p><?= htmlspecialchars($product['description']) ?></p>

          <div class="row mt-4">
            <div class="col-md-6">
              <h5 class="section-title">Key Features</h5>
              <ul>
                <?php foreach (array_slice($features, 0, 6) as $feature):
                  if (!empty(trim($feature))): ?>
                    <li class="mb-2"><?= htmlspecialchars(trim($feature)) ?></li>
                <?php endif;
                endforeach; ?>
              </ul>
            </div>
            <div class="col-md-6">
              <h5 class="section-title">Package Includes</h5>
              <ul>
                <li>1 x <?= htmlspecialchars($product['name']) ?></li>
                <li>User Manual</li>
                <li>Warranty Card (1 Year)</li>
                <li>Original Accessories</li>
              </ul>
            </div>
          </div>
        </div>

        <div class="tab-pane fade" id="specs" role="tabpanel">
          <h5 class="section-title">Technical Specifications</h5>
          <div class="table-responsive">
            <table class="table table-bordered">
              <tbody>
                <tr>
                  <th width="30%">Brand</th>
                  <td><?= htmlspecialchars($product['brand'] ?? 'Generic') ?></td>
                </tr>
                <tr>
                  <th>Model</th>
                  <td><?= htmlspecialchars($product['model'] ?? 'N/A') ?></td>
                </tr>
                <tr>
                  <th>Category</th>
                  <td><?= htmlspecialchars($product['category']) ?></td>
                </tr>
                <tr>
                  <th>Weight</th>
                  <td><?= htmlspecialchars($product['weight'] ?? '0.5') ?> kg</td>
                </tr>
                <tr>
                  <th>Dimensions</th>
                  <td>20 x 15 x 10 cm</td>
                </tr>
                <tr>
                  <th>Material</th>
                  <td>Plastic/Metal</td>
                </tr>
                <tr>
                  <th>Warranty</th>
                  <td>1 Year Manufacturer Warranty</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <div class="tab-pane fade" id="reviews" role="tabpanel">
          <h5 class="section-title">Customer Reviews</h5>

          <div class="row mb-4">
            <div class="col-md-4 text-center">
              <div class="display-4 fw-bold"><?= $avg_rating ?></div>
              <div class="mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                  <i class="fas fa-star <?= $i <= round($avg_rating) ? 'star-filled' : 'star-empty' ?>"></i>
                <?php endfor; ?>
              </div>
              <small class="text-muted">Based on <?= $review_count ?> reviews</small>
            </div>
          </div>

          <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
              <div class="review-card p-3 mb-3 border rounded">

                <div class="d-flex justify-content-between mb-2">
                  <div>
                    <strong><?= htmlspecialchars($review['user_name']) ?></strong>

                    <div class="text-warning">
                      <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?= $i <= $review['rating'] ? 'star-filled' : 'star-empty' ?>"></i>
                      <?php endfor; ?>
                    </div>
                  </div>

                  <small class="text-muted">
                    <?= date('M d, Y', strtotime($review['created_at'])) ?>
                  </small>
                </div>

                <p class="mb-2"><?= nl2br(htmlspecialchars($review['review_text'])) ?></p>

                <?php
                $image = null;
                if (!empty($review['images'])) {
                  $imgs = json_decode($review['images'], true);
                  if (is_array($imgs) && count($imgs) > 0) {
                    $image = 'uploads/reviews/' . $imgs[0];
                  }
                }
                ?>

                <?php if ($image): ?>
                  <div class="mt-2">
                    <img src="  <?= htmlspecialchars($image) ?>"
                      alt="Review Image"
                      class="img-fluid rounded shadow-sm"
                      style="max-width: 150px;width:50px; height:50px; object-fit: cover;">
                  </div>
                <?php endif; ?>

              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="text-center py-4">
              <i class="far fa-comment-dots fa-3x text-muted mb-3"></i>
              <p>No reviews yet. Be the first to review this product!</p>
            </div>
          <?php endif; ?>
        </div>

        <div class="tab-pane fade" id="shipping" role="tabpanel">
          <h5 class="section-title">Shipping Information</h5>
          <div class="row">
            <div class="col-md-6">
              <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                  <h6><i class="fas fa-truck me-2"></i> Shipping Options</h6>
                  <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Standard Shipping: 3-5 business days</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Express Shipping: 1-2 business days (+₦1,500)</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> Free shipping on orders over ₦20,000</li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <div class="card border-0 bg-light mb-3">
                <div class="card-body">
                  <h6><i class="fas fa-undo me-2"></i> Return Policy</h6>
                  <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> 7 days easy return</li>
                    <li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Buyer pays return shipping</li>
                    <li><i class="fas fa-check-circle text-success me-2"></i> Refund will be processed within 3 business days</li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Related Products -->
  <h3 class="section-title mb-4">You May Also Like</h3>
  <div class="row">
    <?php foreach ($related_products as $related): ?>
      <div class="col-md-3 mb-4">
        <div class="card h-100">
          <img src="<?= htmlspecialchars($related['image_url']) ?? "uploads/default-product.png" ?>" class="card-img-top" alt="<?= htmlspecialchars($related['name']) ?>" style="height: 200px; object-fit: contain;">
          <div class="card-body">
            <h6 class="card-title"><?= htmlspecialchars($related['name']) ?></h6>
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <div class="fw-bold text-danger">₦<?= number_format($related['price'], 2) ?></div>
                <?php if (isset($related['original_price']) && $related['original_price'] > $related['price']): ?>
                  <small class="text-muted text-decoration-line-through">₦<?= number_format($related['original_price'], 2) ?></small>
                <?php endif; ?>
              </div>
            </div>
          </div>
          <div class="card-footer bg-white">
            <a href="product_details.php?id=<?= $related['id'] ?>" class="btn btn-sm btn-outline-primary w-100">View Details</a>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
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