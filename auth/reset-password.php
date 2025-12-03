<?php
require '../app/config.php';

if (!isset($_GET['token'])) {
    header("Location: forgot-password.php", true, 302);
    exit;
}

$token = $_GET['token'];

// Check token validity
$stmt = $pdo->prepare("
    SELECT id, reset_expires 
    FROM users 
    WHERE reset_token = ? LIMIT 1
");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || strtotime($user['reset_expires']) < time()) {
    die("Reset link expired or invalid.");
}

// If form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass = $_POST['password'];
    $confirmPass = $_POST['confirm_password'];

    if ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);

        $update = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, reset_token = NULL, reset_expires = NULL
            WHERE id = ?
        ");
        $update->execute([$hashed, $user['id']]);

        $_SESSION['statusMessage'] = "<div class='alert alert-success'>Your password has been reset successfully.</div>";
        header("Location: login.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | <?= APP_NAME ?></title>
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
            <?php if (!empty($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>

            <h4 class="text-center mb-3">Create New Password</h4>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>

                <button class="btn btn-primary w-100">Reset Password</button>
            </form>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>