<?php
require '../app/config.php';
// Initialize variables
$statusMessage = '';

// Fetch admins from DB
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'admin' ORDER BY created_at DESC");
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Approve / Reject Admin (if applicable, though usually admins are created by superadmin)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['approve_admin']) || isset($_POST['reject_admin']))) {
    $adminId = intval($_POST['admin_id'] ?? 0);

    if ($adminId > 0) {
        try {
            if (isset($_POST['approve_admin'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', is_approved = 1 WHERE id = ? AND role = 'admin'");
                $stmt->execute([$adminId]);
                $_SESSION['success'] = "Admin approved successfully.";
            } elseif (isset($_POST['reject_admin'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', is_approved = 0 WHERE id = ? AND role = 'admin'");
                $stmt->execute([$adminId]);
                $_SESSION['success'] = "Admin rejected successfully.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating admin: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid admin ID.";
    }

    // Redirect back to same page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Delete Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_admin'])) {
    $deleteId = intval($_POST['delete_id']);

    try {
        // Check if admin exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'admin'");
        $stmt->execute([$deleteId]);
        $admin = $stmt->fetch();

        if (!$admin) {
            $_SESSION['error'] = "Admin not found.";
        } else {
            // Delete admin
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);

            $_SESSION['success'] = "Admin deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting admin: " . $e->getMessage();
    }

    // Refresh page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<!-- head tag -->
<?php include 'includes/head.php'; ?>

<body>
    <!-- Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <button class="btn btn-outline-secondary d-md-none" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h2>Admin Dashboard | Admins</h2>
        </div>

        <?php echo $statusMessage; ?>
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
                                                unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'];
                                            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-dark text-white">
                <h4 class="mb-0">Admins List</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php $i = 1; ?>
                    <table class="table table-striped table-bordered align-middle dataTable">
                        <thead class="table-dark">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Joined</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($admins as $admin): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($admin['name']) ?></td>
                                    <td><?= htmlspecialchars($admin['email']) ?></td>
                                    <td><?= htmlspecialchars($admin['phone'] ?? 'N/A') ?></td>
                                    <td><?= date("M d, Y", strtotime($admin['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        if ($admin['approval_status'] == 'pending') {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        } elseif ($admin['approval_status'] == 'approved') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        } elseif ($admin['approval_status'] == 'rejected') {
                                            echo '<span class="badge bg-danger">Rejected</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_admin.php?id=<?= urlencode($admin['id']) ?>" class="btn btn-sm btn-warning">Edit</a>

                                        <?php if ($admin['approval_status'] == 'pending'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                                <button type="submit" name="approve_admin" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this admin?')">Approve</button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                                <button type="submit" name="reject_admin" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Reject this admin?')">Reject</button>
                                            </form>
                                        <?php elseif ($admin['approval_status'] == 'approved'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                                <button type="submit" name="reject_admin" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Reject this admin?')">Reject</button>
                                            </form>
                                        <?php elseif ($admin['approval_status'] == 'rejected'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="admin_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                                <button type="submit" name="approve_admin" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this admin?')">Approve</button>
                                            </form>
                                        <?php endif; ?>

                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($admin['id']) ?>">
                                            <button type="submit" name="delete_admin" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this admin?')">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>

    </div>
    <?php include 'includes/script.php'; ?>

</body>
</html>
