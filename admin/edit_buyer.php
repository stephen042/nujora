<?php
require '../app/config.php';

// Initialize
$statusMessage = '';
$buyer = null;

// Get buyer ID from URL
$buyerId = intval($_GET['id'] ?? 0);

if ($buyerId <= 0) {
    $_SESSION['error'] = "Invalid Buyer ID.";
    header("Location: edit_buyer.php?id=$buyerId");
    exit();
}

// Fetch buyer
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'buyer'");
$stmt->execute([$buyerId]);
$buyer = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$buyer) {
    $_SESSION['error'] = "Buyer not found.";
    header("Location: admin_buyer.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_buyer'])) {
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
            WHERE id = ? AND role = 'buyer'");
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
            $buyerId
        ]);

        $_SESSION['success'] = "Buyer details updated successfully.";
        header("Location: edit_buyer.php?id=$buyerId");
        exit();
    } catch (PDOException $e) {
        $statusMessage = "<div class='alert alert-danger'>Error updating buyer: " . $e->getMessage() . "</div>";
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
            <a href="admin_buyers.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h2>Edit Buyer</h2>
        </div>

        <?= $statusMessage ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h4 class="mb-0">Update Buyer Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="edit_buyer.php?id=<?= $buyerId ?>">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                value="<?= htmlspecialchars($buyer['name'] ?? ' ') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                value="<?= htmlspecialchars($buyer['email'] ?? ' ') ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="text" name="phone" class="form-control"
                                value="<?= htmlspecialchars($buyer['phone'] ?? ' ') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Country</label>
                            <input type="text" name="country" class="form-control"
                                value="<?= htmlspecialchars($buyer['country'] ?? ' ') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Street Address</label>
                        <input type="text" name="street_address" class="form-control"
                            value="<?= htmlspecialchars($buyer['street_address'] ?? ' ') ?>">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">State</label>
                            <input type="text" name="state" class="form-control"
                                value="<?= htmlspecialchars($buyer['state'] ?? ' ') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">LGA</label>
                            <input type="text" name="lga" class="form-control"
                                value="<?= htmlspecialchars($buyer['lga'] ?? ' ') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Landmark</label>
                            <input type="text" name="landmark" class="form-control"
                                value="<?= htmlspecialchars($buyer['landmark'] ?? ' ') ?>">
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-2">
                            <label class="form-label">Title</label>
                            <input type="text" name="title" class="form-control"
                                value="<?= htmlspecialchars($buyer['title'] ?? ' ') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">First Name</label>
                            <input type="text" name="first_name" class="form-control"
                                value="<?= htmlspecialchars($buyer['first_name'] ?? ' ') ?>">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">Last Name</label>
                            <input type="text" name="last_name" class="form-control"
                                value="<?= htmlspecialchars($buyer['last_name'] ?? ' ') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Address (General)</label>
                        <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($buyer['address'] ?? ' ') ?></textarea>
                    </div>

                    <div class="text-end">
                        <button type="submit" name="update_buyer" class="btn btn-success">Update Buyer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <?php include 'includes/script.php'; ?>
</body>

</html>