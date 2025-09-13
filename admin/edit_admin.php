<?php
require '../app/config.php';

// Initialize
$statusMessage = '';
$admin = null;

// Get admin ID from URL
$adminId = intval($_GET['id'] ?? 0);

if ($adminId <= 0) {
    $_SESSION['error'] = "Invalid Admin ID.";
    header("Location: edit_admin.php?id=$adminId");
    exit();
}

// Fetch admin
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$adminId]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$admin) {
    $_SESSION['error'] = "Admin not found.";
    header("Location: admin_admins.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
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
            WHERE id = ? AND role = 'admin'");
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
            $adminId
        ]);

        $_SESSION['success'] = "Admin details updated successfully.";
        header("Location: edit_admin.php?id=$adminId");
        exit();
    } catch (PDOException $e) {
        $statusMessage = "<div class='alert alert-danger'>Error updating admin: " . $e->getMessage() . "</div>";
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
            <a href="all_admins.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <h2>Edit Admin</h2>
        </div>

        <?= $statusMessage ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Update Admin Information</h4>
            </div>
            <div class="card-body">
                <form method="POST" action="edit_admin.php?id=<?= $adminId ?>">
                    <!-- Same form fields as buyer -->
                    <!-- Just replace $buyer with $admin -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control"
                                   value="<?= htmlspecialchars($admin['name'] ?? '') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control"
                                   value="<?= htmlspecialchars($admin['email'] ?? '') ?>" required>
                        </div>
                    </div>

                    <!-- Repeat all other fields exactly like in buyer -->

                    <div class="text-end">
                        <button type="submit" name="update_admin" class="btn btn-success">Update Admin</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/script.php'; ?>
</body>
</html>
