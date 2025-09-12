<?php
require '../app/config.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT name, email, phone, country FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    echo "User not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile | <?= APP_NAME ?></title>

    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&family=Open+Sans&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            padding-bottom: 80px;
            background-color: #f8f9fa;
        }

        .nav-bottom {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            border-top: 1px solid #dee2e6;
            z-index: 999;
        }

        .nav-bottom .nav-link {
            padding: 10px;
            font-size: 13px;
            text-align: center;
        }

        .top-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .top-actions a {
            text-decoration: none;
        }

        .card-title {
            font-size: 1.25rem;
        }

        @media (max-width: 576px) {
            h4 {
                font-size: 1.2rem;
            }

            .card-body p {
                font-size: 0.95rem;
            }

            .top-actions {
                flex-direction: column;
                align-items: flex-end;
            }

            .top-actions a {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php include 'includes/nav.php'; ?>

    <div class="container mt-4 mb-5">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-3">
            <h4 class="mb-2 mb-sm-0 text-primary">Profile</h4>
            <div class="top-actions">
                <a href="edit-profile.php" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-pencil"></i> Edit My Profile
                </a>
            </div>
        </div>
        <hr>

        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title">Welcome, <?= htmlspecialchars($user['name']) ?>!</h5>
                <hr>
                <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
                <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone'] ?? '') ?></p>
                <p><strong>Country:</strong> <?= htmlspecialchars($user['country'] ?? '') ?></p>
            </div>
        </div>
    </div>

    <!-- Bottom Navigation -->
    <?php include 'includes/bottomNav.php'; ?>

    <!-- Scripts -->
    <?php include 'includes/script.php'; ?>
</body>

</html>