<?php
require '../app/config.php';


if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Fetch existing user data
$stmt = $pdo->prepare("SELECT name, email, phone, address FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if ($name && $phone && $address) {
        $update = $pdo->prepare("UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?");
        if ($update->execute([$name, $phone, $address, $user_id])) {
            $success = "Profile updated successfully!";
            // Refresh user data
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
        } else {
            $error = "Failed to update profile.";
        }
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Profile | <?= APP_NAME ?></title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../uploads/default-product.png">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-4 mb-5">
        <h4>Edit Profile</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php elseif ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-control" required value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            <div class="mb-3">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" required><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary w-100">Save Changes</button>
            <a href="profile.php" class="btn btn-secondary mt-2 w-100">Cancel</a>
        </form>
    </div>
</body>

</html>