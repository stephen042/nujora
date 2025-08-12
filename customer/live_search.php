<?php
// Database connection
require '../app/config.php';


// Get the search term
$name = isset($_GET['name']) ? trim($_GET['name']) : '';
if ($name === '') exit;

$sql = "SELECT * FROM products WHERE name LIKE :name AND status = 'In Stock' LIMIT 12";
$stmt = $pdo->prepare($sql);
$stmt->execute(['name' => '%' . $name . '%']);
$products = $stmt->fetchAll();

// Display cards
if (empty($products)) {
  echo '<div class="col-12 text-center"><p>No matching products found.</p></div>';
  exit;
}

foreach ($products as $product): ?>
  <div class="col-6 col-md-4 col-lg-3 my-2 col-sm-6" style="max-height: 450px; max-width: 300px;">
    <div class="card h-100">
      <img src="<?= isset($product['image_url']) && $product['image_url'] ? "../" . htmlspecialchars($product['image_url']) : "../uploads/default-product.png" ?>"
           class="product-img"
           alt="<?= htmlspecialchars($product['name']) ?>">

      <div class="card-body">
        <h5 class="card-title"><?= htmlspecialchars($product['name']) ?></h5>
        <p class="card-text">Price: ₦<?= htmlspecialchars($product['price']) ?></p>

        <?php if ($product['stock'] > 0): ?>
          <span class="product-badge bg-success">In Stock</span>
        <?php else: ?>
          <span class="product-badge bg-danger">Out of Stock</span>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center">
          <a href="product_details.php?id=<?= $product['id'] ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-eye me-1"></i> View
          </a>
          <a href="cart.php?add=<?= $product['id'] ?>" class="btn btn-outline-primary btn-sm">
            <i class="fas fa-cart-plus"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
<?php endforeach; ?>
