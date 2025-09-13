<?php
require '../app/config.php';

// Initialize
$statusMessage = '';
$seller = null;

// Get seller ID from URL
$sellerId = intval($_GET['id'] ?? 0);

if ($sellerId <= 0) {
    $_SESSION['error'] = "Invalid Seller ID.";
    header("Location: edit_seller.php?id=$sellerId");
    exit();
}

// Fetch seller
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'seller'");
$stmt->execute([$sellerId]);
$seller = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$seller) {
    $_SESSION['error'] = "Seller not found.";
    header("Location: admin_sellers.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_seller'])) {
    $name       = trim($_POST['name']);
    $email      = trim($_POST['email']);
    $phone      = trim($_POST['phone']);
    $country    = trim($_POST['country']);
    $address    = trim($_POST['address']);
    $state      = trim($_POST['state']);
    $lga        = trim($_POST['lga']);
    $landmark   = trim($_POST['landmark']);
    $title      = trim($_POST['title']);
    $first_name = trim($_POST['first_name']);
    $last_name  = trim($_POST['last_name']);
    $street     = trim($_POST['street_address']);

    try {
        $stmt = $pdo->prepare("UPDATE users 
            SET name = ?, email = ?, phone = ?, country = ?, address = ?, 
                state = ?, lga = ?, landmark = ?, title = ?, first_name = ?, 
                last_name = ?, street_address = ?
            WHERE id = ? AND role = 'seller'");
        $stmt->execute([
            $name,
            $email,
            $phone,
            $country,
            $address,
            $state,
            $lga,
            $landmark,
            $title,
            $first_name,
            $last_name,
            $street,
            $sellerId
        ]);

        $_SESSION['success'] = "Seller details updated successfully.";
        header("Location: edit_seller.php?id=$sellerId");
        exit();
    } catch (PDOException $e) {
        $statusMessage = "<div class='alert alert-danger'>Error updating seller: " . $e->getMessage() . "</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<?php include 'includes/head.php'; ?>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="admin_sellers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h2>Edit Seller</h2>
        </div>

        <?= $statusMessage ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-warning text-white">
                <h4 class="mb-0">Update Seller Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="edit_seller.php?id=<?= $sellerId ?>">
                    <!-- Same form fields as buyer -->
                    <!-- Just replace $buyer with $seller -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($seller['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($seller['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Repeat all other fields exactly like in buyer -->

                    <div class="text-end">
                        <button type="submit" name="update_seller" class="btn btn-success">Update Seller</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>
</body>
</html>
