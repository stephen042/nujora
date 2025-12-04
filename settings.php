<?php
require 'app/config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$success = "";
$error = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // 1. Fetch user password from DB
    $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $error = "User not found.";
    } else {

        // 2. Check if old password matches stored hashed password
        if (!password_verify($old_password, $user['password_hash'])) {
            $error = "Old password is incorrect.";
        }
        // 3. Check if new passwords match
        elseif ($new_password !== $confirm_password) {
            $error = "New password and confirmation do not match.";
        }
        // 4. Validate password length
        elseif (strlen($new_password) < 6) {
            $error = "New password must be at least 6 characters.";
        } else {
            // 5. Hash new password
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);

            // 6. Update DB
            $update = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($update->execute([$new_hash, $user_id])) {
                $success = "Password successfully updated.";
            } else {
                $error = "Something went wrong. Please try again.";
            }
        }
    }
}
?>


<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= APP_NAME ?></title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="uploads/default-product.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4 mb-5">
        <h4>Change password</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Old Password</label>
                <input type="password" name="old_password" class="form-control" required value="">
            </div>
            <div class="mb-3">
                <label class="form-label">New Password</label>
                <input type="password" name="new_password" class="form-control" required value="">
            </div>
            <div class="mb-3">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required value="">
            </div>

            <div class="d-flex justify-content-between">
                <button type="submit" class="btn btn-primary w-25">Changes password</button>
                <a href="javascript:history.back()" class="btn btn-secondary w-25">Back</a>
            </div>
        </form>
    </div>
    <script>
        function togglePassword() {
            const passwordInput = document.getElementById("password");
            const eyeOpen = document.getElementById("eyeOpen");
            const eyeClosed = document.getElementById("eyeClosed");

            if (passwordInput.type === "password") {
                passwordInput.type = "text"; // Show password
                eyeOpen.style.display = "none"; // Hide open eye
                eyeClosed.style.display = "inline"; // Show closed eye
            } else {
                passwordInput.type = "password";
                eyeOpen.style.display = "inline"; // Show open eye
                eyeClosed.style.display = "none"; // Hide closed eye
            }
        }
    </script>
</body>

</html>