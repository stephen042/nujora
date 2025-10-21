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

      // Redirect based on role
      if ($user['role'] === 'buyer') {
        header('Location: ../customer/home.php');
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
  <title>Login | Nujora</title>
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