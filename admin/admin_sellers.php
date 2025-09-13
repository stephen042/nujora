<?php
require '../app/config.php';
// Initialize variables
$statusMessage = '';

// Fetch sellers from DB
$stmt = $pdo->query("SELECT * FROM users WHERE role = 'seller' ORDER BY created_at DESC");
$sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_seller']) || isset($_POST['reject_seller'])) {
    $sellerId = intval($_POST['seller_id'] ?? 0);

    if ($sellerId > 0) {
        try {
            if (isset($_POST['approve_seller'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', is_approved = 1 WHERE id = ? AND role = 'seller'");
                $stmt->execute([$sellerId]);
                $_SESSION['success'] = "Seller approved successfully.";
            } elseif (isset($_POST['reject_seller'])) {
                $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', is_approved = 0 WHERE id = ? AND role = 'seller'");
                $stmt->execute([$sellerId]);
                $_SESSION['success'] = "Seller rejected successfully.";
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error updating seller: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = "Invalid seller ID.";
    }

    // Redirect back to same page (like Laravel back())
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_seller'])) {
    $deleteId = intval($_POST['delete_id']);

    try {
        // Check if seller exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'seller'");
        $stmt->execute([$deleteId]);
        $seller = $stmt->fetch();

        if (!$seller) {
            $_SESSION['error'] = "Seller not found.";
        } else {
            // Delete seller
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$deleteId]);

            $_SESSION['success'] = "Seller deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error deleting seller: " . $e->getMessage();
    }

    // Refresh same page (like Laravel back())
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
            <h2>Admin Dashboard | Sellers</h2>
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
            <div class="card-header bg-warning text-white">
                <h4 class="mb-0">Sellers List</h4>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php
                    $i = 1; // initialize counter
                    ?>
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
                            <?php foreach ($sellers as $seller): ?>
                                <tr>
                                    <td><?= $i++ ?></td>
                                    <td><?= htmlspecialchars($seller['name']) ?></td>
                                    <td><?= htmlspecialchars($seller['email']) ?></td>
                                    <td><?= htmlspecialchars($seller['phone'] ?? 'N/A') ?></td>
                                    <td><?= date("M d, Y", strtotime($seller['created_at'])) ?></td>
                                    <td>
                                        <?php
                                        if ($seller['approval_status'] == 'pending') {
                                            echo '<span class="badge bg-warning">Pending</span>';
                                        } elseif ($seller['approval_status'] == 'approved') {
                                            echo '<span class="badge bg-success">Approved</span>';
                                        } elseif ($seller['approval_status'] == 'rejected') {
                                            echo '<span class="badge bg-danger">Rejected</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit_seller.php?id=<?= urlencode($seller['id']) ?>" class="btn btn-sm btn-warning">Edit</a>

                                        <?php if ($seller['approval_status'] == 'pending'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="seller_id" value="<?= htmlspecialchars($seller['id']) ?>">
                                                <button type="submit" name="approve_seller" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this seller?')">Approve</button>
                                            </form>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="seller_id" value="<?= htmlspecialchars($seller['id']) ?>">
                                                <button type="submit" name="reject_seller" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Reject this seller?')">Reject</button>
                                            </form>
                                        <?php elseif ($seller['approval_status'] == 'approved'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="seller_id" value="<?= htmlspecialchars($seller['id']) ?>">
                                                <button type="submit" name="reject_seller" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Reject this seller?')">Reject</button>
                                            </form>
                                        <?php elseif ($seller['approval_status'] == 'rejected'): ?>
                                            <form method="POST" action="" style="display:inline;">
                                                <input type="hidden" name="seller_id" value="<?= htmlspecialchars($seller['id']) ?>">
                                                <button type="submit" name="approve_seller" class="btn btn-sm btn-success"
                                                    onclick="return confirm('Approve this seller?')">Approve</button>
                                            </form>
                                        <?php endif; ?>


                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="delete_id" value="<?= htmlspecialchars($seller['id']) ?>">
                                            <button type="submit" name="delete_seller" class="btn btn-sm btn-outline-danger"
                                                onclick="return confirm('Are you sure you want to delete this seller?')">
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