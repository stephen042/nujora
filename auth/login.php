<?php
require '../app/config.php';

$statusMessage = ''; // Initialize the status message

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = isset($_POST['email']) ? $_POST['email'] : null;
  $password = isset($_POST['password']) ? $_POST['password'] : null;

  try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['role'] = $user['role'];

      // Merge guest cart into database
      if (isset($_SESSION['guest_cart']) && !empty($_SESSION['guest_cart'])) {

        foreach ($_SESSION['guest_cart'] as $key => $item) {

          // Extract proper values
          $product_id = intval($item['product_id']);
          $quantity   = intval($item['quantity']);
          $variant_id = $item['variant_id'] ?: null;

          // Check if already in user's cart
          $stmt = $pdo->prepare("
                  SELECT id, quantity 
                  FROM cart_items 
                  WHERE buyer_id = ? AND product_id = ? AND variant_id <=> ?
              ");
          $stmt->execute([$user['id'], $product_id, $variant_id]);
          $existing = $stmt->fetch(PDO::FETCH_ASSOC);

          if ($existing) {
            // Update quantity
            $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE id = ?");
            $stmt->execute([$quantity, $existing['id']]);
          } else {
            // Insert new cart row
            $stmt = $pdo->prepare("
                      INSERT INTO cart_items (buyer_id, product_id, variant_id, quantity)
                      VALUES (?, ?, ?, ?)
                  ");
            $stmt->execute([$user['id'], $product_id, $variant_id, $quantity]);
          }
        }

        // Clear guest cart
        unset($_SESSION['guest_cart']);
      }

      if (isset($_SESSION['pending_checkout'])) {
        // Merge guest cart into user cart
        if (!empty($_SESSION['pending_checkout']['cart'])) {

          $stmt = $pdo->prepare("
            INSERT INTO cart_items (buyer_id, product_id, quantity)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)
        ");

          foreach ($_SESSION['pending_checkout']['cart'] as $product_id => $qty) {

            // Force INT type to avoid SQLSTATE 1265 errors
            $product_id = intval($product_id);
            $qty        = intval($qty);

            // Skip invalid items
            if ($product_id <= 0 || $qty <= 0) {
              continue;
            }

            // Insert/merge into DB
            $stmt->execute([$_SESSION['user_id'], $product_id, $qty]);
          }
        }

        // Clear guest pending checkout data
        unset($_SESSION['pending_checkout']);

        // Redirect to continue checkout
        header("Location: ../checkout.php?continue=true");
        exit;
      }

      // Redirect based on role
      if ($user['role'] === 'buyer') {
        header('Location: ../index.php');
        exit;
      } elseif ($user['role'] === 'seller') {
        header('Location: ../seller/seller-dashboard.php');
        exit;
      } elseif ($user['role'] === 'admin') {
        header('Location: ../admin/admin-dashboard.php');
        exit;
      }
    } else {
      $statusMessage = '<div class="alert alert-danger text-center">Invalid credentials</div>';
    }
  } catch (PDOException $e) {
    die("Error: " . $e->getMessage());
  }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login | <?= APP_NAME ?></title>
  <link rel="icon" type="image/png" href="../uploads/default-product.png">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .login-box {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 100%;
    }

    .login-box h4 {
      margin-bottom: 20px;
      font-weight: bold;
      color: #333;
    }

    .form-control:focus {
      box-shadow: 0 0 5px rgba(38, 143, 255, 0.5);
      border-color: #268fff;
    }

    .btn-primary {
      background: #6a11cb;
      border: none;
    }

    .btn-primary:hover {
      background: #2575fc;
    }
  </style>
</head>

<body>
  <div class="d-flex justify-content-center align-items-center">
    <div class="login-box bg-white p-4 shadow rounded" style="width: 100%; max-width: 400px;">

      <!-- Centered Logo -->
      <div class="text-center">
        <?php include '../app/logo.php'; ?>
      </div>
      <hr>
      <!-- Display status messages -->
      <?php if (!empty($_SESSION['statusMessage'])) {
        echo $_SESSION['statusMessage'];
        unset($_SESSION['statusMessage']);
      } ?>
      <?php echo $statusMessage ?>

      <h4 class="text-center mb-3">Login to Your Account</h4>

      <form method="POST" action="login.php">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>
        <div class="mb-3 text-end">
          <a href="forgot-password.php" class="text-muted small">Forgot Password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100">Login</button>
      </form>

      <p class="text-center mt-3">
        Don't have an account? <a href="register.php" class="text-primary">Register here</a>
      </p>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>