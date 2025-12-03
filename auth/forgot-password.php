<?php
require '../app/config.php';
require_once '../lib/mail_functions.php'; // Include mail functions

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email']);

  // Check if user exists
  $stmt = $pdo->prepare("SELECT id, name FROM users WHERE email = ? LIMIT 1");
  $stmt->execute([$email]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($user) {

    // Generate secure token
    $token = bin2hex(random_bytes(32));
    $expires = date("Y-m-d H:i:s", strtotime("+10 minutes"));

    // Store token
    $update = $pdo->prepare("
            UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?
        ");
    $update->execute([$token, $expires, $user['id']]);

    // Build reset link
    $resetLink = APP_URL . "/auth/reset-password.php?token=" . $token;

    // Email contents
    $site_name = APP_NAME;
    $subject   = "Reset Your Password";
    $title     = "Reset Your $site_name Password";
    $body      = "We received a request to reset your password. Click the button below to choose a new one. 
    <br><br>
    <strong>Note:</strong> This password reset link will expire in <strong>10 minutes</strong> for security purposes.";
    $btn_text  = "Reset Password";
    $btn_link  = $resetLink;

    // Send email
    send_passwordreset_email($email, $subject, $title, $body, $btn_text, $btn_link);

    $_SESSION['statusMessage'] = "<div class='alert alert-success'>Password reset link has been sent to your email.</div>";
    header("Location: forgot-password.php");
    exit;
  } else {
    $_SESSION['statusMessage'] = "<div class='alert alert-danger'>Email not found in our system.</div>";
    header("Location: forgot-password.php");
    exit;
  }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Forgot Password | <?= APP_NAME ?></title>
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
      padding: 40px;
      border-radius: 10px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
      max-width: 400px;
      width: 400px;
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
    <div class="login-box bg-white p-4 shadow rounded">

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

      <h4 class="text-center mb-3">Forgot Password</h4>

      <form method="POST">
        <div class="mb-3">
          <label for="email" class="form-label">Email Address</label>
          <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Reset Password</button>
      </form>

      <p class="text-center mt-3">
        Go back to <a href="login.php" class="text-primary">Login here</a>
      </p>
    </div>
  </div>


  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>