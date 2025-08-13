<?php 
require '../app/config.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $role = trim($_POST['role'] ?? 'buyer');
    $shop_name = trim($_POST['shop_name'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($role === 'seller' && empty($shop_name)) {
        $error = "Shop name is required for sellers.";
    } else {
        // Check for duplicate email
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $emailExists = $stmt->fetchColumn();

        if ($emailExists) {
            $error = "Email already exists.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert user into the database
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO users (name, email, password_hash, role, shop_name, badge_level, rating, created_at, is_approved, approval_status, profile_complete)
                    VALUES (?, ?, ?, ?, ?, 'New', 0.00, NOW(), 0, 'pending', 0)
                ");
                $stmt->execute([$name, $email, $hashed_password, $role, $shop_name]);
                
                $userId = $pdo->lastInsertId();
                
                // Store user ID in session for potential profile completion
                $_SESSION['temp_user_id'] = $userId;
                $_SESSION['temp_user_role'] = $role;
                
                $success = "User registered successfully.";
                
                // For buyers, we'll redirect them to profile completion after approval
                // For now just show success message
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register | Nujora</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <style>
    body {
      background: linear-gradient(to right, #6a11cb, #2575fc);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .register-box {
      background: #fff;
      padding: 30px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 100%;
    }
    .register-box h4 {
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
  <div class="register-box">
    <h4 class="text-center">Create an Account</h4>

    <!-- Display success or error message -->
    <?php if (!empty($success)): ?>
      <div class="alert alert-success text-center"><?= htmlspecialchars($success) ?></div>
      <?php if ($role === 'buyer'): ?>
        <div class="alert alert-info text-center">
          After your account is approved, you'll be asked to complete your profile information.
        </div>
      <?php endif; ?>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
      <div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <div class="mb-3">
        <label for="name" class="form-label">Full Name</label>
        <input type="text" name="name" id="name" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email" name="email" id="email" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="password" class="form-label">Password</label>
        <input type="password" name="password" id="password" class="form-control" required>
      </div>
      <div class="mb-3">
        <label for="role" class="form-label">Register As</label>
        <select id="role" name="role" class="form-select" onchange="toggleShopNameField()" required>
          <option value="buyer">Buyer</option>
          <option value="seller">Seller</option>
        </select>
      </div>
      <!-- Shop Name Field (Hidden by Default) -->
      <div class="mb-3" id="shop-name-field" style="display: none;">
        <label for="shop_name" class="form-label">Shop Name</label>
        <input type="text" name="shop_name" id="shop_name" class="form-control" placeholder="Enter your shop name">
      </div>
      <button type="submit" class="btn btn-primary w-100">Register</button>
    </form>
    <p class="text-center mt-3">
      Already have an account? <a href="login.php" class="text-primary">Login here</a>
    </p>
  </div>

  <script>
    // JavaScript to toggle the Shop Name field
    function toggleShopNameField() {
      const roleSelect = document.getElementById('role');
      const shopNameField = document.getElementById('shop-name-field');
      if (roleSelect.value === 'seller') {
        shopNameField.style.display = 'block';
      } else {
        shopNameField.style.display = 'none';
      }
    }
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>